<?php

namespace App\Livewire\Stats;

use App\Mail\NewsletterMail;
use App\Mail\SendEmailToUserMail;
use App\Models\User;
use App\Traits\CustomLivewireAlert;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;

class SendEmailForm extends Component
{
    use CustomLivewireAlert;

    public array $emails = [];
    public string $subject;
    public string $content;

    public function mount(array $emails = []): void
    {
        $this->emails = collect($emails)->unique('email')->toArray();
    }

    public function rules(): array
    {
        return [
            'subject' => 'required|string|min:5',
            'content' => 'required|string|min:10',
        ];
    }

    public function validationAttributes(): array
    {
        return [
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

        $laterSecond = 3;

        foreach ($this->emails as $row) {
            if (empty($row['email'])) {
                continue;
            }

            $content = str_replace(
                [
                    '[ad]',
                    '[soyad]',
                    '[email]',
                    '[basari_orani]'
                ],
                [
                    $row['name'] ?? '',
                    $row['surname'] ?? '',
                    $row['email'],
                    $row['success_rate'] ?? 0,
                ],
                $this->content
            );

            try {
                Mail::to($row['email'])->later(
                    delay: now()->addSeconds($laterSecond),
                    mailable: new SendEmailToUserMail(
                        user: User::where('email', $row['email'])->first(),
                        locale: app()->getLocale(),
                        subject: $this->subject,
                        content: $content
                    )
                );
            } catch (\Exception $e) {}

            $laterSecond += 3;
        }

        $this->message(__('Kullanıcılara mail gönderildi!'))->success();
        $this->dispatch('closeModal');
        return true;
    }

    public function render()
    {
        return view('livewire.backend.stats.send-email-form');
    }
}
