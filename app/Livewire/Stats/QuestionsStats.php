<?php

namespace App\Livewire\Stats;

use App\Enums\RoleTypeEnum;
use App\Exports\FilterStatsExamsExport;
use App\Exports\FilterStatsQuestionsExport;
use App\Traits\CustomLivewireAlert;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
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

        $examResults = DB::table('exam_results')
            ->whereBetween('updated_at', [$this->started_at, $this->ended_at])
            ->select(
                'user_id',
                'question_count',
                'correct_count',
                'incorrect_count',
                'updated_at'
            );

        $testResults = DB::table('tests_results')
            ->whereBetween('updated_at', [$this->started_at, $this->ended_at])
            ->select(
                'user_id',
                'question_count',
                'correct_count',
                'incorrect_count',
                'updated_at'
            );

        $results = DB::query()
            ->fromSub(
                $examResults->unionAll($testResults),
                'results'
            )
            ->leftJoin('users', 'users.id', '=', 'results.user_id')
            ->select(
                'results.user_id',
                'users.username',
                'users.name',
                'users.surname',
                DB::raw('SUM(question_count) as question_count'),
                DB::raw('SUM(correct_count) as correct_count'),
                DB::raw('SUM(incorrect_count) as incorrect_count'),
                DB::raw('ROUND(SUM(correct_count) * 100.0 / SUM(question_count), 2) as success_rate'),
                DB::raw('MAX(results.updated_at) as updated_at')
            )
            ->when(
                auth()->user()->can(RoleTypeEnum::TEACHER),
                fn ($query) => $query->whereIn('user_id', auth()->user()->students()->select('id'))
            )
            ->groupBy(
                'results.user_id'
            )
            ->orderByRaw($this->selectedOrderBy);

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
        return view('livewire.backend.stats.questions-stats');
    }
}
