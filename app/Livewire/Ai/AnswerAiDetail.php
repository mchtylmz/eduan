<?php

namespace App\Livewire\Ai;

use App\Models\AnswerAI;
use App\Models\AnswerAIVote;
use App\Traits\CustomLivewireAlert;
use Livewire\Component;

class AnswerAiDetail extends Component
{
    use CustomLivewireAlert;

    public AnswerAI $answerAI;

    public function delete()
    {
        if (auth()->user()->cannot('ai:delete')) {
            $this->message(__('Yanıt silinemedi!'))->error();
            return false;
        }

        $this->answerAI->delete();

        return redirect()->route('admin.ai.index')->with([
            'status' => 'success',
            'message' => __('Yapay Zekan yanıtı başarıyla silindi!')
        ]);
    }


    public function render()
    {
        return view('livewire.backend.ai.answer-ai-detail');
    }
}
