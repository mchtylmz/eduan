# Başlangıç ve kanıt sınırı

Bu rehber, 5 Ekim 2026 tarihinde kaynak kodu ve kilit dosyası üzerinden yapılan ilk faz incelemesidir. Kod değiştirilmedi. `AGENTS.md` uygulanacak kısa talimat; `Codex/index.html` dosya haritasıdır.

## Nasıl okunur?

1. Göreve başlamadan `../AGENTS.md` ve bu dosyayı oku.
2. İstenen özelliğe karşılık gelen rehberi aç; atıf verilen asıl kaynak dosyayı tekrar kontrol et.
3. `09-BULGULAR-VE-SORULAR.md` içindeki açık konuyu kesinleşmiş karar sayma.
4. Tarihli Docker, migration ve sürüm bulgularını güncel işlemden önce yeniden doğrula.

## İnceleme yöntemi

- `app/` içindeki 250 PHP dosyasının dosya envanteri, temsilci controller/Action/model/Livewire örnekleri, `routes/`, `database/migrations/`, `database/seeders/`, `config/`, `resources/views/`, birinci taraf `public/assets` ve `public/backend/assets` akışları incelendi. Büyük üçüncü taraf dağıtım dosyalarının ve bütün uygulama dosyalarının her satırı elle okunmadı; kullanım yerleri ve paket kimlikleri tarandı.
- `composer.json`, `composer.lock`, `package.json`, `.editorconfig`, `docker-compose.yml`, `phpunit.xml` ve mevcut README okundu. README çoğunlukla paket kurulum notları içeriyor; ürün sözleşmesi değildir.
- `docker compose ps` ilk kontrolde uygulamayı çalışır, MySQL'i healthy gösterdi. Konteyner içinde `php artisan migrate:status --no-ansi` bütün listelenen migration'ları `Ran` gösterdi. Sonraki Docker API erişimi sandbox tarafından reddedildi; bilgi şeması karşılaştırılamadı. `http://127.0.0.1/` ve `/sitemap.xml` bağlantısı kurulamadı. Bu yüzden ekran/HTTP ve canlı veritabanı davranışı doğrulanmadı.
- Yerel veritabanına yazılmadı; migration, seeder, test, build veya paket kurulumu çalıştırılmadı. Mevcut testler yalnızca iki Laravel örneği ve boş `TestCase`.

## Kesin bilgi ile hedefin ayrımı

**Koddan doğrulanan:** `laravel/framework` kilidi v11.44.7; Livewire 3.6.3; Rappasoft 3.5.6; ön yüzde test/`Exam` ve sınav/`Test` ters adlandırması; dinamik çeviri/ayar; Bootstrap 5; Sail.

**Kullanıcı hedefi:** Flemenkçe → İngilizce → Türkçe dil önceliği; yalın kod; güvenli ve testli geliştirme; çok hızlı/stabil ön yüz; iyi hızda panel; Google'a uygun SEO; ileride kontrollü yükseltme, iyileştirme ve paket sadeleştirmesi.

**Henüz kanıtlanmayan:** üretimdeki Laravel sürümü ve şema, aktif `defaultLocale`, tam ekran performansı, PWA çevrimdışı davranışı, tüm izinlerin uçtan uca tutarlılığı ve paketlerin hangisinin kaldırılabileceği.

## 5 Ekim 2026 kullanıcı kararı ve Git sınırı

- Proje sahibi yerel veritabanını incelemeden önce `migrate:fresh` ve seed ile yeniden kurduğunu bildirdi. Bu işlem bu incelemede yapılmadı; veri kaynağı kullanıcı beyanıdır.
- Geliştirme öncesi Git kaydı `2b31f4f5` (`yeni güncelleme öncesi 05 10`, 05.10.2026 08:29:48 +03:00). Bu commit'e kadar olan proje kaynakları mevcut başlangıç durumu kabul edilir; bu rehber ve sonraki kod/veri aktarımı çalışmaları commit sonrasıdır. Commit'in dosya dökümünü canlı şemanın kanıtı sayma.
- Canlı migration tablosunu kullanıcı daha sonra gönderecek. Yerel ile canlı migration/kolon farkı karşılaştırılmadan yeni migration, şema taşıması veya isim değiştirme kararı verilmez.
- Flemenkçe kodu ve varsayılan dil `nl` olarak onaylandı. İlk düzeltme paketinde kaynak varsayılanları `nl` yapıldı; ortam DB ayarı ve `.env` değişmedi.
- Kullanıcı yalnız ayrı `testing` veritabanını test için hazırlama/onaylama yetkisi verdi; ana yerel ve canlı veritabanına test yazma yetkisi yok. Bulguların kod düzeltmeleri ve `public/systeminfo.php` kaldırılması için önce somut plan gösterilecek.

## 5 Ekim 2026 test zemini doğrulaması

Ayrı MySQL `testing` veritabanı `db:show` ile sıfır tablolu olarak doğrulandı; kullanıcı onayıyla yalnız bu veritabanına 36 migration uygulandı. Ardından mevcut iki örnek test 2/2 geçti (2 assertion). Bu, ürün davranışının kapsandığı anlamına gelmez. Docker içinden `/systeminfo.php` HTTP 200 verdi; `Codex/10-BULGU-VE-TEST-PLANI.md` kaldırma adımını tarif eder.

## İlk düzeltme paketinden sonra

Yukarıdaki ilk tarama kayıtları başlangıç durumudur. Plan kullanıcıya gösterildikten sonra bilgi dosyası kaldırıldı (yerel HTTP 404), işlem izinleri ve sınav akışı düzeltildi; kaynak dil varsayılanları `nl` oldu. Güncel uygulama, test kanıtları ve açık worker sorusu `11-ILK-DUZELTMELER-VE-SINAV-AKISI.md` içindedir. Canlı migration listesi bekleniyor; ana yerel/canlı DB'ye bu paket için yazılmadı.
