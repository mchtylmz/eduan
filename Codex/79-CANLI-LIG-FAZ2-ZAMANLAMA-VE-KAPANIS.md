# 79 — Canlı lig Faz 2: zamanlama, kapanış ve yeniden hesaplama

25 Ekim 2026. Faz1 yerel kabul commit'i `96cffdcf` sonrasında kullanıcı sıradaki fazı onayladı. Bu faz backend zamanlama/kapanış işidir; UI ve ön yüz cache düzeni Faz3'tedir.

## 1. Görev ve kapsam

Yeni `leagues:refresh-live` komutu `refresh_live_leagues` adıyla site saat diliminde **00/04/08/12/16/20** çalışacak şekilde kaydedilir (`0 */4 * * *`). Yeni ve eski lig görevlerinde `withoutOverlapping(240)` bulunur. Laravel scheduler kayıtları testte yalnız okunur; gerçek `schedule:run` çalıştırılmadı. Sunucudaki cron/worker değiştirilmedi.

Komut yalnız başlangıcı gelmiş `is_live=1 / closed_at=null` liglerin kimliklerini 50'şer alır. Varsayılan id-desc global scope kaldırılarak chunkById'nin ileri taraması korunur. Gelecek tarihli ligler bekler; eski tek-seferlik ligler ve kapanmış canlı ligler yeni görevden çıkartılır. Gecikmiş fakat kapanmamış bütün uygun ligler taranır; kaçırılmış her saat için ayrı hesaplama yapılmaz.

## 2. Ortak hesaplamada kapanış

`CalculateLeague` scheduled çağrıda mevcut bir lig kimliği ister, yeni lig oluşturmaz. Aynı DB/aralık GET_LOCK ve transaction içindeki parent satır kilidi altında adayın canlı/açık/başlamış olması yeniden doğrulanır. Tarama ile işlem arasındaki kapanışta stale çağrı atlanır.

Başarılı son hesaplama yalnız **hesaplanan veri üst sınırı dönem sonuna ulaştıysa ve dönem gerçekten bittiyse** `closed_at` kaydeder. `calculated_through_at` dönem sonu, `calculated_at` gerçek başarı anı, `closed_at` ilk başarılı kapanış anıdır. Üçü sonuçlarla aynı transaction'dadır. Örneğin 1Kasım23:59 bitişini 2Kasım04:00'te işleyen görevde veri sınırı1Kasım23:59:59, işlem/kapanış zamanı2Kasım04:00 olur.

Gece yarısından önce başlayan hesaplama gece yarısından sonra biterse, eski kısmi cutoff ile kapanış yapılmaz; sonraki görev dönem sonuna kadar hesaplar. Son saniye23:59:59'da henüz kapatılmaz; sonraki gün00:00'da kapanabilir. Bu korumalar gerçek SQL/test saatiyle sınandı.

`updated_at` tarih filtresi ve puan/RANK sorgusu aynıdır. Sonradan dönem dışına taşınan cevap, elle yeniden hesaplamada da dönem dışında kalır. Gecikmiş hesaplama geçmiş anı yeniden oluşturan cevap arşivi değildir.

## 3. Çakışma ve hata davranışı

`LeagueCalculationInProgress` beklenen kilit çakışmasını gerçek hatadan ayırır. Yeni görevde kilitli veya taramadan sonra silinmiş kayıt atlanır; diğer ligler devam eder. Kilit çakışması error logu veya başarısız exit üretmez. Scheduler cache kilidi cache:clear'dan etkilenebilse de dönem hesabı MySQL GET_LOCK ile korunur.

Bir ligde beklenmeyen hata olduğunda son başarılı sonuçlar ve kapanış bilgisi rollback ile korunur. Log yalnız league_id ve exception sınıfıdır; kaynak içerik/SQL/ayar/anahtar/hata mesajı eklenmez. Diğer ligler işlenir, komut gerçek hata varsa exit1 döner. Bir sonraki dört saatlik görev kapanmamış kaydı yeniden dener. Pasif sistemde iki lig komutu da normal skip/exit0; tekrar aktifte gecikmiş kapanış işlenir.

## 4. Eski haftalık görev

`app:run-calculate-league-result` ve mevcut ayarlı gün/saat/ofset aralığı korunur. Gün/saat kontrolü de tarih üretimi gibi site saat dilimini kullanır. Henüz zamanı gelmeyen çağrı hata sayılmaz; exit0 ile çıkar. Gerçek hesaplama kilitliyse aynı şekilde skip eder.

Eski görev tam aynı kaynak/başlangıç/bitişte canlı lig bulursa ortak hesaplama o kaydı, sabit kurallarıyla günceller ve gerekiyorsa kapatır. Yeni kayıt veya yeni puan ayarıyla ikinci sonuç oluşmaz. Gerçek eski komut üzerinden test edildi.

**Aralık sınırı:** Eski otomatik görevin konfigüre edilmiş saat/ofset ve dakika-sonu hesabı yeniden yorumlanmadı (örnek:17Ekim00:00:00–24Ekim00:00:59). Yeni Cumartesi–Cuma yedi günlük varsayılan form bağlantısı Faz3'tedir. Sadece birebir eşit kaynak/aralık aynı ligdir; farklı ama çakışan aralıklar otomatik birleştirilmez. Eski kayıt tarihleri değiştirilmez.

## 5. Kapalı ligi elle yeniden hesaplama altyapısı

Yeni `RecalculateClosedLeague` Action'ı dashboard:access + leagues:view + leagues:run izinlerini her çağrıda denetler. Giriş yok/izin eksik403; açık canlı veya tek-seferlik kayıt409. Kimlikten gerçek lig okunur, kayıtlı kaynak/puan/kapasiteyle yeniden hesaplanır. `closed_at` korunur, canlı otomatik takip yeniden açılmaz. Görünür düğme ve kullanıcı mesajları Faz3'tedir; bu faz yeni route/izin eklemez.

## 6. Testler

İlk birleşik Faz1+2 koşusu **72 senaryo/279 assertion:71başarılı,1fixture hatası**. Manuel yeniden hesaplama fixture'ında eski updated_at orijinaliyle aynı olduğu için Eloquent onu dirty saymayıp bugüne taşıdı. Cevap haklı olarak dönem dışında kaldı. Ürün sorgusu değiştirilmedi; geriye tarihli temsilî fixture yalnız testte `withoutTimestamps` ile saklandı. Ayrıca normal güncellemenin cevabı dönem dışına taşımasını koruyan ayrı test eklendi.

Son dar koşu **13 test/132 assertion başarılı**:

- Düzeltilen kapalı lig/sabit puan manuel yeniden hesaplama;
- Normal güncellemede dönem dışına çıkan cevabın dışlanması;
- Eski haftalık görevin iki DB bağlantısındaki kilidi normal skip etmesi, bırakıldıktan sonra kapanış;
- İlgili iki mevcut izin/validation, bir panel kapsamı, beş ön yüz ve iki mevcut geçmiş/cache temizleme regresyonu.

Toplam **84 farklı ilgili senaryonun başarı kanıtı** vardır:31saf,5eski hesaplama,16Faz1DB,21yeniFaz2,1paket/scheduler,2izin,1panel,5ön yüz,2geçmiş/cache. Tek son temiz84koşu iddiası yok; yalnız hata/eklenen somut sınırlar ve ilgili regresyonlar tekrarlandı. Tüm proje suite/build yok.

Yeni21 Faz2 testi şunları kapsar: altı slot/site timezone/overlap; açık/gelecek/boş/gecikmiş/kapalı lig; tam bitiş saniyesi; hesaplama sırasında gün değişimi; tek lig hatasında diğerinin devamı; son metadata yazımı sonrası rollback ve sonraki görevde toparlanma; gerçek ikinci MySQL bağlantısı ile busy skip; pasif/aktif toparlanma; eski komutun gün/saat/ofseti ve saat dilimi; aynı canlı ligle gerçek eski komut bütünleşmesi; yetkili/izinsiz/misafir/açık/tek-seferlik yeniden hesaplama; yanlış scheduled çağrı;51ligle chunk sınırı.

Değişen8 PHP dosyasında syntax, hedefli Pint ve whitespace kontrolü başarılı. Kernel/scheduler kayıt testleri diğer görevleri çalıştırmadı; AI/push/mail isteği yok. Fiziksel sunucu process-kill/cron sürekliliği veya production yük kabulü değildir.

## 7. Veri ve ortam sınırı

Mevcut Eduan container'ları açık kaldı; servis/port/volume işlemi yapılmadı. Yeni migration yok; Faz1'in onaylı şeması kullanıldı. Testler yalnız ayrı testing DB ve DatabaseTransactions ile çalıştı.

Ana35lig/1238sonuç ve testing başlangıç lig/sonuç satırlarının SHA özetleri, kaynak cevap/dil/çeviri sayıları önceki karşılaştırmayla aynı. Kalıcı canlı lig/snapshot/cutoff/closed metadata iki DB'de0; bütün fixture'lar rollback. Ana DB'de yeni komut/gerçek lig hesabı, .env/ayar değişikliği, paidAPI/SMTP/push veya canlı deploy yapılmadı. UI/CSS/JS değişmediği için asset sürümü korunur.

## 8. Dosyalar ve sonraki faz

- `app/Console/Commands/RefreshLiveLeagues.php` yeni;
- `app/Console/Commands/RunCalculateLeagueResult.php`;
- `app/Actions/Leagues/CalculateLeague.php`;
- `app/Actions/Leagues/RecalculateClosedLeague.php` yeni;
- `app/Exceptions/LeagueCalculationInProgress.php` yeni;
- `bootstrap/app.php`;
- `tests/Feature/LiveLeagueSchedulingTest.php` yeni21senaryo;
- `tests/Feature/PackageRemovalTest.php` yeni görev beklentisi;
- AGENTS.md, README.md, standartlar16, bu79 raporu ve Codex/index.html.

**Faz2 yerel backend kabulü tamamlandı.** Sıradaki Faz3: sağ formda canlı seçim ve yeni varsayılan tarih, Bootstrap Table durum/zaman alanları ve Yeniden hesapla düğmesi, ön yüz durum/veri zamanı, hedefli cache yenileme, çeviriler ve mobil/masaüstü tarayıcı kabulü. Böylece yeni özellik kullanıcı akışında tamamlanacak. Önceki .DS_Store, wiki yeniden adlandırması ve OneSignal klasörü bu faz commit'ine alınmaz.
