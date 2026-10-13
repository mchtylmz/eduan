# Test hazırlığı ve kayıt kapsamı düzeltmesi

**Sonraki uygulama — 6 Ekim:** [38-BOOTSTRAP-TABLE-UC-SAYFA-PILOTU.md](38-BOOTSTRAP-TABLE-UC-SAYFA-PILOTU.md). Dersler/Konular/Kullanıcılar taşındı; JSON60sn/seçenek1saat ve readonly sağ detay paneli onaylandı.26 dar test geçti; Chrome masaüstü/mobil kontrolü yapıldı.28 diğer Rappasoft sınıfı bekliyor. Aşağıdaki kayıt kendi tarihsel kapsamıyla korunur.

5 Ekim 2026. `36` genel koşusundaki ilk engeller açıklandıktan sonra kullanıcı **önce test hazırlığını düzelt** yanıtını verdi. Yalnız bu düzeltme uygulandı; testing QA kayıtları silinmedi, uygulama kodu veya ana DB değiştirilmedi.

## 1. Dil hazırlığı

Yedi test sınıfında `nl` hazırlığı `Language::create` yerine kod bazlı `firstOrCreate` kullanır. Mevcut dil tekrar eklenmez; yoksa gereken varsayılan değerlerle oluşturulur. Bunlar transaction kullanan sınıflardır; ortak TestCase'e bütün senaryoları etkileyen otomatik seed/temizlik eklenmedi.

Etkilenenler: AccessBoundaryTest, FrontendListFindingsTest, PanelCrudTest, PanelModuleCrudTest, PanelPermissionMatrixTest, PopulatedDetailTest, StudentAssignmentTest. Dil oluşturma/duplicate doğrulamasının asıl ürün beklentileri kaldırılmadı.

## 2. Yetkisiz işlem ve doğrulama beklentileri

- AccessBoundaryTest sınav ekleme reddinde başlangıç/sonrası toplamı karşılaştırır; önceki sınavın bulunması yanlış başarısızlık üretmez.
- PracticeTestSecurityTest başlangıç detay sayısını kaydeder. Dört yetkisiz/manipülasyon/erken tamamlama senaryosunda bütün detay tablosunun bu sayıdan artmadığı doğrulanır. 403/422/409 beklentileri korunur.
- PanelCrudTest hatalı cevap yapısı, duplicate ders ve bulunmayan konu-ders ilişkisinde başlangıç/sonrası toplamları karşılaştırır.
- PanelModuleCrudTest duplicate dil, yetkisiz/hatalı rol ve hatalı sezon işlemlerinde başlangıç/sonrası toplamları karşılaştırır. Mevcut diğer kayıtlar sıfır veya tek satır varsayılmaz.

Bu kontroller kayıt sayısındaki değişmezliği sınar; aynı sayıda kaydın içeriğinin değişmediği için genel bir checksum kanıtı olarak sunulmaz.

## 3. Süreli sınav denemesi

TimedExamTest'in arka planla tamamlama ve manuel tamamlama metotlarında `TestsResult::firstOrFail()` kaldırıldı. Testin oluşturduğu sınavın `results()` ilişkisi ve kendi `user_id` değeri kullanılır. Yeniden deneme sayısı ve son deneme seçimi de aynı kapsamda yapılır.

120 saniyelik yeni deneme yerine önceki60 saniyelik QA sonucunun okunması engellenir. Süre sonu, puan/doğru/yanlış, idempotent arka plan işi, yeni deneme başlangıcı ve tamamlanmış cevabın değiştirilememesi beklentileri korunur. Ürün süre veya puan hesabı değiştirilmedi.

## 4. Ön yüz listeleri

- Ders pagination/cache testi22 kaydı kendine ait kod önekiyle oluşturur ve mevcut arama filtresiyle bu22 kaydı seçer. İlk sayfa21/ikinci sayfa1, doğru sayfa numarası ve farklı kayıt beklentileri korunur; eski24 QA dersi sonuca karışmaz.
- Aktif/pasif sınav liste testi oluşturduğu iki kaydın kimliklerini izler. Gerçek bileşen çıktısında bu iki kaydı ayırıp yalnız aktif olanın döndüğünü doğrular. Başka aktif sınavın listede bulunması hata sayılmaz; pasif fixture sonuçta görünürse test başarısız olur.

Liste sorguları veya cache anahtarları bu tur değiştirilmedi.

## 5. Tek dar tekrar

Yalnız değişen dokuz sınıf çalıştırıldı:

```sh
docker compose exec -T \
  -e APP_ENV=testing -e DB_DATABASE=testing -e CACHE_STORE=array \
  laravel.test vendor/bin/phpunit \
  --filter '(AccessBoundaryTest|FrontendListFindingsTest|PanelCrudTest|PanelModuleCrudTest|PanelPermissionMatrixTest|PopulatedDetailTest|StudentAssignmentTest|PracticeTestSecurityTest|TimedExamTest)' \
  --log-junit /tmp/eduan-test-fixture-fix.xml
```

**105 test / 455 doğrulama:70 geçti,11error+24failure kaldı; exit2.** Kalan35 sonucun tamamında Rappasoft sınıfı bulunamadı mesajı var. Dil unique çakışması ve başka ilk hata mesajı yok. Bu koşu başarılı suite diye sunulmaz.

JUnit'teki aynı test kimlikleri önceki genel koşuyla eşleştirildi:

| Önceki durum | Son dar koşu |
|---|---|
|78 dil hazırlığı hatası|45 geçti;33 senaryo bu kez tabloya kadar ilerleyip Rappasoft'ta takıldı|
|9 global kayıt/liste beklentisi hatası|9'u geçti|
|Bu dokuz sınıfta önceden geçen16 test|16'sı geçti|
|Bu dokuz sınıfta önceden Rappasoft'ta takılan2 test|2'si hâlâ Rappasoft'ta takılıyor|

Sonuç: **54 test senaryosu önceki fixture engelinden sonra geçti.** Tablo senaryolarının güvenlik/CRUD/sayfa sonrasındaki beklentileri henüz doğrulanmış değildir. Rappasoft engellerini atlamak için test skip/expect500 veya sahte tablo base class eklenmedi.

Host PHP8.5.7 ile değişen9 test dosyası syntax kontrolü geçti; aynı9 dosyada `git diff --check` temiz. PHPUnit koşusu gerçek container PHP8.5.8 üzerinde yapıldı. Toplu formatter/build veya değişmeyen bütün suite yeniden çalıştırılmadı. PWA7-pass sonucu `36` içindeki önceki koşudur; bu tur PWA kodu değişmedi.

Metin logu host `/tmp/eduan-test-fixture-fix.log`, JUnit container `/tmp/eduan-test-fixture-fix.xml` içindedir.

## 6. Değişen test dosyaları

1. `tests/Feature/AccessBoundaryTest.php`
2. `tests/Feature/FrontendListFindingsTest.php`
3. `tests/Feature/PanelCrudTest.php`
4. `tests/Feature/PanelModuleCrudTest.php`
5. `tests/Feature/PanelPermissionMatrixTest.php`
6. `tests/Feature/PopulatedDetailTest.php`
7. `tests/Feature/StudentAssignmentTest.php`
8. `tests/Feature/PracticeTestSecurityTest.php`
9. `tests/Feature/TimedExamTest.php`

Ayrıca bu kayıt, AGENTS.md, Codex/index.html ve36'dan güncel sonuca bağlantı eklendi.35'e genel sonuç bağlantısı önceki kontrol bölümünde eklenmiştir. Bu dosya listesi önceki yükseltme veya diğer fazların değişikliklerini sahiplenmez.

## 7. Sıradaki iş

Bootstrap Table geçişi `19` ve `25` içindeki Lara Football referansı üzerinden uygulanmalıdır. Tablo aileleri için mevcut filtreler, izin/öğretmen kapsamı, JSON total/rows, arama/sıralama/sayfalama, işlem ve export beklentileri korunur. Kalan35 dar hata bu geçiş tamamlanmadan geçmez; genel suite'in dışında kalan eski tablo senaryoları da ayrıca doğrulanacaktır.

Bu aşamada test hazırlığı için tanımlanan engeller giderildi. Bütün proje testleri veya ürün kabulü tamamlandı denmez; yeni tablo uygulaması bu tur yapılmadı.
