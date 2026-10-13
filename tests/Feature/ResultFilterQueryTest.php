<?php

namespace Tests\Feature;

use App\Enums\StatusEnum;
use App\Helpers\FilterHelper;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\Test;
use App\Models\TestsResult;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ResultFilterQueryTest extends TestCase
{
    use DatabaseTransactions;

    public static function filters(): array
    {
        return [
            'practice' => [Exam::class, ExamResult::class, 'exam_id', 'examsInResults'],
            'timed' => [Test::class, TestsResult::class, 'test_id', 'testsInResults'],
        ];
    }

    #[DataProvider('filters')]
    public function test_result_options_preserve_attempt_selection_and_use_one_cached_query(string $model, string $resultModel, string $foreignKey, string $method): void
    {
        $user = User::factory()->create();
        $items = [];
        foreach (['answered', 'passive', 'unanswered', 'deleted'] as $label) {
            $items[$label] = $model::create([
                'locale' => 'nl', 'code' => 'query-'.$label, 'name' => 'Filter '.$label,
                'created_by' => $user->id,
                'status' => $label === 'passive' ? StatusEnum::PASSIVE : StatusEnum::ACTIVE,
            ]);
        }
        foreach (['answered', 'answered', 'passive', 'deleted'] as $index => $label) {
            $resultModel::create([
                'user_id' => $user->id, $foreignKey => $items[$label]->id,
                'completed' => $index === 1 ? 1 : 0,
            ]);
        }
        $items['deleted']->delete();
        Cache::forget('filter_'.$method);
        $helper = new FilterHelper;
        DB::enableQueryLog();

        try {
            DB::flushQueryLog();
            $options = $helper->$method();
            $this->assertCount(1, DB::getQueryLog());
            $this->assertSame('(query-answered) Filter answered', $options[$items['answered']->id]);
            $this->assertSame('(query-passive) Filter passive', $options[$items['passive']->id]);
            $this->assertArrayNotHasKey($items['unanswered']->id, $options);
            $this->assertArrayNotHasKey($items['deleted']->id, $options);
            $this->assertSame(
                [$items['passive']->id, $items['answered']->id],
                array_keys(array_intersect_key($options, [$items['answered']->id => true, $items['passive']->id => true]))
            );

            DB::flushQueryLog();
            $this->assertSame($options, $helper->$method());
            $this->assertCount(0, DB::getQueryLog());
        } finally {
            DB::disableQueryLog();
            DB::flushQueryLog();
        }
    }
}
