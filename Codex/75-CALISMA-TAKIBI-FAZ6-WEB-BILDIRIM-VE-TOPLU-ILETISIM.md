# Çalışma Takibi — Faz 6: web bildirimleri ve toplu iletişim

14 Ekim 2026. Önceki yerel aktivasyon/test commit’i `65deae7c`. Bu faz web bildirim izinleri, cihaz yönetimi, bildirim geçmişi, hedef/çalışma değişiklik bildirimi ve seçilmiş alıcılara kuyruklu e-posta/push kapsamındadır.

## 1. Kullanıcı ekranı

- Üye ön yüzünde soldaki bildirim ikonu WhatsApp’ın üstündedir. WhatsApp kapalıysa da bildirim ikonu görünür. Misafire gösterilmez.
- `/account/notifications` ayrı sayfadır. Açıklama mevcut Sayfalar bölümündeki `notification-permission` kaydından gelir; TR/NL/EN/FR içerik hazırdır.
- OneSignal hazır zili, otomatik izin penceresi ve hoş geldiniz bildirimi kapalıdır.
- Açıklama ve gönüllü onay kutusundan sonra kullanıcı hizmeti hazırlar; ikinci butona basması tarayıcı iznini doğrudan kullanıcı hareketiyle açar. Bu iki adım Safari kullanıcı hareketi koşulunu korur.
- İlk izin öncesi OneSignal CDN/SDK yüklenmez. Daha önce onay veren hesaba ait tarayıcıda SDK üye ön yüzünde sessiz eşleşir; izin istemez.
- Chrome/masaüstü Safari için tarayıcı adımları; iPhone/iPad tarayıcı sekmesinde Ana Ekrana Ekle → simgeden aç → izin ver yönlendirmesi vardır. iOS tarayıcı sekmesinde izin butonu kapalıdır.
- Her tarayıcı/cihaz ayrı satırdır. Yalnız seçilen cihaz kapatılır; başka cihazlar korunur. Sağlayıcı kapatma başarısızsa yerel hedefleme hemen durur, bekleyen satır ve yeniden deneme butonu kalır.
- Hesap menüsünde aktif abonelik varsa Bildirimler bağlantısı görünür. Bildirim sayfasında yalnız hesabın son 10 kabul edilmiş push mesajı listelenir.
- `sent` burada sağlayıcı/SMTP kabulünü ifade eder; gerçek cihazda gösterildiği veya posta kutusuna ulaştığı iddiası değildir.

## 2. OneSignal ayarları ve ortam ayrımı

Ayarlar → Bildirimler yalnız yönetici tipine açıktır. Bildirim aktif/pasif, canlı App ID/origin/Safari ID, canlı App API Key, yerel App ID ve yerel App API Key alanları vardır. API anahtarları şifreli kaydedilir; formda boş parola alanı olarak gelir. Boş bırakmak mevcut anahtarı korur. Anahtarlar JavaScript/HTML’e gönderilmez.

Kullanıcının verdiği App ID `ff8879de-3bdc-4aae-b07f-de6522f1be82` ve Safari ID yalnız canlı `https://hypotenuse.be` içindir. Production dışındaki ortamlar ayrı yerel App ID/key kullanır; canlıya otomatik geri dönüş yoktur. Mevcut ana yerelde özellik **kapalı**, yerel App ID/key **boş** kalmıştır.

SDK kullanıcı kimliği e-posta yerine sunucu HMAC’inden üretilen opak kimliktir. Abonelik kaydı ve gönderimi sırasında sağlayıcıdan kullanıcı/uygulama/aktif abonelik sahipliği yeniden doğrulanır. Paylaşılan tarayıcı başka hesaba ancak açık onay ve doğrulanmış eşleşmeyle geçebilir. Çıkışta ilgili oturum cihazı yerelde kapatılır; diğer cihazlar etkilenmez.

Kök PWA worker `/pwa-sw.js` korunmuştur. OneSignal dosyası `public/push/onesignal/OneSignalSDKWorker.js`, scope `/push/onesignal/` olur. Kullanıcının yüklediği özgün `OneSignalSDK-v16-ServiceWorker/` klasörü değiştirilmez/commit’e alınmaz. CDN SDK v16 kullanılır; native uygulama entegrasyonu yoktur.

## 3. Panel iletişim ekranı

`/admin/communications`: öğretmen/yönetici, aktif alıcıları Bootstrap Table’dan tek tek seçer. Arama/sıralama/15 kayıt sayfalama vardır; sayfalar arasında seçim korunur. Başlık, düz metin, dil ve e-posta/push kanalı girilir; onaydan sonra kuyruk kaydı oluşturulur.

- `dashboard:access`, `users:view`, `communications:view` ve öğretmen/yönetici tipi gerekir. Gönderim ayrıca `communications:send` ister.
- Öğretmen yalnız güncel `teacher_student` öğrencilerini seçebilir; yönetici aktif kullanıcıları seçebilir. Sahte alıcı isteği reddedilir.
- Salt görüntüleme izni gönderim formunu açmaz. İş sırasında yetkiler ve öğrenci ilişkisi tekrar kontrol edilir.
- En fazla 500 alıcı, başlık 160, metin 10.000 karakter. Boş içerik ve tekrarlanan alıcı reddedilir.
- Yalnız `{{name}}` ve `{{surname}}` değişkenleri desteklenir. HTML çalıştırılmaz; e-posta ve ekranda kaçış uygulanır.
- Aynı UUID + aynı içerik tekrar kayıt oluşturmaz; değişmiş içerikle aynı UUID 409’dur.
- Gönderim işlemi hemen HTTP isteği yapmaz. Kampanya ve kişi bazındaki teslim kayıtları transaction içinde tutulur.
- Son 20 teslim kaydı görünür; öğretmen kendi gönderimlerini, yönetici tümünü görür.

## 4. Hedef/çalışma bildirimleri

Yetkili personel başka kullanıcının hedefini veya manuel çalışmasını oluşturduğunda/değiştirdiğinde/kaldırdığında mevcut revision ile aynı transaction’da push outbox kaydı oluşur. Aynı revision çoğalmaz. Transaction geri alınırsa bildirim kaydı da geri alınır.

Öğrencinin kendi işlemleri, değişmeyen tekrar kaydı, öğretmen notu ve otomatik soru cevabı bildirim üretmez. Özellik kapalıysa outbox oluşmaz. Bildirimler çalışma sayımını veya test/sınav kurallarını değiştirmez.

## 5. Kuyruk ve çalışma düzeni

`communications:dispatch` uygulama scheduler’ında dakikada bir çalışır. Asenkron queue gerektirir; `sync` bağlantısını reddeder. Kanal başına dakikada en fazla beş bekleyen kayıt job’a alınır. Tekrar scheduler çağrısı aynı dakika ikinci paketi oluşturmaz.

Worker tarafında da kanal başına beş/60 saniye sınırlaması ve atomik cache kilidi vardır; gecikmiş job’ların topluca patlaması engellenir. Limitteki kayıt sonraki dakikaya ertelenir. Job tek denemelidir; uygulama otomatik sağlayıcı yeniden denemesi yapmaz. Push’ta sağlayıcı idempotency UUID’si gönderilir. Aynı job tekrar çağrılırsa tamamlanmış kayıt yeniden gönderilmez.

Push öncesi yerel aktif aboneliklerle sağlayıcıdaki güncel sahibi/aktif abonelikler kesiştirilir. Abonelik yoksa atlanır; hata kodları temizlenir, ham sağlayıcı/SMTP mesajı veya anahtar saklanmaz. SMTP kabulü, spam klasörüne düşmeme garantisi sağlamaz.

Yayın aşamasında mevcut scheduler/queue worker çalışır olmalıdır; PHP 8.5/Imagick komut düzenlemesi önceki yayın listesinde kalır. Bu faz canlı cron veya sunucu ayarı değiştirmemiştir.

**İşletim sınırı:** process aniden öldürülürse `sending/scheduled` kayıtlarının durumuyla gerçek dış servis kabulü ayrıca uzlaştırılmalıdır. Belirsiz e-postayı otomatik yeniden göndermek çift teslim riski taşıdığından bu sürüm böyle bir yeniden gönderim yapmaz. Sağlayıcı kapatma hatası kullanıcı tarafından tekrar denenir. Gerçek worker/provider hata senaryoları yayın kabulünde ayrıca sınanmalıdır.

## 6. Onaylı migration ve ana veri koruması

Kullanıcının açık onayıyla yalnız şu iki migration ana `hypotenuse` (batch 15) ve ayrı `testing` (batch 9) veritabanında uygulandı:

1. `2026_10_14_140000_create_web_communications`: `web_push_subscriptions`, `communication_batches`, `communication_deliveries`.
2. `2026_10_14_140100_prepare_web_communications`: iki iletişim izni, mevcut öğretmen/yönetici tipindeki rollerin izinleri, eksik dört dilli açıklama Page’i ve yalnız eksik herkese açık canlı ayarlar. Bildirim kapalı başlar; secret eklemez; mevcut değerleri ezmez.

Ana DB salt SELECT sonrası: `languages=3`, `translations=2557`, `exam_result_details=149914`, `tests_result_details=10695`; önceki sayılar aynıdır. Hedef/manuel/not/revision ve üç yeni iletişim tablosu sıfırdır. Çalışma takibi önceki kullanıcı kararıyla `enabled=1`, `started_on=2026-10-14` olarak korunur. Bildirim `enabled=0`, yerel App/key yok, açıklama Page sayısı 1’dir. Dil aktivasyonu veya mevcut AI cevapları değişmedi.

Testing fixture’ları transaction rollback ile geri alındı; üç yeni tabloda kalıcı test verisi yoktur. `migrate:fresh`, sıfırlama, volume/servis/port değişikliği veya canlı işlem yapılmadı.

## 7. Test sonucu ve kanıt sınırı

- Faz 6’nın 33 PHP senaryosu: sahiplik/izin/ortam, onay ve UUID, kayıt/kapatma/çıkış, son10 gizliliği, anahtar şifreleme/boş koruma, öğretmen alıcı kapsamı, kişiselleştirme, kuyruk/limit/kilit, tekrar job, sağlayıcı hata/yanlış sahiplik ve atomik çalışma bildirimi.
- Birleşik Faz1–6 + süreli sınav + e-posta güvenliği + sayfa render + PWA: **328 farklı senaryo**. İlk koşu 327 başarılı / 1 hata, 1498 assertion; route dosyasının biçim düzenlemesiyle eşzamanlı okunması geçici ParseError oluşturdu. Yazım tamamlandıktan sonra yalnız ilgili PWA senaryosu **1/5** geçti. Toplam 328 farklı senaryo bu iki koşuda başarılı; tek temiz genel suite sonucu olarak sunulmaz. Son route dosyaları syntax kontrolünden geçti.
- 39 PHP dosyası syntax; 36 dosyada Pint kontrolü başarılı. Eski route/bootstrap dosyalarının önceki yazım stili korundu; yalnız yeni route/scheduler satırları eklendi.
- TR/NL/EN/FR aynı 68 anahtar, boş değer yok. İki yeni JS syntax başarılı.
- Yeni web push/seçim ve mevcut PWA güvenliği birlikte **18 Node testi** başarılı.
- Browser plugin bulunmadığı için mevcut Playwright + Chrome kullanıldı; yeni bağımlılık kurulmadı.
- Gerçek Laravel/Livewire/Bootstrap görünümü, testing rollback ve taklit OneSignal SDK ile Chrome NL 1440/390: onay öncesi SDK yok, iki aşamalı izin, tek prompt, kayıt, sessiz reload, seçilmiş cihazı kapatma, sayfalar arası alıcı seçimi ve kuyruk formu başarılı. JS error=0, HTTP5xx=0, belge yatay taşması=0.
- FR 390 iPhone UA: ana ekran rehberi görünür ve tarayıcı sekmesindeki izin butonu kapalı. Bu gerçek Safari/iPhone cihaz testi değildir.
- Geçici HTTP köprüsünde native POST’un 302 yönlendirmesi gerçek localhost’a gidiyordu; QA köprüsünde yönlendirme sonucu ayrı GET ile izlendi. Uygulama yönlendirme/oturum kuralı değiştirilmedi; gerçek auth/CSRF kapsamı PHP testlerindedir.

Kanıtlar `/private/tmp/eduan-faz6-browser-result.json`, `/private/tmp/eduan-faz6-notifications-desktop.png`, `/private/tmp/eduan-faz6-notifications-mobile.png`, `/private/tmp/eduan-faz6-composer-mobile.png`, `/private/tmp/eduan-faz6-ios-guide.png`.

**Gerçek OneSignal, OpenAI, SMTP isteği yapılmadı.** Gerçek tarayıcı izin/abonelik eşleşmesi ve fiziksel cihaz teslimi henüz kabul edilmedi. Yeni otomatik e-posta hatırlatma/haftalık rapor, AI tavsiye veya WhatsApp entegrasyonu eklenmedi.

## 8. Elle nasıl incelenir?

1. Yöneticiyle Ayarlar → Bildirimler: canlı App/origin/Safari bilgilerini, boş secret girişlerini ve kapalı durumu kontrol edin.
2. Sayfalar → Bildirimler: dört dilde açıklamayı mevcut editörle düzenleyebilirsiniz. Bu metin hizmet açıklaması taslağıdır; hukuki uygunluk onayı verilmiş sayılmaz.
3. Üye olarak soldaki zil → `/account/notifications`: açıklama, cihazlar, son10 ve tarayıcı/iOS rehberi. Yerel uygulama/key yokken “ayarlar tamamlanmamış” normaldir.
4. Panel → İletişim → `/admin/communications`: öğretmende yalnız kendi öğrencileri; sayfalar arasında seçim, değişkenler, kanal, onay ve kayıt durumları. Gerçek SMTP/push hazırlanmadan Gönder’e basmayın.
5. Yerel gerçek Chrome/Safari denemesi için ayrı localhost OneSignal uygulaması ve App API Key gerekir. Canlı App ID yerelde kullanılmaz. Gerçek deneme ve özellik aktivasyonu ayrıca onaylanmalıdır.

## 9. Sıradaki kapı

Faz 6 kodu ve yerel taklit/regresyon testleri tamamlandı. **Faz 7** birleşik son kabul, gerçek OneSignal Chrome/Safari cihaz akışı, SMTP/worker hata akışları ve yayın listesi olacaktır. API anahtarının hazırlanması, canlı/yerel bildirim aktivasyonu ve gerçek dış servis denemeleri bu fazda yapılmadı. Yayın/deploy veya Git push yapılmadı.

## 10. Değişen dosyalar

- `.env.example`
- `AGENTS.md`
- `Codex/75-CALISMA-TAKIBI-FAZ6-WEB-BILDIRIM-VE-TOPLU-ILETISIM.md`
- `Codex/index.html`
- `README.md`
- `app/Actions/Auth/LogoutAction.php`
- `app/Actions/Communications/AuthorizeCommunication.php`
- `app/Actions/Communications/CreateCommunicationBatch.php`
- `app/Actions/Communications/RecordStudyNotification.php`
- `app/Actions/Notifications/PrepareNotificationSettings.php`
- `app/Actions/Notifications/RegisterWebSubscription.php`
- `app/Actions/Notifications/RevokeWebSubscription.php`
- `app/Actions/Settings/SettingSaveAction.php`
- `app/Actions/Study/RemoveStudyActivity.php`
- `app/Actions/Study/SaveStudyActivity.php`
- `app/Actions/Study/SaveStudyTarget.php`
- `app/Console/Commands/DispatchCommunications.php`
- `app/Enums/PageMenuEnum.php`
- `app/Enums/SettingsTabsEnum.php`
- `app/Exceptions/OneSignalFailure.php`
- `app/Helpers/OneSignalClient.php`
- `app/Helpers/WebPushSettings.php`
- `app/Http/Controllers/Backend/CommunicationController.php`
- `app/Http/Controllers/Backend/SettingsController.php`
- `app/Http/Controllers/Frontend/NotificationController.php`
- `app/Jobs/DeliverCommunication.php`
- `app/Livewire/Backend/Communications/CommunicationForm.php`
- `app/Mail/CommunicationMail.php`
- `app/Models/CommunicationBatch.php`
- `app/Models/CommunicationDelivery.php`
- `app/Models/WebPushSubscription.php`
- `bootstrap/app.php`
- `config/services.php`
- `database/migrations/2026_10_14_140000_create_web_communications.php`
- `database/migrations/2026_10_14_140100_prepare_web_communications.php`
- `lang/en/notifications.php`
- `lang/fr/notifications.php`
- `lang/nl/notifications.php`
- `lang/tr/notifications.php`
- `public/assets/app.css`
- `public/assets/web-push.js`
- `public/backend/assets/communications.js`
- `public/push/onesignal/OneSignalSDKWorker.js`
- `public/pwa-sw.js`
- `resources/views/backend/communications/index.blade.php`
- `resources/views/backend/layouts/sidebar-menu.blade.php`
- `resources/views/backend/roles/form.blade.php`
- `resources/views/backend/settings/tabs/notifications.blade.php`
- `resources/views/email/communication.blade.php`
- `resources/views/frontend/account/tab.blade.php`
- `resources/views/frontend/layouts/app.blade.php`
- `resources/views/frontend/layouts/section/floating-button.blade.php`
- `resources/views/frontend/layouts/section/notification-script.blade.php`
- `resources/views/frontend/notifications/index.blade.php`
- `resources/views/livewire/backend/communications/communication-form.blade.php`
- `routes/backend.php`
- `routes/frontend.php`
- `tests/Concerns/UsesWebCommunications.php`
- `tests/Feature/CommunicationsTest.php`
- `tests/Feature/WebNotificationsTest.php`
- `tests/Frontend/WebPushTest.cjs`

Yerel `.env` yalnız APP_VERSION1.1.26 için güncellendi; Git’e dahil değildir.
