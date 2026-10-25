<?php

namespace Tests\Feature;

use App\Actions\Leagues\CalculateLeague;
use App\Enums\YesNoEnum;
use App\Exceptions\LeagueCalculationInProgress;
use App\Helpers\LeagueCache;
use App\Livewire\Frontend\Leagues\LeagueResultTable;
use App\Livewire\Frontend\Leagues\LeagueTable as PublicLeagues;
use App\Livewire\Leagues\LeagueRunForm;
use App\Livewire\Leagues\LeagueTable;
use App\Models\ExamResult;
use App\Models\ExamResultDetail;
use App\Models\League;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class LiveLeaguePresentationTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        config(['cache.stores.frontend' => ['driver' => 'array']]);
        $this->travelTo(now()->setDate(2026, 10, 25)->setTime(12, 0));
        settings()->set(['leagueStatus' => 'active', 'leagueResultModel' => ExamResultDetail::class,
            'leagueUserCount' => 1, 'leagueCorrectPoint' => 3, 'leagueIncorrectPoint' => -1]);
        settings()->save();
    }

    protected function tearDown(): void
    {
        $this->travelBack();
        parent::tearDown();
    }

    private function staff(bool $run = true): User
    {
        $actor = User::factory()->create();
        foreach ($run ? ['dashboard:access', 'leagues:view', 'leagues:run'] : ['dashboard:access', 'leagues:view'] as $name) {
            $actor->givePermissionTo(Permission::findOrCreate($name, 'web'));
        }
        $this->actingAs($actor);

        return $actor;
    }

    private function live(): League
    {
        return CalculateLeague::run('2026-10-24 00:00', '2026-10-30 23:59', live: true);
    }

    private function answer(User $student, int $question = 1): void
    {
        $attempt = ExamResult::create(['user_id' => $student->id, 'exam_id' => $question,
            'completed' => YesNoEnum::YES, 'question_count' => 1, 'season_id' => 1]);
        $attempt->details()->create(['question_id' => $question, 'answer_id' => $question,
            'lesson_id' => 1, 'topic_id' => 1, 'correct' => YesNoEnum::YES, 'time' => 10]);
    }

    public function test_form_defaults_to_exact_current_saturday_friday_and_single_mode(): void
    {
        $this->staff();
        Livewire::test(LeagueRunForm::class)->assertSet('start_at', '2026-10-24 00:00')
            ->assertSet('end_at', '2026-10-30 23:59')->assertSet('is_live', false)
            ->assertSee(__('leagues.period_help'))->assertSee('league-mode');
    }

    public function test_live_form_saves_empty_future_and_custom_period_without_changing_history(): void
    {
        $this->staff();
        $old = League::create(['code' => 'untouched-dates', 'start_at' => '2025-01-01', 'end_at' => '2025-01-10']);
        Livewire::test(LeagueRunForm::class)->set('is_live', true)->set('start_at', '2026-11-02 14:10')
            ->set('end_at', '2026-11-03 15:20')->call('run')->assertRedirect(route('admin.leagues.index'));
        $league = League::where('is_live', true)->firstOrFail();
        $this->assertSame('2026-11-03 15:20:59', $league->end_at->format('Y-m-d H:i:s'));
        $this->assertSame('scheduled', $league->calculationStatus());
        $this->assertNull($league->calculated_at);
        $this->assertSame(0, $league->results()->count());
        $this->assertSame('2025-01-10', $old->fresh()->end_at->format('Y-m-d'));
    }

    public function test_single_form_reports_empty_instead_of_false_success(): void
    {
        $this->staff();
        $count = League::count();
        Livewire::test(LeagueRunForm::class)->set('start_at', '2099-01-01 00:00')->set('end_at', '2099-01-02 00:00')
            ->call('run')->assertNoRedirect()->assertSet('alertOptions.text', __('leagues.no_data'));
        $this->assertSame($count, League::count());
    }

    public function test_unsupported_database_dates_roll_back_instead_of_saving_zero_dates(): void
    {
        $this->staff();
        $count = League::count();
        Livewire::test(LeagueRunForm::class)->set('is_live', true)
            ->set('start_at', '2099-01-02 10:00')->set('end_at', '2099-01-03 11:00')
            ->call('run')->assertNoRedirect()->assertSet('alertOptions.text', __('leagues.invalid_configuration'));
        $this->assertSame($count, League::count());
    }

    public function test_single_form_uses_same_scoring_and_reports_completed_result(): void
    {
        $this->staff();
        $this->answer(User::factory()->create(['league_approval' => 1]));
        Livewire::test(LeagueRunForm::class)->call('run')->assertRedirect(route('admin.leagues.index'));
        $league = League::where('start_at', '2026-10-24')->firstOrFail();
        $this->assertFalse($league->is_live);
        $this->assertSame(3, $league->results()->firstOrFail()->total_score);
    }

    public function test_form_rejects_invalid_reversed_and_missing_dates(): void
    {
        $this->staff();
        foreach ([['bad', '2026-10-30 23:59', 'start_at'], ['', '', 'start_at'], ['2026-10-25 12:00', '2026-10-24 12:00', 'end_at']] as [$start, $end, $field]) {
            Livewire::test(LeagueRunForm::class)->set('start_at', $start)->set('end_at', $end)->call('run')->assertHasErrors($field);
        }
    }

    public function test_form_denies_guest_and_rechecks_revoked_run_permission(): void
    {
        Livewire::test(LeagueRunForm::class)->assertForbidden();
        $actor = $this->staff();
        $form = Livewire::test(LeagueRunForm::class);
        $actor->revokePermissionTo('leagues:run');
        $form->call('run')->assertForbidden();
    }

    public function test_form_keeps_single_history_and_explains_conflict(): void
    {
        $this->staff();
        $league = League::create(['code' => 'single-conflict', 'label' => 'exam_result_details',
            'start_at' => '2026-10-24 00:00', 'end_at' => '2026-10-30 23:59:59']);
        Livewire::test(LeagueRunForm::class)->set('is_live', true)->call('run')->assertNoRedirect()
            ->assertSet('alertOptions.text', __('leagues.invalid_configuration'));
        $this->assertFalse($league->fresh()->is_live);
    }

    public function test_passive_form_and_recalculation_do_not_run(): void
    {
        $this->staff();
        $league = $this->live();
        $league->forceFill(['closed_at' => now()])->saveQuietly();
        settings()->set('leagueStatus', 'passive');
        settings()->save();
        Livewire::test(LeagueRunForm::class)->set('is_live', true)->call('run')->assertNoRedirect();
        Livewire::test(LeagueTable::class)->call('recalculate', $league->id)
            ->assertNotDispatched('refresh-server-table');
        $this->assertEquals($league->calculated_at, $league->fresh()->calculated_at);
    }

    public function test_panel_json_localizes_all_states_and_limits_recalculate_to_closed(): void
    {
        $this->staff();
        $leagues = [];
        foreach (['single', 'scheduled', 'live', 'finalizing', 'closed'] as $status) {
            $leagues[$status] = League::create(['code' => 'ui-'.$status, 'label' => 'exam_result_details',
                'is_live' => $status !== 'single', 'start_at' => $status === 'scheduled' ? now()->addDay() : now()->subDay(),
                'end_at' => in_array($status, ['finalizing', 'closed']) ? now()->subHour() : now()->addDay(),
                'closed_at' => $status === 'closed' ? now() : null,
                'calculated_at' => now(), 'calculated_through_at' => now()->subHour()]);
        }
        foreach (['tr', 'nl', 'en', 'fr'] as $locale) {
            app()->setLocale($locale);
            $json = $this->withSession(['locale' => $locale])->getJson(route('admin.leagues.getData', ['search' => 'ui-', 'limit' => 15]))
                ->assertOk()->assertHeader('Cache-Control', 'no-store, private')->json();
            $this->assertCount(5, $json['rows']);
            foreach ($json['rows'] as $row) {
                $status = substr($row['code'], 3);
                $this->assertSame(__('leagues.status_'.$status), $row['status']['label']);
                $this->assertSame($status === 'closed', $row['can_recalculate']);
                $this->assertSame(dateFormat($leagues[$status]->calculated_through_at, 'd/m/Y H:i'), $row['calculated_through_at']);
                $this->assertArrayNotHasKey('calculation_settings', $row);
            }
        }
    }

    public function test_panel_status_changes_at_boundary_without_a_calculation_or_cached_row(): void
    {
        $this->staff();
        $league = $this->live();
        $url = route('admin.leagues.getData', ['search' => $league->code]);
        $this->getJson($url)->assertJsonPath('rows.0.status.label', __('leagues.status_live'));
        $this->travelTo($league->end_at->copy()->addSecond());
        $this->getJson($url)->assertJsonPath('rows.0.status.label', __('leagues.status_finalizing'));
    }

    public function test_closed_row_recalculation_refreshes_table_and_preserves_closed_state(): void
    {
        $this->staff();
        $league = $this->live();
        $this->travelTo($league->end_at->copy()->addSecond());
        $league = CalculateLeague::run('', '', leagueId: $league->id);
        $closed = $league->closed_at;
        Livewire::test(LeagueTable::class)->call('recalculate', $league->id)
            ->assertDispatched('refresh-server-table', table: 'leagues')
            ->assertSet('alertOptions.text', __('leagues.saved'));
        $this->assertEquals($closed, $league->fresh()->closed_at);
    }

    public function test_recalculate_rejects_forged_open_single_missing_and_unpermitted_ids(): void
    {
        $actor = $this->staff();
        $league = $this->live();
        Livewire::test(LeagueTable::class)->call('recalculate', $league->id)->assertStatus(409);
        $single = League::create(['code' => 'ui-single-no-recalc']);
        Livewire::test(LeagueTable::class)->call('recalculate', $single->id)->assertStatus(409);
        Livewire::test(LeagueTable::class)->call('recalculate', 2147483647)->assertNotFound();
        $actor->revokePermissionTo('leagues:run');
        Livewire::test(LeagueTable::class)->call('recalculate', $league->id)->assertForbidden();
        $this->getJson(route('admin.leagues.getData', ['search' => $league->code]))->assertJsonPath('rows.0.can_recalculate', false);
    }

    public function test_same_second_calculation_invalidates_public_caches_and_keeps_frozen_capacity(): void
    {
        $first = User::factory()->create(['league_approval' => 1, 'name' => 'CacheFirst']);
        $second = User::factory()->create(['league_approval' => 1, 'name' => 'CacheSecond']);
        $this->answer($first);
        $league = $this->live();
        $this->answer($second);
        settings()->set('leagueUserCount', 10);
        settings()->save();
        $this->get(route('frontend.leagues'))->assertOk();
        $before = LeagueCache::resultsKey($league->id, 1, 1);
        Cache::put('unrelated-public-key', 'preserved');
        CalculateLeague::run('', '', leagueId: $league->id);
        $this->assertNotSame($before, LeagueCache::resultsKey($league->id, 1, 1));
        $this->assertSame('preserved', Cache::get('unrelated-public-key'));
        $this->assertFalse(Cache::has('leagues_first'));
        $this->assertFalse(Cache::has('leagues_all'));
        Livewire::test(LeagueResultTable::class, ['league' => $league->fresh(), 'name' => 'First', 'ranking' => 1])->assertSet('userCount', 1)->assertSee('CacheSecond');
        $this->get(route('frontend.leagues'))->assertOk()->assertSee('CacheSecond');
    }

    public function test_failed_calculation_keeps_last_public_cache_and_clock(): void
    {
        $league = $this->live();
        $key = LeagueCache::resultsKey($league->id, 1, 1);
        Cache::put('leagues_first', $league);
        Cache::put('leagues_all', collect([$league]));
        $league->calculation_settings = [];
        $league->saveQuietly();
        try {
            CalculateLeague::run('', '', leagueId: $league->id);
            $this->fail('Invalid frozen rules must fail.');
        } catch (\InvalidArgumentException) {
            $this->assertSame($key, LeagueCache::resultsKey($league->id, 1, 1));
            $this->assertEquals($league->calculated_at, Cache::get('leagues_first')->calculated_at);
            $this->assertTrue(Cache::has('leagues_all'));
        }
    }

    public function test_public_standings_keep_frozen_rank_bands_after_capacity_changes(): void
    {
        $first = User::factory()->create(['league_approval' => 1, 'name' => 'FrozenFirst']);
        $second = User::factory()->create(['league_approval' => 1, 'name' => 'FrozenSecond']);
        $this->answer($first);
        $this->answer($first, 2);
        $this->answer($second, 3);
        $league = $this->live();
        settings()->set('leagueUserCount', 10);
        settings()->save();
        Livewire::test(LeagueResultTable::class, ['league' => $league, 'name' => 'First', 'ranking' => 1])
            ->assertSee('FrozenFirst')->assertDontSee('FrozenSecond');
        Livewire::test(LeagueResultTable::class, ['league' => $league, 'name' => 'Second', 'ranking' => 2])
            ->assertSee('FrozenSecond')->assertDontSee('FrozenFirst');
    }

    public function test_busy_form_gives_retry_message_without_redirecting(): void
    {
        $this->staff();
        CalculateLeague::mock()->shouldReceive('handle')->once()
            ->andThrow(new LeagueCalculationInProgress('busy'));
        Livewire::test(LeagueRunForm::class)->set('is_live', true)->call('run')
            ->assertNoRedirect()->assertSet('alertOptions.text', __('leagues.busy'));
    }

    public function test_frontend_has_current_state_and_two_separate_clocks_in_four_languages(): void
    {
        $league = $this->live();
        foreach (['tr', 'nl', 'en', 'fr'] as $locale) {
            app()->setLocale($locale);
            Livewire::test(PublicLeagues::class, ['selectedLeague' => $league])->assertSee(__('leagues.status_live'))
                ->assertSee(__('leagues.calculated'))->assertSee(__('leagues.through'))->assertSee(__('leagues.refresh_help'))
                ->assertDontSee('wire:poll', false)->assertDontSee('leagues.status_', false);
        }
        $this->travelTo($league->end_at->copy()->addSecond());
        Livewire::test(PublicLeagues::class, ['selectedLeague' => $league])->assertSee(__('leagues.status_finalizing'));
    }

    public function test_panel_uses_shared_table_and_translated_create_control(): void
    {
        $this->staff();
        $this->get(route('admin.leagues.index'))->assertOk()->assertSee('leagues-table')
            ->assertSee('data-side-pagination="server"', false)->assertSee('data-page-size="15"', false)
            ->assertSee(__('leagues.create'))->assertSee('data-recalculate-confirm', false);
        $this->getJson(route('admin.leagues.getData', ['sort' => 'closed_at;drop']))->assertUnprocessable();
    }
}
