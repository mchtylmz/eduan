# Canlı SQL kopyasının yerel aktarımı ve bulgular

5 Ekim 2026. Kaynak `hypotenuse_db.sql`; kullanıcının son açık isteği üzerine agent tarafından **Sail ana yerel `hypotenuse` veritabanına aktarıldı**. Önceki “kullanıcı kendisi aktaracak” notu bu işlem için güncellendi. Üretim sunucusuna bağlantı yapılmadı. Bu belge aktarılmış anlık kopyayı anlatır; canlı sunucunun güncel durumu veya tüm uygulama akışlarının kabulü değildir.

## 1. Aktarım ve koruma kanıtı

- Kaynak boyutu: **44,299,818 bayt**; dosya değiştirilmedi.
- Kaynak SHA-256: `79be023110ea3f9921cf6edf9a497c5753a8758e180f60792fe07f4d68278468`.
- Döküm başlığı: phpMyAdmin 5.2.2; kaynak MySQL 8.4.6-6/PHP 8.2.34; 5 Ekim 2026 14:14 UTC. Başlık sunucu ortamı beyanıdır, uzaktan doğrulanmadı.
- Hedef: projenin `eduan-web-mysql-1` Sail servisi; MySQL **8.0.32**. Uygulama servisi PHP 8.3.32.
- SQL ön kontrolü: 50 CREATE TABLE, 509 INSERT IGNORE, 91 ALTER TABLE. Başka DB'ye USE/CREATE DATABASE/DEFINER/trigger/routine/user/grant/outfile işlemi bulunmadı.
- Önce ana DB mysqldump yedeği alındı; gzip açılabilirliği ve dump bitişi kontrol edildi. **Geri yükleme provası yapılmadı.**
- Yedek: `storage/app/private/codex-db/20261005-import/hypotenuse-before-import.sql.gz` (271.420 bayt). SHA-256: `0ae1a472f6778cee57bac5149d4708110938e21724ba428af281bc359dc439d3`.
- Döküm DROP içermediğinden yalnız hedef ana DB'nin 50 tablosu yedekten sonra kaldırılıp kaynak yeniden yüklendi. DB'nin kendisi, diğer proje DB'leri ve kullanıcı/grant ayarları değiştirilmedi.
- MySQL import çıkış kodu **0**. Satır kaybı/truncation/duplicate uyarısı görülmedi. 19 uyarı: 6 eski utf8 adı, 10 utf8mb3 collation, 3 integer display width kullanımı. Bunlar sürümden kaldırılmaya aday kullanım uyarılarıdır.
- Kaynak INSERT tuple sayımı ile hedef COUNT(*) **50/50 tabloda aynı**, toplam **206.164 satır**. Bu sayım tüm hücrelerin byte düzeyinde karşılaştırması değildir.
- `testing` DB'deki 50 tablonun aktarım öncesi/sonrası EXTENDED CHECKSUM çıktısı aynı. Test DB'ye import/fresh/seed/temizlik yapılmadı.
- İşlem sırasında uygulama queue/scheduler worker süreci bulunmadı; hiçbir queue/retry/mail işlemi çalıştırılmadı. İçe aktarılan `jobs` 0, `failed_jobs` 358.
- Kaynak SQL, yedek ve özel audit JSON/log dosyaları mevcut ignore kurallarıyla Git dışında. Rehberde kullanıcı isimleri, e-postalar, parolalar, tokenlar ve job payloadları yoktur.

## 2. Şema sonucu

**50 tablo, 401 kolon, 174 adlandırılmış indeks, 5 fiziksel FK** incelendi. Bütün tablolar InnoDB. 48 tablo utf8mb4_unicode_ci; meta ve tests_sections_meta utf8mb3_unicode_ci.

Ana kopya ile daha önce migration'lardan kurulmuş `testing` şemasında tablo/kolon adı, tip, nullable, default, extra, charset ve collation farkı çıkmadı. Kolon sırası karşılaştırmaya dahil edilmedi. Bugünkü testing şeması karşılaştırma referansıdır; bu tur yeni fresh çalıştırılmadı. Tarihsel migration dosyalarının canlıda çalıştırıldığı gündeki içerik hash'i dökümde yoktur.

Farklar:

1. Ana DB'de ek `users.gpt_limit` tekil indeksi.
2. Ana DB'de ek `user_ai_usages.usage` tekil indeksi.
3. Ana DB'de ek `user_ai_usages.remaining` tekil indeksi.
4. `translations(locale,key)` unique aynı işi yapıyor; canlıdaki adı `locale`, testing'deki adı `translations_locale_key_unique`. **Unique eksikliği değil, ad farkı.**

Bu nedenle “tüm migration'ları yeniden çalıştırmak” veya tüm kolonlara yeni default eklemek gerekmez. Kaynak/isim/batch haritası [27](27-MIGRATION-VE-MODEL-ESLESMESI.md), her kolon/indeks [28](28-TABLO-KOLON-VE-INDEKS-ENVANTERI.md), düzeltme sırası [29](29-SQL-PERFORMANS-VE-DUZELTME-PLANI.md) içindedir.

## 3. Eski sonuçlar

| Alan | Tamamlanmış | Tamamlanmamış | Yorum |
| --- | --- | --- | --- |
| Süresiz test: exam_results | 14.573 | 1.658 | 1.658 yarım kaydın hepsinde cevap var; kullanıcı kararı gereği lig için geçerli kayıtlı cevaplar sayılır. |
| Süreli sınav: tests_results | 327 | 52 | 52 kaydın tamamının süresi geçmiş ve hiç detay cevabı yok. Yeni job otomatik olarak bu tarihsel kayıtlar için kurulmuş değil. |

Tamamlanmış sınavlarda completed_at eksikliği yok; tamamlanmamışlarda expires_at eksikliği yok. Aynı kullanıcı/sınav için birden fazla tamamlanmamış deneme bulunmadı. Tamamlanmış sınavların 2.768 detayında `correct=-1` **boş cevap enumudur**, veri bozukluğu değildir. `answer_id=0` boş cevaptır; `parent_id=0` bölüm köküdür.

### Sonuç sayaçları

- **11 tamamlanmış süresiz sonuçta question_count detay sayısıyla uyuşmuyor.** Doğru ve yanlış toplamları detaylarla eşleşiyor. Bunların içinde 4 kayıtta doğru+yanlış, saklanan soru sayısından fazla.
- **1 tamamlanmış süreli sonuçta question_count detay sayısıyla uyuşmuyor.** Doğru, yanlış ve toplam point eşleşiyor.
- Süreli kayıtlarda 2 expires_at, created_at'tan önce. Tamamlanmış 2 kayıtta duration, created_at/completed_at farkıyla uyuşmuyor. Bu iki kümenin aynı kayıtlar olduğu iddia edilmez.
- 28 tamamlanmış sınavda duration bugünkü sınav ayarındaki duration'dan büyük. Tarihsel ayar değişimi/eskiden geç bitirme olasılığı vardır; doğrudan eski sonucu hatalı veya yeniden puanlanmalı sayma.
- Var olan puanlar tinyint sınırında değil; bugünkü aktif sınav konfigürasyonlarının olası puan toplamları da aralığı aşmıyor. Gelecekte daha uzun sınav için tip genişletme adayı var.

Tarihsel soru/bölüm yapısı değişmiş olabilir. **Bugünkü soru listesinden eski sonuçları yeniden hesaplamak güvenilir göç değildir.** Önce eski snapshot/ürün kararı; yalnız doğrulanan sayaç uyumu için hedefli veri düzeltmesi planlanır.

## 4. Sahipsiz bağlar ve tarihsel artıklar

| Kaynak ve bulunamayan bağ | Satır sayısı | Değerlendirme |
| --- | --- | --- |
| exam_result_details → exam_results | 12 | Üst sonuç fiziksel olarak yok. |
| exams_questions → exams | 13 | Üst test fiziksel olarak yok; pivot artığı. |
| tests_result_details → tests_results | 40 | Üst sonuç fiziksel olarak yok. |
| tests_result_details → tests_sections | 99 | Üst sonuç mevcut, bölüm fiziksel olarak yok; soru/bölüm düzenleme geçmişi araştırılmalı. |
| tests_result_details question/lesson/topic | 10 | Üç alanda aynı sayıda 0 placeholder var. Gerçek bir soru ID'sinin silindiği iddia edilmez. |
| tests_sections_meta → TestsSection | 21 | Polymorphic owner sınıfı eşleşiyor, owner_id artık yok. |
| answer_a_i_votes → answer_a_i_s | 1 | AI cevabı yok. |
| user_ai_usages → answer_a_i_s | 38 | AI cevabı yok; kullanım/kota geçmişinin korunması gerekebilir. |

Süreli sonuç detaylarında bu beş bağlantı kontrolünden herhangi birine takılan **149 farklı satır** var; satır sayıları toplanarak farklı satır sanılmamalı. Fiziksel varlık kontrolü soft-delete durumundan ayrıdır. Süresiz test pivotlarında ayrıca soft-delete edilmiş sorulara 24 bağ var; bu 24 satır fiziksel orphan değildir.

Silme yapılmadı. Arşivleme/silme/bağı koruma kararı geçmişi, export'u, lig ve okul raporlarını birlikte etkiler. Kullanım/kota geçmişi sırf AI cevabı yok diye otomatik temizlenmez.

## 5. Model ve migration kaynak bulguları

| Kod | Kaynak | Bulguyu nasıl ele alacağız? |
| --- | --- | --- |
| SQL-M01 | TestsResultDetail::testResult() | FK `test_result_id` yazılmış; fiziksel/fillable alan `tests_result_id`. Kullanıldığında yanlış bağ; dar ilişki testiyle düzeltme adayı. |
| SQL-M02 | AnswerAI::answer() | Varsayılan `answer_id` kolonu yok. İncelenen çağrılarda bu ilişki kullanılmıyor; kaldırma veya gerçek iş ilişkisi kararı gerekir. |
| SQL-M03 | Faq/Language fillable | Faq brief/slug/type ve Language sort kolonları yok. Mevcut form bu alanları kaydetmiyor; şemaya gereksiz alan eklemek yerine eski tanımı temizleme adayı. |
| SQL-M04 | Exam/Test::userResults() | SELECT * + groupBy exam_id/test_id rastgele deneme seçebilir. Hesap/listeler çağırıyor; MySQL istemcisinin ONLY_FULL_GROUP_BY modunda EXPLAIN 1055. Uygulama config'i strict=false; mevcut ekranın 500 verdiği kanıtlanmadı. |
| SQL-M05 | ExamResult/TestsResult/Page | deleted_at var; SoftDeletes trait'i yok. Bu üç tabloda silinmiş kayıt sayısı 0; mevcut veri sızması iddia edilmez. Silme sözleşmesi kararı gerekir. |
| SQL-M06 | TestsSection parent()/parents() | İki ilişki de child yönünde; parent tek child döndürüyor. Mevcut ağacın isim geleneğiyle birlikte ele alınmalı. |
| SQL-M07 | 2025_08_18_102020_table_new_column down | users.gpt_limit geri alınırken settings.value longtext dönüşümü geri alınmıyor. Şu an kolon sapması yok; rollback beklentisi eksik. |
| SQL-M08 | 2025_04_16_121101_create_tests_table down | tests iki kez dropIfExists; pivotlardan önce parent düşürülüyor. tests_questions drop'u **var**. Mevcut fiziksel FK yok; ileride FK eklenirse sıra yeniden değerlendirilmeli. |
| SQL-M09 | config/database.php | mysql strict=false; tip/limit hataları bazı yazmalarda sessiz dönüşüme dönüşebilir. Bugün veri truncation'ı kanıtlanmadı; strict geçişi önce sorgu/test uyarlaması ister. |

## 6. İzin ve işletim

İçe aktarılan 75 permission içinde **users:send-email yok**. Yeni kod bu izni gerektiriyor; ana kopyada mail erişimi beklenen biçimde reddedilebilir. Tüm PermissionSeeder çalıştırılmadı; tek izni oluşturup hangi role verileceği ayrıca onaylanmalıdır.

358 failed_jobs tarihsel kayıt. İstisna sınıfına yönelik dar sayımda 6 ConnectionException eşleşmesi var; diğerlerinin nedeni bu sınırlı sınıflandırmayla belirlenmedi. Payload veya e-posta içerikleri rapora alınmadı, retry/purge çalıştırılmadı.

İçe aktarma cache temizlemez. Daha önceki yerel cache'lerde eski ayar/listeler bulunabilir; bu tur optimize:clear/resetCache çalıştırılmadı. Sonraki UI doğrulaması somut cache kapsamıyla planlanmalıdır. Döküm dosya eklerini içermez; DB'deki görsel/PDF yollarının fiziksel/CDN varlığı bu SQL kontrolünün kanıtı değildir.

## 7. Doğrulama sınırı ve sonraki karar

231 toplu sayım/tutarlılık kontrolü; **190 kontrolün sonucu 0**. Kalanlar tamamlanma adetleri, soft-delete/boş cevap gibi normal dağılımlar ve yukarıdaki bulgulardır; “41 başarısız test” değildir. 14 salt okunur temsilî EXPLAIN ve katı modda 2 ilişki EXPLAIN kontrolü yapıldı. Bütün kontrol adları/sonuçları 29 içinde.

Bu tur uygulama kodu değişmedi; PHP suite/build/browser tekrar çalıştırılmadı. Önceki 245 PHP + 7 PWA kanıtı eski ayrı test koşusudur; yeni gerçek kopyanın uygulama kabulü olarak sunulmaz. Ana DB'de test yazımı yoktur.

Sonraki onay: [29'daki paketler](29-SQL-PERFORMANS-VE-DUZELTME-PLANI.md). Önce 52 eski boş sınav, sayaçların tarihsel anlamı, orphan geçmiş ve sonuç kartı seçimi netleştirilir; sonra yalnız seçilen dar düzeltmeler ve append migration'lar. Paket/Laravel 13 işlemi bu tur başlamadı.
