<?php

namespace App\Enums;

use ArchTech\Enums\Comparable;
use ArchTech\Enums\Names;
use ArchTech\Enums\Values;

enum TestSectionTypeEnum: string
{
    use Names, Values, Comparable;

    case TOPIC = 'topic';
    case CONTENT = 'content';
    case PDF = 'pdf';
    case QUESTION = 'question';

    public static function options(): array
    {
        return [
            self::TOPIC->value => __('Konu'),
            self::CONTENT->value => __('İçerik'),
            self::PDF->value => __('PDF'),
            self::QUESTION->value => __('Soru'),
        ];
    }

    public function name(): string
    {
        return self::options()[$this->value] ?? '';
    }

    public function icon(): string
    {
        return match ($this->value) {
            self::TOPIC->value => 'fa fa-pencil',
            self::CONTENT->value => 'fa fa-keyboard',
            self::PDF->value => 'fa fa-file-pdf',
            self::QUESTION->value => 'fa fa-question-circle',
        };
    }
}
