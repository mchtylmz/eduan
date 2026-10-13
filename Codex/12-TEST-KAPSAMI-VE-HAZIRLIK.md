# Test kapsamı, kalan bulgular ve hazırlık

> Tarihsel değerlendirme: aşağıdaki 16 testlik tablo ilk paket sonrasına aittir. Sonraki düzeltmeler, genişleyen test kapsamı ve bugün açık kalan konular için `13-GUNCEL-DUZELTMELER-VE-TEST-KANITI.md` okunmalıdır. Bu dosyanın eski "test yok" satırları bugünkü kaynak durumu olarak kullanılmaz.

5 Ekim 2026. Bu kayıt testlerden önce yapılan kapsam değerlendirmesidir. Uygulama kodu, test kodu ve veritabanı bu değerlendirmede değiştirilmedi. Yeni tam suite veya build çalıştırılmadı. Önceki dar test kanıtı `11-ILK-DUZELTMELER-VE-SINAV-AKISI.md` içindedir.

## 1. Bugün tam kapsamlı test yapılabilir mi?

**Henüz hayır.** Depoda 18 test metodu vardır: ilk düzeltme paketine ait 16 anlamlı test ve Laravel'in iki örnek testi. Önceki birlikte koşuda 16 hedefli test / 80 assertion geçti. Bu sayı modül kapsam yüzdesi veya bütün ürün senaryolarının doğrulandığı anlamına gelmez; coverage ölçülmedi.

Bugün tüm testleri çalıştırmak yalnız yazılmış testleri çalıştırır. Giriş, kayıt, öğretmen kapsamı, soru havuzu, bütün panel işlemleri veya tarayıcı etkileşimleri kendiliğinden test edilmiş olmaz. PHP testleri tarayıcı JavaScript'inin, PWA'nın ve gerçek cron/worker'ın çalıştığını tek başına kanıtlamaz.

Hedef, üzerinde anlaşılmış iş kurallarını başarı, hata, yetki, sahiplik ve sınır durumlarıyla kapsayan bir senaryo listesi oluşturmaktır. Her olası girdi/ortamı tamamen test etme garantisi verilmez; eksik kapsam açıkça kaydedilir.

## 2. Mevcut anlamlı testler

| Dosya | Test | Doğrulanan kapsam | Sınır |
|---|---:|---|---|
| `tests/Feature/AccessBoundaryTest.php` | 8 | Misafir sınav başlangıcı; panel kayıt izinleri; doğru güncelleme izni; izinli oluşturma; doğrudan soru ilişkisi eylemini engelleme; çeviri izni, dil kapsamı, izin iptali ve JSON üretimi | Bütün panel işlemleri/rolleri değil; gerçek JSON dosyası yerine Filesystem fake |
| `tests/Feature/DefaultLocaleTest.php` | 1 | Kayıtlı varsayılan ayar yokken yeni session için `nl` | Bütün dil/session/JSON/fallback kombinasyonları değil |
| `tests/Feature/TimedExamTest.php` | 7 | Yeniden girişte cevap/süre; seçim kaldırma; kapalı sayfa için job handler; tekrar çözme; geç cevap; elle tamamlama/puan; yanlış soru/bölüm/öğrenci; izin iptali; tamamlanmış denemelerin ligi | Queue fake ve doğrudan handler; gerçek worker, ağ ve eşzamanlı istemciler değil |

İki `ExampleTest` iş kuralı kapsamına dahil edilmez. Sınav testlerinin bir metodu birden fazla kontrol içerebilir; test adedi senaryo adediyle aynı değildir.

## 3. Modül kapsam matrisi

| Alan | Bugünkü durum | Yazılması gereken temel senaryolar |
|---|---|---|
| Giriş, kayıt, şifre kurtarma, hesap doğrulama | Özelliğe özel test yok | Doğru/yanlış giriş, doğrulama hatası, mükerrer hesap, aktiflik, token geçerliliği/tekrar kullanımı, izin ve hız sınırı |
| Rol/izin, öğrenci/öğretmen/okul kapsamı | İlk düzeltmelerde dar izin testleri | Her işlemin sunucu izni; başka öğrenciye erişememe; öğretmen ilişkisi; izin sonradan kaldırılması; menü ve işlem tutarlılığı |
| Ders, konu, soru, cevap havuzu | Özelliğe özel test yok | Oluşturma/düzenleme/silme, lesson-topic uyumu, cevap-soru bağı, doğru seçenek, geçersiz ilişkiler, çeviri alanları |
| Panel test/sınav tanımı | Kısmi | Dolu mevcut kayıt düzenleme, nullable içerik, soru sırası/bölümü, süresi/puanı, boş havuz, aktiflik, yanlış veri, ilişki güncelleme |
| Öğrencinin test çözmesi (`Exam`, `Frontend/Tests`) | Özelliğe özel test yok | Sunucu puanlaması; public state değiştirme; seçenek-soru eşleşmesi; eksik/tekrarlanan cevap; tamamlanma; yeniden çözme; izinsiz çağrı |
| Süreli sınav (`Test`, `Frontend/Exams`) | Kısmi, 7 hedefli test | Tam süre sınırı, hiç cevap yok, geçersiz matris, pasif sınav/izinsiz başlangıç, erken job, retry, eşzamanlı başlangıç/tamamlama, eski sonuçlar |
| Sonuçlar, favoriler, profil, istatistikler | Özelliğe özel test yok | Yalnız kendi sonuçları, başka kullanıcı ID'si, favori tekrarları, profil/şifre doğrulaması, hesaplama ve boş durum |
| Lig ve sezon | Yalnız tamamlanma filtresi testli | Dönem sınırları, birden çok deneme, eşit puan/sıralama, boş sonuç, test ve sınav kaynakları |
| Diller ve çeviriler | Dar izin + `nl` testi | Aktif dil seçimi, session/query, eksik çeviri fallback, geçersiz locale, gerçek dosya biçimi, dil CRUD |
| Ayarlar ve dosya yüklemeleri | Özelliğe özel test yok | Alan doğrulama, `settings:update`, MIME/boyut/içerik, saklama/yol, yetkisiz yükleme, hata durumunda tutarlılık |
| Panel tabloları ve dış aktarım | Özelliğe özel test yok | Filtre/sıralama/sayfalama, yetkili veri kapsamı, Excel/PDF içerikleri, boş liste, tarayıcı tablo davranışı |
| Sayfalar, blog, SSS, iletişim, bülten | Özelliğe özel test yok | Aktif/pasif ve bulunamayan içerik, form doğrulama, izin, e-posta gönderimini fake ile sınama |
| SEO, PWA, cookie ve ön yüz JS | Tarayıcı otomasyonu yok | Canonical/sitemap/dil/robots HTML'i; consent; offline ve özel veri cache; form etkileşimi; ilk yükleme |
| Mail, AI, backup, Cloudflare ve diğer entegrasyonlar | Özelliğe özel test yok | Yerel fake sözleşmesi, hata/timeout; gerçek servis kontrolü ayrıca açık onayla |
| SQL ve performans | Ölçümlü kapsam yok | Kritik liste sorguları, veri hacmi, N+1, EXPLAIN/indeks adayları, ön yüz ölçümleri; süre hedefi kullanıcıyla netleşmeli |

## 4. Kalan bulguların kanıt düzeyi

### Somut hata: nullable içerik ile string özellik

`TestForm::initializeExistingTest` ve `ExamForm::initializeExistingExam`, nullable `content` değerini `public string $content` özelliğine doğrudan atar. İlk dar test hazırlığında `TestForm` null fixture ile TypeError verdi. Mevcut testin fixture'ına boş string yazılması bu ürün hatasını çözmez. İki form için null içerikli mevcut kaydı açma senaryosu ayrı regresyon testi olarak ele alınmalı; çözüm bu değerlendirmede uygulanmadı.

### Geri çekilen aday: iç içe modelin diziye dönüşmesi

Önceki `ExamForm` değerlendirmesinde `toArray()` zincirinin içerideki soru modelini diziye çevirdiği iddia edilmişti. Kurulu Eloquent/base Collection kaynakları ve uygulama/DB açılmadan yapılan küçük PHP yeniden üretimi bunun yanlış olduğunu gösterdi: array sarmalı içindeki model nesne kalır. Aday `09-BULGULAR-VE-SORULAR.md` içinde düzeltildi. Mevcut kaydı düzenleme akışı için test yine gereklidir; bu şüphe kanıtlanmış hata değildir.

### Öncelikli negatif test adayları

- Süresiz test çözümünde `Frontend/Tests/SolveForm` public `results` içinde doğru seçenek ve `isCorrect` taşır; `evaluateAnswer` bu durumla karşılaştırır, `saveAnswer` doğruluk değerini kayda aktarır. Sunucuya değiştirilmiş durum/başka sorunun seçeneği gönderilmesi ve doğrudan eylem çağrısı negatif testlerle doğrulanmalıdır. Bu inceleme dinamik saldırı kanıtı değildir.
- Giriş/kayıt/doğrulama route'larında görünür throttle/signed koruması bulunmaması araştırma adayıdır. Token ömrü/tekrar kullanım kuralları ve gerçek ağ katmanı ayrıca incelenmelidir.
- `SettingsController::store` içinde `settings:update` izni vardır; alan ve dosya doğrulamasının yeterliliği ayrıca test edilmelidir. İzin yokmuş gibi raporlanmamalıdır.
- Sitemap URL eşleşmeleri ve canonical/robots çıktıları HTTP/HTML testleriyle; PWA scope/özel veri cache davranışı tarayıcıyla doğrulanmalıdır.
- `resetCache()` ve üretim dışı provider cache temizliği testlerde yan etki yaratabilir; performans ve test izolasyonu açısından izlenmelidir. Bu sebeple tüm yapıyı yeniden yazma yetkisi verilmiş değildir.

## 5. Test yazmaya uygun muyuz?

**Mevcut araçlarla başlayabiliriz.** PHPUnit, Laravel feature testleri, Livewire test API'si ve ayrı MySQL `testing` veritabanı iş kuralları için yeterli başlangıç sağlar. Yeni bir mimari veya test framework'ü şart değildir. Önce küçük, deterministik fixture'lar ve güvenli ortak test zemini gerekir.

1. DB yazan ilk test sınıflarında `testing` DB kontrolü vardır; genel `Tests/TestCase.php` henüz ortak koruma sağlamaz. Yeni DB testlerinden önce kontrolün bütün yazan testlere uygulandığı güvenceye alınmalı; koruma migration/transaction gibi DB işlemlerinden önce çalışmalıdır.
2. `QuestionFactory` mevcut ders/konu/dil verilerini bekler; ders ve konuyu bağımsız rastgele seçebilir. Boş testing DB ve tutarlı ilişkiler için asgari fixture/state hazırlanmalıdır. Birçok sonuç factory'si boş olduğundan sonuçlar kontrollü kurulmalıdır.
3. Ana DatabaseSeeder'a veya mevcut ana DB verisine bağımlı test yazılmamalı. Her test kendi kullanıcı/izin/ders/konu/soru/sonuç ihtiyacını hazırlamalıdır.
4. Dosya, mail, Http ve queue etkileri ilgili testte fake edilmelidir. Ham MySQL puanlama sorgularını doğrulamak için MySQL kullanılmalı; SQLite ile aynı davranış kanıtlandığı varsayılmamalıdır.
5. Zaman kuralları sabit saat ve sınır anlarıyla doğrulanmalı. Gerçek queue teslimi ayrı, küçük entegrasyon kontrolü gerektirir; fake başarı bunu kanıtlamaz.
6. `package.json`/Composer içinde kurulmuş Dusk/Playwright/Cypress otomasyonu bulunmuyor. JS, tablo, PWA ve cookie akışları için tarayıcı test kapsamı ayrıca hazırlanmalıdır; araç/paket ekleme kararı şimdilik verilmedi.
7. Üretim config/provider akışları testing ortamından farklıdır. Yerel test sonucu canlı config, fiziksel şema veya sunucu çalışma düzeninin kanıtı olarak sunulmamalıdır.

## 6. Açık sorular ve beklenen girdiler

| Konu | Durum | Hangi adımı etkiler? |
|---|---|---|
| Canlı kuyruk düzeni | Kullanıcı: sürekli Supervisor yok; her dakika cron ile `--stop-when-empty` | İşin gerçek çalışma zamanı; henüz komut/log kontrolü yok |
| Cron gecikmesi kabulü | Yanıtlandı: mevcut cron gecikmesi kabul | Sürekli worker planı gerekmiyor; gerçek çalışma ayrıca doğrulanmalı |
| Canlı migration tablosu ve fiziksel şema | Kullanıcı gönderecek | Şema eşleştirme, yeni migration ve eski veri geçiş planı; mevcut kaynak için yerel test yazılabilir |
| Eski aktif denemeler | Canlı veri incelenmedi | `expires_at` olmayan kayıt/olası mükerrer kayıt geçişi; veri değiştirme onayı ayrıca gerekir |
| İngilizce aktif dil kaydı | Kaynak seed `nl,tr`; `en` fallback mevcut | Dil kaydı ekleme kararı; temel `nl` testi için engel değil |
| Öğretmen/okul kapsamı, lig eşitlik/tekrar puanı, PWA özel veri beklentisi | Alan rehberinde açık karar adayları | İlgili modül senaryoları yazılmadan gereksinim netleşmeli |
| Laravel hedef sürümü, paket/tablo değişimi, hız ölçütleri | Sonraki faz kararı | Mevcut kodun test edilmesini engellemez; bu turda değiştirilmez |

Cron düzeninde gecikmeli iş bitişte kullanılabilir hale gelir; worker boşalıp durmuşsa sonraki cron turunda alınabilir. Kuyruk yoğunluğu ek gecikme yaratır, kesin bir dakikalık üst sınır kanıtlanmış değildir. Kod bitişten sonraki cevapları reddeder ve tamamlanma zamanını ilk deadline'a dayandırır; DB'de tamamlanmış durum/lig görünürlüğü ise işin işlenmesine bağlıdır. Kullanıcı mevcut cron gecikmesini kabul etti. Mevcut düzeni değiştirme onayı alınmadı.

İlk dar test hazırlığını engelleyen yanıtsız soru kalmadı. Canlı şema, eski veri ve alan kararları ilgili fazda netleştirilir; kararı bilinmeyen davranış için beklenen sonuç uydurulmaz.

## 7. Test geliştirme sırası

1. Ortak testing DB güvenliği ve asgari tutarlı fixture'lar; null içerik ve süresiz test yanıtlama için hata/negatif senaryolar.
2. Giriş/kayıt/token, işlem izinleri ve öğretmen/öğrenci sahipliği; ardından soru havuzu ve panel tanımları.
3. Süreli sınavın kalan zaman/eşzamanlılık/job senaryoları; sonuç/favori/lig ve sezon kuralları.
4. Dil, ayarlar, dosyalar, dış aktarım, içerik formları ve yerel entegrasyon sözleşmeleri.
5. İlgili tarayıcı akışları, SEO/PWA/cookie ve ölçüme dayalı performans; canlı ortam kontrolleri ayrı kanıtla.

Her paketten önce yalnız o paketi etkileyen açık iş kuralı sorulur. Yeni test hata bulursa önce kanıt ve dar düzeltme planı sunulur; kullanıcı başka sorunların otomatik düzeltilmesini istemiyor. Özellik başına gerekli dar testler çalıştırılır. Tam suite ancak kapsam oluştuğunda veya bütünleştirme için somut ihtiyaç bulunduğunda çalıştırılır. Bu liste uygulama izni değildir.
