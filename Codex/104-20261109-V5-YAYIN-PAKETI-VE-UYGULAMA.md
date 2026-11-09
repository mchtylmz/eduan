# Eduan 9 Kasım 2026 — v5 yayın paketi ve uygulama sırası

Bu paket, kullanıcı tarafından 6 Kasım'da canlıya alınan v4 / 1.1.43 üzerine güncellemedir. Hedef sürüm **1.1.46**. Öğretmen hesabına geçişte panel erişimi, çalışma filtreleri, dosya kartları/yükleyici, Cloudflare R2 ayarları ve ortak filtre standardı dahildir. Dosya Ayarları AJAX Kaydet düzeltmesi de içerilir. Eski v4 arşivi değiştirilmez; v4 tekrar kurulmaz.

**Karar: incelenen kod/test kapsamı kontrollü yayına uygun.** İki ayrı temiz koşuda 405 farklı PHP senaryosu / 1997 assertion, 27 Node testi ve 50 başarılı Chrome kapsam kontrolü; JS istisnası / beklenmeyen 5xx / yatay taşma 0. Onaylı son form düzeltmesinin iki testi / 85 assertion ayrıca geçti; bunlar 405'in alt kümesidir. Üretim audit 0; no-dev platform kontrolü başarılı. Ayrıntı [103](103-20261109-SON-GELISTIRMELER-YAYIN-KABULU.md). Gerçek R2, üretim proxy/PHP/OPcache ve canlı kullanıcı kabulü yayın sırasında kontrol edilir; bu belge bunların yapıldığını iddia etmez.

**Bu sürümde yeni migration / SQL / seeder / Composer bağımlılığı / cron yok.** `source_files` migration'ı önceki canlı yayında uygulandı; tekrar çalıştırılmaz. Canlı DB'nin bu turki durumu henüz okunmadı; 7. adım salt okunur kapıdır. `.env` içinde yalnız **APP_VERSION=1.1.46** değişir. APP_KEY, gerçek anahtarlar, uploads, storage ve sunucu ayarları korunur.

**Sıra:** bakım → cron/PHP kontrolü → tam yedek → ZIP doğrulama/staging → canlı env/runtime kopyası → no-dev kurulum → DB durum kontrolü → önizleme ve onaylı aktarım → cache/OPcache → web/log kabul → cron kontrolü → up/izleme.

Her maddenin kısa açıklamasını okuyup komutlarını aynı SSH oturumunda sırayla çalıştır. Hata varsa sonraki adıma geçme. Paket hazırlama talebi canlı aktarım, silme veya dış servise gönderim onayı değildir. Aktarım önizlemesi görülüp kapsamı onaylandıktan sonra uygulanır. Bütün canlı kodları önce silme; ZIP'i doğrudan canlıya açma.

Teslimde `DEPLOY-STEPS.sh` de bulunur: bu belgedeki bash bloklarından üretilir; parametresiz çalıştırmak yalnız adımları listeler, toplu kurulum yapmaz. İstersen belge bloklarını kopyalayarak ilerle. Betik yalnız sunucudaki Bash içindir; Mac'te çalıştırılmaz. `8-preview` salt okunur önizleme, `8-apply` aktarım, `12` bakım kaldırma adımıdır; son ikisi ayrıca açık onay değişkeni ister. Betiği ve hash dosyasını doğrulamadan çalıştırma.

## 1. İlk işlem: anahtarlı bakım

Önce siteyi ziyaretçilere kapat; oluşturulan tek kullanımlık bakım adresiyle kendi tarayıcından kontrol edebilirsin. Bu komut henüz kod değiştirmez.

```bash
(
set -e
cd /home/u2930-h840qws8g2us/www/hypotenuse.be
umask 077
release_secret=$(/usr/bin/php85 -r 'echo bin2hex(random_bytes(24));')
/usr/bin/php85 -d extension=imagick.so artisan down --secret="$release_secret" --retry=60
printf 'Yalnız kendi tarayıcında aç: https://hypotenuse.be/%s\n' "$release_secret"
)
```

Anahtarlı adres bakım geçiş çerezi verir; normal hesap girişi yine gerekir. Anahtarsız gizli pencerede503, kendi tarayıcında site beklenir. Anahtarı paylaşma. Mevcut yönetici oturumunu kapatmadan anahtarlı erişimi doğrula.

## 2. Cronları kaydet ve duraklat; yolları/araçları doğrula

SiteGround → Devs → Cron Jobs: mevcut beş kaydın ekran görüntüsünü al; geçici duraklat. Çalışmakta olan scheduler/worker/yedek süreçleri varsa bitmesini bekle. Kuyruk kayıtlarını silme, çalışan süreçleri körlemesine sonlandırma.

```bash
export release_www=/home/u2930-h840qws8g2us/www
export release_live_dir="$release_www/hypotenuse.be"
export release_delivery_dir="$release_www/eduan-release-20261109-v5"
export release_stage_dir="$release_www/eduan-staging-20261109-v5"
export release_backup_dir="$release_www/eduan-backup-20261109-v5"
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

Yedek web kökü dışında, www altında ayrı700 dizinde tutulur. Bu dizin zaten varsa üstüne yazma; eski denemenin durumunu incele. Aşağıdaki DB işlemi yalnız dump alır; import yapmaz. Mevcut R2 DB scripti logs satırlarını dışladığı için tek başına tam yayın yedeğinin yerine kullanılmaz. SQL doğrudan gzip biçimine aktarılır; ayrıca büyük bir sıkıştırılmamış SQL kopyası tutulmaz. Geçici MySQL kimlik dosyası700 yedek dizininde600 izniyle kalır; paylaşılmaz.

```bash
(
set -euo pipefail
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
  --databases "$release_db_name" | gzip -c > "$release_backup_dir/database-before.sql.gz"
test -s "$release_backup_dir/database-before.sql.gz"
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

Bilgisayardan `eduan-20261109-v5-delivery.zip` ve aynı adlı `.sha256` dosyasını **www** içine SFTP ile yükle. public_html içine yükleme. Hash kontrolü olmadan açma.

```bash
(
set -e
umask 022
cd "$release_www"
sha256sum --strict -c eduan-20261109-v5-delivery.zip.sha256
mkdir "$release_delivery_dir"
unzip -q eduan-20261109-v5-delivery.zip -d "$release_delivery_dir"
cd "$release_delivery_dir"
sha256sum --strict -c SHA256SUMS.txt
mkdir "$release_stage_dir"
unzip -q eduan-20261109-v5-public-html.zip -d "$release_stage_dir"
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

Staging, yeni kodu canlıya dokunmadan hazırladığımız geçici klasördür. Canlı `.env` ve kalıcı dosyaların kopyası burada kullanılır; sunucunun anahtarları yerel değerlerle değiştirilmez.

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
if (preg_match("/^APP_VERSION=/m",$s)) $s=preg_replace("/^APP_VERSION=.*$/m","APP_VERSION=1.1.46",$s);
else $s=rtrim($s)."\nAPP_VERSION=1.1.46\n";
file_put_contents($p,$s,LOCK_EX);
'
)
```

Bu env düzenleme komutu yalnız APP_VERSION satırını değiştirir, henüz vendor gerektirmez. Yeni zorunlu env anahtarı yok. **ENV.production-changes.example** dosyasında yalnız bu sürüm değişikliği ve mevcut doğrulanması gereken production/false/HTTPS/public_html değerleri anlatılır. APP_KEY, DB_*, mail/bildirim/Turnstile ve yedekleme anahtarlarını aynen koru; key:generate veya yerel env kopyası kullanma. Etkin APP_PUBLIC_PATH=public_html/APP_MAINTENANCE_DRIVER=file değilse dur ve mevcut duruma göre planı doğrula.

## 6. Staging içinde temiz üretim bağımlılıklarını kur

Burada `vendor` klasörü kilit dosyasından oluşturulur. `--no-dev`, PHPUnit gibi geliştirme paketlerinin canlıya girmesini önler; Composer sürüm yükseltmesi yapılmaz.

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

## 7. DB / migration durumunu yalnız kontrol et

Bu yayında veritabanına uygulanacak yeni değişiklik yok. Yeni kodun doğru canlı DB'ye bağlandığını ve önceki source_files migration'ının kayıtlı olduğunu okuyarak doğrula; bu komut veri yazmaz.

```bash
(
set -e
cd "$release_stage_dir"
/usr/bin/php85 -d extension=imagick.so artisan migrate:status --pending --no-ansi
/usr/bin/php85 -d extension=imagick.so artisan tinker --execute='
if (DB::connection()->getDatabaseName() !== "dbjl8pylrssuae" ||
    ! Schema::hasTable("source_files") ||
    ! DB::table("migrations")->where("migration", "2026_11_04_150000_create_source_files_table")->exists()) {
    throw new RuntimeException("Beklenen canli DB/source_files migration durumu dogrulanamadi.");
}
echo "DB ve onceki source_files migration kaydi dogru.", PHP_EOL;'
)
```

**Beklenen: No pending migrations.** Herhangi bir Pending, farklı DB veya eksik tablo varsa dur ve çıktıyı paylaş. Genel migrate, seed, fresh, rollback veya SQL import çalıştırma. Önceki migration onayı bu yayında beklenmedik bir migration'ı uygulama izni değildir.

## 8. Aktarım önizlemesi ve kod aktarımı

Önceki tam yedek ve 7. adımdaki salt okunur DB kontrolü doğrulanmış olmalı. Uygulama dizinleri/public kaynakları güncellenir; .env, canlı storage/uploads/icons/manifest/.htaccess/ini/log/webstats korunur. Kod tarafında bu güncelleme için genel --delete kullanılmaz. **Yalnız vendor'da --delete**, temiz no-dev kopyayla eşitlemek ve dev/eski paket kalıntılarını bırakmamak içindir; önizlemede görülen silmelerin somut kapsamı onaylanır.

Önce önizleme:

```bash
(
set -e
test -f "$release_live_dir/storage/framework/down"
for release_dir in app config database lang resources routes scripts Codex; do
  rsync -rltcni "$release_stage_dir/$release_dir/" "$release_live_dir/$release_dir/"
done
rsync -rltcni --exclude=/cache/ "$release_stage_dir/bootstrap/" "$release_live_dir/bootstrap/"
rsync -rltcni --exclude=/uploads/ --exclude=/icons/ --exclude=/storage \
  --exclude=/.well-known/ --exclude=/.htaccess --exclude=/.user.ini --exclude=/php.ini \
  --exclude=/pwa-manifest.json "$release_stage_dir/public_html/" "$release_live_dir/public_html/"
rsync -rltcni --delete "$release_stage_dir/vendor/" "$release_live_dir/vendor/"
for release_file in artisan composer.json composer.lock package.json vite.config.js server.php README.md AGENTS.md; do
  rsync -rltcni "$release_stage_dir/$release_file" "$release_live_dir/$release_file"
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
  rsync -rltci "$release_stage_dir/$release_dir/" "$release_live_dir/$release_dir/"
done
rsync -rltci --exclude=/cache/ "$release_stage_dir/bootstrap/" "$release_live_dir/bootstrap/"
rsync -rltci --exclude=/uploads/ --exclude=/icons/ --exclude=/storage \
  --exclude=/.well-known/ --exclude=/.htaccess --exclude=/.user.ini --exclude=/php.ini \
  --exclude=/pwa-manifest.json "$release_stage_dir/public_html/" "$release_live_dir/public_html/"
rsync -rltci --delete "$release_stage_dir/vendor/" "$release_live_dir/vendor/"
for release_file in artisan composer.json composer.lock package.json vite.config.js server.php README.md AGENTS.md; do
  rsync -rltci "$release_stage_dir/$release_file" "$release_live_dir/$release_file"
done
cd "$release_live_dir"
/usr/bin/php85 -r '$p=".env";$s=file_get_contents($p); if(preg_match_all("/^APP_VERSION=/m",$s)!==1) throw new RuntimeException("APP_VERSION tekil olmali."); file_put_contents($p,preg_replace("/^APP_VERSION=.*$/m","APP_VERSION=1.1.46",$s),LOCK_EX);'
/usr/bin/php85 -d extension=imagick.so artisan package:discover --ansi
sha256sum --strict --quiet -c "$release_delivery_dir/FILES.sha256"
)
```

Bu payload manifesti korunan runtime/env/vendor dosyalarını içermez. SiteGround web OPcache yeniden yüklenir; CLI opcache_reset web sürecini yenilemez. Mevcut storage symlink'leri üzerine storage:link --force çalıştırılmaz.

## 9. PHP upload ayarları, cache ve sır göstermeyen kabul

Bu adım yeni kodun görünüm/route/config önbelleklerini canlı dizinde yeniden üretir. Staging altında üretilmiş mutlak yollar canlı cache olarak kopyalanmaz.

SiteGround web PHP etkili değerleri: **upload_max_filesize=100M**, **post_max_size=128M**. Bunlar önceki v4 gereksinimleridir; bu pakette yeni PHP ini dosyasıyla sunucu ayarları ezilmez. Korunan `public_html/php.ini` veya PHP Manager üzerinden bu değerleri birleştir; diğer extension/memory/session satırlarını değiştirme. CLI ini çıktısı web PHP kanıtı değildir. Proxy/CDN gövde limiti ayrıca geçerli olabilir; gerçek upload ile kontrol edilir.

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

cache:clear store adı **positional**; eski --store seçeneği kullanılmaz. Security store flush edilmez. Beklenen production/false/1.1.46/public_html/uploads, `/livewire/update`, cached=true, maintenance=true. Route/view cache yeni kodla yeniden üretilmelidir. storage/views içinde önceki sitemap derlemesi kalmamalıdır.

## 10. Bakım anahtarıyla gerçek web kabulü ve log

Kendi tarayıcında ilk adımdaki anahtarı açarak aşağıdaki kısa kabulü yap. Kontrollerin amacı yalnız HTTP200 görmek değil, ekrandaki yetki/veri/işlem sonucunu doğrulamaktır; gerçek kayıt işlemlerini kendi kontrollü test verinle ve onaylı kapsamda yap.

- Doğru giriş başarılı; yanlış şifrede sayfa404'e gitmez, Livewire POST `/livewire/update` çalışır.
- Yönetici → aktif öğretmen hesabına geçiş → Yönetim paneli görünür; yalnız o öğretmenin yetkileri/öğrencileri açılır. Panel üstündeki turuncu geri dönüş yöneticiye döndürür; normal öğrencide panel açılmaz.
- Çalışma takibinde hafta/öğretmen/durum/öğrenci durumu aynı38px yüksekliğinde; öğretmen/durum aranabilir tek seçimdir. Öğretmen filtresi yalnız yöneticide bulunur. Seçim, yenileme ve temizle çalışır; tablo/Excel/iletişim aynı filtreli öğrenci kapsamını kullanır. Gerçek e-posta/push gönderme.
- Dosyalar kart görünümüyle açılır; tabloya geçiş, arama/filtre/sayfalama çalışır. Yönetim paneli sağ offcanvas masaüstünde max1200px, mobilde tam genişliktir; ön yüz çalışma panelleri640px standardında kalır.
- Yükleme başlığı/yerelleştirilmiş dosya seçimi düzgün; ilk açılışta boş progress yok. İzinli küçük dosyada upload → bağlantı/görüntüleme/kopyala; hatalı dosyada anlaşılır hata ve yeniden deneme görülür. Silme yalnız kendi test dosyanda, onay diyaloğuyla yapılır; mevcut içerikleri silme.
- Dosya Ayarları: Cloudflare R2 etiketleri/placeholder'lar görünür. Kullanıcı kararı gereği global anahtar yetkili formda açıktır; ekran görüntüsü/log paylaşırken gizle. Kaydet JSON sayfası açmaz; panelde başarı mesajı gösterir. Boş anahtar mevcut değeri korur. Geçersiz değer422 mesajı verir. Gerçek anahtarları sırf test için değiştirme.
- PDF/görsel `/sources` içinde açılır; geri dönüş doğru site içi hedefe gider. HTML scriptsiz sandbox'ta; özgün HTML bağlantısı attachment + nosniff indirir. Viewer IP+dosya3/dakika, dördüncü429; özgün dosya adresi sınırsızdır.
- `/sitemap.xml` geçerliXML; `/search?tab=lessons` ve topics eksik/boş aramada500 vermez. 6 Kasım'da bildirilen log hataları bu sürümdeki regresyonlarla kapanmıştır; yeni saatli hataları ayır.
- Medya R2 açıksa gerçek credential/CORS/yükleme/görüntüleme kabulü ayrıca gerekir. Kapalıysa site depolaması korunur; bu yayını yapmak için R2'yi açma veya otomatik dış istek denemesi başlatma.

Loglar saklanır; aşağıdaki komutlar yalnız okur. Günlük sürücü kullanılıyorsa listeden güncel dosyayı seç. Eski kayıtları silerek temiz sonuç üretme.

```bash
cd "$release_live_dir"
find storage/logs -maxdepth 1 -type f -name '*.log' -printf '%TY-%Tm-%Td %TT %p\n' | sort
if test -f storage/logs/laravel.log; then tail -n 100 storage/logs/laravel.log; fi
```

## 11. Cron ve yedekleme: mevcut kayıtları koru

Yeni cron ve zaman değişikliği yok; mevcut kayıtlar korunup bakım sonunda tekrar etkinleştirilir. `CRON.md` beş mevcut görevin tam komutunu içerir: scheduler ve worker her dakika, DB02:00UTC, proje03:00UTC, mevcut cache temizliği `0 6 */3 * *`. Gerçek SiteGround timezone ve mevcut saatler doğrulanır, kopya görev eklenmez. Canlı lig zamanlaması scheduler kodundadır: **Europe/Amsterdam09:00–23:45 her15dk**, **00:00–08:00 saat başı**.08:15/08:30/08:45 çalışmaz;09:00/09:15 çalışır. Ayrıca lig cron'u ekleme. schedule:list cron ifadesini15dk gösterir; gece koşulu kodda uygulanır, saat dilimi Europe/Amsterdam olmalıdır.

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

Bu komut bakım modunu kaldırır; site artık herkese açılır. Önceki kabul tamamlanmadan çalıştırma.

Yeni hata yok, kritik web kabulü geçtiyse:

```bash
cd "$release_live_dir"
/usr/bin/php85 -d extension=imagick.so artisan up
```

Site Tools'tan duraklatılan cronları tekrar etkinleştir. Anahtarsız gizli pencerede ana sayfa/giriş/sitemap/arama ve dosya bağlantısı kontrol edilir. İlk scheduler/worker sonrası schedule_run.log, queue_run.log ve yeni Laravel hata kayıtları izlenir. E-posta/bildirim teslimi veya ücretli AI testi bu checklist adına otomatik gönderilmez. Geri dönüş kopyaları ve staging hemen silinmez.

## Hata / geri dönüş sınırı

Hata olursa bakım açık kalır; up yapma. Bu sürüm migration yapmaz: yedekteki önceki kod/vendor/env ile geri dönüş planlanabilir; yine de yeni kullanıcı/ayar/dosya işlemleri varsa tam DB'yi geri yükleme. Çalışan cronların durumu ve yeni kayıtlar değerlendirilir. SQL import, otomatik rollback, upload/storage veya yedek temizliği ayrı somut onay gerektirir. Yeni yayın kabul edilmeden sunucudaki yedekleri silme; sonrasında bilgisayara indirilen yedek hashlerini doğrulayıp yalnız belirlenmiş geçici yollar için temizlik yapılır.
