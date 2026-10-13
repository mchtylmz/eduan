<?php

namespace App\Livewire\Ai;

use App\Helpers\LearningTable;
use App\Models\LatexImage;
use App\Traits\CustomLivewireAlert;
use Livewire\Component;

class LatexImagesTable extends Component
{
    use CustomLivewireAlert;

    public function mount(): void
    {
        LearningTable::access('ai');
    }

    public function delete(int $id): bool
    {
        LearningTable::access('ai');
        abort_unless(auth()->user()->can('ai:delete'), 403);
        $deleted = LatexImage::findOrFail($id)->delete();
        $this->message(__('Latex görseli başarıyla silindi!'))->success();
        $this->dispatch('refresh-server-table', table: 'ai-latex-images');

        return $deleted;
    }

    public function render()
    {
        LearningTable::access('ai');

        return view('livewire.backend.ai.latex-images-server-table');
    }
}
