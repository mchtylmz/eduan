# Çalışma takibi öğrenci ekranı ve hesap menüsü

**Güncel devam revizyonu bölüm5:** hedef/çalışma ayrı sağ panellere alındı; grafik, outline gezinme, tarih ve sayfalama dili iyileştirildi. İlk sürüm kayıtları aşağıda tarihsel olarak korunur.

25 Ekim 2026. Kullanıcının ekran revizyonu: lise öğrencisinin haftayı, hedefi ve çalışma girişini kolayca bulması. Bu kayıt önceki çalışma takibi raporlarının sunum kararlarını günceller; veri/hesaplama ve yetki kuralları aynı kalır.

## 1. Hesap menüsü

- Ortak üst menüde Hesap bilgilerim bağlantısının hemen ardından, yalnız `study:access` izni varsa Çalışma Takibim görünür.
- Mevcut Bootstrap dropdown tıklamayla açılır; hover ile kendiliğinden açılmaz. Enter, Escape ve dışarı tıklama çalışır.
- Kısa kullanıcı kimliği, ikonlar, seçili sayfa ve ayrı çıkış satırı bulunur. Mobilde düğmenin erişilebilir adı korunur; menü ekran genişliğini aşmaz.
- Hesap düğmesi `header-account-toggle` kimliğiyle dil seçicisinin eski kimliğinden ayrılır. Yönetim paneli ve lig bağlantılarının mevcut izin/ayar koşulları korunur.

## 2. Öğrencinin çalışma alanı

- Beyaz arka plan, mevcut site fontları, Bootstrap grid ve proje ana rengi kullanılır. Genel site veya panel renkleri değiştirilmez.
- Üstte açık “Hesabıma dön” bağlantısı, seçilen hafta ve ilgili tarih aralığı vardır. Ay adları TR/NL/EN/FR diline göre yazılır.
- Önceki/sonraki bağlantıları gidecekleri tarih aralığını gösterir. Geçmiş haftadan güncel haftaya giden bağlantı açıkça “Bu haftaya dön” der. Gelecek hafta kapalıdır ve nedenini kısa açıklar.
- “Dönem sınırları haftayı bölmez” gibi teknik açıklamalar öğrenci ekranından çıkarılır; “Her çalışma haftası cumartesi başlar, cuma günü biter” açıklaması kullanılır.
- Dört bölüm: Haftalık özet, Geçmişim, Konu analizi, Öğretmen notları. İlk görünümde bütün geçmiş/analiz tabloları uzayıp formları aşağıya itmez.
- Haftalık özette soru/sayfa/saat için hedef ve gerçekleşen aynı ölçekte iki çubukla gösterilir. Gerçek oran %100'ü aşabilir; boş hedef hesaplamaya katılmaz. Değerler erişilebilir grafik açıklamasında da bulunur.
- Hedef ve çalışma formları özetin hemen altındadır. Üst kısayollar doğrudan ilgili forma götürür; mobilde kaydırırken görünür kalır. Hedef girmemek çalışma girişini engelleyen yeni bir iş kuralı oluşturmaz.
- Günlük toplamlar ve tekil kayıtlar açılır ayrıntılardadır. Kayıt düzenleme mevcut forma götürür; işlem sonrası rapor mevcut `study-changed` akışıyla yenilenir.
- Geçmişte eğitim dönemi adıyla birlikte başlangıç/bitiş tarihleri de görünür. Bootstrap sayfalama korunur; sayfa değişiminde ilgili tabloya kaydırılır ve renkler proje ana rengine uyar.
- Öğrencinin görebileceği not yoksa açık boş durum vardır. Gizli öğretmen notları gösterilmez.

## 3. Doğrulama

İlgili PHP kapsamı: `StudyTrackingFormTest`, `StudyAnalyticsTest`, `StudyPanelTest`, `FrontendAccountPresentationTest`, `PwaReleaseTest`.

**Son temiz koşu: 69 test / 575 doğrulama başarılı.** Ortak panel görünümü, sahiplik, kapalı hafta/gelecek tarih, tekrar kayıt, düzenleme ve kişisel rapor kuralları bu ilgili testlerde korunur. Yeni sunum testi dört dilde menü sırası, haftanın değişmesi, anlamlı açıklamalar ve ilk HTML formlarını denetler. Eski sayısal tarih beklentisi yerelleştirilmiş ay adına uyarlandı. İlk geliştirme sırasında yakalanan Blade inline/blok `@php` derleme uyuşmazlığı giderildi; son koşuda yoktur. Yeni testteki query string birleştirme hatası da düzeltildi.

Beş değişen PHP dosyası için Pint başarılı. Dört `study.php` dosyasında132 eş anahtar/boş değer yok; yeni16 anahtar her dilde dolu. CSS asset değişimi nedeniyle yerel ignored `.env` APP_VERSION ile kök PWA worker birlikte **1.1.30** oldu.

### Tarayıcı kabulü

Browser plugin mevcut değil; geçici Playwright + kurulu Chrome kullanıldı. Gerçek Blade/Livewire/HTTP kernel akışı `http://localhost/account/study` üzerinde yalnız ayrı `testing` DB outer transaction fixture'larıyla çalıştırıldı. Dış HTTP/SMTP/queue gönderimi engellendi; kayıtlar işlem sonunda geri alındı.

| Akış | Sonuç |
|---|---|
| Sayfa kimliği, ilk HTML, beyaz yüzey | Başarılı |
| Menü tık/Enter/Escape/dışarı tıklama ve bağlantı sırası | Başarılı |
| Önceki haftaya git, tarih değişimi, güncel haftaya dön | Başarılı |
| Hedef150/80/1 kaydet, geçersiz çalışma reddi,180/60/1.5 ekle | Başarılı |
| %120 soru grafiği, reload sonrası hedef/kayıt korunması | Başarılı |
| Kaydı aç/düzenle120, grafiğin yenilenmesi | Başarılı |
| Tarihli dönem seçeneği, geçmiş ikinci sayfa ve sekme korunması | Başarılı |
| Konu analizi, yalnız görünür öğretmen notu | Başarılı |
| NL1440/768/390; FR390; TR390 ve dört sekme | Başarılı |
| Mobil hızlı çalışma ekleme ile forma ulaşma | Başarılı |
| Son mobil NL/FR kısa kontrol: eşit düğme genişliği, benzersiz menü kimliği | Başarılı |
| Son koşularda JS hatası / HTTP5xx / yatay taşma | 0 / 0 / 0 |

Kanıtlar `/tmp/eduan-study-redesign-desktop.png`, `/tmp/eduan-study-redesign-390-nl.png`, `/tmp/eduan-study-menu-390-fr.png` ve son kısayol düzeni `/tmp/eduan-study-shortcuts-390-nl.png`, `/tmp/eduan-study-shortcuts-390-fr.png`. Tam proje testleri yeniden çalıştırılmadı. Gerçek telefon/Safari veya production kabulü iddiası yoktur.

## 4. Değişen dosyalar ve kontrol yolu

- `resources/views/frontend/layouts/section/header-auth.blade.php`: ortak hesap menüsü.
- `resources/views/frontend/study/index.blade.php`: hafta gezinmesi, bölümler ve kısayollar.
- `resources/views/livewire/frontend/study/study-report.blade.php`: haftalık grafik ve bölümlere ayrılan rapor.
- `resources/views/livewire/frontend/study/study-form.blade.php`: form sunumu ve açılır günlük/kayıt ayrıntıları.
- `resources/views/livewire/frontend/study/visible-study-notes.blade.php`: not boş durumu.
- `public/assets/app.css`: yalnız hesap menüsü ve `.study-page` kapsamındaki stiller.
- `public/pwa-sw.js`: asset sürümü1.1.30.
- `lang/tr/study.php`, `lang/nl/study.php`, `lang/en/study.php`, `lang/fr/study.php`: öğrenciye uygun yeni metinler.
- `tests/Feature/StudyTrackingFormTest.php`: yeni sunum sözleşmesi ve tarih beklentisi.
- Bu rapor, AGENTS.md, README.md ve rehber indeksi: güncel kullanım/kabul kaydı.

Elle kontrol: Üye girişi → üst Hesabım → Hesap bilgilerimin altındaki Çalışma Takibim. İlk sekmede hedef/çalışma formları; Geçmişim'de tarihli dönem ve sayfalama; Konu analizi ve Öğretmen notları ayrı açılır.

Yeni migration, DB ayarı/aktivasyonu, mevcut hedef/çalışma/not değişikliği, dış servis gönderimi veya canlı aktarım yapılmadı. Hesaplama kaynakları, görevler, haftalık kurallar ve izin action'ları değiştirilmedi. Kullanıcının `.DS_Store`, wiki dosya taşıması ve özgün OneSignal klasörü bu commit kapsamı dışındadır.

## 5. İkinci ekran revizyonu — sağ paneller ve grafikler

25 Ekim 2026, kullanıcının ek ekran görüntüleriyle istediği devam düzenlemesi. Bu bölüm, yukarıdaki ilk sürümün sayfa içi form ve kaydırma davranışını günceller.

### Güncel kullanım

- Hesabıma dön, önceki/sonraki hafta, güncel haftaya dönüş ve haftalık hedef düğmeleri outline görünümündedir. Pasif gelecek hafta hover sırasında çizilmez; hedef ve çalışma ayrı ikonlu düğmelerdir.
- Hedef ve çalışma için **iki ayrı Bootstrap offcanvas** sağdan açılır. Formlar artık raporun altında yer kaplamaz. Günlük toplamlar ve çalışma kayıtları haftalık özetin hemen altındadır.
- Yeni giriş taslağı X/Escape ile kapatıldığında korunur. Kayıt düzenlemeyi kapatıp “Çalışma ekle” seçmek yeni kayıt moduna geçer; eski kaydı yanlışlıkla güncellemez. Mevcut kaydı Düzenle aynı çalışma panelinde açar.
- Hatalar panelin içinde kalır; başarısız işlem paneli kapatmaz. Başarılı hedef/kayıt işlemi mevcut rapor yenilemesini tetikler ve ilgili panel kapanır. Açılışta ilk uygun alana, kapanışta açan düğmeye odak döner. Hızlı iki panel isteğinde yalnız son istenen panel açık kalır.
- Sayısal alanlar ve tarih alanında çevrilebilir placeholder vardır. Native tarih kontrolü dolu değeri kendi tarayıcı biçiminde gösterir; placeholder görünümü tarayıcı desteğine bağlıdır. Kaydet/ekle/düzenle/kaldır/vazgeç ve sekme düğmeleri ikonludur.
- Hafta alanı ve rapor/analiz/not başlıkları hafif tema zeminlidir. Sekmeler çerçeveli, mobilde iki sütundur. Öğretmen notu boş durumu da aynı başlık düzenini kullanır.
- Geçmişte üç ölçü için dörder haftalık hedef/gerçekleşen **eşli sütun grafikleri** ve açıklama renkleri vardır. Gerçek miktarlar ve %100'ü aşan oran korunur; yeni hesaplama kuralı eklenmez. Tablo başlıkları, satırlar ve durum metinleri sade biçimde belirginleştirilir; normal Bootstrap sayfalama sürer.
- Ortak yayınlanmış Livewire Bootstrap sayfalama şablonu `pagination.summary` kullanır. TR/NL/EN/FR'de sayı aralığı ve toplam ilgili dilde gelir; İngilizce JSON parça anahtarlarına bağımlılık kaldırıldı. Mobilde Bootstrap'ın mevcut kısa önceki/sonraki düzeni korunur.
- Çalışma tarihleri artık `StudyCalendar::label` ile **takvim günü korunarak** biçimlenir. Tarih/saat dönüşümü nedeniyle önceki güne kayma engellenir; hafta sınırı veya kayıt tarihi değiştirilmez. İstanbul/Brüksel farkı ve dört dil testlidir.

### Dar test ve tarayıcı sonucu

İlgili birleşik kapsam: `StudyTrackingFormTest`, `StudyAnalyticsTest`, `StudyPanelTest`, `StudyCalendarTest`, `PwaReleaseTest`: **80 test / 488 doğrulama başarılı**. Son küçük görünüm/formatter düzenlemelerinden sonra yalnız üç yeni sözleşme testi tekrarlandı: **3 test / 106 doğrulama başarılı**; bunlar80'in içindedir,83 farklı test değildir. Yeni kapsam: taslak/yeniden giriş ayrımı, geçersiz panel türü, hata halinde kapanmama, başarılı panel olayları, takvim günü/ay adı ve dört dil sayfalama metni. Ortak panel, kişisel sahiplik ve kapalı hafta kuralları ilgili mevcut testlerde geçti.

Değişen PHP dosyalarında Pint kontrolü; test dosyasındaki import biçimi Pint ile düzeltildi. Yeni JS syntax ve `git diff --check` başarılı. Dört dilde137 eş study anahtarı ve3 eş pagination anahtarı var; boş değer yok. APP_VERSION ve kök PWA worker **1.1.31**.

Browser plugin mevcut değil; geçici Playwright/Chrome, gerçek localhost Blade/Livewire akışı ve yalnız transaction ile geri alınan testing fixture'ları kullanıldı. Dış servisler engellidir. Ana DB üzerinde işlem yapılmadı.

| Kontrol | Sonuç |
|---|---|
| Outline hover/pasif hafta, sayfa içi formun kaldırılması | Başarılı |
| Hedef paneli ilk alan odağı/placeholder/kaydet/kapanma/odak dönüşü | Başarılı |
| Yeni çalışma taslağı, negatif giriş hatası, panelin açık kalması | Başarılı |
| Hedef150 ve çalışma180 ile %120; reload ve rapor güncellemesi | Başarılı |
| Düzenle → panel, yeni girişe geçiş,120 olarak güncelleme | Başarılı |
| Açık kayıt ayrıntılarının Livewire güncellemesinde korunması | Başarılı |
| Dört hafta24 sütun, geçmiş ikinci sayfa, geçmişten panel aç/kapat | Başarılı |
| NL1440/768/390, FR390 ve TR390 düzen/odak/Escape | Başarılı |
| Son ayrı390px kontrol: gerçek TR/FR/NL/EN HTML dili, ay, placeholder ve sayfalama çevirisi | Başarılı |
| Kapalı haftada hedef panelinin salt okunur olması | Başarılı |
| Hızlı hedef/çalışma tıklaması: tek panel, tek backdrop, doğru odak | Başarılı |
| Masaüstü Türkçe sayfalama özetinin görünür doğrulaması | Başarılı |
| Son koşularda JS hatası / HTTP5xx / yatay taşma | 0 / 0 / 0 |

Kanıtlar: `/tmp/eduan-study-drawer-target-desktop.png`, `/tmp/eduan-study-refine-history-desktop.png`, `/tmp/eduan-study-refine-final-desktop-tr.png`, `/tmp/eduan-study-refine-final-mobile-tr.png`, `/tmp/eduan-study-refine-final-drawer-tr.png`. İlk tarayıcı geçişinde bulunan, yeni girişe geçince kayıt ayrıntılarının kapanması `wire:ignore.self` ile düzeltildi ve aynı akış geçti. Son dil kontrolünde geçici testing fixture'ında dört dil etkinleştirilerek `html[lang]` ayrıca doğrulandı; önceki fallback görünümü gerçek Türkçe kabulü sayılmadı.

Genel proje suite'i/build tekrar edilmedi. Fiziksel telefon/Safari/üretim kontrolü bu revizyonun kanıtı değildir. Yeni migration veya ana ayar/veri değişikliği, dış servis gönderimi ve canlı aktarım yoktur.

### Bu revizyonun dosyaları

- `app/Helpers/StudyCalendar.php`: takvim günü için yerelleştirilmiş gösterim.
- `app/Livewire/Frontend/Study/StudyForm.php`: panel aç/kapat UI olayları; mevcut yazma action'ları aynı.
- `resources/views/frontend/study/index.blade.php`: outline gezinme ve panel düğmeleri.
- `resources/views/livewire/frontend/study/study-form.blade.php`: iki offcanvas, placeholder/ikon ve doğrudan erişilen ayrıntılar.
- `resources/views/livewire/frontend/study/study-report.blade.php`: sütun grafikleri, tarih etiketleri ve tablo sunumu.
- `resources/views/livewire/frontend/study/visible-study-notes.blade.php`: boş durumda ortak başlık.
- `resources/views/vendor/livewire/bootstrap.blade.php`: çevrilebilir tek sayfalama özeti.
- `public/assets/study-workspace.js` (yeni), `public/assets/app.css`, `public/pwa-sw.js`: panel yaşam döngüsü, stil ve asset sürümü.
- `lang/{tr,nl,en,fr}/study.php`, `lang/{tr,nl,en,fr}/pagination.php`: yeni metinler.
- `tests/Feature/StudyTrackingFormTest.php`: yeni panel/tarih/sayfalama testleri.
- Bu82 raporu, AGENTS.md, README.md ve rehber indeksi: güncel kullanım ve kabul kaydı.
