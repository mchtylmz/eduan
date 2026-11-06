<?php

namespace Tests\Feature;

use App\Enums\StatusEnum;
use App\Livewire\Frontend\Exams\ListExams;
use App\Livewire\Frontend\Lessons\ListLessons;
use App\Livewire\Frontend\Lessons\ListTopics;
use App\Models\Language;
use App\Models\Lesson;
use App\Models\Test;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Pagination\Paginator;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class FrontendListFindingsTest extends TestCase
{
    use DatabaseTransactions;

    public function test_lesson_cache_keeps_each_page_separate(): void
    {
        foreach (range(1, 22) as $number) {
            Lesson::create(['code' => "fixture-pagination-$number", 'name' => ['nl' => "Lesson $number"], 'sort' => $number, 'status' => StatusEnum::ACTIVE]);
        }
        $list = new ListLessons;
        $list->search = true;
        $list->word = 'fixture-pagination-';
        Paginator::currentPageResolver(fn () => $list->getPage());
        $list->setPage(1);
        $first = $list->lessons();
        $list->setPage(2);
        $second = $list->lessons();
        $this->assertCount(21, $first->items());
        $this->assertSame(2, $second->currentPage());
        $this->assertCount(1, $second->items());
        $this->assertNotSame($first->items()[0]->id, $second->items()[0]->id);
    }

    public function test_topic_cache_keeps_each_page_separate(): void
    {
        $lesson = Lesson::create(['code' => 'topics-lesson', 'name' => ['nl' => 'Math']]);
        foreach (range(1, 22) as $number) {
            Topic::create(['lesson_id' => $lesson->id, 'code' => "topic-$number", 'title' => ['nl' => "Topic $number"], 'sort' => $number, 'status' => StatusEnum::ACTIVE]);
        }
        $list = new ListTopics;
        $list->lesson = $lesson;
        Paginator::currentPageResolver(fn () => $list->getPage());
        $list->setPage(1);
        $list->topics();
        $list->setPage(2);
        $second = $list->topics();
        $this->assertSame(2, $second->currentPage());
        $this->assertCount(1, $second->items());
        $list->setPage(1);
        $this->assertSame(1, $list->topics()->currentPage());
    }

    public function test_public_timed_exam_list_includes_active_and_passive_exams_with_content(): void
    {
        $testIds = [];
        foreach ([StatusEnum::ACTIVE, StatusEnum::PASSIVE] as $status) {
            $test = Test::create(['code' => 'listed-'.$status->value, 'name' => 'Exam', 'locale' => 'nl', 'status' => $status, 'created_by' => 1]);
            $testIds[] = $test->id;
            $section = $test->sections()->create(['parent_id' => 0, 'name' => 'Section', 'type' => 'topic']);
            $test->questions()->create(['section_id' => $section->id, 'question_id' => 1, 'lesson_id' => 1, 'topic_id' => 1]);
        }
        $list = new ListExams;
        $this->assertEqualsCanonicalizing(['listed-active', 'listed-passive'], collect($list->exams()->items())->whereIn('id', $testIds)->pluck('code')->all());
    }

    public function test_question_add_permission_displays_the_question_create_button(): void
    {
        $user = User::factory()->create();
        foreach (['dashboard:access', 'questions:view', 'questions:add'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        $user->givePermissionTo(['dashboard:access', 'questions:view', 'questions:add']);
        Language::firstOrCreate(['code' => 'nl'], ['name' => 'Nederlands', 'status' => StatusEnum::ACTIVE]);
        $this->actingAs($user)->get(route('admin.questions.index'))->assertOk()
            ->assertSee('href="'.route('admin.questions.create').'"', false);
    }

    public function test_lesson_add_permission_alone_does_not_display_the_question_create_button(): void
    {
        $user = User::factory()->create();
        foreach (['dashboard:access', 'questions:view', 'lessons:add'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        $user->givePermissionTo(['dashboard:access', 'questions:view', 'lessons:add']);
        Language::firstOrCreate(['code' => 'nl'], ['name' => 'Nederlands', 'status' => StatusEnum::ACTIVE]);
        $this->actingAs($user)->get(route('admin.questions.index'))->assertOk()
            ->assertDontSee('href="'.route('admin.questions.create').'"', false);
    }
}
