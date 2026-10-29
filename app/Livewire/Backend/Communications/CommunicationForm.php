<?php

namespace App\Livewire\Backend\Communications;

use App\Actions\Communications\AuthorizeCommunication;
use App\Actions\Communications\CreateCommunicationBatch;
use App\Helpers\CommunicationRecipients;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
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
    public int $step = 0;

    #[Locked]
    public array $preview = [];

    #[Locked]
    public string $previewHash = '';

    #[Locked]
    public string $submissionKey;

    protected function authorizeComposition(bool $write = false): void
    {
        AuthorizeCommunication::run(write: $write);
    }

    public function mount(): void
    {
        $this->authorizeComposition();
        $this->submissionKey = (string) Str::uuid();
        $this->locale = in_array(app()->getLocale(), ['tr', 'nl', 'en', 'fr'], true) ? app()->getLocale() : 'nl';
    }

    public function start(): void
    {
        $this->authorizeComposition(write: true);
        $this->resetErrorBag();
        $this->reset('recipients', 'title', 'body', 'preview', 'previewHash', 'notice');
        $this->submissionKey = (string) Str::uuid();
        $this->step = 1;
        $this->dispatch('communication-compose-started');
    }

    public function validationAttributes(): array
    {
        return ['recipients' => __('notifications.recipients'), 'title' => __('notifications.subject'), 'body' => __('notifications.body')];
    }

    public function selectFilteredRecipients(array $filters): void
    {
        $this->authorizeComposition(write: true);
        abort_unless($this->step === 1, 409);
        $this->resetErrorBag();
        Validator::make(['recipients' => $this->recipients], [
            'recipients' => 'array|max:500', 'recipients.*' => CreateCommunicationBatch::rules()['recipients.*'],
        ])->validate();
        if ($this->recipients !== []) {
            CreateCommunicationBatch::recipients(array_map('intval', $this->recipients));
        }
        $parameters = CommunicationRecipients::parameters(Request::create('/', 'GET', $filters));
        $ids = CommunicationRecipients::query($parameters)->limit(501)->pluck('users.id')->map(fn ($id) => (int) $id)->all();
        $selected = array_values(array_unique([...array_map('intval', $this->recipients), ...$ids]));
        if (count($selected) > 500) {
            $this->addError('recipients', __('notifications.selection_limit'));

            return;
        }
        $this->recipients = $selected;
        $this->dispatch('communication-selection-updated', ids: $selected);
    }

    private function input(): array
    {
        return ['recipients' => $this->recipients, 'channel' => $this->channel, 'title' => $this->title,
            'body' => $this->body, 'locale' => $this->locale, 'submission_key' => $this->submissionKey];
    }

    private function fingerprint(array $input): string
    {
        return hash('sha256', json_encode($input, JSON_THROW_ON_ERROR));
    }

    public function next(): void
    {
        $this->authorizeComposition(write: true);
        abort_unless(in_array($this->step, [1, 2], true), 409);
        $this->resetErrorBag();
        if ($this->step === 1) {
            $this->validate(array_intersect_key(CreateCommunicationBatch::rules(), array_flip(['recipients', 'recipients.*'])));
            CreateCommunicationBatch::recipients(array_map('intval', $this->recipients));
            $this->step = 2;
            $this->dispatch('communication-step-changed');

            return;
        }
        try {
            $input = CreateCommunicationBatch::validatedInput($this->input());
            $users = CreateCommunicationBatch::recipients($input['recipients']);
            $sample = $users->get($input['recipients'][0]);
            $replace = ['{{name}}' => (string) $sample->name, '{{surname}}' => (string) $sample->surname];
            $this->title = $input['title'];
            $this->body = $input['body'];
            $this->preview = ['count' => count($input['recipients']), 'channel' => $input['channel'], 'locale' => $input['locale'],
                'names' => $users->take(5)->map(fn ($user) => trim($user->name.' '.$user->surname))->values()->all(),
                'sample' => trim($sample->name.' '.$sample->surname), 'title' => strtr($input['title'], $replace), 'body' => strtr($input['body'], $replace)];
            $this->previewHash = $this->fingerprint($input);
            $this->step = 3;
            $this->dispatch('communication-step-changed');
        } catch (HttpException $error) {
            if ($error->getStatusCode() !== 422) {
                throw $error;
            }
            $this->addError('communication', $error->getMessage());
        }
    }

    public function previous(): void
    {
        $this->authorizeComposition(write: true);
        abort_unless(in_array($this->step, [2, 3], true), 409);
        $this->resetErrorBag();
        $this->preview = [];
        $this->previewHash = '';
        $this->step--;
        $this->dispatch('communication-step-changed');
    }

    public function cancel(): void
    {
        $this->authorizeComposition();
        $this->resetErrorBag();
        $this->reset('step', 'recipients', 'title', 'body', 'preview', 'previewHash');
        $this->dispatch('communication-created');
    }

    public function enqueue(): void
    {
        $this->authorizeComposition(write: true);
        abort_unless($this->step === 3 && $this->previewHash !== '', 409);
        $this->notice = '';
        $this->resetErrorBag();
        try {
            $input = CreateCommunicationBatch::validatedInput($this->input());
            if ($this->previewHash !== $this->fingerprint($input)) {
                $this->step = 2;
                $this->dispatch('communication-step-changed');
                $this->preview = [];
                $this->previewHash = '';
                $this->addError('communication', __('notifications.preview_changed'));

                return;
            }
            CreateCommunicationBatch::run($input);
            $this->reset('step', 'recipients', 'title', 'body', 'preview', 'previewHash');
            $this->submissionKey = (string) Str::uuid();
            $this->notice = __('notifications.queued');
            $this->dispatch('communication-created');
            $this->dispatch('refresh-server-table', table: 'communication-history');
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
        $this->authorizeComposition();

        return view('livewire.backend.communications.communication-form');
    }
}
