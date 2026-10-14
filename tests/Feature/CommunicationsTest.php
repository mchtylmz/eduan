<?php

namespace Tests\Feature;

use App\Actions\Communications\CreateCommunicationBatch;
use App\Actions\Study\RemoveStudyActivity;
use App\Actions\Study\SaveStudyActivity;
use App\Actions\Study\SaveStudyNote;
use App\Actions\Study\SaveStudyTarget;
use App\Actions\Study\StudyWrite;
use App\Console\Commands\DispatchCommunications;
use App\Jobs\DeliverCommunication;
use App\Livewire\Backend\Communications\CommunicationForm;
use App\Mail\CommunicationMail;
use App\Models\CommunicationBatch;
use App\Models\CommunicationDelivery;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Concerns\UsesStudyTimezone;
use Tests\Concerns\UsesWebCommunications;
use Tests\TestCase;

class CommunicationsTest extends TestCase
{
    use DatabaseTransactions, UsesStudyTimezone, UsesWebCommunications;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupCommunications();
        Queue::fake();
        Mail::fake();
    }

    public function test_recipient_table_and_composer_are_teacher_scoped(): void
    {
        $this->get(route('admin.communications.index'))->assertOk()->assertSee('communication-history-table', false)->assertDontSee('communication-recipients-table', false);
        $this->getJson(route('admin.communications.recipients'))->assertOk()->assertJsonPath('total', 1)->assertJsonPath('rows.0.id', $this->student->id);
        Livewire::test(CommunicationForm::class)->call('start')->set('recipients', [$this->outsider->id])->call('next')->assertForbidden();
        $this->assertSame(0, CommunicationBatch::where('actor_id', $this->staff->id)->count());
    }

    public function test_hybrid_admin_has_all_users_and_student_has_no_panel_access(): void
    {
        $this->staff->givePermissionTo('user-type:admin');
        $this->getJson(route('admin.communications.recipients', ['search' => $this->outsider->username]))->assertJsonPath('total', 1);
        $this->actingAs($this->student);
        $this->get(route('admin.communications.index'))->assertForbidden();
    }

    public function test_read_permission_without_send_cannot_enqueue(): void
    {
        $this->staff->revokePermissionTo('communications:send');
        $this->get(route('admin.communications.index'))->assertOk()->assertDontSee('wire:submit="enqueue"', false);
        Livewire::test(CommunicationForm::class)->call('enqueue')->assertForbidden();
    }

    public function test_repeated_uuid_creates_one_batch_and_personalized_deliveries(): void
    {
        $input = $this->batchInput();
        $a = CreateCommunicationBatch::run($input);
        $b = CreateCommunicationBatch::run($input);
        $this->assertSame($a->id, $b->id);
        $this->assertSame(1, CommunicationDelivery::where('batch_id', $a->id)->count());
        $row = CommunicationDelivery::where('batch_id', $a->id)->first();
        $this->assertSame('Hello '.$this->student->name, $row->title);
        Mail::assertNothingSent();
        Queue::assertNothingPushed();
        Http::assertNothingSent();
    }

    public function test_manual_campaign_uses_five_per_minute_schedule_and_dispatch_is_not_synchronous(): void
    {
        $ids = [$this->student->id];
        foreach (range(1, 5) as $i) {
            $pupil = User::factory()->create();
            $this->staff->students()->attach($pupil);
            $ids[] = $pupil->id;
        }
        $batch = CreateCommunicationBatch::run(array_replace($this->batchInput(), ['recipients' => $ids]));
        $rows = CommunicationDelivery::where('batch_id', $batch->id)->orderBy('id')->get();
        $this->assertTrue($rows[0]->available_at->equalTo(now()));
        $this->assertTrue($rows[5]->available_at->equalTo(now()->addMinute()));
        $command = new DispatchCommunications;
        $command->setLaravel($this->app);
        $this->assertSame(0, $command->handle());
        $this->assertSame(5, CommunicationDelivery::where('batch_id', $batch->id)->where('status', 'scheduled')->count());
        $command = new DispatchCommunications;
        $command->setLaravel($this->app);
        $this->assertSame(0, $command->handle());
        $this->assertSame(5, CommunicationDelivery::where('batch_id', $batch->id)->where('status', 'scheduled')->count());
        Mail::assertNothingSent();
    }

    public function test_mail_job_sends_once_and_escapes_html(): void
    {
        $batch = CreateCommunicationBatch::run(array_replace($this->batchInput(), ['body' => '<script>secret</script>']));
        $row = CommunicationDelivery::where('batch_id', $batch->id)->first();
        $row->update(['status' => 'scheduled']);
        $job = new DeliverCommunication($row->id);
        $job->handle();
        $job->handle();
        $this->assertSame('sent', $row->fresh()->status);
        Mail::assertSent(CommunicationMail::class, 1);
        $html = (new CommunicationMail('Subject', '<script>secret</script>'))->render();
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringNotContainsString('<script>secret</script>', $html);
    }

    public function test_revoked_teacher_scope_is_rechecked_in_worker_and_no_mail_is_sent(): void
    {
        $batch = CreateCommunicationBatch::run($this->batchInput());
        $row = CommunicationDelivery::where('batch_id', $batch->id)->first();
        $row->update(['status' => 'scheduled']);
        $this->staff->students()->detach($this->student);
        (new DeliverCommunication($row->id))->handle();
        $this->assertSame('failed', $row->fresh()->status);
        Mail::assertNothingSent();
    }

    public function test_push_worker_targets_only_local_and_provider_verified_subscriptions(): void
    {
        $this->fixtureSubscription($this->student);
        $this->fixtureSubscription($this->outsider, (string) Str::uuid());
        $this->fakeProvider($this->student, [$this->pushId]);
        $batch = CreateCommunicationBatch::run($this->batchInput('push'));
        $row = CommunicationDelivery::where('batch_id', $batch->id)->first();
        $row->update(['status' => 'scheduled']);
        (new DeliverCommunication($row->id))->handle();
        $this->assertSame('sent', $row->fresh()->status);
        Http::assertSent(fn ($r) => $r->method() === 'POST' && $r['include_subscription_ids'] === [$this->pushId] && $r['idempotency_key'] === $row->idempotency_key);
    }

    public function test_no_push_subscription_skips_without_provider_call(): void
    {
        $batch = CreateCommunicationBatch::run($this->batchInput('push'));
        $row = CommunicationDelivery::where('batch_id', $batch->id)->first();
        $row->update(['status' => 'scheduled']);
        (new DeliverCommunication($row->id))->handle();
        $this->assertSame('skipped', $row->fresh()->status);
        Http::assertNothingSent();
    }

    public function test_provider_failure_is_sanitized_and_not_retried(): void
    {
        $this->fixtureSubscription($this->student);
        $this->fakeProvider($this->student, [$this->pushId], 500);
        $batch = CreateCommunicationBatch::run($this->batchInput('push'));
        $row = CommunicationDelivery::where('batch_id', $batch->id)->first();
        $row->update(['status' => 'scheduled']);
        $job = new DeliverCommunication($row->id);
        $job->handle();
        $job->handle();
        $this->assertSame('failed', $row->fresh()->status);
        $this->assertSame('provider_http_500', $row->fresh()->error_code);
        Http::assertSentCount(1);
    }

    public function test_worker_backlog_cannot_send_more_than_five_in_a_minute(): void
    {
        foreach (range(1, 6) as $i) {
            $batch = CreateCommunicationBatch::run($this->batchInput());
            $row = CommunicationDelivery::where('batch_id', $batch->id)->first();
            $row->update(['status' => 'scheduled']);
            (new DeliverCommunication($row->id))->handle();
        }
        Mail::assertSent(CommunicationMail::class, 5);
        $this->assertSame(1, CommunicationDelivery::where('actor_id', $this->staff->id)->where('status', 'pending')->count());
    }

    public function test_study_changes_from_staff_create_atomic_outbox_but_own_changes_notes_and_noops_do_not(): void
    {
        $target = SaveStudyTarget::run($this->student, '2026-10-10', ['questions' => 100]);
        $this->assertSame(1, CommunicationDelivery::where('user_id', $this->student->id)->count());
        SaveStudyTarget::run($this->student, '2026-10-10', ['questions' => 100], StudyWrite::snapshot($target));
        SaveStudyNote::run($this->student, ['body' => 'No notification', 'student_visible' => true], (string) Str::uuid());
        $this->assertSame(1, CommunicationDelivery::where('user_id', $this->student->id)->count());
        $this->actingAs($this->student);
        SaveStudyActivity::run($this->student, '2026-10-14', ['questions' => 20], (string) Str::uuid());
        $this->assertSame(1, CommunicationDelivery::where('user_id', $this->student->id)->count());
        Queue::assertNothingPushed();
        Http::assertNothingSent();
    }

    public function test_study_update_and_outbox_roll_back_together(): void
    {
        $before = CommunicationDelivery::where('user_id', $this->student->id)->count();
        DB::beginTransaction();
        SaveStudyTarget::run($this->student, '2026-10-10', ['questions' => 100]);
        DB::rollBack();
        $this->assertSame($before, CommunicationDelivery::where('user_id', $this->student->id)->count());
        $this->assertDatabaseMissing('study_weekly_targets', ['user_id' => $this->student->id]);
    }

    public function test_composer_validation_and_success_feedback_keep_delivery_separate(): void
    {
        $component = Livewire::test(CommunicationForm::class);
        $component->call('start')->call('next')->assertHasErrors(['recipients']);
        $component->set('recipients', [$this->student->id])->call('next')->assertSet('step', 2)->call('next')->assertHasErrors(['title', 'body']);
        $component->set('title', 'Title')->set('body', 'Body')->call('next')->assertSet('step', 3)->call('enqueue')->assertHasNoErrors()->assertSee(__('notifications.queued'))->assertSet('step', 0);
        $this->assertSame(1, CommunicationBatch::where('actor_id', $this->staff->id)->count());
    }

    public function test_reusing_submission_with_changed_content_conflicts_without_duplicate_delivery(): void
    {
        $input = $this->batchInput();
        $batch = CreateCommunicationBatch::run($input);
        try {
            CreateCommunicationBatch::run(array_replace($input, ['body' => 'Changed']));
            $this->fail('A different message must not reuse the submission key.');
        } catch (HttpException $exception) {
            $this->assertSame(409, $exception->getStatusCode());
        }
        $this->assertSame(1, CommunicationDelivery::where('batch_id', $batch->id)->count());
        Mail::assertNothingSent();
    }

    public function test_provider_ownership_change_excludes_stale_local_subscription_without_sending(): void
    {
        $this->fixtureSubscription($this->student);
        $this->fakeProvider($this->student, []);
        $batch = CreateCommunicationBatch::run($this->batchInput('push'));
        $row = CommunicationDelivery::where('batch_id', $batch->id)->first();
        $row->update(['status' => 'scheduled']);
        (new DeliverCommunication($row->id))->handle();
        $this->assertSame('skipped', $row->fresh()->status);
        $this->assertSame('no_subscription', $row->fresh()->error_code);
        Http::assertSentCount(1);
        Http::assertNotSent(fn ($request) => $request->method() === 'POST');
    }

    public function test_staff_manual_delete_notifies_once_and_disabled_feature_creates_no_outbox(): void
    {
        $entry = SaveStudyActivity::run($this->student, '2026-10-14', ['questions' => 20], (string) Str::uuid());
        RemoveStudyActivity::run($this->student, $entry->id, StudyWrite::snapshot($entry));
        RemoveStudyActivity::run($this->student, $entry->id, StudyWrite::snapshot($entry));
        $this->assertSame(2, CommunicationDelivery::where('user_id', $this->student->id)->count());
        settings()->set('notificationsEnabled', '0');
        settings()->save();
        SaveStudyTarget::run($this->student, '2026-10-10', ['questions' => 100]);
        $this->assertSame(2, CommunicationDelivery::where('user_id', $this->student->id)->count());
        Http::assertNothingSent();
        Mail::assertNothingSent();
    }

    public function test_busy_delivery_rate_lock_defers_without_sending(): void
    {
        $batch = CreateCommunicationBatch::run($this->batchInput());
        $row = CommunicationDelivery::where('batch_id', $batch->id)->first();
        $row->update(['status' => 'scheduled']);
        $lock = Cache::lock('communications-rate:email', 10);
        $this->assertTrue($lock->get());
        try {
            (new DeliverCommunication($row->id))->handle();
            $this->assertSame('pending', $row->fresh()->status);
            $this->assertTrue($row->fresh()->available_at->equalTo(now()->addMinute()));
            Mail::assertNothingSent();
        } finally {
            $lock->release();
        }
    }

    public function test_interrupted_worker_records_uncertain_failure_without_retry_or_secret_and_preserves_terminal_delivery(): void
    {
        $batch = CreateCommunicationBatch::run($this->batchInput());
        $row = CommunicationDelivery::where('batch_id', $batch->id)->first();
        $job = new DeliverCommunication($row->id);
        foreach (['scheduled', 'sending'] as $status) {
            $row->update(['status' => $status]);
            $job->failed(new \RuntimeException('Sensitive SMTP or provider text'));
            $this->assertSame('failed', $row->fresh()->status);
            $this->assertSame('worker_interrupted', $row->fresh()->error_code);
            $this->assertNotNull($row->fresh()->completed_at);
        }
        $row->refresh()->update(['status' => 'sent', 'error_code' => null]);
        $job->failed(new \RuntimeException('Late failure'));
        $this->assertSame('sent', $row->fresh()->status);
        $this->assertNull($row->fresh()->error_code);
        $job->handle();
        Mail::assertNothingSent();
        Http::assertNothingSent();
        Queue::assertNothingPushed();
        $this->assertTrue($job->failOnTimeout);
        $this->assertSame(1, $job->tries);
    }
}
