# Migration sırası, tablo kaynağı ve model eşleştirmesi

**Son append migration:** `2026_10_05_150000_add_initial_query_performance_indexes` ana yerel DB'de id=37 / batch=7 ile uygulandı; aşağıdaki ilk 36 kayıt korunur. Yeni migration yalnız üç normal indeks ekler, kaynak/durum/ölçüm [31](31-INDEKS-UYGULAMA-VE-OLCUM-SONUCU.md) içindedir. Testing repository bu tur kalıcı migrate edilmedi.

5 Ekim 2026; aktarılan ana yerel canlı kopya. Bu bir kaynak haritasıdır; migration çalıştırılmadı, dosya/batch yeniden adlandırılmadı.

## 1. Migration kayıtlarının gerçek sırası

36 satır = 34 uygulama migration'ı + 2 paket migration'ı. Son satır `2026_02_04_101550_table_new_column3`. 34 uygulama dosyasının hepsi bu kayıtlar arasında. Uygulama dizini dışındaki iki satır eksik dosya değildir; vendor paketleri kaynaklarını sağlar. `id`/`batch` aktarılmış tabloda olduğu gibi korunur.

| id | Batch | Migration kaynağı | Sahibi | Oluşturduğu/değiştirdiği tablolar |
| --- | --- | --- | --- | --- |
| 1 | 1 | [database/migrations/0001_01_01_000000_create_users_table.php](../database/migrations/0001_01_01_000000_create_users_table.php) | Uygulama | `users`, `password_reset_tokens`, `sessions` |
| 2 | 1 | [database/migrations/0001_01_01_000002_create_jobs_table.php](../database/migrations/0001_01_01_000002_create_jobs_table.php) | Uygulama | `jobs`, `job_batches`, `failed_jobs` |
| 3 | 1 | [vendor/zoha/laravel-meta/src/Meta/Database/Migrations/2018_04_02_110607_create_meta_table.php](../vendor/zoha/laravel-meta/src/Meta/Database/Migrations/2018_04_02_110607_create_meta_table.php) | Zoha paket | `meta`, `tests_sections_meta` |
| 4 | 1 | [vendor/pharaonic/laravel-settings/src/database/migrations/2021_02_01_000032_create_settings_table.php](../vendor/pharaonic/laravel-settings/src/database/migrations/2021_02_01_000032_create_settings_table.php) | Pharaonic paket | `settings` |
| 5 | 1 | [database/migrations/2024_09_28_112921_create_permission_tables.php](../database/migrations/2024_09_28_112921_create_permission_tables.php) | Uygulama | `permissions`, `roles`, `model_has_permissions`, `model_has_roles`, `role_has_permissions` |
| 6 | 1 | [database/migrations/2024_11_15_114611_create_languages_table.php](../database/migrations/2024_11_15_114611_create_languages_table.php) | Uygulama | `languages` |
| 7 | 1 | [database/migrations/2024_11_15_115629_create_lessons_table.php](../database/migrations/2024_11_15_115629_create_lessons_table.php) | Uygulama | `lessons` |
| 8 | 1 | [database/migrations/2024_11_15_115638_create_topics_table.php](../database/migrations/2024_11_15_115638_create_topics_table.php) | Uygulama | `topics` |
| 9 | 1 | [database/migrations/2024_11_15_115648_create_questions_table.php](../database/migrations/2024_11_15_115648_create_questions_table.php) | Uygulama | `questions` |
| 10 | 1 | [database/migrations/2024_11_15_121331_create_answers_table.php](../database/migrations/2024_11_15_121331_create_answers_table.php) | Uygulama | `answers` |
| 11 | 1 | [database/migrations/2024_11_15_121923_create_exams_table.php](../database/migrations/2024_11_15_121923_create_exams_table.php) | Uygulama | `exams`, `exams_questions`, `exams_favorites` |
| 12 | 1 | [database/migrations/2024_11_15_130148_create_pages_table.php](../database/migrations/2024_11_15_130148_create_pages_table.php) | Uygulama | `pages` |
| 13 | 1 | [database/migrations/2024_11_18_134521_create_contacts_table.php](../database/migrations/2024_11_18_134521_create_contacts_table.php) | Uygulama | `contacts` |
| 14 | 1 | [database/migrations/2024_11_18_165653_create_newsletters_table.php](../database/migrations/2024_11_18_165653_create_newsletters_table.php) | Uygulama | `newsletters`, `newsletters_mails` |
| 15 | 1 | [database/migrations/2024_11_18_183823_create_faqs_table.php](../database/migrations/2024_11_18_183823_create_faqs_table.php) | Uygulama | `faqs` |
| 16 | 1 | [database/migrations/2024_11_19_100215_create_translations_table.php](../database/migrations/2024_11_19_100215_create_translations_table.php) | Uygulama | `translations` |
| 17 | 1 | [database/migrations/2024_11_20_145359_create_blogs_table.php](../database/migrations/2024_11_20_145359_create_blogs_table.php) | Uygulama | `blogs` |
| 18 | 1 | [database/migrations/2024_11_21_205131_create_exam_reviews_table.php](../database/migrations/2024_11_21_205131_create_exam_reviews_table.php) | Uygulama | `exam_reviews` |
| 19 | 1 | [database/migrations/2024_11_23_215259_create_exam_results_table.php](../database/migrations/2024_11_23_215259_create_exam_results_table.php) | Uygulama | `exam_results` |
| 20 | 1 | [database/migrations/2024_11_23_215309_create_exam_result_details_table.php](../database/migrations/2024_11_23_215309_create_exam_result_details_table.php) | Uygulama | `exam_result_details` |
| 21 | 1 | [database/migrations/2024_11_28_114902_create_logs_table.php](../database/migrations/2024_11_28_114902_create_logs_table.php) | Uygulama | `logs` |
| 22 | 2 | [database/migrations/2025_01_29_112745_create_telescope_entries_table.php](../database/migrations/2025_01_29_112745_create_telescope_entries_table.php) | Uygulama | `telescope_entries`, `telescope_entries_tags`, `telescope_monitoring` |
| 23 | 3 | [database/migrations/2025_04_16_121101_create_tests_table.php](../database/migrations/2025_04_16_121101_create_tests_table.php) | Uygulama | `tests`, `tests_sections`, `tests_questions` |
| 24 | 3 | [database/migrations/2025_05_03_123743_create_tests_results_table.php](../database/migrations/2025_05_03_123743_create_tests_results_table.php) | Uygulama | `tests_results` |
| 25 | 3 | [database/migrations/2025_05_03_123759_create_tests_result_details_table.php](../database/migrations/2025_05_03_123759_create_tests_result_details_table.php) | Uygulama | `tests_result_details` |
| 26 | 4 | [database/migrations/2025_08_04_103321_create_answer_a_i_s_table.php](../database/migrations/2025_08_04_103321_create_answer_a_i_s_table.php) | Uygulama | `answer_a_i_s` |
| 27 | 4 | [database/migrations/2025_08_04_111128_create_user_ai_usages_table.php](../database/migrations/2025_08_04_111128_create_user_ai_usages_table.php) | Uygulama | `user_ai_usages` |
| 28 | 4 | [database/migrations/2025_08_18_101525_create_answer_a_i_votes_table.php](../database/migrations/2025_08_18_101525_create_answer_a_i_votes_table.php) | Uygulama | `answer_a_i_votes` |
| 29 | 5 | [database/migrations/2025_08_18_102020_table_new_column.php](../database/migrations/2025_08_18_102020_table_new_column.php) | Uygulama | `users`, `settings` |
| 30 | 5 | [database/migrations/2025_08_21_133841_create_latex_images_table.php](../database/migrations/2025_08_21_133841_create_latex_images_table.php) | Uygulama | `latex_images` |
| 31 | 6 | [database/migrations/2026_01_13_113343_create_teacher_student_table.php](../database/migrations/2026_01_13_113343_create_teacher_student_table.php) | Uygulama | `teacher_student` |
| 32 | 6 | [database/migrations/2026_01_13_165444_create_leagues_table.php](../database/migrations/2026_01_13_165444_create_leagues_table.php) | Uygulama | `leagues` |
| 33 | 6 | [database/migrations/2026_01_14_003409_create_league_results_table.php](../database/migrations/2026_01_14_003409_create_league_results_table.php) | Uygulama | `league_results` |
| 34 | 6 | [database/migrations/2026_01_20_142825_table_new_column2.php](../database/migrations/2026_01_20_142825_table_new_column2.php) | Uygulama | `users` |
| 35 | 6 | [database/migrations/2026_02_02_220814_create_seasons_table.php](../database/migrations/2026_02_02_220814_create_seasons_table.php) | Uygulama | `seasons` |
| 36 | 6 | [database/migrations/2026_02_04_101550_table_new_column3.php](../database/migrations/2026_02_04_101550_table_new_column3.php) | Uygulama | `tests_results`, `exam_results` |

Batch 1: 21; batch 2: 1; batch 3: 3; batch 4: 3; batch 5: 2; batch 6: 6 kayıt. Dosya adı sırası yeni migration yürütme sırasını belirler; `batch` daha önce hangi grupla çalıştığını ve rollback grubunu kaydeder. İsim/batch değiştirerek şema eşitliği sağlanmaz.

`migrations` tablosunu framework migration repository oluşturur; ayrı create_migrations app dosyası olmaması beklenir. Böylece 50 tablonun 49'u yukarıdaki up/config eşlemesinde, biri framework repository'de açıklanır.

## 2. Paket tarafından oluşturulan tablolar

- `config/meta.php` default `meta`, custom `tests_sections_meta` içerir.
- Zoha'nın 2018 migration'ı hem default hem config'teki custom tabloları oluşturur. `tests_sections_meta` için app migration dosyası bulunmaması bu yüzden normaldir; runtime'da modelden kendiliğinden oluşturulduğu varsayılmaz.
- TestsSection metaTable bu custom tabloyu seçer. meta 4 satır/User owner, tests_sections_meta 528 satır/TestsSection owner içerir.
- Pharaonic 2021 migration'ı settings oluşturur; 2025_08_18 app migration'ı value alanını longtext'e çevirir.
- Paket kaldırma kararı verilirse bu tabloların göç/kod davranışı ayrıca korunur; paket kaldırılması veri silme izni değildir.
- Spatie 5 tablo; Telescope 3 tablo config/provider kaynağıyla eşlenir. Pivot/model tablosuna her zaman ayrı app Model gerekmez.

## 3. Model alan haritası

30 app modelinin default özellikleri reflection ile okundu; modeller oluşturulup save/event tetiklenmedi. Tablo, fillable/guarded, timestamps ve SoftDeletes karşılaştırıldı. Boş fillable guarded düzeniyle birlikte değerlendirilir; model alanları fiziksel tablonun tamamını ilan etmek zorunda değildir. Guarded=[] bazı mevcut modellerde bilinçli olabilir; kendiliğinden yeni mimariye çevrilmez.

| Model | Tablo | Timestamps | SoftDeletes | Fiziksel karşılığı olmayan fillable | Yazılabilir alan tanımı |
| --- | --- | --- | --- | --- | --- |
| [app/Models/Answer.php](../app/Models/Answer.php) | `answers` | Evet | Evet | Yok | question_id, title, correct |
| [app/Models/AnswerAI.php](../app/Models/AnswerAI.php) | `answer_a_i_s` | Evet | Hayır | Yok | user_id, lesson_id, topic_id, locale, question_id, title, content, report |
| [app/Models/AnswerAIVote.php](../app/Models/AnswerAIVote.php) | `answer_a_i_votes` | Evet | Hayır | Yok | user_id, answer_ai_id, vote |
| [app/Models/Blog.php](../app/Models/Blog.php) | `blogs` | Evet | Hayır | Yok | locale, slug, image, title, brief, content, keywords, hits, status, published_at |
| [app/Models/Contact.php](../app/Models/Contact.php) | `contacts` | Evet | Hayır | Yok | locale, name, email, phone, school_name, message, accept_terms, ip, reply, has_read |
| [app/Models/Exam.php](../app/Models/Exam.php) | `exams` | Evet | Evet | Yok | guarded: ['created_at', 'updated_at', 'deleted_at'] |
| [app/Models/ExamResult.php](../app/Models/ExamResult.php) | `exam_results` | Evet | Hayır | Yok | user_id, exam_id, question_count, correct_count, incorrect_count, time, completed, season_id |
| [app/Models/ExamResultDetail.php](../app/Models/ExamResultDetail.php) | `exam_result_details` | Evet | Hayır | Yok | exam_result_id, question_id, answer_id, correct, lesson_id, topic_id, time |
| [app/Models/ExamReview.php](../app/Models/ExamReview.php) | `exam_reviews` | Evet | Hayır | Yok | user_id, exam_id, comment, ip, visibility, reply_id, has_read |
| [app/Models/Faq.php](../app/Models/Faq.php) | `faqs` | Evet | Hayır | slug, brief, type | locale, slug, title, brief, content, type, status, sort |
| [app/Models/Language.php](../app/Models/Language.php) | `languages` | Evet | Hayır | sort | code, name, sort, status |
| [app/Models/LatexImage.php](../app/Models/LatexImage.php) | `latex_images` | Evet | Hayır | Yok | formula, image |
| [app/Models/League.php](../app/Models/League.php) | `leagues` | Evet | Hayır | Yok | code, label, calculated_at, start_at, end_at |
| [app/Models/LeagueResult.php](../app/Models/LeagueResult.php) | `league_results` | Evet | Hayır | Yok | league_number, league_id, user_id, total_score, total_correct, total_incorrect, total_duration, position, rank_position |
| [app/Models/Lesson.php](../app/Models/Lesson.php) | `lessons` | Evet | Evet | Yok | code, name, description, sort, hits, status |
| [app/Models/Log.php](../app/Models/Log.php) | `logs` | Hayır | Hayır | Yok | user_id, log_date, table_name, log_type, ip, data_id, data, agent, browser, device |
| [app/Models/Newsletter.php](../app/Models/Newsletter.php) | `newsletters` | Evet | Evet | Yok | email, accept_terms, ip, token |
| [app/Models/Page.php](../app/Models/Page.php) | `pages` | Evet | Hayır | Yok | slug, title, brief, content, images, keywords, sort, link, menu, type, status |
| [app/Models/Question.php](../app/Models/Question.php) | `questions` | Evet | Evet | Yok | lesson_id, topic_id, locale, code, title, attachment, solution, sort, time, status |
| [app/Models/Role.php](../app/Models/Role.php) | `roles` | Evet | Hayır | Yok | guarded: [] |
| [app/Models/Season.php](../app/Models/Season.php) | `seasons` | Evet | Hayır | Yok | name, start_date, end_date, status |
| [app/Models/Test.php](../app/Models/Test.php) | `tests` | Evet | Evet | Yok | guarded: ['created_at', 'updated_at', 'deleted_at'] |
| [app/Models/TestsQuestion.php](../app/Models/TestsQuestion.php) | `tests_questions` | Hayır | Hayır | Yok | test_id, section_id, question_id, lesson_id, topic_id, order |
| [app/Models/TestsResult.php](../app/Models/TestsResult.php) | `tests_results` | Evet | Hayır | Yok | user_id, test_id, question_count, correct_count, incorrect_count, duration, point, passing_score, completed, completed_at, expires_at, season_id |
| [app/Models/TestsResultDetail.php](../app/Models/TestsResultDetail.php) | `tests_result_details` | Evet | Hayır | Yok | tests_result_id, section_id, question_id, answer_id, correct, point, lesson_id, topic_id, time |
| [app/Models/TestsSection.php](../app/Models/TestsSection.php) | `tests_sections` | Evet | Hayır | Yok | guarded: ['created_at', 'updated_at'] |
| [app/Models/Topic.php](../app/Models/Topic.php) | `topics` | Evet | Evet | Yok | code, lesson_id, title, description, sort, hits, status |
| [app/Models/Translation.php](../app/Models/Translation.php) | `translations` | Hayır | Hayır | Yok | locale, key, value |
| [app/Models/User.php](../app/Models/User.php) | `users` | Evet | Evet | Yok | guarded: ['created_at', 'updated_at', 'deleted_at'] |
| [app/Models/UserAiUsage.php](../app/Models/UserAiUsage.php) | `user_ai_usages` | Evet | Hayır | Yok | user_id, answer_ai_id, usage, remaining, usage_date |

Bütün model tabloları var; timestamps=true olan model tablolarında created_at/updated_at var. Faq ve Language'deki yukarıdaki alanlar hem canlı fiziksel şemada hem mevcut fresh referansında yok. Önce form/Action tüketimi kontrol edilir; yalnız fillable listesinde görünmesi “SQL'e alan ekle” gerekçesi değildir.

## 4. Model ilişkileri ve tarihsel davranış

1. TestsResultDetail::testResult FK adı gerçek tests_result_id ile eşlenmelidir; mevcut yanlış test_result_id alanı eklenmemeli.
2. AnswerAI::answer varsayılan answer_id alanı bekliyor; tabloda yok. Çağrı/kullanım kararı olmadan sütun icat edilmemeli.
3. TestsSection::parents ve parent child yönünde. Kök parent_id=0; sıradan parent FK önerisi 0 köküyle çelişir.
4. Exam/Test::userResults grouped SELECT * deterministik sonuç seçmez; liste “özet kartı” ile ayrı deneme geçmişi karıştırılmamalı. Backend toplam istatistik ve lig bütün uygun denemeleri toplar; bu karar değişmez.
5. ExamResult/TestsResult/Page deleted_at içeriyor fakat SoftDeletes trait yok. Bu sütunların hepsi mevcut veride NULL. Trait eklemek sorgu kapsamını değiştirir; ayrı davranış kararı gerekir.
6. Fiziksel FK sadece Spatie 4 ve Telescope 1 bağında. Diğer belongsTo/hasMany tanımları DB'de FK bulunduğu anlamına gelmez.
7. Result detail soru/ders/konu kopyaları tarihsel rapor amaçlıdır. Güncel sorudan kopyaları yeniden yazmak geçmişi değiştirebilir.

## 5. Kolon/default eşitliği ve yorum

401 fiziksel kolon testing ile isim/tip/null/default/extra/charset/collation bazında eşleşti; ayrıntı 28. Bunun sınırı: mevcut testing snapshot'ı karşılaştırıldı, bu tur fresh yapılmadı ve canlıda ran dosyalarının eski hash'leri yok. Kayıt tablosu yalnız isim/batch tutar; geçmişte uygulanan içerik değişikliklerini ispatlamaz.

- Nullable + default NULL çoğu tarih/token/içerik alanında normaldir. NOT NULL + default yok, uygulamanın zorunlu alanı yazmasını ister; kendi başına eksiklik değildir.
- `nullable(0)` false/NOT NULL'dır, default 0 eklemez. `nullable(5)` true/nullable'dır, default 5 değildir. users.gpt_limit ayrı `default(5)` nedeniyle 5 varsayılana ve NULL iznine sahip.
- season_id nullable/default 1; gerçek ilişkilerde olmayan sezon ID bulunmadı. Tek aktif sezon var. Sezonla tarih karşılaştırması eski tarih politikası belirlenmeden otomatik düzeltme gerekçesi değildir.
- tests_results.point ve tests_result_details.point signed tinyint. Bugünkü konfigürasyonda taşma yok; gelecekte soru/puan hacmi büyürse int adayı.
- Çeviri unique ad farkı eşdeğer constraint'tir. Yeni migration'da gerçek isim veya eşdeğer kolon imzası görülmeden aynı unique tekrar eklenmemeli.

## 6. Yeni migration stratejisi

1. 36 eski kayıt/batch ve geçmiş migration isimleri korunur; `fresh`, migration tablosunu silme, kayıtları Ran gibi işaretleme yapılmaz.
2. Eski dosyayı değiştirerek “canlı zaten çalıştırır” varsayımı yapılmaz. İndeks eşitlemesi/onaylı tip değişimi için yeni, açıklayıcı append migration hazırlanır.
3. Canlıda ekstra indeks varken testing'de yok; iki başlangıcı da tanıyabilen migration gerekir. İndeks adı dışında kolon sırası/unique özelliği de denetlenir.
4. down davranışı eklendiği şeyleri geri almalı; önceden var olan canlı indeksi körlemesine kaldırmamalı. Başlangıç schema farkının rollback kapsamı tasarlanır veya açık geri dönüş planıyla ileri göç seçilir.
5. İndeks ekleme ile tarihsel veri düzeltmesi ayrı değişikliklerdir. Orphan silme/puan değiştirme schema migration'a gizlenmez.
6. Yerel testing’de gerekli dar migration/rollback testleri ayrı kullanıcı onayı ve korunmuş başlangıçla yapılır; şu anda QA fixture temizliği onaysızdır.
7. Sonraki şema planı başına eski/yeni DDL, etkilenen tablolar, veri kaybı riski ve hedefli test yazılır. Canlıya uygulama bu yerel import izninden çıkarılmaz.

## 7. Rollback kaynak incelemesi

2025_08_18_102020 down yalnız gpt_limit düşürür; settings.value longtext dönüşümünü geri almaz. Geri dönüşte eski text sınırına sığmayan veri varsa daraltma kayıp yaratabilir; otomatik “tam down” eklenmez. 2025_04_16 down tests'i iki kez düşürür, tests_questions düşürme satırı vardır, fakat parent önce gider. Mevcut FK yokluğuyla bugün bloke olduğu gösterilmedi; yeni FK planında child-before-parent sırası gerekir. Veri içeren yaratma migration'larının rollback'i zaten tablo/veri siler; canlı veride kullanılmaz.

SQL kaynak ve veri bulguları 26; somut düzeltme/performans/karar kapıları 29 içindedir.
