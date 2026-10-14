<?php

namespace App\Livewire\Frontend\Study;

use App\Actions\Study\AuthorizeStudyAccess;
use App\Helpers\StudyTrackingSettings;
use App\Models\StudyNote;
use Livewire\Component;
use Livewire\WithPagination;

class VisibleStudyNotes extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public function render()
    {
        AuthorizeStudyAccess::run(auth()->user());
        $notes = StudyTrackingSettings::enabled() && StudyTrackingSettings::startedOn()
            ? StudyNote::where('user_id', auth()->id())->where('student_visible', true)->with('author:id,name,surname')->orderByDesc('id')->paginate(10, ['*'], 'visibleNotesPage') : null;

        return view('livewire.frontend.study.visible-study-notes', compact('notes'));
    }
}
