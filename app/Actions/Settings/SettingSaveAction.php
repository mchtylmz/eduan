<?php

namespace App\Actions\Settings;

use App\Actions\Files\UploadFileAction;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Lorisleiva\Actions\Concerns\AsAction;
use Intervention\Image\ImageManager;
use Exception;

class SettingSaveAction
{
    use AsAction;

    protected array $data = [];

    public function handle(Request $request)
    {
        if ($request->hasFile('images')) {
            $this->images($request->file('images'));
        }
        if ($request->hasFile('files')) {
            $this->files($request->file('files'));
        }
        if ($request->get('settings')) {
            $this->settings($request->get('settings'));
        }

        settings()->set($this->data);
        settings()->save();

        $this->manifest();
        // TODO: log
    }

    protected function images(Array $images): void
    {
        foreach ($images as $key => $image) {
            if ($image = UploadFileAction::run(file: $image, folder: 'site')) {
                $this->data[$key] = $image;
            }
        }
    }

    protected function files(Array $files): void
    {
        foreach ($files as $key => $file) {
            if ($file = UploadFileAction::run(file: $file, folder: 'files')) {
                $this->data[$key] = $file;
            }
        }
    }

    protected function manifest(): void
    {
        $icons = [];
        foreach ([16, 32, 48, 57, 72, 96, 114, 128, 144, 152, 192, 256, 384, 512] as $width) {
            $icons[] = [
                "src" => 'icons/fav-'.$width.'.png',
                "sizes" => $width. 'x'. $width,
                "type" => "image/png"
            ];
        }

        $data = array_merge(config('manifest'), [
            'lang' => app()->getLocale() ?: config('app.locale'),
            'name' => settings()->appName ?: config('app.name'),
            'short_name' => settings()->appName ?: config('app.name'),
            'theme_color' => settings()->primaryColor ?: '#000',
            'background_color' => settings()->primaryColor ?: '#000',
            'description' => settings()->appName ?: config('app.name'),
            'icons' => array_merge(config('manifest.icons'), $icons),
        ]);

        File::put(
            public_path('pwa-manifest.json'),
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        $this->generateIcons();
    }

    protected function generateIcons(): void
    {
        try {
            if (!is_dir(public_path('icons'))) {
                mkdir(public_path('icons'), 0755, true);
            }

            if (file_exists($faviconPath = public_path(settings()->siteFavicon))) {
                $favicon = ImageManager::imagick()->read($faviconPath);

                foreach ([16, 32, 48, 57, 72, 96, 114, 128, 144, 152, 192, 256, 384, 512] as $width) {
                    $favicon->resize($width, $width)
                        ->toPng()
                        ->save(public_path('icons/fav-'.$width.'.png'));
                }

                $favicon->scale(192, 192)
                    ->toPng()
                    ->save(public_path('icons/manifest-icon-192.maskable.png'));

                $favicon->scale(512, 512)
                    ->toPng()
                    ->save(public_path('/icons/manifest-icon-512.maskable.png'));
            }
        } catch (Exception $exception) {}
    }

    protected function settings(array $settings): void
    {
        foreach ($settings as $key => $value) {
            $this->data[$key] = trim($value);
        }
    }
}
