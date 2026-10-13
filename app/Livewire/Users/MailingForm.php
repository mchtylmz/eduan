<?php

namespace App\Livewire\Users;

use App\Actions\Users\AuthorizeUserMail;
use App\Mail\SendEmailToUserMail;
use App\Models\Contact;
use App\Models\User;
use App\Traits\CustomLivewireAlert;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;

class MailingForm extends Component
{
    use CustomLivewireAlert;

    public User $user;

    public string $locale;
    public string $email;

    public string $subject;
    public string $content;

    public function mount(User $user): void
    {
        AuthorizeUserMail::run($user);
        $this->user = $user;
        $this->email = $user->email ?? $user->username;

        $this->locale = settings()->defaultLocale ?? 'tr';
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
        AuthorizeUserMail::run($this->user);
        $this->validate();

        if (!settings()->mailHost || !settings()->mailUsername || !settings()->mailPassword) {
            $this->message(__('Mail gönderim ayarları yapılmamış, mail gönderimi yapılamaz!!'))->error();
            return false;
        }

        try {
            Mail::to($this->email)->later(
                delay: now()->addSeconds(15),
                mailable: new SendEmailToUserMail(
                    user: $this->user,
                    locale: $this->locale ?? app()->getLocale(),
                    subject: $this->subject,
                    content: $this->content,
                    email: $this->email
                )
            );
        } catch (\Throwable $exception) {
            Log::warning('User mail enqueue failed', [
                'actor_id' => auth()->id(), 'user_id' => $this->user->id,
                'recipient' => $this->email, 'exception' => $exception::class,
            ]);
        }

        return redirect()->route('admin.users.edit', $this->user->id)->with([
            'status' => 'success',
            'message' => __('E-posta gönderildi!')
        ]);
    }

    public function render()
    {
        return view('livewire.backend.users.mailing-form');
    }
}
