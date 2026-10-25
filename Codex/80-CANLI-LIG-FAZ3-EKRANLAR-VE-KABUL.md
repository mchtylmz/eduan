# 80 — Canlı lig Faz 3: ekranlar, cache ve yerel kabul

25 Ekim 2026. Faz2 `bdd83fa2` sonrasında kullanıcının Faz3 ve test talimatıyla uygulandı. Bu rapor yalnız canlı lig geliştirmesinin yerel kabulüdür; canlı yayın veya tüm projenin genel kabulü değildir.

## 1. Panelde nereden kontrol edilir?

`/admin/leagues` → **Lig oluştur / hesapla**. Mevcut sağ form korunur. Hesaplama türü: Tek hesaplama / Canlı. Varsayılan tek hesaplamadır; kullanıcı canlıyı seçebilir.

Yeni formun varsayılanı içinde bulunulan haftanın **Cumartesi 00:00–Cuma 23:59** aralığıdır. Sunucu bitiş dakikasını 23:59:59 olarak saklar. İki tarih/saat serbestçe değiştirilebilir; yedi gün zorunluluğu yoktur. Eski kayıtların tarihleri güncellenmez. Flatpickr, Livewire doğrulaması ve mevcut izinler kullanılır.

Canlı form ilk kayıtta mevcut veriyi hesaplar; gelecek başlangıçta boş canlı kayıt bekler. Tek hesaplamada veri yoksa başarı/sonra çalışacak mesajı verilmez ve yeni lig oluşturulmaz. Form ortak senkron `CalculateLeague` Action'ına bağlandı; eski iki parametreli job ve haftalık komut korunur. Başarı yalnız gerçek sonuçtan sonra gösterilir. Pasif sistem, kilit çakışması, geçersiz aralık/ayar ve beklenmeyen hata ayrılır; otomatik yeniden deneme yoktur.

## 2. Bootstrap Table ve yeniden hesaplama

Mevcut `x-admin.server-table`, sunucu JSON, 15 satır, id azalan, tarih filtreleri ve arama korunur. Durum ve Veri tarihi kolonları eklendi. Hesaplanma Tarihi gerçek işlem zamanıdır; Veri tarihi sonucun kapsadığı üst sınırdır.

Durumlar: Tek hesaplama, Başlamadı, Canlı, Son hesaplama bekleniyor, Kapandı. Zaman ilerlediğinde doğru durum için lig listesi sunucuda yanıt cache'ine alınmaz; private/no-store kalır. Ortak `ServerTable::page` yeni isteğe bağlı `cache` parametresinde varsayılan true olduğundan diğer tabloların davranışı değişmez.

Yalnız `is_live=1` ve `closed_at` dolu satırda, `leagues:run` izni varsa **Yeniden hesapla** çıkar. Ayrı açıklamalı mevcut Swal onayı kullanılır; iptal istek göndermez. Sunucu aynı izinleri ve kapalı durumunu yeniden denetler. Başarılı işlem tabloyu yeniler; sabit kaynak/puan/kapasite ve ilk kapanış zamanı korunur. Açık/tek-seferlik/sahte ID ile işlem yapılamaz. Bu düğme canlı takibi yeniden açmaz.

Mobilde başlık ve işlem düğmesi alt alta gelir; tablonun yatay kaydırması kendi alanında kalır.

## 3. Ön yüz

`/leagues` ve `/league/{code}` mevcut üç lig kartı/podyum/dönem seçicisini kullanır. Dönem özeti durum, tam başlangıç-bitiş saatleri, gerçek Son hesaplama ve varsa Veri tarihi gösterir. Gelecek kayıtta olmayan zamanlar üretilmez. Açık lig için dört saatlik hesaplama ve sayfayı yenileme açıklaması bulunur; polling/otomatik reload eklenmedi.

Süre dolup son görev henüz başarıyla çalışmadığında **Son hesaplama bekleniyor** gösterilir. Başarılı kapanışta veri tarihi dönem sonudur; gerçek işlem zamanı daha sonraki saat olabilir. Eski tek-seferlik kayıtların olmayan cutoff bilgisi tahmin edilmez.

Canlı lig kartlarının kapasitesi global ayardan değil kayıtlı snapshot'tan okunur. Sonradan kapasite değişse de ilk/ikinci/üçüncü lig bantları o ligde değişmez. Eski tek hesaplama ekranı mevcut kapasite davranışını sürdürür.

## 4. Hedefli cache yenileme

Yeni `LeagueCache`, başarılı ortak hesaplama tamamlandıktan sonra `leagues_first`, `leagues_all` anahtarlarını kaldırır; yalnız ilgili ligin sonuç revizyonunu UUID ile değiştirir. Sonuçların 12 saatlik cache'i yeni revizyonu kullanır; aynı saniyede iki hesaplama da ayrı revizyondur. Başka içerik anahtarı silinmez. Yeni/kapanan lig için sitemap revizyonu da yenilenir.

Lig listesi 24 saatlik iç cache'ini kullanmaya devam eder; ayrıca kalıcı Livewire computed cache tutulmaz. Başarısız hesaplamada revizyon ve son başarılı cache korunur. Kök PWA kimliği/URL'si değişmedi. Ortak tablo JS değiştiği için yerel APP_VERSION ve root worker **1.1.29** oldu; .env Git'e alınmaz, canlı sürüm yayın aşamasında eşitlenir.

## 5. Tarayıcıda bulunan tarih saklama sınırı

Mevcut MySQL tarih kolonu ve strict=false davranışında 2099 girdisi sıfır tarihe çevrilebiliyordu. Yalnız testing transaction içinde görüldü; kayıt geri alındı. Yeni kaydın ilk yazımında başlangıç/bitiş DB'den dar kolonlarla geri okunur; istenen aralıkla birebir eşleşmiyorsa transaction hata ile geri alınır. Böylece sessizce yanlış dönem kaydedilmez. Yeni sabit yedi gün/ileri tarih iş kuralı veya migration eklenmedi; normal gelecek dönem kabul edilir.

Bu korumanın ilk dar koşusu Eloquent `fresh` parametresinin ilişki adı olarak yorumlanması nedeniyle 3 failure/1 error verdi. Geri okuma `select(...)->findOrFail(...)` ile düzeltildi; dört ilgili senaryo 4/20 geçti. Ardından son birleşik koşu aşağıdaki gibi temiz tamamlandı.

## 6. PHP / JS kabulü

**Son tek birleşik ilgili koşu: 104 test, 617 assertion; hepsi başarılı (PHP8.5.8/MySQL testing).** Tüm proje suite'i/build çalıştırılmadı. `DatabaseTransactions` kullanıldı. Gerçek `schedule:run`, ücretli AI, SMTP veya push çağrısı yok.

Kapsam:

- Takvim, Cumartesi–Cuma ve DST; serbest aralık ve eski tarihler;
- Eski puan/SUM/RANK sorgusu, iki kaynak ve kısmi/tamamlanmış denemeler;
- Canlı/gelecek/boş dönem, sabit ayarlar, tekrar kayıtta aynı ID;
- Altı scheduler slotu, eski görevle aynı kayda hesap, gerçek iki DB bağlantısıyla kilit;
- Kapanış saniyesi, gece yarısını aşan hesap, rollback/sonraki görevde toparlanma;
- Form iki modu, yanlış/eksik/ters tarih, DB'nin saklayamadığı tarih, boş tek hesaplama, pasif/busy/çakışma;
- Misafir/eksik/sonradan kaldırılmış izin, açık/tek/sahte ID yeniden hesaplama reddi;
- Beş durumun JSON'u ve saat geçişi, yetkiye göre düğme, özel snapshot'ın JSON'a çıkmaması;
- Sıcak cache sonrası aynı saniye hesap, başarısızlıkta cache koruma, sabit kapasiteyle ayrı lig bantları;
- İlk HTML üç kart/cache kullanımı, eski ön yüz/öğretmen kapsamı, geçmiş/cleanup ve scheduler kayıt regresyonları.

Yeni Faz3 test dosyasında 19 senaryo vardır. JavaScript: 12 PWA testi, yeni lig formatter'ında 8 ve mevcut LaTeX formatter'ında 9 assertion başarılı. Yeni düğmenin izin bayrağı, yalnız lig tablosunda görünmesi ve metin HTML kaçışı denetlendi. Değişen15 PHP dosyasında Pint; PHP/JS syntax ve whitespace kontrolleri uygulandı.

## 7. Gerçek Chrome / ekran kabulü

Browser plugin/skill bulunmadığından frontend-testing-debugging rehberinin Playwright yolu kullanıldı. Paket Chromium binary'si kurulu değildi; yeni indirme yapılmadan `/Applications/Google Chrome.app` ayrı geçici profille kullanıldı. `http://localhost` gerçek route/Blade/Livewire/Bootstrap/Flatpickr akışı, ayrı testing DB'de tek transaction köprüsüyle çalıştırıldı; ana uygulamanın statik asset'leri okundu.

Kontroller: 1440×1000 panel/sağ form/ön yüz, 390×844 panel/sağ form/ön yüz; NL ve FR mobil metinler. 15 satır server table, arama, kapalı yeniden hesaplama onayı/iptali, gerçek yenileme, serbest gelecek dönem oluşturma ve redirect sonrası kayıt, dönem araması ve gerçek detay bağlantısına geçiş doğrulandı. Sayfa kimliği/dolu içerik/overlay kontrolü, screenshot, etkileşim, JS error0, HTTP5xx0 ve sayfa overflow0 başarılı.

İlk otomasyon arama/yanıt/Alpine DOM beklemeleri gerçek olaylarla eşlendi; aynı kaynak/aralığa sahip iki yapay fixture ayrı dönemlere ayrıldı. Ürün koduna bunlar için istisna eklenmedi. Mobil başlıkta görülen düğme taşması yerel Blade responsive sınıflarıyla düzeltildi. Son akış sekiz kontrol grubuyla tek başarılı koşuda bitti. Geçici köprü kapandı, tüm DB fixture'ları rollback oldu. Kanıt ekran görüntüleri ve otomasyon /tmp altındadır; repoya alınmaz.

**Ayrı bulgu:** Fransızca eski genel lig başlığı gibi bazı önceki JSON çevirileri Türkçe fallback gösterebiliyor. Bu fazın yeni19 metni TR/NL/EN/FR PHP kaynaklarında tamamdır; eski genel çeviri/DB kayıtları bu kapsamda değiştirilmedi. Gerçek Safari/telefon ve production yük/cron sürekliliği kanıtı yoktur.

## 8. Veri / yayın sınırı

Ana DB **35lig/1238sonuç**, testing başlangıç **1lig/1sonuç**. Liglerin eski kolonları ile bütün sonuçların SHA özetleri Faz1 öncesi referansla aynı. Cevap/dil/çeviri sayıları da aynı; iki DB'de kalıcı yeni canlı/snapshot/cutoff/closed metadata0. Önceki ligler canlıya çevrilmedi.

Yeni migration/izin/ayar aktivasyonu, ana DB gerçek lig hesabı, üretim cron/worker/servis/port/volume, Composer veya dış servis işlemi yapılmadı. APP_URL ve mevcut ayarlar korunur. Yalnız yerel asset patch sürümü değişti.

**Canlı lig üç geliştirme fazı tamamlandı.** Kullanıcının elle kontrolü ve yayın hazırlığı sıradadır. Yayında Faz1'in şema migration'ı, PHP8.5/Imagick CLI, scheduler/worker, asset sürümü/cache ve geri dönüş listesi ayrıca uygulanıp kontrol edilir. Sunucuda dört saatlik fiilî çalışma bu yerel testle kanıtlanmış sayılmaz.

## 9. Değişen dosyalar

- Hesap/cache/model: `app/Actions/Leagues/CalculateLeague.php`, yeni `app/Helpers/LeagueCache.php`, `app/Models/League.php`;
- Ortak tablo/panel JSON: `app/Helpers/ServerTable.php`, `app/Http/Controllers/Backend/LeagueController.php`;
- Panel eylemleri: `app/Livewire/Leagues/LeagueRunForm.php`, `app/Livewire/Leagues/LeagueTable.php`;
- Ön yüz veri sunumu: `app/Livewire/Frontend/Leagues/LeagueTable.php`, `LeagueResultTable.php`;
- Görünümler: `resources/views/backend/leagues/index.blade.php`, `resources/views/livewire/backend/leagues/league-run-form.blade.php`, `server-table.blade.php`, `resources/views/livewire/frontend/leagues/league-table.blade.php`;
- Yeni metinler: `lang/tr/leagues.php`, `lang/nl/leagues.php`, `lang/en/leagues.php`, yeni `lang/fr/leagues.php`;
- Asset: `public/backend/assets/server-table.js`, `public/pwa-sw.js`; yerel ignored `.env` yalnız sürüm;
- Test: yeni `tests/Feature/LiveLeaguePresentationTest.php`, `tests/Frontend/LeagueTableTest.cjs`; eski form beklentisinin ortak Action'a bağlandığı `tests/Feature/StabilityReleaseTest.php`;
- Rehber: AGENTS.md, README.md, standartlar16, bu80 raporu ve Codex/index.html.

Önceki `.DS_Store`, `Codex/Doge.html` → `Codex/docs.html` wiki işi ve özgün OneSignal klasörü bu faz commit'ine alınmaz.


### Güncel kapanış —25 Ekim 2026

Bu raporda tarihsel olarak ayrı bırakılan ortak Fransızca hesap/panel/lig çeviri bulguları kullanıcı onayıyla [81 — genel test ve FR düzeltmeleri](81-GENEL-TEST-KABULU-VE-FRANSIZCA-DUZELTMELER.md) kapsamında kaynak dosyalarında giderildi. Eski özel değerler ve DB dil/aktivasyonları korundu; son genel816/5130 ve Chrome54ziyaret kabulü81'de. Yukarıdaki faza ait test/sınır kayıtları tarihsel kanıttır.

### Ön yüz başlık sadeleştirmesi —25 Ekim 2026

Kullanıcının yeni sunum kararıyla yalnız üst tarih alanındaki kupa/“Lig Sonuçları”, hesaplama türü/durumu, veri kesim zamanı ve canlı yenileme açıklaması kaldırıldı. Tarih aralığı korunur; “Son hesaplama” geçmiş lig seçicisinin altında küçük metindir, hesaplanmamış kayıtta “—” gösterilir. Alt üç lig kartının başlıkları ve hesaplama/panel/scheduler davranışı değişmedi. Yeni CSS/JS veya asset sürümü yok.

`league-table.blade.php` ve mevcut `LiveLeaguePresentationTest` güncellendi. İlgili6 PHP test/111 doğrulama ve değişen test dosyası Pint kontrolü geçti. Browser plugin mevcut olmadığından geçici Playwright/Chrome ile localhost üzerinde testing DB transaction fixture'ları kullanıldı: NL canlı/tek hesaplama ve FR henüz başlamamış kayıt,1440/390px; küçük tarihin seçici altında kalması, üst başlık/durum yokluğu, seçicide arama ve gerçek detay geçişi başarılı. JS hatası0, HTTP5xx0, yatay taşma0; dış servisler engellendi, fixture'lar geri alındı. Bu küçük revizyonda genel test paketi yeniden çalıştırılmadı. Görsel kanıtlar `/tmp/eduan-league-header-1440.png` ve `/tmp/eduan-league-header-390.png`.
