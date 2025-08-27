<?php

namespace App\Livewire\Frontend\Ai;

use App\Models\AnswerAI;
use App\Models\Question;
use App\Models\User;
use Livewire\Attributes\Lazy;
use Livewire\Component;
use function Symfony\Component\String\s;

#[Lazy(isolate: true)]
class SolutionAi extends Component
{
    public User $user;
    public Question $question;
    public ?AnswerAI $answerAi = null;

    public int $remainingGptLimit = 0;

    public string $warningMessage = '';
    public bool $disabledAskGpt = false;

    public function mount(Question $question, User $user): void
    {
        $this->question = $question;
        $this->user = $user;

        $userGptLimit = intval($user->gpt_limit != settings()->gptLimit ? $user->gpt_limit : settings()->gptLimit);

        $this->remainingGptLimit = max($userGptLimit - $user->usageGptLimit(), 0);
        /*
        if ($this->remainingGptLimit <= 0) {
            $this->warningMessage = __('Günlük yapay zeka kullanımı limit sona ermiştir.');
            $this->disabledAskGpt = true;
        }
        */

        if ($user->cannot('ai:solution')) {
            $this->disabledAskGpt = true;
        }
    }

    public function askGpt(): bool
    {
        if ($this->remainingGptLimit <= 0) {
            $this->warningMessage = __('Günlük yapay zeka kullanımı limiti sona ermiştir.');
            $this->disabledAskGpt = true;
            return false;
        }

        $this->answerAi = AnswerAI::where('question_id', $this->question->id)->first();
        if ($this->answerAi) {
            sleep(1);
        }

        if (!$this->answerAi) {
            $aiResponse = aiHelper()
                ->setImage(public_path($this->question->attachment))
                ->setImage(public_path($this->question->solution))
                ->output();
            if (!$aiResponse) {
                $this->warningMessage = __('Yapay zeka ile çözüm detaylandıralamadı, daha sonra tekrar deneyiniz!');
                $this->disabledAskGpt = true;
                return false;
            }

            $this->answerAi = AnswerAI::create([
                'user_id' => $this->user->id,
                'lesson_id' => $this->question->lesson_id,
                'topic_id' => $this->question->topic_id,
                'locale' => $this->question->locale,
                'question_id' => $this->question->id,
                'title' => '',
                'content' => $aiResponse,
            ]);
        }

        $this->remainingGptLimit -= 1;

        $this->user->aiUsages()->create([
            'answer_ai_id' => $this->answerAi->id ?? 0,
            'usage' => 1,
            'remaining' => $this->remainingGptLimit,
            'usage_date' => date('Y-m-d'),
        ]);

        return true;
    }

    public function render()
    {
        return view('livewire.frontend.ai.solution-ai');
    }
}
