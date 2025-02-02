<?php

namespace App\Enums;

use ArchTech\Enums\Comparable;
use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Names;
use ArchTech\Enums\Values;

enum ReviewVisibilityEnum: string
{
    use Names, Values, InvokableCases, Comparable;

    case HIDE = 'hide';
    case PUBLIC = 'public';
    case PRIVATE = 'private';

    public static function options(): array
    {
        return [
            self::HIDE->value => __('Gizli'),
            self::PUBLIC->value => __('Herkese Açık'),
            self::PRIVATE->value => __('Üyelere Özel'),
        ];
    }

    public function name(): string
    {
        return self::options()[$this->value] ?? '';
    }
}
