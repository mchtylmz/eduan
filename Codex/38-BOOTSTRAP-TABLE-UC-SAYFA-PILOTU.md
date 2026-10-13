# Bootstrap Table — ders, konu ve kullanıcı pilotu

**Sonraki görsel revizyon:** [39-TABLO-YAZI-BOYUTU-VE-DETAY-TASARIMI.md](39-TABLO-YAZI-BOYUTU-VE-DETAY-TASARIMI.md). Tablo yazıları15px/işlem16px, readonly detay tasarımı yenilendi;3 test/124 doğrulama geçti. Alt veri kapsamı cevap bekliyor. Aşağıdaki38 kaydı ilk pilotun kanıtıdır.

6 Ekim 2026. Kullanıcı üç örnek sayfanın Lara Football düzeninde taşınmasını istedi; JSON cache ve salt okunur sağ detay paneli kararlarını ayrıca onayladı. **Bu üç liste taşındı.** Diğer 28 Rappasoft tablo sınıfı bu teslimin kapsamı değildir. Bu belge `19` araştırmasının ve `37` test hazırlığının sonraki uygulama kaydıdır.

## 1. Kesin kararlar ve sınır

- OneUI, Bootstrap 5, jQuery, Blade ve Livewire 4 devam eder; Vue.js eklenmedi.
- Satırlar Bootstrap Table tarafından GET JSON ile sunucudan sayfa sayfa alınır. Rappasoft bu üç sınıfta kullanılmaz.
- Liste JSON cache'i 60 saniye; filtre seçenekleri 1 saattir. Yenile düğmesi liste cache'ini atlar, yeni cevabı cache'e yazar.
- Kayıt/rol/dil/çeviri değişiklikleri hedef tablo sürüm anahtarını değiştirir. Yeni kod global flush veya cache tags gerektirmez.
- Görüntüle salt okunur off-canvas açar. Düzenleme mevcut ayrı sayfada kalır.
- Ön yüz, diğer panel tabloları, paketler, SQL şeması ve seedler değiştirilmedi.
- Ana yerel hypotenuse DB testlerde kullanılmadı. PHPUnit ve geçici browser web ortamı yalnız `testing` bağlantısındadır.
- Migration/fresh/seed/import/QA kayıt temizliği, gerçek mail veya deployment yapılmadı. Browser silme onayında Vazgeç seçildi; DB silme doğrulaması transaction içinde geri alınan PHPUnit fixture'larıyla yapıldı.

## 2. Lara Football kaynağı ve kütüphane

Salt okunur referanslar:

- `/Users/mucahit/Desktop/LARAVEL/lara_football/resources/views/components/admin/server-table.blade.php`
- Aynı projedeki `filter-bar.blade.php` ve `resources/views/livewire/users/user-index.blade.php`.
- Dashboard ortak tablo CSS'i ve `app/Http/Controllers/Admin/UserController.php` JSON yaklaşımı.

Filtre bandı, sol Yenile/sağ Arama, dikey işlem düğmeleri, `.85rem` hücre iç boşluğu ve `1.45` line-height, sağ 760px detay paneli referans alınır. Eduan'ın mevcut kolonları ve filtreleri korunur; Lara'nın futbol alanları, iki dil seçimi veya farklı yetki adları kopyalanmaz. Eduan sayfa boyutu 15; seçenekler 15/25/50/100'dür.

Yerel Bootstrap Table **1.22.6** assetleri kullanıldı; yeni CDN/npm/Composer paketi yüklenmedi. [Resmî table options](https://bootstrap-table.com/docs/api/table-options/) ve [methods](https://bootstrap-table.com/docs/api/methods/) incelendi; `sidePagination: server`, `total/rows`, `queryParams` ve `refresh` yerel sürümde tarayıcıyla doğrulandı. Resmî güncel sürümün bütün seçeneklerinin eski assette bulunduğu varsayılmaz.

## 3. Üç sayfanın sözleşmesi

| Sayfa | Filtreler | Satır bilgileri | Detay |
|---|---|---|---|
| Dersler | İçerik dili, durum | Kod, ad, konu/soru sayısı, sıra, görüntüleme, durum, işlemler | Aynı temel bilgiler ve güncel ilişkili sayılar |
| Konular | Çoklu ders, içerik dili, durum | Ders, kod, başlık, soru sayısı, sıra, görüntüleme, durum, işlemler | İlişkili ders ve soru sayısı dahil temel bilgiler |
| Kullanıcılar | Çoklu rol, çoklu e-posta onayı, durum, son giriş ve kayıt tarihi başlangıç/bitişi | Rol, kullanıcı adı, ad, soyad, onay, durum, son giriş, işlemler | E-posta, kayıt tarihi, öğrenci/öğretmen sayısı dahil temel bilgiler |

Konular içerik dili filtresi `topics.title` JSON alanına uygulanır. Başlıkların/gösterilen çevirinin dili arayüz dilidir; içerik dili filtresi çevirinin varlığını seçer. Varsayılan konu filtresi mevcut examlanguageCode ayarıdır. Ders seçenekleri mevcut aktif ders kapsamıyla sunulur.

Kullanıcı ekranının mevcut controller başlığı/type parametresi değiştirilmedi; mevcut listesi bütün yetkili kullanıcı kapsamını kullanır. Öğretmen yalnız kendi öğrencilerini listeler. Yeni detayda da öğretmen kapsamı her çağrıda yeniden denetlenir.

Tarih filtreleri `Y-m-d` kabul eder. Başlangıç gününün 00:00'ı dahil; bitiş gününden sonraki günün 00:00'ı hariçtir. Böylece seçilen bitiş gününün tamamı dahildir. Yalnız bitiş tarihinin seçilmesi de desteklenir. Başlangıç varsa bitiş ondan önce olamaz.

## 4. Route ve JSON

AutoRoute mevcut public `getData` metotlarını açar; yeni routes dosyası veya özel router eklenmedi:

| Ad | GET URL | Gerekli kapılar |
|---|---|---|
| `admin.lessons.getData` | `/admin/lessons/data` | auth, locale, dashboard:access, lessons:view |
| `admin.topics.getData` | `/admin/topics/data` | auth, locale, dashboard:access, topics:view |
| `admin.users.getData` | `/admin/users/data` | auth, locale, dashboard:access, users:view |

URL öneki mevcut panel config'ine bağlıdır. Route middleware'lerine ek olarak controller izin kontrolünü cache okumadan önce yapar.

`ServerTable::parameters` limit 1–100, offset 0–1.000.000, search en fazla 250 karakter, sort açık kolon listesi, order asc/desc, refresh boolean ve sayfa filtrelerini doğrular. Serbest SQL kolon adı kabul edilmez. Limit/offset integer, search trim olarak normalize edilir. Varsayılanlar limit15/offset0/orderasc; ders/konu sort, kullanıcı username sırasıdır.

`ServerTable::page` aynı filtrelenmiş Builder'dan count ve rows üretir. SQL offset/limit uygulanır, eşit sıralama değerinde model ID ikinci sıradır. Konunun ders sıralaması ilişkili dersin arayüz dilindeki adını alt sorguyla kullanır. Liste ilişkileri eager loading/withCount ile alınır. Cache'e Eloquent nesneleri değil, görünür alanların array karşılığı yazılır.

Yanıt `total` ve `rows` taşır. `table` sabit sayfa kimliği, `id`, görünen alanlar, izinli `edit_url` ve `can_delete` istemci formatter'larını besler. Kullanıcı liste JSON'u e-posta, parola, remember_token veya meta döndürmez; e-posta yalnız yetkili detay isteğinde görünür. HTTP yanıtı `Cache-Control: private, no-store` taşır; Laravel sunucu cache'i HTTP/PWA cache'inden ayrıdır.

## 5. Cache anahtarı ve geçersizleştirme

`TableCache` anahtar bağlamı:

1. Tablo adı ve tablo revision UUID'si.
2. Uygulama asset/version ayarı.
3. Arayüz dili.
4. Oturumlu kullanıcı ID'si.
5. Sıralanmış gerçek izin adları.
6. Normalize limit/offset/search/sort/order ve filtreler.
7. Kullanıcı listesinde öğretmenin her istekte yeniden bulunan sıralı öğrenci ID'leri.

Çoklu ders/rol/onay değerleri anahtar için sıralanır; aynı seçim sırasının farklı olması ayrı liste cache'i üretmez. JSON TTL60; seçenek TTL3600. Sayfa/filtre/search anahtarları TTL ile düşer; revision anahtarları tablo başına sabittir. Refresh normal anahtarın loader'ını yeniden çalıştırıp değerini günceller.

`TableCacheObserver` saved/deleted/restored olayları:

| Model tabloları | Geçersizleştirilen kapsam |
|---|---|
| lessons, topics, questions | lessons, topics, options |
| users, roles | users, options |
| languages, translations, permissions | lessons, topics, users, options |

`UpdateRoleAction` syncRoles sonrası users/options sürümünü değiştirir. Öğrenci atamasının JSON kapsamı key içindeki güncel ID listesiyle değişir. İzin değişimi key'deki gerçek izin listesiyle ayrılır; erişim önce denetlenir. Öğrenci/öğretmen detay sayıları cache'den değil güncel sorgudan gelir.

**Mevcut geniş temizliğin sınırı:** AppServiceProvider production dışında her boot'ta cache flush yapıyor. Loggable/resetCache ve mevcut saatlik temizleme de daha geniş cache'i etkileyebilir. Bunlar bu pilotta kaldırılmadı. Production dışı farklı HTTP istekleri arasında cache hit beklemek bu nedenle geçerli hız kanıtı değildir. PHPUnit cache testleri aynı uygulama yaşamında array driver üzerinde hit/refresh/invalidation sözleşmesini doğrular. Yeni cache kodu hedeflidir; bütün uygulamanın artık yalnız hedefli temizlendiği söylenmez. Query Builder toplu/raw güncellemeleri model observer'ı tetiklemez; bu tür ileriki geliştirmede ilgili invalidate açıkça çağrılmalıdır.

## 6. Livewire, işlem ve detay sınırları

Üç tablo artık normal Livewire Component'tir; Rappasoft ve liste lazy attribute'u çıkarıldı. Filtre ve tablo DOM'u wire:ignore; listede AJAX kullanılır. Livewire yalnız detay/silme işlemlerini ve çevirili seçeneklerin ilk HTML'ini yönetir.

`ServerTableActions` mount/işlem/render sırasında dashboard ve kaynak view iznini kontrol eder. Silmede kaynak delete izni de gerekir. Detay ID Locked'tır; gösterme çağrısında scoped Builder ile kayıt bulunur. Kullanıcı detayının açık olması öğretmen atama değişiminden sonra kaydı görme yetkisi vermez.

Mevcut bağlı konu/soru silme engelleri ve kullanıcı kendi hesabını kaldırma engeli korunur. Öğretmen yabancı öğrenciyi silme çağrısı yapamaz. Başarılı silmede refresh-server-table olayı yalnız ilgili tablonun cache'i atlayan yenilemesini başlatır. Silme gerçek soft-delete/model davranışını kullanır; toplu silme eklenmedi.

Panel readonly bilgiler ve izin varsa mevcut Düzenle bağlantısını içerir. Bootstrap Offcanvas genişliği min(760px,100vw), mobilde ekran genişliğidir. Kapatma detailId'yi temizler. Form veya ilişki düzenleme off-canvas'a taşınmadı.

## 7. İstemci ve görünüm standardı

- Ortak script her sayfa root'unu bir kez başlatır; DOMContentLoaded/livewire:navigated desteklenir.
- Search/sort/pagination ve filtreler URL'ye yazılır; yeniden açılışta seçili değerler geri yüklenir. lang gibi diğer query değerleri korunur.
- Filtrele/Temizle yalnız tabloyu ilk sayfadan yeniler; native form, çoklu selectpicker ve Flatpickr beraber sıfırlanır. Arama ayrı kontrol olarak kalır.
- Yenile solda, arama sağda; filtre bandı üstte, işlem düğmeleri satırda dikeydir.
- Kullanıcı metni/rol/durum/action etiketi ve URL HTML kaçırılarak render edilir. Yalnız sunucu sabit badge sınıfları ve izinli linkler kullanılır.
- Loading/boş/istek hatası ayrıdır. load-error çevirili hata kutusunu gösterir; sonraki başarılı yükleme kutuyu kaldırır.
- Silme SweetAlert onayı ister; Vazgeç sunucu silme çağrısı yapmaz. Düğme çağrı boyunca kapalıdır.
- Dar ekranda filtreler alt alta, arama tam genişlik; geniş kolonlar yalnız tablo alanında yatay kaydırılır. Mobilde tüm kolonların tek ekrana sıkışması hedef değildir. Kullanıcı tablosu masaüstünde de mevcut geniş sekiz kolon nedeniyle yatay kaydırma gerektirebilir.
- Yeni detay başlıkları, tarih sınırları, ilgili sayılar, kapatma/temizleme ve yükleme hata metni `lang/{nl,en,tr}/tables.php` içinde; Bootstrap Table locale nl-NL/en-US/tr-TR olarak eşlenir. Mevcut genel çeviri mekanizması korunur.

## 8. Dar PHP doğrulaması

Gerçek container PHP8.5.8 / PHPUnit13.4.1, phpunit.xml DB_DATABASE=testing, DatabaseTransactions. İlk geliştirme koşularındaki route adı/Blade @json ve doğrudan Livewire fixture locale sorunları pilot kapsamında giderildi. Testler hata beklentisini 500/skip'e çevirmedi.

Yeni `BootstrapTablePilotTest`: **16 test metodu**; üç sayfa/yerel asset, üç dil yeni etiketler, cache sonrası izin reddi, ders pagination/sort/count, konu dil/ilişki/sort, geçersiz girdi, kullanıcı rol/onay/tarih, öğretmen atama kapsamı, özel alan dışlama, cache hit/refresh/model update, rol sync invalidation, actor/locale/tablo ayrımı, detay kapsamı, Locked ID, bağlı kayıt silme ve yetkisiz/self/foreign silme.

Mevcut testlerden yalnız şu 10 senaryo eklendi: PageRender panel lessons/topics/users ve hydrated lessons/topics (5); PanelPermissionMatrix bu üç liste giriş kapısı (3); PanelCrud ders/konu CRUD/ilişki ve duplicate/geçersiz parent (2).

**Birleşik koşu: 26 test / 260 doğrulama geçti; error/failure/skip yok.** Son küçük etiket genişletmesinden sonra yalnız etiket/render/detay tekrarlandı: **3 test / 121 doğrulama geçti**. Bunlar önceki 26'nın içindeki üç testtir; 29 farklı test diye toplanmaz. Etiket testi son halinde 33 çeviri doğrulaması yapar.

Log: `/tmp/eduan-bootstrap-pilot-final.log`, container JUnit `/tmp/eduan-bootstrap-pilot-final.xml`; son etiket logu `/tmp/eduan-bootstrap-pilot-labels.log`. 16 PHP dosyasında Pint geçti; son beş ilgili dosyada Pint --test geçti. JS node --check, son değişen beş PHP dosyasında syntax ve ilgili git diff --check temiz.

## 9. Gerçek Chrome kontrolü

Geçici PHP8.5.7 web sunucusu localhost:8765 üzerinde ayrı testing DB/cache/session/storage ile çalıştı; Docker DB kullanıldı. Yeni browser bağımlılığı kurulmadan mevcut Playwright/Chrome kullanıldı. Masaüstü1440×1000 ve mobil390×844 kontrol edildi.

**Yetki kanıtının sınırı:** Mevcut sentetik QA hesabının eksik pilot view/update/delete izinleri yalnız /tmp harness içindeki Gate::before ile UI kontrolüne açıldı; DB rol/izin ataması yapılmadı. Bu browser UI doğrulamasıdır. Gerçek izin ve öğretmen sahipliği yukarıdaki PHPUnit testlerinde normal uygulama kapılarıyla sınandı. Browser girişinin mevcut sentetik kullanıcının last_login_at bilgisini normal akışta güncellemesi dışında QA verisi düzenlenmedi/silinmedi.

Tarayıcı kanıtları:

| Akış | Gözlem |
|---|---|
| Dersler | İlk sayfa15; ikinci sayfa9 ve ilk kayıt qa1005-16; arama1; pasif filtre0; reset geri yükledi |
| Sayfa URL'sini yeniden açma | limit15/offset15/codeasc ile ikinci sayfa9 doğru geri geldi |
| Yenile | JSON isteğinde refresh=1 ve HTTP200 |
| Konular | Çoklu ders filtresi, ders adı sırası; bağlı ders ve iki soru; readonly detay aç/kapat |
| Silme Vazgeç | Konu kaydı kaldı; onay iptalinde silme yapılmadı |
| Kullanıcılar | Rol+onaysız filtre0; reset1; aynı gün son giriş aralığı1; readonly ilişkili sayılar |
| Mobil | HTML scrollWidth390; geniş tablo kendi alanında; kullanıcı detay genişliği390 |
| Dil | NL gerçek ekranlar; EN plugin en-US ve Lesson details. Testing dil seçeneklerinde yalnız nl/en var; lang=tr NL'ye döndü. TR gerçek ekran kabulü yapılmadı; TR etiket/asset eşlemesi PHP/yerel dosya doğrulamasıyla sınırlı |

Normal pilot akışında pageerror/console error-warning ve HTTP400+ listesi boştu. Giriş öncesindeki ayrı favicon/resource uyarısı pilot sonuç listesine dahil değildir. İstek hatası/geri dönüş için kasıtlı 500 yanıtı ayrıca test edildi; Flemenkçe hata göründü, sonraki Yenile kutuyu gizledi ve15 satırı geri yükledi. bu negatif kontroldeki beklenen hata başarılı normal akışla karıştırılmaz.

Screenshot ve loglar `/tmp/eduan-bootstrap-pilot-1006/` altında: lessons/topics/users-desktop.png; lessons/topics/users-mobile.png; users-detail-mobile.png; lessons/topics/users-detail.png; lessons-en-detail.png; lessons/users-mobile-table.png; browser-errors.json ve kasıtlı500 için browser-negative-errors.json. Bunlar geçici yerel kanıttır; Git'e özel kullanıcı verisi veya SQL snapshot eklenmedi.

## 10. Bu tur değişen dosyalar

PHP: app/Helpers/ServerTable.php; app/Helpers/TableCache.php; app/Observers/TableCacheObserver.php; app/Traits/ServerTableActions.php; app/Providers/AppServiceProvider.php; app/Actions/Users/UpdateRoleAction.php; app/Http/Controllers/Backend/{LessonController,TopicController,UsersController}.php; app/Livewire/Lessons/LessonTable.php; app/Livewire/Topics/TopicTable.php; app/Livewire/Users/UserTable.php.

Blade: resources/views/components/admin/{server-table,filter-bar,filter-select,table-detail,table-messages}.blade.php; resources/views/livewire/backend/{lessons,topics,users}/server-table.blade.php.

Assets: public/backend/assets/server-table.css; public/backend/assets/server-table.js. Çeviriler: lang/{nl,en,tr}/tables.php. Test: tests/Feature/BootstrapTablePilotTest.php.

Rehber: AGENTS.md; Codex/16-PROJE-STANDARTLARI.md; Codex/19-RAPPASOFT-BOOTSTRAP-TABLE-GECIS.md; Codex/37-TEST-HAZIRLIGI-VE-KAYIT-KAPSAMI-DUZELTMESI.md; bu38 ve Codex/index.html. Başka fazların mevcut worktree değişiklikleri bu dosya listesine dahil değildir.

## 11. Sonraki kabul sınırı

Kullanıcı önce bu üç örneği görecek; diğer tablolar için ardından yönlendirecek. Kalan **28 extends DataTableComponent sınıfı** Rappasoft paketi yokken çalışır kabul edilmez. Önceki genel koşudaki bütün hataların kapandığı veya bütün proje testlerinin geçtiği sonucu bu 26 dar senaryodan çıkarılmaz; genel suite bu tur çalıştırılmadı.

Kalan ekranların kolon/filtre/eylem/öğretmen/export/modal sözleşmesi kendi kaynağından çıkarılmalı. İlk üç ekranın UI revizyonu, geniş cache temizliğinin azaltılması ve canlı performans ölçümü ayrı kapsamdır. Hız artışı için eski/yeni eş veri HTTP ve SQL ölçümü olmadan yüzde veya toplam hız iddiası verilmez.
