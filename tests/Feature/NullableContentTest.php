<?php

namespace Tests\Feature;

use App\Enums\StatusEnum;
use App\Enums\VisibilityEnum;
use App\Livewire\Exams\ExamForm;
use App\Livewire\Tests\TestForm;
use App\Models\Exam;
use App\Models\Question;
use App\Models\Test;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

class NullableContentTest extends TestCase
{
    use DatabaseTransactions;

    public function test_existing_test_and_exam_with_null_content_open_as_empty_text(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $test = Test::create([
            'locale' => 'nl', 'name' => 'Nullable exam', 'code' => 'nullable-exam',
            'content' => null, 'duration' => 60, 'status' => StatusEnum::ACTIVE,
            'created_by' => $user->id,
        ]);
        $exam = Exam::create([
            'locale' => 'nl', 'name' => 'Nullable test', 'code' => 'nullable-test',
            'content' => null, 'status' => StatusEnum::ACTIVE, 'visibility' => VisibilityEnum::LOGGED,
        ]);
        Livewire::test(TestForm::class, ['test' => $test])->assertSet('content', '');
        Livewire::test(ExamForm::class, ['exam' => $exam])->assertSet('content', '');
    }

    public function test_question_urls_are_empty_strings_when_image_and_logo_are_missing(): void
    {
        $question = new Question(['attachment' => null, 'solution' => null]);
        $this->assertSame('', $question->attachment_url);
        $this->assertSame('', $question->solution_url);
    }
}
