<?php

namespace Tests\Feature;

use App\Actions\Study\AuthorizeStudyAccess;
use App\Actions\Study\AuthorizeStudyChange;
use App\Actions\Study\EnsureStudyWeek;
use App\Enums\RoleTypeEnum;
use App\Models\Role;
use App\Models\Season;
use App\Models\StudyWeek;
use App\Models\StudyWeeklyTarget;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Concerns\UsesStudyTimezone;
use Tests\TestCase;

class StudyTrackingFoundationTest extends TestCase
{
    use DatabaseTransactions;
    use UsesStudyTimezone;

    private User $actor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useStudyTimezone();
        $this->travelTo(now()->setDateTime(2026, 10, 14, 12, 0, 0));
        foreach (['study:access', 'study:view', 'study:update', 'dashboard:access', 'users:view', ...RoleTypeEnum::values()] as $name) {
            Permission::findOrCreate($name, 'web');
        }
        $this->actor = User::factory()->create();
        $this->actor->givePermissionTo('study:access');
        $this->actingAs($this->actor);
        settings()->set(['studyTrackingEnabled' => '1', 'studyTrackingStartedOn' => '2026-10-01', 'studyAllowPastActivities' => '0']);
        settings()->save();
    }

    private function assertDenied(callable $action, int $status): void
    {
        try {
            $action();
            $this->fail('Expected rejection.');
        } catch (HttpException $exception) {
            $this->assertSame($status, $exception->getStatusCode());
        }
    }

    public function test_all_existing_account_types_can_access_self_but_not_other_accounts(): void
    {
        $other = User::factory()->create();
        foreach (RoleTypeEnum::values() as $type) {
            $actor = User::factory()->create();
            $actor->givePermissionTo(['study:access', $type]);
            $this->actingAs($actor);
            AuthorizeStudyAccess::run($actor, write: true);
            $this->assertDenied(fn () => AuthorizeStudyAccess::run($other), 403);
        }
        $this->actingAs($this->actor);
        $this->actor->revokePermissionTo('study:access');
        $this->assertDenied(fn () => AuthorizeStudyAccess::run($this->actor), 403);
        auth()->logout();
        $this->assertDenied(fn () => AuthorizeStudyAccess::run($other), 403);
    }

    public function test_teacher_is_scoped_to_own_students_and_permissions_are_rechecked(): void
    {
        $own = User::factory()->create();
        $other = User::factory()->create();
        $this->actor->givePermissionTo(['user-type:teacher', 'dashboard:access', 'users:view', 'study:view', 'study:update']);
        $this->actor->students()->attach($own->id);
        AuthorizeStudyAccess::run($own);
        AuthorizeStudyAccess::run($own, write: true);
        $this->assertDenied(fn () => AuthorizeStudyAccess::run($other), 403);
        $this->actor->revokePermissionTo('study:update');
        $this->assertDenied(fn () => AuthorizeStudyAccess::run($own, write: true), 403);
        $this->actor->revokePermissionTo('users:view');
        $this->assertDenied(fn () => AuthorizeStudyAccess::run($own), 403);
    }

    public function test_admin_can_manage_another_user_with_existing_panel_permissions(): void
    {
        $this->actor->givePermissionTo(['user-type:admin', 'dashboard:access', 'users:view', 'study:view', 'study:update']);
        AuthorizeStudyAccess::run(User::factory()->create(), write: true);
        $this->actor->givePermissionTo('user-type:teacher');
        AuthorizeStudyAccess::run(User::factory()->create(), write: true);
        $this->assertTrue(true);
    }

    public function test_teacher_change_combines_student_ownership_with_week_rules(): void
    {
        $own = User::factory()->create();
        $other = User::factory()->create();
        $this->actor->givePermissionTo(['user-type:teacher', 'dashboard:access', 'users:view', 'study:view', 'study:update']);
        $this->actor->students()->attach($own->id);
        AuthorizeStudyChange::run($own, 'activity', '2026-10-14');
        AuthorizeStudyChange::run($own, 'target', '2026-10-10');
        $this->assertDenied(fn () => AuthorizeStudyChange::run($other, 'activity', '2026-10-14'), 403);
        $this->assertDenied(fn () => AuthorizeStudyChange::run($own, 'activity', '2026-10-09'), 403);
        settings()->set('studyAllowPastActivities', '1');
        settings()->save();
        AuthorizeStudyChange::run($own, 'activity', '2026-10-09');
        $this->assertDenied(fn () => AuthorizeStudyChange::run($own, 'target', '2026-10-09'), 403);
    }

    public static function closedWeeks(): array
    {
        return [['user-type:user'], ['user-type:teacher']];
    }

    #[DataProvider('closedWeeks')]
    public function test_past_activity_switch_never_unlocks_old_targets(string $type): void
    {
        $this->actor->givePermissionTo($type);
        $this->assertDenied(fn () => AuthorizeStudyChange::run($this->actor, 'activity', '2026-10-09'), 403);
        settings()->set('studyAllowPastActivities', '1');
        settings()->save();
        AuthorizeStudyChange::run($this->actor, 'activity', '2026-10-09');
        $this->assertDenied(fn () => AuthorizeStudyChange::run($this->actor, 'target', '2026-10-09'), 403);
    }

    public function test_admin_can_edit_closed_week_but_cannot_bypass_start_or_future_date(): void
    {
        $this->actor->givePermissionTo('user-type:admin');
        AuthorizeStudyChange::run($this->actor, 'target', '2026-10-09');
        AuthorizeStudyChange::run($this->actor, 'activity', '2026-10-09');
        $this->assertDenied(fn () => AuthorizeStudyChange::run($this->actor, 'activity', '2026-09-30'), 422);
        $this->assertDenied(fn () => AuthorizeStudyChange::run($this->actor, 'activity', '2026-10-15'), 422);
        $this->assertDenied(fn () => AuthorizeStudyChange::run($this->actor, 'target', '2026-10-17'), 422);
    }

    public function test_current_week_target_is_full_even_when_activation_was_midweek(): void
    {
        settings()->set('studyTrackingStartedOn', '2026-10-14');
        settings()->save();
        AuthorizeStudyChange::run($this->actor, 'target', '2026-10-10');
        AuthorizeStudyChange::run($this->actor, 'target', '2026-10-16');
        $this->assertDenied(fn () => AuthorizeStudyChange::run($this->actor, 'activity', '2026-10-13'), 422);
        AuthorizeStudyChange::run($this->actor, 'activity', '2026-10-14');
        // Reading historical automatic work has no manual-date restriction.
        AuthorizeStudyAccess::run($this->actor);
        $this->assertDenied(fn () => AuthorizeStudyChange::run($this->actor, 'invalid', '2026-10-14'), 422);
        settings()->set('studyTrackingEnabled', '0');
        settings()->save();
        $this->assertDenied(fn () => AuthorizeStudyChange::run($this->actor, 'activity', '2026-10-14'), 403);
    }

    public function test_friday_is_editable_and_saturday_closes_the_previous_week(): void
    {
        $this->travelTo(now()->setDateTime(2026, 10, 16, 23, 59, 59));
        AuthorizeStudyChange::run($this->actor, 'activity', '2026-10-10');
        $this->travelTo(now()->addSecond());
        $this->assertDenied(fn () => AuthorizeStudyChange::run($this->actor, 'activity', '2026-10-16'), 403);
        AuthorizeStudyChange::run($this->actor, 'activity', '2026-10-17');
    }

    public function test_weeks_are_idempotent_shared_between_seasons_and_targets_are_unique(): void
    {
        $week = EnsureStudyWeek::run('2026-10-14');
        $this->assertSame($week->id, EnsureStudyWeek::run('2026-10-16')->id);
        $this->assertNotSame($week->id, EnsureStudyWeek::run('2026-10-17')->id);
        $this->assertSame('2026-10-10', $week->starts_on->toDateString());
        $this->assertSame('2026-10-16', $week->ends_on->toDateString());
        foreach ([['2026-10-01', '2026-10-14'], ['2026-10-15', '2026-10-31']] as [$start, $end]) {
            $season = new Season(['start_date' => $start, 'end_date' => $end]);
            $this->assertTrue(StudyWeek::forSeason($season)->whereKey($week->id)->exists());
        }
        $target = $this->actor->studyTargets()->create(['study_week_id' => $week->id, 'questions' => 100, 'pages' => null, 'minutes' => 150]);
        $this->assertSame($this->actor->id, $target->user->id);
        $this->assertSame($week->id, $target->week->id);
        $this->assertSame(150, $target->minutes);
        $this->assertNull($target->pages);
        $this->expectException(QueryException::class);
        $this->actor->studyTargets()->create(['study_week_id' => $week->id, 'questions' => 200]);
    }

    public function test_daily_fragments_and_change_history_are_independent_of_question_tables(): void
    {
        $before = [DB::table('questions')->count(), DB::table('answers')->count()];
        $week = EnsureStudyWeek::run('2026-10-14');
        foreach ([50, 20, 20] as $questions) {
            $entry = $this->actor->studyActivities()->create(['study_week_id' => $week->id, 'recorded_by_id' => $this->actor->id,
                'activity_date' => '2026-10-14', 'questions' => $questions, 'pages' => 2, 'minutes' => 30]);
        }
        $this->assertSame(90, (int) $this->actor->studyActivities()->sum('questions'));
        $this->assertSame(90, (int) $week->activities()->sum('questions'));
        $entry->revisions()->create(['actor_id' => $this->actor->id, 'operation' => 'update', 'before' => ['questions' => 20], 'after' => ['questions' => 25]]);
        $this->assertSame(['questions' => 20], $entry->revisions()->first()->before);
        $this->assertSame($this->actor->id, $entry->recordedBy->id);
        $this->assertSame($this->actor->id, $entry->revisions()->first()->actor->id);
        $target = StudyWeeklyTarget::create(['user_id' => $this->actor->id, 'study_week_id' => $week->id, 'questions' => 100]);
        $revision = $target->revisions()->create(['actor_id' => $this->actor->id, 'operation' => 'create', 'after' => ['questions' => 100]]);
        $this->assertSame($target->id, $revision->target->id);
        $this->assertEquals($before, [DB::table('questions')->count(), DB::table('answers')->count()]);
        $this->assertFalse(Schema::hasColumn('teacher_student', 'lesson_id'));
    }

    public function test_permission_migration_is_idempotent_allows_all_web_roles_and_limits_panel_grants(): void
    {
        $student = Role::create(['name' => 'study-fixture-student', 'guard_name' => 'web']);
        $teacher = Role::create(['name' => 'study-fixture-teacher', 'guard_name' => 'web']);
        $admin = Role::create(['name' => 'study-fixture-admin', 'guard_name' => 'web']);
        $api = Role::create(['name' => 'study-fixture-api', 'guard_name' => 'api']);
        $teacher->givePermissionTo('user-type:teacher');
        $admin->givePermissionTo('user-type:admin');
        $migration = require database_path('migrations/2026_10_14_100100_grant_study_tracking_permissions.php');
        $migration->up();
        $count = DB::table('role_has_permissions')->count();
        $migration->up();
        $this->assertSame($count, DB::table('role_has_permissions')->count());
        foreach ([$student, $teacher, $admin] as $role) {
            $this->assertTrue($role->refresh()->hasPermissionTo('study:access'));
        }
        $this->assertFalse($student->hasPermissionTo('study:view'));
        $this->assertTrue($teacher->hasPermissionTo('study:update'));
        $this->assertTrue($admin->hasPermissionTo('study:view'));
        $this->assertSame(0, $api->permissions()->count());
        $this->assertSame(3, Permission::where('name', 'like', 'study:%')->where('guard_name', 'web')->count());
        $migration->down();
        $this->assertTrue($admin->refresh()->hasPermissionTo('study:view'));
    }
}
