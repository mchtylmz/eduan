<?php

namespace App\Livewire\Tests;

use App\Actions\Files\UploadFileAction;
use App\Traits\CustomLivewireAlert;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class SelectPdfModalForm extends Component
{
    use WithFileUploads, CustomLivewireAlert;

    public int $sectionIndex;
    public int $fieldIndex;
    public $file;

    public function mount(int $sectionIndex, int $fieldIndex): void
    {
        $this->sectionIndex = $sectionIndex;
        $this->fieldIndex = $fieldIndex;
    }

    public function save(): bool
    {
        $filename = '';
        if ($this->file instanceof TemporaryUploadedFile) {
            $filename = UploadFileAction::run(file: $this->file, folder: 'tests');
        }

        if (empty($filename)) {
            $this->message(__('Dosya yüklenemedi!'))->error();
            return false;
        }

        $this->dispatch(
            'selectPdf',
            sectionIndex: $this->sectionIndex,
            fieldIndex: $this->fieldIndex,
            filename: $filename
        );

        $this->dispatch('closeModal');
        return true;
    }

    public function render()
    {
        return view('livewire.backend.tests.select-pdf-modal-form');
    }
}
