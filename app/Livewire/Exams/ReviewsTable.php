<?php

namespace App\Livewire\Exams;

use App\Enums\RoleTypeEnum;
use App\Helpers\TableCache;
use App\Models\Exam;
use App\Models\ExamReview;
use App\Models\User;
use App\Traits\CustomLivewireAlert;
use App\Traits\ServerTableActions;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Locked;
use Livewire\Component;

class ReviewsTable extends Component
{
    use CustomLivewireAlert, ServerTableActions { mount as private mountTable; }

    protected string $resource = 'exams-reviews';

    protected string $tableName = 'reviews';

    #[Locked]
    public ?Exam $exam = null;

    #[Locked]
    public ?User $user = null;

    public function mount(?Exam $exam = null, ?User $user = null): void
    {
        $this->mountTable();
        $this->exam = $exam;
        $this->user = $user;
    }

    protected function builder(): Builder
    {
        return ExamReview::with(['user', 'exam'])
            ->when($this->exam?->exists, fn ($query) => $query->where('exam_id', $this->exam->id))
            ->when($this->user?->exists, fn ($query) => $query->where('user_id', $this->user->id));
    }

    public function render()
    {
        $this->selectedRecord();
        $students = auth()->user()->can(RoleTypeEnum::TEACHER->value) ? auth()->user()->students()->pluck('users.id')->all() : null;

        return view('livewire.backend.reviews.server-table', [
            'details' => [], 'editUrl' => null,
            'users' => TableCache::options('review-users-'.hash('sha256', json_encode($students)),
                fn () => User::orderByDesc('id')->when($students !== null, fn ($query) => $query->whereIn('id', $students))
                    ->get()->mapWithKeys(fn ($user) => [$user->id => '('.$user->email.') '.$user->display_name])->all()),
            'exams' => TableCache::options('review-exams', fn () => Exam::orderByDesc('id')->get()
                ->mapWithKeys(fn ($exam) => [$exam->id => '('.$exam->code.') '.$exam->name])->all()),
        ]);
    }

    public function delete(ExamReview $review)
    {
        $this->requireTableAccess('delete');
        if (auth()->user()->cannot('exams-reviews:delete')) {
            $this->message(__('Değerlendirme silinemedi, yetkiniz bulunmuyor!'))->error();

            return false;
        }

        $this->message(__('Değerlendirme silindi!'))->success();

        $deleted = $review->delete();
        $this->dispatch('refresh-server-table', table: 'reviews');

        return $deleted;
    }
}
