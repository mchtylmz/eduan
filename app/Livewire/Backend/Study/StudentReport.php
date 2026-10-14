<?php

namespace App\Livewire\Backend\Study;

use App\Actions\Study\AuthorizeStudyPanel;
use App\Livewire\Frontend\Study\StudyReport;
use App\Models\User;
use Livewire\Attributes\Locked;

class StudentReport extends StudyReport
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
            AuthorizeStudyPanel::run($student);
            $this->subject = $student;
        }

        return $this->subject;
    }
}
