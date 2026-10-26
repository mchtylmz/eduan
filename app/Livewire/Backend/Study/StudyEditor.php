<?php

namespace App\Livewire\Backend\Study;

use App\Actions\Study\AuthorizeStudyPanel;
use App\Livewire\Frontend\Study\StudyForm;
use App\Models\User;
use Livewire\Attributes\Locked;

class StudyEditor extends StudyForm
{
    #[Locked]
    public int $studentId;

    public function mount(string $weekStart, ?int $studentId = null): void
    {
        abort_unless($studentId, 422);
        $this->studentId = $studentId;
        parent::mount($weekStart);
    }

    private ?User $subject = null;

    protected function owner(): User
    {
        if (! $this->subject) {
            $student = User::findOrFail($this->studentId);
            AuthorizeStudyPanel::run($student, true);
            $this->subject = $student;
        }

        return $this->subject;
    }

    protected function reportComponent(): string
    {
        return StudentReport::class;
    }

    protected function includesActivityLists(): bool
    {
        return false;
    }

    public function render()
    {
        return view('livewire.backend.study.study-editor', parent::render()->getData());
    }
}
