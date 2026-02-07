<?php

namespace App\Livewire\Season;

use App\Enums\StatusEnum;
use App\Jobs\UpdateSeasonIdForResults;
use App\Models\Season;
use App\Traits\CustomLivewireAlert;
use Illuminate\Validation\Rules\Enum;
use Livewire\Component;

class SeasonForm extends Component
{
    use CustomLivewireAlert;

    public string $locale;

    public array $seasons = [];

    public function mount(): void
    {
        $this->locale = app()->getLocale();

        $this->seasons = Season::latest()->get()->toArray();
    }

    public function create(): void
    {
        array_unshift($this->seasons, [
            'id' => 0,
            'name' => '',
            'start_date' => '',
            'end_date' => '',
            'status' => 'active'
        ]);
    }

    public function rules(): array
    {
        return [
            'seasons.*.name' => 'required',
            'seasons.*.start_date' => 'required|date_format:Y-m-d',
            'seasons.*.end_date' => 'required|date_format:Y-m-d|after:seasons.*.start_date',
            'seasons.*.status' => [
                'required',
                new Enum(StatusEnum::class)
            ]
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'seasons.*.name' => __('Dönem Adı'),
            'seasons.*.start_date' => __('Başlangıç Tarihi'),
            'seasons.*.end_date' => __('Bitiş Tarihi'),
            'seasons.*.status' => __('Durum')
        ];
    }

    public function save()
    {
        $this->validate();

        $duplicatesStartDate = collect($this->seasons)
            ->groupBy('start_date')
            ->filter(fn ($items) => $items->count() > 1)
            ->keys();
        if ($duplicatesStartDate->isNotEmpty()) {
            $this->message(__('Aynı başlangıç tarihi birden fazla kez kullanılamaz'))->error();
            return false;
        }

        $duplicatesEndDate = collect($this->seasons)
            ->groupBy('end_date')
            ->filter(fn ($items) => $items->count() > 1)
            ->keys();
        if ($duplicatesEndDate->isNotEmpty()) {
            $this->message(__('Aynı bitiş tarihi birden fazla kez kullanılamaz'))->error();
            return false;
        }

        foreach ($this->seasons as $season) {
            $data = [
                'name' => $season['name'],
                'start_date' => $season['start_date'],
                'end_date' => $season['end_date'],
                'status' => $season['status']
            ];

            if (!empty($season['id'])) {
                Season::where('id', $season['id'])->update($data);
            } else {
                Season::create($data);
            }
        }

        $this->mount();

        UpdateSeasonIdForResults::dispatch();

        $this->message(__('Bilgileriniz başarıyla güncellendi'))->success();
        return true;
    }

    public function delete(int $key): void
    {
        if (!empty($this->seasons[$key]['id'])) {
            Season::where('id', $this->seasons[$key]['id'])->delete();
        }

        unset($this->seasons[$key]);

        $this->message(__('Başarıyla kaldırıldı'))->success();
    }

    public function render()
    {
        return view('livewire.backend.season.season-form');
    }
}
