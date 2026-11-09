<?php

namespace App\Http\Controllers\Backend;

use App\Actions\Study\AuthorizeStudyCommunication;
use App\Actions\Study\AuthorizeStudyPanel;
use App\Exports\StudyTableExport;
use App\Helpers\StudyCalendar;
use App\Helpers\StudyDetailTable;
use App\Helpers\StudyInput;
use App\Helpers\StudyPanelQuery;
use App\Helpers\StudyTrackingSettings;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class StudyController extends Controller
{
    private function week(Request $request): string
    {
        $data = $request->validate(['week' => 'nullable|date_format:Y-m-d']);
        $bounds = StudyCalendar::bounds($data['week'] ?? now());
        abort_if($bounds['start']->isFuture(), 422);

        return $bounds['start']->toDateString();
    }

    public function index(Request $request)
    {
        AuthorizeStudyPanel::run();
        $week = $this->week($request);
        $title = __('study.title');

        $teachers = auth()->user()->can('user-type:admin')
            ? StudyPanelQuery::teachers()->orderBy('name')->orderBy('surname')->get(['id', 'name', 'surname'])->mapWithKeys(fn ($teacher) => [$teacher->id => $teacher->display_name])->all()
            : [];

        return response()->view('backend.study.index', compact('title', 'week', 'teachers'))->header('Cache-Control', 'private, no-store');
    }

    public function show(Request $request, User $user)
    {
        AuthorizeStudyPanel::run($user);
        $week = $this->week($request);
        $request->validate(['tab' => 'sometimes|required|in:overview,history,activity,analysis,notes', 'season' => 'nullable|integer|exists:seasons,id']);
        $tab = $request->query('tab', 'overview');
        $title = __('study.title').' — '.trim($user->name.' '.$user->surname);

        return response()->view('backend.study.show', compact('title', 'week', 'user', 'tab'))->header('Cache-Control', 'private, no-store');
    }

    public function detailData(Request $request, User $user)
    {
        AuthorizeStudyPanel::run($user);

        return response()->json(StudyDetailTable::data($request, $user, $this->week($request)))->header('Cache-Control', 'private, no-store');
    }

    public function export(Request $request)
    {
        AuthorizeStudyPanel::run();
        $week = $this->week($request);
        $p = StudyPanelQuery::parameters($request);
        $students = StudyTrackingSettings::enabled() && StudyTrackingSettings::startedOn() ? StudyPanelQuery::filtered($week, $p)->get() : collect();
        $columns = [__('study.student'), __('E-posta Adresi'), __('study.week')];
        foreach (['goal', 'actual'] as $source) {
            foreach (['questions', 'pages', 'hours'] as $metric) {
                $columns[] = __('study.'.$source).' · '.__('study.'.$metric);
            }
        }
        $columns = [...$columns, __('study.overall').' (%)', __('study.state')];
        $rows = $students->map(fn ($student) => [trim($student->name.' '.$student->surname), $student->email, $week,
            $student->target_questions, $student->target_pages, $student->target_minutes === null ? null : (float) StudyInput::hours($student->target_minutes),
            (int) $student->questions, (int) $student->pages, (float) StudyInput::hours((int) $student->minutes),
            $student->achievement === null ? null : (float) $student->achievement, __('study.state_'.$student->study_state)])->all();

        $response = Excel::download(new StudyTableExport($columns, $rows), 'study-'.$week.'.xlsx');
        $response->headers->set('Cache-Control', 'private, no-store');

        return $response;
    }

    public function detailExport(Request $request, User $user)
    {
        AuthorizeStudyPanel::run($user);
        $data = StudyDetailTable::data($request, $user, $this->week($request), paginate: false);
        $columns = match ($request->query('table')) {
            'history' => ['week' => 'week', 'questions' => 'questions', 'pages' => 'pages', 'minutes' => 'hours', 'achievement' => 'overall', 'state' => 'state'],
            'days' => ['date' => 'date', 'manual_questions' => 'manual_questions', 'automatic_tests' => 'automatic_tests', 'automatic_exams' => 'automatic_exams', 'questions' => 'questions', 'pages' => 'pages', 'minutes' => 'hours', 'fragments' => 'fragments'],
            'entries' => ['date' => 'date', 'questions' => 'questions', 'pages' => 'pages', 'minutes' => 'hours'],
            'topics' => ['name' => 'topic', 'lesson' => 'lesson_panel', 'correct' => 'correct', 'incorrect' => 'incorrect', 'rate' => 'accuracy', 'state' => 'state'],
            'notes' => ['author' => 'note_author_panel', 'body' => 'note_body_panel', 'visibility' => 'note_visibility_panel', 'created_at' => 'date'],
        };
        $headings = array_map(fn ($field, $label) => __('study.'.$label).($request->query('table') === 'history' && in_array($field, ['questions', 'pages', 'minutes'], true) ? ' ('.__('study.goal_actual').')' : ''), array_keys($columns), array_values($columns));
        $numericHours = in_array($request->query('table'), ['entries', 'days'], true);
        $rows = array_map(fn ($row) => array_map(fn ($field) => $numericHours && $field === 'minutes' ? (float) $row[$field] : ($row[$field] ?? null), array_keys($columns)), $data['rows']);

        $response = Excel::download(new StudyTableExport($headings, $rows), 'study-'.$request->query('table').'-'.$user->id.'.xlsx');
        $response->headers->set('Cache-Control', 'private, no-store');

        return $response;
    }

    public function compose(Request $request, string $channel)
    {
        AuthorizeStudyCommunication::run();
        $request->validate(['student_id' => 'nullable|integer|exists:users,id']);
        abort_unless(in_array($channel, ['email', 'push'], true), 422);
        $student = $request->filled('student_id') ? User::findOrFail($request->integer('student_id')) : null;
        AuthorizeStudyCommunication::run($student);
        $week = $this->week($request);
        $filters = StudyPanelQuery::parameters($request);
        $title = __('study.communicate_title');

        return response()->view('backend.study.compose', compact('title', 'student', 'week', 'filters', 'channel'))->header('Cache-Control', 'private, no-store');
    }

    public function data(Request $request)
    {
        AuthorizeStudyPanel::run();
        $week = $this->week($request);
        $p = StudyPanelQuery::parameters($request);
        if (! StudyTrackingSettings::enabled() || ! StudyTrackingSettings::startedOn()) {
            return response()->json(['total' => 0, 'rows' => []])->header('Cache-Control', 'private, no-store');
        }
        $query = StudyPanelQuery::filtered($week, $p);
        $total = (clone $query)->count();
        $rows = $query->offset($p['offset'])->limit($p['limit'])->get()->map(fn ($student) => [
            'id' => $student->id, 'table' => 'study', 'name' => trim($student->name.' '.$student->surname),
            'email' => $student->email,
            'questions' => (int) $student->questions, 'pages' => (int) $student->pages, 'minutes' => StudyInput::hours((int) $student->minutes),
            'goals' => [__('study.questions') => $student->target_questions, __('study.pages') => $student->target_pages, __('study.hours') => StudyInput::hours($student->target_minutes) ?: null],
            'state_key' => $student->study_state,
            'goal' => implode(' / ', [$student->target_questions ?? '—', $student->target_pages ?? '—', StudyInput::hours($student->target_minutes) ?: '—']),
            'achievement' => $student->achievement === null ? '—' : $student->achievement.'%',
            'state' => __('study.state_'.$student->study_state), 'view_url' => route('admin.study.show', ['user' => $student->id, 'week' => $week]),
        ])->all();

        // Live counters and teacher relationships are intentionally not cached.
        return response()->json(compact('total', 'rows'))->header('Cache-Control', 'private, no-store');
    }
}
