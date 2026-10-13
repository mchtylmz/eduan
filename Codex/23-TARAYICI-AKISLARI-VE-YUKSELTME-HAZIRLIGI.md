# Kritik tarayıcı akışları — yükseltme öncesi kayıt

5 Ekim 2026. Kullanıcı `22` belgesindeki birinci adımı istedi: giriş, test/sınav, panel tabloları ve yükleme akışları. Bu kayıt mevcut Laravel 11.44.7 üzerinde gerçek Chrome/Playwright kontrolüdür. Paket/yükseltme uygulaması değildir. Genel otomatik koşunun önceki **245 PHP / 1.163 assertion + 7 PWA** sonucu `22` içinde kalır; bu tur aynı suite tekrar çalıştırılmadı.

## 1. Ortam ve izolasyon

- URL: `http://127.0.0.1:8765`. Sadece loopback üzerinde geçici foreground PHP sunucusu.
- Browser plugin/skill bu oturumda yoktu; paket kurulmadan mevcut bundled Playwright 1.62.1 ve kurulu Google Chrome kullanıldı. Sandbox Chrome başlatmasını engelledi; onaylı escalation ile ayrı geçici profil açıldı. Normal kullanıcının browser profili kullanılmadı.
- Son kontrol sunucusu host **PHP 8.3.31**, Docker DB **MySQL 8.0**, framework **11.44.7**. Docker CLI PHP **8.3.32** ayrıca okundu. Bu browser kanıtı Docker web SAPI veya canlı PHP kanıtı değildir.
- Desktop 1440×1000; seçili mobil kontroller 390×844. Gerçek telefon/Safari/Android cihazı kullanılmadı.
- Ana `.env` değiştirilmedi. Bootstrap öncesi `DB_DATABASE=testing`, bağlantıdan sonra gerçek DB adı `testing` denetlendi. Host DB adresi Docker'ın yayımladığı `127.0.0.1:3306` portuydu.
- Cache/session/view/log/upload ve framework'ün env ile seçilen bootstrap cache yolları `/tmp/eduan-browser-1005` altında; ayrı session cookie kullanıldı. `Loggable/resetCache` çağrıları bu QA bağlamında gerçekleşti. Blade Icons'un sabit `bootstrapPath('cache/blade-icons.php')` yolunu da kapsamak için en son geçici harness'te bootstrapPath ayrıca ayrıldı; ilk koşunun bu manifest için tam izolasyon kanıtı yoktur. Bunların gerçek production hızını bu sonuçtan çıkarma.
- Mail array, Telescope/Pulse/Debugbar kapalı, Laravel HTTP stray requests engelli; Sentry DSN kapalı. Mail, AI, backup/CDN ve production scheduler tetiklenmedi.
- Queue database bağlantısı yalnız `testing`, queue adı `codex-browser-1005`. Kontrolde bu queue için tek `queue:work --stop-when-empty --tries=1` çalıştırıldı. Canlı cron kurulmadı veya değişmedi.
- Sentetik fixture: `qa1005` kullanıcı, 24 ders, bir konu, iki soru/dört cevap, iki sorulu süresiz test ve 60 saniyelik süreli sınav. Blog upload fixture'ı da aynı işaretle tanımlandı. Gerçek öğrenci/veri kullanılmadı; migration/fresh/seeder çalıştırılmadı.

## 2. Kontrol edilen akışlar ve sonuç

| Akış | Gerçek etkileşim | Kanıt / sonuç |
|---|---|---|
| Login hatası | Yanlış parola ile form gönderimi | Flemenkçe hata toast'ı; login ekranında kalır, giriş yapmaz |
| Başarılı login | QA hesabıyla form gönderimi | `/account/profile`, `Mijn account`; ayrı oturum açıldı |
| Süresiz test | İlk doğru cevap → sonraki soru → yanlış cevap → tamamlama | İlk cevap sonrası radio'lar disabled; sonraki soru açılır; `/account/solved` sayfasında 1 doğru / 1 yanlış; DB'de completed=1 |
| Süreli sınav yeniden giriş | Cevap seç → profile git → aynı sınava dön | Aynı `data-timer` bitiş zamanı ve aynı seçili cevap; yeni aktif deneme/süre oluşmaz |
| Süreli cevap değişimi | Doğru cevaptan yanlış cevaba geç | Radio ve DB cevap kaydı değişti; süre bitmeden değiştirilebilir |
| Süre sonu, sayfa açık | 60 saniyelik QA süresi doğal olarak dolar | Sayaç `Beëindigd/Gereed`; tamamlanma modal'ı görünür; deneme tamamlanır |
| Süre sonu, sayfa kapalı | Cevap kaydet → panel listesine git → süre sonrası yalnız QA queue'yu işlet | completed=1, ilk expires_at=completed_at, kayıtlı cevapla 1 doğru; QA failed_jobs=0 |
| Yeniden deneme | Tamamlanmış sınavın start rotasını tekrar aç | Cevaplar boş, yeni bitiş zamanı; geçmiş kayıtlar korunur |
| Manuel tamamlama | Cevap seç → bitir → native onayı kabul et | `Examen voltooid` modal'ı, 3 puan; deadline öncesi completed=1. Eksik ikinci soru cevapsız kalır |
| Ders tablosu sayfalama | İkinci sayfa düğmesi | Gösterim aralığı 1–10'dan 11–20'ye geçti; toplam 24 |
| Ders tablosu arama | `qa1005-01` | Tek ders, bir konu/iki soru; gerçek Livewire yanıtı sonrası satır yenilendi |
| Ders tablosu durum | Passive filtresi | Fixture'ların hepsi active olduğu için boş sonuç mesajı |
| Ders tablosu sıralama | Code başlığı | Query `table-sorts[code]=asc`; qa1005-01…10 sırası |
| Konu / soru / test / sınav tabloları | Sayfaları açıp lazy verinin gelmesini bekle | Konu ve iki soru, QA Practice ve QA Timed satırları; ilgili sütun/sayılar; NL görünümde 500 yok |
| TinyMCE + PNG | Blog içerik alanına yaz → dosya seç → kaydet → düzenlemeyi yeniden aç | HTML içerik kaydedildi; 627 byte gerçek PNG QA upload diskinde; preview 220×90 ve dosya URL'si yüklendi |
| SVG | Blog görseline SVG seç | Dropify dosya tipi hatası; mevcut görsel değiştirilmedi. Bu browser deneyi server MIME kontrollerinin yerine geçmez (`PanelUploadSecurityTest`) |
| Logout / guest | Çıkış → sınav start | `/auth/login` yönlendirmesi; login formu görünür |
| Mobil | Test ve ders tablosunu 390 px'de aç | Sayfa kökü scrollWidth=390; test görseli yüklendi; panel tablosu kendi alanında incelendi. Bütün mobil kontrollerin kullanışlılığı onaylanmış değildir |

Giriş/test/sınav/tablo/PNG akışları için sayfa kimliği, anlamlı içerik, ilgili etkileşim ve screenshot alındı. NL kontrolünde framework hata overlay'i veya uygulama JavaScript exception'ı görülmedi. İngilizce asset 404'leri aşağıdaki açık bulgudur; bütün console kayıtlarına hatasız denmez.

## 3. Yeni bulgular — tarihsel ilk keşif

**Son durum:** Kullanıcı bu iki bulgunun düzeltmesini onayladı. B23-01/B23-02 ve sonradan onaylanan tarih UI locale düzeltmesi `25-DIL-DUZELTMELERI-VE-TABLO-REFERANSI.md` içinde kapatıldı: 9 dar test/170 doğrulama ve NL/EN browser kanıtı. Aşağıdaki metin ilk keşif kaydıdır.

### B23-01 — İngilizce asset URL'leri mevcut dosyayla eşleşmiyor

`?lang=en` ile panel açıldıktan sonra browser şu üç istekte 404 kaydetti:

- `backend/assets/js/plugins/jquery-validation/localization/messages_en.min.js`
- `backend/assets/js/plugins/flatpickr/l10n/en.js`
- `backend/assets/js/plugins/bootstrap-select/dist/js/i18n/defaults-en_EN.min.js`

Ön yüzde de `assets/js/flatpickr/l10n/en.js` 404 verdi. `resources/views/backend/layouts/app.blade.php` 62/69/74 ve `resources/views/frontend/layouts/app.blade.php` 149 locale'den dosya adını otomatik türetiyor. Dosya envanteri bu tam yolları içermiyor. Bazı kütüphanelerde İngilizce zaten varsayılandır; çözüm tüm eksik isimleri sahte dosyayla doldurmak olmamalı. Gerçek dağıtım dosyası ve locale eşlemesi dar düzeltme olarak ayrıca onaylanır.

`lang/en` Laravel PHP mesaj dosyaları var; root `lang/en.json` yok. English aktif QA dili olarak testing DB'ye eklendiğinde uygulama başlıkları Türkçe kaynak metne dönebildi. Bu tam İngilizce ürün çevirisi kanıtı değildir. Ana/canlı DB'de İngilizcenin aktifliği bu testte okunmadı.

### B23-02 — Editor/uploader dil tutarlılığı

NL blog formunda TinyMCE menüsü İngilizce, Dropify mesajları Türkçe göründü. `resources/views/components/tinymce/editor.blade.php` ve backend layout editor init'i her dil için `tinymce_tr.js` yüklerken `language` değerini uygulama locale'inden alıyor; o dosya yalnız `tr` tanımlar. Editor yazma/kaydetme çalışıyor; dil uyumu ayrıca ele alınmalı.

### Mobil görsel inceleme adayı

İki uzun sentetik cevap etiketi 390 px ekranda dar kutuya sığmadı; süreli sınavın sol navigasyonu mobilde sorudan önce geniş bir boşluk oluşturdu. Bu screenshot adayı gerçek soru/cevap uzunluklarıyla değerlendirilmelidir. Sayfanın kökünde yatay taşma ölçülmedi. Bu tur CSS/tasarım değiştirilmedi.

## 4. QA ortam hataları — ürün bulgusu sayılmayanlar

1. İlk geçici CLI bootstrap request bağlamı eksikti; fixture transaction tamamlanmadı. Geçici harness'e request eklendi.
2. Fixture dilinde model fillable listesinden alınan `sort` mevcut testing şemasında yoktu; transaction rollback oldu, fixture alanı kaldırıldı. Migration/ana veri değiştirilmedi. Canlı şema hakkında bundan karar çıkarılmadı.
3. İlk host PHP 8.4 koşusunda `User::usageGptLimit` ve Settings nullable parametre deprecation'ları çıktı. Son kanıt PHP 8.3'te alındı. PHP 8.4 hedefi seçilirse bu imzalar ve paket kodu ayrıca değerlendirilir; vendor dosyası değiştirilmedi.
4. Livewire `APP_ENV=testing` iken upload diskini `tmp-for-tests` olarak seçer. Browser harness'te disk başlangıçta tanımsızdı; ilk upload POST 500, blog görseli null kaldı. Yalnız geçici bootstrap'a local tmp-for-tests diski eklendi. Aynı blog edit üzerinden PNG yeniden yüklenip kaydedildi, dosya/preview doğrulandı. Bu production upload arızası değildir.
5. `getImage` public_path üzerinde fiziksel dosya arar. QA upload dizini ayrıyken ilk fixture resmi fallback'e düştü; QA public_path ve statik kaynak bağlantıları ayrılarak tekrar gerçek resim doğrulandı. Ana public/uploads'a QA görseli yazılmadı.
6. İlk otomasyon beklentileri düzeltildi: süresiz tamamlama hedefi `/account/solved`; tablonun ilk tbody satırı gizli loader; soru listesi başlığı göstermiyor; dil filtresi kayıt locale'ini seçer, UI dilini değiştirmez; native bitirme onayı Chrome tarafından handler yokken iptal edilmişti. Bunlar ürün failure sayısı değildir. Manuel bitirme native onay kabul edildikten sonra doğrulandı.

## 5. Kanıtlar ve tekrar kullanım

Yerel screenshot/log/script'ler `/tmp/eduan-browser-1005`, bootstrap/router/fixture/browser script'leri `/tmp/eduan-qa-*` ve `/tmp/eduan-browser-check.cjs` içindedir. Bunlar Git dışı, makineye özgü ve geçicidir. Başlıca screenshot'lar: `login-final.png`, `practice-answer.png`, `manual-finish.png`, `table-search.png`, `upload-preview.png`, `practice-mobile.png`, `table-mobile.png`. Eski screenshot'taki fixture görsel eksikliği ürün görsel bulgusu değildir.

QA kullanıcı verileri yalnız testing DB'dedir. Sentetik kayıtların silinmesi için AGENTS.md gereği ayrı soru gönderildi; yanıt gelmeden kayıt silme yapılmaz. Kayıtlar kaldığı sürece global count/empty DB bekleyen PHPUnit sınıfları etkilenebilir. Önceki 245-test koşusunun sonucu bu fixture'ların var olduğu DB için yeni koşu sonucu değildir. Testing DB fresh/migrate veya geniş silme çözüm olarak otomatik çalıştırılmaz.

Kontrol sonunda geçici web sunucusu ve browser runner durduruldu; fixture temizliği sorusu yanıt bekliyor. Son okuma: bir tamamlanmış practice denemesi (1 doğru/1 yanlış), dört tamamlanmış timed denemesi (üçü süre sonu, biri manuel); yalnız QA queue'da iki gecikmeli/idempotent tamamlama işi kaldı, QA failed_jobs=0. Bu işler ana/canlı queue değildir. Gelecek fixture temizliği planı aynı kullanıcı/test kimlikleri ve queue adıyla sınırlı olmalı.

## 6. Kapsam dışında kalanlar

- Her panel modülünün bütün CRUD/export ve rol kombinasyonları; tek yetkili QA hesabı kullanıldı.
- Gerçek cihaz, Safari/Firefox, bütün mobil etkileşimler; ilk mobil görüntü/layout kontrolü seçilidir.
- Gerçek offline PWA/eski worker geçişi; browser normal çevrimiçi akışta çalıştı.
- Üretim worker yoğunluğu, cron zamanı, çoklu sekmede aynı anda başlatma ve yük testi.
- Maksimum 10 MB upload'ın browser/SAPI sınırı; bu koşuda küçük PNG/SVG. Mevcut otomatik boyut testinin kapsamı ayrıca `22` içindedir.
- PDF/Word ve bütün Excel exports; bu tur upload kontrolü blog PNG ile sınırlı.
- SEO/hız/LCP ölçümü; QA fixture ve uncached testing uygulaması production benchmark değildir.

## 7. Yükseltme hazırlığına etkisi

Kritik NL akışları için gerçek browser başlangıç kanıtı oluştu. B23-01/B23-02 sonradan kullanıcı onayıyla `25` içinde kapatıldı; bu bütün dillerde bütün ürün senaryolarının kabulü değildir. Güncel paket aralıkları `24-PAKET-UYUMLULUK-MATRISI.md` içindedir. Canlı SQL/migration karşılaştırması kullanıcı dökümünü bekler; bu kayıt şema veya yükseltme uygulama onayı değildir.
