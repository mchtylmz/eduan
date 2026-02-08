<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class UploadBackupToCloudflare implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $files = array_merge(
            glob(storage_path('app/**/**/*.zip'), GLOB_BRACE),
            glob(storage_path('app/**/**/*.zip'), GLOB_BRACE),
            glob(storage_path('app/**/*.zip'), GLOB_BRACE),
            glob(storage_path('app/*.zip'), GLOB_BRACE),
            glob(base_path('logs/*'), GLOB_BRACE)
        );

        foreach ($files as $file) {
            if (!str_ends_with($file, '.zip') && !str_ends_with($file, '.gz')) continue;

            if (!file_exists($file)) continue;

            $uploadedFile = Storage::disk('s3')
                ->putFileAs(
                    date('Y-m'),
                    $file,
                    Str::slug(pathinfo($file, PATHINFO_FILENAME)) . '.' . pathinfo($file, PATHINFO_EXTENSION),
                    ['visibility' => 'public']
                );

            Log::channel('r2')->info('result', [
                'file' => $file,
                'uploaded' => $uploadedFile,
            ]);

            if (file_exists($file)) unlink($file);
        }
    }
}
