<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

## Packages
```
composer require laravel/sail --dev
php artisan sail:install
```

```
composer require spatie/laravel-permission
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
```

```
composer require izniburak/laravel-auto-routes
php artisan vendor:publish --provider="Buki\AutoRoute\AutoRouteServiceProvider"
```

```
composer require pharaonic/laravel-settings
```

```
composer require lorisleiva/laravel-actions
```

```
composer require livewire/livewire
```

```
composer require jantinnerezo/livewire-alert
php artisan vendor:publish --tag=livewire-alert:config
```

```
composer require archtechx/enums
```

```
composer require spatie/laravel-translatable
```

```
composer require zoha/laravel-meta
php artisan vendor:publish --provider="Zoha\Meta\MetaServiceProvider" --tag=config
```

```
composer require intervention/image-laravel
php artisan vendor:publish --provider="Intervention\Image\Laravel\ServiceProvider"

```

```
https://github.com/wotzebra/livewire-sortablejs
```

## Paket geçiş durumu

Langscanner, Telescope, Rappasoft Tables, Sentry, Laravel Backup, S3 adapter ve Debugbar kaldırıldı. Yerel PHP 8.5.8, Laravel 13.34.0, Livewire 4.4.7 ve PHPUnit 13.4.1 kuruldu; Alert 4 ve Excel 4 uyarlamaları uygulandı.

Paneldeki son LaTeX tablo da kendi Blade + Bootstrap Table/server JSON karşılığına geçti; kullanılmayan Rappasoft trait/config/şablonları kaldırıldı. Çeviri anahtarı tarayıcısı Faz2 ile, Laravel bağımsız SQL.gz/ZIP → R2 bash yedeği Faz3 ile geliştirildi; gerçek sunucu/R2/cron kurulumu ayrı adımdır. Mevcut çeviri düzenleme ve yerel yüklemeler korunur; bu geçiş tek başına canlıya hazır kabulü değildir. Faz ve dar test kaydı: [60](Codex/60-RAPPASOFT-SON-TABLO-VE-PAKET-KARSILIK-FAZLARI.md). Ayrıntı: [paket temizleme kaydı](Codex/34-PAKET-CIKARMA-VE-PROJE-TEMIZLIGI.md), [PHP8.5/Laravel13 sonucu](Codex/35-PHP85-VE-LARAVEL13-GUNCELLEME.md).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).


### Çeviri tarayıcısı — Faz2

[Kaynak ve test raporu](Codex/61-CEVIRI-TARAYICISI-VE-KAYNAK-YONETIMI.md): Diller/Çeviriler ekranında Kaynakları tara → Eksikleri ekle. Mevcut değerler/diller korunur; dosya/route/grup/boş filtreleri ve40 kayıtlık düzenleme. Blur DB’ye, Dil dosyalarını kaydet JSON/PHP kaynaklarına yazar. Onaylı tek kullanım tablosu migration’ı yerel ana/testing’de uygulandı; ana tarama ekleme henüz başlatılmadı. SQL.gz + ZIP/R2 bash ayrı Faz3’tür.


### Bağımsız yedek — Faz3

[`scripts/backup-to-r2.sh`](scripts/backup-to-r2.sh): `db` SQL.gz, `project` tam ZIP; Laravel başlatılmadan kök `.env` okunur. Ayrı private `BACKUP_WORK_DIR`, `BACKUP_R2_BUCKET`, `BACKUP_R2_PREFIX` ve AWS R2 erişim/endpoint değerleri gereklidir. `--check` yalnız yerel araç/ayar kontrolüdür. R2 aktarımı boyut ve geri okunan SHA-256 ile doğrulanmadan yerel arşiv silinmez. Hata arşivi korunur. [Kullanım, sunucu komutları ve dar test raporu](Codex/62-LARAVEL-BAGIMSIZ-BASH-YEDEK-VE-R2.md).11 farklı fixture test başarılı; gerçek aktarım/cron/restore uygulanmadı.

### SiteGround PHP 8.5 CLI / Imagick

[`scripts/siteground-cli-cron.example`](scripts/siteground-cli-cron.example): **PHP 8.5 proje yayını sırasında yapılacak adım:** mevcut scheduler ve queue worker cron komutları `/usr/bin/php85 -d extension=imagick.so` kullanacak şekilde güncellenir. O yayına kadar mevcut canlı görevler korunur. Mevcut zaman/worker seçenekleri korunur; örnek dosya otomatik kurulmaz. [CLI kapsamı ve kontrol](Codex/66-GENEL-TEST-DUZELTMELERI-VE-IMAGICK.md).

### Canlı AI ayarları ve son test kapısı

[Canlı aktarım listesi ve test sonucu](Codex/69-SON-TESTLER-VE-CANLI-AKTARIM-LISTESI.md): yayın gününde yalnız `gptModel=gpt-6-luna`, `gptEffect=low`, `gptDeveloperMessage` ve `gptUserMessage` v3 metinleri etkinleştirilir. Mevcut API anahtarı, günlük/kullanıcı limitleri ve kayıtlı AI cevapları korunur. Aktivasyon ücretli API isteği göndermez; her ajan denemesi ayrı onaylıdır. Henüz canlıda uygulanmadı. Mevcut test aşaması tamamlandı: genel koşudaki tek lig başlığı beklentisi düzeltildi, ilgili 1 test/16 doğrulama geçti; genel koşu tekrar edilmedi. Yeni özelliklere geçilebilir. Sunucu/migration kontrolleri ve yayın/geri dönüş hazırlığı kullanıcı kararıyla yayın aşamasına bırakıldı.

### Çalışma Takibi — Faz 6 web bildirimleri / iletişim

[Kurulum, test sınırları ve değişen dosyalar](Codex/75-CALISMA-TAKIBI-FAZ6-WEB-BILDIRIM-VE-TOPLU-ILETISIM.md): Ayarlar → Bildirimler, Sayfalar’daki açıklama, `/account/notifications` ve `/admin/communications`. Ayrı localhost App ID/key gerekir; canlı ID yerelde kullanılmaz. Onaylı iki migration ana/testing’de uygulandı; bildirim kapalı ve secrets boş. `communications:dispatch` scheduler ile asenkron job’ları kanal başına beş/dakika hazırlar; gerçek provider/SMTP/Safari cihaz kabulü Faz 7’dedir. Kök PWA worker korunur, var olan çalışma takibi açık kalır.

### Faz 7 — bildirim görünümü ve yerel kabul

[Yerel test/inceleme kaydı](Codex/76-CALISMA-TAKIBI-FAZ7-KABUL-VE-BILDIRIM-TASARIMI.md): `/account/notifications` yenilendi; kullanıcı ekranındaki sağlayıcı adı nötr, mevcut Sayfalar içerikleri korunur. Genel715 koşudaki tek eski scheduler beklentisi düzeltildi; ilgili44/263 ve20Node başarılı. Gerçek Chrome testi yerel App ID/key ve kullanıcının Chrome girişini bekler; Safari kullanıcı kararıyla sonraya bırakıldı. APP_VERSION/root worker1.1.27; tam yayın kabulü değildir.


### Toplu iletişim — standart tablo ve üç adım

[Yapılanlar, dosyalar ve dar test sonucu](Codex/77-TOPLU-ILETISIM-UC-ADIM-VE-TABLO-STANDARDI.md): Yönetim → Toplu E-posta ve Bildirim, Kullanıcılar'ın üstündedir. `/admin/communications` önce ortak Bootstrap Table/server JSON gönderim geçmişini açar. Yeni gönderim: alıcı seçimi → mesaj → kişisel ön izleme/Gönder. Güncel izin ve alıcı kontrolleri, UUID, asenkron beş/dakika gönderim korunur. 34 PHP/236 assertion,15 Node ve testing Chrome kontrolleri geçti; gerçek servis gönderimi yapılmadı. APP_VERSION/root worker1.1.28. Geliştirme öncesi standart okuma zorunluluğu AGENTS.md'de kalıcıdır.


Faz 7 gerçek kabul devamı: [76 bölüm12](Codex/76-CALISMA-TAKIBI-FAZ7-KABUL-VE-BILDIRIM-TASARIMI.md). SMTP testi kullanıcı kararıyla atlandı. Settings1.2.0 deprecated fatal değil; iki ilgili test2/12 geçti, vendor/Composer değişmedi. Onaylı yerel APP_URL localhost; Chrome giriş tamam; girilen yerel ID canlı ID ile aynı olduğu için ayrı localhost uygulaması bekleniyor. Onaylı canlı origin yerel DB’de hypotenuse.be olarak geri düzeltildi. Gerçek push henüz doğrulanmadı.


### Çalışma Takibi — geliştirme tamamlandı

[Son kullanıcı kabulü ve yayın sınırı —76 bölüm13](Codex/76-CALISMA-TAKIBI-FAZ7-KABUL-VE-BILDIRIM-TASARIMI.md): yedi geliştirme fazı ve planlanan ekranlar tamamlandı. Bildirim testi kullanıcı talimatıyla başarılı kabul edildi; ajan gerçek cihaz teslim testi yapmadı. APP_URL ve bildirim origin'i https://hypotenuse.be ile eşleşir. Önceki localhost/gerçek test bekleme notları tarihsel; sıradaki aşama canlı hazırlığı/aktarımı ve canlı kontrolüdür. Yeni genel test veya deploy yapılmadı.

### Canlı lig — Faz 1 yerel kabulü tamamlandı

[78 bölüm9 — migration ve test kapanışı](Codex/78-CANLI-LIG-FAZ1-VERI-VE-HESAPLAMA.md): onaylı tek migration ana batch16/testing10 uygulandı; eski lig/sonuç ve kaynak verilerinin özetleri korundu. Saf ve DB dahil60 farklı ilgili senaryo başarıyla doğrulandı; ilk JSON anahtar sırası beklentisi düzeltildi, dar tekrarlar raporda. Scheduler/kapanış Faz2; form/ön yüz/cache ve tarayıcı kabulü Faz3'tür. Ana DB'de gerçek lig hesabı veya canlı yayın yapılmadı.

### Canlı lig — Faz 2 yerel backend kabulü tamamlandı

[79 — zamanlama/kapanış ve test raporu](Codex/79-CANLI-LIG-FAZ2-ZAMANLAMA-VE-KAPANIS.md): `leagues:refresh-live` dört saatte bir, ortak DB kilidi ve atomik son hesaplama/kapanış. Eski haftalık görev korunur; aynı aralıktaki canlı kaydı sabit ayarlarla hesaplar. Kapalı lig yeniden hesaplama yetki altyapısı hazır. Faz1+2 toplam84 farklı ilgili senaryo başarıkanıtı; dar koşuların ayrıntısı raporda. Yeni migration/ana DB gerçek hesaplaması/canlı işlem yok. Form, durumlar, ön yüz cache ve tarayıcı kabulü Faz3'tür.


### Canlı lig — Faz 3 ve yerel kabul tamamlandı

[80 — ekranlar, cache, test ve dosya listesi](Codex/80-CANLI-LIG-FAZ3-EKRANLAR-VE-KABUL.md): panelde tek/canlı seçim, yeni Cumartesi–Cuma varsayılanı, durum/veri tarihi ve kapanmış canlı ligde izinli yeniden hesaplama. Ön yüzde durum/iki saat ve sabit kapasite; başarılı hesaplamada hedefli cache yenilenir. Son birleşik104test/617assert,12PWA+17formatterassert, gerçek Chrome1440/390 NL ve FR kontrolleri geçti. Eski verilerin hash/count aynı; yeni migration/ana DB hesabı/canlı işlem yok. APP_VERSION/root worker1.1.29. Üç geliştirme fazı tamam; yayın ayrı adımdır.


### Genel proje kabulü —25 Ekim 2026

[81 — Fransızca düzeltmeler, genel test ve yayın sınırı](Codex/81-GENEL-TEST-KABULU-VE-FRANSIZCA-DUZELTMELER.md): kayıtlı ortak FR bulguları giderildi; son tek temiz816PHP/5130assert,31Node/16backup, syntax/Composer ve54ziyaretlik gerçekChrome kabulü başarılı. Tek503 test-fixture HTTP/HTTPS eşlemesiyle düzeldi; uygulama origin koruması korunur. Ana veri özetleri aynı, yeni kalıcı migration/ayar/gönderim/deploy yok. Sunucu salt okunur PHP8.5.12/Imagick3.7.0/WebP başarılı; CLI için-d bayrağı gerekli. Yerel kod kontrollü canlı aktarıma uygun; migration/cron-worker/productionayarları ve canlısmoke yayın sırasında tamamlanır. APP_VERSION/rootworker1.1.29.

### Çalışma takibi — öğrenci ekranı revizyonu

[82 — kullanım, dosyalar ve dar test raporu](Codex/82-CALISMA-TAKIBI-OGRENCI-EKRANI-VE-HESAP-MENUSU.md): Hesabım menüsünde hesap bilgilerinin altında Çalışma Takibim. Beyaz sayfa, açık tarih/hafta bağlantıları, haftalık özet ve hızlı hedef/çalışma girişi; geçmiş, analiz ve notlar ayrı bölümlerde.69ilgili test/575 doğrulama ve Chrome masaüstü/tablet/mobil kontrolleri geçti. TR/NL/EN/FR metinler; APP_VERSION/rootworker1.1.30. İş kuralları ve ana veriler korunur; canlı aktarım yapılmadı.

Devam revizyonu [82 bölüm5](Codex/82-CALISMA-TAKIBI-OGRENCI-EKRANI-VE-HESAP-MENUSU.md): hedef ve çalışma ayrı sağ panellerde; outline hafta/hesap düğmeleri, ikon/placeholder, belirgin başlıklar ve dört haftalık eşli sütun grafikleri. Tarihler takvim gününü korur; ortak sayfalama özeti TR/NL/EN/FR'de çevrilir.80ilgili test/488 doğrulama; son üç test106altküme ve Chrome responsive/panel/odak/dil kontrolleri başarılı. Asset1.1.31; ana DB/migration/canlı işlem yok.

Güncel [82 bölüm6](Codex/82-CALISMA-TAKIBI-OGRENCI-EKRANI-VE-HESAP-MENUSU.md): iki panel %100/max640px; mevcut Flatpickr ile dört dilde okunur tarih/ISO kayıt, Android-iPhone UA ve masaüstü/tablet/mobil kontrolü. Hafta günü ayarı yok, cumartesi–cuma sabit.52farklıdarPHP/422assert geçti; asset1.1.32. Kullanıcı isteğiyle yalnız yerel admin1 hesabına3hedef/9çalışma/30örnekyanıt/2not eklendi; manifest/tmp, otomatik seeder/migration/canlı işlem yok. Yerel dönem tarihleri korunur.

[82 bölüm7](Codex/82-CALISMA-TAKIBI-OGRENCI-EKRANI-VE-HESAP-MENUSU.md): ön yüzde günlük ve manuel kayıt toplamları ilk satırda, tarihler en yeniden eskiye. Notlar sağında Değerlendir: seçili haftanın7günü/3ölçüsü ve hedef-gerçek-oranları tek matris grafikte. Ek sorgu/AI yok;65darPHP/484assert ve Chrome responsive/4dil/tarih düzenleme geçti. Asset1.1.33; kalıcıDB/migration/canlı işlem yok.

[82 bölüm8](Codex/82-CALISMA-TAKIBI-OGRENCI-EKRANI-VE-HESAP-MENUSU.md): Değerlendirmem sekmesi; masaüstünde solda üç renkli haftalık halka grafik, sağda matris; mobilde grafik önce. Mevcut hedef/gerçek oranlar, sıfır/boş ve%100 aşımı korunur.19ilgiliPHP/139assert,5Pint ve Chrome responsive/4dil kontrolleri başarılı. Asset1.1.34; kalıcıDB/API/canlı işlem yok.

[82 bölüm9](Codex/82-CALISMA-TAKIBI-OGRENCI-EKRANI-VE-HESAP-MENUSU.md): çalışma sekmeleri gerçek GET bağlantılarıdır (`?tab=week|history|analysis|notes|evaluation`). Değerlendirmem için `/account/study?tab=evaluation`; yenileme/doğrudan bağlantı/geri-ileri ve hafta gezinmesinde seçim korunur.28ilgiliPHP/441assert ve dört dil Chrome kontrolleri geçti; asset1.1.35, anaDB/dışservis/canlı işlem yok.

[83 — Çalışma takibi panel detayı ve tablolar](Codex/83-CALISMA-TAKIBI-PANEL-DETAY-VE-TABLOLAR.md): öğrenci e-postası, beş GET sekmesi, üstte işlemler/max640px sağ paneller; bütün detay listeleri ortak Bootstrap Table/private JSON ve15sayfalama.73ilgiliPHP744assert+12JS ve responsive/4dil Chrome kontrolleri başarılı; hesaplama/yetki kuralları korunur. Asset1.1.36, ana DB/migration/canlı işlem yok.
