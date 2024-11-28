<?php

namespace App\Livewire\Contacts;

use App\Enums\YesNoEnum;
use App\Jobs\PrepareNewsletterMails;
use App\Mail\ContactReplyMail;
use App\Mail\NewsletterMail;
use App\Models\Contact;
use App\Traits\CustomLivewireAlert;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;

class ContactReplyForm extends Component
{
    use CustomLivewireAlert;

    public Contact $contact;

    public string $email;
    public string $subject;
    public string $content;

    public function mount(Contact $contact): void
    {
        $this->contact = $contact;
        $this->email = $contact->email ?? '';

        $this->contact->update(['has_read' => YesNoEnum::YES]);
    }

    public function rules(): array
    {
        return [
            'email' => 'required|string|email:rfc,dns',
            'subject' => 'required|string|min:5',
            'content' => 'required|string|min:10',
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'email' => __('E-posta Adresi'),
            'subject' => __('Mail Konu Başlığı'),
            'content' => __('Mail İçeriği'),
        ];
    }

    public function send()
    {
        $this->validate();

        if (!settings()->mailHost || !settings()->mailUsername || !settings()->mailPassword) {
            $this->message(__('Mail gönderim ayarları yapılmamış, mail gönderimi yapılamaz!!'))->error();
            return false;
        }

        Mail::to($this->email)->later(
            delay: now()->addSeconds(15),
            mailable: new ContactReplyMail(
                contact: $this->contact,
                locale: $this->contact->locale ?? app()->getLocale(),
                subject: $this->subject,
                content: $this->content
            )
        );

        $this->contact->update([
            'reply' => htmlentities($this->subject . '<br>' . $this->content)
        ]);

        return redirect()->route('admin.contacts.detail', $this->contact->id)->with([
            'status' => 'success',
            'message' => __('Yanıt gönderildi!')
        ]);
    }

    public function render()
    {
        return view('livewire.backend.contacts.contact-reply-form');
    }
}
