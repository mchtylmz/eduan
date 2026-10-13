# Paket çıkarma ve proje temizliği

**Güncel kurulum:** [35-PHP85-VE-LARAVEL13-GUNCELLEME.md](35-PHP85-VE-LARAVEL13-GUNCELLEME.md). Yerel PHP8.5.8, Laravel13.34.0, Livewire4.4.7, Alert4.2, Excel4.0.3, PHPUnit13.4.1 kuruldu.132 bağımlılık; platform/audit kontrolü başarılı.16 farklı dar senaryo geçti; tüm ürün kabulü değil. Yeni Dockerfile build registry timeout verdi, doğrulanan mevcut yerel8.5 image kullanıldı. Tablolar/tarayıcı/yedek karşılıkları beklenir.

5 Ekim 2026. Kullanıcının ilk aşama onayıyla yedi Composer bağımlılığı ve ilgili uygulama bağlantıları kaldırıldı. **PHP 8.5 + PHPUnit 13** son kullanıcı cevabıyla kesin hedeftir. Bu kayıt gerçek değişikliği anlatır; `32` ve `33` önceki seçim/hazırlık kanıtlarıdır.

## 1. Gerçek kurulum durumu

| Alan | Bu aşamanın sonucu |
|---|---|
| Root PHP isteği | `^8.2` korunuyor; `^8.5` sonraki yükseltme aşamasında işlenecek. |
| Laravel | `^11.9`, lock `11.44.7` korunuyor. |
| Livewire | `^3.5`, lock `3.6.3` korunuyor. |
| PHPUnit | `^11.0.1`, lock `11.5.19` korunuyor. Hedef 13 henüz kurulmadı. |
| Sail runtime | PHP 8.3.32; PHP 8.5 Docker ortamı henüz hazırlanmadı. |
| Lock bağımlılık sayısı | Development dahil **152 → 131**. |
| Kalan bağımlılıklar | Sürüm değişikliği 0; yeni bağımlılık 0. |
| Kaldırılanlar | 7 doğrudan + 14 geçişli = **21**. |

Root `require` 14 paket + PHP koşulu, `require-dev` 6 paket içerir. Bu manifest doğrudan Laravel 13 kurulum manifesti değildir. Hedef adayların paket çözümü `33` içindeki geçici dry-run'dır.

## 2. Kaldırılan doğrudan paketler ve karşılıkları

| Paket | Eski sürüm | Durum / sonraki karşılık |
|---|---|---|
| druc/laravel-langscanner | 2.2.0 | Kaldırıldı. Kendi çeviri anahtarı tarayıcımız gerekiyor. |
| laravel/telescope | 5.7.0 | Kaldırıldı. Yeni karşılık istenmedi. |
| league/flysystem-aws-s3-v3 | 3.29.0 | Kaldırıldı. Yeni yedek scriptinde R2 aktarımı ayrıca geliştirilecek. |
| rappasoft/laravel-livewire-tables | 3.5.6 | Kaldırıldı. Laravel 13/LW4 sonrasında Lara Football standardında Bootstrap Table. |
| sentry/sentry-laravel | 4.13.0 | Kaldırıldı. Yeni karşılık istenmedi. |
| spatie/laravel-backup | 9.3.2 | Kaldırıldı. DB/dosya ZIP yedeği ve R2 aktarımı kendi scriptimizle sağlanacak. |
| barryvdh/laravel-debugbar | 3.15.4 | Development bölümünden kaldırıldı. Yeni karşılık istenmedi. |

Yalnız bunlara bağlı kaldırılan geçişli paketler: aws/aws-crt-php, aws/aws-sdk-php, blade-ui-kit/blade-heroicons, blade-ui-kit/blade-icons, jean85/pretty-package-versions, mtdowling/jmespath.php, nyholm/psr7, php-debugbar/php-debugbar, sentry/sentry, spatie/db-dumper, spatie/laravel-signal-aware-command, spatie/temporary-directory, symfony/options-resolver, symfony/psr-http-message-bridge.

## 3. Uygulama bağlantılarının temizliği

- `bootstrap/providers.php`: Telescope provider kaydı kaldırıldı.
- `bootstrap/app.php`: Sentry importu, DB/dosya backup komutları, R2 upload işi ve Telescope prune görevi kaldırıldı. Laravel'in exception handler kaydı için `withExceptions()` korunur; Sentry closure'ı yoktur.
- Silinen sınıflar: `app/Providers/TelescopeServiceProvider.php`, `app/Jobs/UploadBackupToCloudflare.php`, `app/Console/Commands/UploadCloudflare.php`.
- Silinen config dosyaları: `config/backup.php`, `config/telescope.php`, `config/sentry.php`, `config/debugbar.php`, `config/langscanner.php`.
- `config/filesystems.php`: S3 disk bağlantısı kaldırıldı. `local`, `private`, `icons`, `public` diskleri korunur.
- `config/database.php`: yalnız eski backup paketinin `dump.exclude_tables` tanımı kaldırıldı; bağlantı, strict, kolon/indeks ayarları değiştirilmedi.
- `config/logging.php`: yalnız eski R2 upload log kanalı kaldırıldı; eski log dosyası silinmedi.
- `.env.example`: yalnız S3 bucket/path-style satırları kaldırıldı. AWS key/secret/region başka config örneklerinde de kullanıldığı için korunur. Gerçek `.env` değiştirilmedi.
- `phpunit.xml`: Telescope'a ait env tanımı kaldırıldı.
- `LessonTable` içindeki kullanılmayan Debugbar/DebugBar importları kaldırıldı.
- README'nin kaldırılan paket kurulum talimatları çıkarıldı ve ara faz durumu eklendi.

Kalan scheduler görevleri, bu sırayla: `update_gpt_limit_for_users`, `update_season_id_for_results`, `optimize_clear`, `run_league_result`. Bu görevler testte **çalıştırılmadı**; yalnız kayıtları okundu. Saatlik global optimize temizliğinin cache etkisi ayrı faz konusudur.

## 4. Çeviri davranışı

`TranslationHelper::scan()` ve Druc importları kaldırıldı. `LanguageTranslateForm::mount()` artık otomatik eksik anahtar tarayıp DB'ye eklemez. Mevcut çevirileri okuma/düzenleme, locale kapsamında işlem yetkisi ve JSON oluşturma korunur. `TranslationHelper::upload/build/exists` kendi tarayıcı için mevcut yardımcı davranışlarıyla kalır.

Yeni tarayıcının önceki sözleşmesi korunmalıdır: app/resources kaynaklarında `__`, `trans`, `trans_choice`, `@lang`, `Lang::get` gibi kullanım biçimleri; vendor Blade dışlama; seçilen locale için eksik anahtarları bulma; mevcut değerleri ezmeme; `languages:translate` yetkisi. Ayrıntılı parser sınırları uygulama aşamasında referans config ile kesinleştirilir. Kaldırılan config özel kaynak snapshot'ında korunmuştur.

## 5. Rappasoft kaynaklarının geçici durumu

**31 Livewire tablo sınıfı**, ortak filtre/configure traitleri, `config/livewire-tables.php` ve yayınlanmış vendor Blade kaynakları yeni tablolara aktarım için duruyor. Paket vendor'da olmadığı için bu sınıflar yüklenirse eksik `DataTableComponent`/Rappasoft sınıfı hatası beklenir. Tabloların çalıştığı veya bütün panelin sağlıklı olduğu iddia edilmez; bu ara sürüm **canlıya alınmaz**.

OneUI devam eder; Vue.js eklenmez. Yeni standardın kaynağı Lara Football: Blade tablo, Bootstrap Table, server-side JSON `total/rows`, sunucu sayfalaması/arama/sıralama/filtreler, izin/sahiplik kapsamı ve dil bazlı cache. Tablo aileleri ve korunacak davranışlar `19`, sayfa kabul matrisi `20` içindedir. Bu fazda yeni tablo geliştirilmedi.

## 6. Yedekleme ve veri sınırı

Eski DB/dosya ZIP ve R2 aktarım otomasyonu artık kayıtlı değildir. Kendi yedek scripti bu aşamada yazılmadı; Lara Football'daki gerçek script dosyasının yolu, kapsam, retention ve çalışma zamanı uygulama öncesinde netleştirilir.

Eski kuyrukta `UploadBackupToCloudflare` serialized işleri bulunursa kaldırılan sınıfa bağlıdır. Canlıya alma öncesinde yalnız bu işlerin kapsamı incelenmeli ve açık karar alınmalıdır; bu tur queue kayıtları okunup silinmedi veya worker çalıştırılmadı. Kaynak backup dosyaları, SQL dump, Telescope tabloları, geçmiş sonuçlar korunur. Yeni migration/drop/fresh/seed yoktur; R2'ye obje gönderilmedi.

## 7. Composer ve provider işlemleri

Composer PHP 8.4 binary ile çalıştırıldı; mevcut Laravel 11 için PHP 8.5 kabulü üretilmedi. Runtime paketleri `remove --no-update`, development Debugbar ayrı `remove --dev --no-update` ile manifestten çıkarıldı. Ardından sadece kaldırılan paket adlarıyla sınırlı `update` yapıldı; `-W` kullanılmadı. `--no-scripts --no-plugins` uygulama boot/publish yan etkilerini önledi.

Eski `bootstrap/cache/packages.php` ve `services.php` üretilmiş provider manifestleri temizlendi. `APP_ENV=testing DB_DATABASE=testing CACHE_STORE=array` ile `artisan package:discover` başarılı; ana DB/cache için optimize komutu kullanılmadı.

- Son lock karşılaştırması: 21 çıkarma, 0 ekleme, 0 kalan sürüm değişimi.
- `composer validate --no-check-publish --no-check-all`: başarılı.
- Composer son çıktısı: **16 mevcut pakette 58 güvenlik advisory kaydı**. Ayrıntılı audit ve hedef sürüm karşılaştırması sonraki yükseltme kapısıdır; bu toplam 58 ayrı uygulama exploit'i kanıtı değildir. Bu fazda diğer paketler otomatik güncellenmedi.

## 8. Dar test sonucu ve sınırlar

Sail PHP 8.3.32 / PHPUnit 11.5.19 ile yalnız ayrı **testing** DB:

**6 test / 21 doğrulama başarılı**:

1. Scheduler yalnız dört korunan uygulama görevini kaydeder; kaldırılan görev yoktur.
2. Çeviri sayfasına işlem izni olmayan kullanıcı yazamaz.
3. Yetkili kullanıcı yalnız seçilen dilin çevirisini düzenler ve JSON çıktısı oluşturur.
4. Yetki kaldırıldıktan sonra çeviri yazımı reddedilir.
5. Giriş sayfası uygulama/layout boot ile HTTP 200 döner.
6. Gerçek PNG yerel public diskine doğru uzantıyla yüklenir.

İlk koşuda testing DB'deki önceki browser fixture'ları `nl` ve `home` unique çakışması oluşturdu. İlgili test fixture'ları `firstOrCreate` kullanacak şekilde düzenlendi; çeviri count beklentisi başlangıç sayısına göre kuruldu. Kalıcı fixture silinmedi. Yeni schedule testi ortak TestCase'in optimize:clear için mock'ladığı console kernel yerine gerçek kernel kaydını okuyup Artisan başlangıcını tetikler; komutların kendisini yürütmez.

İlgili üç test dosyasında Pint başarılı. Değiştirilen uygulama/config PHP dosyaları ve yeni testte dokuz dar lint başarılı. Tam suite, build, hedef PHP 8.5/PHPUnit 13 kabulü veya gerçek browser/table/PWA kontrolü bu fazda yapılmadı.

## 9. Değişen dosyalar ve özel kaynak snapshot'ı

Değişen: composer.json, composer.lock, bootstrap/app.php, bootstrap/providers.php, TranslationHelper.php, LanguageTranslateForm.php, LessonTable.php, config/filesystems.php, config/database.php, config/logging.php, .env.example, phpunit.xml, README.md, tests/Feature/AccessBoundaryTest.php, tests/Feature/PageRenderTest.php. Yeni: tests/Feature/PackageRemovalTest.php ve bu belge. Silinen sekiz dosya bölüm 3'tedir.

Rehber eşleştirmesi: AGENTS.md, Codex/index.html, 14/16/19/32/33 güncellendi. Önceki kayıtlar tarihsel kapsamıyla korunur.

İşlem öncesi **21 kaynak dosyası** `storage/app/private/codex-package-removal/20261005/` içinde, manifest.json ile saklandı; Git ignore altında ve public dışındadır. Bu bir **kaynak kod snapshot'ıdır, DB yedeği değildir**. Eski mevcut DB yedeği `31` kaydındaki kapsamıyla durur. Composer logları `/tmp/eduan-package-removal/` altında geçicidir.

## 10. Sonraki adım

PHP 8.5 Sail runtime + gerçek Composer hedef koşulları hazırlanır; doğrudan Laravel 13 / Livewire 4 / Alert 4 / Excel 4 / PHPUnit 13 ve seçilen kalan sürümler kurulur. Laravel 12 kurulmaz; iki yükseltme kılavuzu davranış değişiklikleri açısından birlikte incelenir. Ardından tablo/tarayıcı/yedek karşılıkları ve hedef sürüm uyarlamaları yapılır. PHP 8.5 nullable uyarıları ve hedef API değişiklikleri bu ayrı uyarlama kapsamındadır. Sunucu PHP/cron değişimi canlıya alma aşamasına aittir.
