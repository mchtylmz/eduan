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

    case TEACHER = 'user-type:teacher';

    public static function options(): array
    {
        return [
            self::ADMIN->value => __('Yönetici'),
            self::USER->value => __('Kullanıcı'),
            self::TEACHER->value => __('Öğretmen/Okul'),
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
            self::TEACHER->value => 'fa fa-graduation-cap',
        };
    }
}
