<?php

namespace App\Helpers;

use App\Actions\Study\AuthorizeStudyChange;
use App\Actions\Study\AuthorizeStudyPanel;
use App\Models\Season;
use App\Models\StudyActivityEntry;
use App\Models\StudyNote;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class StudyDetailTable
{
    public static function data(Request $request, User $user, string $week, bool $paginate = true): array
    {
        AuthorizeStudyPanel::run($user);
        $request->validate(['table' => 'required|in:history,topics,days,entries,notes']);
        $type = $request->query('table');
        $sorts = match ($type) {
            'entries' => ['date' => 'activity_date', 'questions' => 'questions', 'pages' => 'pages', 'minutes' => 'minutes'],
            'notes' => ['created_at' => 'created_at'],
            'history' => ['week' => 'date', 'achievement' => 'achievement'],
            'topics' => ['name' => 'name', 'correct' => 'correct', 'incorrect' => 'incorrect', 'rate' => 'rate'],
            'days' => ['date' => 'date', 'questions' => 'questions', 'pages' => 'pages', 'minutes' => 'minutes'],
        };
        $default = array_key_first($sorts);
        $p = ServerTable::parameters($request, $sorts, $default, ['table' => 'required|in:history,topics,days,entries,notes', 'week' => 'nullable|date_format:Y-m-d', 'season' => 'nullable|integer|exists:seasons,id']);
        $p['order'] = $request->query('order', $type === 'topics' ? 'asc' : 'desc');
        if (! StudyTrackingSettings::enabled() || ! StudyTrackingSettings::startedOn()) {
            return ['total' => 0, 'rows' => []];
        }
        $table = 'study-'.$type;
        if ($type === 'entries') {
            $query = StudyActivityEntry::where('user_id', $user->id)->where('activity_date', '>=', $week)
                ->where('activity_date', '<', StudyCalendar::bounds($week)['end']->toDateString());
            if ($p['search'] !== '') {
                $query->where('activity_date', 'like', '%'.$p['search'].'%');
            }

            return self::records($table, $query, $p, $sorts, fn ($entry) => [
                'id' => $entry->id, 'date' => StudyCalendar::label($entry->activity_date), 'questions' => $entry->questions,
                'pages' => $entry->pages, 'minutes' => StudyInput::hours($entry->minutes), 'can_edit' => $paginate && self::editable($user, $entry->activity_date->toDateString()),
            ], $paginate);
        }
        if ($type === 'notes') {
            $query = StudyNote::where('user_id', $user->id)->with('author:id,name,surname');
            if ($p['search'] !== '') {
                $query->where(fn ($q) => $q->where('body', 'like', '%'.$p['search'].'%')->orWhereHas('author', fn ($a) => $a->where('name', 'like', '%'.$p['search'].'%')->orWhere('surname', 'like', '%'.$p['search'].'%')));
            }

            return self::records($table, $query, $p, $sorts, fn ($note) => [
                'id' => $note->id, 'author' => $note->author?->display_name ?? __('study.unknown_author'), 'body' => $note->body,
                'visibility' => __($note->student_visible ? 'study.visible_to_student' : 'study.staff_only'),
                'created_at' => $note->created_at->format('d/m/Y H:i'),
                'can_edit' => auth()->user()->can('study:update') && (auth()->user()->can('user-type:admin') || $note->author_id === auth()->id()),
            ], $paginate);
        }
        $season = isset($p['season']) ? Season::find($p['season']) : null;
        $historyPeriods = $type === 'history' && ! $season ? Season::select('start_date', 'end_date')->get() : [];
        $report = StudyAnalytics::report($user, $week, $type === 'history' ? $season : null, $historyPeriods);
        // Days and calendar weeks are bounded derived rows; answer/topic totals already aggregate in SQL.
        $rows = match ($type) {
            'history' => collect($report['history'])->map(fn ($row) => [
                'id' => $row['start'], 'table' => $table, 'date' => $row['start'],
                'week' => StudyCalendar::label($row['start'], 'j M').' — '.StudyCalendar::label($row['end']),
                'questions' => ($row['target']['questions'] ?? '—').' / '.$row['actual']['questions'],
                'pages' => ($row['target']['pages'] ?? '—').' / '.$row['actual']['pages'],
                'minutes' => (StudyInput::hours($row['target']['minutes'] ?? null) ?: '—').' / '.StudyInput::hours($row['actual']['minutes']),
                'achievement' => $row['rates']['overall'], 'state' => __('study.state_'.$row['state']),
                'view_url' => $row['state'] === 'future' ? null : route('admin.study.show', ['user' => $user->id, 'week' => $row['start'], 'season' => $season?->id ?? '', 'lang' => app()->getLocale()]), 'hide_detail' => $row['state'] === 'future',
            ]),
            'topics' => collect($report['topics'])->map(fn ($row, $id) => ['id' => $id, 'table' => $table] + $row + ['state' => __('study.'.$row['label'])]),
            'days' => collect($report['days'])->map(fn ($row) => ['id' => $row['activity_date'], 'table' => $table, 'date' => $row['activity_date'], 'date_label' => StudyCalendar::label($row['activity_date'])] + $row),
        };
        if ($p['search'] !== '') {
            $search = mb_strtolower($p['search']);
            $rows = $rows->filter(fn ($row) => str_contains(mb_strtolower(implode(' ', array_filter($row, 'is_scalar'))), $search));
        }
        $rows = $rows->sortBy($sorts[$p['sort']], SORT_REGULAR, $p['order'] === 'desc')->values();
        $total = $rows->count();
        $rows = ($paginate ? $rows->slice($p['offset'], $p['limit']) : $rows)->map(function ($row) use ($type) {
            if ($type === 'days') {
                $row['date'] = $row['date_label'];
                $row['minutes'] = StudyInput::hours($row['minutes']);
            }
            if ($type === 'history') {
                $row['achievement'] = $row['achievement'] === null ? '—' : $row['achievement'].'%';
            }
            if ($type === 'topics') {
                $row['rate'] = $row['rate'] === null ? '—' : $row['rate'].'%';
            }

            return $row;
        })->values()->all();

        return compact('total', 'rows');
    }

    private static function records(string $table, Builder $query, array $p, array $sorts, callable $row, bool $paginate): array
    {
        if ($paginate) {
            return ServerTable::page($table, $query, $p, $sorts, $row, cache: false);
        }
        $rows = $query->reorder()->orderBy($sorts[$p['sort']], $p['order'])->orderBy($query->getModel()->getQualifiedKeyName())
            ->get()->map(fn ($record) => ['table' => $table, ...$row($record)])->all();

        return ['total' => count($rows), 'rows' => $rows];
    }

    private static function editable(User $user, string $date): bool
    {
        if (! auth()->user()->can('study:update')) {
            return false;
        }
        try {
            AuthorizeStudyChange::run($user, 'activity', $date);

            return true;
        } catch (HttpException $exception) {
            if (! in_array($exception->getStatusCode(), [403, 422], true)) {
                throw $exception;
            }

            return false;
        }
    }
}
