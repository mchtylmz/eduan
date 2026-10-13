# Veritabanı, migration ve SQL standardı

**Güncel kaynak — 5 Ekim 2026:** Kullanıcının açık talimatıyla canlı SQL kopyası ana yerel Sail `hypotenuse` DB'ye yedek sonrası aktarıldı. Salt okunur fiziksel karşılaştırma tamamlandı: 50 tablo/401 kolon; mevcut testing şemasıyla kolon/tip/null/default farkı yok, üç ek indeks ve translation unique adı farkı var. Migration sırası, eski sonuçlar ve yeni kararlar [26](26-CANLI-SQL-AKTARIM-VE-BULGULAR.md), [27](27-MIGRATION-VE-MODEL-ESLESMESI.md), [28](28-TABLO-KOLON-VE-INDEKS-ENVANTERI.md), [29](29-SQL-PERFORMANS-VE-DUZELTME-PLANI.md) içindedir. Aşağıdaki “henüz karşılaştırılmadı/bekleniyor” ifadeleri ilk incelemenin tarihsel durumudur. Takip eden SQL/veri düzeltmeleri uygulanmadı; testing DB checksum'ları korundu.

## Temel ortam

`docker-compose.yml` Sail PHP 8.3 uygulaması ve MySQL 8.0 servisi tanımlar. `composer.json` PHP `^8.2` ister. `phpunit.xml` testte `DB_DATABASE=testing`, array cache/session ve sync queue ayarlar; doğrudan host PHP ile Docker servis adı `mysql` çözümlenmeyebilir. Test veritabanı ile gerçek yerel veritabanını karıştırma.

## Şema haritası

| Küme | Ana tablolar | Önemli bağlar |
|---|---|---|
| Kimlik/yetki | `users`, `password_reset_tokens`, `sessions`, Spatie permission tabloları, `teacher_student` | `teacher_student.user_id` öğrenci/kullanıcı, `teacher_id` öğretmen. |
| Dil ve içerik | `languages`, `translations`, `settings`, `pages`, `blogs`, `faqs`, `contacts`, `newsletters`, `newsletters_mails` | `translations(locale,key)` unique. Sayfa/ders/konu JSON çevirisi ayrıca var. |
| Havuz | `lessons`, `topics`, `questions`, `answers` | `topics.lesson_id`; `questions.lesson_id/topic_id`; `answers.question_id`. |
| Test | `exams`, `exams_questions`, `exams_favorites`, `exam_reviews`, `exam_results`, `exam_result_details` | Ekrandaki test; pivot seçilmiş soruları sırayla tutar. |
| Sınav | `tests`, `tests_sections`, `tests_questions`, `tests_results`, `tests_result_details` | Ekrandaki süreli sınav; bölüm ağacı ve puan hesabı. `tests_result_details(tests_result_id,section_id,question_id)` unique. |
| Ek özellikler | `answer_a_i_s`, `answer_a_i_votes`, `user_ai_usages`, `latex_images`, `leagues`, `league_results`, `seasons`, `logs`, `telescope_*`, `jobs` | Lig sonucu `(league_id,user_id)` unique; sonuçlara `season_id` son migration'da eklenmiş. |

Bu tablo haritası migration'ların ve modellerin mantıksal ilişkilerini özetler; veritabanındaki fiziksel foreign key varlığı anlamına gelmez. İncelenen migration'larda çok sayıda `unsignedBigInteger()->index()` ve az sayıda açık foreign key görülüyor. Silme/rename planında orphan veri ve gerçek DDL ayrıca ölçülmeli.

## Migration durumu ve doğrulama sınırı

5 Ekim 2026 yerel Sail `migrate:status` çıktısında en son `2026_02_04_101550_table_new_column3` dahil bütün listelenen migration'lar `Ran`. Paketlerin `2018_04_02_110607_create_meta_table` ve `2021_02_01_000032_create_settings_table` migration'ları da uygulanmış görünüyor. Bu yalnız migration kayıt tablosunu kanıtlar. Yerel gerçek sütunlar/indeksler ile migration kaynakları karşılaştırılmadı; üretim şeması hiç incelenmedi. Kullanıcının şema geçmişi sapması olabileceği uyarısı geçerlidir. Migration eklemeden önce salt okunur `SHOW CREATE TABLE` / `information_schema` karşılaştırması, veri sayımı ve geri dönüş planı gerekir; uygulama açık onaya bağlıdır.

Proje sahibi yerel veritabanını bu inceleme öncesinde fresh migration + seed ile yeniden kurduğunu bildirdi. Canlı migration tablosunu ayrıca sağlayacak; `2b31f4f5` geliştirme öncesi Git sınırından sonraki şema ve dosya değişiklikleri ayrı değerlendirilecek. Testlerin DB yazma/migration izni yalnız ana veritabanından ayrı `testing` veritabanı içindir.

Bu izinle `testing` DB adı ve boş başlangıcı doğrulandı, 36 migration yalnız orada uygulandı. Canlı veya ana yerel DB ile şema eşitliği bu işlemle kanıtlanmaz.

## SQL yazım ve performans ilkeleri

- Eloquent/Query Builder'ı yakındaki kod tarzıyla kullan. Kullanıcı girdisini raw SQL'e gömme. `CreateOrUpdateTestResultDetail` ve istatistiklerdeki `DB::raw` kullanımı mevcut örnektir, güvenli/optimal olduğunun genel kanıtı değildir.
- Filtre, sıralama ve sayfalamayı mümkün olduğunca SQL'de uygula. Tüm sonuçları `all()`/`get()` ile belleğe alma ve ardından filtrelemeyi büyük listelerde normalleştirme. Export sınıfları şu an `FromCollection` kullanıyor; hacim artışında bellek etkisini ölç.
- `with`, `withCount`, `whereHas` ve seçilen sütunları gerçek ekran sorgusuna göre kullan; liste görünümündeki `language`, `roles`, soru sayıları, öğretmen-öğrenci ve sonuç detaylarında N+1 riskini sorgu sayısıyla doğrula.
- Mevcut tekil indeksler her birleşik filtreyi karşılamayabilir. `locale/status/sort`, `user_id/completed`, `test_id/season_id`, pivot eşleştirmesi gibi adaylar için gerçek `EXPLAIN`, kardinalite ve ekran sorgusu ölçümü yapmadan indeks ekleme.
- Kullanıcıya/role/dile göre değişen sonuçlar cache'leniyorsa anahtar tüm kapsamı taşımalı. `DataHelper::cacheKey` ve `FilterHelper` anahtarları, `users()` gibi öğretmen filtreleri ve `lessons()/topics()` içindeki sayfalama bağlamı özellikle incelenmeli.
- `tests_results` ve `exam_results` geçmiş sonuç kayıtlarıdır. Yeniden adlandırma, veri taşıma, unique/index değişimi ve sezon düzeltmesi için önce gerçek veri dağılımını salt okunur sorgularla gör, yedek/göç planı çıkar ve kullanıcı onayı al.

## Bilinen yapı nüansları

`tests_sections.parent_id` kök için `0` kullanıyor; modelde hem `parents()` hem `parent()` ilişki adı yönü klasik beklentiden farklı olabilir. `exams_questions` ve `tests_questions` pivot'larında sıra ve ders/konu kopya alanları var. `tests_result_details` unique kısıtı upsert anahtarıyla uyumlu; `exam_result_details` için ayrı migration okunmalı. `league_results` benzersizliği `(league_id,user_id)` üzerinden. `teacher_student` migration'ında çift için unique kısıt görünmüyor; tekrar kayıt riski bir sonraki fazda veriyle doğrulanmalı.
