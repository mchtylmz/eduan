<?php

namespace App\Http\Controllers\Backend;

use App\Actions\Study\AuthorizeStudyPanel;
use App\Helpers\ServerTable;
use App\Helpers\StudyCalendar;
use App\Helpers\StudyDetailTable;
use App\Helpers\StudyInput;
use App\Helpers\StudyPanelQuery;
use App\Helpers\StudyTrackingSettings;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

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

        return response()->view('backend.study.index', compact('title', 'week'))->header('Cache-Control', 'private, no-store');
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

    public function data(Request $request)
    {
        AuthorizeStudyPanel::run();
        $week = $this->week($request);
        $sorts = ['name' => 'name', 'surname' => 'surname', 'questions' => 'questions', 'pages' => 'pages', 'minutes' => 'minutes', 'achievement' => 'achievement'];
        $p = ServerTable::parameters($request, $sorts, 'name', ['week' => 'nullable|date_format:Y-m-d', 'state' => 'nullable|in:no_target,no_activity,zero_activity,low,on_track']);
        if (! StudyTrackingSettings::enabled() || ! StudyTrackingSettings::startedOn()) {
            return response()->json(['total' => 0, 'rows' => []])->header('Cache-Control', 'private, no-store');
        }
        $query = StudyPanelQuery::summary($week);
        if ($p['search'] !== '') {
            $query->where(fn ($q) => $q->where('name', 'like', '%'.$p['search'].'%')->orWhere('surname', 'like', '%'.$p['search'].'%')->orWhere('email', 'like', '%'.$p['search'].'%')->orWhere('username', 'like', '%'.$p['search'].'%'));
        }
        if (! empty($p['status'])) {
            $query->where('status', $p['status']);
        }
        match ($p['state'] ?? '') {
            'no_target' => $query->whereNull('achievement'),
            'no_activity' => $query->where('fragments', 0)->where('questions', 0),
            'zero_activity' => $query->where('fragments', '>', 0)->whereRaw('questions + pages + minutes = 0'),
            'low' => $query->where('achievement', '<', 50),
            default => null,
        };
        if (($p['state'] ?? '') === 'on_track') {
            $query->where('achievement', '>=', 50)->whereRaw('questions + pages + minutes > 0');
        }
        $total = (clone $query)->count();
        $rows = $query->orderBy($sorts[$p['sort']], $p['order'])->orderBy('id')->offset($p['offset'])->limit($p['limit'])->get()->map(fn ($student) => [
            'id' => $student->id, 'table' => 'study', 'name' => trim($student->name.' '.$student->surname),
            'email' => $student->email,
            'questions' => (int) $student->questions, 'pages' => (int) $student->pages, 'minutes' => StudyInput::hours((int) $student->minutes),
            'goal' => implode(' / ', [$student->target_questions ?? '—', $student->target_pages ?? '—', StudyInput::hours($student->target_minutes) ?: '—']),
            'achievement' => $student->achievement === null ? '—' : $student->achievement.'%',
            'state' => __('study.state_'.$student->study_state), 'view_url' => route('admin.study.show', ['user' => $student->id, 'week' => $week]),
        ])->all();

        // Live counters and teacher relationships are intentionally not cached.
        return response()->json(compact('total', 'rows'))->header('Cache-Control', 'private, no-store');
    }
}
