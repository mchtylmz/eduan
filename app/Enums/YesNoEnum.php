<?php

namespace App\Enums;

use ArchTech\Enums\Comparable;
use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Names;
use ArchTech\Enums\Values;

enum YesNoEnum: int
{
    use Names, Values, InvokableCases, Comparable;

    case YES = 1;
    case NO = 0;

    public static function options(): array
    {
        return [
            self::YES->value => __('Evet'),
            self::NO->value => __('Hayır'),
        ];
    }

    public function class(): string
    {
        return match ($this->value) {
            self::YES->value => 'success',
            self::NO->value => 'danger'
        };
    }

    public function hidden(): string
    {
        return match ($this->value) {
            self::YES->value => '',
            self::NO->value => 'd-none'
        };
    }

    public function name(): string
    {
        return self::options()[$this->value] ?? '';
    }
}
