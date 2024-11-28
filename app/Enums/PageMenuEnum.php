<?php

namespace App\Enums;

use ArchTech\Enums\Comparable;
use ArchTech\Enums\Names;
use ArchTech\Enums\Values;

enum PageMenuEnum: string
{
    use Names, Values, Comparable;

    case HOME = 'home';
    case HEADER = 'header';
    case FOOTER = 'footer';

    public static function options(): array
    {
        return [
            self::HOME->value => __('Anasayfa'),
            self::HEADER->value => __('Üst Menü'),
            self::FOOTER->value => __('Alt Menü'),
        ];
    }

    public function name(): string
    {
        return self::options()[$this->value] ?? '';
    }
}
