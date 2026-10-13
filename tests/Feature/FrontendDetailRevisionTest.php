<?php

namespace Tests\Feature;

use App\Enums\PageTypeEnum;
use App\Enums\ReviewVisibilityEnum;
use App\Enums\StatusEnum;
use App\Enums\TestSectionTypeEnum;
use App\Enums\VisibilityEnum;
use App\Enums\YesNoEnum;
use App\Livewire\Frontend\Exams\SolveForm;
use App\Livewire\Frontend\Tests\DetailStatisticList;
use App\Livewire\Frontend\Tests\PersonalResults;
use App\Livewire\Frontend\Tests\ReviewFormAndList;
use App\Models\Exam;
use App\Models\Language;
use App\Models\Lesson;
use App\Models\Page;
use App\Models\Question;
use App\Models\Test;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class FrontendDetailRevisionTest extends TestCase
{
    use DatabaseTransactions;

    private Exam $exam;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        Language::firstOrCreate(['code' => 'nl'], ['name' => 'Nederlands', 'status' => StatusEnum::ACTIVE]);
        $this->owner = User::factory()->create();
        $this->exam = Exam::factory()->create(['code' => fake()->uuid(), 'status' => StatusEnum::ACTIVE, 'visibility' => VisibilityEnum::LOGGED]);
        $this->actingAs($this->owner);
        app()->setLocale('nl');
    }

    public function test_reviews_stay_in_the_selected_test_and_guest_cannot_read_member_reviews(): void
    {
        $otherExam = Exam::factory()->create(['code' => fake()->uuid()]);
        $otherExam->reviews()->create(['user_id' => $this->owner->id, 'comment' => 'Other test hidden marker', 'visibility' => ReviewVisibilityEnum::HIDE]);
        $this->exam->reviews()->create(['user_id' => $this->owner->id, 'comment' => 'Own hidden marker', 'visibility' => ReviewVisibilityEnum::HIDE]);
        $this->exam->reviews()->create(['user_id' => $this->owner->id, 'comment' => 'Member marker', 'visibility' => ReviewVisibilityEnum::PRIVATE]);
        $this->exam->reviews()->create(['user_id' => $this->owner->id, 'comment' => 'Public marker', 'visibility' => ReviewVisibilityEnum::PUBLIC]);
        Livewire::withoutLazyLoading()->test(ReviewFormAndList::class, ['exam' => $this->exam])
            ->assertSee('Own hidden marker')->assertSee('Member marker')->assertDontSee('Other test hidden marker');
        auth()->logout();
        Livewire::withoutLazyLoading()->test(ReviewFormAndList::class, ['exam' => $this->exam])
            ->assertSee('Public marker')->assertDontSee('Member marker')->assertDontSee('Own hidden marker');
    }

    public function test_review_write_rechecks_session_owner_and_guest_is_rejected(): void
    {
        $component = Livewire::withoutLazyLoading()->test(ReviewFormAndList::class, ['exam' => $this->exam]);
        auth()->logout();
        $component->set('message', 'Guest stale session marker')->call('save')->assertForbidden();
        $this->assertFalse($this->exam->reviews()->where('comment', 'Guest stale session marker')->exists());
        $this->actingAs($this->owner);
        $component = Livewire::withoutLazyLoading()->test(ReviewFormAndList::class, ['exam' => $this->exam]);
        $nextUser = User::factory()->create();
        $this->actingAs($nextUser);
        $component->set('message', 'Current session marker')->call('save')->assertStatus(200);
        $this->assertSame($nextUser->id, $this->exam->reviews()->where('comment', 'Current session marker')->firstOrFail()->user_id);
    }

    public function test_review_visibility_badge_validation_and_active_record_controls(): void
    {
        $other = User::factory()->create();
        foreach ([ReviewVisibilityEnum::PUBLIC, ReviewVisibilityEnum::PRIVATE, ReviewVisibilityEnum::HIDE] as $visibility) {
            $this->exam->reviews()->create(['user_id' => $other->id, 'comment' => 'Visibility '.$visibility->value, 'visibility' => $visibility]);
        }
        $this->assertSame(2, $this->exam->publicReviewsCount());
        $form = Livewire::withoutLazyLoading()->test(ReviewFormAndList::class, ['exam' => $this->exam])
            ->assertDontSee('Visibility hide')->call('save')->assertHasErrors('message')
            ->set('message', str_repeat('x', 501))->call('save')->assertHasErrors('message');
        $form->set('message', 'Disabled review');
        $this->exam->update(['status' => StatusEnum::PASSIVE]);
        $form->call('save')->assertForbidden();
        $this->assertFalse($this->exam->reviews()->where('comment', 'Disabled review')->exists());
        auth()->logout();
        $this->assertSame(1, $this->exam->publicReviewsCount());
    }

    public function test_personal_results_default_tab_counts_all_attempts_and_excludes_other_users(): void
    {
        foreach ([[1, 0], [1, 1]] as $correctValues) {
            $attempt = $this->exam->results()->create(['user_id' => $this->owner->id, 'completed' => YesNoEnum::YES]);
            foreach ($correctValues as $correct) {
                $attempt->details()->create(['question_id' => 0, 'answer_id' => 0, 'lesson_id' => 0, 'topic_id' => 0, 'correct' => $correct]);
            }
        }
        $other = User::factory()->create();
        $this->exam->results()->create(['user_id' => $other->id, 'completed' => YesNoEnum::YES]);
        DB::enableQueryLog();
        DB::flushQueryLog();
        $this->assertSame(['results_count' => 2, 'question_count' => 4, 'correct_count' => 3, 'incorrect_count' => 1], examResultStats($this->exam, $this->owner));
        $this->assertCount(1, DB::getQueryLog());
        $this->assertSame(1, examResultStats($this->exam, $other)['results_count']);
        DB::disableQueryLog();
        $this->get(route('frontend.test.detail', $this->exam->code).'?lang=nl')->assertOk()
            ->assertSee('Mijn resultaten')->assertSee('nav-link active" id="pills-results-tab', false);
        $component = Livewire::test(PersonalResults::class, ['exam' => $this->exam])->assertSee('Totaal van alle pogingen');
        $this->assertSame(2, $component->instance()->attempts()->total());
        $this->assertSame(3, $component->instance()->totals()['correct_count']);
        auth()->logout();
        Livewire::test(PersonalResults::class, ['exam' => $this->exam])->assertSee('Log in')->assertDontSee('Totaal van alle pogingen');
    }

    public function test_personal_results_paginates_and_empty_description_has_a_message(): void
    {
        $this->exam->update(['content' => '']);
        for ($index = 0; $index < 16; $index++) {
            $this->exam->results()->create(['user_id' => $this->owner->id, 'completed' => YesNoEnum::NO]);
        }
        $component = Livewire::test(PersonalResults::class, ['exam' => $this->exam]);
        $this->assertCount(15, $component->instance()->attempts());
        $component->call('setPage', 2, 'attemptsPage');
        $this->assertCount(1, $component->instance()->attempts());
        $this->get(route('frontend.test.detail', $this->exam->code).'?lang=nl')->assertOk()->assertSee('Er is nog geen beschrijving beschikbaar.');
    }

    public function test_test_routes_enforce_guest_and_premium_start_and_own_solutions(): void
    {
        auth()->logout();
        $this->get(route('frontend.test.start', $this->exam->code))->assertRedirect(route('login'));
        $this->get(route('frontend.test.solutions', $this->exam->code))->assertRedirect(route('login'));
        $this->actingAs($this->owner);
        $this->exam->update(['visibility' => VisibilityEnum::PREMIUM]);
        $this->get(route('frontend.test.start', $this->exam->code))->assertForbidden();
        $this->exam->results()->create(['user_id' => $this->owner->id, 'completed' => YesNoEnum::YES]);
        $this->exam->results()->create(['user_id' => User::factory()->create()->id, 'completed' => YesNoEnum::YES]);
        $this->get(route('frontend.test.solutions', $this->exam->code))->assertOk()
            ->assertViewHas('results', fn ($results) => $results->count() === 1 && $results->first()->user_id === $this->owner->id);
        $this->exam->update(['status' => StatusEnum::PASSIVE]);
        $this->get(route('frontend.test.start', $this->exam->code))->assertNotFound();
    }

    public function test_public_detail_summary_cache_reuses_queries_and_invalidates_on_question_change(): void
    {
        $lesson = Lesson::factory()->create(['code' => fake()->uuid()]);
        $topic = Topic::factory()->create(['lesson_id' => $lesson->id, 'code' => fake()->uuid()]);
        $question = Question::factory()->create(['status' => StatusEnum::ACTIVE, 'time' => 90, 'lesson_id' => $lesson->id, 'topic_id' => $topic->id]);
        $this->exam->questions()->attach($question->id);
        $component = new DetailStatisticList;
        $component->exam = $this->exam;
        $this->assertSame(90, $component->summary()['seconds']);
        DB::enableQueryLog();
        DB::flushQueryLog();
        $this->assertSame(1, $component->summary()['questions_count']);
        $this->assertCount(0, DB::getQueryLog());
        DB::disableQueryLog();
        $question->update(['time' => 120]);
        $this->assertSame(120, $component->summary()['seconds']);
        $this->assertArrayNotHasKey('results_count', $component->summary());
    }

    public function test_empty_first_exam_section_is_skipped_and_invalid_navigation_is_rejected(): void
    {
        Queue::fake();
        Permission::findOrCreate('tests:solve', 'web');
        $this->owner->givePermissionTo('tests:solve');
        $test = Test::factory()->create(['code' => fake()->uuid(), 'status' => StatusEnum::ACTIVE, 'duration' => 120]);
        $test->sections()->create(['parent_id' => 0, 'name' => 'Empty intro', 'type' => TestSectionTypeEnum::TOPIC, 'order' => 1]);
        $section = $test->sections()->create(['parent_id' => 0, 'name' => 'Questions', 'type' => TestSectionTypeEnum::TOPIC, 'order' => 2]);
        $child = $test->sections()->create(['parent_id' => $section->id, 'type' => TestSectionTypeEnum::QUESTION, 'order' => 1]);
        $question = Question::factory()->create(['status' => StatusEnum::ACTIVE]);
        $child->setMeta('questionId', $question->id);
        $test->questions()->create(['section_id' => $child->id, 'question_id' => $question->id, 'lesson_id' => $question->lesson_id, 'topic_id' => $question->topic_id]);
        $component = Livewire::test(SolveForm::class, ['test' => $test])
            ->assertSet('active.section', 1)->assertSet('active.parent', $child->id);
        $component->call('setActive', 0, $child->id)->assertStatus(422);
        $this->assertSame(1, $test->results()->where('user_id', $this->owner->id)->count());
    }

    public function test_home_sections_keep_existing_content_and_catalog_links(): void
    {
        $page = Page::create(['slug' => fake()->uuid(), 'type' => PageTypeEnum::SYSTEM, 'content' => ['nl' => ['lessonTitle' => 'Course collection', 'testTitle' => 'Test collection', 'blogTitle' => 'Article collection', 'lessonCount' => 3, 'testCount' => 3, 'blogCount' => 3]]]);
        foreach (['Lessons' => 'lessons', 'Tests' => 'tests', 'Blog' => 'blog'] as $class => $route) {
            Livewire::test('frontend.home.'.strtolower($class).'-section', ['page' => $page])
                ->assertSee('home-catalog-heading', false)->assertSee(route('frontend.'.$route), false);
        }
    }
}
