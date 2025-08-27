<?php

namespace App\Actions\Settings;

use App\Actions\Files\UploadFileAction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Lorisleiva\Actions\Concerns\AsAction;
use Intervention\Image\ImageManager;
use Exception;
use Intervention\Image\Laravel\Facades\Image;
use Illuminate\Support\Facades\Storage;

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

        if ($request->hasFile('files')) {
            $this->manifest();
        }

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
        foreach ([192, 256, 384, 512, 1024, 2048] as $width) {
            $icons[] = [
                "src" => 'icons/fav-'.$width.'.jpg',
                "sizes" => $width. 'x'. $width,
                "type" => "image/jpg",
                "purpose" => "any"
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
                // $favicon = ImageManager::imagick()->read($faviconPath);

                foreach ([192, 256, 384, 512, 1024, 2048] as $width) {
                    /*
                    $favicon->resize($width, $width)
                        //->encodeByMediaType(quality: 100)
                        ->encode('jpg', 100)
                        ->toJpg(quality: 100)
                        ->save(public_path('icons/fav-'.$width.'.jpg'));
                    */
                    $image = Image::read($faviconPath)->resize($width, $width);
                    Storage::disk('icons')->put(
                        'fav-'.$width.'.jpg',
                        $image->encodeByExtension('jpg', quality: 85)
                    );
                }

                $image = Image::read($faviconPath)->resize(192, 192);
                Storage::disk('icons')->put(
                    'manifest-icon-192.maskable.jpg',
                    $image->encodeByExtension('jpg', quality: 85)
                );

                $image = Image::read($faviconPath)->resize(512, 512);
                Storage::disk('icons')->put(
                    'manifest-icon-512.maskable.jpg',
                    $image->encodeByExtension('jpg', quality: 85)
                );
            }
        } catch (Exception $exception) {}
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
