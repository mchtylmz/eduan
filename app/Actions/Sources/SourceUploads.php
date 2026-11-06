<?php

namespace App\Actions\Sources;

use App\Actions\Auth\AuthorizePanelForm;
use App\Helpers\Sources\SourceInput;
use App\Helpers\Sources\SourceSettings;
use App\Helpers\Sources\SourceStorage;
use App\Models\SourceFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Throwable;

class SourceUploads
{
    public function __construct(private SourceStorage $storage) {}

    public function prepare(array $values): array
    {
        AuthorizePanelForm::run('files', 'add');
        $input = SourceInput::metadata($values);
        $code = (string) Str::uuid();
        $slug = Str::limit(Str::slug($input['title']), 150, '') ?: 'source';
        $path = 'sources/'.$code.'/'.$slug.'.'.$input['extension'].($input['extension'] === 'html' ? '.download' : '');
        $source = SourceFile::create([
            'code' => $code, 'slug' => $slug, 'title' => trim($input['title']), 'original_name' => $input['name'],
            'extension' => $input['extension'], 'mime' => $input['mime'], 'size' => (int) $input['size'],
            'disk' => SourceSettings::cloud() ? 'r2' : 'local', 'path' => $path,
            'storage_config' => SourceSettings::cloud() ? SourceSettings::cloudConfig() : null,
            'public_url' => SourceSettings::cloud() ? rtrim((string) settings()->sourceR2PublicUrl, '/').'/'.$path : null,
            'uploaded_by' => auth()->id(),
        ]);
        $result = ['code' => $code, 'disk' => $source->disk];
        if ($source->disk === 'r2') {
            try {
                $result['upload'] = $this->storage->uploadUrl($source);
            } catch (Throwable) {
                $source->update(['status' => 'failed']);
                $result['cloud_error'] = true;
            }
        }

        return $result;
    }

    public function finish(SourceFile $source, ?UploadedFile $file = null, bool $fallback = false): SourceFile
    {
        AuthorizePanelForm::run('files', 'add');
        abort_unless($source->uploaded_by === auth()->id(), 403);

        return Cache::store('security')->lock('source-upload:'.$source->code, 1200)->block(2, function () use ($source, $file, $fallback) {
            $source->refresh();
            abort_if($source->trashed(), 404);
            if ($source->status === 'ready') {
                return $source;
            }
            abort_unless(in_array($source->status, ['uploading', 'failed'], true), 409);
            if ($file) {
                abort_unless($source->disk === 'local' || $fallback, 422);
                try {
                    SourceInput::verify($file, $source->extension, $source->size);
                    $source->fill(['disk' => 'local', 'storage_config' => null, 'public_url' => null]);
                    $this->storage->storeLocal($source, $file);
                } catch (Throwable $error) {
                    $source->update(['status' => 'failed']);
                    throw $error;
                }
            } else {
                abort_unless($source->disk === 'r2', 422);
                try {
                    $this->storage->verifyCloud($source);
                } catch (Throwable) {
                    $source->update(['status' => 'failed']);
                    abort(422, __('sources.cloud_failed'));
                }
            }
            $source->fill(['status' => 'ready', 'ready_at' => now()])->save();

            return $source;
        });
    }

    public function delete(SourceFile $source): void
    {
        AuthorizePanelForm::run('files', 'delete');
        Cache::store('security')->lock('source-upload:'.$source->code, 1200)->block(2, function () use ($source) {
            $source->refresh();
            abort_if($source->trashed(), 404);
            $this->storage->remove($source);
            $source->delete();
        });
    }
}
