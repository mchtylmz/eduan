<?php

namespace App\Enums;

use ArchTech\Enums\Comparable;
use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Names;
use ArchTech\Enums\Values;

enum VisibilityEnum: int
{
    use Names, Values, InvokableCases, Comparable;

    case LOGGED = 0;
    case PREMIUM = 1;

    public static function options(): array
    {
        return [
            self::LOGGED->value => __('Üyelere Özel'),
            self::PREMIUM->value => __('Premium Üyelere Özel'),
        ];
    }

    public function name(): string
    {
        return self::options()[$this->value] ?? '';
    }
}
