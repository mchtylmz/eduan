# İlk indekslerin uygulanması ve ikinci SQL optimizasyonu

5 Ekim 2026. Kullanıcı üç indeksin ana yerel DB'ye uygulanmasını açıkça onayladı. Yalnız `2026_10_05_150000_add_initial_query_performance_indexes` çalıştırıldı; uygulama **hypotenuse** DB'sinde tamamlandı. Üretim sunucusunda işlem yapılmadı.

## 1. Hedef ve migration sonucu

- Gerçek bağlantı/DB adı hypotenuse, uygulama ortamı local olarak PHP içinde ayrıca doğrulandı.
- Uygulamadan önce aynı adlı üç indeksin ve migration kaydının yokluğu denetlendi.
- `migrate --database=mysql --path=database/migrations/2026_10_05_150000_add_initial_query_performance_indexes.php` kapsamı Console Kernel üzerinden yürütüldü; başka pending migration çalıştırılmadı.
- Migration **DONE**, 915,56 ms. Kayıt **id=37 / batch=7**. İlk 36 kayıt/id/batch aynen korundu.
- Ana DB'de users_username_index, exam_results_season_completed_updated_index ve exam_result_details_result_question_index doğru kolon sırası ve nonunique özelliğiyle mevcut.
- Eski bütün indeksler korundu; başlangıç 174 → **177 indeks**. Tablo/kolon sayıları değişmedi: 50 / 401.
- Etkilenen üç tabloda users=381, exam_results=16.231, exam_result_details=149.904; toplam **166.516 satır**. Önce/sonra COUNT(*) ve EXTENDED CHECKSUM aynı.
- Migration uygulama aşamasında testing'in 50 tablo checksum'u aynı kaldı. Sonraki testler yalnız testing'de transaction veya kendi schema restore işlemleriyle çalıştı.
- Üç normal indeks eklemek dışında veri düzeltmesi, unique/FK, eski deneme tamamlama, mail izin aktivasyonu veya seeder çalıştırılmadı.

## 2. Bu uygulamaya ait yeni yedek

İçe aktarılmış güncel ana DB ayrıca mysqldump --single-transaction ile yedeklendi:

`storage/app/private/codex-db/20261005-indexes/hypotenuse-before-indexes.sql.gz`

Boyut **18.995.817 bayt**. SHA-256:

`c34b8e4ff511e0b59704c3ab588fd8121ba85704b50d03831c528e85212986db`

Gzip açılması ve dump bitişi doğrulandı; restore provası yapılmadı. Bu yedek indeks uygulamasından önceki **ithal edilmiş kopyayı** korur. 26'daki before-import yedeği ise ithalattan önceki eski yerel DB'yi korur; ikisini karıştırma. SQL/yedek/özel kanıtlar ignore altında; kullanıcı satırları rehbere/Git'e kopyalanmadı.

## 3. Ölçüm yöntemi

Dört SELECT için aynı mevcut kayıtlar, aynı parametreler ve aynı SQL önce/sonra kullanıldı. Parametreler özel dosyada tutuldu; kişisel kullanıcı adı rehbere alınmadı. PHP PDO prepared statement üzerinden, Laravel/HTTP boot süresini ölçmeden sorgu execute+fetch süresi alındı. Her durumda 3 ısınma + **9 ölçülen örnek**, raporda medyan var. Örnekler sorgular arasında dönüşümlü alındı; her faz kendi bağlantısını kullandı.

MySQL session mode uygulamanın mevcut strict=false davranışına uygun NO_ENGINE_SUBSTITUTION idi. Benchmark read-only SELECT/EXPLAIN kullandı; kullanıcı girişi, auth session, last_login güncellemesi veya queue işi tetiklemedi. Migration CLI'sinin normal Laravel boot'u ise mevcut AppServiceProvider'ın local cache flush davranışını içerir; ayrıca optimize:clear komutu verilmedi.

Sezon liste sorgusu, updated_at eşitliğinde karşılaştırmanın belirli olabilmesi için ek id DESC tie-breaker içerir. Panel kaynak sorgusunun sadeleştirilmiş temsili; tam Rappasoft eager loading/search/count sorgusu değildir. Her sorgunun önce/sonra dönen satır sayısı ve SHA-256 sonuç özeti eşleşti.

## 4. Yerel sorgu süreleri

| Sorgu | Önce medyan | Sonra medyan | Bu örneklemde oran |
| --- | --- | --- | --- |
| Var olan kullanıcıyla giriş araması | 0,1825 ms | 0,1213 ms | 1,50× |
| Olmayan kullanıcıyla giriş araması | 0,5089 ms | 0,1199 ms | 4,24× |
| Sezon/tamamlanmış sonuç listesi, LIMIT 15 | 9,0772 ms | 0,2086 ms | 43,51× |
| Sonuç içindeki soru cevabı | 0,8658 ms | 0,1165 ms | 7,43× |

Bunlar aynı yerel kopyada kısa, ısınmış **sorgu** ölçümleridir. HTTP sayfa yüklemesinin veya üretimin bu oranlarda hızlandığı söylenmez. Küçük mutlak sürelerde işletim sistemi/ağ/DB yükü varyansı vardır; kesin SLA veya evrensel yüzde kazanç değildir. Bu tur geniş load test, EXPLAIN ANALYZE veya cihaz/PWA testi yapılmadı.

## 5. EXPLAIN karşılaştırması

| Sorgu | Önce | Sonra | Kanıtın anlamı |
| --- | --- | --- | --- |
| login-hit/miss | users_deleted_at_index; ref; scan tahmini 379 | users_username_index; ref; tahmin 1 | Kullanıcı adıyla doğrudan indeksli arama seçiliyor. Olmayan ad için 1 tahmini gerçek bulunan satır değildir; sonuç 0. |
| season-results | exam_results_completed_index; ref; 8.217 tahmin; filesort var | yeni season/completed/updated indeksi; range; 8.217 tahmin; filesort yok | Tahmin düşmedi; yeni indeks filtre/sıra ve LIMIT akışını karşılıyor. “Gerçekte 8.217 → 15 satır tarandı” sonucu çıkarılmaz. |
| result-answer | iki tek kolonlu indeksin intersect/index_merge planı; tahmin 1 | yeni result/question indeksi; ref; tahmin 1 | Birleşik anahtarla arama; index_merge kalktı. |

Optimizer'ın üç yeni indeksi kendiliğinden seçtiği doğrulandı; FORCE INDEX kullanılmadı. [İndeks ve kolon sırası ilkesi](https://dev.mysql.com/doc/refman/8.0/en/multiple-column-indexes.html) 30'da kaynaklandı. Yeni indekslerin toplam ek disk/yazma maliyeti henüz ayrı benchmark edilmedi; gereksiz yeni geniş indeksler eklenmez.

## 6. İkinci SQL optimizasyonu: filtre seçenekleri

[FilterHelper.php](../app/Helpers/FilterHelper.php) içinde yalnız iki query ifadesi değişti:

- examsInResults: ExamResult::all()->select('exam_id') → ExamResult::select('exam_id').
- testsInResults: TestsResult::all()->select('test_id') → TestsResult::select('test_id').

whereIn artık Laravel Query Builder'ın SQL alt sorgusunu kullanır. Önce sonuç tablosunun tamamı PHP'ye taşınıp model oluşturuluyor, ardından ana liste sorgusu çalışıyordu. Artık sonuç kimlikleri DB içinde seçilir; yalnız gereken test/sınav seçenekleri uygulamaya döner. Ana kopyanın mevcut 16.231 süresiz/379 süreli sonuç modelini sırf seçenek üretmek için hydrate etme kaldırıldı.

Bu iki cache-miss akışında **2 SQL yerine 1 SQL**; cache hit hâlâ 0 SQL. Seçenek biçimi `(code) name`, id DESC sırası, cache anahtarı/TTL, sonuç varlığı, yarım/tamam/tekrar deneme seçimi ve SoftDeletes kapsamı korundu. Ek active/completed/öğretmen filtresi getirilmedi; yeni yetki veya istatistik iş kuralı uygulanmadı. Üst ekranın mevcut izin/scope kontrolü bu performans değişiminin dışında kalır.

Sonuç filter'larının gerçek HTTP/PHP bellek/süre benchmark'ı yapılmadı; bu ikinci değişim için kanıt tek SQL, doğru seçenekler ve cache davranışıdır. Paket/framework/mimari refactor yapılmadı; mevcut helper standardıyla iki satırlık değişiklik.

## 7. Hata testleri ve formatter

İlk tekrar: eski indeks testi **1 test/41 doğrulama** geçti. Sonraki hata incelemesinde bu testin migration'ı önceden uygulamış testing şemasında precondition nedeniyle çalışamadığı görüldü; ilgili test düzeltildi.

[InitialQueryIndexesTest.php](../tests/Feature/InitialQueryIndexesTest.php) artık üç rezerv indeks başlangıçta varsa kolon/unique tanımını doğrular, test sırasında kontrollü olarak bu indeksleri çıkarıp up/down sınar ve finally ile önceki varlık durumunu geri kurar. Hem indekslerin bulunmadığı hem önceden uygulandığı durum sınanır. MySQL DDL transaction rollback'e bırakılmaz; main bağlantıya TestCase guard izin vermez.

[ResultFilterQueryTest.php](../tests/Feature/ResultFilterQueryTest.php) süresiz/süreli iki veri kümesinde:

1. Tek SQL ile seçenek üretme.
2. Tekrarlı denemeyi tek seçenekte toplama.
3. Yarım denemelerin varlığını koruma.
4. Passive seçeneğin mevcut davranışını koruma.
5. Sonucu olmayan seçeneği dışlama.
6. Soft-delete edilmiş test/sınavı dışlama.
7. id DESC ve etiket biçimi.
8. Cache hit'te aynı seçenek ve 0 SQL.

Son ilgili komut:

```sh
docker compose exec -T laravel.test php artisan test --compact tests/Feature/InitialQueryIndexesTest.php tests/Feature/ResultFilterQueryTest.php
```

**4 test / 107 doğrulama geçti; 1,14 saniye.** Bütün proje suite/build tekrar çalıştırılmadı. Formatter migration ve iki test dosyasında kontrol edildi; son constructor/`!` boşluk düzeltmelerinden sonra üç dosya da PASS. Eski FilterHelper dosyasının bütünü yeniden formatlanmadı; iki sorgu satırında yakın kod düzeni korundu. Bu testler tüm ürün/rol/upgrade kabulü değildir.

## 8. Kanıt dosyaları ve güncel faz

Özel klasör `storage/app/private/codex-db/20261005-indexes`: prepare-summary, main önce/sonra checksum/count/index/migration metadata, testing checksum, before/after measurements ve verification-summary. parameters-private kullanıcı adı/ID içerir; rapora/Git'e taşınmaz. Küçük measure/apply PHP yardımcıları sadece bu yerel uygulamanın özel çalışma araçlarıdır; ürün route/command eklenmedi.

İlk indeks paketi **uygulandı ve doğrulandı**; ikinci dar helper optimizasyonu **uygulandı ve test edildi**. Yeni indeks migration'ı ana DB'de Ran, testing repository'de bu tur kalıcı migrate yapılmadı. Testing testleri başlangıç şemasını geri kurar.

Mevcut kaynak/sonuç kanıtıyla paket seçimi fazına geçilebilir. 24'teki adaylardan kullanıcı tarafından kaldırılacaklar/uyumlandırılacaklar seçilir; bu tur Composer veya Laravel değişmedi. Rappasoft geçişi ve Langscanner alternatifi ayrı çağrı/özellik planlarıyla yürütülür. Sonraki performans paketi için lig/pivot/aktif deneme/cache/UNION ölçümleri 29'da durur; ölçüm olmadan bunların tamamına indeks eklenmez. 52 tarihsel boş sınav, soru sayacı/orphan politikası ve stat kartı seçimi hâlâ ayrı yanıttır; ilk iki optimizasyon bunları çözmüş sayılmaz.
