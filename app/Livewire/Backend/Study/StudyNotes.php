<?php

namespace App\Livewire\Backend\Study;

use App\Actions\Study\AuthorizeStudyPanel;
use App\Actions\Study\SaveStudyNote;
use App\Helpers\StudyTrackingSettings;
use App\Models\StudyNote;
use App\Models\User;
use Illuminate\Support\Str;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpKernel\Exception\HttpException;

class StudyNotes extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    #[Locked]
    public int $studentId;

    #[Locked]
    public ?int $editingId = null;

    #[Locked]
    public ?array $before = null;

    #[Locked]
    public string $submissionKey;

    public string $body = '';

    public bool $studentVisible = false;

    public string $notice = '';

    public function mount(int $studentId): void
    {
        $this->studentId = $studentId;
        $this->student();
        $this->submissionKey = (string) Str::uuid();
    }

    private function student(bool $write = false): User
    {
        $student = User::findOrFail($this->studentId);
        AuthorizeStudyPanel::run($student, $write);

        return $student;
    }

    private function note(int $id): StudyNote
    {
        $this->student(true);
        $note = StudyNote::where('user_id', $this->studentId)->whereKey($id)->firstOrFail();
        abort_unless(auth()->user()->can('user-type:admin') || $note->author_id === auth()->id(), 403);

        return $note;
    }

    public function edit(int $id): void
    {
        $note = $this->note($id);
        $this->editingId = $id;
        $this->before = SaveStudyNote::snapshot($note);
        $this->body = $note->body;
        $this->studentVisible = $note->student_visible;
        $this->notice = '';
        $this->resetErrorBag();
    }

    public function cancel(): void
    {
        $this->student();
        $this->editingId = null;
        $this->before = null;
        $this->body = '';
        $this->studentVisible = false;
        $this->submissionKey = (string) Str::uuid();
        $this->resetErrorBag();
    }

    public function save(): void
    {
        $this->persist();
    }

    public function remove(): void
    {
        abort_unless($this->editingId, 422);
        $this->persist(true);
    }

    private function persist(bool $remove = false): void
    {
        $student = $this->student(true);
        $this->notice = '';
        $this->resetErrorBag();
        try {
            if (! $remove) {
                $this->validate(['body' => 'required|string|max:10000', 'studentVisible' => 'boolean']);
            }
            SaveStudyNote::run($student, $remove ? $this->before : ['body' => $this->body, 'student_visible' => $this->studentVisible], $this->submissionKey, $this->editingId, $this->before, $remove);
            $this->cancel();
            $this->resetPage('studyNotesPage');
            $this->notice = __('study.note_saved');
        } catch (HttpException $exception) {
            if (! in_array($exception->getStatusCode(), [403, 409], true) || $exception->getMessage() === '') {
                throw $exception;
            }
            $this->addError('note', $exception->getMessage());
        }
    }

    public function render()
    {
        $this->student();
        $enabled = StudyTrackingSettings::enabled() && StudyTrackingSettings::startedOn();
        $notes = $enabled ? StudyNote::where('user_id', $this->studentId)->with('author:id,name,surname')->orderByDesc('id')->paginate(10, ['*'], 'studyNotesPage') : null;

        return view('livewire.backend.study.study-notes', compact('notes', 'enabled'));
    }
}
