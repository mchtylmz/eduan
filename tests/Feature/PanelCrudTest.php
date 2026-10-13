<?php

namespace Tests\Feature;

use App\Actions\Tests\CreateOrUpdateTestSectionsAction;
use App\Enums\PageTypeEnum;
use App\Enums\StatusEnum;
use App\Enums\TestSectionTypeEnum;
use App\Enums\YesNoEnum;
use App\Livewire\Blogs\BlogForm;
use App\Livewire\Blogs\BlogsTable;
use App\Livewire\Exams\ExamForm;
use App\Livewire\Exams\ExamTable;
use App\Livewire\Faqs\FaqForm;
use App\Livewire\Faqs\FaqTable;
use App\Livewire\Lessons\LessonForm;
use App\Livewire\Lessons\LessonTable;
use App\Livewire\Pages\PageForm;
use App\Livewire\Pages\PagesTable;
use App\Livewire\Questions\QuestionForm;
use App\Livewire\Questions\QuestionTable;
use App\Livewire\Tests\TestForm;
use App\Livewire\Tests\TestTable;
use App\Livewire\Topics\TopicForm;
use App\Livewire\Topics\TopicTable;
use App\Models\Blog;
use App\Models\Exam;
use App\Models\Faq;
use App\Models\Language;
use App\Models\Lesson;
use App\Models\Page;
use App\Models\Question;
use App\Models\Test;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PanelCrudTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $user = User::factory()->create();
        $permissions = ['dashboard:access'];
        foreach (['lessons', 'topics', 'questions', 'exams', 'tests', 'pages', 'blogs'] as $resource) {
            foreach (['view', 'add', 'update', 'delete'] as $operation) {
                $permissions[] = "$resource:$operation";
            }
        }
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        $user->givePermissionTo($permissions);
        $this->actingAs($user);
        app()->setLocale('nl');
        Language::firstOrCreate(['code' => 'nl'], ['name' => 'Nederlands', 'status' => StatusEnum::ACTIVE]);
        settings()->set(['defaultLocale' => 'nl', 'examlanguageCode' => 'nl', 'testlanguageCode' => 'nl', 'examAnswerCount' => 2]);
        settings()->save();
    }

    private function question(): Question
    {
        $lesson = Lesson::create(['code' => 'math', 'name' => ['nl' => 'Wiskunde']]);
        $topic = Topic::create(['lesson_id' => $lesson->id, 'code' => 'algebra', 'title' => ['nl' => 'Algebra']]);

        return Question::create(['lesson_id' => $lesson->id, 'topic_id' => $topic->id, 'code' => 'q1', 'locale' => 'nl', 'title' => 'Question', 'time' => 60]);
    }

    public function test_lesson_and_topic_create_edit_delete_and_relationship_guards(): void
    {
        Livewire::test(LessonForm::class)->set('code', 'math')->set('name', ['nl' => 'Wiskunde'])
            ->call('save')->assertHasNoErrors()->assertRedirect();
        $lesson = Lesson::where('code', 'math')->firstOrFail();
        Livewire::test(LessonForm::class, ['lessonId' => $lesson->id])->assertSet('name.nl', 'Wiskunde')
            ->set('name.nl', 'Rekenen')->call('save')->assertRedirect();
        $this->assertSame('Rekenen', $lesson->fresh()->getTranslation('name', 'nl'));
        Livewire::test(TopicForm::class, ['lazy' => false])->set('lesson_id', $lesson->id)
            ->set('code', 'algebra')->set('title', ['nl' => 'Algebra'])->call('save')->assertHasNoErrors()->assertRedirect();
        $topic = Topic::where('code', 'algebra')->firstOrFail();
        Livewire::test(TopicForm::class, ['topicId' => $topic->id, 'lazy' => false])
            ->set('title.nl', 'Breuken')->call('save')->assertRedirect();
        $this->assertSame('Breuken', $topic->fresh()->getTranslation('title', 'nl'));
        $this->assertFalse((new LessonTable)->delete($lesson));
        $this->assertTrue((new TopicTable)->delete($topic));
        $this->assertTrue((new LessonTable)->delete($lesson));
        $this->assertSoftDeleted($topic);
        $this->assertSoftDeleted($lesson);
    }

    public function test_question_create_edit_answers_and_soft_delete(): void
    {
        $seed = $this->question();
        Livewire::test(QuestionForm::class)->set('lesson_id', $seed->lesson_id)->set('topic_id', $seed->topic_id)
            ->set('code', 'created-question')->set('title', 'New question')
            ->set('answers', [['title' => 'A', 'correct' => YesNoEnum::YES], ['title' => 'B', 'correct' => YesNoEnum::NO]])
            ->call('save')->assertHasNoErrors()->assertRedirect();
        $question = Question::where('code', 'created-question')->firstOrFail();
        $this->assertCount(2, $question->answers);
        $answer = $question->answers->first();
        Livewire::test(QuestionForm::class, ['questionId' => $question->id])->set('title', 'Edited question')
            ->set('answers', [['id' => $answer->id, 'title' => 'Correct edited', 'correct' => YesNoEnum::YES]])
            ->call('save')->assertHasNoErrors()->assertRedirect();
        $this->assertSame('Edited question', $question->fresh()->title);
        $this->assertSame(['Correct edited'], $question->answers()->pluck('title')->all());
        $this->assertFalse((new TopicTable)->delete($question->topic));
        $this->assertTrue((new QuestionTable)->delete($question));
        $this->assertSoftDeleted($question);
    }

    public function test_question_rejects_zero_or_multiple_correct_answers_without_creating_a_record(): void
    {
        $seed = $this->question();
        $initialQuestionCount = Question::count();
        foreach ([YesNoEnum::NO, YesNoEnum::YES] as $correct) {
            Livewire::test(QuestionForm::class)->set('lesson_id', $seed->lesson_id)->set('topic_id', $seed->topic_id)
                ->set('code', 'invalid-question')->set('answers', [['title' => 'A', 'correct' => $correct], ['title' => 'B', 'correct' => $correct]])
                ->call('save')->assertHasErrors('answers');
        }
        $this->assertSame($initialQuestionCount, Question::count());
    }

    public function test_practice_create_edit_selected_question_relations_and_delete(): void
    {
        $question = $this->question();
        Livewire::test(ExamForm::class)->set('name', 'Practice')->set('code', 'practice-crud')
            ->call('toggleQuestions', $question->id)->call('save')->assertHasNoErrors()->assertRedirect();
        $exam = Exam::where('code', 'practice-crud')->firstOrFail();
        $this->assertSame([$question->id], $exam->questions()->pluck('questions.id')->all());
        Livewire::test(ExamForm::class, ['exam' => $exam])->set('name', 'Edited practice')
            ->call('save')->assertHasNoErrors()->assertRedirect();
        $this->assertSame('Edited practice', $exam->fresh()->name);
        $this->assertSame(1, $exam->questions()->count());
        $this->assertTrue((new ExamTable)->delete($exam));
        $this->assertSoftDeleted($exam);
    }

    public function test_timed_exam_create_edit_duration_points_and_delete(): void
    {
        Livewire::test(TestForm::class)->set('name', 'Timed exam')->set('code', 'timed-crud')->set('duration', 60)
            ->call('save')->assertHasNoErrors()->assertRedirect();
        $test = Test::where('code', 'timed-crud')->firstOrFail();
        Livewire::test(TestForm::class, ['test' => $test])->set('name', 'Edited timed')->set('duration', 90)
            ->set('incorrectPoint', -2)->call('save')->assertHasNoErrors()->assertRedirect();
        $this->assertSame(90, $test->fresh()->duration);
        $this->assertSame(-2, $test->fresh()->incorrect_point);
        $this->assertTrue((new TestTable)->delete($test));
        $this->assertSoftDeleted($test);
    }

    public function test_page_and_faq_create_edit_delete_and_protect_system_page(): void
    {
        Livewire::test(PageForm::class)->set('title', ['nl' => 'Informatie'])->set('slug', 'information')
            ->set('content', ['nl' => '<p>Page content</p>'])->call('save')->assertHasNoErrors()->assertRedirect();
        $page = Page::where('slug', 'information')->firstOrFail();
        Livewire::test(PageForm::class, ['pageId' => $page->id])->set('content.nl', '<p>Edited</p>')
            ->call('save')->assertRedirect();
        $this->assertSame('<p>Edited</p>', $page->fresh()->getTranslation('content', 'nl'));
        $this->assertTrue((new PagesTable)->delete($page));
        $this->assertModelMissing($page);
        $system = Page::create(['slug' => 'system', 'title' => ['nl' => 'System'], 'type' => PageTypeEnum::SYSTEM]);
        $this->assertFalse((new PagesTable)->delete($system));
        $this->assertModelExists($system);
        Livewire::test(FaqForm::class)->set('title', 'FAQ')->set('content', '<p>Answer</p>')
            ->call('save')->assertHasNoErrors()->assertRedirect();
        $faq = Faq::where('title', 'FAQ')->firstOrFail();
        $this->assertSame('nl', $faq->locale);
        Livewire::test(FaqForm::class, ['faqId' => $faq->id])->assertSet('content', '<p>Answer</p>')
            ->assertSet('locale', 'nl')
            ->set('content', '<p>Edited answer</p>')->call('save')->assertHasNoErrors()->assertRedirect();
        $this->assertSame('<p>Edited answer</p>', html_entity_decode($faq->fresh()->content));
        $this->assertTrue((new FaqTable)->delete($faq));
    }

    public function test_blog_create_edit_content_encoding_and_delete(): void
    {
        Livewire::test(BlogForm::class)->set('title', 'Blog')->set('content', '<p>Article</p>')
            ->call('save')->assertHasNoErrors()->assertRedirect();
        $blog = Blog::where('slug', 'blog')->firstOrFail();
        Livewire::test(BlogForm::class, ['blogId' => $blog->id])->assertSet('content', '<p>Article</p>')
            ->set('content', '<p>Edited article</p>')->call('save')->assertHasNoErrors()->assertRedirect();
        $this->assertSame('<p>Edited article</p>', html_entity_decode($blog->fresh()->content));
        $this->assertTrue((new BlogsTable)->delete($blog));
    }

    public function test_duplicate_lesson_code_and_nonexistent_topic_lesson_are_rejected(): void
    {
        $this->question();
        $initialLessonCount = Lesson::count();
        $initialTopicCount = Topic::count();
        Livewire::test(LessonForm::class)->set('code', 'math')->set('name', ['nl' => 'Duplicate'])
            ->call('save')->assertHasErrors('code');
        Livewire::test(TopicForm::class, ['lazy' => false])->set('code', 'unknown-topic')
            ->set('title', ['nl' => 'Unknown'])->set('lesson_id', 999999)
            ->call('save')->assertHasErrors('lesson_id');
        $this->assertSame($initialLessonCount, Lesson::count());
        $this->assertSame($initialTopicCount, Topic::count());
    }

    public function test_timed_section_question_creation_and_edit_are_persisted(): void
    {
        $question = $this->question();
        $test = Test::create(['code' => 'section-test', 'name' => 'Sections', 'locale' => 'nl', 'created_by' => auth()->id()]);
        $sections = [['id' => 0, 'test_id' => $test->id, 'parent_id' => 0, 'type' => TestSectionTypeEnum::TOPIC,
            'name' => 'Math section', 'order' => 1, 'fields' => [['id' => 0, 'type' => TestSectionTypeEnum::QUESTION,
                'name' => 'Question', 'order' => 1, 'questionId' => $question->id]]]];
        $this->assertTrue(CreateOrUpdateTestSectionsAction::run($sections, $test));
        $parent = $test->sections_with_no_parent()->firstOrFail();
        $child = $test->sections()->where('parent_id', $parent->id)->firstOrFail();
        $this->assertSame($question->id, $test->questions()->firstOrFail()->question_id);
        $sections[0]['id'] = $parent->id;
        $sections[0]['name'] = 'Edited section';
        $sections[0]['fields'][0]['id'] = $child->id;
        $this->assertTrue(CreateOrUpdateTestSectionsAction::run($sections, $test));
        $this->assertSame('Edited section', $parent->fresh()->name);
        $this->assertSame(1, $test->questions()->count());
    }

    public function test_removing_a_timed_section_removes_its_fields_and_question_links(): void
    {
        $question = $this->question();
        $test = Test::create(['code' => 'removed-section', 'name' => 'Sections', 'locale' => 'nl', 'created_by' => auth()->id()]);
        $sections = [['id' => 0, 'test_id' => $test->id, 'parent_id' => 0, 'type' => TestSectionTypeEnum::TOPIC,
            'name' => 'Math section', 'order' => 1, 'fields' => [['id' => 0, 'type' => TestSectionTypeEnum::QUESTION,
                'name' => 'Question', 'order' => 1, 'questionId' => $question->id]]]];
        $this->assertTrue(CreateOrUpdateTestSectionsAction::run($sections, $test));
        $this->assertSame(2, $test->sections()->count());
        $this->assertSame(1, $test->questions()->count());

        $this->assertTrue(CreateOrUpdateTestSectionsAction::run([], $test));
        $this->assertSame(0, $test->sections()->count());
        $this->assertSame(0, $test->questions()->count());
    }
}
