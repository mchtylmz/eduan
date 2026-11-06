# 69 — Son testler ve canlı aktarım listesi

**Güncel uygulama sırası — 3 Kasım 2026:** [94 canlı yayın planı](94-CANLI-YAYIN-UYGULAMA-PLANI.md) ve [94 migration envanteri](94-CANLI-YAYIN-MIGRATION-ENVANTERI.md) esas alınır. Anahtarlı bakım, tam yedek, canlı .env farkları, public_html paketi, no-dev kurulum, tekil migration, iki Laravel+iki bağımsız R2 cron ve gerçek yedek kabulü aşamalara ayrılmıştır. Aşağıdaki eski sürüm/test sayıları kendi tarihlerinin kaydıdır; güncel APP_VERSION1.1.42 ve son yerel test özeti94'tedir. Canlı işlem yapılmadı.

7 Ekim 2026. Kullanıcı AI cevaplarının şimdilik başarılı olduğunu bildirdi; mevcut ayarlarla devam edilmesini ve canlı AI ayarlarının aktarım listesine alınmasını istedi. Son talimatla tek başarısız lig başlığı beklentisi düzeltildi ve ilgili senaryo geçti. Mevcut test aşaması tamamlandı; yeni özelliklere geçilebilir. Sunucu/migration ve yayın/geri dönüş hazırlığı kullanıcı kararıyla yayın aşamasına bırakıldı. Yeni özellik geliştirmesi bu test turuna eklenmedi.

## 1. Genel test sonucu

- Sail PHP8.5.8 / PHPUnit13.4.1, ayrı testing DB: **483 test / 3353 doğrulama; 482 başarılı, 1 failure**. Süre73,814s. Error/skipped yok. HTTP stray requests engelli; AI cevapları fake ile sınanır. Bu, tamamı başarılı genel koşu değildir.
- Tek failure: `FrontendSeoUpgradeTest::test_lesson_custom_page_and_league_details_have_one_heading_and_real_sitemap_urls`, satır198. Eski test lig başlığında fixture label'ını bekliyor; güncel controller çevrilmiş Lig Sonuçları + tarih aralığı üretiyor. Yanıt200 ve tekH1 kontrolü geçip title beklentisine ulaşıyor. Test/uygulama bu tur otomatik değiştirilmedi; yalnız bu beklentiyi güncel sözleşmeye uyarlama sorusu kullanıcıya iletildi.
- PWA + LaTeX formatter Node koşusu: **13 test başarılı**, failure/skipped0. Formatter ayrıca9assert içerir.
- Bağımsız backup Bash sözleşmeleri: **16 Python testi başarılı**, sahte MySQL/R2 ve gerçek yerel gzip/ZIP/hash. Gerçek R2 upload/restore yapılmadı.
- `composer check-platform-reqs --no-dev` başarılı. Yerel Sail PHP8.5.8, Imagick3.8.1, GD/ZIP var. Sunucunun güncel web PHP/modül kabulü değildir.

## 2. Tarayıcı kapısı

Chrome, ayrı testing bootstrap ve mevcut sentetik QA hesabı; dış ağ istekleri engelli. Canlı/ana DB veya ücretli API kullanılmaz. Gerçek üretim rol matrisi, zaman sonunda canlı worker ve gerçek cihaz kabulü değildir. İlk QA'da yanlış `/dashboard/ai` adresi404 verdi; proje prefix'i `/admin` olarak doğrulanıp yalnız AI ekranı ayrıca200/default created_at-desc ile geçti. Ardından tamamlanan tam Chrome koşusunda19sayfa ziyareti HTTP200: ana sayfa/ders/test/sınav/blog/iletişim/lig, login/register, profil/favori/çözülenler/istatistik/kişisel lig ve test-sınav detayları/AI paneli. Gerçek testing girişi,390px hesap menüsü açılma/geçişte kapanma/taşma yok ve AI created_at-desc ilk seçimi geçti. JS page error0, HTTP5xx0, ücretli API0. Fixture Gate takviyesi gerçek rol matrisi kanıtı değildir. Kayıt: özel /private/tmp/eduan-final-browser-result.json; üretim süre-sonu worker, gerçek ödeme/AI, gerçek mobil PWA veya bütün CRUD browser kapsamı değildir.

## 3. Canlı AI aktivasyonu — yayın günü yapılacak

Kod aktarımında GPT-6 Luna/Sol seçenekleri, request/result sınıfları, v3 prompt kuralları, JSON/stabilite düzeltmeleri ve yerel KaTeX JS/CSS/fontları birlikte yayımlanır. Son AI listesi created_at/desc varsayılanı da aktarılır.

Canlıdaki **yalnız dört global AI ayarı**:

| Ayar | Hedef |
| --- | --- |
| `gptModel` | `gpt-6-luna` |
| `gptEffect` | `low` |
| `gptDeveloperMessage` | `AiSolutionRequest::developerPrompt()` içindeki solution-v3 sistem metni |
| `gptUserMessage` | `AiSolutionRequest::userPrompt()` içindeki solution-v3 kullanıcı metni |

Yayın planında mevcut dört ayarın özel yedeği alınır; yalnız bu satırlar güncellenip eşleşme denetlenir. Anahtar `gptApiKey`, site günlük limiti ve kullanıcı limitleri korunur. Mevcut AnswerAI kayıtları kullanılmaya devam eder; toplu silme/yeniden üretme veya yerel DB'yi canlıya kopyalama yapılmaz. Bu doküman canlıda işlem yapıldığı anlamına gelmez. Her ajan ücretli API denemesi ayrıca tek soru/tek istek için onaylıdır; aktivasyon otomatik deneme göndermez.

## 4. Kalan yayın adımları

1. **Tamamlandı:** kullanıcı onayıyla tek başarısız lig başlığı beklentisi uyarlandı; ilgili dar koşu 1 test/16 doğrulama başarılı (bölüm6). Genel koşu yeniden çalıştırılmadı.
2. Canlıya geçişte web+CLI PHP8.5/module/Imagick kontrolü. Mevcut scheduler ve queue çağrıları `/usr/bin/php85 -d extension=imagick.so` ile çalışacak; `scripts/siteground-cli-cron.example` hatırlatılacak. Mevcut saat/worker seçenekleri korunur, görev çoğaltılmaz. Şimdi canlı değiştirilmez.
3. Canlı migration ve izin envanteri salt okunur karşılaştırılır. İndeks (`2026_10_05_150000`), translation_sources (`2026_10_07_110000`), öğretmen sonuç izinleri (`2026_10_07_160000`), eksik NL çevirileri (`2026_10_07_160100`) ve users:send-email gibi izinlerin eksikleri somut yayın planında listelenir. Dolu çeviri/değerler korunur; toplu seed/migrate varsayılmaz.
4. Yayın dosyaları/bağımlılıklar/yerel KaTeX fontları, public manifest/ikonlar, APP_VERSION ve root worker eşleşmesi (**1.1.25**; son sınav ekranı revizyonu), config/route/view cache ve geri dönüş planı hazırlanır.
   - **3 Kasım 2026 kullanıcı kararı:** canlı bağımlılık kurulumu `composer.lock` üzerinden `composer install --no-dev --optimize-autoloader` ile yapılır; `composer update` çalıştırılmaz. Composer doğrulanmış PHP8.5 ile çalıştırılır. Kurulumdan sonra `composer check-platform-reqs --no-dev` ve `composer audit --locked --no-dev` kontrol edilir; PHPUnit ve diğer geliştirme bağımlılıklarının kurulu olmadığı doğrulanır. Bu madde plan kaydıdır; şimdi kurulum yapılmaz.
5. Kullanıcı yayın onayından sonra dağıtım, dört AI ayarı ve CLI cron düzenlemesi uygulanır. Kritik HTTP/izin/cache/queue ve süre dolan sınav işi canlıda doğrulanır.

2–5 numaralı adımlar kullanıcı kararıyla yayın aşamasında yapılacak kabul kontrolleridir; şimdi uygulanmaz. Geri dönüş planı yalnız migration uygulamak değildir: önceki kod/bağımlılıklar ve ayarlar, veritabanı yedeği ve gerekirse geri yükleme adımları birlikte hazırlanır. Veri koruma ve geri dönüş etkileri yayın planında belirlenir.

Bağımsız R2 yedekleri kullanıcı tarafından canlıya alınmıştı: DB02:00UTC/proje03:00UTC; PHP/Artisan kullanmaz ve Imagick parametresi gerektirmez. Bu tur yedek cron'u değiştirilmedi. Gerçek restore ve mobil/Safari PWA sınırı ayrıca kabul edilir; önceki geçici kullanıcı PWA kabulü yeni cihaz testi değildir.

## 5. Durum

**Mevcut geliştirme ve test aşaması tamam; yeni özelliklere geçilebilir. Canlı yayın kabulü yayın aşamasında yapılacak.** İlk genel koşu 482/483 başarılıydı; kalan tek beklenti ayrı dar koşuda giderildi. Bu iki koşu, tekrar çalıştırılmış 483/483 genel test sonucu olarak sunulmaz. Uygulama kodu, ana/canlı DB, paketler veya cron değiştirilmedi; commit/deploy/gerçek ücretli API yok. Ayrı testing sunucusu ana uygulama/Docker portlarını değiştirmez; test sonunda yalnız bu geçici QA sunucusu kapatıldı; Sail servisleri açık bırakıldı.

## 6. Onaylı lig başlığı test düzeltmesi

- Değişen test: `tests/Feature/FrontendSeoUpgradeTest.php`. Lig fixture label'ı yerine Flemenkçe Lig Sonuçları etiketi beklenir; başlangıç/bitiş tarih aralığı ve eski label'ın title'a taşınmaması ayrıca kontrol edilir. HTTP200, tek H1, BreadcrumbList ve sitemap kontrolleri korunur. Controller değiştirilmedi.
- Sail PHP8.5.8 / PHPUnit13.4.1, yalnız ayrı testing DB ve transaction: `php vendor/bin/phpunit tests/Feature/FrontendSeoUpgradeTest.php --filter=test_lesson_custom_page_and_league_details_have_one_heading_and_real_sitemap_urls` → **1 test/16 doğrulama başarılı**, 0,557s; failure/error yok. Ücretli API isteği yok.
- Yalnız ilgili senaryo tekrarlandı; daha önce geçen genel PHP, PWA/formatter, backup ve tarayıcı kontrolleri ekonomik doğrulama amacıyla tekrarlanmadı.
- Değişen tek test dosyası için Pint `--test` başarılı; değişen test/dokümanlarda `git diff --check` başarılı.


## 7. Onaylı süreli sınav ekranı revizyonu — 7 Ekim devam

Kullanıcı mevcut sınav sözleşmesini koruyan küçük tasarım revizyonunu, süre çubuğu yenileme hatasını ve güvenli tamamlama/kaydetme davranışlarını onayladı. Bu bölüm önceki genel test koşusundan sonra yapılan geliştirmedir; bölüm1 sonucu yeni kod için genel suite kanıtı değildir.

- `Frontend/Exams/SolveForm` navigasyonu ilk sunucu render'ında hazırlar; child başına wire:init isteği kaldırıldı. İlk boş root atlama ve root/child ilişkisi kontrolü korunur; QUESTION bağlantıları bölüm içinde numaralandırılır. Sol işaret kaydedilmiş cevap varsa tik, boşsa nokta; içerik/PDF ziyareti ayrı göz simgesidir.
- Blade/CSS: sol bölüm menüsü ve sağ soru alanı, açık Cevaplanan/toplam ve Boş sayısı, kaydetme durumu, okunabilir seçenekler, küçük görsel büyütme bağlantısı ve sabitlenebilen işlem çubuğu. Mobilde bölüm menüsü açılır, seçimde kapanır; seçenekler iki sütun olur. Genel site header/footer değiştirilmedi.
- Yeni `public/assets/exam-session.js`: başlangıç/bitiş/sunucu zamanı epoch millis; oran `(expires-now)/(expires-started)` ile yenilemede korunur. İstemci saat dilimi/duvar saati ayrıştırılmaz, yerel geçen süre monotonic performance.now ile ölçülür. Sunucudaki son cevap kabulü ve tamamlama eylemleri yetkili kaynak olarak kalır.
- Kaydedildi yalnız başarılı Livewire render sonrasında gösterilir. Bağlantı hatasında cevap gönderildi varsayılmaz; yenileme önerilir ve tamamlama engellenir. Süre dolması tek tamamlama isteği gönderir; sonuç modalı sunucu finished onayından sonra açılır. Hata otomatik tekrar denenmez; kullanıcı açıkça tekrar deneyebilir. Elle tamamlama hatası da kalan süre dolmadan doğru save eylemiyle tekrar denenebilir.
- Tek aktif deneme, yeniden girişte aynı cevap/deadline, değiştirme/kaldırma, boş soru atlama, süre sonunda kayıtlı cevaplarla tamamlanma ve tamamlanmış denemeden sonra yeni deneme kuralları değişmedi. Yeni DB alanı, süre uzatma/duraklatma veya queue işletim kuralı eklenmedi.
- NL/EN JSON'a yeni arayüz metinleri eklendi; dolu değerler değiştirilmedi. Ana DB çeviri yazımı yapılmadı. Yayın günündeki eksik NL migration'ı mevcut JSON kaynaklarını kullanır; daha önce uygulanmış canlı migration tekrar uygulanmaz, eksikler yayın planında ayrıca değerlendirilir.
- `.env` APP_VERSION ve root PWA worker **1.1.24** oldu. Yayın paketine yeni `assets/exam-session.js`, değişen Blade/component/CSS ve JSON dosyaları dahil edilmelidir.

### Bu geliştirmede değişen dosyalar

| Grup | Dosyalar |
| --- | --- |
| Sınav | `app/Livewire/Frontend/Exams/SolveForm.php`, `resources/views/livewire/frontend/exams/solve-form.blade.php` |
| Görünüm/sayaç | `public/assets/app.css`, yeni `public/assets/exam-session.js` |
| Dil/sürüm | `lang/nl.json`, `lang/en.json`, `.env` yalnız APP_VERSION, `public/pwa-sw.js` yalnız sürüm |
| Test | `tests/Feature/TimedExamTest.php`, yeni `tests/Frontend/ExamSessionTest.cjs` |
| Rehber | `AGENTS.md`, `Codex/16-PROJE-STANDARTLARI.md`, bu69belgesi, `Codex/index.html` |

### Dar doğrulama

- Sail PHP8.5.8 / PHPUnit13.4.1, yalnız transaction ile ayrı testing DB: mevcut TimedExamTest **7 test/45 doğrulama**, eklenen iki gezinme/süre durumu senaryosu **2 test/17 doğrulama**, ilk boş root/geçersiz navigation **1 test/4 doğrulama** başarılı. Toplam10 farklı PHP senaryosu66doğrulama; üç dar koşu, genel suite tekrarı değil.
- Node: **7 ExamSession senaryosu** başarılı (yenilemede75% oran, monotonic zaman, sınırlar, tamamlamadan önce modal yok, başarısız istekte otomatik tekrar yok, kaydetme onayı/hata, elle tamamlama tekrar deneme). Ek olarak yalnız **1 PWA sürüm eşleşmesi** testi geçti.
- İki PHP dosyasında Pint başarılı; scoped git diff --check başarılı. Yeni JS Node tarafından parse edilip çalıştırıldı; Blade gerçek Laravel render ve browser akışında sınandı.
- Browser plugin/skill yok; Playwright ile gerçek Google Chrome fallback. `/exam/qa-session-review/start?lang=nl` HTTP200 ve doğru title, desktop1440x1000/mobil390x844: önceki/sonraki/doğrudan soru seçimi, cevap seçme/kaldırma, reload sonrası çubuğun büyümemesi, mobil açılma/seçimde kapanma, cevap save ağ hatası ve finish disabled, elle tamamlamada sunucu sonrası sonuç modalı geçti. Son görseller1440x1100 ve390x844; görsel yüklendi/kaydedildi metni ayrıca doğrulandı. JS pageerror0/HTTP4xx-5xx0; bilerek abort edilen save isteği hata senaryosudur.
- QA yalnız sentetik sınav/hesap ve mevcut örnek görsel kullanır. Soru/sonuç verileri her GET/POST'ta transaction içinde oluşturulup geri alınır, süre/cevap snapshot'ı yalnız özel /private/tmp dosyasında tutulur. İzin Gate takviyesi gerçek tüm rol matrisi kanıtı değildir. Tema/logo yalnız QA belleğinde referans rengine ayarlandı; save çağrılmadı. Ücretli API/dış istek/canlı/ana DB/migration/commit/deploy yok.
- Özel kanıtlar: `/private/tmp/eduan-exam-session-qa-result.json`, `/private/tmp/eduan-exam-session-desktop.png`, `/private/tmp/eduan-exam-session-mobile.png`. Yalnız geçici QA sunucusu kapatıldı; Sail açık bırakıldı.

### Kapsam dışı bulgu

Mobil QA'da genel site `.side-info` kapalı menüsü (`public/assets/css/main.css`,3136) viewport dışına taşar; sayfa yatay genişliği artar. Sınav workspace kendi sınırları içinde ölçüldü. Header/menu bu talebin kapsamı dışında olduğundan otomatik düzeltilmedi. Gerçek mobil cihaz/PDF ve canlı süre-sonu worker testi bu yeni UI kabulünün parçası değildir.


## 8. Sınav görünümü devamı — nötr durum, tam genişlik ve FR

Kullanıcı mevcut yumuşak tasarımı kabul etti; yeşil cevap tikinin doğruluk izlenimini kaldırmayı, alanı tam genişliğe açmayı ve yeni ekran metinlerinin NL/FR karşılıklarını tamamlamayı istedi. Soruya kaldığı yerden dönme eklenmeyecek; sonuç tasarımı ve süre/deneme kuralları korunur.

- `public/assets/app.css`: 1540px üst sınır kaldırıldı; workspace100% ve mevcut20/15/10px iç boşluklar. Cevaplandı tiki, menü açıklama tiki ve cevaplanan çubuğu aynı nötr `#45506a`; doğru/yanlış ayrımı yok. Süre çubuğu, kaydetme, gezinme ve tamamlama kodu değişmedi.
- `lang/nl.json`: yalnız eksik Kapat=Sluiten eklendi; diğer dolu çeviriler korundu. Yeni `lang/fr.json`: bu ekran ve bileşenin bütün38çeviri anahtarı; kaydetme/hata/süre/seçenek açıklaması, soru/bölüm menüsü, tamamlama onayı, başarılı/başarısız sonuç mesajı ve sonuç/kapat düğmeleri dahil. Türkçe kaynak fallback ve soru görsellerinin kendi dili değişmedi. NL/FR38anahtar taramasında eksik0.
- Yeni Fransızca dil kaydı oluşturulmadı/aktif edilmedi; ana/canlı translations veya languages tabloları değiştirilmedi. Fransızca dosya mevcut dil mekanizmasının kaynağıdır; panelde kaynak taraması uygulandığında veritabanında hiç bulunmayan anahtarlar dosyadan alınır. Tarayıcı mevcut boş/dolu satırları değiştirmez; bu tur veritabanındaki boş çeviriler için ayrı bir doldurma işlemi yapılmadı. Dolu veritabanı çevirileri otomatik ezilmez.
- `.env` yalnız APP_VERSION ve `public/pwa-sw.js` release **1.1.25**. Yayın listesine `lang/fr.json` de eklenir.

### Dosyalar ve dar doğrulama

Değişenler: `public/assets/app.css`, `lang/nl.json`, yeni `lang/fr.json`, `.env` yalnız sürüm, `public/pwa-sw.js` yalnız sürüm, `tests/Feature/TimedExamTest.php`, `AGENTS.md`, `Codex/16-PROJE-STANDARTLARI.md`, bu belge ve `Codex/index.html`.

- Sail PHP8.5.8 / PHPUnit13.4.1, yalnız testing transaction: **2 yeni test/29 doğrulama** başarılı. Aynı soruya yanlış ve doğru seçenek kaydedildiğinde sol navigasyonun HTML/durumunun birebir aynı olduğu; NL/FR arayüz, onay ve tamamlanma metinlerinin çevrildiği kontrol edilir.
- Yalnız **1 PWA APP_VERSION eşleşme Node testi** geçti. Test dosyası Pint ve scoped git diff --check başarılı. Genel suite tekrarı yok.
- Browser eklentisi yok; gerçek Chrome/Playwright fallback. NL1920x1100, FR1920x1100 ve FR390x844: HTTP200/doğru title, workspace1920/390px ve sol0, kendi iç taşması0, tik RGB69/80/106, kayıtlı cevap durumu, mobil seçimde menü kapanması ve çevrilmiş tamamlama dialog/sonuç modalı geçti. JS pageerror0/HTTP4xx-5xx0. PNG'ler gözle incelendi.
- QA her istekte testing transaction ile geri alınır; FR dil kaydı gerektiğinde yalnız bu QA transaction'ında oluşturulur. Gerçek aktivasyon değildir. Özel kanıt `/private/tmp/eduan-exam-refinement-qa-result.json`; görseller `eduan-exam-refinement-nl-1920.png`, `eduan-exam-refinement-fr-1920.png`, `eduan-exam-refinement-fr-390.png` aynı private/tmp altında. Ana/canlıDB, migration, ücretli API, commit/deploy yok; geçici QA sunucusu kapatıldı, Sail açık kaldı.
