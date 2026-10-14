# Çalışma Takibi — Faz 7: yerel kabul ve bildirim tasarımı

14 Ekim 2026. Faz 6 commit: `8c17ffb5`. Kullanıcı Faz 7 testlerini ve Bildirimler sayfasının daha modern görünmesini istedi. Gerçek Chrome denemesi yerel App ID/API Key ve kullanıcının Chrome girişini bekliyor. Safari fiziksel cihaz kabulü kullanıcı kararıyla sonraya bırakıldı. Bu kayıt tam canlı/gerçek gönderim kabulü değildir.

## 1. Bildirim sayfasındaki değişiklik

- Mevcut Laravel/Blade, Bootstrap, Font Awesome, font ve site rengi korunur. Yeni görsel/framework bağımlılığı yoktur.
- `/account/notifications` başlığı, kısa açıklaması ve hesap dönüş bağlantısı ayrı bir üst alan oluşturur.
- Ana bölümde açıklama ve açık izin alanı masaüstünde yan yanadır; mobilde alt alta geçer. Uzun satırlar kısıtlanır, mavi bilgi ve büyük sarı uyarı alanları sadeleştirilir.
- Cihazlar ve son10 bildirim masaüstünde iki sütundadır; mobilde tek sütuna düşer. Boş kayıt alanları açıklamalı; mevcut, kapatma bekleyen ve gönderilmiş kayıtlar aynı kapsamı korur.
- İzin durumu gerçek SDK/kayıt sonucuna göre görünür. Hazırlama, kapalı, açık ve hata durumları ayrılır; başarılı kayıt sonrası izin butonu pasifleşir. Hata halinde yeniden deneme açık kalır; yapılandırması kapalı buton hata sonrası yanlışlıkla açılmaz.
- Onay, iki aşamalı izin verme, iPhone rehberi, cihaz bazlı kapatma, oturum çıkışı ve son10 sahiplik kuralları korunur.
- Klavye odak çizgileri, aria-live durum mesajı, semantik başlıklar, kırılabilir uzun metinler ve hareket azaltma kuralı eklendi.
- TR/NL/EN/FR aynı **83** kaynak anahtarına sahiptir; boş çeviri yoktur. Ana DB dil/çeviri satırları ve aktif dil kararları değişmedi.

## 2. Kullanıcı ekranındaki sağlayıcı adı

`NotificationExplanation::forDisplay()` eski dört dilli hazır sağlayıcı giriş cümlesini bu ekranda çıkarır; özel açıklamada kalan sağlayıcı adını dildeki genel “bildirim hizmeti” ifadesiyle gösterir. Açıklamanın kalan HTML’i ve içeriği korunur.

**Sayfalar veritabanındaki metinler yeniden yazılmadı.** Açıklama yine mevcut Sayfalar kaydından yönetilir. Bu sunum değişikliği uygulama/API ayarlarının, SDK sınıflarının veya teknik dosya yollarının adını değiştirmez. Kullanıcıya gösterilen sayfa gövdesinde sağlayıcı adı yoktur.

## 3. Kabul sırasında bulunan scheduler beklentisi

Genel koşudaki tek başarısızlık `PackageRemovalTest` idi: eski test dört uygulama görevi bekliyordu; Faz 6 iletişim görevi beşinci görev olmuştu. Yeni göreve `communications_dispatch` adı verildi. Test beş görevi, iletişimin dakika başı çalışmasını ve `withoutOverlapping` kuralını doğrular. Eski saatler/görevler veya bağımsız yedek scripti değiştirilmedi.

## 4. Worker kesintisi

`DeliverCommunication` tek deneme ve 60 saniye sınırını korur; `failOnTimeout=true` ve `failed()` callback eklendi. Laravel timeout/son başarısız deneme bildirirse yalnız `scheduled/sending` kayıtlar `failed / worker_interrupted` olarak işaretlenir. Ham exception/SMTP/secret metni saklanmaz. Önceden tamamlanmış kayıt değişmez; belirsiz e-posta otomatik tekrar gönderilmez.

Bu callback, işletim sistemi process’i aniden öldürdüğü anda çalışacağı garantisi değildir. Queue yeniden başladığında/son deneme işlemi gerçekleştiğinde framework callback’i uygulanabilir. Sağlayıcının gerçekten kabul edip etmediği belirsiz kalan gönderim manuel uzlaştırılmalıdır; fiziksel process öldürme veya gerçek SMTP/provider kesintisi bu tur denenmedi.

## 5. Testler

| Kontrol | Sonuç |
| --- | --- |
| Tek genel PHP koşusu | **715 test / 4360 assertion**, 714 başarılı; tek eski scheduler beklentisi başarısız |
| Düzenleme sonrası ilgili tekrar | **44 test / 263 assertion**, tamamı başarılı; scheduler, iletişim, bildirim ve PWA |
| Web push + PWA Node | **20 test**, tamamı başarılı |
| PHP syntax | **10 dosya**, başarılı |
| Pint | **9 dosya**, başarılı; mevcut bootstrap stili korunur |
| Dil grupları | TR/NL/EN/FR **83** eş anahtar, boş değer yok |
| Chrome taklit akış | NL1440/390, FR390 iPhone UA; JS0 / HTTP5xx0 / belge taşması0 |

Genel suite ikinci kez bütünüyle çalıştırılmadı. Sonradan eklenen worker kesintisi testiyle bu tur toplam 716 farklı PHP senaryosu sınandı; sonuçlar genel+dar koşuların birleşimidir, tek temiz genel koşu sonucu değildir.

İlk dar tekrar tarayıcı QA ile aynı testing DB’de eşzamanlı çalıştığında geçici permission fixture kilidi çakıştı. Yeni callback testinde de eski model snapshot’ı kullanılmıştı. Test snapshot’ı yenilendi; tarayıcı QA kapandıktan sonra tek başına çalıştırılan **44/263** temiz geçti. Bunlar nedeniyle ürün permission/transaction kuralları gevşetilmedi.

PHPUnit HTTP stray request engeli ve mail fake/array kullanıldı. Ana veritabanına test kaydı yazılmadı; testing fixture’ları transaction rollback ile geri alındı. Ücretli OpenAI, gerçek SMTP veya gerçek push gönderimi yapılmadı.

## 6. Tarayıcı kanıtı

Akış: Bildirimler → açık onay → hizmeti hazırla → izin ver → kayıt/durum → sessiz reload → seçilmiş cihazı kapat. Ayrıca panelde farklı sayfalardan alıcı seçme/kuyruk formu ve FR iPhone rehberi kontrol edildi.

Browser plugin bulunmadığı için mevcut Playwright + Google Chrome kullanıldı; yeni paket kurulmadı. OneSignal SDK ve server HTTP yanıtları taklit edildi. Tarayıcı taklidi gerçek native push aboneliği/cihaz teslimi değildir.

Mevcut sitenin halka açık rengi `#313079` ve `uploads/logo.png` yalnız testing rollback fixture’ına referans alındı. Ana renk/logo ayarları değişmedi. Son ekranlarda üst menü ve yeni izin butonu site rengiyle eşleşir. Taklit SDK’nın yeni sayfada kapalı durumla başlaması gerçek cihazın kayıt durumu kanıtı olarak kullanılmaz.

Kanıtlar:

- `/private/tmp/eduan-faz7-browser-result.json`
- `/private/tmp/eduan-faz7-notifications-desktop.png`
- `/private/tmp/eduan-faz7-notifications-mobile.png`
- `/private/tmp/eduan-faz7-composer-mobile.png`
- `/private/tmp/eduan-faz7-ios-guide.png`
- Genel JUnit: uygulama konteynerinde `/tmp/eduan-faz7-junit.xml`.

Gerçek Chrome açma için tarayıcı bağlantısı `Browser is not available: chrome` döndürdü. Native pencere kontrolüyle ayrı Chrome sekmesi `http://localhost/account/notifications` açıldı; giriş ekranına yönlendi. Beklenmeyen eklenti erişim isteği kabul edilmedi, ek pencereler kapatıldı; zorunlu olmayan çerezler reddedildi. Parola/API Key okunmadı veya girilmedi. Kullanıcı bu Chrome penceresinde kendi deneme hesabıyla giriş yapacağını belirtti.

## 7. Veri ve sürüm sınırı

Yeni migration yoktur. Onaylı Faz 6 migration’ları ana batch15/testing9 olarak korunur. Son ana SELECT: languages3, translations2557, exam_result_details149914, tests_result_details10695; çalışma hedef/manuel/not/revision ve iletişim tabloları0. Çalışma takibi enabled1/tarih2026-10-14 korunur. Bildirim enabled0 ve yerel App ID/key boş olduğundan gerçek gönderim henüz başlayamaz.

CSS/JS değişikliği nedeniyle yerel APP_VERSION ve kök PWA worker sürümü birlikte **1.1.27** yapıldı; worker URL/id/scope sözleşmesi korundu. `.env` Git’e dahil değildir. Canlı/sunucu/cron/servis/port/volume ayarı değiştirilmedi.

## 8. Gerçek Chrome kabulünde kalanlar

Kullanıcı localhost uygulamasını oluşturup Ayarlar → Bildirimler’de **Yerel test App ID** ve **Yerel test App API Key** alanlarını dolduracağını, bildirimleri etkinleştirip Chrome’da giriş yapacağını belirtti. Secret sohbete yazılmayacak.

Hazır olduğunda yalnız bu oturum hesabı için şu akış tamamlanır:

1. Bildirimler’de açık onay ve native Chrome izin verme.
2. Tek browser subscription’ın hem sağlayıcıda hem doğru yerel hesapta eşleşmesini doğrulama.
3. Bu hesaba tek test bildirimi; kabul ile gerçek cihaz gösterimini ayrı kaydetme.
4. Reload/yeniden girişte sessiz eşleşme; farklı hesaba yanlış hedefleme olmaması.
5. Bu cihazı kapatma; diğer cihazları koruma ve gerçek provider doğrulaması.

Bütün bekleyen kampanyalara genel `communications:dispatch` veya genel queue worker çalıştırmak bu testin parçası değildir; yalnız belirlenen deneme hesabı ve mesajı işlenir. Gerçek SMTP gönderimi ve Safari cihaz kabulü ayrıca hazırlanacaktır. Siteyayını yapılmayacak.

Yerel uygulama/origin ve ayrı worker kuralları [resmî özel kod kurulumu](https://documentation.onesignal.com/docs/en/web-push-custom-code-setup), izin/SDK yöntemleri [SDK başvurusu](https://documentation.onesignal.com/docs/en/web-sdk-reference) üzerinden kontrol edildi.

## 9. Kapsam dışı bulgu

Salt okunur `artisan tinker` kontrolü, kurulu `pharaonic/laravel-settings` paketinin constructor’ında `$model` ve `$data` implicit nullable parametreleri için PHP8.5 deprecated uyarısı verdi. Vendor dosyası/bağımlılık değiştirilmedi ve uyarı genel olarak bastırılmadı. Bu, yeni bildirim tasarımından bağımsız mevcut paket uyumluluk bulgusudur; canlıya geçiş öncesi ayrıca ele alınmalıdır.

## 10. Durum ve inceleme

**Faz 7 yerel kod/test hazırlığı tamamlandı; gerçek Chrome gönderim kabulü bekliyor.** Safari sonraya bırakıldı. Bu aşama tam faz/yayın kabulü olarak işaretlenmez.

Yerelde `/account/notifications` açıp masaüstü/mobil görünümü inceleyebilirsiniz. Açıklama Sayfalar’dan gelir; bildirim kapalı/ayarlar boşsa pasif buton ve bilgi normaldir. Ayarlar hazır olduğunda Chrome sekmesinde kendi hesabınıza giriş yapıp “hazır” bilgisi verilmelidir.

## 11. Değişen dosyalar

- `AGENTS.md`
- `Codex/76-CALISMA-TAKIBI-FAZ7-KABUL-VE-BILDIRIM-TASARIMI.md`
- `Codex/index.html`
- `README.md`
- `app/Helpers/NotificationExplanation.php`
- `app/Jobs/DeliverCommunication.php`
- `bootstrap/app.php`
- `lang/en/notifications.php`
- `lang/fr/notifications.php`
- `lang/nl/notifications.php`
- `lang/tr/notifications.php`
- `public/assets/app.css`
- `public/assets/web-push.js`
- `public/pwa-sw.js`
- `resources/views/frontend/layouts/section/notification-script.blade.php`
- `resources/views/frontend/notifications/index.blade.php`
- `tests/Feature/CommunicationsTest.php`
- `tests/Feature/PackageRemovalTest.php`
- `tests/Feature/WebNotificationsTest.php`
- `tests/Frontend/WebPushTest.cjs`

Yerel `.env` APP_VERSION1.1.27 için güncellendi; Git’e dahil edilmez.
