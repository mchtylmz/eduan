<?php

namespace App\Livewire\Faqs;

use App\Actions\Faqs\CreateOrUpdateFaqAction;
use App\Enums\StatusEnum;
use App\Models\Faq;
use Illuminate\Validation\Rules\Enum;
use Livewire\Component;

class FaqForm extends Component
{
    public Faq $faq;

    public string $locale;
    public string $title = '';
    public string $content = '';
    public int $sort = 1;
    public StatusEnum $status = StatusEnum::ACTIVE;

    public string $permission = 'pages:add';

    public function mount(int $faqId = 0): void
    {
        $this->locale = settings()->examlanguageCode ?? app()->getLocale();

        if ($faqId && $this->faq = Faq::find($faqId)) {
            $this->locale = $this->faq->locale;
            $this->title = $this->faq->title;
            $this->content = html_entity_decode($this->faq->content);
            $this->sort = $this->faq->sort;
            $this->status = $this->faq->status;

            $this->permission = 'faqs:update';
        }
    }

    public function rules(): array
    {
        return [
            'locale' => 'required|string|exists:languages,code',
            'sort' => 'required|integer',
            'title' => 'required|string|min:1',
            'status' => [
                'required',
                new Enum(StatusEnum::class)
            ]
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'locale' => __('Dil'),
            'sort' => __('Sıra'),
            'title' => __('Soru Başlığı'),
            'status' => __('Durum'),
        ];
    }

    public function save()
    {
        $this->validate();

        CreateOrUpdateFaqAction::run(
            data: [
                'locale' => $this->locale,
                'status' => $this->status,
                'sort' => $this->sort,
                'title' => $this->title,
                'content' => htmlentities($this->content),
            ],
            faq: !empty($this->faq) && $this->faq->exists ? $this->faq : null
        );

        return redirect()->route('admin.faqs.index')->with([
            'status' => 'success',
            'message' => __('Sıkça sorulan soru kayıt edildi!')
        ]);
    }

    public function render()
    {
        return view('livewire.backend.faqs.faq-form');
    }
}
