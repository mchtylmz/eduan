<?php

namespace App\Actions\Settings;

use App\Models\UserType;
use Lorisleiva\Actions\Concerns\AsAction;

class BulkSaveUserTypeAction
{
    use AsAction;

    protected array $counts = [
        'created' => 0,
        'updated' => 0
    ];

    public function handle(array $types): array
    {
        foreach ($types as $type) {
            if ($id = data_get($type, 'id', false)) {
                UserType::find($id)->update($type);
                $this->counts['updated']++;
            } else {
                UserType::create($type);
                $this->counts['created']++;
            }
        }

        cache()->flush();

        return $this->counts;
    }
}
