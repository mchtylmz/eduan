# Eduan — Canlı yayın uygulama planı (3 Kasım 2026, v2)

**Durum: paket ve yerel testler hazır; canlıya hiçbir işlem uygulanmadı. Dosyalar/medya merkezi bu yayında yok.** Bu belge sırayla uygulanır; her adımın beklenen sonucu görülmeden sonraki adıma geçilmez. Komut blokları otomatik çalıştırılacak tek script değildir.

## Durum tablosu

| İş | Güncel durum |
|---|---|
| Son genel yerel PHP koşusu | 1127 test / 6583 doğrulama başarılı |
| Ek panel rehberi HTTP/DB | Ayrı 8 test / 50 doğrulama başarılı |
| Son dönem yok/NULL düzeltmesi | Sonradan dar 17 test / 216 doğrulama başarılı |
| Son yedekleme public_html uyumu | 19 Python fixture testi ve Bash syntax başarılı |
| Frontend / Chrome | Önceki 53 Node; çalışma takibi 120 hücre ve ek işlemler başarılı |
| Üretim bağımlılıkları | Son audit temiz; ayrı kopyada 98 paket, --no-dev ve PHP8.5 platform başarılı |
| public_html paket denemesi | Ayrı no-dev kopyada package:discover, yollar, down/up ve bu tur config:cache/route:cache/view:cache başarılı; güncel arşiv ayrıca hash/CRC ile doğrulanır |
| Canlı PHP, .env, DB, migration, görevler | Bu tur sunucudan okunmadı; aşağıdaki envanterle doğrulanacak |
| Gerçek R2 yükleme / restore | Bu tur yapılmadı; geçmiş kullanıcı başarısı yeni paket denemesi değildir |

Bu tur uygulama test suite’i tekrar çalıştırılmadı; değişiklik doküman/paket hazırlığıdır. Bu test sayıları farklı aşamaların kanıtıdır; hepsi yeniden tek suite olarak koşturulmadı. Yerel kabul kontrollü aktarım için yeterlidir; üretim kabulü aşağıdaki işlemlerden sonra verilir.

# A — Canlı yayın öncesi hazırlık

## A1. İlk yayın komutu: anahtarlı bakım

Mevcut canlı Laravel kökünde çalıştır. PHP yolu önceki sunucu kaydından alınmıştır; binary bulunamazsa ilerleme, doğru kurulu PHP yolu belirlenmelidir. Eski uygulama henüz PHP8.5 ile açılmıyorsa ilk down komutu mevcut çalışan PHP ile uygulanır; yeni paket için web/CLI8.5 zorunludur.

```sh
/usr/bin/php85 -d extension=imagick.so artisan down --secret="<BAKIM_ANAHTARI>" --retry=60
```

Kendi tarayıcında `https://hypotenuse.be/<BAKIM_ANAHTARI>` aç: bakım geçiş çerezi verir, normal hesap girişi yine gerekir. Anahtarsız gizli pencerede 503, kendi tarayıcında giriş ekranı beklenir. Anahtar paylaşılmaz; up sonrası geçiş sağlamaz. Paket checklist'inde özel anahtar doldurulmuştur; kaynak rehberde sır tutulmaz.

## A2. Çalışan görevleri dondur ve ekran görüntülerini kaydet

- Site Tools → Devs → Cron Jobs: mevcut bütün komutlar/saatler/timezone ekran görüntüsü; özellikle scheduler, queue, DB ve project backup. Kurulu görev varsa yeniden eklenmez.
- Uygulama scheduler/worker cronlarını duraklat, devam eden işlemlerin bitmesini bekle; kuyruk kayıtlarını silme, başarısız işleri topluca tekrar oynatma. Sunucu aktiflik/süre bilgisi belirsizse process sonlandırma yapılmaz.
- PHP Manager: web PHP sürümü ve modüller; File Manager: gerçek Laravel kökü ve document root. SSH `pwd -P` kaydı. Buradaki yollar önceki sunucu kaydıdır, mevcut kurulumla eşleştir.
- Aktif/duraklatılmış görev ve bakım durumu yayın günlüğüne yazılır. Rutin backup cron da kod dosyaları taşınırken tutarsız arşiv üretmemesi için kontrollü duraklatılır.

## A3. Kod yüklemeden ÖNCE tam yedek

- Tam canlı DB: bütün tablolar, şema, veriler, logs satırları, trigger/routine/event. SiteGround/phpMyAdmin tam export veya mevcut doğrulanmış tam dump yöntemi kullanılır. İçe aktarım/temizleme yapılmaz.
- Bütün eski proje: kod, vendor, .env, storage, public_html/uploads, icons, manifest, .htaccess/.user.ini/php.ini, .well-known ve varsa symlink hedefleri. Proje dışındaki symlink hedef verileri ayrıca yedeklenir; ZIP yalnız linki saklayabilir.
- Yedek web kökü DIŞINDA saklanır, yerel bilgisayara indirilir; SQL boş değil, sıkıştırma/ZIP bütünlüğü ve boyut/hash kontrol edilir. Disk alanı mevcut proje + yedek + staging + vendor kurulumuna yetmeli.
- Eski kodu silerek başlama: geri dönüş kopyası ve doğrulanmış DB yedeği saklanır. Sadece uploads korumak yeterli değildir.

**Rutin R2 DB scripti logs satırlarını dışlar. Bu yayın için alınacak tam DB yedeğinin yerine geçmez.** Proje ZIP'inde DB sunucusunun kendisi bulunmaz.

## A4. Sunucu araçları ve PHP kabulü

```sh
pwd -P
/usr/bin/php85 -v
/usr/bin/php85 -d extension=imagick.so -r 'echo "PHP=", PHP_VERSION, PHP_EOL; foreach (["imagick","pdo_mysql","mbstring","curl","openssl","fileinfo","gd","zip","dom","xml","xmlreader","xmlwriter","simplexml","pcntl"] as $e) { echo $e, "=", extension_loaded($e) ? "OK" : "MISSING", PHP_EOL; }'
command -v composer
command -v aws
aws --version
command -v mysqldump
mysqldump --version
command -v zip
command -v unzip
command -v openssl
date -u
```

Web PHP8.5 ayrıca SiteGround'da kontrol edilir; terminali değiştirmek web PHP'yi değiştirmez. Imagick komut bayrağı yalnız o sürece uygulanır. pcntl worker timeout kontrolü için kontrol edilir. Eksik modülde sırf ilerlemek için ignore-platform-reqs kullanılmaz. Sunucuya kalıcı phpinfo dosyası bırakılmaz.

Gerçek kök için bu belgede `/home/u2930-h840qws8g2us/www/hypotenuse.be`, backup scripti için `/home/u2930-h840qws8g2us/backup-to-r2.sh` kullanıldı. SiteGround farklı canonical yol gösteriyorsa bütün ilgili yollar birlikte güncellenir. `.htaccess`, `.user.ini`, PHP ini ve document root yeni PHP'ye uygun olmalıdır; yerel server dosyaları pakette yoktur.

## A5. Canlı .env: korunacaklar, eklenecekler

**Canlı .env aynen esas alınır. Yerel .env veya .env.example canlı .env üzerine kopyalanmaz. Aynı anahtarı iki kez eklemek yerine mevcut satır güncellenir.** Gerçek sırlar sohbete, ekran görüntüsüne veya web köküne konmaz.

| Anahtar | Yayındaki işlem |
|---|---|
| APP_KEY / APP_PREVIOUS_KEYS | Mevcut değerler aynen korunur. key:generate çalıştırılmaz; şifreli ayarlar ve oturum kimlikleri etkilenir. |
| APP_ENV | production olmalı; yoksa ekle, farklıysa bu hedefe getir. OneSignal üretim app seçimi buna bağlıdır. |
| APP_DEBUG | false olmalı. |
| APP_URL | https://hypotenuse.be; alan adı doğru olmalı. |
| APP_PUBLIC_PATH | public_html; yeni pakette public klasörü yok. |
| APP_VERSION | 1.1.42; kök pwa-sw.js ile eşleşmeli. |
| APP_NAME, APP_TIMEZONE, APP_LOCALE/FALLBACK | Canlı marka/dil/timezone korunur. Kod nl/en fallback kullanır; DB timezone ayarı üretimde APP_TIMEZONE'u geçersiz kılabilir. Etkin timezone ayrıca kontrol edilir; UTC/Istanbul varsayımıyla değiştirme. |
| APP_MAINTENANCE_DRIVER/STORE | Etkin sürücüyü kontrol et. Varsayılan file. down dosyaları korunur. Mevcut cache sürücüsünü bakımdayken körlemesine file'a çevirme; mevcut bakım durumu da taşınmalıdır. |
| DB_* / DB_URL / DB_SOCKET | Canlı DB erişimi korunur. Backup db modu açık mysql HOST/PORT/DATABASE/USERNAME/PASSWORD ister; DB_URL/DB_SOCKET override desteklemez. Başka DB'ye sessiz bağlanılmaz. |
| QUEUE_CONNECTION | Mevcut kalıcı kuyruğu koru; bu kurulumun beklenen bağlantısı database. sync kullanımı asenkron iş sözleşmesini değiştireceği için yayın kapısıdır. jobs/failed_jobs tabloları doğrulanır. |
| DB_QUEUE_RETRY_AFTER / REDIS_QUEUE_RETRY_AFTER | Seçilen kuyruk için gerçek worker/job timeout değerinden büyük olmalı. DB varsayılanı90sn, iletişim job timeout60sn. Mevcut daha uzun worker/job varsa buna göre incelenir; rastgele düşürülmez. |
| CACHE_STORE | Mevcut kullanılan driver yeni configde bu isimle okunmalı. Eski .env yalnız CACHE_DRIVER içeriyorsa CACHE_STORE'a aynı geçerli driver geçirilir. Sırf varsayılan diye database seçilmez; database ise cache/cache_locks tabloları gerekir. |
| SESSION_DRIVER, SESSION_*, REDIS_* | Mevcut bağlantı/domain/cookie davranışı korunur. database session ise sessions tablosu gerekir. HTTPS secure cookie / gerçek domain uyumu kontrol edilir. |
| FILESYSTEM_DISK | Mevcut değer korunur. R2 BACKUP için s3'e çevrilmez; uygulama uploads diski yereldir. |
| AWS_ACCESS_KEY_ID / AWS_SECRET_ACCESS_KEY / AWS_ENDPOINT | R2 yedek erişimi; aşağıdaki özel bölüm. Mevcut değer çalışıyorsa tekrar değiştirme. SES veya başka servis aynı anahtarları kullanıyorsa önce çakışma kontrol edilir. |
| AWS_DEFAULT_REGION | Script region=auto değerini kendisi verir; backup için değiştirmek gerekmez. Başka servis ayarı korunur. |
| BACKUP_R2_BUCKET / PREFIX / WORK_DIR | Yedek için zorunlu; mevcut doğruysa koru, yoksa aşağıdaki örneğe göre ekle. |
| AWS_SESSION_TOKEN | Yalnız kullanılan geçici credential gerçekten gerektiriyorsa; normal R2 anahtarı için yeni satır gerekmez. |
| ONESIGNAL_API_KEY | Üretim panelinde şifreli API key kayıtlı DEĞİLSE opsiyonel env fallback. Panelde key varsa ayrıca eklemek gerekmez. |
| ONESIGNAL_LOCAL_API_KEY | Canlı için gerekmez; yerel test app'i. Canlıya yerel ID/key kopyalanmaz. |
| MAIL_* / LOG_* ve kalan mevcut satırlar | Korunur. Üretimde mail ayarları panel/DB'den override edilir; yalnız env değişikliği paneldeki SMTP'yi düzeltmez. Yeni provider gereksinimi yoksa RESEND/POSTMARK/SQS vb. eklenmez. |

Yeni varsayımsal R2_ENABLED, TURNSTILE_SECRET_KEY, STUDY_ENABLED env anahtarları eklenmez: bu isimleri okuyan yeni ayar sistemi yoktur. R2 yedeği yalnız cron/script çağrısıyla çalışır; Turnstile, çalışma takibi, bildirimler ve panel rehberi DB/panel ayarıdır.

## A6. R2 yedek yapılandırması nerede?

- **Uygulama panelinde R2 yedek ayarı yok.** Sunucudaki canlı Laravel kökünün `.env` dosyasında tutulur. Home'a kopyalanmış script bile `--project-dir` ile o .env dosyasını okur; bulunduğu dizinin .env dosyasını okumaz.
- Cloudflare Dashboard → R2 Object Storage: mevcut özel yedek bucket'ını doğrula veya belirle. Bu paket için yeni medya/CDN bucket'ı, Worker veya halka açık alan adı gerekmez; backup bucket'ı özel kalır.
- R2 API erişimi: ilgili bucket ile sınırlı Object Read & Write yetkili Access Key ID / Secret Access Key ve S3 endpoint. HEAD ve tam geri okuma da yapıldığı için yalnız yazma yetkisi yeterli değildir. Endpoint CDN alan adı değildir; bucket'ın kendi S3 endpoint'i alınır.
- Mevcut çalışan bucket/prefix/key varsa tekrar oluşturma. AWS CLI v2 mevcutsa yeniden kurma. Script .env değerlerini kendisi okur; ayrıca aws configure gerektirmez ve AWS_PROFILE'a dayanmaz.

Aşağıdakiler ÖRNEKTİR; köşeli alanlar gerçek private değerlerle doldurulur. Mevcut satırlar yinelenmez. Prefix sonunda / bulunmaz.

```dotenv
APP_PUBLIC_PATH=public_html
AWS_ACCESS_KEY_ID='<R2_ACCESS_KEY_ID>'
AWS_SECRET_ACCESS_KEY='<R2_SECRET_ACCESS_KEY>'
AWS_ENDPOINT='https://<ACCOUNT_ID>.r2.cloudflarestorage.com'
BACKUP_R2_BUCKET='<MEVCUT_PRIVATE_BACKUP_BUCKET>'
BACKUP_R2_PREFIX=hypotenuse
BACKUP_WORK_DIR=/home/u2930-h840qws8g2us/tmp/hypotenuse-backups
```

S3 endpoint'i bucket EU/US jurisdiction kullanıyorsa Cloudflare'ın o bucket için verdiği endpoint ile eşleşmeli. BACKUP_WORK_DIR proje dışında, üst dizini mevcut olmalı; dizin mevcutsa aynı kullanıcıya ait, yazılabilir ve700 olmalı. Script symlink work dizinini reddeder. Sırlar tek satır literal tutulur; ${...} interpolasyonu seçili alanlarda desteklenmez. Script .env'yi source/eval etmez.

# B — Paket aktarımı, kurulum ve migration

## B1. Yeni paketi yükle ve doğrula

Yayın ZIP + CHECKLIST + MIGRATIONS + ENV/CRON örnekleri + hash dosyaları **web kökü dışına** yüklenir. Delivery ZIP varsa önce o dışarıda açılır. Örnekte aktarım dizini yeni/boş bir staging dizinidir; mevcut canlı kökün üzerine unzip yapma.

```sh
sha256sum -c SHA256SUMS.txt
unzip -t eduan-20261103-v2-public-html.zip
unzip eduan-20261103-v2-public-html.zip -d '<YENI_BOS_STAGING_DIZINI>'
cd '<YENI_BOS_STAGING_DIZINI>'
sha256sum -c '<AKTARIM_DIZINI>/FILES.sha256'
```

sha256sum yoksa SiteGround'daki eşdeğer doğrulama aracı belirlenir; hash kontrolü atlanmaz. Paketin kökünde app/vendor vb. için tek Laravel dizin derinliği vardır, web dosyaları public_html altındadır. public_html içine yeniden Laravel projesi açılmaz.

## B2. Staging'e sadece CANLI kalıcı dosyaları yerleştir

Canlı .env/same APP_KEY, bütün storage, public_html/uploads, icons, pwa-manifest.json, .htaccess/.user.ini/php.ini, .well-known ve varsa storage symlink'i korunur. Proje dışındaki kalıcı symlink hedef verileri ayrıca korunur. Storage kopyası bakım down/maintenance.php dosyalarını içermeli; yeni sürüm erken açılmamalı.

Paket vendor, node_modules, tests, SQL dump, gerçek.env, uploads/icons, dolu storage, sunucu PHP/Apache dosyaları ve üretilmiş bootstrap/cache içermez. Boş storage çalışma klasörleri içerir. Eski bootstrap/cache dosyalarını geri kopyalama; eski kod sınıflarını referans edebilir.

```sh
mkdir -p storage/framework/cache/data storage/framework/cache/frontend storage/framework/cache/dashboard storage/framework/cache/security storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
```

SiteGround hesabı/web PHP/cron bu dizinlere yazabilmeli; 777 verme. Mevcut storage symlink'i korunur, hedefi kontrol edilir; körlemesine storage:link komutu çalıştırılmaz. .env yedeği ve private yayın dosyaları public_html içine konmaz.

## B3. .env karşılaştır ve üretim bağımlılıklarını kur

A5'teki anahtarları mevcut canlı .env üzerinde uygula. Composer PHAR yolunu `command -v composer` ile bul; aşağıdaki `<COMPOSER_PHP_DOSYASI>` yerine gerçek PHP/PHAR dosya yolu kullanılır. Shell wrapper çıkarsa doğrudan PHP'ye verme, içindeki gerçek Composer yolu belirlenir.

```sh
/usr/bin/php85 -d extension=imagick.so '<COMPOSER_PHP_DOSYASI>' install --no-dev --prefer-dist --optimize-autoloader --no-scripts --no-interaction
/usr/bin/php85 -d extension=imagick.so '<COMPOSER_PHP_DOSYASI>' check-platform-reqs --no-dev
/usr/bin/php85 -d extension=imagick.so '<COMPOSER_PHP_DOSYASI>' audit --locked --no-dev
/usr/bin/php85 -d extension=imagick.so -r 'require "vendor/autoload.php"; if (class_exists("PHPUnit\\Framework\\TestCase")) { fwrite(STDERR,"DEV PACKAGE FOUND\n"); exit(1); } echo "No PHPUnit\n";'
```

composer update / key:generate / ignore-platform-reqs yok. Eski vendor'a overlay ile dev paket bırakma; yeni staging'de tam vendor kurulur. composer.lock aynı kalmalı. Kaynak varlıklar hazırdır; sırf deploy için npm install/build gerekmiyor.

## B4. Hazır kodu etkin canlı köke taşı; bakım devam etsin

Eski kod/vendor geri dönüş kopyasında tutulur. Yeni app/bootstrap/config/database/lang/resources/routes/scripts ve web kodları tam sürüm olarak aktarılır; silinmiş eski sınıfların kalan kopyaları bırakılmaz. Canlı kalıcı dosyalar ve bakım durumu korunur. Doğrulanmış kopyaları geri yerleştirilmeden eski kod silinmez. Kısmi aktarımda site açılmaz.

Yeni etkin Laravel kökünde:

```sh
/usr/bin/php85 -d extension=imagick.so artisan package:discover --ansi
/usr/bin/php85 -d extension=imagick.so artisan config:clear
/usr/bin/php85 -d extension=imagick.so artisan migrate:status --no-ansi
```

İlk boot'ta settings tablo hatası vb. alınırsa APP_ENV=local yaparak bypass etme; mevcut DB/schema/kök/bağımlılık kontrolüyle durumu çöz. Yanlış DB'de migrate çalıştırma.

## B5. Migration nasıl kontrol edilir?

**Yeni koddan önce eski migrate:status kaydını, yeni koddan sonra güncel kaydı sakla.** Eski durumu A3 yedek aşamasında mevcut çalışan PHP ile kaydet; aşağıdaki PHP8.5 komutları yeni sürüm içindir. Yeni paket 48 uygulama migration kaynağı içerir; Composer'dan settings/meta paket migration'ları da görünür. Hepsi yeni veya eksik değildir.

```sh
/usr/bin/php85 -d extension=imagick.so artisan migrate:status --no-ansi
/usr/bin/php85 -d extension=imagick.so artisan migrate:status --pending --no-ansi
```

- Ran: migration adı DB'de kayıtlı; yeniden çalıştırma.
- Pending: DB kaydı yok; dosya ve mevcut tablo/kolon/index birlikte kontrol edilir. Table already exists / duplicate column/index çıkarsa drop yapma veya migration satırı elle ekleme; dur, mevcut schema ile kayıt farkını incele.
- Migration table not found: mevcut site için beklenmeyen durum; yanlış DB/kök ihtimalini çözmeden ilerleme.
- Eski 0001/2024/2025/2026Ocak-Şubat veya paket create migration'ı Pending görünüyorsa mevcut üretime körlemesine uygulama. `MIGRATIONS.md` bütün dosyaları ve 14 yeni adayın tekil komutlarını içerir.
- Canlı DB henüz okunmadığından gerçek Pending listesi bilinmiyor. Başlangıç çıktısı/kritik tablo-index envanteri yayından önce paylaşılır; yalnız somut liste onaylanınca ilgili komutlar çalıştırılır.

phpMyAdmin'de salt okunur SQL envanteri için `DB-INVENTORY.sql` kullanılır; INSERT/UPDATE/DROP içermez. Migration ve izin eksiklerini gösterir; tam schema diff aracı değildir. `<TABLO>` placeholder içeren sorgu yoktur.

## B6. Migration nasıl tek tek uygulanır?

Her bir adımda yalnız **Pending olduğu ve schema etkisi onaylandığı doğrulanan** dosya için:

```sh
/usr/bin/php85 -d extension=imagick.so artisan migrate --path=database/migrations/2026_10_14_100000_create_study_tracking_tables.php --force
/usr/bin/php85 -d extension=imagick.so artisan migrate:status --path=database/migrations/2026_10_14_100000_create_study_tracking_tables.php --no-ansi
```

İlk komut gerçek DB değişikliği yapar. İkinci komutta Ran görülürse sıradaki onaylı Pending dosyaya geç. Tüm 14 dosyanın tarih sıralı komutları MIGRATIONS.md'de ayrı bloklardadır; bunları tek seferde yapıştırma. Her dosya ayrı migrate çağrısıdır. Permission/data migration'larında Ran görünmesi son rol/ayar sonucunu kanıtlamaz; B7'de doğrulanır. DDL hatası bazı MySQL değişikliklerini bırakabilir; düzeltmeden aynı komutu tekrar tekrar çağırma.

Bu yayın için genel migrate --force, migrate:fresh/refresh/reset, genel db:seed veya migrate --seed önerilmiyor. --pretend salt okunur güvenlik garantisi sayılmaz; data migration PHP gövdesini yürütür. Çeviri izin/settings cache gibi etkiler kaynak incelemesiyle anlaşılır. Rollback komutları otomatik uygulanmaz.

## B7. İzinler, ayarlar ve üretim kimlikleri

- Yetkiler: Yönetici, öğretmen, öğrenci örnekleriyle study:access/view/update; communications:view/send; study:communicate; users:impersonate; eski users:send-email ve newsletter:send kontrol edilir. Genel PermissionSeeder mevcut rolleri yeniden oluşturduğu için çalıştırılmaz.
- users:impersonate migration'ı yalnız web Yönetici rolünü hedefler. Rol adı canlıda farklıysa migration Ran olsa bile izin atanmamış olabilir; ayrı hedefli onay gerekir. Gent/öğretmen başlangıçta bu izni almamalı.
- study:communicate mevcut admin-tip rollere; communications:view/send admin+öğretmen-tip rollere; çalışma paneli öğrenci kapsamı korunur. E-posta/iletişim yetkisinde beklenmeyen rol dağılımında dur.
- Ayarlar → Çalışma takibi: etkinlik/başlangıç günü, geçmiş çalışma politikası, minimum konu cevabı. Yerel örnek admin hedefleri/çalışmaları/öğretmen notları aktarılmaz. Canlı dönem tarihleri panelden kontrol edilir; yerel tarihleri kopyalama.
- Ayarlar → Bildirimler: üretim App ID/origin=https://hypotenuse.be/API key; local alanları canlı yerine kullanılmaz. Panel key şifreli kayıtlıysa aynı APP_KEY ile açılabilmeli. Bildirim açıklama sayfası dört dilde kontrol edilir.
- Ayarlar → Cloudflare Turnstile: Managed site key/secret/hostname; giriş,kayıt,kurtarma,yeni parola,iletişim. Açmadan önce gerçek domain üzerinde giriş testini yap; kullanıcıyı kilitleyecek hatalı ayarla etkinleştirme. Bülten CAPTCHA yok, IP başına60sn/1deneme. Üye test/sınav/çalışma CAPTCHA kapsamı dışıdır.
- Ayarlar → Genel en alt: panel rehberi URL'si; boşsa menü yok, doluysa yalnız web Yönetici rolünde yeni sekmede. Linki kullanıcının gerçek bucket doküman adresiyle gir; kamuya açılacak içeriği ayrıca gözden geçir.
- SMTP panel/DB ayarları, gönderen adresi ve MAIL_MAILER üretim transport'u; log mailer ile gerçek teslimat iddia edilmez.
- AI: yalnız dört mevcut global ayar gptModel=gpt-6-luna, gptEffect=low, gptDeveloperMessage/gptUserMessage solution-v3. Eski dört değeri özel yedekle; gptApiKey, günlük/kullanıcı limitleri ve AnswerAI kayıtları korunur. Ücretli deneme ayrı somut kapsamla yapılır.

## B8. Cache ve çalışma yolu doğrulaması

Migration/ayarlar tamamlandıktan sonra, bakımda:

```sh
/usr/bin/php85 -d extension=imagick.so artisan permission:cache-reset
/usr/bin/php85 -d extension=imagick.so artisan cache:clear
/usr/bin/php85 -d extension=imagick.so artisan cache:clear --store=frontend
/usr/bin/php85 -d extension=imagick.so artisan cache:clear --store=dashboard
/usr/bin/php85 -d extension=imagick.so artisan route:clear
/usr/bin/php85 -d extension=imagick.so artisan view:clear
/usr/bin/php85 -d extension=imagick.so artisan config:cache
/usr/bin/php85 -d extension=imagick.so artisan route:cache
/usr/bin/php85 -d extension=imagick.so artisan view:cache
```

Bu ilk tam aktarım için hedefli yayın adımıdır; normal CRUD kuralı değildir. security cache'i temizleme; limiter/kilitler korunur. Mevcut bakım sürücüsü cache ise kullandığı store'u bu sırada flush etme: bakım durumunu koruyan plan netleşmeden cache:clear uygulanmaz. Hata çıkarsa site bakımda kalır, error görmezden gelinmez. optimize:clear ile bütün store/kilitleri körlemesine sıfırlama yok.

Etkin yapılandırmayı sır dökmeden göster (üretim bağımlılığı Tinker mevcut):

```sh
/usr/bin/php85 -d extension=imagick.so artisan tinker --execute='echo json_encode(["environment"=>app()->environment(),"debug"=>config("app.debug"),"url"=>config("app.url"),"version"=>config("app.version"),"public"=>public_path(),"uploads"=>config("filesystems.disks.public.root"),"icons"=>config("filesystems.disks.icons.root"),"timezone"=>config("app.timezone"),"queue"=>config("queue.default"),"cache"=>config("cache.default"),"session"=>config("session.driver"),"maintenance"=>config("app.maintenance.driver")], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES), PHP_EOL;'
/usr/bin/php85 -d extension=imagick.so artisan schedule:list --no-ansi
/usr/bin/php85 -d extension=imagick.so artisan queue:failed --no-ansi
```

Beklenen: production/false/HTTPS/1.1.42; public_html, public_html/uploads ve icons; doğru site timezone'u, mevcut kalıcı queue/cache/session. queue:failed salt listeler, yeniden göndermez. Hatalı config varsa env'den sonra config:clear/config:cache tekrarlanır. Web OPcache SiteGround/web PHP tarafında yenilenir; CLI opcache_reset web sürecini temizlemez. PWA manifest id/start_url ve icon URL'leri korunur, kök pwa-sw.js1.1.42, OneSignal worker /push/onesignal/ ayrı scope kalır.

# C — Görevler, gerçek yedek denemesi ve yayın kabulü

## C1. Sunucudaki backup script kopyasını güncelle

Yayınlanan scripts/backup-to-r2.sh ile cron'un gerçekten çağırdığı home kopyasının eşleşmesini sağla. Önce eski kopyayı yayın yedeğinde tut. Script artık .env APP_PUBLIC_PATH değerini okuyor; public_html uyumu bu kopyada da bulunmalı.

```sh
cp /home/u2930-h840qws8g2us/www/hypotenuse.be/scripts/backup-to-r2.sh /home/u2930-h840qws8g2us/backup-to-r2.sh
sha256sum /home/u2930-h840qws8g2us/www/hypotenuse.be/scripts/backup-to-r2.sh /home/u2930-h840qws8g2us/backup-to-r2.sh
```

İki hash aynı olmalı. bash ile çalıştırılır, source edilmez; executable izni şart değil. İşlem yedek bucket'ına dosya göndermez.

## C2. Önce iki yerel --check kontrolü

```sh
PATH=/home/u2930-h840qws8g2us/.local/bin:/usr/local/bin:/usr/bin:/bin /bin/bash /home/u2930-h840qws8g2us/backup-to-r2.sh db --project-dir /home/u2930-h840qws8g2us/www/hypotenuse.be --check
PATH=/home/u2930-h840qws8g2us/.local/bin:/usr/local/bin:/usr/bin:/bin /bin/bash /home/u2930-h840qws8g2us/backup-to-r2.sh project --project-dir /home/u2930-h840qws8g2us/www/hypotenuse.be --check
```

Her ikisi Local check OK vermeli. --check DB sorgusu veya R2 ağ isteği yapmaz; anahtarın R2'de doğru olduğunu kanıtlamaz. İş klasörü/endpoint/key/DB/araç hatası çözülmeden gerçek deneme yapılmaz. Script Bayat kilit hatasında lock otomatik silinmez; aktif süreç kontrol edilir.

## C3. Gerçek R2 yükleme denemesi yapılacak mı?

**Evet, yeni sunucu yapılandırmasının kabulünde iki kontrollü deneme planlanır: db ve project. Bu belgeyi hazırlarken çalıştırılmadı.** Gerçek komut dump/arşiv oluşturur, R2'ye yükler ve nesneyi tamamen geri okuyarak hash doğrular. Canlı verilerde değişiklik yapmaz; doğrulanan yalnız kendi yerel arşivini kaldırır, R2 nesnesi kalır. Ücret/ağ/disk etkisi vardır; gerçek uygulama ayrıca somut onayla yapılır. Manuel indirilmiş tam DB/dosya yedeği A3'te zaten alınmış olmalıdır.

```sh
PATH=/home/u2930-h840qws8g2us/.local/bin:/usr/local/bin:/usr/bin:/bin /bin/bash /home/u2930-h840qws8g2us/backup-to-r2.sh db --project-dir /home/u2930-h840qws8g2us/www/hypotenuse.be
PATH=/home/u2930-h840qws8g2us/.local/bin:/usr/local/bin:/usr/bin:/bin /bin/bash /home/u2930-h840qws8g2us/backup-to-r2.sh project --project-dir /home/u2930-h840qws8g2us/www/hypotenuse.be
```

Her komut ayrı tamamlanır: Backup verified and local archive removed ve ilgili db/...sql.gz / project/...zip nesne yolu beklenir. Başarısızsa arşiv/hata logu korunur; başarısız çıktıyla ikinci koşuya geçme. R2 panelinde nesne/boyut/zaman doğrulanır. Project ZIP büyüktür ve .env sırlarını içerir; bucket private kalmalı. Scriptte otomatik remote retention/silme yok; bu yayında eklenmez.

Geri okunan hash gerçek restore testi değildir. Restore kabulü için ayrı boş test DB/dizini seçilir; gerçek canlı DB üzerine import yapılmaz. Restore çalıştırılmadıysa yayın günlüğünde açık olarak kaydedilir.

## C4. SiteGround'a yazılacak dört cron

Site Tools → Devs → Cron Jobs. Mevcut görevler varsa yalnız güncelle/yeniden etkinleştir; kopyalarını ekleme. Site Tools Command alanına yalnız komut, zaman alanlarına tabloda verilen değerler yazılır. Hedef desenler mevcut proje kararından: scheduler/worker her dakika; DB02:00UTC, proje03:00UTC. Güncel sunucu timezone'u `date -u` ve panelle karşılaştırılmalı; mevcut farklı saat kararı varsa sessiz değiştirilmez.

| Görev | Dakika | Saat | Gün | Ay | Hafta günü | İfade |
|---|---:|---:|---|---|---|---|
| Laravel scheduler | * | * | * | * | * | * * * * * |
| Kuyruk worker | * | * | * | * | * | * * * * * |
| DB → R2 | 0 | 2 | * | * | * | 0 2 * * * (UTC) |
| Proje ZIP → R2 | 0 | 3 | * | * | * | 0 3 * * * (UTC) |

**1. Scheduler:**
```sh
cd /home/u2930-h840qws8g2us/www/hypotenuse.be && /usr/bin/php85 -d extension=imagick.so artisan schedule:run
```

**2. Kuyruk:**
```sh
cd /home/u2930-h840qws8g2us/www/hypotenuse.be && /usr/bin/php85 -d extension=imagick.so artisan queue:work --stop-when-empty
```

**3. Günlük DB yedeği:**
```sh
PATH=/home/u2930-h840qws8g2us/.local/bin:/usr/local/bin:/usr/bin:/bin /bin/bash /home/u2930-h840qws8g2us/backup-to-r2.sh db --project-dir /home/u2930-h840qws8g2us/www/hypotenuse.be >> /home/u2930-h840qws8g2us/tmp/hypotenuse-backups/db-cron.log 2>&1
```

**4. Günlük proje ZIP:**
```sh
PATH=/home/u2930-h840qws8g2us/.local/bin:/usr/local/bin:/usr/bin:/bin /bin/bash /home/u2930-h840qws8g2us/backup-to-r2.sh project --project-dir /home/u2930-h840qws8g2us/www/hypotenuse.be >> /home/u2930-h840qws8g2us/tmp/hypotenuse-backups/project-cron.log 2>&1
```

Backup log yönlendirmesi için dizin önceden mevcut/700 olmalı (C2 yalnız kontrol eder, oluşturmaz). Mevcut cron'da ek --queue/--timeout/--tries veya log seçenekleri varsa kaydedip aynı iş kuralıyla koru; özellikle job timeout ile retry_after uyumunu doğrula. queue:work --force ile bakım engelini kaldırma. Her dakika worker'ın tekrar başlatılması önceki işlerin bitmiş olduğunu kanıtlamaz; mevcut süreç/çakışma durumu kontrol edilir.

**Ayrı cron eklenmeyecek uygulama işleri:** schedule:run içinden communications:dispatch her dakika; kullanıcı AI limitleri siteTZ00:00; sonuç dönem ataması15dakikada; default cache saat:15; eski lig görevi saat başı; canlı lig siteTZ00/04/08/12/16/20. Bunların her biri için ikinci bir cron yazma. queue worker e-posta/bildirim ve süre-sonu sınav işleri için gerekir; dakikalık cron gecikmesi mevcut kabul edilmiş davranıştır.

## C5. Anahtarlı canlı kabul ve açılış

- Eski dosya/görsel/PDF URL'leri ve özel storage içerikleri kaybolmamış; yeni upload gerçek izin ve public_html/uploads yoluna gidiyor.
- Giriş/kayıt/parola/iletişim, gerçek Turnstile domaini; yanlış izin ve rate-limit davranışı. Yetkisiz kişiye panel/veri açılmıyor.
- Öğrenci çalışma hedefi bir defa kaydediliyor; panel öğretmeni yalnız kendi öğrencisini düzenliyor; sekme/hafta/rapor/Excel ve not gizliliği.
- Test/sınav sonuçlarında varsayılan Tümü; Dönem yok hem0 hemNULL. Süreli sınav yeniden giriş/bitirme ve kuyrukta süre-sonu tamamlanması.
- Yönetici hesap geçişi/onay/aktif öğrenci-öğretmen sınırı ve turuncu geri dönüş; öğretmen/sıradan kullanıcıya görünmüyor.
- Panel rehberi/SMTP/bildirim/PWA ve dört dilde temel ekranlar. Mevcut gerçek bildirim/SMTP kullanıcı kabulü korunur; yeni gerçek gönderim/ücretli AI gerekiyorsa tek somut test belirlenir, toplu mesaj yollanmaz.
- Cron kayıtları hazır, yedek C2/C3 sonucu kaydedilmiş. Otomatik Laravel işleri bakımdayken normal çalışmaz; gerçek zamanlı yürütme kabulü bakım sonrası ilk dakikada izlenir.

Bütün kritik kontroller geçerse:

```sh
/usr/bin/php85 -d extension=imagick.so artisan up
```

Anahtarsız gizli pencerede giriş/ana sayfa ve birkaç içerik kontrol edilir. Duraklatılmış dört görev kontrollü etkinleştirilir. Yeni kod açık olduğunda ilk scheduler/worker çalışması, failed jobs, bildirim outbox ve DB/project yedek logları izlenir; ilk hata durumunda başarılı kabul verilmez.

## C6. Saklanacak kabul kanıtları ve hata/geri dönüş

- Önce/sonra PHP Manager/document root/cron ekran görüntüleri; sır içermeyen etkin config çıktısı.
- Tam yedeğin adı/boyutu/hash'i ve yerel kopyası; paket SHA256 sonucu; before/after migrate:status ve uygulanan tekil dosya isimleri.
- R2 iki --check ve iki gerçek koşu çıktısı/nesne yolu; ilk planlı loglar. Sır/key/SQL kişisel satırları paylaşılmaz.
- Canlı smoke sonucu, son hata logu/failed jobs ve açılış saati. Yalnız HTTP200 görmek yeterli sayılmaz.

Hata olursa sonraki adıma geçme, bakımda kal. Kod değişiminden önceki hata: yeni kodu etkinleştirme. DB migration veya yeni veri yazımı sonrası hata: eski kod tek başına güvenli rollback sayılmaz; schema/veri uyumluluğunu değerlendir. Tam DB geri yükleme yeni yazılanları kaybettirebilir; ayrıca karar alınmadan import/rollback/reset yapılmaz. Otomatik eski sınıf kuyruğu silme, R2 nesne temizliği veya eski backup kilidi kaldırma yoktur.

## Kaynaklar

Proje: scripts/backup-to-r2.sh, scripts/siteground-cli-cron.example, bootstrap/app.php, config/app/cache/queue/session/services/filesystems.php, 48 uygulama migration'ı ve paket settings/meta migration'ları, Codex62/69/75/88/90/91/92. Güncel yerel kabul raporu önceki test kayıtlarıyla doğrulandı.

- [SiteGround Cron Jobs](https://www.siteground.com/kb/manage-cron-jobs/) — Site Tools yerleşimi.
- [Cloudflare R2 API credentials](https://developers.cloudflare.com/r2/api/tokens/) — bucket kapsamlı Object Read & Write, ID/secret ve endpoint.
- [Cloudflare R2 CLI](https://developers.cloudflare.com/r2/get-started/cli/) — S3 endpoint ile CLI erişimi.

Bu belge sunucuya bağlantı, değişiklik, R2 isteği veya migration yapılmış gibi yorumlanmaz. Uygulama kodu/sürümü bu plan nedeniyle değiştirilmedi.
