<?php

namespace App\Traits;

use App\Actions\Auth\VerifyTurnstile;

trait VerifiesTurnstile
{
    public string $turnstileToken = '';

    protected function verifyTurnstile(string $action): void
    {
        $token = $this->turnstileToken;
        $this->turnstileToken = '';
        try {
            VerifyTurnstile::run($token, $action);
        } finally {
            $this->dispatch('turnstile-reset', instanceId: $this->getId());
        }
    }
}
