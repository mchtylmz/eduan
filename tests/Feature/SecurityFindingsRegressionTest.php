<?php

namespace Tests\Feature;

use App\Actions\Users\UpdatePasswordAction;
use App\Exports\FilterStatsExamsExport;
use App\Exports\FilterStatsQuestionsExport;
use App\Exports\FilterStatsTestsExport;
use App\Exports\FilterStatsTopicsExport;
use App\Livewire\Ai\AnswerAiForm;
use App\Livewire\Contacts\ContactReplyForm;
use App\Livewire\Events;
use App\Livewire\Faqs\FaqForm;
use App\Livewire\Frontend\Account\UpdatePasswordForm;
use App\Livewire\Frontend\Auth\Login;
use App\Livewire\Languages\LanguageForm;
use App\Livewire\Modal;
use App\Livewire\Newsletters\NewsletterSendForm;
use App\Livewire\Offcanvas;
use App\Mail\NewsletterMail;
use App\Models\AnswerAI;
use App\Models\Contact;
use App\Models\Language;
use App\Models\Newsletter;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SecurityFindingsRegressionTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        settings()->turnstileEnabled = '0';
        Mail::fake();
    }

    private function actor(array $permissions = []): User
    {
        $user = User::factory()->create();
        foreach (['dashboard:access', ...$permissions] as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
        $this->actingAs($user);

        return $user;
    }

    public function test_event_dispatch_rejects_unknown_methods_and_denies_settings_without_permission(): void
    {
        $this->actor();
        settings()->siteLogo = 'keep-logo';
        Livewire::test(Events::class)->call('run', 'settingRemove', ['column' => 'siteLogo'])->assertForbidden();
        Livewire::test(Events::class)->call('run', 'clearCache')->assertForbidden();
        Livewire::test(Events::class)->call('run', 'render')->assertForbidden();
        $this->assertSame('keep-logo', settings()->siteLogo);
    }

    public function test_settings_admin_can_remove_only_supported_assets_and_clear_cache(): void
    {
        $this->actor(['settings:update']);
        settings()->siteLogo = 'old-logo';
        Livewire::test(Events::class)->call('run', 'settingRemove', ['column' => 'turnstileEnabled'])->assertUnprocessable();
        Livewire::test(Events::class)->call('run', 'settingRemove', ['column' => 'siteLogo'])->assertRedirect();
        $this->assertNull(settings()->siteLogo);
        Cache::put('security-regression-cache', 'value');
        Livewire::test(Events::class)->call('run', 'clearCache')->assertOk();
        $this->assertNull(Cache::get('security-regression-cache'));
    }

    public static function formTargets(): array
    {
        return [[FaqForm::class, 'faqs.faq-form'], [LanguageForm::class, 'languages.language-form'], [AnswerAiForm::class, 'ai.answer-ai-form'], [ContactReplyForm::class, 'contacts.contact-reply-form'], [NewsletterSendForm::class, 'newsletters.newsletter-send-form']];
    }

    #[DataProvider('formTargets')]
    public function test_global_overlays_do_not_mount_route_forms_or_class_aliases(string $class, string $alias): void
    {
        $this->actor();
        foreach ([$alias, $class, [$alias], ['tests.select-content-modal-form', $alias]] as $target) {
            Livewire::test(Modal::class)->call('showModal', $target)->assertForbidden();
            Livewire::test(Offcanvas::class)->call('showOffcanvas', $target)->assertForbidden();
        }
    }

    public function test_direct_forms_require_current_fixed_resource_permission(): void
    {
        $actor = $this->actor();
        Livewire::test(FaqForm::class)->assertForbidden();
        Livewire::test(LanguageForm::class, ['lazy' => false])->assertForbidden();
        $answer = new AnswerAI;
        Livewire::test(AnswerAiForm::class, ['answerAI' => $answer])->assertForbidden();
        $contact = Contact::create(['name' => 'Contact', 'email' => 'c@example.test', 'school_name' => 'School', 'message' => 'Private message', 'locale' => 'nl', 'has_read' => 0]);
        Livewire::test(ContactReplyForm::class, ['contact' => $contact])->assertForbidden();
        $this->assertSame(0, (int) $contact->fresh()->has_read->value);
        foreach (['pages:view', 'pages:add'] as $p) {
            $actor->givePermissionTo(Permission::findOrCreate($p, 'web'));
        }
        $form = Livewire::test(FaqForm::class);
        $actor->revokePermissionTo('pages:add');
        $form->call('save')->assertForbidden();
        Mail::assertNothingOutgoing();
    }

    public function test_approved_modal_still_opens_and_target_state_is_locked(): void
    {
        $this->actor(['tests:view']);
        $form = Livewire::test(Modal::class)->call('showModal', 'tests.select-content-modal-form', ['sectionIndex' => 0, 'fieldIndex' => 0])->assertOk()->assertSet('show', true);
        $this->expectException(CannotUpdateLockedPropertyException::class);
        $form->set('component', 'faqs.faq-form');
    }

    public function test_newsletter_send_uses_fixed_permission_even_if_public_property_is_replaced(): void
    {
        $this->actor(['newsletter:view']);
        Livewire::test(NewsletterSendForm::class)->call('send')->assertForbidden();
        try {
            Livewire::test(NewsletterSendForm::class)->set('permission', 'dashboard:access');
            $this->fail('Permission must be locked.');
        } catch (CannotUpdateLockedPropertyException) {
            $this->assertTrue(true);
        }
        Mail::assertNothingOutgoing();
    }

    public function test_authorized_newsletter_send_still_queues_only_selected_recipients(): void
    {
        $this->actor(['newsletter:view', 'newsletter:send']);
        Language::firstOrCreate(['code' => 'nl'], ['name' => 'Nederlands', 'status' => 'active']);
        settings()->mailHost = 'smtp.example.test';
        settings()->mailUsername = 'test-user';
        settings()->mailPassword = 'test-only-placeholder';
        $subscriber = Newsletter::create(['email' => 'one@example.test', 'accept_terms' => true, 'token' => 'test-only-token']);
        Newsletter::create(['email' => 'other@example.test', 'accept_terms' => true, 'token' => 'other-test-token']);
        Livewire::test(NewsletterSendForm::class)->set('locale', 'nl')->set('subject', 'Example subject')->set('content', 'Example newsletter content')->set('subscribeIds', [$subscriber->id])->call('send')->assertRedirect();
        Mail::assertQueued(NewsletterMail::class, fn ($mail) => $mail->hasTo('one@example.test'));
        Mail::assertQueuedCount(1);
    }

    public static function exports(): array
    {
        return [[FilterStatsQuestionsExport::class, 'B2'], [FilterStatsTopicsExport::class, 'A2'], [FilterStatsExamsExport::class, 'A2'], [FilterStatsTestsExport::class, 'A2']];
    }

    #[DataProvider('exports')]
    public function test_real_workbooks_preserve_untrusted_formula_text_and_numeric_values(string $class, string $nameCell): void
    {
        $row = (object) ['name' => '=1+1', 'surname' => '=HYPERLINK("https://example.invalid","link")', 'username' => 'user', 'email' => 'e@example.test', 'topic_title' => 'Topic', 'exam_name' => 'Exam', 'test_name' => 'Test', 'point' => 5, 'question_count' => 10, 'total_questions' => 10, 'correct_count' => 5, 'count_correct' => 5, 'incorrect_count' => 5, 'count_incorrect' => 5, 'success_rate' => 50, 'updated_at' => '2026-10-26'];
        $path = tempnam(sys_get_temp_dir(), 'eduan-safe-xlsx-');
        try {
            file_put_contents($path, Excel::raw(new $class(collect([$row])), \Maatwebsite\Excel\Excel::XLSX));
            $sheet = IOFactory::load($path)->getActiveSheet();
            $this->assertSame('=1+1', $sheet->getCell($nameCell)->getValue());
            $this->assertSame(DataType::TYPE_STRING, $sheet->getCell($nameCell)->getDataType());
            foreach ($sheet->getRowIterator(2) as $r) {
                foreach ($r->getCellIterator() as $cell) {
                    $this->assertNotSame(DataType::TYPE_FORMULA, $cell->getDataType());
                }
            }
            $numberCell = $class === FilterStatsQuestionsExport::class ? 'D2' : ($class === FilterStatsTestsExport::class ? 'F2' : 'E2');
            $this->assertEquals(10, $sheet->getCell($numberCell)->getValue());
            $this->assertSame(DataType::TYPE_NUMERIC, $sheet->getCell($numberCell)->getDataType());
        } finally {
            @unlink($path);
        }
    }

    public function test_password_change_rotates_remember_token_and_old_session_is_rejected(): void
    {
        $victim = User::factory()->create(['password' => 'Old-password1!', 'remember_token' => 'old-persistent-token']);
        $oldProof = auth()->guard()->hashPasswordForCookie($victim->password);
        UpdatePasswordAction::run($victim, 'New-password1!');
        $this->assertNotSame('old-persistent-token', $victim->fresh()->remember_token);
        $this->actingAs($victim->fresh())->withSession(['password_hash_web' => $oldProof]);
        $this->get(route('frontend.profile'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_legacy_session_without_password_proof_must_sign_in_again(): void
    {
        $this->actingAs(User::factory()->create());
        session()->forget('password_hash_web');
        $this->get(route('frontend.profile'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_valid_remember_cookie_can_establish_proof_but_rotated_token_is_rejected(): void
    {
        $user = User::factory()->create(['remember_token' => 'persistent-token']);
        $guard = auth()->guard();
        $name = $guard->getRecallerName();
        $cookie = $user->id.'|persistent-token|'.$guard->hashPasswordForCookie($user->password);
        $this->withCookie($name, $cookie)->get(route('frontend.profile'))->assertOk();
        $this->assertAuthenticatedAs($user);
        $this->assertTrue(session()->has('password_hash_web'));
        UpdatePasswordAction::run($user, 'Changed-password1!');
        auth()->guard()->logoutCurrentDevice();
        session()->flush();
        $user->refresh();
        $this->assertNotSame('persistent-token', $user->remember_token);
        auth()->forgetGuards();
        $this->withCookie($name, $cookie)->get(route('frontend.profile'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_real_login_establishes_session_proof_and_self_password_change_keeps_current_session(): void
    {
        $user = User::factory()->create(['username' => 'proof@example.test', 'password' => 'Old-password1!', 'status' => 'active']);
        app('session.store')->start();
        app()->rebinding('request', fn ($app, $request) => $request->setLaravelSession($app['session.store']));
        Livewire::test(Login::class)->set('username', $user->username)->set('password', 'Old-password1!')->call('login')->assertRedirect();
        $this->assertTrue(session()->has('password_hash_web'));
        Livewire::test(UpdatePasswordForm::class)->set('current_password', 'Old-password1!')->set('password', 'New-password1!')->set('password_confirmation', 'New-password1!')->call('submit')->assertHasNoErrors();
        $this->get(route('frontend.profile'))->assertOk();
        $this->assertAuthenticatedAs($user);
    }
}
