# Cloudflare Turnstile ve bülten hız sınırı

26 Ekim 2026. Kullanıcı yalnız giriş yapmamış kullanıcıların beş formunu Turnstile Managed ile korumayı onayladı. Mevcut `app/resources/routes/config` taramasında reCAPTCHA/CAPTCHA uygulaması bulunmadı; kaldırılacak eski sağlayıcı entegrasyonu yoktu.

## 1. Kapsam

| Form | Sunucu action değeri | Kural |
|---|---|---|
| Giriş | login | Misafir ve özellik açıkken zorunlu |
| Kayıt | register | Misafir ve özellik açıkken zorunlu; mevcut kayıt durumu/limit korunur |
| Şifre kurtarma | recover | Misafir ve özellik açıkken zorunlu; mevcut limit korunur |
| Yeni parola | password_reset | Misafir ve özellik açıkken zorunlu; token kilidi/süresi/tek kullanım korunur |
| İletişim | contact | Misafir ve özellik açıkken zorunlu; üye için gösterilmez |
| Bülten | Yok | CAPTCHA yok; IP başına 60 saniyede bir gönderim denemesi |

Test/sınav cevaplama, çalışma kaydı ve diğer üye işlemlerine Turnstile eklenmedi. Bülten sınırı hem misafir hem üyede çalışır; e-posta değiştirmek sınırı sıfırlamaz. Başarısız alan doğrulaması da deneme sayılır. Mevcut güvenlik cache store’u ve kısa kilit, aynı IP’nin eşzamanlı ikinci denemesini engeller; normal cache temizliği sayacı kaldırmaz.

## 2. Ayarlar ve anahtarlar

**Panel → Ayarlar → Cloudflare Turnstile** (`/admin/settings?activeTab=turnstile`). Bölüm yönetici tipindeki kullanıcıya görünür; kayıt ayrıca dashboard/settings:update ve admin ister. Yeni migration veya rol/izin tanımı gerekmez.

- Açık/kapalı, site anahtarı ve gizli anahtar alanları.
- Varsayılan kapalı; ana yerelde anahtar veya aktivasyon yapılmadı.
- Gizli anahtar Laravel Crypt ile şifreli saklanır, HTML/JSON’a geri yazılmaz.
- Gizli alan boş bırakılırsa mevcut anahtar korunur.
- Açmak için iki anahtar gerekir; bilinmeyen turnstile ayar adları reddedilir.
- Kullanıcı kendi anahtarlarını ekrandan girecek. Sohbetten anahtar istenmedi.

Cloudflare panelinde widget türü **Managed** olmalıdır; Managed modu PHP/SDK parametresiyle zorlanmaz. İzinli hostname ile uygulamanın `APP_URL` hostname’i ve tarayıcıda açılan site eşleşmelidir. Mevcut onaylı yerel `APP_URL=https://hypotenuse.be` korunur; localhost testi için bu eşleşmenin operatör tarafından uygun kurulması gerekir.

## 3. Sunucu doğrulaması

`VerifyTurnstile`, açık/misafir durumda token’ı sabit HTTPS Siteverify endpoint’ine gönderir. Başarı kesin boolean true olmalı; hostname yapılandırılmış APP_URL’ye, action ilgili formun sunucudaki sabit değerine eşit olmalıdır. Eksik/uzun token, eksik veya okunamayan anahtar, yanlış hostname/action, hatalı JSON, HTTP hatası ve bağlantı sorunu işlemi durdurur.

HTTP connect timeout3/toplam8 saniye, otomatik retry yoktur. Token/secret ve transport exception içeriği loglanmaz. İstemciden gelen IP başlığı sağlayıcıya güvenilir IP diye aktarılmaz. Cloudflare token’ı 5 dakika ve tek kullanım ile doğrular; uygulama her korunan gönderimde Siteverify çağırır, istemci başarı işaretine güvenmez.

`VerifiesTurnstile` token’ı alıp temizler ve her deneme sonunda ilgili widget’a reset olayı gönderir. Action kontrolü bütün beş formda yapılır; doğrulama DB/mail/parola/giriş yan etkisinden önce gelir. Alan hatasında da yeni challenge alınır.

## 4. Tarayıcı yaşam döngüsü

Mevcut Blade/Livewire/Alpine kullanıldı; yeni paket/build/framework yok. Yerel `turnstile.js` widget gereken durumda tek SDK isteği oluşturur. Explicit render, form/locale/action bağlama, token callback, süre dolması, hata, yeniden deneme ve destroy yönetilir. Token setter defer’lıdır; yalnız challenge tamamlandı diye fazladan Livewire isteği gönderilmez.

Her form kendi widget kimliğine sahiptir. Dar alanda compact, yeterli genişlikte flexible görünüm seçilir. Üye veya kapalı özellikte widget/harici SDK isteği oluşturulmaz. Yerel helper script’i sayfada hazır kalır; açık bir sayfada ayar sonradan değişirse yardımcı fonksiyon eksik kalmaz.

Livewire4 `component` event adını özel hedef parametresi saydığı için özel olay verisinde **instanceId** kullanılır. İlk testte bu gerçek uyumsuzluk yakalanıp düzeltildi.

## 5. Test kanıtı

- Beş formda eksik token yan etkisiz reddedildi; doğru token/form action alan validation’a geçti ve token temizlendi.
- Sağlayıcı false/string success, yanlış/eksik action, yanlış hostname, expired/duplicate, 503, bozuk JSON, bağlantı kesilmesi ve bozuk şifreli ayar reddedildi.
- Kapalı özellik/üye iletişimi sağlayıcı çağırmadı. Tekrar gönderimde tüketilen token yeniden kullanılamadı.
- Bülten formu CAPTCHA’sız; ikinci deneme e-posta değişse bile engellendi. Limit normal cache temizliğinden etkilenmedi, 61 saniye sonra açıldı.
- Ayar yetkisi, eksik anahtar, şifreli kayıt, boş gizli alanı koruma ve HTML’de sırrı göstermeme geçti. Açıkken site anahtarını boş/null yapma reddedilir; kapalıyken temizlenebilir. Bu son aynı senaryonun 6→16 doğrulama genişletmesidir (+10); toplam senaryo sayısını artırmaz.
- Birleşik ilgili paket **174 PHP /1194 doğrulama**; son altı yeni senaryo **6/41**, güvenlik paketindeki ek hatırla senaryosu **1/9**. Toplam **181 farklı PHP /1254 doğrulama**, ayrı koşular; tek 181’li temiz koşu iddiası yok.
- `tests/Frontend/TurnstileTest.cjs`: **6 Node testi** başarılı. Çoklu form/tek SDK, dar widget, expiry/error/retry, reset/destroy ve başarısız yüklemeyi kapsar.
- Browser plugin mevcut değildi; mevcut Playwright ve gerçek Chrome kullanıldı. Gerçek Laravel HTTP kernel + yalnız testing transaction, Mail/Queue/HTTP fake; işlem sonunda rollback.
- Chrome1440px/TR ve390px/NL’de beş form açıldı: widget bir tane, yatay taşma yok. Eksik token görünür hata verdi; sahte challenge tamamlandıktan sonra gerçek login/profile oturumu başarılı. Üye iletişiminde widget yok, ayar gizli alanı boş.
- JS pageerror0/beklenmeyen5xx0. Ekran görüntüleri `/tmp/eduan-turnstile-{1440,390}.png`, ayarlar `/tmp/eduan-turnstile-settings.png`; görsel kontrol yapıldı.
- İlk tarayıcı beklemesi QA köprüsünün BinaryFileResponse JS dosyasını boş iletmesinden kaynaklandı; köprü düzeltildi. Bu bir ürün JS düzeltmesi veya gerçek Cloudflare hatası diye sunulmaz.

## 6. Dosyalar ve sürüm

- `app/Helpers/TurnstileSettings.php`, `app/Actions/Auth/VerifyTurnstile.php`, `app/Traits/VerifiesTurnstile.php`.
- `app/Actions/Settings/{PrepareTurnstileSettings,SettingSaveAction}.php`, `app/Enums/SettingsTabsEnum.php`, `app/Http/Controllers/Backend/SettingsController.php`.
- Beş `app/Livewire/Frontend/Auth/*`/`Contacts/ContactForm` bileşeni ve Blade formları.
- `app/Actions/Contacts/LimitNewsletterRequests.php`, `app/Livewire/Frontend/Newsletter/NewsletterForm.php`.
- `resources/views/backend/settings/tabs/turnstile.blade.php`, ortak `resources/views/components/frontend/turnstile.blade.php` ve frontend layout.
- `public/assets/turnstile.js`, `lang/{tr,nl,en,fr}/turnstile.php`, PHP ve Node testleri.
- Yeni JS nedeniyle yerel ignored `.env` APP_VERSION ve `public/pwa-sw.js` birlikte **1.1.40**. Eski worker URL’si/cache kuralları korunur.
- Bu belge, README, AGENTS ve index bağlantıları.

## 7. Kalan kurulum/kabul

Anahtarlar ve özellik ana yerelde boş/kapalı bırakıldı. Kullanıcı anahtarları girip açacak; gerçek Managed challenge/Cloudflare teslimi yapılmadı. Fiziksel iOS/Safari/Android testi veya canlı hosting/proxy denetimi değildir. Gerçek IP’nin proxy arkasında doğru çözülmesi canlıda önemlidir; proxy topolojisi varsayılarak değiştirilmedi.

Güvenlik taramasının ayrıca onaylanan beş düzeltmesi **87** numaralı kayıttadır. E-posta onayının giriş şartı olup olmadığı ayrı açık politika maddesidir; bu değişiklikle eski onaysız kullanıcılar kapatılmadı.

Migration, ana DB verisi, gerçek e-posta/push/AI isteği, yeni bağımlılık, servis/port değişimi veya deploy yok. Dolayısıyla gerçek anahtar kabulü ve üretim işletim kontrolü tamamlanmadan tüm siteye koşulsuz güvenlik/canlı onayı verilmez.

## 8. Resmî kaynaklar

- [Cloudflare sunucu token doğrulaması](https://developers.cloudflare.com/turnstile/get-started/server-side-validation/)
- [Explicit widget render/reset/remove](https://developers.cloudflare.com/turnstile/get-started/client-side-rendering/)
- [Widget ayarları ve callback’ler](https://developers.cloudflare.com/turnstile/get-started/client-side-rendering/widget-configurations/)
