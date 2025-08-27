<?php

namespace App\Traits;

use App\Enums\StatusEnum;
use App\Enums\VisibilityEnum;
use App\Enums\YesNoEnum;
use App\Models\Lesson;
use App\Models\Role;
use App\Models\Topic;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\Views\Filter;
use Rappasoft\LaravelLivewireTables\Views\Filters\DateRangeFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\MultiSelectDropdownFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\MultiSelectFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

trait CustomLivewireTableFilters
{
    protected function activeLessonFilter($callback = null): \Rappasoft\LaravelLivewireTables\Views\Filter
    {
        return MultiSelectDropdownFilter::make(__('Dersler'), 'lessons')
            ->options(data()->filters()->activeLessons())
            ->filter(is_callable($callback) ? $callback : null);
    }

    protected function activeLessonInResultFilter(int $examResultId, $callback = null): \Rappasoft\LaravelLivewireTables\Views\Filter
    {
        return MultiSelectDropdownFilter::make(__('Dersler'), 'lessons')
            ->options(data()->filters()->activeLessonsInResult($examResultId))
            ->filter(is_callable($callback) ? $callback : null);
    }

    protected function activeTopicFilter($callback = null): \Rappasoft\LaravelLivewireTables\Views\Filter
    {
        return MultiSelectDropdownFilter::make(__('Konular'), 'topics')
            ->options(data()->filters()->activeTopics())
            ->filter(is_callable($callback) ? $callback : null);
    }

    protected function activeTopicInResultFilter(int $examResultId, $callback = null): \Rappasoft\LaravelLivewireTables\Views\Filter
    {
        return MultiSelectDropdownFilter::make(__('Konular'), 'topics')
            ->options(data()->filters()->activeTopicsInResults($examResultId))
            ->filter(is_callable($callback) ? $callback : null);
    }

    protected function usersFilter($callback = null): \Rappasoft\LaravelLivewireTables\Views\Filter
    {
        return MultiSelectDropdownFilter::make(__('Kullanıcılar'), 'users')
            ->options(data()->filters()->users())
            ->filter(is_callable($callback) ? $callback : null);
    }

    protected function usersInExamsResultsFilter(int $examId = 0, $callback = null): \Rappasoft\LaravelLivewireTables\Views\Filter
    {
        return MultiSelectDropdownFilter::make(__('Kullanıcılar'), 'users')
            ->options(data()->filters()->usersInExamsResults($examId))
            ->filter(is_callable($callback) ? $callback : null);
    }

    protected function examsInResultsFilter($callback = null): \Rappasoft\LaravelLivewireTables\Views\Filter|null
    {
        return MultiSelectDropdownFilter::make(__('Testler'), 'exams')
            ->options(data()->filters()->examsInResults())
            ->filter(is_callable($callback) ? $callback : null);
    }

    protected function usersInTestsResultsFilter(int $examId = 0, $callback = null): \Rappasoft\LaravelLivewireTables\Views\Filter
    {
        return MultiSelectDropdownFilter::make(__('Kullanıcılar'), 'users')
            ->options(data()->filters()->usersInTestsResults($examId))
            ->filter(is_callable($callback) ? $callback : null);
    }

    protected function testsInResultsFilter($callback = null): \Rappasoft\LaravelLivewireTables\Views\Filter|null
    {
        return MultiSelectDropdownFilter::make(__('Testler'), 'tests')
            ->options(data()->filters()->testsInResults())
            ->filter(is_callable($callback) ? $callback : null);
    }

    protected function examsFilter($callback = null): \Rappasoft\LaravelLivewireTables\Views\Filter|null
    {
        return MultiSelectDropdownFilter::make(__('Testler'), 'exams')
            ->options(data()->filters()->exams())
            ->filter(is_callable($callback) ? $callback : null);
    }

    protected function languageFilter(string $field = 'name'): \Rappasoft\LaravelLivewireTables\Views\Filter
    {
        return SelectFilter::make(__('Dil'), 'locale')
            ->options(cache()->remember(
                'filter_languages',
                60 * 60 * 24 * 30,
                fn() => data()->languages()->keyBy('code')->map(fn($item) => $item->name)->toArray()
            ))
            ->filter(function (Builder $builder, string $value) use($field) {
                $builder->whereLocale($field, $value);
            });
    }

    protected function languageColumnFilter(string $field = 'locale'): \Rappasoft\LaravelLivewireTables\Views\Filter
    {
        return SelectFilter::make(__('Dil'), 'locale')
            ->options(cache()->remember(
                'filter_languages',
                60 * 60 * 24 * 30,
                fn() => data()->languages()->keyBy('code')->map(fn($item) => $item->name)->toArray()
            ))
            ->filter(function (Builder $builder, string $value) use($field) {
                $builder->where($field, $value);
            });
    }

    protected function roleFilter(): Filter
    {
        return MultiSelectFilter::make(__('Yetkiler'))
            ->options(Role::orderBy('name')->get()->pluck('name', 'name')->toArray())
            ->filter(fn(Builder $builder, array $value) => $builder->role($value));
    }

    protected function lastLoginDateFilter(string $field): Filter
    {
        return DateRangeFilter::make(__('Son Giriş'))
            ->config([
                'placeholder' => __('Tarih Seçiniz'),
                'locale' => app()->getLocale(),
            ])
            ->filter(function(Builder $builder, array $dateRange) use($field) {
                return $builder->whereBetween(
                    $field, [$dateRange['minDate'], $dateRange['maxDate']]
                );
            });
    }

    protected function registrationDateFilter(string $field): Filter
    {
        return DateRangeFilter::make(__('Kayıt Tarihi'))
            ->config([
                'placeholder' => __('Tarih Seçiniz'),
                'locale' => app()->getLocale(),
            ])
            ->filter(function(Builder $builder, array $dateRange) use($field) {
                return $builder->whereBetween($field, [$dateRange['minDate'], $dateRange['maxDate']]);
            });
    }

    protected function usageDateFilter(string $field): Filter
    {
        return DateRangeFilter::make(__('Kullanım Tarihi'))
            ->config([
                'placeholder' => __('Tarih Seçiniz'),
                'locale' => app()->getLocale(),
            ])
            ->filter(function(Builder $builder, array $dateRange) use($field) {
                return $builder->whereBetween($field, [$dateRange['minDate'], $dateRange['maxDate']]);
            });
    }

    protected function dateFilter(string $field, string $label = ''): Filter
    {
        return DateRangeFilter::make($label)
            ->config([
                'placeholder' => __('Tarih Seçiniz'),
                'locale' => app()->getLocale(),
            ])
            ->filter(function(Builder $builder, array $dateRange) use($field) {
                return $builder->whereBetween($field, [$dateRange['minDate'], $dateRange['maxDate']]);
            });
    }

    protected function visibilityFilter(string $field = 'visibility'): \Rappasoft\LaravelLivewireTables\Views\Filter
    {
        return SelectFilter::make(__('Görünüm'), 'visibility')
            ->options([
                '' => __('Tümü'),
                ...VisibilityEnum::options()
            ])
            ->filter(function (Builder $builder, string $value) use($field) {
                $builder->where($field, $value);
            });
    }

    protected function completeFilter(string $field = 'completed'): \Rappasoft\LaravelLivewireTables\Views\Filter
    {
        return SelectFilter::make(__('Durum'), 'completed')
            ->options([
                '' => __('Tümü'),
                YesNoEnum::YES->value => __('Tamamlandı'),
                YesNoEnum::NO->value => __('Tamamlanmadı'),
            ])
            ->filter(function (Builder $builder, string $value) use($field) {
                $builder->where($field, $value);
            });
    }

    protected function statusFilter(string $field = 'status'): \Rappasoft\LaravelLivewireTables\Views\Filter
    {
        return SelectFilter::make(__('Durum'), 'status')
            ->options([
                '' => __('Tümü'),
                ...StatusEnum::options()
            ])
            ->filter(function (Builder $builder, string $value) use($field) {
                $builder->where($field, $value);
            });
    }

    protected function emailVerifiedFilter(string $field = 'status'): \Rappasoft\LaravelLivewireTables\Views\Filter
    {
        return MultiSelectFilter::make(__('E-posta Onay'))
            ->options([
                '1' => __('Onaylı'),
                '0' => __('Onaylı Değil'),
            ])
            ->filter(fn(Builder $builder, array $value) => $builder->where($field, $value));
    }

    protected function hasReadFilter(string $field = 'has_read'): \Rappasoft\LaravelLivewireTables\Views\Filter
    {
        return MultiSelectFilter::make(__('Durum'))
            ->options([
                '1' => __('Okundu'),
                '0' => __('Okunmamış'),
            ])
            ->filter(fn(Builder $builder, array $value) => $builder->where($field, $value));
    }
}
