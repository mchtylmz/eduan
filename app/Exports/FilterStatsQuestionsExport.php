<?php

namespace App\Exports;

use App\Models\ExamResult;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class FilterStatsQuestionsExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
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
            __('Kullanıcı Adı'),
            __('İsim'),
            __('Soyisim'),
            __('Toplam Soru'),
            __('Doğru Yanıt'),
            __('Yanlış Yanıt'),
            __('Başarı Yüzdesi'),
            __('Son Güncelleme')
        ];
    }

    public function map($row): array
    {
        return [
            $row->username,
            $row->name,
            $row->surname,
            intval($row->question_count ?: 0),
            intval($row->correct_count ?: 0),
            intval($row->incorrect_count ?: 0),
            intval($row->success_rate ?: 0),
            $row->updated_at,
        ];
    }
}
