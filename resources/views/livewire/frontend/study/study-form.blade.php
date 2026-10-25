@php($panel = $this instanceof \App\Livewire\Backend\Study\StudyEditor)
<div>
    @error('study')<div role="alert" class="alert alert-warning">{{ $message }}</div>@enderror
    @if($notice)<div role="status" class="alert alert-success">{{ $notice }}</div>@endif
    @unless($enabled)
        <div class="alert alert-info">{{ __('study.disabled') }}</div>
    @else
        <div class="row g-4 mb-4">
            <div class="col-lg-6">
                <section class="{{ $panel ? 'block block-rounded border h-100' : 'member-form-card h-100' }}">
                    <header class="{{ $panel ? 'block-header block-header-default' : 'member-form-heading' }}"><div><h2 @class(['fs-5' => $panel])>{{ __($panel ? 'study.weekly_target_panel' : 'study.weekly_target') }}</h2><p>{{ __($panel ? 'study.target_help' : 'study.target_simple') }}</p></div></header>
                    <form id="study-target-form" wire:submit="saveTarget" class="{{ $panel ? 'block-content pb-3' : 'member-form-body' }}">
                        @if(! $targetBefore || ! collect($targetBefore)->contains(fn ($value) => $value !== null && $value > 0))<p class="text-muted">{{ __('study.no_target') }}</p>@endif
                        @if($targetDenial)<p class="alert alert-info">{{ $targetDenial }}</p>@endif
                        <fieldset @disabled($targetDenial) wire:loading.attr="disabled">
                            <div class="row g-3">
                                @foreach(['targetQuestions' => 'questions', 'targetPages' => 'pages', 'targetHours' => 'hours'] as $field => $label)
                                    <div class="col-sm-4">
                                        <label for="{{ $field }}" class="form-label">{{ __('study.'.$label) }}</label>
                                        <input id="{{ $field }}" type="text" inputmode="{{ $label === 'hours' ? 'decimal' : 'numeric' }}" class="form-control" wire:model="{{ $field }}" maxlength="20">
                                        <x-badge.error :field="$field" />
                                    </div>
                                @endforeach
                            </div>
                            <p class="small text-muted mt-3">{{ __($panel ? 'study.hours_help' : 'study.hours_simple') }}</p>
                            <button type="submit" class="{{ $panel ? 'btn btn-primary' : 'study-button' }}" wire:loading.attr="disabled">{{ __('study.save_target') }}</button>
                        </fieldset>
                    </form>
                </section>
            </div>
            <div class="col-lg-6">
                <section class="{{ $panel ? 'block block-rounded border h-100' : 'member-form-card h-100' }}">
                    <header class="{{ $panel ? 'block-header block-header-default' : 'member-form-heading' }}"><div><h2 @class(['fs-5' => $panel])>{{ $editingEntryId ? __('study.edit_activity') : __('study.add_activity') }}</h2><p>{{ __('study.activity_help') }}</p></div></header>
                    <form id="study-activity-form" wire:submit="saveActivity" class="{{ $panel ? 'block-content pb-3' : 'member-form-body' }}">
                        @if($activityDenial)<p class="alert alert-info">{{ $activityDenial }}</p>@endif
                        <fieldset @disabled($activityDenial) wire:loading.attr="disabled">
                            <label for="activityDate" class="form-label">{{ __('study.date') }}</label>
                            <input id="activityDate" type="date" class="form-control mb-3" min="{{ $minimumDate }}" max="{{ $maximumDate }}" wire:model="activityDate">
                            <x-badge.error field="activityDate" />
                            <div class="row g-3">
                                @foreach(['activityQuestions' => 'questions', 'activityPages' => 'pages', 'activityHours' => 'hours'] as $field => $label)
                                    <div class="col-sm-4">
                                        <label for="{{ $field }}" class="form-label">{{ __('study.'.$label) }}</label>
                                        <input id="{{ $field }}" type="text" inputmode="{{ $label === 'hours' ? 'decimal' : 'numeric' }}" class="form-control" wire:model="{{ $field }}" maxlength="20">
                                        <x-badge.error :field="$field" />
                                    </div>
                                @endforeach
                            </div>
                            <p class="small text-muted mt-3">{{ __($panel ? 'study.hours_help' : 'study.hours_simple') }}</p>
                            <div class="d-flex flex-wrap gap-2">
                                <button type="submit" class="{{ $panel ? 'btn btn-primary' : 'study-button' }}" wire:loading.attr="disabled">{{ $editingEntryId ? __('study.save') : __('study.add_activity') }}</button>
                                @if($editingEntryId)
                                    <button type="button" class="btn btn-outline-danger" wire:click="removeActivity" wire:confirm="{{ __('study.remove_confirm') }}" wire:loading.attr="disabled">{{ __('study.remove') }}</button>
                                @endif
                            </div>
                        </fieldset>
                        @if($editingEntryId)<button type="button" class="btn btn-link mt-2" wire:click="cancelEdit" wire:loading.attr="disabled">{{ __('study.cancel') }}</button>@endif
                    </form>
                </section>
            </div>
        </div>
        @unless($panel)
            <details class="study-details" @if($editingEntryId) open @endif>
                <summary>{{ __('study.daily_totals') }} <i class="fa fa-angle-down" aria-hidden="true"></i></summary>
        @endunless
        <section class="{{ $panel ? 'block block-rounded border mb-4' : 'member-form-card mb-4' }}">
            @if($panel)
            <header class="{{ $panel ? 'block-header block-header-default' : 'member-form-heading' }}"><div><h2 @class(['fs-5' => $panel])>{{ __('study.daily_totals') }}</h2><p>{{ __('study.combined_help') }}</p></div></header>
            @endif
            <div class="{{ $panel ? 'block-content pb-3' : 'member-form-body' }}">
                <div class="table-responsive">
                <table id="study-daily_totals" class="table align-middle">
                    <thead><tr><th scope="col">{{ __('study.date') }}</th><th scope="col">{{ __('study.manual_questions') }}</th><th scope="col">{{ __('study.automatic_tests') }}</th><th scope="col">{{ __('study.automatic_exams') }}</th><th scope="col">{{ __('study.questions') }}</th><th scope="col">{{ __('study.pages') }}</th><th scope="col">{{ __('study.hours') }}</th><th scope="col">{{ __('study.fragments') }}</th></tr></thead>
                    <tbody>
                        @forelse($days as $day)
                            <tr><th scope="row">{{ $panel ? \App\Helpers\StudyCalendar::day($day['activity_date'])->format('d/m/Y') : dateFormat($day['activity_date'], 'j F Y') }}</th><td>{{ $day['manual_questions'] }}</td><td>{{ $day['automatic_tests'] }}</td><td>{{ $day['automatic_exams'] }}</td><td>{{ $day['questions'] }}</td><td>{{ $day['pages'] }}</td><td>{{ \App\Helpers\StudyInput::hours((int) $day['minutes']) }}</td><td>{{ $day['fragments'] }}</td></tr>
                        @empty
                            <tr><td colspan="8" class="text-muted">{{ __('study.no_work') }}</td></tr>
                        @endforelse
                    </tbody>
                    @if($days->isNotEmpty())
                        <tfoot><tr><th scope="row">{{ __('study.week_total') }}</th><td>{{ $days->sum('manual_questions') }}</td><td>{{ $days->sum('automatic_tests') }}</td><td>{{ $days->sum('automatic_exams') }}</td><td>{{ $days->sum('questions') }}</td><td>{{ $days->sum('pages') }}</td><td>{{ \App\Helpers\StudyInput::hours((int) $days->sum('minutes')) }}</td><td>{{ $days->sum('fragments') }}</td></tr></tfoot>
                    @endif
                </table>
                </div>
            </div>
        </section>
        @unless($panel)</details>@endunless
        @unless($panel)
            <details class="study-details" @if($editingEntryId) open @endif>
                <summary>{{ __('study.entries') }} <i class="fa fa-angle-down" aria-hidden="true"></i></summary>
        @endunless
        <section class="{{ $panel ? 'block block-rounded border' : 'member-form-card' }}">
            @if($panel)
            <header class="{{ $panel ? 'block-header block-header-default' : 'member-form-heading' }}"><div><h2 @class(['fs-5' => $panel])>{{ __('study.entries') }}</h2><p>{{ __('study.fragments_help') }}</p></div></header>
            @endif
            <div class="{{ $panel ? 'block-content pb-3' : 'member-form-body' }}">
                <div class="table-responsive">
                    <table id="study-entries" class="table align-middle">
                        <thead><tr><th scope="col">{{ __('study.date') }}</th><th scope="col">{{ __('study.questions') }}</th><th scope="col">{{ __('study.pages') }}</th><th scope="col">{{ __('study.hours') }}</th><th scope="col">{{ __('study.actions') }}</th></tr></thead>
                        <tbody>
                            @forelse($entries as $entry)
                                <tr wire:key="study-entry-{{ $entry->id }}"><th scope="row">{{ $panel ? $entry->activity_date->format('d/m/Y') : dateFormat($entry->activity_date, 'j F Y') }}</th><td>{{ $entry->questions }}</td><td>{{ $entry->pages }}</td><td>{{ \App\Helpers\StudyInput::hours($entry->minutes) }}</td><td><button type="button" class="btn btn-sm btn-outline-secondary" wire:click="editActivity({{ $entry->id }})" @unless($panel) @click="document.getElementById('study-activity-form').scrollIntoView({behavior: 'smooth', block: 'start'})" @endunless wire:loading.attr="disabled" @disabled($activityDenial || $entry->activity_date->toDateString() < $minimumDate)>{{ __('study.edit') }}</button></td></tr>
                            @empty
                                <tr><td colspan="5" class="text-muted">{{ __('study.no_activity') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $panel ? $entries->links() : $entries->links(data: ['scrollTo' => '#study-entries']) }}
                <div wire:loading role="status" class="small text-muted">{{ __('study.processing') }}</div>
            </div>
        </section>
        @unless($panel)</details>@endunless
    @endunless
</div>
