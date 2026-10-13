# Paketler, kütüphaneler ve asset düzeni

> Güncel kullanıcı kararı: Laravel yükseltmesinden sonra Rappasoft çıkarılacak; Lara Football örneğinde Blade + Bootstrap Table/server-side JSON kullanılacak. OneUI korunur, Vue.js eklenmez. Kullanım/geçiş envanteri `19-RAPPASOFT-BOOTSTRAP-TABLE-GECIS.md` içindedir; bu fazda paket değiştirilmedi.

## Sunucu paketleri

Sürüm bilgileri `composer.lock` içindeki 5 Ekim 2026 kaydıdır; aralıklar `composer.json` kaynağındadır. Paket kaldırma kararı henüz verilmedi.

| Paket | Kilit sürüm / aralık | Kodda gözlenen rol |
|---|---|---|
| `laravel/framework` | v11.44.7 / `^11.9` | Çatı; kullanıcı projenin 11.9 temeliyle geliştirildiğini bildirdi. |
| `livewire/livewire` | v3.6.3 / `^3.5` | Form, frontend etkileşimi ve panel. |
| `rappasoft/laravel-livewire-tables` | v3.5.6 / `3.5.6` | Panel tabloları ve filtreler; gerçek kullanım var. |
| `spatie/laravel-permission` | v6.17.0 / `^6.9` | Rol/izin; kalması kullanıcı hedefi. |
| `pharaonic/laravel-settings` | v1.2.0 / `^1.2` | `settings()` ile panel ayarları; kalabilir. |
| `spatie/laravel-translatable` | `*` | Page/Lesson/Topic JSON çevirileri; sürüm aralığı sabit değil. |
| `izniburak/laravel-auto-routes` | v2.1.0 / `^2.1` | Backend controller route üretimi. |
| `lorisleiva/laravel-actions` | `^2.8` | `AsAction` iş adımları. |
| `maatwebsite/excel` | v3.1.64 / `^3.1` | Dört istatistik dışa aktarma sınıfı. |
| `druc/laravel-langscanner` | `^2.2` | Eksik çeviri anahtarlarını tarama. |
| `zoha/laravel-meta` | v2.0.2 / `^2.0` | Kullanıcı ve sınav bölümü meta verisi. |
| `intervention/image-laravel` | v1.5.6 / `^1.5` | Görsel, watermark ve PWA ikon üretimi. |
| `laravel/telescope`, `sentry/sentry-laravel`, `spatie/laravel-backup` | sürümler lock'ta | Gözlem, hata takibi ve yedek. |
| `league/flysystem-aws-s3-v3`, `laravel/tinker`, `archtechx/enums`, `jantinnerezo/livewire-alert`, `webmozart/assert` | `composer.json` | Depolama, geliştirme ve yardımcı tip/UI işlevleri. |

Dev bağımlılıkları `laravel/sail`, PHPUnit 11, Pint, Debugbar, Faker, Mockery ve Collision'dır. Paket kullanımı/kaldırılabilirlik için çağrı yerleri, config, migration, yayınlanmış görünüm ve üretim verisi beraber incelenmelidir. README'nin `composer require` listesi mevcut `composer.json` ile karşılaştırılmadan güncel kurulum reçetesi sayılmaz.

## İstemci düzeni

`package.json` yalnız Axios, Vite ve Laravel Vite Plugin geliştirici bağımlılıkları içerir. `vite.config.js` `resources/css/app.css`/`resources/js/app.js` girişlerini tanımlar. İncelenen iki ana Blade layout doğrudan `public/assets` ve `public/backend/assets` dosyalarını kullanır; build komutu çalıştırılmadı. Birinci taraf stil/senaryo dosyaları `public/assets/app.css|js`, `public/backend/assets/app.css|js`; ayrıca tema `main.css|js` vardır.

Ön yüz: Bootstrap bundle, jQuery, Swiper, MeanMenu, Wow, Nice Select, ScrollUp, Magnific Popup, Odometer, SweetAlert, Bootstrap Select, Flatpickr, KaTeX CDN ve cookie consent. Panel: OneUI, jQuery Validation, SweetAlert, Dropify, Flatpickr, Masked Input, Bootstrap Select, koşullu TinyMCE CDN, Livewire. `public/backend/assets/js/plugins` içinde Bootstrap Table, Chart.js, FullCalendar, Select2, Dropzone ve EditorJS/MathLive gibi dağıtım dosyaları da var; dosyanın varlığı aktif kullanım kanıtı değildir. `public/uploads` içerik/görselleri yaklaşık 284 MB; bu klasörü temizleme veya yeniden üretme izni yok.

## Tablo değiştirme değerlendirmesi için kayıt

Mevcut Rappasoft davranışı: `config/livewire-tables.php` Bootstrap 5 tema ve asset cache; `LivewireTableConfigure` 15/25/50/100 sayfalama, tek sıralama, query string, slide-down filtre, loading placeholder ve total-count kapatma; `CustomLivewireTableFilters` ortak seçenekler; tablo bileşenlerinde `builder/filters/columns/appendColumns`. `resources/views/vendor/livewire-tables` override'ları UI sözleşmesinin parçasıdır. Daha sonra alternatif seçilecekse aynı tablo üzerinde sorgu süresi, DOM/JS yükü, filtre, erişilebilirlik, çok dil, eylem yetkisi, toplu işlem ve mobil görünüm kıyaslanmalı; eski pakete bağımlılıkları çıkarmadan kaldırılmamalıdır.
