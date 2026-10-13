# 55 — Ön yüz lig ilk yükleme ve sayfalama hover

Tarih: 7 Ekim 2026. Kullanıcı ön yüz ligler ekranının lazy yerine daha erken gösterilmesini, test/sınav/blog ve ortak ön yüz sayfalamalarında hover sırasında kaybolan yazının düzeltilmesini istedi.

## 1. Çalışma prensibi ve karar

LeagueTable `Lazy(isolate:false)` ile önce iskelet gösteriyordu. İçindeki üç LeagueResultTable ayrıca `Lazy(isolate:true)` kullanıyordu. Gerçek testing Chrome akışında ilk HTML'de sıralama kartı0, sonradan kart3 ve **4 ek Livewire POST** doğrulandı.

Bu ekranın temel içeriği dönem seçici ve üç sıralamadır. Veriler zaten cache'den gelir; SQL sonuçları userCount/rank_position/position ile sınırlı, kullanıcı eager load'u mevcuttur. İkinci istekleri beklemek yerine bu iki sınıfın Lazy attribute/importları kaldırıldı. Filtre, puan, sıra, mevcut kullanıcı satırı ve dönem seçimi değişmedi. Diğer ön yüz/panel lazy bileşenleri bu kapsamda değildir.

## 2. Cache ve SQL sınırı

- Dönem listesi `leagues_all`: mevcut24saat.
- İlk dönem `leagues_first`: mevcut12saat.
- Sıralama `league_ID_RANKING_USERCOUNT`: mevcut12saat.
- Mevcut Computed ve SQL kapsamı korunur; yeni cache katmanı/TTL/global flush yok.
- İlk HTML artık üç tabloyu üretir; cold cache durumunda SQL ilk istekte çalışır. Bu karar ilk isteğin her ortamda daha kısa olacağı iddiası değildir; dört ek mount isteği ve içerik beklemesi kaldırılır.
- Testte warm HTTP isteğinde `league_results` sorgusu0. Bütün HTTP isteği0SQL veya üretimde maksimum hız kanıtı değildir.
- Yerel hazır olma ölçümleri networkidle beklemesini, assetleri ve testing verisini içerir; üretim LCP/TTFB karşılaştırması olarak kullanılmaz.

## 3. Hover hatasının nedeni ve ortak düzeltme

Ortak sayfalamanın numara bağlantılarında href ve class bulunmuyor. Bootstrap'in `a:not([href]):not([class]):hover` kuralı, daha düşük specificity'li eski `.pagination-area ul li a:hover` yazı rengini bastırıyordu. Aktif bağlantı class taşıdığı için bu sorundan etkilenmiyordu; kullanıcının görüntüsüyle eşleşir.

Chrome'da düzeltme öncesi3 rakamı: yazı `rgba(30,30,30,0.6)`, zemin `rgb(52,47,131)`. Aynı renderer ve CSS ile hata yeniden üretildi.

`public/assets/app.css` ortak kuralı `a:not(.disabled):hover` ve `a:not(.disabled):focus-visible` ile güçlendirildi. Aktif kuralı ve disabled özel rengi korunur; bütün pagination-area kullanımları etkilenir. Bootstrap/vendor dosyası veya ayrı sayfa stilleri değiştirilmedi.

Bu ortak görünümü kullanan yerler: dersler, konular, testler, sınavlar, blog; favoriler, çözülen test/sınavlar ve test yorum listesi. Sayfa adedi, navigation, Livewire gotoPage/previousPage/nextPage veya cache mantığı değiştirilmedi.

CSS değiştiği için `.env` **APP_VERSION1.1.12 →1.1.13**. Testing HTML'de `assets/app.css?v=1.1.13` doğrulandı. JavaScript/paket değişimi yok.

## 4. Dar otomatik doğrulama

Yalnız ayrı `testing` DB, DatabaseTransactions. Ana DB/migration/seed/temizlik veya mail/queue/deploy işlemi yapılmadı.

- `FrontendLeaguePresentationTest`: **4 test /37 doğrulama geçti**.
- Önceki üç senaryo: tr/nl/en dönem linki ve arama, nullable dönem tarihleri; sıra/puan/kendi satırı/HTML escape ve boş sonuç.
- Yeni senaryo: üç ligde ayrı öğrenci fixture'ı; gerçek frontend.leagues GET ilk HTML'inde üç ad ve üç kart; lazy mount işaretçisi yok; ikinci istekte league_results SQL sorgusu0.
- İki kaynak sınıfı ve test dosyası PHP syntax başarılı; Pint başarılı. LeagueTable nullable tip yazımı formatter tarafından `?League` biçimine getirildi.
- CSS değişimi için uygulama davranışını taklit eden yeni PHP testi yazılmadı; gerçek tarayıcı computed style kullanıldı.

## 5. Tarayıcı kanıtı

Browser plugin/skill mevcut olmadığı için kurulu Playwright ve gerçek Google Chrome/headless kullanıldı. İzole testing web `127.0.0.1:8765`, masaüstü1440×950 ve mobil390×844; mevcutlocalhost80/3306 veya Docker/Sail servisleri değiştirilmedi. Yalnız geçici QA sunucusu kontrol sonunda durduruldu.

| Kontrol | Sonuç |
|---|---|
| Gerçek ligler route'u | HTTP200; anlamlı içerik/başlık, framework hata ekranı yok. |
| İlk HTML | Önce kart0/mountPOST4; sonra kart3/mountPOST0; iki ardışık yüklemede aynı sonuç. |
| Dönem araması | Eşleşmeyen metin seçenekleri gizler; temizleme seçenekleri geri getirir. |
| Dönem seçimi | Gerçek detail URL'sine gider ve üç kart gelir. |
| Mobil lig görünümü | Üç kart okunur; yatay taşma yok. |
| Desktop pagination hover |3 rakamı beyaz `rgb(255,255,255)`, zemin mevcut proje moru. |
| Aktif/pasif renk | Aktif yazı beyaz; disabled zemin239/239/239 ve yazı siyah kalır. |
| Mobil pagination | Ortak Next hover yazısı beyaz; yatay taşma yok. |
| JavaScript | İki dar akışta pageerror0. |

Sayfalama örneği gerçek frontend layout ve ortak pagination Blade'inden, DB'ye kayıt eklenmeden sentetik210kayıt/15sayfa boyutuyla üretildi. Render/hover gerçek Chrome/CSS; gerçek öğrenci verisinde14sayfa veya gerçek gotoPage isteği bu aşamada ayrıca sınanmadı, action kodu değişmedi.

İlk arama QA denemesi fill sonrasında Alpine DOM güncellemesini beklemediği için erken hata verdi. QA betiği DOM'un gizlenmesini bekleyecek şekilde düzeltildi; son arama/reset/navigation kontrolü geçti. Uygulama arama kodu değişmedi.

Görsel kanıtlar proje dışında: `/tmp/eduan-front-performance-1007/pagination-hover.png`, `league-first-html.png`, `league-first-html-mobile.png`. Geçici betikler ve QA sayfaları Git/proje/public'e eklenmedi.

## 6. Değişen dosyalar ve sınırlar

- `app/Livewire/Frontend/Leagues/LeagueTable.php`: ana lazy kaldırma ve kullanılmayan import.
- `app/Livewire/Frontend/Leagues/LeagueResultTable.php`: sonuç lazy kaldırma ve kullanılmayan import.
- `public/assets/app.css`: ortak pagination hover/focus renk specificity'si.
- `.env`: yalnız APP_VERSION1.1.13.
- `tests/Feature/FrontendLeaguePresentationTest.php`: ilk HTML/cache regresyon senaryosu.
- AGENTS, standartlar16, lazy kaynak raporu50, tarihsel lig raporu52, bu55 ve index: güncel karar/kanıt.

Mevcut testing NL lig ekranındaki bazı eski `Lig Sonuçları` / `Geçmiş Lig Sonuçları` başlıkları Türkçe kalıyor. Bu lazy/hover değişikliği kaynaklı değildir; çeviri eksikliği yalnız kayda alındı, kullanıcı talimatına göre otomatik düzeltilmedi. Canlı içerik/rol/PWA veya bütün proje testi kabulü verilmez. Üretim veri hacminde cold SQL/TTFB ve uzun dönem listesi ayrı ölçülür.
