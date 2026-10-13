# İlk düzeltme paketi, sınav sözleşmesi ve doğrulama

5 Ekim 2026. Geliştirme öncesi Git sınırı: `2b31f4f5` (`yeni güncelleme öncesi 05 10`). Kullanıcıya `10-BULGU-VE-TEST-PLANI.md` gösterildi; ürün kararları sorularla netleştirildi. Bu kayıt ilk taramadaki durum anlatımlarından daha günceldir. Laravel/paket yükseltmesi, yeni migration ve üretime dağıtım bu paketin parçası değildir.

## Kullanıcının kesinleştirdiği kurallar

1. Flemenkçe kodu ve varsayılan dil `nl`; diğer hedef diller İngilizce ve Türkçe.
2. Misafir doğrudan sınav başlangıcına geldiğinde `login` rotasına yönlendirilir.
3. Bütün sınavlar tamamlandıktan sonra yeniden çözülebilir; şimdilik ayrı tekrar/tek hak ayarı yoktur.
4. Tek hak, aynı kullanıcı/sınav için tek aktif denemedir. Tamamlanana veya süresi dolana kadar yeniden giriş aynı denemeyi sürdürür.
5. İlk bitiş zamanı ve seçilmiş cevaplar korunur; sayfa yenilenmesi/yeniden giriş süreyi baştan başlatmaz.
6. Süre dolana kadar cevap değiştirilebilir veya seçimi kaldırılabilir.
7. Süre dolunca sunucuya ulaşmış mevcut cevaplarla deneme tamamlanır; artık o denemeye yazılamaz. Yeni deneme ayrı sonuç kaydı ve sınavın tam süresiyle başlar.
8. Sayfa kapalıyken de süre sonunda arka plan işi tamamlar. Lig hesabına yalnız tamamlanmış sınav denemeleri katılır.

## Güvenlik sınırlarının düzeltmesi

`public/systeminfo.php` kaldırıldı. Önceki yerel HTTP 200 yerine Docker içi HTTP 404 doğrulandı; canlıya dağıtılmadı.

`Frontend/ExamController::start` misafiri null kullanıcıya erişmeden girişe yönlendirir. Üyenin mevcut `tests:solve` ve sınav aktiflik davranışı korunur.

`Livewire/Tests/TestForm` edit düğmesinin izni `tests:update` yapıldı. Bu form ile `Livewire/Exams/ExamForm` kayıt anında oluşturma/güncelleme iznini modelin mevcut durumundan hesaplar. Public `$permission` değerine veya yalnız Blade düğmesinin gizlenmesine güvenilmez. `ExamForm::updateExamRelations` yalnız form içinden çağrılan private metottur; doğrudan Livewire çağrısıyla soru pivot'una yazma yolu kapatıldı.

`LanguageController::translate` ve `LanguageTranslateForm` mount/inline yazma/JSON üretme adımlarında `languages:translate` gerektirir. Inline güncelleme yalnız `translations.<sayısal ID>.value` biçiminde ve string değerle çalışır; seçilmiş dilin `locale` kapsamıyla sınırlandırılır. İzinli çevirmen başka dilin ID'sini enjekte ederek o kaydı değiştiremez. Arama alanlarının güncellenmesi çeviri yazımı sayılmaz. JSON üretimi mevcut helper üzerinden devam eder.

## Süreli sınav uygulaması

### Başlangıç ve yeniden giriş

`Actions/Tests/StartTestResult` izin ve aktiflik denetler. Transaction içinde sınav satırını kilitler; aynı öğrenci/sınavın tamamlanmamış sonucunu arar. Böylece eşzamanlı başlangıç istekleri yeni aktif denemeyi iki kez oluşturmaz. Aktif denemede soru sayısı/süre tekrar yazılmaz. Süresi dolmuş eski aktif sonuç, devam ettirilmeden tamamlanır. Tamamlanmış sonuçlar silinmez veya üzerine yazılmaz; sonraki başlangıç yeni sonuç oluşturur.

Yeni denemede `expires_at = now + duration` (saniye) ve mevcut sezon bilgisi tutulur. `CompleteExpiredTestResult` ilk bitiş zamanına ertelenir, transaction commit edildikten sonra kuyruğa gönderilir. Kaynakta önceki `CreateOrUpdateTestResult` sınıfı korunmuştur; yeni çözüm bileşeni başlangıç için `StartTestResult` kullanır.

`Frontend/Exams/SolveForm` denemeyi mount aşamasında başlatır/devralır; eski `wire:init="startedExams"` kaldırıldı. Zaman session yerine DB'den gelir. Kayıtlı detaylar cevap matrisine yüklenir. Test/sonuç/bölüm modelleri, bitiş zamanı ve bitmiş durum Livewire `Locked` ile istemci değişimine kapatılır. Gezinme geçmişi ve aktif ekran pozisyonu ayrı kalıcı veri olarak tutulmaz; yeniden girişte cevaplar ve süre korunur.

### Seçimlerin kalıcı olması

`Actions/Tests/SaveTestResultAnswer` her seçimi ve seçim kaldırmayı transaction içinde yazar. Öğrencinin işlem izni ve sonuç sahipliği denetlenir; bölüm/soru eşleştirmesi sınavın `tests_questions` tablosundan doğrulanır. Seçenek aynı soruya ait olmalıdır; `0` seçimsiz cevaptır. Sonuç satırı kilitlenir: süre sonu işi ile seçim kaydı yarıştığında bitmiş/süresi dolmuş sonuca geç cevap yazılamaz.

Devam eden denemenin detayındaki `correct` puanlama öncesinde `EMPTY`, `point` sıfırdır. Doğruluk puanı sınav tamamlanırken sunucuda hesaplanır. `updatedResults` yalnız tek cevap alanını kabul eder; tam matris veya geçersiz sayısal değer reddedilir. `uncheckAnswer` da aynı kayıt eylemini kullanır.

Tarayıcıdaki tıklamanın sunucuya ulaşmamış olması veya ağ isteğinin bitişten sonra gelmesi, o cevabın bitiş öncesinde kaydedildiği anlamına gelmez. Süre sonu mevcut DB cevaplarını esas alır.

### Tamamlama ve queue işi

`Actions/Tests/CompleteTestResult` sonuç satırını kilitler; zaten tamamlanmışsa tekrar puanlamaz. Sınavın DB soru listesi ile kalıcı cevaplardan matris oluşturur; cevaplanmamış sorulara `0` koyar. Projenin mevcut `CreateOrUpdateTestResultDetail` upsert/MySQL puanlama akışını kullanır. Elle bitirmede bitiş anı; süresi dolmuş denemede ilk `expires_at` değeri `completed_at` ve süre hesabının temelidir. Worker gecikmesi sınav süresini uzatmaz.

`Jobs/CompleteExpiredTestResult` silinmiş/bulunamayan veya bitmiş sonuçta işlem yapmaz; zamanı gelmemiş iş kendini kalan süre kadar bırakır. Zamanı gelmiş iş aynı tamamlama eylemini kullanır. Tarayıcının erken `saveAndFinish` çağrısı süreyi bitiremez; normal `save` kullanıcı tarafından elle tamamlama yoludur.

`Jobs/CalculateLeagueResult` yalnız `tests_results` kaynağı seçilmişken `tr.completed=YES` filtresi uygular. `exam_results` (ön yüzdeki test) kaynağına bu paketle farklı iş kuralı eklenmedi. Yeni kalıcı sınav cevapları tamamlanmadan lig sıralamasına girmez.

## Dil değişikliğinin sınırı

`SetLocale` ayarsız fallback, `SettingSeeder.defaultLocale`, `config/app.php` locale varsayılanı ve `.env.example.APP_LOCALE` `nl` oldu. Eksik çeviriler için `fallback_locale=en` korunur. Mevcut `.env`, ana yerel/canlı DB ayarı veya kullanıcı session'ı değiştirilmedi. Ortamda hâlâ farklı ayar varsa kaynak varsayılanını geçersiz kılabilir; ayrı veri/config kararı gerekir. İngilizce `Language` kaydı eklenmedi.

## Test kanıtı

Yeni hedefli dosyalar: `tests/Feature/AccessBoundaryTest.php` (8 test), `DefaultLocaleTest.php` (1 test), `TimedExamTest.php` (7 test). Son birlikte koşu **16 test, 80 assertion geçti**; yalnız ayrı MySQL `testing` DB kullanıldı.

```sh
docker compose exec -T -e APP_ENV=testing -e DB_DATABASE=testing laravel.test php artisan test tests/Feature/AccessBoundaryTest.php tests/Feature/DefaultLocaleTest.php tests/Feature/TimedExamTest.php --no-ansi
```

Test başında DB adı `testing` değilse çalışmayı durdurma koruması vardır. DB yazan testler `DatabaseTransactions` kullanır; fixture'lar geri alınır. Queue işi fake kuyruğa alınır; handler zaman ilerletilerek çağrılır. Çeviri JSON üretimi fake Filesystem ile doğrulanır, gerçek `lang/*.json` yazılmaz. Tüm suite, asset build, paket kurulumu veya üretim testi çalıştırılmadı.

Kapsama: misafir giriş yönlendirmesi; yetkisiz form kaydı; yanlış `exams:update` ile sınav güncelleyememe; izinli güncelleme/oluşturma; soru ilişkisine doğrudan eylem çağrısı; çeviride mount ve yazma izni, başka dil ID'si, izin geri alınması, JSON üretimi; ayarsız `nl`; yeniden girişte süre/cevap, seçim kaldırma, erken süre-sonu çağrısı, kapalı sayfa tamamlama, boş cevap puanı, geç cevap, elle puanlama, bitmiş sonucu tekrar yazma, başka soru/bölüm/öğrenci ve lig tamamlanma filtresi.

## Değişen dosyalar

| Alan | Dosyalar |
|---|---|
| Dil varsayılanı | `.env.example`, `config/app.php`, `database/seeders/SettingSeeder.php`, `app/Http/Middleware/SetLocale.php` |
| Başlangıç/çeviri controllerları | `app/Http/Controllers/Frontend/ExamController.php`, `app/Http/Controllers/Backend/LanguageController.php` |
| Panel işlem izinleri | `app/Livewire/Tests/TestForm.php`, `app/Livewire/Exams/ExamForm.php`, `app/Livewire/Languages/LanguageTranslateForm.php` |
| Sınav bileşeni/görünümü | `app/Livewire/Frontend/Exams/SolveForm.php`, `resources/views/livewire/frontend/exams/solve-form.blade.php` |
| Yeni sınav eylemleri | `app/Actions/Tests/StartTestResult.php`, `SaveTestResultAnswer.php`, `CompleteTestResult.php` |
| Queue/lig | Yeni `app/Jobs/CompleteExpiredTestResult.php`, değişen `app/Jobs/CalculateLeagueResult.php` |
| Kaldırılan dosya | `public/systeminfo.php` |
| Yeni testler | `tests/Feature/AccessBoundaryTest.php`, `DefaultLocaleTest.php`, `TimedExamTest.php` |
| Rehber | `AGENTS.md`, `Codex/*.md`, `Codex/index.html`; HTML indeksi her rehber için sekiz maddelik açıklama içerir. |

## Açık ortam ve geçiş noktaları

- Kullanıcı canlıda sürekli Supervisor worker bulunmadığını, her dakika cron ile `--stop-when-empty` çalıştırıldığını bildirdi. Gerçek cron komutu, kuyruk bağlantısı ve çalışma kayıtları incelenmedi. Yerel `.env` queue bağlantısı `database`; compose dosyası web/MySQL servisleri tanımlar. Bu çalışma sırasında worker kurulmadı/başlatılmadı.
- Queue işi bitiş zamanında kullanılabilir olur; gerçekten o anda işlenmesi worker'ın çalışmasına ve kuyruk yüküne bağlıdır. Canlı çalışma zamanı bu testlerle kanıtlanmadı.
- Her dakika cron düzeninde kapalı sayfanın tamamlama işi sonraki cron turuna kalabilir; yoğunluk/iş süresi ek gecikme yaratabilir. Kullanıcı mevcut cron gecikmesini kabul etti; sürekli worker planı istenmedi. Gecikme sınavın cevap kabul süresini uzatmaz; sonuç satırının tamamlanma ve ligde görünme zamanı gecikebilir.
- Mevcut eski tamamlanmamış sonuçların/olası mükerrer aktif denemelerin geçiş planı, canlı şema/veri salt okunur karşılaştırmasından sonra yapılmalıdır. `expires_at` olmayan eski aktif sonuç yeni akışta 409 verir; rastgele yeni süre atanmaz. Eski kapalı denemelere otomatik toplu job/veri güncellemesi yapılmadı.
- Canlı migration listesi bekleniyor. Bu paket yeni migration, tablo/alan rename veya ana yerel/canlı veri yazımı içermez.
- Gerçek tarayıcı/ağ kesintisi, canlı worker, login/registration/PWA/SEO ve tüm diğer modüller için tam kapsam iddiası yoktur. Sonraki test paketleri somut özellik ve bulgu bazında planlanmalıdır.
