# 66 — Genel test düzeltmeleri ve Imagick

7 Ekim 2026. Kullanıcı genel PHP testindeki yedi engelin giderilmesini, eksik Flemenkçe çevirilerin yalnız boş/missing alanlarda tamamlanmasını ve Imagick'in korunmasını istedi. Öğretmen rolüne iki sonuç görüntüleme izni için migration hazırlama ve yerelde uygulama açıkça onaylandı. Canlı uygulama yayını, commit ve kalıcı sunucu ayarı yapılmadı.

## 1. Önceki yedi engel

| Senaryo | Neden ve düzeltme |
| --- | --- |
| PopulatedDetailTest: seçili sınav sonuçları | Kaldırılmış Rappasoft builder() beklentisi mevcut Bootstrap Table JSON uç noktasına taşındı; seçili sınav kapsamı doğrulanır. |
| PackageRemovalTest: scheduler | Eski optimize_clear beklentisi onaylı cache_clear tanımına uyarlandı; görev çalıştırılmadı. |
| PanelPermissionMatrixTest: öğretmen test sonuçları | Testte öğretmen rolü ve exams:view izni eksikti; gerçek öğretmen rolü/migration kullanılır. |
| PanelPermissionMatrixTest: öğretmen sınav sonuçları | Testte öğretmen rolü ve tests:view izni eksikti; gerçek öğretmen rolü/migration kullanılır. |
| LeagueCalculationTest: sonuçsuz lig | Mevcut testing kayıtlarını sıfır sanan beklenti başlangıç adedinin değişmemesine uyarlandı. |
| TimedExamTest: tamamlanmış deneme | Genel lig satır sayısı yerine senaryodaki iki kullanıcının sonuçları denetlenir; devam eden deneme dışarıda kalır. |
| PanelUploadSecurityTest: filigran biçimi | Host PHP'de Imagick yoktu. Projenin çalışan Sail PHP8.5.8 ortamında Imagick mevcut; mevcut filigran kodu ve biçim testi başarılı. GD dönüşümü yapılmadı. |

İlk genel tekrar, yeni JSON upload etiketi ile mevcut upload.php grubunun çakışmasını ortaya çıkardı. JSON etiketi kaldırıldı; kullanıcı onayıyla yalnız bu tur eklenen locale=nl/key=upload/value=Uploaden kaydı ana ve testing DB'den silindi. Diğer çeviri kayıtları silinmedi.

AccessBoundaryTest dil dosyası üretiminde yalnız JSON yazımını taklit ediyordu; PHP grup yazımları da taklit edilerek genel testin gerçek dil dosyalarını değiştirmesi engellendi. Geçici biçim farkları değerler karşılaştırılarak geri alındı. Son genel koşudan önce/sonra tüm dil PHP/JSON dosyalarının SHA256 değerleri aynı.

## 2. Öğretmen izinleri

- Yeni 2026_10_07_160000_grant_teacher_result_view_permissions migration'ı öğretmen rollerini çevrilmiş ad/ID yerine mevcut user-type:teacher izniyle bulur.
- exams:view ve tests:view eksikse eklenir; mevcut ve diğer rol izinleri korunur. Tekrarlanabilir; izin cache'i hedefli temizlenir.
- Ana yerel öğretmen rolünde iki izin zaten vardı. Önceki iki hata test kurulumundaki eksik izinlerden kaynaklandı. Migration canlıdaki eksik atamalar için hazırdır; canlıda çalıştırılmadı.
- Kendi öğrenci kapsamı korunur; başkasının öğrencisinin sonuçlarına erişim açılmadı.
- Kullanıcı onayıyla yalnız bu iki yeni migration ana hypotenuse ve ayrı testing DB'de uygulandı. Başka pending migration/seed çalıştırılmadı.

## 3. Eksik NL çevirileri

- nl.json: 89 eksik metin; nl/validation.php: 130 alan adı; nl/auth.php: 14 eksik mesaj. Mevcut dolu dosya değerleri korunur.
- Yeni 2026_10_07_160100_fill_missing_dutch_translations migration'ı mevcut NL JSON/PHP dosyalarından eksik kayıtları ekler, yalnız null/boş değerleri doldurur. PHP grup adları JSON etiketi olarak eklenmez.
- Ana yerelde önceki **839 dolu çeviri aynı**; **491 yeni kayıt ve 1 boş alan** tamamlandı. NL toplam **1.331**, boş kayıt **0**. Mevcut dil kayıtları ve aktif/pasif durumları aynı; yeni dil oluşturulmadı.
- Canlıda dolu çeviriler korunur; yalnız eksik/boş alanlar tamamlanır. Rollback mevcut kullanıcı çevirilerini silmez.
- Scanner'ın bulduğu bütün statik kaynak anahtarları NL dosyalarında karşılık bulur. Dinamik çağrılar otomatik içerik keşfi kabulü değildir.

## 4. Imagick — salt okunur sunucu kanıtı

Sunucuda /usr/bin/php85: PHP8.5.11; imagick.so kurulu, varsayılan CLI yüklemesi kapalı. Yalnız tek işlem için -d extension=imagick.so ile gerçek Imagick3.7.0 yüklenip 2×2 PNG üretildi (127 byte). Sunucu dosyası/ayar/cron değiştirilmedi.

SSH kontrolü:

```bash
/usr/bin/php85 -d extension=imagick.so -r 'echo PHP_VERSION, PHP_EOL; echo phpversion("imagick"), PHP_EOL;'
```

Gelecekte PHP8.5 CLI/queue/Artisan komutlarında aynı -d extension=imagick.so seçeneği kullanılabilir. Kalıcı CLI otomatik yükleme gerekiyorsa SiteGround desteğiyle PHP8.5 CLI extension ayarı açılmalıdır. Web için Site Tools → Devs → PHP Manager → PHP Extensions altında Imagick açılır: [SiteGround resmi yönergesi](https://www.siteground.com/kb/enable-imagick-imagemagick/). Web PHP sürümü/ayarları bu tur değiştirilmedi veya yeniden doğrulanmadı.

### 7 Ekim devamı — açık CLI parametresiyle işletim

Kullanıcı destek sürecini beklemek yerine Imagick'i açık CLI parametresiyle yüklemeyi seçti. **Sonraki zamanlama kararı: bu cron düzenlemesi yalnız PHP 8.5 projesi canlıya alınırken yapılacak; şu anda mevcut canlı scheduler/worker değiştirilmeyecek.** Yayın sırasında kullanıcıya bu bekleyen madde hatırlatılacak. `scripts/siteground-cli-cron.example` Site Tools Command alanına girilecek iki satırı içerir: scheduler ve mevcut `queue:work --stop-when-empty` worker'ı `/usr/bin/php85 -d extension=imagick.so` ile başlar. Mevcut görevlerin zamanı, queue/timeout/retry seçenekleri ve yönlendirmeleri korunur; yeni kopya cron eklenmez. PHP web sürümü ve php.ini değiştirilmez. R2 Bash yedekleri PHP çalıştırmadığından aynı parametreyi gerektirmez.

Yerel Laravel13 kaynağı `Schedule::command` için yeni PHP subprocess üretir (`Application::formatCommandString`, `php_binary`); dış `schedule:run` işlemine verilen -d parametresi otomatik aktarılmaz. Mevcut scheduler yalnız kota/sezon job'ları, cache temizliği ve lig hesaplama komutunu içerir; Imagick kullanan filigran komutu scheduler'a bağlı değildir. Görsel işleyen queue worker ayrı -d parametresiyle çalıştırılır. Gelecekte filigran komutu scheduler'a eklenirse o alt süreç de aynı parametreyle başlatılmalıdır.

Bu revizyonda yalnız cron örneği ve ilgili dokümanlar değişti. Örnek `bash -n` syntax kontrolünden geçti; Artisan scheduler/worker/filigran komutları çalıştırılmadı. Sunucuya dosya yükleme, cron değiştirme, veritabanı/görsel yazımı veya genel test yapılmadı. Kurulumun canlıda tamamlandığı iddiası değildir.

## 5. Son doğrulama

- **Genel PHP: 453 test / 3.210 doğrulama, tamamı başarılı; failure/error/skipped yok.** PHP8.5.8 / PHPUnit13.4.1 / Laravel13.34, çalışan Sail ortamı ve ayrı testing DB. Süre 81,882 saniye.
- PwaSafetyTest.cjs + LatexTableFormatterTest.cjs: **13 JavaScript testi başarılı**.
- İlgili scoped Pint/syntax ve whitespace kontrolü başarılı. Genel suite son düzeltmeden sonra bir kez başarıyla tamamlandı; ek tekrar yapılmadı.
- Kullanıcı gerçek PWA offline/güncelleme doğrulamasını şimdilik kabul etti. Bu, bu tur yapılmış yeni cihaz/tarayıcı testi değildir.
- Ana/veri dosyası korunumu ayrıca kontrol edildi; Docker servisleri durdurulmadı, DB reset/temizliği yapılmadı. Canlı migration, uygulama deploy, commit, PHP web geçişi ve worker kurulumu bekler. APP_VERSION/worker1.1.19 korunur; CSS/JS yok.

## 6. Değişen işlevsel dosyalar

- database/migrations/2026_10_07_160000_grant_teacher_result_view_permissions.php
- database/migrations/2026_10_07_160100_fill_missing_dutch_translations.php
- lang/nl.json; lang/nl/auth.php; lang/nl/validation.php
- tests/Feature/PackageRemovalTest.php; PopulatedDetailTest.php; LeagueCalculationTest.php; TimedExamTest.php; PanelPermissionMatrixTest.php; AccessBoundaryTest.php; ReleaseRepairTest.php
- Bu kayıt, AGENTS.md ve Codex/index.html.
