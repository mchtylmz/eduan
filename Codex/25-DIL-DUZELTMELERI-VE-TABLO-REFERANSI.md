# Dil düzeltmeleri ve Lara Football tablo doğrulaması

5 Ekim 2026. Kullanıcı B23-01/B23-02 düzeltmelerini onayladı. Sonraki açık soruya verdiği yanıt: **tarih seçiciler panelin arayüz dilini izler**, düzenlenen içeriğin dilini izlemez. Framework/Composer/veritabanı şeması değiştirilmedi.

## 1. İngilizce 404 düzeltmesi

- Backend jQuery Validation İngilizce metinlerini kendi ana dosyasından kullanır; olmayan `messages_en.min.js` istenmez.
- Backend ve frontend Flatpickr İngilizce yerleşik dilini kullanır; olmayan `l10n/en.js` istenmez.
- Bootstrap Select İngilizce dosya adı `defaults-en_US.min.js` olarak eşlendi; `defaults-en_EN.min.js` kaldırıldı.
- NL/TR yerel validation, Flatpickr ve Bootstrap Select dil dosyaları korunur. Üç dilde üretilen yerel script yollarının fiziksel dosyaları testte denetlenir.

## 2. Editör dili

Ortak TinyMCE bileşenindeki Livewire ve plain init ile sınav bölüm modal'ındaki init aynı eşlemeyi kullanır: NL → `tinymce_nl.js`/`nl`, TR → mevcut `tinymce_tr.js`/`tr`, EN → boş language_url/yerleşik `en`. Help keynav URL'si de aktif arayüz dilini izler. TinyMCE sürümü/plugin seti değiştirilmedi.

Yeni dosya TinyMCE **7** community NL paketidir; kaynağı [tinymce-i18n langs7/nl.js](https://github.com/mklkj/tinymce-i18n/blob/master/langs7/nl.js). Bu bir Composer/npm kurulumu değildir. İndirme tarihi 5 Ekim; SHA256 `331713cb0471661633f81605a23228f62aefd5cf50186ebebd458ca3c67fbe40`. Dağıtımın LICENSE/NOTICE dosyaları aynı asset dizininde saklandı; dil dosyaları ilgili TinyMCE sürümünün lisansına tabidir. Mevcut uygulamanın GPL init'i korunur. [TinyMCE 7 yerelleştirme rehberi](https://www.tiny.cloud/docs/tinymce/7/ui-localization/) dil adı/dosya eşleşmesini açıklar.

**Gerçek browser'ın yakaladığı uygulama ayrıntısı:** Livewire render sırasında JavaScript nesnesinin içindeki Blade `@if` bloğuna HTML morph marker ekledi. `<!--...` aynı satırdaki `language_url` seçeneğini JS yorumuna dönüştürdü. Koşul Blade değer ifadesine taşındı; JS içinde kontrol bloğu yoktur. Testte marker'ın language_url satırını etkilemediği ayrıca denetlenir. Yeni kod bu kalıbı tekrar etmemeli.

## 3. Yükleyici dili

`lang/nl|en|tr/upload.php` Dropify'ın dört normal ve yedi hata mesajını içerir. Backend layout bu diziyi `@json` ile `window.uploadTranslations` olarak verir; mevcut app.js init'i `messages` ve **tekil `error`** seçeneklerine bağlar. Dosya boyutu/tür/genişlik/yükseklik mesajlarının `{{ value }}` yer tutucusu Dropify tarafından doldurulur. Vendor Dropify kodu ve izin/MIME/10 MB sunucu kontrolü değiştirilmedi.

## 4. Tarih seçici kararı

Browser'da EN arayüzle NL blog düzenlerken `invalid locale nl` uyarısı görüldü. Kullanıcıya soruldu ve arayüz dili seçildi. Blog, soru istatistiği, sezon ve lig çalıştırma Blade'lerindeki yedi tarih alanı artık `app()->getLocale()` kullanır. Kayıt locale alanı ve içerik dili değişmez. İngilizce sayfa yerleşik English Flatpickr'i kullanır; ek NL dosyası yükleme çözümüne gidilmedi.

## 5. Dar otomatik test kanıtı

`tests/Feature/PluginLocaleTest.php`, yalnız **testing DB**, DatabaseTransactions:

1. NL/EN/TR panel blog create, yerel asset dosyaları, Livewire editör dili ve upload mesaj anahtarları.
2. Aynı dillerde ayrı bölüm modal'ı editör konfigürasyonu.
3. Aynı dillerde NL blog edit; tarih seçici UI dilinde, kayıt locale'i hâlâ NL.
4. NL/EN/TR frontend login script yolları; İngilizce olmayan Flatpickr URL'si üretilmez.
5. NL/EN/TR plain TinyMCE bileşeni; aynı dil eşlemesi.
6. Mail/queue fake; mail gönderilmez, arka plan işi eklenmez.

Son komut: `docker compose exec -T laravel.test php artisan test --compact tests/Feature/PluginLocaleTest.php` → **9 passed, 170 assertions**, 1.21 saniye. İlk 149/161 doğrulamalı koşular ara kanıttır; son tarih kararıyla 170 oldu. İki değişen JS dosyasının `node --check` kontrolü geçti. Bütün suite/build tekrarlanmadı.

## 6. Gerçek Chrome kanıtı ve sınırı

Önceki `23` belgesindeki ayrı host PHP 8.3/testing harness kullanıldı. Yeni fixture, migration, seeder veya ana DB yazımı yapılmadı. Sentetik mevcut kullanıcı/blog kullanıldı; editör etkileşiminde yazılan metin kaydedilmedi. Browser plugin bulunmadığından kurulu Chrome + bundled Playwright kullanıldı; yeni paket kurulmadı.

- NL blog edit: TinyMCE `Vetgedrukt`, `Bestand` menüsü ve `Nieuw document/Voorbeeld` seçenekleri gerçekten göründü. Dropify normal/hata metinleri Flemenkçe; mevcut PNG naturalWidth=220.
- EN blog create: TinyMCE `Bold`, Dropify İngilizce metin, validation `This field is required.`; editöre yazı yazma/toolbar etkileşimi çalıştı.
- EN arayüz/NL blog edit: tarih `data-locale=en`, ay `January`; dil uyarısı kalmadı.
- EN frontend profile: html lang=en; ilgili İngilizce locale 404'leri yok.
- Bu dört dar sayfa kontrolünde ilgili response>=400 ve console/pageerror kayıtları boştu. İlk login favicon console kaydı ürün script 404 kanıtı olarak sayılmadı.
- TR için otomatik render/asset/config kanıtı vardır; testing dil kataloğunda TR aktif olmadığı için gerçek TR oturumu doğrulanmadı. Dil kaydı oluşturulmadı.
- 390 px screenshot tek başına mobil kabul değildir; sidebar geçişi screenshot'ı etkiledi. Mobil/cihaz matrisi bu tur tamamlanmadı.

Kanıtlar geçici `/tmp/eduan-browser-1005/locale-*.png` ve browser-errors.json altında. PHP server/browser durduruldu. Önceki sentetik fixture'lar için silme onayı hâlâ beklenir; hiçbir kayıt silinmedi. Deploy yapılmadı; mevcut APP_VERSION cache anahtarı kullanılır, yayın fazında değişen JS asset sürümü ayrıca ele alınır.

## 7. Lara Football referansı yeniden doğrulandı

Diğer checkout salt okunur incelendi:

- `resources/views/components/admin/server-table.blade.php`: `data-side-pagination=server`, URL, pagination, search sağda, refresh/butonlar solda, Blade kolonları ve responsive wrapper.
- `resources/views/components/admin/filter-bar.blade.php` ve `resources/views/admin/statistics/favorites.blade.php`: ayrı filtre bandı; form parametrelerini JSON isteğine ekleme; filtre submit'te refresh/pageNumber:1; gizli tab ilk gösterimde init, sonra resetView.
- `app/Http/Controllers/Admin/FavoriteStatisticsController.php`: limit 1–100, offset>=0, search uzunluğu, order asc/desc doğrulanır. Sort gerçek SQL alanlarına match ile eşlenir; count/window count ve offset/limit sonrası total/rows JSON döner. Metin formatter'ı HTML kaçırır.
- README server-side Bootstrap Table düzenini doğrular. Örnek kendi Laravel/Blade/controller kodudur; Rappasoft bağı gerekmez. **Bootstrap Table JS kütüphanesi yine kullanılır**; "kütüphanesiz tablo" değildir.

Eduan için uygundur. 31 Rappasoft sınıfı/61 published view, izin/öğretmen kapsamı, export ve tablo eylemleri `19` haritasıyla aşamalı taşınır. Örneğin Favorites endpoint'inde `Cache::remember` bulunmaz; Eduan'ın sayfa/filtre/sort/kullanıcı/dil cache'i yeni hedef gereksinimdir. Lara'nın TR/EN locale eşlemesi NL'ye genişletilir; futbol iş kuralları/özel rol sistemi buraya kopyalanmaz. **OneUI devam, Vue.js yok, taşıma Laravel güncellemesinden sonra** kararı korunur.

## 8. Açık konu ve sonraki kapı

B23-01/B23-02 ve onaylanan tarih locale bulgusu dar kanıtlarıyla kapandı. Genel EN sayfa başlıkları hâlâ Türkçe görünebilir: `lang/en.json` yok, Türkçe JSON anahtarları kaynak metne düşer. Bu script/editor/upload düzeltmesi bütün içerik/menü çevirisi değildir; ayrı içerik kararı olmadan yeni JSON sözlük yazılmadı.

27 paket için aralık listesi `24` içinde geçerlidir. Kullanıcı gerekirse Langscanner/Alert/Backup'ı kendi koduyla değiştirebileceğini söyledi; bu değerlendirme isteğidir, hangi paketin nasıl kaldırılacağı henüz seçilmedi. Rappasoft kaldırma kararı kesin, uygulaması sonraki fazdır. Bugün Composer update/remove/solver veya Laravel 13 kurulumu yapılmadı. Canlı SQL/migration dökümü kullanıcıdan beklenir.

## 9. Bu düzeltmede değişen dosyalar

- `resources/views/backend/layouts/app.blade.php`, `resources/views/frontend/layouts/app.blade.php`
- `resources/views/components/tinymce/editor.blade.php`, `public/backend/assets/app.js`
- `resources/views/livewire/backend/blogs/blog-form.blade.php`
- `resources/views/livewire/backend/stats/questions-stats.blade.php`
- `resources/views/livewire/backend/season/season-form.blade.php`
- `resources/views/livewire/backend/leagues/league-run-form.blade.php`
- Yeni `lang/nl/upload.php`, `lang/en/upload.php`, `lang/tr/upload.php`
- Yeni `public/backend/assets/langs/tinymce_nl.js`, `tinymce-i18n-LICENSE.md`, `tinymce-i18n-NOTICE.md`
- Yeni `tests/Feature/PluginLocaleTest.php`
- `AGENTS.md`, `Codex/16-PROJE-STANDARTLARI.md`, `Codex/23-TARAYICI-AKISLARI-VE-YUKSELTME-HAZIRLIGI.md`, yeni bu belge ve `Codex/index.html`

Önceki turlardaki diğer çalışma ağacı değişiklikleri bu düzeltmenin parçası değildir; `.DS_Store` dahil kullanıcı dosyalarına müdahale edilmedi.
