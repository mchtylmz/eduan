<?php

namespace App\Enums;

use ArchTech\Enums\Comparable;
use ArchTech\Enums\Names;
use ArchTech\Enums\Values;

enum SettingsTabsEnum: string
{
    use Names, Values, Comparable;

    case GENERAL = 'general';
    case LOGO = 'logo';
    case STYLE = 'style';
    case COVER = 'cover';
    case EXAM = 'exam';
    case TEST = 'test';
    case MAIL = 'email';
    case EMAIL = 'emailTemplate';
    case USER = 'user';
    case CONTACT = 'contact';

    public static function options(): array
    {
        return [
            self::GENERAL->value => __('Site Ayarları'),
            self::LOGO->value => __('Logo Ayarları'),
            self::STYLE->value => __('Stil Ayarları'),
            self::EXAM->value => __('Test & Soru Ayarları'),
            self::TEST->value => __('Sınav Ayarları'),
            self::MAIL->value => __('Mail Gönderim Ayarları'),
            self::USER->value => __('Kullanıcı / Kişi Ayarları'),
            self::COVER->value => __('Sayfa Kapak Görseli Ayarları'),
            self::EMAIL->value => __('E-posta Şablon Ayarları'),
            self::CONTACT->value => __('İletişim Ayarları'),
        ];
    }

    public function name(): string
    {
        return self::options()[$this->value] ?? '';
    }

    public function icon(): string
    {
        return match ($this->value) {
            self::GENERAL->value => 'fa-cogs',
            self::LOGO->value => 'fa-image',
            self::STYLE->value => 'fa-palette',
            self::EXAM->value => 'fa-pen',
            self::TEST->value => 'fa-book-open-reader',
            self::COVER->value => 'fa-images',
            self::USER->value => 'fa-user-cog',
            self::MAIL->value => 'fa-envelope-circle-check',
            self::EMAIL->value => 'fa-envelope-open-text',
            self::CONTACT->value => 'fa-contact-card'
        };
    }
}
