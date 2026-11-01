# 91 — Sonuç tablolarında varsayılan dönem: Tümü

1 Kasım 2026. Kullanıcı, dönem filtresi bulunan test/sınav sonuç tablolarının başlangıçta “Dönem yok” yerine **Tümü** açılmasını istedi. AGENTS.md, README, 03/16 standartları, 38 tablo standardı ve 84 bölüm7 incelendi. Mevcut ortak filtre bileşeni ve URL/Bootstrap Table akışı kullanıldı.

## Kapsam ve yapılan değişiklik

Tarama, Tümü/Dönem yok seçenekli dönem filtresinin iki ortak Blade görünümünde bulunduğunu gösterdi:

- `resources/views/livewire/backend/exams/results-server-table.blade.php`: Test Sonuçları.
- `resources/views/livewire/backend/tests/results-server-table.blade.php`: Sınav Sonuçları.

Her iki görünümde `activeSeason()->id ?? 0` varsayılanı kaldırıldı. Ortak `x-admin.filter-select` zaten boş değeri Tümü olarak sunduğu için bileşen, JavaScript ve controller değişikliği gerekmedi. Aynı görünümleri kullanan genel liste, tek kullanıcı ve tek test/sınav bağlamları da bu davranışı alır. URL'de açıkça seçilen dönem varsa mevcut mekanizma onu yüklemeye devam eder. Temizle Tümü'ne döner; Tümü seçildikten sonraki yenileme tekrar Dönem yok seçmez. Durum filtresinin Tamamlandı varsayılanı korunur.

Çalışma Takibi geçmişindeki dönem seçimi ayrıca tespit edildi: burada mevcut Tümü seçeneği yoktur ve seçim raporun tarih aralığını belirler. Bütün dönemleri birleştiren yeni rapor kapsamı eklenmeden önce kullanıcıya ayrı soru iletildi; bu iki satırlık düzeltmede çalışma raporu değiştirilmedi. Lig tarih aralıkları ve dönem düzenleme formları sonuç filtresi değildir.

“Dönem yok” seçeneğinin NULL kayıtlarını kapsamaması ayrı önceki bulgudur; bu istek yalnız varsayılanı değiştirdiği için sorgu anlamı değiştirilmedi.

## Dar doğrulama

- Yerel PHP **8.5.12** ile gerçek Blade görünümü ve gerçek ortak bileşen render edildi: **2 sonuç türü × 3 bağlam × 4 dil = 24 render kontrolü geçti**. İlk seçenek boş/Tümü; hiçbir dönem seçili zorlanmıyor; Tamamlandı varsayılanı korunuyor.
- Browser plugin mevcut değil; kurulu Playwright ve gerçek Chrome kullanıldı. Blade çıktısı, projenin gerçek jQuery/Bootstrap Table/server-table.js dosyaları ve sahte JSON yanıtlarıyla **56 etkileşim kontrolü geçti**: 2 tablo × 4 dil × 7 durum. İlk açılış/Tümü, dönem1 seçimi, dönem1 URL/yenileme, Tümü'ne dönme/URL, Tümü yenileme, açıkça Dönem yok seçimi ve Temizle doğrulandı. JS istisnası **0**.
- Masaüstü1440 ve mobil390 ekran görüntüleri `/private/tmp/eduan-season-default-{desktop,mobile}.png`; yalnız filtre/tablo fixture'ı, tam dashboard/auth/DB ekranı değildir.
- `git diff --check` başarılı. Yeni CSS/JS veya paket yok; APP_VERSION/root worker **1.1.42** korundu.
- İlk geçici render yardımcısında eksik files binding/route isim haritası, ilk Chrome açılışında sandbox engeli vardı; yalnız geçici test ortamı düzeltildi. Uygulama kodu bu test altyapısı sorunları için değiştirilmedi.
- Eduan Docker servisleri durmuş olduğundan DB/Livewire entegrasyon testleri çalıştırılmadı. Servis/port/DB/ayar/migration/dış servis değişikliği yapılmadı. Yeni kalıcı test veya genel suite/build çalıştırılmadı.

Ürün dosyaları yalnız yukarıdaki iki Blade görünümüdür. Bu rapor, 84 bölüm7, AGENTS.md, README ve Codex/index.html güncel karar/test kaydını taşır.
