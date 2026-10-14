<?php

namespace App\Livewire\Frontend\Study;

use App\Actions\Study\AuthorizeStudyAccess;
use App\Helpers\StudyAnalytics;
use App\Helpers\StudyCalendar;
use App\Helpers\StudyTrackingSettings;
use App\Models\Season;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class StudyReport extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    #[Locked]
    public string $weekStart;

    public $seasonId = '';

    public function mount(string $weekStart): void
    {
        AuthorizeStudyAccess::run(auth()->user());
        $bounds = StudyCalendar::bounds($weekStart);
        abort_unless($weekStart === $bounds['start']->toDateString() && ! $bounds['start']->isFuture(), 422);
        $this->weekStart = $weekStart;
        $candidate = request()->query('season', session('studySeasonId'));
        $valid = (is_int($candidate) || (is_string($candidate) && ctype_digit($candidate))) && Season::whereKey((int) $candidate)->exists();
        $this->seasonId = $valid ? (int) $candidate : (activeSeason()?->id ?? Season::orderByDesc('start_date')->value('id') ?? '');
        session()->put('studySeasonId', $this->seasonId);
    }

    public function updatedSeasonId(): void
    {
        $this->validate(['seasonId' => 'nullable|integer|exists:seasons,id']);
        session()->put('studySeasonId', $this->seasonId);
        $this->resetPage('studyHistoryPage');
    }

    #[On('study-changed')]
    public function refreshReport(): void
    {
        AuthorizeStudyAccess::run(auth()->user());
    }

    private function pages(array $rows, string $name): LengthAwarePaginator
    {
        $total = count($rows);
        $page = max(1, min((int) $this->getPage($name), max(1, (int) ceil($total / 20))));

        return new LengthAwarePaginator(array_slice($rows, ($page - 1) * 20, 20), $total, 20, $page, ['pageName' => $name, 'path' => request()->url()]);
    }

    public function render()
    {
        AuthorizeStudyAccess::run(auth()->user());
        $enabled = StudyTrackingSettings::enabled() && StudyTrackingSettings::startedOn();
        $seasons = $enabled ? Season::select('id', 'name', 'start_date', 'end_date')->orderByDesc('start_date')->get() : collect();
        $valid = is_int($this->seasonId) || (is_string($this->seasonId) && ctype_digit($this->seasonId));
        $season = $valid ? $seasons->firstWhere('id', (int) $this->seasonId) : null;
        $report = $enabled ? StudyAnalytics::report(auth()->user(), $this->weekStart, $season) : null;
        $history = $this->pages($report['history'] ?? [], 'studyHistoryPage');
        $topics = $this->pages($report['topics'] ?? [], 'studyTopicsPage');

        return view('livewire.frontend.study.study-report', compact('report', 'seasons', 'history', 'topics'));
    }
}
