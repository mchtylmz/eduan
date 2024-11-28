<?php

namespace App\Enums;

use ArchTech\Enums\Comparable;
use ArchTech\Enums\Names;
use ArchTech\Enums\Values;

enum PageTypeEnum: string
{
    use Names, Values, Comparable;

    case SYSTEM = 'system';
    case CUSTOM = 'custom';
}
