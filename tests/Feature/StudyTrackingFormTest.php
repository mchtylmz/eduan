<?php

namespace Tests\Feature;

use App\Actions\Study\SaveStudyActivity;
use App\Actions\Study\SaveStudyTarget;
use App\Actions\Study\StudyWrite;
use App\Enums\StatusEnum;
use App\Helpers\StudyCalendar;
use App\Livewire\Frontend\Study\StudyForm;
use App\Models\Language;
use App\Models\Season;
use App\Models\StudyActivityEntry;
use App\Models\StudyWeeklyTarget;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Tests\Concerns\UsesStudyTimezone;
use Tests\TestCase;

class StudyTrackingFormTest extends TestCase
{
    use DatabaseTransactions;
    use UsesStudyTimezone;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useStudyTimezone();
        $this->travelTo(now()->setDateTime(2026, 10, 14, 12, 0, 0));
        foreach (['nl', 'en', 'tr', 'fr'] as $locale) {
            Language::firstOrCreate(['code' => $locale], ['name' => $locale, 'status' => StatusEnum::ACTIVE]);
        }
        foreach (['study:access', 'user-type:admin'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        $this->owner = User::factory()->create();
        $this->owner->givePermissionTo('study:access');
        $this->actingAs($this->owner);
        settings()->set(['studyTrackingEnabled' => '1', 'studyTrackingStartedOn' => '2026-10-01', 'studyAllowPastActivities' => '0']);
        settings()->save();
    }

    private function form(): Testable
    {
        return Livewire::test(StudyForm::class, ['weekStart' => '2026-10-10']);
    }

    public function test_separate_account_page_is_private_and_forms_are_in_initial_html(): void
    {
        $this->get(route('frontend.study').'?lang=tr')->assertOk()
            ->assertSee('Çalışma Takibim')->assertSee('wire:submit="saveTarget"', false)
            ->assertSee('wire:submit="saveActivity"', false)->assertDontSee('member-sidebar', false)
            ->assertSee('noindex, nofollow', false);
        $response = $this->get(route('frontend.study'));
        $this->assertTrue($response->baseResponse->headers->hasCacheControlDirective('private'));
        $this->assertTrue($response->baseResponse->headers->hasCacheControlDirective('no-store'));
        $this->get(route('frontend.profile'))->assertOk()->assertSee(route('frontend.study'), false);
        $this->owner->revokePermissionTo('study:access');
        $this->get(route('frontend.study'))->assertForbidden();
        $this->get(route('frontend.profile'))->assertOk()->assertDontSee(route('frontend.study'), false);
        auth()->logout();
        $this->get(route('frontend.study'))->assertRedirect();
    }

    public function test_future_week_is_rejected_and_existing_week_is_normalized(): void
    {
        $this->get(route('frontend.study', ['week' => '2026-10-17']))->assertUnprocessable();
        $this->get(route('frontend.study', ['week' => '2026-10-13', 'lang' => 'tr']))->assertOk()->assertSee('10 Ekim');
        $this->getJson(route('frontend.study', ['week' => 'tomorrow']))->assertUnprocessable();
        $this->getJson(route('frontend.study', ['week' => ['bad']]))->assertUnprocessable();
    }

    public function test_student_workspace_has_clear_sections_week_links_and_permission_aware_account_menu(): void
    {
        foreach (['tr', 'nl', 'en', 'fr'] as $locale) {
            $response = $this->get(route('frontend.study').'?lang='.$locale)->assertOk();
            $response->assertSeeInOrder(['header-account-menu', __('account.profile'), __('study.my_study'), __('account.favorites')])
                ->assertSee(__('study.week_simple'))->assertSee(__('study.back_account'))
                ->assertSee(__('study.future_week_help'))->assertDontSee(__('study.calendar_help'))
                ->assertSee('study-section-nav', false)->assertSee('study-week-chart', false)
                ->assertSee('study-details', false)->assertSee('wire:submit="saveActivity"', false)
                ->assertSee('study-target-drawer', false)->assertSee('study-activity-drawer', false)
                ->assertSee(__('study.placeholder_questions'))->assertSee(__('study.placeholder_hours'));
            $response->assertViewHas('bounds', fn ($bounds) => $bounds['start']->toDateString() === '2026-10-10');
            $this->get(route('frontend.study', ['week' => '2026-10-03', 'lang' => $locale]))->assertOk()
                ->assertViewHas('bounds', fn ($bounds) => $bounds['start']->toDateString() === '2026-10-03')
                ->assertSee(route('frontend.study', ['week' => '2026-10-10']), false)
                ->assertSee(__('study.current_week'));
        }
    }

    public function test_study_tabs_are_get_links_with_one_current_tab_and_preserved_week_context(): void
    {
        $season = Season::create(['name' => 'Tab period', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => StatusEnum::ACTIVE]);
        $this->get(route('frontend.study'))->assertOk()->assertViewHas('studyTab', 'week');
        foreach (['week', 'history', 'analysis', 'notes', 'evaluation'] as $tab) {
            $query = ['week' => '2026-10-03', 'season' => $season->id, 'lang' => 'tr', 'tab' => $tab];
            $response = $this->get(route('frontend.study', $query))->assertOk()->assertViewHas('studyTab', $tab);
            $document = new \DOMDocument;
            @$document->loadHTML($response->getContent());
            $navigation = (new \DOMXPath($document))->query('//a[@data-study-tab]');
            $this->assertCount(5, $navigation);
            $current = [];
            foreach ($navigation as $link) {
                parse_str(parse_url($link->getAttribute('href'), PHP_URL_QUERY), $parameters);
                $this->assertSame($link->getAttribute('data-study-tab'), $parameters['tab']);
                $this->assertSame('2026-10-03', $parameters['week']);
                $this->assertSame((string) $season->id, $parameters['season']);
                $this->assertSame('tr', $parameters['lang']);
                if ($link->getAttribute('aria-current') === 'page') {
                    $current[] = $parameters['tab'];
                }
            }
            $this->assertSame([$tab], $current);
            $response->assertSee(route('frontend.study', ['week' => '2026-09-26'] + $query))
                ->assertSee(route('frontend.study', ['week' => '2026-10-10'] + $query))
                ->assertSee(route('frontend.study', ['season' => $season->id, 'lang' => 'tr', 'tab' => $tab]));
        }
    }

    public function test_unknown_empty_and_array_tab_values_are_rejected(): void
    {
        foreach (['unknown', '', ['evaluation']] as $tab) {
            $this->getJson(route('frontend.study', ['tab' => $tab]))->assertUnprocessable()->assertJsonValidationErrors('tab');
        }
    }

    public function test_drawers_preserve_new_draft_but_new_activity_does_not_edit_an_existing_entry(): void
    {
        $form = $this->form()->set('activityQuestions', 12)->call('openEditor', 'activity')
            ->assertSet('activityQuestions', 12)->assertDispatched('study-editor-opened', kind: 'activity');
        $form->set('activityQuestions', -1)->call('saveActivity')->assertHasErrors('activityQuestions')
            ->assertNotDispatched('study-editor-saved');
        $form->set('activityQuestions', 20)->call('saveActivity')->assertHasNoErrors()
            ->assertDispatched('study-editor-saved', kind: 'activity');
        $entry = StudyActivityEntry::where('user_id', $this->owner->id)->firstOrFail();
        $form->call('editActivity', $entry->id)->assertSet('editingEntryId', $entry->id)
            ->assertDispatched('study-editor-opened', kind: 'activity');
        $form->call('openEditor', 'activity')->assertSet('editingEntryId', null)->assertSet('activityQuestions', '');
        $this->assertSame(20, $entry->fresh()->questions);
        $this->form()->call('openEditor', 'target')->assertDispatched('study-editor-opened', kind: 'target');
        $this->form()->call('openEditor', 'invalid')->assertUnprocessable();
    }

    public function test_calendar_labels_keep_the_calendar_day_and_pagination_summary_is_localized(): void
    {
        $timezone = date_default_timezone_get();
        try {
            date_default_timezone_set('Europe/Istanbul');
            config(['app.timezone' => 'Europe/Brussels']);
            $day = CarbonImmutable::parse('2026-10-24 00:00:00', 'Europe/Istanbul');
            foreach (['tr' => '24 Ekim 2026', 'nl' => '24 oktober 2026', 'en' => '24 October 2026', 'fr' => '24 octobre 2026'] as $locale => $label) {
                app()->setLocale($locale);
                $this->assertSame($label, StudyCalendar::label($day));
                $this->assertSame($label, StudyCalendar::label('2026-10-24'));
                $paginator = new LengthAwarePaginator(range(41, 44), 44, 20, 3);
                $html = (string) $paginator->links('vendor.livewire.bootstrap');
                $this->assertStringContainsString(__('pagination.summary', ['first' => 41, 'last' => 44, 'total' => 44]), $html);
                if ($locale !== 'en') {
                    $this->assertStringNotContainsString('Showing', $html);
                }
            }
        } finally {
            date_default_timezone_set($timezone);
        }
    }

    public function test_disabled_module_reads_no_study_tables_and_blocks_forged_save(): void
    {
        settings()->set('studyTrackingEnabled', '0');
        settings()->save();
        DB::enableQueryLog();
        try {
            $form = $this->form()->assertSee(__('study.disabled'));
            $form->set('activityQuestions', 1)->call('saveActivity')->assertHasErrors('study');
            $studyReads = collect(DB::getQueryLog())->filter(fn ($query) => preg_match('/\b(?:from|into|update)\s+[`"]?study_/i', $query['query']));
            $this->assertCount(0, $studyReads);
        } finally {
            DB::disableQueryLog();
            DB::flushQueryLog();
        }
    }

    public function test_target_and_multiple_daily_entries_save_with_validation_and_success_feedback(): void
    {
        $form = $this->form()->set('targetQuestions', '150')->set('targetHours', '2,5')->call('saveTarget')
            ->assertHasNoErrors()->assertSee(__('study.target_saved'));
        $target = StudyWeeklyTarget::where('user_id', $this->owner->id)->firstOrFail();
        $this->assertSame(150, $target->questions);
        $this->assertSame(150, $target->minutes);
        $form->set('activityQuestions', 50)->call('saveActivity')->assertHasNoErrors()->assertSee(__('study.activity_saved'));
        $form->set('activityQuestions', 20)->call('saveActivity')->assertHasNoErrors();
        $this->assertEquals(70, StudyActivityEntry::where('user_id', $this->owner->id)->sum('questions'));
        $form->set('targetPages', '-1')->call('saveTarget')->assertHasErrors('study')->assertSee(__('study.target_locked'));
        $this->assertNull($target->fresh()->pages);
        $form->set('activityHours', [1])->call('saveActivity')->assertHasErrors('activityHours');
        $this->assertSame(2, StudyActivityEntry::where('user_id', $this->owner->id)->count());
    }

    public function test_entry_editor_is_scoped_and_updates_only_selected_fragment(): void
    {
        $entry = SaveStudyActivity::run($this->owner, '2026-10-14', ['questions' => 50], (string) Str::uuid());
        $form = $this->form()->call('editActivity', $entry->id)->assertSet('activityQuestions', 50);
        $form->set('activityQuestions', 30)->call('saveActivity')->assertHasNoErrors();
        $this->assertSame(30, $entry->fresh()->questions);
        $form->call('editActivity', $entry->id)->call('removeActivity')->assertHasNoErrors()->assertSee(__('study.activity_removed'));
        $this->assertSoftDeleted($entry);
        $other = User::factory()->create();
        $foreign = StudyActivityEntry::create(['user_id' => $other->id, 'recorded_by_id' => $other->id,
            'study_week_id' => $entry->study_week_id, 'activity_date' => '2026-10-14']);
        $this->form()->call('editActivity', $foreign->id)->assertStatus(404);
    }

    public function test_activity_cannot_escape_selected_week_future_day_or_activation_date(): void
    {
        $form = $this->form()->set('activityDate', '2026-10-09')->call('saveActivity')->assertHasErrors('study');
        $form->set('activityDate', '2026-10-15')->call('saveActivity')->assertHasErrors('study');
        $form->set('activityDate', '2026-02-30')->call('saveActivity')->assertHasErrors('activityDate');
        settings()->set('studyTrackingStartedOn', '2026-10-12');
        settings()->save();
        $form->set('activityDate', '2026-10-11')->call('saveActivity')->assertHasErrors('study');
        $this->assertSame(0, StudyActivityEntry::where('user_id', $this->owner->id)->count());
    }

    public function test_localized_date_display_never_changes_the_saved_iso_calendar_day(): void
    {
        foreach (['tr', 'nl', 'fr', 'en'] as $locale) {
            app()->setLocale($locale);
            $form = $this->form()->set('activityQuestions', 10);
            $form->set('activityDate', '13/10/2026')->call('saveActivity')->assertHasErrors('activityDate');
            $form->set('activityDate', '2026-10-13')->call('saveActivity')->assertHasNoErrors();
        }
        $entries = StudyActivityEntry::where('user_id', $this->owner->id)->get();
        $this->assertCount(4, $entries);
        $this->assertSame(['2026-10-13'], $entries->map(fn ($entry) => $entry->activity_date->toDateString())->unique()->values()->all());
    }

    public function test_closed_week_fields_are_locked_until_allowed_and_target_remains_closed(): void
    {
        $form = Livewire::test(StudyForm::class, ['weekStart' => '2026-10-03']);
        $form->set('targetQuestions', 100)->call('saveTarget')->assertHasErrors('study');
        $form->set('activityDate', '2026-10-08')->set('activityQuestions', 20)->call('saveActivity')->assertHasErrors('study');
        settings()->set('studyAllowPastActivities', '1');
        settings()->save();
        $form->call('saveActivity')->assertHasNoErrors();
        $form->call('saveTarget')->assertHasErrors('study');
        $this->owner->givePermissionTo('user-type:admin');
        $form->call('saveTarget')->assertHasNoErrors();
    }

    public function test_stale_form_has_explicit_conflict_and_revocation_is_not_a_business_warning(): void
    {
        $this->owner->givePermissionTo('user-type:admin');
        $target = SaveStudyTarget::run($this->owner, '2026-10-10', ['questions' => 100]);
        $form = $this->form();
        SaveStudyTarget::run($this->owner, '2026-10-10', ['questions' => 150], StudyWrite::snapshot($target));
        $form->set('targetQuestions', 200)->call('saveTarget')->assertHasErrors('study')->assertSee(__('study.conflict'));
        $this->assertSame(150, $target->fresh()->questions);
        $this->owner->revokePermissionTo('study:access');
        $form->call('saveTarget')->assertForbidden();
    }

    public function test_daily_totals_are_owner_scoped_deleted_entries_excluded_and_details_paginate(): void
    {
        $entries = [];
        for ($i = 1; $i <= 16; $i++) {
            $entries[] = SaveStudyActivity::run($this->owner, '2026-10-14', ['questions' => $i], (string) Str::uuid());
        }
        $deleted = SaveStudyActivity::run($this->owner, '2026-10-14', ['questions' => 999], (string) Str::uuid());
        $deleted->delete();
        $other = User::factory()->create();
        StudyActivityEntry::create(['user_id' => $other->id, 'recorded_by_id' => $other->id,
            'study_week_id' => $entries[0]->study_week_id, 'activity_date' => '2026-10-14', 'questions' => 777]);
        $this->form()->assertSee('136')->assertSee('study-entry-'.$entries[15]->id, false)
            ->assertDontSee('study-entry-'.$entries[0]->id.'"', false)->assertDontSee('>999<', false)->assertDontSee('>777<', false)
            ->call('gotoPage', 2)->assertSee('study-entry-'.$entries[0]->id, false)
            ->assertDontSee('study-entry-'.$entries[15]->id.'"', false);
    }

    public function test_weekly_total_precedes_newest_days_and_entry_total_is_not_the_paginated_subtotal(): void
    {
        SaveStudyActivity::run($this->owner, '2026-10-13', ['questions' => 50], (string) Str::uuid());
        for ($i = 0; $i < 16; $i++) {
            SaveStudyActivity::run($this->owner, '2026-10-14', ['questions' => 10], (string) Str::uuid());
        }
        $form = $this->form();
        $form->assertSeeInOrder(['id="study-daily_totals"', __('study.week_total'), '14', '13']);
        foreach ([1, 2] as $page) {
            $form->call('gotoPage', $page);
            $html = $form->html();
            preg_match('/id="study-entries".*?<tbody>(.*?)<\/tbody>/s', $html, $matches);
            $this->assertMatchesRegularExpression('/study-table-total.*?>210<\/td>/s', $matches[1]);
        }
    }

    public static function lockedProperties(): array
    {
        return [['weekStart', '2026-10-03'], ['editingEntryId', 999], ['editingBefore', ['questions' => 1]],
            ['targetBefore', ['questions' => 1]], ['submissionKey', (string) Str::uuid()]];
    }

    #[DataProvider('lockedProperties')]
    public function test_ownership_and_concurrency_state_cannot_be_replaced_from_browser(string $field, mixed $value): void
    {
        $this->expectException(CannotUpdateLockedPropertyException::class);
        $this->form()->set($field, $value);
    }

    public function test_study_source_translations_are_complete_for_all_four_languages(): void
    {
        $reference = require lang_path('tr/study.php');
        foreach (['nl', 'fr', 'en'] as $locale) {
            $values = require lang_path($locale.'/study.php');
            $this->assertSame(array_keys($reference), array_keys($values));
            $this->assertNotContains('', $values);
        }
    }

    public function test_student_can_save_each_week_once_and_two_open_forms_cannot_bypass_the_lock(): void
    {
        $first = $this->form();
        $stale = $this->form();
        $first->set('targetQuestions', 150)->call('saveTarget')->assertHasNoErrors()
            ->assertSee(__('study.target_locked'));
        $stale->set('targetQuestions', 300)->call('saveTarget')->assertHasErrors('study');
        $this->form()->set('targetQuestions', 400)->call('saveTarget')->assertHasErrors('study');
        $target = StudyWeeklyTarget::where('user_id', $this->owner->id)->firstOrFail();
        $this->assertSame(150, $target->questions);
        $this->assertSame(1, $target->revisions()->count());
        $this->travelTo(now()->addWeek());
        Livewire::test(StudyForm::class, ['weekStart' => '2026-10-17'])
            ->set('targetQuestions', 200)->call('saveTarget')->assertHasNoErrors();
        $this->assertSame(2, StudyWeeklyTarget::where('user_id', $this->owner->id)->count());
    }
}
