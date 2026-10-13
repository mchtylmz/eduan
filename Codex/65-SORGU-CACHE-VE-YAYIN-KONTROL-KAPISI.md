# 65 — Sorgu/cache ve yayın kontrol kapısı

**Sonraki güncel kayıt:** [69 — Son testler ve canlı aktarım listesi](69-SON-TESTLER-VE-CANLI-AKTARIM-LISTESI.md). Mevcut kod fazları tamamlandı; AI canlı aktivasyonunda Luna/low/iki v3 prompt (yalnız dört global ayar) ayrıca uygulanacak. Anahtar, günlük limit ve kayıtlı cevaplar korunur. Genel test sonucu ve açık yayın kapısı69 içindedir; bu önceki fazın dar sonuçları güncel genel kabul yerine kullanılmaz.

7 Ekim 2026. Kullanıcı önce sıradaki fazın uygulanmasını, genel test tekrarının sonra yapılmasını istedi. Bu faz mevcut akışları düzeltir; genel suite veya canlıya hazır kabulü değildir.

## 1. Uygulananlar

1. **Çözülen test kartları:** kart başına `examResultStats()` ve gruplanmış `userResults` yükleme kaldırıldı. Kişisel bütün denemeler SQL alt sorgusunda toplanır. Doğru/yanlış değerleri önceki helper gibi cevap satırlarından hesaplanır; eski sonuç sayaçları kullanılmaz. Yarım denemeler korunur, boş cevap doğru/yanlış sayılmaz. Aktif/soft-delete kapsamı, dil eager loading, 21 kart ve lazy korunur. Count/sayfa/dil için **3 sorgu**; dört kartlık fixture önceki yoldan en az dört sorgu daha az kullanır.
2. **Hiç çözülmeyen testler:** bütün kişisel sonuç kimliklerini PHP'ye pluck etmek yerine SQL `NOT IN (SELECT exam_id…)` kullanılır. Başka kullanıcının çözmesi bu listeyi etkilemez.
3. **Kişisel geçmiş:** test çözümleri ve hesap sınav sonuçları **5 deneme/sayfa**. Mevcut beş sütunlu sekme genişliği, ID artan sırası, testte yarım/tam ve sınavda yalnız tamamlanmış kişisel denemeler korunur. İkinci sayfada sonuç numarası devam eder; dil query parametresi taşınır. Statik pagination önceki sayfa bağlantısındaki nextPageUrl yanlışı previousPageUrl/prev ve doğru erişilebilirlik metniyle düzeltildi.
4. **Sınav cevap geçmişi:** bölümler/alt bölümler/meta bir kez, cevapların soru/yanıt ilişkileri topluca yüklenir. Blade içindeki bölüm/helper/soru sorguları kaldırıldı. Zoha'nın gerçekten kullandığı `metarelation` eager yüklenir. Accordion ID'leri sonuç kimliğine özeldir. Güncel bölüm içeriği ve önceki cevap eşleştirme kuralı korunur.
5. **İstatistik:** ders/konu toplamındaki gösterilmeyen ve gruplanmayan `DATE(updated_at)` kaldırıldı. Ad/kimlik birlikte gruplanır, kullanıcı kapsamı bağlı SQL alt sorgusudur. Günlük tarih/dönem/sıralama/hesaplama değişmedi. `ONLY_FULL_GROUP_BY` altında doğrulandı; uygulamanın strict=false ayarı değiştirilmedi.
6. **Cache:** resetCache default veri cache'ini bir kez flush eder; yinelenen clear ve her kayıtta optimize:clear kaldırıldı. Saatlik scheduler aynı site saat diliminde **her saat :15'te cache:clear** kullanır. Eski veri temizliği korunur; derlenmiş config/route/view/event cache'i her kayıt ve saatte silinmez. Bunlar yayın sırasında yönetilmelidir. Scheduler komutu bu tur yürütülmedi.

Ayrı 7 günlük frontend, saatlik dashboard ve güvenlik store'ları/hedefli invalidation korunur. Yeni kişisel cache yok. Legacy DataHelper/FilterHelper, bölüm helper'ı ve lig anahtarları default store kullandığından geniş veri flush/saatlik veri temizliği kaldırılmadı. Tam hedefli legacy invalidation ayrı adımdır.

## 2. Dar kanıt

- QueryCacheReleaseTest ilk koşu **5 test/49 doğrulama başarılı**: kapsam/yarım deneme/gerçek cevap toplamı/sabit sorgu, SQL dışlama, iki geçmişin ikinci sayfası ve numarası, eager loading, benzersiz accordion, strict grouping.
- CacheCandidateTest yalnız global reset senaryosu **1 test/8 doğrulama başarılı**: default array temizlenir; public/security store korunur; Artisan çağrılmaz.
- Ek scheduler senaryosu son dar tekrar **1 test/5 doğrulama başarılı**: aynı saat/zaman dilimi, cache:clear, optimize:clear yokluğu. İlk iki deneme ortak Artisan taklidi ve başlangıç callback kaydı nedeniyle test kurulumu hatası verdi. Gerçek Kernel ve yalnız komut tanımlarını listeleyen all() ile giderildi; hiçbir komut/job yürütülmedi.
- **7 farklı dar senaryo/62 doğrulama**, ayrı ilgili koşular; tek genel suite değildir. PHP8.5.7/PHPUnit13.4.1, yalnız testing DB/transaction rollback. Strict SQL mode yalnız testing bağlantısında geçici ve finally ile geri alınır.
- İlgili PHP Pint/scoped whitespace başarılı. Blade HTTP/Livewire render ile derlendi. Genel suite/build/yeni browser/cihaz kontrolü yapılmadı; kullanıcı genel test tekrarını sonraki adıma bıraktı.
- İlk Pint çağrısındaki --dirty verilen yolların dışını da biçimlendirdi. Önceki kaynak referanslarıyla biçim farkları geri daraltıldı, PHP8.5 nullable imzalar korunup AST karşılaştırması yapıldı. Kapsam dışındaki kodda işlev farkı bırakılmadı; kalan salt biçim farkları yeni geliştirme değildir. Toparlama nedeniyle etkilenen **79 PHP dosyasında syntax hatası olmadığı** ayrıca kontrol edildi; bu geniş syntax kontrolü işlev testi sayılmaz.

## 3. Kalanlar ve yayın kapısı

**Asset değerlendirmesi:** ortak main.js Swiper/popup/select/menü eklentilerini doğrudan çağırır; editör içerikleri farklı sayfalarda HTML/KaTeX içerebilir. Route adına göre plugin/KaTeX kapatma uygulanmadı. Önce kullanım haritası ve ortak JS başlangıçlarının ayrılması gerekir. Canlı HTTP/Core Web Vitals ölçümü yok.

Sonraki sıra:

1. Kullanıcının istediği genel PHP/PWA test tekrarını tek kayıt altında çalıştır; hataları ilgili akışlara daralt. Önceki farklı dar koşuları tek suite başarısı gibi sunma.
2. Testing browser: login/kayıt, profil/kendi sonuçları, test/sınav çözme-devam-tamamlama, panel izinleri, PWA update/offline. Gerçek Android/iOS/Safari/canlı worker ayrıca kabul edilir.
3. Yayından önce canlı web ve CLI **PHP8.5** binary/modül, queue/cron ve süre dolan sınav job'u doğrulanmalı. Kullanıcı yalnız bağımsız backup scriptini canlıya aldı; uygulama kodu henüz deploy edilmedi.
   - **PHP 8.5 projesinin canlıya alınacağı gün:** kullanıcıya `scripts/siteground-cli-cron.example` hatırlatılacak; mevcut scheduler ve queue worker PHP çağrıları `/usr/bin/php85 -d extension=imagick.so` olarak güncellenecek. Mevcut zamanlama, worker seçenekleri ve yönlendirmeler korunacak; kopya görev oluşturulmayacak. Bu adım şimdi uygulanmaz.
4. Canlı migration/izin envanterini salt okunur karşılaştır. İndeks migration'ı, translation_sources ve users:send-email gibi bekleyen tanımları somut etki/açık onayla uygula; otomatik toplu seed/migrate yok.
5. Onaylı yayında config/route/view/data cache ve kritik HTTP kontrolleri. Bu faz CSS/JS/ikon değiştirmedi; APP_VERSION/worker **1.1.19** korunur.
6. Eski expired/cevapsız/orphan/soru sayacı farkları kullanıcı kararı olmadan silinmez, tamamlanmaz veya yeniden hesaplanmaz.

Kod fazı tamam; genel test/browser ve yayın kabulü bekler. Yeni özellikten önce kapı sonuçları değerlendirilir. Ana/canlı DB, migration, Composer, Docker servisleri, dış servis veya deployment işlemi yok.

## 4. İşlevsel dosyalar

- app/Livewire/Frontend/Account/SolvedTests.php ve StatsTable.php
- app/Http/Controllers/Frontend/TestController.php ve AccountController.php
- app/Helpers/Functions.php; bootstrap/app.php
- resources/views/livewire/frontend/account/solved-tests.blade.php
- resources/views/frontend/tests/solutions.blade.php ve frontend/account/result.blade.php
- resources/views/components/frontend/pagination.blade.php
- tests/Feature/QueryCacheReleaseTest.php ve CacheCandidateTest.php
- Bu rapor, 63, AGENTS.md, standartlar16 ve index.
