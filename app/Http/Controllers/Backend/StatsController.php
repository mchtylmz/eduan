<?php

namespace App\Http\Controllers\Backend;

use App\Actions\Users\AuthorizeStudentAccess;
use App\Exports\FilterStatsExamsExport;
use App\Exports\FilterStatsQuestionsExport;
use App\Exports\FilterStatsTestsExport;
use App\Exports\FilterStatsTopicsExport;
use App\Helpers\LearningTable;
use App\Helpers\ServerTable;
use App\Helpers\StatisticsTable;
use App\Helpers\TableCache;
use App\Http\Controllers\Controller;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Excel;

class StatsController extends Controller
{
    protected array $tabs = [
        'stats',
        'corrects',
        'questions',
    ];

    private function parameters(Request $request, bool $questions): array
    {
        $sorts = $questions ? ['username' => 'username', 'name' => 'name', 'surname' => 'surname', 'question_count' => 'question_count', 'correct_count' => 'correct_count', 'incorrect_count' => 'incorrect_count', 'success_rate' => 'success_rate', 'updated_at' => 'updated_at'] :
            ['name' => 'name', 'surname' => 'surname', 'email' => 'email', 'content_name' => 'content_name', 'total_questions' => 'total_questions', 'count_correct' => 'count_correct', 'count_incorrect' => 'count_incorrect', 'point' => 'point', 'success_rate' => 'success_rate'];
        $filters = $questions ? ['started_at' => ['required', 'date_format:Y-m-d'], 'ended_at' => ['required', 'date_format:Y-m-d', 'after_or_equal:started_at']] : [
            'result_type' => ['nullable', 'in:topics,exams,tests'], 'result_percent' => ['required', 'integer', Rule::in(range(10, 100, 10))],
            'user_id' => ['nullable', 'integer', 'min:0'], 'topic_id' => ['nullable', 'integer', 'min:0', Rule::requiredIf($request->input('result_type') === 'topics')],
            'context_user_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
        $p = ServerTable::parameters($request, $sorts, $questions ? 'question_count' : 'name', $filters);
        if ($questions && ! $request->has('order')) {
            $p['order'] = 'desc';
        }

        return [$p, $sorts];
    }

    private function authorize(Request $request): void
    {
        if ($request->route()?->getName() === 'admin.users.getStatsData' || $request->route()?->getName() === 'admin.users.getStatsExport') {
            $request->validate(['context_user_id' => ['required', 'integer', 'exists:users,id']]);
            $user = User::findOrFail($request->integer('context_user_id'));
            StatisticsTable::authorize($user);
            $request->merge(['user_id' => $user->id]);
        } else {
            LearningTable::access('stats');
        }
        if ($request->integer('user_id')) {
            AuthorizeStudentAccess::run(User::findOrFail($request->integer('user_id')));
        }
    }

    private function query(array $p, bool $questions)
    {
        if ($questions) {
            $q = StatisticsTable::questions($p['started_at'], $p['ended_at'], 'question_count DESC')->reorder();
        } else {
            $kind = $p['result_type'];
            if ($kind === 'topics') {
                abort_unless(($p['topic_id'] ?? 0) > 0, 422);
            }
            if (($p['topic_id'] ?? 0) > 0) {
                Topic::findOrFail($p['topic_id']);
            }
            $q = StatisticsTable::results($kind, (int) ($p['user_id'] ?? 0), (int) ($p['topic_id'] ?? 0), (int) $p['result_percent'])->reorder();
            if ($kind === 'topics') {
                $q->selectRaw("CASE WHEN JSON_VALID(topic_title) THEN COALESCE(JSON_UNQUOTE(JSON_EXTRACT(topic_title, ?)), '') ELSE topic_title END AS content_name", ['$.'.app()->getLocale()]);
            } else {
                $q->selectRaw($kind.'.name as content_name');
            }
            if ($kind !== 'tests') {
                $q->selectRaw('NULL as point');
            }
        }
        $q = DB::query()->fromSub($q, 'summary')->select('summary.*');
        if ($p['search'] !== '') {
            $value = '%'.$p['search'].'%';
            $q->where(fn ($q) => $q->where('name', 'like', $value)->orWhere('surname', 'like', $value)->orWhere($questions ? 'username' : 'content_name', 'like', $value));
        }

        return $q;
    }

    private function data(Request $request, bool $questions)
    {
        $this->authorize($request);
        [$p,$sorts] = $this->parameters($request, $questions);
        if (! $questions && empty($p['result_type'])) {
            return response()->json(['total' => 0, 'rows' => []]);
        }
        $q = $this->query($p, $questions);
        $students = LearningTable::studentIds();
        $data = TableCache::remember($questions ? 'question-stats' : 'result-stats', $p, function () use ($q, $p, $sorts, $questions) {
            $total = (clone $q)->count();
            $rows = $q->orderBy($sorts[$p['sort']], $p['order'])->orderBy('user_id')
                ->when(! $questions, fn ($q) => $q->orderBy('content_name'))->offset($p['offset'])->limit($p['limit'])->get()->map(function ($r) use ($questions) {
                    $data = (array) $r;
                    if (! $questions) {
                        $data['content_name'] = (string) $data['content_name'];
                    } else {
                        $data['updated_at'] = dateFormat($data['updated_at'], 'd/m/Y H:i');
                    }
                    $data['id'] = implode(':', [$data['user_id'], $data['topic_id'] ?? $data['exam_id'] ?? $data['test_id'] ?? 'summary']);

                    return $data;
                })->all();

            return ['total' => $total, 'rows' => $rows];
        }, [$students]);

        return response()->json($data)->header('Cache-Control', 'private, no-store');
    }

    public function getData(Request $request)
    {
        return $this->data($request, false);
    }

    public function getQuestionsData(Request $request)
    {
        return $this->data($request, true);
    }

    private function export(Request $request, bool $questions)
    {
        $this->authorize($request);
        [$p,$sorts] = $this->parameters($request, $questions);
        abort_unless($questions || ! empty($p['result_type']), 422);
        $rows = $this->query($p, $questions)->orderBy($sorts[$p['sort']], $p['order'])->orderBy('user_id')->get();
        $export = $questions ? new FilterStatsQuestionsExport($rows) : match ($p['result_type']) {
            'topics' => new FilterStatsTopicsExport($rows),'exams' => new FilterStatsExamsExport($rows),'tests' => new FilterStatsTestsExport($rows),
        };

        return \Maatwebsite\Excel\Facades\Excel::download($export, 'statistics.xlsx', Excel::XLSX);
    }

    public function getExport(Request $request)
    {
        return $this->export($request, false);
    }

    public function getQuestionsExport(Request $request)
    {
        return $this->export($request, true);
    }

    public function index()
    {
        $activeTab = in_array(request()->input('tab'), $this->tabs) ? request()->input('tab') : 'questions';

        return view('backend.stats.index', [
            'title' => __('İstatistik Raporu'),
            'activeTab' => $activeTab,
            'tinymce' => true,
        ]);
    }
}
