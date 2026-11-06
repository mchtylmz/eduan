# Pasif sınavlar, lig kapasitesi ve Amsterdam zamanlaması — 6 Kasım 2026

## Kullanıcı kararı ve kapsam

- Site Sınavlar listesinde aktif ve pasif sınavlar birlikte görünür. Kullanıcı açıkça, bölümü veya sorusu olmayanların gizli kalmasını seçti.
- Pasif sınav detayı okunur; başlatma düğmesi dört dilde “Yakında başlayacak” karşılığıyla disabled olur. Pasif sınava doğrudan başlatma ve action üzerinden giriş de engellenir; aktif sınavın izin/oturum/süreli deneme kuralları korunur.
- Geçmiş ligler, güncel genel kapasite ayarına göre yeniden bölünmez. Önceden üretilmiş league_number/position/rank_position kayıtları esas alınır; kupa yanlış gruba taşınmaz.
- Açık ve devam eden canlı lig, yeni kapasiteyi bir sonraki başarılı hesaplamada kullanır. Yalnız ayarı kaydetmek lig kaydını/sonuçlarını değiştirmez.
- Yeni tek hesaplamalı ligler de kullandıkları capacity/scoring snapshot'ını mevcut calculation_settings JSON alanına kaydeder. Snapshot'lı eski tek hesaplamanın kayıtlı kuralları korunur. Eski snapshot'sız kayıtların görüntülenmesi üretilmiş gruplara dayanır; toplu backfill veya geçmiş veri düzeltmesi yapılmaz.
- Kapalı veya dönemi bitmiş/finalizasyon bekleyen ligde kapasite değişmez. Açık canlı ligde yalnız kapasite güncellenir; kaynak ve puanlar sabit kalır. Tarih/kod/kimlik, RANK eşitlikleri ve kapanış kuralları korunur.
- Amsterdam Europe/Amsterdam:09:00–23:45 her15dakika;00:00–08:00 saat başı.08:15/08:30/08:45 çalışmaz,09:00 çalışır.23:45 sonrası00:00,01:00 şeklinde devam eder.
- Diğer uygulama işlerinin saat dilimi değişmez. Mevcut dakikalık SiteGround scheduler cron'u kullanılır; yeni bir lig cron'u eklenmez.

## Uygulama

ListExams aktiflik filtresi kaldırıldı; bölüm/soru HAVING koşulları korundu. Cache context'ine include_passive eklendi; eski aktif-only cache aynı anahtarla okunmaz. ExamController yalnız detail için aktiflik404 kapısını kaldırır; start ve StartTestResult kontrolleri korunur. Mevcut disabled Blade dalı learning.coming_soon anahtarını kullanır.

LeagueResultTable, genel ayar yerine sonuç satırındaki league_number ve pozitif sıra alanlarını kullanır. Snapshot varsa kayıtlı kapasite okunur; eski snapshot'sız kayıtta kullanıcı sayısı bilinmeyen0 cache bağlamıdır, UI'da sahte bir tarihsel kapasite üretilmez. Mevcut optional userCount çağrı sözleşmesi korunur ancak grup seçimi bundan türetilmez.

CalculateLeague mevcut transaction/period lock altında, kayıtlı kuralları okur. Devam eden açık canlı ligde yalnız user_count güncel ayardan alınır. Snapshot ve üretilen pozisyonlar aynı transaction'da yazılır; hata olursa birlikte geri alınır. Yeni snapshot alanı/migration gerekmez. Snapshot'sız eski tek lig, ancak açıkça hesaplama işlemi çalıştırılırsa o hesaplamanın kurallarını kaydeder; görüntüleme hiçbir veri yazmaz.

Tek refresh_live_leagues event'i everyFifteenMinutes + Europe/Amsterdam + gece yalnız minute0 koşuluyla çalışır. Tek overlap mutex'i ve240dakika kilit süresi korunur. Yaz/kış saati Amsterdam takviminden alınır; gece tekrarlanan saat iki gerçek saatte, olmayan saat atlanarak ilerler. Eski haftalık görev ve diğer schedule olayları korunur.

Panel kapasite alanına ve mevcut lig formu açıklamalarına dört dilde yeni davranış yazıldı. CSS/JS, env, APP_VERSION/PWA1.1.43, bağımlılıklar, migration ve gerçek veriler değiştirilmedi.

## Doğrulama durumu

- İzole gerçek Livewire form/schedule + LeagueRules/LeaguePeriod: **32 test /5841 doğrulama başarılı**. Tek schedule, kış/yaz ve iki DST gününün bütün5760gerçek dakikası test edildi; toplam276due anı doğrulandı. Eski haftalık event saat başı kalır.
- Değişen20PHP kaynak/test/dil dosyası syntax başarılı. Hedefli Pint, kaldırılmış CronExpression import'u temizlendikten sonra başarılı.
- Lig tablo JavaScript testi:8doğrulama başarılı. Son eklenen kapasite/pasif sınav test dosyalarının Pint kontrolü başarılı.
- Yeni PassiveExamVisibilityTest:4dil×aktif/pasif×misafir/izinli/izinsiz24hücre ve boş/bölüm/soru/içerik/arama listesi senaryoları gerçek testing DB üzerinde başarılı.
- Lig DB regresyonları: eski snapshot'sız23kişinin10/10/3grubu genel14/4ayarında aynı kalır; yeni single snapshot; ayar kaydı sonrası değişmezlik; sonraki canlı hesaplamada14; bitmiş/kapalıda koruma; yazma hatasında kapasite ve sonuç rollback'i. Önceki artık geçerli olmayan donmuş canlı kapasite/aktif-only liste beklentileri yeni kullanıcı kararına uyarlandı.
- Kullanıcı Eduan'ı açtı. Başlangıçta her iki Eduan container'ının ağ bağlantısı boştu; mysql DNS çözümü olmadığı için ilk129test0assert ile bağlantıda durdu. Yalnız mevcut eduan-web_sail ağına mysql/laravel.test alias'ları geri bağlandı; diğer servisler ve proje ayarları değiştirilmedi. Web servisi kendi HTTP adresinde200 döndü.
- Son temiz dar DB koşusu: **129 test /968 doğrulama başarılı**. PassiveExamVisibility, FrontendListFindings, FrontendSeoUpgrade, TimedExam, LiveLeagueCalculation, LiveLeagueScheduling, LiveLeaguePresentation, FrontendLeaguePresentation ve LeagueCalculation testleri birlikte çalıştırıldı. Yeni migration uygulanmadı; testler yalnız testing DB/transaction kullanır, dış HTTP istekleri engellidir.
- Gerçek Chrome: dört dil×1440/390 genişlikte8sınav akışı (liste bağlantısı→pasif disabled; izinli aktif başlatma bağlantısı), ayrıca iki genişlikte geçmiş10/10/3grup ve ikinci grubun Legacy11 birinciliği doğrulandı. Toplam10akış kaydı; JSistisna0/beklenmeyen5xx0/yataytaşma0. Screenshot'lar /private/tmp/eduan100-*.png, sonuç /private/tmp/eduan100-browser-results.json. Browser plugin bulunmadığı için mevcut Playwright/Chrome kullanıldı; servis portları host'a yayınlanmadığından tarayıcı istekleri Docker içindeki gerçek Laravel HTTP kernel/Livewire'a köprülendi; gerçek MySQL transaction ve gerçek kaynak asset'leri kullanıldı. Bu test sunucu port yayını veya üretim reverse-proxy testi değildir.
- Ana ve testing veritabanlarında users/settings/tests/tests_results/leagues/league_results/source_files tablolarının tarayıcı öncesi/sonrası sayı ve satırSHA256 değerleri aynı. Ana381kullanıcı/9sınav/385sonuç/36lig/1238ligsonucu/5dosya korunur; geçici tarayıcı verileri rollback edildi. Gerçek hesaplama/gönderim ana DB'de yapılmadı.
- İncelenen sınav/lig değişikliği yerel kabulü geçti. Canlı deploy/gerçek üretimcron/OPcache ve sunucu smoke kontrolü bu tur yapılmadı.

## Yayın sınırı

Bu değişiklikler daha önce hazırlanıp kullanıcının sunucuya yüklediği **eduan-20261106-v3 ZIP'inde yoktur**. O arşiv ve SHA dosyası sessizce değiştirilmez. Gerekli gerçek testler tamamlandıktan sonra yeni commit/paket istenirse yeni isim/hash ile hazırlanır. Yeni migration yok; normal config/route/view cache ve OPcache yayın adımları uygulanır. Yeni schedule dakikalık mevcut cron üzerinden etkinleşir.
