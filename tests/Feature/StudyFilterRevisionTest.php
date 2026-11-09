<?php

namespace Tests\Feature;

use App\Actions\Study\EnsureStudyWeek;
use App\Helpers\StudyPanelQuery;
use App\Livewire\Backend\Study\StudyCommunication;
use App\Models\Language;
use App\Models\StudyActivityEntry;
use App\Models\StudyWeeklyTarget;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StudyFilterRevisionTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDateTime(2026, 11, 9, 12, 0));
        Language::firstOrCreate(['code' => 'nl'], ['name' => 'Nederlands', 'status' => 'active']);
        foreach (['dashboard:access', 'users:view', 'user-type:admin', 'user-type:teacher', 'user-type:user', 'study:access', 'study:view', 'study:communicate', 'communications:view', 'communications:send'] as $name) {
            Permission::findOrCreate($name, 'web');
        }
        $this->admin = User::factory()->create();
        $this->admin->givePermissionTo(['dashboard:access', 'users:view', 'user-type:admin', 'study:access', 'study:view', 'study:communicate', 'communications:view', 'communications:send']);
        $this->actingAs($this->admin);
        settings()->set(['studyTrackingEnabled' => '1', 'studyTrackingStartedOn' => '2026-10-01']);
        settings()->save();
    }

    protected function tearDown(): void
    {
        $this->travelBack();
        parent::tearDown();
    }

    private function pupil(?array $goals = null, array $actual = [0, 0, 0]): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo('user-type:user');
        $week = EnsureStudyWeek::run('2026-11-07');
        if ($goals !== null) {
            StudyWeeklyTarget::create(['user_id' => $user->id, 'study_week_id' => $week->id,
                'questions' => $goals[0], 'pages' => $goals[1], 'minutes' => $goals[2]]);
        }
        StudyActivityEntry::create(['user_id' => $user->id, 'study_week_id' => $week->id,
            'recorded_by_id' => $this->admin->id, 'submission_key' => (string) Str::uuid(), 'activity_date' => '2026-11-09',
            'questions' => $actual[0], 'pages' => $actual[1], 'minutes' => $actual[2]]);

        return $user;
    }

    private function ids(array $filters): array
    {
        return array_column($this->getJson(route('admin.study.data', ['week' => '2026-11-07', 'limit' => 100] + $filters))->assertOk()->json('rows'), 'id');
    }

    public function test_goal_presence_is_independent_for_each_metric_and_zero_is_not_a_goal(): void
    {
        $questions = $this->pupil([1, 0, 0]);
        $pages = $this->pupil([0, 1, 0]);
        $minutes = $this->pupil([0, 0, 1]);
        $zero = $this->pupil([0, 0, 0]);
        $absent = $this->pupil();
        $this->assertEqualsCanonicalizing([$questions->id, $pages->id, $minutes->id], $this->ids(['state' => 'has_target']));
        foreach (['target_questions' => $questions, 'target_pages' => $pages, 'target_minutes' => $minutes] as $state => $student) {
            $this->assertSame([$student->id], $this->ids(['state' => $state]));
        }
        $this->assertEqualsCanonicalizing([$zero->id, $absent->id], $this->ids(['state' => 'no_target']));
    }

    public function test_percentage_filters_use_strict_boundaries_and_keep_existing_on_track_scope(): void
    {
        $scores = [];
        foreach ([0, 249, 250, 499, 500, 501, 699, 700, 701, 749, 750, 751, 1000, 1001] as $amount) {
            $scores[$this->pupil([1000, 0, 0], [$amount, 0, 0])->id] = $amount / 10;
        }
        $this->pupil(null, [20, 0, 0]);
        foreach (['below_25' => [25, false], 'low' => [50, false], 'below_75' => [75, false],
            'above_50' => [50, true], 'above_70' => [70, true], 'above_100' => [100, true]] as $state => [$boundary, $above]) {
            $expected = array_keys(array_filter($scores, fn ($score) => $above ? $score > $boundary : $score < $boundary));
            $this->assertEqualsCanonicalizing($expected, $this->ids(['state' => $state]), $state);
        }
        $this->assertEqualsCanonicalizing(array_keys(array_filter($scores, fn ($score) => $score >= 50)), $this->ids(['state' => 'on_track']));
        $keys = array_keys(StudyPanelQuery::states());
        $this->assertSame(array_search('no_activity', $keys) + 1, array_search('on_track', $keys));
    }

    public function test_teacher_group_filter_scopes_table_export_and_communication_and_is_admin_only(): void
    {
        $role = Role::findOrCreate('School group', 'web');
        $role->givePermissionTo('user-type:teacher');
        $teacher = User::factory()->create(['name' => 'Chosen teacher']);
        $teacher->assignRole($role);
        $teacher->givePermissionTo(['dashboard:access', 'users:view', 'study:access', 'study:view']);
        $chosen = $this->pupil([100, 0, 0], [75, 0, 0]);
        $other = $this->pupil([100, 0, 0], [75, 0, 0]);
        $teacher->students()->attach($chosen);
        $filters = ['teacher_id' => $teacher->id, 'state' => 'above_70'];
        $this->assertSame([$chosen->id], $this->ids($filters));
        $this->get(route('admin.study.index'))->assertOk()->assertSee('id="study-teacher"', false)->assertSee('Chosen teacher');
        $this->getJson(route('admin.study.data', ['teacher_id' => $other->id]))->assertUnprocessable();
        $this->getJson(route('admin.study.data', ['teacher_id' => '1 OR 1=1']))->assertUnprocessable();
        $export = $this->get(route('admin.study.export', ['week' => '2026-11-07'] + $filters))->assertOk();
        $book = IOFactory::load($export->baseResponse->getFile()->getPathname());
        $rows = $book->getActiveSheet()->toArray();
        $this->assertCount(2, $rows);
        $this->assertSame($chosen->email, $rows[1][1]);
        $book->disconnectWorksheets();
        $composition = Livewire::test(StudyCommunication::class, ['weekStart' => '2026-11-07', 'filters' => $filters])->assertSet('recipients', [$chosen->id]);
        $teacher->students()->detach($chosen);
        $composition->call('next')->assertForbidden();
        $teacher->students()->attach($chosen);
        $this->actingAs($teacher);
        $this->get(route('admin.study.index'))->assertOk()->assertDontSee('id="study-teacher"', false);
        $this->getJson(route('admin.study.data', ['teacher_id' => $teacher->id]))->assertUnprocessable();
        $this->assertSame([$chosen->id], $this->ids([]));
        Http::assertNothingSent();
    }
}
