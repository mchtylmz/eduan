<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class UploadCloudflare extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:upload-cloudflare';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        \App\Jobs\UploadBackupToCloudflare::dispatchSync();
    }
}
