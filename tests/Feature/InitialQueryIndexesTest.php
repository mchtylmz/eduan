<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class InitialQueryIndexesTest extends TestCase
{
    public function test_indexes_preserve_records_and_rollback_restores_existing_indexes(): void
    {
        $indexes = [
            'users' => ['users_username_index', ['username']],
            'exam_results' => ['exam_results_season_completed_updated_index', ['season_id', 'completed', 'updated_at']],
            'exam_result_details' => ['exam_result_details_result_question_index', ['exam_result_id', 'question_id']],
        ];

        $before = [];
        foreach ($indexes as $table => [$name, $columns]) {
            $before[$table] = Schema::getIndexes($table);
            $existing = collect($before[$table])->firstWhere('name', $name);
            if ($existing) {
                $this->assertSame($columns, $existing['columns']);
                $this->assertFalse($existing['unique']);
            }
        }
        $checksums = DB::select('CHECKSUM TABLE users, exam_results, exam_result_details EXTENDED');
        $results = $this->queryResults();
        $migration = require database_path('migrations/2026_10_05_150000_add_initial_query_performance_indexes.php');

        try {
            $baseline = [];
            foreach ($indexes as $table => [$name, $columns]) {
                if (Schema::hasIndex($table, $name)) {
                    Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropIndex($name));
                }
                $baseline[$table] = Schema::getIndexes($table);
            }
            $migration->up();

            foreach ($indexes as $table => [$name, $columns]) {
                $index = collect(Schema::getIndexes($table))->firstWhere('name', $name);
                $this->assertNotNull($index);
                $this->assertSame($columns, $index['columns']);
                $this->assertFalse($index['unique']);
                $this->assertFalse($index['primary']);
                foreach ($before[$table] as $existing) {
                    $this->assertContains($existing, Schema::getIndexes($table));
                }
            }
            $this->assertEquals($checksums, DB::select('CHECKSUM TABLE users, exam_results, exam_result_details EXTENDED'));
            $this->assertEquals($results, $this->queryResults());

            $migration->down();

            foreach ($baseline as $table => $existing) {
                $this->assertSame($existing, Schema::getIndexes($table));
            }
            $this->assertEquals($checksums, DB::select('CHECKSUM TABLE users, exam_results, exam_result_details EXTENDED'));
        } finally {
            // Restore the starting schema even when the migration was already applied.
            foreach ($indexes as $table => [$name, $columns]) {
                $originallyPresent = collect($before[$table])->contains('name', $name);
                $present = Schema::hasIndex($table, $name);
                if ($originallyPresent && ! $present) {
                    Schema::table($table, fn (Blueprint $blueprint) => $blueprint->index($columns, $name));
                } elseif (! $originallyPresent && $present) {
                    Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropIndex($name));
                }
            }
        }

        foreach ($before as $table => $existing) {
            $this->assertSame($existing, Schema::getIndexes($table));
        }
    }

    public function test_validation_also_preserves_a_schema_with_previously_applied_indexes(): void
    {
        $migration = require database_path('migrations/2026_10_05_150000_add_initial_query_performance_indexes.php');
        $simulate = ! Schema::hasIndex('users', 'users_username_index')
            && ! Schema::hasIndex('exam_results', 'exam_results_season_completed_updated_index')
            && ! Schema::hasIndex('exam_result_details', 'exam_result_details_result_question_index');

        try {
            if ($simulate) {
                $migration->up();
            }
            $this->test_indexes_preserve_records_and_rollback_restores_existing_indexes();
        } finally {
            if ($simulate) {
                $migration->down();
            }
        }
    }

    private function queryResults(): array
    {
        return [
            DB::table('users')->where('username', '__codex_index_lookup_missing__')->whereNull('deleted_at')->count(),
            DB::table('exam_results')->where('season_id', 1)->where('completed', 1)
                ->orderByDesc('updated_at')->orderByDesc('id')->limit(15)->pluck('id')->all(),
            DB::table('exam_result_details')->where('exam_result_id', 1)->where('question_id', 1)
                ->orderBy('id')->pluck('id')->all(),
        ];
    }
}
