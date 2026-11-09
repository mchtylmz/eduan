<?php

namespace Tests\Unit;

use App\Actions\Settings\PrepareSourceSettings;
use App\Actions\Sources\SourceUploads;
use App\Helpers\Sources\SourceInput;
use App\Helpers\Sources\SourceSettings;
use App\Helpers\Sources\SourceStorage;
use App\Http\Controllers\Backend\SourceFileController;
use App\Http\Controllers\Frontend\SourceController;
use App\Models\SourceFile;
use App\Models\User;
use Aws\Middleware;
use Aws\MockHandler;
use Aws\Result;
use GuzzleHttp\Psr7\Utils;
use Illuminate\Auth\Access\Gate;
use Illuminate\Cache\CacheServiceProvider;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Database\ConnectionResolver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Encryption\EncryptionServiceProvider;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Filesystem\FilesystemServiceProvider;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Providers\FoundationServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\RouteCollection;
use Illuminate\Routing\RoutingServiceProvider;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Translation\TranslationServiceProvider;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\ValidationServiceProvider;
use Illuminate\View\ViewServiceProvider;
use Mockery;
use PDO;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class SourceFilesTest extends TestCase
{
    private Application $app;

    private string $temporary;

    private mixed $previousContainer;

    private mixed $previousFacades;

    private mixed $previousDatabase;

    private SQLiteConnection $db;

    private User $actor;

    protected function setUp(): void
    {
        $this->previousContainer = Container::getInstance();
        $this->previousFacades = Facade::getFacadeApplication();
        $this->previousDatabase = Model::getConnectionResolver();
        $this->temporary = sys_get_temp_dir().'/eduan-source-tests-'.bin2hex(random_bytes(6));
        mkdir($this->temporary, 0700);
        mkdir($this->temporary.'/views', 0700);
        mkdir($this->temporary.'/templates/frontend/layouts', 0700, true);
        mkdir($this->temporary.'/templates/backend/layouts', 0700, true);
        file_put_contents($this->temporary.'/templates/backend/layouts/app.blade.php', "<!doctype html><html><body>@yield('content')@stack('script')</body></html>");
        file_put_contents($this->temporary.'/templates/frontend/layouts/app.blade.php', "<!doctype html><html><body>@yield('content')</body></html>");
        $this->app = new Application(dirname(__DIR__, 2));
        $this->app->instance('env', 'testing');
        $this->app->useLangPath(dirname(__DIR__, 2).'/lang');
        $this->app->instance('config', new Repository([
            'app' => ['locale' => 'nl', 'fallback_locale' => 'en', 'url' => 'http://localhost', 'key' => 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=', 'cipher' => 'AES-256-CBC'],
            'view' => ['paths' => [$this->temporary.'/templates', dirname(__DIR__, 2).'/resources/views'], 'compiled' => $this->temporary.'/views'],
            'cache' => ['default' => 'security', 'stores' => ['security' => ['driver' => 'array']]],
            'filesystems' => ['default' => 'public', 'disks' => ['public' => ['driver' => 'local', 'root' => $this->temporary.'/uploads', 'throw' => true]]],
        ]));
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication($this->app);
        foreach ([FilesystemServiceProvider::class, TranslationServiceProvider::class, ViewServiceProvider::class,
            RoutingServiceProvider::class, CacheServiceProvider::class, EncryptionServiceProvider::class, ValidationServiceProvider::class,
            FoundationServiceProvider::class] as $provider) {
            $this->app->register($provider);
        }
        $session = new Store('source-tests', new ArraySessionHandler(120));
        $session->start();
        $this->app->instance('session', $session);
        $this->app->instance('Settings', new class
        {
            public array $values = ['timezone' => 'Europe/Amsterdam'];

            public function __get($name)
            {
                return $this->values[$name] ?? null;
            }
        });
        $this->db = new SQLiteConnection(new PDO('sqlite::memory:'), ':memory:');
        $resolver = new ConnectionResolver(['testing' => $this->db]);
        $resolver->setDefaultConnection('testing');
        Model::setConnectionResolver($resolver);
        $this->db->statement('CREATE TABLE source_files (id INTEGER PRIMARY KEY AUTOINCREMENT, code TEXT UNIQUE, slug TEXT, title TEXT, original_name TEXT, extension TEXT, mime TEXT, size INTEGER, disk TEXT, path TEXT, storage_config TEXT, public_url TEXT, status TEXT DEFAULT "uploading", uploaded_by INTEGER, ready_at TEXT, created_at TEXT, updated_at TEXT, deleted_at TEXT)');
        $this->db->statement('CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT, surname TEXT, deleted_at TEXT)');
        $this->db->insert('INSERT INTO users VALUES (7, "Audit", "Admin", NULL)');
        $this->actor = new class extends User
        {
            public array $grants = ['dashboard:access', 'files:view', 'files:add', 'files:delete'];

            public bool $super = true;

            public function can($abilities, $arguments = []): bool
            {
                return in_array($abilities, $this->grants, true);
            }

            public function hasRole($roles, ?string $guard = null): bool
            {
                return $this->super && $roles === 'Yönetici';
            }
        };
        $this->actor->forceFill(['id' => 7]);
        $this->app->instance('auth', new class($this->actor) implements AuthFactory
        {
            public function __construct(private User $actor) {}

            public function guard($name = null): self
            {
                return $this;
            }

            public function shouldUse($name): void {}

            public function user(): User
            {
                return $this->actor;
            }

            public function id(): int
            {
                return $this->actor->id;
            }
        });
        $this->app->instance('request', $this->request());
        $gate = new Gate($this->app, fn () => $this->actor);
        $gate->before(fn ($user, $ability) => $user->can($ability));
        $this->app->instance(\Illuminate\Contracts\Auth\Access\Gate::class, $gate);
        $this->app['view']->share('errors', new ViewErrorBag);
        foreach (['show' => '/sources/{code}/{slug}', 'html' => '/sources/{code}/content/html'] as $name => $path) {
            $this->app['router']->get($path, fn () => '')->name('frontend.sources.'.$name);
        }
        $this->app['router']->delete('/admin/files/{code}', fn () => '')->name('admin.files.delete');
        foreach (['index' => '/admin/files', 'data' => '/admin/files/data', 'prepare' => '/admin/files/prepare', 'finish' => '/admin/files/{code}/finish'] as $name => $path) {
            $this->app['router']->get($path, fn () => '')->name('admin.files.'.$name);
        }
        $this->app['router']->get('/admin/settings', fn () => '')->name('admin.settings.store');
        $this->app['router']->getRoutes()->refreshNameLookups();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        (new Filesystem)->deleteDirectory($this->temporary);
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication($this->previousFacades);
        Container::setInstance($this->previousContainer);
        $this->previousDatabase ? Model::setConnectionResolver($this->previousDatabase) : Model::unsetConnectionResolver();
    }

    private function request(array $input = [], string $ip = '127.0.0.1'): Request
    {
        $request = Request::create('http://localhost/', 'GET', $input, server: ['REMOTE_ADDR' => $ip]);
        $request->setUserResolver(fn () => $this->actor);

        return $request;
    }

    private function pdf(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('guide.pdf', "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF");
    }

    private function prepare(UploadedFile $file, ?SourceUploads $uploads = null): SourceFile
    {
        $result = ($uploads ?? app(SourceUploads::class))->prepare(['title' => 'Konu <one>', 'name' => $file->getClientOriginalName(), 'size' => $file->getSize()]);

        return SourceFile::where('code', $result['code'])->firstOrFail();
    }

    public function test_local_upload_public_view_list_and_delete_share_the_same_record(): void
    {
        $file = $this->pdf();
        $source = $this->prepare($file);
        $uploads = app(SourceUploads::class);
        $source = $uploads->finish($source, $file);
        $this->assertSame('ready', $source->status);
        $this->assertTrue(is_file($this->temporary.'/uploads/'.$source->path));
        $this->assertStringContainsString('/uploads/sources/', $source->rawUrl());
        $this->assertStringNotContainsString('storage_config', $source->toJson());
        $this->assertSame('ready', $uploads->finish($source)->status);
        $response = (new SourceFileController)->data($this->request(['search' => 'Konu', 'type' => 'pdf']));
        $this->assertSame(1, $response->getData(true)['total']);
        $this->assertSame($source->viewerUrl(), $response->getData(true)['rows'][0]['view_url']);
        $this->actor->grants = [];
        $view = (new SourceController)->show($this->request(['return_to' => '/test/abc/start?lang=nl']), $source->code, $source->slug);
        $this->assertSame('/test/abc/start?lang=nl', $view->original->getData()['returnPath']);
        $this->assertStringContainsString('no-store', $view->headers->get('Cache-Control'));
        $this->actor->grants = ['dashboard:access', 'files:view', 'files:delete'];
        $uploads->delete($source);
        $this->assertNull(SourceFile::find($source->id));
        $this->assertFalse(is_file($this->temporary.'/uploads/'.$source->path));
    }

    public function test_view_rate_limit_is_per_ip_and_file_and_does_not_cover_originals(): void
    {
        $source = $this->prepare($file = $this->pdf());
        app(SourceUploads::class)->finish($source, $file);
        $controller = new SourceController;
        for ($i = 0; $i < 3; $i++) {
            $this->assertSame(200, $controller->show($this->request(), $source->code, $source->slug)->status());
        }
        try {
            $controller->show($this->request(), $source->code, $source->slug);
            $this->fail('Fourth viewer visit must be limited.');
        } catch (HttpExceptionInterface $error) {
            $this->assertSame(429, $error->getStatusCode());
            $this->assertArrayHasKey('Retry-After', $error->getHeaders());
        }
        $this->assertSame(200, $controller->show($this->request(ip: '127.0.0.2'), $source->code, $source->slug)->status());
        $other = $this->prepare($file);
        app(SourceUploads::class)->finish($other, $file);
        $this->assertSame(200, $controller->show($this->request(), $other->code, $other->slug)->status());
        $this->assertFileExists($this->temporary.'/uploads/'.$source->path);
    }

    public static function unsafeReturns(): array
    {
        return [['https://evil.example/x'], ['//evil.example'], ['/%2Fevil.example'], ['/\\evil.example'], ['/test%0d%0aLocation:x'], [['url' => 'evil']]];
    }

    #[DataProvider('unsafeReturns')]
    public function test_return_path_rejects_external_or_malformed_targets(mixed $input): void
    {
        $this->assertSame('/', SourceInput::returnPath($input));
    }

    public function test_invalid_types_sizes_and_disguised_content_cannot_be_published(): void
    {
        foreach ([['name' => 'x.php', 'size' => 10], ['name' => '../x.pdf', 'size' => 10], ['name' => 'x.pdf', 'size' => 104857601]] as $values) {
            try {
                SourceInput::metadata(['title' => 'test', ...$values]);
                $this->fail('Invalid metadata must fail.');
            } catch (ValidationException) {
                $this->assertTrue(true);
            }
        }
        $source = $this->prepare($file = UploadedFile::fake()->createWithContent('fake.pdf', '<script>one</script>'));
        $this->expectException(ValidationException::class);
        app(SourceUploads::class)->finish($source, $file);
    }

    public function test_html_original_download_and_sandboxed_content_are_distinct(): void
    {
        $source = $this->prepare($file = UploadedFile::fake()->createWithContent('lesson.html', '<!doctype html><h1>Lesson</h1><script>parent.__audit=1</script>'));
        app(SourceUploads::class)->finish($source, $file);
        $this->assertStringEndsWith('.html.download', $source->path);
        $this->assertStringContainsString('Content-Disposition attachment', file_get_contents($this->temporary.'/uploads/sources/.htaccess'));
        $response = (new SourceController)->html($source->code, app(SourceStorage::class));
        $this->assertStringContainsString('sandbox;', $response->headers->get('Content-Security-Policy'));
        $this->assertStringNotContainsString('allow-scripts', $response->headers->get('Content-Security-Policy'));
        ob_start();
        $response->sendContent();
        $content = ob_get_clean();
        $this->assertStringContainsString('<h1>Lesson</h1>', $content);
    }

    public function test_cloud_failure_requires_explicit_local_fallback(): void
    {
        settings()->values['sourceStorage'] = 'r2';
        $storage = Mockery::mock(SourceStorage::class)->makePartial();
        $storage->shouldReceive('uploadUrl')->once()->andThrow(new \RuntimeException('cloud unavailable'));
        $uploads = new SourceUploads($storage);
        $source = $this->prepare($file = $this->pdf(), $uploads);
        $this->assertSame('failed', $source->status);
        try {
            $uploads->finish($source, $file);
            $this->fail('Fallback needs explicit consent.');
        } catch (HttpExceptionInterface $error) {
            $this->assertSame(422, $error->getStatusCode());
        }
        $source = $uploads->finish($source, $file, true);
        $this->assertSame('local', $source->disk);
        $this->assertSame('ready', $source->status);
        $this->assertNull($source->storage_config);
    }

    public function test_upload_permission_and_owner_are_checked_again_at_finish(): void
    {
        $source = $this->prepare($file = $this->pdf());
        $this->actor->grants = ['dashboard:access', 'files:view'];
        try {
            app(SourceUploads::class)->finish($source, $file);
            $this->fail('View permission does not authorize uploads.');
        } catch (HttpExceptionInterface $error) {
            $this->assertSame(403, $error->getStatusCode());
        }
        $this->actor->grants[] = 'files:add';
        $this->actor->id = 8;
        $this->expectException(HttpException::class);
        app(SourceUploads::class)->finish($source, $file);
    }

    public function test_real_s3_presign_and_verification_publish_only_a_validated_immutable_object(): void
    {
        $config = ['driver' => 's3', 'key' => 'test-access-key', 'secret' => 'test-secret-key', 'region' => 'auto',
            'bucket' => 'media-test', 'endpoint' => 'https://'.str_repeat('a', 32).'.r2.cloudflarestorage.com',
            'use_path_style_endpoint' => true, 'request_checksum_calculation' => 'when_required', 'response_checksum_validation' => 'when_required', 'throw' => true];
        $source = $this->prepare($file = $this->pdf());
        $source->disk = 'r2';
        $source->storage_config = $config;
        $handler = new MockHandler;
        $handler->append(new Result(['ContentLength' => $source->size, 'ETag' => 'verified-etag']),
            new Result(['Body' => Utils::streamFor(file_get_contents($file->getRealPath()))]),
            new Result(['CopyObjectResult' => ['ETag' => 'final-etag']]), new Result);
        $disk = Storage::build($config + ['handler' => $handler]);
        $commands = [];
        $disk->getClient()->getHandlerList()->appendInit(Middleware::tap(function ($command) use (&$commands) {
            $commands[] = ['name' => $command->getName(), 'key' => $command['Key'], 'match' => $command['CopySourceIfMatch'] ?? null];
        }), 'source-test-history');
        $storage = Mockery::mock(SourceStorage::class)->makePartial();
        $storage->shouldReceive('cloud')->andReturn($disk);
        $upload = $storage->uploadUrl($source);
        $this->assertStringContainsString('/pending/'.$source->code, $upload['url']);
        $this->assertStringContainsString('X-Amz-Signature=', $upload['url']);
        $this->assertSame(['application/octet-stream'], array_change_key_case($upload['headers'])['content-type'] ?? null);
        $commands = [];
        $storage->verifyCloud($source);
        $this->assertSame(['HeadObject', 'GetObject', 'CopyObject', 'DeleteObject'], array_column($commands, 'name'));
        $this->assertSame($source->path, $commands[2]['key']);
        $this->assertSame('verified-etag', $commands[2]['match']);
        $this->assertSame('pending/'.$source->code, $commands[3]['key']);
    }

    public function test_cloud_size_mismatch_cannot_be_published(): void
    {
        $config = ['driver' => 's3', 'key' => 'test-key', 'secret' => 'test-secret', 'region' => 'auto',
            'bucket' => 'media-test', 'endpoint' => 'https://'.str_repeat('a', 32).'.r2.cloudflarestorage.com', 'use_path_style_endpoint' => true];
        $source = $this->prepare($this->pdf());
        $source->storage_config = $config;
        $handler = new MockHandler;
        $handler->append(new Result(['ContentLength' => $source->size + 1, 'ETag' => 'wrong-size']));
        $disk = Storage::build($config + ['handler' => $handler]);
        $commands = [];
        $disk->getClient()->getHandlerList()->appendInit(Middleware::tap(function ($command) use (&$commands) {
            $commands[] = $command->getName();
        }), 'source-size-history');
        $storage = Mockery::mock(SourceStorage::class)->makePartial();
        $storage->shouldReceive('cloud')->andReturn($disk);
        try {
            $storage->verifyCloud($source);
            $this->fail('A changed object must not be published.');
        } catch (\RuntimeException $error) {
            $this->assertSame('Unexpected object size.', $error->getMessage());
            $this->assertSame(['HeadObject'], $commands);
        }
    }

    public function test_actual_list_upload_settings_and_viewer_blades_render_in_four_languages(): void
    {
        $source = $this->prepare($file = $this->pdf());
        app(SourceUploads::class)->finish($source, $file);
        foreach (['tr', 'nl', 'en', 'fr'] as $locale) {
            $this->app->setLocale($locale);
            $html = $this->app['view']->make('backend.sources.index', ['title' => __('sources.title')])->render();
            $this->assertStringContainsString('source-files-table', $html);
            $this->assertStringContainsString('data-source-upload-form', $html);
            $this->assertStringContainsString(__('sources.upload'), $html);
            $settings = $this->app['view']->make('backend.settings.tabs.sources')->render();
            $this->assertStringContainsString('settings[sourceR2Secret]', $settings);
            foreach (['max_size', 'types', 'account', 'bucket', 'access_key', 'public_url', 'secret'] as $field) {
                $placeholder = htmlspecialchars(__('sources.'.$field.'_placeholder'), ENT_QUOTES);
                $this->assertStringContainsString('placeholder="'.$placeholder.'"', $settings);
            }
            $this->assertStringContainsString(htmlspecialchars(__('sources.cloud_help'), ENT_QUOTES), $settings);
            $this->assertStringNotContainsString('private-test-key', $settings);
            $view = $this->app['view']->make('frontend.sources.show', ['source' => $source, 'label' => 'sources.return_test', 'returnPath' => '/test/abc/start'])->render();
            $this->assertStringContainsString('/test/abc/start', $view);
            $this->assertStringContainsString(htmlspecialchars(__('sources.return_test'), ENT_QUOTES), $view);
            $this->assertStringContainsString('Konu &lt;one&gt;', $view);
        }
    }

    public function test_ready_only_access_and_filtered_empty_list(): void
    {
        $source = $this->prepare($this->pdf());
        $this->assertSame(0, (new SourceFileController)->data($this->request(['type' => 'png']))->getData(true)['total']);
        $this->expectException(ModelNotFoundException::class);
        (new SourceController)->show($this->request(), $source->code, $source->slug);
    }

    public function test_actual_public_routes_allow_content_as_a_title_without_shadowing_the_viewer(): void
    {
        $router = $this->app['router'];
        $router->setRoutes(new RouteCollection);
        $router->name('frontend.')->middleware(['web', 'set_locale'])->group(dirname(__DIR__, 2).'/routes/frontend.php');
        $code = '12345678-1234-4234-8234-123456789abc';
        $viewer = $router->getRoutes()->match(Request::create('/sources/'.$code.'/content'));
        $html = $router->getRoutes()->match(Request::create('/sources/'.$code.'/content/html'));
        $this->assertSame('frontend.sources.show', $viewer->getName());
        $this->assertSame('frontend.sources.html', $html->getName());
        $this->assertNotContains('auth', $viewer->middleware());
        $this->assertNotContains('auth', $html->middleware());
        $this->assertSame(['GET', 'HEAD'], $viewer->methods());
    }

    public function test_cloud_settings_secret_is_plaintext_legacy_readable_blank_preserved_and_superadmin_only(): void
    {
        $result = (new PrepareSourceSettings)->handle(['sourceStorage' => 'local', 'sourceR2Secret' => 'private-test-key', 'sourceMaxMb' => '100', 'sourceTypes' => 'pdf,html,png']);
        $this->assertSame('private-test-key', $result['sourceR2Secret']);
        settings()->values['sourceR2Secret'] = $result['sourceR2Secret'];
        $this->assertSame('private-test-key', SourceSettings::secret());
        $this->assertArrayNotHasKey('sourceR2Secret', (new PrepareSourceSettings)->handle(['sourceR2Secret' => '']));
        settings()->values['sourceR2Secret'] = Crypt::encryptString('legacy-key');
        $this->assertSame('legacy-key', SourceSettings::secret());
        $settings = $this->app['view']->make('backend.settings.tabs.sources')->render();
        $this->assertStringContainsString('type="text" name="settings[sourceR2Secret]" value="legacy-key"', $settings);
        settings()->values['sourceR2Secret'] = 'key"<test>';
        $settings = $this->app['view']->make('backend.settings.tabs.sources')->render();
        $this->assertStringContainsString('value="key&quot;&lt;test&gt;"', $settings);
        $this->actor->super = false;
        $this->expectException(HttpException::class);
        (new PrepareSourceSettings)->handle(['sourceStorage' => 'local']);
    }
}
