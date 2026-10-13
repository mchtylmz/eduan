<?php

namespace Tests\Feature;

use App\Enums\PageMenuEnum;
use App\Enums\PageTypeEnum;
use App\Enums\StatusEnum;
use App\Enums\YesNoEnum;
use App\Livewire\Blogs\BlogForm;
use App\Livewire\Tests\ResultsTable;
use App\Models\Blog;
use App\Models\Exam;
use App\Models\Language;
use App\Models\Lesson;
use App\Models\Page;
use App\Models\Question;
use App\Models\Test;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PopulatedDetailTest extends TestCase
{
    use DatabaseTransactions;

    private Lesson $lesson;

    private Topic $topic;

    private Question $question;

    private Exam $exam;

    private Test $test;

    private Blog $blog;

    private Page $page;

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();
        Artisan::spy();
        Mail::fake();
        Queue::fake();
        Livewire::withoutLazyLoading();
        app()->setLocale('nl');
        Language::firstOrCreate(['code' => 'nl'], ['name' => 'Nederlands', 'status' => StatusEnum::ACTIVE]);
        settings()->set(['defaultLocale' => 'nl', 'examlanguageCode' => 'nl', 'testlanguageCode' => 'nl']);
        settings()->save();
        $this->student = User::factory()->create();
        $this->lesson = Lesson::create(['code' => 'detail-lesson', 'name' => ['nl' => 'Detail lesson'], 'status' => StatusEnum::ACTIVE]);
        $this->topic = Topic::create(['lesson_id' => $this->lesson->id, 'code' => 'detail-topic', 'title' => ['nl' => 'Detail topic'], 'status' => StatusEnum::ACTIVE]);
        $this->question = Question::create(['lesson_id' => $this->lesson->id, 'topic_id' => $this->topic->id,
            'code' => 'detail-question', 'locale' => 'nl', 'title' => 'Detail question', 'time' => 60, 'status' => StatusEnum::ACTIVE]);
        $answer = $this->question->answers()->create(['title' => 'Detail answer', 'correct' => YesNoEnum::YES]);
        $this->exam = Exam::create(['code' => 'detail-practice', 'name' => 'Detail practice', 'locale' => 'nl', 'content' => 'Practice description', 'status' => StatusEnum::ACTIVE]);
        $this->exam->questions()->attach($this->question);
        $this->exam->lessons()->attach($this->lesson);
        $this->exam->topics()->attach($this->topic);
        $this->test = Test::create(['code' => 'detail-timed', 'name' => 'Detail timed', 'locale' => 'nl', 'content' => 'Timed description',
            'duration' => 1800, 'status' => StatusEnum::ACTIVE, 'created_by' => $this->student->id]);
        $parent = $this->test->sections()->create(['parent_id' => 0, 'name' => 'Detail section', 'type' => 'topic', 'order' => 1]);
        $child = $this->test->sections()->create(['parent_id' => $parent->id, 'name' => 'Detail field', 'type' => 'question', 'order' => 1]);
        $child->setMeta('questionId', $this->question->id);
        $this->test->questions()->create(['section_id' => $child->id, 'question_id' => $this->question->id,
            'lesson_id' => $this->lesson->id, 'topic_id' => $this->topic->id, 'order' => 1]);
        $result = $this->test->results()->create(['user_id' => $this->student->id, 'completed' => YesNoEnum::YES,
            'question_count' => 1, 'correct_count' => 1, 'point' => 3, 'completed_at' => now()]);
        $result->details()->create(['section_id' => $child->id, 'question_id' => $this->question->id,
            'answer_id' => $answer->id, 'lesson_id' => $this->lesson->id, 'topic_id' => $this->topic->id, 'correct' => YesNoEnum::YES, 'point' => 3]);
        $this->blog = Blog::create(['slug' => 'detail-blog', 'title' => 'Detail blog', 'content' => 'Article description',
            'brief' => 'Article brief', 'keywords' => 'article', 'locale' => 'nl', 'status' => StatusEnum::ACTIVE, 'published_at' => now()->subDay()]);
        $this->page = Page::create(['slug' => 'detail-page', 'title' => ['nl' => 'Detail page'], 'content' => ['nl' => 'Page description'],
            'menu' => PageMenuEnum::FOOTER, 'type' => PageTypeEnum::CUSTOM, 'status' => StatusEnum::ACTIVE]);
    }

    public static function details(): array
    {
        return [
            'lesson' => ['frontend.lesson', 'lesson', 'code', 'Detail topic', false],
            'practice' => ['frontend.test.detail', 'exam', 'code', 'Practice description', false],
            'timed' => ['frontend.exam.detail', 'test', 'code', 'Detail section', false],
            'blog' => ['frontend.blog.detail', 'blog', 'slug', 'Article description', false],
            'page' => ['frontend.page', 'page', 'slug', 'Page description', false],
            'edit-lesson' => ['admin.lessons.edit', 'lesson', 'id', 'Detail lesson', true],
            'edit-topic' => ['admin.topics.edit', 'topic', 'id', 'Detail topic', true],
            'edit-question' => ['admin.questions.edit', 'question', 'id', 'Detail question', true],
            'edit-practice' => ['admin.exams.edit', 'exam', 'id', 'Detail practice', true],
            'edit-timed' => ['admin.tests.edit', 'test', 'id', 'Detail timed', true],
            'edit-blog' => ['admin.blogs.edit', 'blog', 'id', 'Detail blog', true],
            'edit-page' => ['admin.pages.edit', 'page', 'id', 'Detail page', true],
            'sections' => ['admin.tests.sections', 'test', 'id', 'Detail section', true],
            'practice-results' => ['admin.exams.result', 'exam', 'id', 'Detail practice', true],
            'timed-results' => ['admin.tests.result', 'test', 'id', 'Detail timed', true],
        ];
    }

    #[DataProvider('details')]
    public function test_populated_detail_renders_real_content(string $route, string $model, string $key, string $content, bool $panel): void
    {
        if ($panel) {
            $permissions = ['dashboard:access'];
            foreach (['lessons', 'topics', 'questions', 'exams', 'tests', 'blogs', 'pages'] as $resource) {
                $permissions[] = "$resource:view";
                $permissions[] = "$resource:update";
            }
            foreach ($permissions as $permission) {
                Permission::findOrCreate($permission, 'web');
            }
            $this->student->givePermissionTo($permissions);
            $this->actingAs($this->student);
        }
        $this->get(route($route, $this->$model->$key))->assertOk()->assertSee($content);
        Mail::assertNothingSent();
        Mail::assertNothingQueued();
        Queue::assertNothingPushed();
    }

    public function test_account_result_shows_only_own_completed_attempts_and_their_answer(): void
    {
        $this->test->results()->create(['user_id' => $this->student->id, 'completed' => YesNoEnum::NO]);
        $this->test->results()->create(['user_id' => User::factory()->create()->id, 'completed' => YesNoEnum::YES]);
        $this->actingAs($this->student)->get(route('frontend.solved.exams.detail', $this->test->code))
            ->assertOk()->assertSee('Detail question')->assertSee('Detail answer')
            ->assertViewHas('results', fn ($rows) => $rows->count() === 1 && $rows->first()->user_id === $this->student->id);
    }

    public function test_missing_detail_identifiers_return_not_found(): void
    {
        foreach (['frontend.lesson', 'frontend.test.detail', 'frontend.exam.detail', 'frontend.blog.detail', 'frontend.page'] as $route) {
            $this->get(route($route, 'missing-detail-record'))->assertNotFound();
        }
    }

    public static function nullableBlogFields(): array
    {
        return ['brief' => ['brief'], 'keywords' => ['keywords']];
    }

    #[DataProvider('nullableBlogFields')]
    public function test_blog_with_nullable_field_can_be_opened_for_editing(string $field): void
    {
        foreach (['dashboard:access', 'blogs:view', 'blogs:update'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        $this->student->givePermissionTo(['dashboard:access', 'blogs:view', 'blogs:update']);
        $this->blog->update([$field => null]);
        $this->actingAs($this->student)->get(route('admin.blogs.edit', $this->blog->id))->assertOk();
        Livewire::test(BlogForm::class, ['blogId' => $this->blog->id])->assertSet($field, '')
            ->set($field, 'Restored value')->call('save')->assertHasNoErrors()->assertRedirect();
        $this->assertSame('Restored value', $this->blog->fresh()->$field);
    }

    public function test_panel_timed_result_table_is_limited_to_the_selected_exam(): void
    {
        foreach (['dashboard:access', 'tests:view'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        $this->student->givePermissionTo(['dashboard:access', 'tests:view']);
        $this->actingAs($this->student);
        $other = Test::create(['code' => 'other-result-test', 'name' => 'Other exam', 'locale' => 'nl', 'created_by' => $this->student->id]);
        $other->results()->create(['user_id' => $this->student->id, 'completed' => YesNoEnum::YES]);
        Livewire::test(ResultsTable::class, ['test' => $this->test])->assertOk();
        $response = $this->getJson(route('admin.tests.getResultsData', ['test_id' => $this->test->id, 'refresh' => true]))->assertOk();
        $response->assertJsonPath('total', 1);
        $this->assertSame(['Detail timed'], array_column($response->json('rows'), 'test'));
        $this->get(route('admin.tests.result', $this->test->id))->assertOk()
            ->assertSeeLivewire(ResultsTable::class)->assertSee('Detail timed');
    }
}
