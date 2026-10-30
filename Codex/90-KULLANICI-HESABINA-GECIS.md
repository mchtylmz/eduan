# 90 — Kullanıcı hesabına geçiş

30 Ekim 2026. Kapsam: Kullanıcılar başlığı, kullanıcı detayından onaylı hesap geçişi ve ön yüzden yöneticiye dönüş. Koddan önce AGENTS.md, README, 03/16, 38 ve 87 rehberleri ile gerçek tablo/offcanvas/hesap bileşenleri incelendi; Lara Football ve Sporr örnekleri salt okunur karşılaştırıldı. Yeni paket veya liste mimarisi eklenmedi.

## 1. Kullanım ve kararlar

- Panel başlığı artık **Kullanıcılar**.
- Kullanıcılar → Görüntüle → alt solda **Hesabına giriş yap**; mevcut Düzenle sağda kalır. İsim içeren mevcut SweetAlert onayı vardır; iptal işlem yapmaz.
- Aynı tarayıcı oturumu aktif öğrenci veya öğretmene dönüşür ve ön yüz ana sayfası açılır. Yönetici, pasif, silinmiş, tipsiz veya kendi hesabına geçiş reddedilir.
- Ön yüzde Hesabım yanında turuncu geri dönüş düğmesi vardır; yönetici oturumunu ve Kullanıcılar listesini açar. Geçici oturumda çıkış yapılırsa dönüş bileti temizlenir.
- `users:impersonate` başlangıçta yalnız mevcut web **Yönetici** rolüne verilir. Gent ve öğretmen rolüne verilmez. Sonradan öğretmene izin verilirse mevcut bağlı öğrenci kapsamı korunur; panel/kullanıcı görüntüleme izinleri de gerekir.
- Geçilen öğretmenin panel izni olsa bile geçici oturum panel rotalarını kullanamaz. Hem hesap menüsü hem profil menüsündeki panel bağlantısı gizlenir; eski Livewire panel snapshot'ı da sunucuda reddedilir.

## 2. Oturum ve güvenlik

Başlatma ve dönüş CSRF korumalı POST'tur. İki yönde oturum kimliği ve CSRF token yenilenir; eski yönetici remember çerezi geçici hesabı geri ezmesin diye temizlenir. Dönüş, sunucu oturumunda tutulan rastgele token ile sınırlıdır. Kaynak hesabın güncel aktifliği, izinleri ve parola kanıtı kontrol edilir; geçersiz kaynakta oturum kapatılır. Normal isteklerde hedef/kaynak uygunluğu yeniden denetlenir; iç içe geçiş yoktur. Özel yanıtlar private/no-store olur.

Hedef kullanıcının son giriş tarihi, parolası veya kullanıcı kaydı başlangıç/dönüş sırasında değiştirilmez. Başlangıç/bitiş logları yalnız aktör ve hedef kimliklerini taşır; dönüş token'ı kaydedilmez. Bu özellik salt okunur ön yüz değildir: geçilen hesabın olağan ön yüz işlemleri kendi izinleriyle çalışır.

Yöneticinin aynı tarayıcıdaki push cihazı başka hesaba bağlanmaz: geçici oturumda SDK yüklenmez, cihaz context/register/revoke engellenir, cihaz düğmeleri pasiftir ve açıklama gösterilir. Önceki cihaz kayıt kimliği dönüşte geri konur; geçici hesaptan çıkışta sağlayıcıya iptal gönderilmez.

## 3. Onaylı migration

Kullanıcının somut onayıyla yalnız `2026_10_30_100000_grant_user_impersonation_permission` migration'ı yerel **hypotenuse** ve **testing** DB'de `--path` ile uygulandı. Şema veya kullanıcı kaydı değiştirmez; izin ve mevcut Yönetici rolünün pivot kaydını ekler. Salt okunur sonuç: iki DB'de migration Ran; ana DB'de izinli roller yalnız Yönetici. Testing DB'de Yönetici adlı rol bulunmadığından orada kalıcı rol ataması yoktur; adlandırılmış rol ataması ve tekrar çalıştırma test transaction'ında doğrulandı. Yeni rol oluşturulmadı. Diğer migration'lar çalıştırılmadı.

## 4. Doğrulama ve sınır

| Kontrol | Sonuç |
|---|---|
| UserImpersonation + BootstrapTablePilot + FrontendAccountPresentation + AuthSecurity + SecurityFindingsRegression + WebNotifications + PwaRelease | **98 test / 956 doğrulama geçti** |
| Son cihaz açıklaması ve biçim düzenlemesiyle UserImpersonation | **18 test / 111 doğrulama geçti**; yukarıdaki koşunun alt kümesi, ayrı yeni test sayısı değildir |
| Node onay/iptal/çift tıklama | **3 test geçti** |
| İlgili 14 PHP dosyası Pint; iki route PHP syntax; JS syntax; diff whitespace | Başarılı |
| TR/NL/EN/FR | Dört yeni anahtar eşliği ve doluluk geçti |
| Gerçek Chrome | Detay düğmesi, iptal/onay, ön yüz geçişi, yenileme ve yöneticiye dönüş geçti |
| Görsel | 1440/TR ve 390/NL-EN-FR; turuncu dönüş hesabın yanında, örtüşme/taşma yok |
| Tarayıcı | JS istisnası 0, beklenmeyen 5xx 0 |

Komutlar: `php vendor/bin/phpunit tests/Feature/UserImpersonationTest.php tests/Feature/BootstrapTablePilotTest.php tests/Feature/FrontendAccountPresentationTest.php tests/Feature/AuthSecurityTest.php tests/Feature/SecurityFindingsRegressionTest.php tests/Feature/WebNotificationsTest.php tests/Feature/PwaReleaseTest.php`; son dar koşu yalnız UserImpersonation; `node --test tests/Frontend/UserImpersonationConfirmTest.cjs`. PHP komutları Docker laravel.test içinde çalıştırıldı.

Browser plugin olmadığı için kurulu Playwright ve gerçek Chrome kullanıldı. Geçici HTTP kernel köprüsü yalnız testing transaction'ında örnek kullanıcı oluşturdu ve çıkışta geri aldı. Başlangıçta 302 sonrası gerçek yerel sunucuya gidilmesi test oturumunu kaybettirdi; köprü çerezleri aktarılıp yönlendirme hedefi tarayıcı navigasyonuyla açıldı. Gerçek 302/POST sözleşmesi ayrıca PHP testleriyle doğrulandı; uygulama oturum koruması bu sorun için gevşetilmedi. Kaynak kodu CSRF testinde testing DB korunurken gerçek CSRF dalı çalıştırıldı. İlk fixture'daki in-memory status ve test HTML locator hataları düzeltildi; gerçek eksik profil panel bağlantısı gizlendi. Genel `pint --dirty` eski route biçim uyarılarını da gösterdi; kapsam dışı route dosyaları topluca formatlanmadı, yeni PHP parçaları dar kontrolden geçti. Geçici dil üretiminin EOF farkı test sonunda geri döndü.

Genel proje suite/build tekrarlanmadı. Ana DB'de gerçek kullanıcı hesabına geçilmedi; gerçek e-posta/push/Cloudflare isteği veya deploy yapılmadı. Fiziksel Safari/iOS ve gerçek production oturum deposu kanıtı değildir. APP_VERSION ve kök PWA worker **1.1.42** birlikte artırıldı; yerel `.env` yalnız sürüm için güncellendi.

## 5. Değişen dosyalar

- `app/Actions/Auth/{ImpersonateUser,LogoutAction}.php`: oturum değişimi, dönüş, audit ve çıkış temizliği.
- `app/Http/Controllers/Frontend/ImpersonationController.php`, `app/Http/Middleware/EnsureUserImpersonation.php`: POST işlemleri ve her istekte kapsam kontrolü.
- `bootstrap/app.php`, `app/Providers/AppServiceProvider.php`, `routes/{backend,frontend}.php`: web/Livewire middleware ve rotalar.
- `app/Http/Controllers/Backend/UsersController.php`, `app/Livewire/Users/UserTable.php`: başlık ve düğme uygunluğu.
- `resources/views/components/admin/table-detail.blade.php`, `resources/views/livewire/backend/users/server-table.blade.php`: ortak isteğe bağlı footer ve geçiş formu.
- `resources/views/frontend/layouts/section/{header-auth,notification-script}.blade.php`, `resources/views/frontend/account/tab.blade.php`, `resources/views/frontend/notifications/index.blade.php`: dönüş ve geçici hesap cihaz/panel davranışı.
- `resources/views/backend/roles/form.blade.php`, `lang/{tr,nl,en,fr}/impersonation.php`: yetki adı ve dört dil metinleri.
- `database/migrations/2026_10_30_100000_grant_user_impersonation_permission.php`: tek onaylı izin migration'ı.
- `public/backend/assets/user-impersonation.js`, `public/assets/app.css`, `public/pwa-sw.js`: onay, dönüş düğmesi ve sürüm.
- `tests/Feature/UserImpersonationTest.php`, `tests/Frontend/UserImpersonationConfirmTest.cjs`: hata, yetki, oturum ve onay testleri.
- Bu rapor, AGENTS.md, README.md, Codex/index.html ve 16 standartları: kalıcı özellik/kabul kaydı.
