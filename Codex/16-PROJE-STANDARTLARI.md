# Eduan — proje standartları

**PWA64:** [64-PWA-GUNCELLEME-MANIFEST-VE-IKON-DUZELTMELERI.md](64-PWA-GUNCELLEME-MANIFEST-VE-IKON-DUZELTMELERI.md). APP_VERSION1.1.19 ve root public/pwa-sw.js PWA_CACHE_VERSION eşleşir; asset/ikon yayınında birlikte artırılır, URL/id değiştirilmez. Worker eski sekmeleri bekler, forced reload yok; özel HTML ve private/no-store response cache'e girmez. Maskable PNG güvenli içerik%56, marka oranı/opak canvas korunur; iOS180px PNG/default meta. Settings icon helper proje driver'ını kullanır, üretim bitmeden atomik manifest replacement yapılmaz; hata report+çevirili422. Chrome+dar test kanıtı fiziksel cihaz/yayın kabulü değildir.

**Güncel stabilite63:** [63-STABILITE-ILK-DUZELTMELER-VE-YAYIN-ADIMLARI.md](63-STABILITE-ILK-DUZELTMELER-VE-YAYIN-ADIMLARI.md). Favori test sonuç/toplamları ve çözülen sınav deneme sayısı yalnız current user; sınavda tamamlanmış deneme kuralı korunur. AI güncel aktör/izin/DB kota ve sabit site günüyle tüketim; public sayaç yetkili kaynak değildir. Dönem/lig eylemlerinde işlem izni yeniden denetlenir.11dar test57assert+mevcut dönem2/10 geçti; tüm ürün/gerçek paralel AI/PWA/yayın kabulü değildir. Sonraki fazlar raporda, üst menü görsel yenileme öncelikten çıkarıldı.

**Güncel kurulum:** [35-PHP85-VE-LARAVEL13-GUNCELLEME.md](35-PHP85-VE-LARAVEL13-GUNCELLEME.md). Yerel PHP8.5.8, Laravel13.34.0, Livewire4.4.7, Alert4.2, Excel4.0.3, PHPUnit13.4.1 kuruldu.132 bağımlılık; platform/audit kontrolü başarılı.16 farklı dar senaryo geçti; tüm ürün kabulü değil. Yeni Dockerfile build registry timeout verdi, doğrulanan mevcut yerel8.5 image kullanıldı. Tablolar/tarayıcı/yedek karşılıkları beklenir.

5 Ekim 2026. Bu belge yeni sayfa, panel modülü, form, tablo veya iş mantığı geliştirirken kullanılacak **revize edilebilir standart listesidir**. Maddelerin kodlarını kullanarak değişiklik isteyebilirsiniz: örneğin “F04 değişsin”, “P07 şöyle olsun”. Kullanıcının yeni kararı belgeyi geçersiz kılan eski maddeden üstündür.

`03-KOD-YAZIM-REHBERI.md` mevcut kodun yazım alışkanlıklarını anlatır; bu dosya o bilgiler ile kullanıcının kesin kararlarını yeni geliştirmeye uygulanacak kurallara dönüştürür. Her eski ekranın aşağıdaki hedeflere zaten uyduğu veya test edildiği iddia edilmez.

## 1. Kuralların dayanağı

| Durum | Anlamı |
|---|---|
| Kullanıcı kararı | Sohbette açıkça doğrulanan gereksinim; geliştirmede uygulanır. |
| Mevcut proje düzeni | Kaynakta gözlenen route/controller/Blade/Livewire/Action/model/asset yaklaşımı; yakındaki örnekle korunur. |
| Yeni geliştirme hedefi | Kullanıcının basitlik, güvenlik, hız, kararlılık ve test beklentisinden çıkan kabul ölçütü; eski kodun otomatik onarım izni değildir. |
| Sonraki faz | SQL, yükseltme, CDN, Turnstile, tablo değişimi gibi ayrıca planlanacak iş; bugün uygulanmış sayılmaz. |

## 2. Ortak çalışma ve kod standardı

- **O01 — Proje sahibinin tercihleri.** Proje elle ve bilinçli paket seçimiyle geliştirildi. Yeni özellikte mevcut düzeni, ilgili paketin rolünü ve yakın örneği önce öğren.
- **O02 — Basit kod.** Küçük, açık metotlar ve anlaşılır isimler kullan. Tek somut ihtiyacı karşılayan değişiklik yap; mevcut Action/helper düzenini izle.
- **O03 — Gereksinim sınırı.** Belirsiz iş kuralını uygulamadan sor. Yeni bulguyu dosya/örnek/etkiyle bildir; düzeltme kapsamını kullanıcıyla netleştir.
- **O04 — Dosya düzeni.** Controller `app/Http/Controllers`, bileşen `app/Livewire`, iş adımı `app/Actions`, model `app/Models`, enum `app/Enums`, ortak helper `app/Helpers`, trait `app/Traits` altında mevcut adlandırmayı takip eder.
- **O05 — Kod zinciri.** Yeni işte route → controller → Blade/Livewire → Action/helper → model/ilişki → test bağlantısını birlikte düşün. Controller'ın sayfa hazırlama işi kısa kalır.
- **O06 — Yazım.** UTF-8, LF, PHP/Blade dört boşluk, final newline; YAML iki boşluk. `.editorconfig` ve mevcut formatter önceliklidir. Formatter gerekirse yalnız değişen dosyalara uygulanır.
- **O07 — Form düzeni.** Yakındaki örneğe göre `mount`, `rules`, `validationAttributes`, `save`/işlem, `render` düzeni kullanılır. Kullanıcı mesajları ve etiketler çeviri sisteminden gelir.
- **O08 — Tip ve null.** Modelin nullable alanını string özelliğe atarken açık boş/fallback davranışı seç. Enum değerini mevcut enumdan al. Yeni test fixture'ı geçerli locale/ilişki/zorunlu alanları içerir.
- **O09 — Sunucu kaynağı.** İzin, sahiplik, süre, doğru cevap, kayıt kimliği ve ilişki sunucudan doğrulanır. Livewire public alanları veri girişi sayılır; gerekli değişmez alanlar mevcut Locked düzeniyle korunur.
- **O10 — Çoklu yazma.** Bağlı kayıtların birlikte tutarlı olması gereken işlemde transaction ve başarısızlık sonucu açık olmalıdır. Sonuç tamamlaması gibi tekrar çağrılabilen işlerde idempotency korunur.
- **O11 — Kapsamlı temizlik ayrı iştir.** Yeni özellik çevresindeki isim, paket, route veya mimari değişikliği ancak istenen kapsam içinde yapılır. Mevcut `Exam`/`Test` ters eşlemesi aynı kalır.
- **O12 — Operasyon izni.** Migration, veri taşıma/silme, canlı deploy veya dış servise gerçek işlem gönderme için somut plan ve onay gerekir. Testler yalnız ayrı `testing` DB'de çalışır.

## 3. Ön yüz — site ve öğrenci ekranları

- **F01 — Temel görünüm.** `resources/views/frontend/layouts/app.blade.php`, mevcut Blade bileşenleri, Bootstrap 5, jQuery, tema ve `public/assets/app.css|js` düzeni kullanılır. Yakındaki benzer sayfanın görsel dili korunur.
- **F02 — Route standardı.** `routes/frontend.php` içindeki açık adlandırılmış route düzenini izle; URL'yi route adıyla üret. Mevcut detail/start/solutions ve hesap rotalarının dil/model bağlamasını doğrula.
- **F03 — Dil.** Hedef varsayılan `nl`, ikinci dil İngilizce, üçüncü Türkçe. UI metni `__()` ile, içerik mevcut modelin locale satırı veya Spatie Translatable yaklaşımıyla yönetilir. Mevcut DB/session ayarı kaynak varsayılanından farklı olabilir.
- **F04 — Hız önceliği.** Ön yüzün çok hızlı ve kararlı açılması ana kullanıcı gereksinimidir. Yeni sayfa yalnız gereken sorgu/ilişki/assetleri yükler; performans iddiası gerçek URL ölçümüyle desteklenir.
- **F05 — İlk görsel.** İlk ekrandaki ana görsel normal/erken yüklenir; bu görsel lazy yapılmaz. Ekran dışında kalan görsellerin stratejisi sayfa ihtiyacına göre belirlenir. Ölçmeden bütün görsellere aynı yükleme davranışı verilmez.
- **F06 — Responsive.** Mevcut Bootstrap breakpoint/grid düzeniyle mobil ve masaüstü kullanım korunur. Mobilde form, tablo ve işlem düğmeleri taşmamalı; değişen görünür akış ilgili tarayıcı kontrolüyle doğrulanır.
- **F07 — SEO.** Sayfa başlığı, açıklama, gerçek canonical link, doğru sosyal URL, robots ve gerektiğinde sitemap görünürlüğü tanımlanır. Hesap/auth/çözüm gibi özel rotaların noindex davranışı korunur. Güncel54 ile public canonical/hreflang/sitemap açık dil URL’si ve gerçek çeviri kapsamıyla uygulanır; yeni URL mimarisi değişimi ayrıca kararlaştırılır.
- **F08 — Giriş ve sınırları.** Giriş hesap+IP beş başarısız deneme/dakika; kayıt ve parola kurtarma IP beş istek/dakika. Güvenlik limiter deposu genel cache temizliğinden etkilenmez. Proxy/IP zinciri ortam bilgisiyle doğrulanır.
- **F09 — Süresiz test.** Seçim mevcut soruya ait olmalıdır; doğru/yanlış DB cevabından hesaplanır. Mevcut süresiz ekranda ilk kaydedilen cevap kilitlidir. Bütün sorular tamamlandığında sonuç tamamlanır; tekrar çözüm ayrı geçmiş kaydı oluşturur.
- **F10 — Süreli sınav.** Aynı kullanıcı/sınavda tek aktif deneme; yeniden açma cevapları ve ilk deadline'ı korur. Süre dolana kadar seçim değişebilir/kaldırılabilir. Süre sonunda kaydedilmiş cevaplar tamamlanır; tamamlanmış denemeden sonra yeni tam süreli deneme serbesttir.
- **F11 — Öğrenci sahipliği.** Hesap sonuç/favori/profil/sınav işlemleri oturumlu kullanıcının verisiyle sınırlanır. Sadece URL'de veya public alanda gelen kimliğe göre başka kullanıcı kaydı okunmaz/yazılmaz.
- **F12 — PWA.** İsim-soyisim çevrimdışında da gösterilir; yalnız bu metin ayrıca saklanır. Guest sayfası kaydı temizler. Hesap, sonuç, test/sınav ve panel HTML'si; token, upload/PDF ve Livewire istekleri offline cache'e girmez. Kullanıcı mevcut PWA yaklaşımını kabul etti; gerçek cihaz kanıtı ayrıca belirtilir.
- **F13 — Cookie ve izleme.** Mevcut CookieConsent/analytics bağlantısının dil ve consent akışı korunur. Yeni takip sistemi veya consent davranışı ayrıca kararlaştırılır.
- **F14 — Hata/boş durum.** Formda mevcut alan hata bileşenleri, yüklenme durumunda mevcut Livewire gösterimi, boş listede anlaşılır çevirili mesaj kullanılır. Bir null kayıt veya boş sonuç sayfayı kırmamalı.
- **F15 — Sayfa başı.** Mevcut header/logo/menü ve gerekiyorsa breadcrumb korunur. Sayfa başlığı ve ana işlem mobilde anlaşılırdır; aynı tür sayfalar aynı sırayı izler.
- **F16 — Grid ve görsel dil.** Yakındaki container/grid, boşluk, font ve renk düzeni kullanılır. Kart başlıkları, açıklama, görsel ve işlem konumları ders/konu/test/blog sayfalarında tutarlıdır.
- **F17 — Filtre konumu.** Arama ve ilgili ders/konu filtreleri sonuçlardan önce görünür. Uygulama/temizleme ilk sayfaya döner; aktif filtre görünür kalır.
- **F18 — İlk HTML.** Temel başlık/açıklama ve kullanıcıya gerekli ilk içerik ilk HTML'de bulunması hedeflenir. SEO içeriğini yalnız sonradan gelen JS isteğine bağlama; lazy stratejisi sayfa bazında ölçülür.
- **F19 — Lazy ayrımı.** Görsel lazy, Livewire bileşen lazy ve Eloquent ilişki lazy ayrı işlerdir: sırasıyla ilk görsel/yerleşim, placeholder/ek istek, N+1 değerlendirilir. Toplu lazy kaldırma yapılmaz.
- **F20 — Form görünümü.** Etiket inputla ilişkilidir, hata/yardım ilgili alanın yanında; zorunlu alan ve submit loading anlaşılırdır. Giriş/kayıt/parola formları yakın sade düzeni korur.
- **F21 — Test/sınav görünümü.** Soru, seçenekler, ilerleme ve gerekiyorsa kalan süre net görünür. Yeniden açılan deneme seçimi/süreyi korur; tamamlama ve yeni deneme ayrı işlemlerdir.
- **F22 — Erişilebilirlik.** Düğme/link doğru öğedir; klavye odağı görünür; ikonun açıklaması vardır. Renk tek başına doğru/yanlış veya hata anlamı taşımaz.
- **F23 — Hesap sayfaları.** Profil, favori, istatistik, geçmiş ve lig ortak navigasyon/başlık düzenini izler. Boş geçmiş mesajı açık; cache kullanıcı kapsamını korur.
- **F24 — Görsel kabul.** Mobil/masaüstünde başlık, taşma, filtre, kart, form hatası ve loading kontrol edilir. HTTP 200 görsel veya gerçek PWA cihaz kabulü değildir. Sayfa bazlı liste `20` dosyasındadır.

## 4. Arka yüz — panel, form ve tablo

- **P01 — Temel görünüm.** `resources/views/backend/layouts/app.blade.php`, OneUI, Bootstrap 5, jQuery, mevcut `x-*` bileşenleri ve `public/backend/assets/app.css|js` kullanılır. `block`, `block-header`, `block-content`, `row/col-*`, `form-control` ve `btn-alt-*` yakın ekran örneğine göre seçilir.
- **P02 — Route kapıları.** Panel config öneki mevcutta `/admin`; auth, locale, dashboard ve kaynak `can:*` route kapıları korunur. `routes/backend.php` AutoRoute düzeninde yeni public controller metodunun hangi HTTP rotasına açıldığı doğrulanır.
- **P03 — İşlem izni.** Kaynak için `:view`, `:add`, `:update`, `:delete` gibi mevcut izin adları kullanılır. Menü/düğme ve gerçek sunucu işlem kapısı aynı ihtiyacı temsil eder. Save sırasında izni formdaki değiştirilebilir `permission` alanından türetme.
- **P04 — Form açma ve kaydetme.** Var olan kayıtta alan/ilişki/null değerleri doğru yükle; save sırasında yeniden izin/sahiplik kontrol et. Başarılı kayıt ancak işlemin gerçek sonucuna dayanarak doğru dönüş ve mesaj üretir; mailde kullanıcı tarafından kabul edilmiş log-only istisna P10'dadır.
- **P05 — Form bileşenleri.** `rules`, çevirili `validationAttributes`, mevcut `x-badge.error`, `wire:submit`, `wire:loading` ve form bölümleri takip edilir. TinyMCE, Dropify, Flatpickr, bootstrap-select gibi araçlar yalnız mevcut ihtiyaçla kullanılır.
- **P06 — Mevcut ve hedef tablo.** Rappasoft eski kaynak uygulamasıdır;34 ile paket kaldırıldı,başlangıçtaki31 sınıftan ders/konu/kullanıcı38 ile taşındı;28 sınıf bekler. Kullanıcı Laravel yükseltmesinden sonra kaldırıp Lara Football'daki kendi Blade + Bootstrap Table + server-side JSON düzenine geçmeyi seçti; yeni kalıcı standart budur. Paket kaldırıldı; üç sayfanın pilotu38 içinde; paket/framework yükseltmesi35 ile uygulandı. Ayrıntı `19-RAPPASOFT-BOOTSTRAP-TABLE-GECIS.md`.
- **P07 — Liste sorgusu.** Kullanıcı/rol/dil/durum/ders/konu filtreleri SQL'de uygulanır. Builder hedef kullanıcı veya öğretmen kapsamını kontrol eder. Sayfalama/sort/search ve ilişkiler yakındaki tabloyla uyumludur; toplam kayıt sorgusu veya eager loading ihtiyacı somut ekrana göre seçilir.
- **P08 — Öğretmen/okul.** Aynı rol/kapsamdır; yalnız kendi öğrencilerini görür/yönetir. Öğretmen yalnız kendisine öğrenci bağlayıp ayırabilir; `users:student-*`/`users:teacher-*` işlem izinleri de gerekir. Başka öğretmen adına işlem yapılamaz. Atanabilir öğrenci havuzu mevcut `users:teacher-findable` iznidir.
- **P09 — Dosyalar.** Ortak upload panel erişimi ve ilgili yazma izniyle korunur. Görsel JPEG/PNG/WebP/GIF/BMP, en fazla 10 MB; gerçek MIME ve uzantı tutarlı olur. Ayrı sınav PDF alanı PDF/10 MB/izin kontrolü kullanır. Genel settings `files` alanı reddedilir.
- **P10 — Mail istisnası.** Yeni `users:send-email` zorunludur. Öğretmen hedef öğrenci bağlamı denetlenir; girilen adres DB mailiyle eşleştirilmez. Toplu kapsam tek sorguyla bütün satırlarda ve tekilleştirmeden önce doğrulanır. Enqueue hataları loglanır; ayrı hata ekranı kullanıcı tarafından istenmedi. Gerçek teslimat ayrıca worker kanıtıdır.
- **P11 — Dışa aktarma.** Ekrandaki izin/sahiplik/filtre ve toplam sözleşmesi export'ta da korunur. Excel gerçek dosya olarak açılabilmeli; başlık, kolon, dil, sayı ve yüzde değerleri kontrol edilir. PDF/Word yeni gereksinimi ayrı tanımlanır; mevcut Excel testinden onların çalıştığı sonucu çıkarılmaz.
- **P12 — İstatistik toplamı.** Aynı öğrenci + aynı test/sınavın denemeleri tek satırda toplanır. Toplam soru/doğru/yanlış ve sınav puanı SUM; başarı `toplam doğru / toplam soru × 100` olur. Örnek 7/10 + 5/10 = 12/20 = %60. Yüzdelerin basit ortalaması kullanılmaz.
- **P13 — Silme.** Her modelin gerçek silme yöntemi ve bağlı kayıtları kontrol edilir. Ders/konu bağlı içerik varken mevcut engeli korur; sistem Page silinmez. `Page` soft delete trait'i kullanmaz; ders/konu/soru/test/sınavda farklı davranış vardır. Testte model davranışını varsayma; canlı silme ayrıca onay ister.
- **P14 — Panel hızı.** İyi hız ve kararlılık gerekir; ön yüzün en yüksek önceliğiyle birlikte ele alınır. Özellikle tablo count, filtre, ilişki, gereksiz cache temizliği ve export hacmi ölçülür; büyük yeniden yazım ölçüm/karar olmadan başlamaz.
- **P15 — OneUI.** OneUI devam eder; Vue.js eklenmez. Tablo değişimi bütün Livewire formlarını veya tema düzenini değiştirmez.
- **P16 — Sayfa iskeleti.** Başlık/breadcrumb → block başlığı/ana işlemler → filtre bandı → tablo araçları → sonuç/pagination sırası izlenir. Veri isteği sayfa kabuğunun görünmesini geciktirmez.
- **P17 — Filtre bandı.** Lara Football `x-admin.filter-bar` örneğinde etiketli Bootstrap grid, açık/çerçeveli alan, sağda normal genişlikte Filtrele/Temizle kullanılır. Eduan'ın ders/konu/dil/öğrenci filtreleri kendi sayfasına göre seçilir.
- **P18 — Araç hizası.** Yenile ve ilgili tablo araçları solda; tablo araması sağda. Ekle düğmesi block başlığında doğru kaynak izniyle görünür.
- **P19 — Başlatma.** Blade başlık/filtreleri sunar; tablo bir kez initialize edilir, izinli JSON endpoint'inden yalnız ilgili sayfayı ister. Gizli sekmede ilk açılış yüklemesi/tekrar açılış resetView ayrı yönetilir.
- **P20 — Kolonlar.** Çevirili başlık, tutarlı metin/sayı/tarih/eylem hizası, gerektiğinde sınırlı genişlik kullanılır. Kullanıcı metni HTML kaçırılarak render edilir; ikon/görsel/badge yakın düzeni izler.
- **P21 — Durumlar.** Loading, kayıt yok ve istek hatası ayrılır. 403/422/500 ya da bozuk JSON boş başarılı tablo sayılmaz; sade hata/yeniden deneme sunulur.
- **P22 — JSON.** Server-side yanıt total ve rows; total filtrelenmiş toplam, rows yalnız sayfadır. Limit/offset/search/sort/order doğrulanır; SQL sort kolonları sunucuda izinli listeyle eşlenir.
- **P23 — Filtre yenileme.** Filtrele/Temizle tam sayfa reload yerine tablo refresh ve ilk sayfaya dönüş yapar. Ders değişince konu seçenekleri daralır; eski uygunsuz konu bırakılmaz.
- **P24 — Cache.** Sayfa/filtre/sort/dil/erişim kapsamı anahtara girer; filtre seçenekleri de gereken kapsamla cache'lenir. Bootstrap Table cache seçeneği Laravel sonuç cache'inin yerine geçmez. Üç pilot sayfada JSON60 saniye, seçenek1 saat; Yenile cache atlayarak yükler. Diğer sayfalar ayrıca netleşir.
- **P25 — Mobil.** Table-responsive ve erişilebilir eylemler korunur; yatay kaydırma/pagination/filtreler taşmaz. Bütün kolonları küçük ekrana sıkıştırma zorunluluğu yoktur.
- **P26 — Form ve detay.** Ana bilgi/ilişkiler/durum/ek içerik ve kaydet/dön sırası aynı tür formda tutarlıdır. Lara Football offcanvas referanstır; 38 pilotunda readonly Görüntüle paneli uygulanır; mevcut Eduan düzenle sayfaları ayrı kalır.
- **P27 — Tablo dili.** Bootstrap Table aktif nl/en/tr arayüzüne eşlenir; Lara Football'ın tr/en eşlemesi nl için aynen kopyalanmaz.
- **P28 — Araçların dili.** Editör, yükleyici normal/hata mesajları ve tarih seçiciler panelin aktif arayüz dilini izler. Düzenlenen içerik locale'i ayrı kalır. İngilizce yerleşik dil için olmayan script URL'si üretilmez. TinyMCE NL/TR gerçek dil dosyasıyla eşlenir; Livewire JavaScript nesnesinin içinde Blade kontrol bloğu kullanma. Güncel kanıt `25` içindedir.
- **P28 — Geçiş kabulü.** Her tablonun builder/filtre/kolon/eylem/modal/export sözleşmesi korunur. JSON testleri yanında gerçek browser initialize/rows/refresh/search/filter/console kontrolü yapılır; yalnız PHP geçmesi geçişi bitirmez.

## 5. Veritabanı, SQL ve sonuç kuralları

- **D01 — İsim eşlemesi.** Site “test” = `Exam/exams`; site “sınav” = `Test/tests`. Yeni route/model/SQL isimlerini bu gerçekle eşleştir. Genel isim taşıması migration/veri/URL planı gerektirir.
- **D02 — Soru havuzu.** Ders → konu → soru → cevap ilişkisi korunur. Yeni yazmada seçilen konunun seçilen derse, cevabın ilgili soruya ait olması hedeflenir. Eski CRUD testinin geçtiği bu bütünlük kontrolünün bütün eski formlarda bulunduğunu kanıtlamaz.
- **D03 — Tek doğru seçenek.** Mevcut soru formu sıfır veya birden fazla doğru cevabı reddeder. Değişiklik soru/cevap kayıtlarını ve ilişkilerini birlikte tutarlı bırakır.
- **D04 — Lig.** Süresiz testin yarım/tamam bütün denemelerindeki kaydedilmiş cevaplar, süreli sınavın bütün tamamlanmış denemeleri toplanır. Kullanıcı son deneme seçimini iptal etti; yeni kod bu filtreyi eklemez.
- **D05 — Lig sırası/dönemi.** Mevcut dönem cevap detayının updated_at aralığıdır. Puan DESC → doğru DESC → süre ASC, tam eşitlikte ortak RANK korunur. Lig ve P12 istatistik ayrı sorgulardır.
- **D06 — Geçmiş.** Yeni deneme yeni kayıt; tamamlanmış sonuç değiştirilmez. Eski sonuç taşıma/silme/backfill canlı SQL görülüp planlanmadan yapılmaz.
- **D07 — Şema kaynağı.** Migration listesi fiziksel şemayı tek başına kanıtlamaz. Canlı migration tablosu/SQL yedeği geldiğinde kolon/indeks/FK/elle eklenmiş alanlar salt okunur eşlenir; sonra migration kararı alınır.
- **D08 — Performans.** Sorguda sayfalama/SQL filtresi/uygun eager loading kullan; indeks değişikliği veri hacmi ve EXPLAIN ile planlanır. Cache anahtarının locale/kullanıcı/izin kapsamı belirtilir. `resetCache()` geniş yan etkisi yeni işte ayrıca değerlendirilir.
- **D09 — Sırlar ve günlük.** SQL yedeği, token, SMTP ve ortam sırları dokümana/Git'e/açık HTML'ye kopyalanmaz. Loggable ve uygulama logunun kaydettiği alanlar değerlendirilir. Test ve gerçek operasyon günlükleri birbirine karıştırılmaz.
- **D10 — Liste kararlılığı.** Sayfalı cache sayfayı içerir; count ve rows aynı filtre/erişim kapsamına dayanır. Eşit sort değerinde mevcut deterministik ikinci sıra korunur.
- **D11 — SQL maliyeti.** Count ve rows ayrı ölçülür; gereken kolon/ilişkiler seçilir. N+1 geçerli fixture/ölçümle gösterilir; körlemesine bütün ilişkiler eager-load edilmez.
- **D12 — Yenileme.** Kayıt/atama/izin değişimi ilgili cache'i geçersizleştirir. TTL ve anahtar kapsamı ayrıca planlanır; bütün uygulama cache'ini temizlemek yeni standardın varsayılanı değildir.

## 6. Test ve teslim standardı

- **T01 — İzolasyon.** Yalnız testing DB; ortak TestCase yanlış DB'yi bootstrap öncesi/sonrası reddeder. Ana yerel/canlı migration/seed/fresh test hazırlığı için çalıştırılmaz.
- **T02 — Özellik testi.** Değişen işin başarılı kaydı, yeniden açılışı, ilgili hatalı girdi ve gerekli izin/sahiplik sınırı doğrulanır. CRUD'da oluştur/oku/düzenle/sil; ilişki ve null durumları özellikle seçilir.
- **T03 — Gerçek yan etki yok.** Mail/queue/dosya/dış servis için uygun fake veya geçici test çıktısı kullanılır. Excel gerçekten üretilip açılır; SMTP gerçek gönderim yapılmaz.
- **T04 — En dar koşu.** İlgili dosya/metotları bir kez çalıştır; tekrar yalnız görülen hata veya somut regresyon riskini çözmek için yapılır. Kullanıcının istediği kapsamlı işlev kontrolü modüllere bölünür; ilgisiz bütün suite/build her tur koşturulmaz.
- **T05 — Bulgular dürüst raporlanır.** Test fixture hatası uygulama hatası diye sunulmaz. Uygulama hatasını göstermek için doğru iş kuralı testi kullanılır; geçsin diye yanlış davranış standartlaştırılmaz.
- **T06 — UI ayrı kanıt.** PHP/Livewire başarı testi tarayıcı JS/TinyMCE/selectpicker/drag-sort veya mobil offline kanıtı değildir. Görünür değişikliğin gerekiyorsa ilgili tarayıcı akışı kontrol edilir; kontrol yapılamadıysa yazılır.
- **T07 — Sonuç kaydı.** Değişen dosya, neden, test komutu/sayısı, sonucu ve kalan sınır ilgili Codex kaydına eklenir. Rehberin index bölümünde dosyanın adı ve en az sekiz açık madde bulunur.
- **T08 — Tam kapsam iddiası.** “Bütün proje sorunsuz/güvenli” yalnız bazı testler geçti diye söylenmez. Modül bazında geçen/eksik/gerçek ortam gerektiren kontroller ayrılır. Güvenlik ve doğruluk kabul değerlendirmesi kullanıcının sonraki aşamasıdır.

## 7. Yeni özellik için kısa teslim listesi

1. Özelliğin ön yüz/panel yeri, kullanıcı rolü, görünürlüğü ve iş kuralı açıklanır.
2. En yakın mevcut ekran örneği ve kullanılacak route/controller/Livewire/Action/model zinciri seçilir.
3. İzinler, dil alanları, ilişkiler, null/boş durum ve varsa süre/cache/export/dosya davranışı belirlenir.
4. Yakın ekranın Bootstrap/Blade ve özel asset düzeni korunarak basit uygulama yapılır.
5. Özelliğin gerekli CRUD/validation/izin/sahiplik ve çıktı testleri çalıştırılır.
6. Sonuç ve sınırlamalar ilgili rehbere yazılır; yalnız gerekli indeks özeti güncellenir.

## 8. Sonraki fazlar — uygulanmış standart değildir

- Laravel 13 hedefi ve paket sadeleştirme; canlı şema/ortam ve uyumlu paket sürümü incelemesi gerekir.
- Spatie Permission korunur; Settings devam edebilir. Rappasoft çıkarılacak, Lara Football örneğinde Bootstrap Table'a geçilecek. Son paket kararıyla Rappasoft önce hedef Composer listesinden çıkarılacak; doğrudan Laravel13/Livewire4 sonrası kendi tabloları tamamlanacak. Yeni sıra ve uyarlama kaydı 32 içindedir.
- Cloudflare CDN ve Turnstile ileriki geliştirmedir. Belge çıktısının uzak görsel okuyamadığı kanıtlanırsa gerekli lokal karşılık korunur.
- Yeni deterministik seed ve eski sonuç taşıma canlı veri görülmeden üretime uygulanmaz.
- Yeni `users:send-email` izninin mevcut DB/rollere hedefli aktivasyonu ayrıca yapılmalıdır; bütün PermissionSeeder'ı çalıştırma yetkisi bu belgeden çıkmaz.

## 9. Kullanıcının revizyonuna açık konular

Bu belgeyi oluşturmak için ek karar gerekmiyor; aşağıdakiler yeni özellik tasarlanırken somut örnekle sorulacak tercihlerdir:

- Tablo örneği artık Lara Football'dır. Her Eduan tablosunun filtre/kolon/eylemi kendi kaynağından çıkarılır; cache TTL ve yenileme davranışı geçiş planında somutlaştırılır.
- Yeni form hangi dil yaklaşımını kullanacak: locale bazlı ayrı kayıt mı, mevcut translatable JSON alanı mı?
- Yeni istatistikte deneme/soru bazlı toplamın kapsamı, dönem ve yarım deneme davranışı ne olacak? P12 mevcut ekran içindir; her yeni rapora otomatik genişletilmez.
- Yeni görsel alanında mevcut upload yolu mu, ileriki CDN çözümü mü kullanılacak? CDN henüz uygulanmadı.
- Yeni özellik için hangi gerçek ekran/ölçüm kabul ölçütü gerekli? Kesin hız sayısı, font/boyut/renk/boşluk tokenı belirlenmedi; yakın sayfa ve mevcut theme esastır.

Kanıt kaynakları: `AGENTS.md`, `.editorconfig`, `routes/frontend.php`, `routes/backend.php`, iki layout, `LessonForm`, `QuestionForm`, `ExamForm`, `TestForm`, tablo trait'leri, `config/livewire-tables.php`, `AuthorizeStudentAccess`, `AuthorizeUserMail`, upload Action, `CalculateLeagueResult`, `ResultStatsCustomTable` ve ilgili testler. Sonraki standart revizyonu bu dosyaya ve gerektiğinde AGENTS/özellik rehberine birlikte işlenir.


## 10. Son paket ve cache standardı

- **P29 — Tablo bağımsızlığı.** Rappasoft geçici mevcut koddur; hedef Composer listesine girmez. Lara Football Blade + Bootstrap Table JSON total/rows, OneUI ve mevcut kolon/filtre/eylem davranışı korunur. Vue.js eklenmez.
- **P30 — Filtre cache'i.** Ortak seçenekler ve kullanıcıya özel öğrenci/sonuç seçenekleri ayrı kapsamlarda tutulur. Aynı filtrelerin sayfalarda tekrarlanması ortak cache'e adaydır; öğretmen kapsamı atlanmaz.
- **P31 — Değişiklikte yenileme.** CRUD/soft-delete/çeviri/rol/atama/sonuç değişikliği ilgili seçenek cache'ini geçersizleştirir; bütün cache flush yeni standart değildir. “Değişene kadar” hedefi invalidation ile sağlanır.
- **P32 — JSON cache kapsamı.** Locale, erişim kapsamı, normalize filtre/search/sort/order, page/limit/offset ve veri sürümü anahtara girer. Total/rows aynı sorgu kapsamına dayanır; izin kontrolü cache okumadan önce yapılır.
- **P33 — Cache hacmi.** Search/sort input'ları allowlist/sayfa sınırıyla doğrulanır; sınırsız kalıcı query cache oluşturulmaz. Driver tags desteği varsayılmaz; TTL ve Yenile davranışı sayfa bazlı seçilir.
- **D13 — Paket karşılığı.** Langscanner ve Rappasoft kaldırılırken çeviri tarama ve tablo işlevi kendi koduyla korunur. Backup/S3 çıkarma kesinleşti; DB/dosya/ZIP-R2 manuel yedek karşılığı referansla tamamlanacak. Telescope/Sentry/Debugbar karşılığı istenmedi.
- **D14 — Hedef stack.** PHP8.5 üzerinde doğrudan Laravel13 + Livewire4; mevcut class/Blade/Action standardı korunur. Yeni major sürüm Vue/SFC/Flux veya mimari yeniden yazma izni değildir. Excel4 ve Alert4 API kontrolü gerekir.
- **T09 — Hedef sürüm kanıtı.** Eski Laravel11 testleri hedef13/LW4 kabulü sayılmaz. Hedef vendor kurulduktan sonra değişen özelliklerin dar hata/mantık testleri, platform ve gerekli browser akışı doğrulanır. Bu rehber turunda uygulama/test stack'i değişmedi.

Güncel paket adayları, API ve hosting/cache sınırları: [32-PAKET-KARARLARI-VE-SITEGROUND-HAZIRLIGI.md](32-PAKET-KARARLARI-VE-SITEGROUND-HAZIRLIGI.md).


### PHP8.5 hedefi ve kanıt standardı

- **D15 — Açık null türü.** Null varsayılanlı typed parametreler açık nullable (`?Type` veya `Type|null`) olur; PHP8.5 lint/uyarı kontrolü yapılır. Mevcut21dosya adayları33 içinde; bu rehber değişikliğinde kod düzeltilmedi.
- **T10 — Ortam ayrımı.** SSHPHP8.2 modül listesi8.5 kabulü sayılmaz. Web/CLI/cron8.5 ayrı kanıtlanır; canlıPHP değişimi yayın aşamasındadır. Solver/lint, PHPUnit ve browser kabulünün yerine geçmez.

Kaynak: [33-PHP85-GECIS-KONTROLU-VE-SSH-KANITI.md](33-PHP85-GECIS-KONTROLU-VE-SSH-KANITI.md).

## 11. Üç sayfa pilotunun uygulanan standardı — 6 Ekim

- **P34 — Pilot kaynağı.** Ders/konu/kullanıcı yeni sayfaları `38-BOOTSTRAP-TABLE-UC-SAYFA-PILOTU.md` ve gerçek server-table bileşenleriyle örneklenir. Diğer28 Rappasoft sınıfına uygulanmış sayılmaz.
- **P35 — Readonly detay.** Görüntüle server izin/kapsam kontrolüyle sağ panel açar; bilgi ve ilişkili sayılar günceldir. Düzenle mevcut sayfaya gider. Detail ID Locked olur; öğretmen scope işlem sırasında yeniden sorgulanır.
- **P36 — Cache kabulü.** JSON60sn/seçenek1saat; locale/actor/izin/scope/page/filtre/sort anahtarı; revision invalidation. Yenile cache bypass. Mevcut global flush etkisi ayrıca kaydedilir, performans kazanımı ölçülmeden iddia edilmez.
- **P37 — Pilot görünümü.** Filtre bandı1rem, hücre dikey padding.85rem/line-height1.45, araç solda/arama sağda, dikey işlemler; readonly panel760px veya mobil ekran. Geniş tablo kendi alanında kaydırılır.
- **P38 — Hata ve durum.** JSON hata yanıtı anlaşılır kutu gösterir; başarılı yeniden yükleme kutuyu kaldırır. Boş sonuç hatadan ayrıdır. Filtre/reset ilk sayfaya döner; URL filtre/sort/page restore sınanır.
- **P39 — Geçiş testi.** İlgili endpoint/listenin normal, geçersiz, yetkisiz, scope, cache ve ilişkili CRUD testleri; ayrıca üç ekranın masaüstü/mobil davranışı doğrulanır. Başka tablolar aktarılmadan genel suite başarısı söylenmez.

## 12. Pilot tipografi ve detay görsel standardı — 6 Ekim

- **P40 — Tablo yazısı.** Üç pilotta hücre/başlık15px, satır işlem16px, badge14px. Filtre görünümü korunur;14→16 gibi büyütme tüm siteye otomatik uygulanmaz.
- **P41 — Detay özeti.** Kayıt adı/kodu veya ilişkili bağlam, ikon, mevcut sayılar için kartlar; bilgi satırları16px, sayılar24px. Bilgi salt okunur; Düzenle mevcut sayfaya gider.
- **P42 — Alt veri kapsamı.** Ders/konu/kullanıcı ilişki listesi ve sekmelerin kapsamı kullanıcı yanıtından sonra uygulanır; listede kendi kaynak view izni ve parent ilişkisi doğrulanır.39 içindeki üç soru bekliyor.
- **P43 — Asset sürümü.** Yerel CSS/JavaScript değişikliğinde `.env` içindeki `APP_VERSION` patch değeri artırılır. `config/app.php` bu değeri `app.version` olarak sunar; panel app.css/app.js ve server-table.css/server-table.js URL'lerindeki `?v=` aynı sürümü kullanır.6 Ekim görsel düzeltmesi sonrası1.1.0→1.1.1 yapıldı. Config cache kullanılan ortamda değişiklik dağıtımın config cache yenilemesiyle etkinleşir; sırf asset değişti diye bütün uygulama/DB cache'i temizlenmez.

## 13. Ayarlar & Yetkiler menüsünün uygulanan standardı — 6 Ekim

- **P44 — Menü ailesi.** Dönemler form; Yetkiler/Loglar JSON tablo; Ayarlar13 tabdır. Formları sırf görsel standart için tabloya dönüştürme; setting alan/ad/değer/action akışını koru.
- **P45 — Alan görünümü.** `x-admin.management-assets` ve `admin-management` kapsamı; kontrol16px/en az44px, kartlara ayrılan dönem ve yetki grupları. Global OneUI/site stilini değiştirme.
- **P46 — Ayarlar navigasyonu.** Masaüstü ikonlu275px menü/esnek içerik; mobil iki sütun ve uzun etiketlerde satır kırılması; aktif bölüm aria-current ile belirtilir. TinyMCE/Dropify/dil alanları mevcut kaynakta kalır.
- **P47 — Yeni iki liste.** Yetkiler name ASC; Loglar id DESC. Arama/sort/pagination ve cache60sn/seçenek1saat/Yenile bypass ortak standardı sürer; Loglar kullanıcı/işlem filtrelerini korur. Form filtresi olmayan rol listesine boş filtre bandı ekleme.
- **P48 — Detay ve invalidation.** Log izin resource settings, panel adı logs olur; ortak trait bunu destekler. Log ham data JSON listesinde bulunmaz. Role/user sync sonrası rol/kullanıcı/seçenek cache'i yenilenir; Eloquent Log değişimi logs cache'ini yeniler. Doğrudan insert observer sınırı ayrıca kaydedilir.
- **P49 — Asset sürümü.** Management CSS de app.version kullanır; bu aşamada APP_VERSION1.1.2. Yeni CSS/JS değişikliğinde patch artırılır, config cache yayın sırasında yenilenir.
- **T11 — Menü kanıtı.**13 dar test/134 doğrulama ve Chrome ilgili masaüstü/mobil kontrolü; mevcut dönem PHP işlem izinleri ve eski log-detail hata adayları ayrı bulgu kaydıdır. Kalan26 tablo aktarılmadan bütün ürün kabulü verilmez.

Kaynak ve dosya listesi: [40-AYARLAR-YETKILER-MENU-GECISI.md](40-AYARLAR-YETKILER-MENU-GECISI.md).

## 14. Sağ panel ve kart görünümü standardı — 6 Ekim

- **P50 — Sağ offcanvas.** Panelde bütün offcanvas-end width100% ve max-width840px; büyük ekranda840px, dar ekranda viewport genişliği. Eski760px override kaldırılır; panel CSS sağ w-sm-75/w-sm-50 utility'lerini geçersiz kılar. Ortak Livewire Blade ön yüzü etkilememek için korunur. Sol/üst/alt yön bu standart değildir.
- **P51 — Dönem kartları.** Toplu Kaydet korunur; masaüstü iki, mobil tek bağımsız kart. Ad/durum/tarih bindingleri ve geçerli ID/Flatpickr başlangıcı korunur.
- **P52 — Rol türü.** Üç mevcut radio değerini koru; seçilebilir label en az58px yüksek olsun. İşlem izin matrisi ayrı kalır.
- **P53 — Log detay.** Metadata bilgi kartları, hizalı alan/eski/güncel değerler; mevcut mavi diff vurgusu, isteğe bağlı yalnız değişen alanlar. Son42 onayıyla hassas alan değerleri *** gösterilir; alan adı ve değişiklik işareti korunur.
- **P54 — Kullanıcı ilişki özeti.** ADMIN sayaç yok; TEACHER öğrenci sayısı; USER öğretmen sayısı. İşlem türü izinleri esas, rol adı metniyle karar verme.
- **P55 — Kullanıcı kartı.** Son kullanıcı kararıyla varsayılan custom view/kart, Yenile yanında görünüm düğmesi; mevcut sayfalı JSON rows üzerinden kart. Ayrı veri API'si/bütün kayıtları client'a alma yok; aynı arama/filtre/sıralama/pagination/cache ve işlem yetkileri korunur. Diğer listelere otomatik genişletilmez.
- **P56 — Görünüm durumları.** Kart loading/empty/error state gösterir; başarılı yanıt hata sonrası yeni veriyi render eder. Kullanıcı verisi escape edilir, action yetkisi aynı sunucu eyleminde tekrar denetlenir.

Kanıt/ortam sınırı ve dosyalar: [41-KART-GORUNUMU-VE-OFFCANVAS-STANDARDI.md](41-KART-GORUNUMU-VE-OFFCANVAS-STANDARDI.md).

## 15. Log maskeleme ve Bilgi Girişleri standardı — 6 Ekim

- **P57 — Hassas log değeri.** Alan adı kalır, parola/token/secret/API anahtarı değeri *** olur. Recursive dizi, embedded JSON ve name/value setting kapsanır. HTML, Livewire public state ve çağrılabilir dönüşlerde aynı yardımcı kullanılır; depolanan log değiştirilmez.
- **P58 — Değişiklik işareti.** Raw değerler sunucuda karşılaştırılır; yalnız değişen alan adları istemciye çıkar. Maske sonrası eşit *** değerlerinden diff üretilmez.
- **P59 — Bilgi listeleri.** Bloglar/Diğer Sayfalar/SSS/İletişim/Aboneler/Diller/Değerlendirmeler ortak server JSON listesine taşındı. Mevcut kolon, izin, filtre ve form iş kuralları kaynak42 haritasına göre korunur; kalan19 tabloya uygulanmış sayılmaz.
- **P60 — Ayrı detay akışı.** Okundu/yanıt akışı olan İletişim ve Değerlendirmeler mevcut detay sayfasına view_url ile gider. Diğer liste readonly paneli fallback'tir; diller translate_url ile ayrı çeviri sayfasını açar.
- **P61 — Kapsam ve cache.** İzin cache'den önce; FAQ pages izinleri, review mevcut exams:view ve exams-reviews:view kontrolü.60sn JSON/1saat seçenek ve model/ilişki/çeviri değişiminde hedef revision; normalize çoklu filtre. Global flush kalıntısının kaldırıldığı veya hız kazandırıldığı iddia edilmez.
- **P62 — Kart kapsamı.** 42 aşamasında kullanıcı kart görünümü yalnız kullanıcı listesindeydi; sonraki açık45 onayı AI/Soru Havuzu kartlarını ekledi. Başka listeye yeni kart tasarımı açık karar olmadan eklenmez.
- **T12 — Port ve çalışma sınırı.** Eduan localhost80/3306 kullanır. Lara Football açıkken Eduan başlatılmaz; alternatif8081/3307 veya diğer projeyi durdurma yoktur. Kullanıcı açtıktan sonra ayrı testing DB ile dar test/browser kontrolü yapılır; statik başarı işlev başarısı sayılmaz.

Kaynak, dosyalar ve hazırlanmış testler: [42-LOG-MASKELEME-VE-BILGI-GIRISLERI-TABLOLARI.md](42-LOG-MASKELEME-VE-BILGI-GIRISLERI-TABLOLARI.md). Son JS değişimi APP_VERSION1.1.4.

## 16. Editör çalışma standardı — 6 Ekim

- **P63 — Editör senkronizasyonu.** Livewire ortak/modal editörlerinde change/input/undo/redo client state'i set(...,false) ile günceller; her tuş veya init için ayrı POST yapılmaz. Sonraki Livewire işleminde güncel content taşınır. Normal formda aynı olaylar editor.save ile textarea'yı günceller.
- **P64 — Alan kimliği.** Her editör kendi ID/height değerini kullanır; tinymce.get guard tekrar başlatmayı önler. Ortak asset @once; core/plugin7.2.1 sürümü tutarlı. Picker iptali kontrol edilir; blob cache ilgili editörden okunur.
- **P65 — HTML başlangıcı.** Textarea değeri escape edilir; content parametresi PHP bindingiyle aktarılır. Editör HTML'ini raw textarea çıktısıyla sayfaya yerleştirme.
- **T13 — Büyük içerik sınırı.** Mevcut içerik için16MiB Livewire request sınırı;10 nesting/50calls/200components korunur. Snapshot+update toplamı ölçülür; guard null yapılarak kaldırılmaz. Yeni büyük içeriğin sınırsız kabulü veya Base64 dönüşümü bu kuraldan çıkarılamaz.

Kanıt, dosyalar ve ayrı upload optimizasyon sınırı: [43-EDITOR-PAYLOAD-VE-SENKRONIZASYON-DUZELTMESI.md](43-EDITOR-PAYLOAD-VE-SENKRONIZASYON-DUZELTMESI.md).


## 17. Ana sayfa istatistik standardı — 6 Ekim

- **P66 — İlk HTML.** Dashboard KPI/sıralama/son kullanıcı ön izlemeleri server Blade render olur. Lazy widget, grafik indirmesi veya yapay çizim beklemesi kullanılmaz; ortak layout Livewire bileşenleri kalır.
- **P67 — Saatlik özet.** Ayrı dashboard file store ve3.600sn TTL. İlk cache miss hesaplar; cron/queue warming eklenmiş sayılmaz. Cache anahtarında dil, izin, AI görünürlüğü ve güncel öğretmen/öğrenci kapsamı; erişim cache'den önce. Öğrenci ataması yeni anahtara geçer, diğer sayı değişimleri saatlik tazeliği izler.
- **P68 — Sayaç tanımı.** Yeni7gün kartı aktif USER normal+premium birlikte; öğretmen kendi öğrencileri. Önceki kayıtlı sayaç yönetici normal/öğretmen tüm aktif, premium ayrı; bu tanımlar değiştirilmez. Sonuçlar bütün ExamResult denemelerini toplar; question_count gerçek cevaplanan adet olarak yeniden adlandırılmaz.
- **P69 — AI ve veri boyutu.** AI konu ID/başlıkta usage toplamıdır, ai:view gerekir. Cache düz ve gerekli alanlarla sınırlıdır; content/password/token veya tam Eloquent User modeli taşımaz. Başlıklar tam gösterilir; doğru/yanlış metin/ikonla da ayrılır.
- **P70 — Ön izleme kapsamı.** Sıralama en fazla8, son kullanıcı listeleri6; bunlar salt okunur dashboard ön izlemesidir. CRUD Bootstrap Table standardının yerine geçmez. Yalnız dashboard CSS, mevcut OneUI fontu ve version query kullanılır; yeni CSS nedeniyle APP_VERSION1.1.6.
- **T14 — Dashboard kanıtı.**5 dar test/29 doğrulama; ilk render, öğretmen/locale/AI izin ayrımı, konu toplamı, aktif yeni öğrenci, TTL ve warm sorgu kontrolü. Testing Chrome masaüstü/mobil var; gerçek AI paneli/öğretmen UI ve canlı yük kanıtı yok. Yerel PDO süreleri HTTP süreleri veya hızlanma katsayısı değildir.

Analiz, dosya listesi, korunan iş kuralları ve test ortamı sınırları: [44-ANASAYFA-ISTATISTIK-ANALIZI-VE-TASARIMI.md](44-ANASAYFA-ISTATISTIK-ANALIZI-VE-TASARIMI.md).


## 18. Eğitim menüsü listeleri — 6 Ekim

- **P71 — Liste eşlemesi.** AI/Soru Havuzu/Testler/Test Sonuçları45 standardındadır. Testler Exam modelidir; süreli Sınavlar Test modeli bu faz değildir. Form içerik/alan düzenlemesi sonraki aşamadır.
- **P72 — İki custom view.** AI ve Soru Havuzu varsayılan kart; Yenile yanında tablo/kart düğmesi. Aynı sayfalı JSON ve filtre/arama/sort/pagination; ayrı tüm-kayıt API'si yok. Kullanıcı kartı çalışmaya devam eder; başka listeye otomatik kart eklenmez.
- **P73 — Korunan bilgiler.** Soru görsel/kod/ders/konu/süre/sıra/durum; AI oy adedi/ortalama, usage kayıt adedi, rapor ve oluşturma. usage toplamı dashboard'a ait ayrı tanımdır. Değerlendirme yoksa ortalama0 uydurma; null/“—”.
- **P74 — Server cache/kapsam.**60sn JSON/1saat seçenek; normalize çoklu topics, ilgili model revision ve pivot sonrası açık invalidate. Öğretmen sonuç/favori ve filtre seçenekleri güncel öğrenci ID kapsamındadır; cache'den önce erişim/parent sahipliği. Direkt DB/pivot işlemi observer üretmez.
- **P75 — Bağlı listeler.** Test soruları, favoriler ve sonuç detayları readonly JSON5-row paneldir; kendi URL state'i parent listeyi değiştirmez. Dinamik morph yeni tabloyu bir kez başlatır. Soru/sonuç detail ayrı düzenleme formu değildir; sağ panel840px/mobil100% devam eder.
- **P76 — Livewire event sözleşmesi.** Livewire4 component/ref/el/self parametreleri hedefleme içindir; payload component adı olarak kullanma. Yeni bağlı learning paneli open-learning-table(view,recordId), server view allowlist ve erişim denetimi, sonra learning-table-opened kullanır. Eski genel modal/offcanvas protokolü bu aşamada topluca düzeltilmiş sayılmaz.
- **T15 — Tablo kanıtı.**13 test/110 doğrulama, son2 görsel/AI sınır testi13 doğrulama; önce iki eski CRUD dahil15/125. Chrome dört liste/kart toggle/arama ve üç panel;390px mobil AI/soru ve mevcut user kartı. Diğer12 tablo/genel suite ve canlı performans kabulü değildir.

Dosyalar, davranış matrisi, kalan AI alt listeleri ve bildirilen event adayları: [45-EGITIM-MENUSU-BOOTSTRAP-TABLE-GECISI.md](45-EGITIM-MENUSU-BOOTSTRAP-TABLE-GECISI.md).


## 19. Dashboard grafik ve filtre standardı — 46

- **P77 — Grafik.** Dört sıralama ilk5 kategori, native CSS/HTML grafik; ilk HTML'de çizilir, ayrı lazy/JS grafik isteği yok. Doğru/yanlış/soru ortak ölçekte ayrı çubuklardır; üst üste soru+doğru+yanlış toplama yok. Tam isim, sayısal metin, legend, sıfır/boş durum ve escape korunur.46 bölüm8 revizyonuyla üst sayısal eksen yok; sağ değerler700, ad–bar1.6px/kategori10.4px; CSS asset sürümü1.1.10. P70'teki8 kategori kararı güncel46 ile5 oldu; son kullanıcı6 devam eder.
- **P78 — Filtreli KPI.** Bağlantı sayaçla aynı sorgu tanımını kullanır. Normal/premium ayrımı role adına değil mevcut scope/permission'a göre; aktiflik ve yeni7gün kesin zaman filtresi görünür alanlara aktarılır. Öğretmen kapsamı her JSON çağrısında sunucuda korunur. Saatlik snapshot ile anlık liste sayısı her an eşit vaat edilmez.
- **P79 — Cache şekli.** Dashboard yeni alan/formatta v2;3.600sn/store/kapsam devam eder. Genel cache temizliği, cron, warming veya indeks bu CSS/grafik işiyle eklenmez.
- **P80 — Tema/mobil.** Dashboard/kart/filtre/grafik metni, zemin, eksen ve legend açık/koyu tema için okunur; renk yalnız başına anlam taşımaz. Dönüştürülmüş ortak tablolar mevcut OneUI theme ile birlikte kontrol edilir. Grafik390px genişlikte document taşırmaz; yeni CSS için APP_VERSION1.1.8. Tema düğmesi genel mantığı ayrı adaydır.
- **T16 — Dar kanıt.** Render/boş/sıfır/escape, KPI filtre-sayaç eşleşmesi, yetkisiz/hatalı filtre ve öğretmen ayrımı; browser açık/koyu/mobile ve gerçek filtreli bağlantı. Testing kanıtını üretim/gerçek rol matrisi/tüm suite kabulü sayma.

## 20. Sınav, lig, rapor ve kart sıralaması —47

- **P81 — Ekran eşlemesi.** Süreli Sınav/Test modeli, sonuç/detay; lig ve sonuç paneli; AI kullanım/oy tabloları47 standardındadır. AI LaTeX ve dört Users alt listesi bu faza dahil değildir.
- **P82 — Kart sırası.** Kart bulunan AI/Soru listesinde kolon+yön alanı vardır; tablo başlığı ve URL aynı sort/order değerini kullanır. Görsel kolonu dahil tüm sortable başlıklar formda seçilebilir; Filtrele ilk sayfaya döner. Ayrı kart verisi veya tarayıcıda tüm kayıt sıralaması oluşturma.
- **P83 — İçerik ve arayüz dili.** İstatistikler bütün içerik dillerini korur; Exam/Test adını panel diline göre değiştirme veya filtreleme. Başlık/Excel label panel dilindedir; Topic mevcut translatable locale'inden alınır.
- **P84 — Ortak rapor/export.** JSON ve Excel aynı authorization, filtre, arama ve aggregate sorguyu kullanır. Excel bütün filtreli kayıtları alır, pagination limit/offset'ini kullanmaz. Bütün denemeler toplamı ve ağırlıklı başarı yüzdesi korunur; mevcut tarih sonu anlamı ayrı karar olmadan değişmez.
- **P85 — Yetki ve parent.** Her JSON/action erişimi sunucuda denetlenir; öğretmen öğrenci kapsamı cache'den önce hesaplanır. Nested parent istemci ID'si scoped sorguyla doğrulanır; ai:delete ve ilgili resource delete gerekir. Mail hazırlama/send ayrı users:send-email iznine bağlıdır.
- **P86 — Cache kaynağı.** JSON60sn/seçenek1saat; ilgili model/öğrenci/locale/izin kapsamı anahtardadır. Bulk upsert/delete ve pivot observer üretmeyebilir; ilgili revision açıkça yenilenir. Global flush, cron veya schema işlemi tablo tasarımının parçası değildir.
- **T17 — Kabul sınırı.**47 içinde22 farklı ilgili senaryo ayrı dar koşularda, son4/39 ve lig1/3; Chrome liste/panel/gerçek Excel/kart JSON sırası ve mobil kontrolü vardır. Gerçek mail, üretim performansı, tam rol/cihaz matrisi ve bütün suite için başarı iddiası oluşturma.

Ayrıntı ve dosyalar: [47-SINAV-LIG-ISTATISTIK-VE-AI-ALT-TABLOLARI.md](47-SINAV-LIG-ISTATISTIK-VE-AI-ALT-TABLOLARI.md).

## 21. Ortak filtre ve tablo altı —48

- **P87 — Tablo altı.** Sayfa başına kayıt adedi seçicisi gösterilmez. Kayıt aralığı/toplam ve sayfa düğmeleri korunur; plugin paginationParts pageInfo/pageList. Ana15/nested5 ve sunucu sayfalama devam eder.
- **P88 — Filtre yerleşimi.** Label ayrı, id/for bağlı; control/select/bootstrap-select kendi kolonunun100%'üdür. Tarih ve sıralama alanlarını label içine gömme. Uzun etiket kırılabilir, Bootstrap row/col düzeni ve mobil genişlik korunur.
- **P89 — Tarih alanı.** Panel listesinde x-admin.filter-date ve nl/en/tr yer tutucusu kullanılır. Panel locale'i, mevcut query alan adı/değeri korunur. Saatli filtre saniye ve24saatlidir; registered_since ISO sözleşmesini değiştirme. Mobil Flatpickr/placeholder aynı yapıdadır; gerçek cihaz kabulü ayrıca yapılır.
- **T18 — Dar kabul.**48 içinde5/26 PHP doğrulaması ve Chrome8 ilgili ekran; gerçek sonraki sayfa, alan genişliği, yer tutucu, saat+saniye JSON ve Reset/mobil kontrolü. Ana DB, genel suite veya üretim kabulü değildir.

Ayrıntı: [48-TABLO-ALT-ALANI-VE-FILTRE-YERLESIMI.md](48-TABLO-ALT-ALANI-VE-FILTRE-YERLESIMI.md).


## 22. Kullanıcı alt tabloları ve menü grupları —49

- **P90 — Kullanıcı bağlamı.** Nested favori/atama/konu listesinde parent user_id gerekir; Livewire context Locked, JSON scoped yetki cache'den önce. İstemci ID veya menü görünürlüğü işlem izni değildir. Kendi öğrenci kapsamı her çağrıda güncel hesaplanır.
- **P91 — İlişki işlemi.** Atama tablosu Kaldır yalnız pivotu ayırır; users:student-delete/users:teacher-delete ve mevcut ilişki denetlenir. Öğretmen yalnız kendi ilişkisini yönetir. Kullanıcı silme veya ayrı görüntüle/düzenle ekleme; ilgili tablo/options revision yenilenir.
- **P92 — Menü başlığı.** OneUI nav-main-heading kullanılır; grup koşulu içindeki görünen çocuk izinlerinin tam birleşimidir. Hiç çocuk yoksa başlık görünmez. Çocuk can koşulları, aktif/açık route, locale label ve mevcut alt menüler korunur; yeni izin/rol seed'i gerekmez.
- **P93 — Lazy kararı.**49'daki hafif kullanıcı tablo shell'leri ilk HTML'dedir, veri JSON'dan gelir. Genel lazy'ler için50 öneri raporudur; bütün lazy attribute'ları otomatik kaldırma. İlk görünür form/ana içerik için sorgu ve payload ölçümü, ikincil ağır alan için görünürlük kararı ayrı yapılır.
- **P94 — Nested liste standardı.** Favori5; atama/hiç çözülmeyen konu15 başlangıç; pageInfo/pageList kalır, adet seçicisi yok. URL state parent'ı bozmaz; ortak filtre/arama/sort/yenile/theme/mobile korunur. Sabit enum filtreye gereksiz DB sorgusu veya ayrı cache katmanı ekleme.
- **T19 — Dar kabul.**13 farklı test senaryosu ve son ilgili atama1/13 tekrar; altı kullanıcı sekmesinde gerçek JSON/refresh/boş arama/390px, mevcut formatter Kaldır ve koyu filtre kontrolü. Boş öğretmen/konu browser verisini dolu ilişki kabulü sayma; dolu senaryolar testing transaction testleridir.290 PHP/296 Blade syntax bütün ürün testi değildir.

Ayrıntılar: [49](49-KULLANICI-ALT-TABLOLARI-VE-MENU-GRUPLARI.md), [50 — Kaynak/lazy raporu](50-LIVEWIRE-LAZY-VE-SAYFA-KOD-INCELEMESI.md), [51 — Bekleyen paket karşılıkları](51-KALDIRILAN-PAKETLER-VE-BEKLEYEN-KARSILIKLAR.md).


## 23. Ön yüz lig geçmişi —52

- **P95 — Dönem seçici.** Yerel tarih/ay/yıl araması mevcut seçenekleri client'ta süzer; her tuşta Livewire/SQL yok. Seçili dönem açıkça işaretlenir, gerçek route linki korunur; boş eşleşme ve temizleme çalışır. Liste scroll, arama ayrı sabit üst alan; keyboard Escape/focus ve mobil kolon korunur.
- **P96 — Sıralama kartı.** Puan ve sıra tabular/okunur; ilk üç madalya ve mevcut kendi satırı vurgusu. İsim Blade escape; tablo caption/scope ve boş sonuç metni korunur. Yalnız yerel league-* CSS ve proje font/rengi; hesaplama/cache/lazy bu tasarımla değişmez.
- **T20 — Lig görünümü.**3 farklı dar testing senaryosu/29 doğrulama; sentetik HTML Chrome14 dönem/15 sonuç, tarih-ay/trim/boş/reset/scroll ve390px taşma kontrolü. Sentetik browser renderı gerçek route/data/lazy veya üretim kabulü değildir. CSS için env APP_VERSION1.1.12; genel suite/build yok.

Kanıt ve dosyalar: [52-ON-YUZ-LIG-ARAMA-VE-GORUNUM.md](52-ON-YUZ-LIG-ARAMA-VE-GORUNUM.md).


## 24. Ön yüz cache ve hız standardı —53

- **P97 — Public veri cache'i.** Kullanıcı kararı7gün/değişiklikte yenile; frontend ayrı store. Dil, revision, APP_VERSION ve query shape anahtara girer. Full HTML/CSRF/snapshot/oturum veya öğrenci cevabı ortak cache'e girmez.
- **P98 — Invalidation.** Model observer ve pivot/bulk/settings action birlikte takip edilir. Genel dizin silme/flush yerine ilgili revision yenilenir; hits-only artış cache'i bozmaz. Planlı blog yayınında TTL bir sonraki yayın zamanıyla sınırlandırılır.
- **P99 — Liste sorgusu.** Kullanılan kolonlar, SQL distinct sayaç ve gerekli eager loading kullanılır. Sayaç için bütün test/sonuç ilişkisini yükleme. Test kataloğu ortak cache; kişisel sonuçlar canlı toplu sorgu ve klonlanmış modele eklenir.
- **P100 — İlk içerik.**53'teki yedi public ana içerik bileşeni ilk HTML'de gelir. Diğer lazy'leri otomatik kaldırma; ağır ikincil içerikte bağımsız karar/ölçüm gerekir. Section content dizisi yalnız kullanılan ayarları taşır.
- **P101 — Cache hacmi ve URL.** Serbest metin/deep page bypass; paginator mevcut request path'ini kullanır. Menü kendi sabit sayfasındadır. TTL otomatik disk purge garantisi değildir; eski revision hacmi ayrıca ölçülür. Favicon/manifest URL'sine time() değil APP_VERSION girer.
- **P102 — Form ve kanıt.** İletişim/bülten kaydı genel cache/compiled view silmez; ilgili bildirim anahtarı yenilenir. Validation/izin korunur. Yerel cold/warm ve dar test sayısını tam suite/production LCP veya maksimum hız garantisi olarak sunma.

Uygulama ve sınırlar: [53-ON-YUZ-PERFORMANS-VE-CACHE-DUZELTMELERI.md](53-ON-YUZ-PERFORMANS-VE-CACHE-DUZELTMELERI.md).


## 25. Ön yüz SEO ve arama motoru standardı —54

- **P103 — Canonical.** APP_URL doğru HTTPS/alan adı olmalı; public URL’de varsayılan dahil açık lang ve gerçek page korunur, takip parametresi atılır. Session dilini URL yerine varsayma; auth/token/özel URL sınırlarını koru.
- **P104 — Gerçek dil.** Aktif dil ve gerçek dolu çeviri kesişimi; Blog/Exam/Test tek kayıt locale’i. Eksik çeviriye hreflang verme; Lesson/Topic/Page eksik dil varyantı noindex ve gerçek dil canonical’ı. Uydurma çeviri eşleştirmesi yapma.
- **P105 — Başlık/açıklama.** İlk HTML’de anlamlı ana başlık, tek title/description; mevcut brief/description/content ve marka. Script/HTML temizle, boş metni içerik eksikliği olarak bildir. Anahtar kelime doldurma veya meta keywords ile sıralama garantisi verme.
- **P106 — Yapılandırılmış veri.** Gerçek kuruluş/site/sayfa/breadcrumb/blog/öğrenme verisi; görünür metinle tutarlı güvenli JSON encode. Sahte author/rating/review/cevap/sonuç üretme; private/noindex sayfada public graph yok.
- **P107 — Sitemap.** Yalnız aktif/yayınlanmış gerçek public URL ve çeviriler; dar kolon, frontend7gün cache/revision, değişiklikte yenile ve planlı yayın TTL. Unknown lastmod üretme; hits updated_at’ı değiştirmez. Hacim50.000URL/50MB sınırına yaklaşırsa parçalama planla.
- **P108 — Robots ve AI.** CSS/JS/Livewire render kaynaklarını engelleme. Hesap/çözüm/token sitemap dışında; robots yetkilendirme değildir. Özel AI etiketi/llms.txt zorunluluğu icat etme; OAI-SearchBot arama ve GPTBot eğitim kararlarını ayır.
- **T21 — SEO kabul sınırı.** Son20/221 ve token1/4 SEO, cache1/4; Chrome genel8sayfa/test detay/mobil ve EN→NL dil bağlantısı. Tam ürün/canlı indeks/Google rich result veya production hız garantisi değildir. Search Console/CDN/deploy ayrı işlem ve gerçek içerik eksikleri panelden hazırlanır.

Ayrıntılar ve resmî kaynaklar: [54-ON-YUZ-SEO-VE-ARAMA-MOTORU-STANDARDI.md](54-ON-YUZ-SEO-VE-ARAMA-MOTORU-STANDARDI.md).


## 26. Lig ilk HTML ve ortak pagination —55

- **P109 — Lig ana içerik.**55 ile LeagueTable ve üç LeagueResultTable ilk HTML'dedir; mevcut12/24saat cache ve sıralama/puan/kapsam korunur. Warm kaynak sorgusu ile ilk HTML mount sayısını ayrı ölç; ilk HTTP/LCP hızını yalnız attribute kaldırarak garanti etme. Diğer lazy'ler ayrı karardır.
- **P110 — Pagination kontrastı.** Ortak pagination-area hover/focus yazısı mevcut koyu proje zemininde beyaz; href/class olmayan numara bağlantısında Bootstrap specificity'si denetlenir. Disabled kuralını hover ile bozma. Ayrı sayfa/vendor override yerine ortak app.css; CSS değişiminde APP_VERSION artır.
- **T22 — Dar görünüm kabulü.**4 test/37 doğrulama, gerçek league route ilkHTML kart3/mount0; arama/reset/detail, mobil taşma ve ortak pagination computed-style kontrolü. Sentetik pagination kaynağını gerçek sayfa geçişi/canlı hacim onayı sayma. APP_VERSION1.1.13.

Kanıt: [55-ON-YUZ-LIG-ILK-YUKLEME-VE-SAYFALAMA-HOVER.md](55-ON-YUZ-LIG-ILK-YUKLEME-VE-SAYFALAMA-HOVER.md).


## 27. Ön yüz giriş, kayıt ve hesap görünümü —56

- **P111 — İlk form.** Giriş/kayıt ve ilk görünür profil/parola formları ilk HTML'de açılır; ek lazy mount beklemesi yok. Oturumdaki kullanıcı tekrar sorgulanmaz. Ağır ikincil raporlarda lazy ayrı ölçüm/karardır; bütün lazy'leri kaldırma.
- **P112 — Çeviri dayanımı.** Telefon label/placeholder/validation aynı tam anahtarı kullanır. Panel JSON yeniden üretiminde boş/eksik veya nl/en için kopya Türkçe telefon değeri kaynak locale varsayılanına döner; dolu özel çeviri korunur. PHP account.* etiketleri üç dilde kaynakta tutulur; yeni EN/TR telefon JSON'u tam sözlük kabulü değildir.
- **P113 — Hesap menüsü.** Kimlik özeti ve açıklamalı menü kartları ortak account/tab içinde; mevcut route/active/leagueStatus ve dashboard:access koşulu korunur. İki gelecek menü adları tanımlanmadan eklenmez; boş kart/grup oluşturma. Grid yeni öğeyle genişleyebilir.
- **P114 — Form görünümü.** Ayrı kişisel bilgi ve parola kartları, label/id/for, uygun autocomplete, erişilebilir parola görünürlük düğmesi. Mobilde kontroller/düğmeler kolon genişliğine uyar; mevcut font/ana renk değişkeni ve Bootstrap korunur. Parola ve sahiplik doğrulamaları tasarım için gevşetilmez.
- **P115 — Kişisel veri.** Profil özeti yalnız oturumdaki kullanıcı; metinler escape. Hesap HTML/CSRF/snapshot public cache'e eklenmez; özel noindex korunur. Header ilk HTTP durumudur; ayrıca reaktif güncelleme gerekiyorsa ayrı kapsam olarak ele al.
- **T23 — Dar kabul.**56 içinde9 farklı yeni senaryo/95 doğrulama ayrı koşularda ve mevcut2 limiter testi/10 doğrulama geçti. Chrome NL/EN ilkHTML mount0, gerçek giriş, validation, favori geçişi ve390px; TR yalnız transaction testi. Syntax/Pint9 veJSON3; APP_VERSION1.1.14. Tüm suite/canlı performans/eksiksiz dil veya rol matrisi kabulü değildir.

Ayrıntı: [56-GIRIS-KAYIT-VE-PROFIL-TASARIMI.md](56-GIRIS-KAYIT-VE-PROFIL-TASARIMI.md).


## 28. Hesap sol menü ve içerik standardı —57

- **P116 — Ortak hesap layout.** Sekiz authenticated hesap ekranında account/layout kullanılır; üst kapak/page-breadcrumb yok. Sayfa başlığı sağ içerikte tekH1. Diğer site ekranlarının kapak ayarına veya public abonelik akışına müdahale edilmez.
- **P117 — Sol/sağ oran.** 768px+ col-md-3 profil/menü ve col-md-9 içerik, aynı üst hizadan başlar. Sol alanda baş harf avatarı/ad ve açıklamasız dikey menü; P113'teki kart grid görünümü bu kullanıcı revizyonuyla değişti. Fotoğraf özelliği eklenmez; gelecekteki menüler ayrı tanımlanır.
- **P118 — Mobil menü.** Kısa kimlik altında kapalı başlayan button + Alpine menü; aria-controls/expanded, x-cloak, klavye focus ve Enter korunur. Menü linki normal route'a gider, seçimde kapanır; yeni sayfa kapalı başlar. Tablet/desktop menü her zaman görünür, mobil düğme gizlidir.
- **P119 — Kapsam/aktiflik.** dashboard:access/leagueStatus ve mevcut route izinleri korunur. Testin tamamlanmamış ekranı test menüsünü, sınav detayı sınav menüsünü aktif tutar; aria-current vardır. Menü adının alt açıklaması gösterilmez. Hesap sorgu/cache/iş mantığını yalnız layout değişikliği için yeniden yazma.
- **T24 — Dar kabul.**57 içinde iki ilgili test/94 doğrulama; sekiz route200/tekH1/kapaksız3-9/izin/aktiflik. Chrome1440/768/390px oran/hiza/mobil aç-kapa/Enter/favori geçişi/taşma ve CSS1.1.15. Gerçek rapor iş mantığının veya bütün ürün/rol/cihaz matrisinin test kabulü değildir.

Kanıt: [57-HESAP-SOL-MENU-VE-ICERIK-YERLESIMI.md](57-HESAP-SOL-MENU-VE-ICERIK-YERLESIMI.md).


## 29. Hesap raporları ve içerik kartları —58

- **P120 — Hesap dil kaynağı.** Sidebar, üst hesap menüsü, sayfa başlığı ve rapor etiketleri account.* nl/en/tr kaynağını paylaşır; public üst lig menüsü leagues.title. İçerik kayıtlarını arayüz çevirisi için yeniden adlandırma. Kaynak PHP etiketlerini panel JSON üretiminden bağımsız tut.
- **P121 — Mobil seçili sayfa.** Düğme aktif hesap sayfasının adını gösterir; tamamlanmamış test/sınav detayında üst menü seçimi korunur. Aç/kapa/aria/izin kuralları P118–119 ile aynıdır; çıkış kırmızı zemin ve beyaz icon/text/focus kontrastı.
- **P122 — Kişisel rapor.** Beş select seçimi doğrulanır, değişimde sayfa1; mevcut20 kayıt pagination ve toplam/season iş anlamı korunur. Başarı sıfır paydada0%; görünür isim/sayı ve native progress birlikte. SQL kolon whitelist; kullanıcı/sezon context Locked, sorgu auth actor. Lazy kaldırılması ayrı karardır.
- **P123 — Kişisel lig.** Mount actor eşitliği/Locked ve her sorguda auth kapsamı; eager league, kararlı created_at/id sırası,15 sayfa. Kart lig/dönem/sıra/puan/doğru/yanlış gösterir; eksik ilişkiyi yer tutucuyla bildir, geçmiş silme/recompute yapma.
- **P124 — Hesap içeriği.** Daralan9 kolon içinde üç course listesi iki geniş/tek dar kart; mevcut21 sayfalama/lazy ve işlemler korunur. Form açıklama/padding sıkılaştırılması validation veya parola kurallarını değiştirmez. Yeni style member-* alanında, font/renk değişkenleri aynı; CSS sürümü1.1.16.
- **T25 — Dar kabul.**58 içinde8 farklı transaction senaryosu ayrı koşularla, son dil ekleri3/45;10 PHP syntax/Pint. ChromeNL/EN/1440/768/390px, rapor select ve boş/dolu kart, iki kolon/menü/renk/taşma. İkinci sayfa yalnız fixture testinde, TR browser yok. Genel ürün/canlı hız veya tüm dil/rol matrisi kabulü değildir.

Kanıt ve mevcut sayaç adayları: [58-HESAP-ISTATISTIK-LIG-VE-KART-DUZENI.md](58-HESAP-ISTATISTIK-LIG-VE-KART-DUZENI.md).


## 30. Ana sayfa ve kişisel test detayları —59

- **P125 — Home katalog.** Mevcut ayar başlıkları/sayıları ve sorgu kapsamı korunur; ortak başlık/CTA, ders ikon/sayaç ve test/blog kartları home-catalog altında. Mobilde başlık/link alt alta; font/palette değişkenleri aynı.
- **P126 — Varsayılan sonuç.** Kullanıcı Sonuçlarım sekmesini varsayılan seçti. Güncel auth kimliğine ait bütün yarım/tam denemelerin toplamı ve15 kayıtlık kart geçmişi; Locked Exam, named pagination, ortak kişisel cache yok. Detay çözümleri mevcut route'ta; boş açıklamada mesaj, medya içeriğini boş sayma.
- **P127 — Review kapsamı.** PUBLIC/PRIVATE/own-HIDE dalları exam relation altında tek closure; misafir yalnızPUBLIC okur, yazamaz. Yazarı istemci User durumu değil güncel auth belirler. Aktif kayıt her işlemde doğrulanır; panel Action davranışını değiştirme.
- **P128 — Detay yükleme.** İki temel bilgi sidebar ilkHTML; public test summary mevcut7gün tests kaynağı/locale/revision/APP_VERSION ile, kişisel sonuç dışarıda. İkincil review lazy korunur. Sınav sections.parents eager; ilk boş root'ta null parent kullanma, seçilen child ilişkisini doğrula.
- **T26 — Dar kabul.**9 yeni ve6 mevcut farklı ilgili senaryo ayrı koşularda geçti; güvenlik2prepatchfail→2postpatchpass, bağımsız tek review.14 PHP syntax/13 Pint,29 dil anahtarı ve Chrome1440/390/NL/EN. Home in-memory ayar ön izlemesi;15+1 sayfa/2deneme transaction kanıtı. Genel suite/canlı hız/worker/rol veya bütün dil matrisi kabulü değildir. APP_VERSION1.1.17.

Kanıt ve dosyalar: [59-ANASAYFA-KARTLARI-VE-TEST-SINAV-DETAYLARI.md](59-ANASAYFA-KARTLARI-VE-TEST-SINAV-DETAYLARI.md).


## 31. Son LaTeX listesi ve paket fazları —60

- **P129 — LaTeX tablo.** Ai/LatexImagesTable ortak Blade/serverJSON; formül/image yolu arama, id/formula/image/created_at whitelist,15 paginationParts. İlişkisiz filtre ekleme; düzenleme mevcut formda, delete yalnız row. Dashboard/ai:view cache'den önce, ai:delete her işlemde, ai:update link/formda.
- **P130 — Görsel ve cache.** Escape edilen formül/alt ve http(s) image link; LaTeX20px ön izleme, koyu temada beyaz backing. JSON60sn/refresh bypass, LatexImage observer hedef revision; global flush ekleme. Varsayılan ortak görsel formatter boyutunu değiştirme. JS sürümü1.1.18.
- **P131 — Paket kaldırma sınırı.** Son tablo sonrası dış referansı kalmayan paket trait/config/published view/dilleri kaldırılabilir; uygulama dil kaynaklarını veya DB çevirilerini silme. Tarihsel rehber adları çalışma bağımlılığı değildir. Çeviri tarama ve bağımsız backup ayrı fazlar.
- **T27 — Dar kabul.** LaTeX4+mevcut scheduler1=5test47assert;9JSassert ve5PHPsyntax/Pint. Chrome gerçek boş endpoint, DB'siz20row JSON ile15+5/arama/sort/refresh/confirm iptal/mobil/koyu. Dolu gerçek DB browser/upload/silme, genel suite/canlı backup kabulü değildir. Ana çeviri şeması yalnızread-only incelendi.

Kanıt ve sonraki fazlar: [60-RAPPASOFT-SON-TABLO-VE-PAKET-KARSILIK-FAZLARI.md](60-RAPPASOFT-SON-TABLO-VE-PAKET-KARSILIK-FAZLARI.md).


## 32. Çeviri kaynağı ve yönetimi —61

- **P132 — Kaynak taraması.** Literal Laravel/Blade çağrılarını gerçek dil key türüyle izle; dinamik key/route uydurma. Controller method sınırı ve literal Blade/Livewire kenarlarıyla doğrulanan route; bilinmeyen kullanımda dosya/satır yeterli. PHP grup tanımını çağrı sanma.
- **P133 — Koruma.** Ayrı translation_sources onaylı tablo; mevcut translations/locale+key/değer ve Language durumlarını değiştirme. Ön izleme10dk kullanıcı+Locked token sunucuda, ekleme transaction/chunk/insertOrIgnore. Tekrar çoğaltma veya eski çeviri silme yok; eski kaynak active=false.
- **P134 — Düzenleme.** Textarea formu40 görünür değer snapshot ve SQL aggregate; locale/güncel dashboard+translate izni her işlemde. Key/orijinal/çeviri/kaynak/surface/group/boş filtreleri ve resetPage. Placeholder adları ve byte sınırı doğrulanır. JSON cümle/PHP grup ayrı, nested değer koruma/var_export/atomik replace/hedef frontend invalidation; dosyalar arası transaction varsayma.
- **T28 — Dar kabul.**7 yeni+4 mevcut farklı ilgili senaryo ayrı koşularla; son1/9. ChromeNL1440/390/koyu/40+5/arama/filtre/ön izleme; per-request rollback fixture. Onaylı tek migration iki yerel DB, eski dil tabloları aggregate/schema korunur. Gerçek ana çeviri yazımı/dış çeviri/genel suite/deploy veya canlı backup kabulü değildir. CSS/JS yok;1.1.18 korunur.

Kanıt: [61-CEVIRI-TARAYICISI-VE-KAYNAK-YONETIMI.md](61-CEVIRI-TARAYICISI-VE-KAYNAK-YONETIMI.md).


## 33. Laravel bağımsız Bash yedeği —62

- **P135 — Bağımsızlık ve kapsam.** Yeni db SQL.gz/project tamZIP scripti Laravel/Artisan çalıştırmaz; açık Laravel kökü.env allowlist, source/eval yok. Seçilmiş interpolasyon/URL/socket desteklenmiyorsa reddet; başka hedefe sessiz düşme. Proje dışı private çalışma alanı ve symlink arşivleme sınırı.
- **P136 — Doğrulama ve koruma.** Ayrı açık backup bucket/prefix, R2 endpoint/auto ve AWS CLI v2. Boyut+metadata+tam readbackSHA256 sonrası yalnız üretilmiş run arşivini kaldır; eski/source/uzak dosyaları silme. Hata arşivi+private log korunur, geçici credential temizlenir. Atomik mkdir lock iki modu korur, stale kilit otomatik silinmez.
- **P137 — İşletim sınırı.** .env.example boş config, gerçek.env ve cron/sunucu/R2 kurulumu ayrı açık adım. Örnek03/04 saatini onaylı schedule sayma; script kendi kendine günlük servis değildir. Restore/nontransactional/live dosya tutarlılığı/gerçek AWS kabulünü fake testten çıkarma.
- **T29 — Dar kabul.** Bashsyntax exit0 ve11 farklı Python fixture test iki dar koşuda, gerçek gzip/ZIP/OpenSSL+fake MySQL/AWS; başarı/dump boş-hata/upload/head/metadata/boyut/readback/hash/lock/config/izin/symlink/dotenv literal/source guard. DB/network/sunucu/migration/Composer/build/genel suite/browser yok; APP_VERSION1.1.18 korunur.

Kanıt: [62-LARAVEL-BAGIMSIZ-BASH-YEDEK-VE-R2.md](62-LARAVEL-BAGIMSIZ-BASH-YEDEK-VE-R2.md).


### 62 devam — SiteGround mysqldump başlatıcısı

Dump çağrısında `--` terminator kullanma: SiteGround başlatıcısı `$@` sonuna `--no-tablespaces` ekliyor. `DB_DATABASE` başta tire reddi, option-file/secret sınırı ve bütün mevcut dump seçenekleri korunur. Başlatıcıyı değiştirme veya raw binary ile bypass etme. T29 devam kanıtı: wrapper fixture1red, dar5testgreen+Bashsyntax; yeni gerçek aktarım/ZIP/cron/restore kullanıcı tekrarını bekler.62 bölüm10.


### 62 devam — logs yalnız şema

Kullanıcının açık kapsamı: yalnız logs satırlarını backup'tan çıkar, şema/trigger korunur. Diğer tablolar tam snapshot `--ignore-table=DB.logs`; ikinci çağrı yalnız logs/no-data/skip-routines/skip-events ve tek gzip akışı. Her çağrının hatası R2'yi engeller, kısmi arşivi korur. SiteGround terminator düzeltmesi korunur; canlı DB'de silme veya başka tablo dışlama yok. Dar5test+Bashsyntax, gerçek yeni boyut/R2 sonucunu kullanıcı tekrarından doğrula.62 bölüm11.


## 34. Sorgu/cache ve kişisel geçmiş —65

- **P138 — Kişisel toplu sorgu.** Çözülen test kartları bütün kişisel denemeler/cevap satırlarıyla SQL toplamı; eski sayaç yerine cevap sayımı, yarım deneme korunur. Hiç çözülmeyenler PHP pluck yerine kullanıcı scoped SQL dışlama. Lazy/21 kart korunur.
- **P139 — Geçmiş.** Test/sınav5deneme sayfası, artan ID ve kesintisiz numara; sınav yalnız tamamlanmış kendi denemeleri. Bölüm/alt/meta/soru/yanıt eager; döngüde sorgu yok, accordion ID sonuçla ayrılır.
- **P140 — Cache sınırı.** resetCache tek veri flush, kayıt sırasında optimize:clear yok. Saatlik cache:clear aynı :15/saat dilimi; legacy veri invalidation korunur. Compiled config/route/view/event yayın aşamasında yönetilir; frontend/dashboard/security ayrı store sözleşmesi korunur.
- **T30 — Dar kabul.**7 farklı senaryo62doğrulama ayrı koşular, SQL3 sorgu ve strict group kanıtı. Genel test/browser tekrarını kullanıcı sonraya bıraktı. Formatter toparlama79syntax kontrolü işlev kabulü değildir. CSS/JS/yeni indeks/migration/deploy yok;1.1.19 korunur.

Kanıt: [65-SORGU-CACHE-VE-YAYIN-KONTROL-KAPISI.md](65-SORGU-CACHE-VE-YAYIN-KONTROL-KAPISI.md).


## 35. Süreli sınav ekranı —69 bölüm7

- **F22 — Soru durumu.** Cevaplanan/toplam ve Boş açık yazılır; tik kaydedilmiş cevap içindir; nötr renkte olur, doğru/yanlış sonucu bildirmez. Yalnız sayfayı ziyaret etmek cevaplandı sayılmaz. Bölüm/soru gezinmesi ilk HTML'de hazırdır; boş sorular atlanabilir.
- **F23 — Zaman ve tamamlama.** Çubuk ilk denemenin başlangıç/bitişinden ve sunucu zamanından hesaplanır; reload kalan oranı korur. İstemci geri sayım sunucu deadline kuralını değiştirmez. Sonuç modalı yalnız sunucu tamamlanma onayından sonra açılır.
- **F24 — Kaydetme geri bildirimi.** Kaydedildi gerçek başarılı render sonrası gösterilir. Ağ hatası cevap kaydı sayılmaz; yenileme/explicit retry sunulur, otomatik tekrar veya süre uzatma eklenmez. Başarısız cevap kaydı çözülmeden elle tamamlama engellenir.
- **F25 — Dar görünüm.** Tam genişlikte sol bölüm menüsü/sağ içerik; mobil menü seçimde kapanır. Native radio klavye/focus ve okunabilir seçenekler; soru/PDF/bağlı içerik davranışı ve mevcut sınav hakları korunur. Header/footer değişimi ayrı kapsamdır.
- **T31 — Dar kabul.**10 farklı PHP senaryosu66doğrulama üç koşuda,7sayaç/istek JS ve1PWA sürüm eşleşmesi testi geçti. Testing gerçek Chrome desktop/mobile seçme-kaldırma/reload/menu/save failure/sonuç modalı kanıtı69 bölüm7; canlı worker/gerçek cihaz/genel suite kanıtı değildir.


## 36. Toplu iletişim —77

- **P141 — Geliştirme öncesi okuma.** Her talepte AGENTS.md, README, bu standartlar, kod yazım rehberi ve ilgili özellik rehberi okunur; yakın ekranın gerçek bileşenleri incelenir. Bu zorunluluk kaldırılmaz. Sonuç doğru olsa bile bu okuma yapılmadan geliştirme kabul edilmez.
- **P142 — Panel listeleri.** Yeni panel listelerinin tamamı ortak Blade + `x-admin.server-table` + Bootstrap Table/server JSON kullanır. Gönderim geçmişi için düz HTML tablo istisnası yoktur. Sunucuda arama, sıralama, sayfalama ve güncel yetki uygulanır; gizli mesaj/provider ayrıntıları JSON'a eklenmez. Worker durumu değişen iletişim geçmişi canlı private/no-store veri döndürür.
- **P143 — İletişim akışı.** Yönetim grubunda Kullanıcılar'ın üstünde mevcut Toplu E-posta ve Bildirim menüsü. İlk ekran kayıtlı gönderim geçmişi; Yeni gönderim → alıcı seçimi → mesaj → ön izleme/Gönder. Üçüncü adım gönderim onayıdır. Alıcı sayısı/devam düğmesi tablonun üstünde; adım geçişi formun başına döner. Geri dönüş seçim ve taslağı korur; iptal temizler.
- **P144 — Güncel doğrulama.** Adım/ön izleme/UUID Locked; ön izleme özeti ve son gönderim girdisi karşılaştırılır. İçerik değişirse yeniden ön izleme gerekir. Son işlem güncel rol, alıcı ilişkisi, aktiflik, kanal ayarı ve UUID sözleşmesini tekrar doğrular. Gönder işlemi mevcut asenkron kuyruğu oluşturur; başarı bildirimi teslim edildi anlamına gelmez.
- **T32 — Dar kabul.** 34 PHP/236 assertion ve 15 Node testi; testing rollback Chrome1440/390 geçmiş, arama/filtre/sayfa, farklı sayfalardan seçim, üç adım, geri dönüş, kişisel ön izleme ve kayıt yenileme. Gerçek SMTP/push yok; genel suite tekrarı yok. TR/NL/EN/FR94 anahtar, APP_VERSION/root worker1.1.28.

Kanıt: [77-TOPLU-ILETISIM-UC-ADIM-VE-TABLO-STANDARDI.md](77-TOPLU-ILETISIM-UC-ADIM-VE-TABLO-STANDARDI.md).

## 37. Canlı lig —78 Faz1 yerel kabulü

- **D16 — Sabit lig kuralları.** Canlı kaynak/puan/kapasite oluşturulurken saklanır; aynı kaydın hesaplaması bu değerleri kullanır. Mevcut updated_at/SUM/RANK sözleşmesi korunur.
- **D17 — Tarih sınırı.** Yeni varsayılan Cumartesi00–Cuma23:59; serbest tarih-saat ve eski kayıtların aralıkları korunur. Canlı veri üst sınırı dönem sonunu aşmaz; gerçek işlem zamanı ayrı tutulur. Gecikmiş sorgu geçmiş anın fotoğrafı değildir.
- **D18 — Yeniden hesaplama.** Eski haftalık görev eşleşen canlı kaydı atlamaz; aynı kimlikle hesaplar. DB+aralık kilidi ve transaction aynı ligde çakışmayı önler. Canlı sonuç eşitlemesi kaynak cevapları silmez.
- **T33 — Faz1 kabul sınırı.** Onaylı tek migration ana16/testing10 uygulandı; eski veri özetleri korundu. Container saf/DB toplam60farklıilgili senaryo başarıkanıtı; ilk JSON-key-order beklentisi düzeltilip dar tekrar10/115 ve ek sınır2/10 geçti. Tektemizgenelkoşu değil. Faz1 veri/hesaplama kapısı tamam; Faz2scheduler/kapanış ve Faz3UI/cache/çeviri/tarayıcı ayrı kabul. Canlı yayın veya ana DB gerçek hesaplama yok.

Kanıt ve dosyalar: [78](78-CANLI-LIG-FAZ1-VERI-VE-HESAPLAMA.md).

## 38. Canlı lig —79 Faz2 yerel backend kabulü

- **D19 — Zamanlama/kapanış.** SiteTZ00/04/08/12/16/20; başlayan/açık canlılar IDchunk50 ile seçilir ve DB dönem kilidinde yeniden doğrulanır. Son başarılı cutoff dönem sonuna ulaşmadan kapanış yok; sonuçlar/calculated_at/closed_at atomiktir. İlk kapanış anı yeniden hesaplamada korunur.
- **D20 — Eski görev ve elle hesap.** Eski konfigüre gün/saat/ofset/dakika aralığı korunur; aynı kaynak/aralık canlı kaydına sabit ayarlar uygulanır. Busy/notdue/passive normal skip; gerçek hata ID/class ile loglanır. Yeniden hesaplama dashboard+leagues:view+leagues:run ve kapalı canlı kayıt ister.
- **T34 — Faz2 kanıtı.** İlk72koşuda71pass/1fixture timestamp, düzeltilen/ek sınırlar ve regresyonlarla son13/132green; toplam84farklıilgili senaryo başarıkanıtı. Tektemizgenelkoşu değil. Main/testing veri özetleri aynı,8syntax/Pint. Yeni migration/sunucucron/anaDBhesap yok; Faz3UI/cache/çeviri/tarayıcı ayrı kabul.

Kanıt ve değişen dosyalar: [79](79-CANLI-LIG-FAZ2-ZAMANLAMA-VE-KAPANIS.md).
