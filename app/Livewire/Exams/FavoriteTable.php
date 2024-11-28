<?php

namespace App\Livewire\Exams;

use App\Models\Exam;
use App\Models\Question;
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

#[Lazy(isolate: true)]
class FavoriteTable extends DataTableComponent
{
    use CustomLivewireAlert, LivewireTableConfigure, CustomLivewireTableFilters;

    protected $model = Exam::class;

    public int $defaultPerPage = 5;

    public int $examId;

    public function mount(int $examId): void
    {
        $this->examId = $examId;
        $this->resetPage($this->getComputedPageName());
        $this->clearSorts();

    }

    public function builder(): Builder
    {
        return Exam::find($this->examId)?->favorites()->getQuery();
    }

    public function columns(): array
    {
        return [
            Column::make(__('Kullanıcı Adı'), "username")
                ->searchable()
                ->sortable(),
            Column::make(__('İsim'), "name")
                ->searchable()
                ->collapseOnMobile()
                ->sortable(),
            Column::make(__('Soyisim'), "surname")
                ->searchable()
                ->collapseOnMobile()
                ->sortable(),
            Column::make(__('Son Giriş'), "last_login_at")
                ->collapseOnMobile()
                ->format(fn($value) => $value ? dateFormat($value) : '-')
                ->sortable()
        ];
    }
}
