<div>
    @if($notes && $notes->total())
        <section class="member-form-card mb-4">
            <header class="member-form-heading"><div><h2>{{ __('study.notes') }}</h2></div></header>
            <div class="member-form-body">
                @foreach($notes as $note)
                    <article class="border rounded p-3 mb-3" wire:key="visible-note-{{ $note->id }}">
                        <strong>{{ trim(($note->author?->name ?? '').' '.($note->author?->surname ?? '')) ?: __('study.unknown_author') }}</strong>
                        <div class="small text-muted mb-2">{{ dateFormat($note->created_at) }}</div>
                        <p class="mb-0 text-break" style="white-space: pre-wrap">{{ $note->body }}</p>
                    </article>
                @endforeach
                {{ $notes->links() }}
            </div>
        </section>
    @else
        <div class="member-form-card p-4"><h2 class="h5">{{ __('study.notes') }}</h2><p class="text-muted mb-0">{{ __('study.no_notes') }}</p></div>
    @endif
</div>
