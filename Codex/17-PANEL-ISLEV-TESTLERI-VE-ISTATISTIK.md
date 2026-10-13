# Panel işlev testleri ve toplam istatistik kararı

> **Güncelleme:** Bu kayıttaki iki CRUD hatası kullanıcı onayıyla giderildi. Sonraki dört liste/düğme bulgusu da ayrıca onaylanıp düzeltildi. Güncel sonuç: `18-CRUD-DUZELTMELERI-VE-SAYFA-KANITI.md`; hedef tablo `19`, sayfa düzeni/kalan matris `20` dosyasıdır. Aşağıdaki başarısız koşular tarihsel yeniden üretim kanıtıdır.

5 Ekim 2026. Kullanıcı soru havuzu/panel CRUD ve export işlev kontrolünü, ayrıca revize edilebilir proje standartlarını istedi. Bu kayıt güncel kanıttır; `15` dosyasındaki önceki lig/mail koşusu tarihsel kapsamıyla geçerlidir. Yeni standart listesi `16-PROJE-STANDARTLARI.md` içindedir.

## 1. Yanıtlanan istatistik sorusu

Lig ve panel sonuç istatistiği ayrı sorgulardır. Lig denemeleri toplarken panel aynı öğrenci + aynı test/sınav kayıtlarını GROUP BY ile tek satır yapıyor, doğru/yanlış/soru alanlarını toplamıyordu. Dolayısıyla bütün denemelerin toplamı görünmüyordu.

Kullanıcı toplam görünümünü onayladı:

| Deneme | Soru | Doğru | Yanlış |
|---|---:|---:|---:|
| İlk | 10 | 7 | 3 |
| İkinci | 10 | 5 | 5 |
| Panelde toplam satır | 20 | 12 | 8 |

Başarı %60'tır. Son deneme seçilmez. Başarı toplam doğrudan/toplam sorudan hesaplanır; yüzdeler basit ortalanmaz. Farklı büyüklükte denemelerde 10/10 + 0/30 → 10/40 = %25.

## 2. Dar uygulama değişikliği

`app/Livewire/Users/ResultStatsCustomTable.php` içindeki `filterExams` ve `filterTests`:

- Soru, doğru ve yanlış sayıları SUM olur; süreli sınav puanı da SUM olur.
- Başarı `ROUND(SUM(result.correct_count) * 100.0 / NULLIF(SUM(result.question_count), 0), 2)` olur.
- Sıfır soruda bölme yapılmaz; NULL başarı mevcut HAVING filtresinden geçmez.
- Ekran sayfalaması ve Excel aynı sorguyu kullanır.
- Öğretmen/kullanıcı kapsamı, başarı üst sınırı, gruplama ve sıralama korunur.
- Konu istatistiği zaten detayları union/sum ile topluyordu; değiştirilmedi.
- Lig hesabı, geçmiş kayıtlar, model/route adları ve şema değiştirilmedi.
- Panel sorgularına yeni yarım/tamam filtresi eklenmedi; mevcut kapsam korunur. Ligin ayrı sözleşmesi `15` dosyasındadır.

Regresyon testi düzeltmeden önce 20 yerine 10 soru buldu. Düzeltme sonrası örnek geçti. Başarı üst sınırı %59 olduğunda %60 satır dışlanır; ekran/export satırları eşleşir; sınav puanı 18+10=28 olur.

## 3. Panel CRUD kapsamı

`tests/Feature/PanelCrudTest.php` yalnız testing DB ve transaction kullanır. Fixture kullanıcıya test için ilgili dashboard/CRUD izinlerini verir; aktif nl ve gereken ayarlar testte hazırlanır. Ana DB rolleri/ayarları değiştirilmez.

| Alan | Kontrol |
|---|---|
| Ders | Oluştur/oku/düzenle, çevrili ad, bağlı konu nedeniyle silmenin engellenmesi, soft delete |
| Konu | Ders ilişkisiyle oluştur/düzenle, çevrili başlık, bağlı soru nedeniyle silmenin engellenmesi |
| Soru | Cevaplarla oluştur/düzenle, cevap çıkarma, sıfır/iki doğru cevabı reddetme, soft delete |
| Süresiz test / Exam | Seçilen soru pivotu, düzenleme sonrası ilişkinin korunması, soft delete |
| Süreli sınav / Test | Oluştur/düzenle, süre ve negatif puan, soft delete |
| Sınav bölümü | Üst/alt soru alanı ve soru bağlantısının oluşturulup düzenlenmesi; çıkarma için ayrı hata testi |
| Sayfa | Çevrili içerik, oluştur/düzenle, fiziksel silme, sistem sayfasını koruma |
| SSS | Oluştur/yeniden aç/düzenle/sil zinciri; dil hatası yakalandı |
| Blog | Oluştur/yeniden aç/düzenle/sil, HTML içerik dönüşümü |
| Geçersiz veri | Yinelenen ders kodu, olmayan dersle konu; kayıt sayısının artmaması |

Bu kontroller Livewire formu, Action/model ve silme eylemini kapsar. Tarayıcıdaki tablo arama/sıralama/sayfalama, TinyMCE, sürükle-sırala ve mobil görünüm kanıtı değildir.

## 4. Gerçek Excel dosyaları

`tests/Feature/PanelExportTest.php` dört senaryoda FilterStatsExamsExport, FilterStatsTestsExport, FilterStatsTopicsExport ve FilterStatsQuestionsExport sınıflarını sınar.

1. Excel::raw ile gerçek XLSX üretilir; yalnız facade fake kullanılmaz.
2. ZIP/XLSX imzası kontrol edilir.
3. Geçici container dosyası PhpSpreadsheet ile açılır.
4. Başlık ve veri satırı sayısı kontrol edilir.
5. Kolon sayısı ve ilk başlık kontrol edilir.
6. Soru toplamı/sınav puanı/konu adı doğru kolonda doğrulanır.
7. Her dosyanın başarı oranı kontrol edilir.
8. Geçici test dosyası temizlenir; proje veya ana DB dosyası silinmez.

Sabit örnek satır export sınıfını, StudentStatsAccessTest ise DB sorgusunun toplamını ve kapsamını sınar. Küçük XLSX kanıtı büyük export performansı, gerçek download, PDF/Word veya bütün biçimlendirme senaryolarının kanıtı değildir.

## 5. Yeni doğrulanmış bulgular — düzeltme onayı bekler

### İŞLEV-01 — SSS dilinin kaydedilmemesi

- FaqForm locale gönderir; `app/Models/Faq.php` fillable listesinde locale yoktur.
- Oluşturulan kayıtta seçilen dil korunmaz; yeniden açılan formun save çağrısı locale doğrulamasından geçmez.
- `page_and_faq_create_edit_delete_and_protect_system_page` düzenleme aşamasında locale hatası verdi. Önceki sayfa adımları geçti.
- Öneri: modele locale kaydedilebilir alanını ekle; oluştur/yeniden aç/düzenle/sil testiyle doğrula.
- Eski SSS kayıtlarına toplu dil atamak ayrı veri kararıdır; backfill/migration önerilmedi.

### İŞLEV-02 — Bölüm çıkarıldığında alt kayıtların kalması

- CreateOrUpdateTestSectionsAction çıkarılan üst bölümü siler; alt alanlar ve tests_questions bağlantıları birlikte kaldırılmaz.
- Kaynak migration'da cascade FK yoktur; testte bölüm çıkarılınca bir alt alan kaldı. Kaynakta eski soru bağlantısını temizleyen adım da yoktur.
- `removing_a_timed_section_removes_its_fields_and_question_links` beklenen sıfır yerine bir alt kayıt buldu.
- Öneri: yalnız çıkarılan bölüme bağlı alt alan/soru ilişkilerini mevcut transaction içinde temizle. SectionForm Action false döndüğünde başarı mesajı/redirect vermemeli; bu hata yolu kaynakta görüldü, henüz testle doğrulanmadı.
- Eski orphan kayıtları toplu temizlemek veya FK/migration eklemek ayrı SQL/veri kararıdır.

İki bulgu somut kapsamla kullanıcıya soruldu. Kullanıcının yeni bulguyu otomatik düzeltmeme talimatı gereği ilgili uygulama kodları onay gelmeden değiştirilmez. Yanıt gelince bu durum ve kanıt güncellenir.

## 6. Koşu kanıtı

- İlk istatistik regresyonu 20 yerine 10 döndü; sorgu düzeltildi.
- İlk fixture düzeltmeleri: Page soft-delete kullanmıyor; blog için test locale açık nl yapılmalı. Bunlar üretim hatası olarak sunulmaz.
- İlgili üç dosyanın son birleşik koşusu: **16 geçti / 1 başarısız, 127 assertion, 5.53 saniye.** SSS düzenleme başarısız.
- SSS ve yeni bölüm çıkarma dar koşusu: **2 başarısız, 17 assertion, 1.19 saniye.** Hatalar gizlenmedi.
- Son istatistik sınır koşusu: **2 geçti, 21 assertion, 0.90 saniye.** Toplam, ağırlıklı yüzde, sıfır soru, puan, başarı filtresi ve ekran/export eşleşmesi geçti.

Komut: `docker compose exec -T -e APP_ENV=testing -e DB_DATABASE=testing laravel.test php artisan test <ilgili dosyalar> --no-ansi`; hata metotları `--filter` ile çalıştırıldı. Ana DB migrate/seed/fresh, gerçek mail, bütün suite/build yapılmadı. Aynı koşu gerekçe olmadan tekrarlanmadı.

## 7. Kalan kapsam ve sıra

1. İki yeni CRUD bulgusunun dar düzeltme yanıtı bekler.
2. Standart listesi `16` kullanıcı tarafından madde koduyla revize edilebilir.
3. Bütün modüller henüz uçtan uca tamamlanmadı: dil CRUD/tarama, rol/izin CRUD ve tam rol matrisi, bütün settings, tablo filtreleri ve diğer raporlar ayrı küçük test paketleri gerektirir.
4. Önceki kullanıcı/öğretmen/mail/sınav sınır testleri korunur; bu işlev koşusu bütün ürün güvenlik/doğruluk kabulü değildir.
5. PWA kuralı yanıtlandı ve mevcut yaklaşım kabul edildi. Gerçek offline/logout/eski worker kontrolü ayrı ortam kanıtıdır; aynı ürün sorusu yeniden bekleyen karar yapılmaz.
6. Yeni mail izninin ana DB/rollere dar aktivasyonu henüz yapılmadı; ayrı onaylı işlemdir.
7. Canlı SQL/migration ve eski sonuç verisi ileride kullanıcıdan gelecek. Salt okunur incelenir; sırlar/veri rehbere kopyalanmaz.
8. Framework/paket yükseltme, CDN/Turnstile ve genel optimizasyon uygulanmadı; `14` faz planı sürer.
