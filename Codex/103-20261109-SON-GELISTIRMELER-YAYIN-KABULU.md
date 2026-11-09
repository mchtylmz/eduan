# 9 Kasım — Son geliştirmelerin birleşik yayın kontrolü

Kaynak: `06e8e137218ddee65f090d9d69ed583e54117b52`, son canlı v4 kodundan sonraki dört geliştirme commit’i. Yerel sürüm/PWA1.1.46. Kullanıcı sıkı varyasyon ve kullanıcı akışı kontrolü ile açık yayın kararı istedi. İnceleme sırasında bulunan ve kullanıcı tarafından ayrıca onaylanan tek form sınıfı düzeltmesi uygulandı; başka uygulama davranışı değiştirilmedi.

## Karar

**Kod/test açısından kontrollü canlıya alıma uygun.** Gerçek kullanıcı akışında bulunan Dosya Ayarları Kaydet hatası, kullanıcının açık onayıyla tek satırda düzeltildi ve ilgili kabul geçti. İncelenen son geliştirmelerde açık yayın engeli kalmadı. Bu karar yerel kod/test kapsamıdır; gerçek R2 ve üretim ortamı kabulü yapıldığı anlamına gelmez.

## 1. Doğrulanan ve kapatılan bulgu

Dosya Ayarları formu `resources/views/backend/settings/tabs/sources.blade.php:1` içinde başlangıçta `class="ajaxForm"` kullanıyordu. Ortak panel `public/backend/assets/app.js:8` yalnız `form.js-validation` formlarını doğrulama ve AJAX kaydına bağlıyor. Diğer ayar sekmeleri bu standartta; `ajaxForm` için ayrı bağlayıcı bulunmuyor.

Gerçek Chrome’da formdan Kaydet → POST `/admin/settings/store` → HTTP200, `{message,refresh:true}` JSON dokümanı. Veri kaydı başarılı, ancak kullanıcı ayar ekranından ham JSON sayfasına gidiyor; başarı mesajı/ekranda kalma gerçekleşmiyor. Bu durum PHP endpoint testleriyle tek başına yakalanmadı; gerçek tarayıcı submit testiyle bulundu. Başlangıçtaki `ffef651d` kaynakta da sınıf aynıdır; form metin/anahtar değişikliklerinin dokunduğu mevcut akışta görüldü.

Kullanıcının somut onayı alındı; yalnız `ajaxForm` → `js-validation` düzeltildi. İlgili Feature/Unit testlerine standart sınıf regresyonu eklendi. Düzeltme sonrası iki dar test85assert ile geçti; bu iki test405 senaryonun alt kümesidir,405’e tekrar eklenmez. Gerçek Chrome’da normal kayıt/başarı/aynı sekmede kalma, boş anahtarı koruma,101MB geçersizliği ve hatalı hesap kimliğinin422/mesaj/yeniden açık Kaydet düğmesi akışları başarılı. Şema, gerçek anahtar veya canlı değiştirilmedi. Tarayıcı kanıtı `/private/tmp/eduan1109-release-r2-settings-failure.png` ve aynı isimli browser logundadır.

## 2. PHP varyasyonları

İki ayrı temiz koşu:

| Koşu | Test | Assertion |
|---|---:|---:|
| Son özellikler ve komşu yetki/form işlemleri,12dosya | 386 | 1927 |
| Route cache / eski üretim log regresyonları,2dosya | 19 | 70 |
| Toplam farklı senaryo | 405 | 1997 |

İlk koşu: StudyFilterRevision, StudyReleaseCombinations, StudyPanel, StudyTrackingForm, UserImpersonation, SourceFilesIntegration, CommunicationWizard, PanelPermissionMatrix, SecurityFindingsRegression, PwaRelease, SourceFiles ve SourceStaticServer. İkinci koşu LivewireUpdateRouteCache ve ProductionLogRegression. Tüm proje suite’i çalıştırılmadı.

Kapsam: öğrenci/öğretmen/yönetici/hybrid/bağsız/readonly aktörler; modül açık/kapalı, geçmiş izni, başlangıç öncesi/geçmiş/mevcut/gelecek haftalar ve hedef/çalışma işlemleri. Pozitif/sıfır/boş hedef, altı yüzde sınırı, öğretmen rol grubu/ilişkisi, array/SQL-benzeri filtre girişi, tablo/Excel/alıcı kapsamı ve bağ kaldırıldıktan sonra iletişim reddi. Geçiş için hedef aktiflik/rol/izin, orijinal yönetici kanıtı, nested reddi, panel erişimi, öğrenci kapsamı, cihaz bağlama engeli ve güvenli geri dönüş. Destekli/bozuk dosya, boyut/MIME, sahiplik, tekrar tamamlama, yerel/bulut-fake/fallback, silme ve kaynak429/sandbox; settings düz metin/legacy okuma/boş koruma/izin/escape. Route cache açık/kapalı Livewire endpoint’i ve sitemap/arama Blade regresyonları başarılı.

PHP8.5.8 Docker/testing; ana DB’ye test yazımı yapılmadı. Loglar `/private/tmp/eduan1109-release-php.log`, `...-cache.log`; JUnit konteynerde `/tmp/eduan1109-release-junit.xml`.

## 3. JavaScript, statik ve bağımlılık

- 27Node testi geçti: çalışma format/hafta seçici, dosya bağlantısı/kart escape, hesap geçiş onayı, iletişim seçimi, PWA.
- Son değişen19PHP dosyası Pint;3JS dosyası syntax başarılı. İki dil dosyasında TR/NL/EN/FR anahtarları eşit. APP_VERSION ve root worker1.1.46 eşleşiyor.
- Gerçek yerel HTTP anasayfa200; çalışan Eduan web/MySQL sağlıklı. Başka proje servisi değiştirilmedi.
- Üretim lock audit:0advisory/0abandoned; `composer check-platform-reqs --no-dev` başarılı. ComposerPHAR’ın PHP8.5 deprecation mesajları araç kaynaklı/exit0; paket veya uygulama kodu değiştirilmedi. Dev bağımlılıkları bu audit’e dahil değil; canlı `--no-dev` kurulum kuralı sürer.
- Yeni migration veya Composer bağımlılık değişikliği yok.

## 4. Gerçek tarayıcı kabulü

Browser plugin olmadığı için kurulu Playwright/Chrome kullanıldı. Gerçek Laravel kernel/Livewire testing transaction köprüsü, fake public disk ve sahte anahtarlar kullanıldı. Gerçek üretim sunucusu/proxy veya fiziksel Safari kanıtı değildir.

Başlangıçta48 başarılı kontrol ve1başarısız kullanıcı akışı; onaylı düzeltme sonrasında yalnız o akışın2dar başarı/hata varyasyonu tekrar edildi: toplam50başarılı kapsam kontrolü. Önceki başarısızlık kapatıldı:

- Dört dil ×1440/390:16kontrol; dosya kart/tablo, yerelleştirilmiş yükleyici, gizli ilk progress ve panel1200px/mobil tam genişlik; çalışma filtre yüksekliği38px, öğretmen/durum seçimi ve filtreli tablo/Excel/iletişim bağlantıları.
- Üç ek uçtan uca başarı: gerçek multipart yerel upload/100%/link/kopyalama/iptal+onaylı silme/arama/sayfa2/tür; hatalı PDF reddi/hata mesajı/progress gizleme/yeniden açılış reset; gerçek geçiş POST’u/site panel bağlantısı/öğretmenin kendi öğrencileri/turuncu geri dönüş/yöneticiye dönüş.
- Öğretmen/durum seçimleri4dil×2genişlik8kontrol ve mevcut Yetkiler çoklu seçim için1komşu kontrol;36geçici öğretmen, arama, tek değer, doğru seçili etiket, URL/reload/reset, actions-box yokluğu.
- Çalışma/Kullanıcılar/Testler/Dosyalar ×1440/768/390:12kontrol. Ortak38px/16px, simetrikpadding, picker metin merkez sapması0px; tek/çoklu seçim ve sayfa taşması yok. NL koyu ek ziyareti görsel incelendi.
- R2 ayar görünümü4dil×1440/390:8kontrol; placeholder/etiket/açık sahte anahtar. Gerçek Kaydet akışı ilk denemede JSON yönlenme bulgusuyla başarısız oldu; onaylı düzeltme sonrası iki ek gerçek AJAX akışıyla kapandı.

Başarılı akışlarda JS istisnası0, beklenmeyen5xx0, yatay sayfa taşması0. Bulgu500/SQL hatası değil, yanlış form sınıfının HTTP200 JSON’u belge olarak açmasıdır. PHP testlerinin tek başına geçmesi UI hatasını kapatmış sayılmadı; son gerçek submit ve422 kabulü ayrıca yapıldı. Test köprüsü, AJAX FormData içindeki settings[...] alanlarını PHP gibi iç içe ayrıştıracak şekilde düzeltildi; bu geçici test aracı değişikliğidir.

## 5. Veri ve yayın sınırı

Ana ve testing için önce/sonra10tablo sayı/hash’leri aynı: users/settings/tests/tests_results/leagues/league_results/source_files/teacher_student/study_weekly_targets/study_activity_entries. Tarayıcı örnekleri/ayar kayıtları dış transaction sonunda geri alındı. Kaynak kodunda kontrol dışı değişiklik yok; önceki yerel medya/.DS_Store dosyaları korunur.

Bu testte gerçek R2/SMTP/push isteği veya canlı deploy yapılmadı. Kullanıcının açık tercihi olan global R2 anahtarının görünür/şifresiz tutulması bilinçli iş kuralıdır; burada yeni bir bulgu diye değiştirilmedi. Gerçek bulut credential/CORS ve üretim PHP/OPcache/cache/HTTP kabulü yayın sırasında ayrıca gerekir. Mevcut v4ZIP’leri değiştirilmedi; yeni onaylı kod için ayrı paket hazırlanmalıdır. Canlı ENV/upload/storage korunur; bu güncellemede yeni ENV anahtarı veya migration yok, sürüm1.1.46 gerekir.


## 6. Son yayın adımı için kısa sınır

Yeni migration/Composer değişikliği yok. Kaynak düzeltme ve test/rapor commit’i yeni pakete dahil edilmeli; önceki v4arşivleri bu değişiklikleri içermez. Canlı `.env` ve kalıcı uploads/storage korunur; APP_VERSION1.1.46, `public/` → `public_html/`, standart view/config/route cache ve OPcache kabulü uygulanır. Üretim kurulumunda `composer install --no-dev --optimize-autoloader` kuralı korunur. Yeni cron yok. Anahtarlı bakım altında gerçek login, geçiş/geri dönüş, çalışma filtre/Excel, dosya upload/view/delete ve Ayarlar Kaydet kısa kabulünden sonra uygulama açılır. Bu tur yayın paketi veya canlı aktarım yapılmadı.
