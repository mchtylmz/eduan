<?php

namespace App\Livewire\Newsletters;

use App\Enums\YesNoEnum;
use App\Jobs\PrepareNewsletterMails;
use App\Models\Newsletter;
use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Component;

class NewsletterSendForm extends Component
{
    public array $subscribeIds = [];
    public string $locale;
    public string $subject;
    public string $content;
    public string $permission = 'newsletter:send';

    #[Computed(cache: true)]
    public function subscribes()
    {
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
        $this->validate();

        if (request()->user()->cannot($this->permission)) {
            $this->message(__('Bilgilendirme gönderimi yapılamaz, yetkiniz bulunmuyor!'))->error();
            return false;
        }

        if (!settings()->mailHost || !settings()->mailUsername || !settings()->mailPassword) {
            $this->message(__('Mail gönderim ayarları yapılmamış, mail gönderimi yapılamaz!!'))->error();
            return false;
        }

        PrepareNewsletterMails::dispatchSync(
            subscribeIds: $this->subscribeIds,
            locale: $this->locale,
            subject: $this->subject,
            content: $this->content,
        );

        flush();

        return redirect()->route('admin.newsletter.index')->with([
            'status' => 'success',
            'message' => __('Bilgilendirmeler gönderildi!')
        ]);
    }

    public function render()
    {
        return view('livewire.backend.newsletters.newsletter-send-form');
    }
}
