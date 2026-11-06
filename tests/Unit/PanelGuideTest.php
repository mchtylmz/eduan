<?php

namespace Tests\Unit;

use App\Actions\Settings\SettingSaveAction;
use App\Helpers\PanelGuide;
use App\Models\Role;
use App\Models\User;
use Illuminate\Cache\CacheServiceProvider;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\ConnectionResolver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Filesystem\FilesystemServiceProvider;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Routing\RoutingServiceProvider;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Translation\TranslationServiceProvider;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\ValidationServiceProvider;
use Illuminate\View\ViewServiceProvider;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class PanelGuideTest extends TestCase
{
    private Application $app;

    private mixed $previousContainer;

    private mixed $previousFacades;

    private mixed $previousDatabase;

    private string $compiled;

    protected function setUp(): void
    {
        parent::setUp();
        $this->previousContainer = Container::getInstance();
        $this->previousFacades = Facade::getFacadeApplication();
        $this->previousDatabase = Model::getConnectionResolver();
        $this->app = new Application(dirname(__DIR__, 2));
        $this->app->useLangPath(dirname(__DIR__, 2).'/lang');
        $this->compiled = sys_get_temp_dir().'/eduan-guide-'.bin2hex(random_bytes(6));
        mkdir($this->compiled, 0700);
        $this->app->instance('config', new Repository([
            'app' => ['locale' => 'tr', 'fallback_locale' => 'en', 'url' => 'http://localhost'],
            'view' => ['paths' => [dirname(__DIR__, 2).'/resources/views'], 'compiled' => $this->compiled],
            'cache' => ['default' => 'frontend', 'stores' => ['frontend' => ['driver' => 'array']]],
            'auth' => ['defaults' => ['guard' => 'web'], 'guards' => ['web' => ['driver' => 'session', 'provider' => 'users']],
                'providers' => ['users' => ['driver' => 'eloquent', 'model' => User::class]]],
            'permission' => ['models' => ['role' => Role::class]],
        ]));
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication($this->app);
        foreach ([FilesystemServiceProvider::class, TranslationServiceProvider::class,
            ValidationServiceProvider::class, ViewServiceProvider::class,
            RoutingServiceProvider::class, CacheServiceProvider::class] as $provider) {
            $this->app->register($provider);
        }
        $this->app->instance('request', Request::create('http://localhost/admin/settings'));
        $this->app->instance('Settings', new class
        {
            public array $values = ['gptModel' => 'gpt-6-luna'];

            public int $saves = 0;

            public function __get($key)
            {
                return $this->values[$key] ?? null;
            }

            public function set(array $values): void
            {
                $this->values = array_replace($this->values, $values);
            }

            public function save(): void
            {
                $this->saves++;
            }
        });
        $this->app['view']->share('errors', new ViewErrorBag);
        // Eloquent builds a query for loadMissing even when the roles are already loaded.
        // No query may execute: a PDO request fails rather than connecting to any database.
        $resolver = new ConnectionResolver(['guide' => new SQLiteConnection(
            fn () => throw new \RuntimeException('Database access is forbidden in this test.'), ':memory:'
        )]);
        $resolver->setDefaultConnection('guide');
        Model::setConnectionResolver($resolver);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->compiled.'/*') as $file) {
            unlink($file);
        }
        rmdir($this->compiled);
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication($this->previousFacades);
        Container::setInstance($this->previousContainer);
        if ($this->previousDatabase) {
            Model::setConnectionResolver($this->previousDatabase);
        } else {
            Model::unsetConnectionResolver();
        }
        parent::tearDown();
    }

    private function actor(string $role, string $guard = 'web', bool $canWrite = true): User
    {
        $user = new class extends User
        {
            public bool $canWrite = true;

            public function can($abilities, $arguments = []): bool
            {
                return $this->canWrite && in_array($abilities, ['dashboard:access', 'settings:update'], true);
            }
        };
        $user->canWrite = $canWrite;
        $user->setRelation('roles', collect([new Role(['name' => $role, 'guard_name' => $guard])]));

        return $user;
    }

    private function save(User $actor, array $values): void
    {
        $request = new class extends Request
        {
            public function validate(array $rules): array
            {
                return Validator::make($this->all(), $rules)->validate();
            }
        };
        $request->request->set('settings', $values);
        $request->setMethod('POST');
        $request->setUserResolver(fn () => $actor);
        (new SettingSaveAction)->handle($request);
    }

    public static function invalidUrls(): array
    {
        return [['javascript:alert(1)'], ['data:text/html,test'], ['file:///tmp/docs.html'], ['//example.com/docs'],
            ['/documents.html'], ['not a url'], ['https://example.com/'.str_repeat('a', 2048)], [['https://example.com']], [42]];
    }

    #[DataProvider('invalidUrls')]
    public function test_invalid_or_executable_urls_are_not_exposed_or_saved(mixed $url): void
    {
        $this->assertNull(PanelGuide::url($url));
        try {
            $this->save($this->actor('Yönetici'), ['panelGuideUrl' => $url]);
            $this->fail('The URL should be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('settings.panelGuideUrl', $exception->errors());
        }
        $this->assertSame(0, settings()->saves);
    }

    public function test_only_the_web_superadmin_role_can_see_the_guide(): void
    {
        $this->assertTrue(PanelGuide::visibleTo($this->actor('Yönetici')));
        foreach (['Gent', 'Öğretmen', 'Normal Üye'] as $role) {
            $this->assertFalse(PanelGuide::visibleTo($this->actor($role)));
        }
        $this->assertFalse(PanelGuide::visibleTo($this->actor('Yönetici', 'api')));
        $this->assertFalse(PanelGuide::visibleTo(null));
    }

    public function test_owner_can_save_read_and_clear_without_changing_other_settings(): void
    {
        $this->assertNull(PanelGuide::url(settings()->panelGuideUrl));
        $url = 'https://docs.example.com/documents.html?mode=panel&lang=nl#calisma';
        $this->save($this->actor('Yönetici'), ['panelGuideUrl' => $url]);
        $this->assertSame($url, settings()->panelGuideUrl);
        $this->save($this->actor('Gent'), ['appName' => 'Example']);
        $this->assertSame($url, settings()->panelGuideUrl);
        $this->save($this->actor('Yönetici'), ['panelGuideUrl' => null]);
        $this->assertSame('', settings()->panelGuideUrl);
        $this->assertNull(PanelGuide::url(settings()->panelGuideUrl));
        $this->assertSame('Example', settings()->appName);
        $this->assertSame(3, settings()->saves);
        $this->assertSame('http://example.com/docs', PanelGuide::url('  http://example.com/docs  '));
    }

    public function test_other_roles_cannot_write_or_clear_the_field_even_with_settings_access(): void
    {
        foreach (['Gent', 'Öğretmen', 'Normal Üye'] as $role) {
            foreach (['https://example.com/docs', '', null] as $value) {
                try {
                    $this->save($this->actor($role), ['panelGuideUrl' => $value]);
                    $this->fail('Non-superadmin should be forbidden.');
                } catch (HttpExceptionInterface $exception) {
                    $this->assertSame(403, $exception->getStatusCode());
                }
            }
        }
        $this->assertSame(0, settings()->saves);
        try {
            $this->save($this->actor('Yönetici', canWrite: false), ['panelGuideUrl' => 'https://example.com/docs']);
            $this->fail('Settings update permission must still be required.');
        } catch (HttpExceptionInterface $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
    }

    public function test_real_field_and_sidebar_views_follow_role_url_and_locale(): void
    {
        foreach (['tr', 'nl', 'en', 'fr'] as $locale) {
            $this->app->setLocale($locale);
            foreach (['Yönetici', 'Gent', 'Öğretmen', 'Normal Üye'] as $role) {
                $actor = $this->actor($role);
                $guard = $this->createStub(Guard::class);
                $guard->method('user')->willReturn($actor);
                $this->app->instance('auth', $guard);
                foreach (['', 'https://example.com/documents.html?a=1&b=2', 'javascript:alert(1)'] as $url) {
                    settings()->set(['panelGuideUrl' => $url]);
                    $field = $this->app['view']->make('backend.settings.partials.panel-guide')->render();
                    $sidebar = $this->app['view']->make('backend.layouts.sidebar-guide')->render();
                    $this->assertSame($role === 'Yönetici', str_contains($field, 'name="settings[panelGuideUrl]"'));
                    $visible = $role === 'Yönetici' && PanelGuide::url($url) !== null;
                    $this->assertSame($visible, str_contains($sidebar, 'data-panel-guide'));
                    if ($visible) {
                        $this->assertStringContainsString('href="https://example.com/documents.html?a=1&amp;b=2"', $sidebar);
                        $this->assertStringContainsString('target="_blank" rel="noopener noreferrer"', $sidebar);
                        $this->assertStringContainsString(__('guide.title'), $sidebar);
                    }
                    if ($role === 'Yönetici' && $url === '') {
                        $this->assertStringContainsString('value=""', $field);
                    }
                }
            }
        }
    }
}
