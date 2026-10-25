# 78 — Canlı lig Faz 1: veri ve hesaplama hazırlığı

25 Ekim 2026. Kullanıcı geliştirmeyi üç fazda, her faz sonunda ilgili kapsamlı test ve ayrı commit ile istedi. Bu kayıt **kod hazırlığıdır; Faz 1 DB kabulü değildir**.

## 1. Kesin kurallar ve faz sırası

- Kayıtlı liglerin başlangıç/bitiş tarihleri, altı/sekiz/on gün olsalar da değişmez.
- Yeni form varsayılanı içinde bulunulan Cumartesi 00:00–Cuma 23:59; yedi takvim günü. Tarih ve saat serbest seçilir. Bu tur saf takvim yardımcısı hazır; form bağlantısı Faz 3'tedir.
- Canlı ligde kaynak (`label`), doğru/yanlış puanı ve kapasite oluşturulurken sabitlenir; yeni ayarlar sonraki liglere uygulanır.
- Eski haftalık görev aynı kaynak ve aralıkta canlı lig bulursa aynı kaydı, sabit kurallarıyla hesaplar. Canlı kaydı atlama veya ikinci lig yaratma yok.
- Dört saatlik yeni görev site saat diliminde 00/04/08/12/16/20 çalışacak; görev/kapanış Faz 2'dir.
- Kapanmış canlı lig ayrıca yeniden hesaplanabilir; takip yeniden açılmaz. Yetkili düğme Faz 3, kapanış/çağrı yönetimi Faz 2'dir.
- Ön yüz kendiliğinden yenilenmez. Veri sınırı ve gerçek başarılı işlem zamanı ayrı gösterilir; cache/görünüm/çeviri Faz 3'tür.
- Faz 1 veri/ortak hesaplama; Faz 2 scheduler/kapanış; Faz 3 standart panel/ön yüz/cache/çeviri ve birleşik lig kabulü.

## 2. Faz 1 kaynakları

- `LeaguePeriod`: serbest aralık, mevcut dakika başlangıcı/son saniye normalizasyonu, Cumartesi–Cuma varsayılanı, canlı veri üst sınırı.
- `LeagueRules`: yalnız iki izinli sonuç kaynağı, mevcut ayar formunun sayı sınırları ve eksiksiz sabit ayar doğrulaması. Bozuk snapshot güncel ayarlara sessizce düşmez.
- `CalculateLeague`: mevcut SQL puan/doğru/yanlış/süre/RANK sorgusunun ortak Action'a taşınması. Test/sınav ters model eşlemesi, tekrar/yarım deneme ve cevap `updated_at` filtresi korunur.
- Eski `CalculateLeagueResult` iki parametreli çağrı sözleşmesiyle Action'a bağlanır. Scheduler, form ve route bu tur değişmez.
- `League` dört yeni alanı ve hesaplama tarihlerini cast/fillable ile tanır.
- Kaynak/başlangıç/bitiş kimliği sabit; yeniden hesaplama kodu ve URL'yi değiştirmez.
- Açık canlı ligde hesaplanan üst sınır o andır, geç çalıştırmada dönem sonunu aşmaz. Bu, geçmiş anı yeniden oluşturan bir cevap geçmişi değildir.

## 3. Atomiklik ve çakışma

MySQL `GET_LOCK` veritabanı+kaynak+aralık anahtarında kullanılır; henüz lig satırı olmayan ilk oluşturma da korunur. Kilit `cache:clear` ile silinmez ve bağlantı kapanınca MySQL tarafından bırakılır. Aynı bağlantıdaki transaction içinde parent `FOR UPDATE`, sonuç upsert ve hesaplama tarihleri birlikte yazılır; hata rollback yapar. `finally` kilidi bırakır. Ayrı DB'lerin aynı tarihleri birbirini kilitlemez.

Canlı hesaplama güncel sorgudan düşmüş eski katılımcı satırlarını **yalnız o ligin üretilmiş `league_results` verisinden** çıkarır; kullanıcı/cevap kaynağına dokunmaz. Tek seferlik hesaplamanın boş veri halinde lig oluşturmama davranışı korunur. İleri tarihli canlı lig boş kaydedilebilir, hesaplama zamanı varmış gibi gösterilmez. Yeni canlı kayıt isteği aynı aralıkta eski tek-seferlik kaydı otomatik dönüştürmez.

Canlı metadata yazımı `saveQuietly` ile genel legacy cache flush üretmez; oturumlu aktör varsa mevcut Loggable formatında açık kayıt yapılır. Admin tablo revision'ları başarıdan sonra yenilenir. Ön yüz cache tasarımı Faz 3'te tamamlanacak; bu kod tek başına görünür canlı sonuç kabulü değildir. Tek seferlik kayıtların mevcut model olayları korunur.

## 4. Migration — uygulanmadı

`2026_10_25_000001_add_live_calculation_fields_to_leagues.php` yalnız `leagues` tablosuna şunları ekler:

- `is_live` boolean/default false;
- `calculation_settings` nullable JSON;
- `calculated_through_at` nullable timestamp;
- `closed_at` nullable timestamp;
- açık canlı lig seçimi için `(is_live, closed_at)` indeksi.

DML/backfill/veri silme yok; eski tarih/puan/kodlar değiştirilmez. Eski ligler canlıya çevrilmez. `down` yeni alanları kaldıracağı için uygulamak ayrıca veri kaybı/onay gerektirir; bu tur çalıştırılmadı. Migration ana/testing DB'de uygulanmadı ve uygulama onayı istenmedi; ortam açıldığında ayrı somut onay kapısıdır. Canlı özelliği yayınlanmadan önce bu migration zorunludur.

## 5. Ortam kararı

Docker salt okunur kontrolde Lara Football laravel.test/mysql/redis 80/3306/6379'da çalışıyor; Eduan'ın iki mevcut container'ı durmuş. Kullanıcı **servis değişimine hayır, DB testleri beklesin, kod hazırlığı devam etsin** dedi. Stop/start/up/down/build, alternatif port, volume veya DB bağlantısı yapılmadı. Laravel uygulaması/ana ayarlar açılmadı; tarayıcı testi bekliyor.

## 6. Yapılan doğrulama

Host PHP **8.5.7**, PHPUnit **13.4.1**; Docker kullanılmadı.

`php vendor/bin/phpunit tests/Unit/LeaguePeriodTest.php tests/Unit/LeagueRulesTest.php --no-progress`

**31 test / 71 assertion başarılı.** Yedi günün tamamı, sonraki Cumartesi, Belçika yaz/kış saati, yıl/artık gün, serbest tarih-saat, önceki job dakika normalizasyonu, gelecek/şimdi/gecikmiş üst sınır, kilit kimliği, ters tarih, kaynak eşlemesi, sayı sınırları, bozuk snapshot ve signed yanlış puanı kapsandı. Bunlar DB/kilit/Livewire başarısı değildir.

Değişen dokuz PHP kaynak/test/migration dosyasında syntax ve hedefli Pint kontrolü yapıldı. SQL puan ve RANK yapısı kaynak karşılaştırmasında korundu; gerçek SQL yürütme kanıtı bekliyor.

## 7. Hazır fakat çalıştırılmamış DB kabulü

Yeni `LiveLeagueCalculationTest`: **14 senaryo**; aynı kimlik/sabit kurallar, eski giriş noktasının canlı kaydı güncellemesi, kaynak değişiminde ID ile sabit kaynak, gelecek/boş lig, yalnız ilgili ligin eski satırlarının çıkması, anlık/bitiş cutoff, tarih/kapalı işaretinin korunması, eski kaydı dönüştürmeme, bozuk snapshot, upsert sonrası hata/rollback/kilit bırakma, iki bağlantıda aynı dönem kilidi pasif sistem ve eksik tarihli eski kayıt reddi.

Ortam ve migration onayı hazır olduğunda yeni14 + mevcut `LeagueCalculationTest`5 senaryosu çalıştırılmalı; ardından ilgili mevcut lig izin/validation senaryoları. Servis/DB hazır olmadığı için **başarılı sayılmadılar**. Faz 1 tamamlandı veya yayın hazır denmez. Faz 2'ye geçmeden bu kapı kapatılır; kullanıcı ayrı hazırlık isterse sınır yeniden belirtilir.

## 8. Değişen dosyalar ve commit sınırı

- `app/Actions/Leagues/CalculateLeague.php`
- `app/Helpers/LeaguePeriod.php`, `app/Helpers/LeagueRules.php`
- `app/Jobs/CalculateLeagueResult.php`, `app/Models/League.php`
- yeni tek migration;
- `tests/Unit/LeaguePeriodTest.php`, `tests/Unit/LeagueRulesTest.php`, `tests/Feature/LiveLeagueCalculationTest.php`;
- bu rapor, AGENTS, README, standartlar16 ve Codex/index.html.

Hazırlık commit'i yalnız bu dosyalardır. Önceden var olan `.DS_Store`, Doge.html→docs.html yeniden adlandırması ve yüklenmiş OneSignal klasörü dışarıda bırakılır. Yeni CSS/JS/asset sürümü, ana DB, ücretli API, push/mail veya canlı deploy yok.
