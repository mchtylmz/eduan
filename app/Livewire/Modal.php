<?php

namespace App\Livewire;

use App\Actions\Users\AuthorizeUserMail;
use App\Traits\AuthorizesPanelOverlay;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Modal extends Component
{
    use AuthorizesPanelOverlay;

    #[Locked]
    public string|array $component;

    public string $title;

    #[Locked]
    public array $data;

    public bool $show = false;

    public string $size;

    public array $sizes = [
        'sm',
        'md',
        'lg',
        'xl',
    ];

    protected $listeners = [
        'showModal' => 'showModal',
        'closeModal' => 'closeModal',
        'stats-mail-ready' => 'openStatsMail',
    ];

    public function showModal(string|array $component, array $data = []): void
    {
        $this->authorizeOverlay($component, 'modal');
        $this->component = $component;

        $this->data = $data;
        $this->title = $data['title'] ?? '';
        $this->size = ! empty($data['size']) && in_array($data['size'], $this->sizes) ? $data['size'] : 'xl';

        $this->show = true;
    }

    public function openStatsMail(array $emails): void
    {
        AuthorizeUserMail::run(studentIds: array_column($emails, 'user_id'));
        $this->showModal('stats.send-email-form', ['title' => __('Kullanıcılara Mail Gönder'), 'emails' => $emails]);
        $this->dispatch('stats-mail-opened');
    }

    public function closeModal(): void
    {
        $this->show = false;

        $this->reset(['title', 'data', 'component', 'size']);
    }

    public function render()
    {
        if ($this->show) {
            $this->authorizeOverlay($this->component, 'modal');
        }

        return view('livewire.modal');
    }
}
