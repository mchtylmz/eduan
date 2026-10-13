<?php

namespace App\Livewire\Ai;

use App\Helpers\LearningTable;
use Livewire\Component;

class AnswerAiTable extends Component
{
    public function render()
    {
        LearningTable::access('ai');

        return view('livewire.backend.ai.server-table', LearningTable::options());
    }
}
