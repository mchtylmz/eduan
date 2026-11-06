# Eduan 6 Kasım 2026 — v4 yayın paketi ve uygulama sırası

Bu, 3 Kasım'da yayımlanan kurulumun güncellemesidir; ilk kurulum değildir. Dosyalar, sonraki güvenlik/arayüz düzeltmeleri ve 98'deki iki canlı log hatası dahildir. Sürüm1.1.43. Kaynak Git commit'i ve bütünlük bilgileri teslim paketinin FILE-MANIFEST.json dosyasındadır. Yerel DB/demolar/env/vendor pakete girmez; public → public_html eşlenir. Canlıya bu belge hazırlanırken işlem uygulanmadı.

**Güncel kabul:** Dosyalar ve önceki güvenlik/log düzeltmeleriyle birlikte son sınav/lig değişikliklerini kapsar. Son iki ayrı dar koşu129test/968assert ve351test/1264assert başarılı; bu480farklıtest/2232assert aynı tek suite koşusu değildir. Önceki32izolePHP/5841assert Amsterdam kış/yaz/DST dakika matrisini kapsar. Chrome dört dil/1440-390 ekranlarda10akış başarılı; JS0/5xx0/taşma0. Ek gerçek web kontrolünde konu araması500bulundu; kullanıcı onayıyla ListTopics ders bağlamı isteğe bağlı yapıldı. Genel arama tüm aktif konuları, ders sayfası kendi konularını getirmeye devam eder. Son ilgili28test/89assert geçti (önceki komşu testleri tekrar içerir);21gerçek web isteği200 ve sitemap geçerliXML. Güncel üretim audit0 ve PHP8.5.12 no-dev platform başarılı. Ayrıntı100 ve TEST-REPORT.md. Üretim migration/deploy/gerçek R2 bu hazırlıkta yapılmadı. Geliştirme paketleri --no-dev ile dışarıda kalır.

Bu v4 paketi **henüz canlıya alınmamış v3 paketinin tamamının yerine** kullanılır. V3 önce kurulmaz; v3 staging/release/yedekleri silinmez. Yeni commit ve SHA dosyası v4'e aittir. Uygulama/asset sürümü1.1.43 korunur; yalnız paket adı değişir.

**Kısa aşamalar:** 1bakım →2cron/yol/PHP →3tam yedek/indirme →4v4 doğrulama/staging →5canlı env/runtime kopyası →6no-dev Composer →7tek migration kontrolü/onay →8önizleme/aktarım →9cache/OPcache/upload →10web/log kabul →11cron/yedek ayarı →12up/izleme. Her komut bloğu sırayla çalıştırılır; hata halinde devam edilmez.

Komut bloklarını aynı SSH oturumunda, adım adım çalıştır. Hata çıktığında sonraki adıma geçme; çıktıyı incele, siteyi bakımda tut. Paket hazırlama izni, bu belgedeki canlı migration/silme/dış servis işlemlerinin otomatik yürütülmesi değildir. İlgili adımın somut kapsamı onaylanır. Test veya eski failed job kayıtları bu yayında topluca silinmez.

## 1. İlk işlem: anahtarlı bakım

```bash
cd /home/u2930-h840qws8g2us/www/hypotenuse.be
umask 077
release_secret=$(/usr/bin/php85 -r 'echo bin2hex(random_bytes(24));')
/usr/bin/php85 -d extension=imagick.so artisan down --secret="$release_secret" --retry=60
printf 'Yalnız kendi tarayıcında aç: https://hypotenuse.be/%s\n' "$release_secret"
```

Anahtarlı adres bakım geçiş çerezi verir; normal hesap girişi yine gerekir. Anahtarsız gizli pencerede503, kendi tarayıcında site beklenir. Anahtarı paylaşma. Mevcut yönetici oturumunu kapatmadan anahtarlı erişimi doğrula.

## 2. Cronları kaydet ve duraklat; yolları/araçları doğrula

SiteGround → Devs → Cron Jobs: mevcut beş kaydın ekran görüntüsünü al; geçici duraklat. Çalışmakta olan scheduler/worker/yedek süreçleri varsa bitmesini bekle. Kuyruk kayıtlarını silme, çalışan süreçleri körlemesine sonlandırma.

```bash
export release_www=/home/u2930-h840qws8g2us/www
export release_live_dir="$release_www/hypotenuse.be"
export release_delivery_dir="$release_www/eduan-release-20261106-v4"
export release_stage_dir="$release_www/eduan-staging-20261106-v4"
export release_backup_dir="$release_www/eduan-backup-20261106-v4"
cd "$release_live_dir"
pwd -P
/usr/bin/php85 -v
/usr/bin/php85 -d extension=imagick.so -r 'foreach (["imagick","pdo_mysql","mbstring","curl","openssl","fileinfo","gd","zip","dom","xml","xmlreader","xmlwriter","simplexml","pcntl"] as $e) {echo $e,"=",extension_loaded($e)?"OK":"MISSING",PHP_EOL;}'
test -f /usr/local/bin/composer.phar
/usr/bin/php85 -d extension=imagick.so /usr/local/bin/composer.phar --version
command -v rsync
command -v tar
command -v unzip
mysqldump --version
df -h .
grep -E '^(APP_ENV|APP_DEBUG|APP_URL|APP_PUBLIC_PATH|APP_VERSION|APP_TIMEZONE|APP_MAINTENANCE_DRIVER|CACHE_STORE|QUEUE_CONNECTION|SESSION_DRIVER)=' .env
```

Web PHP de SiteGround PHP Manager'dan8.5 olmalı. Önceki sunucu kaydı `/usr/bin/php85` ve Composer PHAR yolunu esas alır; bulunamazsa dur. `/usr/local/bin/composer` bir shell wrapper'dı; PHP'ye o wrapper verilmez. Eksik modülü ignore-platform-reqs ile geçme. Gerçek kök `/home/customer/www/hypotenuse.be` görünebilir; kullanıcı yolu aynı yere çözülmeli. Staging/yedek için boş alan gerekir; mevcut klasörleri temizleyerek alan açma.

## 3. Kod yüklemeden ÖNCE tam DB ve tam proje yedeği

Yedek web kökü dışında, www altında ayrı700 dizinde tutulur. Bu dizin zaten varsa üstüne yazma; eski denemenin durumunu incele. Aşağıdaki DB işlemi yalnız dump alır; import yapmaz. Mevcut R2 DB scripti logs satırlarını dışladığı için tek başına tam yayın yedeğinin yerine kullanılmaz.

```bash
(
set -e
umask 077
cd "$release_live_dir"
test -f storage/framework/down
mkdir "$release_backup_dir"
chmod 700 "$release_backup_dir"
/usr/bin/php85 -r '
require "vendor/autoload.php";
$e=Dotenv\Dotenv::parse(file_get_contents(".env"));
if (($e["DB_CONNECTION"]??"mysql")!=="mysql" || !empty($e["DB_URL"]) || !empty($e["DB_SOCKET"])) throw new RuntimeException("DB baglantisi farkli; dump yontemi incelenmeli.");
foreach (["DB_HOST","DB_DATABASE","DB_USERNAME","DB_PASSWORD"] as $k) if (!isset($e[$k])) throw new RuntimeException("Eksik DB alani.");
$quote=fn($s)=>"\"".strtr($s,["\\"=>"\\\\","\""=>"\\\"","\n"=>"\\n","\r"=>"\\r"])."\"";
$cnf="[client]\n";
foreach (["host"=>$e["DB_HOST"],"port"=>$e["DB_PORT"]??"3306","user"=>$e["DB_USERNAME"],"password"=>$e["DB_PASSWORD"]] as $k=>$v) $cnf.=$k."=".$quote($v)."\n";
$root=getenv("release_backup_dir");
if (!is_dir($root)) throw new RuntimeException("Yedek dizini yok.");
file_put_contents($root."/mysql-client.cnf",$cnf,LOCK_EX);
chmod($root."/mysql-client.cnf",0600);
file_put_contents($root."/database-name.txt",$e["DB_DATABASE"],LOCK_EX);
'
release_db_name=$(cat "$release_backup_dir/database-name.txt")
mysqldump --defaults-extra-file="$release_backup_dir/mysql-client.cnf" \
  --single-transaction --quick --routines --events --triggers \
  --no-tablespaces --set-gtid-purged=OFF --column-statistics=0 \
  --databases "$release_db_name" > "$release_backup_dir/database-before.sql"
test -s "$release_backup_dir/database-before.sql"
gzip -c "$release_backup_dir/database-before.sql" > "$release_backup_dir/database-before.sql.gz"
gzip -t "$release_backup_dir/database-before.sql.gz"
find "$release_live_dir" -type l -ls
tar -czf "$release_backup_dir/project-before.tar.gz" -C "$release_live_dir" .
tar -tzf "$release_backup_dir/project-before.tar.gz" > "$release_backup_dir/project-contents.txt"
cd "$release_backup_dir"
sha256sum database-before.sql.gz project-before.tar.gz > BACKUP.sha256
sha256sum --strict -c BACKUP.sha256
)
```

Dump yetki hatası verirse dur: tam DB export SiteGround/phpMyAdmin üzerinden alınabilir; başarısız dump tam yedek sayılmaz. Symlink hedefi proje dışındaysa o hedefin verisi ayrıca yedeklenir. Yedek .env/kişisel veri içerir; private tutulur. SQL.gz, proje.tar.gz ve BACKUP.sha256 dosyalarını SFTP ile bilgisayarına indir, hashleri doğrula. Yalnız ZIP içindeki uploads değil tüm storage/env/vendor/sunucu dosyaları korunur. Hiçbir eski proje dosyasını bu aşamada silme.

## 4. Teslim ZIP'ini yükle, doğrula ve ayrı staging'e aç

Bilgisayardan `eduan-20261106-v4-delivery.zip` ve aynı adlı `.sha256` dosyasını **www** içine SFTP ile yükle. public_html içine yükleme. Hash kontrolü olmadan açma.

```bash
(
set -e
umask 022
cd "$release_www"
sha256sum --strict -c eduan-20261106-v4-delivery.zip.sha256
mkdir "$release_delivery_dir"
unzip -q eduan-20261106-v4-delivery.zip -d "$release_delivery_dir"
cd "$release_delivery_dir"
sha256sum --strict -c SHA256SUMS.txt
mkdir "$release_stage_dir"
unzip -q eduan-20261106-v4-public-html.zip -d "$release_stage_dir"
cd "$release_stage_dir"
sha256sum --strict --quiet -c "$release_delivery_dir/FILES.sha256"
test -f public_html/index.php
test ! -d public
test ! -e .env
test ! -e vendor
printf 'Staging paket dosyalari dogrulandi.\n'
)
```

Dosya adı/yol çakışırsa eski release/staging/yedek klasörünü silme; önce durumunu belirle. Package .env, demo uploads, SQL, vendor, node_modules, yerel ikon/manifest veya sunucu php.ini içermez. Kod doğrudan canlıya unzip edilmez.

## 5. Canlı .env ve runtime verilerini staging'e kopyala

```bash
(
set -e
umask 077
test -f "$release_live_dir/storage/framework/down"
test ! -e "$release_stage_dir/.env"
cp -a "$release_live_dir/.env" "$release_stage_dir/.env"
chmod 600 "$release_stage_dir/.env"
cp -a "$release_live_dir/storage/." "$release_stage_dir/storage/"
for release_path in uploads icons storage .well-known .htaccess .user.ini php.ini pwa-manifest.json; do
  if test -e "$release_live_dir/public_html/$release_path" || test -L "$release_live_dir/public_html/$release_path"; then
    cp -a "$release_live_dir/public_html/$release_path" "$release_stage_dir/public_html/"
  fi
done
cd "$release_stage_dir"
/usr/bin/php85 -r '
$p=".env";$s=file_get_contents($p);
if (preg_match_all("/^APP_VERSION=/m",$s)>1) throw new RuntimeException("Birden fazla APP_VERSION var.");
if (preg_match("/^APP_VERSION=/m",$s)) $s=preg_replace("/^APP_VERSION=.*$/m","APP_VERSION=1.1.43",$s);
else $s=rtrim($s)."\nAPP_VERSION=1.1.43\n";
file_put_contents($p,$s,LOCK_EX);
'
)
```

Bu env düzenleme komutu yalnız APP_VERSION satırını değiştirir, henüz vendor gerektirmez. Yeni zorunlu env anahtarı yok. **ENV.production-changes.example** dosyasında yalnız bu sürüm değişikliği ve mevcut doğrulanması gereken production/false/HTTPS/public_html değerleri anlatılır. APP_KEY, DB_*, mail/bildirim/Turnstile ve yedekleme anahtarlarını aynen koru; key:generate veya yerel env kopyası kullanma. Etkin APP_PUBLIC_PATH=public_html/APP_MAINTENANCE_DRIVER=file değilse dur ve mevcut duruma göre planı doğrula.

## 6. Staging içinde temiz üretim bağımlılıklarını kur

```bash
(
set -e
umask 022
cd "$release_stage_dir"
/usr/bin/php85 -d extension=imagick.so /usr/local/bin/composer.phar install \
  --no-dev --prefer-dist --optimize-autoloader --no-scripts --no-interaction
/usr/bin/php85 -d extension=imagick.so /usr/local/bin/composer.phar check-platform-reqs --no-dev
/usr/bin/php85 -d extension=imagick.so /usr/local/bin/composer.phar audit --locked --no-dev
/usr/bin/php85 -d extension=imagick.so -r 'require "vendor/autoload.php"; if (class_exists("PHPUnit\\Framework\\TestCase")) throw new RuntimeException("Dev paketi uretim kurulumunda var."); echo "no-dev autoload OK\n";'
/usr/bin/php85 -d extension=imagick.so artisan package:discover --ansi
)
```

Composer update çalıştırılmaz. --no-scripts sonrası package discovery elle ve kontrollü yapıldı; vendor publish --force çalıştırılmaz. Kurulum/audit hatasında canlı dosyalara geçme. Staging config/route cache canlı kök için üretilmez.

## 7. Canlı DB'deki Pending migration'ı kontrol et

```bash
(
set -e
cd "$release_stage_dir"
/usr/bin/php85 -d extension=imagick.so artisan migrate:status --pending --no-ansi
/usr/bin/php85 -d extension=imagick.so artisan tinker --execute='
echo json_encode([
 "db"=>DB::connection()->getDatabaseName(),
 "source_table_exists"=>Schema::hasTable("source_files"),
 "admin_roles"=>DB::table("roles")->where("name","Yönetici")->where("guard_name","web")->get(["id","name","guard_name"])
],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE),PHP_EOL;'
)
```

Önceki canlı14 migration uygulanmıştı. Beklenen yeni aday yalnız `2026_11_04_150000_create_source_files_table.php`: source_files tablosu, üç files:view/add/delete izni ve yalnız mevcut web Yönetici rolüne atama. Çalışma/kullanıcı verilerini değiştirmez. Canlı DB adı önceki kayıtta dbjl8pylrssuae; farklı DB veya beklenmeyen Pending listesinde dur. Tablo mevcut ama migration Pending ise körlemesine çalıştırma. Migration Ran ise tekrar uygulama.

**Yalnız bu somut canlı migration kapsamı onaylandıktan sonra**, Pending ve tablo yoksa:

```bash
(
set -e
cd "$release_stage_dir"
/usr/bin/php85 -d extension=imagick.so artisan migrate \
  --path=database/migrations/2026_11_04_150000_create_source_files_table.php --force
/usr/bin/php85 -d extension=imagick.so artisan migrate:status \
  --path=database/migrations/2026_11_04_150000_create_source_files_table.php --no-ansi
)
```

Beklenen Ran. Genel migrate/seed/fresh/rollback yok. Local DB veya demo verisi canlıya import edilmez. Rol adı/yetki dağılımı farklıysa ayrıca incelenir; başka rollere sessiz izin verilmez.

## 8. Aktarım önizlemesi ve kod aktarımı

Önceki tam yedek ve migration sonucu doğrulanmış olmalı. Uygulama dizinleri/public kaynakları güncellenir; .env, canlı storage/uploads/icons/manifest/.htaccess/ini/log/webstats korunur. Kod tarafında bu güncelleme için genel --delete kullanılmaz. **Yalnız vendor'da --delete**, temiz no-dev kopyayla eşitlemek ve dev/eski paket kalıntılarını bırakmamak içindir; önizlemede görülen silmelerin somut kapsamı onaylanır.

Önce önizleme:

```bash
(
set -e
test -f "$release_live_dir/storage/framework/down"
for release_dir in app config database lang resources routes scripts Codex; do
  rsync -rltni "$release_stage_dir/$release_dir/" "$release_live_dir/$release_dir/"
done
rsync -rltni --exclude=/cache/ "$release_stage_dir/bootstrap/" "$release_live_dir/bootstrap/"
rsync -rltni --exclude=/uploads/ --exclude=/icons/ --exclude=/storage \
  --exclude=/.well-known/ --exclude=/.htaccess --exclude=/.user.ini --exclude=/php.ini \
  --exclude=/pwa-manifest.json "$release_stage_dir/public_html/" "$release_live_dir/public_html/"
rsync -rltni --delete "$release_stage_dir/vendor/" "$release_live_dir/vendor/"
for release_file in artisan composer.json composer.lock package.json vite.config.js server.php README.md AGENTS.md; do
  rsync -rltni "$release_stage_dir/$release_file" "$release_live_dir/$release_file"
done
)
```

Önizleme incelenip **aktarım ve yalnız vendor silme kapsamı onaylandıktan sonra**:

```bash
(
set -e
umask 022
test -f "$release_live_dir/storage/framework/down"
for release_dir in app config database lang resources routes scripts Codex; do
  rsync -rlti "$release_stage_dir/$release_dir/" "$release_live_dir/$release_dir/"
done
rsync -rlti --exclude=/cache/ "$release_stage_dir/bootstrap/" "$release_live_dir/bootstrap/"
rsync -rlti --exclude=/uploads/ --exclude=/icons/ --exclude=/storage \
  --exclude=/.well-known/ --exclude=/.htaccess --exclude=/.user.ini --exclude=/php.ini \
  --exclude=/pwa-manifest.json "$release_stage_dir/public_html/" "$release_live_dir/public_html/"
rsync -rlti --delete "$release_stage_dir/vendor/" "$release_live_dir/vendor/"
for release_file in artisan composer.json composer.lock package.json vite.config.js server.php README.md AGENTS.md; do
  rsync -rlti "$release_stage_dir/$release_file" "$release_live_dir/$release_file"
done
cd "$release_live_dir"
/usr/bin/php85 -r '$p=".env";$s=file_get_contents($p); if(preg_match_all("/^APP_VERSION=/m",$s)!==1) throw new RuntimeException("APP_VERSION tekil olmali."); file_put_contents($p,preg_replace("/^APP_VERSION=.*$/m","APP_VERSION=1.1.43",$s),LOCK_EX);'
/usr/bin/php85 -d extension=imagick.so artisan package:discover --ansi
sha256sum --strict --quiet -c "$release_delivery_dir/FILES.sha256"
)
```

Bu payload manifesti korunan runtime/env/vendor dosyalarını içermez. SiteGround web OPcache yeniden yüklenir; CLI opcache_reset web sürecini yenilemez. Mevcut storage symlink'leri üzerine storage:link --force çalıştırılmaz.

## 9. PHP upload ayarları, cache ve sır göstermeyen kabul

SiteGround web PHP etkili değerleri: **upload_max_filesize=100M**, **post_max_size=128M**. `PHP-UPLOAD-ADDITIONS.ini` yalnız bu iki satırı verir. Korunan `public_html/php.ini` veya PHP Manager üzerinden bu değerleri birleştir; diğer extension/memory/session satırlarını değiştirme. CLI ini çıktısı web PHP kanıtı değildir. Proxy/CDN gövde limiti ayrıca geçerli olabilir; gerçek upload ile kontrol edilir.

```bash
(
set -e
cd "$release_live_dir"
test -f storage/framework/down
mkdir -p storage/framework/cache/data storage/framework/cache/frontend \
  storage/framework/cache/dashboard storage/framework/cache/security \
  storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
/usr/bin/php85 -d extension=imagick.so artisan permission:cache-reset
/usr/bin/php85 -d extension=imagick.so artisan cache:clear file
/usr/bin/php85 -d extension=imagick.so artisan cache:clear frontend
/usr/bin/php85 -d extension=imagick.so artisan cache:clear dashboard
/usr/bin/php85 -d extension=imagick.so artisan route:clear
/usr/bin/php85 -d extension=imagick.so artisan view:clear
/usr/bin/php85 -d extension=imagick.so artisan config:cache
/usr/bin/php85 -d extension=imagick.so artisan route:cache
/usr/bin/php85 -d extension=imagick.so artisan view:cache
/usr/bin/php85 -d extension=imagick.so artisan queue:restart
/usr/bin/php85 -d extension=imagick.so artisan tinker --execute='
echo json_encode([
 "environment"=>app()->environment(),"debug"=>config("app.debug"),"version"=>config("app.version"),
 "public_path"=>public_path(),"uploads"=>config("filesystems.disks.public.root"),
 "update_uri"=>app("livewire")->getUpdateUri(),"routes_cached"=>app()->routesAreCached(),
 "maintenance"=>app()->isDownForMaintenance()
],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES),PHP_EOL;'
/usr/bin/php85 -d extension=imagick.so artisan migrate:status --pending --no-ansi
/usr/bin/php85 -d extension=imagick.so artisan schedule:list --no-ansi
/usr/bin/php85 -d extension=imagick.so artisan queue:failed --no-ansi
)
```

cache:clear store adı **positional**; eski --store seçeneği kullanılmaz. Security store flush edilmez. Beklenen production/false/1.1.43/public_html/uploads, `/livewire/update`, cached=true, maintenance=true. Yeni route cache Symfony güvenlik düzeltmesi için zorunludur. storage/views içinde önceki sitemap derlemesi kalmamalıdır.

## 10. Bakım anahtarıyla gerçek web kabulü ve log

- `/sitemap.xml`:200 ve geçerli XML; version parse hatası yok. Short tags sunucu ayarını değiştirmeye gerek yok.
- `/search?tab=lessons`, topics, exams, newspaper: arama parametresi yokken/boşken ve dolu metinde500 yok, ilgili liste açılıyor.
- Giriş: doğru hesap başarılı, yanlış bilgilerde404 yok; Livewire POST `/livewire/update`.
- Çalışma paneli/öğrenci sekmeleri, rapor/Excel/izinler; bildirim kapalıysa ikon/sayfa görünmez. Lig formunda boş tür seçimi; Amsterdam09:00–23:59 her15dakika,00:00–08:59 saat başı açıklaması. Genel kapasite ayarı değiştiğinde geçmiş lig grupları aynı kalmalı; devam eden canlı lig yeni kapasiteyi sonraki başarılı hesaplamada almalı. Sırf smoke için eski ligi yeniden hesaplatma.
- Sınavlar: bölümü ve sorusu olan aktif/pasif sınavlar listelenmeli; pasif detayda “yakında başlayacak” düğmesi disabled olmalı, doğrudan başlatma engellenmeli. Aktif sınavın mevcut izinli başlatma/bitirme akışı korunmalı.
- Dosyalar: yalnız yetkili erişim; local depolama varsayılan. Üç izin yalnız web Yönetici'de olmalı. PDF/HTML/görsel yükle, link kopyala, misafir/new-tab/dönüş ve silme kontrollü kendi test dosyasında doğrulanır.
- HTML `/sources` içinde scriptsiz; özgün bağlantısı attachment+nosniff indirir. Apache kaynak dizininin .htaccess kurallarını uygulamalı; root server.php yalnız yerel Artisan sunucusu içindir.
- Viewer IP+dosya3/dakika, dördüncü429/bekleme; özgün dosya linki sınırsız kalır.
- R2 medya açılacaksa Ayarlar → Dosyalar'dan account/bucket/key/secret/public URL, CORS ve gerçek PUT/Head/Get/Copy/Delete testi ayrı onayla yapılır. Anahtarlar env'de değildir. İlk yayın local ile yapılabilir; fake test gerçek R2 kabulü sayılmaz.

Log dosyası eski kayıtlarıyla saklanır; burada tail yalnız okur, silmez:

```bash
cd "$release_live_dir"
find storage/logs -maxdepth 1 -type f -name '*.log' -printf '%TY-%Tm-%Td %TT %p\n' | sort
tail -n 100 storage/logs/laravel.log
```

Günlük log sürücüsü varsa listede o güne ait gerçek dosyayı aç. Paylaşırken şifre/token/kişisel veri içeren gövdeleri çıkar. Güncellemeden önceki dokuz eski kayıt ile yeni saatli hataları karıştırma; geçmiş logu temizleyerek başarılı görünüm üretme.

## 11. Cron ve yedekleme: mevcut kayıtları koru

Yeni cron yok; mevcutlar güncellenip tekrar etkinleştirilir. `CRON.md` beş mevcut görevin tam komutunu içerir: scheduler ve worker her dakika, DB02:00UTC, proje03:00UTC, mevcut cache temizliği `0 6 */3 * *`. Gerçek SiteGround timezone ve mevcut saatler doğrulanır, kopya görev eklenmez. Canlı lig zamanlaması scheduler kodundadır: **Europe/Amsterdam09:00–23:45 her15dk**, **00:00–08:00 saat başı**.08:15/08:30/08:45 çalışmaz;09:00/09:15 çalışır. Ayrıca lig cron'u ekleme. schedule:list cron ifadesini15dk gösterir; gece koşulu kodda uygulanır, saat dilimi Europe/Amsterdam olmalıdır.

Önce backup script kopyası eşleşmesini ve yerel kontrolleri oku:

```bash
sha256sum "$release_live_dir/scripts/backup-to-r2.sh" /home/u2930-h840qws8g2us/backup-to-r2.sh
PATH=/home/u2930-h840qws8g2us/.local/bin:/usr/local/bin:/usr/bin:/bin \
  /bin/bash /home/u2930-h840qws8g2us/backup-to-r2.sh db --project-dir "$release_live_dir" --check
PATH=/home/u2930-h840qws8g2us/.local/bin:/usr/local/bin:/usr/bin:/bin \
  /bin/bash /home/u2930-h840qws8g2us/backup-to-r2.sh project --project-dir "$release_live_dir" --check
```

Hash farklıysa home kopyasını değiştirmeden önce yedek/kapsam onayıyla incele. Mevcut yedek R2 ayarları canlı .env'de: BACKUP_WORK_DIR/BACKUP_R2_BUCKET/BACKUP_R2_PREFIX ve AWS_ACCESS_KEY_ID/AWS_SECRET_ACCESS_KEY/AWS_ENDPOINT. Önceki başarılı değerler korunur; yeni medya bucket'ıyla karıştırılmaz. --check ağ isteği veya dump yapmaz. Gerçek DB/proje R2 gönderimi ancak ayrıca somut onayla, aynı komutlardan --check kaldırılarak ve iki ayrı adımda yapılır; Backup verified çıktısı beklenir. Bu yayında gerçek yeni R2 gönderimi zorunlu varsayılmadı.

## 12. Son onay: uygulamayı aç ve cronları etkinleştir

Yeni hata yok, kritik web kabulü geçtiyse:

```bash
cd "$release_live_dir"
/usr/bin/php85 -d extension=imagick.so artisan up
```

Site Tools'tan duraklatılan cronları tekrar etkinleştir. Anahtarsız gizli pencerede ana sayfa/giriş/sitemap/arama ve dosya bağlantısı kontrol edilir. İlk scheduler/worker sonrası schedule_run.log, queue_run.log ve yeni Laravel hata kayıtları izlenir. E-posta/bildirim teslimi veya ücretli AI testi bu checklist adına otomatik gönderilmez. Geri dönüş kopyaları ve staging hemen silinmez.

## Hata / geri dönüş sınırı

Hata olursa bakım açık kalır; up yapma. DB migration öncesiyse önceki kod/vendor/env backup'tan geri alma planlanabilir. Migration/yeni veri sonrası eski kodu veya tam SQL'i körlemesine geri yükleme; yeni dosya/kullanıcı verisini kaybettirebilir. Import, migrate:rollback, storage/uploads veya eski yedek silme ayrı onay gerektirir. `source_files` migration down yöntemi otomatik tablo/nesne silmez. Tam yedek ve dosya sayımları saklanır; bu tur bir restore işlemi yapılmadı.
