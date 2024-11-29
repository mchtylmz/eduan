<?php

namespace App\Livewire\Exams;

use App\Actions\Exams\CreateReviewAction;
use App\Enums\ReviewVisibilityEnum;
use App\Enums\YesNoEnum;
use App\Models\ExamReview;
use App\Traits\CustomLivewireAlert;
use Livewire\Component;

class ReviewDetailForm extends Component
{
    use CustomLivewireAlert;

    public ExamReview $review;

    public ReviewVisibilityEnum $visibility;
    public string $comment;

    public function mount(ExamReview $review): void
    {
        $this->review = $review;

        $this->visibility = $this->review->visibility;
    }

    public function save(): void
    {
        $this->review->update([
           'visibility' => $this->visibility,
        ]);

        $this->message(__('Görünüm güncellendi'))->success();
    }

    public function rules(): array
    {
        return [
            'comment' => ['required', 'string'],
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'comment' => __('Yanıt Metni'),
        ];
    }

    public function reply()
    {
        $this->validate();

        CreateReviewAction::run(
            data: [
                'user_id' => auth()->id(),
                'comment' => $this->comment,
                'visibility' => $this->review->visibility,
                'reply_id' => $this->review->id,
                'has_read' => YesNoEnum::YES
            ],
            exam: $this->review->exam,
        );

        return redirect()->route('admin.exams.reviews')->with([
            'status' => 'success',
            'message' => __('Yanıt kayıt edildi')
        ]);
    }

    public function render()
    {
        return view('livewire.backend.exams.review-detail-form');
    }
}
