# Tablo alt alanı ve filtre yerleşimi

6 Ekim 2026. Kullanıcı ekran görüntülerindeki “Sayfa başına15 kayıt” alanını kaldırmayı, panel filtrelerinin kendi kolonlarını tam doldurmasını ve tarih alanlarında yer tutucu/hizalama düzeltmesini istedi.

## 1. Netleşen kapsam

Kullanıcı soruya **yalnız kayıt adedi seçicisini kaldır** yanıtını verdi. Kayıt aralığı/toplam bilgisi ve önceki/sonraki/sayfa düğmeleri korunur. Sayfalama kapatılmaz, bütün kayıtlar tek JSON'a yüklenmez.

Düzenleme ortak Bootstrap Table panel bileşenleri ve mevcut panel filtrelerine aittir. Diğer form alanları, frontend, kalan Rappasoft listeleri ve veri modeli değişmedi.

## 2. Ortak tablo altı

`components/admin/server-table.blade.php` Bootstrap Table'ın `paginationParts` seçeneğini `pageInfo,pageList` olarak ayarlar; `pageSize` bölümü oluşturulmaz. CSS ile metin saklama yerine mevcut plugin API'si kullanılır. Bütün bu bileşeni kullanan ana/nested tablolar aynı davranışı alır.

Ana başlangıç15, nested5 kayıt standardı korunur. Eski URL'deki desteklenen limit değerleri ve backend limit/offset doğrulaması değiştirilmedi. Sağ sayfa düğmeleri çalışmaya devam eder.

## 3. Filtre alanı standardı

Yeni `x-admin.filter-date` id/name/label/value ve isteğe bağlı time alır. Etiket ayrı bir `label[for]`; input kendi kolonu içinde ayrı kardeş öğedir. Önceki label içine input koyma, etiketi daraltıyor ve etiket margin'ini inputun altına taşıyarak select alanlarıyla hizalamayı bozuyordu.

Lig2, soru istatistiği2, AI kullanım1/değerlendirme1 ve kullanıcı5 tarih alanı bu bileşene taşındı. Kullanıcının dört tarih sınırının ad/id'leri, istatistiğin başlangıç/bitiş değerleri ve JSON filtre adları korunur. Sıralama kolon/yön select'leri de ayrı label/select düzenine alındı; AI/Soru köklerine benzersiz id prefix'i verildi.

Ortak `.admin-filter-bar` CSS: label block, uzun metin kırılabilir; control/select/bootstrap-select width100%, max-width100%, min-width0. Sadece bu filtre bandındaki alanlara uygulanır. Bootstrap row/col ve responsive düzen devam eder; bütün panel form inputlarına global kural yazılmaz.

Tarih yer tutucuları `tables.choose_date` ve `tables.choose_datetime` ile nl/en/tr içindedir. Flatpickr mevcut panel locale'ini kullanır. Mobilde native input yerine aynı Flatpickr alanını kullanması için `disableMobile` tanımlıdır; yer tutucu ve saniye özelliği korunur. Gerçek telefon kontrolü yapılmadı.

Kullanıcı `registered_since` alanı native datetime-local yerine aynı Flatpickr bileşenini kullanır. Saat+saniye ve24saat gösterimi açıktır. Gönderilen değer **Y-m-d\TH:i:s** sözleşmesini korur: örnek `2026-10-01T12:34:56`. Yeni UI placeholder'ı görünür; backend tarih doğrulaması, kesin zaman filtresi ve KPI linklerinin değeri değiştirilmedi.

## 4. Cache kontrolü

İlgili veritabanı filtre seçenekleri mevcut `TableCache::options` üzerinden1 saat; JSON listeleri60 saniye ve Yenile bypass kullanır. Öğretmen/actor/locale/izin ve ilgili model revision kapsamı önceki47/45 standardındadır. Bu alanlarda eksik yeni cache katmanı görülmedi; fazladan cache veya global flush eklenmedi. Enum ve logTypes gibi sabit seçenekler için DB cache gerekmiyor; tarih inputunun seçilen değeri tarayıcı form durumudur.

Eski FilterHelper metotları kendi mevcut cache davranışlarını korur. Bu görsel kontrol bütün cache/rol matrisi veya başka legacy yardımcıların yeniden denetimi değildir.

## 5. Dar doğrulama

- PHP8.5.7/PHPUnit13.4.1, yalnız ayrı testing DB/transaction: dört ekran render/izin ve tarih filtre/kart sıralama senaryosu **5 test/26 doğrulama geçti**. Yeni test veya genel suite/build eklenmedi.
- nl/en/tr çeviri dosyalarında Pint geçti. Blade bileşenleri gerçek render/Chrome ile kontrol edildi.
- Browser plugin bulunmadığı için mevcut Chrome/Playwright ve izole testing harness127.0.0.1:8765 kullanıldı; normal uygulama portları/ana DB değişmedi.
- Sekiz ekran: ligler, kullanıcılar, soru istatistiği, AI kullanım/oy, AI, sorular ve dersler. Her ortak tabloda pageInfo/pageList var; pageSize seçicisi DOM'da yok.
- Görünür filtrelerin genişliği kendi kolon içerik genişliğiyle eşleşti.11 tarih alanında yer tutucu, ayrı label/id ve doğru yerleşim kontrol edildi.
- Lig takvimi açıldı/kapandı; kullanıcı kesin tarih+saniye seçimi birebir ISO metnine dönüştü, ilgili JSON isteği200; Reset alanı temizledi.
- Gerçek ders tablosunda birinci sayfadan ikinciye geçildi: offset15 ve pageNumber2, kayıt aralığı bilgisi korunuyor.
- Masaüstü1440×1000 ve390×844 document taşma kontrolü; ilgili console/pageerror yok. QA geçici scriptindeki görünmez selectpicker arama inputunu ölçme beklentisi düzeltildi; ürün hatası değildir.
- Görseller /tmp altında; Yeni fixture oluşturulmadı; ana DB değişmedi. Testing giriş akışı mevcut QA hesabının giriş zamanını/logunu güncelleyebilir; fixture temizliği yapılmadı. Geçici foreground harness kontrol sonunda durduruldu. Bu genel site veya gerçek cihaz/üretim kabulü değildir.

## 6. Değişen dosyalar

- `resources/views/components/admin/server-table.blade.php`: pageSize seçicisini kaldıran paginationParts.
- Yeni `resources/views/components/admin/filter-date.blade.php`: ortak tarih/saat filtresi.
- `resources/views/components/admin/table-sort.blade.php`: ayrı label/select ve benzersiz id.
- `resources/views/livewire/backend/leagues/server-table.blade.php`.
- `resources/views/livewire/backend/stats/questions-server-table.blade.php`.
- `resources/views/livewire/backend/ai/usages-server-table.blade.php`, `votes-server-table.blade.php`, `server-table.blade.php`.
- `resources/views/livewire/backend/questions/server-table.blade.php`.
- `resources/views/livewire/backend/users/server-table.blade.php`.
- `public/backend/assets/server-table.css`: yalnız filtre kolon genişliği/label.
- `lang/nl/tables.php`, `lang/en/tables.php`, `lang/tr/tables.php`.
- Yerel `.env`: APP_VERSION **1.1.10 →1.1.11**, CSS değişikliği için.
- AGENTS.md,16 proje standartları,48 bu belge ve index.html.

## 7. AI değerlendirme filtresinin doğrudan hizalama kontrolü

Kullanıcının16:27 ekran görüntüsündeki kullanıcı/tarih kayması üzerine aynı AI değerlendirme ekranı yeniden, bu kez alanların yalnız genişliği değil üst koordinatı ve yüksekliği ölçülerek kontrol edildi. Yukarıdaki ortak bileşen düzeltmesi güncel kaynakta bu ekranı da kapsıyor; ek CSS/Blade değişikliği gerektiren bir fark görülmedi. APP_VERSION1.1.11 korundu.

- 1440px genişlikte kullanıcı seçicisi ve tarih alanı y317px, genişlik252px, yükseklik38px; etiketler y290px/yükseklik21px. Padding6px12px ve satır yüksekliği24px aynı.
- 768px genişlikte her iki alan y471.75px, genişlik146.5px/yükseklik38px.390px genişlikte alanlar alt alta, genişlik284px/yükseklik38px; yatay sayfa taşması yok.
- Boş tarihte mevcut arayüz dilinin yer tutucusu görünür. Testing tarayıcısında lang=tr isteği nl görünümünde kaldığı için bu koşu Türkçe çalışma zamanı doğrulaması değildir; kaynakta Türkçe karşılık `Tarih seçiniz` mevcuttur. Dil seçme davranışına bu kapsamda müdahale edilmedi.
- Takvim açıldı, tarih seçimi JSON isteğinde200 döndü; Sıfırla alanı boşalttı ve yer tutucu geri geldi. Tablo verisi yüklendikten sonra görüntü alındı: `/tmp/eduan-ai-vote-alignment-current.png`.
- Yalnız ilgili `ai_activity_filters_delete_boundary_and_cache_refresh` testi ayrı testing DB/transaction ile **1 test/15 doğrulama geçti**. Genel test/build çalıştırılmadı. Ana DB değişmedi; geçici tarayıcı sunucusu durduruldu.
