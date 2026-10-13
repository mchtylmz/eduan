<?php

namespace Tests\Feature;

use App\Enums\StatusEnum;
use App\Helpers\TranslationHelper;
use App\Livewire\Frontend\Account\ProfileForm;
use App\Livewire\Frontend\Account\UpdatePasswordForm;
use App\Livewire\Frontend\Auth\Login;
use App\Livewire\Frontend\Auth\Register;
use App\Models\Language;
use App\Models\Test;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class FrontendAccountPresentationTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        settings()->registerStatus = 'active';
        foreach (['nl', 'en', 'tr'] as $locale) {
            Language::firstOrCreate(['code' => $locale], ['name' => $locale, 'status' => StatusEnum::ACTIVE]);
        }
    }

    public static function locales(): array
    {
        return [['nl', 'Telefoonnummer'], ['en', 'Phone number'], ['tr', 'Telefon Numarası']];
    }

    #[DataProvider('locales')]
    public function test_auth_forms_are_in_initial_html_and_phone_is_translated(string $locale, string $phone): void
    {
        $this->get(route('login').'?lang='.$locale)->assertOk()->assertSee('wire:model="username"', false)->assertDontSee('__lazyLoad', false);
        $this->get(route('frontend.register').'?lang='.$locale)->assertOk()->assertSee('wire:model="phone"', false)->assertSee($phone)->assertDontSee('__lazyLoad', false);
        $this->assertSame($phone, (new Register)->validationAttributes()['phone']);
    }

    public function test_panel_json_rebuild_keeps_phone_defaults_and_custom_translations(): void
    {
        $source = lang_path();
        $temporary = sys_get_temp_dir().'/eduan-account-translations-'.bin2hex(random_bytes(8));
        mkdir($temporary);
        try {
            foreach (self::locales() as [$locale, $phone]) {
                mkdir($temporary.'/'.$locale);
                copy($source.'/'.$locale.'/account.php', $temporary.'/'.$locale.'/account.php');
            }
            app()->useLangPath($temporary);
            foreach (self::locales() as [$locale, $phone]) {
                foreach ([null, '', 'Telefon Numarası'] as $missing) {
                    TranslationHelper::build($locale, ['Telefon Numarası' => $missing, 'Other label' => 'Keep this']);
                    $json = json_decode(file_get_contents($temporary.'/'.$locale.'.json'), true, flags: JSON_THROW_ON_ERROR);
                    $this->assertSame($phone, $json['Telefon Numarası']);
                    $this->assertSame('Keep this', $json['Other label']);
                }
                TranslationHelper::build($locale, ['Telefon Numarası' => 'Custom phone label']);
                $this->assertSame('Custom phone label', json_decode(file_get_contents($temporary.'/'.$locale.'.json'), true)['Telefon Numarası']);
            }
        } finally {
            app()->useLangPath($source);
            foreach (self::locales() as [$locale]) {
                @unlink($temporary.'/'.$locale.'.json');
                @unlink($temporary.'/'.$locale.'/account.php');
                @rmdir($temporary.'/'.$locale);
            }
            @rmdir($temporary);
        }
    }

    public function test_profile_shows_only_current_user_and_conditional_navigation_in_initial_html(): void
    {
        $owner = User::factory()->create(['name' => 'Current Member', 'surname' => 'Account']);
        $other = User::factory()->create(['name' => 'Other Private Member', 'surname' => 'Account']);
        settings()->leagueStatus = 'passive';
        $this->actingAs($owner)->get(route('frontend.profile').'?lang=en')->assertOk()
            ->assertSee($owner->email)->assertDontSee($other->email)
            ->assertSee('member-navigation', false)->assertSee('aria-current="page"', false)
            ->assertSee('id="profile-name"', false)->assertSee('id="current_password"', false)
            ->assertDontSee('__lazyLoad', false)->assertDontSee(route('frontend.my.leagues'), false)
            ->assertDontSee(route('admin.home.index'), false)
            ->assertSee('noindex, nofollow', false);
        $owner->givePermissionTo(Permission::findOrCreate('dashboard:access', 'web'));
        settings()->leagueStatus = 'active';
        $this->get(route('frontend.profile').'?lang=en')->assertOk()
            ->assertSee(route('admin.home.index'), false)->assertSee(route('frontend.my.leagues'), false);
    }

    public function test_profile_save_validates_and_updates_only_the_current_user(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $this->actingAs($owner);
        $form = Livewire::test(ProfileForm::class)->set('name', '')->call('save')->assertHasErrors(['name' => 'required']);
        $form->set('name', 'Updated Member')->set('phone', '+32 123 45 67 89')->call('save')->assertHasNoErrors();
        $this->assertSame('Updated Member', $owner->fresh()->name);
        $this->assertSame('+32 123 45 67 89', $owner->fresh()->phone);
        $this->assertSame($other->name, $other->fresh()->name);
        Mail::assertNothingSent();
    }

    public function test_account_pages_share_sidebar_without_cover_or_menu_descriptions(): void
    {
        $owner = User::factory()->create();
        $exam = Test::factory()->create(['code' => 'account-layout-'.fake()->uuid()]);
        $this->actingAs($owner);
        settings()->coverAccount = '/uploads/account-layout-cover.jpg';
        settings()->leagueStatus = 'active';

        foreach (['frontend.profile', 'frontend.favorite', 'frontend.solved', 'frontend.solved.notTests', 'frontend.solved.exams', 'frontend.stats', 'frontend.my.leagues', 'frontend.solved.exams.detail'] as $route) {
            $response = $this->get(route($route, $route === 'frontend.solved.exams.detail' ? ['test' => $exam->code] : []).'?lang=en');
            $response->assertOk()->assertSee('col-md-3 member-sidebar', false)
                ->assertSee('col-md-9 member-content', false)->assertSee('member-menu-toggle', false)
                ->assertSee('aria-controls="member-account-menu"', false)
                ->assertSee('aria-current="page"', false)
                ->assertDontSee('breadcrumb-area', false)->assertDontSee('/uploads/account-layout-cover.jpg', false)
                ->assertDontSee(__('account.favorites_hint'));
            $this->assertSame(1, preg_match_all('/<h1\b/', $response->getContent()));
        }
    }

    public function test_password_form_preserves_current_password_validation_and_updates_only_owner(): void
    {
        $owner = User::factory()->create(['password' => 'Old-password1!']);
        $other = User::factory()->create(['password' => 'Other-password1!']);
        $this->actingAs($owner);
        $form = Livewire::test(UpdatePasswordForm::class)->set('current_password', 'Wrong-password')->set('password', 'New-password1!')->set('password_confirmation', 'New-password1!');
        $form->call('submit')->assertHasErrors(['current_password']);
        $this->assertTrue(Hash::check('Old-password1!', $owner->fresh()->password));
        $form->set('current_password', 'Old-password1!')->call('submit')->assertHasNoErrors();
        $this->assertTrue(Hash::check('New-password1!', $owner->fresh()->password));
        $this->assertTrue(Hash::check('Other-password1!', $other->fresh()->password));
    }

    public function test_login_and_register_validation_remain_active(): void
    {
        $before = User::count();
        Livewire::test(Register::class)->set('acceptTerms', false)->call('save')->assertHasErrors(['name', 'email', 'password', 'acceptTerms']);
        $this->assertSame($before, User::count());
        Livewire::test(Login::class)->call('login')->assertHasErrors(['username', 'password']);
        Mail::assertNothingSent();
    }

    public function test_login_still_authenticates_and_redirects_to_profile(): void
    {
        app('session.store')->start();
        app()->rebinding('request', fn ($app, $request) => $request->setLaravelSession($app['session.store']));
        $owner = User::factory()->create(['username' => 'profile-qa@example.test', 'password' => 'Password1!', 'status' => StatusEnum::ACTIVE]);
        Livewire::test(Login::class)->set('username', $owner->username)->set('password', 'Password1!')->call('login')->assertRedirect(route('frontend.profile'));
        $this->assertAuthenticatedAs($owner);
    }
}
