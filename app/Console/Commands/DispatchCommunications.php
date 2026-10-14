<?php

namespace App\Console\Commands;

use App\Jobs\DeliverCommunication;
use App\Models\CommunicationDelivery;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DispatchCommunications extends Command
{
    protected $signature = 'communications:dispatch';

    protected $description = 'Queue up to five email and five push deliveries per minute.';

    public function handle(): int
    {
        if (config('queue.default') === 'sync') {
            $this->error('An asynchronous queue connection is required.');

            return self::FAILURE;
        }
        $lock = Cache::lock('communications:dispatch', 60);
        if (! $lock->get()) {
            return self::SUCCESS;
        }
        try {
            foreach (['email', 'push'] as $channel) {
                // A repeated invocation in the same minute cannot create a second five-item batch.
                $slot = 'communications:slot:'.$channel.':'.now()->format('YmdHi');
                if (! Cache::add($slot, true, 120)) {
                    continue;
                }
                DB::transaction(function () use ($channel) {
                    $rows = CommunicationDelivery::where('channel', $channel)->where('status', 'pending')->where('available_at', '<=', now())->orderBy('id')->limit(5)->lockForUpdate()->get();
                    foreach ($rows as $row) {
                        $row->update(['status' => 'scheduled', 'claimed_at' => now()]);
                        DeliverCommunication::dispatch($row->id)->afterCommit();
                    }
                });
            }
        } finally {
            $lock->release();
        }

        return self::SUCCESS;
    }
}
