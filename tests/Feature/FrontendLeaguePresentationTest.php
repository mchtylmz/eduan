<?php

namespace Tests\Feature;

use App\Livewire\Frontend\Leagues\LeagueResultTable;
use App\Livewire\Frontend\Leagues\LeagueTable;
use App\Models\League;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class FrontendLeaguePresentationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_history_keeps_period_links_selection_and_localized_search(): void
    {
        $older = League::create(['code' => 'presentation-old', 'start_at' => '2026-09-19', 'end_at' => '2026-09-26']);
        $selected = League::create(['code' => 'presentation-current', 'start_at' => '2026-09-26', 'end_at' => '2026-10-03']);
        Cache::put('leagues_all', collect([$selected, $older]));

        foreach (['tr', 'nl', 'en'] as $locale) {
            app()->setLocale($locale);
            Livewire::withoutLazyLoading()->test(LeagueTable::class, ['selectedLeague' => $selected])
                ->assertSee(__('leagues.search_placeholder'))
                ->assertSee('x-model="search"', false)
                ->assertSee('aria-current="page"', false)
                ->assertSee(route('frontend.leagues.detail', $older->code), false)
                ->assertSee('2026-10-03', false)
                ->assertSee('league-standings-card', false);
        }
    }

    public function test_history_handles_existing_periods_with_nullable_dates(): void
    {
        $league = League::create(['code' => 'presentation-null-dates']);
        Cache::put('leagues_all', collect([$league]));

        Livewire::withoutLazyLoading()->test(LeagueTable::class, ['selectedLeague' => null])
            ->assertSee(route('frontend.leagues.detail', $league->code), false)
            ->assertSee('league-history-search', false);
    }

    public function test_detail_title_uses_localized_dates_instead_of_the_calculation_table(): void
    {
        foreach (['exam_result_details', 'tests_result_details'] as $index => $label) {
            $league = League::create(['code' => 'title-'.$index, 'label' => $label, 'start_at' => '2026-08-22', 'end_at' => '2026-08-29']);
            Cache::put('leagues_all', collect([$league]));

            foreach (['tr', 'nl', 'en'] as $locale) {
                app()->setLocale($locale);
                $response = $this->withSession(['locale' => $locale])->get(route('frontend.leagues.detail', $league->code))->assertOk();
                preg_match('/<title>(.*?)<\/title>/s', $response->getContent(), $matches);
                $this->assertStringContainsString(__('Lig Sonuçları'), $matches[1]);
                $this->assertStringContainsString(dateFormat($league->start_at, 'd M Y'), $matches[1]);
                $this->assertStringContainsString(dateFormat($league->end_at, 'd M Y'), $matches[1]);
                $this->assertStringNotContainsString($label, $matches[1]);
                $this->assertSame($label, $league->fresh()->label);
            }
        }

        $league = League::create(['code' => 'title-null', 'label' => 'exam_result_details']);
        $response = $this->get(route('frontend.leagues.detail', $league->code))->assertOk();
        preg_match('/<title>(.*?)<\/title>/s', $response->getContent(), $matches);
        $this->assertStringStartsWith(__('Lig Sonuçları').' | ', $matches[1]);
        $this->assertStringNotContainsString('exam_result_details', $matches[1]);
    }

    public function test_standings_preserve_rank_score_and_current_user_and_handle_empty_results(): void
    {
        $league = League::create(['code' => 'presentation-results', 'start_at' => '2026-09-26', 'end_at' => '2026-10-03']);
        $student = User::factory()->create(['name' => '<script>student</script>', 'surname' => 'Example']);
        $league->results()->create(['user_id' => $student->id, 'league_number' => 1, 'position' => 1, 'rank_position' => 1, 'total_score' => 593]);
        foreach ([2 => ['Şule', 'Akın'], 3 => ['Thomas', 'Janssen'], 4 => ['Outside', 'Podium']] as $position => [$name, $surname]) {
            $winner = User::factory()->create(['name' => $name, 'surname' => $surname]);
            $league->results()->create(['user_id' => $winner->id, 'league_number' => 1, 'position' => $position, 'rank_position' => $position, 'total_score' => 500 - $position]);
        }
        $this->actingAs($student);

        Livewire::withoutLazyLoading()->test(LeagueResultTable::class, ['league' => $league, 'name' => '1. Lig', 'ranking' => 1, 'userCount' => 10])
            ->assertSee('593')
            ->assertSee('my-league-position', false)
            ->assertSee('league-medal-1', false)
            ->assertSee('league-mini-podium', false)
            ->assertSee('title="Şule Akın"', false)
            ->assertSee('ŞA')
            ->assertSee('title="Thomas Janssen"', false)
            ->assertDontSee('title="Outside Podium"', false)
            ->assertDontSee('uploads/podium.png', false)
            ->assertSee('&lt;script&gt;student&lt;/script&gt;', false)
            ->assertDontSee('<script>student</script>', false);

        Livewire::withoutLazyLoading()->test(LeagueResultTable::class, ['league' => $league, 'name' => '2. Lig', 'ranking' => 2, 'userCount' => 10])
            ->assertSee(__('Gösterilecek sonuç bulunmuyor!'))
            ->assertDontSee('league-mini-podium', false)
            ->assertDontSee('593');
        Cache::put('leagues_all', collect());
        Livewire::withoutLazyLoading()->test(LeagueTable::class, ['selectedLeague' => null])
            ->assertSee('league-page-empty', false)
            ->assertSee(__('Gösterilecek sonuç bulunmuyor!'));
    }

    public function test_all_three_standings_are_in_initial_html_and_reuse_the_existing_cache(): void
    {
        $league = League::create(['code' => 'presentation-initial-html', 'start_at' => '2026-09-26', 'end_at' => '2026-10-03']);
        Cache::put('leagues_first', $league);
        Cache::put('leagues_all', collect([$league]));
        foreach ([1, 2, 3] as $ranking) {
            $student = User::factory()->create(['name' => 'Initial League '.$ranking, 'surname' => 'Example']);
            $league->results()->create(['user_id' => $student->id, 'league_number' => $ranking, 'position' => 1, 'rank_position' => 1 + ($ranking - 1) * 10, 'total_score' => 500 - $ranking]);
        }
        $response = $this->get(route('frontend.leagues'))->assertOk();
        foreach ([1, 2, 3] as $ranking) {
            $response->assertSee('Initial League '.$ranking);
        }
        $this->assertSame(3, substr_count($response->getContent(), 'class="league-standings-card"'));
        $response->assertDontSee('__lazyLoad', false);
        DB::enableQueryLog();
        DB::flushQueryLog();
        $this->get(route('frontend.leagues'))->assertOk();
        $queries = DB::getQueryLog();
        DB::disableQueryLog();
        $this->assertCount(0, array_filter($queries, fn ($query) => str_contains($query['query'], 'from `league_results`')));
    }
}
