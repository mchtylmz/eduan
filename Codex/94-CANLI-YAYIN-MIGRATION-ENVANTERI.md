# Migration kontrolü ve tekil uygulama planı — v2

Canlı DB bu tur okunmadı. Aşağıdaki 14 dosya **aday** listesidir, hepsinin Pending olduğu iddia edilmez. Komutlar yalnız doğru canlı DB, tam yedek ve onaylı Pending listesiyle uygulanır. Genel seed/fresh/refresh/reset yok.

## 1. Envanteri al

Eski koddayken migrate:status çıktısını sakla. Paket kurulduktan ve package:discover tamamlandıktan sonra tekrar:

```sh
/usr/bin/php85 -d extension=imagick.so artisan migrate:status --no-ansi
/usr/bin/php85 -d extension=imagick.so artisan migrate:status --pending --no-ansi
```

Ran = bu isim uygulanmış. Pending = kayıt yok; mevcut tablo/kolon/index ayrıca incelenir. Migration table not found veya eski create migration Pending ise yanlış DB / eski kayıt farkı çözülmeden devam edilmez. phpMyAdmin salt okunur DB-INVENTORY.sql dosyası karşılaştırmaya yardımcıdır; canlı migration tablosu elle işaretlenmez.

## 2. Yeni adaylar: tarih sırasıyla, birer birer

Her blok için önce Pending durumunu doğrula. Etkisi onaylanmışsa ilk komutu çalıştır; ikinci komutta Ran görmeden sonraki dosyaya geçme. Hata olursa dur; MySQL DDL kısmen kalmış olabilir. Bu blokları toplu shell script olarak yürütme.

### 1. 2026_10_05_150000_add_initial_query_performance_indexes

- Etki: users(username), exam_results(season_id,completed,updated_at), exam_result_details(exam_result_id,question_id) indeksleri.
- Kontrol: Üç index zaten aynı adla veya eşdeğer kolonlarla varsa önce karşılaştır; otomatik drop veya migration satırı ekleme yapma.

```sh
/usr/bin/php85 -d extension=imagick.so artisan migrate --path=database/migrations/2026_10_05_150000_add_initial_query_performance_indexes.php --force
/usr/bin/php85 -d extension=imagick.so artisan migrate:status --path=database/migrations/2026_10_05_150000_add_initial_query_performance_indexes.php --no-ansi
```

### 2. 2026_10_07_110000_create_translation_sources_table

- Etki: translation_sources tablosu.
- Kontrol: Mevcut çeviri değerlerini tarayıp değiştirmez; tablo zaten varsa Pending farkını incele.

```sh
/usr/bin/php85 -d extension=imagick.so artisan migrate --path=database/migrations/2026_10_07_110000_create_translation_sources_table.php --force
/usr/bin/php85 -d extension=imagick.so artisan migrate:status --path=database/migrations/2026_10_07_110000_create_translation_sources_table.php --no-ansi
```

### 3. 2026_10_07_160000_grant_teacher_result_view_permissions

- Etki: Mevcut web öğretmen-tip rollerine exams:view ve tests:view; mevcut izinler korunur.
- Kontrol: Öğretmen rolü yoksa işlem veri eklemeden bitebilir. Ran sonrası ilgili rol izinlerini ayrıca kontrol et.

```sh
/usr/bin/php85 -d extension=imagick.so artisan migrate --path=database/migrations/2026_10_07_160000_grant_teacher_result_view_permissions.php --force
/usr/bin/php85 -d extension=imagick.so artisan migrate:status --path=database/migrations/2026_10_07_160000_grant_teacher_result_view_permissions.php --no-ansi
```

### 4. 2026_10_07_160100_fill_missing_dutch_translations

- Etki: Mevcut nl dili için eksik/boş JSON ve PHP kaynak çevirilerini DBde tamamlar; dolu değerleri korur.
- Kontrol: NL dil kaydı yoksa no-op olabilir. Tüm güncel dil kaynakları pakette bulunmalı; tekrar çalıştırmak için Ran kaydı silinmez.

```sh
/usr/bin/php85 -d extension=imagick.so artisan migrate --path=database/migrations/2026_10_07_160100_fill_missing_dutch_translations.php --force
/usr/bin/php85 -d extension=imagick.so artisan migrate:status --path=database/migrations/2026_10_07_160100_fill_missing_dutch_translations.php --no-ansi
```

### 5. 2026_10_14_100000_create_study_tracking_tables

- Etki: study_weeks, study_weekly_targets, study_activity_entries, study_target_revisions, study_activity_revisions.
- Kontrol: users tablosu gerekir; hedef/çalışma/audit tabloları oluşturulur, yerel örnek veri aktarılmaz.

```sh
/usr/bin/php85 -d extension=imagick.so artisan migrate --path=database/migrations/2026_10_14_100000_create_study_tracking_tables.php --force
/usr/bin/php85 -d extension=imagick.so artisan migrate:status --path=database/migrations/2026_10_14_100000_create_study_tracking_tables.php --no-ansi
```

### 6. 2026_10_14_100100_grant_study_tracking_permissions

- Etki: study:access bütün mevcut web rollerine; study:view/update mevcut admin+öğretmen-tip rollere.
- Kontrol: Rol tipi mevcut izinlerden belirlenir; gerçek rol matrisi ayrıca kontrol edilir.

```sh
/usr/bin/php85 -d extension=imagick.so artisan migrate --path=database/migrations/2026_10_14_100100_grant_study_tracking_permissions.php --force
/usr/bin/php85 -d extension=imagick.so artisan migrate:status --path=database/migrations/2026_10_14_100100_grant_study_tracking_permissions.php --no-ansi
```

### 7. 2026_10_14_110000_add_submission_key_to_study_activity_entries

- Etki: study_activity_entries nullable submission_key ve user_id+submission_key unique indeksi.
- Kontrol: 100000 çalışma tablolarından sonra; eski kayıtlar için nullable alan.

```sh
/usr/bin/php85 -d extension=imagick.so artisan migrate --path=database/migrations/2026_10_14_110000_add_submission_key_to_study_activity_entries.php --force
/usr/bin/php85 -d extension=imagick.so artisan migrate:status --path=database/migrations/2026_10_14_110000_add_submission_key_to_study_activity_entries.php --no-ansi
```

### 8. 2026_10_14_120000_add_study_answer_time_to_result_details

- Etki: exam_result_details ve tests_result_details nullable study_answered_at.
- Kontrol: İki sonuç detay tablosu gerekir. Modül kapalı bile olsa yeni çözüm kodu bu alanları kullanır; yayın için gereklidir.

```sh
/usr/bin/php85 -d extension=imagick.so artisan migrate --path=database/migrations/2026_10_14_120000_add_study_answer_time_to_result_details.php --force
/usr/bin/php85 -d extension=imagick.so artisan migrate:status --path=database/migrations/2026_10_14_120000_add_study_answer_time_to_result_details.php --no-ansi
```

### 9. 2026_10_14_130000_create_study_notes_tables

- Etki: study_notes ve study_note_revisions; gizlilik, UUID ve audit alanları.
- Kontrol: users tablosu gerekir; öğrenci görünürlüğü varsayılan kapalıdır.

```sh
/usr/bin/php85 -d extension=imagick.so artisan migrate --path=database/migrations/2026_10_14_130000_create_study_notes_tables.php --force
/usr/bin/php85 -d extension=imagick.so artisan migrate:status --path=database/migrations/2026_10_14_130000_create_study_notes_tables.php --no-ansi
```

### 10. 2026_10_14_140000_create_web_communications

- Etki: web_push_subscriptions, communication_batches, communication_deliveries.
- Kontrol: users tablosu gerekir; gerçek bildirim/SMTP göndermez.

```sh
/usr/bin/php85 -d extension=imagick.so artisan migrate --path=database/migrations/2026_10_14_140000_create_web_communications.php --force
/usr/bin/php85 -d extension=imagick.so artisan migrate:status --path=database/migrations/2026_10_14_140000_create_web_communications.php --no-ansi
```

### 11. 2026_10_14_140100_prepare_web_communications

- Etki: communications:view/send, eksik bildirim varsayılanları ve notification-permission sayfası.
- Kontrol: 140000 sonrası; mevcut ayar/sayfayı ezmez. Eksik notificationsEnabled varsayılanı0, gerçek üretim App ID/origin/API key ayrıca panelde kontrol edilir.

```sh
/usr/bin/php85 -d extension=imagick.so artisan migrate --path=database/migrations/2026_10_14_140100_prepare_web_communications.php --force
/usr/bin/php85 -d extension=imagick.so artisan migrate:status --path=database/migrations/2026_10_14_140100_prepare_web_communications.php --no-ansi
```

### 12. 2026_10_25_000001_add_live_calculation_fields_to_leagues

- Etki: leagues üzerinde is_live, calculation_settings, calculated_through_at, closed_at ve leagues_live_open_index.
- Kontrol: Eski ligler canlıya çevrilmez; gerçek lig hesaplaması başlatılmaz.

```sh
/usr/bin/php85 -d extension=imagick.so artisan migrate --path=database/migrations/2026_10_25_000001_add_live_calculation_fields_to_leagues.php --force
/usr/bin/php85 -d extension=imagick.so artisan migrate:status --path=database/migrations/2026_10_25_000001_add_live_calculation_fields_to_leagues.php --no-ansi
```

### 13. 2026_10_26_130000_grant_study_communication_permission

- Etki: study:communicate izni ve mevcut web admin-tip rollerine atama.
- Kontrol: Öğretmenlere otomatik verilmez; rol dağılımını doğrula.

```sh
/usr/bin/php85 -d extension=imagick.so artisan migrate --path=database/migrations/2026_10_26_130000_grant_study_communication_permission.php --force
/usr/bin/php85 -d extension=imagick.so artisan migrate:status --path=database/migrations/2026_10_26_130000_grant_study_communication_permission.php --no-ansi
```

### 14. 2026_10_30_100000_grant_user_impersonation_permission

- Etki: users:impersonate izni; yalnız web Yönetici rolüne atama.
- Kontrol: Yönetici rol adı yoksa izin oluşsa da atama olmaz. Gent/öğretmen ataması yapılmaz; rol adı farklıysa ayrı hedef kararı gerekir.

```sh
/usr/bin/php85 -d extension=imagick.so artisan migrate --path=database/migrations/2026_10_30_100000_grant_user_impersonation_permission.php --force
/usr/bin/php85 -d extension=imagick.so artisan migrate:status --path=database/migrations/2026_10_30_100000_grant_user_impersonation_permission.php --no-ansi
```

## 3. Paket migration'ları ve eski migration'lar

Composer kurulumundan gelen iki kaynak normal migrate:status içinde görünür:

| Kaynak | Beklenen mevcut kapsam |
|---|---|
| vendor/zoha/laravel-meta/src/Meta/Database/Migrations/2018_04_02_110607_create_meta_table.php | meta ve config/meta.php özel tests_sections_meta |
| vendor/pharaonic/laravel-settings/src/database/migrations/2021_02_01_000032_create_settings_table.php | settings |

Mevcut sitede bu tablolar varken Pending görünürse tekrar create çalıştırma. settings/meta olmadan uygulama boot da hata verebilir; yanlış bağlantı/schema eşlemesi incelenir. Yeni migration diye vendor:publish --force veya toplu migrate çağırma.

## 4. Uygulama kaynak envanteri (48 dosya)

Bu tablo kaynak dosyaların eksiksiz listesidir; canlı Ran/Pending sonucu DEĞİLDİR. Önceki 34 dosyanın tamamı eski kurulum envanteridir; Pending görülürse tekil inceleme gerekir.

| Sıra | Dosya | Sınıf |
|---:|---|---|
| 1 | 0001_01_01_000000_create_users_table.php | Eski kurulum — otomatik yeniden uygulama yok |
| 2 | 0001_01_01_000002_create_jobs_table.php | Eski kurulum — otomatik yeniden uygulama yok |
| 3 | 2024_09_28_112921_create_permission_tables.php | Eski kurulum — otomatik yeniden uygulama yok |
| 4 | 2024_11_15_114611_create_languages_table.php | Eski kurulum — otomatik yeniden uygulama yok |
| 5 | 2024_11_15_115629_create_lessons_table.php | Eski kurulum — otomatik yeniden uygulama yok |
| 6 | 2024_11_15_115638_create_topics_table.php | Eski kurulum — otomatik yeniden uygulama yok |
| 7 | 2024_11_15_115648_create_questions_table.php | Eski kurulum — otomatik yeniden uygulama yok |
| 8 | 2024_11_15_121331_create_answers_table.php | Eski kurulum — otomatik yeniden uygulama yok |
| 9 | 2024_11_15_121923_create_exams_table.php | Eski kurulum — otomatik yeniden uygulama yok |
| 10 | 2024_11_15_130148_create_pages_table.php | Eski kurulum — otomatik yeniden uygulama yok |
| 11 | 2024_11_18_134521_create_contacts_table.php | Eski kurulum — otomatik yeniden uygulama yok |
| 12 | 2024_11_18_165653_create_newsletters_table.php | Eski kurulum — otomatik yeniden uygulama yok |
| 13 | 2024_11_18_183823_create_faqs_table.php | Eski kurulum — otomatik yeniden uygulama yok |
| 14 | 2024_11_19_100215_create_translations_table.php | Eski kurulum — otomatik yeniden uygulama yok |
| 15 | 2024_11_20_145359_create_blogs_table.php | Eski kurulum — otomatik yeniden uygulama yok |
| 16 | 2024_11_21_205131_create_exam_reviews_table.php | Eski kurulum — otomatik yeniden uygulama yok |
| 17 | 2024_11_23_215259_create_exam_results_table.php | Eski kurulum — otomatik yeniden uygulama yok |
| 18 | 2024_11_23_215309_create_exam_result_details_table.php | Eski kurulum — otomatik yeniden uygulama yok |
| 19 | 2024_11_28_114902_create_logs_table.php | Eski kurulum — otomatik yeniden uygulama yok |
| 20 | 2025_01_29_112745_create_telescope_entries_table.php | Eski kurulum — otomatik yeniden uygulama yok |
| 21 | 2025_04_16_121101_create_tests_table.php | Eski kurulum — otomatik yeniden uygulama yok |
| 22 | 2025_05_03_123743_create_tests_results_table.php | Eski kurulum — otomatik yeniden uygulama yok |
| 23 | 2025_05_03_123759_create_tests_result_details_table.php | Eski kurulum — otomatik yeniden uygulama yok |
| 24 | 2025_08_04_103321_create_answer_a_i_s_table.php | Eski kurulum — otomatik yeniden uygulama yok |
| 25 | 2025_08_04_111128_create_user_ai_usages_table.php | Eski kurulum — otomatik yeniden uygulama yok |
| 26 | 2025_08_18_101525_create_answer_a_i_votes_table.php | Eski kurulum — otomatik yeniden uygulama yok |
| 27 | 2025_08_18_102020_table_new_column.php | Eski kurulum — otomatik yeniden uygulama yok |
| 28 | 2025_08_21_133841_create_latex_images_table.php | Eski kurulum — otomatik yeniden uygulama yok |
| 29 | 2026_01_13_113343_create_teacher_student_table.php | Eski kurulum — otomatik yeniden uygulama yok |
| 30 | 2026_01_13_165444_create_leagues_table.php | Eski kurulum — otomatik yeniden uygulama yok |
| 31 | 2026_01_14_003409_create_league_results_table.php | Eski kurulum — otomatik yeniden uygulama yok |
| 32 | 2026_01_20_142825_table_new_column2.php | Eski kurulum — otomatik yeniden uygulama yok |
| 33 | 2026_02_02_220814_create_seasons_table.php | Eski kurulum — otomatik yeniden uygulama yok |
| 34 | 2026_02_04_101550_table_new_column3.php | Eski kurulum — otomatik yeniden uygulama yok |
| 35 | 2026_10_05_150000_add_initial_query_performance_indexes.php | Yeni aday — bölüm2 |
| 36 | 2026_10_07_110000_create_translation_sources_table.php | Yeni aday — bölüm2 |
| 37 | 2026_10_07_160000_grant_teacher_result_view_permissions.php | Yeni aday — bölüm2 |
| 38 | 2026_10_07_160100_fill_missing_dutch_translations.php | Yeni aday — bölüm2 |
| 39 | 2026_10_14_100000_create_study_tracking_tables.php | Yeni aday — bölüm2 |
| 40 | 2026_10_14_100100_grant_study_tracking_permissions.php | Yeni aday — bölüm2 |
| 41 | 2026_10_14_110000_add_submission_key_to_study_activity_entries.php | Yeni aday — bölüm2 |
| 42 | 2026_10_14_120000_add_study_answer_time_to_result_details.php | Yeni aday — bölüm2 |
| 43 | 2026_10_14_130000_create_study_notes_tables.php | Yeni aday — bölüm2 |
| 44 | 2026_10_14_140000_create_web_communications.php | Yeni aday — bölüm2 |
| 45 | 2026_10_14_140100_prepare_web_communications.php | Yeni aday — bölüm2 |
| 46 | 2026_10_25_000001_add_live_calculation_fields_to_leagues.php | Yeni aday — bölüm2 |
| 47 | 2026_10_26_130000_grant_study_communication_permission.php | Yeni aday — bölüm2 |
| 48 | 2026_10_30_100000_grant_user_impersonation_permission.php | Yeni aday — bölüm2 |

## 5. Uygulama sonrası kanıt

```sh
/usr/bin/php85 -d extension=imagick.so artisan migrate:status --no-ansi
/usr/bin/php85 -d extension=imagick.so artisan migrate:status --pending --no-ansi
/usr/bin/php85 -d extension=imagick.so artisan permission:cache-reset
```

Onaylı liste Ran olmalı; kalan her Pending dosya ayrıca açıklanmalı. Hiç Pending görünmemesi veri/izin/ayarların doğru olduğunun tek kanıtı değildir. DB-INVENTORY.sql ile rol/izinleri; panelde notifications ve study ayarlarını kontrol et. users:send-email eski kurulumda eksikse bu 14 migration otomatik tamamlamaz; genel PermissionSeeder çalıştırma. Eksik iznin hedefli eklenmesi ve hangi role verileceği mevcut canlı envanterinden somutlaştırılır.

Down yöntemlerinin bir kısmı tabloları siler, bazı izin/çeviri down yöntemleri ise bilerek no-op'tur. Bu nedenle tek batch rollback veya eski kodu geri kopyalama tam geri dönüş sayılmaz. Tam DB yedeği ve canlıda sonradan yazılan verinin etkisi ayrıca değerlendirilir.
