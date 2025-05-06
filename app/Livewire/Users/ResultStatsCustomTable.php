<?php

namespace App\Livewire\Users;

use App\Exports\FilterStatsExamsExport;
use App\Exports\FilterStatsTestsExport;
use App\Exports\FilterStatsTopicsExport;
use App\Models\Topic;
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
    public int $topicId = 0;

    public array $emails = [];

    public function mount(?User $user = null): void
    {
        $this->user = $user;
    }

    public function filter(): bool
    {
        $this->showResults = false;

        if (empty($this->resultType) || empty($this->resultPercent)) {
            $this->message(__('Sonuç türü ve kriteri seçimi yapılmalıdır!'))->toast(
                toast: false,
                position: 'center'
            )->error();
            return false;
        }

        if ($this->resultType == 'topics' && empty($this->topicId)) {
            $this->message(__('Konu seçimi yapılmalıdır!'))->toast(
                toast: false,
                position: 'center'
            )->error();
            return false;
        }

        $this->showResults = true;

        if ($this->user->exists) {
            $this->userId = $this->user->id ?? 0;
        }

        $this->message(__('Sonuçlar listelendi'))->toast()->success();
        return true;
    }

    public function topics()
    {
        return Topic::active()->orderBy('sort')->get();
    }

    public function filterTopics(bool $export = false): \Illuminate\Support\Collection|\Illuminate\Pagination\LengthAwarePaginator
    {
        $examQuery = DB::table('exam_result_details as erd')
            ->join('exam_results as er', 'erd.exam_result_id', '=', 'er.id')
            ->join('users', 'er.user_id', '=', 'users.id')
            ->join('topics', 'erd.topic_id', '=', 'topics.id')
            ->select(
                'users.id as user_id',
                'users.email',
                'users.name',
                'users.surname',
                'topics.id as topic_id',
                'topics.title as topic_title',
                DB::raw('COUNT(erd.id) as total_questions'),
                DB::raw('SUM(CASE WHEN erd.correct = 1 THEN 1 ELSE 0 END) as count_correct'),
                DB::raw('SUM(CASE WHEN erd.correct = 0 THEN 1 ELSE 0 END) as count_incorrect')
            )
            ->groupBy('users.id', 'topics.id');

        $testQuery = DB::table('tests_result_details as erd')
            ->join('tests_results as er', 'erd.tests_result_id', '=', 'er.id')
            ->join('users', 'er.user_id', '=', 'users.id')
            ->join('topics', 'erd.topic_id', '=', 'topics.id')
            ->select(
                'users.id as user_id',
                'users.email',
                'users.name',
                'users.surname',
                'topics.id as topic_id',
                'topics.title as topic_title',
                DB::raw('COUNT(erd.id) as total_questions'),
                DB::raw('SUM(CASE WHEN erd.correct = 1 THEN 1 ELSE 0 END) as count_correct'),
                DB::raw('SUM(CASE WHEN erd.correct = 0 THEN 1 ELSE 0 END) as count_incorrect')
            )
            ->groupBy('users.id', 'topics.id');

        $unionQuery = $examQuery->unionAll($testQuery);

        $finalQuery = DB::table(DB::raw("({$unionQuery->toSql()}) as sub"))
            ->mergeBindings($unionQuery) // union'daki parametreleri aktar
            ->select(
                'user_id',
                'email',
                'name',
                'surname',
                'topic_id',
                'topic_title',
                DB::raw('SUM(total_questions) as total_questions'),
                DB::raw('SUM(count_correct) as count_correct'),
                DB::raw('SUM(count_incorrect) as count_incorrect'),
                DB::raw('ROUND(SUM(count_correct) * 100.0 / SUM(total_questions), 2) as success_rate')
            )
            ->when($this->userId, fn($q) => $q->where('user_id', $this->userId))
            ->when($this->topicId, fn($q) => $q->where('topic_id', $this->topicId))
            ->groupBy('user_id', 'email', 'name', 'surname', 'topic_id', 'topic_title')
            ->having('success_rate', '<=', $this->resultPercent)
            ->orderBy('name')
            ->orderBy('topic_title')
            ->orderBy('success_rate', 'DESC');

        if (!$export) {
            $this->emails = $finalQuery->get()->toArray();
        }

        return $export ? $finalQuery->get() : $finalQuery->paginate(15);
    }

    public function filterExams(bool $export = false): \Illuminate\Support\Collection|\Illuminate\Pagination\LengthAwarePaginator
    {
        $query = DB::table('exam_results as result')
            ->join('users', 'result.user_id', '=', 'users.id')
            ->join('exams', 'result.exam_id', '=', 'exams.id')
            ->select(
                'users.name as name',
                'users.surname as surname',
                'users.email as email',
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

        if (!$export) {
            $this->emails = $query->get()->toArray();
        }

        return $export ? $query->get() : $query->paginate(15);
    }

    public function filterTests(bool $export = false): \Illuminate\Support\Collection|\Illuminate\Pagination\LengthAwarePaginator
    {
        $query = DB::table('tests_results as result')
            ->join('users', 'result.user_id', '=', 'users.id')
            ->join('tests', 'result.test_id', '=', 'tests.id')
            ->select(
                'users.name as name',
                'users.surname as surname',
                'users.email as email',
                'tests.name as test_name',
                'result.question_count as total_questions',
                'result.correct_count as count_correct',
                'result.incorrect_count as count_incorrect',
                'result.point as point',
                DB::raw('ROUND(result.correct_count * 100.0 / result.question_count, 2) as success_rate')
            )
            ->when($this->userId, function ($query) {
                return $query->where('users.id', $this->userId); // 👤 kullanıcı filtresi
            })
            ->groupBy('users.id', 'tests.id')
            ->having('success_rate', '<=', $this->resultPercent) // 🎯 başarı yüzdesi filtresi
            ->orderBy('users.name')
            ->orderBy('tests.name')
            ->orderBy('success_rate', 'DESC');

        if (!$export) {
            $this->emails = $query->get()->toArray();
        }

        return $export ? $query->get() : $query->paginate(15);
    }

    public function showSendEmails()
    {
        $this->dispatch(
            'showModal',
            component: 'stats.send-email-form',
            data: [
                'title' => __('Kullanıcılara Mail Gönder'),
                'emails' => $this->emails,
            ]
        );
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

    public function exportTests()
    {
        return Excel::download(
            new FilterStatsTestsExport($this->filterTests(export: true)),
            sprintf('istatistik_sinavlar_%d.xlsx', $this->resultPercent),
            \Maatwebsite\Excel\Excel::XLSX
        );
    }

    public function render()
    {
        return view('livewire.backend.users.result-stats-custom-table');
    }
}
