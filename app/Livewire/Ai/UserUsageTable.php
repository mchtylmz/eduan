<?php

namespace App\Livewire\Ai;

use App\Traits\CustomLivewireAlert;
use App\Traits\CustomLivewireTableFilters;
use App\Traits\LivewireTableConfigure;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Lazy;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\UserAiUsage;
use Rappasoft\LaravelLivewireTables\Views\Columns\WireLinkColumn;

#[Lazy(isolate: true)]
class UserUsageTable extends DataTableComponent
{
    use CustomLivewireAlert, LivewireTableConfigure, CustomLivewireTableFilters;

    protected $model = UserAiUsage::class;

    public int $answerAiId = 0;

    public function mount(int $answerAiId = 0): void
    {
        $this->answerAiId = $answerAiId;

        $this->setSortDesc('id');
    }

    public function builder(): Builder
    {
        return UserAiUsage::with(['user'])
            ->when($this->answerAiId, fn (Builder $builder) => $builder->where('answer_ai_id', $this->answerAiId));
    }

    public function filters(): array
    {
        return [
            $this->usageDateFilter('user_ai_usages.usage_date'),
        ];
    }

    public function columns(): array
    {
        return [
            Column::make("Id", "id")
                ->hideIf(true),
            Column::make(__('Kullanıcı adı'), "user.username")
                ->searchable()
                ->sortable(),
            Column::make(__('İsim'), "user.name")
                ->searchable()
                ->sortable(),
            Column::make(__('Soyisim'), "user.surname")
                ->searchable()
                ->sortable(),
            Column::make(__('Kullanım'), "usage")
                ->sortable(),
            Column::make(__('Kalan'), "remaining")
                ->sortable(),
            Column::make(__('Kullanım Zamanı'), "usage_date")
                ->collapseOnMobile()
                ->format(fn($value) => $value ? dateFormat($value, 'd/m/Y H:i') : '-')
                ->sortable()
        ];
    }

    public function appendColumns(): array
    {
        return [
            WireLinkColumn::make(__('Sil'))
                ->collapseOnMobile()
                ->title(fn($row) => sprintf('<i class="fa fa-trash-alt mx-1"></i> %s', __('Sil')))
                ->confirmMessage(__('Kullanım silinecektir, işleme devam edilsin mi?'))
                ->action(fn($row) => 'delete("' . $row->id . '")')
                ->attributes(fn($row) => ['class' => 'btn btn-danger btn-sm'])
                ->html(),
        ];
    }

    public function delete(UserAiUsage $userAiUsage)
    {
        $this->message(__('Kullanım başarıyla silindi!'))->success();

        return $userAiUsage->delete();
    }
}
