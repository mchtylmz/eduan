# Güvenlik ve yetkilendirme

Bu belge ilk kod taramasının başlangıç bulgularıdır; tam penetrasyon testi veya üretim yapılandırması denetimi değildir. İlk dar düzeltme paketinde bilgi dosyası, sınav başlangıcı, iki panel formunun kayıt izni, çeviri yazma izni/dil kapsamı ve süreli sınav sınırları ele alındı. Güncel durum ve test kanıtı `11-ILK-DUZELTMELER-VE-SINAV-AKISI.md` içindedir; diğer satırlar araştırma adaylarıdır.

## Mevcut katmanlar

`routes/web.php` paneli `auth` ve `can:dashboard:access` ile kapatır. `routes/backend.php` kaynak bazında `can:*:view` kapıları kurar. `PermissionSeeder` görüntüle/ekle/güncelle/sil ve çözme izinlerini tanımlar. `sidebar-menu.blade.php` menüyü `@can` ile gösterir. `LoginAction` `Auth::attempt` sonrası oturumu yeniler; `CheckUserStatus` pasif kullanıcıyı çıkarır. `Frontend/Account` route'ları `auth` altında, öğretmen öğrencisi sorgularının bir kısmı `UsersController::edit` ve `UserTable::builder` içinde sınırlanmıştır.

Bu parçalar tek başına her alt ekrana ve Livewire metoduna izin uygulandığını kanıtlamaz. Bir panel sayfasının `view` yetkisi, aynı controller'daki create/edit/export/sonuç detaylarına ayrıca doğru işlem yetkisi uygulandığı anlamına gelmez. Kullanıcı, öğretmen, öğrenci ve sonuç nesnesi sahipliği her endpoint/metot için incelenmeli.

## Öncelikli doğrulama konuları

1. **Kamuya açık bilgi dosyası — giderildi:** `public/systeminfo.php` başlangıçta `phpinfo()` çağırıyordu. Dosya kaldırıldı; Docker içi yerel HTTP isteği artık 404. Üretime dağıtım yapılmadı.
2. **Login ve kayıt koruması:** `LoginAction` oturum yeniliyor, ancak `routes/frontend.php` giriş/kayıt/şifre/verify akışlarında `throttle` görülmedi. Livewire eylemleri ayrıca incelenmeli. `Register::save` e-posta gönderir; testte gerçek SMTP çağrılmamalı.
3. **E-posta doğrulama:** `AuthController::verify` rota model binding ile `email_verified_token` üzerinden kullanıcıyı bulup güncelliyor; token süresi/imzası ve yeniden kullanım kuralı görünmüyor. `EmailVerificationMail` token üretimi ayrı yerde. Güvenlik tasarımı ve testleri bir sonraki fazda netleştirilmeli.
4. **Canlı sınav/test yanıtı:** İlk pakette süreli `Frontend/Exams/SolveForm` DB bitiş zamanı, kalıcı cevap, eylem izni ve nesne sahipliğiyle korundu; bölüm/soru/cevap eşleştirmesi ve bitmiş sonucu tekrar yazmama testleri eklendi. `Frontend/Tests/SolveForm` tarafında yanıt doğruluğunu public `results` dizisinde hesaplama bulgusu ayrı kapsam olarak açık kaldı.
5. **Livewire ve CSRF:** `bootstrap/app.php` `livewire/*` için CSRF istisnası tanımlıyor. Livewire'ın kendi istek koruması ve gerçek endpoint/oturum davranışı doğrulanmadan güvenli/güvensiz hükmü verilmemeli; istisnanın gerekçesi sorgulanmalı.
6. **Ayar, dosya ve HTML:** `SettingsController::store` işlem izni kontrol ediyor, `SettingSaveAction` çok sayıda ayar ve dosyayı tek istekte yazıyor. Dosya MIME/içerik doğrulaması, `UploadFileAction` uzantı/gerçek dönüşüm eşleşmesi, TinyMCE HTML ve PWA ikon/manifest dosyaları sonraki fazda incelenmeli.
7. **Proxy/IP/HTTPS:** Üretimde `AppServiceProvider` `forceScheme('https')` ve request server `HTTPS=on` kullanıyor; güvenilir proxy/Cloudflare yapılandırması burada görünmüyor. Gerçek ağ topolojisi bilinmeden `TrustProxies` ayarı değiştirilmemeli. Login hız sınırı ve doğru IP kaynağı birlikte ele alınmalı.
8. **Paket ve sır:** `SettingSeeder` örnek SMTP kimlik bilgisi değerleri içeriyor; gerçek üretim sırrı olduğu varsayılmamalı, ancak repoda düz metin bulunması gereklilik ve etkisi açısından değerlendirilmelidir. `.env` veya kullanıcı verisi rehbere kopyalanmamalı.

## Gelecek değişiklikte asgari güvenlik sözleşmesi

Her okuma ve yazma yolunda: kimlik doğrulama, işlem izni, kaydın görünürlüğü/durumu, nesne sahipliği veya öğretmen-öğrenci kapsamı, doğrulanmış parametreler, CSRF/oturum, tekrar gönderim ve güvenli hata cevabı birlikte düşünülür. Menünün gizlenmesi yalnız UI davranışıdır. Yetkisiz/hatalı/süre aşımı testleri ilgili özelliğin dar test paketine eklenir. Bulgu doğrulanmadan geniş çaplı güvenlik refactor'u yapılmaz.
