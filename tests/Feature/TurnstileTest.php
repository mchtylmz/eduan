<?php

namespace Tests\Feature;

use App\Actions\Auth\VerifyTurnstile;
use App\Actions\Contacts\LimitNewsletterRequests;
use App\Actions\Settings\SettingSaveAction;
use App\Helpers\TurnstileSettings;
use App\Livewire\Frontend\Auth\CreatePassword;
use App\Livewire\Frontend\Auth\Login;
use App\Livewire\Frontend\Auth\RecoverPassword;
use App\Livewire\Frontend\Auth\Register;
use App\Livewire\Frontend\Contacts\ContactForm;
use App\Livewire\Frontend\Newsletter\NewsletterForm;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class TurnstileTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.url' => 'https://hypotenuse.be']);
        settings()->turnstileEnabled = '1';
        settings()->turnstileSiteKey = 'test-site-key';
        settings()->turnstileSecretKey = Crypt::encryptString('test-secret');
        Cache::store('security')->flush();
        Mail::fake();
    }

    public static function invalidResponses(): array
    {
        return [
            'missing success' => [[]], 'false success' => [['success' => false]],
            'string success' => [['success' => 'true', 'hostname' => 'hypotenuse.be', 'action' => 'login']],
            'other host' => [['success' => true, 'hostname' => 'other.test', 'action' => 'login']],
            'other action' => [['success' => true, 'hostname' => 'hypotenuse.be', 'action' => 'register']],
            'expired or duplicate' => [['success' => false, 'error-codes' => ['timeout-or-duplicate']]],
            'missing action' => [['success' => true, 'hostname' => 'hypotenuse.be']],
        ];
    }

    #[DataProvider('invalidResponses')]
    public function test_invalid_provider_responses_fail_closed(array $body): void
    {
        Http::fake(['challenges.cloudflare.com/*' => Http::response($body)]);
        $this->expectException(ValidationException::class);
        VerifyTurnstile::run('token', 'login');
    }

    public function test_valid_response_is_verified_on_fixed_endpoint_with_action_and_configured_host(): void
    {
        Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => true, 'hostname' => 'hypotenuse.be', 'action' => 'login'])]);
        VerifyTurnstile::run('a-token', 'login');
        Http::assertSent(fn ($r) => $r->url() === 'https://challenges.cloudflare.com/turnstile/v0/siteverify'
            && $r['secret'] === 'test-secret' && $r['response'] === 'a-token');
        Http::assertSentCount(1);
    }

    public static function malformedTokens(): array
    {
        return [[''], [str_repeat('x', 2049)]];
    }

    #[DataProvider('malformedTokens')]
    public function test_missing_or_oversized_tokens_do_not_contact_provider(string $token): void
    {
        try {
            VerifyTurnstile::run($token, 'login');
            $this->fail('Token must be rejected.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('turnstileToken', $e->errors());
        }
        Http::assertNothingSent();
    }

    public function test_provider_timeout_http_failure_malformed_json_and_missing_configuration_are_closed(): void
    {
        foreach ([Http::failedConnection(), Http::response([], 503), Http::response('not-json')] as $response) {
            Http::fake(['challenges.cloudflare.com/*' => $response]);
            try {
                VerifyTurnstile::run('token', 'login');
                $this->fail('Provider failure must block.');
            } catch (ValidationException $e) {
                $this->assertArrayHasKey('turnstileToken', $e->errors());
            }
        }
        settings()->turnstileSecretKey = 'unreadable-ciphertext';
        $this->expectException(ValidationException::class);
        VerifyTurnstile::run('token', 'login');
    }

    public function test_disabled_feature_and_authenticated_contact_do_not_require_widget_or_provider(): void
    {
        settings()->turnstileEnabled = '0';
        VerifyTurnstile::run('', 'login');
        Livewire::test(Login::class)->assertDontSee('eduanTurnstile');
        settings()->turnstileEnabled = '1';
        $this->actingAs(User::factory()->create());
        VerifyTurnstile::run('', 'contact');
        Livewire::test(ContactForm::class)->assertDontSee('eduanTurnstile')->call('submit')->assertHasErrors('name')->assertHasNoErrors('turnstileToken');
        Http::assertNothingSent();
    }

    public static function protectedForms(): array
    {
        return [[Login::class, 'login'], [Register::class, 'save'], [RecoverPassword::class, 'submit'], [CreatePassword::class, 'update'], [ContactForm::class, 'submit']];
    }

    #[DataProvider('protectedForms')]
    public function test_valid_token_reaches_field_validation_and_is_cleared_on_every_form(string $component, string $method): void
    {
        $action = match ($component) {
            Login::class => 'login', Register::class => 'register', RecoverPassword::class => 'recover',
            CreatePassword::class => 'password_reset', ContactForm::class => 'contact',
        };
        Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => true, 'hostname' => 'hypotenuse.be', 'action' => $action])]);
        $params = ['lazy' => false];
        if ($component === CreatePassword::class) {
            $user = User::factory()->create();
            $token = Str::random(40);
            DB::table('password_reset_tokens')->insert(['email' => $user->email, 'token' => hash('sha256', $token), 'created_at' => now()]);
            $params += compact('user', 'token');
        }
        Livewire::test($component, $params)->set('turnstileToken', 'valid-token')->call($method)
            ->assertHasNoErrors('turnstileToken')->assertHasErrors()->assertSet('turnstileToken', '')->assertDispatched('turnstile-reset');
        Http::assertSentCount(1);
        Mail::assertNothingOutgoing();
    }

    #[DataProvider('protectedForms')]
    public function test_all_five_guest_forms_block_missing_token_before_side_effects(string $component, string $method): void
    {
        $params = ['lazy' => false];
        if ($component === CreatePassword::class) {
            $user = User::factory()->create();
            $token = Str::random(40);
            DB::table('password_reset_tokens')->insert(['email' => $user->email, 'token' => hash('sha256', $token), 'created_at' => now()]);
            $params += compact('user', 'token');
        }
        $users = User::count();
        $contacts = Contact::count();
        Livewire::test($component, $params)->call($method)->assertHasErrors('turnstileToken')->assertSet('turnstileToken', '')->assertDispatched('turnstile-reset');
        $this->assertSame($users, User::count());
        $this->assertSame($contacts, Contact::count());
        Mail::assertNothingOutgoing();
        Http::assertNothingSent();
    }

    public function test_login_consumes_token_and_second_attempt_cannot_reuse_it(): void
    {
        Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => true, 'hostname' => 'hypotenuse.be', 'action' => 'login'])]);
        $form = Livewire::test(Login::class)->set('turnstileToken', 'valid-token')->set('username', 'missing@example.test')->set('password', 'wrong-password');
        $form->call('login')->assertHasNoErrors()->assertSet('turnstileToken', '')->assertDispatched('turnstile-reset');
        $form->call('login')->assertHasErrors('turnstileToken');
        Http::assertSentCount(1);
        $this->assertGuest();
    }

    public function test_newsletter_limit_is_ip_based_survives_default_cache_clear_and_expires(): void
    {
        $this->travelTo(now()->startOfSecond());
        LimitNewsletterRequests::run();
        cache()->flush();
        try {
            LimitNewsletterRequests::run();
            $this->fail('Second request must be limited.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('email', $e->errors());
        }
        $this->travel(61)->seconds();
        LimitNewsletterRequests::run();
        $this->travelBack();
        $this->assertTrue(true);
    }

    public function test_newsletter_form_has_no_challenge_and_limits_second_attempt_even_with_new_email(): void
    {
        $form = Livewire::test(NewsletterForm::class)->assertDontSee('eduanTurnstile');
        $form->set('email', 'invalid')->call('submit')->assertHasErrors('email');
        $form->set('email', 'another@example.test')->call('submit')->assertSee(__('turnstile.newsletter_limit'));
        Http::assertNothingSent();
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        foreach (['dashboard:access', 'settings:view', 'settings:update', 'user-type:admin'] as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }

        return $user;
    }

    public function test_settings_encrypt_secret_keep_blank_and_render_only_public_key(): void
    {
        $this->actingAs($this->admin());
        $request = Request::create('/', 'POST', ['settings' => ['turnstileEnabled' => '1', 'turnstileSiteKey' => 'saved-site', 'turnstileSecretKey' => 'saved-private-secret']]);
        $request->setUserResolver(fn () => auth()->user());
        SettingSaveAction::run($request);
        $this->assertSame('saved-private-secret', TurnstileSettings::secret());
        $this->assertNotSame('saved-private-secret', settings()->turnstileSecretKey);
        $request->replace(['settings' => ['turnstileSecretKey' => '']]);
        SettingSaveAction::run($request);
        $this->assertSame('saved-private-secret', TurnstileSettings::secret());
        $this->get(route('admin.settings.index', ['activeTab' => 'turnstile']))->assertOk()->assertSee('saved-site')->assertDontSee('saved-private-secret')->assertDontSee(settings()->turnstileSecretKey);
    }

    public function test_non_admin_settings_write_and_read_are_forbidden(): void
    {
        $user = $this->admin();
        $user->revokePermissionTo('user-type:admin');
        $this->actingAs($user);
        $this->get(route('admin.settings.index', ['activeTab' => 'turnstile']))->assertForbidden();
        $this->postJson(route('admin.settings.store'), ['settings' => ['turnstileEnabled' => '0']])->assertForbidden();
    }

    public function test_enabling_without_keys_and_unknown_keys_are_rejected(): void
    {
        $this->actingAs($this->admin());
        settings()->turnstileSecretKey = '';
        $this->postJson(route('admin.settings.store'), ['settings' => ['turnstileEnabled' => '1']])->assertUnprocessable()->assertJsonValidationErrors('settings.turnstileEnabled');
        $this->postJson(route('admin.settings.store'), ['settings' => ['turnstileBypass' => '1']])->assertUnprocessable()->assertJsonValidationErrors('settings.turnstileBypass');
        settings()->turnstileSecretKey = Crypt::encryptString('saved-secret');
        foreach (['', null] as $blank) {
            $this->postJson(route('admin.settings.store'), ['settings' => ['turnstileEnabled' => '1', 'turnstileSiteKey' => $blank]])
                ->assertUnprocessable()->assertJsonValidationErrors('settings.turnstileEnabled');
            $this->assertSame('test-site-key', TurnstileSettings::siteKey());
        }
        $this->postJson(route('admin.settings.store'), ['settings' => ['turnstileEnabled' => '0', 'turnstileSiteKey' => null]])->assertOk();
        $this->assertSame('', TurnstileSettings::siteKey());
    }
}
