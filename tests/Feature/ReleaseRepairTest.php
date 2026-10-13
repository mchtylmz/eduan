<?php

namespace Tests\Feature;

use App\Enums\StatusEnum;
use App\Helpers\TranslationScanner;
use App\Models\Language;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReleaseRepairTest extends TestCase
{
    use DatabaseTransactions;

    public function test_teacher_migration_is_repeatable_and_preserves_unrelated_roles_and_grants(): void
    {
        Permission::findOrCreate('user-type:teacher', 'web');
        Permission::findOrCreate('exams:view', 'web');
        Permission::findOrCreate('tests:view', 'web');
        Permission::findOrCreate('users:view', 'web');
        $teacher = Role::create(['name' => 'Teacher repair '.fake()->uuid(), 'guard_name' => 'web']);
        $teacher->givePermissionTo(['user-type:teacher', 'exams:view', 'users:view']);
        $other = Role::create(['name' => 'Other repair '.fake()->uuid(), 'guard_name' => 'web']);
        $before = DB::table('model_has_permissions')->count();
        $migration = require database_path('migrations/2026_10_07_160000_grant_teacher_result_view_permissions.php');
        $migration->up();
        $count = DB::table('role_has_permissions')->where('role_id', $teacher->id)->count();
        $migration->up();
        $this->assertSame($count, DB::table('role_has_permissions')->where('role_id', $teacher->id)->count());
        $this->assertEqualsCanonicalizing(['user-type:teacher', 'exams:view', 'tests:view', 'users:view'], $teacher->fresh()->permissions->pluck('name')->all());
        $this->assertFalse($other->fresh()->hasPermissionTo('exams:view'));
        $this->assertFalse($other->fresh()->hasPermissionTo('tests:view'));
        $this->assertSame($before, DB::table('model_has_permissions')->count());
    }

    public function test_dutch_fill_preserves_filled_values_other_locales_and_language_states(): void
    {
        Language::firstOrCreate(['code' => 'nl'], ['name' => 'Nederlands', 'status' => StatusEnum::ACTIVE]);
        $languages = DB::table('languages')->orderBy('id')->get()->toJson();
        $prefix = 'nl-repair-'.fake()->uuid();
        $old = lang_path();
        $root = sys_get_temp_dir().'/eduan-nl-repair-'.bin2hex(random_bytes(8));
        File::ensureDirectoryExists($root.'/nl');
        $defaults = [$prefix.'filled' => 'Default', $prefix.'empty' => 'Leeg aangevuld', $prefix.'null' => 'Null aangevuld', $prefix.'missing' => 'Nieuw'];
        File::put($root.'/nl.json', json_encode($defaults));
        File::put($root.'/nl/demo.php', "<?php return ['label' => 'Groeptekst'];");
        DB::table('translations')->insert([
            ['locale' => 'nl', 'key' => $prefix.'filled', 'value' => 'Custom translation'],
            ['locale' => 'nl', 'key' => $prefix.'empty', 'value' => '  '],
            ['locale' => 'nl', 'key' => $prefix.'null', 'value' => null],
            ['locale' => 'tr', 'key' => $prefix.'empty', 'value' => 'Türkçe korunur'],
        ]);
        try {
            app()->useLangPath($root);
            $migration = require database_path('migrations/2026_10_07_160100_fill_missing_dutch_translations.php');
            $migration->up();
            $count = DB::table('translations')->count();
            $migration->up();
            $this->assertSame($count, DB::table('translations')->count());
            foreach ([$prefix.'filled' => 'Custom translation', $prefix.'empty' => 'Leeg aangevuld', $prefix.'null' => 'Null aangevuld', $prefix.'missing' => 'Nieuw'] as $key => $value) {
                $this->assertSame($value, DB::table('translations')->where('locale', 'nl')->where('key', $key)->value('value'));
            }
            $this->assertSame('Groeptekst', DB::table('translations')->where('locale', 'nl')->where('key', 'demo.label')->value('value'));
            $this->assertSame('Türkçe korunur', DB::table('translations')->where('locale', 'tr')->where('key', $prefix.'empty')->value('value'));
            $this->assertSame($languages, DB::table('languages')->orderBy('id')->get()->toJson());
        } finally {
            app()->useLangPath($old);
            File::deleteDirectory($root);
        }
    }

    public function test_dutch_files_cover_all_scanned_static_source_keys(): void
    {
        $json = json_decode(File::get(lang_path('nl.json')), true, 512, JSON_THROW_ON_ERROR);
        $php = [];
        $missing = [];
        foreach (collect((new TranslationScanner)->preview()['sources'])->whereNotNull('key')->unique('key') as $source) {
            if ($source['kind'] === 'php') {
                $group = $source['group'];
                $php[$group] ??= is_file(lang_path('nl/'.$group.'.php')) ? require lang_path('nl/'.$group.'.php') : [];
                $value = Arr::get($php[$group], substr($source['key'], strlen($group) + 1));
            } else {
                if (is_file(lang_path('nl/'.$source['key'].'.php'))) {
                    $this->assertIsArray(__($source['key'], [], 'nl'));

                    continue;
                }
                $value = $json[$source['key']] ?? null;
            }
            if (! is_string($value) || blank($value)) {
                $missing[] = $source['key'];
            }
        }
        $this->assertSame([], $missing);
    }
}
