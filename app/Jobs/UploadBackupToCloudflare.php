<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
        foreach (Storage::files(config('app.name')) as $file) {
            if (!str_ends_with($file, '.zip')) continue;

            $filename = str_replace(
                [config('app.name'), '.zip'], '', $file
            );

            $uploadedFile = Storage::disk('s3')
                ->putFileAs(date('Y-m'), storage_path('app/private/' . $file), Str::slug($filename));

            if ($uploadedFile) {
                unlink(storage_path('app/private/' . $file));
            }
        }
    }
}
