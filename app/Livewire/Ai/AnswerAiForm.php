<?php

namespace App\Livewire\Ai;

use App\Enums\YesNoEnum;
use App\Models\AnswerAI;
use Illuminate\Validation\Rules\Enum;
use Livewire\Component;

class AnswerAiForm extends Component
{
    public AnswerAI $answerAI;

    public string $content = '';
    public string $locale = '';
    public YesNoEnum $report = YesNoEnum::NO;

    public string $permission = 'ai:update';

    public function mount(AnswerAI $answerAI): void
    {
        $this->answerAI = $answerAI;

        $this->initializeForm();
    }

    public function initializeForm(): void
    {
        $this->locale = $this->answerAI->locale;
        $this->report = $this->answerAI->report;

        if ($steps = $this->answerAI->content['steps'] ?? []) {
            foreach($steps as $step) {
                if ($definition = $step['definition'] ?? '')
                    $this->content .= convertLatexToImg($definition, 'definition') . '<br>';
                if ($explanation = $step['explanation'] ?? '')
                    $this->content .= convertLatexToImg($explanation, 'explanation') . '<br>';
                if ($latex = $step['latex'] ?? '')
                    $this->content .= convertLatexToImg($latex, 'latex') . '<br>';
            }
        }

        if($final_answer = $this->answerAI->content['final_answer'] ?? '') {
            $this->content .= '<br>' . __('Final Answer:') . convertLatexToImg($final_answer, 'final_answer');
        }
    }

    public function rules(): array
    {
        return [
            'locale' => 'required|string|exists:languages,code',
            'report' => [
                'required',
                new Enum(YesNoEnum::class)
            ],
            'content' => 'required|string'
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'locale' => __('Dil'),
            'content' => __('İçerik'),
            'report' => __('Raporlama'),
        ];
    }

    public function save()
    {
        $this->validate();

        $explodeContent = explode(__('Final Answer:'), $this->content);

        $this->answerAI->update([
            'locale' => $this->locale,
            'report' => $this->report,
            'content' => [
                'steps' => [
                    [
                        'explanation' => $explodeContent[0],
                    ]
                ],
                'final_answer' => trim($explodeContent[1] ?? ''),
            ]
        ]);

        return redirect()->route('admin.ai.edit', $this->answerAI->id)->with([
            'status' => 'success',
            'message' => __('Yanıt kayıt edildi!')
        ]);
    }

    public function render()
    {
        return view('livewire.backend.ai.answer-ai-form');
    }
}
