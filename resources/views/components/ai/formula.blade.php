@props(['formula'])
<div class="ai-formula overflow-auto py-1" wire:ignore wire:key="formula-{{ hash('sha256', $formula) }}"
     data-ai-latex="{{ $formula }}">{{ $formula }}</div>
