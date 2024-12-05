<?php

namespace App\Livewire\Frontend\Newsletter;

use App\Actions\Auth\LoginAction;
use App\Actions\Contacts\CreateMessageAction;
use App\Actions\Contacts\CreateNewsLetterAction;
use App\Traits\CustomLivewireAlert;
use Livewire\Attributes\Lazy;
use Livewire\Component;


class NewsletterForm extends Component
{
    use CustomLivewireAlert;

    public string $email;
    public bool $acceptTerms = true;

    public function rules(): array
    {
        return [
            'email' => ['required', 'email:rfc,dns'],
            'acceptTerms' => ['required', 'accepted'],
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'email' => __('E-posta Adresi'),
            'acceptTerms' => __('Site kullanım şartları ve gizlilik kuralları'),
        ];
    }

    public function submit()
    {
        $this->validate();

        CreateNewsLetterAction::run(data: [
            'email' => $this->email,
            'accept_terms' => $this->acceptTerms,
        ]);

       resetCache();
        $this->reset(['email']);

        $this->message(__('Bülten aboneliğiniz kayıt edilid!'))->success();
    }

    public function render()
    {
        return view('livewire.frontend.newsletter.newsletter-form');
    }
}
