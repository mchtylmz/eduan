# Son Rappasoft tablosu ve paket karşılıklarının fazları — 7 Ekim 2026

## 1. Kullanıcı kararı ve faz sınırı

Kullanıcı bütün Rappasoft tablo bağımlılığının kaldırılmasını, çeviri tarayıcısının sonraki işlem olmasını ve bağımsız bash yedeklemesini üçüncü fazda geliştirmeyi istedi. Bu tur **Faz1 uygulandı**, Faz2–3 planlandı. Kaynak/scheduler/ana DB çeviri şeması salt okunur kontrol edildi; yeni Composer işlemi veya migration yapılmadı.

## 2. Faz1 — tamamlanan LaTeX Bootstrap Table geçişi

- Son `Ai/LatexImagesTable` artık normal Livewire Component; Rappasoft/DataTableComponent veya eski filtre/config trait'lerini kullanmaz.
- `AiController::getImagesData` / `admin.ai.getImagesData`: ortak ServerTable `total/rows`,15 kayıt, id/formula/image/created_at sort whitelist; arama formula veya kayıtlı image yolunda. Kayıt yok0/[]; status alanı olmadığı için status filtresi prohibited.
- Formül, görsel ve oluşturma zamanı korunur. Genel server-table action formatter düzenleme bağlantısını mevcut imageForm route'una, Sil'i ilgili Livewire delete metoduna bağlar; ayrı görünüm/detay paneli üretilmez.
- `dashboard:access` + `ai:view` okuma/render/cache öncesi; `ai:update` bağlantı görünürlüğü, formun mevcut save izni; `ai:delete` her silme çağrısında. Eksik ID404. Görsel upload formu/Action/MIME ve iş kuralları yeniden yazılmadı.
- Silme önceki davranışla yalnız seçilen LatexImage kaydını siler; görsel dosyasını/soru/AI geçmişini veya DB'yi temizlemez. Tarayıcı kontrolünde gerçek silme yapılmadı.
- Ortak imageLink üçüncü class argümanı aldı; yalnız LaTeX formatter mevcut `.latex-ai-image` boyutunu kullanır; koyu temada siyah SVG formülleri için Bootstrap beyaz ön izleme zemini vardır. Formül alt etiketi ve URL escape edilir, javascript/data protokolleri reddedilir. Diğer formatter varsayılanı korunur.
- Ortak paginationParts pageInfo/pageList; Yenile solda/arama sağda. Yeni filtre yok; tarih sütunu mevcut lokal tarih sözleşmesinde. Mobilde tablo kendi responsive alanında scroll eder.
- JSON60sn; auth/izin/locale/sort/search/page key bağlamı mevcut TableCache. Yenile refresh=1 bypass. LatexImage mevcut TableCacheObserver'a kaydedildi, saved/deleted yalnız ai-latex-images revision'ını hedefler; arka plan job'ın model yazımı da bu event zincirindedir. Yeni global flush eklenmedi; mevcut Loggable/resetCache geniş etkisi bu fazın konusu değildir.
- Yeni JS nedeniyle APP_VERSION **1.1.18**. CSS/kütüphane/build eklenmedi.

## 3. Rappasoft kalıntı kontrolü ve temizliği

Başlangıç taramasında tek aktif tablo LaTeX'ti. Başka aktif Rappasoft DataTableComponent bulunmadı. Geçişten sonra kullanılmayan **88 dosya** kaldırıldı:

- `app/Traits/CustomLivewireTableFilters.php`, `app/Traits/LivewireTableConfigure.php`, `config/livewire-tables.php`.
- `resources/views/vendor/livewire-tables/`:62 paket şablonu.
- `lang/vendor/livewire-tables/`:23 paket arayüz dili dosyası.

Bu dosyaların dış çağrıları yalnız eski LaTeX sınıfındaydı; uygulamanın `lang/nl`, `lang/en`, `lang/tr`, JSON çevirileri ve DB çevirileri silinmedi. app/config/routes/resources/views/lang/bootstrap/public kaynak taramasında Rappasoft/DataTableComponent/trait/package-view çalışma referansı **0**; Composer lock paketleri **0**. Tarihsel Codex/README açıklamalarını aktif kod bağımlılığı sanma. Bu bütün legacy adaylarının düzeltildiği anlamına gelmez.

## 4. Doğrulama ve kapsam

- `LatexImagesTableTest`4 transaction senaryosu: HTML/JSON ve mevcut oturum izinleri; search/sort/limit/offset/boş/invalid param; cache stale + refresh + model update/deletion + hedef observer; silme ve mevcut upload save yetkisi.
- Mevcut `PackageRemovalTest`1senaryo scheduler'da yalnız dört uygulama işini doğruladı. **Tek dar koşu5test/47doğrulama başarılı.** `DB_HOST=127.0.0.1 /opt/homebrew/opt/php@8.5/bin/php vendor/bin/phpunit tests/Feature/LatexImagesTableTest.php tests/Feature/PackageRemovalTest.php`.
- `node tests/Frontend/LatexTableFormatterTest.cjs`:9 doğrulama; URL/alt escape, tehlikeli protokol, boş görsel ve diğer ortak formatter class varsayılanı. JS syntax,5 PHP syntax/Pint ve ilgili diff whitespace başarılı.
- Testing DB başlangıç LaTeX kaydı0; fixture'ler DatabaseTransactions ile geri alındı. Ana hypotenuse DB yalnız çeviri şema/sayı incelemesinde read-only transaction olarak okundu; tablo/görsel yazımı yok.
- Chrome gerçek testing images route + boş JSON200. Son SVG zemin revizyonu tek dar browser kontrolde computed background rgb(255,255,255) ile doğrulandı. Dolu liste için DB'ye yazmadan20sentetik JSON/görsel ile plugin/render/arama/sort/15+5sayfa/yenile/confirm iptal ve390px incelendi; dolu gerçek DB browser veya gerçek upload/silme kabulü değildir. Gerçek dolu JSON/izin/silme ekonomik feature testinde kontrol edilir. Arama harness ilk input-only aşamada plugin keyup tetiklemedi; klavye olayı eklenerek dar tekrar yapıldı, ürün hatası değişikliği yapılmadı.
- Genel suite/build, migration/seed, dış AI/mail/R2/backup veya deploy yok. Geçici QA server kontrol sonrası kapatıldı; Sail/diğer proje servisleri durdurulmaz.

## 5. Çeviri tablolarının güncel salt okunur kanıtı

Ana yerel `hypotenuse` DB'de **languages ve translations duruyor**. Translation modeli zaten var. translations kolonları id/locale/key/value; primary id, locale index ve **locale+key unique** korunur. Paket kaldırılması bu tabloları kaldırmamış.

Dil bazında toplu envanter (metin değerleri rapora alınmadı):

| Dil | Çeviri kaydı | Dolu değer | Language durumu |
|---|---:|---:|---|
| nl |840|839|active|
| tr |744|0|active|
| fr |482|0|passive|
| en |0|0|languages kaydı yok|

Bu sayıların boş oluşu tek başına site fallback'inin çalışmadığı kanıtı değildir. Mevcut PHP/JSON dil dosyaları ayrıca vardır. Fransızca etkinleştirme ve İngilizce language kaydı Faz2'de kullanıcı hedefiyle değerlendirilir; bu tur DB durumu değiştirilmedi.

## 6. Faz2 — sıradaki çeviri tarayıcısı ve yönetim ekranı

**Hedef:** panel ve ön yüz kaynaklarını tarayarak eksik anahtarları mevcut dil/çeviri ekranında görünür yapmak; mevcut değerleri ezmeden, tekrar taramada çoğaltmadan yönetmek. Kaynak dili Türkçe; hedef önceliği nl, ardından en/fr. Mevcut tr dilini veya varsayılan nl kararını kaldırma.

1. `app`, ilgili route/Blade kaynakları ve uygulama dil dosyaları; literal `__`, `trans`, `trans_choice`, Blade `@lang`/`@choice`, Lang::get biçimlerini inceleyen kendi parser'ımız. Vendor/üretilmiş/cache/log/backup dosyaları taramaya girmez. Dinamik ifadeler güvenli biçimde bilinmeyen olarak raporlanır; tahmini metin/route üretme.
2. Türkçe cümle JSON anahtarları ile `account.*`, `learning.*`, `tables.*` gibi PHP grup anahtarlarını ayır. PHP grup anahtarlarını JSON'a düz yazıp kalıcı kaynak çevirilerini gölgeleme. Kaynak Türkçe gösterim, key ve çoğul/placeholder yapısı birlikte yönetilir.
3. Her key için bulunduğu dosya/satır, panel/ön yüz, component/Blade ve doğrulanabilen route/sayfa kullanım bilgisi. Ortak partial birden çok sayfada gösterilir; tüm kullanımlar ilişkide tutulur. Doğrulanamayan kullanımda dosya bilgisi yeterli, rota tahmini yapılmaz.
4. translations tablosunu koru. Kullanım kayıtları için ayrı ilişkili model/tablo veya gerekli dar kolon planı hazırlanır; mevcut locale+key unique ve değerleri koruyan migration, ana DB uygulaması öncesi somut kapsamla ele alınır. Bu faz planı mevcut migration çalıştırma komutu değildir.
5. Diller/Çeviriler ekranında panel/ön yüz, sayfa/route/kaynak grubu ve boş/dolu filtreleri; arama ve sayfalama. Bir metnin birden çok Blade/sayfada görünmesi açık listelenir. Bütün çevirileri Livewire public snapshot'a doldurmak yerine görünür sayfa ve aggregate sayılar kullanılır.
6. İzinli tarama ön izlemesi, eklenen/kullanılan/bilinmeyen key özeti; uygulamada mevcut değerleri ezmeden eksikleri tamamla. `languages:translate`, güncel dil/record kapsamı ve tekrar tarama idempotence doğrulanır. Çeviri düzenleme/kaydetme JSON/PHP kaynak türüne uygun ve eski çevirileri koruyarak çalışır.
7. Parser/çok satır/escape/dinamik/placeholder/üç kullanım/tekrar tarama/izin testleri ayrı dar koşularla; mevcut çeviri dosyaları için güvenli yazım ve dil üretimi browser kontrolü. Gerçek otomatik dil çevirisi veya dış servis gönderimi bu tarayıcı hedefi değildir.

## 7. Faz3 — Laravel'den bağımsız SQL.gz + proje ZIP → R2 bash

**Hedef:** Laravel Backup veya S3 adapter geri yüklenmeden, sunucuda mevcut Lara Football bash yaklaşımını referans alan iki modlu yedek scripti.

- DB modu mysqldump + gzip ile **SQL.gz**; proje modu tam proje dosyalarıyla **ZIP**. Kullanıcı geçici dosya üretme ve başarılı R2 aktarımı sonrası yalnız üretilmiş yerel yedek dosyasının kaldırılmasını istedi.
- Dosyalar private geçici alanda; mevcut DB/upload/proje kaynakları silinmez. Yükleme başarısız/eksikse yerel yedek korunur ve hata kaydı verilir. Başarı doğrulaması aktarım yöntemiyle birlikte belirlenir; sırf süreç başladı diye dosya silinmez.
- ENV'den DB/R2 bilgisi; sırlar output/loga yazılmaz. Proje ZIP'i .env içerecekse R2 private bucket erişimi ve gizlilik bu kapsamda değerlendirilir. Public URL/DB dosyası sunma yok.
- Laravel scheduler bağımsız; düzenli çalıştırma sunucu crontab'ı gibi işletim sistemi mekanizmasıyla bash'i çağırır. Kullanıcının03:00 DB/04:00 ZIP saatleri örnektir; kesin saat/timezone faz uygulamasında netleşir. Bash kendi kendine uyuyarak sürekli çalışan daemon olarak varsayılmaz.
- Bucket/prefix, SQL/ZIP adları, tam proje dosya kapsamı ve çıktı/ZIP dizininin kendisini dışlama, overlap lock, başarısızlık ve yerel silme sınırı, R2 saklama politikası somutlaştırılır. Uzak retention silmesi kullanıcı kararı olmadan eklenmez.
- Yerel salt okunur/dry-run ve fake transfer hata/başarı testleri; gerçek R2 upload, cron kurulum/değişimi ve restore/canlı DB işlemleri ayrı açık adım. Paket/scriptin varlığı düzenli yedeklerin veya restore'un doğrulandığı anlamına gelmez.

**Referans:** Lara Football yerel repo/scripts/docs ve Desktop/Documents-Codex backup/yedek *.sh dosya envanterinde ilgili SQL.gz→R2 script bulunmadı. Kullanıcıya script adı veya sunucu yolu soruldu; cevap Faz3 referansını belirler. Başka projedeki git-backup.sh aynı işlevin referansı kabul edilmedi.

## 8. Backup scheduler durumu

`bootstrap/app.php` mevcut dört işi: GPT limit, sezon ataması, optimize:clear ve lig hesaplama. Backup komutu/job/schedule yok;34'te zaten kaldırılmış. Bu tur iptal edilecek ikinci kayıt bulunmadığı için scheduler dosyası değiştirilmedi. PackageRemovalTest bunu doğrular. Sunucu crontab'ı/kurulu eski serialized queue işleri erişilip değiştirilmedi; bu yerel kaynak kanıtıdır.

## 9. Bu tur değişen dosyalar

- `app/Http/Controllers/Backend/AiController.php`, `app/Livewire/Ai/LatexImagesTable.php`.
- `app/Providers/AppServiceProvider.php`, `app/Observers/TableCacheObserver.php`.
- `resources/views/livewire/backend/ai/latex-images-server-table.blade.php` (yeni).
- `public/backend/assets/server-table.js`, `.env` (yalnız APP_VERSION).
- `tests/Feature/LatexImagesTableTest.php`, `tests/Frontend/LatexTableFormatterTest.cjs` (yeni).
- Bölüm3'teki üç eski kaynak ve62+23 yayımlanmış dosya kaldırıldı; yalnız Rappasoft yolları.
- `AGENTS.md`, `Codex/{16,19,51,60}*.md`, `Codex/index.html`, README paket geçiş durumu.


## 7 Ekim devam — Faz2 tamamlandı

[61-CEVIRI-TARAYICISI-VE-KAYNAK-YONETIMI.md](61-CEVIRI-TARAYICISI-VE-KAYNAK-YONETIMI.md): kendi tarayıcı/kaynak yönetimi ve JSON/PHP üretimi uygulandı; mevcut çeviri/dil kayıtları korunur. Onaylı yalnız translation_sources migration iki yerel DB’ye eklendi; ana çeviri ekleme/yazım çalıştırılmadı. Mevcut dillerle devam, enDB kaydı eklenmez/fr etkinleşmez.7yeni+4mevcut farklı dar senaryo ve testing Chrome kontrolü var. Faz3 bağımsız yedekleme referans script yolu bekler. Üst bölümler kendi tarihsel durumuyla saklanır.


## 7 Ekim devam — Faz3 Bash hazır

[62-LARAVEL-BAGIMSIZ-BASH-YEDEK-VE-R2.md](62-LARAVEL-BAGIMSIZ-BASH-YEDEK-VE-R2.md): kullanıcı yeni Bash ve yalnız Laravel referansını seçti; önceki Lara scripti bekleme kararı kapanır. SQL.gz/proje ZIP için iki mod, dotenv allowlist/private alan/overlap lock/AWS CLI R2/boyut+SHA256 geri okuma; doğrulama sonrası yalnız bu run arşivi silinir, hata arşivi korunur.11 farklı fake DB/R2 dar test ve Bash syntax başarılı. Gerçek sunucu/R2/cron/restore uygulanmadı; bucket/prefix/private dizin/saat-timezone kurulum adımıdır. Eski üst kayıtlar tarihsel kapsamıyla korunur.
