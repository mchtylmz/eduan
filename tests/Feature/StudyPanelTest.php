<?php

namespace Tests\Feature;

use App\Actions\Study\EnsureStudyWeek;
use App\Actions\Study\SaveStudyNote;
use App\Enums\StatusEnum;
use App\Livewire\Backend\Study\StudentReport;
use App\Livewire\Backend\Study\StudyEditor;
use App\Livewire\Backend\Study\StudyNotes;
use App\Livewire\Frontend\Study\StudyForm;
use App\Livewire\Frontend\Study\VisibleStudyNotes;
use App\Models\Language;
use App\Models\Season;
use App\Models\StudyActivityEntry;
use App\Models\StudyActivityRevision;
use App\Models\StudyNote;
use App\Models\StudyNoteRevision;
use App\Models\StudyTargetRevision;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Concerns\UsesStudyTimezone;
use Tests\TestCase;

class StudyPanelTest extends TestCase
{
    use DatabaseTransactions, UsesStudyTimezone;

    private User $teacher;

    private User $student;

    private User $other;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useStudyTimezone();
        $this->travelTo(now()->setDateTime(2026, 10, 14, 12, 0, 0));
        foreach (['nl', 'en', 'tr', 'fr'] as $locale) {
            Language::firstOrCreate(['code' => $locale], ['name' => $locale, 'status' => StatusEnum::ACTIVE]);
        }
        foreach (['study:access', 'study:view', 'study:update', 'dashboard:access', 'users:view', 'user-type:user', 'user-type:teacher', 'user-type:admin'] as $p) {
            Permission::findOrCreate($p, 'web');
        }
        $this->teacher = $this->staff();
        $this->student = $this->pupil();
        $this->other = $this->pupil();
        $this->teacher->students()->attach($this->student);
        $this->actingAs($this->teacher);
        settings()->set(['studyTrackingEnabled' => '1', 'studyTrackingStartedOn' => '2026-10-01', 'studyAllowPastActivities' => '0']);
        settings()->save();
    }

    private function staff(bool $admin = false): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['study:access', 'study:view', 'study:update', 'dashboard:access', 'users:view', $admin ? 'user-type:admin' : 'user-type:teacher']);

        return $user;
    }

    private function pupil(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['study:access', 'user-type:user']);

        return $user;
    }

    private function url(array $params = []): string
    {
        return route('admin.study.data', ['week' => '2026-10-10'] + $params);
    }

    private function note(User $author, string $body = 'Teacher feedback', bool $visible = false): StudyNote
    {
        $this->actingAs($author);

        return SaveStudyNote::run($this->student, ['body' => $body, 'student_visible' => $visible], (string) Str::uuid());
    }

    public function test_teacher_list_detail_and_private_response_are_scoped(): void
    {
        $this->get(route('admin.study.index'))->assertOk()->assertSee('study-table', false)->assertSee(route('admin.study.data'), false);
        $response = $this->getJson($this->url())->assertOk()->assertJsonPath('total', 1)->assertJsonPath('rows.0.id', $this->student->id)->assertJsonPath('rows.0.email', $this->student->email);
        $this->assertTrue($response->baseResponse->headers->hasCacheControlDirective('no-store'));
        $this->get(route('admin.study.show', $this->student))->assertOk()->assertSee('wire:submit="saveTarget"', false);
        $this->get(route('admin.study.show', $this->other))->assertForbidden();
        $this->getJson($this->url(['search' => $this->other->username]))->assertJsonPath('total', 0);
    }

    public function test_admin_and_hybrid_admin_can_read_unassigned_students_but_not_staff_as_students(): void
    {
        $admin = $this->staff(true);
        $admin->givePermissionTo('user-type:teacher');
        $this->actingAs($admin);
        $this->get(route('admin.study.show', $this->other))->assertOk();
        $this->get(route('admin.study.show', $this->teacher))->assertForbidden();
        $this->get(route('admin.study.show', $admin))->assertForbidden();
    }

    public static function requiredPermissions(): array
    {
        return array_map(fn ($p) => [$p], ['study:access', 'study:view', 'dashboard:access', 'users:view', 'user-type:teacher']);
    }

    #[DataProvider('requiredPermissions')]
    public function test_panel_requires_each_permission_even_if_actor_opens_self(string $permission): void
    {
        $this->teacher->revokePermissionTo($permission);
        $this->getJson($this->url())->assertForbidden();
        $this->get(route('admin.study.show', $this->teacher))->assertForbidden();
    }

    public function test_student_cannot_open_panel_and_frontend_owner_is_not_overridable(): void
    {
        $this->student->givePermissionTo(['dashboard:access', 'users:view', 'study:view', 'study:update']);
        $this->actingAs($this->student);
        $this->getJson($this->url())->assertForbidden();
        $form = Livewire::test(StudyForm::class, ['weekStart' => '2026-10-10', 'studentId' => $this->other->id]);
        $form->set('targetQuestions', 12)->call('saveTarget')->assertHasNoErrors();
        $this->assertDatabaseHas('study_weekly_targets', ['user_id' => $this->student->id, 'questions' => 12]);
        $this->assertDatabaseMissing('study_weekly_targets', ['user_id' => $this->other->id]);
    }

    public function test_week_input_sort_and_filters_reject_invalid_values(): void
    {
        foreach ([['week' => 'tomorrow'], ['week' => ['x']], ['week' => '2026-10-17'], ['state' => 'invalid'], ['sort' => 'body'], ['order' => 'bad']] as $params) {
            $this->getJson(route('admin.study.data', $params))->assertUnprocessable();
        }
        $this->getJson(route('admin.study.data', ['week' => '2026-10-14']))->assertOk();
    }

    public function test_weekly_sql_totals_state_filters_no_goal_zero_and_low_are_independent(): void
    {
        $students = [$this->student, $this->other, $this->pupil(), $this->pupil(), $this->pupil()];
        foreach (array_slice($students, 1) as $student) {
            $this->teacher->students()->attach($student);
        }
        $week = EnsureStudyWeek::run('2026-10-10');
        foreach (array_slice($students, 1) as $student) {
            DB::table('study_weekly_targets')->insert(['user_id' => $student->id, 'study_week_id' => $week->id, 'questions' => 100]);
        }
        foreach ([2 => 0, 3 => 20, 4 => 150] as $i => $questions) {
            StudyActivityEntry::create(['user_id' => $students[$i]->id, 'recorded_by_id' => $this->teacher->id, 'study_week_id' => $week->id, 'activity_date' => '2026-10-14', 'questions' => $questions, 'pages' => 0, 'minutes' => 0]);
        }
        $result = DB::table('exam_results')->insertGetId(['user_id' => $students[3]->id, 'exam_id' => 1, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('exam_result_details')->insert(['exam_result_id' => $result, 'question_id' => 1, 'answer_id' => 1, 'lesson_id' => 1, 'topic_id' => 1, 'time' => 0, 'created_at' => now(), 'updated_at' => now()]);
        foreach (['no_target' => 1, 'no_activity' => 2, 'zero_activity' => 1, 'low' => 3, 'on_track' => 1] as $state => $count) {
            $this->getJson($this->url(['state' => $state]))->assertOk()->assertJsonPath('total', $count);
        }
        $this->getJson($this->url(['sort' => 'questions', 'order' => 'desc']))->assertJsonPath('rows.0.questions', 150)->assertJsonPath('rows.0.achievement', '150.0%');
        $this->getJson($this->url(['search' => $students[3]->username]))->assertJsonPath('rows.0.questions', 21);
        $this->getJson(route('admin.study.data', ['week' => '2026-10-03']))->assertJsonPath('rows.0.questions', 0);
    }

    public function test_table_paginates_stably_and_no_scope_is_cached_after_relationship_removal(): void
    {
        foreach (range(1, 16) as $i) {
            $student = $this->pupil();
            $this->teacher->students()->attach($student);
        }
        $a = $this->getJson($this->url(['limit' => 15]))->assertJsonPath('total', 17)->assertJsonCount(15, 'rows')->json('rows');
        $b = $this->getJson($this->url(['limit' => 15, 'offset' => 15]))->assertJsonCount(2, 'rows')->json('rows');
        $this->assertSame([], array_intersect(array_column($a, 'id'), array_column($b, 'id')));
        $this->teacher->students()->detach($this->student);
        $this->getJson($this->url(['search' => $this->student->username]))->assertJsonPath('total', 0);
        $this->get(route('admin.study.show', $this->student))->assertForbidden();
    }

    public function test_panel_editor_saves_student_not_actor_and_audits_actor(): void
    {
        $form = Livewire::test(StudyEditor::class, ['weekStart' => '2026-10-10', 'studentId' => $this->student->id]);
        $form->set('targetQuestions', 150)->call('saveTarget')->assertHasNoErrors()->assertDispatched('study-changed');
        $form->set('activityQuestions', 20)->call('saveActivity')->assertHasNoErrors();
        $this->assertDatabaseHas('study_weekly_targets', ['user_id' => $this->student->id, 'questions' => 150]);
        $this->assertDatabaseMissing('study_weekly_targets', ['user_id' => $this->teacher->id]);
        $this->assertSame($this->teacher->id, StudyTargetRevision::latest('id')->first()->actor_id);
        $this->assertSame($this->teacher->id, StudyActivityRevision::latest('id')->first()->actor_id);
        $this->assertDatabaseHas('study_activity_entries', ['user_id' => $this->student->id, 'recorded_by_id' => $this->teacher->id, 'questions' => 20]);
    }

    public function test_read_only_staff_has_reports_and_notes_but_no_editor_and_forged_write_is_forbidden(): void
    {
        $this->teacher->revokePermissionTo('study:update');
        $this->get(route('admin.study.show', $this->student))->assertOk()->assertDontSee('wire:submit="saveTarget"', false)->assertDontSee('wire:submit="save"', false);
        Livewire::test(StudyNotes::class, ['studentId' => $this->student->id])->set('body', 'forged')->call('save')->assertForbidden();
        Livewire::test(StudyEditor::class, ['weekStart' => '2026-10-10', 'studentId' => $this->student->id])->assertForbidden();
    }

    public function test_closed_week_teacher_denied_admin_allowed_and_activation_guard_preserved(): void
    {
        $form = Livewire::test(StudyEditor::class, ['weekStart' => '2026-10-03', 'studentId' => $this->student->id]);
        $form->set('targetQuestions', 10)->call('saveTarget')->assertHasErrors('study');
        $form->set('activityQuestions', 10)->call('saveActivity')->assertHasErrors('study');
        $this->actingAs($this->staff(true));
        Livewire::test(StudyEditor::class, ['weekStart' => '2026-10-03', 'studentId' => $this->student->id])->set('targetQuestions', 10)->call('saveTarget')->assertHasNoErrors()
            ->set('activityQuestions', 10)->call('saveActivity')->assertHasNoErrors();
        Livewire::test(StudyEditor::class, ['weekStart' => '2026-09-26', 'studentId' => $this->student->id])->set('activityQuestions', 10)->set('activityDate', '2026-09-30')->call('saveActivity')->assertHasErrors('study');
    }

    public function test_editor_locked_student_and_revoked_relationship_rechecked(): void
    {
        $form = Livewire::test(StudyEditor::class, ['weekStart' => '2026-10-10', 'studentId' => $this->student->id]);
        $this->teacher->students()->detach($this->student);
        $form->call('saveTarget')->assertForbidden();
        $this->teacher->students()->attach($this->student);
        $this->expectException(CannotUpdateLockedPropertyException::class);
        Livewire::test(StudyEditor::class, ['weekStart' => '2026-10-10', 'studentId' => $this->student->id])->set('studentId', $this->other->id);
    }

    public function test_panel_history_links_remain_in_student_detail_context(): void
    {
        $season = Season::create(['name' => 'Study semester', 'start_date' => '2026-10-01', 'end_date' => '2026-10-31', 'status' => StatusEnum::ACTIVE]);
        Livewire::test(StudentReport::class, ['weekStart' => '2026-10-10', 'studentId' => $this->student->id, 'activeTab' => 'history'])->assertSee('data-server-table-page="study-history"', false);
        $rows = $this->getJson($this->detailUrl('history'))->assertOk()->json('rows');
        $this->assertContains(route('admin.study.show', ['user' => $this->student->id, 'week' => '2026-10-03', 'season' => $season->id, 'lang' => app()->getLocale()]), array_column($rows, 'view_url'));
    }

    public function test_notes_default_hidden_and_marked_visible_notes_only_reach_own_student(): void
    {
        $hidden = $this->note($this->teacher, 'Secret note');
        $visible = $this->note($this->teacher, 'Visible note', true);
        $this->actingAs($this->student);
        Livewire::test(VisibleStudyNotes::class)->assertSee('Visible note')->assertDontSee('Secret note');
        $this->actingAs($this->other);
        Livewire::test(VisibleStudyNotes::class)->assertDontSee('Visible note')->assertDontSee('Secret note');
        $this->assertFalse($hidden->student_visible);
        $this->assertTrue($visible->student_visible);
    }

    public function test_other_assigned_teacher_can_read_but_not_edit_or_delete_authors_note(): void
    {
        $note = $this->note($this->teacher);
        $otherTeacher = $this->staff();
        $otherTeacher->students()->attach($this->student);
        $this->actingAs($otherTeacher);
        $this->getJson($this->detailUrl('notes'))->assertOk()->assertJsonPath('rows.0.body', 'Teacher feedback')->assertJsonPath('rows.0.can_edit', false);
        $component = Livewire::test(StudyNotes::class, ['studentId' => $this->student->id]);
        $component->call('edit', $note->id)->assertForbidden();
        $this->assertDatabaseHas('study_notes', ['id' => $note->id, 'deleted_at' => null]);
    }

    public function test_notes_save_update_hide_delete_and_revisions_preserve_author(): void
    {
        Queue::fake();
        $note = $this->note($this->teacher, 'Start', true);
        $this->actingAs($this->staff(true));
        $component = Livewire::test(StudyNotes::class, ['studentId' => $this->student->id]);
        $component->call('edit', $note->id)->set('body', 'Updated')->set('studentVisible', false)->call('save')->assertHasNoErrors();
        $note->refresh();
        $this->assertSame($this->teacher->id, $note->author_id);
        $this->assertFalse($note->student_visible);
        $component->call('edit', $note->id)->call('remove')->assertHasNoErrors();
        $this->assertSoftDeleted($note);
        $this->assertSame(['create', 'update', 'delete'], StudyNoteRevision::where('note_id', $note->id)->orderBy('id')->pluck('operation')->all());
        Queue::assertNothingPushed();
        Http::assertNothingSent();
    }

    public function test_note_duplicate_uuid_stale_conflict_and_invalid_input_do_not_write(): void
    {
        $key = (string) Str::uuid();
        $a = SaveStudyNote::run($this->student, ['body' => 'A', 'student_visible' => false], $key);
        $b = SaveStudyNote::run($this->student, ['body' => 'A', 'student_visible' => false], $key);
        $this->assertSame($a->id, $b->id);
        $this->assertSame(1, StudyNoteRevision::where('note_id', $a->id)->count());
        $old = SaveStudyNote::snapshot($a);
        $a->update(['body' => 'Changed elsewhere']);
        $this->expectException(HttpException::class);
        SaveStudyNote::run($this->student, ['body' => 'B', 'student_visible' => true], $key, $a->id, $old);
    }

    public function test_note_invalid_body_html_escaping_pagination_and_cancel_reset(): void
    {
        $form = Livewire::test(StudyNotes::class, ['studentId' => $this->student->id]);
        $form->call('save')->assertHasErrors('body');
        $form->set('body', str_repeat('a', 10001))->call('save')->assertHasErrors('body');
        for ($i = 0; $i < 11; $i++) {
            $this->note($this->teacher, '<script>alert("x")</script> '.$i);
        }
        $form = Livewire::test(StudyNotes::class, ['studentId' => $this->student->id]);
        $form->assertDontSee('<script>alert("x")</script>', false);
        $this->getJson($this->detailUrl('notes', ['limit' => 5, 'offset' => 10]))->assertOk()->assertJsonPath('total', 11)->assertJsonCount(1, 'rows')->assertJsonPath('rows.0.body', '<script>alert("x")</script> 10');
        $form->set('body', 'Cancel')->set('studentVisible', true)->call('cancel')->assertSet('body', '')->assertSet('studentVisible', false);
    }

    public function test_note_identity_cannot_be_changed_in_livewire(): void
    {
        $note = $this->note($this->teacher);
        $this->expectException(CannotUpdateLockedPropertyException::class);
        Livewire::test(StudyNotes::class, ['studentId' => $this->student->id])->set('editingId', $note->id);
    }

    public function test_revoked_teacher_cannot_read_notes_or_refresh_report(): void
    {
        $notes = Livewire::test(StudyNotes::class, ['studentId' => $this->student->id]);
        $report = Livewire::test(StudentReport::class, ['studentId' => $this->student->id, 'weekStart' => '2026-10-10']);
        $this->teacher->students()->detach($this->student);
        $notes->call('save')->assertForbidden();
        $report->call('refreshReport')->assertForbidden();
    }

    public function test_foreign_note_id_and_foreign_author_write_are_denied(): void
    {
        $note = $this->note($this->teacher);
        $this->teacher->students()->attach($this->other);
        Livewire::test(StudyNotes::class, ['studentId' => $this->other->id])->call('edit', $note->id)->assertNotFound();
        $otherTeacher = $this->staff();
        $otherTeacher->students()->attach($this->student);
        $this->actingAs($otherTeacher);
        $this->expectException(HttpException::class);
        SaveStudyNote::run($this->student, ['body' => 'Forged', 'student_visible' => false], (string) Str::uuid(), $note->id, SaveStudyNote::snapshot($note), true);
    }

    public function test_deleted_note_submission_key_is_not_reusable(): void
    {
        $note = $this->note($this->teacher);
        SaveStudyNote::run($this->student, SaveStudyNote::snapshot($note), $note->submission_key, $note->id, SaveStudyNote::snapshot($note), true);
        $this->expectException(HttpException::class);
        SaveStudyNote::run($this->student, ['body' => 'Teacher feedback', 'student_visible' => false], $note->submission_key);
    }

    public function test_blank_note_and_conflicting_uuid_do_not_create_another_row(): void
    {
        Livewire::test(StudyNotes::class, ['studentId' => $this->student->id])->set('body', '   ')->call('save')->assertHasErrors('body');
        $note = $this->note($this->teacher);
        $this->expectException(HttpException::class);
        SaveStudyNote::run($this->student, ['body' => 'Different', 'student_visible' => false], $note->submission_key);
    }

    public function test_disabled_module_has_no_note_queries_or_writes(): void
    {
        settings()->set('studyTrackingEnabled', '0');
        settings()->save();
        $this->getJson($this->url())->assertJsonPath('rows', []);
        Livewire::test(StudyNotes::class, ['studentId' => $this->student->id])->assertSee(__('study.disabled'))->set('body', 'No write')->call('save')->assertHasErrors('note');
        $this->assertDatabaseMissing('study_notes', ['body' => 'No write']);
    }

    private function detailUrl(string $table, array $params = [], ?User $student = null): string
    {
        return route('admin.study.detail-data', ['user' => ($student ?? $this->student)->id, 'week' => '2026-10-10', 'table' => $table] + $params);
    }

    public function test_all_detail_tables_are_private_and_recheck_student_scope(): void
    {
        foreach (['history', 'topics', 'days', 'entries', 'notes'] as $table) {
            $response = $this->getJson($this->detailUrl($table))->assertOk()->assertJsonStructure(['total', 'rows']);
            $this->assertTrue($response->baseResponse->headers->hasCacheControlDirective('no-store'));
            $this->getJson($this->detailUrl($table, [], $this->other))->assertForbidden();
        }
        $this->teacher->students()->detach($this->student);
        $this->getJson($this->detailUrl('notes'))->assertForbidden();
    }

    public function test_detail_tables_validate_parameters_and_student_cannot_read_them(): void
    {
        foreach ([['table' => 'invalid'], ['limit' => 101], ['offset' => -1], ['sort' => 'password'], ['order' => 'bad'], ['search' => str_repeat('x', 251)], ['season' => 999999999]] as $parameters) {
            $this->getJson(route('admin.study.detail-data', $parameters + ['user' => $this->student->id, 'table' => 'notes']))->assertUnprocessable();
        }
        $this->getJson(route('admin.study.show', ['user' => $this->student->id, 'tab' => 'invalid']))->assertUnprocessable();
        $this->actingAs($this->student);
        $this->getJson($this->detailUrl('notes'))->assertForbidden();
    }

    public function test_detail_tabs_are_get_links_and_only_show_relevant_standard_tables(): void
    {
        foreach (['overview' => null, 'history' => 'history', 'activity' => 'entries', 'analysis' => 'topics', 'notes' => 'notes'] as $tab => $table) {
            $response = $this->get(route('admin.study.show', ['user' => $this->student->id, 'week' => '2026-10-10', 'tab' => $tab]))->assertOk()->assertSee($this->student->email);
            $response->assertSee('data-study-panel-tab="'.$tab.'"', false)->assertSee('data-study-panel-open="note"', false)->assertDontSee('wire:click="gotoPage', false);
            if ($table) {
                $response->assertSee('data-server-table-page="study-'.$table.'"', false);
            }
        }
        $this->getJson($this->url(['search' => $this->student->email]))->assertOk()->assertJsonPath('total', 1);
    }

    public function test_entry_tables_paginate_sort_search_and_read_only_permissions(): void
    {
        $week = EnsureStudyWeek::run('2026-10-10');
        for ($i = 1; $i <= 17; $i++) {
            StudyActivityEntry::create(['user_id' => $this->student->id, 'study_week_id' => $week->id, 'recorded_by_id' => $this->teacher->id, 'activity_date' => $i <= 8 ? '2026-10-12' : '2026-10-13', 'questions' => $i, 'pages' => 2, 'minutes' => 30, 'submission_key' => (string) Str::uuid()]);
        }
        $this->getJson($this->detailUrl('entries', ['sort' => 'questions', 'order' => 'desc']))->assertOk()->assertJsonPath('total', 17)->assertJsonCount(15, 'rows')->assertJsonPath('rows.0.questions', 17)->assertJsonPath('rows.0.can_edit', true);
        $this->getJson($this->detailUrl('entries', ['sort' => 'questions', 'order' => 'desc', 'offset' => 15]))->assertOk()->assertJsonCount(2, 'rows')->assertJsonPath('rows.0.questions', 2);
        $this->getJson($this->detailUrl('entries', ['search' => '2026-10-12']))->assertOk()->assertJsonPath('total', 8);
        $this->getJson($this->detailUrl('days'))->assertOk()->assertJsonPath('total', 2)->assertJsonPath('rows.0.questions', 117);
        $this->teacher->revokePermissionTo('study:update');
        $this->getJson($this->detailUrl('entries'))->assertOk()->assertJsonPath('rows.0.can_edit', false);
        $this->get(route('admin.study.show', ['user' => $this->student->id]))->assertOk()->assertDontSee('data-study-panel-open=', false);
    }

    public function test_notes_table_search_sort_and_disabled_module(): void
    {
        $first = $this->note($this->teacher, 'First feedback');
        $second = $this->note($this->teacher, 'Latest feedback', true);
        $second->forceFill(['created_at' => now()->addMinute()])->save();
        $this->getJson($this->detailUrl('notes'))->assertOk()->assertJsonPath('rows.0.id', $second->id)->assertJsonPath('rows.0.can_edit', true);
        $this->getJson($this->detailUrl('notes', ['search' => 'First']))->assertOk()->assertJsonPath('total', 1)->assertJsonPath('rows.0.id', $first->id);
        $first->delete();
        $this->getJson($this->detailUrl('notes'))->assertOk()->assertJsonPath('total', 1);
        settings()->set('studyTrackingEnabled', '0');
        settings()->save();
        foreach (['history', 'topics', 'days', 'entries', 'notes'] as $table) {
            $this->getJson($this->detailUrl($table))->assertOk()->assertJsonPath('rows', []);
        }
    }

    public function test_week_filter_normalizes_any_day_to_one_week_and_exposes_labeled_goals(): void
    {
        $baseline = null;
        foreach (['2026-10-10', '2026-10-12', '2026-10-14', '2026-10-16'] as $day) {
            $response = $this->getJson(route('admin.study.data', ['week' => $day]))->assertOk();
            $baseline ??= $response->json();
            $this->assertSame($baseline, $response->json());
        }
        $this->get(route('admin.study.index', ['week' => '2026-10-14']))->assertOk()
            ->assertSee('data-study-week', false)->assertViewHas('week', '2026-10-10');
        $this->assertArrayHasKey('goals', $baseline['rows'][0]);
        $this->assertArrayHasKey('state_key', $baseline['rows'][0]);
    }
}
