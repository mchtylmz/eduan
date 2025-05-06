<?php

namespace App\Exports;

use App\Models\ExamResult;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class FilterStatsTestsExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
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
            __('E-posta Adresi'),
            __('Test Adı'),
            __('Puan'),
            __('Toplam Soru'),
            __('Doğru Yanıt'),
            __('Yanlış Yanıt'),
            __('Başarı Yüzdesi'),
        ];
    }

    public function map($row): array
    {
        return [
            $row->name,
            $row->surname,
            $row->email,
            $row->test_name,
            intval($row->point ?: 0),
            intval($row->total_questions ?: 0),
            intval($row->count_correct ?: 0),
            intval($row->count_incorrect ?: 0),
            floatval($row->success_rate ?: 0),
        ];
    }
}
