<?php

namespace App\Enums;


use ArchTech\Enums\Comparable;
use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Meta\Meta;
use ArchTech\Enums\Metadata;
use ArchTech\Enums\Names;
use ArchTech\Enums\Options;
use ArchTech\Enums\Values;

enum StatusEnum: string
{
    use Names, Values, InvokableCases, Comparable;

    case ACTIVE = 'active';
    case PASSIVE = 'passive';

    public static function options(): array
    {
        return [
            self::ACTIVE->value => __('Aktif'),
            self::PASSIVE->value => __('Pasif'),
        ];
    }

    public function class(): string
    {
        return match ($this->value) {
            self::ACTIVE->value => 'success',
            self::PASSIVE->value => 'danger'
        };
    }

    public function name(): string
    {
        return self::options()[$this->value] ?? '';
    }
}
