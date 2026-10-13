# Ön yüz performansı ve cache düzeltmeleri

7 Ekim 2026. Kullanıcının kapsamı: ana sayfadaki file cache yazma hatası; ana sayfa, ders/konu, test/sınav, blog, iletişim ve footer bülten akışının performansı. Kullanıcı kişisel olmayan içerik için **7 gün ve değişiklikte hemen yenileme** seçti. Laravel/paket/schema/deployment değişmedi.

## 1. Hata ve kaynak bulgusu

Ekran görüntüsünde `Frontend/Lessons/ListLessons` cache yazarken `storage/framework/cache/data/...` alt dizini bulunamıyor. Kaynakta nonproduction her istekte `cache()->flush()` vardı. Loggable kayıt değişiklikleri ve görüntüleme sayacı güncellemelerinde `resetCache()` ile default store/derlenmiş görünümler temizleniyordu; bülten kaydında ayrıca iki tekrar vardı. FileStore dizini oluşturma ile yazma arasındaki başka bir temizleme bu hata ile tutarlıdır. Kullanıcının ilk hatasının eşzamanlı yarışını birebir yeniden üretmedik; izin/harici disk temizleyici gibi başka ortam nedenlerini kesin dışlamıyoruz.

Uygulanan çözüm: boot sırasında genel flush kaldırıldı. Ön yüz public verisi default store'dan ayrı `frontend` store'una taşındı (`storage/framework/cache/frontend`). İçerik değişiminde dizin silmek yerine kaynak revision değişir. Laravel FileStore eksik hash dizinlerini oluşturur; eksik kök dizinle başlayan dar test bunu doğruladı. Başarısız yazımları sessizce yutan catch veya 0777/chown düzeltmesi eklenmedi.

**Sınır:** Eski default `resetCache()` ve saatlik `optimize:clear` scheduler hâlâ vardır; bütün panel/cache mimarisi değiştirilmedi. Public yeni store bu default temizliğinden ayrıdır. Harici bir işlem frontend dizinini aynı anda silerse ayrıca ortam incelemesi gerekir.

## 2. Cache sözleşmesi

- `config/cache.php` frontend_ttl =604800sn. PHPUnit testing driver=array; normal ortam file. Dashboard/security ayrı store'ları korunur.
- `FrontendCache`: resource + revision + APP_VERSION + uygulama locale'i + sorgu bağlamı hash anahtarı. Null sonuç da wrapper ile saklanabilir.
- Sayfalamada page/limit, arama uygulanma durumu, görüntüleme sırası ve ilgili ders/konu ID'si ayrılır. Paginator yolu cache dönüşünde mevcut isteğe uyarlanır.
- Serbest metin aramaları ve100. sayfadan sonrası doğrudan sorgulanır; key varyasyonunun gereksiz büyümesi azaltılır. İlk100 sayfa sınırsız veri hacmi kabulü değildir.
- Kaynak: lessons/topics/tests(süresiz Exam)/exams(süreli Test)/blogs/faqs/pages/navigation/languages/settings.
- Model saved/deleted/restored observer ilgili revision'ları yeniler. Soru/cevap değişimi ilgili katalogları; ders/konu/test değişimi bağlı sayaç ve menüyü yeniler. Dil/çeviri model değişimi locale'e bağlı public kaynakları yeniler.
- Pivot sync observer üretmez: UpdateExamQuestionsAction açık invalidation yapar. Query-builder ile toplu ana sayfa kaydı: UpdateHomePageAction pages/navigation revision yeniler. SettingSaveAction lokalize ayar metni cache'ini yeniler.
- Yalnız hits/updated_at değişimi katalog cache'ini bozmaz. Sayacın veritabanı artışı sürer, public sıralama/sayaç görüntüsü en fazla TTL boyunca eski kalabilir. Kayıt silme, son işlem hits olsa bile invalidation yapar.
- Planlı blog yayını için TTL, aynı dildeki bir sonraki aktif yayın zamanıyla kısaltılır; kaydı tekrar düzenlemeye gerek kalmadan yayın görünür olur.
- Eski revision dosyaları yeni isteklerde okunmaz. TTL her dosyanın otomatik diskten silineceği anlamına gelmez; yoğun yazma/çok büyük katalogda disk hacmi ve ayrı bakım politikası ölçülmelidir. Bu tur purge işi eklenmedi.
- Full HTTP HTML, Livewire snapshot, CSRF, oturum, öğrenci sonucu veya favorileri ortak cache'e alınmaz. Ayarlar paketinin tamamı/SMTP kimlik bilgileri değil, mevcut `settingLocale` çağrısının tek metin değeri saklanır.

## 3. Liste ve sorgu değişimleri

| Alan | Uygulama |
|---|---|
| Dersler | Tam exams ilişkisi yerine SQL distinct test sayısı; aktif konu sayısı korunur. code/name OR araması nested where. Yalnız kullanılan kolonlar. |
| Konular | lesson:id,name eager loading; tam exams ilişkisi yerine distinct sayaç. Önceki konu sayacının bütün testleri sayma anlamı korunur. |
| Testler / Exam | Public katalog ve aktif soru/public yorum sayaçları ortak cache. Favoriler yalnız ID sorgusu. Kullanıcı sonuç adedi tek toplu loadCount, doğru/yanlış bütün görünür testler için tek GROUP BY. |
| Sınavlar / Test | Aktif kapsam, bölüm/soru sayaçları korunur; yalnız kullanılan kolonlar ve dil adı getirilir. |
| Blog | Listede büyük content taşınmaz; içerikte arama SQL'de kalır. Dil, yayın zamanı, sayfa ve görüntüleme sırası ayrılır. |
| Menü/footer | Dar kolonlar; genel dil/ders/konu/footer verisi ayrı public cache. Menü paginator'ı gövdenin page değerinden etkilenmez. |
| İletişim/bülten | Yeni kayıt genel cache/compiled view temizlemez; iletişimde doğru `data_countContactMessageNotRead` anahtarı yenilenir. Kayıt, onay ve email doğrulama kuralları korunur. |

Test kartında ortak model/paginator klonlanır, kişisel sayaçlar yalnız klona eklenir. Serialize etmeyen testing array driver'da da sonraki öğrenci/misafir bu sayaçları devralmaz. Yarım ve tamam denemelerdeki mevcut bütün cevap toplamı korunur; lig/istatistik kuralı değişmedi.

## 4. İlk HTML ve asset URL'leri

Lazy kaldırılan yedi sınıf: ListLessons, ListTopics, ListTests, ListExams, ListBlogs, ListFaqs ve Home/LessonsSection. İlk görünür içerik için ikinci mount isteği beklenmez. Diğer hesap/çözüm/yorum/lig ve panel lazy bileşenleri bu tur topluca değiştirilmedi.

Beş ana sayfa section'ı yalnız kullandığı content alanlarını public dizisine kopyalar; her section'a bütün ana sayfa ayarı tekrar aktarılmaz. Page model referansı mevcut görseller için korunur. Görsellerin normal yüklenmesi, SEO/canonical ve tasarım korunur.

Favicon türevleri/manifestte her istekte değişen `time()` URL parametresi APP_VERSION'a bağlandı. Yeni CSS/JS dosyası değişimi yok; APP_VERSION **1.1.12** kalır. Asset değişiminde sürüm güncelleme standardı devam eder.

## 5. Dar otomatik kanıt

Yalnız testing DB, DatabaseTransactions ve mevcut optimize:clear mock'u. Ana veritabanı, migration, seeder, queue worker, email/R2 gönderimi veya deploy yok.

- İlk dar koşu: **18 test /75 doğrulama**. Yeni cache/SQL/kişisel kapsam8 test; ilgili eski arama/pagination/aktif liste/global reset10 test.
- Sonradan eklenen bulk ana sayfa/pivot invalidation: **1/3**.
- İlk HTML dört ana bölüm ve iletişim/bülten hatalı giriş koruması: **2/24**.
- Son lokalize ayar kaydetme/invalidation: **1/3**.
- Toplam **22 farklı dar senaryo /105 doğrulama**, ayrı koşularla başarılı. Tam suite koşusu değildir; bütün ürün/rol/cihaz kabulü değildir.
- CacheHit lessons sorgusu0; cold lessons2 SQL, ilişkili tam exams collection yüklenmez. Konu lesson ilişkisi yüklenmiş ve yinelenen pivot test sayısı1.
- Ortak test kataloğunda guest sıcak sorgu0; öğrenci için sonuç bilgileri2 SQL. İki öğrenci ve ardından misafir fixture'ı birbirinin sonuçlarını görmez.
- TTL6. gün korunur/8. gün yenilenir; locale/revision ayrımı, hit-only güncelleme, ardından delete, planlı yayın ve arama/deep-page sınırı sınandı.
-26 PHP dosyası syntax ve Pint başarılı. Blade akışları ilgili HTTP/Livewire ve Chrome render kontrollerinde açıldı.

## 6. Chrome ve yerel ölçüm

Gerçek Google Chrome, izole testing web sunucusu127.0.0.1:8765; /tmp cache/storage ve mail=array. Asıl80/3306 portları veya diğer projenin servisi değiştirilmedi. Yedi ekran (`/`, `/lessons`, `/tests`, `/exams`, `/blog`, `/contact`, `/lesson/qa1005-01`) cold/warm HTTP200; arama/temizleme iki Livewire isteği200; pageerror yok. Mobil390px ana sayfada yatay taşma yok.

Testing ana sayfasında dört bölüm kapalıydı. Bölümler transaction testinde açılıp **ilk controller HTML'inde** doğrulandı; o HTML için ayrı masaüstü/mobil görsel kontrolü yapıldı. İlk QA harness denemelerinde geçici dosya dizini/asset hostu/debug Livewire dosyası uyuşmazlığı vardı; yalnız /tmp harness düzeltildi. Son görsel kontrolde dört görünür başlık, CSS/JS, mobil taşmasız görünüm ve pageerror0 doğrulandı. Bu sentetik HTML kontrolü gerçek browser form gönderimi/üretim kanıtı değildir. QA blog görselinin eski fixture dosyası yeni /tmp uploads alanında yoktur; üretim görselleri değiştirilmedi.

Aşağıdaki **yükseltme öncesi/sonrası kıyas değil**, bu uygulamanın aynı yerel QA akışındaki ilk/ikinci isteğidir. Ana sayfa ölçümü kapalı bölümlü testing içeriğidir; SQL hacmi canlı veriyi temsil etmez. Süre yalnız kernel handle; ağ/LCP/production hızlanma katsayısı değildir. Son settingLocale store taşımasından önceki ölçüm anıdır; son değişiklik ayrıca yalnız iletişim ekranında kontrol edildi.

| Yol | Cold SQL → warm SQL | Cold → warm yerel ms |
|---|---:|---:|
| / |8→2|107,82→17,69|
| /lessons |4→2|41,54→23,17|
| /tests |7→2|35,07→16,53|
| /exams |5→2|25,74→18,11|
| /blog |5→2|27,38→18,32|
| /contact |3→3|25,38→19,27|
| /lesson/qa1005-01 |7→4|30,50→30,17|

Konu yolu route binding ve gerçek hits update yapar; sıcak isteğin toplamı bu yüzden sıfır değildir. İletişim formu/user/session HTML'i cache edilmez. Sıfır SQL veya bütün site için maksimum hız garantisi verilmez. Son contact tekrarında HTTP200/pageerror0; sıcak kernel15,92ms/3SQL.

## 7. Dosya haritası ve kalan sınırlar

Yeni: app/Helpers/FrontendCache.php; tests/Feature/FrontendPerformanceCacheTest.php; bu belge.

Değişen kaynaklar: config/cache.php; app/Helpers/DataHelper.php; app/Providers/AppServiceProvider.php; app/Observers/TableCacheObserver.php; app/Traits/Loggable.php; Contacts/CreateMessageAction ve CreateNewsLetterAction; Exams/UpdateExamQuestionsAction; Pages/UpdateHomePageAction; Settings/SettingSaveAction; Frontend HomeController; Frontend NewsletterForm; Lessons ListLessons/ListTopics; Tests ListTests; Exams ListExams; Blogs ListBlogs; Faqs ListFaqs; Home Welcome/Lessons/Tests/Blog/FaqSection; frontend/layouts/app ve ilgili üç liste Blade'i. CacheCandidateTest'in reset beklentisi yeni store ayrımına göre güncellendi. AGENTS/16/50/index rehberi güncellendi.

Kapsam dışı mevcut adaylar: diğer panel CRUD'larında genel resetCache, saatlik optimize temizliği;50 içindeki çeviri snapshot/sitemap ve diğer izin/detail adayları;51 paket karşılıkları. Yeni CDN, asset bundling, frontend tema değişimi veya DB indeks migration'ı yapılmadı. Büyük canlı hacimde sorgu EXPLAIN/HTTP/LCP, dosya cache disk hacmi ve gerçek eşzamanlı yük testi ayrı ölçüm gerektirir; bu tur otomatik uygulanmadı.
