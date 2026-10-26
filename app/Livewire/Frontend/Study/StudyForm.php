<?php

namespace App\Livewire\Frontend\Study;

use App\Actions\Study\AuthorizeStudyAccess;
use App\Actions\Study\AuthorizeStudyChange;
use App\Actions\Study\RemoveStudyActivity;
use App\Actions\Study\SaveStudyActivity;
use App\Actions\Study\SaveStudyTarget;
use App\Actions\Study\StudyWrite;
use App\Helpers\StudyAutomaticWork;
use App\Helpers\StudyCalendar;
use App\Helpers\StudyDailyTotals;
use App\Helpers\StudyInput;
use App\Helpers\StudyTrackingSettings;
use App\Models\StudyActivityEntry;
use App\Models\StudyWeeklyTarget;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpKernel\Exception\HttpException;

class StudyForm extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    #[Locked]
    public string $weekStart;

    #[Locked]
    public ?int $editingEntryId = null;

    #[Locked]
    public ?array $targetBefore = null;

    #[Locked]
    public ?array $editingBefore = null;

    #[Locked]
    public string $submissionKey;

    public $targetQuestions = '';

    public $targetPages = '';

    public $targetHours = '';

    public $activityDate = '';

    public $activityQuestions = '';

    public $activityPages = '';

    public $activityHours = '';

    #[Locked]
    public string $notice = '';

    protected function owner(): User
    {
        return auth()->user();
    }

    protected function reportComponent(): string
    {
        return StudyReport::class;
    }

    public function mount(string $weekStart): void
    {
        AuthorizeStudyAccess::run($this->owner());
        $bounds = StudyCalendar::bounds($weekStart);
        abort_unless($bounds['start']->toDateString() === $weekStart && ! $bounds['start']->isFuture(), 422);
        $this->weekStart = $weekStart;
        $this->submissionKey = (string) Str::uuid();
        $this->defaultDate();
        if (StudyTrackingSettings::enabled() && StudyTrackingSettings::startedOn()) {
            $this->loadTarget();
        }
    }

    protected function loadTarget(): void
    {
        $target = StudyWeeklyTarget::query()->where('user_id', $this->owner()->id)
            ->whereHas('week', fn ($query) => $query->where('starts_on', $this->weekStart))->first();
        $this->targetBefore = $target ? StudyWrite::snapshot($target) : null;
        $this->targetQuestions = $target?->questions ?? '';
        $this->targetPages = $target?->pages ?? '';
        $this->targetHours = StudyInput::hours($target?->minutes);
    }

    private function defaultDate(): void
    {
        $bounds = StudyCalendar::bounds($this->weekStart);
        $this->activityDate = min(StudyCalendar::day(now())->toDateString(), $bounds['end']->subDay()->toDateString());
    }

    private function attempt(callable $callback, array $fields = []): bool
    {
        $this->resetErrorBag();
        $this->notice = '';
        AuthorizeStudyAccess::run($this->owner());
        try {
            $callback();

            return true;
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $field => $messages) {
                $this->addError($fields[$field] ?? $field, $messages[0]);
            }
        } catch (HttpException $exception) {
            // Permission failures remain 403; only named business-rule failures are shown inline.
            if (! in_array($exception->getStatusCode(), [403, 409, 422], true) || $exception->getMessage() === '') {
                throw $exception;
            }
            $this->addError('study', $exception->getMessage());
        }

        return false;
    }

    #[On('study-open-editor')]
    public function openEditor(string $kind): void
    {
        AuthorizeStudyAccess::run($this->owner());
        abort_unless(in_array($kind, ['target', 'activity'], true), 422);
        if ($kind === 'activity' && $this->editingEntryId) {
            $this->clearEditor();
        }
        $this->resetErrorBag();
        $this->notice = '';
        $this->dispatch('study-editor-opened', kind: $kind);
    }

    public function saveTarget(): void
    {
        if ($this->attempt(function () {
            SaveStudyTarget::run($this->owner(), $this->weekStart, [
                'questions' => $this->targetQuestions, 'pages' => $this->targetPages, 'hours' => $this->targetHours,
            ], $this->targetBefore);
            $this->loadTarget();
        }, ['questions' => 'targetQuestions', 'pages' => 'targetPages', 'hours' => 'targetHours'])) {
            $this->notice = __('study.target_saved');
            $this->dispatch('study-changed')->to($this->reportComponent());
            $this->dispatch('study-editor-saved', kind: 'target');
        }
    }

    public function saveActivity(): void
    {
        if ($this->attempt(function () {
            Validator::make(['date' => $this->activityDate], ['date' => 'required|date_format:Y-m-d'])->validate();
            $bounds = StudyCalendar::bounds($this->weekStart);
            $date = StudyCalendar::day($this->activityDate);
            abort_unless($date->greaterThanOrEqualTo($bounds['start']) && $date->lessThan($bounds['end']), 422, __('study.outside_week'));
            SaveStudyActivity::run($this->owner(), $this->activityDate, [
                'questions' => $this->activityQuestions, 'pages' => $this->activityPages, 'hours' => $this->activityHours,
            ], $this->submissionKey, $this->editingEntryId, $this->editingBefore);
            $this->clearEditor();
            $this->resetPage();
        }, ['date' => 'activityDate', 'questions' => 'activityQuestions', 'pages' => 'activityPages', 'hours' => 'activityHours'])) {
            $this->notice = __('study.activity_saved');
            $this->dispatch('study-changed')->to($this->reportComponent());
            $this->dispatch('study-editor-saved', kind: 'activity');
        }
    }

    private function entry(int $id): StudyActivityEntry
    {
        AuthorizeStudyAccess::run($this->owner());
        $bounds = StudyCalendar::bounds($this->weekStart);

        return StudyActivityEntry::query()->where('user_id', $this->owner()->id)->whereKey($id)
            ->where('activity_date', '>=', $bounds['start']->toDateString())
            ->where('activity_date', '<', $bounds['end']->toDateString())->firstOrFail();
    }

    public function editActivity(int $id): void
    {
        if ($this->attempt(function () use ($id) {
            $entry = $this->entry($id);
            AuthorizeStudyChange::run($this->owner(), 'activity', $entry->activity_date->toDateString());
            $this->editingEntryId = $entry->id;
            $this->editingBefore = StudyWrite::snapshot($entry);
            $this->activityDate = $entry->activity_date->toDateString();
            $this->activityQuestions = $entry->questions;
            $this->activityPages = $entry->pages;
            $this->activityHours = StudyInput::hours($entry->minutes);
        })) {
            $this->dispatch('study-editor-opened', kind: 'activity');
        }
    }

    public function removeActivity(): void
    {
        if ($this->attempt(function () {
            abort_unless($this->editingEntryId && $this->editingBefore, 422);
            $this->entry($this->editingEntryId);
            RemoveStudyActivity::run($this->owner(), $this->editingEntryId, $this->editingBefore);
            $this->clearEditor();
            $this->resetPage();
        })) {
            $this->notice = __('study.activity_removed');
            $this->dispatch('study-changed')->to($this->reportComponent());
            $this->dispatch('study-editor-saved', kind: 'activity');
        }
    }

    public function cancelEdit(): void
    {
        AuthorizeStudyAccess::run($this->owner());
        $this->clearEditor();
        $this->resetErrorBag();
        $this->notice = '';
        $this->dispatch('study-editor-saved', kind: 'activity');
    }

    private function clearEditor(): void
    {
        $this->editingEntryId = null;
        $this->editingBefore = null;
        $this->activityQuestions = $this->activityPages = $this->activityHours = '';
        $this->submissionKey = (string) Str::uuid();
        $this->defaultDate();
    }

    private function denial(string $kind, string $date): ?string
    {
        try {
            AuthorizeStudyChange::run($this->owner(), $kind, $date);

            return null;
        } catch (HttpException $exception) {
            if ($exception->getMessage() === '') {
                throw $exception;
            }

            return $exception->getMessage();
        }
    }

    protected function includesActivityLists(): bool
    {
        return true;
    }

    public function render()
    {
        AuthorizeStudyAccess::run($this->owner());
        $enabled = StudyTrackingSettings::enabled() && StudyTrackingSettings::startedOn();
        $bounds = StudyCalendar::bounds($this->weekStart);
        $today = StudyCalendar::day(now())->toDateString();
        $minimumDate = max($this->weekStart, StudyTrackingSettings::startedOn() ?? $this->weekStart);
        $maximumDate = min($today, $bounds['end']->subDay()->toDateString());
        $targetDenial = $this->denial('target', $this->weekStart);
        $activityDenial = $this->denial('activity', $maximumDate);
        $entries = null;
        $days = collect();
        if ($enabled && $this->includesActivityLists()) {
            $query = StudyActivityEntry::query()->where('user_id', $this->owner()->id)
                ->where('activity_date', '>=', $this->weekStart)->where('activity_date', '<', $bounds['end']->toDateString());
            $days = (clone $query)->selectRaw('activity_date, COUNT(*) AS fragments, SUM(questions) AS questions, SUM(pages) AS pages, SUM(minutes) AS minutes')
                ->groupBy('activity_date')->orderBy('activity_date')->get();
            $manual = $days->map(fn ($day) => ['activity_date' => $day->activity_date->toDateString(),
                'manual_questions' => (int) $day->questions, 'pages' => (int) $day->pages,
                'minutes' => (int) $day->minutes, 'fragments' => (int) $day->fragments])->all();
            $days = collect(StudyDailyTotals::merge($manual, StudyAutomaticWork::daily($this->owner(), $this->weekStart)));
            $entries = $query->orderByDesc('activity_date')->orderByDesc('id')->paginate(15);
        }

        return view('livewire.frontend.study.study-form', compact('enabled', 'bounds', 'minimumDate', 'maximumDate', 'targetDenial', 'activityDenial', 'entries', 'days'));
    }
}
