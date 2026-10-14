# Çalışma Takibi — Faz 1: takvim, ayarlar, veri ve izinler

14 Ekim 2026. Başlangıç commit’i: `b0a366d4`. Bu rapor yalnız Faz 1 kapsamındadır. Tam modül, otomatik soru hesabı ve canlı yayına hazır olma onayı değildir.

## 1. Kesin iş kuralları

- Mevcut users/roller, teacher_student, questions/answers ve test/sınav sonuç sözleşmeleri korunur. Ders ataması eklenmez.
- Hafta sistem saat diliminde cumartesi 00:00 ile sonraki cumartesi 00:00 arasıdır; üst sınır yeni haftaya aittir. Yaz/kış saatinde sabit saniye sayısı kullanılmaz.
- Dönem sınırı haftayı kısaltmaz. Aynı gerçek hafta iki döneme denk gelebilir; hafta/öğrenci hedefi çoğalmaz.
- Genel haftalık hedefler soru, okunan sayfa ve çalışma süresidir. Dil/ders/konu seçimi yoktur. Süre tam dakika saklanır, arayüz saat gösterebilir.
- Manuel çalışma bugün veya geçmiş gün içindir; gelecek gün yoktur. Gün içi parçalar ayrıntıda korunur, günlük toplam birleşir.
- Başlangıç tarihi tüm manuel çalışmalar için alt sınırdır. Otomatik eski test/sınav cevapları bu tarihle dışlanmaz; entegrasyon Faz 3’tedir.
- Hedef yalnız açık hafta için öğrenci/öğretmen tarafından değiştirilebilir. Kapanmış hedef yalnız mevcut yönetici rolü içindir.
- Geçmiş çalışma ayarı kapalı başlar; açıkken öğrenci/öğretmen geçmiş manuel çalışmayı başlangıç tarihine kadar düzenleyebilir. Yönetici geçmiş çalışma kilidinden muaftır, başlangıç/gelecek sınırından değildir.
- Bütün mevcut web rolleri kendi takibine erişebilir. Başkasını panelden yönetmek dashboard/users görüntüleme ve çalışma işlemi izni gerektirir. Öğretmen kendi öğrencileriyle sınırlıdır; yönetici önceliklidir.
- Gelecek otomatik sayım: yarım ve tekrar denemeler dahil; aynı denemede cevap değişikliği çoğaltmaz, kaldırma toplamdan çıkarır. En son öğrenci cevap işleminin haftası kullanılır; otomatik puanlama tarihi öğrenci cevap değişikliği değildir.
- Gerçek kategori ve genel yüzdeler %100’ü aşabilir. Boş/sıfır hedef ortalamaya girmez. Grafik miktarı gerçek değerdir; yalnız ilerleme çubuğu görsel olarak tam dolabilir.
- Hedefi olmayan öğrenci haftalık karşılaştırmada hareketsizdir. Öğretmen grubu mevcut öğretmenlerin öğrencilerinin birleşimidir; kişi tek sayılır. Diğer öğrencilerin kimliği ön yüzde açıklanmaz.
- Güçlü konu: >%70 doğru; zayıf: >%70 yanlış; diğerleri dengeli. Boş cevaplar paydada yoktur. Asgari cevaplanmış soru ayarı varsayılan 10’dur.
- Notlar öğrenciye varsayılan gizlidir; yazar/yönetici görünürlüğü seçer. Atanmış öğretmenler okuyabilir, yazar kendi notunu ve yönetici bütün notları yönetebilir.
- OneSignal: izin yönetimi, kullanıcı-cihaz eşleştirme, seçilen aboneliği kaldırma ve son 10 gönderilen bildirim. Hedef/çalışma başka yetkili tarafından değiştirilince bildirim; kendi değişikliği veya not eklenince yok.
- WhatsApp, otomatik e-posta hatırlatma/rapor, AI tavsiyesi ve kişisel plan yapılmaz. Panelden seçili kişilere toplu mail/push ayrı fazdır.

## 2. Yedi faz

1. Takvim, ayarlar, veri yapısı, erişim kuralları ve izinler.
2. Hedef/günlük manuel giriş, düzeltme, kaldırma, toplam ve revision yazımı.
3. Otomatik test/sınav bağlantısı ve cevap zamanı/tekrar/kaldırma hesabı.
4. Öğrenci sayfası, grafikler, geçmiş ve anonim karşılaştırmalar.
5. Panel listesi/filtreleri, öğrenci detayı ve öğretmen notları.
6. OneSignal ve seçili alıcılara toplu iletişim.
7. Bütün modülün birleşik kabul testleri ve yayın hazırlığı.

Her faz: değişen kapsam + önceki fazlarla birleşen erişilebilir senaryolar, sınırlar ve hata yolları test edilir; sonuç/inceleme yolu raporlanır ve yalnız faz dosyaları commit edilir. Faz sonunda sonraki faza otomatik geçilmez.

## 3. Faz 1 kaynakları

- StudyCalendar: tam hafta sınırı, tarih doğrulama ve dönemle kesişen haftalar.
- StudyTrackingSettings/PrepareStudyTrackingSettings: dört ayar, ilk aktivasyonda tarih, boş tarihle yeniden açmada tarihi koruma, veri doğrulama.
- AuthorizeStudyAccess/AuthorizeStudyChange: sahiplik, öğretmen kapsamı, yönetici, modül kapalı, başlangıç, gelecek ve geçmiş kuralları.
- EnsureStudyWeek: unique starts_on ile aynı hafta için tek kayıt.
- Beş model ve User ilişkileri: StudyWeek, StudyWeeklyTarget, StudyActivityEntry, StudyTargetRevision, StudyActivityRevision.
- Ayarlar study sekmesi ve rol ekranında çalışma izin grubu. Öğrenci takip sayfası bu fazda eklenmez.
- TR/NL/EN/FR study.php kaynak çevirileri; dil kaydı/aktivasyonu veya eski çeviri satırları değiştirilmez.

## 4. SQL kapsamı

2026_10_14_100000 yalnız study_weeks, study_weekly_targets, study_activity_entries, study_target_revisions, study_activity_revisions oluşturur. Mevcut tabloların kolonları değişmez.

- Hafta starts_on benzersizdir; ends_on cuma tarihidir (takvim üst sınırı sonraki cumartesidir).
- Hedef unique(user_id,study_week_id); soru/sayfa/dakika nullable unsigned integer.
- Çalışma parçaları user/week/recorded_by/activity_date/soru/sayfa/dakika ve soft delete taşır. Günlük kullanıcı ve hafta/kullanıcı indeksleri vardır.
- Revision parent/actor/operation/before/after/created_at; ayrı alanlara snapshot. Ana uygulama logunun yerine geçecek genel log değişimi yapılmaz.
- FK’ler restrict kullanır; hesap/hafta/çalışma silmek geçmişi otomatik silmez. down tabloları kaldırır: veri varsa ayrıca onay olmadan rollback çalıştırılmaz.

2026_10_14_100100 yalnız study:access/view/update izinlerini ekler. access mevcut web rollerine; view/update mevcut öğretmen/yönetici tipindeki web rollerine verilir. Yeni rol yaratmaz, başka izni kaldırmaz. Tekrar çalıştırma çoğaltmaz; down önceden/sonradan tanımlanmış izin kararlarını silmez.

## 5. Ayarların çalışması

Ayarlar → Çalışma Takibi (`/admin/settings?activeTab=study`, gerçek route adı admin.settings.index). Yeni sekme yalnız mevcut yönetici tipi ve mevcut settings erişimiyle açılır; POST ayrıca settings:update/dashboard ve yönetici tipini yeniden denetler.

- studyTrackingEnabled: 0 (kapalı).
- studyTrackingStartedOn: boş. İlk etkinleştirmede sistemdeki gün atanır; migration tarihi sabitlenmez. Yönetici geçmiş geçerli gün seçebilir. Kapat/aç veya boş form tarihi eski tarihi sıfırlamaz.
- studyAllowPastActivities: 0.
- studyMinimumTopicQuestions: 10 (en az 1).

Yeni ayarlar mevcut settings deposundadır, yeni genel settings tablosu yoktur. Faz tamamlamak modülü ana/canlı DB’de etkinleştirme izni değildir. Başlangıç tarihi manuel girişleri sınırlar; eski otomatik sonuçları elemez. Başlangıç tarihinin değişmesi var olan manuel kayıtları silmez.

## 6. Senaryo matrisi

| Alan | Kapsam |
|---|---|
| Takvim | 7 gün, cuma son saniye/cumartesi ilk saniye, UTC giriş/yerel gün, 167/168/169 saat, leap day, yıl/dönem sınırı, ters/geçersiz tarihler |
| Ayarlar | kapalı/boş varsayılan, ilk açılış, kapat-aç, boş/önceki tarih, açık geçmiş ayarı/min soru, diğer ayarların korunması, geçersiz tarih/tip/aralık/anahtar, yetkisiz/guest POST |
| Yetki | tüm tiplerde kendi hesap, başka hesap, atanmış/atanmamış öğrenci, izni kaldırılmış aktör, admin+teacher beraber, okunabilir/değiştirilebilir ayrımı |
| Tarih/işlem | bugün/geçmiş/gelecek, aktivasyon haftası, kapalı hedef, kapalı çalışma/ayar açık-kapalı, yönetici istisnası, modül kapalı/geçersiz işlem |
| Şema | tek hafta/iki dönem, tek kullanıcı-hafta hedefi, nullable hedef, gün içi parçaların toplamı, değişiklik snapshot ilişkileri, questions/answers/pivot korunması |
| İzin migration | mevcut web rollerine grant, panel tipine grant, diğer guard dışlama, iki kez çalışma, diğer izinleri koruma |
| Tarayıcı | gerçek settings tabı, desktop/mobil ve NL/FR, etiketler, kayıt/yeniden açılış, hatalı form, JS/network hata |

## 7. Güncel kanıt ve izin sınırı

Kod hazırlandı. PHP 8.5.7 host saf takvim testi: **19 test / 42 doğrulama başarılı**. İlgili 20 yeni PHP dosyası syntax başarılı. DB/HTTP/model/izin ve tarayıcı testleri henüz yapılmadı.

14 Ekim 2026 kullanıcı yanıtı: yalnız iki migration’ın yerel ana ve ayrı testing veritabanlarında uygulanması **onaylandı**. Lara→Eduan servis değişimine **izin verilmedi**; kullanıcı veritabanı ve tarayıcı testlerini bekletmemizi istedi. Bu nedenle migration’lar da henüz uygulanmadı. Mevcut servisler/volume/port değiştirilmedi. Eduan test ortamı kullanıcı tarafından kullanılabilir hale getirildiğinde bekletme kaldırılarak bu onaylı kapsamdan devam edilir; aynı migration onayı tekrar istenmez. Modül etkinleştirilmez, başlangıç tarihi atanmaz. DB sıfırlama/silme, ücretli API, gerçek push/mail veya deploy/push için izin yoktur.

## 8. Elle kontrol ve teslim

Eduan ortamı açılıp bekletme kaldırıldıktan ve onaylı iki migration uygulandıktan sonra yönetici hesabıyla Ayarlar → Çalışma Takibi açılır. Varsayılan kapalı/başlangıç boş/minimum 10 kontrol edilir. Ana yerel/canlı ayarı gerçek etkinleştirme için değiştirmeden kaynak ve test kanıtı incelenebilir.

14 Ekim 2026 sonraki kullanıcı talimatı: Faz 1 kodu DB/browser testleri beklerken commit edilecek ve Faz 2 geliştirmesi başlayacak. Bu commit doğrulanmış tam Faz 1 kabulü değildir; bekleyen test kapısı korunur. Commit kimliği sonraki faz raporunda kaydedilir. Migration/DB/browser testleri bekletme ve servis değiştirmeme kararı devam eder. APP_VERSION/root worker değişmez: yeni CSS/JS yoktur.
