<?php

namespace App\Mail;

use App\Models\Language;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;

class EmailVerificationMail extends Mailable implements HasLocalePreference, ShouldQueue
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
        $this->subject = settingLocale(key: 'emailVerificationSubject', locale: $this->locale) ?? '';
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
        $this->user->update(['email_verified_token' => Str::random(32)]);

        $viewContent = View::make('email.verify', [
            'user' => $this->user,
            'locale' => $this->locale,
            'subject' => $this->subject,
            'content' => settingLocale(key: 'emailVerificationContent', locale: $this->locale) ?? '',
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
}
