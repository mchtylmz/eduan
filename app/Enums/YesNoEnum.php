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
    case EMPTY = -1;

    public static function options(): array
    {
        return [
            self::YES->value => __('Evet'),
            self::NO->value => __('Hayır'),
            self::EMPTY->value => __('Boş'),
        ];
    }

    public function class(): string
    {
        return match ($this->value) {
            self::YES->value => 'success',
            self::NO->value => 'danger',
            self::EMPTY->value => 'warning'
        };
    }

    public function hidden(): string
    {
        return match ($this->value) {
            self::YES->value => '',
            self::NO->value => 'd-none',
            self::EMPTY->value => 'd-none'
        };
    }

    public function name(): string
    {
        return self::options()[$this->value] ?? '';
    }
}
