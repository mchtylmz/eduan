<?php

namespace App\Livewire\Users;

use App\Models\ExamResult;
use App\Models\ExamResultDetail;
use App\Models\Question;
use App\Models\TestsResult;
use App\Models\TestsResultDetail;
use App\Models\User;
use App\Traits\CustomLivewireAlert;
use App\Traits\CustomLivewireTableFilters;
use App\Traits\LivewireTableConfigure;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Lazy;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\Topic;
use Rappasoft\LaravelLivewireTables\Views\Columns\ComponentColumn;
use Rappasoft\LaravelLivewireTables\Views\Columns\CountColumn;

#[Lazy(isolate: true)]
class TopicsNotResultsTable extends DataTableComponent
{
    use CustomLivewireAlert, LivewireTableConfigure, CustomLivewireTableFilters;

    protected $model = Topic::class;

    public int $defaultPerPage = 25;
    protected array $notInTopicIds = [];

    public User $user;


    public function mount(User $user): void
    {
        $this->user = $user;
        $this->resetPage($this->getComputedPageName());
        $this->clearSorts();

        $examTopicIds = ExamResultDetail::whereIn(
            'exam_result_id',
            ExamResult::where('user_id', $this->user->id)->pluck('id')->toArray()
        )->pluck('topic_id')->toArray();

        $testTopicIds = TestsResultDetail::whereIn(
            'tests_result_id',
            TestsResult::where('user_id', $this->user->id)->pluck('id')->toArray()
        )->pluck('topic_id')->toArray();

        $this->notInTopicIds = array_merge($examTopicIds, $testTopicIds);
    }

    public function builder(): Builder
    {
        return Topic::whereNotIn('id', $this->notInTopicIds);
    }

    public function columns(): array
    {
        return [
            Column::make('id', 'id')
                ->hideIf(true),
            Column::make(__('Kodu'), "code")
                ->searchable()
                ->sortable(),
            Column::make(__('Konu'), "title")
                ->searchable()
                ->sortable(),
            ComponentColumn::make(__('Durum'), "status")
                ->collapseOnMobile()
                ->component('table.status')
                ->attributes(fn($value, $row, Column $column) => [
                    'type' => $value->class(),
                    'label' => $value->name(),
                ])
                ->searchable()
                ->sortable(),
        ];
    }
}
