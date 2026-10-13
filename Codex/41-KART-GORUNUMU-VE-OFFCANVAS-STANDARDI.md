# Kart görünümü ve sağ offcanvas standardı

Tarih: 6 Ekim 2026. Kullanıcı Dönemler'i daha sade ayrı kartlara ayırmayı, rol türü seçimini yükseltmeyi, panelde tüm sağ offcanvas'ları840px/mobil100% standardına getirmeyi, log karşılaştırmasını tasarlamayı ve Kullanıcılar'da Lara Football custom view örneğini uygulamayı istedi.

## 1. Uygulanan tasarım

- Dönem formu aynı toplu Kaydet/Livewire save akışıyla kalır. Masaüstünde iki bağımsız kart,991px altında tek sütun; her kartın ad/durum/tarih/çıkar alanları ve benzersiz ID/wire:key düzeni korunur. Dönem PHP iş kuralları değiştirilmedi.
- Yetki ekle/düzenle formundaki mevcut üç rol türünün label/seçim alanı en az58px yüksekliğe çıktı; permissions[] değerleri ve radio davranışı korunur.
- Panel app.css içinde genel `.offcanvas.offcanvas-end`: width100%, max-width840px; masaüstünde840px, daha dar viewport'ta ekran kadar. Eski admin-table-detail760px override'ı kaldırıldı.
- Genel Livewire offcanvas ön yüzde de kullanılır; ortak Blade değiştirilmedi. Panel app.css width kuralı !important ile mevcut OneUI w-sm-75/w-sm-50 utility'lerini sağ yönde geçersiz kılar. Üst/alt/sol ve ön yüz değişmez.
- Log metadata ayrı bilgi kartlarına; eski/güncel değerler aynı alan satırında hizalanan karşılaştırmaya taşındı. Mavi değişiklik işareti mevcut diff metodu ile korunur. Mevcut karşılaştırma/tarih/DB veri kaynağı değiştirilmedi.
- Güncel log verisi varsa Yalnız değişen alanlar anahtarı gösterilir; varsayılan tüm alanlar görünür. Mobilde alan/eski/güncel alt alta gelir. İçerik Blade escape ile yazılır.
- Kullanıcı detayında ADMIN için ilişki sayacı yok; TEACHER için yalnız öğrenci sayısı; USER için yalnız öğretmen sayısı. RoleType izinleri mevcut Spatie can kontrolüyle okunur. Yönetici önceliklidir; öğretmen ise USER izni de olsa öğretmen sayacı gösterilmez. Türü olmayan kayıt ilişki sayacı göstermez.
- Son6 Ekim kullanıcı kararıyla kullanıcı listesi varsayılan custom view/kart olarak açılır; Yenile'nin hemen yanında kart/tablo görünüm düğmesi vardır. Diğer sayfalara kart görünümü eklenmedi.
- Kartta ad/soyad/username, roller, durum, e-posta doğrulama ve son giriş; aynı Görüntüle/Düzenle/Kaldır izinli işlemler vardır. RoleType ilişki sayaçları kart listesine eklenmedi; detay paneline aittir.
- Kartlar yeni/veri çoğaltan API kullanmaz: aynı total/rows ve Bootstrap Table server-side sayfalama/search/sort/filter/cache sonuçları render edilir. Kart görünümü sadece mevcut sayfanın rows'larını gösterir; bütün kayıtları istemciye almaz.
- Kart host ortak bileşenin wire:ignore tablo alanında kalır, dış kullanıcı kökü wire:ignore.self kullanır; detay Livewire render'ı görünüm sınıfını/JS kart DOM'unu yeniden üretmez. Görünüm anahtarı yeni HTTP isteği üretmez; filtre/arama/sayfa/yenile Bootstrap Table üzerinden devam eder. Loading/empty/error durumları kendi kart alanında gösterilir; hata sonrası başarılı yanıt toparlanır.
- CSS/JS değişikliği sonrası yerel APP_VERSION1.1.3; app.css ve server-table asset query aynı config değerini kullanır. Deploy/config-cache işlemi yapılmadı.

## 2. Referans ve yazım standardı

Lara Football `resources/views/admin/notifications/manual/index.blade.php` salt okunur incelendi: custom host pagination önüne yerleşir; post-body/load-success rows ile render eder; refresh/page/search/sort loading ve hata durumu; Yenile yanında toggle; tablo varsayılanı CSS görünürlük sınıfıyla değişir. Eduan'da bu yaklaşım mevcut server-table.js'ye yalnız data-user-cards bulunan sayfalar için eklendi. Vue veya yeni Composer/JavaScript paketi eklenmedi. Son kullanıcı kararıyla Eduan kullanıcı sayfasının başlangıç sınıfı is-card-view oldu; tabloya geçiş düğmesi korunur. Bootstrap Table custom-view extension'ın kendi slice varsayımını kullanmadık; ayrı JSON/page yaklaşımı Lara referansındaki gibi korundu.

## 3. Sonraki42 aşamasında yanıtlanan içerik kararı

Kullanıcının verdiği screenshot mevcut log karşılaştırmasında password hash'i de içeriyor. Parola/token/secret alanlarını her iki tarafta gizlemek görsel talepten ayrı bir içerik değişikliğidir; ayrı soru gönderildi. Bu aşamada yanıt gelmeden gizleme uygulanmadı. Sonraki42 aşamasında kullanıcı alan adı korunarak *** gösterilmesini onayladı; maskeleme uygulandı. Eski SeasonForm action izinleri ve bağımsız LogDetailTable yetki/null/nested array adayları40 bölüm7'de kalır; bu görsel kapsamda otomatik PHP düzeltmesi yapılmadı.

## 4. Test ve ortam durumu

- UserTable.php ve iki ilgili test dosyasının PHP8.5 syntax kontrolü geçti.
- server-table.js Node syntax geçti; ilgili tracked diff whitespace kontrolü geçti.
- İlgili9 PHP/çeviri/test dosyasında host PHP8.5 Pint geçti; yalnız bir testin closure formatı düzeltildi.
- Yeni dar senaryolar: üç rol türünün gerçek öğrenci/öğretmen ilişkisi olsa da yalnız uygun sayacı göstermesi; kullanıcı sayfasındaki kart host render; eski/güncel log alanları ve değişen/değişmeyen satır işaretleri.
- Henüz bu aşamada PHPUnit veya browser başarısı ilan edilmez. İlk Docker exec testi, mevcut Eduan PHP servisi durmuş olduğu için başlamadı. Mevcut servisleri start etme denemesi3306 port çakışmasıyla durdu; Lara Football PHP/MySQL şu an80/3306 portlarını kullanır.
- Diğer proje konteynerleri durdurulmadı; Eduan'ı geçici8081/3307'de çalıştırma veya kullanıcının Lara Football'u durdurması seçenekleri soruldu. Sonraki42 aşamasında kullanıcı alternatif portları reddetti: Eduan normal80/3306, Lara Football açıkken başlatılmayacak; kullanıcı açtıktan sonra dar test yapılacak. Ana DB/testing verisi silinmedi, migration/seed yapılmadı.
- Tarayıcı kontrolü için frontend-testing-debugging skill ve mevcut Chrome/Playwright fallback uygulanacaktır; Browser plugin mevcut değildir. Gerçek QA yalnız ayrı testing web ortamında yapılır, ana DB kullanılmaz.

## 5. Bu aşamada değişen dosyalar

- `.env`: yalnız APP_VERSION1.1.3.
- `public/backend/assets/app.css`, `server-table.css`, `server-table.js`, `admin-management.css`: genel sağ panel, kartlar, toggle ve form/log görünümü.
- `resources/views/backend/roles/form.blade.php`: rol seçimi CSS kapsamı.
- `resources/views/livewire/backend/season/season-form.blade.php`: dönem kart grid'i.
- `resources/views/livewire/backend/settings/log-detail-table.blade.php`: metadata/hizalı karşılaştırma/değişen alan anahtarı.
- `app/Livewire/Users/UserTable.php`, `resources/views/livewire/backend/users/server-table.blade.php`: tür bazlı ilişki sayaçları ve kullanıcı kart host'u.
- `resources/views/components/admin/{server-table,table-messages}.blade.php`, `lang/{nl,en,tr}/{tables,management}.php`: yeni kart/log arayüz etiketleri.
- `tests/Feature/{BootstrapTablePilotTest,SettingsMenuTableTest}.php`: iki yeni dar mantık/render testi.
- `AGENTS.md`, `Codex/16-PROJE-STANDARTLARI.md`, bu kayıt ve `Codex/index.html`: yeni standart/kanıt.

Önceki aşamaların çalışma ağacı değişiklikleri bu listeye dahil edilmez; tüm proje testi veya canlı güvenlik kabulü değildir.

## 6. Varsayılan custom view kararı

Son kullanıcı talimatıyla yalnız `resources/views/livewire/backend/users/server-table.blade.php` köküne `is-card-view` başlangıç sınıfı eklendi. Mevcut JS bu sınıftan kart host görünürlüğünü ve tabloya geçiş düğmesini belirler. CSS/JS değişmediği için asset sürümü artırılmadı. Dar kaynak kontrolü başlangıç sınıfı, tablo gizleme, kart gösterme ve toggle bağını doğruladı; runtime/browser testi Eduan açılana kadar bekler.
