<div data-study-component="editor">
    @unless($enabled)<div class="alert alert-info">{{ __('study.disabled') }}</div>@else
        @foreach(['target', 'activity'] as $kind)
            <div class="offcanvas offcanvas-end study-editor" tabindex="-1" id="study-panel-{{ $kind }}" aria-labelledby="study-panel-{{ $kind }}-title" wire:ignore.self>
                <div class="offcanvas-header"><h2 id="study-panel-{{ $kind }}-title" class="offcanvas-title h5"><i class="fa {{ $kind === 'target' ? 'fa-bullseye' : 'fa-pen' }} me-1"></i>{{ $kind === 'target' ? __('study.edit_target_panel') : ($editingEntryId ? __('study.edit_activity') : __('study.add_activity')) }}</h2><button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="{{ __('study.close') }}" wire:loading.attr="disabled"></button></div>
                <div class="offcanvas-body">
                    <p class="small text-muted">{{ $kind === 'target' ? __('study.target_simple') : __('study.activity_help') }}</p>
                    @error('study')<div role="alert" class="alert alert-warning">{{ $message }}</div>@enderror
                    @php
                        $denial = $kind === 'target' ? $targetDenial : $activityDenial;
                        $prefix = $kind === 'target' ? 'target' : 'activity';
                    @endphp
                    @if($denial)<p class="alert alert-info">{{ $denial }}</p>@endif
                    <form class="study-editor-form" wire:submit="{{ $kind === 'target' ? 'saveTarget' : 'saveActivity' }}">
                        <fieldset @disabled($denial) wire:loading.attr="disabled">
                            @if($kind === 'activity')
                                <label for="activityDate" class="form-label">{{ __('study.date') }}</label>
                                <div class="mb-3" wire:ignore x-data="{
                                    date: $wire.entangle('activityDate'),
                                    picker: null,
                                    init() {
                                        this.picker = flatpickr(this.$refs.date, {
                                            locale: @js(app()->getLocale()),
                                            disableMobile: true,
                                            allowInput: false,
                                            clickOpens: false,
                                            static: true,
                                            dateFormat: 'j F Y',
                                            ariaDateFormat: 'j F Y',
                                            defaultDate: flatpickr.parseDate(this.date, 'Y-m-d'),
                                            minDate: flatpickr.parseDate(@js($minimumDate), 'Y-m-d'),
                                            maxDate: flatpickr.parseDate(@js($maximumDate), 'Y-m-d'),
                                            onChange: dates => this.date = dates.length ? flatpickr.formatDate(dates[0], 'Y-m-d') : ''
                                        });
                                        this.$watch('date', value => this.picker.setDate(value, false, 'Y-m-d'));
                                    },
                                    destroy() { this.picker?.destroy(); }
                                }">
                                    <input x-ref="date" id="activityDate" type="text" class="form-control" placeholder="{{ __('study.placeholder_date') }}" autocomplete="off" inputmode="none" @click="picker.open()" @keydown.enter.prevent="picker.open()" @keydown.arrow-down.prevent="picker.open()">
                                </div>

                                <x-badge.error field="activityDate" />
                            @endif
                            @foreach(['Questions' => 'questions', 'Pages' => 'pages', 'Hours' => 'hours'] as $suffix => $label)
                                <div class="mb-3"><label class="form-label" for="{{ $prefix.$suffix }}">{{ __('study.'.$label) }}</label><input id="{{ $prefix.$suffix }}" type="text" inputmode="{{ $label === 'hours' ? 'decimal' : 'numeric' }}" class="form-control" placeholder="{{ __('study.placeholder_'.$label) }}" maxlength="20" wire:model="{{ $prefix.$suffix }}"><x-badge.error :field="$prefix.$suffix" /></div>
                            @endforeach
                            <p class="small text-muted">{{ __('study.hours_simple') }}</p>
                            <div class="d-flex gap-2 flex-wrap"><button class="btn btn-primary" type="submit" wire:loading.attr="disabled"><i class="fa fa-check me-1"></i>{{ $kind === 'target' ? __('study.save_target') : ($editingEntryId ? __('study.save') : __('study.add_activity')) }}</button>
                                @if($kind === 'activity' && $editingEntryId)<button class="btn btn-alt-danger" type="button" wire:click="removeActivity" wire:confirm="{{ __('study.remove_confirm') }}" wire:loading.attr="disabled"><i class="fa fa-trash me-1"></i>{{ __('study.remove') }}</button>@endif
                                <button class="btn btn-alt-secondary" type="button" data-bs-dismiss="offcanvas" wire:loading.attr="disabled"><i class="fa fa-times me-1"></i>{{ __('study.cancel') }}</button>
                            </div>
                        </fieldset>
                    </form>
                </div>
            </div>
        @endforeach
    @endunless
    @if($notice)<div role="status" class="alert alert-success">{{ $notice }}</div>@endif
</div>
