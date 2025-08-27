<?php

namespace App\Livewire\Frontend\Ai;

use App\Enums\YesNoEnum;
use App\Models\AnswerAI;
use App\Models\User;
use App\Traits\CustomLivewireAlert;
use Livewire\Component;
use App\Models\AnswerAIVote as  AnswerAIVoteModel;

class AnswerAiVote extends Component
{
    use CustomLivewireAlert;

    public int $vote = 0;
    public int $report = 0;

    public AnswerAI $answerAI;
    public User $user;

    public function mount(AnswerAI $answerAI, User $user): void
    {
        $this->answerAI = $answerAI;
        $this->user = $user;

        $answerVote = AnswerAIVoteModel::where('answer_ai_id', $this->answerAI->id)
            ->where('user_id', $this->user->id)
            ->first();
        if ($answerVote) {
            $this->vote = intval($answerVote->vote);
        }
    }

    public function updatedVote(int $value): void
    {
        if ($value)
            AnswerAIVoteModel::updateOrCreate(
                [
                    'user_id' => $this->user->id,
                    'answer_ai_id' => $this->answerAI->id
                ],
                [
                    'vote' => $value
                ]
            );
    }

    public function sendReport(): void
    {
        $this->report = 1;

        $this->answerAI->update([
            'report' => YesNoEnum::YES
        ]);

        $this->message(__('Yapay zeka yanıtı hatalı olarak raporlandı, yönetici tarafından incelenecektir!'))
            ->toast(false, 'center')
            ->success();
    }

    public function render()
    {
        return view('livewire.frontend.ai.answer-ai-vote');
    }
}
