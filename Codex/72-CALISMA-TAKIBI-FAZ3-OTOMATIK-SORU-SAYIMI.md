# Çalışma Takibi — Faz 3: otomatik test ve sınav sayımı

14 Ekim 2026. **Kod hazırlığı; DB/HTTP/Livewire ve tarayıcı kabulü bekliyor.** Faz 2 zaten `f51ee519` commit’iyle kaydedilmişti; kullanıcı bu tur commit ve Faz 3 geliştirmesini istedi. Aynı Faz 2 için ikinci commit oluşturulmadı. Önceki `0834f8d1` Faz 1 ve `b0a366d4` başlangıç commit’leri korunur.

## 1. Bu fazın kapsamı

- Yeni cevap işleminin zamanı puanlama zamanından ayrılır.
- Test (Exam/ExamResult) ve süreli sınav (Test/TestsResult) soru sayıları doğrudan kayıtlı cevaplardan okunur.
- Yarım ve tamamlanan denemeler birlikte; doğru/yanlış fark etmez. Boş cevap sayılmaz. Aynı testi/sınavı yeniden çözmek yeni deneme olduğu için yeniden sayılır.
- Aynı cevap alanını değiştirmek çoğaltmaz; en son seçimin gün/haftasına taşır. Cevabı kaldırmak sayımdan çıkarır.
- Manuel girişler ayrı kalır. Otomatik cevaplar çalışma parçası veya hedef kaydı oluşturmaz.
- Mevcut hedef ekranında günlük/haftalık toplam artık manuel + test + sınav sorusudur. Okunan sayfa ve çalışma süresi manuel kayıtlardan gelir; sınav süresi otomatik teori/çalışma süresine eklenmez.
- Grafikler, akademik dönem seçimi, karşılaştırmalar ve güçlü/zayıf konu analizi Faz 4’tür; bu tur başlamadı.

## 2. Eski kayıt kararı

Bu tur kullanıcıya soruldu ve **eski kayıtlarda created_at kullanımı onaylandı**. Eski updated_at puanlama/tamamlama sırasında değişebildiğinden çalışma tarihi yapılmaz.

StudyAutomaticWork tarih kaynağı: `COALESCE(study_answered_at, created_at)`. Başlangıç ayarı yalnız manuel kayıtları sınırlar; eski otomatik cevaplar dışlanmaz. Bu işlem geçmiş satırları değiştirmez veya toplu backfill yapmaz. Eski kayıtların gerçek son cevap değiştirme zamanı artık mevcut değilse geri kazanılamaz; onaylı created_at yaklaşımı bu sınırlamayla çalışır. İki tarih de yoksa ilgili satır bir gün/haftaya atanamaz ve sayılmaz.

## 3. Cevap zamanı

`StudyAnswerClock::next` saf yardımcıdır:

- İlk pozitif cevap → işlem zamanı.
- Öncekinden farklı pozitif cevap → yeni işlem zamanı.
- Aynı pozitif cevap tekrar gönderilirse → mevcut cevap zamanı; eski null zamanı da null kalır ve approved created_at kullanılır.
- Cevap 0 olarak kaldırılırsa → zaman null, sayım dışı.
- Kaldırdıktan sonra yeni cevap → o anın zamanı.

Süreli sınav SaveTestResultAnswer mevcut kullanıcı/deneme lock, bölüm-soru-cevap doğrulaması, tamamlanmış deneme ve deadline denetimlerinden sonra bu zamanı aynı detail kaydına yazar. İlk satır oluşturma aynı parent lock içinde kalır. Süresiz test mevcut firstOrCreate yoluyla yalnız yeni cevaba zaman yazar; kilitli cevabı değiştirme/yeniden çözme davranışı değiştirilmedi.

Tamamlama action’ı ve bulk puanlama upsert’i study_answered_at kolonunu güncellemez. Dolayısıyla puanlama gecikse veya sonraki haftada olsa çalışma haftası değişmez. Boş tamamlanan sorular yeni saat almaz. Mevcut puan, doğru/yanlış, sınav süresi, tamamlanma, tek aktif deneme ve queue sözleşmesi korunur; gerçek regresyon testleri hâlâ bekler.

Saat verileri mevcut uygulamanın yerel timestamp saklama düzenini izler. Tam cumartesi 00:00 yeni haftadır; filtre alt sınır dahil/üst sınır hariçtir. Site saat dilimi veya eski veri saat dilimi topluca değiştirilmedi. Gerçek DB kontrolünde timestamp/saat dilimi ve indeksler doğrulanacak.

## 4. SQL ve erişim

StudyAutomaticWork gün başına tek SQL UNION ALL/aggregate çalıştırır. İki kaynağın ilgili kullanıcı sonuçlarına join’i vardır. Tamamlandı filtresi veya doğru/yanlış filtresi yoktur. answer_id>0 ve question_id>0 gerekir; deleted_at işaretli sonuçlar hariçtir. Soru/cevap havuzunun sonradan silinmesi geçmiş denemede pozitif seçimin sayılmasını engellemez; geçmiş cevapları havuza tekrar yazma yoktur.

- Süresiz testte eski duplicate satırlar mümkün olduğundan aynı exam_result_id/question_id için en yüksek mevcut detail id tek kabul edilir. Son satır boşsa önceki cevap yeniden sayılmaz.
- Süreli sınavda mevcut unique(tests_result_id,section_id,question_id) anahtarı korunur: aynı bölüm/soru alanının değişimi tek seçimdir. Farklı bölümde ayrı soru alanı mevcut sınav modeline göre ayrı sayılır; bölümler birleştirilmez.
- Aynı sorunun ayrı denemelerdeki cevapları ayrı sayılır.
- Yetki SQL’den önce AuthorizeStudyAccess ile kontrol edilir; ön yüz yalnız auth kullanıcısı, ortak reader yetkili öğretmen/yönetici kapsamına hazırdır.
- Modül kapalıyken reader boş döner ve cevap tablosu sorgulamaz. Çözme izni olan kullanıcı modül kapalı olsa da cevaplayabilir; cevap zamanı yine saklanır.
- Cache eklenmedi; kaldırma/değiştirme sonraki okumada güncel görünür. Otomatik sayaç manuel tablolara yazmaz, hedef gerektirmez.
- Mevcut sonuç user_id ve detail FK indeksleri kullanılır; duplicate kontrolü için önceki migration’daki exam_result_id/question_id indeksine dayanılır. Fiziksel indeks ve EXPLAIN kontrolü DB kapısında bekler. Ölçülmüş canlı performans iddiası yoktur.

StudyDailyTotals saf birleştirme: manual_questions + automatic_tests + automatic_exams = questions. Günler sıralanır; otomatik kayıt yalnız soru miktarıdır, manuel sayfa/dakika/parça miktarını değiştirmez. Sıfır manuel kayıt ile hiç kayıt olmaması ayrımı korunur.

## 5. Ekran ve çeviri

/account/study günlük tablo: Tarih / Manuel soru / Test sorusu / Sınav sorusu / Toplam soru / Sayfa / Saat / Manuel giriş sayısı. Haftalık toplam aynı kaynak ayrımıyla gösterilir. Manuel ayrıntı listesi ve15pagination değişmez. Ön yüz kullanıcıya ait veriyi okur.

TR/NL/EN/FR60eş anahtar: birleşik açıklama, manuel/test/sınav etiketleri ve boş çalışma mesajı. Dil DB/aktivasyon/özel çeviri değişmedi. Mevcut Bootstrap table-responsive düzeni kullanılır; yeni CSS/JS yok. APP_VERSION/kök worker1.1.25 değişmedi.

## 6. Migration ve yayın koşulu

Yeni `2026_10_14_120000_add_study_answer_time_to_result_details.php` yalnız exam_result_details ve tests_result_details tablolarına nullable datetime study_answered_at ekler. Mevcut answer/correct/point/created_at/updated_at verileri değiştirilmez; backfill yok, yeni indeks yok.

**Bu migration önceki onayların dışında; uygulanmadan ayrıca onay gerekir. Henüz hiçbir DB’de uygulanmadı.** Faz 1’in iki migration’ı onaylı fakat bekler. Faz 2 submission_key migration’ı da ayrıca onay bekler. Kullanıcı servis değişimini reddetti ve DB/browser testlerini bekletti; bu tur aynı karara uyuldu, yeni uygulama onayı istenmedi.

**Yayın kapısı:** Cevap kaydetme kodu bu yeni kolonu kullanır. Migration uygulanmadan bu kod canlıya alınmamalıdır; aksi halde normal test/sınav cevap kaydı da hata verir. Modülün kapalı olması bu şema gerekliliğini kaldırmaz. Yayın günü mevcut DB yedeği, migration sırası ve kod geçişi birlikte değerlendirilir. down cevap saatlerini siler; gerçek rollback ayrıca onay/plan gerektirir.

## 7. Gerçek test kanıtı

```bash
/opt/homebrew/opt/php@8.5/bin/php vendor/bin/phpunit tests/Unit/StudyCalendarTest.php tests/Unit/StudyInputTest.php tests/Unit/StudyAutomaticWorkTest.php
```

**73 test / 239 doğrulama başarılı**: Faz1+2 önceki63 + Faz3 yeni10 saf senaryo. Aynı cevap, yeni seçim, silme/yeniden cevap, eski null saat, cuma→cumartesi, kaynak toplamları, sıfır/boş ve otomatik çalışmada sahte sayfa/saat/parça oluşturmama.

İlgili15PHP syntax ve Pint başarılı; bir Blade’in component etiketleri çalıştırılmadan PHP syntax kontrolü başarılı; dört dil60anahtar eşliği/boş değer kontrolü başarılı; git diff --check başarılı. Bu kontroller gerçek SQL, Laravel render veya tarayıcı kanıtı değildir.

## 8. Hazırlanan ve bekleyen testler

Yeni StudyAutomaticIntegrationTest **11 DB/Livewire senaryosu hazır; çalıştırılmadı**:

1. Yarım/tamam, tekrar deneme, doğru/yanlış/pending cevapların ortak sayımı.
2. Aktivasyondan eski kayıt ve created_at fallback; updated_at puanlamasını dışlama.
3. Eski süresiz duplicate slotun tek sayılması; son boş cevabın öncekini diriltmemesi.
4. Ayrı cevap saati önceliği ve cumartesi üst sınır.
5. Gerçek SaveTestResultAnswer retry/change/clear/reanswer ve hafta taşıma.
6. CompleteTestResult ve tekrarlı tamamlama sonrasında saat/puan/cevap koruması.
7. Deadline geçtiğinde yeni cevabı reddetme, boş tamamlanmış deneme.
8. Gerçek süresiz SolveForm save ve ikinci denemenin tekrar sayılması.
9. Kişisel kapsam, boş/silinmiş sonuç dışlama ve diğer kullanıcıya erişimi reddetme.
10. Kapalı modülde sorgu yapmama ve solve akışının çalışması.
11. Birleşik Livewire tablosu, manuel50+otomatik2=52 ve sahte manuel/hedef yazmama.

Ortam açıldığında Faz1–3 ilgili kapsam birlikte ve yalnız testing DB’de doğrulanacak:

```bash
php vendor/bin/phpunit tests/Feature/StudyTrackingFoundationTest.php tests/Feature/StudyTrackingSettingsTest.php tests/Feature/StudyTrackingEntriesTest.php tests/Feature/StudyTrackingFormTest.php tests/Feature/StudyAutomaticIntegrationTest.php tests/Feature/TimedExamTest.php
```

Komuttaki php, Eduan Sail PHP8.5 veya doğrulanmış host PHP8.5 olmalı; default host PHP8.4 kullanılmaz. Tam proje suite’i değildir. Desktop/mobil /account/study, yeni toplam sütunları, iki gerçek solve akışı, reload, change/clear ve console/browser kontrolleri de bekler. Gerçek eşzamanlı DB/queue/performans kabulü verilmez.

## 9. Değişen dosyalar

- app/Helpers/StudyAnswerClock.php
- app/Helpers/StudyAutomaticWork.php
- app/Helpers/StudyDailyTotals.php
- app/Actions/Tests/SaveTestResultAnswer.php
- app/Models/ExamResultDetail.php
- app/Models/TestsResultDetail.php
- app/Livewire/Frontend/Tests/SolveForm.php
- app/Livewire/Frontend/Study/StudyForm.php
- resources/views/livewire/frontend/study/study-form.blade.php
- database/migrations/2026_10_14_120000_add_study_answer_time_to_result_details.php
- lang/tr/study.php, lang/nl/study.php, lang/en/study.php, lang/fr/study.php
- tests/Unit/StudyAutomaticWorkTest.php
- tests/Feature/StudyAutomaticIntegrationTest.php
- Codex/72-CALISMA-TAKIBI-FAZ3-OTOMATIK-SORU-SAYIMI.md
- Codex/index.html
- AGENTS.md

Model dosyalarının ilgili formatter düzeni de uygulanır. İlgisiz .DS_Store korunur/commit dışında kalır. DB/migration/aktivasyon/sunucu/servis/port/paid API/gerçek mail-push/deploy değişmedi.

## 10. Elle kontrol sırası

Eduan ortamı açılıp gerekli migration onayları/uygulaması tamamlandıktan sonra:

- Geçerli çalışma ayarı ve study:access hesabıyla /account/study. Eski test/sınav cevapları ilgili created_at haftasında görülebilmeli.
- Yarım testte1soru cevapla; günlük test+1. Aynı denemeyi yeniden açınca çoğalmamalı; yeniden çöz ile yeni deneme+1.
- Süreli sınavda1soru cevapla; günlük sınav+1. Seçimi değiştirince toplam aynı, kaldırınca-1, yeniden cevapla+1. Tamamlama puanı koruyup zamanı değiştirmemeli.
- Aynı gün manuel50ekle; manuel/test/sınav ayrı, toplam ortak. Yalnız manuel kayıt düzenlenebilir/kaldırılabilir.
- Cuma23:59→cumartesi00:00 geçişi ayrı testing zaman kontrollü senaryoda denenir; gerçek saat/DB saati değiştirilmez.
- Diğer kullanıcının sonucu görünmez; yetki geri alındığında sayfa403. Mobil yatay tablo, ayrıntı/pagination ve console kontrol edilir.

Sonraki faz **Faz4 öğrenci grafikleri, dönem/geçmiş ve analizler**. Faz3 kod hazırlığı tamamdır; bekleyen DB/browser kapısı tamamlanmadan tam faz kabulü verilmez.

## 11. 14 Ekim yerel kontrol kapısı tamamlandı

Kullanıcı Docker’ı açtı ve bekleyen bütün yerel migration/test işlemlerini açıkça onayladı. Eduan80/3306 çalışır, PHP8.5.8. Yalnız dört migration hypotenuse ve testing’de uygulandı. Ana languages3/translations2557/exam_result_details149914/tests_result_details10695 satırları aynı kaldı; study ayarları null kaldı, modül aktive edilmedi. Testing’de de eski cevap/dil/çeviri sayıları ve ayarlar migration öncesi/sonrası aynı. Canlıya erişim yok.

İlk155test/663assert koşusu153pass/2failure verdi. İki fixture sorunu: test configEurope/Brussels iken PHP varsayılanEurope/Istanbul idi; HTTP middleware no-store/private üstüne ek cache direktifleri ekliyordu. Tests/Concerns/UsesStudyTimezone kurulum ve geri yükleme sağlar; cache testi gerekli private/no-store direktiflerini denetler. Uygulama iş kuralı değiştirilmedi.

Düzeltme sonrası yalnız ilgili82DB/HTTP/Livewire/TimedExam senaryosu430assert başarılı. Önceki aynıtur73saf/239assert ile155farklısenaryo geçti; son155tamkoşu tekrarı değildir. Testing transaction’ları geri alındı.

Browser plugin not available; mevcut bundled Playwright/Chrome kullanıldı. HTTP istekleri aynılocalhost adresinde geçici CLI QA köprüsüyle gerçek testing Laravel kernel’ine yönlendirildi; persistent outer transaction sonunda rollback. Servis/port/ana DB ayarı değişmedi. Hedef150save, çalışma50save, reloadkalıcılık ve düzenleme25, desktop1440/mobile390 ve NLsettings tabı doğrulandı. JSerror0/HTTP5xx0/pageoverflow0. QA başlatma Request/Cookie/BinaryFileResponse ve Livewire flush-state adaptörü gerekir; bu düzeltmeler geçici QA aracına aittir, ürün değişikliği değildir. Gerçek ayrı webserver ortamı/gerçek cihaz/queue teslimi/üretim performansı iddiası yok. Kanıt /private/tmp/eduan-study-faz3-browser-result.json ve desktop/mobile PNG.

Faz3kodcommit4bf2f14e korunur; bu kontrolün test hazırlığı ve dokümantasyonu ayrı commit edilir. Kullanıcı onayıyla Faz4’e geçilir: devam eden sınavların doğruluk analizi dahil, karşılaştırmalar yalnız öğrenci hesaplarında. Ücretli API/SMTP/push/deploy yok.
