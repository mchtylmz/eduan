<?php

namespace Tests\Feature;

use App\Actions\Sources\SourceUploads;
use App\Enums\StatusEnum;
use App\Helpers\Sources\SourceSettings;
use App\Helpers\Sources\SourceStorage;
use App\Models\SourceFile;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SourceFilesIntegrationTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        config(['cache.stores.security' => ['driver' => 'array'], 'cache.stores.dashboard' => ['driver' => 'array'],
            'cache.stores.frontend' => ['driver' => 'array'], 'app.url' => 'http://localhost']);
        URL::forceRootUrl('http://localhost');
        URL::forceScheme('http');
        Storage::fake('public');
        settings()->set(['sourceStorage' => 'local', 'sourceTypes' => implode(',', array_keys(SourceSettings::TYPES)), 'sourceMaxMb' => '100']);
        settings()->save();
        foreach (['dashboard:access', 'files:view', 'files:add', 'files:delete', 'settings:view', 'settings:update'] as $name) {
            Permission::findOrCreate($name, 'web');
        }
        $this->admin = User::factory()->create(['status' => StatusEnum::ACTIVE]);
        $this->admin->givePermissionTo(['dashboard:access', 'files:view', 'files:add', 'files:delete', 'settings:view', 'settings:update']);
        $this->admin->assignRole(Role::findOrCreate('Yönetici', 'web'));
        $this->actingAs($this->admin);
    }

    private function pdf(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('sample.pdf', "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF");
    }

    public function test_files_default_to_compact_cards_and_upload_progress_is_initially_hidden(): void
    {
        $this->get(route('admin.files.index'))->assertOk()
            ->assertSee('class="is-card-view" data-user-card-view', false)
            ->assertSee('data-user-cards', false)
            ->assertSee('data-field="title"', false)
            ->assertDontSee('data-field="name"', false)
            ->assertDontSee('data-field="size"', false)
            ->assertDontSee('data-field="uploader"', false)
            ->assertDontSee('max-width:640px', false)
            ->assertSee('source-upload-progress d-none', false)
            ->assertSee('placeholder="'.__('sources.title_placeholder').'"', false)
            ->assertSee('source-selected-file', false);
    }

    private function prepare(UploadedFile $file, string $title = 'Demo source'): SourceFile
    {
        $code = $this->postJson(route('admin.files.prepare'), ['title' => $title, 'name' => $file->getClientOriginalName(), 'size' => $file->getSize()])
            ->assertOk()->assertJsonPath('disk', 'local')->json('code');

        return SourceFile::where('code', $code)->firstOrFail();
    }

    public static function formats(): array
    {
        return array_map(fn ($type) => [$type], array_keys(SourceSettings::TYPES));
    }

    #[DataProvider('formats')]
    public function test_supported_formats_complete_via_real_http_routes(string $type): void
    {
        $file = match ($type) {
            'pdf' => $this->pdf(),
            'html' => UploadedFile::fake()->createWithContent('sample.html', '<!doctype html><html><body><h1>Demo</h1><script>parent.xss=1</script></body></html>'),
            default => UploadedFile::fake()->image('sample.'.$type, 40, 30),
        };
        $source = $this->prepare($file, 'Demo <unsafe> '.$type);
        $url = route('admin.files.finish', $source->code);
        $this->postJson($url, ['file' => $file])->assertOk()->assertJsonPath('url', $source->viewerUrl());
        $source->refresh();
        $this->assertSame('ready', $source->status);
        Storage::disk('public')->assertExists($source->path);
        $this->postJson($url, ['file' => $file])->assertOk()->assertJsonPath('url', $source->viewerUrl());
        auth()->logout();
        $view = $this->get($source->viewerUrl().'?return_to=%2Ftest%2Fdemo%2Fstart')->assertOk()->assertSee('Demo &lt;unsafe&gt;', false)->assertSee('/test/demo/start', false);
        $this->assertTrue($view->baseResponse->headers->hasCacheControlDirective('no-store'));
        $view->assertSee($type === 'html' ? route('frontend.sources.html', $source->code) : $source->rawUrl(), false);
        if ($type === 'html') {
            $view->assertSee('sandbox', false);
            $html = $this->get(route('frontend.sources.html', $source->code))->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
            $this->assertStringContainsString("sandbox; default-src 'none'", $html->headers->get('Content-Security-Policy'));
            $this->assertStringContainsString('<h1>Demo</h1>', $html->streamedContent());
            $this->assertStringEndsWith('.html.download', $source->rawUrl());
        }
        Http::assertNothingSent();
    }

    public function test_guest_and_staff_permissions_cover_read_add_delete_and_settings(): void
    {
        $source = $this->prepare($file = $this->pdf());
        auth()->logout();
        $this->get(route('admin.files.index'))->assertRedirect(route('login'));
        $this->postJson(route('admin.files.prepare'), [])->assertUnauthorized();
        $this->actingAs($staff = User::factory()->create());
        $staff->givePermissionTo('dashboard:access');
        $this->get(route('admin.files.index'))->assertForbidden();
        $staff->givePermissionTo('files:view');
        $this->get(route('admin.files.index'))->assertOk()->assertDontSee('data-source-upload-form', false);
        $this->getJson(route('admin.files.data'))->assertOk()->assertJsonPath('rows.0.delete_url', null);
        $this->postJson(route('admin.files.prepare'), [])->assertForbidden();
        $this->deleteJson(route('admin.files.delete', $source->code))->assertForbidden();
        $staff->givePermissionTo(['settings:view', 'settings:update']);
        $this->get(route('admin.settings.index', ['activeTab' => 'sources']))->assertForbidden();
        $this->postJson(route('admin.settings.store'), ['settings' => ['sourceStorage' => 'r2']])->assertForbidden();
        $staff->givePermissionTo('files:add');
        $this->postJson(route('admin.files.finish', $source->code), ['file' => $file])->assertForbidden();
        $this->assertSame('uploading', $source->fresh()->status);
    }

    public function test_validation_filters_pagination_and_safe_sort_use_the_same_records(): void
    {
        foreach ([['title' => 'x', 'name' => 'evil.php', 'size' => 4], ['title' => 'x', 'name' => '../sample.pdf', 'size' => 10], ['title' => 'x', 'name' => 'sample.pdf', 'size' => 104857601], ['title' => '', 'name' => 'sample.pdf', 'size' => 10]] as $bad) {
            $this->postJson(route('admin.files.prepare'), $bad)->assertUnprocessable();
        }
        $source = $this->prepare($this->pdf(), 'Unique PDF title');
        $this->postJson(route('admin.files.finish', $source->code), ['file' => UploadedFile::fake()->createWithContent('sample.pdf', '<script>bad</script>')])->assertUnprocessable();
        $this->getJson(route('admin.files.data', ['type' => 'png']))->assertOk()->assertJsonPath('total', 0);
        $this->getJson(route('admin.files.data', ['search' => 'Unique', 'limit' => 1, 'offset' => 0]))->assertOk()->assertJsonPath('total', 1)->assertJsonCount(1, 'rows');
        $this->getJson(route('admin.files.data', ['search' => "' OR 1=1 --"]))->assertOk()->assertJsonPath('total', 0);
        $this->getJson(route('admin.files.data', ['sort' => 'title;drop table users']))->assertUnprocessable();
        $this->getJson(route('admin.files.data', ['limit' => 101]))->assertUnprocessable();
    }

    public function test_delete_removes_only_the_target_and_invalidates_public_viewer(): void
    {
        $uploads = app(SourceUploads::class);
        $first = $this->prepare($file = $this->pdf());
        $uploads->finish($first, $file);
        $second = $this->prepare($file = $this->pdf());
        $uploads->finish($second, $file);
        $this->deleteJson(route('admin.files.delete', $first->code))->assertOk();
        Storage::disk('public')->assertMissing($first->path);
        Storage::disk('public')->assertExists($second->path);
        $this->assertSoftDeleted('source_files', ['id' => $first->id]);
        $this->get($first->viewerUrl())->assertNotFound();
        $this->get($second->viewerUrl())->assertOk();
    }

    public function test_public_limit_return_path_and_unready_or_incorrect_links(): void
    {
        $source = $this->prepare($file = $this->pdf());
        auth()->logout();
        $this->get($source->viewerUrl())->assertNotFound();
        $this->actingAs($this->admin);
        app(SourceUploads::class)->finish($source, $file);
        auth()->logout();
        $this->get(str_replace($source->slug, 'wrong-slug', $source->viewerUrl()))->assertNotFound();
        $this->get($source->viewerUrl().'?return_to=https://evil.example')->assertOk()->assertSee('href="/"', false)->assertDontSee('evil.example');
        $this->get($source->viewerUrl())->assertOk();
        $this->get($source->viewerUrl())->assertOk();
        $this->get($source->viewerUrl())->assertTooManyRequests()->assertHeader('Retry-After');
        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.20'])->get($source->viewerUrl())->assertOk();
        Storage::disk('public')->assertExists($source->path);
    }

    public function test_settings_save_shows_plaintext_secret_preserves_blank_and_reads_legacy_values(): void
    {
        $this->get(route('admin.settings.index', ['activeTab' => 'sources']))->assertOk()->assertSee('settings[sourceR2Secret]', false);
        $this->postJson(route('admin.settings.store'), ['settings' => ['sourceStorage' => 'local', 'sourceMaxMb' => '40', 'sourceTypes' => 'pdf,html,png', 'sourceR2Secret' => 'sample-visible-secret']])->assertOk();
        $this->assertSame(40 * 1024 * 1024, SourceSettings::maxBytes());
        $this->assertSame('sample-visible-secret', SourceSettings::secret());
        $this->assertSame('sample-visible-secret', DB::table('settings')->whereNull('settingable_type')->whereNull('settingable_id')->where('name', 'sourceR2Secret')->value('value'));
        $this->get(route('admin.settings.index', ['activeTab' => 'sources']))->assertOk()
            ->assertSee('type="text" name="settings[sourceR2Secret]" value="sample-visible-secret"', false);
        settings()->set('sourceR2Secret', Crypt::encryptString('legacy-key'));
        settings()->save();
        $this->get(route('admin.settings.index', ['activeTab' => 'sources']))->assertOk()->assertSee('value="legacy-key"', false);
        $this->postJson(route('admin.settings.store'), ['settings' => ['sourceR2Secret' => 'sample-visible-secret']])->assertOk();
        $this->postJson(route('admin.settings.store'), ['settings' => ['sourceR2Secret' => '']])->assertOk();
        $this->assertSame('sample-visible-secret', SourceSettings::secret());
        $this->postJson(route('admin.settings.store'), ['settings' => ['sourceMaxMb' => '101']])->assertUnprocessable();
        $this->postJson(route('admin.settings.store'), ['settings' => ['sourceStorage' => 'r2']])->assertUnprocessable();
        Http::assertNothingSent();
    }

    public function test_failed_cloud_upload_only_becomes_local_after_explicit_fallback(): void
    {
        settings()->set(['sourceStorage' => 'r2', 'sourceR2PublicUrl' => 'https://media.example.test']);
        settings()->save();
        $this->partialMock(SourceStorage::class, function ($mock) {
            $mock->shouldReceive('uploadUrl')->once()->andThrow(new \RuntimeException('Private provider error'));
            $mock->shouldNotReceive('verifyCloud');
        });
        $file = $this->pdf();
        $prepared = $this->postJson(route('admin.files.prepare'), ['title' => 'Cloud fallback', 'name' => 'sample.pdf', 'size' => $file->getSize()])
            ->assertOk()->assertJsonPath('disk', 'r2')->assertJsonPath('cloud_error', true)->assertDontSee('Private provider error')->json();
        $source = SourceFile::where('code', $prepared['code'])->firstOrFail();
        $this->assertSame('failed', $source->status);
        $this->postJson(route('admin.files.finish', $source->code), ['file' => $file, 'fallback' => false])->assertUnprocessable();
        $this->assertSame('r2', $source->fresh()->disk);
        $this->postJson(route('admin.files.finish', $source->code), ['file' => $file, 'fallback' => true])->assertOk();
        $this->assertSame('local', $source->fresh()->disk);
        $this->assertSame('ready', $source->fresh()->status);
        Storage::disk('public')->assertExists($source->path);
        Http::assertNothingSent();
    }

    public function test_source_viewer_suppresses_promotions_without_disabling_the_homepage_popup(): void
    {
        settings()->set(['popupStatus_nl' => 'active', 'popupSubject_nl' => 'Promotion control', 'popupContent_nl' => '<p>Example promotion</p>']);
        settings()->save();
        $source = $this->prepare($file = $this->pdf());
        app(SourceUploads::class)->finish($source, $file);
        auth()->logout();
        $this->withSession(['locale' => 'nl'])->get($source->viewerUrl())->assertOk()->assertDontSee('id="PromoModal"', false);
        $this->get(route('frontend.home'))->assertOk()->assertSee('id="PromoModal"', false)->assertSee('Promotion control');
    }

    public static function locales(): array
    {
        return [['nl'], ['tr'], ['en'], ['fr']];
    }

    #[DataProvider('locales')]
    public function test_rate_limit_shows_one_localized_waiting_page_and_preserves_headers(string $locale): void
    {
        $source = $this->prepare($file = $this->pdf());
        app(SourceUploads::class)->finish($source, $file);
        auth()->logout();
        $this->withSession(['locale' => $locale]);
        foreach (range(1, 3) as $attempt) {
            $this->get($source->viewerUrl())->assertOk();
        }
        $response = $this->get($source->viewerUrl())->assertTooManyRequests()->assertHeader('Retry-After')
            ->assertSee(__('errors.too_many_title', [], $locale))->assertSee(__('errors.too_many_description', [], $locale))
            ->assertDontSee('assets/img/404.png', false)->assertDontSee('error-404__figure', false);
        $this->assertSame(1, preg_match_all('/<html\b/i', $response->getContent()));
        $this->assertSame(1, preg_match_all('/<main\b/i', $response->getContent()));
    }

    public static function displaySizes(): array
    {
        return [[1, '1 B'], [1024, '1.00 KB'], [1275, '1.25 KB'], [1048576, '1.00 MB'], [104857600, '100.00 MB']];
    }

    #[DataProvider('displaySizes')]
    public function test_table_displays_readable_sizes_and_preserves_stored_bytes(int $bytes, string $expected): void
    {
        $source = $this->prepare($this->pdf());
        $source->update(['size' => $bytes]);
        $this->getJson(route('admin.files.data', ['sort' => 'size', 'order' => 'asc']))->assertOk()->assertJsonPath('rows.0.size', $expected);
        $this->assertSame($bytes, $source->fresh()->size);
    }
}
