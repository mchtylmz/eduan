<?php

namespace Tests\Unit;

use App\Mail\ContactReplyMail;
use App\Mail\NewsletterMail;
use App\Mail\SendEmailToUserMail;
use App\Models\Contact;
use App\Models\Language;
use App\Models\Newsletter;
use App\Models\User;
use DOMDocument;
use DOMXPath;
use Illuminate\Cache\CacheServiceProvider;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Filesystem\FilesystemServiceProvider;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Routing\RoutingServiceProvider;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Translation\TranslationServiceProvider;
use Illuminate\View\ViewServiceProvider;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class MailPersonalizationSecurityTest extends TestCase
{
    private Application $app;

    private string $compiled;

    private mixed $previousContainer;

    private mixed $previousFacades;

    protected function setUp(): void
    {
        $this->previousContainer = Container::getInstance();
        $this->previousFacades = Facade::getFacadeApplication();
        $this->app = new Application(dirname(__DIR__, 2));
        $this->app->instance('env', 'testing');
        $this->app->useLangPath(dirname(__DIR__, 2).'/lang');
        $this->compiled = sys_get_temp_dir().'/eduan-mail-escape-'.bin2hex(random_bytes(6));
        mkdir($this->compiled, 0700);
        $this->app->instance('config', new Repository([
            'app' => ['locale' => 'nl', 'fallback_locale' => 'en', 'url' => 'http://localhost'],
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
            public function __get($name): ?string
            {
                return $name === 'siteTitle_nl' ? 'Audit site' : null;
            }
        });
        $this->app->instance('request', Request::create('http://localhost/contact'));
        foreach (['verify', 'create.password', 'unsubscribe'] as $name) {
            $this->app['router']->get('/'.$name.'/{token}', fn () => '')->name('frontend.'.$name);
        }
        $this->app['router']->getRoutes()->refreshNameLookups();
        $this->app['view']->share('errors', new ViewErrorBag);
        $this->app['view']->share('id', 'audit-editor');
    }

    protected function tearDown(): void
    {
        (new Filesystem)->deleteDirectory($this->compiled);
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication($this->previousFacades);
        Container::setInstance($this->previousContainer);
    }

    private function dom(string $html): DOMXPath
    {
        $dom = new DOMDocument;
        @$dom->loadHTML('<?xml encoding="UTF-8">'.$html);

        return new DOMXPath($dom);
    }

    public static function hostileValues(): array
    {
        return [
            ['<img src=x onerror=window.__audit=1>'],
            ['<svg onload=window.__audit=1></svg>'],
            ['&lt;img src=x onerror=window.__audit=1&gt;'],
            ['&#60;img src=x onerror=window.__audit=1&#62;'],
            ['&#x3c;img src=x onerror=window.__audit=1&#x3e;'],
            ['&amp;lt;img src=x onerror=window.__audit=1&amp;gt;'],
            ['\"><img src=x onerror=window.__audit=1>'],
            ['</div><script>window.__audit=1</script>'],
        ];
    }

    #[DataProvider('hostileValues')]
    public function test_all_personalized_email_views_keep_untrusted_values_as_text(string $payload): void
    {
        $user = (new User)->forceFill(['name' => $payload, 'surname' => 'Student', 'email' => 'audit@example.invalid']);
        $user->email_verified_token = 'audit-token';
        $contact = new Contact(['name' => $payload, 'email' => 'audit@example.invalid', 'phone' => $payload, 'school_name' => $payload]);
        $template = htmlentities('<p>Hallo [ad_soyad]; [phone]; [okul_adi]</p>');
        $contentByView = [
            'contact-reply' => (new ContactReplyMail($contact, 'nl', 'Audit', $template))->content,
            'user-email' => (new SendEmailToUserMail($user, 'nl', 'Audit', $template))->content,
            'newsletter' => (new NewsletterMail((new Newsletter)->forceFill(['email' => $payload]), 'nl', 'Audit', '<p>[email]</p>'))->content,
            'verify' => $template,
            'recover-password' => $template,
            'create-password' => $template,
        ];
        foreach ($contentByView as $view => $content) {
            $html = $this->app['view']->make('email.'.$view, compact('content', 'user') + [
                'subject' => 'Audit', 'locale' => 'nl', 'token' => 'audit-token', 'unsubscribe' => false,
            ])->render();
            $xpath = $this->dom($html);
            $this->assertSame(0, $xpath->query('//script|//svg|//*[@onerror or @onload]')->length, $view);
            $this->assertStringContainsString($payload, $xpath->query('//div[@class="email-content"]')->item(0)->textContent, $view);
        }
    }

    public function test_authored_html_names_entities_and_nested_tokens_remain_compatible(): void
    {
        foreach (['<pre><p>Hallo <b>[ad_soyad]</b>; [email]</p></pre>', htmlentities('<pre><p>Hallo <b>[ad_soyad]</b>; [email]</p></pre>')] as $template) {
            $output = replaceEmailVariables($template, ['name' => 'Zoë & [email]', 'surname' => "O'Neil", 'email' => 'real@example.invalid']);
            $xpath = $this->dom($output);
            $this->assertSame("Zoë & [email] O'Neil", $xpath->query('//b')->item(0)->textContent);
            $this->assertStringContainsString('real@example.invalid', $output);
            $this->assertSame(0, $xpath->query('//pre')->length);
        }
        $this->assertSame('&lt;img&gt;', replaceEmailVariables('[ad]', ['name' => '<img>']));
        $this->assertSame('&amp;lt;img&amp;gt;', replaceEmailVariables('[ad]', ['name' => '&lt;img&gt;']));
    }

    public function test_mail_build_stores_safe_reply_and_historical_html_is_sandboxed_without_data_writes(): void
    {
        $contact = new class extends Contact
        {
            protected $dateFormat = 'Y-m-d H:i:s';

            public function update(array $attributes = [], array $options = [])
            {
                $this->fill($attributes);

                return true;
            }
        };
        $contact->fill(['name' => '<img src=x onerror=window.__audit=1>', 'email' => 'audit@example.invalid', 'locale' => 'nl']);
        $contact->setRelation('language', new Language(['name' => 'Nederlands']));
        (new ContactReplyMail($contact, 'nl', 'Audit', '<p>Hallo [ad_soyad]</p>'))->build();
        $safe = html_entity_decode($contact->reply);
        $this->assertSame(0, $this->dom($safe)->query('//*[@onerror]')->length);
        $this->assertStringContainsString('<p>Hallo ', $safe);

        // Simulate an old stored record without changing any real database row.
        $contact->reply = htmlentities('<p>Oud bericht</p><img src=x onerror=window.__audit=1><script>window.__audit=2</script>');
        $xpath = $this->dom($this->app['view']->make('livewire.backend.contacts.contact-reply-form', compact('contact'))->render());
        $frame = $xpath->query('//iframe')->item(0);
        $this->assertNotNull($frame);
        $this->assertTrue($frame->hasAttribute('sandbox'));
        $this->assertSame('', $frame->getAttribute('sandbox'));
        $this->assertStringContainsString('<p>Oud bericht</p>', $frame->getAttribute('srcdoc'));
        $this->assertSame(0, $xpath->query('//*[@onerror or @onload]')->length);
        $this->assertSame('no-referrer', $frame->getAttribute('referrerpolicy'));
    }
}
