<?php

namespace Tests\Feature;

use App\Actions\Settings\SettingSaveAction;
use App\Enums\StatusEnum;
use App\Models\Language;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PwaReleaseTest extends TestCase
{
    use DatabaseTransactions;

    public function test_manifest_and_ios_metadata_are_present_off_home_and_outside_chrome(): void
    {
        settings()->set('siteFavicon', 'icons/fav-512.jpg');
        settings()->save();
        foreach (['nl', 'en', 'tr'] as $locale) {
            Language::firstOrCreate(['code' => $locale], ['name' => $locale, 'status' => StatusEnum::ACTIVE]);
            $this->withHeader('User-Agent', 'Mozilla/5.0 (iPhone) AppleWebKit/605.1.15 Version/18.0 Mobile Safari/604.1')
                ->get(route('login', ['lang' => $locale]))->assertOk()
                ->assertSee('rel="manifest"', false)
                ->assertSee('apple-mobile-web-app-status-bar-style" content="default"', false)
                ->assertSee('apple-mobile-web-app-capable" content="yes"', false)
                ->assertSee('icons/apple-touch-icon.png', false)
                ->assertSee(__('pwa.offline_message', [], $locale));
        }
    }

    public function test_bundled_manifest_icons_match_actual_image_types_and_dimensions(): void
    {
        $manifest = json_decode(File::get(public_path('pwa-manifest.json')), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame('0123456789', $manifest['id']);
        $purposes = [];
        foreach ($manifest['icons'] as $icon) {
            $image = getimagesize(public_path($icon['src']));
            $this->assertSame($image['mime'], $icon['type']);
            $this->assertSame($image[0].'x'.$image[1], $icon['sizes']);
            $purposes[] = $icon['purpose'];
        }
        $this->assertContains('maskable', $purposes);
        $this->assertContains('any', $purposes);
        $this->assertSame([180, 180], array_slice(getimagesize(public_path('icons/apple-touch-icon.png')), 0, 2));
    }

    public function test_regeneration_preserves_logo_aspect_ratio_and_maskable_safe_zone(): void
    {
        $this->withTemporaryPublicPath(function (string $path) {
            $source = imagecreatetruecolor(320, 160);
            imagefill($source, 0, 0, imagecolorallocate($source, 255, 0, 0));
            imagepng($source, $path.'/source.png');
            settings()->set('siteFavicon', 'source.png');
            settings()->save();
            $this->action()->publish();
            $manifest = json_decode(File::get($path.'/pwa-manifest.json'), true, flags: JSON_THROW_ON_ERROR);
            $this->assertSame(config('manifest.id'), $manifest['id']);
            foreach ($manifest['icons'] as $icon) {
                $this->assertFileExists($path.'/'.$icon['src']);
                $this->assertSame(getimagesize($path.'/'.$icon['src'])['mime'], $icon['type']);
            }
            $mask = imagecreatefrompng($path.'/icons/manifest-icon-512.maskable.png');
            $minX = $minY = 512;
            $maxX = $maxY = 0;
            $maxRadius = 0;
            for ($y = 0; $y < 512; $y++) {
                for ($x = 0; $x < 512; $x++) {
                    $color = imagecolorat($mask, $x, $y);
                    if (($color & 0x00FFFFFF) === 0xFF0000) {
                        $maxRadius = max($maxRadius, hypot($x - 255.5, $y - 255.5));
                        $minX = min($minX, $x);
                        $maxX = max($maxX, $x);
                        $minY = min($minY, $y);
                        $maxY = max($maxY, $y);
                    }
                }
            }
            $this->assertLessThan(512, $minX);
            $this->assertLessThanOrEqual(204.8, $maxRadius);
            $this->assertEqualsWithDelta(2, ($maxX - $minX + 1) / ($maxY - $minY + 1), 0.03);
            $this->assertSame(0xFFFFFF, imagecolorat($mask, 0, 0) & 0x00FFFFFF);
            $this->assertFileExists($path.'/icons/apple-touch-icon.png');
        });
    }

    public function test_generation_failure_is_reported_without_publishing_invalid_manifest(): void
    {
        Exceptions::fake();
        $this->withTemporaryPublicPath(function (string $path) {
            File::put($path.'/pwa-manifest.json', '{"id":"existing-app"}');
            settings()->set('siteFavicon', 'missing.png');
            settings()->save();
            try {
                $this->action()->publish();
                $this->fail('Missing favicon must not be reported as a successful generation.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('images.siteFavicon', $exception->errors());
            }
            $this->assertSame('{"id":"existing-app"}', File::get($path.'/pwa-manifest.json'));
            Exceptions::assertReported(\RuntimeException::class);
        });
    }

    public function test_failed_icon_write_keeps_manifest_and_returns_validation_error(): void
    {
        Exceptions::fake();
        $source = public_path('icons/fav-192.jpg');
        $this->withTemporaryPublicPath(function (string $path) use ($source) {
            File::copy($source, $path.'/source.jpg');
            File::put($path.'/pwa-manifest.json', '{"id":"existing-app"}');
            settings()->set('siteFavicon', 'source.jpg');
            settings()->save();
            $storage = Storage::getFacadeRoot();
            $disk = \Mockery::mock(FilesystemAdapter::class);
            $disk->shouldReceive('put')->once()->andReturn(false);
            Storage::shouldReceive('disk')->with('icons')->once()->andReturn($disk);
            try {
                $this->action()->publish();
                $this->fail('Failed icon write must not publish a new manifest.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('images.siteFavicon', $exception->errors());
            } finally {
                Storage::swap($storage);
            }
            $this->assertSame('{"id":"existing-app"}', File::get($path.'/pwa-manifest.json'));
            Exceptions::assertReported(\RuntimeException::class);
        });
    }

    private function action(): SettingSaveAction
    {
        return new class extends SettingSaveAction
        {
            public function publish(): void
            {
                $this->manifest();
            }
        };
    }

    public function test_manifest_write_failure_preserves_existing_manifest(): void
    {
        Exceptions::fake();
        $source = public_path('icons/fav-192.jpg');
        $this->withTemporaryPublicPath(function (string $path) use ($source) {
            File::copy($source, $path.'/source.jpg');
            File::put($path.'/pwa-manifest.json', '{"id":"existing-app"}');
            settings()->set('siteFavicon', 'source.jpg');
            settings()->save();
            $filesystem = File::getFacadeRoot();
            File::partialMock()->shouldReceive('replace')->once()->andThrow(new \RuntimeException('Manifest storage is unavailable.'));
            try {
                $this->action()->publish();
                $this->fail('Failed manifest replacement must not return success.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('images.siteFavicon', $exception->errors());
            } finally {
                File::swap($filesystem);
            }
            $this->assertSame('{"id":"existing-app"}', File::get($path.'/pwa-manifest.json'));
            Exceptions::assertReported(\RuntimeException::class);
        });
    }

    private function withTemporaryPublicPath(callable $callback): void
    {
        $original = public_path();
        $path = sys_get_temp_dir().'/eduan-pwa-'.bin2hex(random_bytes(8));
        File::makeDirectory($path, 0700, true);
        $this->app->usePublicPath($path);
        config(['filesystems.disks.icons.root' => $path.'/icons']);
        Storage::forgetDisk('icons');
        try {
            $callback($path);
        } finally {
            $this->app->usePublicPath($original);
            Storage::forgetDisk('icons');
            File::deleteDirectory($path);
        }
    }
}
