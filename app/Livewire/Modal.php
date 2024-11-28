<?php

namespace App\Livewire;

use Livewire\Attributes\On;
use Livewire\Component;

class Modal extends Component
{
    public string|array $component;
    public string $title;
    public array $data;
    public bool $show = false;
    public string $size;
    public array $sizes = [
        'sm',
        'md',
        'lg',
        'xl'
    ];

    protected $listeners = [
        'showModal' => 'showModal',
        'closeModal' => 'closeModal',
    ];

    public function showModal(string|array $component, array $data = []): void
    {
        $this->component = $component;

        $this->data = $data;
        $this->title = $data['title'] ?? '';
        $this->size = !empty($data['size']) && in_array($data['size'], $this->sizes) ? $data['size'] : 'xl';

        $this->show = true;
    }

    public function closeModal(): void
    {
        $this->show = false;

        $this->reset(['title', 'data', 'component', 'size']);
    }

    public function render()
    {
        return view('livewire.modal');
    }
}
