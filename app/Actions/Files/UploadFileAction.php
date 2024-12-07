<?php

namespace App\Actions\Files;

use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;
use Illuminate\Http\UploadedFile;

class UploadFileAction
{
    use AsAction;

    public function handle(UploadedFile $file, string $folder = '/', bool $keepName = true): ?string
    {
        $folder = $this->sanitizeFolder($folder);
        $filename = $this->generateFilename($file->getClientOriginalName(), $keepName);

        if ($this->storeFile($file, $folder, $filename)) {
            return $this->generateFilePath($folder, $filename);
        }

        return null;
    }

    protected function sanitizeFolder(string $folder): string
    {
        return rtrim('/' . trim($folder, '/')) . '/';
    }

    protected function generateFilename(string $name, bool $keepName): string
    {
        $baseName = $keepName ? Str::slug(pathinfo($name, PATHINFO_FILENAME)) : now()->format('YmdHi');
        $randomString = Str::random(24);

        $extension = pathinfo($name, PATHINFO_EXTENSION);
        if (in_array($extension, ['png', 'jpg', 'jpeg', 'webp'])) {
            $extension = 'webp';
        }

        return sprintf('%s-%s.%s', $baseName, $randomString, $extension);
    }

    protected function storeFile(UploadedFile $file, string $folder, string $filename): bool
    {
        return $file->storeAs($folder, $filename, 'public');
    }

    protected function generateFilePath(string $folder, string $filename): string
    {
        return sprintf('uploads%s%s', $folder, $filename);
    }
}
