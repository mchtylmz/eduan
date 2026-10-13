# Kalan adaylar: cache, dolu detay ve izin matrisi

Tarih: 5 Ekim 2026. Bu kayıt `18` ve `20` belgelerindeki kalan adayların dar incelemesidir. **Güncel durum: kullanıcı 15 başarısız senaryonun düzeltmesini onayladı; bölüm 10'daki son koşuda 19 test/79 doğrulama geçti.** Bölüm 1–9 ilk incelemenin tarihsel kanıtını korur; oradaki başarısız/açık ifadeleri düzeltme öncesine aittir. Yükseltme, paket değişimi, migration, ana/canlı veritabanı yazımı ve gerçek mail gönderimi yapılmadı.

## 1. Kapsam ve test ortamı

- Sıra: filtre/cache doğruluğu → uzun TTL ve genel temizliğin etkisi → dolu detaylar → panel giriş ve öğretmen/işlem izinleri.
- Üç yeni test sınıfı: `CacheCandidateTest`, `PopulatedDetailTest`, `PanelPermissionMatrixTest`.
- Bütün DB fixture'ları yalnız `testing` bağlantısında `DatabaseTransactions` ile geri alınır. Ortam sınırı `tests/TestCase.php` tarafından da denetlenir.
- Cache ve güvenlik cache'i ayrı array store'dur. Yeni sınıflarda Artisan mock/spy ile model event'lerinin `optimize:clear` çağrıları yakalanır; son test düzeneği gerçek komutu çalıştırmaz. Mail ve queue detay/izin sınıflarında fake'dir.
- İlk doğrudan pagination düzeneği, sayfa başlatılmadan resolver'ın aynı bileşene bağlanması yüzünden döngü/signal 11 oluşturdu. Testte önce `setPage(1)` yapılarak giderildi; uygulamanın çalışma zamanı hatası olarak raporlanmadı.
- İlk detay fixture'ında Page türü açıkça CUSTOM verilmediği için sistem sayfası redirect'i görüldü. Tür düzeltildi. Dolu blog fixture'ının brief/keywords alanları dolduruldu; nullable davranış ayrıca iki hedefli teste ayrıldı.
- İlk cache spy geçmişi model create çağrısını da içeriyordu. Etki testinde fixture sonrasında yeni mock nesnesiyle geçmiş ayrıldı. Ön koşu hataları nihai bulgu sayısına dahil değildir.
- HTTP + hydrated Livewire kontrolü vardır; gerçek tarayıcı, ağ kesme, JavaScript/Bootstrap etkileşimi veya üretim yük ölçümü yapılmadı.

## 2. Sonuç özeti

**Düzeltme öncesi 61 ayrı senaryo için son ilgili koşuların birleşimi: 46 geçti, 15 başarısız.** Bu sayı tek bir tam proje koşusu değildir. Testler skip/xfail yapılmadı veya yanlış davranışı onaylayacak şekilde yeşile çevrilmedi; kullanıcı onayından sonra uygulama düzeltmesiyle geçti (bölüm 10).

| Dar koşu / son kanıt | Sonuç | Doğrulama / süre |
|---|---|---|
| PanelPermissionMatrixTest başlangıç koşusu | 34: 28 geçti, 6 başarısız | 136 assertions / 6,14 sn |
| PopulatedDetailTest dolu sayfa ve eksik ID koşusu | 17: 16 geçti, 1 başarısız | 80 assertions / 2,87 sn PHPUnit sınıf süresi |
| CacheCandidateTest son koşusu | 8: 2 geçti, 6 başarısız | 20 assertions / 0,55 sn PHPUnit sınıf süresi |
| Yalnız altı izinsiz yazma + iki nullable blog kontrolü | 8 başarısız | 8 assertions / yaklaşık 1,99 sn sınıf süreleri toplamı |

Son satır önceki altı izinsiz yazma senaryosunu DB değişikliği kontrolüyle kesinleştirir ve iki yeni nullable senaryo ekler. Altı tekrar toplam senaryo sayısına yeniden eklenmez. Farklı koşuların assertion/süre sayıları tek bir koşunun toplamı gibi sunulmaz. Önceki `18` belgesinin 102 senaryosu bugünkü koşuda bütünüyle yeniden çalıştırılmadı.

## 3. Doğrulanan bulgular ve somut düzeltme planı

### B21-01 — ders/konu/sayfa kayıtlarında işlem izni eksik (öncelikli)

Dosyalar: `app/Livewire/Lessons/LessonForm.php`, `Topics/TopicForm.php`, `Pages/PageForm.php` ve çağırdıkları `CreateOrUpdate*Action` sınıfları.

Panel route'u dashboard ve resource:view izni istiyor; Blade kayıt düğmesi add/update izniyle gizleniyor. Fakat `save()` aynı izni sunucuda denetlemiyor, ilgili Action'da da bu denetim yok. Yalnız `dashboard:access` + ilgili `*:view` verilen kullanıcıyla doğrudan Livewire çağrısında:

- Ders, konu ve sayfa oluşturma: `forbidden-*` kaydı DB'ye gerçekten eklendi.
- Düzenleme: `Original` metni gerçekten `Changed` oldu.
- Altı ayrı regresyon testi kaydın değişmemesi/oluşmaması beklentisinde başarısız.
- Bütün işlemler test transaction'ında geri alındı; gerçek öğrenci veya içerik değiştirilmedi.

Önerilen dar düzeltme: `save()` başında gerçek model varlığına göre add/update seç; dashboard ve ilgili işlem iznini sunucuda doğrula. İstemciden değiştirilebilen `permission` metnini güvenilir kaynak sayma. Açıldıktan sonra izin kaldırılması ve başarılı yetkili kayıt senaryosu bu üç form için ayrıca doğrulanmalı. Yeni role/permission üretmeye ihtiyaç yok; mevcut izinler kullanılacak.

### B21-02 — arama açık/kapalı durumu cache anahtarında yok

Dört dosya: `Frontend/Lessons/ListLessons.php`, `ListTopics.php`, `Frontend/Tests/ListTests.php`, `Frontend/Exams/ListExams.php`.

Aynı kelime/sayfa/diğer filtrelerde `search=false` durumuyla sonuç önce cache'e girerse, arama eylemi `search=true` yaptığı halde anahtar değişmiyor. Dört ayrı testte yalnız `needle` beklenirken filtrelenmemiş kayıtlar geri geldi.

Kanıt bileşenin sorgu/cache durum geçişidir. Blade'deki `wire:model="word"` ertelenmiş olduğundan, bu kanıt her ilk arama gönderiminin her zaman hatalı olduğunu göstermez. Yazılmış kelimeyle başka render/filtre/eylem yapılması ve sonra aramayı açma durumu önemlidir; gerçek tarayıcı adımları ayrıca kabul testine alınır.

Önerilen düzeltme: sorguyu etkileyen `intval($this->search)` değerini dört cache anahtarına ekle; arama/temizleme mevcut ilk sayfaya dönme davranışını koru. İlgili altı cache hatasını gidermek için TTL'i aynı anda değiştirmek gerekmez.

### B21-03 — blog ikinci sayfa ilk sayfanın cache'ini kullanıyor

`Frontend/Blogs/ListBlogs.php`: anahtar locale, hits tercihi, limit, search içeriyor; sayfa yok. 19 yayımlanmış aktif blog/18 sayfa boyutunda ikinci sayfa `currentPage=1` dönüyor.

Önerilen düzeltme: `getPage()` anahtara eklenir. İlk/ikinci sayfa kayıtları, arama ve boş sonuç aynı küçük fixture ile kontrol edilir; mevcut üç saat TTL korunabilir.

### B21-04 — lig sonuç cache'i kişi sayısını ayırmıyor

`Frontend/Leagues/LeagueResultTable.php`: anahtar yalnız league ID + ranking. Aynı lig/ranking için bir kişilik liste cache'e girdikten sonra üç kişi istendiğinde yine bir kişi dönüyor.

Önerilen düzeltme: `userCount` anahtara dahil edilir. Bu yalnız gösterilecek aralığın doğruluğudur; toplam deneme puanı, dönem veya eşitlik/RANK sözleşmesi değiştirilmez. Mevcut ekranda farklı sayı kullanılan widget/tasarım ve settings değişimi cache sıcaklığıyla birlikte değerlendirilir; normal sabit sayı kullanımında her istek hatalı değildir.

### B21-05 — panel sınav sonucu görünümü eksik

`Backend/TestsController::result(Test $test)` `backend.tests.result` döndürüyor; `resources/views/backend/tests/result.blade.php` yok. Aktif sınav, bölüm, soru ve tamamlanmış öğrenci sonucu içeren fixture'la yetkili GET 500 döndü: `View [backend.tests.result] not found.`

Önerilen düzeltme: mevcut `backend.exams.result` blok/başlık düzenine uygun Blade görünümü; Test modelinin adı/kodu ve `tests.results-table` bileşeni ilgili Test filtresiyle kullanılır. Rappasoft bugün değiştirilmez. Sonuç tablosunun gerçekten bu sınava filtrelendiği de doğrulanır.

### B21-06 — nullable blog metinleri edit formunu düşürüyor

`Blogs/BlogForm.php` mount sırasında `brief` ve `keywords` doğrudan non-null string property'lere atanıyor. Form validation'ı ikisini de nullable|string kabul ediyor. İki ayrı testte brief=NULL ve keywords=NULL kayıtları düzenleme GET'inde TypeError/500 oluşturdu.

Önerilen düzeltme: yalnız bu nullable metinlerin mount fallback'i boş string olur. Dolu içeriği/encode-decode davranışını değiştirme; başlığı ve zorunlu alanları gevşetme. NULL/dolu değerin yeniden açılması ve yetkili kayıt kontrol edilir. published_at gibi farklı kurallı alanlar bu iki testin kanıtı değildir.

## 4. Doğrulanmayan aday: pasif dersin aramada görünmesi

`ListLessons` içindeki düz OR kaynakta şüpheli göründü; fakat test aktif/pasif code eşleşmeleriyle yalnız aktif dersi aldı. Laravel Eloquent local scope'un önceki OR koşullarını gruplaması göz önüne alınmalıdır. Bu aday bug olarak listelenmez ve buna özel uygulama değişikliği önerilmez. Test gelecekte sorgu değişimi için korunur.

## 5. TTL envanteri — kaynak hesaplaması, yeni süre kararı değil

| Alan | Gerçek mevcut süre | Değerlendirme |
|---|---|---|
| Ders, konu, SSS | 60×60×60×365 = 78.840.000 sn = 912,5 gün | Bir yıl değildir; fazladan 60 çarpanı vardır |
| Süresiz test listesi | 60×60×365 = 1.314.000 sn ≈ 15,21 gün | Yıllık süre değildir |
| Süreli sınav listesi | 60×60×30 = 108.000 sn = 30 saat | Yanındaki “1 ay” yorumu doğru değil |
| Blog listesi | 3 saat | Kaynak süre |
| Lig sonuç tablosu | 12 saat | Anahtarda gösterilecek sayı eksik |
| Lig listesi | 24 saat | Kaynak süre |
| Test detay ders/konu istatistiği | 365 gün | Anahtarda test ve kullanıcı |
| Home sayfa cache'i | 30 gün | Kaynak süre |

Canlı CACHE_STORE/config ve scheduler'ın gerçekten çalışması bu kaynak sürelerinden çıkarılamaz. Çok uzun TTL bugün verinin gerçekten yıllarca eski kaldığını tek başına kanıtlamaz: global invalidation sık tetikleniyor. Süreleri topluca küçültmek de doğru anahtar/temizleme sorununun yerine geçmez.

Hedef süreler kullanıcı kararı/ölçüm olmadan değişmez. Plan: her alanın tazelik ihtiyacını belirle; model/ilişki bazlı invalidation kapsaması çıkar; yalnız ilgili kayıt sonrası etkilenen cache grubunu temizle; gerekirse makul güvenlik TTL'i ekle. Redis tag gibi yeni altyapı/paket önkoşulu yaratmadan mevcut store yeteneklerine uygun basit yaklaşım seçilir.

## 6. Genel cache temizliği ve sorgu etkisi

`resetCache()` üç adım: varsayılan store `flush()`, aynı store `clear()`, ardından Artisan `optimize:clear`. Kilitli Laravel kaynağında son komut cache, compiled/config/event/route/view cache'lerini temizler; provider komutları da eklenebilir.

- `Loggable` created/updated/deleted olayları bu helper'ı çağırır. Birçok Action/form da tekrar çağırır; tek işleme birden çok temizlik bağlanabilir.
- Süresiz test `SolveForm::saveAnswer` ayrıca çağırır; sonuç/detail modellerinin Loggable olayları da etkilidir. Yeni süreli sınav Action'ları kendi kodunda açık reset yazmasa bile model trait'lerinin etkisi sürer.
- `bootstrap/app.php` her saatin 15. dakikasında `optimize:clear` tanımlar. Bu, canlı cron'un bu görevi gerçekten çalıştırdığına dair işletim kanıtı değildir.
- Array store etki testi: ilk ders sorgusu SQL üretir; sıcak tekrar sıfır SQL üretir; reset sonrası aynı sorgular yeniden üretilir. Bağımsız ekran marker'ı da silinir. Gerçek compiled dosya temizliği spy ile engellenir.
- Aynı testte ayrı `security` store limiter marker'ı korunur. Bu, array store ayrımının yerel kanıtıdır; canlı dosya yolu/çok worker yapılandırması ayrıca doğrulanır.
- Sonuç: cache kapsamının geniş olduğu ve sıcak sorgu kazanımının başka işlemle kaybolduğu doğrulandı. Üretim TTFB/CPU/IO, kaç çağrı/saniye veya bütün kullanıcılardaki gecikme ölçülmedi; sayısal hız kazancı vaat edilmez.

Somut sonraki plan: model/event → etkilenen ekran anahtarları eşlemesi; transaction sonrası invalidation ihtiyacı; lig job'larının ve settings/çeviri/ilişki değişiminin cache güncelleme kapsamı; normal içerik kaydında optimize komutunun gerekliliği; saatlik schedule'ın ayrı operasyon kararı. Global helper/schedule bugün kaldırılmadı veya değiştirilmedi.

## 7. Panel izin matrisi — mevcut dar kanıt

18 giriş ailesi sınandı: contacts, newsletter, blogs, lessons, topics, questions, exams, tests, users, roles, stats, settings, languages, ai, leagues, seasons, pages/all ve pages/faqs. FAQ `pages:view` kullanır.

| Aktör / izin | Beklenti | Kanıt |
|---|---|---|
| Misafir | Login redirect | 18 girişte geçti |
| Yalnız ilgili resource:view | Dashboard engeli, 403 | 18 girişte geçti |
| Yalnız dashboard:access | Resource engeli, 403 | 18 girişte geçti |
| Dashboard + resource:view | Giriş 200 | 18 girişte gerçek lazy render açıkken geçti |
| Dashboard + lessons/topics/pages:view, add/update yok | Oluşturma/düzenleme engeli | Altı yazma testi başarısız; gerçek DB değişikliği doğrulandı |
| Dashboard + questions/exams/tests/blogs:view, add yok | Doğrudan save 403 | Dört create eyleminde geçti |
| Öğretmen gerekli izin + bağlı öğrenci | Kendi öğrenci tabları açılır | user/favorite/tests/exams/topicsNotResult/topicsStats: altı tab geçti |
| Aynı öğretmen + ilişkisiz öğrenci | Kullanıcı listesine redirect | Aynı altı tabda geçti |

Bu tablo gerçek canlı rol dağılımı değil, test kullanıcılarına verilen açık izinlerin matrisidir. Admin rol adı otomatik tüm izinlerin kanıtı sayılmaz; öğretmen davranışı `user-type:teacher` iznine bağlıdır. Kullanıcı mevcut kurala göre yalnız kendi öğrencilerini yönetir.

Eksik geniş matris: her modülde add/update/delete, doğrudan Livewire action, istemci permission/model değişimi, izin/ilişki iptali, soft-deleted nesne, export ve queue/job sırasında kapsam. Önceki `AccessBoundaryTest`, `StudentAccessTest`, `StudentAssignmentTest`, `StudentStatsAccessTest`, mail/export testleri bazı hücreleri kapsar; bu tur hepsi yeniden koşulmadı. Giriş testi, modülün bütün yazma/yükleme/export yetkilerinin onayı değildir.

## 8. Dolu sayfa kanıtı ve sınırı

Başarılı: site ders→konu, süresiz test detay, süreli sınav bölüm detay, blog detay, footer custom Page; panel ders/konu/soru/süresiz test/süreli sınav/blog/sayfa edit, sınav bölümleri, süresiz test sonuç ekranı. Aranan metinler gerçek fixture verisidir; lazy placeholder ile yetinilmedi.

Öğrenci süreli sonuç detayında gerçek soru/yanıt görüntülendi; aynı öğrencinin yarım denemesi ve başka öğrencinin tamamlanmış denemesi view sonuç koleksiyonuna girmedi. Beş site detayında olmayan code/slug 404 verdi.

Başarısız: panel süreli sonuç route'u eksik view; blogun iki nullable alanı. Dolu blog edit normal değerlerle geçer, nullable testler ayrı kırmızıdır.

Henüz bütün detaylar değil: iletişim/bülten/AI/log, tüm lig ve sezon geçmişi, review moderation yaşam döngüsü, nl/en/tr varyantları, silinmiş ilişki/eksik metadata, gerçek tarayıcı modal/TinyMCE/upload/accordion/pagination ve büyük veri. Gerçek PWA offline/logout/eski worker ve canlı worker/SQL karşılaştırması da açık kalır. SSS liste sorgusunda locale filtresi yok; tüm dillerin birlikte gösterilmesi mi, yalnız seçili dil mi gerektiği ayrı ürün teyidi gerektirir, bug kararı verilmedi.

## 9. Sonraki karar ve genel test kapısı

Önerilen sıra:

1. Mevcut izinlerle üç formun kayıt sınırını kapat.
2. Dört arama cache'i + blog sayfası + lig kişi sayısı anahtarlarını düzelt.
3. Eksik panel sınav sonuç görünümünü mevcut OneUI düzeninde tamamla; nullable blog metinlerini düzelt.
4. Yalnız etkilenen kırmızı testleri ve gerekli olumlu/sınır senaryolarını çalıştır; sonuçları bu kayda ekle.
5. TTL/global invalidation için somut ayrı planı kararlaştır; bugün ölçülmemiş optimizasyonu topluca uygulama.
6. Yeni kırmızı testler giderilip testlerin ortak cache/queue/mail/DB ve dosya yazma yan etkileri kontrol edildikten sonra kullanıcının genel test kararını uygula. Tek tam suite yalnız mevcut yazılmış senaryoları kapsar; eksik ürün senaryolarını kendiliğinden üretmez.

**İlk incelemenin sonunda tüm ürün/upgrade için “okey” verilmedi.** Kullanıcı daha sonra bu 15 hata senaryosunun düzeltmesini açıkça onayladı; aşağıdaki bölüm uygulama ve son kanıttır. Laravel 13/paket geçişi ve canlı SQL/migration karşılaştırması ayrı fazlarda kalır.

## 10. Onaylı düzeltmeler ve son dar doğrulama

### Durum ve yapılan değişiklikler

**Sonuç: fixed — onaylı B21-01…B21-06 bulguları ilgili yerel testlerle giderildi.**

1. Ders/konu/sayfa `save()` başında dashboard ve gerçek model varlığından seçilen add/update izni kontrol edilir. Public `permission` yalnız UI alanı olarak kalır; yazma izni kaynağı değildir. Denetim validation/Action/DB yazmasından önce gerçekleşir. En yakın BlogForm/QuestionForm biçimi kullanıldı; yeni soyutlama, Action API değişimi veya izin/seeder eklenmedi.
2. Dört liste cache anahtarına arama aktifliği eklendi. Blog anahtarına sayfa, lig sonuç anahtarına kişi sayısı eklendi. Arama temizleme, mevcut sıralama/puan ve sorgu filtreleri korunur. Çağıranlarda bu anahtarlara doğrudan özel forget bulunmadı; mevcut global invalidation kapsamı değişmedi.
3. `backend/tests/result.blade.php` mevcut OneUI `backend/exams/result` düzeninden oluşturuldu. Başlık/kod ve `tests.results-table :test="$test"` ile seçilen sınav gösterilir.
4. Blog mount'ta nullable brief/keywords için boş string fallback eklendi. Zorunlu içerik/başlık, encode-decode ve yayın tarihi kuralları değiştirilmedi.

### Güvenlik sınırı ve bağımsız inceleme

Kaynak → sink: yetkili panel sayfasındaki Livewire `save` çağrısı → CreateOrUpdate Action → Eloquent create/update. Görüntüleme izninin yazma iznine dönüşmemesi gerekir. Üç formun tek yazma girişinde sunucu denetimi eklendi; Action'ların başka uygulama çağıranı veya bağımsız HTTP kaydı bulunmadı. Gerçek modelin exists koşulu Action'a mevcut/yeni kayıt gönderme koşuluyla aynıdır.

`codex-security:fix-finding` rehberiyle bir salt okunur sınır/uyumluluk incelemesi ve bir bağımsız son patch incelemesi yapıldı. İzin property değişimi, Livewire model kimliği/snapshot, lifecycle çağrısı ve izin iptali değerlendirmesinde somut kalan bypass veya meşru akış regresyonu bulunmadı. Bu, diğer modüllerin güvenlik taraması değildir.

### Çalıştırılan dar kontroller

1. Değişen 10 uygulama PHP dosyası + üç test dosyasında Docker PHP `php -l`: **13/13 geçti**. İlgili `git diff --check` geçti; yeni Blade HTTP testinde derlendi.
2. Son hedefli komut:

```sh
docker compose exec -T laravel.test php artisan test --compact \
  tests/Feature/CacheCandidateTest.php \
  tests/Feature/PanelPermissionMatrixTest.php \
  tests/Feature/PopulatedDetailTest.php \
  --filter 'test_search_activation|test_blog_cache|test_league_cache|test_view_permission_does_not_allow|test_authorized_writes|test_blog_with_nullable_field|test_panel_timed_result|test_populated_detail_renders_real_content.*timed-results'
```

**19 test geçti, 79 assertions, 2,80 saniye.** Önceki 15 başarısız vaka + üç formun olumlu/izin yaşam döngüsü vakası + bir sınava göre sonuç filtreleme vakasıdır. Bütün 61/65 senaryo veya tam proje suite'i bu komutla çalıştırılmadı.

| Alan | Son kanıt |
|---|---|
| View-only create/update | Altı vaka 403; yeni kayıt yok, mevcut metin değişmedi |
| Yetkili kayıt ve izin yaşam döngüsü | Üç formda create/update geçti; açıldıktan sonra update iptali, sahte public permission, yalnız update ile create ve dashboard iptali engellendi |
| Arama cache'i | Dört listede filtreli sonuç doğru; temizleme sonrası tam liste geri geldi |
| Blog sayfası | İkinci sayfa/tek kayıt doğru; arama ve boş sonuç doğru |
| Lig kişi sayısı | Bir kişilik sıcak cache ardından üç kişi doğru döndü |
| Sınav sonuç ekranı | Önceki 500 artık 200; bileşen mevcut; başka sınav sonucu seçilen sınav sorgusuna girmedi |
| Blog nullable | İki NULL alan edit ekranını açtı, boş string yükledi, yeni metinle kaydedilip tekrar DB'den doğrulandı |

İlk düzeltme koşusunda 18 vaka geçti; yeni olumlu konu testinin ikinci Livewire bileşeni lazy placeholder'da kaldığı için validation hatası verdi. Test düzeneğinde her instance için `lazy:false` açıkça belirtildi; uygulama kodu bunun için değiştirilmedi. Yalnız bu vaka tekrar geçti. Bağımsız inceleme sonrası yukarıdaki son dar koşu tamamen geçti; gereksiz tam suite/build tekrarı yapılmadı.

### Bu pakette değişen dosyalar

| Dosya | İşlem |
|---|---|
| `app/Livewire/Lessons/LessonForm.php` | Sunucu add/update + dashboard izni |
| `app/Livewire/Topics/TopicForm.php` | Sunucu add/update + dashboard izni |
| `app/Livewire/Pages/PageForm.php` | Sunucu add/update + dashboard izni |
| `app/Livewire/Blogs/BlogForm.php` | Brief/keywords NULL fallback |
| `app/Livewire/Frontend/Lessons/ListLessons.php` | Search durumu cache anahtarı |
| `app/Livewire/Frontend/Lessons/ListTopics.php` | Search durumu cache anahtarı |
| `app/Livewire/Frontend/Tests/ListTests.php` | Search durumu cache anahtarı |
| `app/Livewire/Frontend/Exams/ListExams.php` | Search durumu cache anahtarı |
| `app/Livewire/Frontend/Blogs/ListBlogs.php` | Sayfa cache anahtarı |
| `app/Livewire/Frontend/Leagues/LeagueResultTable.php` | Kişi sayısı cache anahtarı |
| `resources/views/backend/tests/result.blade.php` | Yeni sınav sonuç görünümü |
| `tests/Feature/PanelPermissionMatrixTest.php` | Yetkili kayıt/izin iptali/sahte permission kontrolleri |
| `tests/Feature/CacheCandidateTest.php` | Arama temizleme ve blog arama/boş sonuç kontrolleri |
| `tests/Feature/PopulatedDetailTest.php` | Nullable edit+kayıt ve seçilen sınav sonuç filtresi |
| `AGENTS.md` | Güncel kapanış/test kaydı |
| `Codex/20-SAYFA-DUZENI-VE-TEST-MATRISI.md` | İlgili matrisin kapanış durumu |
| `Codex/21-KALAN-INCELEME-CACHE-DETAY-YETKI.md` | Bu bölüm ve tarihsel/güncel kanıt ayrımı |
| `Codex/index.html` | Sekiz maddelik özetin güncellenmesi |

### Kalan sınır

Onaylı 15 hata vakası kapandı. TTL/global reset ve saatlik optimize schedule incelemesi ayrı karar olarak durur. Gerçek tarayıcı/JS/PWA, bütün işlem/rol matrisi, canlı SQL ve worker kanıtı bu paketle tamamlanmış sayılmaz. Ana/canlı DB, migration, paket, deployment veya gerçek mail işlemi yapılmadı. Tam proje testine veya Laravel yükseltmesine bu dar sonuç tek başına genel onay değildir.
