# Kaldırılan paketler ve bekleyen karşılıklar

6 Ekim 2026. Mevcut composer.json/composer.lock ve uygulama referanslarıyla kontrol edildi; yeni Composer install/update/remove yapılmadı. Yedi paketin çıkartılması34, gerçek Laravel13/PHP8.5 kurulumu35, güncel tablolar49 içindedir. Tarihsel ara faz sürümlerini güncel sanma.

## 1. Paket ve özellik matrisi

| Çıkarılan paket | Güncel karşılık | Eksik / karar |
|---|---|---|
| druc/laravel-langscanner | Mevcut çeviri düzenleme, upload/build/exists korunur | **Kendi kaynak anahtarı tarayıcısı henüz yok** |
| rappasoft/laravel-livewire-tables |31 eski sınıftan30'u server Bootstrap Table'a taşındı | **Ai/LatexImagesTable son eksik tablo** |
| spatie/laravel-backup | Eski backup schedule/config/job kaldırıldı | **DB+dosya ZIP yedek scripti henüz yok** |
| league/flysystem-aws-s3-v3 | Eski S3 disk/R2 upload bağlantısı kaldırıldı | **Yeni yedek scriptinin R2 aktarımı henüz yok** |
| laravel/telescope | Laravel'in kendi log/exception davranışı devam eder | Kullanıcı alternatif istemedi; geliştirme borcu olarak yeni paket ekleme |
| sentry/sentry-laravel | Sentry bağlantısı kaldırıldı | Kullanıcı alternatif istemedi; dış hata servisi ekleme |
| barryvdh/laravel-debugbar | Debugbar kaldırıldı | Kullanıcı alternatif istemedi; yeni debug aracı ekleme |

Yedi isim güncel lock'ta yoktur. Bekleyen üç özellik ailesi: **çeviri tarama, LaTeX görsel tablosu, ZIP/R2 yedekleme**. Sonuncusu iki paketin eski işlevini birlikte karşılar. Mail queue, permission, translatable, settings, Excel, Intervention ve Alert bu yedi çıkarma listesinin parçası değildir.

## 2. Çeviri tarayıcısı

LanguageTranslateForm artık bütün kaynakları tarayıp eksik anahtar eklemez; yalnız mevcut kayıtları düzenler. TranslationHelper::upload/build/exists tarayıcının yerine geçmez. Yeni metinlerin DB'ye keşfedilmesi ve bütün locale kayıtları için ayrı kendi tarayıcımız gerekir.

Sonraki uygulamada app/resources kaynak kapsamı, __/trans/trans_choice/@lang/Lang::get biçimleri, vendor/üretilmiş dosya dışlama, languages:translate izni, mevcut değerleri ezmeme ve tekrar taramada idempotence korunmalı. Önce tarama ön izlemesi, sonra açık uygulama kararı düşünülür. nl varsayılan/en/tr dil sözleşmesi korunur; JSON build ayrı işlem. Dinamik/çok satırlı anahtarların parser sınırları uygulama öncesi netleşmelidir. Bu tur DB'ye çeviri eklenmedi.

## 3. Son Rappasoft tablosu

app/Livewire/Ai/LatexImagesTable.php hâlâ DataTableComponent kullanır; resources/views/backend/ai/images.blade.php çağırır. AI ana ekran/kullanım/oy listeleri taşındı; görseller ayrı alt ekranıdır. Paket artık kurulmadığından bu ekran yeni karşılık gelmeden çalışır kabul edilmez.

Kaynakta formül/görsel/tarih ve düzenleme/silme akışı vardır. Bootstrap JSON dönüşümünde arama/sort/durum değil gerçek mevcut alanlar, işlem izinleri, modal olayları, görsel yolu ve cache invalidation ayrı korunmalıdır. Görsel formu yeni tabloyla işlevsiz bırakılmamalı. Yetkisiz mutation testleri ve browser boş/dolu/responsive kontrolü gerekir; bu tur otomatik dönüştürülmedi.

Eski ortak Rappasoft trait/config ve yayımlanmış vendor Blade kaynakları taşıma referansı olarak durur. Son tablo bitmeden topluca silinmez; sonrasında kullanımsızlık kanıtıyla ayrı temizleme onayı gerekir.

## 4. ZIP ve R2 yedekleme

Kullanıcı Lara Football'daki kendi PHP/script yaklaşımını onayladı; burada karşılık henüz yazılmadı. Eski backup komutları, R2 upload job/command/config ve schedule34 ile kaldırıldı. Yeni yedek, aktarım veya saat03:00 cron bu tur eklenmedi.

Uygulama öncesi gerçek referans dosya yolu, yedeklenecek DB/dosyalar, dışlanacak geçici/cache/vendor alanları, retention, private geçici dizin, başarısızlık kaydı, R2 bucket/prefix ve cron çalışma zamanı belirlenir. SQL dump/ZIP web public içinde sunulmamalı; credentials rapor/loga yazılmamalı. Restore denemesi olmadan yedek doğrulandı denmez.

Kullanıcının SSH çıktısı mysqldump/curl/zip ve PHP8.2 CLI modüllerini gösterdi; SSH erişimi CLI çalıştırma imkânı sağlar. Bu çıktı gelecekte seçilecek PHP8.5 CLI yolu/extension veya web runtime doğrulaması değildir. Canlıya alma aşamasında seçilen binary, cron/worker binary ve web PHP ayrı doğrulanır. SSH komutu, R2 yazımı ve canlı cron değişikliği bu raporda uygulanmadı.

## 5. Kuyruk ve dağıtım sınırı

Eski serialized UploadBackupToCloudflare işleri canlı kuyrukta varsa kaldırılan sınıfa işaret edebilir. Canlıya geçişte yalnız ilgili işlerin salt okunur envanteri ve ardından açık karar gerekir; bütün queue/failed_jobs temizliği yapılmaz. Verilmiş cron gecikmesi/süreli sınav tamamlama kararı korunur; backup çalışmasıyla worker davranışı değişmez.

## 6. Gelecek işler ile ayrım

Cloudflare CDN görsel taşıma, Turnstile geçişi ve diğer yeni özellikler bu paket karşılıkları değildir; ayrı sonraki fazdır. PDF/Excel/Word dışa aktarımında gerekli yerel görselleri koruma kararı devam eder. Yeni S3 paketi otomatik geri eklenmez; R2 aktarım yöntemi kendi script kararıyla seçilir.

## 7. Doğrulama sınırı

Composer/lock ad kontrolü ve kaynak taraması salt okunurdur. Yeni dependency audit/network araştırması, install/update/build, migration, backup/restore, R2 upload veya scheduler işi çalıştırılmadı.49 testleri kullanıcı tablolarını doğrular; tarayıcı/backup/LaTeX karşılığı için başarı kanıtı değildir.

## 8. İlgili kayıtlar

- [34 — Gerçek paket çıkarma](34-PAKET-CIKARMA-VE-PROJE-TEMIZLIGI.md)
- [35 — Laravel13/PHP8.5 kurulumu](35-PHP85-VE-LARAVEL13-GUNCELLEME.md)
- [19 — Tablo geçiş haritası](19-RAPPASOFT-BOOTSTRAP-TABLE-GECIS.md)
- [49 — Son dört kullanıcı tablosu](49-KULLANICI-ALT-TABLOLARI-VE-MENU-GRUPLARI.md)
- [50 — Lazy ve sayfa kaynak bulguları](50-LIVEWIRE-LAZY-VE-SAYFA-KOD-INCELEMESI.md)


## 9. Güncel durum —7 Ekim /60

Kullanıcı son LaTeX tablosunun tamamlanmasını ve ardından iki fazı seçti. [60](60-RAPPASOFT-SON-TABLO-VE-PAKET-KARSILIK-FAZLARI.md) son tabloyu taşıdı ve kullanılmayan Rappasoft kaynaklarını temizledi; çalışma bağımlılığı0. Yukarıdaki son tablo bekliyor ve eski trait/config kalır kayıtları tarihsel durumdur. Yeni sıradaki Faz2 çeviri kaynak tarayıcısı/kullanım grupları, Faz3 Laravel bağımsız SQL.gz + projeZIP→R2 bash. Ana DB languages/translations durur, source read-only şema/sayı kanıtı60 bölüm5. Backup scheduler34'te zaten kaldırılmıştır, mevcut test doğrular. Yeni yedek veya dış upload/cron bu tur yoktur; bash referansı kullanıcıdan beklenir.


## 7 Ekim devam — Faz2 tamamlandı

[61-CEVIRI-TARAYICISI-VE-KAYNAK-YONETIMI.md](61-CEVIRI-TARAYICISI-VE-KAYNAK-YONETIMI.md): kendi tarayıcı/kaynak yönetimi ve JSON/PHP üretimi uygulandı; mevcut çeviri/dil kayıtları korunur. Onaylı yalnız translation_sources migration iki yerel DB’ye eklendi; ana çeviri ekleme/yazım çalıştırılmadı. Mevcut dillerle devam, enDB kaydı eklenmez/fr etkinleşmez.7yeni+4mevcut farklı dar senaryo ve testing Chrome kontrolü var. Faz3 bağımsız yedekleme referans script yolu bekler. Üst bölümler kendi tarihsel durumuyla saklanır.


## 7 Ekim devam — Faz3 Bash hazır

[62-LARAVEL-BAGIMSIZ-BASH-YEDEK-VE-R2.md](62-LARAVEL-BAGIMSIZ-BASH-YEDEK-VE-R2.md): kullanıcı yeni Bash ve yalnız Laravel referansını seçti; önceki Lara scripti bekleme kararı kapanır. SQL.gz/proje ZIP için iki mod, dotenv allowlist/private alan/overlap lock/AWS CLI R2/boyut+SHA256 geri okuma; doğrulama sonrası yalnız bu run arşivi silinir, hata arşivi korunur.11 farklı fake DB/R2 dar test ve Bash syntax başarılı. Gerçek sunucu/R2/cron/restore uygulanmadı; bucket/prefix/private dizin/saat-timezone kurulum adımıdır. Eski üst kayıtlar tarihsel kapsamıyla korunur.
