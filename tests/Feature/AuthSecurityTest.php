<?php

namespace Tests\Feature;

use App\Actions\Auth\LimitAuthRequests;
use App\Actions\Auth\LoginAction;
use App\Actions\Users\GetUserFromPasswordTokenAction;
use App\Livewire\Frontend\Auth\CreatePassword;
use App\Livewire\Frontend\Auth\RecoverPassword;
use App\Livewire\Frontend\Auth\Register;
use App\Mail\CreatePasswordMail;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Livewire\Exceptions\MethodNotFoundException;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Tests\TestCase;

class AuthSecurityTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->startOfSecond());
        Cache::store('security')->flush();
        Mail::fake();
    }

    protected function tearDown(): void
    {
        $this->travelBack();
        parent::tearDown();
    }

    private function token(User $user, string $token, int $minutesAgo = 0): void
    {
        DB::table('password_reset_tokens')->updateOrInsert(['email' => $user->email], [
            'token' => $token, 'created_at' => now()->subMinutes($minutesAgo),
        ]);
    }

    public function test_reset_token_expiry_and_legacy_and_hashed_formats(): void
    {
        $user = User::factory()->create();
        $token = Str::random(40);
        $this->token($user, $token);
        $this->assertSame($user->id, GetUserFromPasswordTokenAction::run($token)->id);
        $this->assertFalse(GetUserFromPasswordTokenAction::run(strtoupper($token)));
        $this->token($user, hash('sha256', $token));
        $this->assertSame($user->id, GetUserFromPasswordTokenAction::run($token)->id);
        $this->token($user, $token, 60);
        $this->assertFalse(GetUserFromPasswordTokenAction::run($token));
        $this->assertFalse(GetUserFromPasswordTokenAction::run(''));
    }

    public function test_password_update_consumes_token_before_queued_mail_and_cannot_be_replayed(): void
    {
        $user = User::factory()->create();
        $token = Str::random(40);
        $this->token($user, hash('sha256', $token));
        $form = Livewire::test(CreatePassword::class, ['user' => $user, 'token' => $token, 'lazy' => false])
            ->set('password', 'New-password1!')->set('password_confirmation', 'New-password1!');
        $form->call('update')->assertRedirect(route('login'));
        $this->assertTrue(Hash::check('New-password1!', $user->fresh()->password));
        $this->assertSame(0, DB::table('password_reset_tokens')->count());
        Mail::assertQueued(CreatePasswordMail::class, 1);
        $form->call('update')->assertNotFound();
        Mail::assertQueued(CreatePasswordMail::class, 1);
    }

    public function test_open_form_rechecks_expired_or_replaced_token(): void
    {
        $user = User::factory()->create();
        $originalPassword = $user->password;
        $token = Str::random(40);
        $this->token($user, $token);
        $form = Livewire::test(CreatePassword::class, ['user' => $user, 'token' => $token, 'lazy' => false])
            ->set('password', 'New-password1!')->set('password_confirmation', 'New-password1!');
        $this->travel(60)->minutes();
        $form->call('update')->assertNotFound();
        $this->assertSame($originalPassword, $user->fresh()->password);
        $this->travelBack();
        $this->token($user, $token);
        $form = Livewire::test(CreatePassword::class, ['user' => $user, 'token' => $token, 'lazy' => false])
            ->set('password', 'New-password1!')->set('password_confirmation', 'New-password1!');
        $this->token($user, Str::random(40));
        $form->call('update')->assertNotFound();
        $this->assertSame($originalPassword, $user->fresh()->password);
        Mail::assertNothingSent();
        Mail::assertNothingQueued();
    }

    public function test_success_mail_does_not_delete_a_new_reset_token(): void
    {
        $user = User::factory()->create();
        $token = Str::random(40);
        $this->token($user, $token);
        (new CreatePasswordMail($user, 'nl'))->build();
        $this->assertSame($token, DB::table('password_reset_tokens')->value('token'));
    }

    public function test_login_limit_survives_application_cache_clear_and_resets_after_a_minute(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->assertFalse(LoginAction::run('missing@example.test', 'Wrong-password'));
        }
        cache()->flush();
        try {
            LoginAction::run('missing@example.test', 'Wrong-password');
            $this->fail('Altıncı giriş engellenmeliydi.');
        } catch (HttpExceptionInterface $exception) {
            $this->assertSame(429, $exception->getStatusCode());
        }
        $this->travel(61)->seconds();
        $this->assertFalse(LoginAction::run('missing@example.test', 'Wrong-password'));
    }

    public function test_register_and_recover_actions_are_limited_before_validation(): void
    {
        foreach (['register' => Register::class, 'recover' => RecoverPassword::class] as $operation => $component) {
            for ($i = 0; $i < 5; $i++) {
                LimitAuthRequests::run($operation);
            }
            Livewire::test($component, ['lazy' => false])
                ->call($operation === 'register' ? 'save' : 'submit')->assertStatus(429);
        }
        Mail::assertNothingQueued();
    }

    public function test_database_equivalent_usernames_share_the_login_limit(): void
    {
        $user = User::factory()->create(['username' => 'ada@example.test']);
        $this->assertSame($user->id, User::where('username', 'áda@example.test')->value('id'));
        for ($i = 0; $i < 5; $i++) {
            $this->assertFalse(LoginAction::run('ada@example.test', 'Wrong-password'));
        }
        try {
            LoginAction::run('áda@example.test', 'Wrong-password');
            $this->fail('Aynı hesap için alternatif yazımla limit aşılmamalı.');
        } catch (HttpExceptionInterface $exception) {
            $this->assertSame(429, $exception->getStatusCode());
        }
    }

    public function test_recovery_mail_helper_cannot_be_called_directly(): void
    {
        $this->expectException(MethodNotFoundException::class);
        Livewire::test(RecoverPassword::class, ['lazy' => false])
            ->call('sendRecoverPasswordMail', User::factory()->create()->id);
    }
}
