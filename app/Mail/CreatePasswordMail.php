<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;

class CreatePasswordMail extends Mailable implements HasLocalePreference, ShouldQueue
{
    use Queueable;

    public User $user;

    public $locale = 'tr';

    public $subject = '';

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, string $locale = null)
    {
        $this->user = $user;
        $this->locale = $locale;
        $this->subject = settingLocale(key: 'emailAfterCreatePasswordSubject', locale: $this->locale) ?? '';
    }

    public function preferredLocale(): string
    {
        return $this->locale;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subject
        );
    }

    public function build()
    {
        $this->resetPasswordToken();

        $viewContent = View::make('email.create-password', [
            'user' => $this->user,
            'locale' => $this->locale,
            'subject' => $this->subject,
            'content' => settingLocale(key: 'emailAfterCreatePasswordContent', locale: $this->locale) ?? '',
            'unsubscribe' => false
        ])->render();

        return $this
            ->subject($this->subject)
            ->html($viewContent);
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }

    private function resetPasswordToken(): void
    {
        DB::table('password_reset_tokens')->where('email', $this->user->email)->delete();
    }
}
