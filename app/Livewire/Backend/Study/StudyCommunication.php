<?php

namespace App\Livewire\Backend\Study;

use App\Actions\Study\AuthorizeStudyCommunication;
use App\Helpers\StudyCalendar;
use App\Helpers\StudyPanelQuery;
use App\Helpers\StudyTrackingSettings;
use App\Livewire\Backend\Communications\CommunicationForm;
use App\Models\User;
use Illuminate\Http\Request;
use Livewire\Attributes\Locked;
use Symfony\Component\HttpKernel\Exception\HttpException;

class StudyCommunication extends CommunicationForm
{
    #[Locked]
    public array $recipients = [];

    #[Locked]
    public ?int $studentId = null;

    #[Locked]
    public array $filters = [];

    #[Locked]
    public string $weekStart;

    protected function authorizeComposition(bool $write = false): void
    {
        AuthorizeStudyCommunication::run($this->studentId ? User::findOrFail($this->studentId) : null);
        if ($write && $this->recipients !== []) {
            abort_unless(StudyPanelQuery::students()->whereIn('users.id', $this->recipients)->count() === count($this->recipients), 403);
        }
    }

    public function mount(?int $studentId = null, string $weekStart = '', array $filters = [], string $initialChannel = 'email'): void
    {
        $this->studentId = $studentId;
        parent::mount();
        abort_unless(in_array($initialChannel, ['email', 'push'], true), 422);
        $bounds = StudyCalendar::bounds($weekStart);
        abort_unless($weekStart === $bounds['start']->toDateString() && ! $bounds['start']->isFuture(), 422);
        $this->filters = StudyPanelQuery::parameters(Request::create('/', 'GET', $filters));
        $this->weekStart = $weekStart;
        $this->channel = $initialChannel;
        if (StudyTrackingSettings::enabled() && StudyTrackingSettings::startedOn()) {
            $this->recipients = $studentId ? [$studentId] : StudyPanelQuery::filtered($weekStart, $this->filters)->pluck('id')->map(fn ($id) => (int) $id)->all();
        }
        $this->step = 1;
    }

    public function start(): void
    {
        // Study selections are made by the authorized source table, never by browser-supplied IDs.
        abort(403);
    }

    public function next(): void
    {
        try {
            parent::next();
        } catch (HttpException $error) {
            if ($error->getStatusCode() !== 422) {
                throw $error;
            }
            $this->addError('communication', __('study.communication_unavailable'));
        }
    }

    public function render()
    {
        $this->authorizeComposition();
        $names = User::whereIn('id', array_slice($this->recipients, 0, 5))->get(['name', 'surname'])->map(fn ($user) => $user->display_name)->all();
        $returnUrl = $this->studentId ? route('admin.study.show', ['user' => $this->studentId, 'week' => $this->weekStart, 'lang' => app()->getLocale()]) : route('admin.study.index', ['week' => $this->weekStart, 'lang' => app()->getLocale()] + array_intersect_key($this->filters, array_flip(['state', 'status', 'search', 'sort', 'order'])));

        return parent::render()->with(['studyCompose' => true, 'studyNames' => $names, 'studyReturnUrl' => $returnUrl]);
    }
}
