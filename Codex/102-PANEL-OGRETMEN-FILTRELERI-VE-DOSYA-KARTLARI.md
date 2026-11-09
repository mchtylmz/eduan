# Panel: öğretmen hesabı, çalışma filtreleri ve dosya kartları

9 Kasım 2026. Kullanıcının kapsamı ve dört net yanıtı uygulandı: Dosyalar varsayılan kart, 1200px genişlik yalnız yönetim paneli, öğretmen filtresi yalnız yönetici, “Çalışma mevcut” için mevcut hesap korunur. Yeni bağımlılık veya migration yok.

## 1. Öğretmen hesabına geçiş

- Hesaba geçiş mevcut POST/onay ve aynı oturum mekanizmasını kullanır. Hedef öğretmen `dashboard:access` iznine sahipse site hesap menüsü ve hesap sayfasında Yönetim paneli bağlantısı görünür.
- `admin.*` için eski genel geçiş engeli kaldırıldı. Panel route, Livewire form/eylem izinleri ve öğretmenin bağlı öğrenci kapsamı hedef hesabın kendi yetkileriyle çalışır. Kaynak yöneticinin izinleri hedefe aktarılmaz.
- Panel üstünde Siteyi Görüntüle bağlantısının hemen solunda turuncu geri dön düğmesi vardır. CSRF ve mevcut dönüş token’ı ile POST eder; yönetici ve kullanıcı listesi geri gelir.
- Kaynak yönetici/parola/aktiflik, hedef uygunluğu ve oturum kanıtı her istekte korunur. İç içe geçiş yasaktır. Geçici hesaba push cihazı bağlama/çıkarma engeli sürer. Panel izni olmayan hedef için panel bağlantısı/erişim açılmaz.

## 2. Çalışma Takibi

Çalışma haftası, Öğretmen, Durum ve Öğrenci durumu sıralaması kullanılır. Görünür tarih alanı ve select’ler 44px/16px’tir; tablet iki, mobil tek sütundur.

Öğretmen alanı yalnız `user-type:admin` iznine sahip kullanıcıya görünür ve sunucuda da bu izin aranır. Seçenekler rol adından değil, doğrudan veya rol üzerinden `user-type:teacher` (Öğretmen/Okul) iznine sahip kullanıcılardan gelir. Öğretmen-öğrenci bağı `teacher_student` üzerinden filtrelenir. Öğretmenin kendi öğrenci sınırı her durumda korunur; sahte/geçersiz öğretmen parametresi reddedilir.

Tablo JSON’u, tam Excel çıktısı ve mevcut iletişim oluşturucusunun alıcıları ortak sorguyu kullanır. İletişim adımları/gönderim sırasında seçilen öğretmen grubu ve alıcı bağları yeniden kontrol edilir; açılıştan sonra kaldırılan öğrenciye gönderime devam edilmez. Mevcut aktif alıcı/500 sınırı ve kanal izinleri değişmez. Geri bağlantısı öğretmen filtresini korur.

| Yeni filtre | Kapsam |
|---|---|
| Hedef girenler | Soru/sayfa/dakika hedeflerinden en az biri > 0 |
| Soru sayısı / okunan sayfa / çalışma saati girenler | İlgili hedef > 0 |
| Gerçekleşme %25 / %50 / %75 altında | Mevcut genel gerçekleşme ilgili eşikten küçük |
| Gerçekleşme %50 / %70 / %100 üzerinde | Mevcut genel gerçekleşme ilgili eşikten büyük |

Eşit değerler “altında/üzerinde” filtrelerine dahil edilmez; hedefi olmayanın oranı boş kalır. Eski %50 altı seçeneği korunur. Çalışma bulunmuyor ve Çalışma mevcut art arda gelir. **Çalışma mevcut eski kapsamındadır:** gerçekleşme ≥ %50 ve soru + sayfa + dakika > 0. Hedef kolonuna sıralama eklenmedi.

## 3. Panel ve Dosyalar görünümü

- Yalnız backend sağ offcanvas’ları `width:100%`, `max-width:1200px` kullanır. Çalışma panelinin eski 640px özel sınırı ve dosya panelinin inline 640px sınırı kaldırıldı. Ön yüzün hedef/çalışma panellerindeki 640px değiştirilmedi.
- Dosyalar ortak Blade + Bootstrap Table + server JSON standardında kalır. İlk açılışta kart, Yenile yanında kart/tablo geçişi vardır; arama, tür, sıralama, sayfalama ve filtreli URL korunur.
- Kart ve tabloda başlık, tür, yüklenme tarihi, durum ve işlemler görünür. Özgün ad/boyut/yükleyen gizlidir; veritabanı, JSON metadata ve mevcut özgün ad araması korunur. Hazır olmayan dosyada görüntüle/kopyala yoktur; silme izni ve onayı aynıdır.
- Kart metinleri ve bağlantı attribute’ları escape edilir; tür ikonu ve durum rengi sabit seçeneklerden gelir.
- Yükleme panelinde başlık örneği, dört dilde Dosya seç, dosya adı ve izinli tür/boyut bilgisi bulunur. Gerçek required dosya input’u klavye ile kullanılabilir. İlerleme çubuğu boş formda gizlidir; aktarımda yüzde/aşama gösterir, başarıda 100% ve link kalır. Hata, yeni seçim ve panel kapatma taslak durumunu tutarlı sıfırlar; yükleme sürerken kapanma engeli korunur.
- Yerel/R2 yükleme, açık onaylı yerel fallback, dosya doğrulaması ve public görüntüleyici değiştirilmedi. Yerel asset/PWA sürümü **1.1.44**; canlı ENV’de yayın sırasında APP_VERSION aynı yapılmalıdır.

## 4. Dar kabul kanıtı

- Son tek koşu: **96 PHP testi / 865 assertion**, PHP8.5.8 Docker/testing. Dosyalar, hesap geçişi, çalışma paneli/yeni filtreler, iletişim sihirbazı ve PWA komşu testleri. Hedef sıfır/boş/pozitif, altı yüzde sınırı, geçersiz/SQL benzeri öğretmen ID, rol üzerinden öğretmen seçimi, Excel içeriği/alıcı eşleşmesi, ilişki kaldırılması, öğretmen yetkisiz işlemleri ve geri dönüş dahildir.
- **23 Node testi**: dosya/kart escape ve eylemler, çalışma formatları/hafta takvimi, geçiş onayı ve PWA güvenliği. İki değişen JS syntax kontrolü, değişen PHP’lerde Pint ve iki dil dosyasının dört dil anahtar eşliği geçti.
- Browser plugin bulunmadığı için kurulu Playwright + gerçek Chrome kullanıldı. Gerçek Laravel HTTP kernel ve Livewire aynı testing transaction köprüsünden çalıştırıldı; gerçek yerel multipart dosya aktarımı fake public diske yapıldı. Üretim proxy/web sunucusu kanıtı değildir.
- Dört dil × 1440/390: **16 ekran/akış kontrolü**; kart varsayılanı/tablo geçişi, yerelleştirilmiş seçici, başlangıçta gizli progress, 1200px/tam mobil genişlik, eşit filtre yüksekliği/font ve filtreli tablo/Excel/iletişim URL’leri geçti. Ekran görüntüleri görsel incelendi; sayfa taşması yok.
- İki ek uçtan uca akış: gerçek yerel yükle/100%/link, kopyala, iptal+onaylı sil, arama/sayfa2/tür; gerçek hesap geçiş POST’u → site menüsü → öğretmen paneli/kendi öğrencileri → turuncu dönüş → yönetici. JS istisnası **0**, beklenmeyen5xx **0**.
- İlk testte yanlış `admin.users.data` adı `admin.users.getData` olarak düzeltildi; Excel testinde gerçek BinaryFileResponse okundu. Tarayıcı fixture’ının eksik yükle/sil izinleri tamamlandı ve aramaya gerçek keyup gönderildi. Bunlar için uygulama izinleri/arama mekanizması gevşetilmedi. Geçen dil/genişlik matrisi tekrar koşulmadan yalnız kalan etkileşimler çalıştırıldı.
- Ana ve testing DB’de 10 ilgili tablonun önce/sonra sayı/hash’leri aynı: users/settings/tests/tests_results/leagues/league_results/source_files/teacher_student/study_weekly_targets/study_activity_entries. Ana381kullanıcı/5dosya korunur; test örnekleri transaction sonunda geri alındı. Dış R2/SMTP/push veya production işlemi yok; diğer proje servisleri değiştirilmedi.

## 5. Yayın sınırı

Yerel dar kabul başarılıdır. Bu değişiklikler önceki v4ZIP’ine eklenmedi ve arşivler değiştirilmedi. Yeni yayın paketi ayrı hazırlanmalı; mevcut canlı `.env`, uploads, storage ve diğer kalıcı dosyalar korunmalı. Yeni ENV anahtarı/izin/migration/cron değişikliği yok; yalnız sürüm1.1.44, yeni/değişen asset ve normal cache/OPcache yenilemesi ile canlı giriş/öğretmen/panel/dosya kabulü gerekir. Bu çalışma canlıya aktarım değildir.


## 6. Öğretmen ve Durum için aramalı tek seçim — 9 Kasım

Kullanıcı son tarifinde her iki alanı tek seçimli, arama kutulu selectpicker istedi; toplu seçim/kaldırma düğmeleri olmayacak. Ortak `x-admin.filter-select` bileşenine varsayılanı false olan `searchable` seçeneği eklendi; yalnız çalışma listesindeki Öğretmen ve Durum için açıldı. Öğrenci durumu native select kalır; mevcut çoklu Yetkiler filtresi değişmez. Tümü boş değer seçeneği, öğretmen için yönetici sınırı ve scalar teacher_id/state URL/JSON/Excel/iletişim kapsamı korunur.

Yeni tek seçimli listelerin URL’den geri yükleme ve sıfırlamasında `selectpicker('val', value)` kullanılır. Mevcut plugin’in refresh sırasında aynı seçenekleri yeniden eklemesi ve render’ın önbellekteki eski etiketi göstermesi böylece bu alanlarda önlenir. Çoklu listelerin mevcut refresh yolu korunur; vendor değiştirilmedi. Butonlar diğer alanlarla44px/16px’tir. Asset/PWA sürümü1.1.45; canlı yayın sırasında APP_VERSION buna eşitlenmelidir.

Dar kabul:3PHP/54assert; aynı senaryolarda scope/Excel/alıcı eşliği, searchable-single DOM, toplu seçim kapalı, native öğrenci durumu ve array parametre reddi geçti.17Node (hafta/format/PWA), JSsyntax ve değişen testin Pint kontrolü başarılı. Browser plugin olmadığından gerçek Chrome/Playwright + testing transaction HTTP köprüsü kullanıldı.36 geçici öğretmenle TR/NL/EN/FR ×1440/390: arama/tek seçim, aynı seçili etiket, filtreli tablo/Excel URL, refresh ve sıfırlama geçti; ayrıca kullanıcı Yetkiler çoklu seçim/arama/actions-box korunur.8ekran+1komşu kabulde JS0/5xx0/yatay taşma0. Ekranlar görsel incelendi. Ana veriler/dış servisler/canlıya işlem yok; test verileri geri alındı. İlk tarayıcı kabulünde görülen çift seçenek ve eski buton etiketi dar olarak giderildi; son sekizli kabul temizdir.


## 7. Filtre ölçülerinin tek merkezde standardizasyonu — 9 Kasım

Kullanıcı, çalışma filtresindeki yüksekliğin ve yukarıda kalan selectpicker metninin Kullanıcılar/Testler referansıyla aynı olmasını ve tek merkezden yönetilmesini istedi. Gerçek Chrome ölçümünde Kullanıcılar Yetkiler ve Testler Konular38px/16px, Çalışma haftası/öğretmen/durum/öğrenci durumu44px/16px çıktı; iki çalışma picker’ının metin merkezi düğme merkezinden3px yukarıdaydı.

`public/backend/assets/server-table.css` ortak `.admin-filter-bar[data-table-filters]` kontrol ölçülerinin tek kaynağıdır:38px yükseklik,16px yazı,1.5 satır yüksekliği ve kontrol ölçüsüne göre simetrik dikey padding. Input/tarih, native select, selectpicker ana düğmesi ve Filtrele/Temizle aynı ölçüde kalır. `study-panel.css` içindeki üç yerel kontrol yükseklik kuralı kaldırıldı; yalnız çalışma listesinin kolon/responsive yerleşimi orada kalır. `admin-management` içindeki filtreler de ortak ölçüyü izler; filtre bandı dışındaki ayar/form alanları değiştirilmedi. Önceki44px filtre notları bu kararla tarihseldir. CSS/PWA sürümü1.1.46.

Dar kabul: gerçek Laravel testing transaction köprüsü/Chrome ile Çalışma Takibi, Kullanıcılar, Testler, Dosyalar ×1440/768/390 =12ekran. Bütün görünen ana kontroller38px/16px, dikey padding eşit; picker metin merkez sapması0px. Çalışma öğretmen araması/tek seçimi, filtreli tablo/Excel URL’si ve sıfırlama; Yetkiler ve Konular çoklu/arama/actions-box davranışı korundu. NL koyu görünüm ek ziyareti ve ekran görüntüleri incelendi. JS0/5xx0/yatay taşma0;12Node PWA testi geçti. Yalnız CSS ve asset sürümü değiştiği için PHP/DB suite’i tekrar çalıştırılmadı. Ana DB’de kalıcı değişiklik, migration, dış servis veya canlı aktarım yok; mevcut yayın arşivleri değişmedi.
