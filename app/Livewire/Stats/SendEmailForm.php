<?php

namespace App\Livewire\Stats;

use App\Actions\Users\AuthorizeUserMail;
use App\Mail\SendEmailToUserMail;
use App\Models\User;
use App\Traits\CustomLivewireAlert;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Computed;
use Livewire\Component;

class SendEmailForm extends Component
{
    use CustomLivewireAlert;

    public array $emails = [];

    public string $subject;
    public string $content;

    public function mount(array $emails = []): void
    {
        AuthorizeUserMail::run();
        $this->emails = array_map(fn ($row) => is_object($row) ? (array) $row : $row, $emails);
    }

    public function rules(): array
    {
        return [
            'emails' => 'required|array|min:1',
            'emails.*' => 'required|array',
            'emails.*.user_id' => 'required|integer|min:1',
            'emails.*.email' => 'nullable|string|email:rfc',
            'emails.*.name' => 'nullable|string',
            'emails.*.surname' => 'nullable|string',
            'emails.*.success_rate' => 'nullable|numeric|min:0|max:100',
            'subject' => 'required|string|min:5',
            'content' => 'required|string|min:10',
        ];
    }

    #[Computed]
    public function recipientCount(): int
    {
        return collect($this->emails)->filter(fn ($row) => ! empty(data_get($row, 'email')))->unique('email')->count();
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
        AuthorizeUserMail::run();
        $this->emails = array_map(fn ($row) => is_object($row) ? (array) $row : $row, $this->emails);
        $this->validate();
        AuthorizeUserMail::run(studentIds: array_column($this->emails, 'user_id'));

        if (!settings()->mailHost || !settings()->mailUsername || !settings()->mailPassword) {
            $this->message(__('Mail gönderim ayarları yapılmamış, mail gönderimi yapılamaz!!'))->error();
            return false;
        }

        $laterSecond = 3;

        foreach (collect($this->emails)->unique('email') as $row) {
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
                        user: new User(['name' => $row['name'] ?? '', 'surname' => $row['surname'] ?? '', 'email' => $row['email']]),
                        locale: app()->getLocale(),
                        subject: $this->subject,
                        content: $content,
                        email: $row['email']
                    )
                );
            } catch (\Throwable $exception) {
                Log::warning('User mail enqueue failed', [
                    'actor_id' => auth()->id(), 'user_id' => $row['user_id'],
                    'recipient' => $row['email'], 'exception' => $exception::class,
                ]);
            }

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
