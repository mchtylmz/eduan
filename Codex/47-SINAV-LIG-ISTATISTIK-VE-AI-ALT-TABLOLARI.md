# Sınav, lig, istatistik ve yapay zekâ alt tabloları

6 Ekim 2026. Kullanıcının bu fazdaki kapsamı: Sınavlar/Sınav Sonuçları/İstatistikler/Ligler ve AI yanıtının kullanım/değerlendirme listelerini mevcut Bootstrap Table standardına taşımak; AI ve Soru Havuzu kartlarına sıralama alanı eklemek. Form alanlarının veya sonuç hesaplama kurallarının yeniden tasarımı değildir.

## 1. Kesin kararlar

- JSON liste cache'i60 saniye, seçenek cache'i1 saat; ilgili model değişiminde hedefli geçersizleştirme, Yenile ile taze veri.
- İstatistikler bütün içerik dillerini içerir; test/sınav adları kayıtların özgün adlarıdır. Panel Türkçe olduğu için İngilizce adlı kaydı saklama/yeniden adlandırma.
- Arayüz ve Excel başlıkları panel dilinin mevcut çeviri sistemini kullanır. Translatable konu başlığı mevcut panel locale alanından alınır.
- AI/Soru Havuzu varsayılan custom view devam eder. Kart/table görünümü aynı JSON, sayfalama, filtre ve sıralamayı kullanır.
- Excel mevcut işlevini korur; yalnız ekrandaki sayfa değil filtre ve aramaya uyan bütün sonuçlar aktarılır.
- Ana DB'ye bu fazda yazılmaz. PHPUnit ve sentetik UI fixture yalnız ayrı `testing` DB'dedir.

## 2. Sayfa haritası

| Ekran | Sınıf / JSON | Korunan kapsam |
|---|---|---|
| Sınavlar | Tests/TestTable → TestsController::getData | Dil/durum, süre, geçme puanı, bölüm/soru sayıları; düzenle/bölümler/sil izinleri |
| Sınav Sonuçları | Tests/ResultsTable → getResultsData | Dönem, öğrenciler, sınavlar, tamamlanma; öğretmenin kendi öğrencileri |
| Sınav sonucu detay | Tests/ResultsDetailTable → getResultDetailsData | Scoped parent, ders/konu, soru/cevap/doğruluk/puan/tarih;5 kayıt başlangıcı |
| Ligler | Leagues/LeagueTable → LeagueController::getData | Başlangıç/bitiş tarih eşitliği, hesaplanma, readonly sonuç paneli, mevcut silme izni |
| Lig sonuç paneli | LeagueResultsTable → getResultsData | Kullanıcı/lig1–7, lig numarası sonra pozisyon, öğretmen kapsamı;5 kayıt |
| AI görüntülemeler | UserUsageTable → AiController::getUsagesData | Yanıt ID, kullanım tarihi, kullanıcı/kullanım/kalan bilgisi |
| AI değerlendirmeler | AnswerVoteTable → getVotesData | Yanıt ID, kullanıcı/tarih, oy; parent/öğretmen ve ai:delete denetimi |
| İstatistik soru özeti | Stats/QuestionsStats → StatsController::getQuestionsData | Başlangıç/bitiş, kullanıcı bazlı test+sınav toplamı ve başarı |
| İstatistik raporu | Users/ResultStatsCustomTable → StatsController::getData | Kullanıcı, konu/test/sınav türü, yüzde eşiği, konu; bütün denemelerin toplamı |
| İstatistik kusursuz sonuç | Mevcut Exams/ResultsTable |45 içinde taşınmış tablo; mevcut incorrectCount davranışı korunur |
| Kullanıcıya ait istatistik | UsersController::getStatsData/getStatsExport | context_user_id sunucuda yetkilendirilir; istemci user_id ile başka öğrenciye geçemez |

Terminoloji: uygulamadaki **Test = Exam/exams**, süreli **Sınav = Test/tests**. Fiziksel tablo veya migration adı değişmedi. Süreli detay FK'si `tests_result_id`; yeni panel parametresi `testsResultId`.

## 3. Liste ve detay standardı

Ana listeler15 kayıt; sunucu limit1–100 ve offset üst sınırı doğrular. Arama250 karakterle sınırlı, sort whitelist ve asc/desc dışındaki değerler reddedilir. ID eşitlik sırası sayfalamayı kararlı tutar; ligde eski lig/pozisyon sırası ayrıca korunur. Sınav JSON'u tam içerik veya kullanıcı parola/token alanlarını taşımaz.

Sınav sonuçları varsayılan tamamlanmış kayıtlar ve aktif dönem/0; filtreyle eski kapsam seçilebilir. Sonuçlar ayrı denemeler halinde listelenir. İstatistik toplam kuralı değişmedi; yalnız son deneme seçilmez. Sınav puan/geçme ve süre bilgileri korunur.

Sağ panel ortak width100%/max840px standardındadır. Nested tablo kendi URL state'iyle ana listenin filtrelerini bozmaz. `open-learning-table(view,recordId)` sunucudaki allowlist ve scoped parent kontrolünden geçer. Yeni izin veya genel event mimarisi eklenmedi.

Lig türü gerçek job kaydındaki `tests_result_details` değeriyle Sınav Sonuçları olarak gösterilir; eski `tests_results` değeri de kabul edilir. Puan hesaplama/RANK kuralı bu fazda değiştirilmedi.

## 4. Custom view sıralaması

`x-admin.table-sort` iki alan sunar: kolon ve artan/azalan yön. AI: dil, ders, konu, soru görseli, değerlendirme adedi, ortalama, görüntüleme kayıt adedi, oluşturma, rapor durumu. Soru: dil, ders, konu, soru görseli, kod, sıra, süre, durum.

Filtrele, Bootstrap Table'ın mevcut `sortBy` API'sini kullanır. Sunucuda ilk sayfaya döner; kartlar gerçek sıralanmış JSON satırlarından üretilir. Tablo başlığıyla sıralama değiştirilince form seçimi de güncellenir. URL'den sort/order geri yüklenir; Yenile ve Reset ortak davranışı korur. Soru görseli kolonu da select içinde vardır; başlığa tıklayınca geçersiz boş sort üretilmez.

Yön metinleri ve yeni alan etiketi nl/en/tr `tables.php` içindedir; yeni npm/Composer paketi yok.

## 5. İstatistik ve Excel

`StatisticsTable` mevcut aggregate sorgularını paylaşır. Rapor query'si kullanıcı/konu/test/sınav toplamlarını korur; konu iki sonuç detay kaynağının UNION ALL toplamıdır. Başarı ağırlıklı `toplam doğru / toplam soru`dur; yüzde ortalamalarının ortalaması değildir. Sınav puanları toplanır.

`StatsController` aggregate sonucu alt sorguya alarak server search/sort/pagination uygular. İçerik dillerini panel diline göre daraltmaz. Konu JSON başlığı panel locale'inden, test/sınav adı düz kayıt adından alınır. Örneğin iki10-soruluk deneme7+5 doğruysa20 soru/12 doğru/8 yanlış/%60 görünür.

JSON ve Excel aynı authorize/parameters/query zincirini kullanır. Excel, sayfa limit/offset ve refresh parametrelerini dışarıda bırakarak bütün filtreli kayıtları aktarır. Test/sınav Excel başlıklarındaki model eşlemesi düzeltildi. Konu export'u JSON ve düz başlığı işleyebilir. Kriter select'i10–100 aralığındadır, boş “Tümü” seçeneği üretmez.

Sonuç türü seçilmeden rapor boş gelir; konu türünde konu seçilmesi gerekir. Eksik kriterde export/mail düğmeleri kullanılabilir görünmez. Soru istatistiğinin eski tarih sonu anlamı korunmuştur: `ended_at` Y-m-d doğrudan whereBetween içindedir, gün sonuna genişletilmedi. Bunu ayrı tarih davranışı kararı olmadan değiştirme.

Mevcut mail hazırlama düğmesi korunur. Sonuç filtresi sunucuda yeniden yetkilendirilir ve alıcı listesi mevcut kullanıcı/konu/tür/yüzde kriterleriyle yeniden sorgulanır (tablodaki yeni serbest arama eski mail kriterine eklenmedi); `users:send-email` ve güncel öğretmen öğrenci kapsamı korunur. Livewire4 `component` hedef parametresiyle çakışmamak için `stats-mail-ready` → izinli `openStatsMail` → `stats-mail-opened` kullanılır. Bu fazda gerçek mail veya dış servis işlemi yapılmadı.

## 6. Cache ve değişiklikler

`TableCache` normalize filtre dizilerine tests eklendi. Cache anahtarında actor/izin/locale/APP_VERSION/revision/parametre ve güncel öğretmen öğrenci ID kapsamı bulunur. Parent ve erişim kontrolleri cache'den önce yapılır. JSON HTTP yanıtı private/no-store; sunucu cache'i60sn devam eder.

Tests, TestsSection/Question, TestsResult/Detail ve League/LeagueResult observer'a dahil edildi; AI ve istatistik revision hedefleri genişletildi. Sınav/test adı değişiminde rapor cache'i de yenilenir. Lig job'ının bulk upsert'i observer üretmediğinden işlem sonunda leagues/league-results/options açıkça geçersizleştirilir. Genel cache flush, yeni cron/worker, indeks/migration yok.

JS değiştiği için yerel `.env` APP_VERSION **1.1.8 →1.1.9**. Config app.version mevcut env bağlantısını kullanmaya devam eder; `.env` içeriği rehbere kopyalanmaz.

## 7. Dar test kanıtı

Test zemini PHP8.5.7/PHPUnit13.4.1, yalnız testing/MySQL bağlantısı; transaction, mock optimize:clear ve engellenmiş dış HTTP. Tüm suite/build çalıştırılmadı.

- Yeni `NextMenuTableTest`12 senaryosu: dört ekran render/izin; sınav kolon/sayfalama/sort; öğretmen sonuç/detail sınırı; AI parent/delete/cache bypass; lig sırası ve kapsamı; JSON+gerçek Excel toplam/ad kapsamı; soru özeti ve kart sort render; konu/sınav raporu ve model invalidation; mail modal izni ve öğretmen kapsamı.
- İlk genişleyen dar koşu12/88 doğrulamada11 geçti, konu adında1 beklenti sorunu vardı: HTTP SetLocale nl varsayılanını kullanıyordu, test yalnız app locale'ini tr yapmıştı. Test session'ı açık tr ile hazırlandı; ürün locale davranışı gevşetilmedi.
- Önceki üç hata: gerçek `testsResultId` panel parametre uyumsuzluğu düzeltildi; iki test fixture hatası (olmayan parent'ı mount etme ve league/user unique çakışması) gerçek ayrı parent/öğrenci fixture'larıyla giderildi.
- Son ilgili tekrar **4 test/39 doğrulama geçti**. Diğer yeni senaryolar önceki dar koşuda başarılıydı; böylece12 farklı yeni senaryonun başarı kanıtı vardır, hepsi tek son koşuymuş gibi sunulmaz.
- Mevcut StudentStatsAccess5 senaryosu, PanelExport4 workbook senaryosu ayrı dar koşularda geçti; son lig hesaplama regresyonu **1 test/3 doğrulama geçti**. Toplam22 farklı ilgili senaryo için başarı kanıtı; genel ürün/rol matrisi kabulü değildir.
-28 ilgili PHP/language/test dosyasında Pint kontrolü, ortak JS node syntax ve job PHP syntax geçti.

Chrome/Playwright, mevcut izole testing harness127.0.0.1:8765 üzerinde kullanıldı. Normal Eduan portu değiştirilmedi, diğer proje başlatılıp durdurulmadı. UI Gate sadece sentetik QA hesabına harness içinden izin sağladı; gerçek DB izin kanıtı PHPUnit'e aittir.

- Sekiz ekran/sekme: Sınavlar/Sınav Sonuçları/Ligler, istatistiğin3 sekmesi, AI kullanım/oy listesi. Kusursuz sonuç sekmesi mevcut fixture kapsamında boş durumla kontrol edildi.
- Sınav readonly ve sonuç/lig nested panel açılış-kapanış; lig paneli animasyon sonrası840px, opak zemin ve doğru backdrop katmanı.
- Filtreli gerçek XLSX indirme; mail hazırlama modalı açılıp kapandı, gönderilmedi.
- AI/Soru kart+tablo toggle, sort alanı/yön, gerçek JSON sort/order/offset0 ve AI sayısal sırası; soru görseli başlık sırası form alanına döner.
-390px document taşma ve görünür kart kontrolü; ortak koyu sınıf altında yeni sıralama/kart okunabilirliği. Genel tema düğmesinin önceki46 adayını düzeltme veya bütün cihaz/tema/locale kabulü değildir.
- Tarayıcı assertion scriptlerinde önce modal kapatma seçicisi ve boş sekmeye dolu veri beklentisi düzeltildi; bunlar uygulama 500 bulgusu değildir. Son kalan akışlarda console/pageerror yok.
- Testing'e sentetik bir süreli sınav, sonucu/detayı ve lig/sonucu hazırlandı; QA kayıtları temizlenmedi. Ana DB/schema/migration/seed/deploy yok. Harness kontrol sonrasında durduruldu.

## 8. Kalan kapsam ve ayrıca bildirilen adaylar

Kalan5 Rappasoft sınıfı: `Ai/LatexImagesTable`, `Users/FavoriteTable`, `Users/StudentsTable`, `Users/TeachersTable`, `Users/TopicsNotResultsTable`. Bunlar bu talebin dışındadır; Bootstrap Table'a taşınmış veya çalışır kabul edilmez.

Kaynakta ayrıca görülen, **bu fazda otomatik düzeltilmeyen** iki aday:

1. `Leagues/LeagueRunForm::run` kendi içinde işlem izni ve alan doğrulaması yapmıyor; panel görünümündeki izin, sunucu action yetkilendirmesinin kanıtı değildir. Bu faz liste/paneli taşır; yeniden hesaplama formunun yetki/validation matrisi ayrı onay ister. Ana DB'de hesaplama çalıştırılmadı.
2. `TestsResultDetail::testResult` ilişkisinin FK'si `test_result_id`, gerçek kolon `tests_result_id`. Yeni tablo doğru FK ve parent üzerinden çalışır; modeldeki eski bağımsız ilişkinin kullanım yerleri ayrı inceleme ister.

## 9. Değişen dosyalar

- Controller: TestsController, LeagueController, AiController, StatsController, UsersController.
- Helper/cache: LearningTable, yeni StatisticsTable, TableCache, TableCacheObserver, AppServiceProvider; CalculateLeagueResult yalnız targeted cache invalidation.
- Livewire: Tests/TestTable, ResultsTable, ResultsDetailTable; Leagues/LeagueTable, LeagueResultsTable; Ai/UserUsageTable, AnswerVoteTable; Stats/QuestionsStats; Users/ResultStatsCustomTable; Offcanvas, Modal.
- Blade: yeni tests3/leagues2/AI2/statistics2 server-table view'ları; mevcut AI/Soru root view'ları; yeni admin/table-sort ve mevcut filter-select.
- JS/çeviri: backend/assets/server-table.js, nl/en/tr tables.php; yerel APP_VERSION1.1.9.
- Excel: FilterStatsExamsExport, FilterStatsTestsExport, FilterStatsTopicsExport.
- Test: yeni tests/Feature/NextMenuTableTest.php.
- Rehber: AGENTS.md,16 standartlar,19 geçiş haritası,47 bu belge ve index.html.
