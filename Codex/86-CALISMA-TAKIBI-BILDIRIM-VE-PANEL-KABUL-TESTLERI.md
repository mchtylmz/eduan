# 86 — Çalışma takibi, bildirim ve panel kabul testleri

26 Ekim 2026. Kullanıcı, son geliştirmelerden sonra dashboard ve öğrencinin çalışma ekranlarını birleşik ve kombinasyonlu test ederek canlıya uygunluğu değerlendirmemizi istedi. İncelenen uygulama sürümü **1.1.39**, başlangıç commit’i **8b638541**.

## 1. Karar

**İncelenen son geliştirmeler, dashboard/çalışma takibi/bildirim kapsamı için kontrollü canlı aktarıma uygundur.** Son temiz birleşik koşuda uygulama hatası veya bu kapsama ait yeni bir yayın engeli bulunmadı. Uygulama kodunda düzeltme gerekmedi; test beklentisi güncellendi ve kabul testleri eklendi.

Bu, uygulamanın bütün menülerine, sınırsız girdiye veya gerçek üretim teslimine sıfır hata garantisi değildir. Canlı ayar/şema/cron-worker ve yayın sonrası smoke kontrolü yayın aşamasının kapısıdır. Önceden raporlanan dönem filtresi maddeleri bu tur kapatılmadı (bölüm7).

## 2. Sonuç sayıları

| Kontrol | Toplam | Çalıştırılan | Başarılı | Başarısız | Çalıştırılmayan |
|---|---:|---:|---:|---:|---:|
| Son birleşik PHP koşusu / 25 ilgili dosya | 539 | 539 | 539 | 0 | 0 |
| İş kuralı kombinasyon matrisi (üstteki539’a dahil) | 192 | 192 | 192 | 0 | 0 |
| Tanımlı tarayıcı dil/ekran/sayfa-durum matrisi | 180 | 180 | 180 | 0 | 0 |
| Node test runner | 37 | 37 | 37 | 0 | 0 |
| Tarayıcıdan XLSX indirme türleri | 6 | 6 | 6 | 0 | 0 |

PHP: **2.617 doğrulama**, son tek temiz koşu48,24 saniye. Node runner37 sonucu, çalışma tablosu formatter dosyasındaki12 ayrı doğrulamayı da içerir. Bu sayılar birbirine eklenip tek test toplamı olarak sunulmaz.

## 3. Sonlu kombinasyonların tanımı

### İş kuralları — 192

`6 rol × 2 modül durumu × 2 geçmiş giriş ayarı × 4 tarih sınıfı × 2 işlem türü`.

- Roller: öğrenci kendi hesabı, bağlı öğretmen, yönetici, yönetici+öğretmen, bağlı olmayan öğretmen, düzenleme izni olmayan bağlı öğretmen.
- Modül: açık/kapalı. Geçmiş çalışma girişi: açık/kapalı.
- Tarih: başlangıçtan önceki tam hafta, kapanmış hafta, mevcut hafta, gelecek hafta.
- İşlem: hedef/manuel çalışma.
- Hedefin kapanmış haftada yalnız yöneticide kalması, geçmiş ayarının sadece çalışmayı açması, ilişki/izin sınırı ve başlangıç/gelecek kısıtları gerçek `AuthorizeStudyChange` üzerinden denetlendi.
- İlk fixture’da başlangıçtan önce diye seçilen26Eylül haftası1Ekim başlangıcını da içeriyordu; kullanıcı kararı gereği hafta bölünmediğinden8 beklenti yanlıştı. Tamamen başlangıç öncesindeki19Eylül haftası kullanıldı. İş kuralı değiştirilmedi; son192 matris temiz geçti.

### Tarayıcı — 180

`4 dil × 3 genişlik × 15 sayfa/durum`.

- Diller: TR, NL, EN, FR. Genişlikler:1440,768,390 piksel.
- Öğrenci: haftalık özet, geçmiş, konu analizi, öğretmen notları, değerlendirmem —5.
- Yönetici öğrenci detayı: özet, geçmiş, çalışma kayıtları, konu analizi, notlar —5.
- Dashboard ana sayfa, çalışma takibi ana listesi, toplu iletişim geçmişi —3.
- Bildirimler: kapalı/açık izin görünümü —2.

Her hücrede doğru HTTP200/dil, seçili sekme, gerçek içerik, ilgili Bootstrap Table başlatılması, not gizliliği ve yatay belge taşması kontrol edildi. Bildirim açık görünümü SDK ve sunucu kaydı sonrası doğrulandı; kapalı görünümde işlem alanı açık kaldı. Matris12 dil/genişlik grubunun tamamını kapsar; örnek/pairwise seçimi değildir.

Tarayıcı matrisi iki başarılı parçada tamamlandı: ilk5 grup75 hücre, kalan7 grup105 hücre. İlk devam sırasında hızlı gezinme mevcut abonelik kayıt sınırına ulaştı; ilgili429 başarı diye sayılmadı. Kalan gruplar yalnız testing array limit sayacı her bağımsız grupta sıfırlanarak tekrar alındı. Son yedi grubun kaydındaki112 ziyaret,105 matris hücresine ek yedi etkileşim ziyaretini içerir; ilk75 ile toplam187 başarılı ziyaretlik kanıt vardır. Tek kesintisiz180 koşu iddiası yoktur.

## 4. Ek işlev ve regresyonlar

- PHP/Livewire: modül/izin ayarları; hedef ve manuel kayıt ekleme/düzenleme/kaldırma; UUID tekrarları; stale conflict; tarih ve hafta sınırları; saat/ondalık/sıfır/boş/hatalı değerler; soft delete/audit/transaction rollback.
- Otomatik sorular: yarım/tamam/tekrar denemeler, doğru/yanlış, cevap değiştirme/temizleme, son cevap zamanı ile haftanın taşınması, eski created_at fallback’i, kişisel toplamlar ve devam eden sınav analizi.
- Grafik/analiz: hedef yok/sıfır çalışma/düşük gerçekleşme ayrımı, yüzde100 aşımı, konu eşik/asgari soru, anonim öğrenci karşılaştırması ve staff dışlanması, ortak dönem haftası.
- Panel/notlar: öğretmen yalnız bağlı öğrencisi; bağlı olmayan öğrenciye erişim yok; yazar/yönetici düzenleme, diğer öğretmenin okuması; öğrenci yalnız paylaşılmış kendi notunu görür. Not ve tablolarda HTML kaçırma ve izin geri alma sınırları.
- Tarayıcıda öğretmen listesi yalnız1 bağlı öğrenci gösterdi; yeni iletişim izni olmayan öğretmende kaynak gönderim düğmeleri yoktu. Öğretmenin kendi ön yüz raporu ayrı açıldı.
- Mobil hedef180 olarak kaydedildi, panel kapandı, yenileme sonrası180 korundu; panel genişliği390px ekrana uydu.
-17 çalışma kaydı Bootstrap Table’da15+2 sayfalandı; belge navigasyonu oluşmadı.
- Yönetici notu varsayılan gizli açıldı; özellikle paylaşılan yeni not öğrencide göründü, gizli not görünmedi.
- Liste16 filtreli öğrencide15+1 sayfalandı; ikinci sayfadayken iletişime tüm16 alıcı taşındı. E-posta/aktiflik/durum/hafta filtresi ve kaynak kapsamı korundu.
- Ana liste ve geçmiş/kayıt/günlük toplam/konu/not XLSX dosyaları gerçekten indirildi. PHP tarafında PhpSpreadsheet ile satır/değer/hücre türü ve kapsamları da kontrol edildi.
- Mevcut üç adımlı iletişim: alıcılar → mesaj → kişisel ön izleme → fake outbox; tek öğrenciden push hazırlama. Mail/queue fake, dışarı teslim yok.
- Bildirim: açık onay, sessiz sync, cihaz sahipliği, yeniden kayıt, tekil kapatma, bekleyen kapatma, son10 kişisel mesaj, hata/desteklenmeyen/iOS durumları; frontend JS ve HTTP kontrolleri birlikte.
- Yeni rate-limit testi: aynı kullanıcıya30 kayıt200,31’inci429 ve Retry-After; tek abonelik kalır,31’inci işlem sağlayıcı isteği oluşturmaz.
- Dashboard rol/kapsam/istatistik, ortak Bootstrap Table, kişisel hesap raporları ve süreli sınav regresyonları aynı birleşik koşuya dahildir.

## 5. Komutlar ve ortam

Son PHP koşusu:

```sh
docker compose exec -T laravel.test php artisan test --compact --no-ansi \
  tests/Unit/Study*Test.php tests/Feature/Study*Test.php \
  tests/Feature/{CommunicationsTest,CommunicationWizardTest,WebNotificationsTest,PwaReleaseTest,DashboardStatsTest,AccessBoundaryTest,StudentAccessTest,StudentStatsAccessTest,BootstrapTablePilotTest,FrontendAccountPresentationTest,FrontendAccountReportsTest,TimedExamTest}.php
```

Node: `WebPushTest.cjs`, `StudyPanelFormatterTest.cjs`, `CommunicationSelectionTest.cjs`, `PwaSafetyTest.cjs`, `ExamSessionTest.cjs`. Değişen üç test dosyasında Pint başarılı; `git diff --check` temiz. Üretim bağımlılıkları için containerPHP8.5.8 üzerinde `composer check-platform-reqs --no-dev` başarılı. Asset/root worker **1.1.39** eşleşir, config cache yok.

Browser plugin not available. Kurulu Chrome/Playwright, gerçek Laravel HTTP kernel’i ve ayrı `testing` DB dış transaction’ı kullanıldı. Çıkışta fixture satırları rollback edildi. SDK/sağlayıcı yanıtları, mail ve queue testtedir; gerçek dış servis çağrısı yapılmadı.

Sayfa JS istisnası0, beklenmeyen HTTP5xx0;180 matris hücresinde yatay taşma0. Testte engellenen Google font/çerez CDN kaynakları ve bloke edilmiş service worker nedeniyle oluşan iki mevcut PWA uyarısı konsolda ayrıca kaydedildi; bunlar gerçek PWA/Safari kabulü değildir. İlk test köprüsünün Bootstrap yükleme katmanını `:visible` ile beklemesi API/satır/sayfa kontrolüne düzeltildi; uygulama kodu değişmedi.

Geçici kanıtlar:

- `/tmp/eduan-release-tests.log`:539/2617 son temiz koşu.
- `/tmp/eduan-acceptance-results.json`: kalan105 matris+7 etkileşim, hata ve konsol kayıtları; ilk75 tamamlanması önceki yürütme çıktısındadır.
- `/tmp/eduan-acceptance-actions.log`: altı XLSX ve iletişim akışları.
- `/tmp/eduan-acceptance-front.png`, `/tmp/eduan-acceptance-panel-mobile.png`: görsel kabul.

## 6. Düzeltilen test / değişen dosyalar

İlk birleşik koşu346 senaryoda345 başarılıydı. Tek hata, ilk çalışma izin migration’ının tüm `study:*` sayısını3 varsaymasıydı. Sonradan ayrı migration ile gelen `study:communicate` nedeniyle sayı4’tü. Test, ilk üç iznin yanında mevcut diğer çalışma izinlerinin korunmasını karşılaştıracak şekilde güncellendi; idempotency/rol/APIguard/down kontrolleri korunur. Uygulama izinleri veya migration kodu değiştirilmedi.

- `tests/Feature/StudyTrackingFoundationTest.php`: yukarıdaki test beklentisi.
- `tests/Feature/StudyReleaseCombinationsTest.php`:192 tam kombinasyon, kalıcı regresyon testi.
- `tests/Feature/WebNotificationsTest.php`:30/31 kayıt rate-limit regresyonu.
- Bu belge, `AGENTS.md`, `README.md`, `Codex/index.html`: sonuç/kapı kaydı.

Uygulama PHP/Blade/CSS/JS/route, şema, kullanıcı/çalışma verileri ve canlı ayarlar değiştirilmedi; sürüm artırılmadı. Ana yerel DB’de yalnız migration durumu okundu: tüm mevcut migration’lar Ran. Testing’de çalışma/bildirim migration’ları Ran; eski `2026_10_05_150000_add_initial_query_performance_indexes` hâlâ Pending. Bu tur uygulanmadı; ana yerelde zaten Ran. Kalıcı migration/seed/sıfırlama/deploy yok. Başlangıçtaki `.DS_Store`, `Codex/Doge.html` silinmesi, `Codex/docs.html` ve yüklenen worker klasörü bu işin dışında kalır.

## 7. Açık sınırlar ve yayın kapısı

- Bu kabul son geliştirmeler/dashboard/çalışma/bildirim kapsamındadır. Önceki test/sınav sonuçları için `Dönem yok` filtresininNULL kapsamı ve `Tümü` seçiminin yenilemede korunmaması bulguları84 bölüm7’de açık durur; bu tur düzeltilmedi veya kapanmış sayılmadı.
- Gerçek SMTP/push teslim testi yapılmadı; önceki kullanıcı kabulü korunur. Yeni localhost uygulaması veya gerçek Safari testi tekrar istenmedi.
- Fiziksel Android/iOS/Safari, gerçek queue process/cron ve üretim yükü bu yerel koşunun kanıtı değildir.
- Yayında üretim APP_ENV/URL/ID-key ve HTTPS/worker scope; mevcut migration/izin/aktivasyon; PHP8.5 cron-worker/Imagick `-d extension=imagick.so`; cache/asset ve yayın sonrası gerçek sayfa kontrolü tamamlanmalıdır. Bu tur üretim üzerinde işlem yapılmadı.

İncelenen kod için kontrollü aktarım onayı verilir; üretim işletim kabulü yayın sonrası bu kapılarla tamamlanır. Tanımlı matrislerde çalıştırılmamış hücre yoktur; sınırsız girdi veya gelecekte sıfır hata iddia edilmez.
