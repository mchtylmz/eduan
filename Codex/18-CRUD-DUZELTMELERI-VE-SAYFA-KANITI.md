# CRUD düzeltmeleri, liste bulguları ve sayfa test kanıtı

5 Ekim 2026. Kullanıcı `17` kaydındaki iki hatanın daha kapsamlı düzeltilmesini onayladı. Sonraki dört bulgu da örnek/test sonuçları gösterildikten sonra ayrıca onaylandı. Bu kayıt önceki “onay bekliyor” durumlarının yerine geçer; migration, ana DB/canlı veri, Composer ve deploy değişmedi.

## 1. SSS dil kaydı — giderildi

- `app/Models/Faq.php` fillable listesine locale eklendi.
- Formun gönderdiği seçilmiş dil artık kayıtla saklanır; yeniden açılış nl yükler.
- Oluşturma → yeniden açma → içerik düzenleme → silme zinciri geçti.
- Önceden dilsiz oluşmuş SSS satırlarına toplu değer atanmadı; canlı veri görülmeden backfill yapılmaz.

## 2. Sınav bölümünün bağlı kayıtları — giderildi

`CreateOrUpdateTestSectionsAction` mevcut transaction'ını korur:

1. Çıkarılan üst bölümün alt alan kimlikleri aynı sınav ilişkisi üzerinden bulunur.
2. Yalnız ilgili sınav + ilgili section ID'lerine bağlı tests_questions satırları silinir.
3. Üst/alt bölümün kendi meta verileri `deleteMeta()` ile temizlenir.
4. İlgili section model kayıtları silinir; başka sınav kayıtlarına dokunulmaz.
5. Tek alt alan çıkarılması da aynı dar temizleme metodunu kullanır.
6. Action'a açık boş fields dizisi gönderilirse eski alt alanlar kalmaz. UI'nin mevcut min:1 form kuralı değiştirilmedi.
7. İşlem sırasında Throwable oluşursa önceki yazmalar rollback edilir, false döner.
8. SectionForm false sonucunda hata mesajı verir, başarı redirect'i vermez. Formdaki gereksiz ikinci resetCache kaldırıldı; Action'ın mevcut başarılı cache temizliği korunur.

Özel meta tablosu kullanan modelde bulk query delete model event'lerini çalıştırmadığı için yalnız parent satırını silmek yeterli değildir. Yeni test metadata temizliğini ve başka sınavın alan/metadata/soru bağlantılarının korunmasını da doğrular. Var olan eski orphan kayıtlar taranıp toplu silinmedi; bu ayrı SQL/veri kararıdır.

Yeni `SectionPersistenceTest` beş senaryo: parent/metadata/link temizliği ve başka sınavı koruma; tek/boş alt alan çıkarma; hatada transaction geri alma; false kayıtta formda kalma; update izni olmadan kayıt yapmama.

## 3. Sonraki dört bulgu — kullanıcı onayıyla giderildi

| Bulgu | Yeniden üretim | Dar düzeltme |
|---|---|---|
| Ders cache'i sayfayı ayırmıyor | 22 ders, 21'lik liste: ikinci sayfa birinciyi döndürüyor | ListLessons cache anahtarına getPage eklendi |
| Konu cache'i sayfayı ayırmıyor | 22 konu, 21'lik liste: ikinci sayfa birinciyi döndürüyor | ListTopics cache anahtarına getPage eklendi |
| Pasif sınav listede görünüyor | Active/passive ve geçerli section/question bağlantılı iki Test listeleniyor | ListExams query'sinde active scope etkinleştirildi |
| Yeni Soru yanlış izinle görünür | questions:add sahibi düğmeyi göremiyor; view lessons:add'a bakıyor | questions/index Blade düğmesi questions:add kullanır |

`FrontendListFindingsTest` bu dördünü ve ters izin durumunu doğrular. Yalnız lessons:add sahibi Yeni Soru düğmesini göremez. Cache testlerinde doğrudan bileşen çağrısına Livewire'ın kullandığı page resolver bağlanır; uygulamadaki lazy/JavaScript davranışı değiştirilmez. Önce kırmızı olan testler artık yeşildir. Eski cache girdileri topluca temizlenmedi; yeni anahtarlar kullanılır.

Pasif sınavın doğrudan detay URL'sine erişim davranışı bu dar liste düzeltmesiyle değiştirilmedi; ayrı ürün/yetki kuralıdır.

## 4. Ek panel işlevleri

`PanelModuleCrudTest` altı senaryo:

- Dil oluştur/oku/düzenle/sil; varsayılan nl silme engeli; yinelenen dil kodu.
- Test dili qx için JSON dosyası olmadığı doğrulanır; gerçek dil dosyası silinmez.
- HTTP rol oluştur/düzenle, izinleri sync etme, role bağlı kullanıcı varken silmeme; kullanıcı ayrılınca test rolünü silme.
- Rol yazmada işlem izni ve zorunlu girdi kontrolü.
- Sezon oluştur/yeniden yükle/düzenle/sil; arka plan işi Queue fake ile iki dispatch.
- Ters tarih ve yinelenen tarih sınırlarının reddedilmesi; hatalı veride kayıt/job oluşmaması.

Rol düzenleme view'ı RoleTypeEnum izinlerinin DB'de tanımlı olmasını bekler. İlk fixture'daki eksiklik gerçek uygulama hatası diye raporlanmadı; enum izinleri yalnız test DB'de oluşturuldu. Gerçek role/permission seeder çalıştırılmadı.

## 5. Sayfa açılış zemini

`PageRenderTest`: **76 test senaryosu**. Liste `20-SAYFA-DUZENI-VE-TEST-MATRISI.md` içindedir.

- 20 panel giriş/listesi (PageController'da index yok; home/all gerçek rotalarıdır).
- 11 yeni kayıt sayfası.
- 13 settings tabı.
- 11 site giriş sayfası.
- 7 oturumlu hesap sayfası.
- Login/PWA ve ayrı mevcut offline fragment kontrolü.
- Kayıt kapalıyken home redirect kontrolü.
- Ek 10 lazy bileşen için `Livewire::withoutLazyLoading()` ile gerçek içerik render'ı.

Geçerli testing dil/ana sayfa/ayar ve gereken aktör izinleri hazırlanır. Mail/queue fake'dir. HTTP 200 + HTML ve istenmeyen gönderim/job olmaması sınanır. Offline legacy route mevcut `<h3>offline</h3>` fragment'ini verir; onu tam HTML sayfası varsaymak test hatasıdır. Aktif register ayarı olmadan 302 mevcut beklenen davranıştır.

**Sınır:** Normal HTTP koşusu lazy placeholder'ını geçerli sayar; yalnız belirtilen 10 ek senaryo gerçek component render'ını zorlar. Bunlar tarayıcı JS, dolu/büyük veriyle tüm tablolar, bütün detail/edit rotaları ve tüm rol matrisi kabulü değildir.

## 6. Dar koşu sonuçları

| Koşu | Sonuç |
|---|---|
| PanelCrudTest + SectionPersistenceTest | **15 geçti / 121 assertion / 4.95 saniye** |
| PanelModuleCrudTest + PageRenderTest + dört yeni bulgu, düzeltme öncesi | **82 geçti / 4 başarısız / 417 assertion / 29.43 saniye** |
| Onaylanan liste/düğme düzeltmeleri sonrası FrontendListFindingsTest | **5 geçti / 11 assertion / 1.37 saniye** |

Bu tur farklı dar koşularda toplam **102 test senaryosu** geçti: 15 CRUD/bölüm + 6 dil/rol/sezon + 76 sayfa + 5 liste/düğme. 102'sinin tek birleşik koşusu yapıldığı iddia edilmez. Önceki Excel/istatistik/auth/lig/mail/sınav kanıtları kendi kayıtlarında kalır; tüm suite tekrar koşulmadı.

Komutlar `docker compose exec -T -e APP_ENV=testing -e DB_DATABASE=testing laravel.test php artisan test <ilgili dosyalar> --no-ansi`; fixture sorunlarında yalnız ilgili metot/data set çalıştırıldı. Son sayfa koşusunun JUnit çıktısı container `/tmp/eduan-page-test-results.xml` geçici kanıtıdır; repoya/ana DB'ye aktarılmadı.

## 7. Ölçülmemiş adaylar ve sonraki test boşlukları

- ListLessons/ListTopics search koşulu cache key'de açık search bayrağı taşımaz; değişen filtre durumunun gerçekten doğru sonucu verdiği ayrıca sınanmalıdır. ListLessons arama OR gruplaması aktif kapsamıyla birlikte incelenmelidir. Bu adaylar bu tur değiştirilmedi.
- Cache TTL ifadeleri ve yorumlar tutarsız: lessons/topics 60×60×60×365 = 78.840.000 saniye; tests 60×60×365 = 1.314.000 saniye; exams 60×60×30 = 108.000 saniye. “Ay/yıl” yorumlarına göre karar verilmez; hedef TTL ve invalidation ayrı planlanır.
- Genel resetCache flush/clear ve saatlik optimize:clear planı cache sıcaklığı/ilk veri süresini etkileyebilir. Cron gerçek çalışması ve performans etkisi ölçülmedi, bugün değişmedi.
- Rappasoft'un yavaşlığın nedeni olduğu kanıtlanmadı; geçiş kararı kullanıcıya aittir. JSON/SQL/render/asset ölçümü `19` planında ayrı tutulur.
- İçerik/detail/edit sayfaları, tüm rol/öğretmen matrisi, gerçek tarayıcı filtre/sort/download/JS, PDF/Word, çok dilli SEO, gerçek queue/offline ve üretim şema yükü henüz tamamlanmadı.

Laravel yükseltmesine “bütün ürün test edildi” onayı verilmez. Bir sonraki test paketi bu açık alanlardan somut bir modül seçerek ilerler; ana DB/canlı şema değişimi SQL yedeği ve ayrı onay gerektirir.
