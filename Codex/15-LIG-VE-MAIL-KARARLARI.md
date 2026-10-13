# Lig ve mail kararları, düzeltme ve dar test kanıtı

5 Ekim 2026. Bu kayıt `13` ve `14` dosyalarındaki son deneme/mail kararı bekleyen satırların yerine geçer. Kullanıcının en son açıklaması esas alınır. Composer/framework, migration, ana DB ve canlı ortam değiştirilmedi.

## 1. Kesin lig sözleşmesi

Kullanıcı önceki **yalnız son tamamlanan deneme** isteğini açıkça geri çekti. Son deneme seçme/yarım testleri dışlama planı iptal edildi.

| Kullanıcıdaki alan | Kaynak modeli | Lige giren veri |
|---|---|---|
| Süresiz test | Exam / ExamResultDetail | Tamamlanmış veya yarım bütün denemelerin kaydedilmiş cevapları |
| Süreli sınav | Test / TestsResultDetail | Yalnız tamamlanmış denemelerin kaydedilmiş cevapları |

- Aynı test üç kez çözülürse üç denemenin ilgili cevapları toplanır. Aynı sınavın bütün tamamlanmış tekrarları da toplanır.
- Yarım süresiz testte tek kaydedilmiş cevap bile lige katkı yapar. Sınavın henüz tamamlanmamış denemesi katkı yapmaz.
- Mevcut dönem filtresi cevap detayının `updated_at` aralığıdır; değiştirilmedi. Sınav tamamlanma zamanı üzerinden yeni dönem sözleşmesi uydurulmadı.
- Mevcut sıralama yüksek puan → fazla doğru → kısa toplam süredir. Üçü de eşitse `RANK()` ortak sıra verir; ardından sıra atlar. Örnek 1, 2, 2, 4.
- `CalculateLeagueResult` zaten bu deneme toplama/filtre sözleşmesini uyguluyordu; bu turda hesaplama kodu değiştirilmedi. Önceki negatif puan/boş sonuç/lig grubu düzeltmeleri korunur.
- Sonuç geçmişi silinmedi veya birleştirilmedi. Mevcut tablo/model adları değiştirilmedi.

Üç yeni test bu sözleşmeyi korur: yarım/tekrar süresiz test ve dönem dışı cevap; tamamlanmış sınav tekrarlarının toplanması ve yarımın dışlanması; eşit puanda doğru/süre ve ortak sıra. Dönem dışı fixture timestamps guarded olduğu için testte `forceFill` kullanılır; bu uygulama veri yazma standardı olarak örnek alınmaz.

## 2. Mail sözleşmesi

Kullanıcı yeni ve ayrı gönderim iznini seçti. Mevcut proje adlandırmasına göre **`users:send-email`** tanımlandı; `newsletter:send` kullanılmıyor.

- Tekil kullanıcı maili ve istatistiklerden toplu mail için yeni izin + dashboard gerekir. Toplu modal ayrıca mevcut okuma bağlamını korur: users:view veya stats:view.
- Tekil kullanıcı ekranında mevcut users:view ve öğretmenin kendi öğrencisi kuralı sürer. İlk açılış ve send işlemi ayrı denetlenir; açık formda izin veya ilişki iptal edilirse gönderim reddedilir.
- Kullanıcı **girilen adrese gönderilsin, adres DB ile eşleştirilmesin** dedi. E-posta adresi öğrenciye ait mi diye sorgu yapılmaz. Bu nedenle öğretmen kontrolü hedef öğrenci kaydı üzerindedir; gerçek mailbox sahibinin aynı kişi olduğu iddia edilmez.
- E-posta biçim kontrolü kalır. Tekil formun mevcut RFC+DNS kuralı korunur; toplu satırda RFC kontrolü vardır. DB alıcı doğrulaması yapılmaması, bütün biçim kontrollerinin kaldırılması değildir.
- Kuyruğa ekleme hataları Laravel loguna kaydedilir; kullanıcı ayrı hata ekranı veya raporu istemedi. Mevcut başarı/redirect/close akışı korunur. Bu mesajlar gerçek teslimat kanıtı değildir.

## 3. Uygulanan sunucu sınırı

`app/Actions/Users/AuthorizeUserMail.php` ortak gönderim kapısıdır. Tekil mail mevcut `AuthorizeStudentAccess` üzerinden öğrenci kapsamını yeniden denetler. Toplu gönderimde bütün satırların `user_id` değerleri, öğretmenin students ilişkisiyle **tek toplu sorguda** doğrulanır. Öğretmen olmayan aktöre öğretmen filtresi uygulanmaz; yeni gönderim ve okuma izinleri yine zorunludur.

Toplu sıra:

1. Mount sırasında gönderim kapısı denetlenir; array/stdClass satırlar array'e normalize edilir. Mount'ta e-posta tekilleştirilmez: aksi halde aynı adresli yabancı öğrenci satırı kaybolup denetimden kaçabilir.
2. Send sırasında izin yeniden kontrol edilir; bütün satırlarda açık pozitif tamsayı user_id, adres/metin/yüzde biçimi doğrulanır. Email veya ad üzerinden öğrenci kimliği türetilmez.
3. Öğretmen için bütün kimlikler tek sorguda denetlenir. Bir yabancı öğrenci varsa **hiçbir mail kuyruğa eklenmeden** 403 döner. Boş adresli satırın kimliği de bu aşamadan kaçmaz.
4. Denetimden sonra adresler mevcut davranış gibi tekilleştirilir; boş adresler atlanır. Bir null adres bütün grubu durdurmaz.
5. Toplu mailable için yalnız ad/soyad/adres alanlı, persist edilmeyen User nesnesi şablon bağlamıdır. Alıcı başına `User::where('email', ...)` sorgusu kaldırıldı; DB'de olmayan girilmiş adrese gönderim mümkündür.
6. Kuyruğa eklenemeyen satır loglanır, kalan satırların işlenmesi sürer. Tekil kuyruk hatası da loglanır.

Test/sınav istatistik SELECT'lerine `users.id as user_id` eklendi; konu sonuçları zaten user_id taşıyordu. `showSendEmails` okuma/gönderim kapılarını denetler. Mail sekmesi ve üç toplu gönderim düğmesi `@can('users:send-email')` ile gösterilir; sunucu kontrolleri yine zorunludur. Modal sayacı, boş adresleri çıkartıp adresleri tekilleştirir; orijinal yetkilendirilecek satırları silmez.

`SendEmailToUserMail` içindeki tanımsız `$contact` yerine açık recipient parametresi kullanılır; yeni parametre isteğe bağlıdır, mevcut constructor çağrı biçimi korunur. Gerçek alıcı `Mail::to` değeridir. Bulk mailin şablonu DB kullanıcı yüklemeden render edilebilir.

## 4. Hata kaydı ve ortam sınırı

Kuyruğa ekleme catch'i `Throwable` kapsar. Log mesajı `User mail enqueue failed`; context actor_id, user_id, recipient ve exception sınıfıdır. Mail içeriği, konu, token veya SMTP parolası loglanmaz. Mevcut Laravel log channel'ı kullanılır; varsayılan stack/single `storage/logs/laravel.log` olabilir, gerçek ortam `LOG_CHANNEL/LOG_STACK` değerine bağlıdır.

Bu kayıt **enqueue hatası** içindir. Kuyruğa girdikten sonra worker/SMTP teslimat hatası ayrı queue/failed_jobs takibidir; gerçek worker veya teslimat bu testlerle kanıtlanmadı. Retry, kalıcı hata tablosu veya yeni cron eklenmedi.

Yeni izin `PermissionSeeder` kaynağına eklendi. Ana yerel/canlı DB'de permission yaratılmadı veya rol ataması yapılmadı. Mevcut DB'lerde izni oluşturup ilgili rollere vermeden mail ekranları kapalıdır. **Sadece yeni izin için hedefli aktivasyon** planlanmalı; tüm PermissionSeeder'ı canlıda körlemesine çalıştırma, çünkü seed mevcut rolleri de değiştirir. Aktivasyon SQL/migration incelemesiyle veya ayrıca onaylı dar işlemle ele alınır.

## 5. Kanıt ve inceleme sonucu

Mail düzeltmesinden önce `panel_read_access_does_not_authorize_single_or_bulk_mail` testi beklenen 403 yerine 200 aldı. Düzeltme sonrası yetkisiz mount reddedilir; form açıkken iptal edilen izin/öğrenci ilişkisi de send çağrısında reddedilir. Mail fake nedeniyle gerçek gönderim yapılmadı.

Bağımsız incelemede iki somut regresyon doğrulanıp düzeltildi: null adres bütün grubu durdurmamalı; yinelenen adres sayacı gönderim adedini fazla göstermemeli. Karma kendi/yabancı öğrenci listesinin aynı adresi kullanması tekilleştirme ile yetki denetimini atlatamaz.

| Dosya | Dar kapsam | Son koşu |
|---|---|---:|
| UserMailSecurityTest.php | Yeni izin, girilen adres, başka öğrenci/iptal ilişki, stdClass, tek sorgu/email lookup olmaması, aynı adresli yabancı satır, iptal izin/eksik kimlik, tekil/toplu enqueue hata logu, hata sonrası devam, stats-only öğretmen olmayan aktör, şablon render, null adres/sayaç | 10 test |
| LeagueCalculationTest.php | Önceki puan/boş sonuç testleri ve üç yeni kesin lig kuralı testi | 5 test |
| StudentStatsAccessTest.php | Üç okuma/export kapsamı testi ve her sonuç türünün user_id taşıması | 3 test |
| **Toplam** | **Ayrı testing DB; gerçek mail yok** | **18 test / 74 assertion geçti; 4.04 saniye** |

Komut: `docker compose exec -T -e APP_ENV=testing -e DB_DATABASE=testing laravel.test php artisan test tests/Feature/UserMailSecurityTest.php tests/Feature/LeagueCalculationTest.php tests/Feature/StudentStatsAccessTest.php --no-ansi`.

Test sırasında iki fixture/test uyarlaması yapıldı: mount'ta reddedilen bileşene ikinci `send` çağrısı yapılmaz; tekil formdaki mevcut DNS kuralı için MX kabul eden domain kullanılır. Sonrasında bağımsız incelemenin doğruladığı regresyonlar düzeltildi ve yalnız bu üç dosya tekrar koşuldu. Tüm suite/build veya eski testlerin tekrar tekrar koşulması yapılmadı. Önceki 57 anlamlı PHP testine üç lig ve on mail testi eklenerek 70 anlamlı PHP test metodu oldu; 70'inin birlikte geçtiği iddia edilmez.

## 6. Sıradaki bulgular ve karar kapısı

- **Sonradan yanıtlandı ve düzeltildi:** Kullanıcı backend sonuç istatistiğinde toplamları seçti: 7/10 + 5/10 → 12/20, 8 yanlış, %60. Sorgular SUM ve toplamdan yüzde hesabına geçirildi; ekran/Excel kanıtı `17-PANEL-ISLEV-TESTLERI-VE-ISTATISTIK.md` içindedir. Bu karar artık yanıt beklemez; ligden ayrı olarak onaylandı.
- Bütün panel/soru havuzu CRUD/export ve rol matrisi henüz kapsamlı test edilmedi. Mevcut kapsam tüm ürün güvenlik veya doğruluk onayı değildir.
- Gerçek PWA offline/logout/eski worker geçişi ve kabul edilmiş cron gecikmesinin ölçümü ayrı ortam doğrulamasıdır.
- Canlı SQL/migration yedeği ve eski sonuç verisi hâlâ beklenir; dosya gelince salt okunur karşılaştırma yapılır. Laravel 13 veya paket kaldırma bu paketin parçası değildir.
