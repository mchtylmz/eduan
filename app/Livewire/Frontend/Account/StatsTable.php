<?php

namespace App\Livewire\Frontend\Account;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

#[Lazy(isolate: true)]
class StatsTable extends Component
{
    use WithPagination;

    #[Locked]
    public array $tabs = [];

    public string $activeTab = 'questions';

    #[Locked]
    public $activeSeason;

    #[Locked]
    public $user;

    public function mount(): void
    {
        $this->activeSeason = activeSeason() ?? false;
        $this->user = auth()->user();

        $this->tabs = [
            'questions' => __('account.questions_stats'),
            'lessonsDesc' => __('account.lessons_desc'),
            'lessonsAsc' => __('account.lessons_asc'),
            'topicsDesc' => __('account.topics_desc'),
            'topicsAsc' => __('account.topics_asc'),
        ];
    }

    public function chooseTab(string $tab): void
    {
        abort_unless(array_key_exists($tab, $this->tabs), 422);
        $this->activeTab = $tab;
        $this->resetPage();
    }

    public function updatedActiveTab(): void
    {
        $this->chooseTab($this->activeTab);
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

    public function statsQuestions(): LengthAwarePaginator
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
                fn ($query) => $query->whereBetween('created_at', [$this->activeSeason->start_date, $this->activeSeason->end_date])
            )
            ->where('user_id', auth()->id())
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
                        fn ($query) => $query->whereBetween('created_at', [$this->activeSeason->start_date, $this->activeSeason->end_date])
                    )
                    ->where('user_id', auth()->id())
                    ->groupBy(DB::raw('DATE(created_at)'))
            );

        return DB::query()
            ->fromSub($subQuery, 't')
            ->selectRaw('
                SUM(question_count)  AS question_count,
                SUM(correct_count)   AS correct_count,
                SUM(incorrect_count) AS incorrect_count,
                updated_at
            ')
            ->groupBy('updated_at')
            ->orderByDesc('updated_at')
            ->paginate(20);
    }

    public function stats(string $column = 'lesson_id', bool $desc = false, bool $asc = false): LengthAwarePaginator
    {
        abort_unless(in_array($column, ['lesson_id', 'topic_id'], true), 422);

        $subQuery = DB::table('exam_result_details')
            ->selectRaw(sprintf('
                COUNT(correct) as total_count,
                SUM(IF(correct = 1, 1, 0)) as correct_count,
                SUM(IF(correct = -1, 1, 0)) as empty_count,
                %s
            ', $column))
            ->when(
                $this->activeSeason,
                fn ($query) => $query->whereBetween('updated_at', [$this->activeSeason->start_date, $this->activeSeason->end_date])
            )
            ->whereIn('exam_result_id', DB::table('exam_results')->select('id')->where('user_id', auth()->id()))
            ->groupBy($column)
            ->unionAll(
                DB::table('tests_result_details')
                    ->selectRaw(sprintf('
                        COUNT(correct) as total_count,
                        SUM(IF(correct = 1, 1, 0)) as correct_count,
                        SUM(IF(correct = -1, 1, 0)) as empty_count,
                        %s
                    ', $column))
                    ->when(
                        $this->activeSeason,
                        fn ($query) => $query->whereBetween('updated_at', [$this->activeSeason->start_date, $this->activeSeason->end_date])
                    )
                    ->whereIn('tests_result_id', DB::table('tests_results')->select('id')->where('user_id', auth()->id()))
                    ->groupBy($column)
            );

        $columnName = 'lessons.name';
        if ($column == 'topic_id') {
            $columnName = 'topics.title';
        }

        return DB::query()
            ->fromSub($subQuery, 't')
            ->selectRaw(sprintf('
                SUM(t.total_count) AS total_count,
                SUM(t.correct_count) AS correct_count,
                SUM(t.empty_count) AS empty_count,
                t.%s,
                %s as name
            ', $column, $columnName))
            ->when(
                $column == 'lesson_id',
                fn ($query) => $query->leftJoin('lessons', 'lessons.id', '=', 't.lesson_id')
            )
            ->when(
                $column == 'topic_id',
                fn ($query) => $query->leftJoin('topics', 'topics.id', '=', 't.topic_id')
            )
            ->groupBy(sprintf('t.%s', $column), $columnName)
            ->orderByDesc('total_count')
            ->when(
                $desc,
                fn ($query) => $query->having('correct_count', '>', 0)->orderByDesc('correct_count')
            )
            ->when(
                $asc,
                fn ($query) => $query->having('correct_count', '<=', 0)->orderByDesc('empty_count')
            )
            ->paginate(20);
    }

    public function render()
    {
        return view('livewire.frontend.account.stats-table');
    }
}
