# Çeviri tarayıcısı ve kaynak yönetimi — 7 Ekim 2026

## 1. Faz ve kullanıcı kararları

Faz2 uygulandı. Kullanıcı yalnız yeni `translation_sources` tablosunun ana yerel ve ayrı testing DB'ye eklenmesini açıkça onayladı. İkinci yanıtla **mevcut dil kayıtlarıyla devam** seçildi: İngilizce language kaydı eklenmedi, Fransızca etkinleştirilmedi; nl/tr/fr mevcut kayıtları korunur. Kaynak orijinali Türkçe PHP dosyalarından, cümle anahtarlarında doğrudan çağrıdan alınır; Türkçe grup karşılığı bulunmayan anahtarlar mevcut başka dil tanımıyla gösterilebilir. Otomatik/dış servisle çeviri yapılmaz.

## 2. Kaynak taraması

`TranslationScanner`: app/config/routes/resources/views PHP ve Blade kaynakları; vendor/published vendor şablonları, testing, storage/cache/log/backup taranmaz. PHP token parser global `__`, `trans`, `trans_choice`, `Lang::get/choice` ve tam facade namespace'ini tanır. Blade derlemesindeki gerçek çağrılarla adaylar doğrulanır; `@lang/@choice`, echo ve PHP blokları desteklenir. Literal ilk argüman, çok satır, kaçış, Unicode ve çoğul metin korunur; concat/interpolasyon/dinamik veya mevcut varchar255 sınırını aşan anahtar tahmin edilmez.

Uygulama PHP dil grupları ayrıca tanım olarak kaydedilir; framework'ün dinamik validation gibi kullanımını da dil ekranında düzenlemek mümkündür. Grup tanımları dosya bilgisi taşır; çağrıların gerçek satırı ayrı gösterilir. Yeni UI metinleri için tr/nl/en/fr `translation_manager.php` kaynakları eklendi; bu DB dil aktivasyonu değildir.

İlk salt okunur/Chrome ön izlemesinde **591 dosya / 1.253 anahtar / 10 dinamik çağrı**. Yerel tek PHP ön izleme yaklaşık0,335sn; üretim HTTP performans garantisi veya bütün dinamik anahtarların çözümü değildir. `account.*`, `learning.*`, `tables.*` gibi PHP anahtarları JSON cümlelerinden ayrılır.

## 3. Dosya ve route eşlemesi

Her kullanım key/tür/grup/orijinal/dosya/satır/ön yüz-panel-ortak/route/call metadata taşır. Kayıtlı route'ların gerçek controller method aralıkları ve literal view dönüşleri eşlenir; aynı controller'ın diğer route'ları çeviriye eklenmez. Blade include/extends ve Livewire tag/directive → component → render view ilişkileri izlenir; ortak partial birden fazla route altında listelenebilir. Dinamik view, helper çağrı zinciri veya otomatik eşlenemeyen component rotası uydurulmaz; dosya/satır gösterilir. Bunlar çalışma sırasında izlenen bütün olası koşulların route grafiği değildir.

## 4. Mevcut kayıtları koruyan ekleme

Kaynakları tara yalnız ön izleme yapar. Büyük kaynak dizisi istemciye taşınmaz; kullanıcı+Locked token'a bağlı,10 dakika geçerli sunucu cache'inde tutulur. Eksikleri ekle izin kontrolünden sonra DB transaction içinde kullanım kayıtlarını upsert eder ve mevcut Language kayıtlarının eksik çeviri key'lerini `insertOrIgnore` ile tamamlar. Mevcut değer, locale+key unique, dil durumu veya eski translation satırı değiştirilmez. Yeni key için mevcut JSON/PHP dil dosyası değeri varsa alınır; yoksa boş bırakılır. PHP dosyaları dil/grup başına bir kez okunur, DB işlemleri200 kayıtlık parçalarla yapılır.

Tekrar tarama çoğaltmaz. Artık kullanılmayan kaynak satırları silinmeden pasif işaretlenir; çeviri kayıtları silinmez. Ön izleme uygulandıktan sonra tüketilir; tekrar/sona ermiş ön izleme anlaşılır hata verir. Ana DB'de bu tur **tarama ekleme veya gerçek çeviri değer güncellemesi çalıştırılmadı**; kullanıcı ekrandaki ön izleme/ekleme düğmeleriyle başlatabilir.

## 5. Yönetim ekranı

LanguageTranslateForm ilk HTML'e gelir; bütün çeviri relation'ı public snapshot'a yüklenmez. Yalnız görünür40 kayıt değerleri taşınır; sayılar SQL aggregate, kaynaklar tek sayfanın key'leriyle sorgulanır. Mevcut düzenleme formu korunur; bu textarea ekranı serverJSON'a dönüştürülmez. Arama kaynak metin/key veya çeviri; panel/ön yüz/ortak, PHP grup, dosya/route ve boş/dolu filtreleri vardır. Filtre değişimi sayfayı1'e alır; sayfa geçişi korunur.

Kullanım bilgileri açılır detayda bütün dosyalar/satırlar ve eşlenmiş route adlarıyla gösterilir. Güncel kaynakla eşlenmeyen eski çeviri korunur ve açıkça belirtilir. Dinamik çağrılar ilk30 dosya/satır olarak ayrı bölümde görülebilir; toplam sayı ön izlemededir. Bootstrap/OneUI mevcut responsive ve koyu tema stilleri kullanıldı; CSS/JS değişmedi, APP_VERSION **1.1.18** kaldı.

## 6. Yetki, düzenleme ve dil dosyası üretimi

Güncel dashboard:access + languages:translate mount/render/okuma/tarama/ekleme/save ve blur yazımında denetlenir; language ve scan durumları Locked. Gelen textarea kimliği public state içindeki id'den alınmaz; gerçek id+seçilen locale ile sorgulanır. Başka dile ait id404; iznin sonradan alınması403. Metin boyutu text kolonunun byte sınırıyla denetlenir; `:name` gibi yer tutucu adları kaynakla uyuşmazsa DB yazılmaz. Düzeltilen değer hata mesajını temizler. Blur veritabanına kaydeder; dosya yayını ayrı Kaydet işlemidir.

TranslationHelper JSON/PHP türlerini ayırır. PHP group key'lerini JSON'a yazmaz; mevcut nested PHP değerleri korunarak ilgili grup dosyası üretilir. `var_export` çeviri içeriğini çalıştırılabilir PHP'ye dönüştürmez. Mevcut JSON değerleri ve DB'deki boş alanların karşısındaki hazır dosya çevirileri korunur; eksik boş key'ler JSON'da boş çıktı üretmez. Telefon varsayılanı/custom değeri eski sözleşmeyle devam eder. Dosyalar tek tek atomik replace ile yazılır; farklı dosyalar arasında tek dosya sistemi transaction'ı yoktur. Locale yolu doğrulanır. Yayından sonra translator loaded durumu ve ilgili ön yüz cache revision'ları yenilenir; global flush eklenmedi. Mevcut Translation modelinin Loggable davranışı yeniden yazılmadı.

## 7. Onaylı migration kanıtı

Yalnız `2026_10_07_110000_create_translation_sources_table.php` hedefli `migrate --path ... --database ... --force` uygulandı. Diğer bekleyen migration'lar çalıştırılmadı; rollback/fresh/seed/temizlik yok.

- Ana `hypotenuse`: migration id38/batch8.
- `testing`: migration id37/batch2.
- İki DB'de de languages/translations şema SHA256 ve satır sayısı + toplu satır CRC özeti önce/sonra eşleşti. Var olan iki tablo değiştirilmedi.

Yeni kullanım tablosu kimlik hash unique, key/grup/surface/active indeksleri içerir; mevcut çeviri tablosuna FK/kolon/indeks veya sınırlama eklenmedi. Yerel migration kanıtı canlı sunucu uygulaması değildir.

## 8. Dar test ve Chrome kanıtı

**7 yeni ve4 mevcut, toplam11 farklı ilgili senaryo başarılı**, ayrı dar koşularda. İlk6 yeni senaryoda3 başarılı/3 başarısızdı; satır beklentisi ve textarea değerinin HTML yerine snapshot'tan kontrolü düzeltildi, geçerli düzenleme sonrası eski validation hatası temizlendi. Yalnız bu3 senaryo tekrar3/22 ile geçti. Ön izleme testindeki mock partial hale getirildi;1/11 geçti. Route adı beklentisi kayıtlı frontend.home olarak düzeltildi; son eşleme1/9 geçti. Son grup okuma optimizasyonu yalnız tekrar tarama1/9 ile doğrulandı.

- Parser literal/multiline/escape/comment/dynamic/concat/Blade/directive; gerçek ön yüz partial ve Livewire/controller route eşlemesi.
- Tekrar tarama, üç kullanım, eski çeviri ve kullanım geçmişinin korunması, mevcut PHP değerini alma.
- Seçili dil, placeholder hata→düzeltme, yetki kaybı, süresi bitmiş ön izleme ve tüketim.
- Kaynak/orijinal filtreleri,40+1 sayfa ve yalnız görünür değer snapshot'ı.
- JSON/PHP ayrımı, nested/custom değer ve zararsız PHP görünümlü çeviri string'i; yalnız geçici dil dosyaları.
- Mevcut AccessBoundaryTest'in3 çeviri senaryosu3/14; atomic replace/404/dashboard sözleşmesine uyarlanmıştır. Mevcut telefon build testi1/21 geçti.
- İlgili11 PHP dosyası Pint ve scoped diff whitespace başarılı; Blade gerçek component/browser render ile derlendi. Genel suite/build/Composer/deploy yok.

Chrome testing QA: NL gerçek admin translate route200/ilk HTML,40+5 kayıt, textarea değeri, iki source route, arama, filtre ve gerçek ön izleme;1440px/390px ve koyu tema kontrolü. Mobilbody390/viewport390, input284px; koyu textarea zemin rgb(23,30,40) ve metin rgb(190,200,213). JS pageerror0. QA fixture'leri her HTTP isteğinde transaction ile geri alındı; gerçek ana kayıtta düzenleme/ekleme/Kaydet tıklanmadı. Kalıcı DB ekleme/yazım kanıtı feature test transaction'larıdır. İlk QA betiğinde yanlış /login URL, dil id1 ve gizli mobil pagination düğmesi seçildi; kayıtlı /auth/login/testing id492/görünür düğme ile düzeltildi, ürün değişikliği yapılmadı. QA sunucusu kontrol sonrası kapatıldı; Sail/başka proje servisleri durdurulmadı.

## 9. Değişen dosyalar ve sıra

- `app/Helpers/{TranslationScanner,TranslationHelper}.php`.
- `app/Models/TranslationSource.php`, yeni hedefli migration.
- `app/Livewire/Languages/LanguageTranslateForm.php`, ilgili Blade.
- `lang/{tr,nl,en,fr}/translation_manager.php`.
- `tests/Feature/{TranslationScannerTest,AccessBoundaryTest}.php`.
- AGENTS/README/Codex16,51,60,61/index güncel faz kaydı.

Faz1 Rappasoft karşılığı tamam. Faz2 tarayıcı/yönetim tamam. **Faz3 sırada:** Laravel bağımsız SQL.gz + ZIP → R2 bash. Lara Football referans scriptinin adı/sunucu yolu hâlâ bekleniyor;61 kapsamında backup/R2/cron işlemi yapılmadı. Yedek dosya kapsamı/saat/timezone/bucket/prefix ve gerçek dış işlem ayrı netleşecek; phase60 bölüm7 esas alınır.


## 7 Ekim devam — Faz3 Bash hazır

[62-LARAVEL-BAGIMSIZ-BASH-YEDEK-VE-R2.md](62-LARAVEL-BAGIMSIZ-BASH-YEDEK-VE-R2.md): kullanıcı yeni Bash ve yalnız Laravel referansını seçti; önceki Lara scripti bekleme kararı kapanır. SQL.gz/proje ZIP için iki mod, dotenv allowlist/private alan/overlap lock/AWS CLI R2/boyut+SHA256 geri okuma; doğrulama sonrası yalnız bu run arşivi silinir, hata arşivi korunur.11 farklı fake DB/R2 dar test ve Bash syntax başarılı. Gerçek sunucu/R2/cron/restore uygulanmadı; bucket/prefix/private dizin/saat-timezone kurulum adımıdır. Eski üst kayıtlar tarihsel kapsamıyla korunur.
