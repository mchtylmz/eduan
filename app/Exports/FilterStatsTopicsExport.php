<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class FilterStatsTopicsExport extends TextValueBinder implements FromCollection, ShouldAutoSize, WithCustomValueBinder, WithHeadings, WithMapping
{
    public Collection $collection;

    public function __construct(?Collection $collection)
    {
        $this->collection = $collection;
    }

    public function collection(): Collection
    {
        return $this->collection;
    }

    public function headings(): array
    {
        return [
            __('İsim'),
            __('Soyisim'),
            __('E-posta Adresi'),
            __('Konu Adı'),
            __('Toplam Soru'),
            __('Doğru Yanıt'),
            __('Yanlış Yanıt'),
            __('Başarı Yüzdesi'),
        ];
    }

    public function map($row): array
    {
        $topic_title = str($row->topic_title)->isJson() ? getJsonLocaleValue($row->topic_title) : $row->topic_title;

        return [
            $row->name,
            $row->surname,
            $row->email,
            $topic_title,
            intval($row->total_questions ?: 0),
            intval($row->count_correct ?: 0),
            intval($row->count_incorrect ?: 0),
            floatval($row->success_rate ?: 0),
        ];
    }
}
