<?php

namespace App\Actions\Settings;

use App\Actions\Files\UploadFileAction;
use App\Actions\Notifications\PrepareNotificationSettings;
use App\Actions\Study\PrepareStudyTrackingSettings;
use App\Helpers\AiSolutionRequest;
use App\Helpers\FrontendCache;
use App\Helpers\PanelGuide;
use App\Helpers\PwaIcons;
use App\Helpers\StudyTrackingSettings;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;
use RuntimeException;

class SettingSaveAction
{
    use AsAction;

    protected array $data = [];

    public function handle(Request $request)
    {
        abort_unless($request->user()?->can('settings:update') && $request->user()?->can('dashboard:access'), 403);
        if ($request->has('settings.panelGuideUrl')) {
            abort_unless(PanelGuide::visibleTo($request->user()), 403);
        }
        $requestedModel = $request->input('settings.gptModel');
        $modelForEfforts = is_string($requestedModel) && $requestedModel !== '' ? $requestedModel : (settings()->gptModel ?: 'gpt-6-luna');
        $request->validate([
            'images' => 'sometimes|array',
            'images.*' => 'file|image|mimes:jpg,jpeg,png,webp,gif,bmp|extensions:jpg,jpeg,png,webp,gif,bmp|max:10240',
            'files' => 'prohibited',
            'settings' => 'sometimes|array',
            'settings.*' => 'nullable|string',
            'settings.panelGuideUrl' => ['nullable', 'url:http,https', 'max:2048'],
            'settings.gptModel' => ['sometimes', 'required', Rule::in(array_keys(aiHelper()->gptLists()))],
            'settings.gptEffect' => ['sometimes', 'required', Rule::in(AiSolutionRequest::efforts($modelForEfforts))],
        ]);
        if ($request->has('settings.gptModel') && ! in_array($request->input('settings.gptEffect') ?: (settings()->gptEffect ?: 'low'), AiSolutionRequest::efforts($request->input('settings.gptModel')), true)) {
            throw ValidationException::withMessages(['settings.gptEffect' => __('validation.in', ['attribute' => __('Yapa Zeka Reasoning effort')])]);
        }
        $values = PrepareSourceSettings::run(PrepareTurnstileSettings::run(PrepareNotificationSettings::run(PrepareStudyTrackingSettings::run($request->input('settings', [])))));
        if (array_key_exists('panelGuideUrl', $values)) {
            $values['panelGuideUrl'] = PanelGuide::url($values['panelGuideUrl']) ?? '';
        }
        if ($request->hasFile('images')) {
            $this->images($request->file('images'));
        }
        if ($values) {
            $this->settings($values);
        }

        $save = function () {
            settings()->set($this->data);
            settings()->save();
        };
        if (StudyTrackingSettings::contains($values)) {
            DB::transaction($save);
        } else {
            $save();
        }
        FrontendCache::invalidate('settings', 'sitemap');

        if ($request->hasFile('images.siteFavicon')) {
            $this->manifest();
        }

        // TODO: log
    }

    protected function images(array $images): void
    {
        foreach ($images as $key => $image) {
            if ($image = UploadFileAction::run(file: $image, folder: 'site')) {
                $this->data[$key] = $image;
            }
        }
    }

    protected function manifest(): void
    {
        // Do not publish a manifest referencing icons that failed to generate.
        $this->generateIcons();
        $icons = [];
        foreach ([192, 256, 384, 512, 1024, 2048] as $width) {
            $icons[] = [
                'src' => 'icons/fav-'.$width.'.jpg',
                'sizes' => $width.'x'.$width,
                'type' => 'image/jpeg',
                'purpose' => 'any',
            ];
        }

        $data = array_merge(config('manifest'), [
            'lang' => settings()->defaultLocale ?: config('app.locale'),
            'name' => settings()->appName ?: config('app.name'),
            'short_name' => settings()->appName ?: config('app.name'),
            'theme_color' => settings()->primaryColor ?: '#000',
            'background_color' => settings()->primaryColor ?: '#000',
            'description' => settings()->appName ?: config('app.name'),
            'icons' => array_merge(config('manifest.icons'), $icons),
        ]);

        try {
            File::replace(
                public_path('pwa-manifest.json'),
                json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            );
        } catch (Exception $exception) {
            report($exception);
            throw ValidationException::withMessages(['images.siteFavicon' => __('pwa.icons_failed')]);
        }
    }

    protected function generateIcons(): void
    {
        try {
            if (! is_dir(public_path('icons'))) {
                mkdir(public_path('icons'), 0755, true);
            }

            $faviconPath = public_path(settings()->siteFavicon ?? '');
            if (! is_file($faviconPath) || ! is_readable($faviconPath)) {
                throw new RuntimeException('PWA favicon source is unavailable.');
            }
            foreach ([192, 256, 384, 512, 1024, 2048] as $width) {
                $this->writeIcon('fav-'.$width.'.jpg', PwaIcons::image($faviconPath, $width)->toJpeg(quality: 85));
            }
            foreach ([192, 512] as $width) {
                $this->writeIcon('manifest-icon-'.$width.'.maskable.png', PwaIcons::image($faviconPath, $width, true)->toPng());
            }
            $this->writeIcon('apple-touch-icon.png', PwaIcons::image($faviconPath, 180)->toPng());
        } catch (Exception $exception) {
            report($exception);
            throw ValidationException::withMessages(['images.siteFavicon' => __('pwa.icons_failed')]);
        }
    }

    private function writeIcon(string $name, string $contents): void
    {
        if (! Storage::disk('icons')->put($name, $contents)) {
            throw new RuntimeException('PWA icon could not be written: '.$name);
        }
    }

    protected function settings(array $settings): void
    {
        foreach ($settings as $key => $value) {
            if ($key == 'gptLimitCount' && $oldValue = settings()->gptLimitCount) {
                User::where('gpt_limit', $oldValue)->update(['gpt_limit' => $value]);
            }

            $this->data[$key] = trim($value);
        }
    }
}
