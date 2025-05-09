<?php

namespace App\Livewire\Tests;

use App\Enums\YesNoEnum;
use App\Models\Test;
use App\Models\TestsResult;
use App\Models\User;
use App\Traits\CustomLivewireAlert;
use App\Traits\CustomLivewireTableFilters;
use App\Traits\LivewireTableConfigure;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Lazy;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Columns\ComponentColumn;
use Rappasoft\LaravelLivewireTables\Views\Columns\LinkColumn;
use Rappasoft\LaravelLivewireTables\Views\Columns\WireLinkColumn;

#[Lazy(isolate: true)]
class ResultsTable extends DataTableComponent
{
    use CustomLivewireAlert, LivewireTableConfigure, CustomLivewireTableFilters;

    protected $model = TestsResult::class;
    public ?Test $test = null;
    public ?User $user = null;

    public ?bool $incorrectCount = false;

    public function mount(?Test $test = null, ?User $user = null, ?bool $incorrectCount = false): void
    {
        $this->test = $test;
        $this->user = $user;
        $this->incorrectCount = $incorrectCount;

        $this->setSortDesc('duration');
    }

    public function builder(): Builder
    {
        if ($this->incorrectCount) {
            return TestsResult::with(['user', 'test'])->whereColumn('question_count','correct_count');
        }

        return TestsResult::with(['user', 'test'])
            ->when($this->test->exists, fn($query) => $query->where('test_id', $this->test->id))
            ->when($this->user->exists, fn($query) => $query->where('user_id', $this->user->id));
    }

    public function filters(): array
    {
        $filters = [];

        if (!$this->user->exists) {
            $filters[] = $this->usersInTestsResultsFilter(
                $this->test->id ?? 0,
                fn(Builder $builder, array $value) => $builder->whereIn('tests_results.user_id', $value)
            );
        }

        if (!$this->test->exists && !$this->incorrectCount) {
            $filters[] = $this->testsInResultsFilter(
                fn(Builder $builder, array $value) => $builder->whereIn('tests_results.test_id', $value)
            );
        }

        $filters[] = $this->completeFilter('tests_results.completed');

        return $filters;
    }

    public function columns(): array
    {
        return [
            Column::make("ID", "id")->hideIf(true),
            Column::make("Geçme", "passing_score")->hideIf(true),
            Column::make(__('Test Adı'), "Test.name")
                ->hideIf($this->test->exists)
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
            ComponentColumn::make(__('Puan'), "point")
                ->collapseOnMobile()
                ->component('table.status')
                ->attributes(fn($value, $row, Column $column) => [
                    'type' => $value >= $row->passing_score ? 'success' : 'warning',
                    'label' => sprintf(
                        '%d (%s)',
                        $value,
                        $value >= $row->passing_score ? __('Geçti') : __('Kaldı')
                    )
                ])
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
            Column::make(__('Süre'), "duration")
                ->format(fn($value) => formatSecondToTime(secondToTime($value), hideSeconds: true))
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
            Column::make(__('Tamamlanma'), "completed_at")
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

    public function showDetail(TestsResult $testResult): void
    {
        $this->dispatch(
            'showOffcanvas',
            component: [
                'tests.results-user-detail',
                'tests.results-detail-table'
            ],
            data: [
                'title' => __('Sonuç Detayları'),
                'testsResultId' => $testResult->id,
                'userId' => $testResult->user_id,
            ]
        );
    }
}
