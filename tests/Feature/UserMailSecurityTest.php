<?php

namespace Tests\Feature;

use App\Livewire\Stats\SendEmailForm;
use App\Livewire\Users\MailingForm;
use App\Mail\SendEmailToUserMail;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class UserMailSecurityTest extends TestCase
{
    use DatabaseTransactions;

    private User $teacher;

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['dashboard:access', 'users:view', 'stats:view', 'user-type:teacher', 'users:send-email'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        $this->teacher = User::factory()->create();
        $this->student = User::factory()->create();
        $this->teacher->givePermissionTo(['dashboard:access', 'users:view', 'stats:view', 'user-type:teacher']);
        $this->teacher->students()->attach($this->student);
        $this->actingAs($this->teacher);
        settings()->set(['mailHost' => 'mail.test', 'mailUsername' => 'test', 'mailPassword' => 'test']);
        settings()->save();
        Mail::fake();
    }

    private function row(User $user, string $email = 'entered@example.com'): array
    {
        return ['user_id' => $user->id, 'email' => $email, 'name' => $user->name, 'surname' => $user->surname, 'success_rate' => 80];
    }

    public function test_panel_read_access_does_not_authorize_single_or_bulk_mail(): void
    {
        Livewire::test(MailingForm::class, ['user' => $this->student])->assertForbidden();
        Livewire::test(SendEmailForm::class, ['emails' => [$this->row($this->student)]])->assertForbidden();
        Mail::assertNothingQueued();
    }

    public function test_single_mail_uses_entered_address_without_database_recipient_matching(): void
    {
        $this->teacher->givePermissionTo('users:send-email');
        Livewire::test(MailingForm::class, ['user' => $this->student])
            ->set('email', 'entered@gmail.com')->set('subject', 'Test subject')->set('content', 'Hello [ad_soyad]')
            ->call('send')->assertHasNoErrors()->assertRedirect();
        Mail::assertQueued(SendEmailToUserMail::class, fn ($mail) => $mail->hasTo('entered@gmail.com') && $mail->email === 'entered@gmail.com');
    }

    public function test_teacher_cannot_open_another_student_mail_or_send_after_relationship_is_removed(): void
    {
        $this->teacher->givePermissionTo('users:send-email');
        Livewire::test(MailingForm::class, ['user' => User::factory()->create()])->assertForbidden();
        $form = Livewire::test(MailingForm::class, ['user' => $this->student]);
        $this->teacher->students()->detach($this->student);
        $form->call('send')->assertForbidden();
        Mail::assertNothingQueued();
    }

    public function test_bulk_mail_accepts_object_rows_and_checks_student_scope_once_without_email_lookup(): void
    {
        $this->teacher->givePermissionTo('users:send-email');
        $queries = [];
        DB::listen(function ($event) use (&$queries) {
            $queries[] = $event;
        });
        Livewire::test(SendEmailForm::class, ['emails' => [
            (object) $this->row($this->student), (object) $this->row($this->student, 'second@example.com'),
        ]])->set('subject', 'Test subject')->set('content', '[ad] [basari_orani] percent')
            ->call('send')->assertDispatched('closeModal');
        Mail::assertQueued(SendEmailToUserMail::class, 2);
        Mail::assertQueued(SendEmailToUserMail::class, fn ($mail) => $mail->hasTo('second@example.com') && str_contains($mail->content, '80 percent'));
        $scopeQueries = array_filter($queries, fn ($query) => str_contains($query->sql, 'teacher_student'));
        $this->assertCount(1, $scopeQueries);
        foreach ($queries as $query) {
            $this->assertNotContains('entered@example.com', $query->bindings);
            $this->assertNotContains('second@example.com', $query->bindings);
        }
    }

    public function test_bulk_scope_preflight_rejects_a_foreign_student_before_any_enqueue(): void
    {
        $this->teacher->givePermissionTo('users:send-email');
        $other = User::factory()->create();
        Livewire::test(SendEmailForm::class, ['emails' => [$this->row($this->student), $this->row($other)]])
            ->assertSet('recipientCount', 1)
            ->set('subject', 'Test subject')->set('content', 'Message content')
            ->call('send')->assertForbidden();
        Mail::assertNothingQueued();
    }

    public function test_bulk_mail_rechecks_revoked_permission_and_rejects_missing_student_identity(): void
    {
        $this->teacher->givePermissionTo('users:send-email');
        $form = Livewire::test(SendEmailForm::class, ['emails' => [$this->row($this->student)]]);
        $this->teacher->revokePermissionTo('users:send-email');
        $form->call('send')->assertForbidden();
        $this->teacher->givePermissionTo('users:send-email');
        Livewire::test(SendEmailForm::class, ['emails' => [['email' => 'entered@example.com']]])
            ->set('subject', 'Test subject')->set('content', 'Message content')->call('send')
            ->assertHasErrors('emails.0.user_id');
        Mail::assertNothingQueued();
    }

    public function test_enqueue_failure_is_logged_without_exposing_exception_in_ui(): void
    {
        $this->teacher->givePermissionTo('users:send-email');
        $pending = \Mockery::mock();
        $pending->shouldReceive('later')->once()->andThrow(new \RuntimeException('queue unavailable'));
        $nextPending = \Mockery::mock();
        $nextPending->shouldReceive('later')->once();
        Mail::shouldReceive('to')->once()->with('entered@example.com')->andReturn($pending);
        Mail::shouldReceive('to')->once()->with('second@example.com')->andReturn($nextPending);
        Log::shouldReceive('warning')->once()->withArgs(fn ($message, $context) => $message === 'User mail enqueue failed' && $context['recipient'] === 'entered@example.com' && $context['actor_id'] === $this->teacher->id);
        Livewire::test(SendEmailForm::class, ['emails' => [$this->row($this->student), $this->row($this->student, 'second@example.com')]])
            ->set('subject', 'Test subject')->set('content', 'Message content')->call('send')
            ->assertDispatched('closeModal')->assertDontSee('queue unavailable');
    }

    public function test_non_teacher_stats_sender_can_send_without_student_or_email_database_lookup(): void
    {
        $actor = User::factory()->create();
        $actor->givePermissionTo(['dashboard:access', 'stats:view', 'users:send-email']);
        $this->actingAs($actor);
        Livewire::test(SendEmailForm::class, ['emails' => [$this->row($this->student)]])
            ->set('subject', 'Test subject')->set('content', 'Hello [ad_soyad]')
            ->call('send')->assertDispatched('closeModal');
        Mail::assertQueued(SendEmailToUserMail::class, fn ($mail) => $mail->hasTo('entered@example.com') && str_contains($mail->render(), $this->student->display_name));
    }

    public function test_single_enqueue_failure_is_logged_without_a_new_error_screen(): void
    {
        $this->teacher->givePermissionTo('users:send-email');
        $pending = \Mockery::mock();
        $pending->shouldReceive('later')->once()->andThrow(new \RuntimeException('queue unavailable'));
        Mail::shouldReceive('to')->once()->with('entered@gmail.com')->andReturn($pending);
        Log::shouldReceive('warning')->once()->withArgs(fn ($message, $context) => $message === 'User mail enqueue failed' && $context['user_id'] === $this->student->id);
        Livewire::test(MailingForm::class, ['user' => $this->student])
            ->set('email', 'entered@gmail.com')->set('subject', 'Test subject')->set('content', 'Message content')
            ->call('send')->assertRedirect()->assertDontSee('queue unavailable');
    }

    public function test_a_student_without_email_does_not_block_other_bulk_recipients(): void
    {
        $this->teacher->givePermissionTo('users:send-email');
        $withoutEmail = array_replace($this->row($this->student), ['email' => null]);
        Livewire::test(SendEmailForm::class, ['emails' => [$withoutEmail, $this->row($this->student)]])
            ->assertSet('recipientCount', 1)
            ->set('subject', 'Test subject')->set('content', 'Message content')
            ->call('send')->assertHasNoErrors()->assertDispatched('closeModal');
        Mail::assertQueued(SendEmailToUserMail::class, 1);
    }
}
