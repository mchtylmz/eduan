# Güvenlik incelemesi ve onaylı beş düzeltme

26 Ekim 2026. Kullanıcı genel güvenlik incelemesini ve bulunan beş sorunun ayrı paket olarak düzeltilmesini onayladı. İnceleme mevcut kaynak üzerindedir; üretim penetrasyon testi veya bütün dosyaların güvenlik sertifikası değildir.

## 1. Kaynak taraması

Codex Security Standard kaydı: `1bf90f1d-e41d-41e5-b015-598f10750032`, başlangıç commit’i `74e8875c`. Bağımsız baseline, bağımsız mimari inceleme ve üst ajan doğrulaması yapıldı. İki takip ajanı kapasite hatası verdi; ilgili değerlendirme üst ajanda tamamlandı. Yeni bir model seçilmedi. Tarama kapsamı dürüstçe **partial** bırakıldı.

Değişmez başlangıç raporu: [yerel güvenlik raporu](/Users/mucahit/.codex/state/plugins/codex-security/scans/eduan-web/74e8875c985d18f1c52721f3c7f8e5407faeef09_20261026T134413Z_fu11ry0q/report.md). Rapor düzeltme öncesi kaynak durumunu gösterir. Sonraki [doğrulama kaydı](/Users/mucahit/.codex/state/plugins/codex-security/scans/eduan-web/artifacts-bb40a0b3cedfde65b10e859fd5e535d5563af2bc4bcabea8795d977a5279ebf4/artifacts/security-fixes-verification.md) ayrıca saklandı; mühürlü rapor değiştirilmedi.

## 2. Giderilen sınırlar

| Bulgu | Yapılan işlem | Korunan normal kullanım |
|---|---|---|
| Events üzerinden izinsiz ayar kaldırma | Sabit olay listesi; ayar kaldırma/cache için dashboard ve settings:update; yalnız mevcut logo/kapak anahtarları kaldırılabilir | Tema/sidebar tercihleri ve yetkili cache işlemi |
| Global modal/offcanvas ile form izni aşma | Gerçek çağrılardan sabit hedef listesi, Locked component/data, açılış/render yetkisi; FAQ/dil/AI/iletişim formlarında bağımsız sabit kaynak yetkisi | Mevcut seçim/lig/sonuç/mail panelleri, yetkili zengin metin düzenleme |
| Public permission değiştirerek bülten maili | Sabit newsletter:send ve güncel kaynak yetkisi; izin alanı Locked | Yalnız seçilen abonelere yetkili gönderim |
| İstatistik XLSX metninin formüle dönüşmesi | Dört FilterStats export’unda ortak açık TYPE_STRING binder | Sayısal hücreler sayısal kalır; öğrenci metni aynen görünür |
| Parola kurtarmadan sonra eski oturum | Girişte parola kanıtı, parola değişiminde remember_token dönüşü, web grubunda parola/oturum kontrolü | Geçerli hatırla girişi ve kullanıcının mevcut oturumunda parola değiştirmesi |

İlk üç bulgu panel hesabı gerektirir; anonim yönetici ele geçirme iddiası yoktur. Excel’de personelin dosyayı açması gerekir; işletim sistemi komut çalıştırması gösterilmedi. Oturum bulgusu daha önce çalınmış bir oturum gerektirir. Kalibrasyon: üç orta, iki düşük.

## 3. Oturum geçişi

`EnsurePasswordSession`, Laravel `AuthenticateSession` kontrolünü kullanır. Stok middleware eksik parola kanıtını güncel paroladan oluşturduğu için tek başına eski, bekleyen oturumu iptal etmez. Bu nedenle önceden açık olup parola kanıtı taşımayan oturumlar yeniden giriş yapar. Remember çerezi kendi parola kanıtıyla ayrıca denetlenir.

Parolayı kendi açık oturumundan değiştiren kullanıcının guard nesnesi ve oturum kanıtı güncellenir. Başka oturumların eski kanıtı reddedilir. Dosya/database session ayrımı için kayıt silme veya migration gerekmez. Canlıya aktarımda mevcut kullanıcıların bir kez yeniden giriş yapması beklenen güvenlik etkisidir.

`tests/TestCase.php::actingAs`, gerçek LoginAction’ın oluşturduğu kanıtı test oturumuna ekler. Eski oturum testi bu alanı açıkça kaldırıp reddi doğrular; testlere özel üretim bypass’ı eklenmez.

## 4. Test kanıtı

- Başlangıçtaki mevcut güvenlik paketi: **49 PHP /199 doğrulama**.
- Yeni güvenlik regresyon dosyası: **18 PHP /130 doğrulama**, ardından eklenen ayrı hatırla/parola değişikliği senaryosu **1/9** geçti. Son durumda 19 farklı güvenlik senaryosu vardır.
- İlgili 15 dosyalık birleşik paket: **174 PHP /1194 doğrulama** geçti. Giriş/kurtarma, hesap, kaynak izinleri, upload, test çözümü, mail, panel CRUD, Excel, öğrenme tabloları, ayarlar ve PWA sürümü kapsanır. Aynı çalışma ağacındaki Turnstile testleri de bu toplamın içindedir.
- Sonradan eklenen altı Turnstile/bülten senaryosu **6/41**, hatırla senaryosu **1/9** geçti. Turnstile ayar senaryosunun son dar genişletmesi ayrıca +10 doğrulama sağladı. Böylece **181 farklı PHP senaryosu /1254 doğrulama** ayrı koşularla başarılıdır; tek temiz 181’li koşu iddiası değildir.
- Dört gerçek XLSX üretildi, yeniden açıldı: formül benzeri metin string, miktar hücreleri numeric.
- 21 güvenlik PHP dosyasının Pint kontrolü başarılı. Bağımsız son yama incelemesinde somut kalan bypass/regresyon bulunmadı.
- İlk dört Excel testinde formatlanmış `toArray` çıktısını integer sanan fixture beklentisi düzeltildi; ürünün sayısal hesaplaması değiştirilmedi.

Tarayıcı ve Turnstile yaşam döngüsü kanıtı sonraki Turnstile kaydında; gerçek sağlayıcı veya üretim teslim testi değildir.

## 5. Değişen güvenlik dosyaları

- `app/Actions/Auth/{AuthorizePanelForm,LoginAction}.php`, `app/Actions/Users/UpdatePasswordAction.php`.
- `app/Http/Middleware/EnsurePasswordSession.php`, `bootstrap/app.php`.
- `app/Traits/AuthorizesPanelOverlay.php`, `app/Livewire/{Events,Modal,Offcanvas}.php`.
- `app/Livewire/{Faqs/FaqForm,Languages/LanguageForm,Ai/AnswerAiForm,Contacts/ContactReplyForm,Newsletters/NewsletterSendForm}.php`.
- `app/Exports/TextValueBinder.php`, dört `FilterStats*Export.php`.
- `tests/TestCase.php`, `tests/Feature/SecurityFindingsRegressionTest.php` ve rehber bağlantıları.

## 6. Açık sınırlar

Kayıt ekranı e-posta onayından sonra giriş sözü veriyor; mevcut aktif ama onaysız hesap girişini kod engellemiyor. Eski hesapları kapatabilecek yeni bir politika varsayılmadı; bu davranış ayrıca kararlaştırılmalıdır.

Yerel Livewire geçici upload dizini public disk altında çözülüyor; imzalı endpoint/MIME/uzantı kontrolleri incelendi, statik sunucu erişimi/üretim eşdeğerliği doğrulanmadı. Proxy/gerçek istemci IP’si, canlı cookie/TLS, filesystem izinleri, tüm bağımlılık advisory’leri, tüm JS/vendor kaynakları ve kalan modüllerin bütün dosyaları denetlenmedi.

Ana DB, gerçek mail/push/AI/Cloudflare gönderimi, migration, kullanıcı verisi silme ve canlı deploy yapılmadı. Yeni güvenlik politikası dosyası veya paket eklenmedi. Kullanıcının eski `.DS_Store`, Doge/docs ve service worker dosyaları pakete alınmaz.

Tarama kullanım ölçümü kısmi ve eski rollout bağlamı içerir; host’un toplamı 16.496.961 token (16.004.224 cached input dahil), eksik 14 thread/rollout uyarısıdır. Bu rakam değişikliğe özgü fatura veya tam ölçüm olarak yorumlanmaz.


## 7. Hızlı SQL injection / XSS taraması ve kişiselleştirme düzeltmesi —4 Kasım 2026

Kullanıcı API/panel/site/giriş dahil hızlı SQL injection ve XSS kontrolünü, bu sınıflardaki bulguların düzeltilmesini istedi. Mevcut çalışma ağacında Codex Security Standard taraması `83e48f97-be44-4b48-9e29-f631700ff273` tamamlandı. Kaynak incelemesine bağımsız mimari/baseline değerlendirmesi eklendi. Kapsam **partial**: ham SQL ifadeleri ve dinamik kolon/sıralama kaynakları, giriş/kurtarma, ortak panel JSON/arama, kişi/yorum/study tabloları ve şablon HTML sınırları incelendi. Bütün repository/vendor satırları veya tüm rol kombinasyonları incelenmiş değildir; üretim penetrasyon testi değildir. Ayrı `routes/api.php`/bootstrap API rotası bulunmadı; uygulamanın API benzeri JSON/Livewire/bildirim uçları değerlendirmeye dahildir.

**SQL injection:** İncelenen yollarda doğrulanmış SQL injection, SQL üzerinden login bypass veya komut çalıştırma bulgusu yok. Giriş/kurtarma kriterleri bağlanmış değer; ServerTable sort/order allowlist; StatisticsTable raw order çağrıları sabit ya da doğrulanmış seçim; kişisel StatsTable kolonları allowlist; LeagueRules kaynak/puanlar sabit tip ve izinli değerlerle sınırlı. Bu sonuç bütün projenin veya canlı altyapının kesin güvenli olduğu iddiası değildir.

**Doğrulanan orta önem bulgusu:** Ziyaretçinin iletişim ad/telefon/okul alanındaki HTML, `replaceEmailVariables` ile yanıt şablonuna kaçışsız ekleniyordu. `ContactReplyMail::build()` kişiselleştirilmiş yanıtı kaydediyor; panelde entity decode + raw render, personel tarayıcısında saklanan XSS yolu oluşturuyordu. Personelin ilgili parametreyi kullanması, mail build ve detayın tekrar açılması gerekir. Turnstile bunu önlemez. Gerçek helper/Mailable ve kayıt/decode dönüşümleriyle ağ/DB olmadan PoC panelde1, e-postada2 olay işleyicili HTML düğümü üretti. Canlı saldırı veya betik yürütme denemesi yapılmadı.

Düzeltme:

- Ortak helper yalnız güvenilen şablonu önce decode eder; bütün kullanıcı değerlerini double-encode açık şekilde escape eder.
- `strtr` tek geçişli parametre değişimiyle kullanıcı metnindeki başka parametrelerin yeniden yorumlanmasını önler; tam `<pre>` sarmalayıcı kaldırma korunur.
- Altı mail görünümünde kişiselleştirmeden sonraki decode kaldırıldı: iletişim, tek/toplu kullanıcı maili, bülten, doğrulama, kurtarma, yeni parola.
- Daha önce kaydedilmiş yanıtlar da panel ana belgesinde raw HTML değildir: kaçışlanmış `srcdoc`, boş `sandbox`, `no-referrer` ile300px yüksekliğinde kaydırılabilir HTML önizlemesinde gösterilir. Script/same-origin/top-navigation izinleri verilmez; kayıtlar değiştirilmez. Mailin zengin HTML biçimi korunur.
- Kaynak şablonların yetkili HTML yazarlığı sözleşmesi değişmez. Kullanıcı parametreleri metin/normal tırnaklı HTML niteliklerinde kullanılmalıdır; şablona bilerek JS/CSS veya kullanıcı değerini URL protokolü yapan ifade yazma yetkisi ayrı güven sınırıdır.

Değişen uygulama dosyaları: `app/Helpers/Functions.php`, `resources/views/email/{contact-reply,user-email,newsletter,verify,recover-password,create-password}.blade.php`, `resources/views/livewire/backend/contacts/contact-reply-form.blade.php`. Yeni test `tests/Unit/MailPersonalizationSecurityTest.php`.

Dar kabul: **10 PHP test/112 assertion**;8 saldırı girdisi ×6 gerçek Blade mail görünümü, ham/script/SVG/decimal/hex/çift entity girdileri, token içeren isimler, aksan/apostrof/ampersand, ham/kodlanmış şablon biçimi, gerçek Mailable build ve sahte model update ile kayıt dönüşü, geçmiş kayıt iframe sandbox sözleşmesi. Tüm fixture'lar bellekte ve geçici derleme dizinindedir; ana/testing DB açılmaz. Ortak tablo formatter'ları2 Node testi/25 assertion geçti. Helper syntax, helper+yeni test Pint ve dar diff whitespace başarılı. İlk testte User mass-assignment fixture'ının şema çözümlemesi gerektirmesi `forceFill` ile DB'siz fixture olacak şekilde düzeltildi; ürün davranışı değiştirilmedi. Bağımsız son yama incelemesi somut bypass/regresyon bulmadı.

Üretim DB/SMTP/push/AI/Cloudflare isteği, migration veya deploy yok. Eski veriler silinmedi/düzenlenmedi. Gerçek tarayıcı/CSP/hosting ve DB/HTTP bütünleşik testleri bu hızlı turda yapılmadı. CSS/JS ve APP_VERSION1.1.42 korunur. Kaynak taramasının token kullanım ölçümü mevcut değil. Mühürlü tarama raporu düzeltme **öncesi** durumu kaydeder:

[Tarama raporu](/Users/mucahit/.codex/state/plugins/codex-security/scans/eduan-web/48b6d9e6c45a72d7871a00a6db8acfe2c5818264_20261104T131915Z_wqfs6cwg/report.md).
