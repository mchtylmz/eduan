<?php

namespace Tests\Feature;

use App\Enums\StatusEnum;
use App\Livewire\Users\FavoriteTable;
use App\Livewire\Users\StudentsTable;
use App\Livewire\Users\TeachersTable;
use App\Livewire\Users\TopicsNotResultsTable;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\Language;
use App\Models\Question;
use App\Models\Test;
use App\Models\TestsResult;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class UserRelatedTableTest extends TestCase
{
    use DatabaseTransactions;

    private User $actor;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['nl', 'tr'] as $locale) {
            Language::firstOrCreate(['code' => $locale], ['name' => $locale, 'status' => StatusEnum::ACTIVE]);
        }
        foreach (['dashboard:access', 'users:view', 'user-type:teacher', 'users:student-delete', 'users:teacher-delete',
            'exams:view', 'tests:view', 'stats:view', 'ai:view', 'seasons:view'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        $this->actor = User::factory()->create();
        $this->actor->givePermissionTo(['dashboard:access', 'users:view', 'exams:view', 'tests:view', 'stats:view']);
        $this->actingAs($this->actor)->withSession(['locale' => 'tr']);
        app()->setLocale('tr');
    }

    public function test_favorites_are_paged_sorted_scoped_and_refreshable(): void
    {
        $student = User::factory()->create();
        $other = User::factory()->create();
        $one = Exam::factory()->create(['code' => 'Related-A', 'name' => 'First', 'locale' => 'tr']);
        $two = Exam::factory()->create(['code' => 'Related-Z', 'name' => 'Second', 'locale' => 'tr']);
        $student->favoriteExams()->attach([$one->id, $two->id]);
        $other->favoriteExams()->attach(Exam::factory()->create()->id);
        $p = ['user_id' => $student->id, 'limit' => 1, 'sort' => 'code', 'order' => 'asc'];
        $this->getJson(route('admin.users.getFavoritesData', $p))->assertOk()->assertJsonPath('total', 2)
            ->assertJsonPath('rows.0.id', $one->id)->assertJsonMissingPath('rows.0.password');
        $this->getJson(route('admin.users.getFavoritesData', [...$p, 'offset' => 1]))->assertJsonPath('rows.0.id', $two->id);
        $this->getJson(route('admin.users.getFavoritesData', [...$p, 'search' => 'Second']))->assertJsonPath('total', 1);
        $student->favoriteExams()->detach($two->id);
        $this->getJson(route('admin.users.getFavoritesData', $p))->assertJsonPath('total', 2);
        $this->getJson(route('admin.users.getFavoritesData', [...$p, 'refresh' => 1]))->assertJsonPath('total', 1);
        $this->actor->givePermissionTo('user-type:teacher');
        $this->actor->students()->attach($student);
        $this->getJson(route('admin.users.getFavoritesData', ['user_id' => $other->id]))->assertForbidden();
        Livewire::test(FavoriteTable::class, ['userId' => $other->id])->assertForbidden();
    }

    public function test_unsolved_topics_exclude_answers_in_both_result_families_only_for_context_user(): void
    {
        $student = User::factory()->create();
        $other = User::factory()->create();
        $topics = Topic::factory()->count(3)->create(['title' => ['tr' => 'Related Topic'], 'status' => StatusEnum::ACTIVE]);
        $exam = ExamResult::create(['exam_id' => Exam::factory()->create()->id, 'user_id' => $student->id, 'completed' => 0]);
        $exam->details()->create(['topic_id' => $topics[0]->id, 'question_id' => Question::factory()->create()->id, 'answer_id' => 0, 'correct' => 0, 'time' => 0]);
        $test = TestsResult::create(['test_id' => Test::factory()->create()->id, 'user_id' => $student->id, 'completed' => 0]);
        $test->details()->create(['topic_id' => $topics[1]->id, 'question_id' => Question::factory()->create()->id, 'answer_id' => 0, 'correct' => 0, 'point' => 0]);
        $foreign = ExamResult::create(['exam_id' => $exam->exam_id, 'user_id' => $other->id]);
        $foreign->details()->create(['topic_id' => $topics[2]->id, 'question_id' => Question::factory()->create()->id, 'answer_id' => 0, 'correct' => 0, 'time' => 0]);
        $p = ['user_id' => $student->id, 'search' => 'Related Topic', 'sort' => 'title', 'status' => 'active'];
        $this->getJson(route('admin.users.getUnsolvedTopicsData', $p))->assertOk()->assertJsonPath('total', 1)->assertJsonPath('rows.0.id', $topics[2]->id);
        $exam->details()->create(['topic_id' => $topics[2]->id, 'question_id' => Question::factory()->create()->id, 'answer_id' => 0, 'correct' => 0, 'time' => 0]);
        $this->getJson(route('admin.users.getUnsolvedTopicsData', $p))->assertJsonPath('total', 0);
        Livewire::test(TopicsNotResultsTable::class, ['user' => $student])->assertSee('user-unsolved-topics-table')->assertDontSee('wire:lazy', false);
    }

    public function test_assignment_json_and_remove_actions_preserve_teacher_scope_and_invalidate_cache(): void
    {
        $this->actor->givePermissionTo(['user-type:teacher', 'users:student-delete', 'users:teacher-delete']);
        $student = User::factory()->create();
        $otherTeacher = User::factory()->create();
        $otherTeacher->givePermissionTo('user-type:teacher');
        $this->actor->students()->attach($student);
        $otherTeacher->students()->attach($student);
        $p = ['user_id' => $this->actor->id];
        $this->getJson(route('admin.users.getStudentsData', $p))->assertOk()->assertJsonPath('total', 1)->assertJsonPath('rows.0.can_delete', true)->assertJsonPath('rows.0.hide_detail', true);
        $this->getJson(route('admin.users.getStudentsData', ['user_id' => $otherTeacher->id]))->assertForbidden();
        $teachers = $this->getJson(route('admin.users.getTeachersData', ['user_id' => $student->id]))->assertOk()->json('rows');
        $byId = collect($teachers)->keyBy('id');
        $this->assertTrue($byId[$this->actor->id]['can_delete']);
        $this->assertFalse($byId[$otherTeacher->id]['can_delete']);
        Livewire::test(TeachersTable::class, ['studentId' => $student->id])->call('delete', $otherTeacher->id)->assertForbidden();
        $component = Livewire::test(StudentsTable::class, ['teacherId' => $this->actor->id]);
        $component->call('delete', $student->id)->assertDispatched('refresh-server-table');
        $this->getJson(route('admin.users.getStudentsData', $p))->assertJsonPath('total', 0);
        $this->assertTrue($otherTeacher->students()->where('users.id', $student->id)->exists());
        $component->call('delete', $student->id)->assertNotFound();
    }

    public function test_invalid_parameters_and_revoked_permission_cannot_read_cached_data(): void
    {
        $student = User::factory()->create();
        $this->getJson(route('admin.users.getFavoritesData', ['user_id' => $student->id]))->assertOk();
        foreach ([['sort' => 'name;DROP'], ['limit' => 101], ['status' => 'unknown'], ['user_id' => -1]] as $invalid) {
            $this->getJson(route('admin.users.getFavoritesData', ['user_id' => $student->id, ...$invalid]))->assertUnprocessable();
        }
        $this->actor->revokePermissionTo('users:view');
        $this->getJson(route('admin.users.getFavoritesData', ['user_id' => $student->id]))->assertForbidden();
        Livewire::test(FavoriteTable::class, ['userId' => $student->id])->assertForbidden();
    }

    public function test_user_tabs_render_new_and_existing_tables_without_lazy_table_wrappers(): void
    {
        $student = User::factory()->create();
        foreach (['favorite' => 'user-favorites', 'teachers' => 'user-teachers', 'topicsNotResult' => 'user-unsolved-topics',
            'tests' => 'exam-results', 'exams' => 'test-results', 'topicsStats' => 'result-stats'] as $tab => $table) {
            $this->get(route('admin.users.edit', ['user' => $student->id, 'tab' => $tab]))->assertOk()->assertSee('data-server-table-page="'.$table.'"', false);
        }
        $this->actor->givePermissionTo('user-type:teacher');
        $this->get(route('admin.users.edit', ['user' => $this->actor->id, 'tab' => 'teachers']))->assertOk()->assertSee('user-students-table', false);
    }

    public static function menuPermissions(): array
    {
        return [[null, null], ['ai:view', 'education'], ['exams:view', 'tests'], ['tests:view', 'exams'],
            ['stats:view', 'results'], ['seasons:view', 'management']];
    }

    #[DataProvider('menuPermissions')]
    public function test_sidebar_groups_have_visible_children_and_no_unauthorized_empty_heading(?string $permission, ?string $group): void
    {
        $actor = User::factory()->create();
        if ($permission) {
            $actor->givePermissionTo($permission);
        }
        $this->actingAs($actor);
        $html = view('backend.layouts.sidebar-menu')->render();
        $this->assertSame($group ? 1 : 0, substr_count($html, 'data-menu-group='));
        if ($group) {
            $this->assertStringContainsString('data-menu-group="'.$group.'"', $html);
        }
        if ($permission === 'ai:view') {
            $this->assertStringContainsString(route('admin.ai.index'), $html);
        }
        if ($permission === 'seasons:view') {
            $this->assertStringContainsString(route('admin.seasons.index'), $html);
        }
    }
}
