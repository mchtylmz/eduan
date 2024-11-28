<?php

namespace App\Livewire\Frontend\Contacts;

use App\Actions\Contacts\CreateMessageAction;
use App\Traits\CustomLivewireAlert;
use Livewire\Component;

class ContactForm extends Component
{
    use CustomLivewireAlert;

    public string $name;
    public string $email;
    public string $phone;
    public string $schoolName;
    public string $message;
    public bool $acceptTerms = true;

    public function rules(): array
    {
        return [
            'name' => ['required', 'string'],
            'email' => ['required', 'email:rfc,dns'],
            'phone' => ['nullable', 'string'],
            'schoolName' => ['required', 'string'],
            'message' => ['required', 'string', 'max:777'],
            'acceptTerms' => ['required', 'accepted'],
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'name' => __('İsim Soyisim'),
            'email' => __('E-posta Adresi'),
            'phone' => __('Telefon Numarası'),
            'schoolName' => __('Okul Adı'),
            'message' => __('Mesajınız'),
            'acceptTerms' => __('Site kullanım şartları ve gizlilik kuralları'),
        ];
    }

    public function submit()
    {
        $this->validate();

        try {
            CreateMessageAction::run(data: [
                'name' => $this->name,
                'email' => $this->email,
                'phone' => $this->phone ?? '',
                'school_name' => $this->schoolName,
                'message' => $this->message,
                'accept_terms' => $this->acceptTerms,
            ]);

            $this->reset(['name', 'email', 'phone', 'schoolName', 'message']);
            $this->message(__('Mesajınız başarıyla gönderildi!'))->toast(false, 'center')->success();
        } catch (\Exception $exception) {
            $this->message(__('Mesajınız gönderilemedi, bilinmeyen hata oluştu, lütfen daha sonra tekrar deneyiniz!'))->toast(false, 'center')->error();
        }
    }

    public function render()
    {
        return view('livewire.frontend.contacts.contact-form');
    }
}
