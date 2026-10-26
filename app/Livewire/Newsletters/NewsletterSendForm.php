<?php

namespace App\Livewire\Newsletters;

use App\Actions\Auth\AuthorizePanelForm;
use App\Jobs\PrepareNewsletterMails;
use App\Models\Newsletter;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

class NewsletterSendForm extends Component
{
    public array $subscribeIds = [];

    public string $locale;

    public string $subject;

    public string $content;

    #[Locked]
    public string $permission = 'newsletter:send';

    public function mount(): void
    {
        AuthorizePanelForm::run('newsletter', 'view');
    }

    #[Computed(cache: true)]
    public function subscribes()
    {
        AuthorizePanelForm::run('newsletter', 'view');

        return Newsletter::all();
    }

    public function rules(): array
    {
        return [
            'locale' => 'required|string|exists:languages,code',
            'subject' => 'required|string|min:5',
            'content' => 'required|string|min:10',
            'subscribeIds' => 'required|array|min:1',
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'locale' => __('Dil'),
            'subject' => __('Mail Konu Başlığı'),
            'content' => __('Mail İçeriği'),
            'subscribeIds' => __('Aboneler'),
        ];
    }

    public function send()
    {
        AuthorizePanelForm::run('newsletter', 'send');
        $this->validate();

        if (request()->user()->cannot('newsletter:send')) {
            $this->message(__('Bilgilendirme gönderimi yapılamaz, yetkiniz bulunmuyor!'))->error();

            return false;
        }

        if (! settings()->mailHost || ! settings()->mailUsername || ! settings()->mailPassword) {
            $this->message(__('Mail gönderim ayarları yapılmamış, mail gönderimi yapılamaz!!'))->error();

            return false;
        }

        PrepareNewsletterMails::dispatchSync(
            subscribeIds: $this->subscribeIds,
            locale: $this->locale,
            subject: $this->subject,
            content: $this->content,
        );

        resetCache();

        return redirect()->route('admin.newsletter.index')->with([
            'status' => 'success',
            'message' => __('Bilgilendirmeler gönderildi!'),
        ]);
    }

    public function render()
    {
        AuthorizePanelForm::run('newsletter', 'view');

        return view('livewire.backend.newsletters.newsletter-send-form');
    }
}
