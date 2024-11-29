<?php

namespace App\Livewire\Frontend\Tests;

use App\Actions\Exams\CreateReviewAction;
use App\Enums\ReviewVisibilityEnum;
use App\Models\Exam;
use App\Models\ExamReview;
use App\Models\User;
use App\Traits\CustomLivewireAlert;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Lazy;
use Livewire\Component;
use Livewire\WithoutUrlPagination;
use Livewire\WithPagination;

#[Lazy(isolate: true)]
class ReviewFormAndList extends Component
{
    use CustomLivewireAlert, WithPagination, WithoutUrlPagination;

    public Exam $exam;
    public User $user;

    public string $message;

    public function mount(Exam $exam, ?User $user = null): void
    {
        $this->exam = $exam;
        $this->user = !empty($user) && $user->exists ? $user : auth()->user();
    }

    public function updateBadge(): void
    {
        $this->dispatch('update-badge', ['count' => $this->exam->publicReviewsCount()]);
    }

    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'max:500'],
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'message' => __('Mesajınız'),
        ];
    }

    #[Computed]
    public function reviews(): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return $this->exam->reviews()
            ->where('visibility', '!=', ReviewVisibilityEnum::HIDE)
            ->orWhere('user_id', $this->user->id ?? 0)
            ->orderByDesc('id')
            ->paginate(12);
    }

    public function save()
    {
        $this->validate();

        CreateReviewAction::run(
            data: [
                'user_id' => $this->user->id,
                'comment' => $this->message,
                'visibility' => ReviewVisibilityEnum::PUBLIC
            ],
            exam: $this->exam
        );

        $this->updateBadge();

        $this->message(__('Mesajınız başarıyla gönderildi!'))->success();
        $this->reset('message');
    }

    public function render()
    {
        return view('livewire.frontend.tests.review-form-and-list');
    }
}
