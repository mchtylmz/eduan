# Paket kararları, Laravel 13 ve SiteGround hazırlığı

**Güncel uygulama:** [34-PAKET-CIKARMA-VE-PROJE-TEMIZLIGI.md](34-PAKET-CIKARMA-VE-PROJE-TEMIZLIGI.md). Yedi paket gerçek composer/lock/vendor’dan kaldırıldı; 152 → 131 bağımlılık, kalan sürümlerde değişiklik yok. PHP 8.5 ve PHPUnit 13 hedefi kesin; mevcut Laravel 11/LW3 korunuyor. Ara sürümde tablolar/tarayıcı/yedek karşılıkları beklenir; canlıya alınmaz. Dar sonuç 6 test/21 doğrulama.

**Son karar / kontrol:** [33-PHP85-GECIS-KONTROLU-VE-SSH-KANITI.md](33-PHP85-GECIS-KONTROLU-VE-SSH-KANITI.md). Hedef PHP8.5; Backup/S3 kaldırma kesinleşti, Alert4 devam. PHP8.5.7 üzerinde hedef graph dry-run başarılı;390 kaynak dosyasında0syntax hata,21 dosyada27nullable uyarı. Sunucu çıktısıPHP8.2.34'tür; canlıPHP değişimi deployment aşamasında. Önceki PHP8.3/PHPUnit12 ve bekleyen Backup kararları tarihsel; gerçek Composer kurulumu yapılmadı.

5 Ekim 2026. Kullanıcının yeni paket seçimi, `24` belgesindeki değerlendirme adaylarından üstündür. Bu tur kaynak/metadata araştırması ve rehber güncellemesidir. Composer JSON/lock/vendor, uygulama kodu, sunucu PHP ayarı, cron veya DB değiştirilmedi. Kullanıcı açıklamaları aldıktan sonra Composer çıkarma/güncelleme aşamasına geçmek istiyor. Backup/S3 seçimi ve “5 script” ifadesi iki ayrı soruyla kullanıcıya gönderildi; cevap gelmeden seçim yapılmaz.

## 1. PHP ve kurulum zemini

- Laravel 13 minimum **PHP 8.3** gerektirir: [resmî deployment gereksinimleri](https://laravel.com/framework/docs/13.x/deployment).
- Yerel Sail PHP 8.3 zemini bu minimumla uyumludur; hosting web PHP'sinin 8.2 olduğu kullanıcı bilgisidir. Web ve SSH/cron PHP'si ayrı doğrulanmalıdır.
- Hazırlık hedefi PHP 8.3'ün hostingde sunulan güncel bakım sürümüdür. Bu, canlı ayarın değiştirildiği veya başka PHP sürümlerinin test edildiği anlamına gelmez.
- Doğrudan Laravel 13 kurulur; Laravel 12 kurulmaz. 12 ve 13 değişiklik kılavuzları birlikte okunur.
- 27 doğrudan paketin güncel stable metadata'sı `https://repo.packagist.org/p2/<vendor>/<package>.json` üzerinden tekrar okundu. Bu bilgiler require/conflict beyanıdır; tam Composer graph/solver veya uygulama uyumu kanıtı değildir.
- Çalışma kaydı `/tmp/eduan-package-selection-oct5.json`; geçici makine dosyasıdır. P2 metadata'nın eski sürüm kayıtları sıkıştırılmış olabilir; boş/eksik require alanını “bağımlılığı yok” diye yorumlama. Seçili Image/Excel alt bağımlılıklarının composer.json dosyaları ayrıca kaynak sürümlerinden okundu.
- Bu belgedeki aralıklar uygulama için aday Composer aralıklarıdır. Güncelleme sırasında graph ve güvenlik/advisory koşulları ayrıca değerlendirilir; platform koşullarını görmezden gelerek kurulum yapılmaz.

## 2. Yirmi runtime paketin karar listesi

| Paket | Kullanıcı kararı / hedef aday | Yapılacak uyarlama veya kontrol |
|---|---|---|
| archtechx/enums | Güncelle: `^1.1`, stable 1.1.2 | Framework bağı yok; mevcut enum değerleri/yardımcıları korunur. |
| druc/laravel-langscanner | **Kaldır; kendi koduyla karşılık geliştir** | Stable 2.3.0 contracts 13'ü kabul etmiyor. Çeviri tarama özelliği kaybolmayacak. |
| intervention/image-laravel | Koru/güncelle: `^1.5.9` | 1.5.9 Laravel 13 kabul eder; Image 3.11 serisi ve GD sürücüsü korunur. Image 4 geçişi bu seçimde istenmedi. |
| izniburak/laravel-auto-routes | Koru: `^2.1`, stable 2.1.0 | Daha yeni stable yok. Geniş/aralıksız framework metadata'sı runtime garantisi değildir; panel route/binding kontrol edilir. |
| jantinnerezo/livewire-alert | Laravel 13 uyumlu **4.2.0 / `^4.2` korunacak aday** | Trait API → Facade API; mevcut CustomLivewireAlert çağrı standardı korunarak iç uygulama uyarlanır. Bölüm 4. |
| laravel/framework | Güncelle: `^13.0`, stable 13.34.0 | Doğrudan 13; provider/route/config/Carbon/cache ve test uyarlamaları yapılır. |
| laravel/telescope | **Kaldır**, karşılık istenmedi | Provider kaydı, provider sınıfı/config ve prune schedule bağlantıları temizlenir; mevcut DB migration/geçmiş tabloları kendiliğinden silinmez. |
| laravel/tinker | Koru/güncelle: `^3.0`, stable 3.0.2 | CLI geliştirme aracı; eski 2 serisi yerine 13 uyumlu 3 adayı. |
| league/flysystem-aws-s3-v3 | **Koşullu; Backup ile birlikte karar bekliyor** | Stable 3.35.3; güncel explicit S3 çağrısı yedek aktarım işindedir. Bölüm 5. |
| livewire/livewire | **Kullanıcı Livewire 4 seçti:** `^4.4`, stable 4.4.7 | Rappasoft kaldırılmalı. Livewire 4 config, JS/Blade ve test API uyarlamaları ayrı kontrol edilir. |
| lorisleiva/laravel-actions | Koru/güncelle: `^2.12`, stable 2.12.0 | 13 aralığı var; Action çağrıları/container ve mevcut kısa sınıf standardı korunur. |
| maatwebsite/excel | Kullanıcının “en güncel” isteği: **`^4.0`, stable 4.0.3** | PhpSpreadsheet `^5.9` geçişi; gerçek XLSX veri/başlık/toplam/stil/formül ve download testleri. `24` belgesindeki daha dar 3.1.70 adayı yeni kararla hedef değildir. |
| pharaonic/laravel-settings | Koru: `^1.2`, stable 1.2.0 | Yeni stable yok. Framework>=6 beyanı 13'ü dışlamıyor; settings okuma/yazma/cache boot hedefte test edilir. |
| rappasoft/laravel-livewire-tables | **Kaldır; kendi tablolarımızı geliştir** | Lara Football referanslı Blade + Bootstrap Table + server JSON total/rows. Livewire 4 ile Rappasoft 3.8 de kullanılamaz. |
| sentry/sentry-laravel | **Kaldır**, karşılık istenmedi | Config ve bootstrap içindeki import/yorumlanmış entegrasyon bağlantıları kontrol edilir. Laravel normal logları kalır. |
| spatie/laravel-backup | **Koşullu; karar bekliyor** | Korunursa `^10.3`, stable 10.3.3; kaldırılırsa kendi yedek çözümü gerekir. Bölüm 5. |
| spatie/laravel-permission | Koru/güncelle: **6 serisi `^6.25`, stable 6.25.0 adayı** | Laravel 13 kabul eder. Kullanıcı ayrıca 8'e major geçiş istemedi; rol/izin ve öğretmen kapsamı korunur. |
| spatie/laravel-translatable | Koru/güncelle: **`^6.14`, stable 6.14.1 adayı** | Wildcard yerine kontrollü aralık önerilir; JSON alan çevirileri korunur. Laravel'in JSON/PHP UI çevirilerinden ayrı özelliktir. |
| webmozart/assert | Koru: mevcut **`^1.12` / 1.12.1** | Girdi/çıktı doğrulama yardımcıları; uygulamada doğrudan kullanım bulunmadı, mevcut cron-expression transitively `^1.0` istiyor. Son 2.4.1'e major geçiş kullanıcının “koru” kararından çıkmaz. |
| zoha/laravel-meta | Koru: `^2.0`, stable 2.0.2 | User ve TestsSection Metable kullanır; kullanıcı arayüz tercihleri ve sınav bölüm içerik/meta alanları test edilir. |

## 3. Yedi development paketi

| Paket | Karar / PHP 8.3 hedef adayı | Kullanım ve kontrol |
|---|---|---|
| barryvdh/laravel-debugbar | **Kaldır**, karşılık istenmedi | LessonTable'daki eski Debugbar importu ve yayımlanmış config kontrol edilir. |
| fakerphp/faker | Koru: `^1.24`, stable 1.24.1 | Factory/sentetik test verisi; canlıya seed çalıştırma yetkisi değildir. |
| laravel/pint | **Koru/güncelle:** `^1.32`, stable 1.32.1 | PHP^8.3 gerektirir; Laravel 13 için kaldırılması gerekmez. Dev formatter, production runtime gereksinimi değildir. |
| laravel/sail | Koru/güncelle: `^1.68`, stable 1.68.0 | Yerel Docker geliştirmesi; SiteGround'da Docker varsayılmaz. |
| mockery/mockery | Koru/güncelle: `^1.6`, stable 1.6.15 | require-dev'de. TestCase partialMock; mail, cache ve izin testlerinde spy/mock kullanımı var. PHPUnit 12 graph ve çalışma uyumu test edilir. |
| nunomaduro/collision | Güncelle: `^8.9`, stable 8.9.5 | Hata/test çıktısı; Laravel13/PHPUnit12 doğrudan conflict kapsamı dışında. Symfony koşulları tam solver'da kontrol edilir. |
| phpunit/phpunit | PHP8.3 için güncelle: **`^12.5`, stable 12.5.38 adayı** | En yeni 13.4.1 PHP>=8.4.1 istiyor. PHP8.3 tabanını sırf test aracı için yükseltme; PHPUnit12 XML/API/deprecation ve Mockery uyumu kontrol edilir. |

Production kurulumu no-dev olduğunda Pint, Sail, Faker, Mockery, Collision, PHPUnit sunucu runtime paketleri değildir. Tinker mevcut runtime grubundadır; grubunu değiştirme kararı alınmadı.

## 4. Alert API değişimi ne demek?

Laravel 13 desteği var: Alert 4.2 Illuminate10/11/12/13 ve Livewire3/4 kabul eder. Sorun framework desteği değil, paketle konuşan PHP/JS çağrılarının değişmesidir.

- Bugün `app/Traits/CustomLivewireAlert.php`, `Jantinnerezo\LivewireAlert\LivewireAlert` trait'ini kullanıyor; error/success içinde `$this->alert(type: ..., options: ...)` çağırıyor.
- 4 serisindeki kullanım `Jantinnerezo\LivewireAlert\Facades\LivewireAlert` ile fluent API'dir; örnek `LivewireAlert::title(...)->success()->show()`.
- Uygulamanın `message()->toast()->success()/error()` gibi basit çağrı zinciri mümkün olduğunca korunur; ortak trait'in içi uyarlanır. Bütün form sınıflarını sırf paket API'si değişti diye yeniden yazma.
- İki layout'taki `<x-livewire-alert::scripts/>` ve yeni paket JS listener yüklemesi birlikte kontrol edilir. Mevcut SweetAlert2 ön yüzde ve panelde yerel asset olarak zaten yükleniyor; ikinci kopya/CDN/Filament eklemek varsayılan değildir.
- Toast konumu, timer, kapatma, mesajın text olarak ele alınması, NL/EN/TR, modal/confirm ve Livewire4 sonrası gösterim korunmalıdır. Sayfa HTTP200 tek başına toast browser kabulü değildir.
- API değişimi “paket mutlaka kaldırılacak” demek değildir. Kullanıcının 13 destekliyorsa koru tercihi için uyumlu 4.2 adayı var; karşılık olarak yeni uyarı paketi seçilmedi.

Kaynak: [Alert 4.2 README](https://github.com/jantinnerezo/livewire-alert/blob/v4.2.0/README.md), [Alert dokümantasyonu](https://livewire-alert.jantinnerezo.me/).

## 5. Backup ve S3 — kaynak koddan bulunan gerçek kullanım

### Mevcut akış

1. `bootstrap/app.php` DB için `backup:run --only-db` planlıyor. `wednesdays()->saturdays()` ardışık çağrıları günleri birleştirmez: mevcut framework `days()` aynı cron alanını tekrar yazar; etkin gün **cumartesi**, saat **01:30**, uygulama timezone'u.
2. Dosya yedeği `backup:run --only-files`: pazartesi **02:30**.
3. `config/backup.php` kaynak dosyaları proje kökü; vendor/node_modules/storage hariç. MySQL dump + gzip compressor; ZIP önce local diske gider. Uygulamanın `public/uploads` alanı proje kökü içinde olabilir; storage tamamının hariç tutulması mevcut kapsamdır.
4. Asıl DB dump exclusion ayarı `config/database.php` mysql.dump.exclude_tables içindedir. `config/backup.php` içinde ayrıca camelCase excludeTables içeren source.mysql bloğu var; korunursa/yazılırsa doğru yerde gerçek davranış karşılaştırılmalıdır.
5. `UploadBackupToCloudflare` her gün **04:30** queue job olarak planlanıyor. storage altındaki ZIP'leri ve proje logs altındaki ZIP/GZ dosyalarını S3 diskiyle gönderiyor, r2 log kanalına sonucu yazıyor.
6. `config/filesystems.php` S3 credentials/bucket/endpoint'i AWS_* env anahtarlarından alıyor. Job adı ve r2 logu Cloudflare niyetini gösterir; canlı endpoint/bucket, mevcut başarılı gönderim ve private erişim bu tur doğrulanmadı.
7. Kaynakta açık `Storage::disk('s3')` çağrısı yalnız bu job'da bulundu. Panel upload, ikon ve LaTeX işlemleri local/public disk kullanıyor. SES/SQS/DynamoDB config şablonları S3 adapterinin başka gerçek kullanımını kanıtlamaz; canlı FILESYSTEM_DISK ve dış cron kullanımı ayrıca kontrol edilir.

### İki paketi kaldırma önerisinin sınırı

Uygulamadaki belirgin S3 görevi yedek aktarımıdır. Kullanıcı paketsiz bir karşılık istediğinde **Backup ve S3 adapterini birlikte kaldırmak uygun bir adaydır**; mevcut yedekleme özelliğinin karşılığı zorunludur. Son seçim kullanıcı yanıtını bekler. “S3 adapterini kaldırmak” gelecekteki CDN planından vazgeçmek anlamına gelmez; görsel/CDN ihtiyacı ileride ayrı tasarlanır.

Korunursa Backup **10.3.3** mevcut metadata'da PHP^8.3, Laravel12.40/13, DbDumper4 ister. 9→10 geçişi: encryption null/false yerine none; event nesne alanları yerine diskName/backupName primitive alanları; consoleOutput yerine backupLogger; custom cleanup typed config; disableNotifications kaldırılması. Projede encryption=default zaten kabul edilen değerdir. Bu değişikliklerin tümü uygulamada kullanılan özel kod olduğu anlamına gelmez; mevcut listeners/cleanup/dump/notification config'i karşılaştırılır. Kaynak: [Backup 10.3.3 kaynağının upgrade notu](https://github.com/spatie/laravel-backup/blob/1ab5c2211481d5f962d118d0d35c40b9abf5b3f0/UPGRADING.md), [paket metadata](https://packagist.org/packages/spatie/laravel-backup).

### Bu araştırmada görülen sorunlar — otomatik düzeltilmedi

- Job upload sonucunu kontrol etmeden `unlink($file)` yapıyor. S3 throw=false olduğundan başarısız gönderimde de yerel yedek kaybolabilir.
- Yedek yüklemesinde visibility=public isteniyor. Bu çağrı gerçek R2 bucket'ının public olduğunu kanıtlamaz; özel yedek sözleşmesi ve bucket erişimi doğrulanmalıdır.
- Aynı glob pattern iki kez birleştiriliyor; dosya listesi tekilleştirilmiyor.
- storage hariç tutulması / source.mysql config bloğu / hafta günü zinciri yeni çözümde bilinçli kapsam ve davranış karşılaştırması gerektiriyor.

### Kendi çözümümüz için taslak

- “5 script” PHP scripti mi, beş dosya mı soruldu; sayı/uygulama dili varsayılmadı.
- Kullanıcının hedef saati **her gün 03:00**; timezone, saklama süresi, dosya dahil/hariç kapsamı, başarısızlık bildirimi ve şifreleme gerçek uygulamadan önce netleştirilir.
- SSH, uzaktaki hesabın terminal erişimidir; web PHP ayarı veya root yetkisi sağladığını varsayma. Binary/cron/süre/disk/exec/outbound erişimi doğrulanınca uygulanabilirlik netleşir.
- DB dump mümkünse single-transaction tutarlı snapshot; private geçici dosya, sıkıştırma, benzersiz isim, boyut/checksum kaydı ve çakışmayı önleyen kilit.
- R2 S3 uyumlu API imzası gerekir. Composer S3 paketini kaldırınca PHP `Storage::disk('s3')` kendiliğinden çalışmaz. Hostingdeki cURL/SigV4 veya başka doğrulanmış araç koşuluna göre kendi istemci kodu seçilir; binary yolu/CLI aracı varmış gibi komut uydurulmaz.
- Yalnız başarı ve yüklenen obje doğrulamasından sonra yerel dosya temizlenir. Hata/retry durumunda dosya saklanır; secrets/log/SQL satırları web'e açılmaz.
- Local ve R2 retention işlemleri ayrı, onaylı kapsamda uygulanır. Önce restore denemesi gerekir; dosyanın yüklenmiş olması geri yüklenebilirlik kanıtı değildir.
- Scheduler içinde çalışacaksa mevcut günlük backup/upload görevleri yeni görevin yerine geçmesiyle temizlenir; iki paralel yedek sistemi varsayılan değildir. Canlı cron kurulumu bu belgeyle yapılmadı.

## 6. Yerine kod yazılacaklar ve kaldırma sırası

| Kaldırılacak | Korunacak yetenek / karşılık | Hedef dar doğrulama |
|---|---|---|
| Langscanner | Mevcut config'teki app/resources tarama, vendor views hariç; __/trans/trans_choice/@lang/Lang/choice anahtarları, mevcut çevirileri koruyan missing-key listesi | PHP/Blade string/escape/çoğul, iç içe anahtar ve nl/en/tr JSON/PHP ayrımı; panel tarama izni/save akışı |
| Rappasoft | Mevcut 31 tablo ailesinin kolon/filtre/sayfalama/arama/sıralama/action/export/kapsam davranışı; Lara Football Blade Bootstrap Table JSON total/rows | Sayfa sayfa feature + ilgili browser pilotu, boş/dolu/sort/filter/pagination/izin ve CRUD sonrası yenileme |
| Backup + S3 (karar bekler) | DB + onaylı dosya kapsamı, private R2 aktarımı, cron, hata/retry/retention/restore | Başarılı/başarısız upload, aynı anda çalışma, dosya korunması, geçerli dump ve restore; canlı gönderim ayrıca onaylı |
| Telescope / Sentry / Debugbar | Kullanıcı karşılık istemedi | Provider/bootstrap/import/config/schedule kalıntısı yok, normal uygulama log/hata davranışı korunuyor |

Yeni kullanıcı sırası: **seçilen paketleri çıkar → doğrudan Laravel13 + Livewire4 kur → karşılık/uyarlamaları tamamla → hedefte test et**. Bu, önceki `19` içindeki yükseltme sonrası Rappasoft kaldırma sırasını değiştirir. Mevcut Rappasoft3.8 bile Livewire^3 ister; Livewire4 için geçici Rappasoft güncellemesi seçilmedi.

Paketleri kaldırdıktan hemen sonra uygulamayı çalışır kabul etme: TranslationHelper Druc importları ve Rappasoft sınıfları kullanıldıklarında eksik sınıf hatası verir. Composer scriptleri artisan package:discover/vendor publish yapabilir; uygulama boot'unu bozan provider bağlantıları kontrollü temizlenmeden standart update scriptleri başarı garantisi değildir. Ara kırık aşama canlıya dağıtılmaz. Hedef vendor doğrudan 13 için çözülür; no-scripts kullanımı gerekirse geçici kurulum tekniğidir, son package-discover/runtime doğrulamasının yerine geçmez.

Livewire4 rehberi config layout/placeholder adları, class component standardı, wire:model blur/change davranışı, component kapanışları, JS event/morph/navigation ve upload/pagination/form işleyişi için okunur. Yeni SFC/Vue/Flux veya mimari dönüşüm istenmedi: mevcut class + Blade standardı korunur. Kaynak: [Livewire4 upgrade](https://livewire.laravel.com/docs/4.x/upgrading).

## 7. SiteGround web PHP, SSH ve cron hazırlığı

Paylaşılan hesabın dashboard sayfası web okuyucuda alınamadı; hesabın PHP sürüm seçenekleri/limitleri görülmedi. Aşağıdaki yönlendirme resmî SiteGround belgelerine dayanır; canlı ayar değiştirilmedi.

1. Site Tools → **Devs → PHP Manager**.
2. PHP version yanındaki kalem/Change PHP version; sürümü elle seçme seçeneği ve **8.3** bakım sürümü.
3. Kaydetme canlı bir değişikliktir; önce test ortamındaki mevcut uygulamanın PHP8.3 uyumu ve doğru site seçimi doğrulanır. Ultrafast/Standard PHP modu ayrıca değiştirilmez.
4. HTTP tarafındaki PHP/extension ile SSH/cron CLI tarafı ayrı kontrol edilir. PHP Manager, CLI binary yolunu kanıtlamaz. Eski .htaccess PHP handler override varsa yeni seçimin etkisi kontrol edilir; rastgele satır silinmez.
5. SSH bilgisayarın terminalinden kullanılabilir. Giriş bilgileri/key, erişilebilir binary ve yetkiler gerekir; hesabın CLI varsayılanının değiştirilebildiği varsayılmaz.
6. Salt okunur SSH kontrolleri: `php -v`, `php --ini`, `php -m`, `command -v php`, `command -v mysqldump`, `command -v curl`, `curl --version`. Bunlar bu tur sunucuda çalıştırılmadı. Yanlış eski PHP görünürse destekten **PHP8.3 CLI tam yolu ve cron'da kullanımı** istenir; php83 alias'ının mevcut olduğunu varsayma.
7. Devs → Cron Jobs içinde mevcut scheduler ve stop-when-empty worker komutunun da doğrulanmış PHP8.3 binary'sini kullanması gerekir. Gerçek binary/proje yolu bilinmeden çalıştırılacak cron komutu yazılmaz. Kullanıcının mevcut dakikalık worker/gecikme kararı korunur.
8. Hedef vendor kurulduktan sonra doğru CLI ile `composer check-platform-reqs --no-dev`; yerelde dev dahil platform kontrolü. Web modülleri ayrıca kontrol edilir. Bu tur bu hedef komutlar çalışmadı; eski vendor sonucu hedef13 kanıtı değildir.

Kaynaklar: [SiteGround PHP Manager](https://www.siteground.com/kb/what-is-managed-php), [Cron Jobs](https://www.siteground.com/kb/manage-cron-jobs), [SiteGround SSH/Site Tools](https://www.siteground.com/site-tools), [PHP handler kontrolü](https://www.siteground.com/kb/How_to_check_which_PHP_version_is_running_on_my_account).

### Extension ve hosting koşulları

| Katman | Gereken veya doğrulanacak |
|---|---|
| Laravel13 resmî minimum | ctype, curl, dom, fileinfo, filter, hash, mbstring, openssl, pcre, PDO, session, tokenizer, xml |
| Proje MySQL | pdo_mysql ve DB erişimi; web/CLI aynı hedefte çalışmalı |
| Excel4 / PhpSpreadsheet5.9 | gd, iconv, libxml, simplexml, xmlreader, xmlwriter, zip, zlib, ayrıca temel ctype/dom/fileinfo/filter/mbstring/xml |
| Mevcut Intervention GD config | gd ve kullanılan JPEG/PNG/WebP/GIF codec desteği; auto-orientation için EXIF desteği ayrıca kontrol; Imagick bu config'te zorunlu değil |
| Runtime | json/date vb. çekirdek modüller; mevcut kaynakların erişimi, storage ve bootstrap/cache yazımı, upload/post/memory/time limitleri |
| Kendi backup çözümü | mysqldump/MySQL istemci uyumu, gerekirse proc_open/exec izinleri, cURL HTTPS/SigV4 yeteneği, geçici disk/kota, cron süresi, R2 outbound443 ve TLS |
| Opsiyonel hız | OPcache açık/ayarları kontrol edilir; Redis mevcut cache driver ve hosting desteği belirlenmeden zorunlu yapılmaz |

Hosting destek mesajı taslağı, gönderilmedi:

> Laravel13 projemizi PHP8.3 üzerinde çalıştıracağız. Site Tools web PHP8.3 ayarıyla birlikte SSH ve cron için PHP8.3 binary tam yolunu paylaşabilir misiniz? CLI/web tarafında pdo_mysql, mbstring, curl, openssl, fileinfo, GD (JPEG/PNG/WebP), zip, iconv ve XML/DOM/SimpleXML/XMLReader/XMLWriter desteğini teyit etmek istiyoruz. MySQL dump aracının yolu/sürümü, proc_open/exec kısıtları, cron maksimum süresi, yedekleme disk kotası ve Cloudflare R2'ye HTTPS443 bağlantısı koşullarını da öğrenmek istiyoruz. Varsayılan CLI PHP değiştirilemiyorsa cron/Composer/Artisan için önerilen tam binary yolu nedir?

## 8. Cache standardının genişletilmesi

- Panel filtre seçenekleri korunacak ve uygun olanlar değişiklikte geçersizleşen cache kullanabilecek. Bu yeni paket gerektirmez; Laravel Cache kullanılabilir.
- Filtre seçenek cache'i ile total/rows sorgu cache'i ayrı tasarlanır. Ortak ders/konu/rol/dil seçeneklerinin kapsamı ayrı; öğrenci/sonuç seçimleri öğretmen/kullanıcı kapsamı içerir.
- Locale anahtarda zorunlu; kaydı çeviren DB alanları ve arayüz dilini karıştırma. Ön yüz public cache ile giriş yapmış kullanıcı cache'i ayrı ele alınır.
- Cache okumadan önce izin kontrolü; teacher id/erişim kapsamı ve gerekiyorsa izin sürümü anahtara girer. Ortak cache öğretmene başka okulun öğrencilerini göstermez.
- Table JSON anahtarı: tablo/veri sürümü + locale + erişim kapsamı + normalize filtre/search/sort/order + limit/offset/sayfa. Total ve rows aynı kapsamla üretilir.
- Allowlist sort/filtre alanları, bound SQL parametreleri, sayfa boyutu sınırı; search input'larının sınırsız kalıcı cache anahtarı üretmesine izin verilmez.
- Kullanıcının “bir değişiklik olana kadar” hedefi seçenek cache'inde ilgili CRUD/soft-delete/atama/izin/çeviri/sonuç değişimine bağlı invalidation ile uygulanır. Cache driver forever davranışı, tablo TTL ve key hacmi sayfa bazlı planlanır; bu tur sabit TTL seçilmedi.
- Tag desteğini file/database driver'da varmış gibi kabul etme; basit hedefli forget veya sürümlü anahtar mevcut driver'a göre seçilir.
- Yenile düğmesinin ilgili cache'i mi geçersizleştireceği/bypass mı yapacağı tablo başına netleşir; başka kullanıcının veya güvenlik limiter'ının cache'ini temizlemez.
- Mevcut FilterHelper seçeneklerinin bir kısmı zaten Cache::remember kullanıyor. `AppServiceProvider` production dışındayken her boot'ta cache flush yapıyor; `bootstrap/app.php` saatlik optimize:clear planlıyor; resetCache geniş temizlik yapıyor. Bunlar hedef uzun cache ömrünü keser. Bu araştırmada değiştirilmedi; ilgili dar cache fazında azaltma planlanır.
- Hesap/sonuç/panel JSON'unun uygulama cache'inde tutulması, bu cevapların PWA/CDN'de public veya offline saklanacağı anlamına gelmez.
- Kabul: sıcak/soğuk query sayısı ve süre, locale/kapsam izolasyonu, kayıt sonrası güncel filtre/total/rows, cache arızasında izin koruması. Eski sonuç toplam/lig kuralları değişmez.

## 9. Bu turun sonucu ve açık kararlar

- Kesin çıkarma: Langscanner, Telescope, Rappasoft, Sentry, Debugbar. Langscanner ve Rappasoft yeteneklerinin kendi kodumuzla karşılığı şart.
- Backup/S3 için iki karar sorusu gönderildi; yanıtlar geldiğinde bu belge güncellenir. PHP scripti mi beş dosya mı varsayılmadı.
- Kesin major hedefler: Laravel13, Livewire4, Tinker3; Excel için en güncel4.0.3. Alert13/LW4 uyumlu4.2 kullanımı açıklanmış adaydır.
- SiteGround web PHP8.3 + CLI/cron yolu ve modüller henüz sunucuda doğrulanmadı. Panel veya SSH ayarı değiştirilmedi.
- Composer çözümleme/kurulum ve yeni hedefte hata testleri henüz yapılmadı. Mevcut245+7 veya son4/107 sonuçları Laravel13/Livewire4 uyumu diye sunulmaz.
- Bu tur yalnız rehber değiştiği için PHPUnit/build/formatter çalıştırılmaz. Yeni doküman indeks ve yerel bağlantı kontrolü yeterlidir.
