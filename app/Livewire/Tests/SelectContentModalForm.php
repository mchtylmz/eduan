<?php

namespace App\Livewire\Tests;

use Livewire\Component;

class SelectContentModalForm extends Component
{
    public int $sectionIndex;
    public int $fieldIndex;
    public string $content = '';

    public function mount(int $sectionIndex, int $fieldIndex, string $content = ''): void
    {
        $this->sectionIndex = $sectionIndex;
        $this->fieldIndex = $fieldIndex;
        $this->content = $content;
    }

    public function save(): void
    {
        $this->dispatch(
            'selectContent',
            sectionIndex: $this->sectionIndex,
            fieldIndex: $this->fieldIndex,
            content: $this->content
        );
    }

    public function render()
    {
        return view('livewire.backend.tests.select-content-modal-form');
    }
}
