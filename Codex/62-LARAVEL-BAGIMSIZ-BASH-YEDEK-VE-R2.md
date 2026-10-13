# 62 — Laravel bağımsız Bash yedeği ve R2

**Güncel durum:** Kullanıcının SSH çıktısıyla DB ve proje ZIP aktarımı/doğrulaması başarılı; günlük saatler DB **02:00 UTC**, proje **03:00 UTC** olarak kesinleşti. SiteGround `crontab` komutu bulunmadığı için Site Tools kurulumu aşağıdaki bölüm12'de hazırlanmıştır. Cron henüz kurulmuş/çalışmış veya restore denenmiş kabul edilmez. Önceki bölümlerdeki bekleyen durumlar ilgili aşamanın tarihsel kaydıdır.

## 1. Onay ve kapsam

7 Ekim 2026: kullanıcı eski Lara Football scripti yerine **yeni Bash, yalnız Laravel referansı** istedi. Paylaşılan canlı Laravel kökü `/home/u2930-h840qws8g2us/www/hypotenuse.be`; `public` document root, yedek kapsamı ise Laravel kökünün tamamıdır. Önceki referans script bekleme kararı bu talimatla kapanır.

Faz3 geliştirmesi yerelde hazırlandı. Sunucuya SSH bağlantısı, dosya gönderimi, gerçek DB dump, R2 aktarımı, cron değişikliği veya restore yapılmadı. Ana/testing DB'ye yazma, migration, Composer, CSS/JS ve APP_VERSION değişikliği yok; sürüm1.1.18 kalır.

## 2. İki bağımsız mod

`scripts/backup-to-r2.sh` PHP, Artisan, Composer veya Laravel scheduler çalıştırmaz. Yalnız belirtilen Laravel kökündeki `.env` yapılandırmasını okur.

- `db`: MySQL `mysqldump` → SQL.gz. Tablo verileri, şema, trigger, routine ve event tanımları dahildir; **logs yalnız şema/trigger, satır verileri hariç**. Başka DB'leri veya MySQL hesaplarını yedeklemez.
- `project`: Laravel kökünün tamamı → ZIP. `.env`, `.git`, yüklemeler, bağımlılıklar ve gizli dosyalar dahildir. Sembolik bağlantılar bağlantı olarak saklanır; proje dışındaki hedef dosyalar takip edilmez.
- UTC tarih + benzersiz çalışma kimliği ile isim; R2 nesneleri `<prefix>/db/...sql.gz` ve `<prefix>/project/...zip` altında ayrı tutulur.
- Çalışma alanı proje dışında olmak zorundadır; böylece arşiv kendi kendisini ZIP'e alamaz.

## 3. `.env` referansı ve yapılandırma

`.env.example` içine boş backup seçenekleri eklendi. Gerçek `.env` veya mevcut AWS medya bucket ayarları değiştirilmedi. Backup için `AWS_BUCKET` otomatik kullanılmaz; ayrı `BACKUP_R2_BUCKET` açıkça verilmelidir.

| Alan | Kaynak / anlamı |
|---|---|
| DB_CONNECTION | `mysql`; DB modu için |
| DB_HOST / DB_PORT / DB_DATABASE / DB_USERNAME | Laravel'in açık DB bağlantı değerleri; DB modu için |
| DB_PASSWORD | Açık anahtar zorunlu; boş olabilir |
| AWS_ACCESS_KEY_ID / AWS_SECRET_ACCESS_KEY | R2 erişim bilgileri |
| AWS_SESSION_TOKEN | Varsa isteğe bağlı; R2 için zorunlu değil |
| AWS_ENDPOINT | `https://<hesap-kimliği>.r2.cloudflarestorage.com` |
| BACKUP_R2_BUCKET | Özel yedek bucket'ı; gerçek ad kurulumda belirlenir |
| BACKUP_R2_PREFIX | Siteye ait nesne yolu; gerçek değer kurulumda belirlenir |
| BACKUP_WORK_DIR | Proje dışı mutlak private dizin; üst dizini mevcut olmalı |

Örnek çalışma dizini `/home/u2930-h840qws8g2us/tmp/eduan-backups` olabilir; bu değer sunucuya uygulanmadı. Dizin script tarafından ilk gerçek çalıştırmada700 ile oluşturulur. Mevcut dizin aynı kullanıcıya ait, yazılabilir ve700 olmalıdır; symlink reddedilir. Sırları içeren ZIP için bucket özel tutulmalıdır. Sırlar rapora, CLI argümanlarına veya standart çıktıya yazılmaz.

Seçilen değişkenler allowlist ile okunur, `.env` **source/eval edilmez**. Tek satır, tek/çift tırnak, CRLF, boş değer ve yorum desteklenir. Tek tırnak içindeki `$`, `${...}`, `#`, backtick ve komut benzeri metinler literal kalır. Tek tırnak dışındaki `${...}` genişletmesi ve kontrol karakteri/bilinmeyen kaçış ve çok satırlı seçilen değerler sessizce yorumlanmak yerine reddedilir. Tırnaksız `null`/`(null)` boş değerdir. Tam php-dotenv/Laravel config yorumlayıcısı değildir: `DB_URL`/`DB_SOCKET` override'ı DB modunda reddedilir; özel TLS/socket/config çözümleri bu hazırlığın doğrulanmış kapsamı değildir.

## 4. Araçlar ve dump sınırı

Ortak: Bash, AWS CLI v2, OpenSSL ve standart shell araçları. DB: MySQL `mysqldump` ve gzip. Proje: ZIP/unzip. Sunucu binary/PATH ve izinleri henüz doğrulanmadı; araç kurulumu yapılmadı.

MySQL parolası private çalışma dizininde600 izinli geçici client dosyasına yazılır. `--defaults-file` yalnız bu ayarları kullanır; `MYSQL_TEST_LOGIN_FILE` var olmayan run-local yola yönlendirilir, `MYSQL_PWD` kaldırılır. Böylece kullanıcının genel client/login ayarları başka bağlantı seçmez. [MySQL option file kuralları](https://dev.mysql.com/doc/refman/8.0/en/option-files.html).

`--single-transaction --quick --skip-lock-tables --no-tablespaces --set-gtid-purged=OFF` kullanılır. InnoDB snapshot yaklaşımıdır; MyISAM veya eşzamanlı DDL için bütün sitenin tek anlık tutarlılık garantisi değildir. ZIP de canlı dosyaların uygulama snapshot'ını oluşturmaz. Routine/event/view/trigger okuma izinleri gerekir; hata durumunda upload başlamaz. gzip bütünlüğü ve açılmış SQL'in boş olmadığı kontrol edilir. Kaynak DB'ye import/reset veya kaynak proje dosyası silme yapılmaz. [mysqldump davranışı ve izinleri](https://dev.mysql.com/doc/refman/8.0/en/mysqldump.html).

## 5. R2 aktarım ve doğrulama

AWS CLI S3 aktarımı açık R2 endpoint'i ve `auto` region ile yapılır. Büyük dosyalardaki multipart aktarımı CLI yönetir. Optional S3 checksum gönderimleri `when_required` ayarıyla sınırlandırılır. R2 bütün S3 özelliklerini destekliyor kabul edilmez. [Cloudflare CLI yapılandırması](https://developers.cloudflare.com/r2/get-started/cli/), [R2 S3 uyumluluğu](https://developers.cloudflare.com/r2/api/s3/api/), [AWS CLI cp](https://docs.aws.amazon.com/cli/latest/reference/s3/cp.html).

1. Yerel arşivin byte boyutu ve SHA-256 değeri hesaplanır.
2. Arşiv, SHA-256 metadata değeriyle yüklenir.
3. HEAD sonucu boyut/metadata ile eşleştirilir.
4. Uzak nesnenin tamamı stdout üzerinden okunup SHA-256 yeniden hesaplanır; ikinci büyük yerel kopya oluşmaz.
5. Yalnız üç kontrol başarılıysa bu run'ın yerel SQL.gz/ZIP dosyası silinir.

Multipart ETag hash kabul edilmez. Tam geri okuma ek R2 okuması ve ağ trafiği oluşturur; upload'ın başlatılması başarı sayılmaz. R2 credentials için yükleme, HEAD ve GetObject yetkileri gerekir. Uzak nesne silme/retention veya başka uygulama bucket'ına müdahale yoktur.

## 6. Çakışma, hata ve silme

Private çalışma alanında atomik `mkdir .backup.lock` iki modu da korur. Kilit mevcutsa ikinci işlem dump/ZIP/upload başlatmaz. Bayat kilit kendiliğinden silinmez; sunucuda aktif iş kontrol edildikten sonra ayrıca karar verilmelidir.

Normal başarısızlık, upload hatası, boyut/metadata uyuşmazlığı veya farklı uzak içerikte üretilen arşiv ve private `errors.log` korunur. Geçici DB credential dosyası temizlenir. Başarıda yalnız bu run'ın arşivi, logu ve boş run dizini kaldırılır. Önceki yedekler/dosyalar ve mevcut başka işin kilidi korunur. SIGINT/SIGTERM çıkış kodları130/143; normal EXIT temizliği vardır. SIGKILL/host kapanması shell trap çalıştırmaz, private dosya/kilit kalabilir. Otomatik stale cleanup eklenmedi.

## 7. Kullanım ve düzenli çalışma

Aşağıdaki **kontrol** komutları yapılandırma ve yerel araçları doğrular; backup/DB sorgusu/R2 isteği ve dosya üretimi yapmaz:

```bash
bash /home/u2930-h840qws8g2us/www/hypotenuse.be/scripts/backup-to-r2.sh db --project-dir /home/u2930-h840qws8g2us/www/hypotenuse.be --check
bash /home/u2930-h840qws8g2us/www/hypotenuse.be/scripts/backup-to-r2.sh project --project-dir /home/u2930-h840qws8g2us/www/hypotenuse.be --check
```

Onaylı gerçek çalıştırmada `--check` kaldırılır. `bash /absolute/path/...` ile child process olarak kullanılır; `source` reddedilir. Executable/chmod şartı yoktur.

Günlük tekrar işletim sistemi cron'u veya sunucunun zamanlama aracıyla bu iki Bash komutunu çağırır; Laravel scheduler'a backup geri eklenmez. Script kendiliğinden günlük çalışan servis değildir. Kullanıcının03:00 DB/04:00 proje örneği henüz kesin saat/timezone veya kurulmuş cron anlamına gelmez. Bu tur crontab oluşturulmadı, değiştirilmedi.

Kurulum adımı: gerçek bucket/prefix/private dizin ve cron saat/timezone kesinleşmesi → sunucu araç kontrolü → açık onayla ilk DB/proje R2 çalıştırması → ayrı restore kontrolü → düzenli çağrı kurulumu. R2 erişimi/retention ve gerçek restore bu geliştirme testlerinin kanıtı değildir.

## 8. Dar test kanıtı

`bash -n scripts/backup-to-r2.sh`: exit0. İlk dar koşu `python3 tests/Backup/test_backup_to_r2.py`: **10 test başarılı**, yaklaşık8 saniye. Ardından çift tırnaklı parola kaçışını açıkça doğrulayan yeni senaryo eklendi; yalnız `BackupTest.test_double_quoted_password_escape_contract` çalıştırıldı ve başarılı oldu. **Toplam11 farklı test**, bütün suite yeniden koşulmadı. Fake mysqldump ve fake AWS CLI; gerçek gzip/ZIP/OpenSSL. Network veya mevcut DB kullanılmaz; fixture dosyaları yalnız sistem temp alanındadır.

Senaryolar: iki mode check'te dosya/transfer yok; DB başarı ve eski arşiv koruma; ZIP `.env`/gizli dosya/upload/symlink; dump hata/boş SQL upload engeli; upload/HEAD/boyut/metadata/readback/içerik hata koruması; ZIP upload hata koruması; yanlış ayar/proje içi çalışma alanı/URL/socket/port/interpolation reddi; mevcut lock korunması; dotenv shell metninin çalışmaması; private izin/symlink ve source guard; çift tırnaklı parola/backslash/quote doğru aktarımı ve desteklenmeyen kaçışların reddi.

Tüm proje testleri/build/gerçek sunucu performansı veya gerçek backup/restore kabulü değildir. Browser testi gerektiren UI değişikliği yoktur.

## 9. Değişen dosyalar ve faz sonucu

- Yeni `scripts/backup-to-r2.sh`, `tests/Backup/test_backup_to_r2.py`, bu rapor.
- `.env.example` backup yapılandırma alanları.
- AGENTS/README/standartlar16/index ve paket/faz kayıtları51/60/61: güncel Faz3 teslimi.

Faz1 tablo karşılığı tamam, Faz2 tarayıcı/yönetim tamam, Faz3 bağımsız Bash hazır ve dar testli. **Sunucu kurulum/gerçek aktarım/cron/restore hâlâ uygulanmadı.** Eski Backup/S3 Composer paketleri geri eklenmedi; Laravel backup scheduler'ı34'te zaten kaldırılmıştı ve değiştirilmedi.


## 10. SiteGround canlı deneme ve başlatıcı düzeltmesi —7 Ekim devam

Kullanıcının SSH çıktısı: AWS CLI2.37.10 resmî imza doğrulamasıyla home altında kuruldu. İlk kurucu `/tmp` üzerinden Permission denied verdi; aynı kurucu `TMPDIR=/home/u2930-h840qws8g2us` ile başarılı oldu. `/tmp` mount seçenekleri incelenmedi, kesin noexec kanıtı değildir. Sunucu UTC; DB/proje `--check` başarılı. Ana root symlink/canonical çalışma alanı kullanıcı çıktısında `/home/customer/tmp/hypotenuse-backups` olarak göründü. Bu rapor ajan tarafından sunucuya erişim veya kurulum yapıldığını söylemez.

İlk gerçek DB dump başarısız: `mysqldump: Couldn't find table: "--no-tablespaces"`. Kullanıcının doğruladığı67bayt `/bin/mysqldump` başlatıcısı:

```sh
#!/bin/sh
exec /usr/libexec/mysql/mysqldump "$@" --no-tablespaces
```

Scriptimizin `--` ayıracı, başlatıcının sonradan eklediği seçeneğin tablo adı olarak işlenmesine neden oluyordu. `mysqldump` çağrısından yalnız bu ayıraç kaldırıldı. `DB_DATABASE` başında `-` varsa işlem çalışma alanı/dump/upload öncesinde reddedilir; normal ad ve içindeki tire korunur. Host/port/user/password private defaults-file'da kalır. Provider başlatıcısı veya diğer dump seçenekleri değiştirilmedi; raw provider binary üzerinden bypass eklenmedi.

Dar test: gerçek başlatıcı davranışını aynı `$@`+son seçenek düzeniyle taklit eden fixture düzeltme öncesi1failure/exit45 üretti. Düzeltme sonrası wrapper, seçenek biçimliDB adı reddi, iç tireli ad, mevcut DB başarı/yalnız run silme ve dump hata/boş SQL koruma olmak üzere **5 ilgili test başarılı**; Bash syntax exit0. Network, ana/testingDB ve gerçek R2 kullanılmadı. İlk11testin tamamı tekrar koşulmadı; farklı toplam test tanımı14'tür, tek tam koşu kabulü değildir.

Bu revizyonda değişenler: `scripts/backup-to-r2.sh`, `tests/Backup/test_backup_to_r2.py`, bu rapor, AGENTS ve standartlar16. Yeni dosya/sürüm/migration/Composer/CSS/JS/gerçek.env değişimi yok. Kullanıcı yeni scripti sunucu home kopyasına aktardıktan sonra DB modu tekrar denenmelidir; başarılı gerçek R2 yedeği/projeZIP/cron/restore henüz doğrulanmadı.


## 11. Logs veri dışlama —7 Ekim devam

Kullanıcı canlı dump devam ederken `logs` içeriğinin boyutu büyüttüğünü bildirdi ve yalnız bu tablonun verisini dışlama, şemasını koruma istedi. Paylaşılan ekran görüntüsündeki23.6MiB tablo boyutu, bildirilen4GB dump boyutuyla aynı ölçüm değildir; kaynak/SQL.gz/hamSQL boyutları bu tur sunucuda ölçülmedi.300–400MB hedefi gerçek yeni dump sonucu görülmeden doğrulanmış sayı kabul edilmez.

DB modu iki mysqldump çıktısını tek gzip akışına birleştirir:

1. Mevcut snapshot/quick/no-lock/routine/event/trigger seçenekleri ve `--ignore-table="$DB_DATABASE.logs"`: diğer tabloların tüm şema/verisi.
2. Aynı bağlantı ve `--no-data --skip-routines --skip-events "$DB_DATABASE" logs`: logs tablo şeması ve trigger'ları; satırlar yok, routine/event tekrar yok.

Bu, MySQL8.4 için belgelenmiş [ignore-table ve no-data seçeneklerini](https://dev.mysql.com/doc/refman/8.4/en/mysqldump.html) kullanır; MariaDB'ye ait ignore-table-data seçeneğine dayanmaz. SiteGround başlatıcısının sonda eklediği flag'i tabloya dönüştüren `--` terminator geri eklenmez. Diğer tablolar tek veri snapshot'ında, logs şeması ayrı çağrıda alınır; eşzamanlı DDL için iki çağrının tek atomik schema snapshot'ı olduğu iddia edilmez.

İlk/ikinci çağrı başarısızsa alt süreç exit ile akışı keser, pipefail upload'ı engeller; kısmi SQL.gz korunur. Canlı logs satırları silinmez veya değiştirilmez. Başarılı boyut/hash/upload ve yerel temizleme kuralları korunur. DB modu `--help` metni dışlamayı açıklar; projeZIP modu değiştirilmedi.

Dar doğrulama: yeni fixture senaryosu önce eski dump'ın logs INSERT içerdiği SQL ile başarısız oldu. Sonrasında **5 ilgili test başarılı**: logs şema+trigger var/satır yok/diğer veri var/routine-event tek kez; logs şema dump hatasında R2 yok ve arşiv kalır; SiteGround wrapper; ilk dump hata/boşSQL; mevcut DB başarı ve eski dosya koruma. Bashsyntax exit0. Önceki testlerin hepsi yeniden koşulmadı; güncel test tanımı16, tek16test başarı koşusu iddiası yok. Gerçek DB veya R2 testte kullanılmadı.

Değişenler: script, backup testleri, rapor62, AGENTS ve standartlar16. Kullanıcı çalışan SSH foreground backup'ını Ctrl+C ile durdurup prompt geri geldikten sonra yeni scripti home'a kopyalayarak DB modunu yeniden çalıştırır. Ajan uzak process öldürmedi, dosya silmedi, canlı DB/R2/cron veya restore işlemi yapmadı. Eski kısmi arşivler otomatik silinmez; kilit hatası varsa eski işin durduğu ayrı kontrol edilir, toplu kill/rm eklenmez. Gerçek yeni yedek boyutu ve R2 başarısı kullanıcı çıktısını bekler.

## 12. Başarılı canlı çıktı ve UTC zamanlama —7 Ekim devam

Kullanıcının paylaştığı SSH çıktıları:

- DB:19.136.233byte; `hypotenuse/db/20261007T095758Z-run.k3jo30jL6M.sql.gz` için doğrulama ve yerel arşiv kaldırma başarılı.
- Proje:1.016.832.889byte; `hypotenuse/project/20261007T095953Z-run.zcopcStsYL.zip` için doğrulama ve yerel arşiv kaldırma başarılı.

Bu kullanıcı çıktısıdır; ajan bağımsız uzak erişim/restore yapmadı. Kullanıcı son saat kararını **her gün UTC02:00 DB / UTC03:00 proje** olarak verdi. SSH tarih çıktısı UTC+0000; `crontab -l` komutu bulunmuyor. [SiteGround resmi yöntemi](https://static-cdn.siteground.com/kb/manage-cron-jobs): Site Tools → Devs → Cron Jobs; iki ayrı görev oluşturulur.

| Görev | Dakika | Saat (UTC) | Ayın günü | Ay | Haftanın günü | İfade |
|---|---|---|---|---|---|---|
| DB | 0 | 2 | * | * | * | `0 2 * * *` |
| Proje ZIP | 0 | 3 | * | * | * | `0 3 * * *` |

Command alanlarına aşağıdaki satırlar ayrı ayrı girilir; cron ifadesi Command alanına eklenmez. Cron ortamı SSH'teki export'u devralmadığından AWS CLI kullanıcı bin dizini PATH'e açıkça dahil edilir. Çalışma alanı başarılı çalıştırmalarda mevcut olduğundan yeni dizin/izin kurulumu gerektirilmez.

DB:

```bash
PATH=/home/u2930-h840qws8g2us/.local/bin:/usr/local/bin:/usr/bin:/bin /bin/bash /home/u2930-h840qws8g2us/backup-to-r2.sh db --project-dir /home/u2930-h840qws8g2us/www/hypotenuse.be >> /home/u2930-h840qws8g2us/tmp/hypotenuse-backups/db-cron.log 2>&1
```

Proje:

```bash
PATH=/home/u2930-h840qws8g2us/.local/bin:/usr/local/bin:/usr/bin:/bin /bin/bash /home/u2930-h840qws8g2us/backup-to-r2.sh project --project-dir /home/u2930-h840qws8g2us/www/hypotenuse.be >> /home/u2930-h840qws8g2us/tmp/hypotenuse-backups/project-cron.log 2>&1
```

İlk planlı çalıştırma sonrası iki log `tail -n 20` ile okunur. Kurulu diğer queue/scheduler görevlerine müdahale yoktur. Bu revizyonda yalnız rapor62 ve AGENTS güncellendi; Bash kodu değişmedi, testler yeniden koşulmadı. Canlı cron kurulum/ilk çalışma ve restore kanıtı beklenir.
