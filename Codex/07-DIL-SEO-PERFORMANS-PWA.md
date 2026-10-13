# Dil, SEO, hız, görseller, cookie ve PWA

> İlk tarama bulguları bu dosyada bağlam olarak korunur. Güncel canonical/sitemap/PWA uygulaması ve test kanıtı aşağıdaki güncellemede ve `13-GUNCEL-DUZELTMELER-VE-TEST-KANITI.md` içindedir.

## Dil akışı

Kullanıcının hedef ürün sırası Flemenkçe, İngilizce, Türkçedir; `nl` kodu ve varsayılan dil olarak onaylandı. Kaynakta `LanguageSeeder` `tr` ve `nl` oluşturuyor; ilk düzeltme paketinde `SettingSeeder.defaultLocale`, ayarsız `SetLocale` fallback'i, `config/app.php` locale varsayılanı ve `.env.example.APP_LOCALE` `nl` yapıldı. Eksik metin çevirisinin `fallback_locale=en` ayarı korundu. `lang/nl.json` ve PHP `en/nl/tr` klasörleri mevcut. Gerçek aktif dil ortamın `.env`, session ve `settings()->defaultLocale` değeriyle değişebilir; bu pakette mevcut ortam verisi değiştirilmedi. Çeviri panelinde `LanguageTranslateForm` anahtar taraması → DB `translations` → JSON dosyası akışı var ve yazma adımları `languages:translate` izniyle korunur. İçerik modellerinin bazıları ayrı `locale` satırı, `Page/Lesson/Topic` ise Spatie JSON çevirisi kullanıyor. Yeni ekranda hangi yaklaşımın kullanıldığını yakın modele göre seç ve bütün hedef dillerde fallback/kayıp çeviri durumunu kontrol et.

## SEO mevcut durum ve inceleme listesi

`resources/views/frontend/layouts/app.blade.php` title, açıklama bileşeni, `lang`, robots, sosyal meta ve favicon üretir; `components/seo/tags.blade.php` varsayılan description/OG/Twitter alanlarını verir. `routes/frontend.php` `/sitemap.xml`; `frontend/home/sitemap.blade.php` sabit ve dinamik URL'leri yazar. `public/robots.txt` erişimi açık bırakır. İlk bakışta şu kod noktaları doğrulanmalı:

- Layout `meta name="canonical"` yazıyor; arama motoru için standart `link rel="canonical"` kullanımına eşdeğer kabul edilmemeli. URL `request()->fullUrl()` ile query parametresi de içerebilir.
- OG/Twitter URL'leri varsayılan olarak `url('/')` kullanıyor; her detay sayfasını temsil etmeyebilir. Çok dilde `hreflang`/alternatif URL stratejisi görünmedi.
- Sitemap'te kök URL ve `frontend.home` olası yineleniyor. `Exam` döngüsünde `frontend.exams` route'una kod parametresi, `Test` döngüsünde `frontend.tests` route'una kod parametresi veriliyor; detay URL'leriyle eşleşme kontrolü gerekir. `::all()` ile tüm tabloları sitemap isteğinde okumak büyüme riski taşır. Aktif/yayınlanmış/indekslenebilir filtreleri ayrıca incelenmeli.
- SEO çıktısının HTTP üzerinden gerçek kaynak HTML'si, canonical, robots, sitemap URL'leri, çok dil ve indekslenebilirlik henüz test edilmedi. `http://127.0.0.1/` yerel bağlantı vermedi; Google Search Console veya üretim verisi yok.

Gelecek doğrulamada Google Search Central [geliştirici kılavuzu](https://developers.google.com/search/docs/fundamentals/get-started-developers), [SEO başlangıç rehberi](https://developers.google.com/search/docs/fundamentals/seo-starter-guide) ve [lazy content kılavuzu](https://developers.google.com/search/docs/crawling-indexing/javascript/lazy-loading) esas alınmalı. Google sıralama garantisi verilmez; sayfanın taranabilirliğini ve kullanıcı deneyimini ölç.

## Hız ve görsel önceliği

Kullanıcı ilk görünen ana görselin normal/erken yüklenmesini istiyor. [web.dev LCP kılavuzu](https://web.dev/articles/optimize-lcp), LCP görselini lazy yüklememeyi ve ilk HTML'de keşfedilebilir kılmayı önerir. Ekran dışında kalan tüm görselleri zorunlu eager yapmak ayrıca gerekli değildir; kullanıcı tercihi ve ölçümle sayfa bazında karar verilir. Ön yüz ana layout çok sayıda CSS/JS yükler; `public/assets` yaklaşık 28 MB, panel assetleri yaklaşık 28 MB, uploads yaklaşık 284 MB. Bunlar diskteki toplam boyuttur, bir sayfanın ağ transferi değildir. Önce URL bazında LCP/TTFB/JS yükü ve sorgu sayısı ölç; sonra değişiklik yap.

`HomeController::index` ana sayfa kaydını 30 gün cache'liyor. `DataHelper` yüksek süreli cache'ler kullanıyor; `AppServiceProvider` üretim dışında her açılışta `cache()->flush()` çağırıyor ve `resetCache()` tüm cache/optimize temizliği yapıyor. Bunlar doğrulanacak performans adaylarıdır; işlevi anlamadan kaldırma. Panelde Rappasoft total-count kapalı ve lazy tablolar var; performans kıyasına dahil et.

## PWA ve cookie

`public/pwa-manifest.json` ve `config/manifest.php` kurulum meta verisini, `SettingSaveAction` manifest/ikon üretimini, `public/assets/app.js` service worker kaydını, `public/assets/pwa-sw.js` cache davranışını içerir. Worker `/assets/pwa-sw.js` yolundan varsayılan scope ile kaydediliyor; kök sayfanın worker tarafından kontrol edildiği varsayılmamalı. Manifest linki yalnız ana sayfa + Chrome koşulunda ve `?v=time()` ile ekleniyor. Gerçek tarayıcı kurulum/çevrimdışı testi yapılmadı. PWA cache'inin oturumlu içerik ve admin URL'lerini depolamaması ayrıca doğrulanmalı.

`public/assets/cookieconsent-config.js` CookieConsent 3 CDN modülü, `cc_cookie` ve Google consent kategorileri kullanır; `frontend/layouts/app.blade.php` analytics etiketini koşullu ekler. Consent varsayılanlarının etiket yüklenme sırasıyla uyumu, yerel dil dosyaları ve kayıt davranışı tarayıcıda test edilmeli. Cookie/analitik değişikliği hukuki ve ürün kararlarına bağlı olduğundan ayrı kapsam gerektirir.

## Güncel uygulama — devam paketi

- Canonical artık gerçek `link rel="canonical"`; sosyal URL mevcut sayfadır. Özel auth/hesap/çözüm rotaları noindex; sitemap aktif/yayınlanmış gerçek detay URL'lerini üretir, kök tekrarını ve arama sayfasını dışlar. İki HTTP/XML testi geçti. Query temizliği/hreflang ve üretim indekslenmesi bu kapsamda doğrulanmadı.
- Worker root `/pwa-sw.js` ve `/` scope kullanır; eski assets scope kaydı kaldırılır. Navigation network üzerinden gider; hata durumunda anonim statik `/offline.html` gösterilir. Yalnız izinli statik assetler cache'lenir; özel sayfa/upload/PDF/Livewire/POST saklanmaz.
- Kullanıcı **isim-soyisim çevrimdışında da görünsün** kararını verdi. `pwa-user-name` localStorage kaydı yalnız görünen ad taşır; guest temizler. Offline HTML bunu `textContent` ile gösterir; HTML çalıştırmaz.
- Yedi Node VM testi geçti. Tarayıcıda offline HTML ve yeniden deneme bağlantısı hatasız açıldı; gerçek ağ kesintisi, isimle logout geçişi, fiziksel mobil kurulum ve eski worker geçişi henüz test edilmedi.
- Cache sürümü `hypotenuse-pwa-2`; dağıtımda app.js için mevcut `APP_VERSION`/HTTP cache planı hazırlanmalıdır. Bu incelemede `.env` veya canlı asset sürümü değiştirilmedi.
