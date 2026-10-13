<?php

namespace App\Livewire\Frontend\Tests;

use App\Actions\Exams\CreateReviewAction;
use App\Enums\ReviewVisibilityEnum;
use App\Enums\StatusEnum;
use App\Models\Exam;
use App\Traits\CustomLivewireAlert;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithoutUrlPagination;
use Livewire\WithPagination;

#[Lazy(isolate: true)]
class ReviewFormAndList extends Component
{
    use CustomLivewireAlert, WithoutUrlPagination, WithPagination;

    #[Locked]
    public Exam $exam;

    public string $message = '';

    public function mount(Exam $exam): void
    {
        $this->exam = $exam;
        $this->authorizeExam();
    }

    public function updateBadge(): void
    {
        $this->authorizeExam();
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
    public function reviews(): LengthAwarePaginator
    {
        $this->authorizeExam();

        return $this->exam->reviews()
            ->with('user:id,name,surname')
            ->where(function ($query) {
                $query->where('visibility', ReviewVisibilityEnum::PUBLIC);
                if (auth()->check()) {
                    $query->orWhere('visibility', ReviewVisibilityEnum::PRIVATE)
                        ->orWhere('user_id', auth()->id());
                }
            })
            ->orderByDesc('id')
            ->paginate(12);
    }

    public function save()
    {
        abort_unless(auth()->check(), 403);
        $this->authorizeExam();
        $this->validate();

        CreateReviewAction::run(
            data: [
                'user_id' => auth()->id(),
                'comment' => $this->message,
                'visibility' => ReviewVisibilityEnum::PUBLIC,
            ],
            exam: $this->exam
        );

        $this->updateBadge();

        $this->message(__('Mesajınız başarıyla gönderildi!'))->success();
        $this->reset('message');
    }

    private function authorizeExam(): void
    {
        abort_unless(Exam::whereKey($this->exam->id)->where('status', StatusEnum::ACTIVE)->exists(), 403);
    }

    public function render()
    {
        return view('livewire.frontend.tests.review-form-and-list');
    }
}
