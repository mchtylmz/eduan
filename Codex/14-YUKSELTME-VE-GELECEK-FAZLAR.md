# Laravel yükseltmesi ve gelecek fazlar

**Güncel kurulum:** [35-PHP85-VE-LARAVEL13-GUNCELLEME.md](35-PHP85-VE-LARAVEL13-GUNCELLEME.md). Yerel PHP8.5.8, Laravel13.34.0, Livewire4.4.7, Alert4.2, Excel4.0.3, PHPUnit13.4.1 kuruldu.132 bağımlılık; platform/audit kontrolü başarılı.16 farklı dar senaryo geçti; tüm ürün kabulü değil. Yeni Dockerfile build registry timeout verdi, doğrulanan mevcut yerel8.5 image kullanıldı. Tablolar/tarayıcı/yedek karşılıkları beklenir.

**Güncel uygulama:** [34-PAKET-CIKARMA-VE-PROJE-TEMIZLIGI.md](34-PAKET-CIKARMA-VE-PROJE-TEMIZLIGI.md). Yedi paket gerçek composer/lock/vendor’dan kaldırıldı; 152 → 131 bağımlılık, kalan sürümlerde değişiklik yok. PHP 8.5 ve PHPUnit 13 hedefi kesin; mevcut Laravel 11/LW3 korunuyor. Ara sürümde tablolar/tarayıcı/yedek karşılıkları beklenir; canlıya alınmaz. Dar sonuç 6 test/21 doğrulama.

**Son karar / kontrol:** [33-PHP85-GECIS-KONTROLU-VE-SSH-KANITI.md](33-PHP85-GECIS-KONTROLU-VE-SSH-KANITI.md). Hedef PHP8.5; Backup/S3 kaldırma kesinleşti, Alert4 devam. PHP8.5.7 üzerinde hedef graph dry-run başarılı;390 kaynak dosyasında0syntax hata,21 dosyada27nullable uyarı. Sunucu çıktısıPHP8.2.34'tür; canlıPHP değişimi deployment aşamasında. Önceki PHP8.3/PHPUnit12 ve bekleyen Backup kararları tarihsel; gerçek Composer kurulumu yapılmadı.

**Yeni paket fazı:** [32-PAKET-KARARLARI-VE-SITEGROUND-HAZIRLIGI.md](32-PAKET-KARARLARI-VE-SITEGROUND-HAZIRLIGI.md) kullanıcı seçimlerini ve SiteGround PHP8.3 hazırlığını içerir. Yeni sıra seçilen paketleri çıkar → doğrudan Laravel13/Livewire4 kur → kendi karşılıkları ve uyarlamaları → hedef testler. Backup/S3 kararı bekler; Composer ve sunucu değişmedi. Önceki geçici Rappasoft/Livewire3 adayları güncel hedef değildir.

5 Ekim 2026. Bu bir plandır; paket kaldırma, framework yükseltme, deployment veya migration izni değildir. Kullanıcı önce mevcut bulguların kapatılmasını ve test zemininin kurulmasını, ardından yükseltmeyi istiyor. Son onaylı düzeltmeler `21`, bütün mevcut otomatik testlerin 245 PHP/1.163 doğrulama + 7 PWA geçen kanıtı ve kalan kabul kapıları `22-GENEL-TEST-SONUCU-VE-YUKSELTME-KAPISI.md` içindedir. `13` belgesi önceki uygulama kanıtıdır.

## 1. Başlangıç ve hedef

Sonraki hazırlık kaydı: `23-TARAYICI-AKISLARI-VE-YUKSELTME-HAZIRLIGI.md` gerçek Chrome kritik akışları ve yeni dil bulgularını, `24-PAKET-UYUMLULUK-MATRISI.md` güncel stable paket aralıklarını içerir. Aşağıdaki kilit aralıkları tarihsel başlangıçtır. Güncel aynı-major 13 adayları raporlandı; ayrıntılı paket uygulama/geçiş planı henüz seçilmedi. Kullanıcının son isteğiyle `hypotenuse_db.sql` ana yerel Sail DB'ye agent tarafından yedek sonrası aktarıldı; salt okunur karşılaştırma artık `26`–`29` içindedir. 52 eski cevapsız expired deneme, sayaç/orphan politikası ve indeks/izin planı onaylanmadan takip eden veri/şema düzeltmesi yapılmaz.

Composer isteği `laravel/framework:^11.9`; kilit dosyasında v11.44.7. Sail PHP 8.3, mevcut Composer PHP isteği ^8.2. **Son kullanıcı kararı: doğrudan Laravel 13 kurulur; Laravel 12 ara sürümü kurulmaz.** Resmi [Laravel 13 yükseltme kılavuzu](https://laravel.com/framework/docs/13.x/upgrade) PHP en az 8.3 gerektirir ve 12'den geçişi anlatır. Kurulum doğrudan 13 olsa da 11'den sonraki 12 ve 13 davranış değişiklikleri birlikte incelenir; bu inceleme Laravel 12 kurulumu anlamına gelmez.

[Laravel 12 yükseltme kılavuzu](https://laravel.com/framework/docs/12.x/upgrade) ve 13 kılavuzu değişiklik anında yeniden okunmalı. Yeni framework yanında kullanılan Tinker/PHPUnit gereksinimleri de güncellenir. Projede kullanılmayan Pest veya Boost sırf kılavuz örneğinde bulunduğu için kurulmaz.

### Son onaylanan işlem sırası

1. Kullanıcı canlı SQL dosyasını sağladı ve son açık isteğiyle agent **Sail'in ana yerel hypotenuse veritabanına** aktardı. Önceki yerel ana veri yedeklendi; kaynak/hedef 50 tabloda 206.164 satır eşleşti, testing checksum aynı. Güncel kanıt `26`. Bu özel import onayı testing'de test çalıştırma sınırını değiştirmez; sonraki fresh/seed/temizlik/migration veya üretim işlemine izin sayılmaz.
2. Aktarılan kopyanın salt okunur incelemesi tamamlandı: fiziksel şema/indeks/FK ve 36 migration kaydı `27`–`28`, eski sonuç/anomaliler `26`, 231 kontrol/14 temsilî EXPLAIN ve somut düzeltme paketleri `29` içinde. Kullanıcıyla tarihsel veri/sonuç kartı ve rol kararları netleştirilir; seçilen dar düzeltme ve append migration'lar ayrı onayla uygulanır. Kolon/default eşitliği toplu migration yeniden çalıştırma gerekçesi değildir.
3. Paket kararları kullanıcıyla verilir. Kullanılmayacak paketler, açıkça seçildikten sonra kaldırılır; yerine gelecek özelliklerin çağrı noktaları kaydedilir. Şu an seçilmiş yeni bir kaldırma listesi veya Composer değişikliği yoktur.
4. Hedef bağımlılıklar çözümlenerek doğrudan Laravel 13 kurulur; Laravel 12 kurulmaz.
5. Kaldırılan paketlerin yerine seçilen geliştirmeler ve hedef framework uyarlamaları yapılır. Tablo inceleme/taşıması tablo bazında yürür; OneUI ve Bootstrap Table/Lara Football referansı korunur.
6. Hedef sürümde testler ve kritik gerçek browser akışları doğrulanır. Paket kaldırma/geçiş sırasında eksik özelliğin oluşturduğu geçici hatalar kayıt altında tutulur; bu ara çalışma durumu bitmiş veya canlıya hazır ürün sayılmaz.

Bu sıra önceki ara Laravel sürümü kurma önerisini değiştirir. SQL importu son açık istekle tamamlandı; paket/framework değişimi henüz başlamadı. Doğrudan 13 kararı ve ayrı testing DB sınırı korunur.

## 2. Paketler: bugünkü kilit durumu

Bu tablo mevcut composer.lock beyanlarıdır. Paketlerin en yeni sürümlerinin uyumlu olmadığı iddiası değildir; güncelleme fazında en yeni resmi sürüm/changelog ayrı doğrulanmalıdır.

| Mevcut kilit | Gözlenen engel/inceleme |
|---|---|
| Rappasoft Tables 3.5.6 | Illuminate 10/11 aralığı; 12 geçişinde bile bu kilit değişmelidir. Composer'da tam sürüm sabit. Panelin çoğu tablosu ve yayınlanmış view'ları bağlı. |
| druc/langscanner 2.2 | Illuminate 9/10/11 beyanı; panel çeviri taramasının yerine gelecek davranış korunmalıdır. |
| Livewire 3.6.3 | Illuminate 10/11/12 beyanı; 13 için güncel uyum ve formlar/lazy/Locked davranışı doğrulanmalıdır. |
| Spatie Permission 6.17 / Translatable 6.11 | Mevcut kilit 13'ü beyan etmiyor; Permission korunacak, yeni uyumlu sürüm değerlendirilir. |
| Excel 3.1.64 / Tinker 2.10 / Telescope 5.7 / Intervention Laravel 1.5.6 | Kilit bağımlılık aralıkları ve PHP/framework geçişi birlikte incelenir. Export ve yükleme regresyonu gerekir. |
| Settings 1.2 / Meta 2.0 | Geniş framework alt sınırı Composer kabulü sağlayabilir; bu, Laravel 13 çalışma kanıtı değildir. Settings'i kullanmaya devam etmek kullanıcı tarafından uygun görüldü. |

Diğer paketlerin kullanım haritası `05-PAKETLER-VE-ASSETLER.md`. Kullanılmıyor kararı yalnız composer ismine bakılarak verilmez: Action, Blade directive, trait, provider, config, job ve yayımlanmış view çağrıları taranır. `*` sürüm istekleri ileride kontrollü aralığa alınabilir; bu fazda değiştirilmedi.

## 3. Yükseltmeye başlamadan gereken kapılar

1. Öğretmenin yalnız kendisine atama yapma kuralı uygulandı. Lig tüm denemeleri toplama/eşitlik kararı ve yeni users:send-email kapısı tamamlandı (`15` dosyası). Backend istatistik toplamı sonradan onaylandı ve düzeltildi (`17` dosyası). DB izin aktivasyonu ve kalan bulgular/testler ayrıca tamamlanır.
2. Canlı SQL veya migration tablosu read-only incelenir: fiziksel kolon/indeks/FK ile migration ve eski tamamlanmış sonuçlar eşlenir. SQL yedeği hassas veri sayılır; rehbere satır verisi veya sır kopyalanmaz, Git'e yanlışlıkla eklenmez.
3. Gerçek canlı PHP, eklentiler, cron, queue bağlantısı ve dosya izinleri doğrulanır. Yerel Sail sürümü canlı kanıtı değildir.
4. Hedef paket sürümleri ve kaldırılacakların yerini dolduracak özellikler somut dosya listesiyle hazırlanır; test/rollback kabul ölçütü yazılır.
5. Şema değişimi/veri taşıma/deploy için kullanıcı onayı alınır. Ana yerel veya canlıda fresh/seed/db:wipe yapılmaz.

## 4. Framework geçişinde projeye özel riskler

- 13'ün request forgery/CSRF değişiklikleri Livewire, frontend post rotaları ve mevcut istisnalarla birlikte denetlenmelidir.
- Cache'in nesne serileştirme ayarları önemlidir: proje Eloquent/Collection/ayar nesnelerini uzun süre cache'leyebilir. Kılavuzdaki `serializable_classes` varsayılanını mevcut cache içeriğiyle değerlendir.
- MySQL upsert ve lig yeniden hesaplama davranışı hedef sürümde test edilir.
- Container çağrısı ve nullable parametre davranışları Action/Livewire mount bağımlılıklarıyla kontrol edilir.
- 5 Ekim resmi 13 kılavuzu yeniden incelemesi: `DefaultOrderBy::bootDefaultOrderBy` içindeki `(new $class())->getTable()` model boot sırasında tekrar model oluşturur. Laravel 13 bu durumu LogicException ile engeller. Bu hedef sürüm uyumluluk adayıdır; Laravel 13 üzerinde henüz çalıştırılmadı ve bugünkü Laravel 11 suite'i yeşildir. Geçiş planında ele alınır, bu tur değiştirilmedi.
- Yayımlanmış Bootstrap pagination/Rappasoft Blade view'ları hedef paket API'siyle uyumlandırılır; var olan Bootstrap 5 tasarımı korunur.
- Parola tokenı, login limit cache'i, kaynak dil, timed exam job ve özel upload/export davranışı regresyon kapısıdır.

## 5. Paket sadeleştirme ve tablo kararı

Spatie rol/izin sistemi korunur. Settings kullanılmaya devam edebilir. Kullanıcı Rappasoft'u Laravel yükseltmesinden sonra kaldırıp Lara Football'daki Blade + Bootstrap Table + server-side JSON düzenine geçmeyi seçti. OneUI devam eder, Vue.js eklenmez. Kilit 3.5.6 Laravel 13 ile birlikte tutulamaz; yükseltmede uyumlu geçici paket sürümü veya onaylı geçiş dalı stratejisi gerekir. Bugün Composer/paket değişikliği yapılmadı. Geçiş haritası `19-RAPPASOFT-BOOTSTRAP-TABLE-GECIS.md`; önce/sonra hız kıyası sorgu/TTFB/render ölçümüyle yapılır.

Bootstrap Table geçişi seçilirse sorgu sunucuda sayfalı olmalı; filtre/sort whitelist'i, işlem izinleri, total/rows JSON sözleşmesi, export davranışı ve öğretmen kapsamı korunmalı. Paket kaldırıldıktan sonra kırılan sayfaların rastgele onarılması yerine çağrı haritasına göre küçük paketler uygulanır. Yeni mimari veya uzun soyut sınıflar zorunlu değildir.

## 6. Seed ve canlı geçmiş

Canlı veri gelmesi seed ihtiyacını tamamen kaldırmaz: ayrı test/geliştirme ortamı için deterministik asgari fixture gerekir. Rol/izin/ayar/dil seed'leri mevcut kimlikleri ve üretim verisini ezmeden değerlendirilir. Örnek öğrenci/soru verisi production'da otomatik oluşturulmaz. Migration isim değiştirme ve eski sonuç taşıma canlı şema görüldükten sonra ayrıca yazılır.

## 7. Daha sonraki CDN ve Turnstile fazı

Kullanıcı Cloudflare CDN ve reCAPTCHA yerine Turnstile istiyor; mevcut pakette uygulanmadı. Görsel URL çözümü tek tutarlı noktada geliştirilmeli; logo/favicon/soru çözümü/Watermark/PWA ikonları ve eski upload yolları birlikte sayılmalıdır. PDF/Excel/Word çıktısının uzak görsel okuyamadığı doğrulanırsa gerekli görselin lokal karşılığı korunur. CDN geçişi dosya silme yetkisi değildir.

Turnstile'da site anahtarı ve sunucu doğrulaması, hata/zaman aşımı/tekrar kullanım davranışı ve login/register/recover formlarının dar testleri planlanır. Trust Proxy ve gerçek IP zinciri ortam bilgisiyle ele alınır. Cloudflare kurulum veya dış servis çağrısı bu dokümandan yetki çıkarmaz.

## 8. Performans, SEO ve dağıtım

Ön yüzde yüksek kararlılık/hız önceliği; panelde iyi kararlılık/hız hedefi korunur. Optimize etmeden önce URL, veri hacmi, ölçüm ortamı, TTFB/LCP ve sorgu sayısı kaydedilir. İndeks değişikliği EXPLAIN ve canlı şema kanıtıyla planlanır; migration onayı ayrıca alınır.

Çok dilli SEO/canonical query/hreflang ve Search Console kapsamı kullanıcı alan adı/URL kararıyla ilerler. İlk ekranın ana görseli erken yüklenir; ekran dışındaki bütün görselleri koşulsuz eager yapma.

JS değişiklikleri için `APP_VERSION` ve HTTP cache planı dağıtımda hazırlanır. PWA root worker geçişi eski cihazla ve gerçek offline/logout ile kontrol edilir. Onaylı dağıtım planı dosyaları, migration komutunu, worker/cron kontrolünü ve geri dönüşünü açık gösterir; yeni scheduler/supervisor/deploy komutu varsayılmaz.
