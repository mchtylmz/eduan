# 92 — Superadmin için panel rehberi bağlantısı

2 Kasım 2026. Kod öncesinde AGENTS.md, README, 03/16, 40 ayar/menü ve 87 güvenlik rehberi, mevcut genel ayar formu, SettingsController/SettingSaveAction, sidebar ve Yönetici rolünün 90 kaydı incelendi.

## Kullanım

- Yönetici rolüyle Ayarlar → Site Ayarları / genel sekmesine girin.
- Formun sonunda, Kaydet düğmesinden önce **Doküman görüntüleme linki** alanı vardır.
- Alanın varsayılanı boştur; migration veya önceden atanmış bir doküman adresi yoktur.
- HTTP/HTTPS adresini kaydettiğinizde sidebar menüsünün sonunda gri kitap ikonu ve **Panel rehberi** bağlantısı görünür.
- Bağlantı yeni sekmede açılır; `noopener noreferrer` kullanılır.
- Alanı boş kaydetmek bağlantıyı gizler. Diğer sekmelerden ayar kaydetmek bu değeri değiştirmez.
- Alan ve bağlantı yalnız mevcut web **Yönetici** rolüne görünür; genel `user-type:admin` yetkisi yeterli değildir. Gent, öğretmen ve öğrenci kapsam dışıdır.
- Kaydetmede mevcut dashboard/settings:update kontrollerine ek olarak aynı rol şartı vardır; alanı boşaltma dahil doğrudan POST denemesi yetkisiz rolde 403 olur.

## Uygulama

Mevcut key/value ayar kaydı `panelGuideUrl` kullanılır; tablo, izin migration’ı veya yeni controller/route eklenmedi. URL sunucuda yalnız HTTP/HTTPS ve en fazla 2048 karakter olarak doğrulanır; boş/null temizleme olarak kaydedilir. Sidebar da URL’yi bağımsız kontrol eder; elle girilmiş bozuk veya çalıştırılabilir adres linke dönüşmez. Blade değeri HTML kaçışıyla basar. Sunucu bağlantıyı indirip doğrulamaz ve dış servise istek göndermez.

Form alanı gerçek genel ayar formunun sonunda ayrı Blade parçasıdır. Sidebar parçası mevcut menüden sonra aynı kaydırma alanına eklenir; OneUI nav-main/Bootstrap sınıfları kullanılır. Özel CSS/JS eklenmedi; APP_VERSION/root worker 1.1.42 korunur. Metinler TR/NL/EN/FR kaynaklarındadır.

## Dar testler ve sınır

- PHP8.5.12 ile `tests/Unit/PanelGuideTest.php`: **13 senaryo / 164 doğrulama geçti**. HTTP/HTTPS, eksik/boş/sıfırlanan değer, kötü protokol, göreli/bozuk/uzun/array/sayısal URL, tam rol/guard ayrımı, yetkisiz yazma/boşaltma ve ilgisiz ayarı kaydetme kontrol edildi.
- Gerçek `SettingSaveAction`, validator, Spatie rol kontrolü ve iki Blade görünümü kullanıldı. Settings deposu ve cache bellekteydi. Eloquent yalnız sorgu oluşturabilir; PDO erişimini hata veren bağlantı korumasıyla bütün DB erişimi engellendi.
- Minimal test ortamındaki guard, POST yöntemi ve Eloquent query-builder bağımlılıkları tamamlandı; bunlar ürün hatası için istisna eklenerek çözülmedi. PHPUnit beklentisiz mock bildirimi stub kullanılarak giderildi; son görünüm alt kümesi **1 test /112 doğrulama**, bildirimsiz geçti.
- Gerçek Blade ile 4 dil ×4 rol ×3 URL durumu, alanın görünürlüğü/kaçışlı adres/yeni sekme özellikleri kontrol edildi. Geçici Chrome fixture’ı ayrıca 24 gerçek parça çıktısı üretti.
- Browser plugin yok; mevcut Playwright/Chrome kullanıldı. **48 tarayıcı hücresi:** 4 dil ×3 rol ×2 URL durumu ×2 genişlik (1440/390). Alanın boş/dolu değeri, görünürlük, native URL doğrulaması ve belge taşması kontrol edildi.
- Bağlantıya gerçek tıklamada yeni sekme doğru URL’ye gitti, kaynak sekme korundu ve `window.opener` null oldu. Hedef yanıtı tarayıcıda taklit edildi; gerçek bucket/siteye istek gönderilmedi. JS istisnası0.
- Görsel kanıt: `/private/tmp/eduan-guide-1440.png`, `/private/tmp/eduan-guide-390.png`; gerçek Blade/OneUI parçalarıdır, tam oturumlu dashboard kabulü değildir.
- Değişen 7 PHP/Pint, PHP syntax, dört dil anahtar eşliği/doluluk ve whitespace kontrolü başarılı.

Eduan laravel.test/mysql servisleri durmuş olduğu için gerçek DB/oturumlu HTTP kaydetme testi çalıştırılmadı. Servisler açılmadı veya değiştirilmedi; ana/testing DB, migration, mevcut ayarlar, bucket, gerçek doküman yüklemesi ve deploy üzerinde işlem yapılmadı. Genel test/build çalıştırılmadı.

## Değişen dosyalar

- `app/Helpers/PanelGuide.php`: kesin rol ve güvenli bağlantı kontrolü.
- `app/Actions/Settings/SettingSaveAction.php`: yeni alanın yazma yetkisi, URL doğrulaması ve boşaltılması.
- `resources/views/backend/settings/tabs/general.blade.php`, `partials/panel-guide.blade.php`: genel ayarın sonundaki URL alanı.
- `resources/views/backend/layouts/page-container.blade.php`, `sidebar-guide.blade.php`: menü sonundaki koşullu bağlantı.
- `lang/{tr,nl,en,fr}/guide.php`: dört dilde metinler.
- `tests/Unit/PanelGuideTest.php`: dar rol/URL/kaydetme/render sınırları.
- `Codex/docs.html`, `Codex/index.html`, AGENTS.md ve bu kayıt: kullanım rehberi ve test kapsamı.

Önceden değişik .DS_Store, silinmiş Doge.html, OneSignal klasörü ve site görselleri korunur. docs.html önceki kullanıcı isteğinin güncel rehberidir; bu tur yalnız yeni ayarın kullanım bilgisi eklenir, public’e taşınmaz.
