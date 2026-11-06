<?php

namespace App\Helpers\Sources;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class SourceInput
{
    public static function metadata(array $values): array
    {
        $data = Validator::make($values, ['title' => 'required|string|max:180', 'name' => 'required|string|max:255', 'size' => 'required|integer|min:1|max:'.SourceSettings::maxBytes()])->validate();
        $data['extension'] = strtolower(pathinfo($data['name'], PATHINFO_EXTENSION));
        if (! in_array($data['extension'], SourceSettings::types(), true) || preg_match('/[\x00-\x1f\\\\\/]/', $data['name'])) {
            throw ValidationException::withMessages(['file' => __('sources.invalid_file')]);
        }
        $data['mime'] = SourceSettings::TYPES[$data['extension']];

        return $data;
    }

    public static function verify(UploadedFile $file, string $extension, int $size): void
    {
        $rules = ['required', 'file', 'max:'.(SourceSettings::maxBytes() / 1024)];
        $rules[] = $extension === 'html' ? 'mimetypes:text/html,text/plain,application/xhtml+xml' : 'mimetypes:'.SourceSettings::TYPES[$extension];
        Validator::make(['file' => $file], ['file' => $rules])->validate();
        if ($file->getSize() !== $size || ! in_array($extension, SourceSettings::types(), true)) {
            throw ValidationException::withMessages(['file' => __('sources.invalid_file')]);
        }
        if ($extension === 'pdf' && file_get_contents($file->getRealPath(), false, null, 0, 5) !== '%PDF-') {
            throw ValidationException::withMessages(['file' => __('sources.invalid_file')]);
        }
        if (in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true) && ! @getimagesize($file->getRealPath())) {
            throw ValidationException::withMessages(['file' => __('sources.invalid_file')]);
        }
    }

    public static function returnPath(mixed $value): string
    {
        if (! is_string($value) || ! $value || strlen($value) > 2048) {
            return '/';
        }
        $decoded = rawurldecode($value);
        if (! str_starts_with($value, '/') || str_starts_with($decoded, '//') || preg_match('/[\x00-\x20\\\\]/', $decoded)) {
            return '/';
        }

        return $value;
    }
}
