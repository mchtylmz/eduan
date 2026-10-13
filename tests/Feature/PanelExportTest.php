<?php

namespace Tests\Feature;

use App\Exports\FilterStatsExamsExport;
use App\Exports\FilterStatsQuestionsExport;
use App\Exports\FilterStatsTestsExport;
use App\Exports\FilterStatsTopicsExport;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PanelExportTest extends TestCase
{
    public static function exports(): array
    {
        return [
            'practice' => [FilterStatsExamsExport::class, 8, 4, 20, 7],
            'timed' => [FilterStatsTestsExport::class, 9, 4, 30, 8],
            'topic' => [FilterStatsTopicsExport::class, 8, 3, 'Algebra', 7],
            'question' => [FilterStatsQuestionsExport::class, 8, 3, 20, 6],
        ];
    }

    #[DataProvider('exports')]
    public function test_excel_is_a_readable_workbook_with_headers_and_correct_values(string $class, int $columns, int $position, int|string $expected, int $successPosition): void
    {
        app()->setLocale('nl');
        $row = (object) [
            'name' => 'Ada', 'surname' => 'Lovelace', 'email' => 'ada@example.com', 'username' => 'ada@example.com',
            'exam_name' => 'Practice', 'test_name' => 'Timed', 'topic_title' => '{"nl":"Algebra"}',
            'point' => 30, 'total_questions' => 20, 'count_correct' => 12, 'count_incorrect' => 8,
            'success_rate' => 60, 'question_count' => 20, 'correct_count' => 12, 'incorrect_count' => 8,
            'updated_at' => '2026-10-05 10:00:00',
        ];
        $bytes = Excel::raw(new $class(collect([$row])), ExcelFormat::XLSX);
        $this->assertStringStartsWith('PK', $bytes);
        $path = tempnam(sys_get_temp_dir(), 'eduan-export-');
        try {
            file_put_contents($path, $bytes);
            $workbook = IOFactory::load($path);
            $rows = $workbook->getActiveSheet()->toArray();
            $this->assertCount(2, $rows);
            $this->assertCount($columns, $rows[0]);
            $this->assertNotEmpty($rows[0][0]);
            $this->assertEquals($expected, $rows[1][$position]);
            $this->assertEquals(60, $rows[1][$successPosition]);
            $workbook->disconnectWorksheets();
        } finally {
            unlink($path);
        }
    }
}
