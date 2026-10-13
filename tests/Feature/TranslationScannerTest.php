<?php

namespace Tests\Feature;

use App\Enums\StatusEnum;
use App\Helpers\TranslationHelper;
use App\Helpers\TranslationScanner;
use App\Livewire\Languages\LanguageTranslateForm;
use App\Models\Language;
use App\Models\Translation;
use App\Models\TranslationSource;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class TranslationScannerTest extends TestCase
{
    use DatabaseTransactions;

    private User $actor;

    private Language $language;

    protected function setUp(): void
    {
        parent::setUp();
        $this->language = Language::firstOrCreate(['code' => 'nl'], ['name' => 'Nederlands', 'status' => StatusEnum::ACTIVE]);
        $this->actor = User::factory()->create();
        foreach (['dashboard:access', 'languages:translate'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        $this->actor->givePermissionTo(['dashboard:access', 'languages:translate']);
        $this->actingAs($this->actor);
        config(['cache.stores.frontend.driver' => 'array']);
    }

    public function test_parser_handles_literals_escapes_multiline_plural_and_dynamic_without_evaluating(): void
    {
        $source = <<<'SOURCE'
<?php
// __('ignore comment')
$example = "__('ignore string')";
__('İletişim');
trans(
    'Ali\'nin :name metni', ['name' => 'Ali']
);
trans_choice('Bir|Çok :count', 2);
\Illuminate\Support\Facades\Lang::get("Satır\nYeni");
Lang::choice('Diğer :count', 2);
__($dynamic);
__('prefix'.$dynamic);
$object->trans('not global');
SOURCE;
        $calls = (new TranslationScanner)->calls($source);
        $this->assertSame(['İletişim', "Ali'nin :name metni", 'Bir|Çok :count', "Satır\nYeni", 'Diğer :count', null, null], array_column($calls, 'key'));
        $this->assertSame(5, $calls[1]['line']);
        $blade = <<<'BLADE'
{{-- __('ignore Blade comment') --}}
<!-- @lang('ignore HTML comment') -->
<div>{{ __('İletişim') }}</div>
@lang('Başlık')
@choice('Bir|Çok :count', 2)
{{ __('account.title') }}
{{ __($key) }}
<span>__('not PHP')</span>
BLADE;
        $calls = (new TranslationScanner)->calls($blade, true);
        $this->assertSame(['İletişim', 'Başlık', 'Bir|Çok :count', 'account.title', null], array_column($calls, 'key'));
        $this->assertSame(3, $calls[0]['line']);
    }

    public function test_real_preview_maps_frontend_blade_livewire_routes_and_excludes_vendor(): void
    {
        $preview = (new TranslationScanner)->preview();
        $this->assertGreaterThan(1000, $preview['keys']);
        $this->assertGreaterThan(0, $preview['dynamic']);
        $rows = collect($preview['sources']);
        $this->assertFalse($rows->contains(fn ($row) => str_contains($row['file'], '/vendor/')));
        $contact = $rows->first(fn ($row) => $row['key'] === 'İletişim' && $row['file'] === 'resources/views/frontend/layouts/section/main-menu.blade.php');
        $this->assertNotNull($contact);
        $this->assertContains('frontend.contact', $contact['routes']);
        $phone = $rows->firstWhere('key', 'account.stats');
        $this->assertSame('php', $phone['kind']);
        $this->assertSame('İstatistikler', $phone['original']);
        $form = $rows->first(fn ($row) => $row['file'] === 'resources/views/livewire/backend/languages/language-translate-form.blade.php' && $row['key'] === 'translation_manager.scan');
        $this->assertContains('admin.languages.translate', $form['routes']);
        $home = $rows->first(fn ($row) => $row['key'] === 'Anasayfa' && $row['file'] === 'app/Http/Controllers/Frontend/HomeController.php');
        $this->assertSame(['frontend.home'], $home['routes']);
    }

    public function test_repeat_scan_preserves_values_and_multiple_usages_and_marks_only_old_sources_inactive(): void
    {
        $key = 'scan-'.fake()->uuid();
        Translation::create(['locale' => 'nl', 'key' => $key, 'value' => 'Keep me']);
        $otherKey = 'old-'.fake()->uuid();
        Translation::create(['locale' => 'nl', 'key' => $otherKey, 'value' => 'Keep legacy']);
        $sources = [];
        foreach (['one', 'two', 'three'] as $page) {
            $sources[] = ['identity' => hash('sha256', $key.$page), 'key' => $key, 'kind' => 'json', 'group' => null, 'original' => $key, 'file' => 'resources/views/frontend/'.$page.'.blade.php', 'line' => 8, 'surface' => 'frontend', 'routes' => ['frontend.'.$page], 'call' => '__'];
        }
        $sources[] = ['identity' => hash('sha256', $key.'php'), 'key' => 'account.title', 'kind' => 'php', 'group' => 'account', 'original' => 'Hesabım', 'file' => 'lang/tr/account.php', 'line' => 1, 'surface' => 'shared', 'routes' => [], 'call' => 'definition'];
        $preview = ['sources' => $sources, 'keys' => 2, 'files' => 3, 'dynamic' => 0];
        $scanner = new TranslationScanner;
        $scanner->apply($preview);
        $count = Translation::count();
        $repeat = $scanner->apply($preview);
        $this->assertSame(0, $repeat['added']);
        $this->assertSame($count, Translation::count());
        $this->assertSame('Keep me', Translation::where('key', $key)->where('locale', 'nl')->value('value'));
        $this->assertSame('Keep legacy', Translation::where('key', $otherKey)->where('locale', 'nl')->value('value'));
        $this->assertSame(3, TranslationSource::where('key', $key)->where('active', true)->count());
        $this->assertSame('Mijn account', Translation::where('key', 'account.title')->where('locale', 'nl')->value('value'));
        $scanner->apply([...$preview, 'sources' => array_slice($sources, 0, 1)]);
        $this->assertSame(1, TranslationSource::where('key', $key)->where('active', true)->count());
        $this->assertSame(3, TranslationSource::where('key', $key)->count());
        $this->assertSame($count, Translation::count());
    }

    public function test_editing_is_scoped_to_language_permission_and_placeholder_names(): void
    {
        $key = 'Hello :name '.fake()->uuid();
        $row = Translation::create(['locale' => 'nl', 'key' => $key, 'value' => 'Hallo :name']);
        $foreign = Translation::create(['locale' => 'fr', 'key' => $key, 'value' => 'Bonjour :name']);
        $component = Livewire::test(LanguageTranslateForm::class, ['languageId' => $this->language->id])->set('searchOriginal', $key);
        $this->assertCount(1, $component->get('translations'));
        $component->set('translations.'.$row->id.'.value', 'Missing placeholder')->assertHasErrors();
        $this->assertSame('Hallo :name', $row->fresh()->value);
        $component->set('translations.'.$row->id.'.value', 'Welcome :name')->assertHasNoErrors();
        $this->assertSame('Welcome :name', $row->fresh()->value);
        $component->set('translations.'.$foreign->id.'.value', 'Wrong language :name')->assertNotFound();
        $this->assertSame('Bonjour :name', $foreign->fresh()->value);
        $component = Livewire::test(LanguageTranslateForm::class, ['languageId' => $this->language->id]);
        $component->call('applyScan')->assertHasErrors('scan');
        $this->actor->revokePermissionTo('languages:translate');
        $component->call('previewScan')->assertForbidden();
        Livewire::test(LanguageTranslateForm::class, ['languageId' => $this->language->id])->assertForbidden();
    }

    public function test_source_filters_original_search_pagination_and_snapshot_use_only_visible_rows(): void
    {
        $prefix = 'paging-'.fake()->uuid();
        for ($i = 0; $i < 41; $i++) {
            $row = Translation::create(['locale' => 'nl', 'key' => $prefix.'-'.sprintf('%02d', $i), 'value' => $i === 40 ? 'Filled' : '']);
            TranslationSource::create(['identity' => hash('sha256', $row->key), 'key' => $row->key, 'kind' => 'php', 'group' => 'demo', 'original' => 'Türkçe kaynak '.$i, 'file' => 'resources/views/frontend/contact.blade.php', 'line' => $i + 1, 'surface' => 'frontend', 'routes' => ['frontend.contact'], 'call' => '__', 'active' => true, 'scan' => str_repeat('a', 32)]);
        }
        $component = Livewire::test(LanguageTranslateForm::class, ['languageId' => $this->language->id])->set('searchOriginal', $prefix);
        $this->assertCount(40, $component->get('translations'));
        $component->call('setPage', 2);
        $this->assertCount(1, $component->get('translations'));
        $component->set('completion', 'filled');
        $this->assertSame(1, $component->get('paginators.page'));
        $this->assertCount(1, $component->get('translations'));
        $component->set('searchOriginal', 'Türkçe kaynak')->set('surface', 'frontend')->set('group', 'demo')->set('sourceSearch', 'frontend.contact');
        $this->assertSame('Filled', array_values($component->get('translations'))[0]['value']);
        $component->set('surface', 'backend')->assertSee(__('translation_manager.no_results'));
    }

    public function test_preview_is_private_then_apply_is_idempotent_and_consumes_preview(): void
    {
        $key = 'preview-'.fake()->uuid();
        $preview = ['sources' => [[
            'identity' => hash('sha256', $key), 'key' => $key, 'kind' => 'json', 'group' => null,
            'original' => $key, 'file' => 'resources/views/frontend/contact.blade.php', 'line' => 4,
            'surface' => 'frontend', 'routes' => ['frontend.contact'], 'call' => '__',
        ]], 'keys' => 1, 'files' => 1, 'dynamic' => 0];
        // Initial catalog fallback and the explicit refresh are both read-only scans.
        $this->partialMock(TranslationScanner::class)->shouldReceive('preview')->twice()->andReturn($preview);
        $component = Livewire::test(LanguageTranslateForm::class, ['languageId' => $this->language->id]);
        $component->call('previewScan')->assertHasNoErrors();
        $this->assertArrayNotHasKey('sources', $component->get('scanSummary'));
        $this->assertSame(0, Translation::where('key', $key)->count());
        $component->call('applyScan')->assertHasNoErrors();
        $this->assertTrue($component->get('scanSummary')['applied']);
        $this->assertSame(1, Translation::where('key', $key)->where('locale', 'nl')->count());
        $component->call('applyScan')->assertHasErrors('scan');
        $this->assertSame(1, Translation::where('key', $key)->where('locale', 'nl')->count());
    }

    public function test_empty_catalog_filters_cached_sources_without_writing_or_adding_translations(): void
    {
        $this->assertSame(0, TranslationSource::count());
        $prefix = 'catalog-'.fake()->uuid();
        $rows = [];
        foreach (['frontend', 'backend', 'shared', 'php', 'legacy'] as $area) {
            $rows[$area] = Translation::create(['locale' => 'nl', 'key' => $prefix.'-'.$area, 'value' => $area === 'backend' ? '' : 'Keep '.$area]);
        }
        $sources = [];
        foreach (['frontend', 'backend', 'shared', 'php'] as $area) {
            $sources[] = [
                'identity' => hash('sha256', $rows[$area]->key), 'key' => $rows[$area]->key,
                'kind' => $area === 'php' ? 'php' : 'json', 'group' => $area === 'php' ? 'demo' : null,
                'original' => $area === 'php' ? 'Türkçe grup metni' : $rows[$area]->key,
                'file' => $area === 'php' ? 'lang/tr/demo.php' : 'resources/views/'.$area.'/example.blade.php',
                'line' => 1, 'surface' => $area === 'php' ? 'shared' : $area,
                'routes' => ['frontend.contact'], 'call' => '__',
            ];
        }
        $sources[] = [...$sources[0], 'identity' => hash('sha256', $prefix.'second-usage'), 'surface' => 'backend', 'file' => 'resources/views/backend/another.blade.php'];
        $preview = ['sources' => $sources, 'keys' => 4, 'files' => 5, 'dynamic' => 0];
        $this->partialMock(TranslationScanner::class)->shouldReceive('preview')->once()->andReturn($preview);
        $count = Translation::count();
        $component = Livewire::test(LanguageTranslateForm::class, ['languageId' => $this->language->id])->set('searchOriginal', $prefix);
        $this->assertCount(5, $component->get('translations'));
        $component->set('surface', 'frontend');
        $this->assertSame([$rows['frontend']->id], array_keys($component->get('translations')));
        $component->set('surface', 'backend');
        $this->assertEqualsCanonicalizing([$rows['frontend']->id, $rows['backend']->id], array_keys($component->get('translations')));
        $component->set('surface', 'shared');
        $this->assertCount(2, $component->get('translations'));
        $component->set('surface', '')->set('group', 'demo')->assertSee('Türkçe grup metni');
        $this->assertSame([$rows['php']->id], array_keys($component->get('translations')));
        $component->set('group', '@json');
        $this->assertCount(4, $component->get('translations')); // Includes preserved, unmapped legacy JSON.
        $component->set('group', '')->set('completion', 'empty');
        $this->assertSame([$rows['backend']->id], array_keys($component->get('translations')));
        $component->set('completion', '')->set('sourceSearch', 'backend/another');
        $this->assertSame([$rows['frontend']->id], array_keys($component->get('translations')));
        $component->call('setPage', 2)->call('resetFilters')->assertSet('surface', '')->assertSet('group', '')->assertSet('sourceSearch', '')->assertSet('completion', '')->assertSet('searchOriginal', '')->assertSet('paginators.page', 1);
        Livewire::test(LanguageTranslateForm::class, ['languageId' => $this->language->id])->set('surface', 'frontend');
        $this->assertSame(0, TranslationSource::count());
        $this->assertSame($count, Translation::count());
        $this->assertSame('Keep frontend', $rows['frontend']->fresh()->value);
    }

    public function test_header_uses_selected_file_modification_time_and_handles_a_missing_file(): void
    {
        $old = lang_path();
        $root = sys_get_temp_dir().'/eduan-file-time-'.bin2hex(random_bytes(8));
        File::ensureDirectoryExists($root.'/nl');
        foreach (['tr', 'nl', 'en', 'fr'] as $locale) {
            File::ensureDirectoryExists($root.'/'.$locale);
            File::copy($old.'/'.$locale.'/translation_manager.php', $root.'/'.$locale.'/translation_manager.php');
        }
        File::put($root.'/nl.json', '{}');
        File::put($root.'/nl/demo.php', "<?php return ['label'=>'Demo'];");
        touch($root.'/nl.json', 1728000000);
        touch($root.'/nl/demo.php', 1728000300);
        $row = Translation::create(['locale' => 'nl', 'key' => 'file-time-'.fake()->uuid(), 'value' => 'Saved']);
        TranslationSource::create(['identity' => hash('sha256', $row->key), 'key' => $row->key, 'kind' => 'php', 'group' => 'demo', 'original' => 'Kaynak', 'file' => 'lang/tr/demo.php', 'line' => 1, 'surface' => 'shared', 'routes' => [], 'call' => 'definition', 'active' => true, 'scan' => str_repeat('a', 32)]);
        try {
            app()->useLangPath($root);
            $component = Livewire::test(LanguageTranslateForm::class, ['languageId' => $this->language->id]);
            $expected = dateFormat(Carbon::createFromTimestamp(1728000000)->toIso8601String(), 'd/m/Y H:i:s');
            $component->assertSee('nl.json')->assertSee($expected);
            $component->set('group', 'demo');
            $expected = dateFormat(Carbon::createFromTimestamp(1728000300)->toIso8601String(), 'd/m/Y H:i:s');
            $component->assertSee('nl/demo.php')->assertSee($expected);
            File::delete($root.'/nl/demo.php');
            $component->call('$refresh')->assertSee(__('translation_manager.file_missing', ['file' => 'nl/demo.php']));
        } finally {
            app()->useLangPath($old);
            app('translator')->setLoaded([]);
            File::deleteDirectory($root);
        }
    }

    public function test_build_keeps_json_and_php_separate_preserves_nested_values_and_never_executes_translation(): void
    {
        $old = lang_path();
        $root = sys_get_temp_dir().'/eduan-scanner-'.bin2hex(random_bytes(8));
        File::ensureDirectoryExists($root.'/tr');
        File::ensureDirectoryExists($root.'/nl');
        File::put($root.'/tr/demo.php', "<?php return ['nested'=>['label'=>'Kaynak :name'], 'untouched'=>'Korunur'];");
        File::put($root.'/nl/demo.php', "<?php return ['nested'=>['label'=>'Before','other'=>'Keep'], 'untouched'=>'Keep too'];");
        File::put($root.'/nl.json', json_encode(['legacy' => 'Keep JSON', 'demo.nested.label' => 'Old shadow']));
        try {
            app()->useLangPath($root);
            $malicious = "'); file_put_contents('/tmp/SHOULD_NOT_EXIST', 'bad'); // :name";
            TranslationHelper::build('nl', ['demo.nested.label' => $malicious, 'New text' => 'New value', 'Blank' => '']);
            $json = json_decode(File::get($root.'/nl.json'), true, flags: JSON_THROW_ON_ERROR);
            $php = require $root.'/nl/demo.php';
            $this->assertSame('Keep JSON', $json['legacy']);
            $this->assertSame('New value', $json['New text']);
            $this->assertArrayNotHasKey('demo.nested.label', $json);
            $this->assertArrayNotHasKey('Blank', $json);
            $this->assertSame($malicious, $php['nested']['label']);
            $this->assertSame('Keep', $php['nested']['other']);
            $this->assertSame('Keep too', $php['untouched']);
            $this->assertFileDoesNotExist('/tmp/SHOULD_NOT_EXIST');
            $this->assertSame($malicious, app('translator')->get('demo.nested.label', locale: 'nl'));
        } finally {
            app()->useLangPath($old);
            app('translator')->setLoaded([]);
            File::deleteDirectory($root);
        }
    }
}
