<?php

namespace App\Actions\Settings;

use App\Helpers\PanelGuide;
use App\Helpers\Sources\SourceSettings;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;

class PrepareSourceSettings
{
    use AsAction;

    public function handle(array $values): array
    {
        $keys = array_filter(array_keys($values), fn ($key) => str_starts_with($key, 'source'));
        if (! $keys) {
            return $values;
        }
        abort_unless(PanelGuide::visibleTo(auth()->user()), 403);
        foreach ($keys as $key) {
            if (! in_array($key, SourceSettings::KEYS, true)) {
                throw ValidationException::withMessages(['settings.'.$key => __('sources.invalid_settings')]);
            }
        }
        Validator::make($values, [
            'sourceStorage' => 'sometimes|required|in:local,r2', 'sourceMaxMb' => 'sometimes|required|integer|between:1,100',
            'sourceTypes' => ['sometimes', 'required', 'regex:/^(pdf|html|jpe?g|png|webp)(,(pdf|html|jpe?g|png|webp))*$/D'],
            'sourceR2Account' => ['nullable', 'regex:/^[a-f0-9]{32}$/D'],
            'sourceR2Bucket' => ['nullable', 'regex:/^[a-z0-9][a-z0-9.-]{1,61}[a-z0-9]$/D'],
            'sourceR2Key' => ['nullable', 'regex:/^[a-zA-Z0-9_-]{1,200}$/D'],
            'sourceR2Secret' => ['nullable', 'string', 'max:300'],
            'sourceR2PublicUrl' => ['nullable', 'url:https', 'max:1000'],
        ])->validate();
        $secret = trim($values['sourceR2Secret'] ?? '');
        if (array_key_exists('sourceR2Secret', $values)) {
            unset($values['sourceR2Secret']);
            if ($secret !== '') {
                $values['sourceR2Secret'] = $secret;
            }
        }
        if (($values['sourceStorage'] ?? settings()->sourceStorage) === 'r2') {
            foreach (['sourceR2Account', 'sourceR2Bucket', 'sourceR2Key', 'sourceR2PublicUrl'] as $key) {
                if (! trim((string) ($values[$key] ?? settings()->{$key}))) {
                    throw ValidationException::withMessages(['settings.'.$key => __('sources.cloud_required')]);
                }
            }
            if ($secret === '' && SourceSettings::secret() === '') {
                throw ValidationException::withMessages(['settings.sourceR2Secret' => __('sources.cloud_required')]);
            }
        }

        return $values;
    }
}
