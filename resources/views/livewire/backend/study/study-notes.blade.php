<section class="block block-rounded border">
    <div class="block-header block-header-default"><h2 class="block-title">{{ __('study.notes') }}</h2></div>
    <div class="block-content pb-3">
        @if($notice)<p role="status" class="alert alert-success">{{ $notice }}</p>@endif
        @error('note')<p role="alert" class="alert alert-warning">{{ $message }}</p>@enderror
        @unless($enabled)<p class="alert alert-info">{{ __('study.disabled') }}</p>@else
            @can('study:update')
                <form wire:submit="save" class="mb-4">
                    <label for="study-note-body" class="form-label">{{ $editingId ? __('study.edit_note') : __('study.add_note') }}</label>
                    <textarea id="study-note-body" class="form-control" rows="4" maxlength="10000" wire:model="body"></textarea>
                    <x-badge.error field="body" />
                    <label class="form-check mt-3"><input type="checkbox" class="form-check-input" wire:model="studentVisible"><span class="form-check-label">{{ __('study.visible_to_student') }}</span></label>
                    <p class="small text-muted">{{ __('study.notes_help') }}</p>
                    <div class="d-flex flex-wrap gap-2">
                        <button class="btn btn-primary" type="submit" wire:loading.attr="disabled">{{ __('study.save') }}</button>
                        @if($editingId)
                            <button class="btn btn-alt-danger" type="button" wire:click="remove" wire:confirm="{{ __('study.remove_note_confirm') }}" wire:loading.attr="disabled">{{ __('study.remove') }}</button>
                            <button class="btn btn-alt-secondary" type="button" wire:click="cancel" wire:loading.attr="disabled">{{ __('study.cancel') }}</button>
                        @endif
                    </div>
                </form>
            @endcan
            @forelse($notes as $note)
                <article class="border rounded p-3 mb-3" wire:key="study-note-{{ $note->id }}">
                    <div class="d-flex flex-wrap justify-content-between gap-2 mb-2">
                        <strong>{{ trim(($note->author?->name ?? '').' '.($note->author?->surname ?? '')) ?: __('study.unknown_author') }}</strong>
                        <span class="small text-muted">{{ dateFormat($note->created_at) }} · {{ $note->student_visible ? __('study.visible_to_student') : __('study.staff_only') }}</span>
                    </div>
                    <p class="mb-2 text-break" style="white-space: pre-wrap">{{ $note->body }}</p>
                    @if(auth()->user()->can('study:update') && (auth()->user()->can('user-type:admin') || $note->author_id === auth()->id()))
                        <button class="btn btn-sm btn-alt-secondary" type="button" wire:click="edit({{ $note->id }})" wire:loading.attr="disabled">{{ __('study.edit') }}</button>
                    @endif
                </article>
            @empty<p class="text-muted">{{ __('study.no_notes') }}</p>@endforelse
            {{ $notes->links() }}
        @endunless
    </div>
</section>
