# Çalışma takibi öğrenci ekranı ve hesap menüsü

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
