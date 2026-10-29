# 89 — Çalışma paneli, tek seferlik hedef ve toplu alıcı seçimi

30 Ekim 2026. Kapsam: çalışma takibi panelindeki hafta seçimi/liste/detay, toplu iletişimde alıcı filtreleri ve bütün filtre sonucunu seçme, öğrencinin haftalık hedefini yalnız bir kez kaydetmesi. Koddan önce AGENTS.md, README, 03/16 standartları, 71/77/83/84 özellik rehberleri ve gerçek ortak bileşenler incelendi. Mevcut OneUI/Bootstrap/Livewire, Flatpickr ve Bootstrap Table/server JSON kullanıldı; bağımlılık eklenmedi.

## 1. Kararlar

- Aynı haftadaki günler tek cumartesi–cuma aralığını temsil eder; hesaplama/takvim kuralı değişmez.
- Öğrenci bulunduğu hafta içinde istediği gün hedefini ilk kez kaydedebilir. Kayıttan sonra aynı hedefi değiştiremez; başka sekme, sayfayı yenileme veya doğrudan Livewire isteği bu kilidi açmaz.
- Mevcut kayıtlar da kilide dahildir. Kaydedilmiş boş/sıfır hedef mevcut bir kayıt sayılır; pozitif hedef zorunluluğu eklenmedi.
- Yetkili öğretmen/yönetici panelden mevcut hedefi değiştirebilir. Öğretmen yalnız bağlı öğrencilerini yönetir; kapanmış hafta ve geçmiş hedef için mevcut yönetici kuralı korunur.
- Kullanıcı ek soruya **yalnız aktif kullanıcılara gönderim** yanıtını verdi. Pasif kullanıcı gönderimi açılmadı.
- Mevcut 500 alıcı, üç adım/ön izleme, UUID ve asenkron gönderim kuralları korunur.

## 2. Panelde kullanım

Çalışma Takibi → Çalışma haftası alanında örneğin **24 Ekim 2026 — 30 Ekim 2026** görünür. 28 veya 29 Ekim seçmek aynı 24 Ekim hafta kimliğini üretir; URL, liste, export ve gönderim hazırlığı aynı hafta değerini kullanır. Görünen alan yerelleştirilir; gönderilen değer ISO başlangıç günüdür. Flatpickr alt alanının ortak tablo tarafından yeniden doldurulması da aralık metnini korur. Klavye etiketi görünür alanla eşleştirilir.

Listede üç hedef artık soru/sayfa/saat etiketleriyle ayrı satırlarda; durum metni renkli rozetle görünür. İsim ve e-posta, arama ve mevcut server JSON sözleşmesi korunur; eski `goal` metni geriye uyum için JSON'da kalır.

Detayda öğrenci kimliği/e-posta/aktiflik, hafta gezinmesi ve beş GET sekmesi korunur. Hedef/çalışma/not ve izinli iletişim işlemleri aynı üst bloktadır. Her sekmede haftanın üç miktarı, hedefleri ve ilerleme oranları okunur kartlarla görünür; grafikler görselde %100, metinde gerçek oranı korur. Sağ paneller ve bütün detay Bootstrap tabloları mevcut yetkilerle devam eder; rapor hesaplama sorgusu değiştirilmedi ve yeni rapor sorgusu eklenmedi.

## 3. Toplu iletişim

Yönetim → Toplu E-posta ve Bildirim → Yeni gönderim → Alıcılar:

1. **Kullanıcı türü:** tümü/öğrenci/öğretmen/yönetici. Öğrenci filtresi personel tiplerini dışlar; öğretmen kapsamı her zaman kendi bağlı kullanıcılarıdır.
2. **Bildirim izni:** tümü/izin verenler/izin vermeyenler. Bu bilgi mevcut uygulama için doğrulanmış, rızası bulunan ve kaldırılmamış yerel cihaz kaydına dayanır; gerçek sağlayıcı teslim garantisi değildir.
3. Arama isim, soyisim, kullanıcı adı ve **e-posta** alanlarını kapsar; e-posta tabloda da görünür.
4. **Filtrelenenlerin tümünü seç** yalnız açık sayfayı değil bütün filtre sonucunu seçer; önceden seçilen alıcıları korur ve tekilleştirir. Ortak tabloya en son uygulanmış filtre/arama kullanılır; henüz Filtrele'ye basılmamış değer uygulanmış gibi yorumlanmaz.
5. **Seçimi temizle** tüm sayfalardaki seçimi kaldırır. Sayfalama/geri dönüş mevcut seçimi korur.
6. 500 alıcı kabul edilir; 501 veya mevcut seçimle birleşince 500'ü aşan sonuç hata verir. Sessizce ilk 500 kişi seçilmez; önceki seçim korunur.

Liste ve toplu seçim aynı `CommunicationRecipients` sorgusundan gelir. İşlem yazma iznini/adımı/mevcut alıcıların aktifliğini ve öğretmen ilişkisini tekrar kontrol eder. Çalışma takibinden açılan kilitli composer bu genel alıcı seçme metodunu kullanamaz; kaynak kapsamı aşılamaz. Ön izleme ve son gönderim doğrulamaları değişmedi. Bu geliştirmede gerçek gönderim yapılmadı.

## 4. Hedefin sunucuda kilitlenmesi

`AuthorizeStudyChange`, kendi hesabındaki personel olmayan kullanıcı için o haftaya ait kayıt varsa açıklamalı 403 üretir. Kontrol hem ilk çağrıda hem mevcut `SaveStudyTarget` transaction'ında kullanıcı satırı kilitlendikten sonra çalışır. Böylece açık kalmış ikinci form yeni hedef veya güncelleme yazamaz; revision/bildirim de oluşturamaz. Panelin hedef açma işlemi güncel hedef değerini yeniden yükler. İstemci alanlarını devre dışı bırakmak tek koruma değildir.

## 5. Doğrulama

Yalnız ayrı **testing** DB; transaction/rollback, HTTP engeli ve Mail/Queue fake. Ana DB'ye örnek veya migration yazılmadı.

| Koşu | Sonuç |
|---|---|
| StudyTrackingEntries, StudyTrackingForm, StudyPanel, CommunicationWizard, Communications | **102 test / 873 doğrulama başarılı** |
| Son ek hafta normalizasyonu + genişletilmiş öğretmen düzenleme testi | **2 test / 19 doğrulama başarılı**; öğretmen senaryosu önceki koşunun alt kümesidir |
| 500 kabul/501 ret sınırı için genişletilmiş tek senaryo | **1 test / 9 doğrulama başarılı**; önceki wizard senaryosunun alt kümesidir |
| PwaRelease | **6 test / 73 doğrulama başarılı** |
| Alıcı seçimi + dört dil hafta input'u Node | **8 test başarılı** |
| Panel formatlayıcıları | **16 doğrulama başarılı**, yeni hedef/durum metinlerinde HTML kaçışı dahil |
| Değişen 19 PHP/Pint, üç JS syntax, dört dil anahtar eşliği ve doluluk | Başarılı |
| Gerçek Chrome, 1440/TR + 390/NL | Beş detay sekmesi, hafta normalizasyonu, admin hedef düzenleme ve özet yenileme, 100 kişinin tüm sayfalardan seçimi/geri dönüş/temizleme, öğrencide hedef kilidi başarılı |
| Tarayıcı sınırı | JS istisnası **0**, beklenmeyen HTTP 5xx **0**, kontrol edilen ekranlarda belge taşması **0** |

Browser plugin mevcut olmadığı için kurulu Playwright ve gerçek Chrome kullanıldı. Geçici HTTP kernel köprüsü ve örnekler yalnız testing transaction'ında kaldı; çıkışta geri alındı. İlk UI koşusunda ortak Flatpickr yeniden biçimlemesinin aralığı tek güne düşürmesi düzeltildi ve dört dil Node regresyonu eklendi. Sonraki koşudaki Devam/Devam et farkı yalnız test locator'ıydı; uygulama davranışı değiştirilmedi. Son tamamlanan Chrome akışı yukarıdaki bütün adımları geçti.

Bu sayılar tek bir bütün proje koşusu değildir. Genel suite/build, gerçek eşzamanlı yük, fiziksel iOS/Safari, gerçek SMTP/push ve canlı deploy çalıştırılmadı. İncelenen yerel kapsam geçti; üretim işletim/yayın kapısı önceki kararlara göre ayrı kalır.

## 6. Değişen dosyalar

- `app/Actions/Study/AuthorizeStudyChange.php`: kayıtlı kişisel hedef kilidi.
- `app/Livewire/Frontend/Study/StudyForm.php`: editör açılırken hedefi tazeleme.
- `app/Helpers/CommunicationRecipients.php`: ortak filtreli aktif alıcı sorgusu.
- `app/Http/Controllers/Backend/{CommunicationController,StudyController}.php`: alıcı filtreleri/e-posta ve etiketli hedef/durum JSON alanları.
- `app/Livewire/Backend/Communications/CommunicationForm.php`: filtrelenen bütün alıcıları seçme ve 500 sınırı.
- `app/Livewire/Backend/Study/StudyCommunication.php`: kilitli kaynak seçimini koruma.
- `resources/views/backend/study/{index,show}.blade.php`: hafta aralığı/kimlik/üst işlemler.
- `resources/views/livewire/backend/study/student-report.blade.php`: hızlı haftalık miktar/hedef kartları.
- `resources/views/livewire/frontend/study/study-form.blade.php`: ilk kayıt öncesi açık tek hak açıklaması.
- `resources/views/livewire/backend/communications/communication-form.blade.php`: iki filtre, toplu seçim/temizleme ve e-posta kolonu.
- `public/backend/assets/{study-panel.js,study-panel.css,communications.js}`: yalnız bu ekranların sunum ve etkileşimleri.
- `public/backend/assets/server-table.js`: en son uygulanmış query parametrelerini tablo kökünde paylaşır; diğer tablo davranışları korunur.
- `lang/{tr,nl,en,fr}/{study,notifications}.php`: yerelleştirilmiş yeni metinler.
- `tests/Feature/{StudyTrackingEntries,StudyTrackingForm,StudyPanel,CommunicationWizard}Test.php`: iş kuralı/sınır/izin senaryoları.
- `tests/Frontend/{CommunicationSelection,StudyPanelFormatter,StudyWeekPicker}Test.cjs`: seçim/HTML kaçışı/hafta biçimi regresyonları.
- `public/pwa-sw.js` ve ignored yerel `.env`: APP_VERSION/root worker **1.1.41**.
- AGENTS/README/16/index ve bu rehber: güncel kural ve kanıt kaydı.

Önceden değişik `.DS_Store`, silinmiş `Codex/Doge.html`, `Codex/docs.html`, yüklenen OneSignal klasörü ve iki site görseli bu işin dışında bırakıldı. Turnstile ayarı/anahtarlar, mevcut çalışma verileri, dönemler ve roller değiştirilmedi.

## 7. Elle kontrol

1. Panel çalışma listesinde aynı haftanın iki gününü seçin; aynı cumartesi–cuma aralığının ve sonuçların geldiğini görün.
2. Öğrenci detayında beş sekmeyi, üst işlemleri ve üç haftalık kartı kontrol edin; öğretmenle yalnız bağlı öğrencileri açın.
3. İletişimde kullanıcı türünü/izni filtreleyin, arayın, tümünü seçin; sayfa değiştirip toplam seçimi kontrol edin.
4. İkinci adımdan geri dönün; seçimin korunduğunu ve temizle ile hepsinin kaldırıldığını görün.
5. Öğrenci hesabıyla yeni haftanın hedefini kaydedin; yenilemeden ve yeniledikten sonra değiştiremediğini kontrol edin.
6. Öğretmen/yönetici panelden hedefi değiştirsin; öğrenci ekranında yeni değer görünsün. Gerçek gönderim testi bu kaydın kapsamında değildir.
