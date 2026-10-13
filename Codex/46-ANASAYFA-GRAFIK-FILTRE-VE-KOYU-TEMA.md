# Ana sayfa grafik, bağlantı filtresi ve koyu tema — 6 Ekim 2026

## 1. Onaylanan kapsam

Kullanıcı ana sayfadaki dört sıralamanın grafik olarak, en fazla beş kategoriyle gösterilmesini istedi. Popüler AI konuları, görüntülenen testler ve dersler yatay çubuk; yanıtlanan testler aynı ölçekte soru/doğru/yanlış olmak üzere üç ayrı çubuk serisi kullanır. Başlık ve sayaç formülleri korunmuştur. Kayıtlı/premium/yeni öğrenci bağlantıları ilgili filtrelerle açılır; yeni kullanıcı ön izlemesine kayıt zamanı eklenir. KPI ikonları renklendirilir. Dashboard, grafikler ve dönüştürülmüş tablo/kartların koyu tema görünümü bu kapsamdır.

44 tarihsel sıralama-kart tasarımıdır; güncel görsel karar bu belgededir. Liste içeriklerini yeniden geliştirme, diğer 12 Rappasoft sınıfını taşıma veya genel tema düğmesini yeniden yazma bu faz değildir.

## 2. Grafik çalışma biçimi

- DashboardStats gerekli düz dizileri ilk HTTP cevabında Blade'e verir; mevcut 3.600 saniye cache korunur.
- Dört sıralama SQL limit5 kullanır. Cache formatı `dashboard:v2` oldu; eski v1 snapshot yeni alanlar için okunmaz. Anahtarda dil, izin ve güncel öğretmen öğrenci kapsamı devam eder.
- Grafik CSS/HTML figure, figcaption, kategori, ortak eksen ve çubuklar ile ilk HTML'de oluşur. Yeni JS kütüphanesi, CDN/chart indirmesi, Livewire lazy veya ayrı grafik isteği yoktur.
- AI mor, test görüntüleme mavi, ders görüntüleme turkuazdır. Sonuç serileri soru=mavi, doğru=yeşil, yanlış=kırmızı; legend ve sayısal metin renkleri tamamlar.
- Sonuç çubukları **gruplanır**, üst üste toplanmaz. Soru sayısı zaten denemenin toplamı olduğundan doğru+yanlış+soru şeklinde hatalı bir toplam üretilmez. Yarım denemelerin mevcut toplam kuralı korunur.
- Eksen tüm panel kategorileri için ortak üst sınıra sahiptir. Sıfırda bölme yok; sıfır değer açıkça0 görünür. Boş veri çevrilmiş boş durum verir. Tam kategori isimleri kırılabilir, HTML escape edilir; sayısal değer yanında metin ve title bulunur.
- Özel grafik elementi ekran okuyucuya renk dışında seri etiketini de verir. Mobilde aynı beş kategori/seri korunur; grafik isimleri satıra kırılır.

## 3. KPI bağlantısı ve kullanıcı filtreleri

| Kart | URL filtresi ve sunucu sorgusu |
| --- | --- |
| Kayıtlı öğrenciler / yönetici | status=active, student_group=normal → mevcut User::user() |
| Kayıtlı öğrenciler / öğretmen | status=active; mevcut teacher students kapsamı, premium dahil |
| Premium öğrenciler | status=active, student_group=premium → User::premiumUser(); öğretmen kendi kapsamı |
| Yeni öğrenciler | status=active, student_group=all → USER işlem türü, normal+premium; registered_since snapshot'ın7gün önceki kesin zamanı |

Öğrenci türü, rol adına sabit metinle bağlanmaz; mevcut permission/scope kurallarını kullanır. Öğretmen kapsamı kullanıcının istemci filtresiyle genişletilemez. Dashboard:access/users:view JSON cache okunmadan önce gerekir. Kullanıcı filtreleri öğrenci türü ve isteğe bağlı saatli kayıt başlangıcı alanını görünür gösterir; mevcut rol/durum/günlük kayıt ve son giriş filtreleri kalır. Birden fazla filtre SQL'de kesişir; temizleme düğmesi mevcut ortak davranışı kullanır.

`registered_since` Y-m-d\TH:i:s biçiminde doğrulanır ve uygulamanın mevcut tarih/DB saat dilimiyle karşılaştırılır. Böylece yeni öğrencilerin7gün hesabı gün başlangıcına yuvarlanmaz. Dil dosyaları nl/en/tr yeni etiketleri taşır. Kayıtlı/premium sayaç tanımları değiştirilmedi.

Snapshot saatlik güncellendiğinden, canlı kullanıcı listesinin kayıt/durum değişimleri snapshot'taki sayıyı bir sonraki hesaplamaya kadar farklılaştırabilir. Filtre tanımı eşleşir; sayı her an birebir eşit vaat edilmez. Liste kendi60sn cache/refresh/invalidation standardını kullanır; yeni filtre parametreleri mevcut parametre cache anahtarına girer.

Mesaj bağlantısının mevcut davranışı korunur. Yeni/son giriş ön izlemeleri mevcut6 kayıt kullanır; yeni kayıtlarda tarih+saat ve mevcut e-posta onay etiketi birlikte bulunur.

## 4. Görünüm ve tema

KPI üst kenarı ve beyaz ikon zemini sırasıyla mavi/mor/yeşil/amber renkleri kullanır. Dashboard dark-mode kart/panel zeminleri, metinleri, çizgi/eksen/legend/avatar renkleri ayrıdır; KPI linki açık tema metninin karanlık zeminde kalmasını önler. 768px altında tek grafik sütunu; küçük KPI grid ve kullanıcı tarih satırı responsive kalır.

server-table.css ortak koyu tema; filtrelerin select/input/dropdown alanları, kart başlık/meta/ayraçları, tablonun Bootstrap renk değişkenleri ve detay açıklamalarını tamamlar. OneUI'nin kendi koyu tablo yazısı da korunur. Bu CSS ortak dönüştürülmüş liste bileşenleri kapsamındadır; site veya eski Rappasoft ekranlarının koyu tema kabulü değildir.

CSS değiştiğinden `.env APP_VERSION` **1.1.7→1.1.8**. Mevcut config('app.version') query ile yüklenir. Canlı config/cache veya yayın işlemi yapılmadı.

## 5. Dar test kanıtı

PHP8.5.7 / PHPUnit13.4.1, yalnız ayrı testing DB ve DatabaseTransactions. İlk sandbox koşusu yerel bağlantıyı engelledi,0 doğrulama; uygulama hatası sayılmaz. İzinli yerel bağlantıda DashboardStatsTest7 senaryosunun6'sı geçti; yeni senaryonun son isteğinde testin status=active parametresi eksikti. Parametre eklendi; yalnız bu senaryo yeniden çalıştı: **1 test/24 doğrulama geçti**. Sonuç:7 ilgili senaryo ayrı koşularla başarılı; tek yeni genel suite veya tek7-test başarılı koşu iddiası yoktur.

Kapsam:

1. İlk HTML'de native grafik ve lazy/chart indirme isteği olmaması.
2. AI konu toplamı, öğretmen ve dil cache ayrımı.
3. Son7gün aktif normal/premium ve değişen atama kapsamı.
4. Tamam/yarım sonuç toplamları, warm cache ağır sorguları atlama ve TTL.
5. İzin kaybında403 ve AI görünürlüğü.
6. Yönetici/öğretmen KPI filtrelerinin sayaç sorgularıyla eşleşmesi; saatli sınır, pasif/kapsam dışı ayrımı, hatalı grup/tarih422 ve users:view olmadan403.
7. Limit5, güvenli kayıt tarihi projeksiyonu, boş/sıfır grafik ve HTML escape.

Formatter ilgili PHP/dil dosyalarında geçti. Tüm proje suite/build, migration, seed veya yeni indeks çalıştırılmadı.

## 6. Gerçek Chrome kontrolü ve sınırlar

Browser plugin bulunmadığından mevcut Chrome/Playwright fallback kullanıldı; yeni bağımlılık kurulmadı. Mevcut izole testing harness127.0.0.1:8765, ana Docker80/3306 değişmeden kullanıldı. QA yalnız testing sentetik hesabıdır; Gate takviyeleri gerçek rol matrisinin kanıtı değildir. Gerçek izin/öğretmen kanıtı PHPUnit'tedir. Tema düğmesi sentetik hesabın tema metasını değiştirdi; kontrol sonunda açık tema geri doğrulandı. Ana DB yazılmadı.

- Dashboard200, doğru sayfa/title, dört grafik/üç sonuç serisi ve ilk içerik; framework hata ekranı yok.
- Masaüstü1440×1000 açık/koyu ve mobil390×844 koyu; document yatay taşması yok.
- Üç KPI linki kullanıcılar ekranında öğrenci türü/aktiflik/kesin kayıt başlangıcı alanlarını gerçekten seçti.
- Dersler, Konular, Kullanıcılar, Yetkiler, Loglar, AI, Sorular, Testler ve Test Sonuçları dokuz sayfa200/koyu tema metni kontrol edildi. Kullanıcı/AI custom view ve gerçek tablo görünümü görselleri ayrıca kontrol edildi.
- İlgili console/page error yok. Mevcut testing verisi seyrek/sentetiktir; canlı sayıları, üretim yükü veya tüm cihaz/rol matrisi doğrulandığı iddia edilmez. Dashboard için yeni grafik/lazy POST eklenmedi.
- Geçici görseller `/tmp/eduan-dashboard-charts-light.png`, `...-dark.png`, `...-mobile.png`; kullanıcı/AI dark card ve dark-table görselleri de /tmp altında. Depoya ekran görüntüsü veya özel kullanıcı verisi taşınmadı.

**Ek mevcut aday (otomatik düzeltilmedi):** app.js tema düğmesi `!sessionStorage.getItem('oneuiDarkMode')` kullanır. Server'da darkMode aktif olup yeni browser sessionStorage boş olduğunda ilk tık HTML'de yeniden koyu modu seçebilir; `'false'` stringi de truthy'dir. Son görsel QA'da geçici sessionStorage görünür durumla eşleştirilerek açık tema kontrolü tamamlandı. Genel tema düğmesi davranışı ayrı düzeltme kararıdır; bu faz renk/CSS uyumudur.

## 7. Değişen dosyalar

- app/Helpers/DashboardStats.php:5 kategori/cachev2/yeni öğrenci başlangıcı/created_at.
- app/Http/Controllers/Backend/UsersController.php: öğrenci grubu ve saatli kayıt filtre doğrulaması/sorgusu.
- resources/views/backend/home/index.blade.php: doğru filtreli KPI bağlantıları ve renk seçimi.
- resources/views/backend/home/partials/ranking.blade.php: native grafik/legend/eksen/boş durum.
- resources/views/backend/home/partials/metric.blade.php: KPI renk sınıfı.
- resources/views/backend/home/partials/users.blade.php: kayıt zamanı.
- resources/views/livewire/backend/users/server-table.blade.php: görünür yeni filtreler.
- public/backend/assets/dashboard.css: grafik/renk/tema/mobil.
- public/backend/assets/server-table.css: ortak koyu tema tamamlamaları.
- lang/nl/dashboard.php, lang/en/dashboard.php, lang/tr/dashboard.php: yeni etiketler.
- .env: yalnız APP_VERSION1.1.8.
- tests/Feature/DashboardStatsTest.php: mevcut render beklentisi ve iki dar senaryo.
- AGENTS.md, Codex/16-PROJE-STANDARTLARI.md, bu belge ve Codex/index.html: karar, kanıt ve rehber.

## 8. Kullanıcı revizyonu — grafik aralıkları

6 Ekim 2026: Dört grafiğin üst sayısal ekseni kaldırıldı; çubuk ölçeğinin hesabı ve sağdaki sayısal değerler korunur. Kategori adı–çubuk margin7.2px →1.6px, kategori aralığı16px →10.4px; seri alt aralığı4px →2px, son seride0px. Legend alt aralığı16px →9.6px. Sağdaki bütün değerler font-weight700. Panel grid aralığı, başlıklar, veri/SQL/saatlik cache ve üç ayrı sonuç serisi değişmedi.

Değişen uygulama dosyaları: `resources/views/backend/home/partials/ranking.blade.php`, `public/backend/assets/dashboard.css`; CSS nedeniyle yerel `.env` APP_VERSION1.1.9 →1.1.10. İlgili standart/AGENTS/index kaydı güncellendi.

Yalnız mevcut `DashboardStatsTest::test_chart_limit_empty_zero_values_and_registration_date_projection` çalıştırıldı: **1 test/9 doğrulama geçti** (PHP8.5.7/PHPUnit13.4.1, testing transaction). Yeni test/build/genel suite yok.

Browser plugin bulunmadığından mevcut Chrome/Playwright kullanıldı. İzole testing harness127.0.0.1:8765: dashboard kimliği/4 grafik/ilk içerik/hata ekranı, eksenin yokluğu, computed1.6px/10.4px/700 değerleri ve asset query1.1.10 doğrulandı. Masaüstü1440×1000 açık ve390×844 koyu CSS görünümü/taşma kontrolü; reload sonrasında aynı şablon/sürüm, ilgili console/pageerror yok. Görseller /tmp altında; ana DB'ye yazılmadı, normal Docker portları değişmedi. Tema düğmesinin önceki adayını kontrol/düzeltme değildir. Geçici foreground harness kontrol sonunda durduruldu.
