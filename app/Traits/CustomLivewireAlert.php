<?php

namespace App\Traits;

use Jantinnerezo\LivewireAlert\LivewireAlert;

trait CustomLivewireAlert
{
    use LivewireAlert;

    public array $alertOptions = [
        'text' => '',
        'position' => 'top-end',
        'timer' => 3000,
        'toast' => true,
        'timerProgressBar' => true,
        'showCloseButton' => true,
    ];

    public function message(string $message): static
    {
        $this->alertOptions['text'] = $message;

        return $this;
    }

    public function toast(bool $toast = true, string $position = 'top-end'): static
    {
        $this->alertOptions['toast'] = $toast;
        $this->alertOptions['position'] = $position;

        return $this;
    }

    public function error(): void
    {
        $this->alert(type: 'error', options: $this->alertOptions);
    }

    public function success(): void
    {
        $this->alert(type: 'success', options: $this->alertOptions);
    }
}
