# Test stratejisi ve sonraki fazlar

## Bugünkü test zemini

İlk dar düzeltme paketinde `AccessBoundaryTest`, `DefaultLocaleTest` ve `TimedExamTest` eklendi. Aşağıdaki iki örnek test anlatımı ilk tarama tabanını kaydeder; artık tüm test envanteri değildir. Güncel kapsama ve komutlar `11-ILK-DUZELTMELER-VE-SINAV-AKISI.md` içindedir. Yeni testlerde DB adı `testing` değilse işlem başlamadan hata verilir; yazan testler transaction ile geri alınır. Çeviri dosyası üretimi fake Filesystem ile sınanır; gerçek dil JSON'u testte yazılmaz.

`tests/TestCase.php` boş temel sınıf, `tests/Feature/ExampleTest.php` yalnız `/` için 200 bekliyor, `tests/Unit/ExampleTest.php` yalnız `true` kontrol ediyor. Bu, kayıt, yetki, test/sınav, çeviri, SQL, PWA veya SEO için kapsama kanıtı değildir. `database/factories` ve seeder'lar veri kurmaya yardımcı olabilir; gerçek veritabanında `DatabaseSeeder` çalıştırılmamalı. `phpunit.xml` ayrı `testing` DB adı tanımlıyor fakat bunun gerçekten yalıtılmış olduğunu testten önce teyit etmek gerekir.

5 Ekim 2026'da kullanıcı onayıyla ayrı MySQL `testing` veritabanının adı/sıfır tablosu doğrulandı ve yalnız bu DB'ye migration uygulandı. Var olan iki örnek test 2/2 geçti. Bu ilk doğrulama sonraki komutlarda DB hedefini kontrol etme zorunluluğunu kaldırmaz.

## Test edilebilirlik engelleri / adayları

| Kod noktası | Neden zor | İlk test yaklaşımı |
|---|---|---|
| `Frontend/Tests/SolveForm` | Soru/cevap ve zaman public Livewire state içinde; DB yazımı eylemde. | Sabit soru/cevap fixture'ı, kullanıcı, yanlış/başka soruya ait cevap, tekrar gönderim. |
| `Frontend/Exams/SolveForm` | Bölüm ağacı, kalıcı cevap ve queue tamamlama etkileşimi. | İlk pakette yeniden giriş, süre aşımı, bitmiş sonuç, puanlama, sahiplik ve bölüm/soru eşleştirmesi testleri eklendi; gerçek worker/tarayıcı ayrıca doğrulanacak. |
| `CreateOrUpdateTestResultDetail` | Toplu upsert ve MySQL raw alt sorguları. | Yalıtılmış MySQL test DB üzerinde puan, boş cevap, yinelenen kaydı doğrula. |
| `LoginAction` ve kayıt | Oturum, log, e-posta ve varsayılan rol. | Mail fake, rate/sahiplik kontrolleri, pasif kullanıcı, oturum yenileme. |
| `TranslationHelper` ve ayar | DB + JSON dosyası + cache ve PWA ikon yan etkisi. | Geçici dosya/disk, test DB, yerel dillerin korunması; gerçek dosyayı yazma. |
| `DataHelper` / istatistik / tablolar | Uzun cache ve kullanıcı/öğretmen kapsamı. | Dar sorgu/sayfalama testi, öğretmen başka kullanıcı verisini göremez testi. |
| SEO/PWA | Tarayıcı ve HTTP kaynak çıktısı gerekir. | İlgili rota için HTML/sitemap doğrulaması, sonra yerel tarayıcı ve service worker testi. |

Bu tablo bir uygulama yetkisi değil, test hazırlık haritasıdır. Her özellik değişiminden sonra en dar anlamlı başarılı/hatalı/yetkisiz/sınır testleri çalıştırılır. Her seferinde tüm proje suite'i, asset build veya uzun performans taraması yapılmaz. Test düzeni değiştirilecekse önce veri izolasyonu ve dış servis fake'leri kesinleştirilir.

## Önerilen faz kapıları — sıralama kullanıcıyla kesinleştirilecek

1. **Tamamlanan ilk inceleme:** kod ve paket envanteri, ilk rehber, soru listesi. Bu tarama başlangıçta salt okunurdu; ardından plan gösterilip kullanıcı kararlarıyla ilk dar düzeltme/test paketi uygulandı.
2. **Güvenli test zemini ve şema keşfi:** test DB yalıtımı, kritik akışların küçük testleri, yerel/üretim şema farkının salt okunur çıkarılması. Eksik migration için ayrı veri taşıma planı ve onay.
3. **Laravel yükseltme planı ve uygulaması:** mevcut gerçek sürümden hedef sürüme bağımlılık uyumu, breaking change, tek tek paket etkisi ve dar regresyon testleri. Hedef sürüm kullanıcıyla belirlenir; kör `composer update` yapılmaz.
4. **Yükseltme kaynaklı hatalar:** kanıtlanan hataları kapsamına göre düzelt, yeni işlere karıştırma.
5. **Yeni geliştirme:** kullanıcı gereksinimi ve izin matrisi netleştirilince mevcut stile uygun uygula.
6. **Ölçüme dayalı optimizasyon:** önce ön yüz hız/SEO/stabilite; sonra panel tabloları, sorgular/cache; en son paket sadeleştirme. Paket kaldırma veya tablo teknolojisi değiştirme önce kıyas ve onay gerektirir.

Kullanıcı bazı işlerin farklı sırada yapılmasını isteyebilir. Bu dosya karar kaydı değil, sonraki konuşma için başlangıç taslağıdır.
