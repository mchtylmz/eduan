# Log maskeleme ve Bilgi Girişleri tablo geçişi

Tarih: 6 Ekim 2026. Kullanıcı loglarda parola/token bilgisinin alan adı korunarak yıldızlarla gösterilmesini ve diğer ekranların Bootstrap Table'a aktarılmasını onayladı. Bu kayıt kaynak uygulaması ve statik kontrol kanıtıdır; PHPUnit/tarayıcı kabulü henüz yoktur.

## 1. Netleşen ortam kararı

- Eduan normal localhost HTTP80/MySQL3306 portlarında çalışır; geçici8081/3307 seçilmedi.
- Lara Football aynı portlarda açıkken Eduan başlatılmaz; diğer projenin servisleri durdurulmaz.
- Kullanıcı uygulamayı açtıktan sonra gerekli dar test ve tarayıcı kontrolü yapılır. Bu tur Docker/app başlatılmadı.
- Testler yalnız ayrı testing DB'dedir. Ana DB'ye yazı, migration, seed, temizlik veya gerçek mail gönderimi yapılmadı.
- 41 belgesindeki iki karar yanıtlandı; bu iki konu için yanıt beklenmiyor.

## 2. Log değerlerinin maskelenmesi

`LogDataMasker` alan adını korur, hassas değeri `***` yapar. Büyük/küçük harf ve ayraç farkları normalize edilir; password/passwd/pwd/token/secret/api-key/private-key/access-key/authorization adlarını içeren alanlar kapsanır. Null hassas değer de yıldızlarla gösterilir.

- İç içe diziler ve dizi/nesne içeren JSON stringleri aynı kuralla taranır.
- Setting kaydındaki `name` hassas bir alan adıysa `value` maskelenir. Normal name/value ve hassas olmayan içerik korunur.
- Eski log JSON'u, sonraki aynı nesneye ait log ve güncel DB satırı fallback'i aynı yardımcıdan geçer.
- Maskeleme yalnız Blade metninde değildir: Livewire public currentData durumu ve çağrılabilir jsonData/currentData dönüşleri de maskelidir. Raw okuyucu protected'tır; public durum Locked, hydrate tekrar maskeler.
- Mavi değişiklik işareti raw değerler sunucuda karşılaştırılarak hesaplanır. İstemciye yalnız değişen alan adları aktarılır; iki taraf da `***` görünse bile parola değişimi işaretlenir.
- Dizi karşılaştırmasında JSON metnine dönüştürme kullanılır; eski scalar string karşılaştırması korunur. Recursive maske için gerekli bu sınır, eski array-to-string karşılaştırma uyarısını da önler.
- Kalıcı log kaydı veya kaynak kullanıcı/setting değeri değiştirilmez. Bu bir ekran/Livewire yanıtı maskelemesidir; depolanan geçmişin temizlenmesi değildir.
- Salt okunur bağımsız sınır ve yama incelemesinde somut kalan raw değer çıkışı bulunmadı. Çalışan Livewire entegrasyonu henüz sınanmadı; tam güvenlik onayı verilmez.

## 3. Yedi listenin eşleştirmesi

| Menü | JSON route adı | Başlangıç sırası | Korunan filtre/akış |
|---|---|---|---|
| Bloglar | admin.blogs.getData | published_at DESC | Dil, durum; readonly detay ve ayrı düzenleme |
| Diğer Sayfalar | admin.pages.getData | id ASC | Filtre bandı yok; çevrilen başlık/link; SYSTEM düzenleme/silme kapalı |
| SSS | admin.pages.faqs.getData | id ASC | Dil, durum; pages izinleri |
| İletişim | admin.contacts.getData | created_at DESC | Dil, çoklu okundu; mevcut ayrı detay/yanıt sayfası |
| Aboneler | admin.newsletters.getData | id DESC | E-posta/IP araması; token JSON'a girmez |
| Diller | admin.languages.getData | id ASC | Durum, varsayılan işareti; ayrı Çeviriler bağlantısı |
| Değerlendirmeler | admin.exams.getReviewsData | created_at DESC | Kullanıcı, test, çoklu okundu; varsa sabit exam/user kapsamı |

Route adları AutoRoute kaynak düzeninden türetilmiştir; runtime route:list/HTTP kontrolü yapılmadı. Değerlendirmeler endpoint yolu kaynak sözleşmesinde `/admin/exams/reviews-data` olur; çalışan ortamda ayrıca doğrulanır.

## 4. Görünüm ve mevcut davranış

- Ortak OneUI/Blade/Bootstrap Table server JSON total/rows bileşeni kullanılır; Vue veya yeni Composer paketi eklenmez.
- Kolonlar, filtreler, yenile/search/sort/pagination ve mevcut ekleme/düzenleme sayfaları korunur. Sayfa/controller izinleri cache okumadan önce denetlenir.
- Hücre/başlık15px, işlemler16px; filtre alanları16px/44px ve sağ panel width100%/max840px mevcut ortak standardıdır.
- Bilgilendirme listelerinin dar tablosu800px; İletişim/Değerlendirmeler1100px minimum ile kendi alanında kayar.
- İletişim araması ad/e-posta/telefon/okul alanlarına genişletildi; okundu araması da korunur.
- Diğer Sayfalar'ın translatable başlık/link sıralaması panel locale'inin JSON alanına göre yapılır.
- İletişim ve değerlendirmeler Görüntüle bağlantısı mevcut detay sayfasına gider: okundu işaretleme ve yanıt formu burada kalır. Bunlara yalnız genel offcanvas açarak bu iş kuralı atlatılmaz.
- Değerlendirmelerde mevcut exams:view route şartına ek exams-reviews:view kontrolü vardır; silme exams-reviews:delete ister.
- SSS pages izinleriyle, diller languages izinleriyle çalışır. Dil silmenin son dil/varsayılan dil/JSON dosyası kuralları korunur; bu tur silme çalıştırılmadı.
- İletişimde mevcut ayrı contacts:delete izni yoktur; mevcut contacts:view işlem kontrolü ve yalnız okunmuş satır için silme düğmesi korunur. Yeni izin politikası uydurulmadı.
- Yedi listeye kullanıcı kart görünümü eklenmedi. Kullanıcı kartı önceki41 kapsamındadır.

## 5. Cache ve asset

Liste JSON60 saniye, seçenekler1 saat; Yenile bypass, izin/locale/actor/scope/revision ve doğrulanmış pagination/search/sort/filter anahtarı ortak standardıdır. Users/exams/has_read çoklu değerleri de normalize edilir.

Blog/Page/Faq/Contact/Newsletter/ExamReview/Exam Eloquent değişimleri ilgili liste veya seçenek revision'ını yeniler. Kullanıcı değişimi değerlendirme listesini, dil/çeviri/izin değişimi yeni yedi listeyi de etkiler. Doğrudan SQL insert observer çalıştırmaz; eski global resetCache/nonproduction flush hâlâ vardır, bu tur kaldırılmadı. Ölçülmüş hız kazanımı iddiası yoktur.

Ortak server-table.js Görüntüle için isteğe bağlı view_url, çeviri için translate_url destekler; önceki detay düğmesi fallback olarak korunur. JS değişikliği nedeniyle `.env APP_VERSION`1.1.3→1.1.4 artırıldı; portlar değiştirilmedi. Config/deployment cache yenilemesi yapılmadı.

## 6. Dar doğrulama ve bekleyen testler

- İlgili25 PHP dosyası PHP8.5 syntax ve Pint kontrolünden geçti.
- server-table.js Node syntax ve ilgili diff/whitespace kontrolü geçti.
- Yeni LogDataMaskerTest iki unit test içerir: recursive/JSON/alias maskeleme; normal içerik/named setting koruma.
- SettingsMenuTableTest yeni senaryo HTML, Livewire snapshot, public dönüş/effects, raw DB fallback ve parola değişimi işaretini kapsar.
- InformationMenuTableTest16 vaka hazırlar: yedi sayfanın JSON/search/pagination/render kontrolü; yedi invalid sort/izin/cache kontrolü; contact filtre/invalidation; SYSTEM sayfa işlemlerinin reddi.
- Bu yeni testler **çalıştırılmadı**. Önceki41 kart/log karşılaştırma testleri de runtime bekler. Önceki40 başarılı sonuç bu yamayı doğrulamaz.
- Uygulama açıldığında yalnız aşağıdaki dar grup çalıştırılır; test bağlantısı testing olarak doğrulanır. Başarısızlık olursa ilgili hata çözülmeden başarı ilan edilmez.

```sh
docker compose exec -T laravel.test vendor/bin/phpunit --filter 'InformationMenuTableTest|LogDataMaskerTest|SettingsMenuTableTest::test_log_(secrets_are_masked_in_html_state_and_public_method_returns|comparison_aligns_old_and_current_fields_and_marks_changes)|BootstrapTablePilotTest::test_user_detail_metrics_follow_account_type'
```

Sonrasında gerekli yedi sayfanın JSON/render, filtre/arama/sayfalama, izinli düğmeler ve mobil taşma kontrolü yapılır. Gerçek gönderim/silme veya ana DB ile QA yapılmaz. İlgili eski CRUD testleri ayrı dar regression adımıdır; bütün suite bu tur çalıştırılmadı.

## 7. Yalnız kayda alınan adaylar

- Aboneler sayfasının mevcut Gönder düğmesi lessons:add iznine bakıyor; seed'de newsletter:send var. Bu başka bir izin bulgusudur; bu tur otomatik değiştirilmedi.
- İletişim silmede ayrı izin bulunmaması ve has_read düğme koşulunun PHP işleminde tekrar doğrulanmaması mevcut politikadır; kullanıcı kararı olmadan yeni silme standardı uygulanmadı.
- 40 bölüm7'deki SeasonForm işlem izni ve LogDetailTable bağımsız izin/geçersiz ID/bozuk JSON adayları bu maskeleme onayıyla otomatik düzeltilmedi. Recursive array karşılaştırması yalnız maskelemenin gerektirdiği sınırlı düzeltmedir.
- Kaynakta kalan Rappasoft DataTableComponent sınıfı19'dur: ilk31 sınıfın3'ü38,2'si40,7'si42 ile taşındı. Henüz bütün proje tabloları veya tüm testler başarılı değildir.

## 8. Bu aşamanın dosyaları

- `app/Helpers/LogDataMasker.php`, `app/Livewire/Settings/LogDetailTable.php`: log maskeleme ve raw diff alan adları.
- `app/Http/Controllers/Backend/{Blog,Page,Faq,Contact,Newsletter,Language,Exam}Controller.php`: yedi JSON endpointi.
- `app/Livewire/{Blogs/BlogsTable,Pages/PagesTable,Faqs/FaqTable,Contacts/ContactTable,Newsletters/NewsletterTable,Languages/LanguageTable,Exams/ReviewsTable}.php`: eski tablo yerine mevcut Component/ServerTableActions.
- `resources/views/livewire/backend/{blogs,pages,faqs,contacts,newsletter,languages,reviews}/server-table.blade.php`: yedi liste görünümü.
- `resources/views/backend/{blogs/index,pages/all,faqs/index,contacts/index,newsletters/index,languages/index,exams/reviews}.blade.php`: ortak management asset/kapsam bağlama.
- `public/backend/assets/server-table.js`, `resources/views/components/admin/table-messages.blade.php`, `lang/{nl,en,tr}/tables.php`, `.env`: bağlantılı aksiyonlar, etiketler ve asset sürümü.
- `app/Providers/AppServiceProvider.php`, `app/Observers/TableCacheObserver.php`, `app/Helpers/TableCache.php`: yeni liste invalidation ve normalize filtre anahtarları.
- `tests/Unit/LogDataMaskerTest.php`, `tests/Feature/InformationMenuTableTest.php`, `tests/Feature/SettingsMenuTableTest.php`: hazırlanmış dar testler.
- `AGENTS.md`, standartlar16, önceki41 karar durumu, bu42 kayıt ve `Codex/index.html`: güncel rehber.

Önceki fazların çalışma ağacı/vendor değişiklikleri bu aşamanın değişiklik listesi değildir.

## 9. Log kullanıcı filtresi — 6 Ekim

Kullanıcı talimatıyla yalnız Loglar kullanıcı filtresinin açılır listesi en fazla10 seçenek gösterir; kalan seçenekler liste içinde kaydırılır. Ortak filter-select bileşeni dropdownSize parametresini Bootstrap Select data-size ayarına aktarır; diğer filtrelerin auto varsayılanı korunur. Arama, Tümünü Seç ve seçimleri kaldırma aynı kalır. CSS/JS değişmedi, APP_VERSION artırılmadı. Dar kaynak kontrolü geçti; tarayıcı kontrolü uygulama açıldığında yapılır.
