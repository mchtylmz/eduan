<?php

namespace App\Enums;

use ArchTech\Enums\Comparable;
use ArchTech\Enums\Names;
use ArchTech\Enums\Values;

enum RoleTypeEnum: string
{
    use Names, Values, Comparable;
    case ADMIN = 'user-type:admin';
    case USER = 'user-type:user';

    public static function options(): array
    {
        return [
            self::ADMIN->value => __('Yönetici'),
            self::USER->value => __('Kullanıcı'),
        ];
    }

    public function name(): string
    {
        return self::options()[$this->value] ?? '';
    }

    public function icon(): string
    {
        return match ($this->value) {
            self::ADMIN->value => 'fa fa-shield-alt',
            self::USER->value => 'fa fa-user',
        };
    }
}
