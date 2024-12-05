<?php

namespace App\Actions\Users;

use App\Enums\StatusEnum;
use App\Enums\YesNoEnum;
use App\Models\User;
use Lorisleiva\Actions\Concerns\AsAction;

class CreateOrUpdateUserAction
{
    use AsAction;

    public function handle(array $data, ?User $user = null): User
    {
        if (is_null($user)) {
            $user = User::create($data);
        } else {
            if (!empty($data['email_verified']) && YesNoEnum::NO->is($user->email_verified) && YesNoEnum::YES->is($data['email_verified'])) {
                $data['email_verified_at'] = now();
            }

            $user->update($data);
        }

       resetCache();

        return $user;
    }
}
