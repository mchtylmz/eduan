<?php

namespace App\Mail;

use App\Models\Contact;
use App\Models\Newsletter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;

class ContactReplyMail extends Mailable implements HasLocalePreference, ShouldQueue
{
    use Queueable;

    public Contact $contact;

    public string $email;
    public $locale = 'tr';
    public $subject = '';
    public string $content = '';

    /**
     * Create a new message instance.
     */
    public function __construct(Contact $contact, string $locale = null, string $subject = null, string $content = null)
    {
        $this->contact = $contact;
        $this->email = $contact->email ?? '0';
        $this->locale = $locale;
        $this->subject = $subject;
        $this->content = $content ? replaceEmailVariables($content, $contact->toArray()) : '';
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
        $viewContent = View::make('email.contact-reply', [
            'email' => $this->email,
            'locale' => $this->locale,
            'subject' => $this->subject,
            'content' => $this->content,
            'contact' => $this->contact
        ])->render();

        $this->saveReply();

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

    protected function saveReply(): void
    {
        $this->contact->update([
            'reply' => htmlentities($this->subject . '<br>' . $this->content)
        ]);
    }
}
