<?php

return [
    'settings' => 'Cloudflare Turnstile',
    'feature' => 'Form güvenlik kontrolü',
    'settings_help' => 'Cloudflare’da Managed türünü seçin. Kontrol, giriş yapmamış kullanıcıların giriş, kayıt, şifre kurtarma, yeni parola ve iletişim formlarında çalışır. Bülten ve üye işlemlerinde gösterilmez.',
    'site_key' => 'Site anahtarı',
    'secret_key' => 'Gizli anahtar',
    'secret_help' => 'Kayıtlı anahtar gösterilmez. Değiştirmek istemiyorsanız boş bırakın. Anahtar şifrelenerek saklanır.',
    'host_help' => 'Cloudflare’da bu alan adını izinli ekleyin: :host. Yerel testte APP_URL ile açtığınız alan adı aynı olmalıdır.',
    'invalid_settings' => 'Güvenlik ayarları geçersiz.',
    'keys_required' => 'Güvenlik kontrolünü açmadan önce iki anahtarı da girin.',
    'failed' => 'Güvenlik kontrolü tamamlanamadı. Lütfen tekrar doğrulayıp gönderin.',
    'unavailable' => 'Güvenlik kontrolü yüklenemedi. Bağlantını kontrol edip tekrar dene.',
    'retry' => 'Tekrar dene',
    'newsletter_limit' => 'Dakikada bir kez abone olabilirsin. Biraz bekleyip tekrar dene.',
];
