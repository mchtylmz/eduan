<?php

namespace App\Actions\Users;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;

class UpdatePasswordAction
{
    use AsAction;

    public function handle(User $user, string|null $password = null): bool
    {
        if (!$password) {
            $password = Str::random(6);
        }

        return $user->update([
            'password' => Hash::make($password)
        ]);
    }
}
