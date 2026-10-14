<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebPushSubscription extends Model
{
    protected $table = 'web_push_subscriptions';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['consented_at' => 'immutable_datetime', 'verified_at' => 'immutable_datetime', 'revoked_at' => 'immutable_datetime', 'provider_revoked_at' => 'immutable_datetime'];
    }
}
