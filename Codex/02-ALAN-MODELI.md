# Alan modeli: ders, test, sınav ve kullanıcı

## İsim eşlemesi — kritik

| Ekrandaki ad | PHP/SQL adı | Route ve Livewire | Veri |
|---|---|---|---|
| Test | `Exam`, `exams`, `exams_questions`, `exam_results`, `exam_result_details` | `Frontend/TestController`, `/test/{exam:code}`, `Livewire/Frontend/Tests/SolveForm` | Soru seçimi, soru başına sonuç, favori ve review. |
| Sınav | `Test`, `tests`, `tests_sections`, `tests_questions`, `tests_results`, `tests_result_details` | `Frontend/ExamController`, `/exam/{test:code}`, `Livewire/Frontend/Exams/SolveForm` | Bölüm, süre, puan ve tamamlanma. |

Bu eşleme `routes/frontend.php`, ilgili controller'lar, iki `SolveForm`, `ExamForm`/`TestForm` ve panel menüsünde tutarlıdır. Ad değişimi yalnızca dosya/SQL değişikliği değil; route adı, ilişki, export, yetki adı, çeviri, URL/SEO, veri taşıma ve geçmiş sonuçları kapsar. Nihai adlandırma ve uyumluluk stratejisi henüz kararlaştırılmadı.

## Soru havuzu

`lessons` ve `topics` çok dilli isim/açıklama tutar (`Spatie HasTranslations`); konu `lesson_id` taşır. `questions` belirli `lesson_id`, `topic_id`, `locale`, `code`, başlık, ek/çözüm yolu, sıra, süre ve durum taşır. `answers` soru seçeneklerini ve doğruluk işaretini tutar. `ExamForm` aktif ders/konu/soru filtreleriyle `exams_questions` pivot'una sıralı soru ekler. `Test` tarafında `tests_sections` hiyerarşisi; `tests_questions` ise bölüm ve soru bağını taşır. Soru ekleme/güncelleme aksiyonları ve `QuestionForm` mevcut desenin kaynağıdır.

## Test sonucu

`Frontend/Tests/SolveForm` `Exam` sorularını cevaplarla yükler, Livewire durumunda cevabı değerlendirir ve `ExamResult`/`ExamResultDetail` kayıtlarını `updateOrCreate` ile yazar. Son soruda `completed`, doğru/yanlış sayısı ve süre güncellenir. `User::examResults`, `Exam::userResultsWithoutGroupBy`, hesapta çözülen testler ve sonuç ekranları bu veriyle çalışır. `exams_favorites` kullanıcı favorisini, `exam_reviews` değerlendirmeleri taşır. Gelecek testlerde soru sahipliği, seçeneğin soruya aitliği ve yeniden gönderim özellikle sınanmalıdır; bu ilk fazda değiştirilmedi.

## Sınav sonucu

`TestForm` süre (`duration`, saniye), geçme (`passing_score`) ve puanları kaydeder. İlk düzeltme paketinden sonra `StartTestResult` aynı kullanıcı/sınavın aktif denemesini ve ilk `expires_at` değerini korur; tamamlanmış sonuçtan sonra yeni kayıt açar. `SaveTestResultAnswer` seçimleri `tests_result_details` tablosuna kaydeder ve cevap/soru/sınav/sahiplik/süre sınırlarını doğrular. `CompleteTestResult` DB'deki cevaplarla, sınavın soru listesini esas alarak mevcut `CreateOrUpdateTestResultDetail` puanlamasını kullanır. `CompleteExpiredTestResult` sayfa kapalıyken de sonucu tamamlamak için ilk bitiş zamanına ertelenir. Sonuçta `completed_at`, `expires_at`, `duration`, `season_id` bulunur. Süre dolmuş sonucun `completed_at` değeri işin geciktiği zaman değil ilk bitiş zamanıdır. Worker'ın canlı ortamda çalıştığı henüz doğrulanmadı. Ayrıntı: `11-ILK-DUZELTMELER-VE-SINAV-AKISI.md`.

## Kullanıcı, okul ve lig

`users` modelinde Spatie `HasRoles`, soft delete, durum ve e-posta onayı bulunur. `teacher_student` pivot'u kullanıcı ile öğretmeni eşler; `User::teachers()`/`students()` ilişkileri ve panel kullanıcı sekmeleri bu eşlemeyi kullanır. Bu yapı bağımsız bir okul tablosu olarak belgelenmemelidir. `RoleTypeEnum`: `user-type:admin`, `user-type:user`, `user-type:teacher`. `PermissionSeeder` normal, premium ve yönetici rolleriyle işlem izinleri oluşturur; seed tekrar çalıştırmaya uygun/idempotent görünmez, bu nedenle canlı veriye çalıştırma. `League`/`LeagueResult` ve `Season` ilerleyen sıralama/istatistik kurgusuna bağlıdır; hesaplaması zamanlanmış job içindedir.

## İçerik ve çok dillilik

`Language` listesi, `Translation` tablosu ve `lang/*.json` arayüz metinlerini besler. `Page`, `Lesson`, `Topic` alanları Spatie Translatable JSON alanları kullanır; blog, soru, test/sınav gibi içeriklerde `locale` sütunu vardır. Bu iki çeviri yaklaşımı bilinçsizce tek yönteme indirgenmemelidir. `LanguageTranslateForm` eksik anahtarları tarayıp `translations` tablosuna yazar ve kayıtta JSON dosyası üretir; mount/inline yazma/JSON üretme `languages:translate` izni gerektirir. Inline yazma seçilmiş dil kapsamındadır. `SetLocale` `lang` query parametresi → session → `settings()->defaultLocale` → `nl` sırasını kullanır. Seed yalnızca `tr` ve `nl` dili oluşturur; `lang/en` dosyası ve cookie consent `en` kaynağı bulunması, yerel DB'de İngilizcenin aktif olduğunu kanıtlamaz.
