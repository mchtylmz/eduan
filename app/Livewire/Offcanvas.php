<?php

namespace App\Livewire;

use Livewire\Component;

class Offcanvas extends Component
{
    public string|array $component;
    public string $title;
    public array $data;
    public bool $show = false;
    public string $position;

    protected $listeners = [
        'showOffcanvas' => 'showOffcanvas',
        'closeOffcanvas' => 'closeOffcanvas',
    ];

    public function showOffcanvas(string|array $component, array $data = []): void
    {
        $this->component = $component;

        $this->data = $data;
        $this->title = $data['title'] ?? '';

        $this->position = 'right';
        if (!empty($data['position']) && in_array($data['position'], ['top', 'left', 'md-left', 'right', 'md-right', 'bottom'])) {
            $this->position = $data['position'];
        }

        $this->show = true;
    }

    public function closeOffcanvas(): void
    {
        $this->show = false;

        $this->reset(['title', 'data', 'component', 'position']);
    }

    public function render()
    {
        return view('livewire.offcanvas');
    }
}
