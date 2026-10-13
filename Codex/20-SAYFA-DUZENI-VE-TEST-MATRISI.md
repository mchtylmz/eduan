# Sayfa düzeni, görsel standartlar ve yükseltme öncesi test matrisi

Güncel kritik browser kabulü `23-TARAYICI-AKISLARI-VE-YUKSELTME-HAZIRLIGI.md`: ayrı testing DB'de login, test/sınav yeniden açma/süre sonu/tamamlama, beş panel tablo ailesi, PNG/editor ve seçili mobil kontroller yapıldı. Bu belgenin tarihsel "browser eksik" kayıtları artık bu seçili kapsam için `23` ile okunur. İngilizce asset 404 ve editor/uploader dil tutarlılığı açık; bütün ürün/rol/cihaz matrisi tamamlanmış değildir.

5 Ekim 2026. `16` ortak kuralları, `18` güncel test kanıtını, `19` hedef tablo geçişini anlatır. Bu dosya bunları sayfa gruplarına uygular. **Mevcut görünüm**, **hedef düzen** ve **eksik kabul kontrolü** ayrıdır; bugün CSS/JS yeniden tasarımı yapılmadı. OneUI/Bootstrap/jQuery devam eder, Vue.js eklenmez.

## 1. Panel için ortak görsel yerleşim

1. Mevcut OneUI header/sidebar/breadcrumb kullanılır; başlık kullanıcının hangi kaydı/modülü açtığını anlatır.
2. Liste sayfasında block-rounded → block-header/block-title → doğru izinli ana Ekle/Export işlemleri korunur.
3. Hedef filtreler başlığın altında ortak açık/çerçeveli bandadır; etiketli Bootstrap row/col grid kullanılır.
4. Filtrele ve Temizle normal genişlikte sağda; filtre sayısı mobilde alt satıra geçer.
5. Lara Football server-table örneği gibi yenile araçları solda, arama sağda; kolon/pagination kontrolü bütün listelerde tutarlıdır.
6. Blade sayfa kabuğu ilk açılışta görünür; JSON yalnız ilgili sayfanın verisini getirir. İlk veri/yenileme loading durumu ayrıdır.
7. Kolon metni/puan/sayı/tarih ve işlem hizası aynı anlam için aynı biçimdedir. Uzun başlık/e-posta taşmamalı; gerekirse title/uygun genişlik kullanılır.
8. Status/correct/completed enum badge'leri mevcut anlamını korur. Salt ikon tek başına anlam taşımaz.
9. Satır eylemleri mevcut sırayı/izinleri korur; görüntüleme izni yazma izni değildir. Yeni Soru gibi düğme kaynağın kendi add iznine bağlıdır.
10. Formlar başlık/geri dönüş, ana bilgi, ilişkiler, durum/ek içerik, kaydetme sırasını yakın örnekle izler. Hata input yanındadır.
11. Modal/offcanvas seçimi işi gerektiriyorsa kullanılır; Lara Football referansı bütün Eduan formlarını offcanvas'a taşıma kararı değildir.
12. Browser'da responsive taşma, filtre, loading, boş/hata, gerçek kayıt, refresh/search/sort/pagination ve console kontrol edilir. HTTP render bu kabulün yalnız bir kısmıdır.

## 2. Panel sayfaları ve korunan sözleşme

| Grup / route | Düzen ve işlev standardı | Güncel kanıt / kalan |
|---|---|---|
| Home — admin.home.index | Aynı OneUI widget/başlık sırası; ağır widget ile ilk sayfa iskeletini ayrı ölç | HTTP geçti; widget dolu veri/JS/hız ayrıca |
| Ders — admin.lessons.* | Kod/çok dilli ad/durum ve tablo filtreleri; konu varken silme engeli | Create/edit/delete + hydrated liste geçti; browser filtre/sort ayrıca |
| Konu — admin.topics.* | Ders ilişkisi/başlık; ders filtresi üstte, soru varken silme engeli | CRUD + hydrated liste geçti; çapraz ilişki girişleri ayrıca |
| Soru — admin.questions.* | Ders→konu seçimi, kod/title/answers, tek doğru; Ekle questions:add | CRUD, invalid answers, iki düğme izin durumu, hydrated liste geçti; zengin editör/asset ayrıca |
| Süresiz test — admin.exams.* | Tanım + soru seçimi; favorite/review/results/detail eylemleri korunur | Temel tanım/pivot CRUD ve liste render; bütün modal/rapor/detay ayrıca |
| Süreli sınav — admin.tests.* | Tanım/süre/puan + bölüm/PDF/content/soru; bölüm çıkarma bağlı kayıtları temizler | Tanım, bölüm persistence/rollback ve liste render geçti; PDF viewer/sıralama/çok alanlı UI ayrıca |
| Kullanıcı — admin.users.* | Kullanıcı tipi/rol/öğrenci/öğretmen; kendi öğrenci kapsamı bütün JSON listede korunur | Önceki kapsam/atama/mail testleri + giriş/form render; tam rol matrisi ve browser ayrıca |
| Rol — admin.roles.* | Ad/grup/izin seçimi, sync, atanan rolü silmeme | HTTP CRUD/invalid/izin + render geçti; duplicate isim/izin matrisi ayrıca |
| Sayfa — admin.pages.home/all/create/edit | Home ayrı düzen, custom sayfa çevirileri; sistem sayfası silinmez | Home/all/create HTTP, custom CRUD/system guard geçti; bütün home alanları/uploads ayrıca |
| SSS — admin.pages.faqs.* | Dil/title/content/sort/status; edit dil korunur | CRUD düzeltildi/geçti; site dili ve browser zengin editör ayrıca |
| Blog — admin.blogs.* | Locale/title/content/image/SEO; içerik encode/decode tutarlı | Temel CRUD ve index/create render; upload/slug/SEO çok dil ayrıca |
| Dil — admin.languages.* | Code/name/status; varsayılan/son dili silme engeli; çeviri ayrı işlem | Temel CRUD/duplicate/default guard geçti; scanner/JSON yazma/çapraz locale ayrıca |
| Sezon — admin.seasons.index | Tarih/name/status satırları, yinelenen sınır kontrolü | CRUD/date/queue fake geçti; gerçek results job ve örtüşme kararı ayrıca |
| Lig — admin.leagues.* | Dönem/sonuç/öğrenci sırası ve filtre; existing RANK sözleşmesi | Önceki hesap testleri + index render; gerçek job/hacim/detail UI ayrıca |
| İstatistik — admin.stats.* / users stats | Tür/kriter/konu/öğrenci filtreleri üstte; ekran/mail/export aynı scope | Önceki scoped toplam + gerçek XLSX ve index render; bütün rapor/PDF/Word ayrıca |
| Ayarlar — admin.settings.index | Mevcut 13 tab, uygun form grupları, yalnız update izniyle yazma | 13 tab HTTP + önceki upload/izin testleri; bütün ayar yazma/validation/cache invalidation ayrıca |
| İletişim — admin.contacts.* | Gönderen/konu/durum ve reply/detay; mevcut mail sınırı | Index HTTP; reply/dolu detay/gerçek queue ayrıca |
| Bülten — admin.newsletter.* | Alıcı/abonelik listesi, gönderim izni/durum | Index HTTP; send/unsubscribe/job ayrıca |
| AI — admin.ai.* | Answer/vote/usages/image tabloları, görünür loading/detay | Index HTTP; gerçek dış servise çağrı yapılmadı; fake response/error/limit ayrıca |
| Log — admin.settings.logs | Arama/tarih/actor/detail, hassas içeriğin görünürlüğü | Bu tur özel log/detail testi yok; ayrı dar kontrol |

Futbol projesinin alanları aynen taşınmaz; Eduan filtreleri ders, konu, dil, durum, kullanıcı/öğretmen, test/sınav ve ilgili sezon/dönemdir. Bu liste her formun bütün girdisinin veya her tablonun dolu veriyle kanıtı değildir.

## 3. Ön yüz için ortak görsel yerleşim

1. Mevcut header/logo/ana menü, container ve gerektiğinde page-breadcrumb kapak düzeni korunur.
2. İlk ekrandaki ana görsel normal/erken yüklenir; görüntünün width/height/alt ve uygun dosya boyutu düşünülür.
3. Aynı tür ders/konu/test/sınav kartlarında başlık, kısa bilgi, sayılar ve çöz/detay/favori işlemi aynı sıradadır.
4. Arama ve ilgili filtreler kart/listeden önce; arama uygula/temizle ilk sayfaya döner.
5. Login/kayıt/parola formlarında etiket/hata/help ve loading tutarlıdır; mobilde input/submit/geri linkleri erişilebilirdir.
6. Soru ekranında seçenek metni/görseli, seçim/correct geri bildirimi, ilerleme ve süreli sınavda timer net görünür; salt renkle anlatılmaz.
7. Hesap alt navigasyonu profil/favori/sonuç/istatistik/lig için tutarlıdır; özel sayfa noindex ve kendi kullanıcı kapsamı korunur.
8. Boş sonuç, yüklenme, hatalı giriş, auth gerektiği ve bitmiş deneme farklı durumlar olarak açık gösterilir.
9. nl/en/tr metin uzaması, uzun başlık, mobil menü ve yatay taşma kontrol edilir; font/renk/boşluk tüm siteyi ayrı yeniden tasarlamaz.
10. SEO başlık/canonical/robots/sitemap mevcut politikayla uyumludur. Lazy listeler sadece placeholder HTTP testiyle indeksleme açısından kabul edilmez.

## 4. Ön yüz sayfa matrisi

| Grup / route | Korunacak görünüm ve davranış | Kanıt / kalan |
|---|---|---|
| Home | Welcome/search + ayara göre lessons/tests/blog/faq sırası | Geçerli home fixture HTTP; bütün ayar kombinasyonu/dolu kart/browser/LCP ayrıca |
| Search | Arama üstte, sonuç/boş durum ve sayfalama | HTTP; tüm search türleri ve filtre cache çarpışması ayrıca |
| Lessons / lesson topics | Ders/konu kartları ve search/pagination | Liste HTTP + ders hydrated; ders/konu cache sayfa regresyonu giderildi; topic detail route ayrıca |
| Tests / test detail/start/solutions | Süresiz test kartı/detail, soru/favori, ilk cevap kilidi ve geçmiş | Liste HTTP/hydrated + önceki SolveForm güvenlik/işlev; bütün detail/solution browser ayrıca |
| Exams / exam detail/start | Süreli sınav kartı/süre/puan/deneme | Liste HTTP/hydrated/active filtre + önceki süre/yeniden giriş testleri; dolu detail/browser/timer/iki istemci ayrıca |
| Blog / blog detail | Liste/grid/detail ve içerik/görsel/SEO | Index HTTP; detail/filtre/cache/dil browser ayrıca |
| FAQs | Dil/sort/content ve expand/collapse | HTTP/hydrated; nl/en/tr filtre ve JS davranışı ayrıca |
| Leagues / league detail | Dönem seçimi ve sonuç sırası | Index HTTP; seçili lig + sonuç/dolu veri/browser ayrıca |
| Contact | İletişim bilgileri + form/hata/consent | HTTP; submit/queue fake/rate/validation ayrıca |
| Login/register/recover/create-password/verify | Sade giriş/form ve doğrulama yaşam döngüsü | Login/register/recover HTTP + kapalı kayıt redirect + önceki auth testleri; bütün başarılı kullanıcı yaşam döngüsü ayrıca |
| Account profile/favorite/stats/my-leagues/solved/notTests/exams | Ortak hesap başlığı/navigasyonu, kendine ait veriler ve boş durum | 7 HTTP, profile hydrated + önceki scope; dolu geçmiş/istatistik/favori UI ayrıca |
| Page slug | Menü/durum/çevirili içerik ve link redirect | Panel CRUD vardır; bütün site Page varyantları ayrı |
| PWA/offline | Offline full name ve statik fallback, özel HTML cache yasağı | Önceki 7 VM test + bu tur PWA/legacy offline route; gerçek bağlantı kesme/logout/eski worker ayrıca |
| AI solution/newsletter/unsubscribe | Soru/oturum bağlamı ve dış servis/abonelik yan etkileri | Bu tur HTTP detay/submit yok; fake ve ayrı ürün sınırlarıyla test edilir |

## 5. Lazy ve sorgu iyileştirme planı — bugün uygulanmadı

Kaynakta ListLessons/ListTopics/ListTests/ListExams, login/register/recover, hesap ve birçok widget Lazy kullanıyor. WelcomeSection'da Lazy attribute yorum satırıdır. Bunları aynı şekilde değerlendirmek doğru değildir.

Resmî [Livewire 3 lazy dokümanı](https://livewire.laravel.com/docs/3.x/lazy) ilk render yerine placeholder ve görünür olduğunda ek istek davranışını açıklar. Bileşen bazında lazy:false uygulanabilir; isolate:false istekleri birleştirebilir. Bunlar ölçümsüz bütün uygulamaya uygulanacak değişiklikler değildir.

Önerilen dar değerlendirme:

1. Kritik başlık/ilk içerik/login formunda eager render ile mevcut lazy ilk görünme süresini karşılaştır.
2. Ekran dışındaki ağır istatistik/widget için yer tutucu ve lazy korunabilir; yerleşim kayması ölçülür.
3. Ders/konu cache sayfa doğruluğu giderildi; search/sort/locale/kullanıcı anahtarları ayrıca senaryo bazında sınanır.
4. Cache TTL/yorum farkları ve global resetCache/saatlik optimize:clear etkisi `18` kaydındadır. Hedef TTL/invalidation kararı almadan değiştirme.
5. Count ve rows, ilişkiler/withCount/with, mümkün N+1 ve gereksiz kolon hacmi aynı fixture/SQL planıyla ölçülür.
6. Tablo için HTML TTFB ile ilk veri görünme süresi ayrı tutulur; Bootstrap Table sonradan yükleme daha hızlı görünebilir ama SQL maliyetini tek başına azaltmaz.
7. CDN/Turnstile sonraki fazdır; bugün görsel yolu veya captcha paketi değiştirilmez.
8. Hız için sayı/süre hedefi gerçek URL/veri/cihazla netleştirilir. Yerel test süresi kullanıcı sayfasının performans ölçümü değildir.

## 6. Laravel yükseltmesi öncesi kabul kaydı

Her taşınan/güncellenen sayfa için route/aktör/fixture, önceki görünüm örneği, filtre/kolon/eylem, HTTP + işlev + gerektiğinde browser kanıtı kaydedilir. Paket yükseltmesinden sonra aynı senaryolar yeniden seçilir; HTTP kabuk kontrolü dolu veri/JS kabulünün yerine geçmez.

Sonraki açık paketler: filtre/search/cache doğruluğu; dolu detail/edit ve rol matrisi; gerçek browser form/table; auth yaşam döngüsü ve contact/newsletter fake submit; PDF/Word/AI hata ve limit; gerçek PWA/cron ve canlı şema. Bu matris bütününün yeşil olduğu iddia edilmez. SQL yedeği gelmeden migration/üretim veri taşıması yapılmaz.

5 Ekim devam incelemesi: cache anahtarları/TTL/global reset, 17 dolu detay ve eksik ID senaryosu, 18 panel giriş ailesi ve altı öğretmen tabı `21-KALAN-INCELEME-CACHE-DETAY-YETKI.md` içinde dar testlerle değerlendirildi. Bulunan üç formda izinsiz yazma, arama/blog/lig cache hataları, eksik sınav sonucu view'u ve nullable blog edit hataları kullanıcı onayıyla düzeltildi. Güncel bölüm 10: önceki 15 başarısız senaryo + dört olumlu/sınır kontrolü, toplam 19 test/79 doğrulama geçti. TTL/global reset planı, gerçek browser ve geniş işlem/rol matrisi açık kalır.

Ardından kullanıcı isteğiyle mevcut bütün otomatik testler tek PHP suite koşusunda ve ayrı JS koşusunda çalıştırıldı: **245 PHP testi/1.163 doğrulama + 7 PWA testi geçti**. Sınıf dökümü ve kalan gerçek browser/ürün kabulü `22-GENEL-TEST-SONUCU-VE-YUKSELTME-KAPISI.md` içindedir. Yukarıdaki eksik ürün hücreleri bu sayılar nedeniyle otomatik tamamlanmış sayılmaz.
