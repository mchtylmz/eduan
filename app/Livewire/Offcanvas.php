<?php

namespace App\Livewire;

use App\Helpers\LearningTable;
use App\Models\Exam;
use App\Models\League;
use Livewire\Component;

class Offcanvas extends Component
{
    public string|array $component;

    public string $title;

    public array $data;

    public bool $show = false;

    public string $position;

    protected $listeners = [
        'showOffcanvas' => 'showOffcanvas',
        'open-learning-table' => 'openLearningTable',
        'closeOffcanvas' => 'closeOffcanvas',
    ];

    public function showOffcanvas(string|array $component, array $data = []): void
    {
        $this->component = $component;

        $this->data = $data;
        $this->title = $data['title'] ?? '';

        $this->position = 'right';
        if (! empty($data['position']) && in_array($data['position'], ['top', 'left', 'md-left', 'right', 'md-right', 'bottom'])) {
            $this->position = $data['position'];
        }

        $this->show = true;
    }

    public function openLearningTable(string $view, int $recordId): void
    {
        LearningTable::access(match ($view) {
            'tests.results-detail-table' => 'tests', 'leagues.league-results-table' => 'leagues', default => 'exams'
        });
        abort_unless(in_array($view, ['exams.question-table', 'exams.favorite-table', 'exams.results-detail-table', 'tests.results-detail-table', 'leagues.league-results-table'], true), 403);
        if ($view === 'tests.results-detail-table') {
            $record = LearningTable::timedResultsQuery(LearningTable::studentIds())->findOrFail($recordId);
            $data = ['title' => __('Sonuç Detayları'), 'testsResultId' => $record->id];
        } elseif ($view === 'leagues.league-results-table') {
            $record = League::findOrFail($recordId);
            $data = ['title' => $record->code.' — '.__('Ligi Sonuç Detayları'), 'leagueId' => $record->id];
        } elseif ($view === 'exams.results-detail-table') {
            $record = LearningTable::resultsQuery(LearningTable::studentIds())->findOrFail($recordId);
            $data = ['title' => __('Sonuç Detayları'), 'examResultId' => $record->id];
        } else {
            $exam = Exam::findOrFail($recordId);
            $data = ['title' => $view === 'exams.favorite-table' ? sprintf('%s (%s)', __('Favoriye Ekleyenler'), $exam->name) : $exam->name, 'examId' => $exam->id];
        }
        $this->showOffcanvas($view, $data);
        $this->dispatch('learning-table-opened');
    }

    public function closeOffcanvas(): void
    {
        $this->show = false;

        $this->reset(['title', 'data', 'component', 'position']);
    }

    public function render()
    {
        return view('livewire.offcanvas');
    }
}
