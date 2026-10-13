# 64 — PWA güncelleme, manifest ve ikon düzeltmeleri

7 Ekim 2026. Kullanıcının63 sonrası sıradaki faza devam talimatıyla PWA güvenilirlik fazı uygulandı. Üst menü tasarımı ve yeni özellik eklenmedi. APP_VERSION **1.1.19**.

## 1. Worker ve cache

- Kök worker URL'si `/pwa-sw.js` ve uygulama manifest kimliği `0123456789` korunur. Kök dosyadaki `self.PWA_CACHE_VERSION` APP_VERSION ile birlikte1.1.19 oldu; worker statik cache adını bu sürümden üretir. Eski doğrudan `/assets/` worker'ı için fallback3 vardır; mevcut kayıt temizliği yalnız eski `/assets/` scope'una uygulanır.
- Sonraki CSS/JS/ikon yayını sırasında **APP_VERSION ve public/pwa-sw.js içindeki sürüm birlikte değiştirilmelidir**. İlgili Node testi yerel ENV veya process APP_VERSION mevcutsa eşitliği denetler; ENV dosyası olmayan CI ortamında dosya zorunluluğu yoktur. Bu dinamik sunucu sürümü sorgulama veya otomatik deploy değildir.
- Install artık `skipWaiting()` çağırmaz. Açık sekmeler/PWA eski worker ile devam eder; bunlar kapandıktan sonra yeni worker etkinleşir, yalnız uygulamanın eski cache'leri kaldırılır. Otomatik sayfa reload veya sınav state sıfırlama yoktur. İlk kurulumda `clients.claim()` korunur. [Güncelleme yaşam döngüsü](https://web.dev/learn/pwa/update).
- HTML network-only; kesintide yalnız genel statik offline ekranı döner. Livewire, POST, soru/çözüm yüklemeleri, PDF ve sonuç HTML'i cache'e girmez.
- Statik cache okuma/yazma başarısızlığı başarılı ağ yanıtını bozmaz. HTML response, private/no-store, redirect veya başarısız HTTP yanıtı statik cache'e yazılmaz. Cache erişimi yokken offline desteği garanti edilmez.
- Registration, eski scope kaldırma ve update promise hataları aynı catch'e ulaşır; başarı/debug console mesajları kaldırıldı, gerçek başarısızlık console.warn ile korunur. Online offline uyarısı nl/en/tr PHP dil kaynaklarından gelir. Statik offline ekranının mevcut Flemenkçe metni korunur; yeni dil tercihi veya özel veri saklanmaz.

## 2. Manifest ve ikon üretimi

- Manifest linki yalnız Chrome ana sayfasıyla sınırlı değildir; ortak ön yüz layout'unda bulunur. iOS capable=yes/status-bar=default ve180px PNG Apple touch icon eklendi. JPEG favicon MIME bilgisi image/jpeg; maskable192/512 PNG purpose=maskable. [Apple meta ayarları](https://developer.apple.com/library/archive/documentation/AppleApplications/Reference/SafariWebContent/ConfiguringWebApplications/ConfiguringWebApplications.html).
- Mevcut marka korundu. Bundled üç PNG, mevcut yüksek çözünürlüklü fav-4096.jpg kaynağından üretildi; altı mevcut normal JPEG ve eski maskable JPEG dosyaları silinmedi/değiştirilmedi.
- `PwaIcons` kaynak oranını korur; beyaz opak kare canvas'a yerleştirir. Maskable içerik kare kenarının%56'sına sığar; kare köşeleri bile merkezdeki%40 yarıçap güvenli alan içinde kalır. [Maskable güvenli alan](https://web.dev/articles/maskable-icon).
- Settings favicon güncellemesi aynı helper ile normal JPEG, maskable PNG ve Apple PNG üretir. Laravel13'ün `image` container binding'i ile Intervention facade çakışması üretim testinde ortaya çıktı; yalnız bu PWA yolunda mevcut Intervention manager doğrudan proje driver'ı ve desteklenen dört seçenekle kurulur. Config'teki quality constructor seçeneği aktarılmaz; JPEG kalite85 encoder aşamasında korunur. Paket/config driver değiştirilmedi.
- İkon üretimi tamamlanmadan manifest yayımlanmaz; manifest aynı dizinde `File::replace` ile atomik değiştirilir. Eksik kaynak/disk yazımı/manifest yazımı gerçek hatası raporlanır ve settings alanına çevirili422 validation mesajı döner. Hata gizlenmez; eski manifest korunur. Settings kaydı ve önceden yazılmış ikonlar için DB/dosya rollback eklenmedi.
- Global manifest dili paneldeki geçici dile bağlı değildir; site defaultLocale/config locale kullanılır. Hazır manifest lang=nl; ad/renk/scope/start URL/id korunur. Aynı sürüm sırasında favicon değiştirmek worker cache'ini otomatik temizlemez; yayın/ikon değişiminde sürüm artırma kuralı geçerlidir.

## 3. Ekonomik dar test kanıtı

**JavaScript:** `node --test tests/Frontend/PwaSafetyTest.cjs` son ilgili koşuda12/12 başarılı. Ardından ENV olmayan ortam toleransı değişen yalnız sürüm testi1/1 tekrar başarılı oldu. Senaryolar: anonim offline install, private/public HTML hariç, network kesintisi, statik-only/POST hariç, eski cache temizliği, yeni namespace/offline asset, cache read/write hatası, HTML/private/no-store/redirect/HTTP error hariç, APP_VERSION/id, güvenli isim textContent, guest temizliği, registration/update hatası ve çevirili uyarı. Güncellemeler install sırasında beklemeyi atlamaz.

**PHP:** `tests/Feature/PwaReleaseTest.php` içinde6 farklı senaryo dar koşularla başarılı. Son altı test koşusunda ilk beşi geçti, yalnız manifest mock'u dil dosyası okumayı engelledi; test partial mock'a düzeltildikten sonra son senaryo1test/4assert geçti. Tek altı-test tamamı yeşil koşusu iddia edilmez. Kapsam: Safari UA ile ana sayfa dışı metadata ve nl/en/tr uyarı; bundled MIME/boyut/id; gerçek icon üretimi/oran/opaklık/güvenli alan; eksik kaynak, disk write=false ve manifest replace hataları/önceki manifest koruma. Testler yalnız testing DB transactionları ve yeni private temp dizinleri; ana DB veya gerçek uploads değiştirilmedi.

İlk üretim testinde gerçek image binding hatası, ardından constructor quality uyumsuzluğu doğrulandı ve PWA helper'ında giderildi. Boş favicon ayarı ve File mock'u test hazırlığı hatalarıdır. Pint7 PHP dosyada başarılı; PHP/JS syntax ve seçili diff whitespace kontrolü başarılı. Genel suite/build/migration/Composer çalıştırılmadı.

## 4. Chrome QA

Browser plugin yok; `frontend-testing-debugging` gereği mevcut Playwright + sistem Chrome kullanıldı. Ayrı testing bootstrap, localhost8765, private QA storage; normal localhost80/3306 veya Sail servislerine müdahale yok. QA sunucusu kontrol sonunda durduruldu. Gerçek Chrome bağlamında:

| Kontrol | Sonuç |
|---|---|
| Login URL/title/anlamlı form, framework hata ekranı yok | Başarılı |
| Manifest/kimlik/iki maskable ikon ve iOS metadata | Başarılı |
|1440px masaüstü ve390px taşmasız görünüm | Başarılı |
| Network offline → /account/profile → genel offline ekranı | Başarılı |
| Cache URL'lerinde özel HTML yok, guest adı gizli | Başarılı |
| Sentetik1.1.19→1.1.20 worker update açık sayfada waiting | Başarılı |
| Açık sayfa eski asseti kullanmaya devam eder | Başarılı |
| Sayfa kapanıp tekrar açıldığında yeni cache/asset, eski cache silinir | Başarılı |
| Uygulama JS exception/PWA warning/yerel HTTP400+ |0 |

Sentetik sürüm değişimi yalnız `/tmp` QA router'ın servis ettiği root/probe response'larında; proje sürümü ve dosyaları1.1.19 kaldı. Chrome/Playwright native alert dismiss yarışında iki QA denemesi kesildi; yalnız QA bağlamında alert çağrısı/metni kaydedilip native dialog yerine doğrulandı. Ürün alert davranışı değiştirilmedi; native modalın cihazda gösterimi kabul edilmedi. Beklenen offline network console ERR_INTERNET_DISCONNECTED dışında hata yoktu. İki ana fotoğraf:

- [Mobil login](/private/tmp/eduan-pwa-mobile.png)
- [Offline ekran](/private/tmp/eduan-pwa-offline.png)
- [Masaüstü](/private/tmp/eduan-pwa-desktop.png)

Bu headless Chrome ve viewport testidir; fiziksel Android/iOS kurulumu, Safari PWA, eski kullanıcının production worker geçişi veya açık gerçek sınavın cihaz testi değildir. Offline/link/maskable standartları doğrulanır, işletim sistemi icon kırpmasının gerçek görünümü ayrıca cihazda görülmelidir.

## 5. Değişen dosyalar ve sıra

- `public/pwa-sw.js`, `public/assets/pwa-sw.js`, `public/assets/app.js`
- `public/pwa-manifest.json`, `config/manifest.php`
- `public/icons/apple-touch-icon.png`, `public/icons/manifest-icon-192.maskable.png`, `public/icons/manifest-icon-512.maskable.png`
- `resources/views/frontend/layouts/app.blade.php`
- `app/Helpers/PwaIcons.php`, `app/Actions/Settings/SettingSaveAction.php`
- `lang/nl/pwa.php`, `lang/en/pwa.php`, `lang/tr/pwa.php`
- `tests/Frontend/PwaSafetyTest.cjs`, `tests/Feature/PwaReleaseTest.php`
- `.env` yalnız APP_VERSION1.1.19; bu rapor, AGENTS, standartlar16, index ve63 faz durumu.

Faz2 kod düzeltmeleri ve ilgili yerel QA tamam. Sırada63 Faz3 sorgu/cache ve kontrollü yayın kapısı. Canlıya alma, gerçek R2/cron, üst menü görsel değişimi, kullanıcı geçmişi, ana DB/schema ve yeni özellik yok. Cihaz/üretim kontrolleri henüz tamamlanmadığından bütün uygulama canlıya hazır ilan edilmez.
