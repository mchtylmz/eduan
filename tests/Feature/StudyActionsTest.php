<?php

namespace Tests\Feature;

use App\Actions\Study\EnsureStudyWeek;
use App\Enums\StatusEnum;
use App\Livewire\Backend\Study\StudyCommunication;
use App\Models\CommunicationBatch;
use App\Models\CommunicationDelivery;
use App\Models\Season;
use App\Models\StudyActivityEntry;
use App\Models\StudyNote;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Concerns\UsesStudyTimezone;
use Tests\Concerns\UsesWebCommunications;
use Tests\TestCase;

class StudyActionsTest extends TestCase
{
    use DatabaseTransactions, UsesStudyTimezone, UsesWebCommunications;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupCommunications();
        Permission::findOrCreate('study:communicate', 'web');
        Queue::fake();
        Mail::fake();
    }

    private function admin(): void
    {
        $this->staff->revokePermissionTo('user-type:teacher');
        $this->staff->givePermissionTo(['user-type:admin', 'study:communicate']);
    }

    private function sheet(string $route, array $params, callable $check): void
    {
        $response = $this->get(route($route, $params))->assertOk();
        $this->assertTrue($response->baseResponse->headers->hasCacheControlDirective('no-store'));
        $file = $response->baseResponse->getFile()->getPathname();
        $book = IOFactory::load($file);
        try {
            $check($book->getActiveSheet());
        } finally {
            $book->disconnectWorksheets();
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    public function test_teacher_exports_all_filtered_rows_and_email_search_without_communication_permission(): void
    {
        $this->student->update(['name' => '=2+2', 'email' => 'export-a@example.test', 'username' => 'export-a@example.test']);
        foreach (range(1, 16) as $i) {
            $u = User::factory()->create(['email' => 'export-'.$i.'@example.test', 'username' => 'export-'.$i.'@example.test']);
            $u->givePermissionTo(['study:access', 'user-type:user']);
            $this->staff->students()->attach($u);
        }
        $this->outsider->update(['email' => 'export-foreign@example.test']);
        $params = ['week' => '2026-10-10', 'search' => 'export-', 'limit' => 5, 'offset' => 10];
        $this->getJson(route('admin.study.data', $params))->assertJsonPath('total', 17)->assertJsonCount(5, 'rows');
        $this->sheet('admin.study.export', $params, function ($sheet) {
            $rows = $sheet->toArray();
            $this->assertCount(18, $rows);
            $this->assertNotContains('export-foreign@example.test', array_column($rows, 1));
            foreach ($rows as $index => $row) {
                if ($row[0] === '=2+2') {
                    $this->assertSame('s', $sheet->getCell('A'.($index + 1))->getDataType());
                }
            }
        });
        $this->get(route('admin.study.index'))->assertSee('data-table-export', false)->assertDontSee('data-table-query-link', false);
        $this->get(route('admin.study.compose', ['channel' => 'email', 'week' => '2026-10-10']))->assertForbidden();
    }

    public function test_status_and_state_filters_are_identical_for_list_export_and_compose(): void
    {
        $this->student->update(['status' => StatusEnum::PASSIVE]);
        $this->getJson(route('admin.study.data', ['week' => '2026-10-10', 'status' => 'active']))->assertJsonPath('total', 0);
        $this->sheet('admin.study.export', ['week' => '2026-10-10', 'status' => 'active'], fn ($sheet) => $this->assertCount(1, $sheet->toArray()));
        $this->getJson(route('admin.study.data', ['week' => '2026-10-10', 'status' => 'passive', 'state' => 'no_target']))->assertJsonPath('total', 1);
        $this->staff->givePermissionTo('study:communicate');
        Livewire::test(StudyCommunication::class, ['weekStart' => '2026-10-10', 'filters' => ['status' => 'active']])->assertSet('recipients', []);
        Livewire::test(StudyCommunication::class, ['weekStart' => '2026-10-10', 'studentId' => $this->student->id])->call('next')->assertHasErrors('communication')->assertSet('step', 1);
        $this->assertSame(0, CommunicationBatch::count());
    }

    public static function detailTables(): array
    {
        return array_map(fn ($table) => [$table], ['history', 'days', 'entries', 'topics', 'notes']);
    }

    #[DataProvider('detailTables')]
    public function test_all_detail_tables_export_the_whole_filtered_dataset_with_owner_scope(string $table): void
    {
        $season = Season::create(['name' => 'Export year', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 'active']);
        $week = EnsureStudyWeek::run('2026-10-10');
        foreach (range(1, 17) as $i) {
            StudyActivityEntry::create(['user_id' => $this->student->id, 'study_week_id' => $week->id, 'activity_date' => '2026-10-12', 'questions' => $i, 'pages' => 2, 'minutes' => 30, 'recorded_by_id' => $this->staff->id, 'submission_key' => (string) Str::uuid()]);
            StudyNote::create(['user_id' => $this->student->id, 'author_id' => $this->staff->id, 'body' => '=SUM(1,2) '.$i, 'student_visible' => false, 'submission_key' => (string) Str::uuid()]);
        }
        $params = ['user' => $this->student->id, 'week' => '2026-10-10', 'table' => $table, 'season' => $season->id, 'limit' => 5, 'offset' => 10];
        $expected = $this->getJson(route('admin.study.detail-data', $params))->assertOk()->json('total');
        $this->sheet('admin.study.detail-export', $params, function ($sheet) use ($expected, $table) {
            $this->assertCount($expected + 1, $sheet->toArray());
            if ($table === 'notes') {
                $this->assertSame('s', $sheet->getCell('B2')->getDataType());
            }if ($table === 'entries') {
                $this->assertSame('n', $sheet->getCell('D2')->getDataType());
            }if ($table === 'days') {
                $this->assertSame('n', $sheet->getCell('G2')->getDataType());
            }
        });
        $this->get(route('admin.study.detail-export', array_replace($params, ['user' => $this->outsider->id])))->assertForbidden();
    }

    public function test_detail_export_search_and_invalid_parameters(): void
    {
        foreach (['match', 'other'] as $text) {
            StudyNote::create(['user_id' => $this->student->id, 'author_id' => $this->staff->id, 'body' => $text, 'student_visible' => false, 'submission_key' => (string) Str::uuid()]);
        }
        $p = ['user' => $this->student->id, 'week' => '2026-10-10', 'table' => 'notes', 'search' => 'match'];
        $this->sheet('admin.study.detail-export', $p, fn ($sheet) => $this->assertSame('match', $sheet->getCell('B2')->getValue()));
        $this->getJson(route('admin.study.detail-export', array_replace($p, ['table' => 'password'])))->assertUnprocessable();
        $this->getJson(route('admin.study.export', ['sort' => 'password']))->assertUnprocessable();
        $this->getJson(route('admin.study.export', ['status' => 'bad']))->assertUnprocessable();
    }

    private function composer(array $values = [])
    {
        return Livewire::test(StudyCommunication::class, $values + ['studentId' => $this->student->id, 'weekStart' => '2026-10-10']);
    }

    public function test_admin_can_prepare_email_or_push_from_filters_and_single_student_without_sending(): void
    {
        $this->admin();
        foreach (['email', 'push'] as $channel) {
            $this->get(route('admin.study.compose', ['channel' => $channel, 'student_id' => $this->student->id, 'week' => '2026-10-10']))->assertOk()->assertSee('data-communication-form', false);
            $this->composer(['initialChannel' => $channel])->assertSet('channel', $channel)->assertSet('recipients', [$this->student->id])->call('next')->assertSet('step', 2)
                ->set('title', 'Hello {{name}}')->set('body', 'Study message')->call('next')->assertSet('step', 3)->call('previous')->assertSet('step', 2);
        }
        $this->student->update(['email' => 'selection@example.test']);
        $this->composer(['studentId' => null, 'filters' => ['search' => 'selection@example.test', 'limit' => 1, 'offset' => 99]])->assertSet('recipients', [$this->student->id]);
        $this->assertSame(0, CommunicationBatch::count());
        Http::assertNothingSent();
        Mail::assertNothingSent();
        Queue::assertNothingPushed();
    }

    public function test_study_send_revalidates_permission_and_uses_existing_outbox(): void
    {
        $this->admin();
        $form = $this->composer()->call('next')->set('title', 'Hello')->set('body', 'Message')->call('next')->assertSet('step', 3);
        $this->staff->revokePermissionTo('study:communicate');
        $form->call('enqueue')->assertForbidden();
        $this->assertSame(0, CommunicationBatch::count());
        $this->staff->givePermissionTo('study:communicate');
        $this->composer()->call('next')->set('title', 'Hello')->set('body', 'Message')->call('next')->call('enqueue')->assertSet('step', 0)->assertHasNoErrors();
        $this->assertSame(1, CommunicationBatch::count());
        $this->assertSame(1, CommunicationDelivery::where('user_id', $this->student->id)->count());
        Http::assertNothingSent();
        Mail::assertNothingSent();
        Queue::assertNothingPushed();
    }

    public function test_study_recipients_cannot_be_forged_and_teacher_scope_is_rechecked(): void
    {
        $this->staff->givePermissionTo('study:communicate');
        $this->get(route('admin.study.compose', ['channel' => 'email', 'student_id' => $this->outsider->id]))->assertForbidden();
        $form = $this->composer();
        $this->staff->students()->detach($this->student);
        $form->call('next')->assertForbidden();
        $this->staff->students()->attach($this->student);
        $this->expectException(CannotUpdateLockedPropertyException::class);
        $this->composer()->set('recipients', [$this->outsider->id]);
    }

    public function test_preview_cannot_be_skipped_or_changed_and_module_disabled_is_empty(): void
    {
        $this->admin();
        $this->composer()->call('enqueue')->assertStatus(409);
        $this->composer()->call('next')->set('title', 'Title')->set('body', 'Body')->call('next')->set('body', 'Changed')->call('enqueue')->assertSet('step', 2)->assertHasErrors('communication');
        settings()->set('studyTrackingEnabled', '0');
        settings()->save();
        $this->composer()->assertSet('recipients', [])->call('next')->assertHasErrors('recipients');
        $this->sheet('admin.study.export', [], fn ($sheet) => $this->assertCount(1, $sheet->toArray()));
    }

    public function test_permission_migration_grants_only_admin_roles_and_is_repeatable(): void
    {
        $adminRole = Role::create(['name' => 'study-audit-admin-'.Str::uuid(), 'guard_name' => 'web']);
        $teacherRole = Role::create(['name' => 'study-audit-teacher-'.Str::uuid(), 'guard_name' => 'web']);
        $adminRole->givePermissionTo('user-type:admin');
        $teacherRole->givePermissionTo('user-type:teacher');
        $migration = require database_path('migrations/2026_10_26_130000_grant_study_communication_permission.php');
        $migration->up();
        $migration->up();
        $this->assertTrue($adminRole->fresh()->hasPermissionTo('study:communicate'));
        $this->assertFalse($teacherRole->fresh()->hasPermissionTo('study:communicate'));
    }

    public function test_export_and_compose_reject_missing_permissions_before_validating_targets(): void
    {
        $this->getJson(route('admin.study.compose', ['channel' => 'email', 'student_id' => 999999999]))->assertForbidden();
        $this->staff->revokePermissionTo('study:view');
        $this->get(route('admin.study.export'))->assertForbidden();
        $this->actingAs($this->student);
        $this->get(route('admin.study.export'))->assertForbidden();
        $this->getJson(route('admin.study.compose', ['channel' => 'email']))->assertForbidden();
    }

    public function test_filtered_recipient_role_changes_are_rechecked_before_send(): void
    {
        $this->admin();
        $this->student->update(['email' => 'role-change@example.test']);
        $form = $this->composer(['studentId' => null, 'filters' => ['search' => 'role-change@example.test']])->call('next')->set('title', 'Title')->set('body', 'Body')->call('next')->assertSet('step', 3);
        $this->student->givePermissionTo('user-type:teacher');
        $form->call('enqueue')->assertForbidden();
        $this->assertSame(0, CommunicationBatch::count());
    }
}
