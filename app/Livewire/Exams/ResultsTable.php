<?php

namespace App\Livewire\Exams;

use App\Enums\RoleTypeEnum;
use App\Enums\YesNoEnum;
use App\Models\Exam;
use App\Models\User;
use App\Traits\CustomLivewireAlert;
use App\Traits\CustomLivewireTableFilters;
use App\Traits\LivewireTableConfigure;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Lazy;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\ExamResult;
use Rappasoft\LaravelLivewireTables\Views\Columns\ComponentColumn;
use Rappasoft\LaravelLivewireTables\Views\Columns\LinkColumn;
use Rappasoft\LaravelLivewireTables\Views\Columns\WireLinkColumn;

#[Lazy(isolate: true)]
class ResultsTable extends DataTableComponent
{
    use CustomLivewireAlert, LivewireTableConfigure, CustomLivewireTableFilters;

    protected $model = ExamResult::class;
    public ?Exam $exam = null;
    public ?User $user = null;

    public ?bool $incorrectCount = false;

    public function mount(?Exam $exam = null, ?User $user = null, ?bool $incorrectCount = false): void
    {
        $this->exam = $exam;
        $this->user = $user;
        $this->incorrectCount = $incorrectCount;

        $this->setSortDesc('updated_at');
        $this->setFilter('completed', YesNoEnum::YES->value);
        $this->setFilter('season_id', activeSeason()->id ?? 0);
    }

    public function builder(): Builder
    {
        $builder = ExamResult::with(['user', 'exam'])->when(
            auth()->user()->can(RoleTypeEnum::TEACHER),
            function ($query) {
                return $query->whereIn('user_id', auth()->user()->students()->select('id'));
            }
        );

        if ($this->incorrectCount) {
            return $builder->whereRaw('question_count = correct_count');
        }

        return $builder
            ->when($this->exam->exists, fn($query) => $query->where('exam_id', $this->exam->id))
            ->when($this->user->exists, fn($query) => $query->where('user_id', $this->user->id));
    }

    public function filters(): array
    {
        $filters = [];

        $filters[] = $this->seasonsFilter('exam_results.season_id');

        if (!$this->user->exists) {
            $filters[] = $this->usersInExamsResultsFilter(
                $this->exam->id ?? 0,
                fn(Builder $builder, array $value) => $builder->whereIn('exam_results.user_id', $value)
            );
        }

        if (!$this->exam->exists && !$this->incorrectCount) {
            $filters[] = $this->examsInResultsFilter(
                fn(Builder $builder, array $value) => $builder->whereIn('exam_results.exam_id', $value)
            );
        }

        $filters[] = $this->completeFilter('exam_results.completed');

        return $filters;
    }

    public function columns(): array
    {
        return [
            Column::make("ID", "id"),
            Column::make(__('Test Adı'), "exam.name")
                ->hideIf($this->exam->exists)
                ->searchable()
                ->sortable(),
            Column::make(__('İsim'), "user.name")
                ->searchable()
                ->sortable(),
            Column::make(__('Soyisim'), "user.surname")
                ->searchable()
                ->sortable(),
            Column::make(__('E-posta Adresi'), "user.email")
                ->collapseOnMobile()
                ->searchable()
                ->sortable(),
            Column::make(__('Soru'), "question_count")
                ->collapseOnMobile()
                ->sortable(),
            Column::make(__('Doğru'), "correct_count")
                ->collapseOnMobile()
                ->sortable(),
            Column::make(__('Yanlış'), "incorrect_count")
                ->collapseOnMobile()
                ->sortable(),
            Column::make(__('Süre'), "time")
                ->format(fn($value) => sprintf(
                    '∼%d %s',
                    intval($value / 60), __('dakika')
                ))
                ->collapseOnMobile()
                ->sortable(),
            ComponentColumn::make(__('Durum'), "completed")
                ->collapseOnMobile()
                ->component('table.status')
                ->attributes(fn($value, $row, Column $column) => [
                    'type' => YesNoEnum::YES->is($value) ? 'success' : 'warning',
                    'label' => YesNoEnum::YES->is($value) ? '<i class="fa-regular fa-circle-check fa-1_5x mx-1"></i>' : '<i class="fa fa-circle-xmark fa-1_5x mx-1"></i>'
                ])
                ->searchable()
                ->sortable(),
            Column::make(__('Tarih'), "updated_at")
                ->collapseOnMobile()
                ->format(fn($value) => $value ? dateFormat($value, 'd/m/Y, H:i') : '-')
                ->sortable()
        ];
    }

    public function appendColumns(): array
    {
        return [
            WireLinkColumn::make(__('Detay'))
                ->collapseOnMobile()
                ->title(fn($row) => sprintf('<i class="fa fa-poll mx-1"></i> %s', __('Detay')))
                ->action(fn($row) => 'showDetail("' . $row->id . '")')
                ->attributes(fn($row) => ['class' => 'btn btn-info btn-sm'])
                ->html(),
        ];
    }

    public function showDetail(ExamResult $examResult): void
    {
        $this->dispatch(
            'showOffcanvas',
            component: [
                'exams.results-user-detail',
                'exams.results-detail-table'
            ],
            data: [
                'title' => __('Sonuç Detayları'),
                'examResultId' => $examResult->id,
                'userId' => $examResult->user_id,
            ]
        );
    }
}
