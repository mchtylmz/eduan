<?php

namespace App\Livewire\Ai;

use App\Models\UserAiUsage;
use App\Traits\CustomLivewireAlert;
use App\Traits\CustomLivewireTableFilters;
use App\Traits\LivewireTableConfigure;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Lazy;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\AnswerAIVote;
use Rappasoft\LaravelLivewireTables\Views\Columns\WireLinkColumn;

#[Lazy(isolate: true)]
class AnswerVoteTable extends DataTableComponent
{
    use CustomLivewireAlert, LivewireTableConfigure, CustomLivewireTableFilters;

    protected $model = AnswerAIVote::class;

    public int $answerAiId = 0;

    public function mount(int $answerAiId = 0): void
    {
        $this->answerAiId = $answerAiId;

        $this->setSortDesc('created_at');
    }

    public function builder(): Builder
    {
        return AnswerAIVote::with(['user'])
            ->when($this->answerAiId, fn (Builder $builder) => $builder->where('answer_ai_id', $this->answerAiId));
    }

    public function filters(): array
    {
        return [
            $this->usersFilter(
                fn(Builder $builder, array $value) => $builder->whereIn('answer_a_i_votes.user_id', $value)
            ),
            $this->dateFilter('answer_a_i_votes.created_at', __('Değerlendirme Zamanı')),
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
            Column::make(__('Değerlendirme'), "vote")
                ->sortable(),
            Column::make(__('Değerlendirme Zamanı'), "created_at")
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
                ->confirmMessage(__('Değerlendirme silinecektir, işleme devam edilsin mi?'))
                ->action(fn($row) => 'delete("' . $row->id . '")')
                ->attributes(fn($row) => ['class' => 'btn btn-danger btn-sm'])
                ->html(),
        ];
    }

    public function delete(AnswerAIVote $answerAIVote)
    {
        $this->message(__('Değerlendirme başarıyla silindi!'))->success();

        return $answerAIVote->delete();
    }
}
