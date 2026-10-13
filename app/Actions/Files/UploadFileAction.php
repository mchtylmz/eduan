<?php

namespace App\Actions\Files;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;

class UploadFileAction
{
    use AsAction;

    public function handle(UploadedFile $file, string $folder = '/', bool $keepName = true): ?string
    {
        abort_unless(auth()->user()?->can('dashboard:access'), 403);
        $typeRules = trim($folder, '/') === 'tests'
            ? ['mimes:pdf', 'extensions:pdf']
            : ['image', 'mimes:jpg,jpeg,png,webp,gif,bmp', 'extensions:jpg,jpeg,png,webp,gif,bmp'];
        Validator::make(['file' => $file], ['file' => ['required', 'file', 'max:10240', ...$typeRules]])->validate();
        $folder = $this->sanitizeFolder($folder);
        $filename = $this->generateFilename($file->getClientOriginalName(), $keepName, $file->guessExtension());

        if ($this->storeFile($file, $folder, $filename)) {
            return $this->generateFilePath($folder, $filename);
        }

        return null;
    }

    protected function sanitizeFolder(string $folder): string
    {
        abort_unless(in_array(trim($folder, '/'), ['site', 'questions', 'pages', 'blogs', 'tests', 'latex']), 422);

        return rtrim('/' . trim($folder, '/')) . '/';
    }

    protected function generateFilename(string $name, bool $keepName, string $extension): string
    {
        $baseName = $keepName ? Str::slug(pathinfo($name, PATHINFO_FILENAME)) : now()->format('YmdHi');
        $randomString = Str::random(24);

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
