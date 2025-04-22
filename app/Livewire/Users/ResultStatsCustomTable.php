<?php

namespace App\Livewire\Users;

use App\Exports\FilterStatsExamsExport;
use App\Exports\FilterStatsTopicsExport;
use App\Models\User;
use App\Traits\CustomLivewireAlert;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class ResultStatsCustomTable extends Component
{
    use CustomLivewireAlert, WithPagination;

    public ?User $user;

    public bool $showResults = false;
    public string $resultType = '';
    public int $resultPercent = 0;
    public int $userId = 0;

    public function mount(?User $user = null): void
    {
        $this->user = $user;
    }

    public function filter(): bool
    {
        $this->showResults = true;

        if (empty($this->resultType) || empty($this->resultPercent)) {
            $this->message(__('Sonuç türü ve kriteri seçimi yapılmalıdır!'))->toast(
                toast: false,
                position: 'center'
            )->error();
            return false;
        }

        if ($this->user->exists) {
            $this->userId = $this->user->id ?? 0;
        }

        $this->message(__('Sonuçlar listelendi'))->toast()->success();
        return true;
    }

    public function filterTopics(bool $export = false): \Illuminate\Support\Collection|\Illuminate\Pagination\LengthAwarePaginator
    {
        $query = DB::table('exam_result_details as erd')
            ->join('exam_results as er', 'erd.exam_result_id', '=', 'er.id')
            ->join('users', 'er.user_id', '=', 'users.id')
            ->join('topics', 'erd.topic_id', '=', 'topics.id')
            ->join('exams', 'er.exam_id', '=', 'exams.id')
            ->select(
                'users.name as name',
                'users.surname as surname',
                'exams.name as exam_name',
                'topics.id as topic_id',
                'topics.title as topic_title',
                DB::raw('COUNT(erd.id) as total_questions'),
                DB::raw('SUM(CASE WHEN erd.correct = 1 THEN 1 ELSE 0 END) as count_correct'),
                DB::raw('SUM(CASE WHEN erd.correct = 0 THEN 1 ELSE 0 END) as count_incorrect'),
                DB::raw('ROUND(SUM(CASE WHEN erd.correct = 1 THEN 1 ELSE 0 END) * 100.0 / COUNT(erd.id), 2) as success_rate')
            )
            ->when($this->userId, function ($query) {
                return $query->where('users.id', $this->userId); // 👤 kullanıcı filtresi
            })
            ->groupBy('users.id', 'exams.id', 'erd.topic_id')
            ->having('success_rate', '<=', $this->resultPercent) // 🎯 başarı yüzdesi filtresi
            ->orderBy('users.name')
            ->orderBy('exams.name')
            ->orderBy('topics.title')
            ->orderBy('success_rate', 'DESC');

        return $export ? $query->get() : $query->paginate(15);
    }

    public function filterExams(bool $export = false): \Illuminate\Support\Collection|\Illuminate\Pagination\LengthAwarePaginator
    {
        $query = DB::table('exam_results as result')
            ->join('users', 'result.user_id', '=', 'users.id')
            ->join('exams', 'result.exam_id', '=', 'exams.id')
            ->select(
                'users.name as name',
                'users.surname as surname',
                'exams.name as exam_name',
                'result.question_count as total_questions',
                'result.correct_count as count_correct',
                'result.incorrect_count as count_incorrect',
                DB::raw('ROUND(result.correct_count * 100.0 / result.question_count, 2) as success_rate')
            )
            ->when($this->userId, function ($query) {
                return $query->where('users.id', $this->userId); // 👤 kullanıcı filtresi
            })
            ->groupBy('users.id', 'exams.id')
            ->having('success_rate', '<=', $this->resultPercent) // 🎯 başarı yüzdesi filtresi
            ->orderBy('users.name')
            ->orderBy('exams.name')
            ->orderBy('success_rate', 'DESC');

        return $export ? $query->get() : $query->paginate(15);
    }

    public function exportTopics()
    {
        return Excel::download(
            new FilterStatsTopicsExport($this->filterTopics(export: true)),
            sprintf('istatistik_konular_%d.xlsx', $this->resultPercent),
            \Maatwebsite\Excel\Excel::XLSX
        );
    }

    public function exportExams()
    {
        return Excel::download(
            new FilterStatsExamsExport($this->filterExams(export: true)),
            sprintf('istatistik_testler_%d.xlsx', $this->resultPercent),
            \Maatwebsite\Excel\Excel::XLSX
        );
    }

    public function render()
    {
        return view('livewire.backend.users.result-stats-custom-table');
    }
}
