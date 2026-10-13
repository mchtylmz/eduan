# İnceleme bulguları için düzeltme ve test planı

**Durum:** 5 Ekim 2026; plan kullanıcıya gösterildi ve ilk dar paket uygulandı. Güncel değişiklik/test kaydı `11-ILK-DUZELTMELER-VE-SINAV-AKISI.md` içindedir. Canlı migration tablosu gelene kadar ana yerel/canlı şema değişikliği yapılmaz. Başlangıç Git sınırı `2b31f4f5`.

## Hazır olan test ortamı

Kullanıcı onayıyla ayrı MySQL `testing` veritabanının adı ve boşluğu `db:show` ile doğrulandı; yalnız orada 36 migration çalıştırıldı. Ana yerel DB'ye dokunulmadı. Var olan iki örnek test 2/2, iki assertion ile geçti; iş kurallarına kanıt oluşturmaz. Bundan sonraki test koşularında DB adının `testing` olduğu koruma ile doğrulanmalı ve gerçek mail/dış servisler fake edilmeli.

## İlk uygulama paketi: kesin ve dar bulgular

| Sıra | Somut değişiklik | Dar test / kabul ölçütü | Not |
|---|---|---|---|
| 1 | `public/systeminfo.php` kaldır. | Docker içinden `/systeminfo.php` artık 404; yerel ana sayfa erişimi bozulmaz. | Şu an yerel HTTP 200 olduğu doğrulandı; kod/konfigürasyon sızdırabilecek bilgi sayfası. |
| 2 | `Frontend/ExamController::start` doğrudan gelen misafiri giriş rotasına yönlendir; null kullanıcıya erişme. | Misafir, yetkisiz üye ve `tests:solve` yetkilisi için üç dar route testi. | Misafir davranışı kullanıcı tarafından doğrulandı. |
| 3 | `Livewire/Tests/TestForm` güncellemede yanlış `exams:update` iznini `tests:update` olarak düzelt; `save` eyleminde ayrıca sunucu tarafı izni uygula. `Livewire/Exams/ExamForm` için aynı eylem korumasını yakın kodla birlikte değerlendir. | `tests:update` var/`exams:update` yok, tersi ve yetkisiz doğrudan Livewire `save` vakaları. | Yalnız butonun `@can` ile gizlenmesi yetmez. |
| 4 | `LanguageTranslateForm` içindeki `mount` DB upsert, `updated` çeviri yazma ve `save` JSON üretme eylemlerine `languages:translate` iznini uygula. | `languages:view` yetkilisi yazamaz; `languages:translate` yetkilisi test dosyasında çalışır. | Dosya yazma yan etkisi testte geçici dosyaya yönlendirilir. |
| 5 | `nl` hedef varsayılanını yeni kurulum kaynağı ve mevcut ayar düzeyinde ayrı değerlendir. | `SetLocale` fallback ve `nl/en/tr` seçimi; mevcut kullanıcı session dilini istemeden bozmaz. | Kullanıcı `nl` kodu ve varsayılanı onayladı. Yerel/canlı ayar güncellemesi şema karşılaştırmasından ayrı veri işlemidir; önce tam etki planı gerekir. |

## Sınav için doğrulanan iş kuralı

"Tek hak" aynı kullanıcı/sınav için tek aktif denemedir; tamamlanmış denemeden sonra bütün sınavlar tekrar çözülebilir. Yeni deneme sıfır cevap ve sınavın tam süresiyle ayrı sonuç kaydı açar. Süre dolana kadar cevap değiştirilebilir veya kaldırılabilir. Bu akış uygulandı; aşağıdaki paragraf planın kapsamını kaydeder.

`Frontend/Exams/SolveForm` süre ve tek giriş kuralı, `CreateOrUpdateTestResult` ile kayıt ve `CreateOrUpdateTestResultDetail` ile puan hesabı birlikte ele alınacak. Kullanıcı kararı: süre dolmadan yarım sınava dönülebilir; seçilmiş cevaplar ve ilk girişte başlayan süreden kalan süre korunur. Süre dolunca denemeye devam edilemez; o ana kadar kaydedilmiş cevaplarla deneme tamamlanır. Sayfa kapalı olsa da süre anında arka plan işiyle tamamlanması hedeflenir. Bugün tarayıcı süre bitiminde `saveAndFinish` çağırıyor; metot yanıtları kaydetmeden `finished=true` yapıyor. Yeni akışta cevaplar seçim anında sunucuda kalıcı olmalı; son işlem istemciye bağımlı olmamalı. Testlerde aynı kullanıcı ikinci deneme, süre aşımı, bağlantı kopması/yeniden giriş, yanıtın sınava ait olmaması, sayfa kapalıyken tamamlanma ve bitmiş sonucu tekrar yazma vakaları kurulacak. Kod değişikliği bu testleri karşılayacak en küçük sunucu denetimiyle yapılacak. Arka plan işinin çalışması için canlı ortamda worker bulunduğu henüz doğrulanmadı. `Frontend/Tests/SolveForm` için başka soruya ait yanıt ve tekrar gönderim sınanacak; istemci durumuna güvenen akış kanıtlandığı ölçüde düzeltilecek.

## Ayrı karar gerektiren bulgular

- Giriş/kayıt/verify için hız sınırı ve doğrulama token politikası: trafik, proxy/IP, token ömrü ve kullanıcı deneyimi netleşmeden rastgele kural eklenmez.
- `SettingsController` ayar ve dosya doğrulaması: hangi alanların yöneticilere açık olduğu ve MIME/içerik sözleşmesi çıkarılır; eylem bazlı testlerden sonra düzeltilir.
- Sitemap/canonical/çok dilli URL, PWA scope/cache, cookie consent, cache ve tablo performansı: mevcut işlev ve ölçümle küçük ayrı işler halinde ele alınır. İlk pakete SEO/PWA refactor'u veya kütüphane değişimi eklenmez.
- Canlı migration tablosu geldiğinde yalnız kayıt, gerçek kolon/indeks ve kod kullanım haritası karşılaştırılır. Yeni migration, rename veya veri taşıma için ayrı plan/onay gerekir.

## Test yazma sırası

1. Test veritabanı koruması ve en yakın feature/Livewire test fixture'ları: yalnız gerekli tablo satırları/factory; `DatabaseSeeder`ı tekrar çalıştırma.
2. Önce mevcut durumu gösteren dar test; hatalı/eksik korumayı doğrulayan negatif test; sonra en küçük düzeltme ve aynı testin tekrar koşması.
3. Güvenlik/iş kuralı akışında başarılı, yetkisiz, hatalı veri ve sınır durumlarını kapsa. Yalnız ilgili dosyaları çalıştır; tüm suite/build için ayrı gerekçe aranır.
4. Testler geçse bile tarayıcı/üretim davranışı kanıtlanmış sayılmaz. Gerekli tek ekran akışı Docker içi HTTP veya uygun tarayıcıyla ayrıca doğrulanır.

Mevcut projenin test tabanı eşitlenirken tek bir dev suite yerine alan bazlı küçük paketler açılacak: kimlik/kayıt/rol; ders–konu–soru/cevap; test/favori/sonuç; süreli sınav/bölüm/puan; öğretmen–öğrenci/hesap erişimi; dil/çeviri/içerik; ayar/dosya; lig/sezon; export ve salt okunur public sayfalar. Her pakette önce kritik bir başarılı akış ve erişim sınırı, sonra yalnız görülen riskin negatif/sınır vakaları yazılacak. Mail, yedek, Cloudflare ve AI gerçek dış servise gönderilmeden fake edilir. SEO/PWA'nın tarayıcıya bağımlı kısmı PHP testiyle kanıtlanmış sayılmaz.

## Açık sorular

1. Canlı ortamda sürekli çalışan Laravel queue worker var mı? Yoksa bunun kurulumu bu fazda mı yapılacak? Gecikmeli işin gerçek zamanda çalışması bu altyapıya bağlıdır.
2. Canlı migration listesi ve gerçek şema ne zaman paylaşılacak? O gelene dek şema kararları beklemede.

Lig hesabına yalnız tamamlanmış sınav denemelerinin katılması onaylandı; `CalculateLeagueResult` için filtre ve dar regresyon testi eklendi.
