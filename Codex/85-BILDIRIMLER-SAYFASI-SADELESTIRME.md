# 85 — Bildirimler sayfasını sadeleştirme

## İlk revizyon — 1.1.38

26 Ekim 2026. Kullanıcı, uzun izin alanının tekrar tekrar gösterilmesini, karmaşık anlatımı ve ön yüz standartlarıyla uyuşmayan görünümü düzeltmemizi istedi.

- Mevcut ön yüz layout, Bootstrap, ikonlar, font ve tema rengi korunur. Bildirim sayfasının zemini beyazdır.
- Başlık altında kısa açıklama ve çalışma takibindeki görsel dile uygun outline **Hesabıma dön** düğmesi bulunur.
- Üstte tek, kısa **Bu cihazdaki bildirimler** durumu vardır. Açık/kapalı/kontrol/hata bilgisi yalnız renkle anlatılmaz.
- **Bildirim ayarları** açılır bölümündedir. Önceden onay veren kullanıcının aboneliği sessizce doğrulanırken uzun form açılmaz. SDK ve sunucu kaydı doğrulandığında onay formu gizlenir, bölüm kapanır.
- Yeni kullanıcıda veya izin/bağlantı hatasında ayarlar açılır. Açık onay kutusu korunur; etiket kısaltılmıştır. İzin kendiliğinden istenmez. İlk düğme hizmeti hazırlar; gerekiyorsa ikinci kullanıcı tıklaması tarayıcı iznini açar.
- Ayrıntılı açıklama **Hangi bildirimleri alacağım?** içindedir; içerik hâlâ mevcut Sayfalar bölümünden gelir. Kayıtlı içerik değiştirilmez, sağlayıcı adı sunumda nötr kalır.
- Son 10 kişisel bildirim ana içeriktir; cihazlar masaüstünde yanında, mobilde altında bulunur. İki kısa bağlantıyla ilgili bölüme doğrudan gidilir.
- Kullanılan cihaz gerçek SDK abonelik kimliğiyle işaretlenir. Tarihler mevcut tarih yardımcısıyla seçili dil ve site saat diliminde ay adı/saat olarak gösterilir.
- Her cihazda ayrı, ikonlu kapatma düğmesi vardır. Diğer cihazı kapatmak mevcut cihazı etkilemez. Kapatma onayı bekleyen kayıtların yeniden deneme işlemi korunur.
- Yeni abonelik kaydedilince cihaz bölümü aynı özel sayfanın HTML yanıtından yenilenir. Yeniden etkinleştirilen bekleyen kayıt da yenilenir; eski uyarı/sayaç kalmaz. Bu yenileme başarısızsa bildirim izni yanlışlıkla kapalı gösterilmez; yeniden kontrol açıklaması verilir.

## Değişen dosyalar

- `resources/views/frontend/notifications/index.blade.php`: durum, açılır ayarlar, önce mesajlar, cihaz listesi, bağlantılar ve tarih sunumu.
- `resources/views/frontend/layouts/section/notification-script.blade.php`: özel cihaz-listesi yenileme URL’si ve çevrili hata mesajı.
- `public/assets/app.css`: yalnız bildirim sayfasının eski stilleri yenilendi; beyaz zemin, outline düğmeler, mobil yerleşim ve klavye odağı.
- `public/assets/web-push.js`: doğrulanmış duruma göre form görünürlüğü, kullanılan cihaz işareti, hedefli cihaz yenileme ve yenilenen formların kapatma olayı.
- `lang/{tr,nl,en,fr}/notifications.php`: kısa arayüz metinleri ve yeni durum açıklamaları; dört dilde 104 eş kaynak anahtarı.
- `tests/Feature/WebNotificationsTest.php`, `tests/Frontend/WebPushTest.cjs`: ilgili HTTP/dil/sahiplik ve istemci durum kontrolleri.
- `public/pwa-sw.js`: cache sürümü **1.1.38**. Yerel, Git dışı `.env` dosyasında yalnız `APP_VERSION` aynı sürüme yükseltildi.
- `AGENTS.md`, `README.md`, `Codex/index.html` ve bu kayıt: kullanım ve kabul sınırı.

Controller, route, tablo şeması, izinler, sağlayıcı entegrasyon ayarları ve mevcut açıklama verileri değiştirilmedi.

## Dar doğrulama

1. `docker compose exec -T laravel.test php artisan test --compact tests/Feature/WebNotificationsTest.php tests/Feature/PwaReleaseTest.php`: **25 test / 224 doğrulama başarılı**. İlk koşudaki tek hata, artık kaldırılan `notifications-setup` sınıfını bekleyen eski testti; beklenti yeni kapsayıcıyla güncellendi. Son koşu temizdir.
2. Son durum işaretleri/açılmamış yapılandırma düzeni sonrası yalnız ilgili üç HTTP senaryosu: **3 test / 60 doğrulama başarılı**. Bu üçü yukarıdaki 25’in alt kümesidir.
3. `node --test tests/Frontend/WebPushTest.cjs`: **14 test başarılı**. Onaysız SDK çağrısı olmaması, açık izinle dönüş, sunucu kayıt hatası, kapalı/desteklenmeyen cihaz, iki tıklamalı izin, opt-out, cihaz yenileme hatası ve bekleyen eski kaydı yenileme dahil.
4. `node --check public/assets/web-push.js`: başarılı. Beş değişen PHP dosyasında `vendor/bin/pint --test`: başarılı. Dört dilde aynı 104 anahtar, tekrar yok; APP_VERSION/root worker eşleşir.
5. Browser plugin not available. Kurulu Playwright ve gerçek Chrome kullanıldı; yeni bağımlılık kurulmadı. Gerçek Laravel HTTP kernel’i ayrı `testing` bağlantısında dış transaction içinde çalıştırıldı; sonunda rollback yapıldı. SDK ve sağlayıcı HTTP yanıtları sahtedir.
6. Chrome: TR/1440, NL/768, TR/FR/EN/390; açık izinle yeniden yükleme, kapalı/yeni/engelli/desteksiz/hata, iki tıklamalı yeni izin, sayfa yenilenmeden cihaz ekleme, son 10 mesaj, kullanılan cihaz, bekleyen kapatma, boş listeler ve klavyeyle ayar açma kontrol edildi.
7. Yenilenen cihaz formunda kapatma, başka cihazı kapatırken mevcut cihazın korunması, mevcut cihazı kapatma, bekleyen kaydı yeniden etkinleştirme/sayaç/uyarı temizliği ve hesabıma dönüş kontrol edildi. Test köprüsü 302 yanıtını testing kernel’inde takip eder; ilk köprünün yönlendirmeyi gerçek yerel sunucuya bırakması nedeniyle oluşan giriş ekranı ürün kodu değişmeden düzeltildi.
8. Sayfa JS istisnası **0**, beklenmeyen HTTP 5xx **0**, kontrol edilen genişliklerde yatay taşma **0**. Kasıtlı context 503 hata senaryosu beklenen test yanıtıdır. iPhone kontrolü yalnız kullanıcı aracısı simülasyonudur.

Son tek aktif sayfa ziyaretinde konsol da okundu: bildirim koduna ait hata yok. İki uzak font/çerez asseti testte ağ kapatıldığı için yüklenmedi; service worker bloklandığından Playwright ve mevcut PWA kodu iki uyarı üretti. Bunlar test düzeneği sınırıdır; gerçek PWA veya dış asset kabulü iddiası yok. Bu tur ortak PWA/çerez/font kodu değiştirilmedi.

Geçici kanıtlar: `/tmp/eduan-notifications-browser-results.json`, `/tmp/eduan-notifications-pending-results.json`, `/tmp/eduan-notifications-final-desktop.png`, `/tmp/eduan-notifications-390-tr.png`. Geçici fixture/harness ürün koduna eklenmez.

## Kontrol etme

Giriş yaptıktan sonra `/account/notifications` adresini açın. Ayarlar kapalıyken son bildirimler ve cihazlar görünür; cihazları mobilde üstteki kısa bağlantıyla bulun. Bildirim izni doğrulanmış bir cihazda sayfayı yenilediğinizde onay formu yeniden açılmaz. İzin yoksa Ayarlar bölümünden açık onay ve izin düğmesiyle ilerlenir.

## Sınırlar

Ana yerel DB’ye kalıcı veri/ayar yazılmadı; migration, gerçek SMTP/push/API çağrısı veya canlı aktarım yapılmadı. Safari/telefon üzerinde gerçek işletim sistemi teslim testi bu değişikliğin kanıtı değildir. Önceden kullanıcı kabulüyle kapanmış gerçek push testi yeniden açılmadı. Başlangıçtaki `.DS_Store`, `Codex/Doge.html` silinmesi, `Codex/docs.html` ve yüklenmiş worker klasörü kapsam dışı bırakılır.


## İkinci revizyon — belirgin açma çağrısı / 1.1.39

Kullanıcının yeni ekran görüntüsünde açma işlemi ayarlar başlığı içinde kayboluyordu. Bu revizyonda kapalı bildirimler ve açık bildirimler için iki belirgin sunum vardır:

- Kapalıyken büyük **Bildirimlerini aç, haberdar kal** başlığı, kısa fayda listesi ve sağda **Bildirimleri aç** işlem alanı görünür.
- İşlem alanı varsayılan açık; **1. Bu cihazda bildirim almayı seç**, **2. Bildirimleri aç ve izin ver** adımlarıyla açık onay ve tarayıcı izni ayrılır.
- Ana düğme işlem alanının tamamını kaplar; numara ve ikonlarla ikincil bağlantılardan ayrılır. Dört dilde kontrol edilen ilk ekran içinde görünür.
- İzin penceresi sorunları ve ayrıntılı açıklama ikinci düzeydedir. İsteğe bağlılık/reklam gönderilmemesi/kapatabilme kısa metinle düğmenin yanında kalır.
- Masaüstünde fayda/açıklama solda, izin adımları sağdadır. Tablet ve mobilde başlık, işlem alanı, ardından fayda listesi gelir.
- İzin sunucudan doğrulanınca **Bildirimlerin açık** başlığına geçilir; başlangıç faydaları ve onay formu gizlenir, ayarlar kapanır. Tekrar ziyaret/yenileme bunu korur.
- İzin engelli veya hizmet hatalıysa işlem alanı geri açılır; önceki açık onay, iki tıklamalı hazırlama/izin akışı, iOS rehberi ve cihaz yönetimi kuralları korunur.
- Sayfalar içeriği, controller/Action, veri şeması ve gerçek bildirim ayarları değiştirilmedi.

### Değişen dosyalar ve doğrulama

Bu revizyonda `resources/views/frontend/notifications/index.blade.php`, `resources/views/frontend/layouts/section/notification-script.blade.php`, `public/assets/app.css`, `public/assets/web-push.js`, dört `lang/*/notifications.php`, iki ilgili test ve `public/pwa-sw.js` değişti. `.env` yalnız yerel APP_VERSION=1.1.39; root worker aynı sürümde. Bu belge, README, AGENTS ve rehber index kaydı güncellendi.

- İlgili `WebNotificationsTest` + `PwaReleaseTest`: **25 test / 236 doğrulama**, tek temiz koşu başarılı.
- `node --test tests/Frontend/WebPushTest.cjs`: **14 test başarılı**; açık/kapalı/hata durumlarında yeni başlık/işlem/fayda görünürlüğü dahil.
- Değişen beş PHP dosyasında Pint ve JS syntax başarılı. Dört dilde **111 eş kaynak anahtarı**; yinelenen anahtar yok. `git diff --check` temiz.
- Browser plugin not available; kurulu Playwright/Chrome ve önceki ayrı testing HTTP-kernel köprüsü kullanıldı. Dış transaction rollback; SDK ve servis HTTP yanıtları sahte, gerçek gönderim yok.
- TR1440/NL768/TR-FR-EN390: ana düğmenin ilk ekranda görünmesi, iki adım, onaysız SDK yüklenmemesi, hazırlama sonrası ayrı izin tıklaması, cihaz eklenmesi, açık görünüme geçiş ve yenilemeyle açık kalma doğrulandı.
- Ek olarak engelli izin/isteğe bağlı sorun açıklaması, context hata halinde tekrar işlem yapabilme ve iOS tarayıcı rehberi kontrol edildi. Sayfa JS istisnası0, beklenmeyen5xx0, kontrol edilen genişliklerde taşma0.
- Geçici kanıt: `/tmp/eduan-notifications-v2-browser-results.json`; masaüstü `/tmp/eduan-notifications-v2-1440-tr-off.png`, mobil `/tmp/eduan-notifications-v2-390-tr-off.png`; `-on.png` dosyaları izin sonrası görünümü gösterir.

Ana yerel DB/migration/gerçek API-push/SMTP/canlı aktarım yok. Fiziksel Safari/telefon izin veya teslim kabulü yapılmadı. Önceki konsol ağ/PWA test kısıtları geçerlidir; ortak PWA/asset akışı değişmedi.
