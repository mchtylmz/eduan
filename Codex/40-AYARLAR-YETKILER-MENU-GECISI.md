# Ayarlar & Yetkiler menüsü — Bootstrap Table ve form görünümü

Tarih: 6 Ekim 2026. Kullanıcı Dönemler, Yetkiler, Ayarlar ve Loglar ekranlarını mevcut içerik/iş mantığını koruyarak pilot tablo standardına getirmeyi istedi. Laravel/PHP/paket veya şema güncellemesi bu aşamanın konusu değildir.

## 1. Ekranlar ve korunan davranış

| Ekran | Uygulanan görünüm | Korunan yapı |
|---|---|---|
| Dönemler | Her dönem için başlıklı kart, açıklama, belirgin ad/durum/tarih alanları, mobil düzen | Livewire SeasonForm; ekleme/silme/tümünü kaydet; mevcut doğrulama ve sonuç dönem job'u |
| Yetkiler | Bootstrap Table, arama/yenile/sayfalama/sıralama, sağ readonly detay | Rol grupları, kullanıcı sayısı, ayrı ekle/düzenle sayfaları ve izin matrisi |
| Ayarlar | İkonlu sekme menüsü, aktif bölüm başlığı, okunabilir kontrol/etiketler | 13 tab, form action/name/değerler, çok dilli içerik, TinyMCE ve Dropify |
| Loglar | Bootstrap Table, kullanıcı/işlem filtreleri, readonly sağ detay | Sekiz kolon ve eski/güncel kayıt karşılaştırması; düzenleme/silme eklenmedi |

Dönemler bir tablo listesi değildir; sırf standardizasyon için JSON tabloya dönüştürülmedi. Menü isimleri, setting anahtarları, route/form akışları, kayıt içerikleri ve yetki grupları değiştirilmedi. OneUI + Bootstrap5 + jQuery devam eder; Vue eklenmedi.

## 2. Yeni JSON liste sözleşmesi

- Yetkiler: `admin.roles.getData`, `/admin/roles/data`; name/users_count allowlist sort, varsayılan name ASC, ad araması, SQL count/pagination.
- Loglar: `admin.settings.getLogsData`, `/admin/settings/logs-data`; varsayılan id DESC; ad/soyad/işlem/agent/browser/IP/tarih sort ve arama.
- Her ikisi `total` ve `rows` döndürür; ilk sayfa15 ve sayfa seçenekleri15/25/50/100 mevcut ortak bileşenden gelir.
- Log kullanıcı filtresi çoklu, en fazla100 geçerli/tekil ID; işlem filtresi mevcut helper seçenekleriyle doğrulanır.
- Log ham `data` alanı liste JSON'una gönderilmez. Karşılaştırma yalnız görüntüle panelinde mevcut bileşenle açılır.
- `dashboard:access` ve ilgili `roles:view` / `settings:view` izinleri cache okunmadan denetlenir; rol silme `roles:delete` ister.
- Detay kimliği Locked; showDetail ve render kayıt/izin erişimini yeniden kontrol eder. Düzenle mevcut ayrı sayfada kalır.
- Öğretmen log kullanıcı seçenekleri mevcut kendi öğrenci kapsamını korur; eski log satır sorgusunun genel görünürlüğü daraltılmadı. Yeni rol/sahiplik politikası kararı verilmedi.

## 3. Cache ve yenileme

Önceki pilotun JSON60 saniye / seçenek1 saat / Yenile bypass düzeni bu iki listeye uygulandı. Anahtar locale/actor/izin/sorgu/scope/revision içerir; istemciye private/no-store yanıtı gelir. Log seçenekleri öğretmenin öğrenci ID kapsamını içerir.

- Kullanıcı değişimi users/roles/logs/options sürümlerini değiştirir.
- Rol değişimi roles/users/options sürümlerini değiştirir.
- Role permission sync ve user role sync tamamlandıktan sonra ilgili invalidation ayrıca uygulanır; pivot değişimi eski kullanıcı sayısı/grup cache'i bırakmaz.
- Eloquent Log saved/deleted/restored logs sürümünü değiştirir.
- Dil/çeviri/izin değişimi beş yeni liste ve seçenekleri etkiler.

Mevcut nonproduction boot flush, Loggable/resetCache geniş temizliği bu tur kaldırılmadı. Loggable'ın doğrudan `Log::insert` yazımı Eloquent observer'ı çalıştırmaz; ayrı resetCache olmayan bu tür bir yazım JSON TTL boyunca eski liste bırakabilir, Yenile bypass eder. Üretim hız kazanımı ölçülmedi; hedefli cache kodu tüm global temizliğin çözüldüğü anlamına gelmez.

## 4. Form ve görünüm standardı

- Ortak `admin-management` kapsamı: alanlar16px, en az44px; etiketler belirgin; textarea/color alanları kendi ölçüsüyle korunur.
- Tablo başlık/hücre15px, işlem16px ve readonly detay önceki pilot bileşenlerinden gelir.
- Yetki ekle/düzenle ekranları aynı POST ve permissions[] matrisiyle devam eder; izin grupları kart sınırları ve daha rahat seçim aralığı kazanır.
- Dönem kartında ad/durum ve başlangıç/bitiş ayrı satırlar; her kontrolün benzersiz ID/label bağlantısı vardır. Tarih formatıY-m-d, dil panel arayüzüdür.
- Dönem iş kuralları: tarihlerin eşit/tekrarlı olamaması, end after start, StatusEnum ve UpdateSeasonIdForResults dispatch mevcut PHP'de korunur.
- Ayarlar13 bölüm: general, ai, popup, logo, style, cover, exam, league, test, email, emailTemplate, user, contact.
- Bu13 tabın kaynak dosyaları değiştirilmedi; değerler/settings anahtarları ve editor/uploader davranışları aynı kalır.
- Masaüstünde275px sekme menüsü ve esnek içerik; mobilde iki sütunlu menü, altta tam genişlik form. Uzun Flemenkçe adlar satıra bölünür.
- Kaydet bölümü belirgin alt sınırla ayrılır; cache temizle eski onaylı eylem olarak korunur, QA sırasında çalıştırılmaz.
- Yeni boş dönem/rol/log detay etiketleri nl/en/tr management.php içindedir.
- CSS/JS değişikliği nedeniyle yerel `.env` APP_VERSION1.1.1→1.1.2 oldu. Ortak management CSS aynı app.version query değeriyle yüklenir. Canlıya deploy/config-cache yenilemesi yapılmadı.

## 5. Dar test kanıtı

PHP8.5.8, PHPUnit13.4.1; yalnız ayrı testing DB ve DatabaseTransactions. Queue fake; normal TestCase dış HTTP'yi engeller. Ana hypotenuse DB'ye migration/seed/veri işlemi yapılmadı.

```sh
docker compose exec -T laravel.test vendor/bin/phpunit --filter 'SettingsMenuTableTest|PanelModuleCrudTest::test_(role|season)|BootstrapTablePilotTest::test_(cache_separates_actor_locale_and_table_and_targeted_invalidation_keeps_other_entries|details_open_and_user_details_recheck_teacher_scope)' --log-junit /tmp/eduan-management.xml
```

**13 test /134 doğrulama geçti.** Yeni7 test; mevcut4 rol/dönem CRUD/doğrulama; ortak trait/cache için mevcut2 pilot testi. JSON sayfalama/count/grup, log filtre/sort, bozuk giriş, cache öncesi izin iptali/guest, role sync invalidation ve readonly detay kapsanır.

Son boş durum/Blade düzenlemesinin ardından yalnız iki render testi yeniden çalıştırıldı: **2 test /52 doğrulama geçti**; bunlar13 testin alt kümesidir. Tüm proje testleri/build çalıştırılmadı. Pint ilgili13 PHP dosyasında başarılı; server-table.js Node syntax ve ilgili diff whitespace kontrolü başarılı.

Kanıtlar: `/tmp/eduan-management-tests.log`, `/tmp/eduan-management-final-render.log`, container `/tmp/eduan-management.xml`. Geçici dosyalar kalıcı teslim/yayın dosyaları değildir.

## 6. Gerçek tarayıcı kanıtı

Gerçek Chrome/Playwright; ayrı testing web harness,1440×1000 masaüstü ve390×844 mobil. Sentetik QA hesabına yalnız UI harness içinde geçici Gate izinleri; normal yetkilendirme kanıtı PHPUnit'ten gelir. Ana DB/admin hesabı kullanılmadı; form kaydet/sil/yükle/cache temizle/gerçek mail yapılmadı.

- Yetkiler ve Loglar dolu tablo render, arama→boş sonuç→geri dönüş, refresh=1 HTTP200, readonly detay aç/kapat geçti.
- İki listenin mobil genişliği390px; geniş kolonlar kendi tablo alanında kayar. Yetkiler başlık/Ekle dar ekran sarma düzeltildi.
- Dönem testing verisinde kayıt yoktu; kaydetmeden oluşturulan taslak kartlarla benzersiz tarih ID/Flatpickr başlatma/takvim açma ve ikinci kart ekleme kontrol edildi. Son mobil bağlama kontrolünde ikinci taslak eklenince ilk kartın başlangıcı boş, önce seçilmiş2040-01-01 tarihi ikinci kartta kaldı; dört Flatpickr kontrolü de hazırdı.
- Bütün13 ayar sekmesi HTTP200 ve named form alanlarıyla açıldı. Alan sayıları general20, ai6, popup6, logo3, style6, cover9, exam9, league9, test5, email7, emailTemplate12, user1, contact8. Bunlar setting sayısı değildir; hidden/CSRF alanları da sayılır.
- Popup iki mevcut dil editörüyle açıldı; logo Dropify alanları hazır. Genel/popup/logo masaüstü ve mobil kontrol edildi, uzun etiket taşması giderildi.
- Rol ekleme izin matrisi masaüstünde açıldı; mevcut POST kaydı browser'da gönderilmedi, ilgili CRUD testi kapsar.
- Son ayar/browser akışında JavaScript pageerror ve HTTP400+ yanıt yok. Mevcut ana tema sidebar durumu viewport değiştirilince kalabildiği için mobil kontrol temiz sayfa yüklemesiyle yapıldı; global tema değiştirilmedi.
- Ekran görüntüleri `/tmp/eduan-management-1006/`; settings form içeriği ayrıca mobile-content görüntülerinde. SMTP/AI anahtar değerleri kayda çıkarılmadı.

Bu kanıt gerçek offline PWA, bütün roller, bütün form yüklemeleri veya canlı hosting kabulü değildir.

## 7. Kaynakta ayrıca görülen inceleme adayları — otomatik düzeltilmedi

Kullanıcı kuralına göre kapsam dışı mevcut sorun adayları yalnız kaydedildi; ayrı dar doğrulama/onay gerekir:

1. `app/Livewire/Season/SeasonForm.php`: create/save/delete metotlarında sunucu tarafı seasons izin kontrolü yok; view'daki @can düğme gizler. Public seasons içindeki kayıt ID'lerinin işlem kapsamı ayrıca sınanmalıdır. Bu tur PHP form iş mantığı değiştirilmedi.
2. `app/Livewire/Settings/LogDetailTable.php`: bağımsız bileşen mount'ta izin/kayıt-null kontrolü yok; yeni liste paneli erişimi kontrol eder ama bileşenin kendi sınırı ayrı değerlendirilmelidir. View kullanıcı ilişkisini doğrudan kullanır; silinmiş kullanıcı/bozuk JSON/nested array_diff_assoc karşılaştırması hata adaylarıdır. Mevcut normal detail akışı test ve browser'da geçti; uç durumlar henüz yeniden üretilmedi.

Bu adayları kapsamlı güvenlik taraması veya doğrulanmış açık diye sunma. Kullanıcının otomatik başka sorun düzeltmeme talimatı gereği bu aşamada ilgili PHP/view dosyaları değiştirilmedi.

## 8. Bu aşamada değişen dosyalar

- `.env`: yalnız APP_VERSION1.1.2.
- `app/Http/Controllers/Backend/{RolesController,SettingsController}.php`: iki JSON endpoint.
- `app/Livewire/Roles/RoleTable.php`, `app/Livewire/Settings/LogsTable.php`: iki eski tablo karşılığı.
- `app/Traits/ServerTableActions.php`: permission resource'tan ayrı detay panel adı.
- `app/Observers/TableCacheObserver.php`, `app/Providers/AppServiceProvider.php`: rol/log liste invalidation ve Log observer.
- `app/Actions/Roles/CreateOrUpdateAction.php`, `app/Actions/Users/UpdateRoleAction.php`: pivot sonrası invalidation.
- `public/backend/assets/server-table.js`, `public/backend/assets/admin-management.css`: tablo formunun opsiyonelliği/log formatter ve yeni kapsamlı form CSS'i.
- `resources/views/components/admin/{server-table,table-detail,management-assets}.blade.php`: varsayılan sort yönü, detail slot, ortak management CSS.
- `resources/views/livewire/backend/roles/server-table.blade.php`, `resources/views/livewire/backend/settings/logs-server-table.blade.php`: yeni listeler/paneller.
- `resources/views/livewire/backend/season/season-form.blade.php`: dönem kartları.
- `resources/views/backend/roles/{index,create,edit}.blade.php`, `resources/views/backend/season/index.blade.php`, `resources/views/backend/settings/{index,logs}.blade.php`: ekran wrapper/görünümü.
- `lang/{nl,en,tr}/management.php`: üç dilde yeni etiketler.
- `tests/Feature/SettingsMenuTableTest.php`: ilgili7 senaryo.
- `AGENTS.md`, `Codex/16-PROJE-STANDARTLARI.md`, bu kayıt ve `Codex/index.html`: rehber.

Önceki çalışma ağacı/vendor değişiklikleri bu aşamaya mal edilmez. Başlangıç31 eski Rappasoft sınıfından pilot3+bu aşama2 taşındı; **26 sınıf hâlâ aktarılmadı**. Bütün proje testleri başarılı veya canlıya hazır denmez.
