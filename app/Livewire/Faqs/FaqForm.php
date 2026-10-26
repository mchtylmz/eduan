<?php

namespace App\Livewire\Faqs;

use App\Actions\Auth\AuthorizePanelForm;
use App\Actions\Faqs\CreateOrUpdateFaqAction;
use App\Enums\StatusEnum;
use App\Models\Faq;
use Illuminate\Validation\Rules\Enum;
use Livewire\Attributes\Locked;
use Livewire\Component;

class FaqForm extends Component
{
    #[Locked]
    public Faq $faq;

    public string $locale;

    public string $title = '';

    public string $content = '';

    public int $sort = 1;

    public StatusEnum $status = StatusEnum::ACTIVE;

    #[Locked]
    public string $permission = 'pages:add';

    public function mount(int $faqId = 0): void
    {
        AuthorizePanelForm::run('pages', $faqId ? 'update' : 'add');
        $this->locale = settings()->examlanguageCode ?? app()->getLocale();

        if ($faqId && $this->faq = Faq::find($faqId)) {
            $this->locale = $this->faq->locale;
            $this->title = $this->faq->title;
            $this->content = html_entity_decode($this->faq->content);
            $this->sort = $this->faq->sort;
            $this->status = $this->faq->status;

            $this->permission = 'pages:update';
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
                new Enum(StatusEnum::class),
            ],
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
        $this->authorizeForm();
        $this->validate();

        CreateOrUpdateFaqAction::run(
            data: [
                'locale' => $this->locale,
                'status' => $this->status,
                'sort' => $this->sort,
                'title' => $this->title,
                'content' => htmlentities($this->content),
            ],
            faq: ! empty($this->faq) && $this->faq->exists ? $this->faq : null
        );

        return redirect()->route('admin.pages.faqs.index')->with([
            'status' => 'success',
            'message' => __('Sıkça sorulan soru kayıt edildi!'),
        ]);
    }

    public function render()
    {
        $this->authorizeForm();

        return view('livewire.backend.faqs.faq-form');
    }

    private function authorizeForm(): void
    {
        AuthorizePanelForm::run('pages', isset($this->faq) && $this->faq->exists ? 'update' : 'add');
    }
}
