# Laravel 13 — genel paket uyumluluk matrisi

**Son kullanıcı seçimi:** [32-PAKET-KARARLARI-VE-SITEGROUND-HAZIRLIGI.md](32-PAKET-KARARLARI-VE-SITEGROUND-HAZIRLIGI.md) bu belgedeki aday kararlarından üstündür. Livewire4 ve en güncel Excel4 hedefi seçildi; Rappasoft/Langscanner/Telescope/Sentry/Debugbar çıkarılacak. Backup/S3 son seçimi bekler. Aşağıdaki aynı-major seçenekleri tarihsel araştırma olarak oku; Composer kurulumu henüz yapılmadı.

5 Ekim 2026. Kullanıcı yalnız genel paket uyumluluğunu çıkarmamı istedi; ayrıntılı geçiş planı sonraki adımdır. `composer.json`, `composer.lock`, vendor paketleri ve uygulama kodu değiştirilmedi. Composer install/update, solver dry-run, migration, vendor publish veya asset build çalıştırılmadı.

## 1. Kanıt ve değerlendirme sınırı

- Yerel kaynak: composer.json'daki **20 runtime + 7 development = 27 doğrudan paket** ve PHP isteği. Transitive paketlerin tümü tek tek çalışma testine tabi tutulmadı.
- Güncel kaynak: her paketin [Packagist Composer metadata](https://repo.packagist.org/) `p2/vendor/package.json` kaydı aynı gün salt okunur alındı. Bu kayıtlar paket yazarının yayımladığı require/conflict bilgisidir.
- Stable sürümler değerlendirildi; dev/alpha/beta/RC aday seçilmedi. Kilit sürüm, güncel en yeni sürüm ve mevcut major içinde daha dar aday ayrıldı.
- PHP **8.3.32**, Laravel **13.34.0** sayıları üzerinde `Composer\Semver\Semver::satisfies` ile doğrudan PHP/Illuminate/framework aralıkları karşılaştırıldı. Bu tam Composer çözümlemesi değildir; bütün require/conflict/replaces/extensions/platform grafiğinin kabulü veya runtime sonucu değildir.
- En son sürüm her zaman bu proje için doğru aday değildir. Livewire 4 / Excel 4 / Permission 8 / Intervention 4 sırf daha yeni oldukları için seçilmez.
- Metadata uyumu, uygulama API'si ve yayımlanmış Blade/config uyumu birbirinden ayrıdır. Aşağıdaki "aday" ifadeleri karar veya uygulama izni değildir.

## 2. PHP / framework / test zemini

| Alan | Mevcut | Genel değerlendirme |
|---|---|---|
| PHP root isteği | `^8.2` | [Laravel 13](https://laravel.com/framework/docs/13.x/releases) minimum PHP 8.3 ister. Sonraki plan hedef alt sınırı açık belirtmeli. |
| Docker CLI | 8.3.32 | Yerel PHP alt sınırı uygun; canlı PHP/eklenti kanıtı yok. |
| Browser QA host | 8.3.31 | `23` akışları bu sürümde çalıştı. Host 8.4 ön denemesinde nullable deprecation görüldü; 8.4 zorunlu hedef seçilmedi. |
| Framework | root `^11.9`, lock 11.44.7 | Metadata'da son stable 13.34.0. [Resmi 12](https://laravel.com/framework/docs/12.x/upgrade) ve [13](https://laravel.com/framework/docs/13.x/upgrade) rehberleri iki uyumluluk adımı olarak değerlendirilir. |
| PHPUnit | root `^11.0.1`, lock 11.5.19 | 13 upgrade rehberindeki seri PHPUnit 12. PHP 8.3 için 12.5.38 adaydır; en yeni 13.4.1 PHP>=8.4.1 ister. PHPUnit 13'e sırf son sürüm diye geçilmez. |
| Yerel extensions | Docker listesi okundu | mbstring, xml/dom/xmlwriter, fileinfo, gd/imagick, zip, PDO MySQL, curl vb. mevcut. Paketlerin tüm platform koşulları için `check-platform-reqs`/hedef Composer çözümü yapılmadı. |

## 3. Runtime paketleri

"13 beyanı yok" kilit paketin direct require aralığının 13'ü kabul etmediğini ifade eder. "Geniş/aralık yok" açık bir engel bulunmadığı anlamındadır; 13 test kanıtı değildir. Root aralığında kalabilen adaylar işaretlendi; bütün graph çözülmedi.

| Paket | Root / lock | Genel Laravel 13 durumu ve aday |
|---|---|---|
| [archtechx/enums](https://packagist.org/packages/archtechx/enums) | `^1.1` / 1.1.1 | Framework bağı yok, PHP^8.1. 1.1.2 aynı aralıkta; enum davranışı regresyonla korunur. |
| [druc/laravel-langscanner](https://packagist.org/packages/druc/laravel-langscanner) | `^2.2` / 2.2.0 | Lock contracts9/10/11; son stable **2.3.0 yalnız 9/10/11/12**. İncelenen stable metadata'da 13 uyumlu aday yok. Açık karar gerektirir. |
| [intervention/image-laravel](https://packagist.org/packages/intervention/image-laravel) | `^1.5` / 1.5.6 | Lock13 beyanı yok; **1.5.9** PHP^8.1 ve Illuminate8…13, mevcut root'a sığar. Son 4.1.1 için Image4 major geçişine bu nedenle ihtiyaç kanıtlanmadı. |
| [izniburak/laravel-auto-routes](https://packagist.org/packages/izniburak/laravel-auto-routes) | `^2.1` / 2.1.0 | Son stable aynı 2.1.0; PHP^8.1, direct framework aralığı yok. 13 runtime kabulü henüz yok; üretilen panel route/controller binding'leri hedefte kontrol edilir. |
| [jantinnerezo/livewire-alert](https://packagist.org/packages/jantinnerezo/livewire-alert) | `^3.0` / 3.0.3 | Son3 aynı 3.0.3, Illuminate aralığı12'de biter. **4.2.0** Illuminate10…13, Livewire3/4; root major değişimi ve API uyarlaması gerekir. |
| [laravel/framework](https://packagist.org/packages/laravel/framework) | `^11.9` / 11.44.7 | 13.34.0 PHP^8.3; root değişimi gerekir. Yeni framework henüz kurulmadı. |
| [laravel/telescope](https://packagist.org/packages/laravel/telescope) | `^5.4` / 5.7.0 | Lock13 yok; **5.25.0** framework8.37…13 kabul eder, root içinde. Yeni laravel/sentinel bağı da metadata'da vardır; bütün graph ayrıca çözülür. |
| [laravel/tinker](https://packagist.org/packages/laravel/tinker) | `^2.9` / 2.10.1 | Güncel2.11.1 de Illuminate12'ye kadar. **3.0.2** 13 kabul eder; root2→3 gerekir. |
| [league/flysystem-aws-s3-v3](https://packagist.org/packages/league/flysystem-aws-s3-v3) | `^3.0` / 3.29.0 | Direct framework bağı yok; **3.35.3** PHP^8.0.2, Flysystem^3.10, AWS SDK^3.371.5. Root içinde; gerçek S3/CDN işlemi sınanmadı. |
| [livewire/livewire](https://packagist.org/packages/livewire/livewire) | `^3.5` / 3.6.3 | Lock13 yok; **3.8.10** Illuminate10…13 kabul eder, root içinde. Son4.4.7 bu proje için zorunlu değil; Rappasoft3.8 de Livewire3 ister. |
| [lorisleiva/laravel-actions](https://packagist.org/packages/lorisleiva/laravel-actions) | `^2.8` / 2.9.0 | Lock13 yok; **2.12.0** PHP^8.2, contracts11/12/13, Lody^0.7; root içinde. Action/container nullable davranışı ayrıca sınanır. |
| [maatwebsite/excel](https://packagist.org/packages/maatwebsite/excel) | `^3.1` / 3.1.64 | Lock13 yok; **3.1.70** support5.8…13, mevcut root içinde. Son4.0.3 / PhpSpreadsheet5 major geçişi zorunlu değil. Mevcut gerçek XLSX testleri hedefte tekrarlanır. |
| [pharaonic/laravel-settings](https://packagist.org/packages/pharaonic/laravel-settings) | `^1.2` / 1.2.0 | Son stable yine1.2.0; framework>=6, PHP>=7.2: geniş aralık 13'ü dışlamaz. Yeni framework runtime test edilmedi; PHP8.4 nullable uyarısı ayrı. Kullanıcı koruyabileceğimizi söyledi. |
| [rappasoft/laravel-livewire-tables](https://packagist.org/packages/rappasoft/laravel-livewire-tables) | **`3.5.6` sabit** / 3.5.6 | LockIlluminate10/11; 12'de bile engel. **3.8.0** contracts/support10…13 ve Livewire^3.0. Geçici aday var; tam sürüm sabiti değişmeden Composer bunu seçemez. 61 yayımlanmış view/31 tablo uyumu gerekir. |
| [sentry/sentry-laravel](https://packagist.org/packages/sentry/sentry-laravel) | `^4.11` / 4.13.0 | Lock13 yok; **4.28.0** support6…13, Sentry SDK^4.31; root içinde. Gerçek dış raporlama bu tur tetiklenmedi. |
| [spatie/laravel-backup](https://packagist.org/packages/spatie/laravel-backup) | `^9.1` / 9.3.2 | Güncel9.4.1 support^12.40; 13'ü kabul etmez. **10.3.3** PHP^8.3, Illuminate^12.40\|^13, DB Dumper4; root9→10/API/config incelemesi gerekir. |
| [spatie/laravel-permission](https://packagist.org/packages/spatie/laravel-permission) | `^6.9` / 6.17.0 | Lock13 yok; **6.25.0** auth/container/contracts/database8…13, root içinde. Son8.3.0'a geçiş şart değil. Mevcut rol/izin sistemi korunur. |
| [spatie/laravel-translatable](https://packagist.org/packages/spatie/laravel-translatable) | `*` / 6.11.4 | Lock13 yok; **6.14.1** PHP^8.3, database/support11/12/13. Wildcard seçebilir; sonraki plan kontrollü sürüm aralığını belirler. |
| [webmozart/assert](https://packagist.org/packages/webmozart/assert) | `^1.12` / 1.12.1 | Framework bağı yok; mevcut1.12.1 PHP^7.2\|^8.0 kabul eder. Son2.4.1'e geçiş zorunlu kanıtlanmadı. |
| [zoha/laravel-meta](https://packagist.org/packages/zoha/laravel-meta) | `^2.0` / 2.0.2 | Son stable aynı; framework>=8, 13'ü dışlamayan geniş aralık. Metable model/metadata/bölüm okuma-yazması hedef framework üzerinde sınanır. |

## 4. Development paketleri

| Paket | Root / lock | Genel değerlendirme |
|---|---|---|
| [barryvdh/laravel-debugbar](https://packagist.org/packages/barryvdh/laravel-debugbar) | `*` / 3.15.4 | Lock13 yok; güncel4.4.4 metadata Illuminate11/12/13, PHP^8.2. Kaynak/namespace **Fruitcake\LaravelDebugbar**, eski paket ismi için replace var. Sadece wildcard update ile eski namespace/config kullanımının çalışacağı varsayılmaz. |
| [fakerphp/faker](https://packagist.org/packages/fakerphp/faker) | `^1.23` / 1.24.1 | Son stable1.24.1, PHP^7.4\|^8; direct framework engeli yok. |
| [laravel/pint](https://packagist.org/packages/laravel/pint) | `^1.13` / 1.22.1 | 1.32.1 PHP^8.3; root içinde. Bu rapor için bütün kod formatter'dan geçirilmedi. |
| [laravel/sail](https://packagist.org/packages/laravel/sail) | `^1.33` / 1.42.0 | Lock13 yok; 1.68.0 Illuminate9.52.16…13 ve Symfony6/7/8; root içinde. Runtime Docker değişimi uygulanmadı. |
| [mockery/mockery](https://packagist.org/packages/mockery/mockery) | `^1.6` / 1.6.12 | 1.6.15 PHP>=7.3; PHPUnit<8 ile conflict. PHPUnit12 doğrudan dışlanmıyor; bütün test graph'ı ayrıca çözülür. |
| [nunomaduro/collision](https://packagist.org/packages/nunomaduro/collision) | `^8.0` / 8.8.0 | 8.9.5 PHP^8.2; conflict framework<11.48 veya>=14, PHPUnit<11.5.50 veya>=14. Aday13.34/12.5.38 direct conflict aralığına girmez; Symfony console koşulu ayrıca çözülür. |
| [phpunit/phpunit](https://packagist.org/packages/phpunit/phpunit) | `^11.0.1` / 11.5.19 | PHP8.3 için **12.5.38**; root11→12 değişimi ve test API/config uyumu gerekir. En yeni13.4.1 PHP>=8.4.1 olduğundan bu hedefte aday değildir. |

## 5. Kırılma riski salt paket seçiminin ötesinde

### Alert API

[Paketin v4.2 README'si](https://raw.githubusercontent.com/jantinnerezo/livewire-alert/v4.2.0/README.md) v4'ü Facade/fluent API kullanan major yeniden düzenleme olarak açıklar. Projede `CustomLivewireAlert` mevcut `LivewireAlert` trait'ini ve `$this->alert(...)` çağrısını kullanır; iki layout `<x-livewire-alert::scripts/>` içerir. Bu nedenle v4 require aralığının 13'ü kabul etmesi tek başına uygulama uyumu değildir. Trait/toast/native onay/modal davranışı için dosya haritası ve regresyon gerekir. Bu tur uyarlanmadı.

### Rappasoft geçici sürüm

Kullanıcı Rappasoft'u **Laravel yükseltmesinden sonra** kaldıracak. Mevcut sürümü kaldırmadan yükseltme için3.8.0 metadata açısından adaydır; tercih kesinleştirilmedi. `19` içindeki yayımlanmış Blade/ortak configure/filter/31 tablo haritası göz ardı edilmez. Livewire3.8 ile eş değerlendirme gerekir. OneUI devam, Vue.js eklenmez; hedef Bootstrap Table kararı değişmedi.

### Dil tarayıcı

`App\Helpers\TranslationHelper::scan` doğrudan Druc CachedFileTranslations/FileTranslations/MissingTranslations/RequiredTranslations sınıflarını kullanır; panel çeviri formu bunu çağırır. Tarayıcı paketi kullanılmıyor diye kaldırılamaz. Son stable2.3'ün contracts13'ü dışlaması halen açık engeldir. Koruma/yama/fork/yerine özellik seçimi bu genel raporda yapılmadı.

### Eski geniş aralıklı paketler

Settings>=6 ve Meta>=8 aralıkları Composer'da kabulü mümkün kılar; paketin 13 CI testi olduğu anlamına gelmez. Model boot, metadata relation, settings cache/save ve `DefaultOrderBy` adayları hedef uygulamada test edilir. Settings ve Meta vendor'ları bu tur değiştirilmedi.

## 6. Henüz yapılmayan uyumluluk kontrolleri

- Bütün transitive require/conflict/replaces, platform eklentileri ve advisory denetimini kapsayan Composer çözümlemesi.
- Son kullanıcı kararı: doğrudan Laravel13 hedef kombinasyonu çözülür, Laravel12 kurulmaz. 12 kılavuzundaki değişiklikler yalnız uyumluluk kontrolüne dahildir; eski ara sürüm kombinasyonu önerisi uygulanmaz.
- Gerçek canlı PHP/MySQL/extension bilgisi; kullanıcı SQL/migration dökümü ikinci adım olarak bekleniyor.
- Hedefte provider boot, auto route binding, nullable Action/mount, `DefaultOrderBy` model boot, cache object allowlist, Bootstrap pagination adları ve lig upsert.
- 13 üzerinde otomatik 245+7 baseline ve gerçek tarayıcı `23` akışlarının tekrar koşulması.
- API değişimleri için upstream changelog/config karşılaştırmalarının bütünü; bu rapor major uyarlama planı değildir.

## 7. Şimdilik karar gerektiren noktalar

1. Langscanner13 engelinin nasıl aşılacağı.
2. Alert4 API geçişi veya kullanıcı onaylı alternatif; mevcut3'ü 13 uyumlu sayma.
3. Backup10 major/config/dumper değişimi; gerçek backup gönderimi/depolaması ayrıca onay.
4. Rappasoft3.8 geçici adayının seçimi ve yayımlanmış view regresyonu.
5. Debugbar yeni paket/namespace seçimi ve wildcard yerine kontrollü aralık.

Livewire4, Excel4, Permission8 veya Intervention4'e zorunlu toplu geçiş kanıtlanmadı. Daha küçük aynı-major adaylar genel planı sadeleştirebilir; **tam Composer/runtime çözümü yapıldı** diye yorumlanmaz. Bu kararlar uygulamadan önce ayrıntılı geçiş planında netleşir.

## 8. Güncelleme ve kaynak kaydı

Metadata sürümleri bu tarihe bağlıdır; sonraki uygulama gününde seçilen adayların require/conflict ve release notları yenilenir. Tablolardaki Packagist linkleri doğrudan pakete gider. Exact JSON çalışma kanıtı `/tmp/eduan-package-metadata.json`, candidate kaydı `/tmp/eduan-package-candidates.json`; geçici makine dosyalarıdır, Git'e eklenmedi. Composer dosyalarının bu tur diff'i yoktur.
