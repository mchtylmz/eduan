<?php

namespace App\Livewire\Ai;

use App\Actions\Files\UploadFileAction;
use App\Models\LatexImage;
use App\Traits\CustomLivewireAlert;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class LatexImageForm extends Component
{
    use WithFileUploads, CustomLivewireAlert;

    public LatexImage $latexImage;
    public $image;

    public function rules(): array
    {
        return [
            'image' => 'nullable|image',
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'image' => __('Görsel'),
        ];
    }

    public function save()
    {
        $this->validate();

        $data = [];
        if ($this->image instanceof TemporaryUploadedFile) {
            $data['image'] = UploadFileAction::run(file: $this->image, folder: 'latex');
        }

        if (empty($data)) {
            $this->message(__('Latex görseli yüklenemedi!'))->error();
            return false;
        }

        $this->latexImage->update($data);

        return redirect()->route('admin.ai.images')->with([
            'status' => 'success',
            'message' => __('Latex görseli kayıt edildi!')
        ]);
    }

    public function render()
    {
        return view('livewire.backend.ai.latex-image-form');
    }
}
