<?php

namespace App\Livewire\Backend\Communications;

use App\Actions\Communications\AuthorizeCommunication;
use App\Actions\Communications\CreateCommunicationBatch;
use App\Models\CommunicationDelivery;
use Illuminate\Support\Str;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CommunicationForm extends Component
{
    public array $recipients = [];

    public string $channel = 'email';

    public string $title = '';

    public string $body = '';

    public string $locale = 'nl';

    public string $notice = '';

    #[Locked]
    public string $submissionKey;

    public function mount(): void
    {
        AuthorizeCommunication::run();
        $this->submissionKey = (string) Str::uuid();
        $this->locale = in_array(app()->getLocale(), ['tr', 'nl', 'en', 'fr'], true) ? app()->getLocale() : 'nl';
    }

    public function enqueue(): void
    {
        AuthorizeCommunication::run(write: true);
        $this->notice = '';
        $this->resetErrorBag();
        try {
            CreateCommunicationBatch::run(['recipients' => $this->recipients, 'channel' => $this->channel, 'title' => $this->title,
                'body' => $this->body, 'locale' => $this->locale, 'submission_key' => $this->submissionKey]);
            $this->recipients = [];
            $this->title = $this->body = '';
            $this->submissionKey = (string) Str::uuid();
            $this->notice = __('notifications.queued');
            $this->dispatch('communication-created');
        } catch (HttpException $error) {
            if (in_array($error->getStatusCode(), [409, 422], true)) {
                $this->addError('communication', $error->getMessage());
            } else {
                throw $error;
            }
        }
    }

    public function render()
    {
        AuthorizeCommunication::run();
        $query = CommunicationDelivery::orderByDesc('id');
        if (! auth()->user()->can('user-type:admin')) {
            $query->where('actor_id', auth()->id());
        }

        return view('livewire.backend.communications.communication-form', ['recent' => $query->limit(20)->get()]);
    }
}
