<?php

namespace Tests\Feature;

use App\Livewire\Backend\Communications\CommunicationForm;
use App\Models\CommunicationBatch;
use App\Models\CommunicationDelivery;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Tests\Concerns\UsesStudyTimezone;
use Tests\Concerns\UsesWebCommunications;
use Tests\TestCase;

class CommunicationWizardTest extends TestCase
{
    use DatabaseTransactions, UsesStudyTimezone, UsesWebCommunications;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupCommunications();
        Queue::fake();
        Mail::fake();
    }

    private function row(array $values = []): CommunicationDelivery
    {
        return CommunicationDelivery::create($values + ['actor_id' => $this->staff->id, 'user_id' => $this->student->id,
            'channel' => 'email', 'title' => 'History '.Str::uuid(), 'body' => 'Private body', 'locale' => 'nl',
            'status' => 'sent', 'available_at' => now(), 'completed_at' => now(), 'idempotency_key' => (string) Str::uuid()]);
    }

    private function preview()
    {
        return Livewire::test(CommunicationForm::class)->call('start')->set('recipients', [$this->student->id])->call('next')
            ->set('title', 'Hello {{name}}')->set('body', 'Message {{surname}}')->call('next')->assertSet('step', 3);
    }

    public function test_history_is_default_shared_bootstrap_table_and_menu_is_above_users_in_management(): void
    {
        $html = $this->get(route('admin.communications.index'))->assertOk()->assertSee('communication-history-table', false)
            ->assertSee('data-side-pagination="server"', false)->assertSee('data-pagination-parts', false)
            ->assertDontSee('communication-recipients-table', false)->getContent();
        $group = strpos($html, 'data-menu-group="management"');
        $communication = strpos($html, 'href="'.route('admin.communications.index').'"');
        $users = strpos($html, 'href="'.route('admin.users.index').'"');
        $this->assertLessThan($communication, $group);
        $this->assertLessThan($users, $communication);
        $this->assertSame(1, substr_count($html, 'href="'.route('admin.communications.index').'"'));
    }

    public function test_history_has_live_pagination_search_filter_sort_and_teacher_scope(): void
    {
        foreach (range(1, 16) as $i) {
            $last = $this->row(['title' => 'Own history '.$i, 'channel' => $i === 16 ? 'push' : 'email', 'status' => $i === 16 ? 'failed' : 'sent']);
        }
        $this->row(['actor_id' => $this->outsider->id, 'title' => 'Other private message']);
        $json = $this->getJson(route('admin.communications.history'))->assertOk()->assertJsonPath('total', 16)->assertJsonCount(15, 'rows')->assertJsonPath('rows.0.id', $last->id)->json();
        $this->assertSame(['id', 'recipient', 'channel', 'title', 'status', 'created_at'], array_keys($json['rows'][0]));
        $this->getJson(route('admin.communications.history', ['offset' => 15]))->assertJsonCount(1, 'rows');
        $this->getJson(route('admin.communications.history', ['search' => 'Own history 16', 'channel' => 'push', 'delivery_status' => 'failed']))->assertJsonPath('total', 1)->assertJsonPath('rows.0.id', $last->id);
        $this->getJson(route('admin.communications.history', ['search' => 'Other private']))->assertJsonPath('total', 0);
        $last->update(['status' => 'sent']);
        $this->getJson(route('admin.communications.history', ['channel' => 'push', 'delivery_status' => 'failed']))->assertJsonPath('total', 0);
        $this->staff->givePermissionTo('user-type:admin');
        $this->getJson(route('admin.communications.history', ['search' => 'Other private']))->assertJsonPath('total', 1);
    }

    public function test_invalid_table_inputs_are_rejected_and_student_cannot_read_history(): void
    {
        foreach ([['sort' => 'body'], ['channel' => 'sms'], ['delivery_status' => 'unknown'], ['limit' => 101], ['offset' => -1]] as $input) {
            $this->getJson(route('admin.communications.history', $input))->assertUnprocessable();
        }
        $this->actingAs($this->student);
        $this->getJson(route('admin.communications.history'))->assertForbidden();
    }

    public function test_three_steps_preview_personalization_and_send_create_only_one_delivery_at_the_end(): void
    {
        $component = $this->preview()->assertSet('preview.count', 1)->assertSee('Hello '.$this->student->name)->assertSee('Message '.$this->student->surname);
        $this->assertSame(0, CommunicationBatch::where('actor_id', $this->staff->id)->count());
        $component->call('enqueue')->assertSet('step', 0)->assertDispatched('refresh-server-table', table: 'communication-history')->assertSee(__('notifications.queued'));
        $this->assertSame(1, CommunicationBatch::where('actor_id', $this->staff->id)->count());
        $this->assertSame(1, CommunicationDelivery::where('actor_id', $this->staff->id)->count());
        Mail::assertNothingSent();
        Http::assertNothingSent();
        Queue::assertNothingPushed();
    }

    public function test_send_cannot_skip_preview_and_preview_step_is_locked(): void
    {
        Livewire::test(CommunicationForm::class)->call('enqueue')->assertStatus(409);
        $this->assertSame(0, CommunicationBatch::where('actor_id', $this->staff->id)->count());
        $this->expectException(CannotUpdateLockedPropertyException::class);
        Livewire::test(CommunicationForm::class)->set('step', 3);
    }

    public function test_changed_message_requires_a_new_preview_without_creating_batch(): void
    {
        $this->preview()->set('body', 'Changed body')->call('enqueue')->assertSet('step', 2)->assertHasErrors('communication')
            ->call('next')->assertSet('step', 3)->assertHasNoErrors()->assertSee('Changed body');
        $this->assertSame(0, CommunicationBatch::where('actor_id', $this->staff->id)->count());
    }

    public function test_scope_and_write_permission_are_rechecked_after_preview(): void
    {
        $component = $this->preview();
        $this->staff->students()->detach($this->student);
        $component->call('enqueue')->assertForbidden();
        $this->assertSame(0, CommunicationDelivery::where('actor_id', $this->staff->id)->count());
        $this->staff->revokePermissionTo('communications:send');
        Livewire::test(CommunicationForm::class)->call('start')->assertForbidden();
    }

    public function test_back_and_cancel_preserve_selection_until_explicit_cancel(): void
    {
        $this->preview()->call('previous')->assertSet('step', 2)->assertSet('body', 'Message {{surname}}')
            ->call('previous')->assertSet('step', 1)->assertSet('recipients', [$this->student->id])
            ->call('cancel')->assertSet('step', 0)->assertSet('recipients', [])->assertSet('preview', []);
        $this->assertSame(0, CommunicationDelivery::where('actor_id', $this->staff->id)->count());
    }

    public function test_preview_is_escaped_and_whitespace_does_not_create_a_message(): void
    {
        $component = Livewire::test(CommunicationForm::class)->call('start')->set('recipients', [$this->student->id])->call('next');
        $component->set('title', '   ')->set('body', '   ')->call('next')->assertHasErrors(['title', 'body'])->assertSet('step', 2);
        $component->set('title', '<script>unsafe</script>')->set('body', '<img src=x onerror=alert(1)>')->call('next')
            ->assertSet('step', 3)->assertSee('&lt;script&gt;', false)->assertDontSee('<script>unsafe</script>', false);
        $this->assertSame(0, CommunicationBatch::where('actor_id', $this->staff->id)->count());
    }
}
