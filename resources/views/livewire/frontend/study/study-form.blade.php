<div>
    @error('study')<div role="alert" class="alert alert-warning">{{ $message }}</div>@enderror
    @if($notice)<div role="status" class="alert alert-success">{{ $notice }}</div>@endif
    @unless($enabled)
        <div class="alert alert-info">{{ __('study.disabled') }}</div>
    @else
        <div class="row g-4 mb-4">
            <div class="col-lg-6">
                <section class="member-form-card h-100">
                    <header class="member-form-heading"><div><h2>{{ __('study.weekly_target') }}</h2><p>{{ __('study.target_help') }}</p></div></header>
                    <form wire:submit="saveTarget" class="member-form-body">
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
                            <p class="small text-muted mt-3">{{ __('study.hours_help') }}</p>
                            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">{{ __('study.save_target') }}</button>
                        </fieldset>
                    </form>
                </section>
            </div>
            <div class="col-lg-6">
                <section class="member-form-card h-100">
                    <header class="member-form-heading"><div><h2>{{ $editingEntryId ? __('study.edit_activity') : __('study.add_activity') }}</h2><p>{{ __('study.activity_help') }}</p></div></header>
                    <form wire:submit="saveActivity" class="member-form-body">
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
                            <p class="small text-muted mt-3">{{ __('study.hours_help') }}</p>
                            <div class="d-flex flex-wrap gap-2">
                                <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">{{ $editingEntryId ? __('study.save') : __('study.add_activity') }}</button>
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
        <section class="member-form-card mb-4">
            <header class="member-form-heading"><div><h2>{{ __('study.daily_totals') }}</h2><p>{{ __('study.combined_help') }}</p></div></header>
            <div class="member-form-body table-responsive">
                <table class="table align-middle">
                    <thead><tr><th scope="col">{{ __('study.date') }}</th><th scope="col">{{ __('study.manual_questions') }}</th><th scope="col">{{ __('study.automatic_tests') }}</th><th scope="col">{{ __('study.automatic_exams') }}</th><th scope="col">{{ __('study.questions') }}</th><th scope="col">{{ __('study.pages') }}</th><th scope="col">{{ __('study.hours') }}</th><th scope="col">{{ __('study.fragments') }}</th></tr></thead>
                    <tbody>
                        @forelse($days as $day)
                            <tr><th scope="row">{{ \App\Helpers\StudyCalendar::day($day['activity_date'])->format('d/m/Y') }}</th><td>{{ $day['manual_questions'] }}</td><td>{{ $day['automatic_tests'] }}</td><td>{{ $day['automatic_exams'] }}</td><td>{{ $day['questions'] }}</td><td>{{ $day['pages'] }}</td><td>{{ \App\Helpers\StudyInput::hours((int) $day['minutes']) }}</td><td>{{ $day['fragments'] }}</td></tr>
                        @empty
                            <tr><td colspan="8" class="text-muted">{{ __('study.no_work') }}</td></tr>
                        @endforelse
                    </tbody>
                    @if($days->isNotEmpty())
                        <tfoot><tr><th scope="row">{{ __('study.week_total') }}</th><td>{{ $days->sum('manual_questions') }}</td><td>{{ $days->sum('automatic_tests') }}</td><td>{{ $days->sum('automatic_exams') }}</td><td>{{ $days->sum('questions') }}</td><td>{{ $days->sum('pages') }}</td><td>{{ \App\Helpers\StudyInput::hours((int) $days->sum('minutes')) }}</td><td>{{ $days->sum('fragments') }}</td></tr></tfoot>
                    @endif
                </table>
            </div>
        </section>
        <section class="member-form-card">
            <header class="member-form-heading"><div><h2>{{ __('study.entries') }}</h2><p>{{ __('study.fragments_help') }}</p></div></header>
            <div class="member-form-body">
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead><tr><th scope="col">{{ __('study.date') }}</th><th scope="col">{{ __('study.questions') }}</th><th scope="col">{{ __('study.pages') }}</th><th scope="col">{{ __('study.hours') }}</th><th scope="col">{{ __('study.actions') }}</th></tr></thead>
                        <tbody>
                            @forelse($entries as $entry)
                                <tr wire:key="study-entry-{{ $entry->id }}"><th scope="row">{{ $entry->activity_date->format('d/m/Y') }}</th><td>{{ $entry->questions }}</td><td>{{ $entry->pages }}</td><td>{{ \App\Helpers\StudyInput::hours($entry->minutes) }}</td><td><button type="button" class="btn btn-sm btn-outline-secondary" wire:click="editActivity({{ $entry->id }})" wire:loading.attr="disabled" @disabled($activityDenial || $entry->activity_date->toDateString() < $minimumDate)>{{ __('study.edit') }}</button></td></tr>
                            @empty
                                <tr><td colspan="5" class="text-muted">{{ __('study.no_activity') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $entries->links() }}
                <div wire:loading role="status" class="small text-muted">{{ __('study.processing') }}</div>
            </div>
        </section>
    @endunless
</div>
