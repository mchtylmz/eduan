# PHP 8.5 geçiş kontrolü ve SSH kanıtı

**Güncel uygulama:** [34-PAKET-CIKARMA-VE-PROJE-TEMIZLIGI.md](34-PAKET-CIKARMA-VE-PROJE-TEMIZLIGI.md). Yedi paket gerçek composer/lock/vendor’dan kaldırıldı; 152 → 131 bağımlılık, kalan sürümlerde değişiklik yok. PHP 8.5 ve PHPUnit 13 hedefi kesin; mevcut Laravel 11/LW3 korunuyor. Ara sürümde tablolar/tarayıcı/yedek karşılıkları beklenir; canlıya alınmaz. Dar sonuç 6 test/21 doğrulama.

5 Ekim 2026. Kullanıcı PHP8.5 standardını seçti, Backup/S3 paketlerini çıkarma kararını verdi, Alert4 ile devamı doğruladı. Bu tur **geçişi konuşma ve PHP8.5 kontrolü**dür; gerçek paket kurma/kaldırma, uygulama düzeltme, Sail build/up, DB, cron veya sunucu değişikliği yapılmadı. Önceki PHP8.3 hedefi / Backup-S3 bekleyen seçimleri `32` içinde tarihseldir; bu belge ve son kullanıcı talimatı üstündür.

## 1. Yeni kesin hedefler

- Uygulama/test hedefi **PHP8.5 bakım sürümü**, Laravel13, Livewire4, Alert4, Excel4. Minimum Laravel13 koşulu PHP8.3'tür; PHP8.4.1 şartı Laravel'e değil, seçilen PHPUnit13.4.1'e aittir.
- Laravel13'ün resmî destek aralığı PHP8.3–8.5: [release notes](https://laravel.com/framework/docs/13.x/releases).
- PHP8.5'in aktif desteği 31 Aralık2027, güvenlik desteği 31 Aralık2029'a kadar: [PHP support policy](https://www.php.net/supported-versions.php). Bu süreler paketin bütün API'lerinin uyumlu olduğu anlamına gelmez.
- PHPUnit13 PHP8.5 ile adaydır; önceki PHP8.3 hedefindeki PHPUnit12 seçimi artık hedef değildir. [PHPUnit13 kurulum dokümanı](https://docs.phpunit.de/en/13.4/installation.html).
- Kesin çıkarma **7 paket**: druc/laravel-langscanner, laravel/telescope, league/flysystem-aws-s3-v3, rappasoft/laravel-livewire-tables, sentry/sentry-laravel, spatie/laravel-backup, barryvdh/laravel-debugbar.
- Langscanner/table yeteneklerinin kendi kodumuzla karşılığı şart. Backup: DB/dosya yedeği ve R2 aktarımı için Laravel Football örneğindeki manuel/paketsiz karşılık; burada dosyaların ZIP olarak gönderilmesi korunacak hedef.
- Kullanıcının “5'te” ifadesini kesin bir cron saati veya beş dosya sayısı olarak uygulama. Paket çıkarma kararı açıktır; yeni backup'ın script yolu/dili/saat/retention/dosya kapsamı gerçek uygulamadan önce referansla netleştirilir. Önceki03:00 taslağı yeni mesajdan varsayımla05:00'a çevrilmez.
- Sunucu web/SSH PHP değişimi **proje bitip canlıya alma aşamasında** yapılır. Şimdiki SSH kanıtı dağıtım izni veya dağıtım sonucu değildir.

## 2. Kullanıcıdan alınan SSH çıktısının sınırı

Aşağıdaki bilgiler kullanıcı tarafından paylaşılan terminal çıktısıdır; agent bağımsız sunucu bağlantısı açmadı.

| Kontrol | Çıktı | Değerlendirme |
|---|---|---|
| SSH | Giriş başarılı, komut çıktısı var | Hosting hesabına CLI erişimi mevcut; root/exec/cron/R2 erişim kapsamının tamamı kanıtlanmadı. |
| Varsayılan CLI PHP | **8.2.34 ZTS** | Hedef8.5 değil; canlıya alma sırasında doğru8.5 binary seçilmeli. |
| PHP binary | `/usr/local/bin/php` | Şu an8.2 çalıştırıyor; bu yolu8.5 diye kullanma. |
| ini | `/usr/local/php82/lib/php.ini` | PHP8.2 konfigürasyonu; 8.5 ini/modülleri ayrı doğrulanacak. |
| MySQL dump aracı | `/bin/mysqldump` | Araç yolu mevcut; sürüm, bağlantı yetkisi ve tutarlı restore kanıtı yok. |
| cURL CLI | 8.15.0, HTTPS/TLS desteği | R2'ye network/kimlik doğrulama/obje başarı kanıtı değildir. |
| OPcache/pcntl | Listede mevcut | PHP8.2 için modül var; web SAPI / PHP8.5 / worker davranışı ayrıca kontrol edilir. |

Gönderilen modül listesinde Laravel gereksinimleri ve proje ek modülleri (pdo_mysql, gd, exif, zip, iconv, simplexml, xmlreader/xmlwriter, zlib) bulunuyor. **Bu liste PHP8.2'ye aittir; PHP8.5'e taşınmış sayılmaz.** GD'nin WebP/JPEG codec seçenekleri yalnız modül adıyla kanıtlanmaz. PHP8.5 CLI yolu ve modülleri şu an kullanıcı hesabında görülmedi; destekten tam binary yolu istenebilir. Alias'ın var olduğu varsayılmaz. Web PHP ayarı ve CLI/cron binary farklı olabilir.

## 3. Yerel PHP8.5 hazırlık kontrolü

- Host varsayılan `php`: Homebrew PHP8.4.22; Sail mevcut build context'i `vendor/laravel/sail/runtimes/8.3`. Bu ortamlar hedef8.5 diye sunulmaz.
- Makinede ayrı **`/opt/homebrew/opt/php@8.5/bin/php` → PHP8.5.7** bulundu ve kontroller açıkça bu binary ile çalıştırıldı; varsayılan PHP değiştirilmedi.
- Bu yerel8.5 binary'sinde Laravel ve Excel/GD için kontrol edilen21 extension'ın tamamı mevcut; php -r ile kontrol edildi. Bu sonuç SiteGround modül kanıtı değildir.
- Mevcut vendor Sail1.42 runtime klasörlerinde8.5 yok; seçilen Sail1.68 upstream içinde [8.5 Dockerfile](https://github.com/laravel/sail/blob/v1.68.0/runtimes/8.5/Dockerfile) var. Sail paket/runtime güncellemesi gerekir; henüz Docker rebuild yapılmadı.
- PHP8.3/8.4/8.5 geçiş rehberleri birlikte incelenir; eski nullable tanımları8.4'te uyarı olmaya başlamıştır. [PHP8.4 deprecated](https://www.php.net/manual/en/migration84.deprecated.php), [PHP8.5 deprecated](https://www.php.net/manual/en/migration85.deprecated.php), [PHP8.5 incompatibilities](https://www.php.net/manual/en/migration85.incompatible.php).

## 4. Hedef paket graph kontrolü — gerçek dry-run

Geçici `/tmp/eduan-php85-readiness/composer.json` oluşturuldu. Projenin runtime/dev istekleri temel alındı; onaylı7 çıkarma ve seçilen hedef aralıkları sadece bu geçici dosyaya işlendi. Aday14 runtime +6 development paket; PHP isteği^8.5 ve platform PHP8.5.0. Uygulama autoload/script/provider boot'ları bu paketsel kontrolde çalıştırılmadı.

Komut, yerel gerçek PHP8.5.7 ile:

```sh
/opt/homebrew/opt/php@8.5/bin/php /usr/local/bin/composer update --dry-run --no-scripts --no-plugins --no-interaction --no-progress --working-dir=/tmp/eduan-php85-readiness
```

**Exit0; graph çözüldü: 132 planlanan paket, 0 update/0 removal.** Geçici temiz manifestte mevcut lock yoktu; bu nedenle Composer hepsini “install” diye yazar. Bu satırlar gerçek vendor kurulumu veya projedeki132 paketin yeniden kurulduğu anlamına gelmez. Proje composer.json/lock/docker-compose SHA256 değerleri önce/sonra değişmedi. Geçici klasörde de composer.lock/vendor oluşmadı.

| Doğrulanan aday | Çözülen sürüm |
|---|---|
| Laravel | 13.34.0 |
| Livewire | 4.4.7 |
| Alert | 4.2.0 |
| Excel | 4.0.3 |
| Image Laravel | 1.5.9 |
| Actions | 2.12.0 |
| Permission | 6.25.0 |
| Translatable | 6.14.1 |
| Sail / Pint | 1.68.0 / 1.32.1 |
| PHPUnit / Mockery / Collision | 13.4.1 / 1.6.15 / 8.9.5 |
| Settings / Meta | 1.2.0 / 2.0.2 |

Diğer çözülmüş adaylar özel geçici `resolved-candidates.json` kaydında; `32` listesiyle aynı ana seri seçimleridir. Webmozart1.12.1 kaldı. Solver başarı **boot, çalışma, UI, migration veya test kabulü değildir**. Composer “No installed packages - skipping audit” yazdı; güvenlik/advisory taraması tamamlandı denmez. Kullanılan global Composer PHAR'ın kendi bağımlılıklarında PHP8.5 deprecation çıktısı vardı (curl_close, case semicolon, http_response_header); bunlar proje kaynak bulgularından ayrıdır, araç güncelliği hedef kurulumda ele alınır. Global Composer güncellenmedi.

## 5. Proje PHP8.5 lint sonucu ve düzeltme adayları

`app`, `bootstrap` (cache hariç), `config`, `routes`, `database`, `tests` altındaki **390 PHP dosyası** yerelPHP8.5.7 / E_ALL ile birer kez lint edildi. `php -l` dosyayı çalıştırmaz; DB bağlantısı, queue, application boot veya migration yapılmadı. Blade derleme/JS/assets bu test değildir.

- **Sözdizimi hatası:0.**
- **Deprecated uyarısı olan dosya:21.**
- **Tekil uyarı:27 nullable parametre.** stdout/stderr çift yazımı tekilleştirildi.
- Örnek `string $locale = null` → `?string $locale = null`; model/int parametreleri de açık nullable yapılmalı. Bu tur değiştirilmedi. API'nin null kabulü korunarak onaylı geçiş düzeltmesinde ele alınır; varsayılan değer/iş davranışı değiştirilmez.

| Proje dosyası | Satır | Tekil uyarı |
|---|---|---|
| `app/Mail/ContactReplyMail.php` | 29 | 3 |
| `app/Mail/CreatePasswordMail.php` | 28 | 1 |
| `app/Mail/NewsletterMail.php` | 30 | 3 |
| `app/Mail/EmailVerificationMail.php` | 30 | 1 |
| `app/Mail/RecoverPasswordMail.php` | 29 | 1 |
| `app/Mail/SendEmailToUserMail.php` | 30 | 3 |
| `app/Models/User.php` | 100 | 1 |
| `app/Helpers/FilterHelper.php` | 219 | 1 |
| `app/Actions/Blogs/CreateOrUpdateBlogAction.php` | 13 | 1 |
| `app/Actions/Tests/CreateOrUpdateAction.php` | 13 | 1 |
| `app/Actions/Roles/CreateOrUpdateAction.php` | 13 | 1 |
| `app/Actions/Languages/CreateOrUpdateLanguageAction.php` | 12 | 1 |
| `app/Actions/Faqs/CreateOrUpdateFaqAction.php` | 12 | 1 |
| `app/Actions/Lessons/CreateOrUpdateLessonAction.php` | 15 | 1 |
| `app/Actions/Exams/CreateOrUpdateExamAction.php` | 13 | 1 |
| `app/Actions/Exams/CreateReviewAction.php` | 13 | 1 |
| `app/Actions/Exams/UpdateExamFavoriteForUserAction.php` | 13 | 1 |
| `app/Actions/Pages/CreateOrUpdatePageAction.php` | 14 | 1 |
| `app/Actions/Topics/CreateOrUpdateTopicAction.php` | 13 | 1 |
| `app/Http/Controllers/Frontend/TestController.php` | 12 | 1 |
| `app/Livewire/Frontend/Leagues/LeagueResultTable.php` | 22 | 1 |

## 6. Korunacak eski paketlerde PHP8.5 uyarıları

Yeni sürümü değişmeyen Settings1.2.0, Meta2.0.2 ve AutoRoutes2.1.0 mevcut vendor kaynaklarında dar lint kontrolü: **36 PHP dosyası, 0 syntax error; 2 dosyada uyarı**.

- `vendor/pharaonic/laravel-settings/src/Classes/Settings.php:37`: constructor model parametresi implicit nullable.
- `vendor/zoha/laravel-meta/src/Meta/Helpers/MetaHelper.php:289`: `(boolean)` cast'i PHP8.5'te deprecated; canonical `(bool)` karşılığı gerekir.
- AutoRoutes incelenen kaynaklarında lint uyarısı yok; hedef route çalışma testi henüz yok.

Bu dosyalar değiştirilmedi. Solver paketleri kabul etse bile PHP8.5 uyarısı temiz runtime garantisi vermiyor. Hedef kurulumdan sonra gerekli upstream sürüm/yama/uyarlama kararı ayrı verilir; vendor içinde elle kalıcı düzenleme yapıp Composer update'de kaybolacak çözüm bırakılmaz. Paketleri kullanıcı kararı olmadan çıkarma veya E_DEPRECATED'i kapatarak uyumu sağlanmış gösterme.

## 7. Somut geçiş sırası

1. Bu7 çıkarma ve PHP8.5/Laravel13/LW4/Excel4/Alert4 kararlarını gerçek hedef manifestine geçirmek; aralık/lock kayıtları incelemeye açık olmalı. Geçici dry-run hazırlığı bunu değiştirmedi.
2. Telescope provider/prune, Sentry import/config, Backup komutları/aktarımı ve Debugbar importları gibi kaldırılan paket bağlantılarını boot'u bozmayacak şekilde temizlemek. Mevcut veritabanı tablolarını, geçmiş migration'ları, yedekleri veya R2 objelerini silmemek.
3. Gerçek hedef vendor'u yerelPHP8.5 ile doğrudan Laravel13 için kurmak. Gerekli ara no-scripts tekniği son package-discovery/boot doğrulamasının yerine geçmez; Laravel12 kurulmaz.
4. Sail1.68 ile PHP8.5 context/image hazırlamak; yalnız uygulama container'ı gereken şekilde rebuild. MySQL volume/ana SQL kopyası/testing ayrımı korunur; down -v/fresh/seed bu geçişin parçası değildir.
5. Langscanner'ın çeviri tarama karşılığı, Rappasoft31 tablo haritasındaki kendi Bootstrap Table JSON tabloları, Alert4 ortak trait/layout, Excel4 export, Livewire4 config/Blade/JS uyarlaması. OneUI, mevcut sınıf/Blade standardı ve bütün filtre/eylem/izinler korunur.
6. 21 proje dosyasındaki nullable uyarıları; Settings/Meta upstream uyumu ve hedefte kalan PHP8.5 deprecation'ları kontrollü gidermek. Bu lint tek başına vendor/runtime hatalarının tümü değildir.
7. Önce değişen özelliğin hata/mantık/izin sınırlarına ait dar testler; testingDB dışında test yazımı yok. Büyük hedef framework değişiminde ardından gereken baseline ve kritik browser akışları; eski245+7 veya4/107 sonuçları yeni hedef testi yerine kullanılmaz.
8. Paketsiz backup karşılığını referans scriptle tamamlamak; ZIP/private R2, başarısız upload'da dosyayı koruma, tutarlı dump/restore ve cron/retention kapsamı netleştirilir. Laravel Football checkout'ındaki app/routes/scripts/bootstrap aramasında bu asıl periyodik yedek scripti bulunmadı; sunucu/dış script yolu istenir, referans incelenmiş gibi sunulmaz.
9. Yerel hedef kabulünden sonra canlı hazırlık: SiteGround web8.5, SSH/cron tam PHP8.5 yolu, modüller, hedef no-dev platform kontrolü, backup/geri dönüş ve deployment. Kullanıcı canlı PHP değişimini bu aşamaya bıraktı; bugün dağıtım yapılmadı.

Ara kaldırılmış ama karşılığı yazılmamış tabloların 500 vermesi normal kabul edilmiş son ürün değildir. Bütün karşılıklar tamamlanmadan canlıya çıkılmaz. Canlı worker/günlük yedek ve HTTP/CLI modülleri yerel başarıdan ayrı kanıttır.

## 8. Kanıt dosyaları ve bu turun değişiklik sınırı

Geçici kanıt klasörü `/tmp/eduan-php85-readiness`: candidate composer.json, solver.log, resolved-candidates.json, lint.json, unchanged-vendor-lint.json ve repository-hashes-before.json. Host PHP8.5 gerçek çıktı; SiteGroundPHP8.2 kullanıcı çıktısı; hedef vendor sadece solver taslağıdır. Dosyalar geçicidir, uygulama runtime veya public'e taşınmadı. Kaynak/paket hataları otomatik düzeltilmedi.

Güncellenenler: bu33 rehberi,32 karar kaydı,AGENTS,standartlar/geçiş rehberi ve index. Gerçek composer.json/lock/vendor, docker-compose, uygulama/test dosyaları ve DB değişmedi. PHPUnit/build/migration çalışmadı. Doküman bağlantı/indeks kontrolü ayrıca uygulanır.
