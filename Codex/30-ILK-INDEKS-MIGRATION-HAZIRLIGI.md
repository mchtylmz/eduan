# İlk SQL performans paketi — üç indeks migration'ı

**Güncel sonuç:** Kullanıcı onayıyla ana yerel hypotenuse DB'ye uygulandı; id=37/batch=7. Veri checksum'ları aynı; yeni indeksler EXPLAIN'de seçiliyor. Yerel önce/sonra süreler, ikinci helper optimizasyonu ve migration uygulanmış şemayı da koruyan güncel 4 test/107 doğrulama kanıtı [31](31-INDEKS-UYGULAMA-VE-OLCUM-SONUCU.md) içinde. Aşağıdaki “henüz uygulanmadı” ve 1 test/41 doğrulama bölümleri hazırlık anının tarihsel kaydıdır; eski test precondition'ı güncellendi.

5 Ekim 2026. Kullanıcının SQL optimizasyonuna başlama isteğiyle somut migration ve dar testi hazırlandı. **Ana yerel hypotenuse DB'de veya üretimde uygulanmadı.** Testing'de test sırasında up/down çağrıldı; sonunda başlangıç indeksleri ve satır checksum'ları korundu. Migration repository'ye yeni Ran kaydı yazılmadı.

## 1. Seçilen ilk kapsam

| Tablo | Yeni indeks | Sıralı kolonlar | Kaynak sorgu ve gerekçe |
| --- | --- | --- | --- |
| users | users_username_index | username | LoginAction/Auth::attempt kullanıcı adıyla arar. Kaynak kopyada bu kolon indeksli değil; önceki EXPLAIN deleted_at indeksinden yaklaşık 379 satır tahmin etmişti. |
| exam_results | exam_results_season_completed_updated_index | season_id, completed, updated_at | Panel Exams/ResultsTable varsayılanında aktif sezon + tamamlanmış filtresi + updated_at DESC var. Önceki temsilî EXPLAIN completed indeksinden yaklaşık 8.217 satır alıp sezon filtresi/filesort yapıyordu. |
| exam_result_details | exam_result_details_result_question_index | exam_result_id, question_id | Frontend Tests/SolveForm sonuç içindeki seçilmiş sorunun cevabını arar; mount/savedAnswer/firstOrCreate aynı çiftle çalışır. 149.904 detayda mevcut plan iki tek kolonlu indeksin index_merge birleşimini kullanıyor. |

Üçü de **normal, nonunique BTREE indeks**. Kullanıcı adı benzersizliği veya cevap tekilliği kısıtı eklenmez; bunlar ayrı iş kuralı değişiklikleridir. Eski indeksler kaldırılmaz. Kolon/default/null/charset/FK/puan/sonuç verisi değişmez. Geçmişteki yarım sınavları tamamlama veya sahipsiz kayıtları silme bu pakete dahil değildir.

Önceki genel aday tablosundaki user_id/completed geçmiş indeksi ilk pakete alınmadı: kaynakta panel sezon filtresi varsayılanı açıkça var; eldeki ilk kanıt doğrudan bu şekille eşleştirildi. Kullanıcı/test deneme kartının deterministik seçimi hâlâ ayrı karardır.

Birleşik indekste sıralı kolonlar sorgunun eşitlik filtreleriyle başlayacak şekilde seçildi; [MySQL resmi çok kolonlu indeks açıklaması](https://dev.mysql.com/doc/refman/8.0/en/multiple-column-indexes.html). updated_at eşitlikten sonraki sıralamayı hedefler. [InnoDB secondary indekslerin PK kolonlarıyla genişlemesi](https://dev.mysql.com/doc/refman/8.0/en/index-extensions.html) nedeniyle bu ilk indeks tanımına ayrıca id eklenmedi; gerçek planner seçimi uygulama sonrası kontrol edilecek.

## 2. Dosyalar ve eski migration sınırı

- [Yeni migration](../database/migrations/2026_10_05_150000_add_initial_query_performance_indexes.php).
- [Dar migration testi](../tests/Feature/InitialQueryIndexesTest.php).
- Eski 36 migration adı/id/batch ve eski dosyaların içerikleri değiştirilmedi.
- up üç CREATE INDEX işlemini Laravel Schema/Blueprint üzerinden üretir; down yalnız bu üç adlandırılmış indeksi ters sırada düşürür.
- İndeksler yeni/rezerve adlarla tanımlandı; bu tur salt okunur metadata kontrolünde hem hypotenuse hem testing'de üçü de yoktu.
- Normal Laravel migration repository yalnız ilk çalışmayı yönetir. up doğrudan iki kez çağrılacak idempotent araç değildir; beklenmeyen aynı adlı indeks varsa inceleyip durmak gerekir, sessizce atlama yapılmaz.
- MySQL DDL işlemleri bir transaction ile tümüyle geri alınmaz. Üç tablo arasında yarım uygulama olursa repository kaydına körlemesine müdahale veya tüm migration'ları tekrar yürütme yapılmaz; hangi yeni indeksin eklendiği görülüp hedefli düzeltme/geri alma ayrıca planlanır.
- down önceden var olan gpt_limit/usage/remaining/translation unique veya diğer indeksleri düşürmez. Yeni isimlerin başka değişiklikte yeniden kullanılması engellenmelidir.

## 3. Test kanıtı

Komut:

```sh
docker compose exec -T laravel.test php artisan test --compact tests/Feature/InitialQueryIndexesTest.php
```

**1 test geçti; 41 doğrulama; 1,90 saniye.** TestCase öncesi DB_DATABASE ve gerçek bağlantı adı testing doğrulanır. Bu test DatabaseTransactions kullanmaz; DDL'yi down ile geri alır ve hata durumunda yalnız başlangıçta bulunmadığı doğrulanmış üç test indeksini finally içinde temizler.

Doğrulananlar:

1. Başlangıçta yeni indeks adlarının yokluğu.
2. Her indeksin doğru tablo/kolon sırasına eklenmesi.
3. Unique/primary olmaması.
4. Var olan bütün indekslerin korunması.
5. Üç tablonun EXTENDED CHECKSUM değerlerinin up sonrasında aynı kalması.
6. Giriş araması, sezon/tamamlanma listesi ve sonuç/soru aramasının önce/sonra eşit sonuç üretmesi; tarih eşitliğinde test sorgusunda id tie-breaker kullanılır.
7. down sonrasında indeks metadata'sının tam başlangıç haline dönmesi.
8. down sonrasında checksum'ların yine aynı olması.

Formatter komutu yalnız iki yeni dosyaya uygulandı:

```sh
docker compose exec -T laravel.test vendor/bin/pint --test database/migrations/2026_10_05_150000_add_initial_query_performance_indexes.php tests/Feature/InitialQueryIndexesTest.php
```

**2 dosya PASS.** Tüm PHP suite/build/browser tekrar koşulmadı; uygulama kodu değişmedi. Bu sonuç testing verisinin yeni indekslerle korunduğunu ve rollback'i kanıtlar; ana kopyada hız kazanımı veya üretim kabulü değildir. Testing şeması başlangıca döndüğü için bu migration orada da henüz kalıcı uygulanmış değildir.

## 4. Ana yerel DB'ye uygulama için somut plan

AGENTS.md veri/migration riskinde somut plan ve açık onay ister. Hazırlık/test tamamlandı; aşağıdaki ana DB uygulaması kullanıcı onayı sonrası yürütülür:

1. Gerçek hedefin hypotenuse olduğunu, pending dosyayı ve üç yeni indeksin hâlâ yokluğunu salt okunur kontrol et. Diğer pending migration'ları otomatik yürütme.
2. İçe aktarılan güncel ana DB'nin yeni özel yedeğini al. Önceki before-import yedeği SQL aktarımından önceki yerel veridir; bu migration öncesi ana kopyanın yedeği olarak sunulmaz.
3. Yalnız `2026_10_05_150000_add_initial_query_performance_indexes.php` dosyasını ana yerel bağlantıda migrate --path ile uygula; fresh/seed/veri temizliği yok. Üretim komutu veya dağıtım yok.
4. Üç tablonun sayım/checksum değerlerini ve eklenen indeks imzalarını kontrol et; migration repository yeni kayıt/batch durumunu kaydet.
5. Aynı veri/parametre/sorgu şekillerinde yalnız üç ilgili EXPLAIN'i önce/sonra karşılaştır. Satır tahmini, seçilen indeks, filesort ve gerektiğinde süreyi kaydet; başka veri setlerinden ölçülen süreleri yüzde kazanım diye karşılaştırma.
6. Yerel DDL sırasında disk/kilit durumunu gözle. Yeni indeksler ek depolama ve yazma bakımı getirir; sonuç ölçümsüz “maksimum hız” diye sunulmaz.
7. Sonuç olumluysa dar uygulama akışı testi testing'de seçilen ilgili senaryoyla tamamlanır. Eski grouped SELECT * sorgularının nondeterministik kart seçimi hâlâ 29'daki ayrı bulgudur; bu migration tüm ekranın davranış onayı değildir.
8. Geri alma gerekirse yalnız yeni dosyanın down kapsamı ve son batch kontrol edilerek onaylı işlem yapılır; geniş rollback/fresh yok.

## 5. Sonraki indeks ve optimizasyon paketi

İlk uygulama/EXPLAIN sonrası kullanıcı/test aktif deneme, pivot sıralama, lig updated_at dönemi ve büyük filtre all() yüklemeleri 29'daki adaylarla sırayla değerlendirilir. Unique/FK/charset/tip dönüşümü, ekstra üç canlı-only indeksin keep/drop kararı, strict=true ve tarihsel veri düzeltmesi bu ilk normal indeks migration'ına eklenmez.

Bu ilk üç indeks yalnız ilk adım: gerçek SQL shape, kullanıcı/öğretmen kapsamı, export ve cache maliyetleri ayrıca ölçülmeden “bütün SQL optimize edildi” denmez. Paketler veya Laravel sürümü bu tur değiştirilmedi.
