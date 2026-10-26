# 83 — Çalışma takibi panel detayı ve tablolar

## 1. İstek ve uygulanan düzen

26 Ekim 2026. Panel → Çalışma Takibi listesinde öğrencinin adının altında e-posta gösterilir; mevcut arama e-postayı da kapsar. Aynı isimli öğrenciler böylece ayırt edilebilir.

Öğrenci detayının uzun, alt alta form ve rapor düzeni yerine beş GET sekmesi vardır:

| Sekme | İçerik |
|---|---|
| Haftalık özet | Üç ölçünün hedef/gerçekleşen halkaları ve yedi günlük matris. Masaüstünde yan yana, mobilde grafik önce. |
| Çalışma geçmişi | Son dört hafta hedef/gerçekleşen gösterimi; adı ve açık tarih aralığıyla eğitim dönemi filtresi; geçmiş haftalar tablosu. |
| Çalışma kayıtları | Üstte hafta toplamı; günlük manuel/test/sınav dökümü ve ayrı manuel kayıt tablosu. |
| Konu analizi | Ders, konu, doğru, yanlış, doğruluk ve durum; mevcut anonim karşılaştırma. |
| Öğretmen notları | Yazan kişi, not, öğrenci görünürlüğü, tarih ve izinli düzenleme işlemi. Uzun notların tamamı satır içinde açılabilir. |

Öğrenci adı/e-postası, öğrenci listesine dönüş, okunur hafta aralığı ve hafta gezinmesi üstte yer alır. Yetkili personel için **Hedefi düzenle / Çalışma ekle / Not ekle** düğmeleri her sekmede görünürdür. Formlar ayrı sağ panellerdedir; genişlik %100, azami 640px; tarih alanı mevcut dört dilli Flatpickr/ISO sözleşmesini kullanır.

Seçilen sekme `?tab=overview|history|activity|analysis|notes` ile açılır. Örnek: `/admin/study/students/{id}?tab=notes`. Yenileme ve doğrudan bağlantı aynı bölümü açar; hafta bağlantıları sekme, dil ve dönem bağlamını korur. Geçmiş tablosundaki görüntüleme bağlantısı ilgili hafta için özeti açar ve dönem/dili taşır.

## 2. Proje standardı

- Zorunlu başlangıç okumaları: AGENTS, README, 16 proje standartları, 03 kod rehberi, 74 panel/not rehberi ve gerçek yakın tablo bileşenleri.
- Mevcut panel OneUI/Bootstrap bileşenleri, ortak `x-admin.server-table`, filtre ve tablo mesajları kullanılır. Ön yüzün tüm CSS dosyası panele yüklenmez.
- Geçmiş, konu, günlük toplam, manuel kayıt ve not listeleri **Bootstrap Table + özel sunucu JSON** kullanır. Varsayılan 15 satır; arama, sıralama, filtre, yenileme ve sayfalama tam sayfa GET yapmaz.
- Endpoint: `admin.study.detail-data`, `GET /admin/study/students/{user}/data?table=history|topics|days|entries|notes`.
- Manuel kayıt/not listeleri SQL count/sort/offset/limit ile sayfalanır. Takvim haftaları ve günlük toplamlar mevcut analizden elde edilen sınırlı rapor satırlarıdır; mevcut konu SQL aggregate sonucu ile birlikte JSON helper’da filtrelenip sıralanır ve istenen sayfa kesilir. Hesaplama sorgusu yeniden yazılmaz.
- Her tablo kendi kökünü kullanır; tablo işlemleri sayfanın GET sekmesini değiştirmez. Mutation sonrası ilgili tablo yenilenir; mevcut hedefli Livewire rapor yenilemesi korunur.
- Dört dilde dokuz yeni kaynak anahtarı vardır. Başlıklar/dönem tarihleri ve Bootstrap Table sayfalama metinleri TR/NL/EN/FR’ye uygundur.
- Açık/koyu tema ve 1440/768/390px düzenleri kontrol edildi. OneUI koyu modu sınıf tabanlı olduğu için yeni bileşenlerin tema değişkenleri yalnız `.study-panel` içinde eşlenir.

## 3. Korunan iş kuralları

- Yönetici/öğretmen izinleri ve öğretmenin güncel bağlı öğrenci kapsamı her HTTP/JSON/Livewire işleminde doğrulanır. Personel hesabı öğrenci gibi açılmaz.
- Çalışma miktarı, otomatik cevap sayımı, hedef yüzdeleri, hafta/dönem kuralları ve karşılaştırma hesapları değiştirilmez.
- Yetkisiz düzenleme düğmesi gösterilmez; yazma işlemi ayrıca mevcut Action/Locked sahiplik doğrulamasından geçer. İlişkisi kaldırılan öğretmenin eski açık bileşeni erişimi sürdüremez.
- Not varsayılan olarak öğrenciye gizlidir. Bağlı öğretmenler okuyabilir; yazar kendi notunu, yönetici tüm notları düzenleyebilir/kaldırabilir.
- UUID, stale conflict, transaction, audit ve mevcut değişiklik bildirim sözleşmeleri korunur. Not için yeni bildirim eklenmez.
- JSON cevapları `private, no-store`; öğrenci/e-posta/not metinleri HTML olarak çalıştırılmaz. Öğrenci kendi ön yüzündeki mevcut gizlilik kurallarıyla devam eder.

## 4. Test kanıtı

- İlgili birleşik PHP koşusu: `StudyPanelTest`, `StudyAnalyticsTest`, `StudyTrackingFormTest`, `PwaReleaseTest`: **73 test / 744 doğrulama başarılı**. Genel proje suite’i çalıştırılmadı.
- İlk panel koşusunda iki yeni fixture hatası (eksik recorded_by_id ve fillable olmayan created_at) düzeltildi; yukarıdaki birleşik koşu bu düzeltmelerden sonradır.
- Son dönem/dil bağlantısı düzeltmesi yalnız ilgili history-context testiyle ayrıca doğrulandı; bu senaryo 73 testin alt kümesidir.
- `StudyPanelFormatterTest.cjs`: **12 doğrulama**; isim/e-posta/not/işlem etiketinde HTML kaçışları ve düzenleme düğmesi sınırları.
- Yeni JS syntax, dört dil anahtar eşliği/dolu değerleri ve değişen PHP kaynakları kontrol edildi. 12 uygulama/test/dil dosyası Pint kontrolünden geçti. `routes/backend.php` eski biçim uyarıları HEAD kopyasında da aynıdır; route dosyasının tamamı kapsam dışı biçimlendirilmedi.
- Kurulu gerçek Chrome + Playwright, testing DB/gerçek Laravel HTTP kernel: liste e-postası; hedef/çalışma/not kaydet-düzenle; not kaldırma onayı; 15+2 kayıt sayfalama; tarih sınırı; geçmiş filtre/yenileme; konu doğruluğu; not HTML kaçışı ve dört dil tablo metinleri.
- Sekmeler gerçek GET’tir; tabloların sayfalama/filtre/yenilemesi document navigation üretmez. 1440/TR, 768/NL, 390/TR-FR-EN kontrolünde yatay belge taşması yok; dört dil sayfalama özeti doğrulandı. JS hata 0, HTTP 5xx 0.
- Koyu tema görsel kontrolünde bulunan yeni bileşen kontrastı düzeltildi; son dar Chrome kontrolünde koyu zemin/metin ve analiz görünümü yeniden doğrulandı.
- Tarayıcı örnekleri dış transaction içinde yalnız **testing** DB’de tutuldu ve çıkışta rollback edildi; Http stray requests engelli, Queue/Mail fake. Ana DB yazımı, migration, gerçek bildirim/e-posta veya canlı deploy yok. Fiziksel mobil/Safari testi değildir.

## 5. Değişen dosyalar

- `app/Helpers/StudyPanelQuery.php`: e-posta kolonu.
- `app/Helpers/StudyDetailTable.php`: yeni yetkili JSON rapor/list helper.
- `app/Http/Controllers/Backend/StudyController.php`, `routes/backend.php`: sekme doğrulaması, e-posta arama/çıktı ve detay veri rotası.
- `app/Livewire/Backend/Study/{StudentReport,StudyEditor,StudyNotes}.php`: ayrı panel görünümleri ve editör olayları; eski not sayfalaması kaldırıldı.
- `app/Livewire/Frontend/Study/StudyForm.php`: backend editörünün kullanılmayan listeleri tekrar sorgulamamasını sağlayan varsayılan açık sunum hook’u; ön yüz davranışı aynı.
- `resources/views/backend/study/{index,show}.blade.php`: öğrenci kimliği ve sekmeli panel kabuğu.
- `resources/views/livewire/backend/study/{student-report,detail-table,study-editor,study-notes}.blade.php`: rapor, ortak tablolar ve sağ paneller.
- `resources/views/livewire/frontend/study/weekly-evaluation.blade.php`: panel için isteğe bağlı başlık/açıklama; ön yüz varsayılanları aynı.
- `public/backend/assets/study-panel.{css,js}`: yalnız bu ekranın yerleşim, formatlayıcı ve etkileşimleri.
- `lang/{tr,nl,en,fr}/study.php`: yeni panel metinleri.
- `tests/Feature/StudyPanelTest.php`, `tests/Frontend/StudyPanelFormatterTest.cjs`: yeni UI/JSON sözleşmesi ve regresyonlar.
- `public/pwa-sw.js`: asset sürümü **1.1.36**; ignored yerel `.env APP_VERSION` aynı sürüme eşlendi.
- `AGENTS.md`, `README.md`, `Codex/index.html`, bu rehber: güncel kayıt.

## 6. Elle kontrol

1. Panel → Çalışma Takibi: öğrencinin adının altında e-postayı görün; e-posta ile arayın.
2. Görüntüle: beş sekmeyi ve üstteki üç işlemi kontrol edin. Sekmedeyken yenileyin veya URL’yi kopyalayın.
3. Çalışma kayıtları/Geçmiş/Notlar: arama, sayfalama ve yenilemede sayfanın yeniden açılmadığını görün.
4. Sağ panelde hedef/çalışma/not kaydedin; tablo veya özette yeni değeri görün. Not görünürlüğü varsayılan gizlidir.
5. Öğretmen hesabıyla yalnız bağlı öğrencileri, diğer öğretmenin notunda yalnız okuma hakkını kontrol edin.

Önceden değişik `.DS_Store`, silinmiş `Codex/Doge.html`, yeni `Codex/docs.html` ve yüklenmiş OneSignal klasörü bu geliştirme/commit kapsamına alınmaz.
