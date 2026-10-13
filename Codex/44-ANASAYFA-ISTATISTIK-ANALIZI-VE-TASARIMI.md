# Panel ana sayfa istatistikleri — 6 Ekim 2026

## 1. İstek ve kesinleşen kararlar

Ana sayfadaki istatistikler ilk ekranda görünmeli, sorgular hafiflemeli ve saatlik cache kullanılmalıdır. Mevcut başlıklar korunarak çubuk grafikler yerine okunabilir sıralama kartları uygulanmıştır. OneUI, Bootstrap ve mevcut fontlar korunur; yeni paket yoktur.

Kullanıcı kararları:

- Popüler Yapay Zeka Konuları, cevap kimliği yerine konu kimliğinde birleştirilir.
- Dördüncü kart: son 7 gündeki yeni öğrenciler.
- Bu kart aktif normal ve premium öğrencileri birlikte sayar; öğretmen yalnız kendi öğrencilerini görür.
- Saatlik veri tazeliği kabul edilir. Cache süresi dolduktan sonraki ziyaret yeni özeti üretir; ayrıca cron veya queue işi eklenmemiştir.

## 2. Önceki çalışma biçimi ve bulunan yükler

Önceki ana sayfa, AI izni olan yönetici için üç `Home/Statistics`, dört `Home/ChartWidget` ve iki `Home/UserTable` olmak üzere dokuz ayrı lazy bileşen kullanıyordu. İlk HTML istatistik değerlerini içermiyordu. Grafik çiziminde ayrıca 1.000 ms bekleme ve 1.100 ms animasyon vardı. ApexCharts dış kaynaktan yükleniyordu.

Sayaçlar/grafikler 2 saat, son kullanıcı listeleri 3 saat cache kullanıyordu. Fakat yerel provider ve mevcut kayıt/resetCache akışları default cache'i temizleyebildiğinden bu süreler her istekte güvenilir bir cache ömrü sağlamıyordu. Genel cache temizliği bu geliştirmede değiştirilmedi.

AI sorgusu öğretmenin öğrencilerini filtreliyor, fakat `home_chart_popularAiAnswers` anahtarına öğretmeni katmıyordu. Bu nedenle ortak cache farklı öğretmen kapsamlarını karıştırabilirdi. Ayrıca cevap ID'sinde gruplama aynı konuyu birden fazla satırda gösteriyordu. Yerel salt okunur veride birden fazla AI cevabı bulunan 59 konu görüldü; bu sayı test veya kullanım sayısı değildir.

Eski grafikler isimleri 10/12 karakterde kısaltıyordu. Tam model kayıtları ve gereksiz ilişkiler cache'e giriyordu. Sonuç gruplamasında toplulaştırılmayan `id` seçiliyordu; yeni sorguda yalnız `exam_id` ve toplamlar seçilir. Mevcut `strict=false` ortamında eski sorgunun 500 verdiği iddia edilmez.

## 3. Yeni veri akışı

`HomeController::index` → `DashboardStats::get()` → Blade ana sayfa ve üç küçük partial.

Veriler ilk HTTP cevabında HTML olarak gelir. Ana sayfa istatistikleri için Livewire update, grafik çizim olayı, ApexCharts indirmesi ve animasyon beklemesi yoktur. Ortak layout'un modal/offcanvas/events bileşenleri korunmuştur; bütün uygulamadan Livewire kaldırılmamıştır.

Snapshot yalnız görünümde gereken düz dizileri taşır. Test/ders için ID, isim, görüntüleme; sonuç için test ID/isim ve üç toplam; kullanıcı için ID, kullanıcı adı, isim, soyisim, onay durumu, son giriş zamanı alınır. Parola/token, soru içeriği, Base64 içerik veya bütün User modeli snapshot'a girmez.

Mevcut `Home/Statistics`, `Home/ChartWidget`, `Home/UserTable` kaynakları silinmemiştir; güncel ana sayfa bunları çağırmaz. Eski Home tablo sınıflarının geçişi bu kapsamda değildir.

## 4. Sayaç ve istatistik tanımları

| Alan | Uygulanan kapsam |
| --- | --- |
| Kayıtlı öğrenciler | Yönetici tarafında mevcut aktif normal öğrenci (`User::user()`) kuralı; öğretmende mevcut kendi aktif öğrencileri, premium dahil |
| Premium öğrenciler | Aktif USER işlem türü + `exams:solve`; öğretmende kendi öğrencileri |
| Son 7 gündeki yeni öğrenciler | Aktif USER işlem türü, normal/premium birlikte, `created_at >= now()->subDays(7)`; öğretmende kendi öğrencileri |
| Yeni mesajlar | Okunmamış Contact sayısı; öğretmende gösterilmez |
| Popüler AI konuları | Konu ID + başlık gruplaması ve usage toplamı; öğretmende kendi öğrencilerinin kullanımı; `ai:view` gerekir |
| En çok görüntülenen testler | Mevcut aktif Exam içeriklerinin global görüntüleme sırası; öğretmen için de içerik istatistiği global kalır |
| En çok yanıtlanan testler | ExamResult denemelerinde question/correct/incorrect toplamı; öğretmende kendi öğrencileri; bütün denemeler, yarım dahil, mevcut kural korunur |
| En çok görüntülenen dersler | Mevcut Lesson görüntüleme sırası; mevcut pasif kayıt kapsamı korunur; öğretmende gösterilmez |
| Yeni kullanıcılar / son girişler | Mevcut son 6 kayıt; öğretmende gösterilmez |

Önemli isim eşlemesi: kullanıcıdaki süresiz **test** `Exam/exams`, süreli **sınav** `Test/tests` modelidir. Bu dashboard sonuç kartı ExamResult kullanır; süreli sınav veya lig hesabı değiştirilmemiştir.

**Korunan anlam sınırı:** “En çok yanıtlanan” sırası mevcut `SUM(question_count)` ile belirlenir. Bu değer yarım denemede de denemenin toplam soru sayısını içerebilir; yalnız gerçekten cevaplanan soru sayısı olduğu iddia edilmez. Doğru/yanlış ayrı değerler olarak gösterilir. Başlık/formül değişikliği ayrı kullanıcı kararı gerektirir; bu aşamada otomatik değiştirilmedi.

## 5. Saatlik cache ve erişim

- `dashboard` isimli file store, `storage/framework/cache/dashboard` altında tutulur. Default file store `.../cache/data` dizininden ayrıdır; mevcut default flush özeti temizlemez.
- TTL 3.600 saniyedir. Cache boşsa ilk ziyaret sorguları çalıştırır; doluyken ağır toplam ve sıralama sorguları tekrarlanmaz. Arka planda önceden ısıtma eklenmedi.
- `dashboard:access` cache okunmadan önce denetlenir.
- Anahtar sürümü `dashboard:v1`; locale, öğretmen ID veya global kapsam, öğretmenin güncel öğrenci ID'leri, sıralı izin listesi ve güncel `ai:view` sonucu hash'e katılır.
- Öğrenci ataması değişince farklı kapsam anahtarı kullanılır. Öğrenci ID listesi ve erişim/izin bilgisi her çağrıda güncel değerlendirilir; bütün SQL sorgularının sıfırlandığı iddia edilmez.
- AI izni olmayan snapshot AI alanını `null` taşır. İzin değişimi aynı AI özetini tekrar kullanmaz. Yönetici global özeti yalnız aynı izin/locale kapsamlarında paylaşılır.
- Kayıt durumu, sonuç, okunmamış mesaj ve isim değişikliği onaylanan saatlik tazelikle yansır. Global temizliği kaldırma, tüm tablo cache'lerini değiştirme veya anlık KPI invalidation bu aşamada yoktur.
- Bakım sırasında yalnız bu store temizlenmek istenirse `cache:clear dashboard` kullanılır; bu belge komutu çalıştırma yetkisi değildir. Yeni cache formatında anahtar sürümü artırılır. File store için Laravel storage yazma izni gerekir.

## 6. Yerel SQL ölçümü ve indeks kararı

Ana yerel DB'de yalnız SELECT/EXPLAIN, Laravel boot edilmeden PDO ile çalıştırıldı. Session SQL modu mevcut strict=false koşulunu temsil eder. Her temsilî sorgu üç kez çalıştırıldı; tabloda medyan vardır. Bunlar HTTP/gerçek Eloquent uçtan uca süreleri veya canlı performans kanıtı değildir. Modelin global ikincil sırası ile ölçüm sorgusunun ikincil sırası farklı olabilir.

| Temsilî sorgu | Medyan ms | Ölçüm notu |
| --- | ---: | --- |
| AI eski gruplama | 2,777 | 8 satır, 708 byte ham JSON |
| Test görüntüleme, bütün alanlar | 1,209 | 8 satır, 2.500 byte |
| Test görüntüleme, ID/isim/hits | 0,300 | 8 satır, 450 byte |
| Sonuç toplamları | 14,638 | 8 satır; exam_id indeksi, yaklaşık 16.434 plan satırı, temporary/filesort |
| Ders görüntüleme, bütün alanlar | 0,372 | 7 satır, 1.742 byte |
| Ders görüntüleme, ID/isim/hits | 0,221 | 7 satır, 529 byte |
| Yeni kullanıcılar, eski tam kayıt | 0,209 | 6 satır, 3.217 byte |
| Son girişler, eski tam kayıt | 1,101 | 6 satır, 3.278 byte; mevcut last_login indeksi kullanılıyor |

Bu ölçümde en yüksek toplam sorgusu yaklaşık 14,6 ms; görünür beklemenin önemli kısmı lazy/istek/çizim akışındadır. Bu aşamada yeni indeks veya migration yazılmadı/uygulanmadı. Küçük sonuç kümelerinde sütun projeksiyonu ve saatlik cache tercih edildi. İleride veri hacmi/soğuk istek bütçesi büyürse gerçek Eloquent SQL/EXPLAIN ile ayrıca değerlendirilir. Kesin HTTP hızlanma yüzdesi/katı verilmez.

## 7. Görsel standart

Dört yönetici KPI kartı ve öğretmende mesaj hariç üç kart. Etiket, kapsam ipucu, büyük sayı ve izin varsa ilgili liste bağlantısı. Kayıtlı/premium sayacı tanımları birbirine karıştırılmamalıdır.

Sıralamalar ilk 8 kayıt, tam isim ve sağda sayısal değerle gösterilir. Sonuç kartında doğru/yanlış metinleri ve ikonlar renkleri tamamlar. Silinmiş ilişki için çevrilmiş kayıt etiketi; boş liste için açık boş durum. Yeni/son giriş kullanıcıları salt okunur 6 kayıt ön izlemesidir, ayrı CRUD tablosu değildir.

Masaüstü iki panel sütunu; 768px altında tek panel sütunu. KPI grid ekran genişliğine uyum sağlar; 400px altında tek kart sütunu. CSS yalnız `.admin-dashboard` kapsamındadır. Ana metin 15px, başlık 16px, büyük sayılar 30/34px; OneUI fontu korunur. Uzun isim/kullanıcı adları satıra kırılır. Yeni font, JS grafik kütüphanesi, Vue veya dış UI paketi eklenmedi.

Yeni CSS `dashboard.css?v=config('app.version')`; `.env APP_VERSION` 1.1.5 → **1.1.6**. Yayında mevcut config cache düzeniyle sürüm etkinleştirilmelidir; canlı yayın yapılmadı.

## 8. Dar test ve tarayıcı kanıtı

Son koşu: PHP 8.5.8 / PHPUnit 13.4.1, `DashboardStatsTest`: **5 test / 29 doğrulama geçti**. Yalnız ayrı `testing` DB, transaction ile test kayıtları geri alınır. Dashboard store testte array store'dur.

1. İlk HTML'de veriler, yeni kart, lazy/grafik olaylarının yokluğu ve private içerik projeksiyonu.
2. Aynı konunun iki AI cevabı 4+7=11; başka öğretmenin 20 kullanımının ayrılması, locale ayrımı.
3. Aktif normal+premium yeni öğrenci, eski/pasif/kapsam dışı hariç; öğrenci ayırma sonrası yeni anahtar.
4. Tamam/yarım bütün denemelerin toplamı; warm cache ağır SQL'i atlar, default flush'tan korunur, 3.601 saniye sonra yenilenir.
5. AI izni kaldırılınca görünürlük değişimi ve dashboard erişimi kaldırılınca 403.

Gerçek Chrome, Playwright ile mevcut izole testing web harness üzerinde kontrol edildi. İlgili browser plugin/runtime yerine mevcut yerel Chrome/Playwright fallback'i kullanıldı. Uygulamanın normal 80/3306 portları değiştirilmedi; QA foreground sunucusu yalnız test harness içindir.

- Masaüstü 1440×1000 ve mobil 390×844: HTTP 200, dört KPI, mevcut QA iznine göre test/sonuç/ders panelleri, yatay taşma yok, console/page error yok.
- Yenilemede snapshot zaman metni korundu; file store ile render çalıştı. TTL ve ağır sorgu atlama ayrıca PHPUnit ile sınandı.
- Dashboard için ek lazy widget isteği ve ApexCharts indirmesi yok. Yakalanan iki Livewire POST, önceki giriş yönlendirmesinin **account/profile** lazy formlarına aittir; dashboard isteği sayılmaz.
- QA hesabında AI izni yok; AI panelinin gerçek tarayıcı görsel kontrolü bu koşuya dahil değildir. AI toplam/çeviri/erişim kanıtı PHPUnit'tedir. Gerçek öğretmen UI/production yük testi, koyu tema ve bütün rol matrisi kabulü verilmez.
- İlk QA koşusunun 500 nedeni testing DB'de `user-type:user` tanımının olmamasıydı. Eksik üç izin tanımı (`user-type:user`, `user-type:teacher`, `exams:solve`) yalnız testing DB'ye eklendi; hesaplara izin/rol atanmadı, seed/migrate/silme yapılmadı. Ana DB sorunu olduğu iddia edilmez.
- Kanıt görselleri geçici `/tmp/eduan-dashboard-desktop-1006.png`, `/tmp/eduan-dashboard-mobile-1006.png`; kalıcı doküman çıktıları değildir.

Tüm proje suite/build çalıştırılmadı; bu sonuç yalnız dashboard kapsamıdır. Formatter ilgili PHP dosyalarında geçti. Ana DB yalnız okundu; veri/şema/indeks değişikliği, deploy ve gerçek mail/dış servis işlemi yoktur.

## 9. Değişen dosyalar

- `app/Helpers/DashboardStats.php`: erişim, kapsam, projeksiyon, konu toplamı ve snapshot cache.
- `app/Http/Controllers/Backend/HomeController.php`: özet verisini Blade'e aktarır; kullanılmayan importlar formatter ile temizlendi.
- `config/cache.php`: ayrı dashboard file store.
- `resources/views/backend/home/index.blade.php`: doğrudan render ve yeni dört kart.
- `resources/views/backend/home/partials/metric.blade.php`: sayı kartı.
- `resources/views/backend/home/partials/ranking.blade.php`: okunabilir sıralama/sonuç/boş durum.
- `resources/views/backend/home/partials/users.blade.php`: son kayıt/giriş ön izlemesi.
- `public/backend/assets/dashboard.css`: responsive görünüm.
- `lang/nl/dashboard.php`, `lang/en/dashboard.php`, `lang/tr/dashboard.php`: yeni etiketler.
- `.env`: yalnız APP_VERSION 1.1.6; secret değerleri dokümana alınmaz.
- `tests/Feature/DashboardStatsTest.php`: beş dar test.
- `AGENTS.md`, `Codex/16-PROJE-STANDARTLARI.md`, bu dosya ve `Codex/index.html`: yeni kurallar, kanıt ve indeks.
