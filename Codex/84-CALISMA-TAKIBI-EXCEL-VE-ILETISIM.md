# 84 — Çalışma takibi Excel ve iletişim işlemleri

26 Ekim 2026. Başlangıç: `991294a6`. Kapsam: çalışma takibi ana listesi ve öğrenci detayındaki tabloların filtreli Excel çıktıları; listeden veya tek öğrenciden e-posta/bildirim hazırlama. Proje standartları, kod rehberi, 77 iletişim ve 83 çalışma paneli rehberi ile gerçek ortak bileşenler geliştirme öncesinde incelendi.

## 1. Kullanıcı kararları

- Ana liste **ve** detayın geçmiş, günlük toplam, manuel kayıt, konu analizi, öğretmen notları tabloları dışa aktarılacak.
- Çıktı yalnız görünen sayfayı değil, erişim kapsamı/filtre/arama/sıralamaya uyan tüm satırları içerir.
- Tabloyu okuyabilen personel aynı kapsamı dışa aktarabilir; yeni export izni gerekmez.
- Liste araçları sağda: Excel’e aktar, Filtrelenenlere e-posta, Filtrelenenlere bildirim.
- Öğrenci detayından yalnız o kişiye e-posta veya bildirim hazırlanabilir.
- Gönderim için ayrı izin; başlangıçta yalnız mevcut yönetici tipindeki roller açık.
- Kullanıcı yalnız `2026_10_26_130000_grant_study_communication_permission` migration’ının yerel ana ve testing DB’de uygulanmasını açıkça onayladı. İki DB’de de uygulandı.

## 2. Kullanım ve filtreler

Sağdaki mevcut **Ara** kutusu öğrenci adı, soyadı, kullanıcı adı ve e-postada arar. Üst alana **Öğrenci durumu: Tümü / Aktif öğrenciler / Pasif öğrenciler** eklendi. Mevcut hafta ve hedef/çalışma durum filtreleri korunur.

Ana liste, export ve gönderim hazırlığı `StudyPanelQuery::parameters/filtered` üzerinden aynı doğrulanmış sorguyu kullanır. Matematiksel toplam/başarı kuralları yeniden yazılmadı. Filtrele ile uygulanmış parametreler ve arama, araç bağlantılarına taşınır; limit/offset/refresh dışarıda bırakılır. Dolayısıyla ikinci sayfadan başlatılan işlem de filtrelenmiş bütün öğrencileri kapsar. Henüz Filtrele’ye basılmamış seçimler uygulanmış veri gibi kullanılmaz.

Öğrenci/e-posta kolonu okunur minimum genişliktedir; uzun hedef başlığı sarılır. Ortak Bootstrap Table ve yatay tablo kaydırması korunur. Sekmeler, tablo sayfalaması ve izinli düzenleme işlemleri önceki sözleşmeleriyle devam eder.

## 3. Excel

| Kaynak | URL/route |
|---|---|
| Ana liste | `/admin/study/export`, `admin.study.export` |
| Öğrenci detay tabloları | `/admin/study/students/{user}/export?table=history|days|entries|topics|notes`, `admin.study.detail-export` |

Mevcut Maatwebsite Excel/PhpSpreadsheet ile gerçek XLSX üretilir. Öğretmen yalnız bağlı öğrencilerini, yönetici mevcut panel kapsamını aktarır. Detayda ebeveyn öğrenci her çağrıda yeniden yetkilendirilir. Not görünürlüğü/personel erişim sınırı değişmez.

Ana dosyada öğrenci, e-posta, hafta, üç hedef, üç gerçekleşen değer, genel gerçekleşme (%) ve durum bulunur. Detay dosyaları ilgili tablonun veri kolonlarını taşır; işlem URL/kimliği, düzenleme hakkı gibi sunum alanları dışarı aktarılmaz. Geçmişte birleşik miktar başlıkları Hedef/Gerçekleşen şeklinde açıklanır. Saat miktarları uygun tablolarda sayı hücresidir. Kullanıcı metinleri açık string hücresi olarak yazılır; `=...` ile başlayan ad veya not Excel formülü olarak çalışmaz.

Filtre ve sıralama aynıdır; sayfa sınırı uygulanmaz. Detay JSON yardımcısında export yolu tam veri döndürür; export için gereksiz satır düzenleme yetkisi hesabı yapılmaz. Yanıt `private, no-store`; dil açık query parametresiyle korunur.

## 4. E-posta ve bildirim hazırlama

`/admin/study/compose/{email|push}` yeni girişidir. Mevcut iletişim formunun üç adımı, ön izleme, UUID, içerik fingerprint’i ve outbox işlemi yeniden kullanılır:

1. **Alıcılar:** kaynak sorgudan sunucuda hazırlanmış sayı, ilk beş örnek isim ve filtre özeti. Genel kullanıcı seçiciyle başka alıcı eklenmez.
2. **Mesaj:** kanal/dil, başlık ve düz metin içerik; mevcut `{{name}}`/`{{surname}}` kişiselleştirmesi.
3. **Ön izleme/Gönder:** kişisel örnek görülür; yalnız son Gönder kuyruğa kayıt oluşturur.

Alıcılar ve kaynak öğrenci/filtre/hafta Locked’dır. Genel iletişim sayfasının mevcut seçim davranışı aynı kalır; study bağlamı ayrı alt bileşendir. Kaynak sayfaya dönüş filtre/dil bağlamını korur. Başlık ve adımlar dar ekranda sarılır.

Mevcut aktif alıcı ve azami 500 alıcı kuralları korunur. Pasif hesap içeren seçim sessizce eksiltilmez; aktif öğrencileri filtreleyerek tekrar hazırlama mesajı gösterilir. Boş seçim gönderilemez. Gönderim sonrasında mevcut beş/dakika asenkron işleyiş sürer; bu geliştirme gerçek teslimat iddiası değildir. Hazırlama veya ön izleme kayıt/gönderim oluşturmaz.

## 5. Yetki ve onaylı migration

Yeni `study:communicate` izni, mevcut `study:view`, panel/kullanıcı erişimi ve `communications:view/send` ile birlikte denetlenir. Düğmeleri gizlemekle yetinilmez; GET girişinde, her adımda ve son enqueue’da sunucuda kontrol vardır. Kaynak alıcılar güncel öğrenci/öğretmen kapsamına tekrar uymalıdır; sonradan personel rolüne geçirilen alıcı veya kaldırılan öğretmen ilişkisiyle gönderime devam edilemez.

Tek migration şema/kolon eklemez. `permissions` içine yeni izin ve yalnız `user-type:admin` taşıyan web rollerine bağ ekler; mevcut izinleri silmez/değiştirmez. Ana readback’te **Yönetici** ve **Gent** rollerinin bu yönetici tipiyle eşleştiği ve yeni izni aldığı görüldü; normal öğretmen rolüne otomatik verilmez. Migration testi yeni yönetici/öğretmen rolleriyle bu ayrımı ve tekrar çalıştırma güvenilirliğini doğrular. Rol formunda yeni izin dört dilde açıklanır.

Diğer modüllerin mevcut iletişim yetkileri değiştirilmedi. Çalışma kayıtlarına/ayarlarına yazılmadı. Canlı ortam migration’ı veya deploy yapılmadı; yayında yeni izin migration’ı ayrıca aktarılmalıdır.

## 6. Test ve tarayıcı kanıtı

Yalnız ayrı `testing` DB/transaction, HTTP stray-request engeli ve Mail/Queue fake kullanıldı. Ana DB’deki tek kalıcı işlem onaylı izin migration’ıdır.

| Kontrol | Sonuç |
|---|---|
| Son güncel StudyActions özellik koşusu | **15 test / 107 doğrulama başarılı**; aşağıdaki önceki kapsamın alt kümesi |
| StudyActions + mevcut StudyPanel | 45 test / 335 doğrulama başarılı |
| Mevcut iletişim wizard/iletişim/PWA | Önceki birleşik koşuda 34 farklı senaryo başarılı; sürüm satırı eşitlemesi sonrası yalnız PWA release yeniden kontrol edildi |
| Yeni eksik izin/öğrenci erişimi sınırı | 1 test / 4 doğrulama başarılı |
| Son alıcı rol değişimi + pozitif outbox akışı | 2 test / 13 doğrulama başarılı; biri önceki senaryonun alt kümesi |
| Detay Excel sayı hücreleri/5 tablo | Son dar 5 test / 28 doğrulama başarılı; önceki testlerin alt kümesi |
| Sürüm eşitlemesi sonrası PWA release | 6 test / 73 doğrulama başarılı; önceki kapsamın alt kümesi |
| JavaScript | 3 iletişim seçim senaryosu + 12 study formatter + 9 ortak formatter doğrulaması, syntax başarılı |
| Kaynaklar | 14 PHP syntax; 13 değişen PHP/dil dosyasında Pint; dört dilde eş/dolu 13 yeni kaynak anahtarı |
| Chrome | Filtreli 16 öğrenci / 15+1 sayfa; ikinci sayfadan bütün alıcılar; ana ve beş detay XLSX indirme; tek kişilik push hazırlama; email→ön izleme→fake outbox |
| Responsive/dil | 1440/TR,768/NL,390/TR-FR; filtreler ve dil bağlantıya taşınır, mesaj/ön izleme taşmaz |
| Hata sınırı | Son kabul kontrollerinde JS0 / HTTP5xx0 / yatay belge taşması0 |

**81 farklı ilgili PHP senaryosunun başarılı çalıştırılma kanıtı vardır; tek ortak son koşu değildir.** Genel proje suite’i/build tekrarlanmadı. Dosyalar PhpSpreadsheet ile açılıp satır/kolon/değer ve string/numeric hücre türleri doğrulandı. Öğretmen dışındaki öğrenci, eksik/geri alınmış izin, Locked alıcı değiştirme, ön izleme atlama/içerik değişimi, pasif/boş seçim, hatalı parametre, dönem kapsamı ve migration rol sınırı kontrol edildi.

Geliştirme sırasında yeni kontrol metodunun Livewire `authorize` adıyla çakışması, download response header kullanımı ve Blade’de olmayan `canAll` API’si düzeltildi. Sonraki dar testler etkilenen yolları doğruladı. Tarayıcıda yeni başlık mobilde taştı; yalnız study composer sınıfı içinde wrap/grid ile giderildi ve 390/TR-FR +768/NL tekrar doğrulandı. Öğrenci/e-posta genişliği son 1440/390 kontrolünde doğrulandı.

Browser plugin bulunmadığından kurulu Chrome/Playwright ve geçici gerçek Laravel testing HTTP kernel köprüsü kullanıldı. Dış ağ engelliydi. Örnek öğrenci/hedef/çalışma/not/iletişim kayıtları çıkışta rollback edildi. Fiziksel cihaz/Safari veya gerçek SMTP/push teslim testi değildir.

## 7. Önceki dönem filtresi kontrolüne yanıt

Önceki 48 filtre/durum karşılaştırmasında seçilen dönem ID’sine göre sorgular tutarlıydı. İki ayrı bulgu vardı: “Dönem yok” NULL kayıtları kapsamıyor; “Tümü” seçimi yenilemede varsayılana dönüyor. Yerel mevcut dönem tarihi bitmiş olduğundan yeni sonuç mevcut fallback ile Dönem2’ye atanıyordu. Bu geliştirmede sonuç controller’ları, dönem verileri veya bu iş kuralları değiştirilmedi.

## 8. Değişen dosyalar ve sürüm

- `app/Actions/Study/AuthorizeStudyCommunication.php`: yeni kaynak izni.
- `app/Exports/StudyTableExport.php`: XLSX, güvenli metin/sayı hücreleri.
- `app/Helpers/StudyPanelQuery.php`: liste/export/iletişim için ortak filtre/sort sorgusu.
- `app/Helpers/StudyDetailTable.php`: mevcut rapor filtreleriyle sayfasız export yolu.
- `app/Http/Controllers/Backend/StudyController.php`, `routes/backend.php`: export ve compose girişleri.
- `app/Livewire/Backend/Study/StudyCommunication.php`: kaynak kapsamı kilitli iletişim alt bileşeni.
- `app/Livewire/Backend/Communications/CommunicationForm.php`: mevcut kuralları koruyan protected kontrol hook’u.
- `resources/views/backend/study/{index,show,compose}.blade.php`: araçlar, durum filtresi, tek kişi işlemleri.
- `resources/views/livewire/backend/study/detail-table.blade.php`: tüm detay tablolarında filtre/dil koruyan dışa aktarma.
- `resources/views/livewire/backend/communications/communication-form.blade.php`: study bağlamı için kaynak özeti; mevcut genel seçim ekranı korunur.
- `resources/views/backend/roles/form.blade.php`: yeni izin etiketi.
- `public/backend/assets/server-table.js`: export ile aynı parametre eşlemesini kullanan query-link desteği.
- `public/backend/assets/study-panel.css`: yeni composer responsive başlığı ve okunur öğrenci kolonu.
- `lang/{tr,nl,en,fr}/study.php`: 13 yeni metin.
- `database/migrations/2026_10_26_130000_grant_study_communication_permission.php`: onaylı, iki yerelde uygulandı.
- `tests/Feature/StudyActionsTest.php`: export ve iletişim sınırları.
- `public/pwa-sw.js`: **1.1.37**; ignored yerel `.env APP_VERSION` aynı sürüm; tırnaklı kalan eski yerel satır son kontrolde yalnız bu alan için eşitlendi, config cache yok.
- `AGENTS.md`, `README.md`, `Codex/index.html`, bu belge: teslim kaydı.

Önceden değişmiş `.DS_Store`, silinmiş `Codex/Doge.html`, yeni `Codex/docs.html` ve OneSignal yükleme klasörü commit dışında tutulur.

## 9. Elle kontrol

1. Panel → Çalışma Takibi: Ara kutusuna e-posta girin; hafta/durum/aktif öğrenci filtresini uygulayın.
2. Excel’e aktar: dosyada yalnız ekrandaki sayfayı değil bütün eşleşen öğrencileri görün.
3. Filtrelenenlere e-posta veya bildirim: alıcı sayısı/filtre özeti → mesaj → ön izleme. Gerçek işlem istenmiyorsa son Gönder yerine dönüş/iptal kullanın.
4. Öğrenci detayı: iki iletişim düğmesinin yalnız bu öğrenciyi hazırladığını kontrol edin.
5. Her detay tablosunda arama/filtre ardından Excel’i açın. Öğretmen hesabında dışa aktarma açık, yeni gönderim izni verilmemişse iletişim düğmeleri kapalıdır.

## 10. Ana liste başlığı — 26 Ekim 2026

Kullanıcı isteğiyle ana Çalışma Takibi listesindeki `study.panel_help` açıklaması kaldırıldı. Excel, filtrelenenlere e-posta ve bildirim düğmeleri mevcut OneUI `block-header` / `block-options` alanında sağ üste taşındı. Dar ekranda düğmeler sağa hizalı olarak sarılır.

`data-server-table-page` kapsamı dış block’a taşındı: başlıktaki düğmeler ve filtre/tablo aynı kapsamda kaldığı için uygulanmış hafta/durum/öğrenci durumu/arama parametreleri korunur. Görünürlük izinleri, route/JSON/sorgu ve gönderim kuralları değişmedi. Uygulamada yalnız `resources/views/backend/study/index.blade.php` değişti; yeni CSS/JS veya sürüm artışı yok,1.1.39 korunur. Bu kayıt, AGENTS/README/index güncellendi.

Dar test: `StudyActionsTest` içindeki öğretmen export/yetki, filtre eşliği ve yönetici iletişim hazırlama senaryoları **3 test / 39 doğrulama başarılı**. Kurulu Chrome/Playwright (Browser plugin not available) ile testing transaction’ında1440/TR,768/NL,390/TR-FR kontrol edildi: başlık sağ hizası, açıklamanın kalkması, düğmelerin taşmaması, filtreli üç URL ve gerçek XLSX indirme başarılı; sayfaJS0/5xx0/taşma0. `/tmp/eduan-study-header-1440-tr.png` ve `/tmp/eduan-study-header-390-fr.png` görsel kanıttır. Fixture rollback edildi; ana DB/migration/gerçek gönderim/canlı işlem yok. Genel suite veya build tekrarlanmadı.
