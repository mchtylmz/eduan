<?php

namespace App\Mail;

use App\Models\Newsletter;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;

class NewsletterMail extends Mailable implements HasLocalePreference, ShouldQueue
{
    use Queueable;

    public Newsletter $subscribe;

    public $locale = 'tr';
    public $subject = '';
    public string $content = '';
    public string $token = '0';

    /**
     * Create a new message instance.
     */
    public function __construct(Newsletter $subscribe, string $locale = null, string $subject = null, string $content = null)
    {
        $this->subscribe = $subscribe;
        $this->locale = $locale;
        $this->subject = $subject;
        $this->content = $content ? replaceEmailVariables($content, $subscribe->toArray()) : '';
        $this->token = $subscribe->token ?? '0';
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
        $viewContent = View::make('email.newsletter', [
            'subscribe' => $this->subscribe,
            'locale' => $this->locale,
            'subject' => $this->subject,
            'content' => $this->content,
            'unsubscribe' => route('frontend.unsubscribe', $this->token),
        ])->render();

        $this->saveSentStatus();

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

    protected function saveSentStatus(): void
    {
        DB::table('newsletters_mails')->insert([
            'locale' => $this->locale,
            'email' => $this->subscribe->email,
            'content' => $this->content,
            'sent_at' => now()
        ]);
    }
}
