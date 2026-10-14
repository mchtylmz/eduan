# Toplu iletişim — üç adım ve standart tablo

14 Ekim 2026. Önceki Faz 7 commit: `6cc0ded9`. Kullanıcının menü, Bootstrap Table ve sade gönderim akışı düzeltmesi.

## 1. Standartlar ve kapsam

Kod değişikliğinden önce AGENTS.md, README, `16-PROJE-STANDARTLARI`, `03-KOD-YAZIM-REHBERI`, `20-SAYFA-DUZENI-VE-TEST-MATRISI`, ilgili Faz 6/7 rehberleri ve gerçek ortak tablo/form örnekleri okundu. OneUI blok, mevcut Bootstrap/Livewire/Blade, filtre bileşenleri ve `x-admin.server-table` kullanıldı. P01/P03/P06/P07/P14/P16/P19/P22/P25/P87/P92 karşılıkları uygulandı. Yeni framework, editör veya bağımlılık eklenmedi.

AGENTS.md'ye geliştirmeden önce standartları okuma zorunluluğu kalıcı başlangıç kuralı olarak yazıldı; `16` rehberinde P141–144 bu ekranın sözleşmesini kayıt altına alır. Bu değişiklik diğer mevcut form ekranlarını yeniden tasarlama kapsamı değildir.

## 2. Ekran ve menü

- Mevcut Toplu E-posta ve Bildirim menüsü **Yönetim → Kullanıcılar'ın üstüne** taşındı; görünürlük izinleri korunur.
- `/admin/communications` önce kayıtlı gönderim geçmişini açar. Düz HTML son kayıt tablosu kaldırıldı.
- Geçmiş ortak Bootstrap Table/server JSON'dur: 15 kayıt/sayfa, tarih azalan varsayılan, SQL arama, kanal/durum filtresi, sıralama, yenileme ve standart sayfalama.
- Öğretmen kendi gönderim kayıtlarını; yönetici tüm kayıtları görür. Her JSON isteği güncel izni doğrular. Mesaj gövdesi, sağlayıcı ID/hata/secret alanları listede döndürülmez.
- Worker durumları değiştiğinden geçmiş sorgusu private/no-store güncel veri döndürür; sonuç cache'i eklenmedi. Mevcut kayıtlar silinmedi veya yeniden yazılmadı.

## 3. Yeni gönderim

1. **Alıcılar:** mevcut scoped Bootstrap kullanıcı tablosu, sayfalar/arama arasında seçim; seçilen sayı ve Devam düğmesi tablonun üstünde ve kaydırmada görünür.
2. **Mesaj:** kanal, dil, başlık ve mevcut düz metin içerik. `{{name}}`/`{{surname}}` desteği korunur. Zengin metin editörü bu revizyonda eklenmedi.
3. **Ön izleme:** alıcı sayısı/ilk beş isim, kanal/dil, bir alıcıya göre kişiselleştirilmiş başlık/metin ve **Gönder**. Bu adım gönderim onayıdır; ek dördüncü dialog yoktur.

Geri dönünce taslak ve seçim korunur. İptal temizler. Adım geçişi form başına döner; aşağıdaki başka forma kaydırmak gerekmez. Geçmiş DOM'u adımlar arasında gizli korunur; kapanınca mevcut tablo yenilenir.

## 4. Sunucu sınırları

Adım, ön izleme, fingerprint ve UUID Locked'dır. Ön izlemeyi atlama engellenir. Son gönderim girdisi ön izlemeyle karşılaştırılır; değiştirilmiş içerik ikinci adıma döner ve yeniden ön izleme ister. Her adımda güncel rol/alıcı ilişkisi/aktiflik, son işlemde kanal ayarı da tekrar doğrulanır. Alıcı yetkisi toplu SQL ile kontrol edilir; kullanıcı başına ilişki sorgusu yapılmaz.

Mevcut transaction, aktör kilidi, idempotent UUID, öğretmen kapsamı ve asenkron beş/dakika gönderim korunur. Gönder düğmesi dayanıklı kuyruk kaydı oluşturur; başarı metni kuyruğa alındığını söyler, teslim edildiğini iddia etmez. Ön izleme kayıt veya dış istek oluşturmaz. Metin/kişiselleştirme HTML olarak çalıştırılmaz.

## 5. Dar test sonucu

| Kontrol | Sonuç |
| --- | --- |
| Wizard + mevcut iletişim + PWA release PHPUnit | **34 test / 236 assertion**, başarılı |
| Seçim JS + PWA Node | **15 test**, başarılı |
| TR/NL/EN/FR | **94 eş anahtar**, boş değer yok |
| İlgili PHP/Pint ve JavaScript syntax | Başarılı |
| Chrome 1440/390 | Geçmiş sayfa/arama/filtre/reset, iki sayfadan seçim, üç adım, geri dönüş, kişisel ön izleme, gönderim sonrası kayıt yenileme |
| Chrome hata sınırı | JS0 / HTTP5xx0 / mobil belge taşması0 |
| `git diff --check` | Başarılı |

PHP senaryoları ayrıca yetkisiz liste/aktör, hatalı query parametreleri, alıcı ilişkisinin kaldırılması, rol kaybı, boş metin, HTML kaçış, Locked adım, ön izleme atlama ve sonradan içerik değişmesini kapsar. Tüm proje testleri tekrar çalıştırılmadı.

Browser plugin bulunmadığından mevcut Playwright ve Google Chrome kullanıldı. Laravel testing HTTP kernel + outer transaction rollback, mail/HTTP/queue fake kullanıldı; dış ağ talepleri engellendi/taklit edildi. İlk QA koşularında test başlık beklentisi ve aramayı klavye olayıyla tetikleme düzeltildi; son ekran kanıtında mobil menü kapatılıp Livewire adım render'ı beklendi. Ürün davranışı bu QA sorunları için değiştirilmedi.

Kanıtlar `/private/tmp/eduan-communication-wizard-result.json`, `/private/tmp/eduan-communication-history.png`, `/private/tmp/eduan-communication-selection-mobile.png`, `/private/tmp/eduan-communication-message-mobile.png`, `/private/tmp/eduan-communication-preview-mobile.png`, `/private/tmp/eduan-communication-preview.png`.

## 6. Değişen dosyalar

- İşlem/istek: `app/Actions/Communications/CreateCommunicationBatch.php`, `app/Http/Controllers/Backend/CommunicationController.php`, `app/Livewire/Backend/Communications/CommunicationForm.php`, `routes/backend.php`.
- Görünüm: `resources/views/backend/layouts/sidebar-menu.blade.php`, `resources/views/backend/communications/index.blade.php`, `resources/views/livewire/backend/communications/communication-form.blade.php`.
- Asset: `public/backend/assets/communications.js`, `public/backend/assets/app.css`, `public/pwa-sw.js`; yerel ignored `.env` APP_VERSION.
- Diller: `lang/{tr,nl,en,fr}/notifications.php`.
- Test: `tests/Feature/CommunicationsTest.php`, yeni `CommunicationWizardTest.php`, `tests/Frontend/WebPushTest.cjs`, yeni `CommunicationSelectionTest.cjs`. Seçim testi eski web push dosyasından bağımsız dosyaya taşındı.
- Rehber: `AGENTS.md`, `README.md`, `Codex/16-PROJE-STANDARTLARI.md`, bu rapor ve `Codex/index.html`.

CSS/JS nedeniyle APP_VERSION ve kök worker birlikte **1.1.28**. Worker URL/id/scope korunur; `.env` commit edilmez. Önceden değişmiş `.DS_Store` ve orijinal `OneSignalSDK-v16-ServiceWorker/` commit dışıdır.

## 7. Elle inceleme ve kalan kapı

Panel → Yönetim → Toplu E-posta ve Bildirim. Önce geçmişi filtreleyin/sıralayın; Yeni gönderimden alıcı seçip Devam, mesaj, Ön izleme; Geri ile seçimlerin/taslağın korunmasını kontrol edin. Gerçek dış gönderim istenmiyorsa son Gönder yerine İptal kullanın.

Ana DB'ye yazım, migration, gerçek SMTP/push, ücretli AI, sunucu/servis/port/cron veya canlı yayın işlemi yapılmadı. Testing fixture'ları geri alındı. Kullanıcının yerel App ID/key ve Chrome girişinden sonra yapılacak gerçek bildirim kabulü `76` rehberinde beklemeye devam eder; bu düzeltme o kapıyı tamamlanmış saymaz.
