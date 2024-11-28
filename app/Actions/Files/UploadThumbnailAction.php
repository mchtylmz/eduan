<?php

namespace App\Actions\Files;

use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;
use Spatie\Image\Enums\Fit;
use Spatie\Image\Image;

class UploadThumbnailAction
{
    use AsAction;

    public function handle(string $path, string $folder = '/', bool $keepName = true): ?string
    {
        if (!file_exists($path)) {
            return null;
        }

        $folder = $this->prepareFolder($folder);
        $this->createDirectoryIfNotExists(public_path('uploads/' . $folder));

        $filename = $this->generateFilename($path, $keepName);
        $savedImage = $this->resizeAndSaveImage($path, $folder, $filename);

        return $savedImage ? 'uploads' . $folder . $filename : null;
    }

    protected function prepareFolder(string $folder): string
    {
        return rtrim('/' . trim($folder, '/')) . '/thumbnail/';
    }

    protected function createDirectoryIfNotExists(string $directoryPath): void
    {
        if (!file_exists($directoryPath)) {
            mkdir($directoryPath, 0777, true);
        }
    }

    protected function generateFilename(string $path, bool $keepName = true): string
    {
        $name = $keepName ? Str::slug(pathinfo($path, PATHINFO_FILENAME)) : now()->format('YmdHi');

        return sprintf('%s-thumbnail-%s.webp', $name, Str::random());
    }

    protected function resizeAndSaveImage(string $path, string $folder, string $filename): ?Image
    {
        return Image::load($path)
            ->fit(
                fit: Fit::Crop,
                desiredWidth: settings()->thumbnailHeight ?? 640,
                desiredHeight: settings()->thumbnailHeight ?? 400
            )
            ->quality(85)
            ->optimize()
            ->save(public_path('uploads' . $folder) . $filename);
    }
}
