# PHP 8.5 ortamı ve doğrudan Laravel 13 kurulumu

**Sonraki genel test kanıtı:** [36-LARAVEL13-GENEL-TEST-SONUCU.md](36-LARAVEL13-GENEL-TEST-SONUCU.md).262 PHP testinden152 geçti;78 dil fixture/9 kayıt kapsamı/23 Rappasoft ilk engeli var.7 PWA testi geçti. Aşağıdaki16 dar senaryo kaydı tarihsel kapsamıyla geçerlidir; genel başarı değildir.

5 Ekim 2026. Kullanıcı yerel PHP 8.5 ortamını hazırlamayı ve seçilen paketleri güncellemeyi açıkça onayladı. Paket temizliği34'ten sonra gerçek Composer/vendor yükseltmesi uygulandı. **Laravel 12 kurulmadı.**

## 1. Güncel gerçek durum

- Yerel uygulama container'ı **PHP 8.5.8** çalıştırıyor.
- Laravel **13.34.0**, Livewire **4.4.7**, Alert **4.2.0**, Excel **4.0.3**, PHPUnit **13.4.1** kurulu.
- Root PHP koşulu `^8.5`; gerçek Composer koşullarında platform taklidi eklenmedi.
- Lock işlemi: **6 kurulum / 104 güncelleme / 5 kaldırma**, toplam **131 → 132 bağımlılık**.
- `composer check-platform-reqs`: yeni container'da development dahil başarılı.
- `composer validate --no-check-publish --no-check-all`: başarılı.
- `composer audit --locked --format=json`: advisories ve abandoned listeleri boş, exit0.
- Bu sonuç paket/verilen dar test kabulüdür; bütün panel veya canlıya çıkış kabulü değildir.

## 2. Doğrudan paketlerin önce/sonra sürümleri

| Paket | Aşama34 sonrası eski lock | Gerçek yeni lock |
|---|---|---|
| `archtechx/enums` | v1.1.1 | v1.1.2 |
| `intervention/image-laravel` | 1.5.6 | 1.5.9 |
| `izniburak/laravel-auto-routes` | v2.1.0 | v2.1.0 |
| `jantinnerezo/livewire-alert` | v3.0.3 | v4.2.0 |
| `laravel/framework` | v11.44.7 | v13.34.0 |
| `laravel/tinker` | v2.10.1 | v3.0.2 |
| `livewire/livewire` | v3.6.3 | v4.4.7 |
| `lorisleiva/laravel-actions` | v2.9.0 | v2.12.0 |
| `maatwebsite/excel` | 3.1.64 | 4.0.3 |
| `pharaonic/laravel-settings` | 1.2.0 | 1.2.0 |
| `spatie/laravel-permission` | 6.17.0 | 6.25.0 |
| `spatie/laravel-translatable` | 6.11.4 | 6.14.1 |
| `webmozart/assert` | 1.12.1 | 1.12.1 |
| `zoha/laravel-meta` | v2.0.2 | v2.0.2 |
| `fakerphp/faker` | v1.24.1 | v1.24.1 |
| `laravel/pint` | v1.22.1 | v1.32.1 |
| `laravel/sail` | v1.42.0 | v1.68.0 |
| `mockery/mockery` | 1.6.12 | 1.6.15 |
| `nunomaduro/collision` | v8.8.0 | v8.9.5 |
| `phpunit/phpunit` | 11.5.19 | 13.4.1 |

Önceden çıkarılan yedi paket geri eklenmedi. Yeni geçişli paketler: symfony/polyfill-php82, polyfill-php84, polyfill-php85, polyfill-php86, sebastian/file-filter, sebastian/git-state. Kaldırılan geçişli paketler: symfony/polyfill-php83, sebastian/code-unit-reverse-lookup, sebastian/code-unit, ralouphie/getallheaders, ezyang/htmlpurifier.

## 3. Sail PHP 8.5 hazırlığı ve build sınırı

`docker-compose.yml` context'i `vendor/laravel/sail/runtimes/8.5`, image adı `eduan-sail-8.5/app` oldu. WWWGROUP/WWWUSER env değerleri eksikse1000 kullanılır; mevcut tanımlı değerler üstündür. MySQL image, volume, port ve bağlantı ayarları değiştirilmedi.

Yeni Sail1.68 Dockerfile ile `docker compose build laravel.test` denendi; Docker registry'den `ubuntu:24.04` metadata alırken **DeadlineExceeded** oluştu. Bu yeni Dockerfile build'i başarılı sayılmaz. Makinede önceden bulunan `sail-8.5/app:latest` salt okunur PHP/modül kontrolünden geçti: **PHP8.5.8**, bcmath/ctype/curl/dom/exif/fileinfo/gd/intl/mbstring/openssl/pdo_mysql/simplexml/xml/xmlreader/xmlwriter/zip/zlib mevcut.

Bu doğrulanmış yerel image, asıl etiketi korunarak projeye özel `eduan-sail-8.5/app` etiketiyle kullanıldı; image id `sha256:39b058e8f523740b2dcad0217f7b0d4ff1a31e95d350abc390b294f295c9951e`. `docker compose up -d --no-deps --no-build laravel.test` yalnız uygulama container'ını yeniden oluşturdu. MySQL aynı container id `69e97b34d650` ile healthy kaldı. Yeni Sail1.68 Dockerfile'dan temiz/reproducible build, registry erişimi düzeldiğinde ayrıca doğrulanmalıdır.

Bu image uygulama/test CLI8.5 kanıtıdır; SiteGround web/CLI modüllerini kanıtlamaz. Sunucu PHP değişimi canlıya alma aşamasına bırakılmıştır.

## 4. Uygulanan hedef sürüm uyarlamaları

### Alert4

[Alert resmi kaynak ve API](https://github.com/jantinnerezo/livewire-alert): v4 paketindeki `LivewireAlert` sınıftır; eski trait kullanımı kaldırıldı. Ortak `CustomLivewireAlert` public `message/toast/success/error` sözleşmesini korur; `new LivewireAlert($this)` ile mevcut seçenekleri aktararak çalışır. Timer3000, timerProgressBar, showCloseButton, toast/position ve text korunur.

Config v4'ün düz seçenek şemasına geçirildi. Eski `x-livewire-alert::scripts` bileşeni iki layout'tan çıkarıldı; zaten bulunan yerel SweetAlert2 script/CSS korunur. Yeni paket Livewire `js()` üzerinden çağrı gönderir; yeni CDN/build/paket eklenmedi.

### Livewire4

[Resmi yükseltme rehberi](https://livewire.laravel.com/docs/4.x/upgrading): `layout` → `component_layout`, `lazy_placeholder` → `component_placeholder`; mevcut view yolları korunur. Yeni bileşen oluşturma ayarı `type=class, emoji=false`; mevcut PHP class + ayrı Blade standardı korunur. `wire:model.blur/change` kullanan beş view'da `.live` eklendi; önceki sunucu güncelleme zamanı korunur. Tüm lazy bileşenler veya mimari yeniden yazılmadı.

### Excel4

Dört export sınıfının `collection(): Collection` dönüş tipi eklendi; v4 `FromCollection` arayüzü `Enumerable` dönüş tipi ister. Mevcut Collection bu arayüzü uygular. Başlık, kolon sırası, doğru/yanlış/toplam ve yüzde hesabı değiştirilmedi. Gerçek XLSX dosyaları yeniden okunarak sınandı.

### Laravel13

[13 rehberi](https://laravel.com/framework/docs/13.x/upgrade) ve [12 rehberi](https://laravel.com/framework/docs/12.x/upgrade) doğrudan geçişin davranış değişiklikleri için incelendi;12 kurulmadı.

- `DefaultOrderBy` trait'i boot sırasında `new $class()` üretmek yerine scope çalışırken builder'ın mevcut modelinden tablo adını okur. Laravel13 nested model boot'u yasaklar; kolon/sıralama yönü ve qualified tablo korunur.
- Middleware API `validateCsrfTokens` yerine `preventRequestForgery` kullanır. Önceki istisna listesi korunmuştur; yeni istisna veya middleware kaldırma eklenmedi.
- İki serve=true disk aynı `/storage` URL'siyle13'te açılışı engelledi. `local` `/storage` olarak kaldı; `private` `/private-storage` oldu. Kökler, local/private file okuma ve signed erişim şartı korunur. Eski özel disk signed URL'leri yeni path ile yeniden üretilmelidir; kaynak taramasında uygulamada doğrudan `disk('private')/temporaryUrl` tüketimi bulunmadı.
- Cache prefix ve session cookie ayarları eski explicit config'te durur. Cache payload/serialization veya session formatı bu fazda topluca değiştirilmedi. Laravel13 `serializable_classes` ve session JSON hedefi ayrıca değerlendirilmelidir; sessiz veri/session geçersizleştirmesi yapılmadı.

### PHP8.5

21 uygulama dosyasındaki **27 implicit nullable parametre** açık `?Type` yapıldı; null kabulü/varsayılanlar korunur. MySQL/MariaDB config'teki iki SSL sabit kullanımı `Pdo\Mysql::ATTR_SSL_CA` oldu. Son35 değişen PHP dosyasında lint/E_ALL kontrolü: syntax veya deprecated mesajı yok.

Settings1.2 ve Meta2.0.2 eski PHP8.5 uyarı adayları33 içinde hâlâ kayıttadır. Upstream/model nullable ve `(boolean)` cast konusu bu fazda vendor içine elle yama ile gizlenmedi; hedefin bütün vendor dosyalarında sıfır deprecation iddiası yok. Global host Composer2.8.5'in kendi PHAR bağımlılıkları PHP8.5 deprecation mesajı verdi; uygulama kaynak hatalarıyla karıştırılmaz.

## 5. Dar test kanıtı

Yalnız ayrı **testing DB**, PHP8.5.8 / PHPUnit13.4.1 ile aşağıdaki **16 farklı senaryo son ilgili koşularında geçti**. Bu tek tam suite koşusu değildir; başarılı senaryolar gereksiz tekrar çalıştırılmadı.

| Kapsam | Senaryo |
|---|---:|
| Alert4 success toast / error dialog | 2 |
| İki served disk rotası ve imzasız erişimin reddi | 1 |
| Kaldırılan paketler sonrası dört scheduler kaydı | 1 |
| Dört okunabilir ve doğru kolon/değerli XLSX export | 4 |
| Çeviri işlem izni / seçilen dil / yetki iptali | 3 |
| Giriş HTML/layout HTTP200 | 1 |
| Gerçek PNG'nin yerel public diskine yüklenmesi | 1 |
| Parola token tüketimi / tekrar kullanamama / fake mail | 1 |
| Süreli sınava dönüşte cevap ve ilk bitiş zamanının korunması | 1 |
| Süresiz test doğruluğu / tüm soruda tamamlama / yeni deneme | 1 |

İlk hedef koşusu Excel4 interface dönüş tipinde process fatal verdi; dönüş tipi uyarlanıp yeniden çalıştırıldı. Ardından DefaultOrderBy boot hatası7 senaryoyu etkiledi; ortak uyarlamadan sonra etkilenenler yeniden doğrulandı. Son iki solver başarısızlığı, önceki browser fixture'larının global `first/count` sorgularına karışmasıydı; yalnız bu test metotları exam/test + user kapsamında sorgulandı. Kalıcı testing fixture'ları silinmedi.

Son etkilenen9 senaryo koşusunda7 başarılı/2 fixture başarısız; bu iki senaryonun düzeltilmiş son koşusu **2 test/21 doğrulama** başarılı. İlk16 koşuda etkilenmeden başarılı kalan7 ayrı senaryo ile toplam16 farklı başarılı senaryo doğrulandı. Yeni Alert testleri toplam18 assertion içerir; disk testinde4 assertion vardır. Tam sayfa/rol/PWA/browser ve246+ eski baseline bu hedefte yeniden koşulmadı.

Formatter yalnız yeni FrameworkUpgradeTest, ortak Alert/sıralama traitleri ve v4 Alert config üzerinde başarılı; eski21 nullable dosyaya toplu formatter uygulanmadı. Mevcut phpunit.xml PHPUnit13'te çalıştı; config schema migration veya tam build yapılmadı.

## 6. Veri ve kapsam sınırı

- Ana yerel SQL tabloları/indeksleri/migration kayıtları değiştirilmedi; fresh/seed/drop/migrate yok.
- Testler yalnız testing bağlantısında, transaction/fake mail/queue ile çalıştı. Gerçek mail/R2 aktarımı ve scheduler görev yürütmesi yapılmadı.
- Önceki7 paket temizliği geri çevrilmedi;31 Rappasoft tablo sınıfı hâlâ yeni karşılığını bekler.
- Langscanner kendi tarayıcısı ve paketsiz DB/dosya ZIP/R2 backup henüz geliştirilmedi.
- Bütün ürün kabulü, gerçek tarayıcı Livewire JS/upload ve canlı worker kanıtı yoktur.
- Bu yerel ara sürüm **canlıya alınmaz**; tablolar/karşılıklar ve hedef sayfa kabulü tamamlanmalıdır.

## 7. Değişen kaynak dosyaları

- `composer.json`
- `composer.lock`
- `docker-compose.yml`
- `app/Mail/ContactReplyMail.php`
- `app/Mail/CreatePasswordMail.php`
- `app/Mail/NewsletterMail.php`
- `app/Mail/EmailVerificationMail.php`
- `app/Mail/RecoverPasswordMail.php`
- `app/Mail/SendEmailToUserMail.php`
- `app/Models/User.php`
- `app/Helpers/FilterHelper.php`
- `app/Actions/Blogs/CreateOrUpdateBlogAction.php`
- `app/Actions/Tests/CreateOrUpdateAction.php`
- `app/Actions/Roles/CreateOrUpdateAction.php`
- `app/Actions/Languages/CreateOrUpdateLanguageAction.php`
- `app/Actions/Faqs/CreateOrUpdateFaqAction.php`
- `app/Actions/Lessons/CreateOrUpdateLessonAction.php`
- `app/Actions/Exams/CreateOrUpdateExamAction.php`
- `app/Actions/Exams/CreateReviewAction.php`
- `app/Actions/Exams/UpdateExamFavoriteForUserAction.php`
- `app/Actions/Pages/CreateOrUpdatePageAction.php`
- `app/Actions/Topics/CreateOrUpdateTopicAction.php`
- `app/Http/Controllers/Frontend/TestController.php`
- `app/Livewire/Frontend/Leagues/LeagueResultTable.php`
- `app/Traits/CustomLivewireAlert.php`
- `app/Traits/DefaultOrderBy.php`
- `config/database.php`
- `config/filesystems.php`
- `config/livewire.php`
- `config/livewire-alert.php`
- `bootstrap/app.php`
- `tests/Feature/FrameworkUpgradeTest.php`
- `tests/Feature/TimedExamTest.php`
- `tests/Feature/PracticeTestSecurityTest.php`
- `app/Exports/FilterStatsQuestionsExport.php`
- `app/Exports/FilterStatsExamsExport.php`
- `app/Exports/FilterStatsTestsExport.php`
- `app/Exports/FilterStatsTopicsExport.php`
- `resources/views/livewire/backend/tests/section-form.blade.php`
- `resources/views/livewire/backend/tests/test-form.blade.php`
- `resources/views/livewire/backend/languages/language-translate-form.blade.php`
- `resources/views/livewire/frontend/tests/list-tests.blade.php`
- `resources/views/livewire/frontend/ai/answer-ai-vote.blade.php`
- `resources/views/backend/layouts/app.blade.php`
- `resources/views/frontend/layouts/app.blade.php`

Ayrıca README.md, AGENTS.md, Codex/index.html ve ilgili34/14/16/19 rehber bağlantıları güncellendi. `app/Livewire/Events.php` ve `app/Actions/Users/UpdatePasswordAction.php` başlangıçla aynı bırakıldı; mixed/union nullable tanımları zaten uygundu.

Özel kaynak snapshot'ı: `storage/app/private/codex-laravel13-upgrade/20261005/`,55 kaynak dosyası/manifest; bu sınırlı kaynak snapshot'ı DB yedeği veya bütün çalışma ağacı snapshot'ı değildir. Önceki DB yedeği31 kapsamıyla durur. Geçici loglar `/tmp/eduan-laravel13-*`; kaynak/test veri satırları rehbere alınmaz.

## 8. Sıradaki iş

1. Rappasoft31 aile için kendi Bootstrap Table server-side JSON tablolarını, filtre/izin/export/arama davranışını koruyarak aktarmak.
2. Kendi çeviri anahtarı tarayıcısını tamamlamak.
3. Lara Football gerçek backup script referansı alındığında DB/dosya ZIP ve R2 scriptini tamamlamak; saat/retention/restore şartlarını netleştirmek.
4. Settings/Meta PHP8.5 upstream uyumu, cache nesne şeması ve hedefte kalan sayfa/asset davranışlarını dar testlerle ele almak.
5. Karşılıklar tamamlandıktan sonra gerekli genel hedef suite ve kritik gerçek browser akışları; ardından SiteGround PHP/CLI/cron/deployment hazırlığı.
