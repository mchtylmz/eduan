# Laravel 13 sonrası genel otomatik test sonucu

**Sonraki onaylı düzeltme:** [37-TEST-HAZIRLIGI-VE-KAYIT-KAPSAMI-DUZELTMESI.md](37-TEST-HAZIRLIGI-VE-KAYIT-KAPSAMI-DUZELTMESI.md). Dil hazırlığı ve kayıt kapsamı düzeltildi. Değişen9 sınıfın dar tekrarında105 testten70 geçti; kalan35 sonuç Rappasoft'a bağlı. Aşağıdaki262-test koşusu düzeltme öncesi kayıttır.

5 Ekim 2026. Kullanıcı Docker açıkken mevcut kod testlerinin yeniden genel olarak kontrol edilmesini istedi. Bu kayıt, `35` içindeki 16 farklı dar senaryodan sonra yapılan **tek genel PHP koşusudur**. Sonuç başarılı değildir; eski Laravel 11 koşusunun başarısı yeni hedef sürüme taşınamaz.

## 1. Doğrulanan ortam ve komut

- Uygulama container'ı çalışıyor; MySQL container'ı healthy.
- Gerçek CLI **PHP 8.5.8**; PHP 8.1 değildir.
- Kurulu hedefler `35` içindeki Laravel13.34.0, Livewire4.4.7 ve PHPUnit13.4.1 sürümleridir.
- Test bağlantısı yalnız **testing**. Ortak `Tests\TestCase`, hem bootstrap öncesi DB_DATABASE değerini hem gerçek config bağlantısını denetler.
- Ana yerel hypotenuse DB üzerinde bu kontrolün SQL işlemi yoktur. Yeni migration/fresh/seed/import veya QA fixture temizliği yapılmadı.
- Mevcut indeks testi kendi testing indekslerini sınar ve başlangıç durumuna geri getirir; ana DB migration uygulaması değildir.
- Model olaylarının `optimize:clear` çağrısı TestCase içinde taklit edilir; Laravel HTTP istemcisinin beklenmeyen dış istekleri engellenir.

Genel PHP koşusu:

```sh
docker compose exec -T \
  -e APP_ENV=testing -e DB_DATABASE=testing -e CACHE_STORE=array \
  laravel.test vendor/bin/phpunit \
  --log-junit /tmp/eduan-laravel13-general.xml
```

PWA JavaScript koşusu:

```sh
docker compose exec -T laravel.test node --test tests/Frontend/PwaSafetyTest.cjs
```

PHP metin logu host üzerinde `/tmp/eduan-laravel13-general.log`, JUnit raporu container içinde `/tmp/eduan-laravel13-general.xml`; PWA logu host üzerinde `/tmp/eduan-laravel13-pwa-general.log`. Bunlar geçici yerel kanıttır; üretim verisi veya depoya alınacak SQL çıktısı değildir.

## 2. Sonuç

| Koşu | Test | Geçen | Error | Failure | Atlanan | Doğrulama |
|---|---:|---:|---:|---:|---:|---:|
| Genel PHP suite | 262 | 152 | 81 | 29 | 0 | 946 |
| PWA JavaScript | 7 | 7 | 0 | 0 | 0 | — |

PHP exit2, PWA exit0. PHP koşusu sonuna kadar tamamlandı. **110 başarısız test sonucu vardır; bu sayı 110 ayrı uygulama hatası demek değildir.** PWA sonucu VM/script davranışını doğrular; gerçek tarayıcı çevrimdışı/logout veya cihaz kabulü değildir.

## 3. İlk başarısızlık nedenlerinin ayrımı

JUnit mesajları ve ilgili test kaynakları birlikte incelendi:

| İlk engel | Test sayısı | Kanıt ve anlamı |
|---|---:|---|
| Aynı `nl` dil kaydını yeniden ekleyen hazırlık | 78 | Tamamında `languages.languages_code_unique` çakışması. Mevcut dil kaydıyla `Language::create` çakışır; asıl senaryo ilerlemez. |
| Kaldırılmış Rappasoft sınıfları | 23 | PageRenderTest20, StudentAccessTest1, StudentAssignmentTest2. Tablo yüklenmesi sınıf bulunamadı hatası veya HTTP500 üretir. Kendi tablo karşılığı henüz yoktur. |
| Kalan kayıtlara da bakan test sorgusu/beklentisi | 9 | Global count/first ve listeyi boş veritabanı sayan beklentiler. Aşağıdaki kaynak ve salt okunur toplamlar bu çakışmayı açıklar. |

Bu bir **ilk engel sınıflandırmasıdır**. Özellikle 78 hazırlık hatası giderilmeden bu testlerin uygulama adımları hakkında başarı veya başka hata olmadığı sonucu çıkarılamaz. Aynı şekilde fixture sorguları düzeltildikten sonra kalan doğrulamalar tekrar çalıştırılmalıdır.

## 4. Dokuz kayıt kapsamı sorunu

Testing üzerinde salt okunur sayım: languages2, exams1, exam_result_details2, lessons24, tests_results4. İlk süreli sonuç duration60 / correct_count0 / completed1 değerindedir. Bu sayımların ana yerel veya canlı DB ile ilgisi yoktur. Önceki browser fixture kaydı `23` içinde açıklanır; kayıtlar silinmedi.

| Test / dosya | Başarısız beklenti | Kaynak değerlendirmesi |
|---|---|---|
| AccessBoundaryTest:64 | İzin reddinden sonra bütün exams tablosu0 bekleniyor;1 var | `assertForbidden` aşaması geçti; global count önceden duran sınavı da sayıyor. Başlangıç sayısının değişmediği doğrulanmalıdır. |
| FrontendListFindingsTest:31 | Derslerin ikinci sayfasında1 kayıt bekleniyor;21 var | Test22 ders ekliyor, önceden24 ders var. Sayfa numarası2 doğrulaması geçti. Liste testinin veri kapsamı belirlenmelidir; bu sonuç tek başına cache sayfa çakışması değildir. |
| FrontendListFindingsTest:62 | Aktif sınav listesi yalnız yeni tek kodu içermeli | Gerçekte yeni aktif kayıtla birlikte önceki aktif QA sınavı da var. Testin eklediği aktif/pasif kayıtlar ayrı doğrulanmalıdır. |
| PracticeTestSecurityTest:73 | Misafir/premium reddinden sonra tüm detay tablosu0 | İzin reddi doğrulamaları geçti; önceki2 detay global count'a giriyor. |
| PracticeTestSecurityTest:81 | Sahte doğruluk isteği reddinden sonra tüm detay tablosu0 | 422 doğrulaması geçti; önceki2 detay global count'a giriyor. |
| PracticeTestSecurityTest:89 | Başka sorunun cevabı reddinden sonra tüm detay tablosu0 | 422 doğrulaması geçti; önceki2 detay global count'a giriyor. |
| PracticeTestSecurityTest:124 | Erken tamamlama/pasif sınav reddinden sonra tüm detay tablosu0 | 409/403 doğrulamaları geçti; önceki2 detay global count'a giriyor. |
| TimedExamTest:142 | Yeni120 saniyelik denemenin süresi120 olmalı;60 okunuyor | `TestsResult::firstOrFail()` önceki QA sonucunu seçiyor. Testin kendi test_id/user_id denemesi okunmalıdır. Sonraki global count/latest beklentileri de aynı kapsamda ele alınmalıdır. |
| TimedExamTest:183 | Yeni manuel denemenin doğru sayısı1 olmalı;0 okunuyor | Global first önceki QA sonucunu seçiyor; yeni deneme tamamlanma doğrulaması geçti. Kendi denemesi üzerinden puan/süre/cevap değişmezliği tekrar sınanmalıdır. |

Bu inceleme yeni bir uygulama düzeltmesi yapıldığı veya bu dokuz testin artık geçtiği anlamına gelmez. Testlerin güvenlik beklentileri kaldırılmaz; yalnız oluşturdukları kayıtlarla ilişkilendirilir veya başlangıç/sonrası değişmezliği doğrulanır.

## 5. Tablo geçişinin gerçek etkisi

Rappasoft paketi kullanıcının kararıyla `34` içinde kaldırıldı. `19` geçiş haritasındaki **31 tablo sınıfı** referans olarak duruyor. Bunların kullandığı paketin base class/column/filter API'si mevcut vendor'da yoktur. İlgili panel liste sayfalarının500 vermesi şu an doğrulanmış işlev eksikliğidir; yeni PHP image'a geçmek bunu gidermez.

Hedef düzen değişmedi: OneUI + Blade + Bootstrap Table + sunucudan JSON `total/rows`; Vue.js eklenmez. Mevcut filtreler, izin/öğretmen kapsamı, sıralama, sayfalama, işlemler ve dış aktarma korunmalıdır. Referans Lara Football kaynak düzenidir. Cache anahtarı dil/izin-kapsam/filtre/sort/sayfa boyutu/sayfa değerlerini ayırmalıdır. Paket geri ekleme bu kontrolün kararı değildir.

## 6. Sıradaki düzeltme planı

1. Önce test hazırlığını mevcut testing kayıtlarıyla çalışacak hâle getirmek: dil kaydını yeniden kullanmak, gerekli önkoşullarını belirlemek, kendi deneme/kayıt kapsamını seçmek ve liste beklentilerini izole etmek. Kayıt silme, geniş fresh/seed veya ana DB işlemi çözüm olarak uygulanmaz.
2. Yalnız etkilenen test sınıflarını çalıştırmak. Bir sonraki engel Rappasoft ise bunu yeni fixture hatası olarak gizlememek; testin asıl senaryosunun hâlâ erişilemediğini kaydetmek.
3. Onaylı Bootstrap Table geçişini tablo aileleri için dar paketlerle uygulamak; her pakette JSON sözleşmesi, izin/kapsam, filtre/sıralama/sayfalama ve ilgili ekranı kontrol etmek.
4. Karşılıklar ve dar doğrulamalar tamamlandıktan sonra gerekli genel koşuyu yapmak. Mevcut başarısız suite'i aynı durumda tekrar tekrar çalıştırmak yararlı değildir.

Kullanıcıya sonraki iş sırası soruldu. Bu kayıt hazırlanırken yeni test veya uygulama düzeltmesine başlanmadı. Güncel talimatların yeni bulguları otomatik düzeltmeme sınırı uygulanır.

## 7. Bu tur değişikliği ve kapsam

Yalnız bu belge, `Codex/index.html`, `AGENTS.md` ve `35` içindeki güncel genel test bağlantısı değişti. Uygulama/test kaynakları, Composer/Docker ayarları, ana SQL, dil kayıtları ve QA fixture'ları değiştirilmedi. Mevcut test suite'i bir kere ve PWA suite'i bir kere çalıştırıldı; salt okunur triage yapıldı.

**Karar:** Laravel13/PHP8.5 kurulumu çalışıyor; mevcut uygulamanın bütün testleri geçmiyor. Tablo karşılıkları, test hazırlığı ve hedef sürüm kabulü bitmeden proje canlıya hazır ilan edilmez.
