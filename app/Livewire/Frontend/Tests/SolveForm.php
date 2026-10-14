<?php

namespace App\Livewire\Frontend\Tests;

use App\Enums\StatusEnum;
use App\Enums\VisibilityEnum;
use App\Enums\YesNoEnum;
use App\Helpers\StudyAnswerClock;
use App\Models\Exam;
use App\Models\ExamResultDetail;
use App\Models\Question;
use App\Traits\CustomLivewireAlert;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Lazy(isolate: true)]
class SolveForm extends Component
{
    use CustomLivewireAlert;

    #[Locked]
    public Exam $exam;

    #[Locked]
    public array $questionIds = [];

    #[Locked]
    public int $questionIndex = 1;

    #[Locked]
    public int $questionsCount = 0;

    #[Locked]
    public int $resultId = 0;

    #[Locked]
    public array $startedAt = [];

    public array $results = [];

    public function mount(Exam $exam): void
    {
        $this->exam = $exam;
        $this->authorizeExam();
        $this->questionIds = $exam->questions()->active()->get()->modelKeys();
        $this->questionsCount = count($this->questionIds);
        abort_unless($this->questionsCount > 0, 404);

        $result = $exam->results()->where('user_id', auth()->id())
            ->where('completed', YesNoEnum::NO)->latest('id')->first();
        $this->resultId = $result?->id ?? 0;
        foreach ($this->questionIds as $index => $questionId) {
            $detail = $result?->details()->where('question_id', $questionId)->first();
            $this->results[$index + 1] = ['userAnswer' => $detail?->answer_id];
        }
        $this->startedAt[1] = now()->timestamp;
    }

    private function authorizeExam(): void
    {
        abort_unless(auth()->check(), 403);
        $exam = Exam::findOrFail($this->exam->id);
        abort_unless(StatusEnum::ACTIVE->is($exam->status), 403);
        abort_unless(VisibilityEnum::LOGGED->is($exam->visibility) || auth()->user()->can('exams:solve'), 403);
    }

    private function question(): Question
    {
        abort_unless(isset($this->questionIds[$this->questionIndex - 1]), 422);

        return $this->exam->questions()->active()->with('answers')
            ->whereKey($this->questionIds[$this->questionIndex - 1])->firstOrFail();
    }

    public function previousPage(): void
    {
        $this->authorizeExam();
        if ($this->questionIndex > 1) {
            $this->questionIndex--;
            $this->startedAt[$this->questionIndex] ??= now()->timestamp;
            $this->dispatch('scrollToQuestion');
        }
    }

    public function nextPage(): bool
    {
        $this->authorizeExam();
        if (! $this->savedAnswer()) {
            $this->message(__('Sonraki soruya geçmek için soru cevaplanmalıdır!'))
                ->toast(false, 'center')->error();

            return false;
        }
        if ($this->questionIndex < $this->questionsCount) {
            $this->questionIndex++;
            $this->startedAt[$this->questionIndex] ??= now()->timestamp;
            $this->dispatch('scrollToQuestion');
        }

        return true;
    }

    public function updatingResults(mixed $value, ?string $key = null): void
    {
        $this->authorizeExam();
        abort_unless($key === $this->questionIndex.'.userAnswer', 422);
        abort_unless((is_int($value) || is_string($value)) && preg_match('/^[1-9][0-9]*$/D', (string) $value), 422);
        abort_unless($this->question()->answers()->whereKey($value)->exists(), 422);
    }

    public function updatedResults(): void
    {
        $this->saveAnswer();
        $this->dispatch('scrollToSolution');
    }

    public function evaluateAnswer(int $answerId): bool
    {
        if (! $this->savedAnswer()) {
            return false;
        }

        return $this->question()->answers()->whereKey($answerId)->where('correct', YesNoEnum::YES)->exists();
    }

    private function savedAnswer(): ?ExamResultDetail
    {
        return ExamResultDetail::where('exam_result_id', $this->resultId)
            ->whereHas('examResult', fn ($query) => $query->where('user_id', auth()->id())->where('exam_id', $this->exam->id))
            ->where('question_id', $this->questionIds[$this->questionIndex - 1])->first();
    }

    public function isDisabled(): bool
    {
        return $this->savedAnswer() !== null;
    }

    public function saveAnswer(): bool
    {
        $this->authorizeExam();
        $answerId = $this->results[$this->questionIndex]['userAnswer'] ?? null;
        if ($answerId === null) {
            return false;
        }
        abort_unless((is_int($answerId) || is_string($answerId)) && preg_match('/^[1-9][0-9]*$/D', (string) $answerId), 422);

        return DB::transaction(function () use ($answerId) {
            Exam::whereKey($this->exam->id)->lockForUpdate()->firstOrFail();
            $question = $this->question();
            $answer = $question->answers()->whereKey($answerId)->first();
            abort_unless($answer, 422);
            $result = $this->exam->results()->where('user_id', auth()->id())
                ->when($this->resultId, fn ($query) => $query->whereKey($this->resultId))
                ->when(! $this->resultId, fn ($query) => $query->where('completed', YesNoEnum::NO))
                ->latest('id')->lockForUpdate()->first();
            abort_if($this->resultId && ! $result, 403);
            if ($result && YesNoEnum::YES->is($result->completed)) {
                return false;
            }
            $result ??= $this->exam->results()->create([
                'user_id' => auth()->id(), 'completed' => YesNoEnum::NO,
                'question_count' => $this->questionsCount, 'season_id' => activeSeason()->id ?? 1,
            ]);
            $this->resultId = $result->id;
            $detail = $result->details()->firstOrCreate(['question_id' => $question->id], [
                'answer_id' => $answer->id, 'correct' => $answer->correct,
                'study_answered_at' => StudyAnswerClock::next(0, (int) $answer->id, null, now()),
                'lesson_id' => $question->lesson_id, 'topic_id' => $question->topic_id,
                'time' => max(0, now()->timestamp - ($this->startedAt[$this->questionIndex] ?? now()->timestamp)),
            ]);
            $this->results[$this->questionIndex]['userAnswer'] = $detail->answer_id;
            if ($result->details()->whereIn('question_id', $this->questionIds)->distinct()->count('question_id') === $this->questionsCount) {
                $result->update([
                    'completed' => YesNoEnum::YES,
                    'correct_count' => $result->details()->where('correct', YesNoEnum::YES)->count(),
                    'incorrect_count' => $result->details()->where('correct', YesNoEnum::NO)->count(),
                    'time' => $result->details()->sum('time'),
                ]);
                $this->dispatch('endTest');
            }
            resetCache();

            return true;
        });
    }

    public function complete()
    {
        $this->authorizeExam();
        abort_unless($this->exam->results()->whereKey($this->resultId)->where('user_id', auth()->id())
            ->where('completed', YesNoEnum::YES)->exists(), 409);

        return redirect()->route('frontend.solved');
    }

    public function render()
    {
        return view('livewire.frontend.tests.solve-form', ['question' => $this->question()]);
    }
}
