<x-jet-dialog-modal wire:model="viewingHistoryModal">
    <x-slot name="title">
        @lang('History Properties')
    </x-slot>

    <x-slot name="content">
        <div wire:replace class="space-y-4">
            {{-- Display history properties Here using $currentHistory --}}
        </div>
    </x-slot>

    <x-slot name="footer">
        <x-jet-secondary-button wire:click="resetHistoryModal" wire:loading.attr="disabled">
            @lang('Done')
        </x-jet-secondary-button>
    </x-slot>
</x-jet-dialog-modal>
