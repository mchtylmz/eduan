<?php

namespace Tests\Feature;

use App\Actions\Study\RemoveStudyActivity;
use App\Actions\Study\SaveStudyActivity;
use App\Actions\Study\SaveStudyTarget;
use App\Actions\Study\StudyWrite;
use App\Models\StudyActivityEntry;
use App\Models\StudyActivityRevision;
use App\Models\StudyTargetRevision;
use App\Models\StudyWeeklyTarget;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Concerns\UsesStudyTimezone;
use Tests\TestCase;

class StudyTrackingEntriesTest extends TestCase
{
    use DatabaseTransactions;
    use UsesStudyTimezone;

    private User $actor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useStudyTimezone();
        $this->travelTo(now()->setDateTime(2026, 10, 14, 12, 0, 0));
        foreach (['study:access', 'study:view', 'study:update', 'dashboard:access', 'users:view', 'user-type:admin', 'user-type:teacher'] as $name) {
            Permission::findOrCreate($name, 'web');
        }
        $this->actor = User::factory()->create();
        $this->actor->givePermissionTo('study:access');
        $this->actingAs($this->actor);
        settings()->set(['studyTrackingEnabled' => '1', 'studyTrackingStartedOn' => '2026-10-01', 'studyAllowPastActivities' => '0']);
        settings()->save();
    }

    private function activity(array $values = [], string $date = '2026-10-14', ?string $key = null): StudyActivityEntry
    {
        return SaveStudyActivity::run($this->actor, $date, $values, $key ?? (string) Str::uuid());
    }

    private function denied(callable $callback, int $status): void
    {
        try {
            $callback();
            $this->fail('Expected rejection.');
        } catch (HttpException $exception) {
            $this->assertSame($status, $exception->getStatusCode());
        }
    }

    public function test_target_is_unique_per_week_and_changes_are_audited_with_no_op_reuse(): void
    {
        $this->actor->givePermissionTo('user-type:admin');
        $target = SaveStudyTarget::run($this->actor, '2026-10-10', ['questions' => 150, 'hours' => '2.5']);
        $this->assertSame(150, $target->minutes);
        $this->assertNull($target->pages);
        $this->assertSame(1, $target->revisions()->count());
        $before = StudyWrite::snapshot($target);
        $updated = SaveStudyTarget::run($this->actor, '2026-10-14', ['questions' => 180, 'pages' => 25, 'hours' => '3'], $before);
        $this->assertSame($target->id, $updated->id);
        SaveStudyTarget::run($this->actor, '2026-10-10', ['questions' => 180, 'pages' => 25, 'hours' => '3'], $before);
        $this->assertSame(2, $updated->revisions()->count());
        $this->assertSame(1, StudyWeeklyTarget::where('user_id', $this->actor->id)->count());
        $revision = $updated->revisions()->latest('id')->first();
        $this->assertEquals($before, $revision->before);
        $this->assertEquals(['questions' => 180, 'pages' => 25, 'minutes' => 180], $revision->after);
        $this->assertSame($this->actor->id, $revision->actor_id);
    }

    public function test_blank_and_zero_targets_are_saved_without_inventing_a_positive_goal(): void
    {
        $target = SaveStudyTarget::run($this->actor, '2026-10-10', []);
        $this->assertNull($target->questions);
        $this->denied(fn () => SaveStudyTarget::run($this->actor, '2026-10-10', ['questions' => 0], StudyWrite::snapshot($target)), 403);
        $this->actor->givePermissionTo('user-type:admin');
        $target = SaveStudyTarget::run($this->actor, '2026-10-10', ['questions' => 0], StudyWrite::snapshot($target));
        $this->assertSame(0, $target->questions);
        $this->assertNull($target->pages);
    }

    public function test_stale_target_cannot_overwrite_another_change(): void
    {
        $this->actor->givePermissionTo('user-type:admin');
        $target = SaveStudyTarget::run($this->actor, '2026-10-10', ['questions' => 100]);
        $old = StudyWrite::snapshot($target);
        SaveStudyTarget::run($this->actor, '2026-10-10', ['questions' => 150], $old);
        $this->denied(fn () => SaveStudyTarget::run($this->actor, '2026-10-10', ['questions' => 200], $old), 409);
        $this->assertSame(150, $target->fresh()->questions);
        $this->assertSame(2, $target->revisions()->count());
    }

    public function test_daily_fragments_sum_and_edit_or_remove_affects_only_one_fragment(): void
    {
        $a = $this->activity(['questions' => 50, 'hours' => '1,5']);
        $b = $this->activity(['questions' => 20]);
        $this->activity(['questions' => 20]);
        $query = StudyActivityEntry::where('user_id', $this->actor->id);
        $this->assertEquals(90, $query->sum('questions'));
        SaveStudyActivity::run($this->actor, '2026-10-14', ['questions' => 25], (string) Str::uuid(), $b->id, StudyWrite::snapshot($b));
        $this->assertEquals(95, $query->sum('questions'));
        RemoveStudyActivity::run($this->actor, $a->id, StudyWrite::snapshot($a));
        RemoveStudyActivity::run($this->actor, $a->id, StudyWrite::snapshot($a));
        $this->assertEquals(45, $query->sum('questions'));
        $this->assertSoftDeleted($a);
        $this->assertSame(2, $a->revisions()->count());
        $this->assertNull($a->revisions()->latest('id')->first()->after);
    }

    public function test_request_key_reuse_is_idempotent_but_new_key_can_add_identical_work(): void
    {
        $key = (string) Str::uuid();
        $a = $this->activity(['questions' => 20], key: $key);
        $same = $this->activity(['questions' => 20], key: $key);
        $this->assertSame($a->id, $same->id);
        $this->assertSame(1, $a->revisions()->count());
        $this->denied(fn () => $this->activity(['questions' => 30], key: $key), 409);
        $new = $this->activity(['questions' => 20]);
        $this->assertNotSame($a->id, $new->id);
        RemoveStudyActivity::run($this->actor, $a->id, StudyWrite::snapshot($a));
        $this->denied(fn () => $this->activity(['questions' => 20], key: $key), 409);
        $this->assertSame(2, StudyActivityEntry::withTrashed()->where('user_id', $this->actor->id)->count());
    }

    public function test_stale_activity_edit_and_delete_are_conflicts(): void
    {
        $entry = $this->activity(['questions' => 20]);
        $old = StudyWrite::snapshot($entry);
        SaveStudyActivity::run($this->actor, '2026-10-14', ['questions' => 30], (string) Str::uuid(), $entry->id, $old);
        $this->denied(fn () => SaveStudyActivity::run($this->actor, '2026-10-14', ['questions' => 40], (string) Str::uuid(), $entry->id, $old), 409);
        $this->denied(fn () => RemoveStudyActivity::run($this->actor, $entry->id, $old), 409);
        $this->assertSame(30, $entry->fresh()->questions);
    }

    public function test_current_week_entry_can_move_to_another_valid_day_and_retain_creator(): void
    {
        $entry = $this->activity(['questions' => 20]);
        $updated = SaveStudyActivity::run($this->actor, '2026-10-11', ['questions' => 20], (string) Str::uuid(), $entry->id, StudyWrite::snapshot($entry));
        $this->assertSame('2026-10-11', $updated->activity_date->toDateString());
        $this->assertSame($this->actor->id, $updated->recorded_by_id);
        $this->assertSame($entry->study_week_id, $updated->study_week_id);
    }

    public function test_closed_record_cannot_be_moved_to_current_week_to_bypass_policy(): void
    {
        settings()->set('studyAllowPastActivities', '1');
        settings()->save();
        $entry = $this->activity(['questions' => 20], '2026-10-09');
        settings()->set('studyAllowPastActivities', '0');
        settings()->save();
        $this->denied(fn () => SaveStudyActivity::run($this->actor, '2026-10-14', ['questions' => 20], (string) Str::uuid(), $entry->id, StudyWrite::snapshot($entry)), 403);
        $this->denied(fn () => RemoveStudyActivity::run($this->actor, $entry->id, StudyWrite::snapshot($entry)), 403);
        $this->assertSame('2026-10-09', $entry->fresh()->activity_date->toDateString());
    }

    public function test_past_switch_unlocks_activity_only_and_admin_can_edit_closed_targets(): void
    {
        settings()->set('studyAllowPastActivities', '1');
        settings()->save();
        $entry = $this->activity([], '2026-10-09');
        $this->assertSame(0, $entry->questions);
        $this->denied(fn () => SaveStudyTarget::run($this->actor, '2026-10-03', ['questions' => 100]), 403);
        $this->actor->givePermissionTo('user-type:admin');
        $this->assertSame(100, SaveStudyTarget::run($this->actor, '2026-10-03', ['questions' => 100])->questions);
    }

    public static function prohibitedDates(): array
    {
        return [['2026-10-15', 422], ['2026-09-30', 422], ['2026-10-09', 403]];
    }

    #[DataProvider('prohibitedDates')]
    public function test_invalid_date_boundaries_write_nothing(string $date, int $status): void
    {
        $before = StudyActivityEntry::count();
        $this->denied(fn () => $this->activity(['questions' => 1], $date), $status);
        $this->assertSame($before, StudyActivityEntry::count());
    }

    public function test_invalid_values_and_request_key_write_nothing(): void
    {
        $before = StudyActivityEntry::count();
        foreach ([['questions' => -1], ['hours' => []], ['pages' => '4294967296']] as $input) {
            try {
                $this->activity($input);
                $this->fail('Expected validation error.');
            } catch (ValidationException) {
                $this->assertSame($before, StudyActivityEntry::count());
            }
        }
        $this->expectException(ValidationException::class);
        $this->activity([], key: 'not-a-uuid');
    }

    public function test_unauthorized_owner_and_revoked_permission_cannot_write(): void
    {
        $other = User::factory()->create();
        $this->denied(fn () => SaveStudyTarget::run($other, '2026-10-10', ['questions' => 1]), 403);
        $this->denied(fn () => SaveStudyActivity::run($other, '2026-10-14', [], (string) Str::uuid()), 403);
        $entry = $this->activity([]);
        $this->actor->revokePermissionTo('study:access');
        $this->denied(fn () => RemoveStudyActivity::run($this->actor, $entry->id, StudyWrite::snapshot($entry)), 403);
    }

    public function test_teacher_can_change_only_own_student_and_revision_identifies_actor(): void
    {
        $student = User::factory()->create();
        $other = User::factory()->create();
        $this->actor->givePermissionTo(['user-type:teacher', 'dashboard:access', 'users:view', 'study:view', 'study:update']);
        $this->actor->students()->attach($student->id);
        $target = SaveStudyTarget::run($student, '2026-10-10', ['questions' => 100]);
        $target = SaveStudyTarget::run($student, '2026-10-10', ['questions' => 150], StudyWrite::snapshot($target));
        $this->assertSame(150, $target->questions);
        $this->assertSame(2, $target->revisions()->count());
        $entry = SaveStudyActivity::run($student, '2026-10-14', ['questions' => 5], (string) Str::uuid());
        $this->assertSame($this->actor->id, $entry->recorded_by_id);
        $this->assertSame($this->actor->id, $entry->revisions()->first()->actor_id);
        $this->denied(fn () => SaveStudyTarget::run($other, '2026-10-10', []), 403);
        $this->actor->students()->detach($student->id);
        $this->denied(fn () => RemoveStudyActivity::run($student, $entry->id, StudyWrite::snapshot($entry)), 403);
    }

    public function test_revision_failure_rolls_back_both_target_and_activity_creation(): void
    {
        $targetCount = StudyWeeklyTarget::count();
        $entryCount = StudyActivityEntry::count();
        StudyTargetRevision::creating(fn () => throw new \RuntimeException('audit failure'));
        try {
            SaveStudyTarget::run($this->actor, '2026-10-10', ['questions' => 100]);
            $this->fail('Expected audit failure.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('audit failure', $exception->getMessage());
            $this->assertSame($targetCount, StudyWeeklyTarget::count());
        } finally {
            StudyTargetRevision::flushEventListeners();
        }
        StudyActivityRevision::creating(fn () => throw new \RuntimeException('audit failure'));
        try {
            $this->activity(['questions' => 20]);
            $this->fail('Expected audit failure.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('audit failure', $exception->getMessage());
            $this->assertSame($entryCount, StudyActivityEntry::count());
        } finally {
            StudyActivityRevision::flushEventListeners();
        }
    }
}
