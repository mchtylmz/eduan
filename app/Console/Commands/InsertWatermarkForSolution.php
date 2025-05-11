<?php

namespace App\Console\Commands;

use App\Models\Question;
use Illuminate\Console\Command;
use Intervention\Image\ImageManager;

class InsertWatermarkForSolution extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:insert-watermark-for-solution';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     * @throws \Throwable
     */
    public function handle()
    {
        if (\App\Enums\StatusEnum::ACTIVE->value != settings()->solutionWatermark) {
            $this->fail(__('Filigran özelliği aktif değil!'));
        }

        $watermarkPath = public_path(settings()->solutionWatermarkLogo);
        if (!file_exists($watermarkPath) || !is_readable($watermarkPath)) {
            $this->fail(__('Filigran logo okunamadı!'));
        }

        $questions = Question::select(['id','solution'])->orderBy('id')->get();
        foreach ($questions as $question) {
            $solutionPath = public_path($question->solution);
            if (!file_exists($solutionPath) || !is_readable($solutionPath)) {
                $this->error(__('Çözüm resmi okunamadı, ') . $question->solution);
                continue;
            }

            $solutionPathExtension = pathinfo($solutionPath, PATHINFO_EXTENSION);
            $webpSolutionPath = str_replace(
                $solutionPathExtension,
                'webp',
                $solutionPath
            );

            try {
                $image = ImageManager::imagick()->read($solutionPath);
                $watermark = ImageManager::imagick()->read($watermarkPath);
                $watermark->scale($image->width() - intval($image->width() / 3));
                $watermark->rotate(45);

                $image->place($watermark, position: 'center', opacity: 10);
                $image->toWebp()->save($webpSolutionPath);

                $question->update([
                    'solution' => str_replace($solutionPathExtension, 'webp', $question->solution)
                ]);

                $this->comment(__('Filigran başarıyla uygulandı, ') . $question->solution);
            } catch (\Exception $exception) {
                $this->error($exception->getMessage());
            }

            usleep(500);
        }

    }
}
