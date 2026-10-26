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

    #[Locked]
    public string $activeTab = 'overview';

    public function mount(string $weekStart, ?int $studentId = null, string $activeTab = 'overview'): void
    {
        abort_unless($studentId, 422);
        $this->studentId = $studentId;
        abort_unless(in_array($activeTab, ['overview', 'history', 'activity', 'analysis', 'notes'], true), 422);
        $this->activeTab = $activeTab;
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

    public function render()
    {
        return view('livewire.backend.study.student-report', parent::render()->getData());
    }
}
