# Eğitim menüsü Bootstrap Table geçişi — 6 Ekim 2026

## 1. Onay ve kapsam

Kullanıcı Yapay Zeka, Soru Havuzu, Testler ve Test Sonuçları listelerinin mevcut proje standardına taşınmasını; ilk iki listenin custom view/kart görünümüyle açılmasını istedi. JSON60sn/seçenek1saat cache, kayıt değişiminde invalidation ve Yenile bypass ayrıca onaylandı.

Menü eşlemesi: Testler `Exam/exams`, Test Sonuçları `ExamResult/exam_results`. Süreli Sınavlar `Test/tests` bu fazda taşınmadı. Formların içerik/alan düzenlemesi sonraki aşamadır. Liste başlıkları, oluşturma düğmeleri ve mevcut düzenleme sayfaları korunur. Composer/paket, SQL/migration ve hesaplama formülü değişmedi.

Dört ana sınıf ve bunlardan açılan üç bağlı sınıf Rappasoft'tan normal Livewire Component'e geçti. İlk HTML filtre/tablo iskeletini içerir; kayıtlar Bootstrap Table server JSON ile sayfa sayfa gelir. Ana sayfa istatistiklerinin SSR standardıyla karıştırılmamalıdır.

## 2. Liste davranışları

| Menü | JSON route | Varsayılan | Korunan filtreler |
| --- | --- | --- | --- |
| Yapay Zeka | `admin.ai.getData` | Kart; report DESC; tüm diller | Çoklu aktif ders/konu, dil, yalnız raporlananlar |
| Soru Havuzu | `admin.questions.getData` | Kart; sort DESC; mevcut içerik dili | Çoklu aktif ders/konu, dil, aktif/pasif |
| Testler | `admin.exams.getData` | Tablo; ID DESC; mevcut içerik dili | Çoklu aktif ders/konu, dil, görünüm0/1, durum |
| Test Sonuçları | `admin.exams.getResultsData` | Tablo; updated_at DESC; tamamlanmış; aktif dönem veya0 | Dönem, kullanıcılar, testler, tamamlanma |

Ana listeler15 kayıt;15/25/50/100 seçilebilir. `total` ve `rows` aynı SQL kapsamından gelir. Sort whitelist, limit1–100, offset sınırı ve arama250 karakter sınırı ortak ServerTable ile uygulanır. Çoklu ID filtreleri integer/distinct/exists ve en fazla100 değer olarak doğrulanır. Arama/sıra/filtre SQL'de; bütün kayıtları istemciye yükleme yoktur. Görünüm0 ve tamamlanma0 boş filtre sayılmaz.

Testin ders/konu filtresi seçili soruların lesson_id/topic_id değerleri üzerinden `whereHas(questions)` ve `whereIn` kullanır. Exam modelindeki aynı pivotu kullanan lessons/topics ilişkileri değiştirilmedi. Soru Havuzu'nun mevcut model varsayılanı sort DESC korunur; bağlı test soruları status ASC kullanır.

Sonuçlar ayrı deneme satırlarıdır; bu listeye yeni gruplama veya son deneme filtresi eklenmedi. Yanıt, lig ve dashboard toplam kuralları değiştirilmedi. Kullanıcı/tek test/istatistik içindeki ResultsTable kullanımları exam_id/user_id ve eski `incorrectCount` kapsamını taşır. `incorrectCount` mevcut question_count=correct_count anlamını korur. Liste ve JSON artık boş exam/user modeline `exists` erişimi yapmaz.

AI değerlendirme ve görüntüleme adetleri `withCount(votes/usages)`, ortalama `withAvg(votes,vote)` ile hesaplanır; oy/kullanım koleksiyonları satır başına yüklenmez. Görüntüleme sütunu mevcut **usage kayıt sayısıdır**, SUM(usage) değildir. Dashboard konu toplamıyla karıştırma. Oy yoksa ortalama null/“—”; yeni kartta0 puan uydurulmaz. AI content JSON listeye/cache'e taşınmaz.

## 3. Kart ve görsel standardı

AI ve Soru Havuzu `is-card-view` ile açılır. Yenile yanında tablo/kart düğmesi; aynı rows, aynı sayfalama/arama/sıra/filtre kullanılır. Kullanıcılar kartı ortak renderer içinde korunmuştur.

- Soru kartı: kod (boşsa konu), ders, konu, soru görseli, dil, saniye, sıra, durum; Görüntüle, izinli Düzenle/Sil.
- AI kartı: yanıt başlığı, ders/konu, soru görseli, dil, kullanım kayıt adedi, değerlendirme adedi/ortalama, rapor ve oluşturma; mevcut AI detay sayfasına Görüntüle.
- Grid mevcut kullanıcı standardını izler: geniş ekranda3, orta2, mobil1 sütun. Başlık18px, meta15px; tablo15px, işlem16px. OneUI ve mevcut fontlar korunur.
- Görsel tablo120×90, kartta150px yüksekliğinde contain; tıklama ayrı sekmede/noopener. URL HTTP/HTTPS ile sınırlı, metinler escape edilir. Görsel/thumb lazy olabilir; bu kayıt listesidir, ön yüz ilk ana görsel kuralı değildir.
- Görselsiz satır “—” gösterir. Attachment fallback'i boşsa ana sayfa URL'sini yanlışlıkla görsel URL'sine dönüştürmez. Mevcut geçerli site-logo fallback korunur.
- Loading, empty ve hata durumları mevcut ortak renderer'dan gelir. Form içeriği/yükleme sistemi değiştirilmedi.

Soru Görüntüle readonly840px panel, mevcut metadata ve varsa görsel gösterir. Düzenleme ayrı sayfada kalır. Test Görüntüle readonly metadata; Soru adedi düğmesi bağlı sorular, Favoriye Ekleyenler ayrı panel, Sonuçlar mevcut tek-test sayfasına gider.

## 4. Bağlı üç panel

| Panel | JSON route | Sınır/kapsam |
| --- | --- | --- |
| Test soruları | `admin.exams.getQuestionsData` | Exam ilişkisi, durum filtresi, dil/ders/konu/görsel/süre/durum;5 satır |
| Favoriler | `admin.exams.getFavoritesData` | Seçili Exam; öğretmende kendi öğrencileri; kullanıcı adı/ad/soyad/son giriş;5 satır |
| Sonuç detayları | `admin.exams.getResultDetailsData` | Önce sonuç sahibinin öğretmen kapsamı; ders/konu, görsel, cevap/doğru-yanlış, süre/tarih;5 satır |

Bağlı listelerde5/15/25/50/100 sayfalama, arama/sıralama ve JSON vardır. Parent liste URL filtrelerini değiştirmez; `data-table-url-state=false`. Dinamik Livewire morph sonrasında ortak JS yeni tabloyu bir kez başlatır; WeakSet zaten başlatılan ana tabloyu tekrar başlatmaz. Sonuç paneli yetkilendirilmiş kullanıcının isim/e-posta özetini aynı bileşende gösterir; eski ayrı ResultsUserDetail burada çağrılmaz.

### Açılışta bulunan Livewire4 uyarlaması

Yerel vendor `Event` sınıfı `component` parametresini payload'dan çıkarıp event hedefi olarak kullanıyor. Eski `dispatch('showOffcanvas', component: ..., data: ...)` bu üç yeni paneli açmıyordu; backend unit dispatch başarı kontrolü tek başına bunu yakalamadı. Tarayıcıda doğrulandı.

Bu üç akış `open-learning-table(view, recordId)` kullanır. Ortak Offcanvas'a bu akış için dar handler eklendi: dashboard/exams izni, üç izinli view adı, sunucudan kayıt bulma ve öğretmen sonuç kapsamı. Sonra `learning-table-opened` ile Bootstrap panel açılır. Parent/child kayıtlar ayrıca yetkilendirilir; child ID'leri Locked'dır. Eski genel showOffcanvas/showModal protokolü topluca değiştirilmedi.

Animasyon sonrası panel opacity1/z-index1045, backdrop0,5/z-index1040 kontrol edildi. Genel sağ panel100%/max840px standardı devam eder.

## 5. Cache ve güncellenme

Ortak TableCache JSON60sn, seçenekler3600sn; locale, actor, izinler, asset sürümü, filtre/arama/sıra/page ve revision anahtardadır. Çoklu topic ID'leri de normalize edilir. Yenile fresh sorgu üretip60sn cache'e yazar; JSON cevap private/no-store, PWA offline deposuna alınmaz.

Öğretmenin güncel öğrenci ID'leri sonuç/favori JSON cache kapsamına ve sonuç kullanıcı/test seçenek anahtarına girer. Öğrenci ayrılınca cached eski sonuçlar gösterilmez. Sonuç detay erişimi cache'den önce scoped parent sorgusuyla denetlenir.

Yeni observer kayıtları: AnswerAI, AnswerAIVote, UserAiUsage, ExamResult, ExamResultDetail, Answer ve Season. İlgili ders/konu/soru/kullanıcı/Exam/dil/çeviri olayları yeni listeleri/options revision'ını da yeniler. `UpdateExamQuestionsAction` pivot sync sonrası exams/exam-questions; mevcut favorite Action attach/detach sonrası exam-favorites invalidate eder. Doğrudan SQL/pivot işlemleri observer üretmez; böyle bir yeni yazma yolunda açık invalidate gerekir. Eski global resetCache/yerel boot flush burada kaldırılmadı; gerçek HTTP hız kazanımı ölçülmedi.

## 6. Test ve tarayıcı kanıtı

Yalnız testing DB; PHPUnit transaction, array cache, mock optimize:clear ve engellenen dış HTTP kullanıldı. Seed/migrate/silme/canlı mail/deploy çalıştırılmadı.

- İlk LearningMenuTable koşusu13 test/105 doğrulama geçti.
- İlgili iki mevcut soru/test CRUD senaryosu dahil dar koşu15 test/125 doğrulama geçti.
- Panel event/allowlist/sahiplik uyarlamasından sonra son LearningMenuTable koşusu **13 test/110 doğrulama geçti**.
- Görselsiz soru ve boş ortalama son düzeltmesinden sonra yalnız ilgili2 senaryo **2 test/13 doğrulama geçti**. İlk boş görsel beklentisinde factory'nin gerçek sample görseli nedeniyle test hazırlığı yanlıştı; fixture açıkça attachment=null yapılıp düzeltildi. Uygulama dosyası bu beklenti için gevşetilmedi.

Kapsam: dört route render ve JSON; iki satır/pagination/search; invalid sort/limit, AI'da olmayan status filtresi reddi; izin kaldırıldıktan sonra cached data403, misafir401; ders/konu/dil/durum/report/visibility filtreleri; oy/kullanım/ortalama; sync sonrası cache; teacher sonuç/parent/detail404, öğrenci detach sonrası kapsam; delete izinleri; JSON private alanların yokluğu; refresh bypass; üç panel render ve allowlist.

**Chrome / Playwright:** Browser plugin bulunmadığından mevcut yerel Chrome/Playwright fallback'i kullanıldı. Normal Eduan80/3306 çalışıyordu; başka proje başlatılmadı/durdurulmadı. Mevcut izole testing harness127.0.0.1:8765 foreground olarak kullanıldı ve kontrol bitince durduruldu; bu normal uygulama portunu değiştirmez.

- Dört menü HTTP200, doğru başlık ve dolu tablo/kart; framework hata ekranı yok, console/page error yok.
- AI/Soru Havuzu1440×1000 ve390×844: varsayılan kart, düğmeyle tablo ve geri, gerçek arama; mobilde document taşması yok.
- Soru readonly paneli840px; test soruları/favoriler ve sonuç detayları açıldı/kapandı, nested JSON ve5-row başlangıcı kontrol edildi.
- Ortak JS nedeniyle mevcut Kullanıcılar kartı ayrıca tek render kontrolüyle doğrulandı.
- Son görsel/animasyon kontrolü1280×720; sentetik QA soruları gerçek yerel logo dosyasıyla thumbnail olarak gösterildi. Bu gerçek matematik sorusu içeriği kabulü değildir.
- QA hesabına yalnız harness Gate erişimi eklendi; gerçek DB izin matrisi PHPUnit'te sınandı. Testing'e bir sentetik AI yanıt/oy/kullanım/favori hazırlandı; mevcut QA sonuç completed/season0 ve soru görsel/kod alanları ayarlandı. Ana DB'ye yazılmadı; QA kayıtları silinmedi.
- Geçici kanıtlar `/tmp/eduan-ai-desktop-1006.png`, `/tmp/eduan-questions-desktop-1006.png`, iki mobile görseli, `/tmp/eduan-result-detail-1006.png`, `/tmp/eduan-exam-favorites-1006.png`. İlk screenshot'taki geçiş animasyonu son sonuç paneli görselinde tamamlanmış halde yeniden kaydedildi.

Pint ilgili PHP dosyalarında, node --check ortak JS'de geçti. Tüm suite/build çalıştırılmadı. Gerçek öğretmen tarayıcısı, bütün locale/cihazlar, üretim cache/performans ve dört menünün dışındaki formlar kabul edilmedi.

## 7. Kalan kapsam ve ayrıca bildirilen aday

Kaynak taramasında12 DataTableComponent sınıfı kaldı:

- AI AnswerVoteTable, UserUsageTable, LatexImagesTable.
- Sınav TestTable, ResultsTable, ResultsDetailTable.
- Lig LeagueTable, LeagueResultsTable.
- User FavoriteTable, StudentsTable, TeachersTable, TopicsNotResultsTable.

AI Görüntüle'nin varsayılan Yanıt Bilgileri sayfası mevcut kalır. Görüntülemeler/Değerlendirmeler sekmeleri ve Latex Görselleri hâlâ eski tablo sınıflarıdır; bu dört ana listenin kabulünü onların geçişi diye sunma. Sonraki sayfa/içerik aşamasında ayrıca ele alınmalıdır.

Başka mevcut kodlarda da `component:` kullanan event kaynakları görüldü: `Tests/SectionForm`, `Tests/ResultsTable`, `Users/ResultStatsCustomTable`. Vendor davranışıyla aynı uyumsuzluk adayıdır; bu ekranların runtime'ı bu koşuda doğrulanmadı ve otomatik düzeltilmedi. Eski public/genel offcanvas dispatcher'ın bütün bileşenlere yetki modeli bu dar geçişin güvenlik kabulü değildir.

## 8. Değişen dosyalar

- Helper: `app/Helpers/LearningTable.php`, `app/Helpers/TableCache.php`.
- Controller: `app/Http/Controllers/Backend/AiController.php`, `QuestionController.php`, `ExamController.php`.
- Ana component: `app/Livewire/Ai/AnswerAiTable.php`, `Questions/QuestionTable.php`, `Exams/ExamTable.php`, `Exams/ResultsTable.php`.
- Bağlı component: `app/Livewire/Exams/QuestionTable.php`, `FavoriteTable.php`, `ResultsDetailTable.php`; dar açılış handler'ı `app/Livewire/Offcanvas.php`.
- Cache: `app/Observers/TableCacheObserver.php`, `app/Providers/AppServiceProvider.php`, `app/Actions/Exams/UpdateExamQuestionsAction.php`, `UpdateExamFavoriteForUserAction.php`. Formatter yalnız ilgili dosyalarda çalıştı.
- Blade: `resources/views/livewire/backend/ai/server-table.blade.php`, `questions/server-table.blade.php`; `exams/server-table.blade.php`, `results-server-table.blade.php`, `questions-server-table.blade.php`, `favorites-server-table.blade.php`, `result-details-server-table.blade.php`.
- Ortak Blade: `resources/views/components/admin/server-table.blade.php`, `table-messages.blade.php`.
- Asset: `public/backend/assets/server-table.js`, `server-table.css`; `.env` APP_VERSION1.1.7 (önce1.1.6).
- Çeviri: `lang/nl/tables.php`, `lang/en/tables.php`, `lang/tr/tables.php`: dönem0 için çevrilmiş “Dönem Yok”. Eski JSON'da Flemenkçe Dönem etiketi Türkçe görünebiliyor; genel çeviri taraması yapılmadı.
- Test: `tests/Feature/LearningMenuTableTest.php`.
- Rehber: `AGENTS.md`, `Codex/16-PROJE-STANDARTLARI.md`, `19-RAPPASOFT-BOOTSTRAP-TABLE-GECIS.md`, bu45 dosyası ve `Codex/index.html`.

## 7 Ekim — AI listesi varsayılan sıralama revizyonu

Kullanıcı isteğiyle AI ana listesinin sıralama seçicisi, Bootstrap Table başlangıç alanı ve JSON endpoint varsayılanı `created_at / desc` olarak eşitlendi: Oluşturma, Büyükten küçüğe. Kullanıcının açıkça gönderdiği diğer sıralamalar korunur. İlgili mevcut tek test genişletildi; yeni raporlanmamış kayıt, eski raporlu kayıttan önce gelir; açık report sırası ve ilk HTML seçimi doğrulanır. Sail PHP8.5.8: **1 test/16 doğrulama**, ilgili2PHP Pint başarılı. Değişen uygulama dosyaları `AiController.php` ve `livewire/backend/ai/server-table.blade.php`; JS/CSS/APP_VERSION, ana DB, API ve canlı değişmedi.
