# Symfony güvenlik yamaları ve yayın kabulü — 5 Kasım 2026

## Sonuç ve kapsam

**Sonuç: fixed — altı üretim bağımlılığı bildirimi kapatıldı.** Yerel kod ve incelenen işlevler kontrollü canlı aktarıma uygundur. Bu sonuç canlı sunucuya aktarım veya bütün projede sıfır açık garantisi değildir.

Kullanıcı güvenlik bulgularını işlev kaybı oluşturmadan düzeltmeyi onayladı. Composer'ın dar önizlemesi ve uygulaması yalnız şu üç üretim paketini değiştirdi:

| Paket | Önce | Sonra |
|---|---|---|
| symfony/http-foundation | 8.1.8 | 8.1.11 |
| symfony/http-kernel | 8.1.8 | 8.1.11 |
| symfony/routing | 8.1.8 | 8.1.11 |

`composer.json` byte olarak aynı; yeni paket/silme yok. `composer.lock`, üç resmi vendor dağıtımı ve Composer'ın ürettiği autoload/installed metadata güncellendi. Vendor içine elle yama yapılmadı. Önceden değişmiş Livewire/Mime ve Dosyalar bağımlılıkları korunur; HEAD farkının tamamı bu tura ait değildir.

Yeni uygulama testi: `tests/Unit/SymfonySecurityPatchTest.php`. Uygulama iş kuralları, rotaları, izinleri, şeması, CSS/JS, APP_VERSION/root PWA1.1.43 değiştirilmedi. Rehber/index/README/AGENTS güncel kayıtları eklendi.

## Güvenlik sınırı ve kanıt

| Üretici bildirimi | Korunan sınır / doğrulama |
|---|---|
| [CVE-2026-118514](https://symfony.com/cve-2026-118514) | GET erişim eşleştirmesi HEAD isteğinde atlanmaz; POST ayrı kalır. |
| [GHSA-3h27-hjv7-fqjx](https://github.com/symfony/symfony/security/advisories/GHSA-3h27-hjv7-fqjx) | SCRIPT_NAME sonuna eklenen korumalı yol ve kodlanmış biçimleri gizli bir base URL parçasına dönüşmez. Normal script/subdirectory yolu korunur. |
| [GHSA-xg2q-4rrq-657q](https://github.com/symfony/symfony/security/advisories/GHSA-xg2q-4rrq-657q) | private_ranges, genel IPv6 geçiş adreslerinden sahte forwarded IP/host/proto kabul etmez; özel IPv4/mapped-private/ULA proxy kontrolleri çalışır. |
| [GHSA-5mx8-mwrp-5693](https://github.com/symfony/symfony/security/advisories/GHSA-5mx8-mwrp-5693) | Çift slash ve slash/backslash yolu üretilen adresleri dış hosta çeviremez. |
| [GHSA-wg5q-m2ww-f3c9](https://github.com/symfony/symfony/security/advisories/GHSA-wg5q-m2ww-f3c9) | Alt istek sırasında geçici proxy güveni ana isteğin kaydedilmiş routing context'ini değiştiremez. |
| [CVE-2026-118516](https://symfony.com/cve-2026-118516) | Derlenmiş host placeholder, noktalı korumalı yol önekini host içine yutamaz; geçerli host/yol eşleşmesi korunur. |

Yama öncesinde gerçek paket sınıflarında HEAD, üç script-name biçimi, iki URL authority biçimi, dört IPv6 geçiş biçimi, alt istek context'i ve noktalı derlenmiş host denemesi başarısız güvenlik kontrolü üretti. Aynı test sınıfının yama sonrası 16 senaryosu/52 doğrulaması başarılıdır. Paket sınırındaki bu kanıt, altı yolun da uygulamada dışarıdan istismar edilebildiği iddiası değildir. Laravel'de path/baseURL, compiled routing ve IP kullanan hız sınırları önemlidir; MethodRequestMatcher ve Symfony RouterListener doğrudan uygulama tüketicisi bulunmayan paket kontrolleridir.

`fix-finding` becerisinin bir bağımsız salt okunur ön incelemesi ve bir ayrı yama/bypass incelemesi tamamlandı. Son inceleme somut kalan bypass/yeni regresyon bulmadı; ek 24 normal/derlenmiş host-yol eşleşmesi kontrolü geçti.

## Komut ve test kaydı

Dar güncelleme (mevcut Eduan konteynerinde):

```sh
composer update symfony/http-foundation:8.1.11 symfony/http-kernel:8.1.11 symfony/routing:8.1.11 \
  --with-dependencies --minimal-changes --no-scripts --no-interaction --no-progress --no-audit
```

`--no-scripts`, uygulamanın package discovery/vendor publish scriptlerinin ve dolaylı asset/ayar işlemlerinin çalışmasını önledi. Kayıtlar için güncelleme öncesi dar yerel dosya yedeği alındı.

| Kontrol | Sonuç |
|---|---|
| Yeni Symfony sınırları + mevcut izole cached/uncached Livewire endpoint testi | 17 PHP /64 doğrulama |
| İlgili 20 Feature dosyası, testing DB/transaction | 496 PHP /2362 doğrulama |
| Dosyalar, mail escaping, bildirim görünürlüğü, lig türü/zamanlama Unit | 41 PHP /317 doğrulama |
| Gerçek geçici PHP sunucusunda özgün HTML/alias/GET-HEAD/PDF/görsel/fallback | 1 PHP /35 doğrulama |
| İlgili dokuz Frontend Node dosyası | 42 test, hata0 |
| Değişen upstream PHP dosyaları syntax | 44 dosya başarılı |
| Yeni test syntax/Pint | Başarılı |
| Composer validate ve platform --no-dev | Başarılı |
| composer audit --locked --no-dev --format=json | exit0; advisories=[], abandoned=[] |
| Ayrı, .env içermeyen geçici dizinde gerçek composer install --no-dev --optimize-autoloader --no-scripts | Başarılı; Laravel HTTP/R2 sınıfları autoload olur, PHPUnit yok |

**555 farklı PHP senaryosu /2778 doğrulama**, ilgili ayrı koşuların toplamıdır; tek bütün-suite koşusu değildir. Feature grubu: AuthSecurity, Turnstile, AccessBoundary, PanelPermissionMatrix, SecurityFindingsRegression, UserMailSecurity, PanelUploadSecurity, ResultSeasonFilter, StudyReleaseCombinations, StudyPanel, UserImpersonation, SourceFilesIntegration, ContactReplyStoredXss, Communications, CommunicationWizard, WebNotifications, PracticeTestSecurity, TimedExam, LiveLeaguePresentation, LiveLeagueScheduling. Gerçek dış mesaj/API gönderimleri test düzeninde engellendi.

Statik sunucu testinin ilk sandbox koşusu port açamadı; yalnız bu test gerekli izinle tekrar çalışınca geçti. İzole no-dev probe ilk olarak Laravel'in kullanmadığı Symfony tam-uygulama Kernel sınıfını yüklemeye çalıştı; önceki pakette de bulunan opsiyonel DI bağımlılığı nedeniyle durdu. Doğru Laravel/Foundation ve Symfony HttpKernel sınıflarıyla kontrol tamamlandı; bunun için paket veya uygulama kodu değiştirilmedi.

## Gerçek yerel tarayıcı kabulü

Browser eklentisi mevcut değil; mevcut Playwright ve Google Chrome, yeni geçici profil ve dış HTTPS istekleri kapatılarak `http://localhost` üzerinde kullanıldı. 1440×1000 ve390×844 ölçüleri incelendi.

- Dosyalar gerçek JSON tür filtresi ve temizle: beş kalıcı örnek korunur.
- Mobil dosya yükleme paneli açılır; yatay taşma yok.
- Panel çalışma detayı GET geçmiş sekmesi ve aktif sekme gösterimi.
- Kullanıcı Değerlendirmem sekmesi refresh sonrası korunur.
- Yanlış bilgilerle giriş POST `/livewire/update`200; parola alanı temizlenir, giriş sayfasında kalır;404 yok.
- HTML görüntüleyici iki genişlikte okunur; sandbox boş/JSkapalı, dönüş adresi doğru, tanıtım popup'ı yok.
- Özgün HTML200/attachment/nosniff; dördüncü viewer isteği429/Retry-After ve doğru bekleme sayfası.

Dokuz ilgili kontrol ayrı akışlarda tamamlandı; uygulama JS istisnası0, beklenmeyen5xx0. İlk birleşik geçici test, giriş popup'ını açılmadan kapatmaya çalıştığı için yanıt beklemesinde durdu. Ayrı kontrollü giriş denemesi doğru endpoint200 sonucunu verdi; ürün koduna bir istisna eklenmedi. Ekran görüntüleri incelendi. Geçici kanıtlar macOS `codex-security-artifacts-234b2ccc07495bf933875d2bd9b3d4761a20953ec1fcfe125bcc740e35253fc1` dizininde; OS temizleyebilir.

## Veri ve ortam korunması

Ana DB: kullanıcı381, kaynak5; settings SHA256 `4f4d02e3e1d20c82a2334b633243b87fe34009433025aa4101ab553bf4d2abcd` ve source_files SHA256 `614a678f913bc1b3a3d8335e983f8945b6534753091b456fa22584c8b9d80493` test öncesi/sonrası aynı. Testing son durum kullanıcı1/ayar10/kaynak0. Yeni demo eklenmedi, mevcut beş demo silinmedi. Kendi geçici yönetici oturumu kapatıldı; parola/rol/ayar değişmedi. Migration, gerçek R2/SMTP/push/ücretli AI, servis/cron veya canlı deploy yapılmadı.

## Geliştirme bağımlılıkları ve yayın sınırı

Tam Composer audit'te geliştirme paketleri için iki bildirim sürer: PHPUnit13.4.1 [GHSA-9vw2-f4vp-x7gf](https://github.com/sebastianbergmann/phpunit/security/advisories/GHSA-9vw2-f4vp-x7gf), Symfony YAML8.1.8 [GHSA-hwfg-qwvh-2wfx](https://github.com/symfony/symfony/security/advisories/GHSA-hwfg-qwvh-2wfx). Dar önizleme PHPUnit13.4.4/YAML8.1.11 ve zorunlu sebastian/git-state1.1.0 ile toplam üç ek dev güncellemesi gösterdi. AGENTS'nin ek bulguları otomatik düzeltmeme kuralı nedeniyle somut onay soruldu; bu kaydın yazıldığı anda yanıt yok, bu üç dev paket değiştirilmedi. Bunlar gerçek doğrulanan `--no-dev` kurulumuna dahil değildir. Üretim audit temizliği, geliştirme dahil audit temizliği diye sunulmaz.

Kontrollü yayın sırasında:

1. Canlı `.env`/uploads/storage korunarak DB+dosya yedeği ve bakım adımı uygulanır; yayın somut kapsamı ayrıca onaylanır.
2. Güncel lock üzerinden PHP8.5/Imagick ile `composer install --no-dev --prefer-dist --optimize-autoloader` çalıştırılır; platform ve üretim audit doğrulanır. Yerel dev vendor doğrudan taşınmaz.
3. Dosyalar'ın canlıda gerçekten Pending olan tek migration'ı envanterle karşılaştırılır; onaylı kapsam dışında genel seeder çalıştırılmaz.
4. **Yeni Symfony Routing ile route cache yeniden üretilir**; eski derlenmiş cache korunmaz. Config/view cache olağan yayın sırasıyla hazırlanır; Livewire update adresi `/livewire/update` olmalıdır.
5. APP_PUBLIC_PATH=public_html ve sunucuda etkili upload100M/post128M değerleri kontrol edilir. Mevcut php.ini korunacaksa ilgili iki değer birleştirilir.
6. Apache'nin özgün HTML'yi attachment/nosniff sunduğu canlı HTTP ile doğrulanır; root server.php yalnız yerel Artisan sunucusunu korur.
7. R2 opsiyoneldir: ilk yayın local depolama ile yapılabilir. Gerçek medya bucket/CORS/metadata/PUT/Head/Get/Copy/Delete kabulü yapılmadan R2 açılmaz. Sahte SDK testleri gerçek bulut kabulü değildir.
8. Giriş, panel yetkileri, çalışma, PDF/HTML/görsel ve geri dönüş canlı smoke'u tamamlanır; mevcut PHP8.5 cron/worker ve loglar izlenir.

Üretimde bu son kontroller yapılmadan “canlıda doğrulandı” denmez. Bu tur sonucunda yeni bir üretim kodu engeli görülmedi; tam işlevlilik iddiası yukarıdaki sınanmış kapsamla sınırlıdır.
