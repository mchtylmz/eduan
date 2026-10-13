<?php

namespace App\Livewire\Frontend\Ai;

use App\Models\AnswerAI;
use App\Models\Question;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Lazy(isolate: true)]
class SolutionAi extends Component
{
    #[Locked]
    public int $actorId;

    #[Locked]
    public Question $question;

    #[Locked]
    public ?AnswerAI $answerAi = null;

    public int $remainingGptLimit = 0;

    public string $warningMessage = '';

    public bool $disabledAskGpt = false;

    public function mount(Question $question, User $user): void
    {
        abort_unless(auth()->check() && auth()->id() === $user->id, 403);
        $this->question = $question;
        $this->actorId = $user->id;
        $actor = $this->currentActor();
        $this->remainingGptLimit = max((int) $actor->gpt_limit - $actor->usageGptLimit(), 0);
        $this->disabledAskGpt = $this->remainingGptLimit === 0;
    }

    public function askGpt(): bool
    {
        $this->currentActor();
        $this->warningMessage = '';
        $this->disabledAskGpt = false;

        // Serialize quota consumption across tabs; the cache counter is never authoritative.
        return DB::transaction(function () {
            $actor = User::whereKey($this->actorId)->lockForUpdate()->firstOrFail();
            abort_unless($actor->can('ai:solution'), 403);
            $usageDate = dateFormat(now(), 'Y-m-d');
            $used = $actor->aiUsages()->where('usage_date', $usageDate)->count();
            $this->remainingGptLimit = max((int) $actor->gpt_limit - $used, 0);

            return $this->solve($actor, $usageDate);
        });
    }

    private function currentActor(): User
    {
        abort_unless(auth()->check() && auth()->id() === $this->actorId, 403);
        $actor = User::findOrFail(auth()->id());
        abort_unless($actor->can('ai:solution'), 403);

        return $actor;
    }

    private function solve(User $actor, string $usageDate): bool
    {
        if ($this->remainingGptLimit <= 0) {
            $this->warningMessage = __('Günlük yapay zeka kullanımı limiti sona ermiştir.');
            $this->disabledAskGpt = true;

            return false;
        }

        $this->answerAi = AnswerAI::where('question_id', $this->question->id)->first();

        if (! $this->answerAi) {
            if (! is_file(public_path($this->question->attachment ?? '')) || ! is_readable(public_path($this->question->attachment ?? ''))
                || ! is_file(public_path($this->question->solution ?? '')) || ! is_readable(public_path($this->question->solution ?? ''))) {
                $this->warningMessage = __('Yapay zeka ile çözüm detaylandıralamadı, daha sonra tekrar deneyiniz!');
                $this->disabledAskGpt = true;

                return false;
            }
            $result = Cache::lock('ai-solution:'.$this->question->id, 150)->get(function () use ($actor) {
                // A current read also sees an answer committed after this user's transaction began.
                $existing = AnswerAI::where('question_id', $this->question->id)->lockForUpdate()->first();
                if ($existing) {
                    return $existing;
                }
                try {
                    $aiResponse = aiHelper()
                        ->forQuestion($this->question)
                        ->setImage(public_path($this->question->attachment))
                        ->setImage(public_path($this->question->solution))
                        ->output();
                } catch (\InvalidArgumentException $exception) {
                    return null;
                }
                if (! $aiResponse) {
                    return null;
                }
                if ($aiResponse['status'] !== 'ready') {
                    // Show the model's validated source diagnostic; it is not a solved answer.
                    // Do not cache it as an answer or consume quota, and never retry automatically.
                    $this->warningMessage = $aiResponse['final_answer'];

                    return null;
                }

                return AnswerAI::create([
                    'user_id' => $actor->id,
                    'lesson_id' => $this->question->lesson_id,
                    'topic_id' => $this->question->topic_id,
                    'locale' => $this->question->locale,
                    'question_id' => $this->question->id,
                    'title' => '',
                    'content' => $aiResponse,
                ]);
            });
            $this->answerAi = $result ?: null;
            if (! $this->answerAi) {
                if (! $this->warningMessage) {
                    $this->warningMessage = $result === false
                        ? __('Çözüm yapılıyor, lütfen bekleyiniz..')
                        : __('Yapay zeka ile çözüm detaylandıralamadı, daha sonra tekrar deneyiniz!');
                }
                $this->disabledAskGpt = true;

                return false;
            }
        }

        $this->remainingGptLimit -= 1;

        $actor->aiUsages()->create([
            'answer_ai_id' => $this->answerAi->id ?? 0,
            'usage' => 1,
            'remaining' => $this->remainingGptLimit,
            'usage_date' => $usageDate,
        ]);

        return true;
    }

    public function render()
    {
        return view('livewire.frontend.ai.solution-ai');
    }
}
