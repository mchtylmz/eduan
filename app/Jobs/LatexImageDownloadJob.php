<?php

namespace App\Jobs;

use App\Models\LatexImage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class LatexImageDownloadJob implements ShouldQueue
{
    use Queueable;

    public string $formula;

    /**
     * Create a new job instance.
     */
    public function __construct(string $formula)
    {
        $this->formula = $formula;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $imageName = 'latex/' . md5($this->formula) . '.svg';
        $url = "https://latex.codecogs.com/svg.download?" . urlencode($this->formula);

        if ($this->formula) {
            // Yeni indir ve kaydet
            $response = \Illuminate\Support\Facades\Http::get($url);

            if ($response->successful()) {
                \Illuminate\Support\Facades\Storage::disk('public')
                    ->put($imageName, $response->body());

                \App\Models\LatexImage::updateOrCreate(
                    [
                        'formula' => $this->formula
                    ],
                    [
                        'formula' => $this->formula,
                        'image'   => 'uploads/' . $imageName,
                    ]
                );
            } else {
                $this->fail($response->body());
            }
        }
    }
}
