<div data-study-component="notes">
    @unless($enabled)<div class="alert alert-info">{{ __('study.disabled') }}</div>@else
        <div class="offcanvas offcanvas-end study-editor" tabindex="-1" id="study-panel-note" aria-labelledby="study-panel-note-title" wire:ignore.self>
            <div class="offcanvas-header"><h2 id="study-panel-note-title" class="offcanvas-title h5"><i class="fa fa-comment me-1"></i>{{ $editingId ? __('study.edit_note') : __('study.add_note') }}</h2><button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="{{ __('study.close') }}" wire:loading.attr="disabled"></button></div>
            <div class="offcanvas-body">
                @error('note')<p role="alert" class="alert alert-warning">{{ $message }}</p>@enderror
                <form wire:submit="save">
                    <label for="study-note-body" class="form-label">{{ __('study.note_body_panel') }}</label>
                    <textarea id="study-note-body" class="form-control" rows="7" maxlength="10000" placeholder="{{ __('study.note_placeholder_panel') }}" wire:model="body"></textarea>
                    <x-badge.error field="body" />
                    <label class="form-check mt-3"><input type="checkbox" class="form-check-input" wire:model="studentVisible"><span class="form-check-label">{{ __('study.visible_to_student') }}</span></label>
                    <p class="small text-muted">{{ __('study.notes_help') }}</p>
                    <div class="d-flex flex-wrap gap-2">
                        <button class="btn btn-primary" type="submit" wire:loading.attr="disabled"><i class="fa fa-check me-1"></i>{{ __('study.save') }}</button>
                        @if($editingId)<button class="btn btn-alt-danger" type="button" wire:click="remove" wire:confirm="{{ __('study.remove_note_confirm') }}" wire:loading.attr="disabled"><i class="fa fa-trash me-1"></i>{{ __('study.remove') }}</button>@endif
                        <button class="btn btn-alt-secondary" type="button" data-bs-dismiss="offcanvas" wire:loading.attr="disabled"><i class="fa fa-times me-1"></i>{{ __('study.cancel') }}</button>
                    </div>
                </form>
            </div>
        </div>
    @endunless
    @if($notice)<p role="status" class="alert alert-success">{{ $notice }}</p>@endif
</div>
