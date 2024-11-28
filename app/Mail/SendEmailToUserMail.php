<?php

namespace App\Mail;

use App\Models\Contact;
use App\Models\Newsletter;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;

class SendEmailToUserMail extends Mailable implements HasLocalePreference, ShouldQueue
{
    use Queueable;

    public User $user;

    public string $email;
    public $locale = 'tr';
    public $subject = '';
    public string $content = '';

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, string $locale = null, string $subject = null, string $content = null)
    {
        $this->user = $user;
        $this->email = $contact->email ?? '0';
        $this->locale = $locale;
        $this->subject = $subject;
        $this->content = $content ? replaceEmailVariables($content, $user->toArray()) : '';
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
        $viewContent = View::make('email.user-email', [
            'email' => $this->email,
            'locale' => $this->locale,
            'subject' => $this->subject,
            'content' => $this->content,
            'user' => $this->user
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
