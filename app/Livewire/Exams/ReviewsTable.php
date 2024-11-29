<?php

namespace App\Livewire\Exams;

use App\Enums\YesNoEnum;
use App\Models\Contact;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\User;
use App\Traits\CustomLivewireAlert;
use App\Traits\CustomLivewireTableFilters;
use App\Traits\LivewireTableConfigure;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Lazy;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\ExamReview;
use Rappasoft\LaravelLivewireTables\Views\Columns\ComponentColumn;
use Rappasoft\LaravelLivewireTables\Views\Columns\LinkColumn;
use Rappasoft\LaravelLivewireTables\Views\Columns\WireLinkColumn;
use Rappasoft\LaravelLivewireTables\Views\Filters\MultiSelectFilter;

#[Lazy(isolate: true)]
class ReviewsTable extends DataTableComponent
{
    use LivewireTableConfigure, CustomLivewireTableFilters, CustomLivewireAlert;

    protected $model = ExamReview::class;

    public ?Exam $exam = null;
    public ?User $user = null;

    public function mount(?Exam $exam = null, ?User $user = null): void
    {
        $this->exam = $exam;
        $this->user = $user;

        $this->setSortDesc('exam_reviews.created_at');
    }

    public function builder(): Builder
    {
        return ExamReview::with(['user', 'exam'])
            ->when($this->exam->exists, fn($query) => $query->where('exam_id', $this->exam->id))
            ->when($this->user->exists, fn($query) => $query->where('user_id', $this->user->id));
    }

    public function filters(): array
    {
        $filters = [];

        if (!$this->user->exists) {
            $filters[] = $this->usersFilter(
                fn(Builder $builder, array $value) => $builder->whereIn('exam_reviews.user_id', $value)
            );
        }

        if (!$this->exam->exists) {
            $filters[] = $this->examsFilter(
                fn(Builder $builder, array $value) => $builder->whereIn('exam_reviews.exam_id', $value)
            );
        }

        $filters[] = $this->hasReadFilter();

        return $filters;
    }

    public function columns(): array
    {
        return [
            Column::make("Id", "id")
                ->hideIf(true),
            Column::make(__('Test Adı'), "exam.name")
                ->searchable()
                ->sortable(),
            Column::make(__('İsim'), "user.name")
                ->searchable()
                ->sortable(),
            Column::make(__('Soyisim'), "user.surname")
                ->searchable()
                ->sortable(),
            Column::make(__('Değerlendirme'), "comment")
                ->collapseOnMobile()
                ->searchable()
                ->format(fn($value) => str($value)->limit(54))
                ->sortable(),
            ComponentColumn::make(__('Görünüm'), "visibility")
                ->collapseOnMobile()
                ->component('table.status')
                ->attributes(fn($value, $row, Column $column) => [
                    'type' => 'dark',
                    'label' => $value->name()
                ])
                ->searchable()
                ->sortable(),
            ComponentColumn::make(__('Durum'), "has_read")
                ->collapseOnMobile()
                ->component('table.status')
                ->attributes(fn($value, $row, Column $column) => [
                    'type' => YesNoEnum::YES->is($value) ? 'success' : 'warning',
                    'label' => YesNoEnum::YES->is($value) ? __('Okundu') : __('Okunmamış'),
                ])
                ->searchable()
                ->sortable(),
            Column::make(__('Kayıt Tarihi'), "created_at")
                ->collapseOnMobile()
                ->format(fn($value) => dateFormat($value, 'd/m/Y, H:i'))
                ->sortable(),
        ];
    }

    public function appendColumns(): array
    {
        return [
            LinkColumn::make(__('Detay'))
                ->collapseOnMobile()
                ->title(fn($row) => sprintf('<i class="fa fa-comment mx-1"></i> %s', __('Detay')))
                ->location(fn($row) => route('admin.exams.reviewDetail', $row->id))
                ->attributes(fn($row) => ['class' => 'btn btn-info btn-sm'])
                ->html(),

            WireLinkColumn::make(__('Sil'))
                ->collapseOnMobile()
                ->hideIf(auth()?->user()->cannot('exams-reviews:delete'))
                ->title(fn($row) => sprintf('<i class="fa fa-trash-alt mx-1"></i> %s', __('Sil')))
                ->confirmMessage(__('Değerlendirme silinecektir, işleme devam edilsin mi?'))
                ->action(fn($row) => 'delete("' . $row->id . '")')
                ->attributes(fn($row) => ['class' => 'btn btn-danger btn-sm'])
                ->html(),
        ];
    }

    public function delete(ExamReview $review)
    {
        if (auth()->user()->cannot('exams-reviews:delete')) {
            $this->message(__('Değerlendirme silinemedi, yetkiniz bulunmuyor!'))->error();
            return false;
        }

        $this->message(__('Değerlendirme silindi!'))->success();

        return $review->delete();
    }
}
