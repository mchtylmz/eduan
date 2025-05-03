<?php

namespace App\Livewire\Tests;

use App\Models\Lesson;
use App\Models\Question;
use App\Models\Topic;
use App\Traits\CustomLivewireAlert;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class SelectQuestionModalForm extends Component
{
    use WithPagination, CustomLivewireAlert;

    public int $sectionIndex;
    public int $fieldIndex;
    public int $questionId = 0;

    public int $topic_id = 0;
    public int $lesson_id = 0;

    public function mount(int $sectionIndex, int $fieldIndex, int $questionId = 0): void
    {
        $this->sectionIndex = $sectionIndex;
        $this->fieldIndex = $fieldIndex;
        $this->questionId = $questionId;
    }

    #[Computed(cache: true)]
    public function lessons()
    {
        return Lesson::active()->withCount('topics')->get();
    }

    #[Computed(cache: true)]
    public function topics()
    {
        return Topic::active()->where('lesson_id', $this->lesson_id)->withCount('questions')->get();
    }

    #[Computed]
    public function questions()
    {
        return Question::active()
            ->where('topic_id', $this->topic_id)
            ->where('lesson_id', $this->lesson_id)
            ->latest()
            ->paginate(6);
    }

    public function select(int $questionId): bool
    {
        $this->questionId = $questionId;
        if (empty($this->questionId)) {
            $this->message(__('Soru seçimi yapınız!'))->error();
            return false;
        }

        $this->dispatch(
            'selectQuestion',
            sectionIndex: $this->sectionIndex,
            fieldIndex: $this->fieldIndex,
            questionId: $this->questionId
        );

        $this->dispatch('closeModal');
        return true;
    }

    public function render()
    {
        return view('livewire.backend.tests.select-question-modal-form');
    }
}
