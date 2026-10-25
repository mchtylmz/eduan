# 81 — Fransızca bulguların kapanışı ve genel yayın testi

25 Ekim 2026. Kullanıcı önce son rapordaki eski çeviri bulgularını düzeltmeyi, ardından kapsamlı proje testi ve canlıya uygunluk değerlendirmesini istedi. Başlangıç commit'i `6b484e50`.

## 1. Karar

**Yerel kod/test kabulü verildi: kontrollü canlı aktarıma uygun.** Son temiz genel koşuda **816 PHP testi / 5.130 assertion** başarılı. Yazılmış testler ve aşağıdaki gerçek tarayıcı akışlarında açık bir işlevsel engel bulunmadı.

Bu karar production yayınının tamamlandığı anlamına gelmez. Sunucuda uygulama dosyası, veritabanı, web PHP sürümü, cron veya worker değiştirilmedi. Yayın sırasında migration/izinler, ortam ve dört AI ayarı, PHP8.5/Imagick cron-worker, asset/cache ve geri dönüş hazırlığı uygulanıp kısa canlı kontrol yapılmalıdır. Bütün olası kullanıcı davranışlarının veya üretim yükünün hatasızlığı garantisi verilmez.

## 2. Kapatılan kayıtlı bulgular

- **80'deki lig başlığı:** Fransızca Ligler, Lig Sonuçları, geçmiş lig seçicisi, üç lig adı, tarih/işlem başlıkları ve ortak düğmeler tamamlandı.
- **73'teki hesap geri dönüşü:** eksik `lang/fr/account.php` eklendi; 63 hesap menüsü/profil/istatistik metni ve `:number` yer tutucusu tamamlandı.
- **74'teki ortak panel fallback'leri:** `lang/fr/tables.php`30 ve `lang/fr/menus.php`3 anahtarı eklendi; eğitim/sonuçlar/yönetim grupları, tablo hata/yüklenme/filtre/tarih/sıralama metinleri Fransızca.
- `lang/fr.json`38→122: **84 eksik ortak metin** eklendi; önceki38 dolu değer birebir korundu. `upload` grup anahtarı JSON'a eklenmedi; önceki grup/JSON ayrımı korunur.
- Ortak `x-admin.server-table` Fransızca için mevcut `bootstrap-table-fr-FR.min.js` ve `fr-FR` locale'ini seçer. Başka dillerin eşlemesi aynı kaldı.

Bu, kaydedilmiş ortak ekran bulgularının düzeltmesidir; bütün eski içeriklerin ve yönetim formlarının Fransızca çevirisinin eksiksiz olduğu iddia edilmez. Soru görselleri ve kullanıcı tarafından yazılmış içerikler çevrilmedi. Ana DB `languages/translations`, aktif dil kararları ve mevcut özel çeviriler değiştirilmedi. Dosya kaynağından çeviri yeniden üretiminde boş kayıt varsayılanı silmez; dolu özel çeviri kullanılır. Yeni test bunu geçici dizinde doğrular.

## 3. Genel PHP testinin bulduğu ve düzeltilen nokta

İlk genel koşu: **816 test /5.115 assertion;815 başarılı,1 başarısız**. `WebNotificationsTest` context isteğinde200 yerine503 beklenmedik sonucu vardı.

Neden: iletişim fixture'ı `URL::forceRootUrl('http://localhost')` ve config APP_URL=http://localhost kuruyordu; geliştiricinin HTTPS APP_URL'sinden türeyen URL şeması ayrıca sabitlenmemişti. Üretilen https://localhost isteği ile HTTP localhost origin farklıydı. Ürünün origin kontrolü doğru şekilde reddediyordu.

Yalnız `tests/Concerns/UsesWebCommunications.php` içine HTTP fixture için `URL::forceScheme('http')` eklendi. Production origin kontrolü gevşetilmedi, uygulama .env'i veya bildirim ayarları değiştirilmedi. Bildirim/iletişim/wizard birlikte **46 test/265 assertion** geçti; yanlış origin'in503 vermesi korundu.

Son yeni menü metni ve fixture düzeltmesi sonrasında **tek temiz genel koşu:816/5.130 başarılı**, 91,705 saniye, PHP8.5.8/MySQL testing. Genel suite tekrar tekrar çalıştırılmadı: ilk koşu, neden için dar1 ve ilgili46, ardından son temiz genel koşu. Yeni Fransızca dosyası dört test içerir; ilk dar koşu4/201 geçti, üç menü anahtarı son genel koşuya dahil edildi.

## 4. Diğer doğrulamalar

| Kontrol | Sonuç |
|---|---|
| Bütün PHP suite | 816 test /5.130 assertion, başarısız0 |
| Bütün mevcut Frontend CJS dosyaları | Node31 test grubu başarılı; iki formatter içindeki17 assertion dahil |
| Bağımsız yedek Bash sözleşmesi | 16 Python testi başarılı; gerçek gzip/ZIP/SHA256 ve sahte DB/R2, ağ isteği yok |
| Birinci taraf PHP syntax | İlk573 dosya hata0/uyarı0; son eklenen menus.php ve değişen test dosyalarında dar syntax da başarılı; güncel toplam574 |
| Özel JavaScript/CJS syntax | 16 dosya, hata0 |
| Composer | validate ve check-platform-reqs --no-dev başarılı; install/update yok |
| Yazım/veri biçimi | Değişen5 PHP dosyasında Pint --test; tüm dil JSON'ları parse; whitespace başarılı |
| Dil dosyaları | Test öncesi62 dosyanın SHA'sı testler sırasında aynı; yeni menus.php yalnız geliştirmenin parçası |
| Eski veri | Ana/testing kaynak sayıları ve lig/sonuç SHA özetleri aynı |

Ücretli AI isteği, gerçek e-posta/push/R2 gönderimi yapılmadı. AI/iletişim testleri fake yanıt kullanır, TestCase dış HTTP kaçaklarını reddeder. SMTP'nin ve gerçek bildirim tesliminin önceki kullanıcı kabulü korunur; yeniden gerçek teslim testi yapılmış sayılmaz.

Vite build bu kontrolün parçası değildir: mevcut ekranlar `@vite` kullanmadan depodaki statik tema/özel dosyaları sunuyor. Paket veya CSS/JS içeriği değişmedi; APP_VERSION/root worker1.1.29 korundu.

## 5. Gerçek Chrome işlevsel kabulü

frontend-testing-debugging rehberi kullanıldı. Browser plugin/skill bulunmadığından Playwright + kurulu Google Chrome, ayrı geçici profil. `http://localhost`, gerçek route/controller/Blade/Livewire/Bootstrap ve testing DB'de tek transaction köprüsü kullanıldı. Başka öğretmen/öğrenci verileri ana DB'den okunmadı. API/SMTP/queue fake ve dış CDN/font istekleri engelliydi.

**54 ziyaret /22 yönetici ziyareti**, masaüstü1440×1000 ve mobil390×844. Son akış başarılı:

1. Misafir: ana sayfa, dersler, testler, sınavlar, blog, iletişim, SSS, lig, giriş/kayıt/parola kurtarma ve test/sınav detayları13 ekran.
2. Gerçek sentetik öğrenci username/parola girişi, session-cookie devamı ve profil yönlendirmesi.
3. Profil, favoriler, istatistik, kişisel lig, test geçmişi, yarım testler, sınav geçmişi, çalışma takibi ve bildirimler.
4. Testte gerçek radio seçimi/Livewire kaydı.
5. Süreli sınavda sunucudan1/2 sayacı geldikten sonra reload; cevap korunması; onay sonrası finishedModal.
6. Yönetici index ekranları gerçek atanmış route ve modül izinleriyle; Gate bypass kullanılmadı.
7. Fransızca ortak Bootstrap arama/sayfalama/durum metinleri, profil/çalışma/bildirim/lig/panel mobil görünümleri.
8. FR lig başlığında Türkçe kaynak metnin kaybolması ve hesap başlığının Fransızca olması.

Sayfa kimliği/title ve anlamlı içerik kontrol edildi; framework hata ekranı görülmedi. **JS runtime exception0 / HTTP5xx0**, kontrol edilen mobil sayfalarda document overflow0. Tablo kendi alanında yatay kaydırılabilir. Ekran görüntüleri gözle incelendi. Safari/fiziksel cihaz/PWA native bildirim izni bu tur çalıştırılmadı; önceki kabul sınırları korunur.

Otomasyon fixture'larında username–email eşlemesi, yönetici izinleri ve uzun lig kodu gerçek şemaya uygun tamamlandı. Radio'nun yalnız yerel seçimini değil sunucu kaydını bekleyen doğrulama kullanıldı. Bunlar ürün kodu hatası olarak sunulmaz. Geçici kernel/URL başlatma ve vendor locale metnini bekleme koşulları da yalnız otomasyonda düzeltildi. Bütün fixture'lar transaction rollback ile kaldırıldı.

Kanıtlar yalnız `/tmp`: `eduan-general-browser-result.json`, `eduan-general-fr-panel-desktop.png`, `eduan-general-fr-panel-mobile.png`, `eduan-general-fr-account-mobile.png`, `eduan-general-fr-league-mobile.png`. JUnit container `/tmp/eduan-general-acceptance-final-junit.xml`. Sırlar ve gerçek kullanıcı bilgileri repoya alınmadı.

## 6. DB / migration sınırı

Son genel suite ve tarayıcı sonrasında salt okunur kontrol:

- Ana:35lig,1238sonuç,149914test cevap detayı,10695sınav cevap detayı,3dil,2557çeviri.
- Testing başlangıcı:1lig,1sonuç,2test cevap detayı,9sınav cevap detayı,2dil,1308çeviri.
- Her iki DB'de eski lig/sonuç hash'leri önceki referansla aynı; kalıcı canlı/snapshot/cutoff/closed metadata0.
- Ana yerel migration listesinde bekleyen yok; bu tur migration uygulanmadı.
- Testing'de5Ekim performans indeks migration'ı kalıcı olarak Pending görünür. 30/31 raporlarında belirtildiği gibi onayı ana DB için verilmişti; `InitialQueryIndexesTest` üç indeksi up/down ile sınayıp başlangıç şemasına döndürür. Diğer feature migration'ları uygulanmış durumda. Bu tur ayrıca kalıcı DDL izni istenmedi/uygulanmadı; üretim yük/performance parity kabulü yapılmıyor.

## 7. Canlı sunucuda salt okunur PHP kontrolü

25Ekim2026 **15:36:34 UTC**, mevcut SSH hesabıyla sadece CLI metadata ve bellekte2×2 PNG üretimi çalıştı:

- `/usr/bin/php85`: **8.5.12**;
- Imagick3.7.0 kurulu; varsayılan CLI yüklemesi **0**;
- `-d extension=imagick.so` ile Imagick, GD, pdo_mysql, fileinfo, zip, openssl, mbstring, dom, xml, simplexml ve tokenizer açık;
- WebP format desteği var; bellekte PNG127byte üretildi.

Web PHP sürümü, php.ini, cron/worker, uygulama, dosya veya DB değiştirilmedi. Yayındaki eski uygulamanın bu geliştirmelerle çalıştığı iddia edilmez. Önceki yayın kararı geçerli: PHP8.5 proje aktarımı sırasında scheduler/queue komutlarında **php85 -d extension=imagick.so** kullanılacak. Örnek `scripts/siteground-cli-cron.example`; ayrı alt PHP süreci başlatan gelecekteki komutlar parametreyi otomatik devralmaz.

## 8. Yayın günü tamamlanacak işler

1. Kayıtlı yayın/geri dönüş planına göre dosya ve veritabanı yedeği; mevcut uygulama, depolama ve .env sınırlarını koruma.
2. Canlı migration durumunu okuyup gerekli migration/izinleri kontrollü uygulama; eski lig tarihlerini değiştirmeme, yeni modül aktivasyonlarını planlanan ayarlarla yapma.
3. PHP8.5 web/CLI ve Imagick bayrağıyla mevcut scheduler/worker komutlarını düzenleme; dört saatlik canlı lig görevinin scheduler'da işlemesini kontrol etme.
4. Production URL/origin, yerel/canlı bildirim ID-key ayrımı, dört global AI ayarı ve günlük limit/anahtar/cevapların korunması; app/worker1.1.29 ve cache adımları.
5. Giriş, test/sınav kaydı, çalışma takibi, panel, lig ve kuyrukta kısa canlı smoke; kullanıcı kabulüyle bildirim sorunu olursa ayrıca inceleme.

Bu adımların tamamlanması için bu test turunda canlı yazma işlemi yapılmadı. Kod bakımından açık düzeltme engeli kalmadı; kontrollü yayın hazırlığına geçilebilir.

## 9. Değişen dosyalar

Ürün: `lang/fr.json`, yeni `lang/fr/account.php`, `lang/fr/tables.php`, `lang/fr/menus.php`, `resources/views/components/admin/server-table.blade.php`.

Test: yeni `tests/Feature/FrenchSharedPresentationTest.php`, `tests/Concerns/UsesWebCommunications.php`.

Rehber: AGENTS.md, README.md, standartlar16, bu81 raporu, Codex/index.html ve73/74/80 raporlarındaki tarihsel bulguların kapanış bağlantıları.

Önceki `.DS_Store`, wiki `Doge.html`→`docs.html` değişikliği ve özgün OneSignal klasörü korunur/commit'e dahil edilmez. Testin ürettiği tek yeni Python bytecode cache'i temizlendi; proje/kullanıcı dosyası silinmedi.
