# 93 — Yayın öncesi kontrol ve iki bağımlılık yaması

3 Kasım 2026. Dosyalar/R2 geliştirmesi kapsam dışıdır. AGENTS.md, README, 03/16 standartları ve 35/69/81/86/88/91/92 özellik/yayın kayıtları incelendi. Kullanıcı servis değişimini reddetti ve DB testlerini bekletti; ardından yalnız aşağıdaki iki güvenlik güncellemesini onayladı.

## Karar

**Çalıştırılabilen kod, bağımlılık ve izole tarayıcı kontrolleri geçti. Tam canlıya çıkış onayı henüz verilmedi.** Son değişikliklerin gerçek DB/oturumlu HTTP/Livewire kabulü bekliyor. Önceki tarihli genel test başarıları güncel tam test koşusu yerine sayılmadı. Canlı aktarım yapılmadı.

## Uygulanan değişiklik

| Üretim paketi | Önce | Sonra |
|---|---|---|
| livewire/livewire | 4.4.7 | 4.4.8 |
| symfony/mime | 8.1.7 | 8.1.10 |

`composer.lock` eski/yeni JSON karşılaştırmasında yalnız bu iki paket değişti; kurulan/kaldırılan veya başka güncellenen paket yok. `composer.json` değişmedi. Bu depoda vendor takip edildiğinden iki paketin dağıtım dosyaları ve Composer'ın dört üretilmiş autoload/installed dosyası da güncellendi. Vendor içine elle yama yapılmadı. Autoload yeniden üretimi daha önce eklenmiş uygulama/test sınıflarını da classmap'e ekledi.

Komut (PHP8.5.12, global Composer):

```sh
/opt/homebrew/opt/php@8.5/bin/php /usr/local/bin/composer update livewire/livewire:4.4.8 symfony/mime:8.1.10 --with-dependencies --minimal-changes --no-scripts --no-interaction --no-audit
```

Artisan/Composer uygulama scriptleri çalıştırılmadı. DB, migration, uygulama ayarları, servis/portlar ve dış mesaj gönderimleri değiştirilmedi. Livewire kendi dağıtım manifesti üzerinden asset hash'ini yeniledi (`a964a3c8`); yerel CSS/JS ve APP_VERSION/root worker1.1.42 korunur. `public/vendor/livewire` yayımlanmış kopyası bulunmuyor.

## Güvenlik doğrulaması

- [Livewire üretici bildirimi GHSA-mhf7-xp6r-6qrc](https://github.com/livewire/livewire/security/advisories/GHSA-mhf7-xp6r-6qrc): sırası değiştirilmiş sayısal parametreler, olay dinleyicileri ve JSON işlemlerinin yetki sırası. Uygulamada `#[Authorize]`/`#[Json]` kullanımı bulunmadı; uygulamaya özgü bir istismar kanıtı ileri sürülmüyor.
- [Symfony üretici bildirimi CVE-2026-45070](https://symfony.com/blog/cve-2026-45070-email-header-injection-via-non-token-characters-in-mime-parameter-names): MIME parametre **adlarının** doğrulanması. Kullanıcı kontrollü parametre adı aktaran uygulama çağrısı bulunmadı.
- Yama öncesi gerçek MIME constructor/setParameter/setParameters yollarında CR/LF içeren ad kabul edilip başlık gövdesine yazıldı; geçerli Unicode dosya adı kontrolü geçti. Yama sonrası aynı üç deneme reddedildi, geçerli kontrol korundu.
- Güncel paketlerle **55 dar doğrulama geçti**: JSON işlem gövdesinden önce Gate reddi, izinli JSON sonucu; positional/named parametrelerde aynı argümanın yetkilendirilmesi ve yürütülmesi; olay dinleyicisinde izin/red; bozuk sıralı/seyrek/karma sayısal anahtarlarda gerçek update/event girişlerinin hem debug hem production hata semantiğiyle reddi; MIME adlarında CR/LF, boşluk, ayraç ve boş değer reddi; hatada önceki başlığın korunması; geçerli Unicode PDF adı ve ekli e-postanın serileştirilmesi. Gerçek e-posta gönderilmedi, DB kullanılmadı.
- İlk geçici Livewire testinde bozuk parametreyi doğrudan iç çağrıya verip geçerli giriş gibi beklemek hatalıydı. Test gerçek dış update/event doğrulama girişine taşındı; ürün kodunda bunun için istisna eklenmedi.
- `fix-finding` akışının iki ayrı salt okunur incelemesi tamamlandı; son incelemede somut kalan bypass/regresyon bulunmadı. Bu bir bütün uygulama güvenlik taraması veya sıfır açık garantisi değildir.
- Güncelleme sonrası `composer audit --locked --no-dev --format=json`: **exit0; advisories=[]; abandoned=[]**. Sonuç yalnız üretim bağımlılıkları içindir.
- Development dahil audit ayrıca çalıştırıldı: **exit1**, yalnız `require-dev` altındaki PHPUnit13.4.1 için [GHSA-9vw2-f4vp-x7gf / CVE-2026-119193](https://github.com/sebastianbergmann/phpunit/security/advisories/GHSA-9vw2-f4vp-x7gf) kaldı; düzeltilmiş sürüm13.4.4. Bildirim, ortak geçici dizini kullanan farklı yerel kullanıcılarla PHPT+coverage çalıştırma yoluna aittir. Bu projede PHPT testi bulunmadı ve coverage çalıştırılmadı; uygulamanın uzaktan erişilen bir açığı gösterilmedi. Kullanıcının onayı yalnız iki paket olduğundan PHPUnit değiştirilmedi. Yayın paketinde geliştirme bağımlılıklarının bulunmaması ayrıca doğrulanmalıdır.

Geçici doğrulamalar: `codex-security-artifacts-234b2ccc07495bf933875d2bd9b3d4761a20953ec1fcfe125bcc740e35253fc1/artifacts/` altında `mime-boundary.php`, `dependency-boundaries.php`, `livewire-runtime.cjs` (macOS geçici dizininde). Bunlar ürün dosyası veya kalıcı test sınıfı değildir.

## Bu tur çalıştırılan testler

| Kontrol | Sonuç | Gerçek sınır |
|---|---|---|
| PHPUnit Unit | **155 test /552 doğrulama** | Güncelleme sonrası tek temiz koşu; DB yok |
| Frontend Node | **53 test** | Zamanlayıcı, PWA, Turnstile, seçim, hafta, hesap geçişi ve push yaşam döngüsü; yama öncesi bu tur çalıştı |
| Backup Python | **16 test** | Sahte DB/R2; gerçek uzak işlem yok |
| Kaynak PHP syntax | **943 dosya** | Blade `.php` dosyaları dahil; hata/deprecated yok; vendor hariç |
| JS/CJS syntax | **23 dosya** | Uygulama/test kaynakları; vendor hariç |
| Güncellenen vendor syntax | **32 PHP +4 JS** | Değişen vendor PHP dosyaları ve standart/CSP debug-minified JS; başarılı |
| Shell syntax | **2 dosya** | Yedekleme ve cron örneği |
| Panel rehberi Chrome | **48 hücre** | 4 dil/3 rol/boş-dolu/1440-390; yeni sekme ve noopener; gerçek Blade, sahte hedef yanıtı |
| Çalışma geçmişi Chrome | **20 hücre** | 4 dil/2 yüz; gerçek Blade/native select; DB ve Livewire sunucu etkileşimi yok |
| Güncel Livewire runtime Chrome | **4 hücre** | Standard/CSP ×1440/390, gerçek yeni dağıtımda Alpine açılış/tıklama; JS0, beklenmeyen istek0; DB/server güncellemesi yok |
| Composer validate/platform | **Başarılı** | Yerel PHP8.5.12; üretim sunucusu kanıtı değil |

Unit komutu:

```sh
/opt/homebrew/opt/php@8.5/bin/php vendor/bin/phpunit --testsuite Unit --log-junit /private/tmp/eduan-release-20261103-unit-after-patch.xml
node --test tests/Frontend/*.cjs
python3 -m unittest discover -s tests/Backup -p 'test_*.py'
```

75 dil dosyasının test öncesi/sonrası SHA256 değerleri aynı. Panel rehberinin yedi PHP dosyası Pint kontrolünden geçti. Global Composer'ın kendi PHAR bağımlılıklarından PHP8.5 deprecated mesajları var; uygulama veya paket test hatası gibi sunulmadı ve global Composer değiştirilmedi. Chrome ilk sandbox denemesinde başlatılamadı; yalnız geçici profil/dış istekleri kesilmiş test sandbox dışında çalıştırıldı. Kullanıcının mevcut Chrome oturumu kullanılmadı.

## Bekleyen yayın kapıları

1. Kullanıcının beklettiği gerçek testing DB/HTTP/Livewire regresyonları: özellikle 91 dönem Tümü/geçmiş ve 92 ayar kaydetme/sidebar rol akışı; güncel Livewire ile ilgili form/CRUD/oturum kombinasyonları.
2. Önceden açık bırakılmış “Dönem yok” filtresinin NULL dönem kayıtları kapsamı ayrı bulgudur; bu tur değiştirilmedi. Varsayılan Tümü düzeltmesi bu filtre semantiğinin düzeltildiği anlamına gelmez.
3. Yayında gerçek migration/izin/env envanteri, DB+dosya yedeği, PHP8.5/Imagick web-CLI ve cron-worker kontrolü, cache/asset yenilemesi ve geri dönüş planı; ardından canlı smoke. Gerekli yazma/migration işlemleri somut kapsamla onaylanmadan çalıştırılmaz.

Servisleri açma, başka projenin servislerini durdurma, ana/testing DB'ye yazma, migration, ücretli AI, SMTP/push, R2/Dosyalar geliştirmesi, commit ve deploy yapılmadı. Önceden değişik rehber/uygulama dosyaları, site görselleri ve OneSignal klasörü korundu.
