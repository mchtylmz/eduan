<?php

namespace App\Livewire\Users;

use App\Actions\Users\AuthorizeStudentAccess;
use App\Actions\Users\AuthorizeUserMail;
use App\Exports\FilterStatsExamsExport;
use App\Exports\FilterStatsTestsExport;
use App\Exports\FilterStatsTopicsExport;
use App\Helpers\LearningTable;
use App\Helpers\StatisticsTable;
use App\Helpers\TableCache;
use App\Models\Topic;
use App\Models\User;
use App\Traits\CustomLivewireAlert;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class ResultStatsCustomTable extends Component
{
    use CustomLivewireAlert, WithPagination;

    #[Locked]
    public ?User $user = null;

    public bool $showResults = false;

    public string $resultType = '';

    public int $resultPercent = 0;

    public int $userId = 0;

    public int $topicId = 0;

    public array $emails = [];

    public function mount(?User $user = null): void
    {
        $this->user = $user ?? new User;
        $this->authorizeQuery();
    }

    private function authorizeQuery(): void
    {
        if ($this->user?->exists) {
            AuthorizeStudentAccess::run($this->user);
            $this->userId = $this->user->id;
        } else {
            abort_unless(auth()->user()?->can('dashboard:access') && auth()->user()?->can('stats:view'), 403);
            if ($this->userId) {
                AuthorizeStudentAccess::run(User::findOrFail($this->userId));
            }
        }
    }

    public function filter(): bool
    {
        $this->authorizeQuery();
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

    public function filterTopics(bool $export = false): Collection|LengthAwarePaginator
    {
        $this->authorizeQuery();
        $query = StatisticsTable::results('topics', $this->userId, $this->topicId, $this->resultPercent);
        if (! $export) {
            $this->emails = $query->get()->toArray();
        }

        return $export ? $query->get() : $query->paginate(15);
    }

    public function filterExams(bool $export = false): Collection|LengthAwarePaginator
    {
        $this->authorizeQuery();
        $query = StatisticsTable::results('exams', $this->userId, $this->topicId, $this->resultPercent);
        if (! $export) {
            $this->emails = $query->get()->toArray();
        }

        return $export ? $query->get() : $query->paginate(15);
    }

    public function filterTests(bool $export = false): Collection|LengthAwarePaginator
    {
        $this->authorizeQuery();
        $query = StatisticsTable::results('tests', $this->userId, $this->topicId, $this->resultPercent);
        if (! $export) {
            $this->emails = $query->get()->toArray();
        }

        return $export ? $query->get() : $query->paginate(15);
    }

    public function showSendEmails(array $filters = [])
    {
        $this->authorizeQuery();
        AuthorizeUserMail::run();
        if ($filters) {
            $valid = Validator::make($filters, [
                'result_type' => 'required|in:topics,exams,tests', 'result_percent' => 'required|integer|min:10|max:100',
                'user_id' => 'nullable|integer|min:0', 'topic_id' => 'nullable|integer|min:0',
            ])->validate();
            $this->resultType = $valid['result_type'];
            $this->resultPercent = (int) $valid['result_percent'];
            $this->userId = (int) ($valid['user_id'] ?? 0);
            $this->topicId = (int) ($valid['topic_id'] ?? 0);
        }
        $this->authorizeQuery();
        abort_unless(in_array($this->resultType, ['topics', 'exams', 'tests'], true), 422);
        $method = match ($this->resultType) {
            'topics' => 'filterTopics','exams' => 'filterExams','tests' => 'filterTests'
        };
        $emails = $this->$method(export: true)->toArray();
        $this->dispatch('stats-mail-ready', emails: $emails);
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
        $this->authorizeQuery();

        return view('livewire.backend.users.result-stats-server-table', [
            'users' => TableCache::options('stats-users-'.hash('sha256', json_encode(LearningTable::studentIds())), fn () => data()->filters()->users()),
            'topics' => TableCache::options('stats-topics', fn () => Topic::active()->get()->pluck('title', 'id')->all()),
        ]);
    }
}
