<?php

namespace App\Livewire\Frontend\Tests;

use App\Enums\YesNoEnum;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\ExamResultDetail;
use App\Models\Question;
use App\Traits\CustomLivewireAlert;
use Barryvdh\Debugbar\Facades\Debugbar;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Lazy;
use Livewire\Component;
use Livewire\WithoutUrlPagination;
use Livewire\WithPagination;

#[Lazy(isolate: true)]
class SolveForm extends Component
{
    use WithPagination, WithoutUrlPagination, CustomLivewireAlert;

    public Exam $exam;
    public Collection $questions;
    public Collection $answers;

    public int $questionIndex;
    public int $questionsCount;

    public array $results = [];

    public function mount(Exam $exam): void
    {
        $this->exam = $exam;
        $this->questions = $exam->questions()->active()->with('answers')->get();

        $this->questionIndex = 1;
        $this->questionsCount = count($this->questions);

        $this->initializeResults();
    }

    private function initializeResults(): void
    {
        foreach ($this->questions as $index => $question) {
            $this->results[$index + 1] = [
                'answers' => $question->answers,
                'correct' => $question->answers->firstWhere(fn($answer) => YesNoEnum::YES->is($answer->correct))?->id ?? 0,
                'question' => $question,
                'userAnswer' => null,
                'isCorrect' => null,
                'startTime' => now(),
                'endTime' => now(),
                'diffTime' => 0
            ];
        }
    }

    public function question(): Question
    {
        $this->results[$this->questionIndex]['startTime'] = now();

        return $this->results[$this->questionIndex]['question'] ?? $this->questions[$this->questionIndex - 1];
    }

    public function previousPage(): void
    {
        $this->questionIndex--;
        $this->dispatch('scrollToQuestion');
    }

    public function nextPage(): bool
    {
        if (is_null($this->results[$this->questionIndex]['userAnswer'])) {
            $this->message(__('Sonraki soruya geçmek için soru cevaplanmalıdır!'))
                ->toast(false, 'center')
                ->error();
            return false;
        }

        $this->questionIndex++;
        $this->dispatch('scrollToQuestion');
        return true;
    }

    public function updated($field, $value): void
    {
        if (str_ends_with($field, '.userAnswer')) {
            $this->results[$this->questionIndex]['userAnswer'] = (int)$value;

            $this->evaluateAnswer((int)$value);
            $this->diffTime();
            $this->saveAnswer();

            $this->dispatch('scrollToSolution');
        }
    }

    public function evaluateAnswer(int $answerId): ?bool
    {
        if (!empty($this->results[$this->questionIndex]['userAnswer'])) {
            $isCorrect = $answerId == $this->results[$this->questionIndex]['correct'];
        }

        return $this->results[$this->questionIndex]['isCorrect'] = $isCorrect ?? false;
    }

    public function isDisabled(): bool
    {
        return !empty($this->results[$this->questionIndex]['userAnswer']);
    }

    public function diffTime(): void
    {
        $this->results[$this->questionIndex]['endTime'] = now();
        $this->results[$this->questionIndex]['diffTime'] =
            $this->results[$this->questionIndex]['startTime']->diffInSeconds(now());
    }

    public function saveAnswer(): bool
    {
        if (empty($this->results[$this->questionIndex]['userAnswer'])) {
            return false;
        }

        $examResult = ExamResult::updateOrCreate(
            [
                'user_id' => auth()->id(),
                'exam_id' => $this->exam->id,
                'completed' => YesNoEnum::NO
            ],
            [
                'question_count' => $this->questionsCount,
            ]
        );

        $results = $this->results[$this->questionIndex];
        ExamResultDetail::updateOrCreate(
            [
                'exam_result_id' => $examResult->id,
                'question_id' => $results['question']->id
            ],
            [
                'answer_id' => $results['userAnswer'],
                'correct' => intval($results['isCorrect']),
                'lesson_id' => $results['question']->lesson_id,
                'topic_id' => $results['question']->topic_id,
                'time' => $results['diffTime']
            ]
        );

        if ($this->questionIndex >= $this->questionsCount) {
            $examResult->update([
                'completed' => YesNoEnum::YES,
                'correct_count' => $examResult->details()->where('correct', YesNoEnum::YES)->count(),
                'incorrect_count' => $examResult->details()->where('correct', YesNoEnum::NO)->count(),
                'time' => $examResult->details()->sum('time')
            ]);

            $this->dispatch('endTest');
        }

        resetCache();
        return true;
    }

    public function complete()
    {
        return redirect()->route('frontend.solved');
    }

    public function render()
    {
        return view('livewire.frontend.tests.solve-form', ['question' => $this->question()]);
    }
}
