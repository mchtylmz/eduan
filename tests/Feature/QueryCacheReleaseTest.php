<?php

namespace Tests\Feature;

use App\Enums\StatusEnum;
use App\Enums\TestSectionTypeEnum;
use App\Enums\YesNoEnum;
use App\Livewire\Frontend\Account\SolvedTests;
use App\Livewire\Frontend\Account\StatsTable;
use App\Models\Exam;
use App\Models\Language;
use App\Models\Lesson;
use App\Models\Question;
use App\Models\Test;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class QueryCacheReleaseTest extends TestCase
{
    use DatabaseTransactions;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        Language::firstOrCreate(['code' => 'nl'], ['name' => 'Nederlands', 'status' => StatusEnum::ACTIVE]);
        $this->owner = User::factory()->create();
        $this->actingAs($this->owner);
        app()->setLocale('nl');
    }

    public function test_solved_cards_keep_answer_totals_with_constant_query_count(): void
    {
        $other = User::factory()->create();
        $exams = collect();
        foreach (range(1, 4) as $index) {
            $exam = Exam::factory()->create(['code' => fake()->uuid(), 'status' => StatusEnum::ACTIVE]);
            foreach ([YesNoEnum::YES, YesNoEnum::NO] as $completed) {
                // Intentionally different stored counters: cards must still count answer rows.
                $attempt = $exam->results()->create(['user_id' => $this->owner->id, 'completed' => $completed, 'correct_count' => 99]);
                foreach ([1, 0, -1] as $correct) {
                    $attempt->details()->create(['question_id' => 0, 'answer_id' => 0, 'lesson_id' => 0, 'topic_id' => 0, 'correct' => $correct]);
                }
            }
            $exam->results()->create(['user_id' => $other->id, 'correct_count' => 1000]);
            $exams->push($exam);
        }
        $list = new SolvedTests;
        DB::enableQueryLog();
        try {
            DB::flushQueryLog();
            $previous = Exam::with(['language', 'userResults'])
                ->whereHas('userResults', fn ($query) => $query->where('user_id', $this->owner->id))->active()->paginate(21);
            foreach ($previous as $exam) {
                examResultStats($exam, $this->owner);
            }
            $oldQueries = count(DB::getQueryLog());
            DB::flushQueryLog();
            $rows = $list->tests();
            $newQueries = count(DB::getQueryLog());
            $this->assertSame(3, $newQueries);
            $this->assertGreaterThanOrEqual($newQueries + 4, $oldQueries);
        } finally {
            DB::disableQueryLog();
        }
        foreach ($exams as $exam) {
            $row = $rows->firstWhere('id', $exam->id);
            $expected = examResultStats($exam, $this->owner);
            $this->assertEquals($expected['results_count'], $row->personal_results_count);
            $this->assertEquals($expected['correct_count'], $row->personal_correct_count);
            $this->assertEquals($expected['incorrect_count'], $row->personal_incorrect_count);
            $this->assertFalse($row->relationLoaded('userResults'));
        }
        Livewire::withoutLazyLoading()->test(SolvedTests::class)->assertSee($exams->first()->name)->assertDontSee('1000');
    }

    public function test_unsolved_cards_use_current_owner_and_database_subquery(): void
    {
        $mine = Exam::factory()->create(['code' => fake()->uuid(), 'status' => StatusEnum::ACTIVE]);
        $notMine = Exam::factory()->create(['code' => fake()->uuid(), 'status' => StatusEnum::ACTIVE]);
        $mine->results()->create(['user_id' => $this->owner->id]);
        $notMine->results()->create(['user_id' => User::factory()->create()->id]);
        $list = new SolvedTests;
        $list->showNotComplete = true;
        DB::enableQueryLog();
        try {
            DB::flushQueryLog();
            $rows = $list->tests();
            $this->assertSame(3, count(DB::getQueryLog()));
            $this->assertStringContainsString('not in (select', DB::getQueryLog()[0]['query']);
            $this->assertNull($rows->firstWhere('id', $mine->id));
            $this->assertNotNull($rows->firstWhere('id', $notMine->id));
        } finally {
            DB::disableQueryLog();
        }
        Livewire::withoutLazyLoading()->test(SolvedTests::class, ['showNotComplete' => true])->assertDontSee($mine->name);
        $this->assertFalse($notMine->userResultsWithoutGroupBy()->exists());
    }

    public function test_test_history_paginates_personal_attempts_and_keeps_numbering_and_previous_link(): void
    {
        $exam = Exam::factory()->create(['code' => fake()->uuid(), 'status' => StatusEnum::ACTIVE]);
        $ids = [];
        foreach (range(1, 6) as $index) {
            $ids[] = $exam->results()->create(['user_id' => $this->owner->id, 'completed' => $index % 2])->id;
        }
        $exam->results()->create(['user_id' => User::factory()->create()->id]);
        $url = route('frontend.test.solutions', $exam->code);
        $this->get($url.'?lang=nl')->assertOk()->assertViewHas('results', fn ($rows) => $rows->total() === 6 && $rows->count() === 5 && $rows->first()->id === $ids[0]);
        $response = $this->get($url.'?page=2&lang=nl')->assertOk()
            ->assertViewHas('results', fn ($rows) => $rows->count() === 1 && $rows->first()->id === $ids[5]);
        $response->assertSee('6. '.__('Sonuç'))->assertSee('page=1', false)->assertSee('rel="prev"', false);
        auth()->logout();
        $this->get($url)->assertRedirect(route('login'));
    }

    public function test_exam_history_eager_loads_sections_answers_and_only_completed_own_attempts(): void
    {
        $test = Test::factory()->create(['code' => fake()->uuid(), 'status' => StatusEnum::ACTIVE]);
        $section = $test->sections()->create(['parent_id' => 0, 'name' => 'History section', 'type' => TestSectionTypeEnum::TOPIC]);
        $child = $test->sections()->create(['parent_id' => $section->id, 'type' => TestSectionTypeEnum::QUESTION]);
        $question = Question::factory()->create(['title' => 'Visible own answer question']);
        $child->setMeta('questionId', $question->id);
        $answer = $question->answers()->create(['title' => 'Visible own answer', 'correct' => YesNoEnum::YES]);
        $ids = [];
        foreach (range(1, 6) as $index) {
            $attempt = $test->results()->create(['user_id' => $this->owner->id, 'completed' => YesNoEnum::YES, 'completed_at' => now()]);
            $ids[] = $attempt->id;
            $attempt->details()->create(['section_id' => $child->id, 'question_id' => $question->id, 'answer_id' => $answer->id, 'correct' => YesNoEnum::YES]);
        }
        $test->results()->create(['user_id' => $this->owner->id, 'completed' => YesNoEnum::NO]);
        $test->results()->create(['user_id' => User::factory()->create()->id, 'completed' => YesNoEnum::YES]);
        $url = route('frontend.solved.exams.detail', $test->code);
        DB::enableQueryLog();
        try {
            DB::flushQueryLog();
            $response = $this->get($url.'?lang=nl')->assertOk()->assertSee('Visible own answer');
            $detailQueries = collect(DB::getQueryLog())->filter(fn ($query) => str_contains($query['query'], 'from `tests_result_details`'));
            $this->assertCount(1, $detailQueries);
        } finally {
            DB::disableQueryLog();
        }
        $response->assertViewHas('results', fn ($rows) => $rows->total() === 6 && $rows->count() === 5);
        $response->assertViewHas('sections', fn ($sections) => $sections->first()->parents->first()->relationLoaded('metarelation'));
        $html = $response->getContent();
        preg_match_all('/id="(result-collapse[^"]+)"/', $html, $matches);
        $this->assertCount(5, array_unique($matches[1]));
        $this->get($url.'?page=2&lang=nl')->assertOk()->assertSee('6. '.__('Sonuç'))
            ->assertViewHas('results', fn ($rows) => $rows->count() === 1 && $rows->first()->id === $ids[5]);
    }

    public function test_topic_totals_are_valid_with_strict_grouping_and_have_no_unused_date(): void
    {
        $lesson = Lesson::factory()->create();
        $topic = Topic::factory()->create(['lesson_id' => $lesson->id]);
        $exam = Exam::factory()->create(['code' => fake()->uuid()]);
        foreach ([1, 0, -1] as $correct) {
            $attempt = $exam->results()->create(['user_id' => $this->owner->id]);
            $attempt->details()->create(['question_id' => 0, 'answer_id' => 0, 'lesson_id' => $lesson->id, 'topic_id' => $topic->id, 'correct' => $correct]);
        }
        $mode = DB::selectOne('SELECT @@SESSION.sql_mode AS mode')->mode;
        DB::statement("SET SESSION sql_mode = 'ONLY_FULL_GROUP_BY'");
        try {
            $list = new StatsTable;
            $list->activeSeason = false;
            $row = $list->stats('topic_id', desc: true)->first(fn ($row) => (int) $row->topic_id === $topic->id);
            $this->assertEquals(3, $row->total_count);
            $this->assertEquals(1, $row->correct_count);
            $this->assertEquals(1, $row->empty_count);
            $this->assertObjectNotHasProperty('updated_at', $row);
        } finally {
            DB::statement('SET SESSION sql_mode = ?', [$mode]);
        }
    }

    public function test_hourly_cleanup_keeps_legacy_data_expiry_without_clearing_compiled_files(): void
    {
        // The shared Artisan mock protects compiled files; resolving a schedule needs the real kernel.
        Artisan::swap(new Kernel($this->app, $this->app['events']));
        Artisan::all(); // Register starting callbacks; do not execute commands.
        $events = collect(app(Schedule::class)->events());
        $cleanup = $events->first(fn ($event) => $event->description === 'cache_clear');
        $this->assertNotNull($cleanup);
        $this->assertStringContainsString('cache:clear', $cleanup->command);
        $this->assertSame('15 * * * *', $cleanup->expression);
        $this->assertSame(config('app.timezone'), $cleanup->timezone);
        $this->assertFalse($events->contains(fn ($event) => str_contains($event->command ?? '', 'optimize:clear')));
    }
}
