# SQL performans ölçümü, düzeltme paketleri ve kararlar

**Güncel uygulama:** İlk üç nonunique indeks ana yerel DB'de onayla Ran (37/batch 7); iki sonuç filter'ı all() yerine SQL alt sorgusuna taşındı. Önce/sonra ölçüm ve son dar test kanıtı [31](31-INDEKS-UYGULAMA-VE-OLCUM-SONUCU.md). Aşağıdaki genel aday tablosu bütün indekslerin uygulandığı anlamına gelmez; tarihsel veri/unique/FK ve diğer optimizasyon kararları açık.

**Sonraki hazırlık:** Kullanıcının optimizasyona başlama isteğiyle üç normal indeks migration'ı hazırlandı; testing'de up/down ve veri koruma testi 1 test/41 doğrulamayla geçti. Ana yerel DB'ye uygulanmadı. Somut dosyalar, ilk adayların kaynak sorguyla seçimi ve uygulama onay planı [30-ILK-INDEKS-MIGRATION-HAZIRLIGI.md](30-ILK-INDEKS-MIGRATION-HAZIRLIGI.md) içindedir. Aşağıdaki uygulanmamış inceleme kaydı tarihsel başlangıçtır.

5 Ekim 2026. Salt okunur canlı SQL kopyası incelemesi sonrası plan. **İndeks/kolon/constraint/ayar değişikliği, veri temizliği veya uygulama düzeltmesi uygulanmadı.** Kullanıcının onayı seçilen somut paketten sonra alınır. Aktarım izni bütün takip eden değişiklikler için izin değildir.

## 1. Performans kanıtının kapsamı

14 temsilî query shape için EXPLAIN FORMAT=JSON okundu. Gerçek kayıtlardan hacimli kullanıcı/öğretmen seçimi yapıldı; kimlikler özel audit dosyasında kalır. Sorgular gerçek akışların sadeleştirilmiş temsilleridir: tam Livewire render/league RANK/UNION/export sorgularının tamamı değildir. EXPLAIN ANALYZE, ANALYZE TABLE, benchmark, DDL, uygulama isteği veya queue job çalıştırılmadı.

`rows_examined_per_scan` optimizer tahminidir; süre ve toplam gerçek taranan satır sayısı değildir. Filesort/temp görüldü diye tek başına sorun denmez; küçük tabloda normal olabilir. İndeks sonrası hız kazanımı henüz ölçülmedi.

| Temsilî akış | Tablo: erişim / seçilen indeks | Tarama tahmini / adım | Filesort | Temporary |
| --- | --- | --- | --- | --- |
| practice-history | exam_results: index_merge / intersect(exam_results_user_id_index,exam_results_completed_index) | exam_results: 337 | Evet | Hayır |
| practice-season-table | exam_results: ref / exam_results_completed_index | exam_results: 8217 | Evet | Hayır |
| timed-active-attempt | tests_results: ref / tests_results_test_id_index | tests_results: 1 | Hayır | Hayır |
| practice-detail-question | exam_result_details: index_merge / intersect(exam_result_details_exam_result_id_index,exam_result_details_question_id_index) | exam_result_details: 1 | Hayır | Hayır |
| practice-latest-same-exam | exam_results: index_merge / intersect(exam_results_exam_id_index,exam_results_user_id_index) | exam_results: 1 | Evet | Hayır |
| league-practice | u: ALL / yok; tr: ref / exam_results_user_id_index; trd: ref / exam_result_details_exam_result_id_index | u: 381; tr: 62; trd: 9 | Hayır | Evet |
| league-timed | tr: ref / tests_results_completed_index; u: eq_ref / PRIMARY; trd: ref / unique_tests_result_details | tr: 327; u: 1; trd: 32 | Hayır | Evet |
| teacher-students | teacher_student: ref / teacher_student_teacher_id_index | teacher_student: 20 | Hayır | Hayır |
| login-lookup | users: ref / users_deleted_at_index | users: 379 | Hayır | Hayır |
| settings-global | settings: ALL / yok | settings: 101 | Hayır | Hayır |
| practice-question-order | exams_questions: ref / exams_questions_exam_id_index | exams_questions: 7 | Evet | Hayır |
| timed-question-order | tests_questions: ref / tests_questions_section_id_index | tests_questions: 1 | Evet | Hayır |
| questions-by-topic | questions: ref / questions_topic_id_index | questions: 43 | Evet | Hayır |
| stats-one-student | r: ref / exam_results_user_id_index | r: 675 | Hayır | Evet |

Önemli ayrım: lig sorgusu tüm 149.904 detayı ALL taramıyor; kullanıcı → sonuç → detay mevcut indekslerini seçiyor. Kullanıcılarda 381 satırlık ALL ve gruplayan temporary var. `updated_at` indeksi yokluğu daha seçici dönem için adaydır; mevcut planı görmeden “lig tüm tabloyu tarar” denmemeli.

## 2. Önceliklendirilmiş indeks adayları

İndeksler paket olarak rastgele eklenmez. Her satırın SQL shape'i, canlı hacmi ve dar sorgu testi birlikte ele alınır. Birleşik indekste en soldaki kolonlar kullanılabilir; bu nedenle filtre/sıralama sırası önemlidir. [MySQL resmi çok kolonlu indeks açıklaması](https://dev.mysql.com/doc/refman/8.0/en/multiple-column-indexes.html).

| Öncelik | Tablo/önerilen kolon sırası | Gerekçe ve sınır |
| --- | --- | --- |
| Yüksek | users(username) | Login kullanıcı adıyla sorgular; indeks yok, deleted_at indeksiyle yaklaşık 379 satır inceleniyor. Nonunique B-tree ilk sade aday. Unique kararı ayrı; şu an aktif/tüm kullanıcılar arasında duplicate username veya boş username yok. |
| Yüksek | exam_results(user_id,completed,updated_at,id) | Kullanıcı tamamlanmış geçmişinde index_merge + filesort; composite dar kapsamlı test/EXPLAIN ile değerlendir. Sonuç kartı ve tüm deneme istatistiği farklı sorgular; birlikte tek seçim kuralı icat etme. |
| Orta | exam_results(season_id,completed,updated_at,id) | Temsilî sezon listesi tamamlanma indeksinden yaklaşık 8.217 satır alıp sezon filtresi/sort yapıyor. Gerçek sezon/filtre ekranı ve boş/dolu sezon ölçümü gerekir. |
| Orta | tests_results(user_id,test_id,completed,id) | Tek aktif deneme araması/yeniden giriş. Şimdiki 379 satır küçük, hız sorunu kanıtlanmadı; ileride büyüme ve aynı deneme yarış koşulları için aday. Sadece composite unique olmaz; completed denemeleri tekrarlama hakkını koru. |
| Orta | exam_result_details(exam_result_id,question_id) UNIQUE adayı | Cevap tekilliği ve index_merge yerine bağ/lookup. Mevcut duplicate grupları 0. İlk cevap kilidi/aynı soru yarış koşulu testi şart. |
| Orta | exams_questions(exam_id,question_id) UNIQUE; (exam_id,order) INDEX | Yinelenen pivot 0; liste sırası exam_id indeksinden filesort. Unique ve sort farklı ihtiyaçlar. |
| Orta | tests_questions(test_id,section_id,question_id) UNIQUE; (test_id,section_id,order) INDEX | Yinelenen üçlü 0. Aynı soru farklı bölümde bulunabilir; yalnız test_id/question_id unique ekleme. |
| Orta | teacher_student(teacher_id,user_id) UNIQUE | Çift duplicate 0; öğretmenin liste yönünü kapsar, yarış tekrarını DB'de engelleme adayı. user_id tekil indeksi başka yönde lazım olabilir. |
| Orta | exams_favorites(user_id,exam_id) UNIQUE | Çift duplicate 0. Favori ekle/çıkarın eşzamanlılık ve sahiplik regresyon testiyle. |
| Ölçüm sonrası | exam_result_details(updated_at,exam_result_id), tests_result_details(updated_at,tests_result_id) | Dönem lig filtresi için aday. Mevcut join planı sonuç ID indeksini seçiyor; tarih öne alınmasının gerçek farklı dönem seçiciliğinde avantajı kanıtlanmalı. Mevcut unique_tests_result_details korunur. |
| Ölçüm sonrası | questions(topic_id,locale,status,deleted_at,sort,id) | Mevcut topic_id ref yaklaşık 43 satır. Küçük hacimde gereksiz geniş indeks olabilir; ön yüz filtre/sort yönüyle daraltılır. |
| Düşük | settings(settingable_type,name) / iş anahtarı | 101 satır, paket bütün global ayarları bir defa yükler. Temsilî name lookup ALL; doğrudan hot login bottleneck'i değildir. Unique(name,type,id) NULL global scope'ta tekrarları tek başına engellemez; global/model ayar sözleşmesi belirlenmeli. |

Yeni indekslerin yazma/bellek/depolama maliyeti vardır. İlk 3 canlı-only gpt_limit/usage/remaining indeksinin gerçek ekran sorgu tüketimi kanıtlanmadı; yalnız “var” diye fresh'e taşınmaz veya yalnız düşük kardinalite diye silinmez. Ya keep+append migration, ya ölçümle drop; kullanıcı kararı sonrası aynı şemaya getirilir. Translation unique ad farkı kozmetik; zorunlu rename yok.

## 3. İndeksten önce/beraber değerlendirilecek kod maliyeti

- FilterHelper::examsInResults `ExamResult::all()->select('exam_id')` önce **16.231 tam model** yüklüyor; select Collection üzerinde. SQL `select/distinct` subquery/whereIn ile yalnız gereken ID'leri DB'de seçmek dar optimizasyon adayı.
- testsInResults aynı biçimde 379 sonuç yüklüyor; hacim küçük olsa da aynı sade sorgu düzeltmesi düşünülebilir. Öğretmen kapsamı/dil/cache anahtarı korunur.
- ResultStatsCustomTable filtreleme sırasında e-posta listesi için tüm sonuçları get edip tekrar paginate/count/export çalıştırabilir. Exact query count ve peak memory ölçülerek büyük get çağrısı yalnız ihtiyacına göre daraltılır; mail alıcı iş kuralı değiştirilmez.
- Konu istatistiklerinin union/group dış filtreleri iki dalın tamamını topladıktan sonra azaltabilir. Filtreyi dallara taşımak SUM/başarı yüzdesi/öğretmen kapsamını koruyan fixture ile test edilir; bu tur tam UNION planı ölçülmedi.
- Loggable create/update/delete olayları resetCache çağırıyor. resetCache flush+clear+Artisan optimize:clear çalıştırır. Bu kaynakta doğrulanan geniş temizleme, cevap kaydının maliyetini etkileyebilir; sorgu indeksi bunu çözmez. Değişiklik scope/cache invalidation sözleşmesiyle ayrı paket olmalı.
- Cache TTL ve global flush daha önce 21'de adaydı; test fake optimize:clear gerçek maliyeti kanıtlamaz. Bu tur cache temizlenmedi.
- N+1, export memory, route TTFB/LCP için bu tur gerçek render profili alınmadı; kesin yavaşlık oranı verilmez. CSV/Excel tüm hacmi için snapshot+chunk planı sonraki seçilen optimizasyon olabilir.

## 4. Katı SQL modu ve veri tipleri

MySQL istemci/global modları ONLY_FULL_GROUP_BY ve STRICT_TRANS_TABLES içeriyor. `config/database.php` mysql **strict=false**; Laravel MySqlConnector bu bağlantının session mode'unu NO_ENGINE_SUBSTITUTION yapar. Dolayısıyla server global modunun uygulama session moduyla aynı olduğu varsayılmaz. Bu tur app bağlantısı başlatılmadı; kaynak davranışı okunarak ayrıldı.

Exam ve Test userResults ilişkilerinin SQL biçimi `SELECT * ... GROUP BY exam_id/test_id`. Katı istemci EXPLAIN iki sorguda da **1055** verdi. Uygulama strict=false olduğundan bugünkü ekranın 500 verdiği kanıtı yok; mevcut deterministik olmayan sonuç seçimi ve strict geçiş engeli doğrulandı. Kullanım: SolvedTests/SolvedExams/FavoriteTests/ListTests eager load. Kardaki toplam mı/son deneme mi gösterileceği netleşmeden SELECT ANY_VALUE ile hatayı örtme. Backend toplam istatistik ve lig kararı zaten tüm uygun denemelerdir; bu kart seçimi ayrı.

strict=true'ya doğrudan geçilmez. Önce bütün grouped select/write paths dar feature testleriyle uyarlanır; ardından ayrı onayla strict hedefi sınanır. Puan tinyint gelecekte signed int'e büyütülebilir; bugünkü aktif sınavlarda kapasite aşımı 0. Geri down ile büyük puanları tinyint'e daraltmak kayıp yaratabilir; verili rollback koruması gerekir.

meta/tests_sections_meta toplam 8 metin kolonu eski utf8mb3 charset'te. 4 bayt Unicode desteği ve gelecekte sürüm uyumu için utf8mb4 conversion adayı. Kaynak vendor migration/config de hesaba katılır; yalnız canlı ALTER ardından fresh referansı eski kalmamalı. [MySQL resmi utf8mb3 açıklaması](https://dev.mysql.com/doc/refman/8.0/en/charset-unicode-utf8mb3.html). Bu bir bugünkü geçersiz JSON bulgusu değildir.

## 5. Onaya sunulacak dar düzeltme paketleri

### A — Kod ilişki/alan tutarlılığı

TestsResultDetail yanlış FK, Faq/Language stale fillable tanımı için küçük kod düzeltmesi + 1 ilişki/ilgili form testi. AnswerAI answer relation kaldırılacak mı/yeni amaç mı önce karar. Parent adı/SoftDeletes/stat kartı seçimi bitişik otomatik refactor'a alınmaz.

### B — Eski sonuç/veri politikası

52 cevapsız expired sınav için seçenekler: geçmişi olduğu gibi tut; veya kullanıcının onayıyla boş/tamamlanmış tarihsel sonuçlara dönüştür. Eski sınav soru seti snapshot'ı yoksa bugünkü soruları kullanarak CompleteTestResult çalıştırmak tarihsel veri üretebilir; toplu job dispatch veya yeniden puanlama yapılmaz. 2 deadline/2 duration kaydı ayrı audit edilir; geçmiş puanlar korunur.

11+1 soru sayacı farkında doğru/yanlış/point eşleşiyor. question_count başlangıçtaki soru setinin mi yoksa kayıtlı detay sayısının mı snapshot'ı olmalı? Önce karar; yalnız question_count düzeltilecekse hedef ID listesi özel çalışma dosyasında/işlem öncesi backup'la, old/new değer ve etkilenen satır kontrolüyle transactional dar işlem hazırlanır. Güncel soru havuzu üzerinden tekrar not verilmez.

149 sorunlu timed detail / 12 practice detail / 13 practice pivot / 21 section meta / 1 AI vote / 38 AI usage saklama politikasına göre ele alınır. İlgili rapor/lig geçmişini etkileyen silme ayrı açık onay ister. AI kota kullanım satırını cevabı yok diye silme varsayımı yapılmaz. question/lesson/topic 0 placeholder eski EMPTY mantığıyla ayrıca sınıflanır.

### C — Şema standardı ve koruma

Önce extra indeks kararı. Yeni append migration ile iki başlangıç (canlı kopya vs testing referansı) hedef şemaya getirilir; isim/kolon/unique imzası kontrolü. Pivota unique eklemek mevcut duplicate 0 ile mümkün görünüyor; yarış koşulu testi olmadan yeterli kabul edilmez. Domain FK'leri yalnız orphan politikası ve soft-delete/geçmiş/0 sentinel çözüldükten sonra; otomatik CASCADE geçmişi silebileceğinden tüm belongsTo alanlarına toplu CASCADE eklenmez.

Mevcut schema down sınırlamaları 27. Tarihsel ran dosyaları/batch'ler korunur. Kullanıcının kod standardına uygun kısa yeni migration'lar; tek büyük tüm projeyi dönüştüren migration yok.

### D — İzin ve performans

Yeni users:send-email iznini dar şekilde ekleme/rol ataması kullanıcı tarafından seçilir; bütün seeder çalıştırılmaz. Sonra username indeksi + all() subquery düzeltmesi + seçilen liste composite ilk ölçüm paketi olabilir. Ayrı testing'de ilgili testler; read-only EXPLAIN tekrar yalnız değişen sorgular. Cache/league/union ayrı ölçüm paketi.

## 6. Hâlâ gereken kararlar

1. 52 eski cevapsız/süresi geçmiş sınav denemesinin geçmişte kalması veya boş tamamlanması.
2. Eski question_count snapshot'ının anlamı ve 11+1 sayaç uyumlaması.
3. Tarihsel orphan/meta/AI kullanım satırları arşivde korunacak mı; hangi artıklar silinebilir?
4. Ön yüz test/sınav/favori kartında grouped userResults yerine toplam mı, son tamamlanmış mı, son deneme mi? Backend toplam ve lig kuralı değişmez.
5. users:send-email hangi mevcut rollere verilecek? Yeni izin oluşturmak her role vermek değildir.
6. Username unique kapsamı bütün kayıtlar mı, aktif/soft-delete politikası mı? Nonunique hız indeksi için bu karar gerekmez; unique için gerekir.
7. Extra 3 indeks keep/drop stratejisi; ölçüm olmadan otomatik karar yok.

SQL importu ve şema keşfi tamamlandı; **düzeltme kabulü ve Laravel 13 runtime uyumu tamamlandı anlamına gelmez**. Framework/paket geçişine kullanıcı kararıyla 14/24 üzerinden devam edilir; bu tur Composer'a dokunulmadı.

## 7. Kontrol kapsamı ve tekrar üretim

Özel kanıt klasörü: storage/app/private/codex-db/20261005-import. import-summary, row-verification, schema-audit, data-audit, explain-audit, audit-extra-metadata ve testing önce/sonra checksum dosyaları var; tümü ignore altında. EXPLAIN dosyası gerçek ID içerdiğinden public/rehber/Git'e taşınmaz. Kaynak dump ve yedek erişim korunur.

Kontroller SELECT/COUNT/INFORMATION_SCHEMA/EXPLAIN ve testing CHECKSUM okumasıdır. DB correction DML/DDL yalnız ilk onaylı importta çalıştı; sonrasındaki incelemede yok. PHP artisan/model boot yapılmadı; Reflection yalnız default model özelliklerini okudu. Test runner/build/app UI tetiklenmedi. Eski tüm suite kanıtı 22, QA fixture kapsamı 23; SQL check sayımı uygulama test sayısı değildir.

Aşağıda **231** sayım/tutarlılık kontrolünün hepsi vardır; **190** sonucu 0. orphan/invalid/duplicate/mismatch kontrolllerinde 0 beklenen olumlu sonuçtur. completed/uncompleted/softdeleted/unanswered/active_seasons ise dağılımdır; nonzero başarısız test sayılmaz. deadline/duration/tinyint/current config karşılaştırmaları tarihsel karar gerektirir. İlk jenerik enum kontrolü YesNo EMPTY=-1 ve numeric meta status nedeniyle düzeltildi; yanlış pozitifler nihai tabloya dahil edilmedi.

| Kontrol anahtarı | Sonuç |
| --- | --- |
| active_seasons | 1 |
| count_exceeds_questions:exam_results | 4 |
| count_exceeds_questions:tests_results | 0 |
| duplicate:meta | 0 |
| duplicate:settings | 0 |
| duplicate:tests_sections_meta | 0 |
| duplicate:translations | 0 |
| expired_empty_attempts | 52 |
| failed_jobs_connection | 6 |
| failed_jobs_mail_transport | 0 |
| failed_jobs_query_exception | 0 |
| invalid_boolean:answer_a_i_s.report | 0 |
| invalid_boolean:answers.correct | 0 |
| invalid_boolean:contacts.has_read | 0 |
| invalid_boolean:exam_result_details.correct | 0 |
| invalid_boolean:exam_results.completed | 0 |
| invalid_boolean:exam_reviews.has_read | 0 |
| invalid_boolean:tests_results.completed | 0 |
| invalid_json:answer_a_i_s.content | 0 |
| invalid_json:lessons.description | 0 |
| invalid_json:lessons.name | 0 |
| invalid_json:pages.brief | 0 |
| invalid_json:pages.content | 0 |
| invalid_json:pages.images | 0 |
| invalid_json:pages.keywords | 0 |
| invalid_json:pages.link | 0 |
| invalid_json:pages.title | 0 |
| invalid_json:topics.description | 0 |
| invalid_json:topics.title | 0 |
| invalid_meta_status | 0 |
| invalid_section_meta_status | 0 |
| invalid_status:blogs | 0 |
| invalid_status:exams | 0 |
| invalid_status:faqs | 0 |
| invalid_status:languages | 0 |
| invalid_status:lessons | 0 |
| invalid_status:pages | 0 |
| invalid_status:questions | 0 |
| invalid_status:seasons | 0 |
| invalid_status:tests | 0 |
| invalid_status:topics | 0 |
| invalid_status:users | 0 |
| invalid_timed_correct_enum | 0 |
| invalid_user_verified_enum | 0 |
| language_duplicate_codes | 0 |
| mail_permission_missing | 1 |
| negative_counts:exam_results | 0 |
| negative_counts:tests_results | 0 |
| orphan:answer_a_i_s.lesson_id->lessons | 0 |
| orphan:answer_a_i_s.question_id->questions | 0 |
| orphan:answer_a_i_s.topic_id->topics | 0 |
| orphan:answer_a_i_s.user_id->users | 0 |
| orphan:answer_a_i_votes.answer_ai_id->answer_a_i_s | 1 |
| orphan:answer_a_i_votes.user_id->users | 0 |
| orphan:answers.question_id->questions | 0 |
| orphan:exam_result_details.answer_id->answers | 0 |
| orphan:exam_result_details.exam_result_id->exam_results | 12 |
| orphan:exam_result_details.lesson_id->lessons | 0 |
| orphan:exam_result_details.question_id->questions | 0 |
| orphan:exam_result_details.topic_id->topics | 0 |
| orphan:exam_results.exam_id->exams | 0 |
| orphan:exam_results.season_id->seasons | 0 |
| orphan:exam_results.user_id->users | 0 |
| orphan:exam_reviews.exam_id->exams | 0 |
| orphan:exam_reviews.user_id->users | 0 |
| orphan:exams.created_by->users | 0 |
| orphan:exams_favorites.exam_id->exams | 0 |
| orphan:exams_favorites.user_id->users | 0 |
| orphan:exams_questions.exam_id->exams | 13 |
| orphan:exams_questions.lesson_id->lessons | 0 |
| orphan:exams_questions.question_id->questions | 0 |
| orphan:exams_questions.topic_id->topics | 0 |
| orphan:league_results.league_id->leagues | 0 |
| orphan:league_results.user_id->users | 0 |
| orphan:logs.user_id->users | 0 |
| orphan:model_has_permissions.permission_id->permissions | 0 |
| orphan:model_has_roles.role_id->roles | 0 |
| orphan:questions.lesson_id->lessons | 0 |
| orphan:questions.topic_id->topics | 0 |
| orphan:role_has_permissions.permission_id->permissions | 0 |
| orphan:role_has_permissions.role_id->roles | 0 |
| orphan:sessions.user_id->users | 0 |
| orphan:teacher_student.teacher_id->users | 0 |
| orphan:teacher_student.user_id->users | 0 |
| orphan:tests.created_by->users | 0 |
| orphan:tests_questions.lesson_id->lessons | 0 |
| orphan:tests_questions.question_id->questions | 0 |
| orphan:tests_questions.section_id->tests_sections | 0 |
| orphan:tests_questions.test_id->tests | 0 |
| orphan:tests_questions.topic_id->topics | 0 |
| orphan:tests_result_details.answer_id->answers | 0 |
| orphan:tests_result_details.lesson_id->lessons | 10 |
| orphan:tests_result_details.question_id->questions | 10 |
| orphan:tests_result_details.section_id->tests_sections | 99 |
| orphan:tests_result_details.tests_result_id->tests_results | 40 |
| orphan:tests_result_details.topic_id->topics | 10 |
| orphan:tests_results.season_id->seasons | 0 |
| orphan:tests_results.test_id->tests | 0 |
| orphan:tests_results.user_id->users | 0 |
| orphan:tests_sections.test_id->tests | 0 |
| orphan:topics.lesson_id->lessons | 0 |
| orphan:user_ai_usages.answer_ai_id->answer_a_i_s | 38 |
| orphan:user_ai_usages.user_id->users | 0 |
| orphan_timed_details_unique | 149 |
| orphan_timed_lesson_zero | 10 |
| orphan_timed_question_zero | 10 |
| orphan_timed_section_with_existing_result | 99 |
| orphan_timed_topic_zero | 10 |
| permission_model_user_orphans | 0 |
| practice_completed | 14573 |
| practice_completed_counter_mismatch | 11 |
| practice_detail_answer_other_question | 0 |
| practice_detail_topic_mismatch | 0 |
| practice_duplicate_answers | 0 |
| practice_duplicate_favorites | 0 |
| practice_duplicate_questions | 0 |
| practice_pivot_topic_mismatch | 0 |
| practice_result_answers_without_rows | 0 |
| practice_summary_correct_mismatch | 0 |
| practice_summary_incorrect_mismatch | 0 |
| practice_summary_question_mismatch | 11 |
| practice_time_negative | 0 |
| practice_uncompleted | 1658 |
| practice_uncompleted_with_answers | 1658 |
| question_topic_other_lesson | 0 |
| questions_multiple_correct_answers | 0 |
| questions_without_active_correct_answer | 0 |
| role_model_user_orphans | 0 |
| seasons_bad_dates | 0 |
| seasons_overlapping | 0 |
| section_meta_orphans | 21 |
| section_missing_parent | 0 |
| section_parent_other_test | 0 |
| section_self_parent | 0 |
| setting_duplicate_scopes | 0 |
| softdeleted:answers | 0 |
| softdeleted:exam_results | 0 |
| softdeleted:exams | 11 |
| softdeleted:lessons | 0 |
| softdeleted:newsletters | 0 |
| softdeleted:pages | 0 |
| softdeleted:questions | 31 |
| softdeleted:tests | 1 |
| softdeleted:tests_results | 0 |
| softdeleted:topics | 5 |
| softdeleted:users | 2 |
| softdeleted_reference_completed_practice_user | 0 |
| softdeleted_reference_completed_timed_user | 0 |
| softdeleted_reference_practice_questions | 24 |
| softdeleted_reference_timed_questions | 0 |
| teacher_duplicate_pairs | 0 |
| teacher_self_link | 0 |
| tests_bad_duration | 0 |
| tests_bad_passing_score | 0 |
| timed_completed | 327 |
| timed_completed_before_created | 0 |
| timed_completed_counter_mismatch | 1 |
| timed_completed_missing_completed_at | 0 |
| timed_config_max_score_outside_tinyint | 0 |
| timed_deadline_before_created | 2 |
| timed_detail_answer_other_question | 0 |
| timed_detail_config_outside_tinyint | 0 |
| timed_detail_section_other_test | 0 |
| timed_detail_topic_mismatch | 0 |
| timed_duplicate_answers | 0 |
| timed_duplicate_questions | 0 |
| timed_duration_exceeds_current_config | 28 |
| timed_duration_negative | 0 |
| timed_duration_vs_timestamps_mismatch | 2 |
| timed_multiple_active_attempts | 0 |
| timed_pivot_section_other_test | 0 |
| timed_pivot_topic_mismatch | 0 |
| timed_result_answers_without_rows | 52 |
| timed_score_at_tinyint_limit | 0 |
| timed_score_sum_outside_tinyint | 0 |
| timed_summary_correct_mismatch | 0 |
| timed_summary_incorrect_mismatch | 0 |
| timed_summary_point_mismatch | 0 |
| timed_summary_question_mismatch | 1 |
| timed_unanswered_details | 2768 |
| timed_uncompleted | 52 |
| timed_uncompleted_expired | 52 |
| timed_uncompleted_missing_deadline | 0 |
| unknown_locale:answer_a_i_s | 0 |
| unknown_locale:blogs | 0 |
| unknown_locale:contacts | 0 |
| unknown_locale:exams | 0 |
| unknown_locale:questions | 0 |
| unknown_locale:tests | 0 |
| unknown_locale:translations | 0 |
| user_meta_orphans | 0 |
| username_duplicate_groups_active | 0 |
| username_duplicate_groups_all | 0 |
| username_empty | 0 |
| zero_dates:answer_a_i_s | 0 |
| zero_dates:answer_a_i_votes | 0 |
| zero_dates:answers | 0 |
| zero_dates:blogs | 0 |
| zero_dates:contacts | 0 |
| zero_dates:exam_result_details | 0 |
| zero_dates:exam_results | 0 |
| zero_dates:exam_reviews | 0 |
| zero_dates:exams | 0 |
| zero_dates:failed_jobs | 0 |
| zero_dates:faqs | 0 |
| zero_dates:languages | 0 |
| zero_dates:latex_images | 0 |
| zero_dates:league_results | 0 |
| zero_dates:leagues | 0 |
| zero_dates:lessons | 0 |
| zero_dates:logs | 0 |
| zero_dates:meta | 0 |
| zero_dates:newsletters | 0 |
| zero_dates:newsletters_mails | 0 |
| zero_dates:pages | 0 |
| zero_dates:password_reset_tokens | 0 |
| zero_dates:permissions | 0 |
| zero_dates:questions | 0 |
| zero_dates:roles | 0 |
| zero_dates:seasons | 0 |
| zero_dates:settings | 0 |
| zero_dates:teacher_student | 0 |
| zero_dates:telescope_entries | 0 |
| zero_dates:tests | 0 |
| zero_dates:tests_result_details | 0 |
| zero_dates:tests_results | 0 |
| zero_dates:tests_sections | 0 |
| zero_dates:tests_sections_meta | 0 |
| zero_dates:topics | 0 |
| zero_dates:user_ai_usages | 0 |
| zero_dates:users | 0 |
