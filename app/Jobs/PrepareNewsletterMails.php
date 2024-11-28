<?php

namespace App\Jobs;

use App\Mail\EmailVerificationMail;
use App\Mail\NewsletterMail;
use App\Models\Newsletter;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class PrepareNewsletterMails implements ShouldQueue
{
    use Queueable;

    public Collection $subscribes;
    public string $locale;
    public string $subject;
    public string $content;

    /**
     * Create a new job instance.
     */
    public function __construct(array $subscribeIds, string $locale, string $subject, string $content)
    {
        $this->locale = $locale;
        $this->subject = $subject;
        $this->content = $content;
        $this->subscribes = Newsletter::whereIn('id', $subscribeIds)->get();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        foreach ($this->subscribes as $key => $subscribe) {
            Mail::to($subscribe->email)->later(
                delay: now()->addSeconds($key + 3),
                mailable: new NewsletterMail(
                    subscribe: $subscribe,
                    locale: $this->locale,
                    subject: $this->subject,
                    content: $this->content
                )
            );
        }

        flush();
    }
}
