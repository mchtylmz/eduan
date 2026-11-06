<?php

namespace App\Helpers\Sources;

use App\Models\SourceFile;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class SourceStorage
{
    public function cloud(SourceFile $source): FilesystemAdapter
    {
        $config = $source->storage_config;
        if (empty($config['key']) || empty($config['secret']) || empty($config['bucket']) || ! preg_match('/^https:\/\/[a-f0-9]{32}\.r2\.cloudflarestorage\.com$/D', $config['endpoint'] ?? '')) {
            throw new RuntimeException('Cloud storage configuration unavailable.');
        }

        return Storage::build($config);
    }

    public function uploadUrl(SourceFile $source): array
    {
        $upload = $this->cloud($source)->temporaryUploadUrl('pending/'.$source->code, now()->addMinutes(10), [
            'ContentType' => 'application/octet-stream', 'ContentDisposition' => 'attachment',
        ]);
        $upload['headers']['Content-Type'] = ['application/octet-stream'];

        return $upload;
    }

    public function verifyCloud(SourceFile $source): void
    {
        $disk = $this->cloud($source);
        $key = 'pending/'.$source->code;
        $head = $disk->getClient()->headObject(['Bucket' => $source->storage_config['bucket'], 'Key' => $key]);
        if ((int) $head['ContentLength'] !== $source->size || $source->size > SourceSettings::maxBytes()) {
            throw new RuntimeException('Unexpected object size.');
        }
        $object = $disk->getClient()->getObject(['Bucket' => $source->storage_config['bucket'], 'Key' => $key, 'IfMatch' => $head['ETag']]);
        $temporary = tmpfile();
        if (! $temporary) {
            throw new RuntimeException('Temporary validation storage unavailable.');
        }
        try {
            $body = $object['Body'];
            $size = 0;
            while (! $body->eof()) {
                $chunk = $body->read(1024 * 1024);
                $size += strlen($chunk);
                if ($size > $source->size || fwrite($temporary, $chunk) !== strlen($chunk)) {
                    throw new RuntimeException('Object validation failed.');
                }
            }
            $body->close();
            $file = new UploadedFile(stream_get_meta_data($temporary)['uri'], $source->original_name, null, null, true);
            SourceInput::verify($file, $source->extension, $source->size);
            // A presigned PUT can only overwrite the pending key, never the published object.
            $disk->getClient()->copyObject([
                'Bucket' => $source->storage_config['bucket'], 'Key' => $source->path,
                'CopySource' => $source->storage_config['bucket'].'/'.$key, 'CopySourceIfMatch' => $head['ETag'],
                'MetadataDirective' => 'REPLACE', 'ContentType' => $source->extension === 'html' ? 'application/octet-stream' : $source->mime,
                'ContentDisposition' => $source->extension === 'html' ? 'attachment' : 'inline',
                'CacheControl' => 'public, max-age=0, must-revalidate',
            ]);
            $disk->delete($key);
        } finally {
            fclose($temporary);
        }
    }

    public function storeLocal(SourceFile $source, UploadedFile $file): void
    {
        $disk = Storage::disk('public');
        $disk->makeDirectory('sources');
        // Static HTML originals download instead of running at the application origin.
        $rules = "Options -Indexes\n<FilesMatch \"\\.download$\">\nForceType application/octet-stream\n<IfModule mod_headers.c>\nHeader set Content-Disposition attachment\nHeader set X-Content-Type-Options nosniff\n</IfModule>\n</FilesMatch>\n";
        if (! $disk->put('sources/.htaccess', $rules)) {
            throw new RuntimeException('Static file protection unavailable.');
        }
        $stream = fopen($file->getRealPath(), 'rb');
        try {
            if (! $disk->put($source->path, $stream)) {
                throw new RuntimeException('File could not be stored.');
            }
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
    }

    public function remove(SourceFile $source): void
    {
        $disk = $source->disk === 'r2' ? $this->cloud($source) : Storage::disk('public');
        if (! $disk->delete($source->path)) {
            throw new RuntimeException('File could not be removed.');
        }
        if ($source->disk === 'r2') {
            $disk->delete('pending/'.$source->code);
        }
    }

    public function htmlStream(SourceFile $source)
    {
        return ($source->disk === 'r2' ? $this->cloud($source) : Storage::disk('public'))->readStream($source->path);
    }
}
