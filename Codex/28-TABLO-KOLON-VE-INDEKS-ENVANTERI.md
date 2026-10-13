# Fiziksel tablo, kolon ve indeks envanteri

**Güncel fark:** Bu envanter ilk SQL aktarımının 174 indeksli snapshot'ıdır. Son onaylı migration üç nonunique indeks ekledi; ana DB artık 177 indekslidir. Tablo/kolon yapısı değişmedi. Yeni isimler/kolonlar ve ölçüm [31](31-INDEKS-UYGULAMA-VE-OLCUM-SONUCU.md), migration kaynağı 30 içindedir.

5 Ekim 2026; Sail ana yerel hypotenuse, aktarılan SQL kopyası. Yalnız metadata ve satır sayımı. 50 tablo / 401 kolon / 174 indeks / 5 FK. Kullanıcı satırları veya sırlar içermez.

## Okuma kuralı

- Kolon sırası fiziksel ordinal sırasıdır; default NULL/yok information_schema anlamında gösterilir. NOT NULL alanındaki `yok` otomatik hata değildir; identity veya uygulamanın yazması gereken zorunlu alan olabilir.
- Nullable kolonda `NULL` varsayılan/atanmamış değeri gösterir. Explicit DEFAULT NULL ile implicit nullable davranışını bu metadata tek başına ayırmaz.
- Tüm indeksler BTREE ve görünür; aşağıda unique/PRIMARY ile normal ayrılır. Çok kolonlu indekste yazılan kolon sırası önemlidir.
- Satır sayıları COUNT(*) ile tam, data/index boyutları InnoDB metadata tahminidir. Kullanıcıya ait gerçek kimlik/alan değerleri listelenmez.
- App model yoksa tablo framework/paket/pivot olabilir; bu yokluk kendi başına sorun değildir.
- Kaynak migration listesi 27, veri anomalileri 26 ve düzeltme adayları 29 içindedir. Bu envanter DDL uygulama izni değildir.

## Tablo dizini

| Tablo | Satır | Kolon | İndeks | Model |
| --- | --- | --- | --- | --- |
| [answer_a_i_s](#answer_a_i_s) | 484 | 11 | 7 | AnswerAI |
| [answer_a_i_votes](#answer_a_i_votes) | 57 | 6 | 3 | AnswerAIVote |
| [answers](#answers) | 12269 | 7 | 3 | Answer |
| [blogs](#blogs) | 4 | 13 | 3 | Blog |
| [contacts](#contacts) | 4 | 13 | 3 | Contact |
| [exam_result_details](#exam_result_details) | 149904 | 10 | 7 | ExamResultDetail |
| [exam_results](#exam_results) | 16231 | 12 | 8 | ExamResult |
| [exam_reviews](#exam_reviews) | 5 | 10 | 6 | ExamReview |
| [exams](#exams) | 297 | 12 | 4 | Exam |
| [exams_favorites](#exams_favorites) | 112 | 2 | 2 | Framework/paket/pivot |
| [exams_questions](#exams_questions) | 2982 | 5 | 5 | Framework/paket/pivot |
| [failed_jobs](#failed_jobs) | 358 | 7 | 2 | Framework/paket/pivot |
| [faqs](#faqs) | 5 | 8 | 2 | Faq |
| [job_batches](#job_batches) | 0 | 10 | 1 | Framework/paket/pivot |
| [jobs](#jobs) | 0 | 8 | 2 | Framework/paket/pivot |
| [languages](#languages) | 3 | 6 | 3 | Language |
| [latex_images](#latex_images) | 2538 | 5 | 1 | LatexImage |
| [league_results](#league_results) | 1238 | 12 | 6 | LeagueResult |
| [leagues](#leagues) | 35 | 8 | 2 | League |
| [lessons](#lessons) | 7 | 10 | 5 | Lesson |
| [logs](#logs) | 0 | 11 | 1 | Log |
| [meta](#meta) | 4 | 9 | 2 | Framework/paket/pivot |
| [migrations](#migrations) | 36 | 3 | 1 | Framework/paket/pivot |
| [model_has_permissions](#model_has_permissions) | 0 | 3 | 2 | Framework/paket/pivot |
| [model_has_roles](#model_has_roles) | 381 | 3 | 2 | Framework/paket/pivot |
| [newsletters](#newsletters) | 0 | 8 | 1 | Newsletter |
| [newsletters_mails](#newsletters_mails) | 0 | 4 | 2 | Framework/paket/pivot |
| [pages](#pages) | 3 | 15 | 4 | Page |
| [password_reset_tokens](#password_reset_tokens) | 12 | 3 | 1 | Framework/paket/pivot |
| [permissions](#permissions) | 75 | 5 | 2 | Framework/paket/pivot |
| [questions](#questions) | 3067 | 14 | 8 | Question |
| [role_has_permissions](#role_has_permissions) | 128 | 2 | 2 | Framework/paket/pivot |
| [roles](#roles) | 7 | 5 | 2 | Role |
| [seasons](#seasons) | 2 | 7 | 3 | Season |
| [sessions](#sessions) | 0 | 6 | 3 | Framework/paket/pivot |
| [settings](#settings) | 101 | 7 | 1 | Framework/paket/pivot |
| [teacher_student](#teacher_student) | 38 | 3 | 2 | Framework/paket/pivot |
| [telescope_entries](#telescope_entries) | 0 | 8 | 6 | Framework/paket/pivot |
| [telescope_entries_tags](#telescope_entries_tags) | 0 | 2 | 2 | Framework/paket/pivot |
| [telescope_monitoring](#telescope_monitoring) | 0 | 1 | 1 | Framework/paket/pivot |
| [tests](#tests) | 9 | 15 | 4 | Test |
| [tests_questions](#tests_questions) | 263 | 6 | 6 | TestsQuestion |
| [tests_result_details](#tests_result_details) | 10609 | 12 | 9 | TestsResultDetail |
| [tests_results](#tests_results) | 379 | 16 | 7 | TestsResult |
| [tests_sections](#tests_sections) | 307 | 8 | 4 | TestsSection |
| [tests_sections_meta](#tests_sections_meta) | 528 | 9 | 2 | Framework/paket/pivot |
| [topics](#topics) | 80 | 11 | 5 | Topic |
| [translations](#translations) | 2066 | 4 | 3 | Translation |
| [user_ai_usages](#user_ai_usages) | 1155 | 8 | 5 | UserAiUsage |
| [users](#users) | 381 | 18 | 6 | User |

## answer_a_i_s

Satır: **484**. Engine: `InnoDB`. Collation: `utf8mb4_unicode_ci`. Metadata data/index baytları: 2637824 / 98304.

| # | Kolon | SQL tip | NULL kabul | Default | Extra | Charset / collation |
| --- | --- | --- | --- | --- | --- | --- |
| 1 | `id` | bigint unsigned | NO | yok | auto_increment | — / — |
| 2 | `user_id` | bigint unsigned | YES | NULL | — | — / — |
| 3 | `lesson_id` | bigint unsigned | NO | yok | — | — / — |
| 4 | `topic_id` | bigint unsigned | NO | yok | — | — / — |
| 5 | `locale` | varchar(5) | NO | yok | — | utf8mb4 / utf8mb4_unicode_ci |
| 6 | `question_id` | bigint unsigned | NO | yok | — | — / — |
| 7 | `title` | varchar(255) | YES | NULL | — | utf8mb4 / utf8mb4_unicode_ci |
| 8 | `content` | longtext | YES | NULL | — | utf8mb4 / utf8mb4_unicode_ci |
| 9 | `report` | tinyint | YES | `0` | — | — / — |
| 10 | `created_at` | timestamp | YES | NULL | — | — / — |
| 11 | `updated_at` | timestamp | YES | NULL | — | — / — |

### İndeksler

| İsim | Tür | Sıralı kolonlar | Görünür |
| --- | --- | --- | --- |
| PRIMARY | PRIMARY | `id` | YES |
| answer_a_i_s_lesson_id_index | INDEX | `lesson_id` | YES |
| answer_a_i_s_locale_index | INDEX | `locale` | YES |
| answer_a_i_s_question_id_index | INDEX | `question_id` | YES |
| answer_a_i_s_report_index | INDEX | `report` | YES |
| answer_a_i_s_topic_id_index | INDEX | `topic_id` | YES |
| answer_a_i_s_user_id_index | INDEX | `user_id` | YES |

Fiziksel foreign key: yok. Eloquent ilişkileri 27 içinde değerlendirilir.

## answer_a_i_votes

Satır: **57**. Engine: `InnoDB`. Collation: `utf8mb4_unicode_ci`. Metadata data/index baytları: 16384 / 32768.

| # | Kolon | SQL tip | NULL kabul | Default | Extra | Charset / collation |
| --- | --- | --- | --- | --- | --- | --- |
| 1 | `id` | bigint unsigned | NO | yok | auto_increment | — / — |
| 2 | `user_id` | bigint unsigned | NO | yok | — | — / — |
| 3 | `answer_ai_id` | bigint unsigned | NO | yok | — | — / — |
| 4 | `vote` | int | NO | yok | — | — / — |
| 5 | `created_at` | timestamp | YES | NULL | — | — / — |
| 6 | `updated_at` | timestamp | YES | NULL | — | — / — |

### İndeksler

| İsim | Tür | Sıralı kolonlar | Görünür |
| --- | --- | --- | --- |
| PRIMARY | PRIMARY | `id` | YES |
| answer_a_i_votes_answer_ai_id_index | INDEX | `answer_ai_id` | YES |
| answer_a_i_votes_user_id_index | INDEX | `user_id` | YES |

Fiziksel foreign key: yok. Eloquent ilişkileri 27 içinde değerlendirilir.

## answers

Satır: **12269**. Engine: `InnoDB`. Collation: `utf8mb4_unicode_ci`. Metadata data/index baytları: 1589248 / 540672.

| # | Kolon | SQL tip | NULL kabul | Default | Extra | Charset / collation |
| --- | --- | --- | --- | --- | --- | --- |
| 1 | `id` | bigint unsigned | NO | yok | auto_increment | — / — |
| 2 | `question_id` | bigint unsigned | NO | yok | — | — / — |
| 3 | `title` | varchar(255) | NO | yok | — | utf8mb4 / utf8mb4_unicode_ci |
| 4 | `correct` | tinyint | NO | `0` | — | — / — |
| 5 | `created_at` | timestamp | YES | NULL | — | — / — |
| 6 | `updated_at` | timestamp | YES | NULL | — | — / — |
| 7 | `deleted_at` | timestamp | YES | NULL | — | — / — |

### İndeksler

| İsim | Tür | Sıralı kolonlar | Görünür |
| --- | --- | --- | --- |
| PRIMARY | PRIMARY | `id` | YES |
| answers_correct_index | INDEX | `correct` | YES |
| answers_question_id_index | INDEX | `question_id` | YES |

Fiziksel foreign key: yok. Eloquent ilişkileri 27 içinde değerlendirilir.

## blogs

Satır: **4**. Engine: `InnoDB`. Collation: `utf8mb4_unicode_ci`. Metadata data/index baytları: 7880704 / 32768.

| # | Kolon | SQL tip | NULL kabul | Default | Extra | Charset / collation |
| --- | --- | --- | --- | --- | --- | --- |
| 1 | `id` | bigint unsigned | NO | yok | auto_increment | — / — |
| 2 | `locale` | varchar(5) | NO | yok | — | utf8mb4 / utf8mb4_unicode_ci |
| 3 | `slug` | varchar(255) | NO | yok | — | utf8mb4 / utf8mb4_unicode_ci |
| 4 | `image` | varchar(255) | YES | NULL | — | utf8mb4 / utf8mb4_unicode_ci |
| 5 | `title` | varchar(255) | NO | yok | — | utf8mb4 / utf8mb4_unicode_ci |
| 6 | `brief` | varchar(255) | YES | NULL | — | utf8mb4 / utf8mb4_unicode_ci |
| 7 | `content` | longtext | YES | NULL | — | utf8mb4 / utf8mb4_unicode_ci |
| 8 | `keywords` | varchar(255) | YES | NULL | — | utf8mb4 / utf8mb4_unicode_ci |
| 9 | `hits` | bigint | NO | `0` | — | — / — |
| 10 | `status` | varchar(255) | NO | `active` | — | utf8mb4 / utf8mb4_unicode_ci |
| 11 | `published_at` | timestamp | YES | NULL | — | — / — |
| 12 | `created_at` | timestamp | YES | NULL | — | — / — |
| 13 | `updated_at` | timestamp | YES | NULL | — | — / — |

### İndeksler

| İsim | Tür | Sıralı kolonlar | Görünür |
| --- | --- | --- | --- |
| PRIMARY | PRIMARY | `id` | YES |
| blogs_locale_index | INDEX | `locale` | YES |
| blogs_slug_unique | UNIQUE | `slug` | YES |

Fiziksel foreign key: yok. Eloquent ilişkileri 27 içinde değerlendirilir.

## contacts

Satır: **4**. Engine: `InnoDB`. Collation: `utf8mb4_unicode_ci`. Metadata data/index baytları: 16384 / 32768.

| # | Kolon | SQL tip | NULL kabul | Default | Extra | Charset / collation |
| --- | --- | --- | --- | --- | --- | --- |
| 1 | `id` | bigint unsigned | NO | yok | auto_increment | — / — |
| 2 | `locale` | varchar(5) | NO | yok | — | utf8mb4 / utf8mb4_unicode_ci |
| 3 | `name` | varchar(255) | NO | yok | — | utf8mb4 / utf8mb4_unicode_ci |
| 4 | `email` | varchar(255) | NO | yok | — | utf8mb4 / utf8mb4_unicode_ci |
| 5 | `phone` | varchar(255) | YES | NULL | — | utf8mb4 / utf8mb4_unicode_ci |
| 6 | `school_name` | varchar(255) | YES | NULL | — | utf8mb4 / utf8mb4_unicode_ci |
| 7 | `message` | text | NO | yok | — | utf8mb4 / utf8mb4_unicode_ci |
| 8 | `reply` | text | YES | NULL | — | utf8mb4 / utf8mb4_unicode_ci |
| 9 | `accept_terms` | tinyint | NO | `0` | — | — / — |
| 10 | `ip` | varchar(30) | NO | `0` | — | utf8mb4 / utf8mb4_unicode_ci |
| 11 | `has_read` | tinyint | NO | `0` | — | — / — |
| 12 | `created_at` | timestamp | YES | NULL | — | — / — |
| 13 | `updated_at` | timestamp | YES | NULL | — | — / — |

### İndeksler

| İsim | Tür | Sıralı kolonlar | Görünür |
| --- | --- | --- | --- |
| PRIMARY | PRIMARY | `id` | YES |
| contacts_has_read_index | INDEX | `has_read` | YES |
| contacts_locale_index | INDEX | `locale` | YES |

Fiziksel foreign key: yok. Eloquent ilişkileri 27 içinde değerlendirilir.

## exam_result_details

Satır: **149904**. Engine: `InnoDB`. Collation: `utf8mb4_unicode_ci`. Metadata data/index baytları: 13123584 / 32604160.

| # | Kolon | SQL tip | NULL kabul | Default | Extra | Charset / collation |
| --- | --- | --- | --- | --- | --- | --- |
| 1 | `id` | bigint unsigned | NO | yok | auto_increment | — / — |
| 2 | `exam_result_id` | bigint unsigned | NO | yok | — | — / — |
| 3 | `question_id` | bigint unsigned | NO | yok | — | — / — |
| 4 | `answer_id` | bigint unsigned | NO | yok | — | — / — |
| 5 | `correct` | tinyint | NO | `0` | — | — / — |
| 6 | `lesson_id` | bigint unsigned | NO | yok | — | — / — |
| 7 | `topic_id` | bigint unsigned | NO | yok | — | — / — |
| 8 | `time` | int | NO | `0` | — | — / — |
| 9 | `created_at` | timestamp | YES | NULL | — | — / — |
| 10 | `updated_at` | timestamp | YES | NULL | — | — / — |

### İndeksler

| İsim | Tür | Sıralı kolonlar | Görünür |
| --- | --- | --- | --- |
| PRIMARY | PRIMARY | `id` | YES |
| exam_result_details_answer_id_index | INDEX | `answer_id` | YES |
| exam_result_details_correct_index | INDEX | `correct` | YES |
| exam_result_details_exam_result_id_index | INDEX | `exam_result_id` | YES |
| exam_result_details_lesson_id_index | INDEX | `lesson_id` | YES |
| exam_result_details_question_id_index | INDEX | `question_id` | YES |
| exam_result_details_topic_id_index | INDEX | `topic_id` | YES |

Fiziksel foreign key: yok. Eloquent ilişkileri 27 içinde değerlendirilir.

## exam_results

Satır: **16231**. Engine: `InnoDB`. Collation: `utf8mb4_unicode_ci`. Metadata data/index baytları: 1589248 / 5177344.

| # | Kolon | SQL tip | NULL kabul | Default | Extra | Charset / collation |
| --- | --- | --- | --- | --- | --- | --- |
| 1 | `id` | bigint unsigned | NO | yok | auto_increment | — / — |
| 2 | `season_id` | bigint unsigned | YES | `1` | — | — / — |
| 3 | `user_id` | bigint unsigned | NO | yok | — | — / — |
| 4 | `exam_id` | bigint unsigned | NO | yok | — | — / — |
| 5 | `question_count` | int | NO | `0` | — | — / — |
| 6 | `correct_count` | int | NO | `0` | — | — / — |
| 7 | `incorrect_count` | int | NO | `0` | — | — / — |
| 8 | `time` | int | NO | `0` | — | — / — |
| 9 | `completed` | tinyint | NO | `0` | — | — / — |
| 10 | `created_at` | timestamp | YES | NULL | — | — / — |
| 11 | `updated_at` | timestamp | YES | NULL | — | — / — |
| 12 | `deleted_at` | timestamp | YES | NULL | — | — / — |

### İndeksler

| İsim | Tür | Sıralı kolonlar | Görünür |
| --- | --- | --- | --- |
| PRIMARY | PRIMARY | `id` | YES |
| exam_results_completed_index | INDEX | `completed` | YES |
| exam_results_correct_count_index | INDEX | `correct_count` | YES |
| exam_results_exam_id_index | INDEX | `exam_id` | YES |
| exam_results_incorrect_count_index | INDEX | `incorrect_count` | YES |
| exam_results_question_count_index | INDEX | `question_count` | YES |
| exam_results_time_index | INDEX | `time` | YES |
| exam_results_user_id_index | INDEX | `user_id` | YES |

Fiziksel foreign key: yok. Eloquent ilişkileri 27 içinde değerlendirilir.

## exam_reviews

Satır: **5**. Engine: `InnoDB`. Collation: `utf8mb4_unicode_ci`. Metadata data/index baytları: 16384 / 81920.

| # | Kolon | SQL tip | NULL kabul | Default | Extra | Charset / collation |
| --- | --- | --- | --- | --- | --- | --- |
| 1 | `id` | bigint unsigned | NO | yok | auto_increment | — / — |
| 2 | `exam_id` | bigint unsigned | NO | yok | — | — / — |
| 3 | `user_id` | bigint unsigned | NO | yok | — | — / — |
| 4 | `comment` | varchar(255) | NO | yok | — | utf8mb4 / utf8mb4_unicode_ci |
| 5 | `visibility` | varchar(255) | NO | `private` | — | utf8mb4 / utf8mb4_unicode_ci |
| 6 | `reply_id` | bigint unsigned | NO | `0` | — | — / — |
| 7 | `has_read` | tinyint | NO | `0` | — | — / — |
| 8 | `ip` | varchar(255) | YES | NULL | — | utf8mb4 / utf8mb4_unicode_ci |
| 9 | `created_at` | timestamp | YES | NULL | — | — / — |
| 10 | `updated_at` | timestamp | YES | NULL | — | — / — |

### İndeksler

| İsim | Tür | Sıralı kolonlar | Görünür |
| --- | --- | --- | --- |
| PRIMARY | PRIMARY | `id` | YES |
| exam_reviews_exam_id_index | INDEX | `exam_id` | YES |
| exam_reviews_has_read_index | INDEX | `has_read` | YES |
| exam_reviews_reply_id_index | INDEX | `reply_id` | YES |
| exam_reviews_user_id_index | INDEX | `user_id` | YES |
| exam_reviews_visibility_index | INDEX | `visibility` | YES |

Fiziksel foreign key: yok. Eloquent ilişkileri 27 içinde değerlendirilir.

## exams

Satır: **297**. Engine: `InnoDB`. Collation: `utf8mb4_unicode_ci`. Metadata data/index baytları: 13123584 / 49152.

| # | Kolon | SQL tip | NULL kabul | Default | Extra | Charset / collation |
| --- | --- | --- | --- | --- | --- | --- |
| 1 | `id` | bigint unsigned | NO | yok | auto_increment | — / — |
| 2 | `locale` | varchar(5) | NO | yok | — | utf8mb4 / utf8mb4_unicode_ci |
| 3 | `code` | varchar(255) | YES | NULL | — | utf8mb4 / utf8mb4_unicode_ci |
| 4 | `name` | varchar(255) | NO | yok | — | utf8mb4 / utf8mb4_unicode_ci |
| 5 | `content` | longtext | YES | NULL | — | utf8mb4 / utf8mb4_unicode_ci |
| 6 | `visibility` | tinyint | NO | `1` | — | — / — |
| 7 | `hits` | bigint | NO | `0` | — | — / — |
| 8 | `status` | varchar(255) | NO | `active` | — | utf8mb4 / utf8mb4_unicode_ci |
| 9 | `created_by` | bigint unsigned | NO | yok | — | — / — |
| 10 | `created_at` | timestamp | YES | NULL | — | — / — |
| 11 | `updated_at` | timestamp | YES | NULL | — | — / — |
| 12 | `deleted_at` | timestamp | YES | NULL | — | — / — |

### İndeksler

| İsim | Tür | Sıralı kolonlar | Görünür |
| --- | --- | --- | --- |
| PRIMARY | PRIMARY | `id` | YES |
| exams_code_index | INDEX | `code` | YES |
| exams_created_by_index | INDEX | `created_by` | YES |
| exams_locale_index | INDEX | `locale` | YES |

Fiziksel foreign key: yok. Eloquent ilişkileri 27 içinde değerlendirilir.

## exams_favorites

Satır: **112**. Engine: `InnoDB`. Collation: `utf8mb4_unicode_ci`. Metadata data/index baytları: 16384 / 0.

| # | Kolon | SQL tip | NULL kabul | Default | Extra | Charset / collation |
| --- | --- | --- | --- | --- | --- | --- |
| 1 | `exam_id` | bigint unsigned | NO | yok | — | — / — |
| 2 | `user_id` | bigint unsigned | NO | yok | — | — / — |

### İndeksler

| İsim | Tür | Sıralı kolonlar | Görünür |
| --- | --- | --- | --- |
| exams_favorites_exam_id_index | INDEX | `exam_id` | YES |
| exams_favorites_user_id_index | INDEX | `user_id` | YES |

Fiziksel foreign key: yok. Eloquent ilişkileri 27 içinde değerlendirilir.

PRIMARY yok; bu tabloda InnoDB iç kimliği ile mevcut indeksler kullanılır. Birden fazla model ilişki pivotunu temsil eder. İş anahtarına unique ekleme adayı, onaydan sonra yarış/tekrar kayıt testiyle değerlendirilir.

## exams_questions

Satır: **2982**. Engine: `InnoDB`. Collation: `utf8mb4_unicode_ci`. Metadata data/index baytları: 245760 / 0.

| # | Kolon | SQL tip | NULL kabul | Default | Extra | Charset / collation |
| --- | --- | --- | --- | --- | --- | --- |
| 1 | `exam_id` | bigint unsigned | NO | yok | — | — / — |
| 2 | `question_id` | bigint unsigned | NO | yok | — | — / — |
| 3 | `lesson_id` | bigint unsigned | NO | yok | — | — / — |
| 4 | `topic_id` | bigint unsigned | NO | yok | — | — / — |
| 5 | `order` | bigint unsigned | NO | `1` | — | — / — |

### İndeksler

| İsim | Tür | Sıralı kolonlar | Görünür |
| --- | --- | --- | --- |
| exams_questions_exam_id_index | INDEX | `exam_id` | YES |
| exams_questions_lesson_id_index | INDEX | `lesson_id` | YES |
| exams_questions_order_index | INDEX | `order` | YES |
| exams_questions_question_id_index | INDEX | `question_id` | YES |
| exams_questions_topic_id_index | INDEX | `topic_id` | YES |

Fiziksel foreign key: yok. Eloquent ilişkileri 27 içinde değerlendirilir.

PRIMARY yok; bu tabloda InnoDB iç kimliği ile mevcut indeksler kullanılır. Birden fazla model ilişki pivotunu temsil eder. İş anahtarına unique ekleme adayı, onaydan sonra yarış/tekrar kayıt testiyle değerlendirilir.

## failed_jobs

Satır: **358**. Engine: `InnoDB`. Collation: `utf8mb4_unicode_ci`. Metadata data/index baytları: 3686400 / 49152.

| # | Kolon | SQL tip | NULL kabul | Default | Extra | Charset / collation |
| --- | --- | --- | --- | --- | --- | --- |
| 1 | `id` | bigint unsigned | NO | yok | auto_increment | — / — |
| 2 | `uuid` | varchar(255) | NO | yok | — | utf8mb4 / utf8mb4_unicode_ci |
| 3 | `connection` | text | NO | yok | — | utf8mb4 / utf8mb4_unicode_ci |
| 4 | `queue` | text | NO | yok | — | utf8mb4 / utf8mb4_unicode_ci |
| 5 | `payload` | longtext | NO | yok | — | utf8mb4 / utf8mb4_unicode_ci |
| 6 | `exception` | longtext | NO | yok | — | utf8mb4 / utf8mb4_unicode_ci |
| 7 | `failed_at` | timestamp | NO | `CURRENT_TIMESTAMP` | DEFAULT_GENERATED | — / — |

### İndeksler

| İsim | Tür | Sıralı kolonlar | Görünür |
| --- | --- | --- | --- |
| PRIMARY | PRIMARY | `id` | YES |
| failed_jobs_uuid_unique | UNIQUE | `uuid` | YES |

Fiziksel foreign key: yok. Eloquent ilişkileri 27 içinde değerlendirilir.

## faqs

Satır: **5**. Engine: `InnoDB`. Collation: `utf8mb4_unicode_ci`. Metadata data/index baytları: 32768 / 16384.

| # | Kolon | SQL tip | NULL kabul | Default | Extra | Charset / collation |
| --- | --- | --- | --- | --- | --- | --- |
| 1 | `id` | bigint unsigned | NO | yok | auto_increment | — / — |
| 2 | `locale` | varchar(5) | NO | yok | — | utf8mb4 / utf8mb4_unicode_ci |
| 3 | `title` | varchar(255) | NO | yok | — | utf8mb4 / utf8mb4_unicode_ci |
| 4 | `content` | longtext | YES | NULL | — | utf8mb4 / utf8mb4_unicode_ci |
| 5 | `sort` | tinyint | NO | `1` | — | — / — |
| 6 | `status` | varchar(255) | NO | `active` | — | utf8mb4 / utf8mb4_unicode_ci |
| 7 | `created_at` | timestamp | YES | NULL | — | — / — |
| 8 | `updated_at` | timestamp | YES | NULL | — | — / — |

### İndeksler

| İsim | Tür | Sıralı kolonlar | Görünür |
| --- | --- | --- | --- |
| PRIMARY | PRIMARY | `id` | YES |
| faqs_locale_index | INDEX | `locale` | YES |

Fiziksel foreign key: yok. Eloquent ilişkileri 27 içinde değerlendirilir.

## job_batches

Satır: **0**. Engine: `InnoDB`. Collation: `utf8mb4_unicode_ci`. Metadata data/index baytları: 16384 / 0.

| # | Kolon | SQL tip | NULL kabul | Default | Extra | Charset / collation |
| --- | --- | --- | --- | --- | --- | --- |
| 1 | `id` | varchar(255) | NO | yok | — | utf8mb4 / utf8mb4_unicode_ci |
| 2 | `name` | varchar(255) | NO | yok | — | utf8mb4 / utf8mb4_unicode_ci |
| 3 | `total_jobs` | int | NO | yok | — | — / — |
| 4 | `pending_jobs` | int | NO | yok | — | — / — |
| 5 | `failed_jobs` | int | NO | yok | — | — / — |
| 6 | `failed_job_ids` | longtext | NO | yok | — | utf8mb4 / utf8mb4_unicode_ci |
| 7 | `options` | mediumtext | YES | NULL | — | utf8mb4 / utf8mb4_unicode_ci |
| 8 | `cancelled_at` | int | YES | NULL | — | — / — |
| 9 | `created_at` | int | NO | yok | — | — / — |
| 10 | `finished_at` | int | YES | NULL | — | — / — |

### İndeksler

| İsim | Tür | Sıralı kolonlar | Görünür |
| --- | --- | --- | --- |
| PRIMARY | PRIMARY | `id` | YES |

Fiziksel foreign key: yok. Eloquent ilişkileri 27 içinde değerlendirilir.

## jobs

Satır: **0**. Engine: `InnoDB`. Collation: `utf8mb4_unicode_ci`. Metadata data/index baytları: 16384 / 16384.

| # | Kolon | SQL tip | NULL kabul | Default | Extra | Charset / collation |
| --- | --- | --- | --- | --- | --- | --- |
| 1 | `id` | bigint unsigned | NO | yok | auto_increment | — / — |
| 2 | `queue` | varchar(255) | NO | yok | — | utf8mb4 / utf8mb4_unicode_ci |
| 3 | `payload` | longtext | NO | yok | — | utf8mb4 / utf8mb4_unicode_ci |
| 4 | `attempts` | tinyint unsigned | NO | yok | — | — / — |
| 5 | `reserved_at` | int unsigned | YES | NULL | — | — / — |
| 6 | `available_at` | int unsigned | NO | yok | — | — / — |
| 7 | `created_at` | int unsigned | NO | yok | — | — / — |
| 8 | `model_type` | int unsigned | NO | yok | — | — / — |

### İndeksler

| İsim | Tür | Sıralı kolonlar | Görünür |
| --- | --- | --- | --- |
| PRIMARY | PRIMARY | `id` | YES |
| jobs_queue_index | INDEX | `queue` | YES |

Fiziksel foreign key: yok. Eloquent ilişkileri 27 içinde değerlendirilir.

## languages

Satır: **3**. Engine: `InnoDB`. Collation: `utf8mb4_unicode_ci`. Metadata data/index baytları: 16384 / 32768.

| # | Kolon | SQL tip | NULL kabul | Default | Extra | Charset / collation |
| --- | --- | --- | --- | --- | --- | --- |
| 1 | `id` | bigint unsigned | NO | yok | auto_increment | — / — |
| 2 | `code` | varchar(5) | NO | yok | — | utf8mb4 / utf8mb4_unicode_ci |
| 3 | `name` | varchar(255) | NO | yok | — | utf8mb4 / utf8mb4_unicode_ci |
| 4 | `status` | varchar(12) | NO | `active` | — | utf8mb4 / utf8mb4_unicode_ci |
| 5 | `created_at` | timestamp | YES | NULL | — | — / — |
| 6 | `updated_at` | timestamp | YES | NULL | — | — / — |

### İndeksler

| İsim | Tür | Sıralı kolonlar | Görünür |
| --- | --- | --- | --- |
| PRIMARY | PRIMARY | `id` | YES |
| languages_code_unique | UNIQUE | `code` | YES |
| languages_status_index | INDEX | `status` | YES |

Fiziksel foreign key: yok. Eloquent ilişkileri 27 içinde değerlendirilir.

## latex_images

Satır: **2538**. Engine: `InnoDB`. Collation: `utf8mb4_unicode_ci`. Metadata data/index baytları: 360448 / 0.

| # | Kolon | SQL tip | NULL kabul | Default | Extra | Charset / collation |
| --- | --- | --- | --- | --- | --- | --- |
| 1 | `id` | bigint unsigned | NO | yok | auto_increment | — / — |
| 2 | `formula` | varchar(255) | NO | yok | — | utf8mb4 / utf8mb4_unicode_ci |
| 3 | `image` | varchar(255) | NO | yok | — | utf8mb4 / utf8mb4_unicode_ci |
| 4 | `created_at` | timestamp | YES | NULL | — | — / — |
| 5 | `updated_at` | timestamp | YES | NULL | — | — / — |

### İndeksler

| İsim | Tür | Sıralı kolonlar | Görünür |
| --- | --- | --- | --- |
| PRIMARY | PRIMARY | `id` | YES |

Fiziksel foreign key: yok. Eloquent ilişkileri 27 içinde değerlendirilir.

## league_results

Satır: **1238**. Engine: `InnoDB`. Collation: `utf8mb4_unicode_ci`. Metadata data/index baytları: 180224 / 294912.

| # | Kolon | SQL tip | NULL kabul | Default | Extra | Charset / collation |
| --- | --- | --- | --- | --- | --- | --- |
| 1 | `id` | bigint unsigned | NO | yok | auto_increment | — / — |
| 2 | `league_number` | bigint | NO | yok | — | — / — |
| 3 | `league_id` | bigint unsigned | NO | yok | — | — / — |
| 4 | `user_id` | bigint unsigned | NO | yok | — | — / — |
| 5 | `total_score` | bigint | NO | yok | — | — / — |
| 6 | `total_correct` | bigint | NO | yok | — | — / — |
| 7 | `total_incorrect` | bigint | NO | yok | — | — / — |
| 8 | `total_duration` | bigint | NO | yok | — | — / — |
| 9 | `position` | bigint | NO | yok | — | — / — |
| 10 | `rank_position` | bigint | NO | yok | — | — / — |
| 11 | `created_at` | timestamp | YES | NULL | — | — / — |
| 12 | `updated_at` | timestamp | YES | NULL | — | — / — |

### İndeksler

| İsim | Tür | Sıralı kolonlar | Görünür |
| --- | --- | --- | --- |
| PRIMARY | PRIMARY | `id` | YES |
| league_results_league_id_index | INDEX | `league_id` | YES |
| league_results_league_id_user_id_unique | UNIQUE | `league_id`, `user_id` | YES |
| league_results_league_number_index | INDEX | `league_number` | YES |
| league_results_rank_position_index | INDEX | `rank_position` | YES |
| league_results_user_id_index | INDEX | `user_id` | YES |

Fiziksel foreign key: yok. Eloquent ilişkileri 27 içinde değerlendirilir.

## leagues

Satır: **35**. Engine: `InnoDB`. Collation: `utf8mb4_unicode_ci`. Metadata data/index baytları: 16384 / 16384.

| # | Kolon | SQL tip | NULL kabul | Default | Extra | Charset / collation |
| --- | --- | --- | --- | --- | --- | --- |
| 1 | `id` | bigint unsigned | NO | yok | auto_increment | — / — |
| 2 | `code` | varchar(16) | NO | yok | — | utf8mb4 / utf8mb4_unicode_ci |
| 3 | `label` | varchar(255) | NO | yok | — | utf8mb4 / utf8mb4_unicode_ci |
| 4 | `calculated_at` | timestamp | YES | NULL | — | — / — |
| 5 | `start_at` | timestamp | YES | NULL | — | — / — |
| 6 | `end_at` | timestamp | YES | NULL | — | — / — |
| 7 | `created_at` | timestamp | YES | NULL | — | — / — |
| 8 | `updated_at` | timestamp | YES | NULL | — | — / — |

### İndeksler

| İsim | Tür | Sıralı kolonlar | Görünür |
| --- | --- | --- | --- |
| PRIMARY | PRIMARY | `id` | YES |
| leagues_code_index | INDEX | `code` | YES |

Fiziksel foreign key: yok. Eloquent ilişkileri 27 içinde değerlendirilir.

## lessons

Satır: **7**. Engine: `InnoDB`. Collation: `utf8mb4_unicode_ci`. Metadata data/index baytları: 16384 / 65536.

| # | Kolon | SQL tip | NULL kabul | Default | Extra | Charset / collation |
| --- | --- | --- | --- | --- | --- | --- |
| 1 | `id` | bigint unsigned | NO | yok | auto_increment | — / — |
| 2 | `code` | varchar(255) | YES | NULL | — | utf8mb4 / utf8mb4_unicode_ci |
| 3 | `name` | text | NO | yok | — | utf8mb4 / utf8mb4_unicode_ci |
| 4 | `description` | text | YES | NULL | — | utf8mb4 / utf8mb4_unicode_ci |
| 5 | `sort` | bigint unsigned | NO | `1` | — | — / — |
| 6 | `hits` | bigint unsigned | NO | `0` | — | — / — |
| 7 | `status` | varchar(12) | NO | `active` | — | utf8mb4 / utf8mb4_unicode_ci |
| 8 | `created_at` | timestamp | YES | NULL | — | — / — |
| 9 | `updated_at` | timestamp | YES | NULL | — | — / — |
| 10 | `deleted_at` | timestamp | YES | NULL | — | — / — |

### İndeksler

| İsim | Tür | Sıralı kolonlar | Görünür |
| --- | --- | --- | --- |
| PRIMARY | PRIMARY | `id` | YES |
| lessons_deleted_at_index | INDEX | `deleted_at` | YES |
| lessons_hits_index | INDEX | `hits` | YES |
| lessons_sort_index | INDEX | `sort` | YES |
| lessons_status_index | INDEX | `status` | YES |

Fiziksel foreign key: yok. Eloquent ilişkileri 27 içinde değerlendirilir.

## logs

Satır: **0**. Engine: `InnoDB`. Collation: `utf8mb4_unicode_ci`. Metadata data/index baytları: 16384 / 0.

| # | Kolon | SQL tip | NULL kabul | Default | Extra | Charset / collation |
| --- | --- | --- | --- | --- | --- | --- |
| 1 | `id` | bigint unsigned | NO | yok | auto_increment | — / — |
| 2 | `user_id` | bigint unsigned | NO | yok | — | — / — |
| 3 | `log_date` | datetime | NO | yok | — | — / — |
| 4 | `table_name` | varchar(50) | YES | NULL | — | utf8mb4 / utf8mb4_unicode_ci |
| 5 | `log_type` | varchar(50) | NO | yok | — | utf8mb4 / utf8mb4_unicode_ci |
| 6 | `ip` | varchar(255) | YES | NULL | — | utf8mb4 / utf8mb4_unicode_ci |
| 7 | `agent` | longtext | YES | NULL | — | utf8mb4 / utf8mb4_unicode_ci |
| 8 | `browser` | longtext | YES | NULL | — | utf8mb4 / utf8mb4_unicode_ci |
| 9 | `device` | longtext | YES | NULL | — | utf8mb4 / utf8mb4_unicode_ci |
| 10 | `data_id` | bigint unsigned | NO | `0` | — | — / — |
| 11 | `data` | longtext | NO | yok | — | utf8mb4 / utf8mb4_unicode_ci |

### İndeksler

| İsim | Tür | Sıralı kolonlar | Görünür |
| --- | --- | --- | --- |
| PRIMARY | PRIMARY | `id` | YES |

Fiziksel foreign key: yok. Eloquent ilişkileri 27 içinde değerlendirilir.

## meta

Satır: **4**. Engine: `InnoDB`. Collation: `utf8mb3_unicode_ci`. Metadata data/index baytları: 16384 / 16384.

| # | Kolon | SQL tip | NULL kabul | Default | Extra | Charset / collation |
| --- | --- | --- | --- | --- | --- | --- |
| 1 | `id` | int unsigned | NO | yok | auto_increment | — / — |
| 2 | `key` | varchar(110) | NO | yok | — | utf8mb3 / utf8mb3_unicode_ci |
| 3 | `value` | text | YES | NULL | — | utf8mb3 / utf8mb3_unicode_ci |
| 4 | `type` | varchar(255) | NO | `string` | — | utf8mb3 / utf8mb3_unicode_ci |
| 5 | `status` | tinyint(1) | NO | `1` | — | — / — |
| 6 | `owner_type` | varchar(80) | NO | yok | — | utf8mb3 / utf8mb3_unicode_ci |
| 7 | `owner_id` | int | NO | yok | — | — / — |
| 8 | `created_at` | timestamp | YES | NULL | — | — / — |
| 9 | `updated_at` | timestamp | YES | NULL | — | — / — |

### İndeksler

| İsim | Tür | Sıralı kolonlar | Görünür |
| --- | --- | --- | --- |
| PRIMARY | PRIMARY | `id` | YES |
| meta_key_owner_type_owner_id_unique | UNIQUE | `key`, `owner_type`, `owner_id` | YES |

Fiziksel foreign key: yok. Eloquent ilişkileri 27 içinde değerlendirilir.

## migrations

Satır: **36**. Engine: `InnoDB`. Collation: `utf8mb4_unicode_ci`. Metadata data/index baytları: 16384 / 0.

| # | Kolon | SQL tip | NULL kabul | Default | Extra | Charset / collation |
| --- | --- | --- | --- | --- | --- | --- |
| 1 | `id` | int unsigned | NO | yok | auto_increment | — / — |
| 2 | `migration` | varchar(255) | NO | yok | — | utf8mb4 / utf8mb4_unicode_ci |
| 3 | `batch` | int | NO | yok | — | — / — |

### İndeksler

| İsim | Tür | Sıralı kolonlar | Görünür |
| --- | --- | --- | --- |
| PRIMARY | PRIMARY | `id` | YES |

Fiziksel foreign key: yok. Eloquent ilişkileri 27 içinde değerlendirilir.

## model_has_permissions

Satır: **0**. Engine: `InnoDB`. Collation: `utf8mb4_unicode_ci`. Metadata data/index baytları: 16384 / 16384.

| # | Kolon | SQL tip | NULL kabul | Default | Extra | Charset / collation |
| --- | --- | --- | --- | --- | --- | --- |
| 1 | `permission_id` | bigint unsigned | NO | yok | — | — / — |
| 2 | `model_type` | varchar(255) | NO | yok | — | utf8mb4 / utf8mb4_unicode_ci |
| 3 | `model_id` | bigint unsigned | NO | yok | — | — / — |

### İndeksler

| İsim | Tür | Sıralı kolonlar | Görünür |
| --- | --- | --- | --- |
| PRIMARY | PRIMARY | `permission_id`, `model_id`, `model_type` | YES |
| model_has_permissions_model_id_model_type_index | INDEX | `model_id`, `model_type` | YES |

### Fiziksel foreign key

| Ad | Alan | Hedef | ON DELETE | ON UPDATE |
| --- | --- | --- | --- | --- |
| model_has_permissions_permission_id_foreign | permission_id | permissions.id | CASCADE | NO ACTION |

## model_has_roles

Satır: **381**. Engine: `InnoDB`. Collation: `utf8mb4_unicode_ci`. Metadata data/index baytları: 16384 / 16384.

| # | Kolon | SQL tip | NULL kabul | Default | Extra | Charset / collation |
| --- | --- | --- | --- | --- | --- | --- |
| 1 | `role_id` | bigint unsigned | NO | yok | — | — / — |
| 2 | `model_type` | varchar(255) | NO | yok | — | utf8mb4 / utf8mb4_unicode_ci |
| 3 | `model_id` | bigint unsigned | NO | yok | — | — / — |

### İndeksler

| İsim | Tür | Sıralı kolonlar | Görünür |
| --- | --- | --- | --- |
| PRIMARY | PRIMARY | `role_id`, `model_id`, `model_type` | YES |
| model_has_roles_model_id_model_type_index | INDEX | `model_id`, `model_type` | YES |

### Fiziksel foreign key

| Ad | Alan | Hedef | ON DELETE | ON UPDATE |
| --- | --- | --- | --- | --- |
| model_has_roles_role_id_foreign | role_id | roles.id | CASCADE | NO ACTION |

## newsletters

Satır: **0**. Engine: `InnoDB`. Collation: `utf8mb4_unicode_ci`. Metadata data/index baytları: 16384 / 0.

| # | Kolon | SQL tip | NULL kabul | Default | Extra | Charset / collation |
| --- | --- | --- | --- | --- | --- | --- |
| 1 | `id` | bigint unsigned | NO | yok | auto_increment | — / — |
| 2 | `email` | varchar(255) | NO | yok | — | utf8mb4 / utf8mb4_unicode_ci |
| 3 | `accept_terms` | tinyint | NO | `1` | — | — / — |
| 4 | `ip` | varchar(30) | NO | `0` | — | utf8mb4 / utf8mb4_unicode_ci |
| 5 | `token` | varchar(255) | YES | NULL | — | utf8mb4 / utf8mb4_unicode_ci |
| 6 | `created_at` | timestamp | YES | NULL | — | — / — |
| 7 | `updated_at` | timestamp | YES | NULL | — | — / — |
| 8 | `deleted_at` | timestamp | YES | NULL | — | — / — |

### İndeksler

| İsim | Tür | Sıralı kolonlar | Görünür |
| --- | --- | --- | --- |
| PRIMARY | PRIMARY | `id` | YES |

Fiziksel foreign key: yok. Eloquent ilişkileri 27 içinde değerlendirilir.

## newsletters_mails

Satır: **0**. Engine: `InnoDB`. Collation: `utf8mb4_unicode_ci`. Metadata data/index baytları: 16384 / 0.

| # | Kolon | SQL tip | NULL kabul | Default | Extra | Charset / collation |
| --- | --- | --- | --- | --- | --- | --- |
| 1 | `locale` | varchar(5) | NO | yok | — | utf8mb4 / utf8mb4_unicode_ci |
| 2 | `email` | varchar(255) | NO | yok | — | utf8mb4 / utf8mb4_unicode_ci |
| 3 | `content` | longtext | YES | NULL | — | utf8mb4 / utf8mb4_unicode_ci |
| 4 | `sent_at` | timestamp | YES | NULL | — | — / — |

### İndeksler

| İsim | Tür | Sıralı kolonlar | Görünür |
| --- | --- | --- | --- |
| newsletters_mails_email_index | INDEX | `email` | YES |
| newsletters_mails_locale_index | INDEX | `locale` | YES |

Fiziksel foreign key: yok. Eloquent ilişkileri 27 içinde değerlendirilir.

PRIMARY yok; bu tabloda InnoDB iç kimliği ile mevcut indeksler kullanılır. Birden fazla model ilişki pivotunu temsil eder. İş anahtarına unique ekleme adayı, onaydan sonra yarış/tekrar kayıt testiyle değerlendirilir.

## pages

Satır: **3**. Engine: `InnoDB`. Collation: `utf8mb4_unicode_ci`. Metadata data/index baytları: 81920 / 49152.

| # | Kolon | SQL tip | NULL kabul | Default | Extra | Charset / collation |
| --- | --- | --- | --- | --- | --- | --- |
| 1 | `id` | bigint unsigned | NO | yok | auto_increment | — / — |
| 2 | `slug` | varchar(255) | NO | yok | — | utf8mb4 / utf8mb4_unicode_ci |
| 3 | `title` | longtext | NO | yok | — | utf8mb4 / utf8mb4_unicode_ci |
| 4 | `brief` | longtext | YES | NULL | — | utf8mb4 / utf8mb4_unicode_ci |
| 5 | `content` | longtext | YES | NULL | — | utf8mb4 / utf8mb4_unicode_ci |
| 6 | `keywords` | text | YES | NULL | — | utf8mb4 / utf8mb4_unicode_ci |
| 7 | `images` | longtext | YES | NULL | — | utf8mb4 / utf8mb4_unicode_ci |
| 8 | `sort` | tinyint | NO | `1` | — | — / — |
| 9 | `menu` | varchar(255) | NO | `footer` | — | utf8mb4 / utf8mb4_unicode_ci |
| 10 | `type` | varchar(10) | NO | `system` | — | utf8mb4 / utf8mb4_unicode_ci |
| 11 | `status` | varchar(10) | NO | `active` | — | utf8mb4 / utf8mb4_unicode_ci |
| 12 | `link` | text | YES | NULL | — | utf8mb4 / utf8mb4_unicode_ci |
| 13 | `created_at` | timestamp | YES | NULL | — | — / — |
| 14 | `updated_at` | timestamp | YES | NULL | — | — / — |
| 15 | `deleted_at` | timestamp | YES | NULL | — | — / — |

### İndeksler

| İsim | Tür | Sıralı kolonlar | Görünür |
| --- | --- | --- | --- |
| PRIMARY | PRIMARY | `id` | YES |
| pages_slug_unique | UNIQUE | `slug` | YES |
| pages_status_index | INDEX | `status` | YES |
| pages_type_index | INDEX | `type` | YES |

Fiziksel foreign key: yok. Eloquent ilişkileri 27 içinde değerlendirilir.

## password_reset_tokens

Satır: **12**. Engine: `InnoDB`. Collation: `utf8mb4_unicode_ci`. Metadata data/index baytları: 16384 / 0.

| # | Kolon | SQL tip | NULL kabul | Default | Extra | Charset / collation |
| --- | --- | --- | --- | --- | --- | --- |
| 1 | `email` | varchar(255) | NO | yok | — | utf8mb4 / utf8mb4_unicode_ci |
| 2 | `token` | varchar(255) | NO | yok | — | utf8mb4 / utf8mb4_unicode_ci |
| 3 | `created_at` | timestamp | YES | NULL | — | — / — |

### İndeksler

| İsim | Tür | Sıralı kolonlar | Görünür |
| --- | --- | --- | --- |
| PRIMARY | PRIMARY | `email` | YES |

Fiziksel foreign key: yok. Eloquent ilişkileri 27 içinde değerlendirilir.

## permissions

Satır: **75**. Engine: `InnoDB`. Collation: `utf8mb4_unicode_ci`. Metadata data/index baytları: 16384 / 16384.

| # | Kolon | SQL tip | NULL kabul | Default | Extra | Charset / collation |
| --- | --- | --- | --- | --- | --- | --- |
| 1 | `id` | bigint unsigned | NO | yok | auto_increment | — / — |
| 2 | `name` | varchar(255) | NO | yok | — | utf8mb4 / utf8mb4_unicode_ci |
| 3 | `guard_name` | varchar(255) | NO | `web` | — | utf8mb4 / utf8mb4_unicode_ci |
| 4 | `created_at` | timestamp | YES | NULL | — | — / — |
| 5 | `updated_at` | timestamp | YES | NULL | — | — / — |

### İndeksler

| İsim | Tür | Sıralı kolonlar | Görünür |
| --- | --- | --- | --- |
| PRIMARY | PRIMARY | `id` | YES |
| permissions_name_guard_name_unique | UNIQUE | `name`, `guard_name` | YES |

Fiziksel foreign key: yok. Eloquent ilişkileri 27 içinde değerlendirilir.

## questions

Satır: **3067**. Engine: `InnoDB`. Collation: `utf8mb4_unicode_ci`. Metadata data/index baytları: 1589248 / 737280.

| # | Kolon | SQL tip | NULL kabul | Default | Extra | Charset / collation |
| --- | --- | --- | --- | --- | --- | --- |
| 1 | `id` | bigint unsigned | NO | yok | auto_increment | — / — |
| 2 | `lesson_id` | bigint unsigned | NO | yok | — | — / — |
| 3 | `topic_id` | bigint unsigned | NO | yok | — | — / — |
| 4 | `locale` | varchar(5) | NO | yok | — | utf8mb4 / utf8mb4_unicode_ci |
| 5 | `code` | varchar(255) | YES | NULL | — | utf8mb4 / utf8mb4_unicode_ci |
| 6 | `title` | text | YES | NULL | — | utf8mb4 / utf8mb4_unicode_ci |
| 7 | `attachment` | varchar(255) | YES | NULL | — | utf8mb4 / utf8mb4_unicode_ci |
| 8 | `solution` | varchar(255) | NO | yok | — | utf8mb4 / utf8mb4_unicode_ci |
| 9 | `sort` | int | NO | `1` | — | — / — |
| 10 | `time` | int | NO | `300` | — | — / — |
| 11 | `status` | varchar(255) | NO | `active` | — | utf8mb4 / utf8mb4_unicode_ci |
| 12 | `created_at` | timestamp | YES | NULL | — | — / — |
| 13 | `updated_at` | timestamp | YES | NULL | — | — / — |
| 14 | `deleted_at` | timestamp | YES | NULL | — | — / — |

### İndeksler

| İsim | Tür | Sıralı kolonlar | Görünür |
| --- | --- | --- | --- |
| PRIMARY | PRIMARY | `id` | YES |
| questions_deleted_at_index | INDEX | `deleted_at` | YES |
| questions_lesson_id_index | INDEX | `lesson_id` | YES |
| questions_locale_index | INDEX | `locale` | YES |
| questions_sort_index | INDEX | `sort` | YES |
| questions_status_index | INDEX | `status` | YES |
| questions_time_index | INDEX | `time` | YES |
| questions_topic_id_index | INDEX | `topic_id` | YES |

Fiziksel foreign key: yok. Eloquent ilişkileri 27 içinde değerlendirilir.

## role_has_permissions

Satır: **128**. Engine: `InnoDB`. Collation: `utf8mb4_unicode_ci`. Metadata data/index baytları: 16384 / 16384.

| # | Kolon | SQL tip | NULL kabul | Default | Extra | Charset / collation |
| --- | --- | --- | --- | --- | --- | --- |
| 1 | `permission_id` | bigint unsigned | NO | yok | — | — / — |
| 2 | `role_id` | bigint unsigned | NO | yok | — | — / — |

### İndeksler

| İsim | Tür | Sıralı kolonlar | Görünür |
| --- | --- | --- | --- |
| PRIMARY | PRIMARY | `permission_id`, `role_id` | YES |
| role_has_permissions_role_id_foreign | INDEX | `role_id` | YES |

### Fiziksel foreign key

| Ad | Alan | Hedef | ON DELETE | ON UPDATE |
| --- | --- | --- | --- | --- |
| role_has_permissions_permission_id_foreign | permission_id | permissions.id | CASCADE | NO ACTION |
| role_has_permissions_role_id_foreign | role_id | roles.id | CASCADE | NO ACTION |

## roles

Satır: **7**. Engine: `InnoDB`. Collation: `utf8mb4_unicode_ci`. Metadata data/index baytları: 16384 / 16384.

| # | Kolon | SQL tip | NULL kabul | Default | Extra | Charset / collation |
| --- | --- | --- | --- | --- | --- | --- |
| 1 | `id` | bigint unsigned | NO | yok | auto_increment | — / — |
| 2 | `name` | varchar(255) | NO | yok | — | utf8mb4 / utf8mb4_unicode_ci |
| 3 | `guard_name` | varchar(255) | NO | yok | — | utf8mb4 / utf8mb4_unicode_ci |
| 4 | `created_at` | timestamp | YES | NULL | — | — / — |
| 5 | `updated_at` | timestamp | YES | NULL | — | — / — |

### İndeksler

| İsim | Tür | Sıralı kolonlar | Görünür |
| --- | --- | --- | --- |
| PRIMARY | PRIMARY | `id` | YES |
| roles_name_guard_name_unique | UNIQUE | `name`, `guard_name` | YES |

Fiziksel foreign key: yok. Eloquent ilişkileri 27 içinde değerlendirilir.

## seasons

Satır: **2**. Engine: `InnoDB`. Collation: `utf8mb4_unicode_ci`. Metadata data/index baytları: 16384 / 32768.

| # | Kolon | SQL tip | NULL kabul | Default | Extra | Charset / collation |
| --- | --- | --- | --- | --- | --- | --- |
| 1 | `id` | bigint unsigned | NO | yok | auto_increment | — / — |
| 2 | `name` | varchar(255) | NO | yok | — | utf8mb4 / utf8mb4_unicode_ci |
| 3 | `start_date` | date | NO | yok | — | — / — |
| 4 | `end_date` | date | NO | yok | — | — / — |
| 5 | `status` | varchar(10) | NO | `passive` | — | utf8mb4 / utf8mb4_unicode_ci |
| 6 | `created_at` | timestamp | YES | NULL | — | — / — |
| 7 | `updated_at` | timestamp | YES | NULL | — | — / — |

### İndeksler

| İsim | Tür | Sıralı kolonlar | Görünür |
| --- | --- | --- | --- |
| PRIMARY | PRIMARY | `id` | YES |
| seasons_start_date_end_date_index | INDEX | `start_date`, `end_date` | YES |
| seasons_status_index | INDEX | `status` | YES |

Fiziksel foreign key: yok. Eloquent ilişkileri 27 içinde değerlendirilir.

## sessions

Satır: **0**. Engine: `InnoDB`. Collation: `utf8mb4_unicode_ci`. Metadata data/index baytları: 16384 / 32768.

| # | Kolon | SQL tip | NULL kabul | Default | Extra | Charset / collation |
| --- | --- | --- | --- | --- | --- | --- |
| 1 | `id` | varchar(255) | NO | yok | — | utf8mb4 / utf8mb4_unicode_ci |
| 2 | `user_id` | bigint unsigned | YES | NULL | — | — / — |
| 3 | `ip_address` | varchar(45) | YES | NULL | — | utf8mb4 / utf8mb4_unicode_ci |
| 4 | `user_agent` | text | YES | NULL | — | utf8mb4 / utf8mb4_unicode_ci |
| 5 | `payload` | longtext | NO | yok | — | utf8mb4 / utf8mb4_unicode_ci |
| 6 | `last_activity` | int | NO | yok | — | — / — |

### İndeksler

| İsim | Tür | Sıralı kolonlar | Görünür |
| --- | --- | --- | --- |
| PRIMARY | PRIMARY | `id` | YES |
| sessions_last_activity_index | INDEX | `last_activity` | YES |
| sessions_user_id_index | INDEX | `user_id` | YES |

Fiziksel foreign key: yok. Eloquent ilişkileri 27 içinde değerlendirilir.

## settings

Satır: **101**. Engine: `InnoDB`. Collation: `utf8mb4_unicode_ci`. Metadata data/index baytları: 1589248 / 0.

| # | Kolon | SQL tip | NULL kabul | Default | Extra | Charset / collation |
| --- | --- | --- | --- | --- | --- | --- |
| 1 | `id` | bigint unsigned | NO | yok | auto_increment | — / — |
| 2 | `name` | varchar(255) | NO | yok | — | utf8mb4 / utf8mb4_unicode_ci |
| 3 | `value` | longtext | YES | NULL | — | utf8mb4 / utf8mb4_unicode_ci |
| 4 | `settingable_type` | varchar(255) | YES | NULL | — | utf8mb4 / utf8mb4_unicode_ci |
| 5 | `settingable_id` | varchar(255) | YES | NULL | — | utf8mb4 / utf8mb4_unicode_ci |
| 6 | `created_at` | timestamp | YES | NULL | — | — / — |
| 7 | `updated_at` | timestamp | YES | NULL | — | — / — |

### İndeksler

| İsim | Tür | Sıralı kolonlar | Görünür |
| --- | --- | --- | --- |
| PRIMARY | PRIMARY | `id` | YES |

Fiziksel foreign key: yok. Eloquent ilişkileri 27 içinde değerlendirilir.

## teacher_student

Satır: **38**. Engine: `InnoDB`. Collation: `utf8mb4_unicode_ci`. Metadata data/index baytları: 16384 / 0.

| # | Kolon | SQL tip | NULL kabul | Default | Extra | Charset / collation |
| --- | --- | --- | --- | --- | --- | --- |
| 1 | `user_id` | bigint unsigned | NO | yok | — | — / — |
| 2 | `teacher_id` | bigint unsigned | NO | yok | — | — / — |
| 3 | `created_at` | timestamp | YES | NULL | — | — / — |

### İndeksler

| İsim | Tür | Sıralı kolonlar | Görünür |
| --- | --- | --- | --- |
| teacher_student_teacher_id_index | INDEX | `teacher_id` | YES |
| teacher_student_user_id_index | INDEX | `user_id` | YES |

Fiziksel foreign key: yok. Eloquent ilişkileri 27 içinde değerlendirilir.

PRIMARY yok; bu tabloda InnoDB iç kimliği ile mevcut indeksler kullanılır. Birden fazla model ilişki pivotunu temsil eder. İş anahtarına unique ekleme adayı, onaydan sonra yarış/tekrar kayıt testiyle değerlendirilir.

## telescope_entries

Satır: **0**. Engine: `InnoDB`. Collation: `utf8mb4_unicode_ci`. Metadata data/index baytları: 16384 / 81920.

| # | Kolon | SQL tip | NULL kabul | Default | Extra | Charset / collation |
| --- | --- | --- | --- | --- | --- | --- |
| 1 | `sequence` | bigint unsigned | NO | yok | auto_increment | — / — |
| 2 | `uuid` | char(36) | NO | yok | — | utf8mb4 / utf8mb4_unicode_ci |
| 3 | `batch_id` | char(36) | NO | yok | — | utf8mb4 / utf8mb4_unicode_ci |
| 4 | `family_hash` | varchar(255) | YES | NULL | — | utf8mb4 / utf8mb4_unicode_ci |
| 5 | `should_display_on_index` | tinyint(1) | NO | `1` | — | — / — |
| 6 | `type` | varchar(20) | NO | yok | — | utf8mb4 / utf8mb4_unicode_ci |
| 7 | `content` | longtext | NO | yok | — | utf8mb4 / utf8mb4_unicode_ci |
| 8 | `created_at` | datetime | YES | NULL | — | — / — |

### İndeksler

| İsim | Tür | Sıralı kolonlar | Görünür |
| --- | --- | --- | --- |
| PRIMARY | PRIMARY | `sequence` | YES |
| telescope_entries_batch_id_index | INDEX | `batch_id` | YES |
| telescope_entries_created_at_index | INDEX | `created_at` | YES |
| telescope_entries_family_hash_index | INDEX | `family_hash` | YES |
| telescope_entries_type_should_display_on_index_index | INDEX | `type`, `should_display_on_index` | YES |
| telescope_entries_uuid_unique | UNIQUE | `uuid` | YES |

Fiziksel foreign key: yok. Eloquent ilişkileri 27 içinde değerlendirilir.

## telescope_entries_tags

Satır: **0**. Engine: `InnoDB`. Collation: `utf8mb4_unicode_ci`. Metadata data/index baytları: 16384 / 16384.

| # | Kolon | SQL tip | NULL kabul | Default | Extra | Charset / collation |
| --- | --- | --- | --- | --- | --- | --- |
| 1 | `entry_uuid` | char(36) | NO | yok | — | utf8mb4 / utf8mb4_unicode_ci |
| 2 | `tag` | varchar(255) | NO | yok | — | utf8mb4 / utf8mb4_unicode_ci |

### İndeksler

| İsim | Tür | Sıralı kolonlar | Görünür |
| --- | --- | --- | --- |
| PRIMARY | PRIMARY | `entry_uuid`, `tag` | YES |
| telescope_entries_tags_tag_index | INDEX | `tag` | YES |

### Fiziksel foreign key

| Ad | Alan | Hedef | ON DELETE | ON UPDATE |
| --- | --- | --- | --- | --- |
| telescope_entries_tags_entry_uuid_foreign | entry_uuid | telescope_entries.uuid | CASCADE | NO ACTION |

## telescope_monitoring

Satır: **0**. Engine: `InnoDB`. Collation: `utf8mb4_unicode_ci`. Metadata data/index baytları: 16384 / 0.

| # | Kolon | SQL tip | NULL kabul | Default | Extra | Charset / collation |
| --- | --- | --- | --- | --- | --- | --- |
| 1 | `tag` | varchar(255) | NO | yok | — | utf8mb4 / utf8mb4_unicode_ci |

### İndeksler

| İsim | Tür | Sıralı kolonlar | Görünür |
| --- | --- | --- | --- |
| PRIMARY | PRIMARY | `tag` | YES |

Fiziksel foreign key: yok. Eloquent ilişkileri 27 içinde değerlendirilir.

## tests

Satır: **9**. Engine: `InnoDB`. Collation: `utf8mb4_unicode_ci`. Metadata data/index baytları: 16384 / 49152.

| # | Kolon | SQL tip | NULL kabul | Default | Extra | Charset / collation |
| --- | --- | --- | --- | --- | --- | --- |
| 1 | `id` | bigint unsigned | NO | yok | auto_increment | — / — |
| 2 | `locale` | varchar(5) | NO | yok | — | utf8mb4 / utf8mb4_unicode_ci |
| 3 | `code` | varchar(255) | YES | NULL | — | utf8mb4 / utf8mb4_unicode_ci |
| 4 | `name` | varchar(255) | NO | yok | — | utf8mb4 / utf8mb4_unicode_ci |
| 5 | `content` | longtext | YES | NULL | — | utf8mb4 / utf8mb4_unicode_ci |
| 6 | `hits` | bigint | NO | `0` | — | — / — |
| 7 | `status` | varchar(255) | NO | `active` | — | utf8mb4 / utf8mb4_unicode_ci |
| 8 | `duration` | int | NO | `600` | — | — / — |
| 9 | `passing_score` | int | NO | `60` | — | — / — |
| 10 | `correct_point` | int | NO | `3` | — | — / — |
| 11 | `incorrect_point` | int | NO | `-1` | — | — / — |
| 12 | `created_by` | bigint unsigned | NO | yok | — | — / — |
| 13 | `created_at` | timestamp | YES | NULL | — | — / — |
| 14 | `updated_at` | timestamp | YES | NULL | — | — / — |
| 15 | `deleted_at` | timestamp | YES | NULL | — | — / — |

### İndeksler

| İsim | Tür | Sıralı kolonlar | Görünür |
| --- | --- | --- | --- |
| PRIMARY | PRIMARY | `id` | YES |
| tests_code_index | INDEX | `code` | YES |
| tests_created_by_index | INDEX | `created_by` | YES |
| tests_locale_index | INDEX | `locale` | YES |

Fiziksel foreign key: yok. Eloquent ilişkileri 27 içinde değerlendirilir.

## tests_questions

Satır: **263**. Engine: `InnoDB`. Collation: `utf8mb4_unicode_ci`. Metadata data/index baytları: 49152 / 0.

| # | Kolon | SQL tip | NULL kabul | Default | Extra | Charset / collation |
| --- | --- | --- | --- | --- | --- | --- |
| 1 | `test_id` | bigint unsigned | NO | yok | — | — / — |
| 2 | `section_id` | bigint unsigned | NO | yok | — | — / — |
| 3 | `question_id` | bigint unsigned | NO | yok | — | — / — |
| 4 | `lesson_id` | bigint unsigned | NO | yok | — | — / — |
| 5 | `topic_id` | bigint unsigned | NO | yok | — | — / — |
| 6 | `order` | bigint unsigned | NO | `1` | — | — / — |

### İndeksler

| İsim | Tür | Sıralı kolonlar | Görünür |
| --- | --- | --- | --- |
| tests_questions_lesson_id_index | INDEX | `lesson_id` | YES |
| tests_questions_order_index | INDEX | `order` | YES |
| tests_questions_question_id_index | INDEX | `question_id` | YES |
| tests_questions_section_id_index | INDEX | `section_id` | YES |
| tests_questions_test_id_index | INDEX | `test_id` | YES |
| tests_questions_topic_id_index | INDEX | `topic_id` | YES |

Fiziksel foreign key: yok. Eloquent ilişkileri 27 içinde değerlendirilir.

PRIMARY yok; bu tabloda InnoDB iç kimliği ile mevcut indeksler kullanılır. Birden fazla model ilişki pivotunu temsil eder. İş anahtarına unique ekleme adayı, onaydan sonra yarış/tekrar kayıt testiyle değerlendirilir.

## tests_result_details

Satır: **10609**. Engine: `InnoDB`. Collation: `utf8mb4_unicode_ci`. Metadata data/index baytları: 1589248 / 4079616.

| # | Kolon | SQL tip | NULL kabul | Default | Extra | Charset / collation |
| --- | --- | --- | --- | --- | --- | --- |
| 1 | `id` | bigint unsigned | NO | yok | auto_increment | — / — |
| 2 | `tests_result_id` | bigint unsigned | NO | yok | — | — / — |
| 3 | `section_id` | bigint unsigned | NO | yok | — | — / — |
| 4 | `question_id` | bigint unsigned | NO | yok | — | — / — |
| 5 | `answer_id` | bigint unsigned | NO | yok | — | — / — |
| 6 | `correct` | tinyint | NO | `0` | — | — / — |
| 7 | `point` | tinyint | NO | `0` | — | — / — |
| 8 | `lesson_id` | bigint unsigned | NO | yok | — | — / — |
| 9 | `topic_id` | bigint unsigned | NO | yok | — | — / — |
| 10 | `time` | int | NO | `0` | — | — / — |
| 11 | `created_at` | timestamp | YES | NULL | — | — / — |
| 12 | `updated_at` | timestamp | YES | NULL | — | — / — |

### İndeksler

| İsim | Tür | Sıralı kolonlar | Görünür |
| --- | --- | --- | --- |
| PRIMARY | PRIMARY | `id` | YES |
| tests_result_details_answer_id_index | INDEX | `answer_id` | YES |
| tests_result_details_correct_index | INDEX | `correct` | YES |
| tests_result_details_lesson_id_index | INDEX | `lesson_id` | YES |
| tests_result_details_question_id_index | INDEX | `question_id` | YES |
| tests_result_details_section_id_index | INDEX | `section_id` | YES |
| tests_result_details_tests_result_id_index | INDEX | `tests_result_id` | YES |
| tests_result_details_topic_id_index | INDEX | `topic_id` | YES |
| unique_tests_result_details | UNIQUE | `tests_result_id`, `section_id`, `question_id` | YES |

Fiziksel foreign key: yok. Eloquent ilişkileri 27 içinde değerlendirilir.

## tests_results

Satır: **379**. Engine: `InnoDB`. Collation: `utf8mb4_unicode_ci`. Metadata data/index baytları: 65536 / 98304.

| # | Kolon | SQL tip | NULL kabul | Default | Extra | Charset / collation |
| --- | --- | --- | --- | --- | --- | --- |
| 1 | `id` | bigint unsigned | NO | yok | auto_increment | — / — |
| 2 | `season_id` | bigint unsigned | YES | `1` | — | — / — |
| 3 | `user_id` | bigint unsigned | NO | yok | — | — / — |
| 4 | `test_id` | bigint unsigned | NO | yok | — | — / — |
| 5 | `question_count` | int | NO | `0` | — | — / — |
| 6 | `correct_count` | int | NO | `0` | — | — / — |
| 7 | `incorrect_count` | int | NO | `0` | — | — / — |
| 8 | `point` | tinyint | NO | `0` | — | — / — |
| 9 | `duration` | int | NO | `0` | — | — / — |
| 10 | `passing_score` | int | NO | `0` | — | — / — |
| 11 | `completed` | tinyint | NO | `0` | — | — / — |
| 12 | `completed_at` | timestamp | YES | NULL | — | — / — |
| 13 | `expires_at` | timestamp | YES | NULL | — | — / — |
| 14 | `created_at` | timestamp | YES | NULL | — | — / — |
| 15 | `updated_at` | timestamp | YES | NULL | — | — / — |
| 16 | `deleted_at` | timestamp | YES | NULL | — | — / — |

### İndeksler

| İsim | Tür | Sıralı kolonlar | Görünür |
| --- | --- | --- | --- |
| PRIMARY | PRIMARY | `id` | YES |
| tests_results_completed_index | INDEX | `completed` | YES |
| tests_results_correct_count_index | INDEX | `correct_count` | YES |
| tests_results_incorrect_count_index | INDEX | `incorrect_count` | YES |
| tests_results_question_count_index | INDEX | `question_count` | YES |
| tests_results_test_id_index | INDEX | `test_id` | YES |
| tests_results_user_id_index | INDEX | `user_id` | YES |

Fiziksel foreign key: yok. Eloquent ilişkileri 27 içinde değerlendirilir.

## tests_sections

Satır: **307**. Engine: `InnoDB`. Collation: `utf8mb4_unicode_ci`. Metadata data/index baytları: 65536 / 49152.

| # | Kolon | SQL tip | NULL kabul | Default | Extra | Charset / collation |
| --- | --- | --- | --- | --- | --- | --- |
| 1 | `id` | bigint unsigned | NO | yok | auto_increment | — / — |
| 2 | `test_id` | bigint unsigned | NO | yok | — | — / — |
| 3 | `parent_id` | bigint unsigned | NO | yok | — | — / — |
| 4 | `type` | varchar(16) | NO | `topic` | — | utf8mb4 / utf8mb4_unicode_ci |
| 5 | `name` | text | YES | NULL | — | utf8mb4 / utf8mb4_unicode_ci |
| 6 | `order` | bigint unsigned | NO | `1` | — | — / — |
| 7 | `created_at` | timestamp | YES | NULL | — | — / — |
| 8 | `updated_at` | timestamp | YES | NULL | — | — / — |

### İndeksler

| İsim | Tür | Sıralı kolonlar | Görünür |
| --- | --- | --- | --- |
| PRIMARY | PRIMARY | `id` | YES |
| tests_sections_order_index | INDEX | `order` | YES |
| tests_sections_test_id_index | INDEX | `test_id` | YES |
| tests_sections_type_index | INDEX | `type` | YES |

Fiziksel foreign key: yok. Eloquent ilişkileri 27 içinde değerlendirilir.

## tests_sections_meta

Satır: **528**. Engine: `InnoDB`. Collation: `utf8mb3_unicode_ci`. Metadata data/index baytları: 98304 / 65536.

| # | Kolon | SQL tip | NULL kabul | Default | Extra | Charset / collation |
| --- | --- | --- | --- | --- | --- | --- |
| 1 | `id` | int unsigned | NO | yok | auto_increment | — / — |
| 2 | `key` | varchar(110) | NO | yok | — | utf8mb3 / utf8mb3_unicode_ci |
| 3 | `value` | text | YES | NULL | — | utf8mb3 / utf8mb3_unicode_ci |
| 4 | `type` | varchar(255) | NO | `string` | — | utf8mb3 / utf8mb3_unicode_ci |
| 5 | `status` | tinyint(1) | NO | `1` | — | — / — |
| 6 | `owner_type` | varchar(80) | NO | yok | — | utf8mb3 / utf8mb3_unicode_ci |
| 7 | `owner_id` | int | NO | yok | — | — / — |
| 8 | `created_at` | timestamp | YES | NULL | — | — / — |
| 9 | `updated_at` | timestamp | YES | NULL | — | — / — |

### İndeksler

| İsim | Tür | Sıralı kolonlar | Görünür |
| --- | --- | --- | --- |
| PRIMARY | PRIMARY | `id` | YES |
| tests_sections_meta_key_owner_type_owner_id_unique | UNIQUE | `key`, `owner_type`, `owner_id` | YES |

Fiziksel foreign key: yok. Eloquent ilişkileri 27 içinde değerlendirilir.

## topics

Satır: **80**. Engine: `InnoDB`. Collation: `utf8mb4_unicode_ci`. Metadata data/index baytları: 16384 / 65536.

| # | Kolon | SQL tip | NULL kabul | Default | Extra | Charset / collation |
| --- | --- | --- | --- | --- | --- | --- |
| 1 | `id` | bigint unsigned | NO | yok | auto_increment | — / — |
| 2 | `lesson_id` | bigint unsigned | NO | yok | — | — / — |
| 3 | `code` | varchar(255) | YES | NULL | — | utf8mb4 / utf8mb4_unicode_ci |
| 4 | `title` | text | NO | yok | — | utf8mb4 / utf8mb4_unicode_ci |
| 5 | `description` | text | YES | NULL | — | utf8mb4 / utf8mb4_unicode_ci |
| 6 | `sort` | bigint unsigned | NO | `1` | — | — / — |
| 7 | `hits` | bigint unsigned | NO | `0` | — | — / — |
| 8 | `status` | varchar(12) | NO | `active` | — | utf8mb4 / utf8mb4_unicode_ci |
| 9 | `created_at` | timestamp | YES | NULL | — | — / — |
| 10 | `updated_at` | timestamp | YES | NULL | — | — / — |
| 11 | `deleted_at` | timestamp | YES | NULL | — | — / — |

### İndeksler

| İsim | Tür | Sıralı kolonlar | Görünür |
| --- | --- | --- | --- |
| PRIMARY | PRIMARY | `id` | YES |
| topics_deleted_at_index | INDEX | `deleted_at` | YES |
| topics_lesson_id_index | INDEX | `lesson_id` | YES |
| topics_sort_index | INDEX | `sort` | YES |
| topics_status_index | INDEX | `status` | YES |

Fiziksel foreign key: yok. Eloquent ilişkileri 27 içinde değerlendirilir.

## translations

Satır: **2066**. Engine: `InnoDB`. Collation: `utf8mb4_unicode_ci`. Metadata data/index baytları: 180224 / 262144.

| # | Kolon | SQL tip | NULL kabul | Default | Extra | Charset / collation |
| --- | --- | --- | --- | --- | --- | --- |
| 1 | `id` | bigint unsigned | NO | yok | auto_increment | — / — |
| 2 | `locale` | varchar(5) | YES | NULL | — | utf8mb4 / utf8mb4_unicode_ci |
| 3 | `key` | varchar(255) | NO | yok | — | utf8mb4 / utf8mb4_unicode_ci |
| 4 | `value` | text | YES | NULL | — | utf8mb4 / utf8mb4_unicode_ci |

### İndeksler

| İsim | Tür | Sıralı kolonlar | Görünür |
| --- | --- | --- | --- |
| PRIMARY | PRIMARY | `id` | YES |
| locale | UNIQUE | `locale`, `key` | YES |
| translations_locale_index | INDEX | `locale` | YES |

Fiziksel foreign key: yok. Eloquent ilişkileri 27 içinde değerlendirilir.

## user_ai_usages

Satır: **1155**. Engine: `InnoDB`. Collation: `utf8mb4_unicode_ci`. Metadata data/index baytları: 16384 / 65536.

| # | Kolon | SQL tip | NULL kabul | Default | Extra | Charset / collation |
| --- | --- | --- | --- | --- | --- | --- |
| 1 | `id` | bigint unsigned | NO | yok | auto_increment | — / — |
| 2 | `user_id` | bigint unsigned | YES | NULL | — | — / — |
| 3 | `answer_ai_id` | bigint unsigned | NO | yok | — | — / — |
| 4 | `usage` | int | YES | NULL | — | — / — |
| 5 | `remaining` | int | NO | yok | — | — / — |
| 6 | `usage_date` | date | NO | yok | — | — / — |
| 7 | `created_at` | timestamp | YES | NULL | — | — / — |
| 8 | `updated_at` | timestamp | YES | NULL | — | — / — |

### İndeksler

| İsim | Tür | Sıralı kolonlar | Görünür |
| --- | --- | --- | --- |
| PRIMARY | PRIMARY | `id` | YES |
| remaining | INDEX | `remaining` | YES |
| usage | INDEX | `usage` | YES |
| user_ai_usages_answer_ai_id_index | INDEX | `answer_ai_id` | YES |
| user_ai_usages_user_id_index | INDEX | `user_id` | YES |

Fiziksel foreign key: yok. Eloquent ilişkileri 27 içinde değerlendirilir.

## users

Satır: **381**. Engine: `InnoDB`. Collation: `utf8mb4_unicode_ci`. Metadata data/index baytları: 131072 / 81920.

| # | Kolon | SQL tip | NULL kabul | Default | Extra | Charset / collation |
| --- | --- | --- | --- | --- | --- | --- |
| 1 | `id` | bigint unsigned | NO | yok | auto_increment | — / — |
| 2 | `name` | varchar(255) | NO | yok | — | utf8mb4 / utf8mb4_unicode_ci |
| 3 | `surname` | varchar(255) | NO | yok | — | utf8mb4 / utf8mb4_unicode_ci |
| 4 | `username` | varchar(255) | NO | yok | — | utf8mb4 / utf8mb4_unicode_ci |
| 5 | `email` | varchar(255) | YES | NULL | — | utf8mb4 / utf8mb4_unicode_ci |
| 6 | `phone` | varchar(255) | YES | NULL | — | utf8mb4 / utf8mb4_unicode_ci |
| 7 | `gpt_limit` | int | YES | `5` | — | — / — |
| 8 | `league_approval` | tinyint | YES | `1` | — | — / — |
| 9 | `password` | varchar(255) | YES | NULL | — | utf8mb4 / utf8mb4_unicode_ci |
| 10 | `status` | varchar(255) | NO | `active` | — | utf8mb4 / utf8mb4_unicode_ci |
| 11 | `last_login_at` | timestamp | YES | NULL | — | — / — |
| 12 | `email_verified` | varchar(255) | NO | `0` | — | utf8mb4 / utf8mb4_unicode_ci |
| 13 | `email_verified_token` | varchar(255) | YES | NULL | — | utf8mb4 / utf8mb4_unicode_ci |
| 14 | `email_verified_at` | timestamp | YES | NULL | — | — / — |
| 15 | `remember_token` | varchar(100) | YES | NULL | — | utf8mb4 / utf8mb4_unicode_ci |
| 16 | `created_at` | timestamp | YES | NULL | — | — / — |
| 17 | `updated_at` | timestamp | YES | NULL | — | — / — |
| 18 | `deleted_at` | timestamp | YES | NULL | — | — / — |

### İndeksler

| İsim | Tür | Sıralı kolonlar | Görünür |
| --- | --- | --- | --- |
| PRIMARY | PRIMARY | `id` | YES |
| gpt_limit | INDEX | `gpt_limit` | YES |
| users_deleted_at_index | INDEX | `deleted_at` | YES |
| users_email_verified_index | INDEX | `email_verified` | YES |
| users_last_login_at_index | INDEX | `last_login_at` | YES |
| users_status_index | INDEX | `status` | YES |

Fiziksel foreign key: yok. Eloquent ilişkileri 27 içinde değerlendirilir.
