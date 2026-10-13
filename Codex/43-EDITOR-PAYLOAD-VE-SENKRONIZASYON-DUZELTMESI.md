# Editör payload ve senkronizasyon düzeltmesi

Tarih: 6 Ekim 2026. Kullanıcı blog düzenleme ekranındaki PayloadTooLargeException hatasının çözümünü ve proje genelinde editörlerin aynı hata açısından taranmasını istedi.

## 1. Doğrulanan neden

Ekran görüntüsünde Livewire update isteği13470KB, izin verilen boyut1024KB. Ana yerel DB salt okunur ölçüldü: blog5 stored6781417 byte, decoded6780825 byte;18 img ve18 data:image, blob URL yok. Livewire snapshot ve güncelleme aynı içeriği tekrar taşıyınca istek yaklaşık iki katına çıkar. Yeni Livewire4 varsayılanı1MiB olduğu için mevcut içerik bu sınıra sığmıyor. Yerel PHP post_max_size100M; ilk engel PHP değil Livewire guard.

| Editörlü tablo | Maksimum stored content byte | data:image içeren kayıt |
|---|---:|---:|
| blogs | 6781417 | 1 |
| faqs | 9066 | 0 |
| pages | 28162 | 0 |
| exams | 2439133 | 14 |
| tests | 779 | 0 |

Bu tablo yalnız içerik boyutu sayımlarını gösterir; özel içerik rehbere kopyalanmadı. Exam/Test isimlerinin projedeki ters iş eşlemesi korunur. Bütün DB kolonlarının veya bütün ürün senaryolarının tekrar denetlendiği iddia edilmez.

## 2. Uygulanan dar düzeltmeler

- livewire.payload.max_size sonlu16MiB oldu. Nesting10, calls50, components200 korundu; guard devre dışı bırakılmadı. Bu boyut ölçülen eski snapshot+update isteğini karşılar.
- Ortak Livewire editörü init sırasında veya her keypress'te ağ isteği göndermez. Change/input/undo/redo editör içeriğini client Livewire durumuna set(...,false) ile aktarır; sonraki işlem bu güncellemeyi taşır.
- Sınav bölüm içerik modal'ı aynı deferred kuralını kullanır; change/input/undo/redo olayları metnin son halini aktarır.
- Normal form editörü init/change/input/undo/redo sırasında editor.save kullanır; yazılan son metin textarea'da hemen bulunur.
- Her ortak editör yalnız kendi ID'sini başlatır; tinymce.get guard tekrar başlatmayı engeller. Önce bütün textarea'ları her bileşen scriptinde tarayan başlatma kaldırıldı. Ayrı alan yükseklikleri korunur.
- Ortak TinyMCE script/style kaynakları @once; fullscreen plugin core ile aynı7.2.1 sürümüdür. Modal'daki sürüm de eşitlendi; geçersiz forced_root_block:false kaldırıldı.
- Textarea başlangıç içeriği Blade escape ile yazılır; HTML editör içinde metin olarak kalır, textarea'yı kapatıp sayfada HTML çalıştırmaz.
- Blog/çok dilli sayfa/SSS value parametreleri gerçek PHP bindingleriyle aktarılır; önceden raw/echo karışımı olan alanlar aynı içerik sözleşmesini korur.
- Dosya seçimi iptal edilirse FileReader çağrılmaz; blob cache aktif global editör yerine ilgili editör ID'sinden bulunur.
- Blade içindeki JavaScript değişikliği nedeniyle APP_VERSION1.1.5. Config cache, deploy, migration veya ana DB write yapılmadı.

## 3. İncelenen editör kullanım haritası

Ortak Livewire bileşeni Blog, Diğer Sayfalar, SSS, Test açıklaması, Sınav açıklaması, Kullanıcı maili, İstatistik maili, Abone maili, İletişim yanıtı ve AI cevap alanında kullanılır. Normal form kullanımları settings e-posta şablonları ve popup'tır. Sınav bölüm içerik modal'ı layout içinde ayrı init kullanır; o da düzeltildi.

Kaynakta bu kullanımların init/model bağları tarandı. Her ekranın kaydetme/gerçek mail/görsel yükleme işlemi çalıştırılmadı; diğer iş kurallarını veya izinlerini değiştirmek bu tur kapsamı değildir.

## 4. Test kanıtı

Yeni tests/Feature/EditorPayloadTest.php:

1. Sentetik yaklaşık6.8MB Base64 içerikli blogu ayrı testing DB transaction içinde oluşturur.12MiB üzerindeki gerçek JSON snapshot+update isteğini Livewire endpointine gönderir; HTTP200 ve güncel content döner. Blogun kalıcı content'i değişmez; save çağrılmaz.
2.16MiB üzerindeki istek PayloadTooLargeException ile reddedilir; diğer üç guard aynı kalır.
3. İki normal editör render'ında textarea escape, yalnız bir TinyMCE core yüklemesi, alan başına init guard, alan seçici ve senkronizasyon olayları sınanır.

- Üç test/17 doğrulama geçti. Son normal-form input düzeltmesinden sonra yalnız üçüncü test tekrarlandı:1 test/7 doğrulama geçti.
- PluginLocaleTest'in panel ve normal editör için nl/en/tr altı ilgili vakası geçti; frontend-only vakalar bu tur çalıştırılmadı.
- İlk iki HTTP testinde X-Livewire başlığı eksik olduğu için404 alındı; test isteği gerçek protokole göre düzeltildi, iki vaka sonra geçti. Uygulama route'u değiştirilmedi.
- PHP8.5 config/test syntax, Pint ve ilgili diff whitespace kontrolü geçti.
- Ana DB'ye test verisi/migration/seed yazılmadı; testing transaction'ları geri alındı. Gerçek mail/queue/AI çağrısı yapılmadı. Tüm proje suite/build çalıştırılmadı.

## 5. Tarayıcı kanıtı ve sınırı

Browser plugin mevcut değil; frontend-testing-debugging rehberiyle Chrome/Playwright fallback kullanıldı. Mevcut yalnız testing DB kullanan QA harness geçici foreground http://127.0.0.1:8765 üzerinde çalıştı; normal localhost80/3306 değişmedi. Sentetik QA hesabı,1440x1000 viewport. Giriş QA hesabının normal last_login_at bilgisini günceller; ana kullanıcı hesabı kullanılmadı. Form save/silme/gerçek yükleme/mail yapılmadı.

- Blog düzenleme sayfası anlamlı içerikle açıldı; hata overlay yok. Editöre yazmak600ms içinde POST üretmedi.
- Sonraki live başlık güncellemesi editor content'i aynı request updates içinde taşıdı ve HTTP200 döndü. Livewire morph sonrasında editör metni kaldı.
- E-posta şablonları sayfasında iki dilin altı editörü ayrı ID ile açıldı. Görünen nl editörleri300px; gizli en tabında ölçülen yükseklik0 görünmez alanın durumudur.
- İkinci editöre yazılan metin yalnız kendi textarea'sına anında aktarıldı. Bu kontrolde ortaya çıkan son-metni-aktar sorunu input/undo/redo eklenerek düzeltildi.
- Kontrol edilen akışlarda ilgili pageerror/console error yok. Blog ekranı screenshot'u görsel olarak incelendi.
- QA scriptinin TinyMCE get API ve dil başına editör sayısı beklentileri düzeltildi; bunlar uygulama hatası olarak sayılmadı.
- Kanıtlar /tmp/eduan-editor-blog-1006.png ve /tmp/eduan-editor-settings-1006.png; script /tmp/eduan-editor-qa.cjs. Bunlar geçici yerel kanıttır.
- Mobil/tüm editörlü sayfaların gerçek save/gerçek görsel yüklemesi sınanmadı. Büyük payload kanıtı otomatik HTTP testidir; ana blog5 kaydedilmedi.

## 6. Sonraki ayrı karar gerektiren optimizasyon

Base64 görseller eski DB içeriğinde kalır; bu hata düzeltmesi onları dosyaya dönüştürmez. Yeni dosya upload endpointi veya mevcut15 kaydın görsellerini ayırma/veri taşıma bu tur yapılmadı. TinyMCE automatic_uploads açık fakat server upload handler yok; bu nedenle mevcut file picker Base64 içeriğe devam edebilir. Kalıcı boyut azaltma için panel yetkileri,10MB/MIME/extension, CSRF, kayıt iptalinde dosya yönetimi ve CDN planı ile gerçek görsel upload akışı ayrıca tasarlanmalıdır. Mevcut içerik taşıması kullanıcı onayı gerektirir.16MiB sınırsız büyüklük garantisi değildir; daha büyük yeni içerik aynı guard'a takılabilir.

Referans: [TinyMCE image upload dokümanı](https://www.tiny.cloud/docs/tinymce/latest/upload-images/), [görsel seçenekleri](https://www.tiny.cloud/docs/tinymce/latest/file-image-upload/). Bunlar upload handler/save öncesi yükleme yaklaşımını açıklar; bu tur yeni upload sistemi uygulanmadı.

## 7. Değişen dosyalar

- config/livewire.php: sonlu payload boyutu ve korunmuş guard'lar.
- resources/views/components/tinymce/editor.blade.php: deferred/normal-form senkronizasyon, alan başlatma, escape, ortak asset, picker iptali.
- resources/views/backend/layouts/app.blade.php: modal senkronizasyonu, fullscreen sürümü, picker iptali/editör kimliği.
- resources/views/livewire/backend/{blogs/blog-form,pages/page-form,faqs/faq-form}.blade.php: PHP value bindingleri.
- resources/views/livewire/backend/tests/select-content-modal-form.blade.php: textarea escape.
- tests/Feature/EditorPayloadTest.php: üç dar regresyon senaryosu.
- .env: yalnız APP_VERSION1.1.5.
- AGENTS.md, Codex/16-PROJE-STANDARTLARI.md, bu kayıt ve Codex/index.html: güncel standart/kanıt.
