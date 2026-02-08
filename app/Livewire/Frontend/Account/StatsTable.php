<?php

namespace App\Livewire\Frontend\Account;

use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Lazy;
use Livewire\Component;
use Livewire\WithPagination;

#[Lazy(isolate: true)]
class StatsTable extends Component
{
    use WithPagination;

    public array $tabs = [];
    public string $activeTab = 'questions';

    public $activeSeason;

    public $user;

    public function mount(): void
    {
        $this->activeSeason = activeSeason() ?? false;
        $this->user = auth()->user();

        $this->tabs = [
            'questions' => __('Soru İstatistiği'),
            'lessonsDesc' => __('En Çok Çözülen Dersler'),
            'lessonsAsc' => __('En Az Çözülen Dersler'),
            'topicsDesc' => __('En Çok Çözülen Konular'),
            'topicsAsc' => __('En Az Çözülen Konular'),
        ];
    }

    public function chooseTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function results()
    {
        return match ($this->activeTab) {
            'questions' => $this->statsQuestions(),
            'lessonsDesc' => $this->stats(column: 'lesson_id', desc: true),
            'lessonsAsc' => $this->stats(column: 'lesson_id', asc: true),
            'topicsDesc' => $this->stats(column: 'topic_id', desc: true),
            'topicsAsc' => $this->stats(column: 'topic_id', asc: true),
            default => []
        };
    }

    public function statsQuestions(): \Illuminate\Pagination\LengthAwarePaginator
    {
        $subQuery = DB::table('exam_results')
            ->selectRaw("
                'exam_results' AS table_name,
                SUM(question_count)  AS question_count,
                SUM(correct_count)   AS correct_count,
                SUM(incorrect_count) AS incorrect_count,
                DATE(created_at)     AS updated_at
            ")
            ->when(
                $this->activeSeason,
                fn($query) => $query->whereBetween('created_at', [$this->activeSeason->start_date, $this->activeSeason->end_date])
            )
            ->where('user_id', $this->user->id)
            ->groupBy(DB::raw('DATE(created_at)'))
            ->unionAll(
                DB::table('tests_results')
                    ->selectRaw("
                        'tests_results' AS table_name,
                        SUM(question_count)  AS question_count,
                        SUM(correct_count)   AS correct_count,
                        SUM(incorrect_count) AS incorrect_count,
                        DATE(created_at)     AS updated_at
                    ")
                    ->when(
                        $this->activeSeason,
                        fn($query) => $query->whereBetween('created_at', [$this->activeSeason->start_date, $this->activeSeason->end_date])
                    )
                    ->where('user_id', $this->user->id)
                    ->groupBy(DB::raw('DATE(created_at)'))
            );

        return DB::query()
            ->fromSub($subQuery, 't')
            ->selectRaw("
                SUM(question_count)  AS question_count,
                SUM(correct_count)   AS correct_count,
                SUM(incorrect_count) AS incorrect_count,
                updated_at
            ")
            ->groupBy('updated_at')
            ->orderByDesc('updated_at')
            ->paginate(20);
    }

    public function stats(string $column = 'lesson_id', bool $desc = false, bool $asc = false): \Illuminate\Pagination\LengthAwarePaginator
    {
        $subQuery = DB::table('exam_result_details')
            ->selectRaw(sprintf("
                COUNT(correct) as total_count,
                SUM(IF(correct = 1, 1, 0)) as correct_count,
                SUM(IF(correct = -1, 1, 0)) as empty_count,
                %s,
                DATE(updated_at) AS updated_at
            ", $column))
            ->when(
                $this->activeSeason,
                fn($query) => $query->whereBetween('updated_at', [$this->activeSeason->start_date, $this->activeSeason->end_date])
            )
            ->whereRaw(sprintf("exam_result_id IN(SELECT id FROM exam_results WHERE user_id = %d)", $this->user->id))
            ->groupBy($column)
            ->unionAll(
                DB::table('tests_result_details')
                    ->selectRaw(sprintf("
                        COUNT(correct) as total_count,
                        SUM(IF(correct = 1, 1, 0)) as correct_count,
                        SUM(IF(correct = -1, 1, 0)) as empty_count,
                        %s,
                        DATE(updated_at) AS updated_at
                    ", $column))
                    ->when(
                        $this->activeSeason,
                        fn($query) => $query->whereBetween('updated_at', [$this->activeSeason->start_date, $this->activeSeason->end_date])
                    )
                    ->whereRaw(sprintf("tests_result_id IN(SELECT id FROM tests_results WHERE user_id = %d)", $this->user->id))
                    ->groupBy($column)
            );

        $columnName = 'lessons.name';
        if ($column == 'topic_id') {
            $columnName = 'topics.title';
        }

        return DB::query()
            ->fromSub($subQuery, 't')
            ->selectRaw(sprintf("
                SUM(t.total_count) AS total_count,
                SUM(t.correct_count) AS correct_count,
                SUM(t.empty_count) AS empty_count,
                t.%s,
                %s as name,
                t.updated_at
            ", $column, $columnName))
            ->when(
                $column == 'lesson_id',
                fn($query) => $query->leftJoin('lessons', 'lessons.id', '=', 't.lesson_id')
            )
            ->when(
                $column == 'topic_id',
                fn($query) => $query->leftJoin('topics', 'topics.id', '=', 't.topic_id')
            )
            ->groupBy(sprintf('t.%s', $column))
            ->orderByDesc('total_count')
            ->when(
                $desc,
                fn($query) => $query->having('correct_count', '>', 0)->orderByDesc('correct_count')
            )
            ->when(
                $asc,
                fn($query) => $query->having('correct_count', '<=', 0)->orderByDesc('empty_count')
            )
            ->paginate(20);
    }


    public function render()
    {
        return view('livewire.frontend.account.stats-table');
    }
}
