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

    public string $tab = 'tests';

    public function statsDetailData(): \Illuminate\Pagination\LengthAwarePaginator
    {
        if ($this->tab === 'tests') {
            return DB::table('tests_result_details as erd')
                ->join('tests_results as er', 'erd.tests_result_id', '=', 'er.id')
                ->join('topics', 'erd.topic_id', '=', 'topics.id')
                ->join('lessons', 'erd.lesson_id', '=', 'lessons.id')
                ->select(
                    'topics.id as topic_id',
                    'topics.title as topic_title',
                    'lessons.id as lesson_id',
                    'lessons.name as lesson_title',
                    DB::raw('COUNT(erd.id) as total_questions'),
                    DB::raw('SUM(CASE WHEN erd.correct = 1 THEN 1 ELSE 0 END) as count_correct'),
                    DB::raw('SUM(CASE WHEN erd.correct = 0 THEN 1 ELSE 0 END) as count_incorrect')
                )
                ->where('er.user_id', user()->id ?? 0)
                ->groupBy('topics.id')
                ->orderBy('count_correct', 'DESC')
                ->paginate(25);
        }

        return DB::table('exam_result_details as erd')
            ->join('exam_results as er', 'erd.exam_result_id', '=', 'er.id')
            ->join('topics', 'erd.topic_id', '=', 'topics.id')
            ->join('lessons', 'erd.lesson_id', '=', 'lessons.id')
            ->select(
                'topics.id as topic_id',
                'topics.title as topic_title',
                'lessons.id as lesson_id',
                'lessons.name as lesson_title',
                DB::raw('COUNT(erd.id) as total_questions'),
                DB::raw('SUM(CASE WHEN erd.correct = 1 THEN 1 ELSE 0 END) as count_correct'),
                DB::raw('SUM(CASE WHEN erd.correct = 0 THEN 1 ELSE 0 END) as count_incorrect')
            )
            ->where('er.user_id', user()->id ?? 0)
            ->groupBy('topics.id')
            ->orderBy('count_correct', 'DESC')
            ->paginate(25);
    }


    public function render()
    {
        return view('livewire.frontend.account.stats-table');
    }
}
