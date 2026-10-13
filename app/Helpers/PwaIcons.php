<?php

namespace App\Helpers;

use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface;

class PwaIcons
{
    public static function image(string $source, int $size, bool $maskable = false): ImageInterface
    {
        // Even a square logo's corners fit inside the 40%-radius maskable safe zone.
        $contentSize = $maskable ? (int) floor($size * 0.56) : $size;

        $manager = new ImageManager(
            driver: config('image.driver'),
            autoOrientation: config('image.options.autoOrientation', true),
            decodeAnimation: config('image.options.decodeAnimation', true),
            blendingColor: config('image.options.blendingColor', 'ffffff'),
            strip: config('image.options.strip', false),
        );

        return $manager->create($size, $size)->fill('ffffff')
            ->place($manager->read($source)->scale($contentSize, $contentSize), 'center');
    }
}
