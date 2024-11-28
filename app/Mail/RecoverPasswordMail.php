<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;

class RecoverPasswordMail extends Mailable implements HasLocalePreference, ShouldQueue
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
        $this->subject = settingLocale(key: 'emailRecoverPasswordSubject', locale: $this->locale) ?? '';
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
        $viewContent = View::make('email.recover-password', [
            'user' => $this->user,
            'locale' => $this->locale,
            'subject' => $this->subject,
            'content' => settingLocale(key: 'emailRecoverPasswordContent', locale: $this->locale) ?? '',
            'token' => $this->createPasswordLink(),
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

    private function createPasswordLink(): string
    {
        $token = Str::random(40);

        DB::table('password_reset_tokens')->updateOrInsert(
            [
                'email' => $this->user->email
            ],
            [
                'token' => $token,
                'created_at' => now()
            ]
        );

        return $token;
    }
}
