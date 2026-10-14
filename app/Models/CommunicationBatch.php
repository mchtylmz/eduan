<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommunicationBatch extends Model
{
    protected $table = 'communication_batches';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['recipient_ids' => 'array'];
    }
}
