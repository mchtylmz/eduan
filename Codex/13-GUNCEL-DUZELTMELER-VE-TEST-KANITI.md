# Güncel düzeltmeler, test kanıtı ve kalan sınırlar

> Sonraki istatistik toplamı onaylandı ve düzeltildi; panel CRUD/export kanıtı ve yeni bulgular `17-PANEL-ISLEV-TESTLERI-VE-ISTATISTIK.md` içindedir. Aşağıdaki kapsam tablosu önceki koşuyu anlatır.

> Sonraki kesin lig/mail kararları ve test kanıtı `15-LIG-VE-MAIL-KARARLARI.md` içindedir. Bu dosyadaki 57 testlik tablo önceki paketin tarihsel kanıtıdır.

5 Ekim 2026. Bu dosya `11` numaralı ilk paketin ve `12` numaralı tarihsel kapsam değerlendirmesinin devamıdır. Güncel durum için önce bu kaydı oku. Çalışma ağacı henüz commit/deploy edilmedi. Laravel veya Composer paketleri yükseltilmedi. Kullanıcının geliştirme öncesi Git sınırı `2b31f4f5` korunur.

## 1. Kullanıcının kesinleştirdiği kararlar

- Kaynak dil ve hedef varsayılan dil `nl`; mevcut ortam ayarı değiştirilmeden korunuyor.
- Süreli sınavda tek aktif deneme; yeniden açma cevapları ve ilk bitiş zamanını korur. Tamamlandıktan sonra yeni deneme serbesttir. Süre dolana kadar cevap değişebilir/kaldırılabilir.
- Kapalı sayfada süre sonunda job tamamlar. Canlıdaki dakikalık `--stop-when-empty` cron gecikmesi kabul edildi; süre sonrasında cevap kabulü uzamaz.
- Güncel karar: süresiz testin yarım/tamam bütün denemelerindeki kaydedilmiş cevaplar, süreli sınavın yalnız tamamlanmış bütün denemeleri lige girer. Kullanıcı son deneme isteğini geri çekti. Mevcut dönem ve eşitlik sırası korunur.
- Öğretmen ve okul aynı rol/kapsamdır; öğretmen yalnız kendi öğrencilerini görür. Öğretmen yalnız kendisine öğrenci bağlayıp ayırabilir; mevcut işlem izinleri ayrıca gerekir.
- Girişte hesap + IP için dakikada beş başarısız deneme; kayıt ve parola kurtarmada IP için dakikada beş istek.
- Parola sıfırlama mevcut config'in 60 dakikalık süresi ve tek kullanım ile yürür. Eski e-posta doğrulama bağlantıları korunur; süreli imza sonraki fazdır.
- Panel görselleri JPEG/PNG/WebP/GIF/BMP, en fazla 10 MB. Kullanılmayan genel ayar `files` alanı reddedilir. Kaynakta bulunan ayrı sınav PDF yüklemesi korunur ve PDF/10 MB/işlem izniyle denetlenir.
- PWA isim-soyisim çevrimdışında da gösterir. Hesap, sonuç, test/sınav ve panel HTML'si çevrimdışı saklanmaz. Yalnız görünen ad ayrıca saklanır.
- Canlı migration tablosu/SQL yedeği daha sonra sağlanacak; yükseltmeden önce incelenecek. Ana yerel ve canlı DB testlere kapalıdır.

## 2. Süresiz test çözümü: istemci doğruluğu yerine sunucu kaydı

Kaynak: `app/Livewire/Frontend/Tests/SolveForm.php`. Kullanıcıdaki test burada `Exam` modelidir; süreli sınav ile karıştırma.

Önceki uygulama public `results.isCorrect` bilgisiyle kayıt yapabiliyor ve doğrudan son soruya geçme/tamamlama sınırı yeterince denetlenmiyordu. İki saldırı testi düzeltme öncesinde beklenen 422 yerine 200 döndü; bu, kontrol eksikliğinin yerel yeniden üretimidir.

Yeni akış:

1. Giriş, aktif test ve LOGGED görünürlük veya `exams:solve` izni sunucuda denetlenir; normal oturumlu testler için ek premium izni zorunlu değildir.
2. Test, soru kimlikleri, sıra, sonuç kimliği ve başlangıç zamanı Livewire `Locked` alanlarıdır. Public sonuç yalnız seçilen cevap kimliğini taşır.
3. Seçim yalnız mevcut sorunun cevapları arasından kabul edilir. Başka soru cevabı ve `isCorrect` gibi sahte alanlar 422 üretir.
4. Doğruluk DB cevap kaydından hesaplanır. İlk kayıt mevcut süresiz ekran davranışına uygun olarak kilitlidir; bu, süreli sınavın cevap değiştirme kuralını değiştirmez.
5. İlerlemede önceki cevabın DB'de bulunması gerekir. Sonuç ancak bütün seçili soru kimlikleri cevaplandığında tamamlanır.
6. Doğrudan `save`/`complete` çağrısı da yetkiyi ve tamamlanmayı kontrol eder. Tamamlanan sonucun üzerine yazılmaz; tekrar çözüm ayrı geçmiş kaydıdır.
7. Soru/cevap modeli sağlayan yardımcı private'dır; Livewire public metot dönüşünden doğru cevap nesnesi sızmaz. Ayrı test doğrudan helper çağrısını reddeder.

Sınır: MySQL transaction/kayıt kilitleri kullanılıyor; aynı anda gerçek iki tarayıcının yarışması bu pakette test edilmedi. Silinen/değiştirilen soru havuzunun uzun süren açık denemelere etkisi ayrıca senaryo ister.

## 3. Giriş ve parola sıfırlama

`LimitAuthRequests` rate limiter anahtarlarını oluşturur. Girişte gerçek DB kullanıcı kimliği kullanılır; MySQL collation'ın eşdeğer kabul ettiği kullanıcı adı varyasyonları aynı limite bağlanır. Var olmayan hesaplarda normalize ad + IP kullanılır. Başarılı giriş kendi başarısız deneme sayacını temizler. Kayıt/kurtarma limiti doğrulamadan önce işler.

`config/cache.php` içindeki ayrı `security` limiter deposu normal uygulama cache temizliğinden etkilenmez. Testing'de array, uygulamada file driver kullanır. Çok sunuculu ortak limiter gereksinimi veya proxy/IP güven zinciri bu yerel testlerin kanıtı değildir.

Parola token akışı:

- Yeni token linkte rastgele değerdir; DB'de SHA-256 tutulur. Geçerli eski ham tokenlar desteklenir.
- Token biçimi, tam eşleşme, oluşturulma zamanı, config süresi ve aktif kullanıcı kontrol edilir. DB'nin harf duyarsız eşleşmesi tek başına yeterli sayılmaz.
- Formun ilk açılışı ve kaydı ayrı doğrulanır. Açıkken süresi dolmuş veya yeni bağlantıyla değiştirilmiş token yazamaz.
- Parola değişimi ile token silme aynı transaction içindedir; bildirim mailinin çalışması beklenmez.
- Geciken başarı maili artık yeni oluşturulmuş başka tokenı silemez. Mail yardımcıları public Livewire eylemi değildir.

Testlerde mail fake kullanıldı; hiçbir gerçek e-posta gönderilmedi. E-posta doğrulama bağlantılarının süreli imzası kullanıcı kararıyla uygulanmadı.

## 4. Yüklemeler ve nullable kayıtlar

Ortak `UploadFileAction` panel erişimini, klasör whitelist'ini, gerçek MIME'ı, istemci uzantısını ve boyutu denetler. Kaydedilen dosya uzantısı gerçek MIME'a göre seçilir; içeriği dönüştürmeden WebP adı verilmez. Watermark gerçek kayıt uzantısında encode edilir. PNG kaydının watermark sonrasında da PNG kalması test edildi.

Question/Blog/PageHome/AI/SelectPdf çağrılarında ilgili yazma izni dosya yazılmadan kontrol edilir. Settings yazımı ayrıca `settings:update`, görsel dizisi ve genel `files` yasağını denetler. Favicon görseliyle manifest güncelleme koşulu düzeltildi. Başka panel formlarının tüm yazma davranışları bu paketle kapsanmış değildir.

`TestForm` ve `ExamForm` nullable `content` değerini boş string olarak açar. `Question` eksik görsel ve logo durumunda URL accessor'ından string döndürür. Önceki `toArray()` soru modelini diziye çevirme iddiası geri çekilmiş durumda kalır; yeni hata olarak tekrar raporlama.

## 5. Öğretmen/okul erişimi: uygulanan bölüm ve açık bölüm

`AuthorizeStudentAccess` dashboard + users:view + ilgili işlem iznini denetler. `user-type:teacher` izni bulunan aktör için hedef kullanıcının `teacher_student` ilişkisinde bulunması gerekir. Rolün adı yerine mevcut izin standardı kullanılır.

Uygulandı: kullanıcı formu açma/kaydetme, parola formu açma/kaydetme, favori listesi sorgusu, çözülmeyen konu listesi sorgusu, kullanıcı tablosu silme eylemi, öğrenci/öğretmen atama ve kaldırma formları, atama tabloları ve istatistik sorgularının okuma/export kapsamı. Kullanıcı formunda yalnız sunucunun sunduğu roller atanabilir. Açıldıktan sonra ilişki kaldırılırsa parola kaydı da reddedilir.

**Bütün öğretmen kapsamı kapandı denilemez:**

- `StudentAddForm` mevcut atanabilir öğrenci havuzundaki `users:teacher-findable` iznini denetler. Öğretmen hedef teacherId olarak yalnız kendisini kullanabilir. `TeacherAddForm` kendi öğrencisi için yalnız kendisini öğretmen olarak seçebilir; başka öğretmenin atamasını/kaldırmasını yapamaz. Öğretmen olmayan, ilgili izni verilmiş aktör mevcut genel atama davranışını sürdürür.
- `StudentsTable` / `TeachersTable` sorguları ve kaldırma eylemleri her çağrıda yetki/kapsam denetler. Controller öğretmenin kendisine ait yalnız `teachers` sekmesini açmasına izin verir; kendi diğer sekmeleri ve başka öğretmen ekranı bu istisnaya girmez.
- `ResultStatsCustomTable` null kullanıcıyı boş modelle açar; global ekranda dashboard+stats:view, kullanıcı ekranında users:view+sahiplik gerekir. Seçili öğrenci ekranı public userId ile genişletilemez; öğretmen global sorguları da kendi öğrencileriyle filtrelenir. Üç query/export testi ve null filtre testi geçti. Aynı testin çoklu denemelerini GROUP BY ile hangi satırdan okuyacağı ayrı doğruluk incelemesidir; bu pakette değiştirilmedi.
- **Sonradan düzeltildi:** `MailingForm` / `Stats/SendEmailForm` yeni users:send-email ve öğretmen öğrenci bağlamı kapısıyla korunur; girilen adres kullanıcı kararıyla DB e-postasıyla eşleştirilmez. Enqueue hataları loglanır. Dar kanıt ve DB izin aktivasyonu sınırı `15` dosyasındadır.
- Kullanıcı dışındaki sonuç/istatistik tablolarında bütün okuma/export yolları henüz rol matrisiyle doğrulanmadı.

## 6. Lig hesabındaki dar doğruluk düzeltmeleri

`CalculateLeagueResult`: negatif `incorrectPoint` ayarını tekrar -1 ile çarparak yanlış cevabı ödüllendirme kaldırıldı; boş sonuç collection'ı için lig oluşturulmaz; yeniden hesaplamada `league_number` da güncellenir. Bunların iki testi vardır.

**Son deneme planı iptal edildi.** Kullanıcı bütün tekrarların toplanmasını ve mevcut puan → doğru sayısı → süre/RANK sırasını istedi. Yarım süresiz test de lige girer; süreli sınav tamamlanmış olmalıdır. Hesaplama kodu bu sözleşmeye uyduğu için değiştirilmedi; üç yeni regresyon testi `15` dosyasında kayıtlıdır.

## 7. SEO ve PWA

Canonical gerçek `link rel="canonical"` oldu; sosyal URL mevcut sayfayı gösterir. Özel auth/hesap/çözüm rotaları noindex olur. Sitemap kök tekrarını, arama sayfasını ve pasif/yayın zamanı gelmemiş kayıtları dışlar; ters Test/Exam isimlendirmesine uygun gerçek detay rotalarını kullanır. Canonical query parametresi temizliği, hreflang, gerçek Search Console ve Core Web Vitals sonraki kapsamdır.

Root `public/pwa-sw.js`, asıl worker dosyasını import eder. Root scope kaydı ve eski `/assets/` scope'unun kaldırılması sağlanır. Cache `hypotenuse-pwa-2` yalnız anonim `/offline.html` ve izinli statik assetleri saklar; bütün navigation istekleri network üzerinden gider. Ağ hatasında nötr offline HTML döner. Upload/PDF/Livewire/POST istekleri bu cache'e girmez; yalnız bu uygulamanın eski cache'leri temizlenir.

İsim-soyisim layout meta alanından `localStorage['pwa-user-name']` içine yazılır; guest sayfası açıldığında temizlenir. Offline ekran `textContent` kullanır, kullanıcı metnini HTML olarak çalıştırmaz. Başka hesap bilgisi, sonuç veya token bu özellik için saklanmaz. Aynı cihazda son oturumlu kişinin adı, guest sayfası çevrimiçinde açılana kadar görünebilir; logout sonrası gerçek çevrimdışı geçiş ayrıca tarayıcı senaryosu ister.

Tarayıcıda offline HTML açıldı, yeniden deneme ana sayfaya döndü ve console hatası görülmedi. Gerçek ağ kesintisi, mobil kurulum, mevcut cihazın eski worker'dan geçişi ve offline ad görünümü gerçek cihazda doğrulanmadı. Node VM testleri gerçek service worker yaşam döngüsünün yerine geçmez.

Dağıtımda `APP_VERSION` artışı/cache politikası somut planla hazırlanmalıdır: app.js URL'si bu değeri kullanır. Mevcut `.env` değiştirilmedi; worker cache sürümü kaynakta artırıldı. Eski cihazların yeni app.js alması sağlanmadan geçiş tamamlandı denilemez.

## 8. Test kanıtı

Yalnız ayrı `testing` DB. `tests/TestCase.php` uygulama oluşturulmadan önce ve oluşturulduktan sonra DB adını denetler; yanlış DB'de test transaction'ı başlamaz. Ana DB migration/seed/fresh çalıştırılmadı. Storage/Mail/Queue testlerinde ilgili fake'ler kullanılır.

| Dosya | Test sayısı | Dar kapsam |
|---|---:|---|
| AccessBoundaryTest | 8 | İlk panel/çeviri izin sınırları |
| DefaultLocaleTest | 1 | Ayarsız yeni session nl |
| TimedExamTest | 7 | Kalıcı cevap, deadline, tamamlama, sahiplik, tamamlanan lig |
| NullableContentTest | 2 | Null içerik/görsel fallback |
| PracticeTestSecurityTest | 6 | Sunucu doğruluğu, soru sahipliği, helper gizliliği, tamamlama |
| AuthSecurityTest | 8 | Limit, DB eşdeğer kullanıcı adı, token süre/tüketim/eski-yeni |
| PanelUploadSecurityTest | 7 | Dosya MIME/uzantı/boyut, izin, watermark, ayar files |
| FrontendSeoTest | 2 | Gerçek HTML canonical/noindex ve XML sitemap |
| LeagueCalculationTest | 2 | Negatif puan, boş sonuç, grup yeniden hesaplaması |
| StudentAccessTest | 5 | Başka öğrenci reddi, kendi parola, iptal ilişki, rol sınırı |
| StudentAssignmentTest | 6 | Kendi bağlama/ayırma, sahte teacherId, havuz izni, iptal yetki, izinli genel atama, kendi atama sekmesi |
| StudentStatsAccessTest | 3 | Global/null ekran, kendi öğrenci sorgu/export, sahte filtre kapsamı |
| **İlgili PHP toplamı** | **57** | **218 assertion; aşağıdaki dar koşular geçti** |
| tests/Frontend/PwaSafetyTest.cjs | 7 | Offline install/navigation/cache, isim metni ve guest temizliği |

Bu pakette 41 PHP testi eklendi; önceki 16 testle birlikte 57 anlamlı PHP testi oldu. Laravel'in iki ExampleTest'i bu sayıya dahil değil ve bu koşuda çalıştırılmadı. Coverage yüzdesi ölçülmedi.

Birlikte koşu: ilk 10 dosya 48 test/189 assertion, 13.05 saniye. Karar sonrasında atama dosyası beş test/13 assertion (2.11 saniye) ve ek HTTP testi bir test/altı assertion (0.67 saniye) olarak dar çalıştırıldı. İstatistik dosyası üç test/10 assertion (1.08 saniye). HTTP fixture'ına zorunlu aktif dil kaydı eklendi; boş test DB dil hatası üretim hatası olarak sunulmadı. Sonraki dar dosyalar nedeniyle bütün paket tekrar koşturulmadı.

PHP komutu: `docker compose exec -T -e APP_ENV=testing -e DB_DATABASE=testing laravel.test php artisan test` ardından ilgili Feature dosyaları ve `--no-ansi`. JavaScript komutu: container içinde `node --test tests/Frontend/PwaSafetyTest.cjs`. Tüm proje suite'i veya build çalıştırılmadı; ilgili dosyalar bir kez birlikte doğrulandı.

## 9. Tam kapsam ve yükseltme kararı

Henüz tüm ürün senaryoları test edilemiyor. Eksik başlıklar: soru havuzunun CRUD/ilişki/dil akışları; bütün panel kayıt/export işlemleri; öğretmen UI rol matrisi ve gerçek mail/worker teslimatı; çoklu deneme istatistik görünümü; gerçek cron/queue, aynı anda iki istemci, gerçek offline/logout; tam giriş/kayıt başarılı akışları ve e-posta doğrulama yaşam döngüsü; üretim SQL/indeks/taşıma; çok dilli SEO ve ölçülmüş hız.

Yerel test zemini hazırdır; her yeni özellik kendi başarılı/hatalı/yetkisiz sınır testlerini eklemelidir. Test sayısı güvenlik onayı, tüm ürün kapsamı veya üretim kanıtı değildir. Laravel 13 geçişine koşulsuz onay verilmedi. Gelecek plan ve kapılar `14-YUKSELTME-VE-GELECEK-FAZLAR.md` içindedir.
