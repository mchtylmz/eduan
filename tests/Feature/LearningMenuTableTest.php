<?php

namespace Tests\Feature;

use App\Actions\Exams\UpdateExamQuestionsAction;
use App\Enums\RoleTypeEnum;
use App\Enums\StatusEnum;
use App\Livewire\Exams\ExamTable;
use App\Livewire\Exams\FavoriteTable;
use App\Livewire\Exams\ResultsDetailTable;
use App\Livewire\Exams\ResultsTable;
use App\Livewire\Offcanvas;
use App\Livewire\Questions\QuestionTable;
use App\Models\AnswerAI;
use App\Models\AnswerAIVote;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\ExamResultDetail;
use App\Models\Language;
use App\Models\Question;
use App\Models\User;
use App\Models\UserAiUsage;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class LearningMenuTableTest extends TestCase
{
    use DatabaseTransactions;

    private User $actor;

    protected function setUp(): void
    {
        parent::setUp();
        app()->setLocale('nl');
        Language::firstOrCreate(['code' => 'nl'], ['name' => 'Nederlands', 'status' => StatusEnum::ACTIVE]);
        $this->actor = User::factory()->create();
        foreach (['dashboard:access', 'ai:view', 'questions:view', 'questions:update', 'questions:delete', 'exams:view', 'exams:update', 'exams:delete', RoleTypeEnum::TEACHER->value] as $name) {
            Permission::findOrCreate($name, 'web');
            if ($name !== RoleTypeEnum::TEACHER->value) {
                $this->actor->givePermissionTo($name);
            }
        }
        $this->actingAs($this->actor);
    }

    public static function lists(): array
    {
        return [['ai', 'ai', 'admin.ai.index', 'admin.ai.getData'], ['questions', 'questions', 'admin.questions.index', 'admin.questions.getData'],
            ['exams', 'exams', 'admin.exams.index', 'admin.exams.getData'], ['exam-results', 'exams', 'admin.exams.results', 'admin.exams.getResultsData']];
    }

    #[DataProvider('lists')]
    public function test_server_pagination_search_and_initial_menu_render(string $table, string $resource, string $pageRoute, string $dataRoute): void
    {
        $prefix = 'learning-'.Str::random(10);
        $records = [];
        foreach (['a', 'b'] as $suffix) {
            $question = Question::factory()->create(['locale' => 'nl', 'code' => $prefix.$suffix]);
            $exam = Exam::factory()->create(['locale' => 'nl', 'name' => $prefix.$suffix, 'created_by' => $this->actor->id]);
            $records[] = match ($table) {
                'questions' => $question,
                'exams' => $exam,
                'ai' => AnswerAI::create(['user_id' => $this->actor->id, 'locale' => 'nl', 'lesson_id' => $question->lesson_id, 'topic_id' => $question->topic_id, 'question_id' => $question->id, 'title' => $prefix.$suffix]),
                'exam-results' => ExamResult::create(['exam_id' => $exam->id, 'user_id' => $this->actor->id, 'question_count' => 10, 'completed' => 1]),
            };
        }
        $parameters = ['search' => $prefix, 'sort' => 'id', 'order' => 'asc', 'limit' => 1];
        $first = $this->getJson(route($dataRoute, $parameters))->assertOk()->assertJsonPath('total', 2)->assertJsonPath('rows.0.id', $records[0]->id);
        $this->getJson(route($dataRoute, [...$parameters, 'offset' => 1]))->assertOk()->assertJsonPath('rows.0.id', $records[1]->id);
        $this->assertArrayNotHasKey('content', $first->json('rows.0'));
        $this->assertArrayNotHasKey('password', $first->json('rows.0'));
        $this->assertStringContainsString('no-store', $first->headers->get('Cache-Control'));
        $page = $this->get(route($pageRoute))->assertOk()->assertSee($table.'-table')->assertDontSee('wire:lazy', false);
        if (in_array($table, ['ai', 'questions'])) {
            $page->assertSee('class="is-card-view"', false)->assertSee('data-user-cards', false);
        }
    }

    #[DataProvider('lists')]
    public function test_invalid_inputs_and_revoked_permissions_reject_cached_data(string $table, string $resource, string $pageRoute, string $dataRoute): void
    {
        $this->getJson(route($dataRoute, ['sort' => 'name;drop table']))->assertUnprocessable();
        $this->getJson(route($dataRoute, ['limit' => 101]))->assertUnprocessable();
        if ($table === 'ai') {
            $this->getJson(route($dataRoute, ['status' => 'active']))->assertUnprocessable();
        }
        $this->getJson(route($dataRoute))->assertOk();
        $this->actor->revokePermissionTo($resource.':view');
        $this->getJson(route($dataRoute))->assertForbidden();
        auth()->logout();
        $this->getJson(route($dataRoute))->assertUnauthorized();
    }

    public function test_question_filters_and_detail_delete_permission(): void
    {
        $question = Question::factory()->create(['locale' => 'nl', 'code' => 'learning-filter-'.Str::random(8), 'status' => 'passive', 'attachment' => null]);
        $params = ['search' => $question->code, 'lessons' => [$question->lesson_id], 'topics' => [$question->topic_id], 'locale' => 'nl', 'status' => 'passive'];
        $this->getJson(route('admin.questions.getData', $params))->assertJsonPath('total', 1);
        $this->getJson(route('admin.questions.getData', ['search' => $question->code]))->assertJsonPath('rows.0.attachment', null);
        $this->getJson(route('admin.questions.getData', [...$params, 'status' => 'active']))->assertJsonPath('total', 0);
        Livewire::test(QuestionTable::class)->call('showDetail', $question->id)->assertSee($question->code);
        $this->actor->revokePermissionTo('questions:delete');
        Livewire::test(QuestionTable::class)->call('delete', $question->id)->assertForbidden();
        $this->assertNotSoftDeleted($question);
    }

    public function test_ai_aggregate_sort_report_filter_and_model_invalidation(): void
    {
        $question = Question::factory()->create(['locale' => 'nl']);
        $answer = AnswerAI::create(['user_id' => $this->actor->id, 'locale' => 'nl', 'lesson_id' => $question->lesson_id, 'topic_id' => $question->topic_id, 'question_id' => $question->id, 'title' => 'learning-ai-'.Str::random(8), 'report' => 1]);
        $answer->forceFill(['created_at' => now()->subDay()])->save();
        $newest = AnswerAI::create(['user_id' => $this->actor->id, 'locale' => 'nl', 'lesson_id' => $question->lesson_id, 'topic_id' => $question->topic_id, 'question_id' => $question->id, 'title' => $answer->title.'-newest', 'report' => 0]);
        $this->getJson(route('admin.ai.getData', ['search' => $answer->title]))
            ->assertOk()->assertJsonPath('total', 2)->assertJsonPath('rows.0.id', $newest->id);
        $this->getJson(route('admin.ai.getData', ['search' => $answer->title, 'sort' => 'report', 'order' => 'desc']))
            ->assertOk()->assertJsonPath('rows.0.id', $answer->id);
        $page = $this->get(route('admin.ai.index'))->assertOk()->assertSee('data-sort-name="created_at"', false)->assertSee('data-sort-order="desc"', false);
        $this->assertMatchesRegularExpression('/value="created_at"[^>]*selected/', $page->getContent());
        AnswerAIVote::create(['answer_ai_id' => $answer->id, 'user_id' => $this->actor->id, 'vote' => 4]);
        UserAiUsage::create(['answer_ai_id' => $answer->id, 'user_id' => $this->actor->id, 'usage' => 7, 'remaining' => 0, 'usage_date' => now()]);
        $params = ['search' => $answer->title, 'report' => 1, 'lessons' => [$question->lesson_id], 'topics' => [$question->topic_id], 'sort' => 'votes_count'];
        $this->getJson(route('admin.ai.getData', $params))->assertOk()->assertJsonPath('total', 1)->assertJsonPath('rows.0.votes_count', 1)->assertJsonPath('rows.0.usages_count', 1)->assertJsonPath('rows.0.votes_avg_vote', 4);
        AnswerAIVote::create(['answer_ai_id' => $answer->id, 'user_id' => $this->actor->id, 'vote' => 2]);
        $this->getJson(route('admin.ai.getData', $params))->assertJsonPath('rows.0.votes_count', 2)->assertJsonPath('rows.0.votes_avg_vote', 3);
    }

    public function test_exam_question_pivot_filters_and_linked_json_tables(): void
    {
        $question = Question::factory()->create(['locale' => 'nl']);
        $exam = Exam::factory()->create(['locale' => 'nl', 'code' => 'learning-pivot-'.Str::random(8), 'created_by' => $this->actor->id, 'visibility' => 0]);
        $params = ['search' => $exam->code, 'lessons' => [$question->lesson_id], 'topics' => [$question->topic_id], 'visibility' => 0];
        $this->getJson(route('admin.exams.getData', $params))->assertJsonPath('total', 0);
        UpdateExamQuestionsAction::run($exam, [$question->id => ['order' => 1, 'lesson_id' => $question->lesson_id, 'topic_id' => $question->topic_id]]);
        $this->getJson(route('admin.exams.getData', $params))->assertJsonPath('total', 1)->assertJsonPath('rows.0.questions_count', 1);
        $this->getJson(route('admin.exams.getQuestionsData', ['exam_id' => $exam->id, 'limit' => 5]))->assertOk()->assertJsonPath('total', 1)->assertJsonPath('rows.0.id', $question->id);
        $exam->favorites()->attach($this->actor);
        $this->getJson(route('admin.exams.getFavoritesData', ['exam_id' => $exam->id]))->assertOk()->assertJsonPath('total', 1)->assertJsonMissing(['password' => $this->actor->password]);
        Livewire::test(ExamTable::class)->call('showModal', $exam->id)->assertDispatched('open-learning-table');
        Livewire::test(Offcanvas::class)->call('openLearningTable', 'exams.question-table', $exam->id)->assertDispatched('learning-table-opened')->assertSee('exam-questions-table');
        Livewire::test(\App\Livewire\Exams\QuestionTable::class, ['examId' => $exam->id])->assertSee('exam-questions-table');
        Livewire::test(FavoriteTable::class, ['examId' => $exam->id])->assertSee('exam-favorites-table');
    }

    public function test_teacher_result_scope_filters_and_detail_authorization(): void
    {
        $this->actor->givePermissionTo(RoleTypeEnum::TEACHER->value);
        $student = User::factory()->create();
        $outsider = User::factory()->create();
        $this->actor->students()->attach($student);
        $exam = Exam::factory()->create(['locale' => 'nl', 'created_by' => $this->actor->id]);
        $own = ExamResult::create(['exam_id' => $exam->id, 'user_id' => $student->id, 'completed' => 0, 'season_id' => 0]);
        $foreign = ExamResult::create(['exam_id' => $exam->id, 'user_id' => $outsider->id, 'completed' => 1, 'season_id' => 0]);
        $question = Question::factory()->create(['locale' => 'nl']);
        ExamResultDetail::create(['exam_result_id' => $own->id, 'question_id' => $question->id, 'lesson_id' => $question->lesson_id, 'topic_id' => $question->topic_id, 'correct' => 0]);
        $params = ['exams' => [$exam->id], 'season_id' => 0, 'completed' => 0];
        $this->getJson(route('admin.exams.getResultsData', $params))->assertJsonPath('total', 1)->assertJsonPath('rows.0.id', $own->id);
        $this->getJson(route('admin.exams.getResultsData', [...$params, 'completed' => 1]))->assertJsonPath('total', 0);
        $this->getJson(route('admin.exams.getResultDetailsData', ['exam_result_id' => $own->id]))->assertOk()->assertJsonPath('total', 1);
        $this->getJson(route('admin.exams.getResultDetailsData', ['exam_result_id' => $foreign->id]))->assertNotFound();
        Livewire::test(ResultsTable::class)->call('showDetail', $foreign->id)->assertNotFound();
        Livewire::test(Offcanvas::class)->call('openLearningTable', 'exams.results-detail-table', $foreign->id)->assertNotFound();
        Livewire::test(Offcanvas::class)->call('openLearningTable', 'users.user-table', $own->id)->assertForbidden();
        Livewire::test(ResultsDetailTable::class, ['examResultId' => $own->id])->assertSee($student->email);
        $this->actor->students()->detach($student);
        $this->getJson(route('admin.exams.getResultsData', $params))->assertJsonPath('total', 0);
        $this->getJson(route('admin.exams.getResultDetailsData', ['exam_result_id' => $own->id]))->assertNotFound();
    }

    public function test_refresh_bypasses_cached_rows(): void
    {
        $question = Question::factory()->create(['locale' => 'nl', 'code' => 'learning-cache-'.Str::random(8), 'sort' => 1]);
        $params = ['search' => $question->code];
        $this->getJson(route('admin.questions.getData', $params))->assertJsonPath('rows.0.sort', 1);
        DB::table('questions')->where('id', $question->id)->update(['sort' => 2]); // Deliberately bypass model invalidation.
        $this->getJson(route('admin.questions.getData', $params))->assertJsonPath('rows.0.sort', 1);
        $this->getJson(route('admin.questions.getData', [...$params, 'refresh' => 1]))->assertJsonPath('rows.0.sort', 2);
    }
}
