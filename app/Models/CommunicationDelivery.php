<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommunicationDelivery extends Model
{
    protected $table = 'communication_deliveries';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['available_at' => 'immutable_datetime', 'claimed_at' => 'immutable_datetime', 'completed_at' => 'immutable_datetime'];
    }
}
