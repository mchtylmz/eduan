<div>
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <strong>{{ strtoupper($language->code) }}</strong>
            <span class="text-muted ms-2">{{ __('translation_manager.progress', $this->counts) }}</span>
        </div>
        <div class="text-md-end">
            <div class="fs-xs text-muted mb-2" role="status">
                @if($this->publishedFile['date'])
                    {{ __('translation_manager.file_saved', ['file' => $this->publishedFile['name'], 'date' => $this->publishedFile['date']]) }}
                @else
                    {{ __('translation_manager.file_missing', ['file' => $this->publishedFile['name']]) }}
                @endif
            </div>
            <div class="d-flex flex-wrap justify-content-md-end gap-2">
                <button type="button" wire:click="previewScan" wire:loading.attr="disabled" @disabled(!$this->catalogReady) class="btn btn-alt-primary">
                    <i class="fa fa-search me-1"></i> {{ __('translation_manager.scan') }}
                </button>
                <button type="button" wire:click="save" wire:loading.attr="disabled" class="btn btn-alt-success">
                    <i class="fa fa-save me-1"></i> {{ __('translation_manager.publish') }}
                </button>
            </div>
        </div>
    </div>
    <div class="alert alert-info fs-sm mb-3">{{ __('translation_manager.hint') }}</div>
    <div wire:loading class="text-primary mb-3"><i class="fa fa-spinner fa-pulse me-1"></i> {{ __('translation_manager.working') }}</div>
    @if(!$this->catalogReady)
        <div class="alert alert-warning">{{ __('translation_manager.migration_needed') }}</div>
    @elseif($this->previewSources !== null)
        <p class="fs-xs text-muted mb-3">{{ __('translation_manager.preview_catalog') }}</p>
    @endif
    @if($scanSummary)
        <div class="alert alert-success d-flex flex-wrap align-items-center justify-content-between gap-2">
            <span>{{ __('translation_manager.scan_summary', ['files' => $scanSummary['files'], 'keys' => $scanSummary['keys'], 'dynamic' => $scanSummary['dynamic']]) }}
                @if($scanSummary['applied']) {{ __('translation_manager.added', ['count' => $scanSummary['added']]) }} @endif
            </span>
            @if(!$scanSummary['applied'])
                <button type="button" wire:click="applyScan" wire:loading.attr="disabled" class="btn btn-sm btn-success">{{ __('translation_manager.apply') }}</button>
            @endif
        </div>
    @endif
    @error('scan') <div class="alert alert-danger">{{ $message }}</div> @enderror
    <div class="bg-body-light border rounded p-3 mb-3">
        <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
            <span class="fw-semibold fs-sm">{{ __('translation_manager.filters') }}</span>
            <button type="button" wire:click="resetFilters" wire:loading.attr="disabled" class="btn btn-sm btn-alt-secondary">
                <i class="fa fa-rotate-left me-1"></i> {{ __('translation_manager.clear_filters') }}
            </button>
        </div>
        <div class="row g-2">
            <div class="col-md-6">
                <label class="form-label" for="translation-original">{{ __('translation_manager.original') }}</label>
                <input id="translation-original" class="form-control w-100" wire:model.live.debounce.300ms="searchOriginal" placeholder="{{ __('translation_manager.search_original') }}">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="translation-value">{{ __('translation_manager.translation') }}</label>
                <input id="translation-value" class="form-control w-100" wire:model.live.debounce.300ms="searchTranslate" placeholder="{{ __('translation_manager.search_translation') }}">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="translation-surface">{{ __('translation_manager.surface') }}</label>
                <select id="translation-surface" class="form-select" wire:model.live="surface">
                    <option value="">{{ __('translation_manager.all') }}</option>
                    <option value="frontend">{{ __('translation_manager.frontend') }}</option>
                    <option value="backend">{{ __('translation_manager.backend') }}</option>
                    <option value="shared">{{ __('translation_manager.shared') }}</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="translation-group">{{ __('translation_manager.group') }}</label>
                <select id="translation-group" class="form-select" wire:model.live="group">
                    <option value="">{{ __('translation_manager.all') }}</option>
                    @foreach($this->groups as $name => $label) <option value="{{ $name }}">{{ $label }}</option> @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="translation-completion">{{ __('translation_manager.completion') }}</label>
                <select id="translation-completion" class="form-select" wire:model.live="completion">
                    <option value="">{{ __('translation_manager.all') }}</option>
                    <option value="empty">{{ __('translation_manager.empty') }}</option>
                    <option value="filled">{{ __('translation_manager.filled') }}</option>
                </select>
            </div>
        </div>
        <details class="mt-3 fs-sm" @if($sourceSearch !== '') open @endif>
            <summary class="text-muted">{{ __('translation_manager.source') }}</summary>
            <div class="mt-2">
                <label class="visually-hidden" for="translation-page">{{ __('translation_manager.source') }}</label>
                <input id="translation-page" class="form-control w-100" wire:model.live.debounce.300ms="sourceSearch" placeholder="{{ __('translation_manager.search_source') }}">
            </div>
        </details>
    </div>
    <div class="table-responsive">
        <table class="table table-bordered table-striped align-middle">
            <thead><tr><th style="width:40%">{{ __('translation_manager.original') }}</th><th>{{ __('translation_manager.translation') }}</th></tr></thead>
            <tbody>
            @forelse($this->rows as $row)
                @php($usages = $this->usages->get($row->key, collect()))
                <tr wire:key="translation-{{ $row->id }}">
                    <td>
                        <div class="fw-semibold text-break">{{ $usages->first()?->original ?? $row->key }}</div>
                        @if($usages->first()?->kind === 'php') <code class="text-break fs-xs">{{ $row->key }}</code> @endif
                        @if($usages->isNotEmpty())
                            <details class="fs-xs mt-2 text-muted">
                                <summary>{{ __('translation_manager.usages', ['count' => $usages->where('call', '!=', 'definition')->count()]) }}</summary>
                                @foreach($usages as $usage)
                                    <div class="border-top mt-1 pt-1 text-break">
                                        {{ $usage->file }}@if($usage->call !== 'definition'):{{ $usage->line }}@endif
                                        @if($usage->call === 'definition') <span>({{ __('translation_manager.definition') }})</span> @endif
                                        @foreach($usage->routes as $route) <div>{{ $route }}</div> @endforeach
                                    </div>
                                @endforeach
                            </details>
                        @else
                            <small class="text-muted">{{ __('translation_manager.unmapped') }}</small>
                        @endif
                    </td>
                    <td>
                        <label class="visually-hidden" for="translation-{{ $row->id }}">{{ $row->key }}</label>
                        <textarea id="translation-{{ $row->id }}" class="form-control" rows="2" wire:model.blur="translations.{{ $row->id }}.value" placeholder="{{ __('translation_manager.enter') }}"></textarea>
                        @error('translations.'.$row->id.'.value') <small class="text-danger">{{ $message }}</small> @enderror
                    </td>
                </tr>
            @empty
                <tr><td colspan="2" class="text-center text-muted py-4">{{ __('translation_manager.no_results') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $this->rows->links() }}
    @if($this->dynamicSources->isNotEmpty())
        <details class="mt-3 fs-sm">
            <summary>{{ __('translation_manager.dynamic_hint') }}</summary>
            @foreach($this->dynamicSources as $source)
                <div class="text-break text-muted mt-1">{{ $source->file }}:{{ $source->line }} — {{ $source->call }}</div>
            @endforeach
        </details>
    @endif
</div>
