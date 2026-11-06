<?php

namespace App\Enums;

use ArchTech\Enums\Comparable;
use ArchTech\Enums\Names;
use ArchTech\Enums\Values;

enum SettingsTabsEnum: string
{
    use Comparable, Names, Values;

    case GENERAL = 'general';
    case AI = 'ai';
    case POPUP = 'popup';
    case LOGO = 'logo';
    case STYLE = 'style';
    case COVER = 'cover';
    case EXAM = 'exam';
    case LEAGUE = 'league';
    case TEST = 'test';
    case MAIL = 'email';
    case EMAIL = 'emailTemplate';
    case USER = 'user';
    case CONTACT = 'contact';
    case NOTIFICATIONS = 'notifications';
    case STUDY = 'study';
    case TURNSTILE = 'turnstile';
    case FILES = 'sources';

    public static function options(): array
    {
        return [
            self::GENERAL->value => __('Site Ayarları'),
            self::AI->value => __('Yapay Zeka Ayarları'),
            self::POPUP->value => __('Anasayfa Pop-up Özelliği'),
            self::LOGO->value => __('Logo Ayarları'),
            self::STYLE->value => __('Stil Ayarları'),
            self::EXAM->value => __('Test & Soru Ayarları'),
            self::LEAGUE->value => __('Lig Ayarları'),
            self::TEST->value => __('Sınav Ayarları'),
            self::MAIL->value => __('Mail Gönderim Ayarları'),
            self::USER->value => __('Kullanıcı / Kişi Ayarları'),
            self::COVER->value => __('Sayfa Kapak Görseli Ayarları'),
            self::EMAIL->value => __('E-posta Şablon Ayarları'),
            self::CONTACT->value => __('İletişim Ayarları'),
            self::NOTIFICATIONS->value => __('notifications.settings'),
            self::STUDY->value => __('study.title'),
            self::TURNSTILE->value => __('turnstile.settings'),
            self::FILES->value => __('sources.settings'),
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
            self::AI->value => 'fa-wand-magic-sparkles',
            self::POPUP->value => 'fa-window-maximize',
            self::LOGO->value => 'fa-image',
            self::STYLE->value => 'fa-palette',
            self::EXAM->value => 'fa-pen',
            self::LEAGUE->value => 'fa-trophy',
            self::TEST->value => 'fa-book-open-reader',
            self::COVER->value => 'fa-images',
            self::USER->value => 'fa-user-cog',
            self::MAIL->value => 'fa-envelope-circle-check',
            self::EMAIL->value => 'fa-envelope-open-text',
            self::CONTACT->value => 'fa-contact-card',
            self::NOTIFICATIONS->value => 'fa-bell',
            self::STUDY->value => 'fa-calendar-check',
            self::TURNSTILE->value => 'fa-shield-halved',
            self::FILES->value => 'fa-folder-open',
        };
    }
}
