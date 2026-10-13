<?php

namespace App\Livewire\Stats;

use App\Exports\FilterStatsQuestionsExport;
use App\Helpers\LearningTable;
use App\Helpers\StatisticsTable;
use App\Traits\CustomLivewireAlert;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class QuestionsStats extends Component
{
    use CustomLivewireAlert, WithPagination;

    public string $started_at;

    public string $ended_at;

    public string $locale;

    public string $search = '';

    public string $selectedOrderBy = 'question_count DESC';

    public bool $showResults = false;

    public function mount(): void
    {
        $this->locale = app()->getLocale();

        $this->started_at = now()->subDays(7)->format('Y-m-d');
        $this->ended_at = now()->format('Y-m-d');
    }

    #[Computed]
    public function orderByList(): array
    {
        return [
            'question_count DESC' => __('En Çok Soru Çözen'),
            'question_count ASC' => __('En Az Soru Çözen'),
            'correct_count DESC' => __('En Çok Doğru Soru Çözen'),
            'correct_count ASC' => __('En Az Doğru Soru Çözen'),
            'incorrect_count DESC' => __('En Çok Yanlış Soru Çözen'),
            'incorrect_count ASC' => __('En Az Yanlış Soru Çözen'),
            'success_rate DESC' => __('Başarı Yüzdesi 100 -> 0'),
            'success_rate ASC' => __('Başarı Yüzdesi 0 -> 100'),
        ];
    }

    public function filterResults(bool $export = false)
    {
        $this->showResults = true;

        LearningTable::access('stats');
        $this->validate(['started_at' => 'required|date_format:Y-m-d', 'ended_at' => 'required|date_format:Y-m-d|after_or_equal:started_at',
            'selectedOrderBy' => ['required', Rule::in(array_keys($this->orderByList()))]]);
        $results = StatisticsTable::questions($this->started_at, $this->ended_at, $this->selectedOrderBy);

        return $export ? $results->get() : $results->paginate(20);
    }

    public function export()
    {
        return Excel::download(
            new FilterStatsQuestionsExport($this->filterResults(export: true)),
            sprintf('İstatistikler - %s.xlsx', $this->orderByList()[$this->selectedOrderBy]),
            \Maatwebsite\Excel\Excel::XLSX
        );
    }

    public function render()
    {
        LearningTable::access('stats');

        return view('livewire.backend.stats.questions-server-table');
    }
}
