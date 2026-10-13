<?php

namespace Tests\Feature;

use App\Enums\StatusEnum;
use App\Livewire\Frontend\Account\FavoriteTests;
use App\Livewire\Frontend\Account\MyLeagueResultTable;
use App\Livewire\Frontend\Account\SolvedExams;
use App\Livewire\Frontend\Account\SolvedTests;
use App\Livewire\Frontend\Account\StatsTable;
use App\Models\Exam;
use App\Models\Language;
use App\Models\League;
use App\Models\LeagueResult;
use App\Models\Lesson;
use App\Models\Season;
use App\Models\Test;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class FrontendAccountReportsTest extends TestCase
{
    use DatabaseTransactions;

    private User $owner;

    private Exam $exam;

    private Season $season;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['nl', 'en', 'tr'] as $locale) {
            Language::firstOrCreate(['code' => $locale], ['name' => $locale, 'status' => StatusEnum::ACTIVE]);
        }
        $this->owner = User::factory()->create();
        $this->exam = Exam::factory()->create(['code' => fake()->uuid()]);
        $this->season = Season::factory()->create(['name' => 'Account report season', 'start_date' => now()->subYear(), 'end_date' => now()->addYear(), 'status' => StatusEnum::ACTIVE]);
        $this->actingAs($this->owner);
    }

    public static function locales(): array
    {
        return [['nl', 'Vraagstatistieken', 'Mijn ligresultaten'], ['en', 'Question statistics', 'My league results'], ['tr', 'Soru İstatistiği', 'Lig sonuçlarım']];
    }

    #[DataProvider('locales')]
    public function test_reports_and_current_mobile_menu_are_localized(string $locale, string $statistics, string $leagues): void
    {
        $this->get(route('frontend.stats').'?lang='.$locale)->assertOk();
        $this->createAttempt(0, 0);
        $this->leagueResult($this->owner, 0);
        Livewire::withoutLazyLoading()->test(StatsTable::class)->assertSee($statistics)->assertSee(__('account.success'))->assertSee('0,00%')
            ->assertSee('member-stat-card', false);
        if ($locale !== 'tr') {
            Livewire::withoutLazyLoading()->test(StatsTable::class)->assertDontSee('En Çok Çözülen Dersler');
        }
        Livewire::withoutLazyLoading()->test(MyLeagueResultTable::class, ['userId' => $this->owner->id])
            ->assertSee(__('account.period'))->assertSee(__('account.position'))->assertSee(__('account.points'));
        $response = $this->get(route('frontend.my.leagues').'?lang='.$locale)->assertOk()->assertSee($leagues);
        $response->assertSee(__('leagues.title'))->assertSee(__('account.favorites'));
        if ($locale !== 'tr') {
            $response->assertDontSee('Lig Sonuçlarım')->assertDontSee('>Ligler<', false);
        }
        $this->assertMatchesRegularExpression('/member-menu-toggle[\s\S]*?'.preg_quote($leagues, '/').'[\s\S]*?<\/button>/', $response->getContent());
    }

    public function test_statistics_paginates_only_current_user_and_resets_when_report_changes(): void
    {
        for ($day = 0; $day < 21; $day++) {
            $this->createAttempt($day, 2);
        }
        $other = User::factory()->create();
        $this->createAttempt(0, 999, $other);
        $component = Livewire::withoutLazyLoading()->test(StatsTable::class);
        $this->assertSame(21, $component->instance()->results()->total());
        $component->call('setPage', 2)->assertSee('member-stat-card', false);
        $this->assertCount(1, $component->instance()->results());
        $component->set('activeTab', 'topicsDesc')->assertSet('paginators.page', 1)->assertSee(__('account.topics_desc'));
        $component->call('chooseTab', 'invalid')->assertStatus(422);
    }

    public function test_topic_report_keeps_totals_and_uses_topic_label(): void
    {
        $lesson = Lesson::factory()->create(['name' => ['nl' => 'Eigen les']]);
        $topic = Topic::factory()->create(['lesson_id' => $lesson->id, 'title' => ['nl' => 'Eigen onderwerp']]);
        $resultId = $this->createAttempt(0, 3);
        foreach ([1, 0, -1] as $correct) {
            DB::table('exam_result_details')->insert(['exam_result_id' => $resultId, 'question_id' => 0, 'answer_id' => 0, 'lesson_id' => $lesson->id, 'topic_id' => $topic->id, 'correct' => $correct, 'time' => 0, 'created_at' => now(), 'updated_at' => now()]);
        }
        app()->setLocale('nl');
        $component = Livewire::withoutLazyLoading()->test(StatsTable::class)->set('activeTab', 'topicsDesc')
            ->assertSee('Eigen onderwerp')->assertSee('Onderwerp')->assertSee('33,33%');
        $row = $component->instance()->results()->first();
        $this->assertEquals(3, $row->total_count);
        $this->assertEquals(1, $row->correct_count);
        $this->assertEquals(1, $row->empty_count);
        $component->call('stats', 'invalid_column')->assertStatus(422);
    }

    public function test_league_cards_paginates_and_excludes_other_users(): void
    {
        for ($index = 0; $index < 16; $index++) {
            $this->leagueResult($this->owner, $index);
        }
        $this->leagueResult(User::factory()->create(), 99);
        $component = Livewire::withoutLazyLoading()->test(MyLeagueResultTable::class, ['userId' => $this->owner->id])->assertSee('member-league-card', false);
        $this->assertSame(16, $component->instance()->results()->total());
        $this->assertCount(15, $component->instance()->results());
        $component->call('setPage', 2);
        $this->assertCount(1, $component->instance()->results());
        $this->assertSame($this->owner->id, $component->instance()->results()->first()->user_id);
    }

    public function test_league_user_context_rejects_other_owner_and_hydration_changes(): void
    {
        $other = User::factory()->create();
        Livewire::withoutLazyLoading()->test(MyLeagueResultTable::class, ['userId' => $other->id])->assertForbidden();
        $this->expectException(CannotUpdateLockedPropertyException::class);
        Livewire::withoutLazyLoading()->test(MyLeagueResultTable::class, ['userId' => $this->owner->id])->set('userId', $other->id);
    }

    public function test_course_cards_preserve_pagination_and_two_column_markup(): void
    {
        $this->owner->favoriteExams()->attach($this->exam);
        $this->createAttempt(0, 2);
        Livewire::withoutLazyLoading()->test(FavoriteTests::class)->assertSee('col-xxl-6 col-lg-6', false);
        Livewire::withoutLazyLoading()->test(SolvedTests::class)->assertSee('member-course-grid', false)->assertSee('col-xxl-6 col-lg-6', false);
        $exam = Test::factory()->create(['code' => fake()->uuid()]);
        DB::table('tests_results')->insert(['user_id' => $this->owner->id, 'test_id' => $exam->id, 'completed' => 1, 'created_at' => now(), 'updated_at' => now()]);
        Livewire::withoutLazyLoading()->test(SolvedExams::class)->assertSee('col-xxl-6 col-lg-6', false)->assertSee($exam->name);
    }

    private function createAttempt(int $daysAgo, int $questions, ?User $user = null): int
    {
        $date = now()->subDays($daysAgo)->startOfDay();

        return DB::table('exam_results')->insertGetId(['user_id' => ($user ?? $this->owner)->id, 'exam_id' => $this->exam->id, 'season_id' => $this->season->id, 'question_count' => $questions, 'correct_count' => 0, 'incorrect_count' => 0, 'time' => 0, 'completed' => 1, 'created_at' => $date, 'updated_at' => $date]);
    }

    private function leagueResult(User $user, int $daysAgo): void
    {
        $league = League::factory()->create(['code' => substr(fake()->uuid(), 0, 16), 'label' => 'Account report QA', 'start_at' => now()->subDays($daysAgo + 7), 'end_at' => now()->subDays($daysAgo)]);
        LeagueResult::factory()->create(['league_id' => $league->id, 'user_id' => $user->id, 'league_number' => 2, 'total_score' => -6, 'total_correct' => 1, 'total_incorrect' => 9, 'total_duration' => 10, 'position' => 2, 'rank_position' => 2]);
    }
}
