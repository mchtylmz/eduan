<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\Language;
use App\Models\Season;
use App\Models\Test;
use App\Models\TestsResult;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ResultSeasonFilterTest extends TestCase
{
    use DatabaseTransactions;

    public static function resultTypes(): array
    {
        $cases = [];
        foreach ([['exams', Exam::class, ExamResult::class, 'exam_id'], ['tests', Test::class, TestsResult::class, 'test_id']] as $type) {
            foreach ([false, true] as $teacher) {
                $cases[$type[0].($teacher ? '-teacher' : '-admin')] = [...$type, $teacher];
            }
        }

        return $cases;
    }

    #[DataProvider('resultTypes')]
    public function test_no_season_includes_null_and_zero_without_leaking_other_filters_or_teacher_scope(string $resource, string $model, string $resultModel, string $foreignKey, bool $teacher): void
    {
        Language::firstOrCreate(['code' => 'nl'], ['name' => 'Nederlands', 'status' => 'active']);
        $actor = User::factory()->create();
        foreach (['dashboard:access', $resource.':view', $teacher ? 'user-type:teacher' : 'user-type:admin'] as $permission) {
            $actor->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
        $this->actingAs($actor);
        $student = User::factory()->create();
        $outsider = User::factory()->create();
        $actor->students()->attach($student);
        $item = $model::factory()->create(['locale' => 'nl', 'created_by' => $actor->id]);
        $otherItem = $model::factory()->create(['locale' => 'nl', 'created_by' => $actor->id]);
        $season = Season::create(['name' => 'Season filter regression', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 'active']);
        $make = fn ($seasonId, $userId = null, $itemId = null, $completed = 1) => $resultModel::create([
            $foreignKey => $itemId ?? $item->id, 'user_id' => $userId ?? $student->id,
            'season_id' => $seasonId, 'completed' => $completed,
        ]);
        $null = $make(null);
        $zero = $make(0);
        $assigned = $make($season->id);
        $foreign = $make(null, $outsider->id);
        $make(null, itemId: $otherItem->id);
        $make(null, completed: 0);
        $base = [$foreignKey => $item->id, 'user_id' => $student->id, 'completed' => 1];
        $url = 'admin.'.$resource.'.getResultsData';
        foreach ([[], ['season_id' => ''], ['season_id' => 0], ['season_id' => $season->id]] as $filter) {
            $expected = match (true) {
                ! array_key_exists('season_id', $filter), $filter['season_id'] === '' => [$null->id, $zero->id, $assigned->id],
                $filter['season_id'] === 0 => [$null->id, $zero->id],
                default => [$assigned->id],
            };
            $response = $this->getJson(route($url, [...$base, ...$filter]))->assertOk()->assertJsonPath('total', count($expected));
            $this->assertEqualsCanonicalizing($expected, array_column($response->json('rows'), 'id'));
        }
        $scope = $this->getJson(route($url, [$foreignKey => $item->id, 'season_id' => 0, 'completed' => 1]))->assertOk();
        $this->assertEqualsCanonicalizing($teacher ? [$null->id, $zero->id] : [$null->id, $zero->id, $foreign->id], array_column($scope->json('rows'), 'id'));
        $this->getJson(route($url, [...$base, 'season_id' => 0, 'limit' => 1, 'offset' => 1]))->assertOk()->assertJsonPath('total', 2)->assertJsonCount(1, 'rows');
        foreach ([-1, ['bad']] as $invalid) {
            $this->getJson(route($url, [...$base, 'season_id' => $invalid]))->assertUnprocessable();
        }
        $html = $this->get(route('admin.'.$resource.'.results'))->assertOk()->getContent();
        $this->assertSame(1, preg_match('/<select\b[^>]*id="result-season"[^>]*>(.*?)<\/select>/s', $html, $select));
        $this->assertStringContainsString('<option value="">', $select[1]);
        $this->assertDoesNotMatchRegularExpression('/<option\b[^>]*\bselected\b/', $select[1]);
        $actor->revokePermissionTo($resource.':view');
        $this->getJson(route($url, [...$base, 'season_id' => 0]))->assertForbidden();
    }
}
