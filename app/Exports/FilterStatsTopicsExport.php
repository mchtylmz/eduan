<?php

namespace App\Exports;

use App\Models\ExamResult;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class FilterStatsTopicsExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    public Collection $collection;

    public function __construct(?Collection $collection)
    {
        $this->collection = $collection;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return $this->collection;
    }

    public function headings(): array
    {
        return [
            __('İsim'),
            __('Soyisim'),
            __('Sınav Adı'),
            __('Konu Adı'),
            __('Toplam Soru'),
            __('Doğru Yanıt'),
            __('Yanlış Yanıt'),
            __('Başarı Yüzdesi'),
        ];
    }

    public function map($row): array
    {
        $topic_title = '';
        if (str($row->topic_title)->isJson()) {
            $topic_title = json_decode($row->topic_title, true)[app()->getLocale()] ?? '-' ;
        }

        return [
            $row->name,
            $row->surname,
            $row->exam_name,
            $topic_title,
            intval($row->total_questions ?: 0),
            intval($row->count_correct ?: 0),
            intval($row->count_incorrect ?: 0),
            floatval($row->success_rate ?: 0),
        ];
    }
}
