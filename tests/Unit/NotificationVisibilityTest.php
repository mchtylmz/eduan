<?php

namespace Tests\Unit;

use App\Actions\Auth\ImpersonateUser;
use App\Http\Controllers\Frontend\NotificationController;
use App\Models\User;
use Illuminate\Cache\CacheServiceProvider;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Database\ConnectionResolver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Filesystem\FilesystemServiceProvider;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Routing\RoutingServiceProvider;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Facade;
use Illuminate\Translation\TranslationServiceProvider;
use Illuminate\View\ViewServiceProvider;
use PDO;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class NotificationVisibilityTest extends TestCase
{
    private Application $app;

    private SQLiteConnection $database;

    private string $compiled;

    private mixed $previousContainer;

    private mixed $previousFacades;

    private mixed $previousDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->previousContainer = Container::getInstance();
        $this->previousFacades = Facade::getFacadeApplication();
        $this->previousDatabase = Model::getConnectionResolver();
        $this->app = new Application(dirname(__DIR__, 2));
        $this->app->instance('env', 'testing');
        $this->app->useLangPath(dirname(__DIR__, 2).'/lang');
        $this->compiled = sys_get_temp_dir().'/eduan-notification-visibility-'.bin2hex(random_bytes(6));
        mkdir($this->compiled, 0700);
        $this->app->instance('config', new Repository([
            'app' => ['locale' => 'nl', 'fallback_locale' => 'en', 'url' => 'http://localhost', 'key' => 'visibility-test', 'version' => '1.1.42'],
            'view' => ['paths' => [dirname(__DIR__, 2).'/resources/views'], 'compiled' => $this->compiled],
            'cache' => ['default' => 'frontend', 'frontend_ttl' => 60, 'stores' => ['frontend' => ['driver' => 'array']]],
        ]));
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication($this->app);
        foreach ([FilesystemServiceProvider::class, TranslationServiceProvider::class,
            ViewServiceProvider::class, RoutingServiceProvider::class, CacheServiceProvider::class] as $provider) {
            $this->app->register($provider);
        }
        $this->app->instance('Settings', new class
        {
            public array $values = ['notificationsEnabled' => '1', 'floatWhatsapp' => 'active', 'socialWhatsapp' => '32123456789', 'socialWhatsappText_nl' => 'Hallo'];

            public function __get($name)
            {
                return $this->values[$name] ?? null;
            }
        });
        $session = new Store('visibility-test', new ArraySessionHandler(120));
        $this->app->instance('session', $session);
        $request = Request::create('http://localhost/account');
        $request->setLaravelSession($session);
        $this->app->instance('request', $request);
        foreach (['profile', 'favorite', 'solved', 'solved.exams', 'study', 'notifications',
            'notifications.context', 'notifications.register', 'my.leagues', 'stats', 'logout'] as $name) {
            $this->app['router']->get('/'.$name, fn () => '')->name('frontend.'.$name);
        }
        $this->app['router']->getRoutes()->refreshNameLookups();
        $this->database = new SQLiteConnection(new PDO('sqlite::memory:'), ':memory:');
        $resolver = new ConnectionResolver(['testing' => $this->database]);
        $resolver->setDefaultConnection('testing');
        Model::setConnectionResolver($resolver);
        $this->database->statement('CREATE TABLE web_push_subscriptions (id INTEGER PRIMARY KEY, user_id INTEGER, revoked_at TEXT)');
        $this->database->enableQueryLog();
        $this->actor(true);
    }

    protected function tearDown(): void
    {
        (new Filesystem)->deleteDirectory($this->compiled);
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

    private function actor(bool $authenticated): void
    {
        $user = null;
        if ($authenticated) {
            $user = new class extends User
            {
                public function can($abilities, $arguments = []): bool
                {
                    return false;
                }
            };
            $user->forceFill(['id' => 42, 'name' => 'Test', 'surname' => 'Student']);
        }
        $this->app->instance('auth', new class($user) implements AuthFactory
        {
            public function __construct(private ?User $user) {}

            public function guard($name = null): self
            {
                return $this;
            }

            public function shouldUse($name): void {}

            public function check(): bool
            {
                return $this->user !== null;
            }

            public function user(): ?User
            {
                return $this->user;
            }
        });
        $this->app['request']->setUserResolver(fn () => $user);
    }

    private function render(string $view): string
    {
        return $this->app['view']->make($view)->render();
    }

    public static function disabledValues(): array
    {
        return [['0'], [null], [''], [false]];
    }

    #[DataProvider('disabledValues')]
    public function test_disabled_page_is_not_found_before_reading_any_notification_data(mixed $value): void
    {
        settings()->values['notificationsEnabled'] = $value;
        $this->database->flushQueryLog();
        try {
            (new NotificationController)->index($this->app['request']);
            $this->fail('Disabled notifications page must not be accessible.');
        } catch (HttpExceptionInterface $exception) {
            $this->assertSame(404, $exception->getStatusCode());
        }
        $this->assertSame([], $this->database->getQueryLog());
    }

    #[DataProvider('disabledValues')]
    public function test_disabled_setting_hides_real_blade_entry_points_even_with_an_existing_device(mixed $value): void
    {
        $this->database->insert('INSERT INTO web_push_subscriptions VALUES (1, 42, NULL)');
        settings()->values['notificationsEnabled'] = $value;
        $this->database->flushQueryLog();
        $floating = $this->render('frontend.layouts.section.floating-button');
        $account = $this->render('frontend.account.tab');
        $script = $this->render('frontend.layouts.section.notification-script');

        $this->assertStringNotContainsString('float-notifications', $floating);
        $this->assertStringContainsString('float-whatsapp', $floating);
        $this->assertStringNotContainsString('http://localhost/notifications', $account);
        $this->assertStringContainsString('http://localhost/profile', $account);
        $this->assertStringNotContainsString('eduan-web-push-config', $script);
        $this->assertStringNotContainsString('assets/web-push.js', $script);
        $this->assertSame([], $this->database->getQueryLog());
        $this->assertSame(1, $this->database->table('web_push_subscriptions')->count());
    }

    public function test_enabled_setting_preserves_widget_and_device_based_account_link_without_provider_keys(): void
    {
        $this->assertStringContainsString('float-notifications', $this->render('frontend.layouts.section.floating-button'));
        $this->assertStringNotContainsString('http://localhost/notifications', $this->render('frontend.account.tab'));
        $this->database->insert('INSERT INTO web_push_subscriptions VALUES (1, 42, NULL)');
        $this->assertStringContainsString('http://localhost/notifications', $this->render('frontend.account.tab'));
        $script = $this->render('frontend.layouts.section.notification-script');
        $this->assertStringContainsString('eduan-web-push-config', $script);
        $this->assertStringContainsString('"ready":false', $script);
        $this->assertStringContainsString('assets/web-push.js', $script);
    }

    public function test_guests_do_not_get_widget_or_bootstrap_when_notifications_are_enabled(): void
    {
        $this->actor(false);
        $this->assertStringNotContainsString('float-notifications', $this->render('frontend.layouts.section.floating-button'));
        $this->assertStringNotContainsString('eduan-web-push-config', $this->render('frontend.layouts.section.notification-script'));
    }

    public function test_impersonation_still_omits_provider_bootstrap_when_notifications_are_enabled(): void
    {
        session()->put(ImpersonateUser::SESSION_KEY, ['target_id' => 42]);
        $this->assertStringNotContainsString('eduan-web-push-config', $this->render('frontend.layouts.section.notification-script'));
    }

    public function test_toggling_off_and_on_restores_existing_links_without_changing_devices(): void
    {
        $this->database->insert('INSERT INTO web_push_subscriptions VALUES (1, 42, NULL)');
        foreach (['1', '0', '1'] as $value) {
            settings()->values['notificationsEnabled'] = $value;
            $this->assertSame($value === '1', str_contains($this->render('frontend.layouts.section.floating-button'), 'float-notifications'));
            $this->assertSame($value === '1', str_contains($this->render('frontend.account.tab'), 'http://localhost/notifications'));
        }
        $this->assertSame(1, $this->database->table('web_push_subscriptions')->whereNull('revoked_at')->count());
    }
}
