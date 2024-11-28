<?php

namespace App\Livewire\Exams;

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

    public function mount(?Exam $exam = null, ?User $user = null): void
    {
        $this->exam = $exam;
        $this->user = $user;

        $this->setSortAsc('time');
    }

    public function builder(): Builder
    {
        return ExamResult::with(['user', 'exam'])
            ->when($this->exam->exists, fn($query) => $query->where('exam_id', $this->exam->id))
            ->when($this->user->exists, fn($query) => $query->where('user_id', $this->user->id));
    }

    public function filters(): array
    {
        $filters = [];

        if (!$this->user->exists) {
            $filters[] = $this->usersInTestResultsFilter(
                $this->exam->id ?? 0,
                fn(Builder $builder, array $value) => $builder->whereIn('exam_results.user_id', $value)
            );
        }

        if (!$this->exam->exists) {
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
            Column::make(__('E-posta Adresi'), "user.email")
                ->searchable()
                ->sortable(),
            Column::make(__('İsim'), "user.name")
                ->searchable()
                ->sortable(),
            Column::make(__('Soyisim'), "user.surname")
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
                    '∼%d %s (%d %s)',
                    intval($value / 60), __('dakika'),
                    $value, __('sn'),
                ))
                ->collapseOnMobile()
                ->sortable(),
            ComponentColumn::make(__('Durum'), "completed")
                ->collapseOnMobile()
                ->component('table.status')
                ->attributes(fn($value, $row, Column $column) => [
                    'type' => YesNoEnum::YES->is($value) ? 'success' : 'warning',
                    'label' => YesNoEnum::YES->is($value) ? __('Tamamlandı') : __('Tamamlanmadı')
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
