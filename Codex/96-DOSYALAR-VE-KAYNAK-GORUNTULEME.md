# Dosyalar ve kaynak görüntüleme

4 Kasım 2026. Kullanıcının netleştirdiği kapsam: panelde Dosyalar; herkese açık `/sources/{uuid}/{slug}` görüntüleyici; isteğe bağlı R2 veya yerel depolama. Bu kayıt kod geliştirmesidir; canlı kurulum veya gerçek R2 kabulü değildir.

## Kullanım

- Dosyalar, panelde Ayarlar'ın hemen üstündedir. Yeni liste ortak Blade + Bootstrap Table + server JSON kullanır; dosya adı/başlık araması, tür filtresi ve sunucu sayfalaması vardır.
- Sağ üstten yükle; başlık ve dosya seç; ilerleme ve sunucu doğrulaması tamamlanınca görüntüleme bağlantısı verilir. Görüntüle, bağlantıyı kopyala ve onaylı silme bulunur. Düzenle/aynı bağlantıyla değiştirme yoktur.
- `files:view`, `files:add`, `files:delete` işlem izinleri bağımsız denetlenir. Migration yalnız mevcut web `Yönetici` rolüne başlangıç izinlerini verir; Gent ve öğretmenlere vermez. Sonradan yetki atanabilir.
- PDF, HTML, JPG/JPEG, PNG ve WebP desteklenir. Varsayılan üst sınır 100 MiB; desteklenen uzantıların alt kümesi ve 1–100 MB sınırı ayarlanabilir. İçerik MIME, boyut, PDF imzası ve görsel yapısı sunucuda doğrulanır.
- 9 Kasım güncellemesiyle liste varsayılan karttır; tabloya geçiş korunur. Başlık/tür/tarih/durum/işlemler görünür; özgün ad/boyut/yükleyen yalnız metadata olarak saklanır. Yarım yükleme görüntüleyicide açılmaz. Ayrıntı ve kabul: [102](102-PANEL-OGRETMEN-FILTRELERI-VE-DOSYA-KARTLARI.md).

## Ayarlar → Dosyalar

Bu sekme ve ayar kaydı yalnız web Yönetici rolüne açıktır; kaydetmede ayrıca mevcut `settings:update` gereklidir. Yeni ENV anahtarı gerekmez; mevcut `APP_KEY`, `APP_URL` ve `APP_PUBLIC_PATH` geçerlidir. `APP_KEY`, mevcut dosya kayıtlarının şifreli bağlantı yapılandırması ve eski şifreli ayarların okunması için korunur.

| Ayar | İşlev |
|---|---|
| sourceStorage | `local` varsayılan veya `r2` |
| sourceMaxMb | 1–100; varsayılan 100 |
| sourceTypes | `pdf,html,jpg,jpeg,png,webp` desteklenen alt kümesi |
| sourceR2Account | Cloudflare hesap kimliği; endpoint bu kimlikten sabit HTTPS alan adıyla üretilir |
| sourceR2Bucket | Ayrı medya bucket adı |
| sourceR2Key / sourceR2Secret | Cloudflare R2 Access Key ID / Secret Access Key; 9 Kasım kararıyla global secret açık gösterilir ve yeni kayıtta şifresiz saklanır, boş gönderim eski değeri korur |
| sourceR2PublicUrl | Medya bucket'ın herkese açık HTTPS dosya alan adı |

**Özel yedek bucket'ını bu alan için açmayın veya kullanmayın.** Bash yedekleme ayarlarıyla yeni medya ayarları bağımsızdır. Dosyanın depolandığı konum ve şifreli bağlantı yapılandırması kayıtta korunur; sonradan varsayılan depolama değişse de eski dosya aynı yerden açılır. Eski anahtarların erişimini kaldırmak eski dosyaların silme/HTML okuma işlemini etkiler.

## Depolama akışı

Yerel: mevcut `public` disk kökü kullanılır: `public/uploads/sources/{uuid}/...`; canlı `APP_PUBLIC_PATH=public_html` ise `public_html/uploads/sources/...`. Laravel sayfası üzerinden geçmeden özgün dosyaya erişilebilir.

Bulut: tarayıcı 10 dakika geçerli imzalı PUT ile `pending/{uuid}` nesnesine doğrudan yükler. Sunucu boyutu ve ETag'i okur; aynı ETag ile içeriği indirip geçici dosyada doğrular; koşullu CopyObject ile ayrı nihai anahtara yayımlar. İmzalı PUT, yayımlanan anahtarı yeniden yazamaz. Geçici dosya kapanır; pending nesnesi başarıda silinir. Bu süreç sunucuda geçici disk ve dış trafik tüketir.

Bulut hazırlama/yükleme/doğrulama hatasında nötr bulut depolama mesajı ve **“Evet, site depolamasına yükle”** seçeneği çıkar. Tıklanmadan yerel yükleme yapılmaz. Yükleme yetkisi ve dosyayı hazırlayan kullanıcı, tamamlama anında yeniden kontrol edilir. Kilit, eşzamanlı tamamlama/silme yarışını engeller; hazır kaydın tekrar tamamlanması aynı bağlantıyı verir.

R2 kurulumunda medya bucket için CORS, yalnız gerçek site origin'ini (`https://hypotenuse.be`, gerekiyorsa ayrı yerel origin), PUT ve gereken GET/HEAD yöntemlerini, `Content-Type` ve `Content-Disposition` istek başlıklarını kabul etmelidir. Public alan adı nihai nesneleri açmalıdır. Gerçek tarayıcı PUT + sunucu Head/Get/Copy/Delete denemesi henüz yapılmadı; anahtar veya dış servis yazma onayı olmadan yapılmaz. Başarısız/terk edilen pending yüklemeler için otomatik temizleme görevi eklenmedi; nesne kalabilir. Bucket lifecycle politikası ayrıca işletim kararıdır.

## Görüntüleyici ve geri dönüş

- Kopyalanan bağlantı `/sources/{uuid}/{slug}` adresidir; giriş gerekmez.
- IP + dosya başına 60 saniyede üç görüntüleme sayfası açılır; dördüncü 429 ve Retry-After alır. Sayfa private/no-store'dur. Doğru ziyaretçi IP'si için mevcut proxy yapılandırması geçerli olmalıdır.
- Özgün PDF/görsel/HTML indirme adresine bu limit uygulanmaz. PDF'nin kendi iç/range istekleri sayılmaz. Worker kullanılmaz.
- Sitedeki bu adreslere ait bağlantılar yeni sekmede açılır; tıklanan kaynak sayfanın yol/sorgusu `return_to` olarak eklenir. Dönüş yalnız site içi güvenli yoldur; eksik/geçersiz değer `/` olur. Yeni sekme eski sekmeyi otomatik öne getirmez; düğme kaynak adrese gider.
- Site dili kullanılır; NL, TR, EN ve FR metinleri vardır. Test/konu/siteye dönüş metni kaynak yolundan seçilir. Mevcut test süresi durdurulmaz.
- PDF iframe, görseller normal img ile gösterilir. HTML ayrı içerik yanıtında, boş sandbox ve CSP sandbox ile gösterilir: JavaScript, aynı origin yetkisi, form ve üst pencere yönlendirme izni yoktur. HTML içindeki harici HTTPS görselleri ve gömülü stil desteklenir; harici CSS/paket yüklemesi yoktur.
- Özgün HTML dosyası `.html.download` adıyla saklanır; yerelde `.htaccess` octet-stream/attachment/nosniff, R2'de nesne metadata'sı indirmeyi sağlar. Sunucunun statik dosya kurallarını gerçekten uyguladığı yayın kabulünde ayrıca doğrulanmalıdır; nginx/CDN `.htaccess` okumaz.
- Silme depolamadaki nesneyi kaldırır ve kaydı soft-delete yapar; eski görüntüleme bağlantısı 404 olur. Önceden indirilmiş kullanıcı kopyaları silinmez. Silme hata verirse kayıt silinmiş sayılmaz.

## Veri ve kaynaklar

Yeni `source_files` tablosu kimlik, başlık/özgün ad, MIME/uzantı/boyut, yükleyen, durum ve konumu tutar; dosya byte'ları DB'de değildir. Migration `database/migrations/2026_11_04_150000_create_source_files_table.php`; Kullanıcı yalnız bu migration için ana/testing uygulamasını onayladı; Eduan servisleri kapalı olduğu için henüz uygulanmadı. Down otomatik tablo/dosya/izin silmez; veri kaybı için ayrı kaldırma planı gerekir.

Başlıca kod: `app/Actions/Sources/SourceUploads.php`, `app/Helpers/Sources/*`, iki Source controller, `app/Models/SourceFile.php`, kaynak liste/görüntüleyici/ayar Blade'leri, `public/backend/assets/source-files.js`, `public/assets/source-links.js`, dört dil `sources.php`.

R2 için `league/flysystem-aws-s3-v3` eklendi; Composer yalnız beş yeni paket kurdu, mevcut paket sürümlerini değiştirmedi. Composer install scriptleri çalıştırılmadı. APP_VERSION ve kök worker cache sürümü birlikte 1.1.43; diğer yerel, henüz yayımlanmamış düzeltmeler korunur.

## Dar test ve yayın sınırı

- İzole SQLite bellek deposu/gerçek Eloquent + action/controller testleri: 16 test/86 assertion başarılı; ek bulut boyutu değişikliği reddi 1 test/2 assertion ve gerçek public route/Content başlığı çakışma kontrolü 1 test/5 assertion başarılı. Bunlar ayrı dar koşulardır; tek birleşik koşu iddiası değildir. Ana/testing MySQL'e bağlanmadı.
- Başarı, kötü uzantı/içerik/boyut, sahiplik/yetki, tekrar tamamlama, silme, gizli ayar, güvenli dönüş ve IP/dosya limiti test edildi. AWS SDK gerçek imza ve sahte Head/Get/Copy/Delete yanıtlarıyla test edildi; gerçek R2 isteği yok.
- Node: iki yeni link/kaçış testi ve 12 mevcut PWA testi başarılı. Dört dilde gerçek yeni Blade içerikleri izole dış layout ile render edildi.
- Chrome NL 1440 ve FR 390: gerçek yeni JS + Blade/controller/bellek DB köprüsüyle yükleme/ilerleme/bağlantı, klavye araması/JSON, onaylı silme, mobil offcanvas, HTML sandbox/geri dönüş ve dördüncü açılış 429 geçti; JS istisnası 0, beklenmeyen 5xx 0 ve mobil taşma 0. Ekran görüntüleri incelendi. İzole dış layout ve bellek DB köprüsü gerçek ana uygulama oturumu veya canlı sunucu kanıtı değildir.
- Eduan servisleri kapalı; başka projelerin Docker servisleri değiştirilmedi. Onaylı migration ve gerçek MySQL/oturumlu HTTP kabulü servislerin açılmasını bekler. Gerçek R2 için ayrıca anahtar/CORS ve işlem onayı gerekir.
- Ayrı bulgu: mevcut production lock'ta Symfony HttpFoundation/HttpKernel/Routing için toplam altı güvenlik bildirimi görüldü; düzeltme 8.1.11 olarak bildirilmiş. Bu kapsamda mevcut paketler otomatik güncellenmedi. Genel yayın onayı verilmez.

Yayın öncesi: yalnız onaylı migration'ı uygula; `composer install --no-dev --optimize-autoloader`, public_html ve kalıcı uploads korunması, cache/asset sürümü, PHP upload_max_filesize/post_max_size ve web sunucusu gövde sınırlarının seçilen boyutu karşılaması, HTML özgün indirme başlıkları, PDF görüntüleme, guest dönüş/429 ve yetkili upload/delete kontrolü gerekir. Yeni cron yoktur.

## 5 Kasım 2026 — Docker/MySQL ve gerçek ekran kabulü

Önceki bölümdeki servis/migration bekleme notları 4 Kasım kod hazırlığının tarihsel kaydıdır. Bu tur kullanıcı Eduan'ı açtı ve normal yerel veritabanına 3–5 örnek bırakılmasını açıkça istedi.

### Uygulanan veri adımları

- Daha önce onaylı yalnız `2026_11_04_150000_create_source_files_table.php`, yerel `hypotenuse` ve `testing` DB'lerine uygulandı. Eski kullanıcı/ayar sayıları korundu. Ana DB'de üç izin yalnız mevcut web Yönetici rolüne verildi. Testing'de bu rol olmadığı için yalnız izinler oluşturuldu; rol eklenmedi.
- Mevcut yerel yöneticinin parolası/rolü değiştirilmeden ayrı test oturumu hazırlandı. Gerçek `http://localhost/admin/files?lang=tr` panelinden beş dosya prepare + multipart finish akışıyla yüklendi. Özgün içerik SHA-256 ve boyut karşılaştırmaları geçti.
- Ana DB son durum: kullanıcı381 (önce381), source_files5 (önce0); beşi de local/ready. Mevcut settings satırlarının sıralı içerik SHA-256 değeri aynı. Testing son durum kullanıcı1/settings10/source_files0; senaryo verileri transaction ile geri alındı.
- Eklenen örnekler çalışma verisi değil, yalnız yerel Dosyalar demo kayıtlarıdır. Gelecek canlı pakete örnek veri seeder'ı eklenmedi; gerçek R2 veya canlı DB değişikliği yapılmadı.

| Başlık | Yerel görüntüleme bağlantısı |
|---|---|
| [Demo] Breuken - PDF | http://localhost/sources/e7ab78cf-91d9-43ba-94af-535349d9c993/demo-breuken-pdf |
| [Demo] Procenten - HTML | http://localhost/sources/c05ecd79-f284-4cae-85b8-c07780e94bf1/demo-procenten-html |
| [Demo] Studieoverzicht - PNG | http://localhost/sources/ea08503e-d433-4a3c-a9c4-0813cd473395/demo-studieoverzicht-png |
| [Demo] Studieoverzicht - JPEG | http://localhost/sources/032ef946-659e-4a2e-b913-869d2ea464f1/demo-studieoverzicht-jpeg |
| [Demo] Studieoverzicht - WebP | http://localhost/sources/f8cb8061-d768-49fd-8417-d41b5cd9aa8e/demo-studieoverzicht-webp |

### Bulunan ve açık onayla düzeltilen HTML sorunu — fixed

**Önce:** Artisan'ın PHP geliştirme sunucusu statik özgün HTML'yi `.htaccess` okumadan ve Content-Type/Disposition olmadan sunuyordu. Chrome ham `.html.download` adresini HTML olarak yorumladı; örnek dosyanın zararsız `window.__demoSourceScriptExecuted` işareti çalıştı. `/sources` içeriğindeki sandbox etkilenmiyordu. Sorun gerçek statik dosya sunumu sınırındaydı.

**Düzeltme:** Kullanıcının ayrıca onayladığı `server.php`, Laravel ServeCommand'ın desteklediği proje yönlendiricisidir. Çalışma dizinindeki public/public_html yolunu kullanır. Kaynak kökü ve hedef dosya realpath ile doğrulanır; gerçek HTML özgünleri ve bu hedeflere giden kodlanmış/dot-segment/symlink takma yolları statik passthrough'dan önce ele alınır. GET/HEAD `application/octet-stream`, `attachment` ve `nosniff` döner; HEAD gövdesizdir, diğer yöntem405; eksik/kök dışı hedef404. Diğer dosyalar ve dinamik sayfalar Laravel'in mevcut framework router dosyasına devredilir. Vendor değiştirilmedi. Kaynak HTML byte'ları, görüntüleyici sandbox'ı, PDF ve görseller aynı kalır.

Yalnız Eduan web konteyneri onayla yeniden başlatıldı; MySQL ve diğer projelerin servisleri değiştirilmedi. Chrome aynı özgün bağlantıda artık download olayı üretti; indirilen byte'lar aynı, test betiği çalışmadı. Bir bağımsız salt okunur sınır incelemesi ve tek bağımsız patch/bypass incelemesi somut kalan bypass/regresyon bulmadı. Bu yerel sunucu düzeltmesidir; Apache `.htaccess` ve R2 metadata'sının gerçek canlı ortamda kabulü hâlâ ayrıca gerekir.

### Çalıştırılan ilgili testler

Toplam **472 farklı PHP testi / 2.673 assertion**, dört ayrı dar koşunun toplamıdır; tek genel proje suite'i çalıştırıldığı anlamına gelmez.

1. **192 test / 1.874 assertion:** yeni `SourceFilesIntegrationTest` ilk11 senaryo; WebNotifications, LiveLeaguePresentation/Scheduling, UserImpersonation, ResultSeasonFilter, StudyPanel/StudyTrackingForm; SourceFiles/MailPersonalization/LeagueModeAndCadence/NotificationVisibility/LivewireUpdateRouteCache Unit dosyaları. Gerçek testing MySQL/HTTP/Livewire + izole testler başarılı.
2. **277 test / 742 assertion:** SecurityFindingsRegression, UserMailSecurity, PanelUploadSecurity, AuthSecurity, Turnstile, StudyReleaseCombinations ve PanelGuide. Bütün192 çalışma kombinasyonu bu gruptadır. Başarılı; gerçek mail/push/Turnstile çağrısı yok.
3. **2 test / 49 assertion:** `php vendor/bin/phpunit tests/Unit/SourceStaticServerTest.php tests/Feature/SourceFilesIntegrationTest.php --filter 'real_php_server|failed_cloud'`. Gerçek geçici PHP sunucusunda public_html/GET/HEAD/POST/encoding/alias/kök dışı symlink ve PDF/görsel/dinamik fallback; ayrıca cloud prepare hatasından sonra yalnız açık onaylı local fallback HTTP/DB akışı başarılı. Gerçek R2 isteği yok.
4. **1 test / 8 assertion:** `php vendor/bin/phpunit tests/Feature/ContactReplyStoredXssTest.php`. Gerçek testing kaydında mail build yanıtı güvenli escape edilir, tarihsel HTML Livewire görünümünde sandbox içinde kalır; mail gönderilmedi/kuyruğa alınmadı.

Komutlar `docker exec eduan-web-laravel.test-1 php vendor/bin/phpunit ...` ile çalıştı; ortak TestCase yalnız testing DB'yi kabul eder. Dört koşunun tamamı başarılı. Yeni test dosyaları `tests/Feature/SourceFilesIntegrationTest.php`, `tests/Feature/ContactReplyStoredXssTest.php`, `tests/Unit/SourceStaticServerTest.php`.

**Node29 başarılı:** SourceFiles, PwaSafety, LeagueTable, UserImpersonationConfirm, Turnstile, StudyPanelFormatter, StudyWeekPicker test dosyaları `node --test` ile çalıştırıldı. PHP syntax ve yalnız yeni/değişen PHP dosyalarının Pint kontrolü başarılı.

### Gerçek tarayıcı ve görsel kontroller

Browser eklentisi bu oturumda yok; mevcut Playwright + Google Chrome kullanıldı. Ana uygulamanın gerçek HTTP oturumu/CSRF/yükleme akışı kullanıldı; izole dış layout veya sahte JSON kullanılmadı. Dış HTTPS entegrasyonları testte engellendi. Masaüstü1440×1000, mobil390×844; panelTR, misafirNL/FR.

- Gerçek yükleme/ilerleme/hazır bağlantı, kopyalama, tür filtresi, klavye araması, mobil panel ve boş secret/yerel varsayılan ekranı doğrulandı. Silme/sahiplik/revoked yetki/kötü içerik/sort/SQLi benzeri arama testleri gerçek MySQL/HTTP senaryolarında uygulandı; beş ana örnek silinmedi.
- Herkese açık beş dosya, HTML sandbox, üç görselin yüklenmesi, mobil görünüm, üst dönüş barı ve ayrı sekme/sahici ana sayfaya dönüş çalıştı. Dördüncü görüntüleme429, özgün dosya aynı anda200 kaldı.
- PDF önce test aracının tüm istekleri yakalaması nedeniyle boş göründü. Aynı dosya Poppler ile sağlıklı tekA4/JavaScript yok şeklinde doğrulandı/render edildi. Chrome PDF eklentisinin isteklerini yakalamadan gerçek kaynak sayfası tekrar açıldı: PDF içeriği, araç çubuğu ve site dönüş barı birlikte görsel olarak doğrulandı. Bu ürün kodu hatası değildi.
- Lig formu gerçek panel/Livewire üzerinden açıldı: ilk değer boş, disabled/hidden placeholder; tek/canlı seçilebilir ve üç saat metni doğru. Hesaplama başlatılmadı/veri yazılmadı. Scheduler üç saat regresyonu PHP testinde doğrulandı.
- Normal yükleme/liste/HTML/PDF/görsel/lig/dönüş akışlarında JS istisnası veya beklenmeyen5xx görülmedi; ana kontrol betiğinin429 aşamasında ortak hata şablonundan tek `Livewire is not defined` istisnası saptandı. Bu yüzden bütün tarayıcı koşusu kusursuz diye raporlanmaz.
- Ekran görüntüleri incelendi. Geçici kanıtlar `/private/tmp/eduan-sources-20261105/` altında: panel-1440.png, upload-390.png, html-final-1440.png, pdf-final-1440.png, viewer-390.png. Yerel örnekler kalıcı; bu geçici kanıt klasörü OS tarafından temizlenebilir.

### Revizyon önerileri ve kalan kabul sınırı

1. **100 MiB web sınırı:** Yerelde `upload_max_filesize=100M` ve `post_max_size=100M`. Gerçek100MiB dosyanın multipart isteği104857812byte oldu ve413 aldı; kayıt oluşturulmadı. Dosya üst sınırı100MiB korunup post_max_size için128M gibi form verisine pay bırakılması önerilir. Sunucu/ini ayarı bu tur değiştirilmedi. Canlı/proxy sınırları da ayrıca kontrol edilmeli.
2. **429 hata ekranı:** Mevcut `errors/429.blade.php`, tam404 şablonunu içeriyor. Kullanıcı bekleme mesajı yerine sayfa bulunamadı görünümü alıyor; ortak şablonun Livewire hook'u bir JS istisnası üretiyor. HTTP429 ve Retry-After doğru, limit çalışıyor. 429 için anlaşılır bekleme metni ve tek layout önerilir; otomatik kapsam dışı düzeltme yapılmadı.
3. **Okuma odaklı görünüm:** Mevcut tanıtım popup'ı ilk kaynak açılışında içeriği örtüyor. Testte kullanıcı gibi kapatıldı. `/sources` sayfalarında bu popup'ı göstermemek önerilir; ana ayarlar değiştirilmedi.
4. **Tablo okunurluğu:** Küçük PDF/HTML örneklerinde yuvarlama0MB; dar tür kolonunda HTML/JPEG/WebP bölünüyor. Küçük boyuttaKB, tür/boyut için nowrap ve daha net eylem hizası önerilir. Bu UX revizyonu henüz yapılmadı.
5. **R2/canlı:** Ayrı medya bucket/anahtar/public URL/CORS olmadığı için gerçek R2 PUT/Head/Get/Copy/Delete yapılmadı; SDK ve hata/fallback testleri sahte sağlayıcıyla geçti. Daha önce bildirilen Symfony güvenlik yamaları uygulanmadı. Bu tur canlı deploy veya bütün proje için koşulsuz yayın onayı yoktur.

## 5 Kasım 2026 — Onaylı dört revizyon tamamlandı

Kullanıcı, bir önceki rapordan seçtiği 100MiB/post payı,429 sayfası,kaynak popup'ı veKB gösterimi maddelerinin düzeltilmesini istedi. Bu bölüm önceki açık notların yalnız bu dört bölümünü günceller; R2 ve Symfony beklemeleri aynıdır.

- **PHP sınırı:** Sahipli `docker/php/99-uploads.ini` dosyası upload100M/post128M içerir; compose yalnız Eduan web servisinin `/etc/php/8.5/cli/conf.d/99-uploads.ini` yoluna salt okunur bağlar. Sail/vendor dosyası değiştirilmedi. `public/php.ini` içine de aynı iki değer eklendi. Yalnız Eduan web `up -d --no-deps --no-build --pull never laravel.test` ile yeniden oluşturuldu; MySQL/volume ve diğer proje servisleri korundu. Etkin PHP readback upload100M/post128M. Gerçek104857600byte multipart geçerli CSRF ile gönderildi; önceki413 yerine beklenen misafir401 alındı, kaynak kaydı oluşturulmadı. Bu, HTTP gövdesinin PHP'ye kabulünü kanıtlar;100MiB kalıcı yükleme yapılmış gibi raporlanmaz.
- **429 görünümü:** `errors/429.blade.php` artık404 tam şablonunu içermez; tek frontend layout, anlaşılır bekleme başlığı/açıklaması ve ana sayfa bağlantısı gösterir. Yeni TR/NL/EN/FR `errors.php` metinleri. HTTP429/Retry-After ve asıl limiter kuralları korunur. Yeni kontrol gerçek sayfalarda bir html/main ve sıfırJSistisnası gösterdi; eski Livewire hatası tekrar etmedi. Global JavaScript guard veya limiter gevşetilmedi.
- **Popup:** Ortak promosyon parçası yalnız `frontend.sources.*` rotalarında içerik/başlatma betiği üretmez. Global popup ayarı ve diğer sayfalardaki davranış korunur. Kaynak ilk ziyaretinde popup yok; aktif NL ana sayfa popup'ı gerçek tarayıcıda görünmeye devam ediyor.
- **Boyut:** Dosya JSON çıktısı1024byte altınıB,1MiB altını iki basamaklıKB, daha büyüğüMB gösterir. Boyut hücresi nowrap; DB byte değeri/sayısal SQL sort aynı kalır. ÖrnekPDF1.25KB, HTML1.37KB. Tür sütununun genişliği için önceki isteğe bağlı UX önerisi bu dar kapsamda uygulanmadı.

**Değişen uygulama/config dosyaları:** docker-compose.yml, docker/php/99-uploads.ini, public/php.ini, resources/views/errors/429.blade.php, dört lang/*/errors.php, resources/views/frontend/layouts/section/footer-popup.blade.php, app/Http/Controllers/Backend/SourceFileController.php, resources/views/backend/sources/index.blade.php. İlgili test dosyası tests/Feature/SourceFilesIntegrationTest.php; rehber/index/README/AGENTS kaydı güncellendi. Yeni migration, paket, CSS/JS veya sürüm değişikliği yok;1.1.43 korunur.

**Dar kabul:** `docker exec eduan-web-laravel.test-1 php vendor/bin/phpunit tests/Feature/SourceFilesIntegrationTest.php` → **22 test/256 assertion başarılı**. Popup kaynak/home kontrolü, dört dilde gerçek429 render/headers/tek layout ve B/KB/MB sınırları eklendi. Yalnız yeni/değişen PHP'lerde Pint başarılı; compose config --quiet başarılı. Tüm proje suite'i tekrarlanmadı.

Browser eklentisi yok; mevcut Playwright/Chrome ile http://localhost: panelTR1440/390;429NL/TR/EN/FR×1440/390; kaynak popup yok/ana sayfa popup korunur. **12 tarayıcı kontrolü, JS0, beklenmeyen5xx0, yatay taşma0**. İngilizce ana DB dil kaydı olmadığı için EN kontrolü yalnız geçici misafir oturumundaki locale=en ile yapıldı, dil kaydı/aktivasyon değiştirilmedi. Geçici kanıtlar `/private/tmp/eduan-source-revisions-20261105/` altında; 429-tr-390.png,429-nl-1440.png,sizes-1440.png,source-no-popup-1440.png incelendi.

**Canlı uygulama sınırı:** Bu tur production sunucusu değiştirilmedi. Yayında mevcut `public_html/php.ini` dosyası korunuyorsa iki upload/post satırı ona ayrıca birleştirilmeli; SiteGround web PHP etkili değerleri ve proxy gövde sınırı kontrol edilmeli. `public/php.ini` kaynak değişikliği tek başına canlı ayarın etkinleştiği kanıtı değildir. Yeni Blade/dil kaynakları yayımlandıktan sonra normal view cache adımı uygulanır. R2 gerçek denemesi ve önceki Symfony yaması bu kapsamın dışındadır.

**5 Kasım güvenlik devamı:** Symfony üretim yamaları sonraki onaylı çalışmada uygulandı; altı üretim bildirimi kapandı. Güncel test ve kontrollü yayın kararı [97](97-SYMFONY-GUVENLIK-YAMALARI-VE-YAYIN-KABULU.md) içindedir. Bu rehberdeki eski Symfony bekleme kayıtları tarihseldir; gerçek R2/canlı kabulü ayrı kalır.


## 9 Kasım — Cloudflare R2 ayar metinleri ve açık anahtar

Kullanıcının açık seçimi: **anahtar formda açık görünsün ve global settings kaydında şifresiz saklansın**. Cloudflare R2 adı depolama seçeneğinde/hesap/bucket/Access Key ID/Secret Access Key/dosya adresinde açıkça yazılır. Boş placeholder’lar dört dilde tamamlandı; uzantı açıklaması ve CORS/HTTPS/bucket bağlantı açıklaması anlaşılır hale getirildi. Turnstile’a ait eski gizli anahtar yardım metni bu alandan kaldırıldı.

`PrepareSourceSettings` yeni dolu secret değerini düz metin kaydeder; boş gönderim mevcut değeri korur. `SourceSettings::secret()` önceden şifrelenmiş bir global değeri okuyabilir; aksi halde düz metni kullanır. Formdaki `type=text` alanına bu değer Blade escape ile yazılır. Mevcut kayıtlar topluca dönüştürülmedi; kullanıcı formu kaydettiğinde yeni saklama davranışı uygulanır. Dosya bazlı mevcut `storage_config` encrypted cast’i ve ayrı yedek/Turnstile/OneSignal anahtarları değiştirilmedi. Ekran ve kayıt erişimi yine yalnız mevcut web Yönetici rolü ve ilgili ayar izinleri kapsamındadır.

Dar kabul: iki ilgili test dosyası41PHP/399assert; dört dil gerçek Blade placeholder/metin kontrolü, düz metin global kayıt, eski encrypted okuma, boş koruma, form value escape, yetki ve mevcut yerel/bulut-fake upload akışları geçti. Ek gerçek DB ham value kontrolü aynı settings senaryosunda doğrulanır. Chrome1440/390 × TR/NL/EN/FR sekiz ekran: placeholder’lar, açık sahte kayıtlı anahtar ve Cloudflare R2 seçeneği geçti; JS0/5xx0/yatay taşma0. Testing transaction/fake disk/sahte anahtar kullanıldı; gerçek R2 çağrısı, ana DB anahtar değişikliği, migration veya canlı aktarım yok. CSS/JS değişmediğinden asset/PWA1.1.44 korunur.


## 9 Kasım son birleşik kabul — Kaydet akışı

Gerçek submit testinde Dosya Ayarları formunun `ajaxForm` sınıfı ortak AJAX işleyicisine bağlanmadığından JSON belgeye gittiği bulundu. Kullanıcı onayıyla yalnız `js-validation` yapıldı; iki dar PHP85assert ve gerçek Chrome kayıt/boş koruma/422hata-aynı sayfa kabulü geçti. Global anahtar davranışı, izinler ve depolama değişmedi. [103 — Birleşik test ve yayın kararı](103-20261109-SON-GELISTIRMELER-YAYIN-KABULU.md).
