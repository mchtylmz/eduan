# Çalışma Takibi — Faz 2: haftalık hedef ve manuel çalışma

14 Ekim 2026. **Kod hazırlığı tamamlandı; DB/Livewire/HTTP ve tarayıcı kabulü bekliyor.** Kullanıcı servisleri değiştirmeye izin vermedi ve veritabanı/tarayıcı testlerini bekletti. Bu rapor tam faz kabulü veya canlı yayın onayı değildir.

## 1. Commit ve kapsam

- Başlangıç: `b0a366d4` — çalışma takibi öncesi sürüm.
- Faz 1: `0834f8d1` — takvim, ayarlar, beş tablo ve izin altyapısı. Kullanıcının bu tur talimatıyla commit edildi; bekleyen test notu commit gövdesinde de bulunur.
- Faz 2 kodu aynı test bekleme sınırıyla ayrı commit olarak kaydedilir. Gerçek DB kabulü sonraki kontrol kapısında yapılacak.
- Faz 3 otomatik test/sınav entegrasyonu bu tur başlamadı. Mevcut soru/cevap, puan, süre, öğretmen ilişkileri değişmedi.

## 2. Ekran ve giriş

Hesap menüsünde izinli kullanıcıya **Çalışma Takibim** bağlantısı eklenir. `/account/study` ayrı sayfadır; profilin sağ içeriği veya sekmesi değildir. Normal/premium, öğretmen ve yönetici kendi verisini görür. Ön yüzde başka kullanıcı seçimi yoktur.

- Cumartesi–cuma tarih aralığı, önceki/sonraki hafta ve bu haftaya dönüş bağlantıları.
- Gelecek hafta okunamaz; tarih parametresi geçerli tarih olmalı. Hafta içi tarih ilgili cumartesiye normalleştirilir.
- Hedef formunda soru, okunan sayfa ve çalışma saati. Ders/konu seçimi yok.
- Çalışma formunda tarih ve aynı üç değer; bugün ve seçilen haftanın geçmiş günleri.
- Günlük SQL toplamları ve hafta toplamı. Ayrıntı listesi 15 kayıt/sayfa; tarih DESC, id DESC.
- Bir günün yeni girişleri birleşik toplamda gösterilir; örneğin 50 + 20 + 20 = 90. Ayrıntıda üç ayrı kayıt kalır.
- Düzenle seçilen parçayı değiştirir. Kaldır, kullanıcı onayıyla yalnız o parçayı soft-delete eder ve toplamdan çıkarır. Audit kaydı korunur.
- Hedef/çalışma boş ve sıfır değerleri ayrıdır; sıfır çalışma kaydı mümkündür. Boş/sıfır hedef bir pozitif hedef sayılmaz. Gerçekleşme grafiği Faz 4 kapsamındadır.
- Saat hem virgül hem nokta kabul eder; en fazla dört ondalık, en yakın tam dakika. Tekrar düzenlemeye açılan süre tam dakika değerini korur.
- Bu ekranın toplamları **yalnız manuel çalışma** olarak etiketlenir. Otomatik çözümler Faz 3’te eklenecek.
- TR/NL/EN/FR kaynak dosyaları 55 eş anahtar içerir; dil DB kayıtları, aktivasyonu ve mevcut özel çeviriler değiştirilmedi.
- Mevcut Bootstrap ve member-form tasarımı kullanılır. Yeni CSS/JS yok; APP_VERSION ve kök worker 1.1.25 korunur.

## 3. Sunucu kuralları

Her eylem güncel giriş yapan kişiye ve sunucu sahiplik sorgusuna dayanır. Tarayıcı kullanıcı ID’si belirlemez. Livewire hafta, kayıt ID’si, gönderim UUID’si ve önceki değerler Locked’tır. Okuma ve yazma sırasında izin yeniden denetlenir.

- Haftalık hedef yalnız bulunduğumuz hafta; geçmiş hedef yalnız yönetici. Geçmiş çalışma anahtarı hedef kilidini açmaz.
- Manuel kayıt tarihi başlangıçtan önce veya gelecekte olamaz. Kapanmış hafta öğrenci/öğretmen için anahtara bağlıdır; yönetici bu hafta kilidini aşabilir, başlangıç/gelecek sınırını aşamaz.
- Düzenlenen kaydın eski tarihi de denetlenir: kapanmış kaydı bugüne taşıyarak kilit aşılamaz.
- Ön yüz tarih girişi seçilen hafta ile sınırlıdır. Ortak action sonraki panel kullanımında geçerli haftalar arası taşımanın iki tarihini de denetler.
- Soru/sayfa yalnız negatif olmayan tam sayı; süre en yakın dakika. Veritabanının unsigned integer üst sınırı aşılırsa doğrulama hatası, yazma yok.
- Hedef ortalaması, geçmiş otomatik cevaplar ve öğretmen grubu kuralları Faz 1 kararıyla aynı kalır.
- `private, no-store` HTTP cevabı; mevcut SEO public izin listesine dahil değildir, `noindex, nofollow` gelir. Bu davranışın gerçek HTTP testi bekler.

## 4. İşlem ve geçmiş

SaveStudyTarget/SaveStudyActivity/RemoveStudyActivity transaction içinde sahibin users satırını kilitler; yetki ve hafta kuralını tekrar denetler. Kayıt ile revision atomiktir. Revision yazımı başarısızsa kayıt işlemi geri alınır.

- Hedef tek kullanıcı/hafta kaydıdır. Eski değerleri değişen başka formun üstüne yazma 409 ile durur.
- Çalışma create işlemi kullanıcı + submission_key ile tekrar kullanım sağlar. Aynı anahtar/aynı veri aynı kaydı döndürür; aynı anahtar/farklı veri veya silinmiş kayıt 409 olur.
- Yeni UUID ile aynı miktarda ikinci çalışma girişi mümkündür: ayrı çalışma parçalarıdır.
- Başarılı kayıt sonrası form UUID’si yenilenir; başarısız doğrulama tüketmez. Bu, aynı form isteğinin tekrarı içindir; ayrı yeni sayfa/girişleri otomatik olarak aynı çalışma saymaz.
- Düzenleme/kaldırmada önceki değerler karşılaştırılır; eski form güncel farklı kaydı ezmez. Aynı değerleri tekrar kaydetmek yeni revision üretmez.
- Revision aktör, işlem, önce/sonra ve oluşturma zamanını saklar. Recorded_by ilk giriş yapan kişi olarak kalır.
- Bildirim/posta gönderimi veya genel cache flush yok. Bildirim altyapısı Faz 6’dadır.

## 5. Ek migration ve onay sınırı

`2026_10_14_110000_add_submission_key_to_study_activity_entries.php` yalnız study_activity_entries tablosuna nullable UUID ve unique(user_id,submission_key) ekler. Eski kayıtlar null kalır; veri silinmez veya otomatik doldurulmaz.

**Bu üçüncü migration önceki iki migration onayına dahil değildir. Henüz uygulanmadı; uygulama öncesinde ayrıca onay alınacak.** Kullanıcı DB kontrollerini beklettiğinden bu tur yeni DB işlem onayı istenmedi. İlk iki migration’ın yerel ana/testing onayı sürer; onlar da henüz uygulanmadı. Canlı DB’ye hiçbir işlem yapılmadı.

## 6. Gerçek doğrulama

Yalnız DB gerektirmeyen testler çalıştırıldı:

```bash
/opt/homebrew/opt/php@8.5/bin/php vendor/bin/phpunit tests/Unit/StudyCalendarTest.php tests/Unit/StudyInputTest.php
```

- **63 test / 216 doğrulama başarılı**: Faz 1 takvim 19 + Faz 2 giriş/süre 44 senaryo. Saat değişimi, hafta sınırı, yanlış tarih, boş/sıfır, uint32 sınırı, geçersiz tür, nokta/virgül, yuvarlama ve dakika tekrar açma kapsanır.
- İlgili 17 PHP dosyası syntax başarılı; ilgili 16 PHP dosyası Pint başarılı (routes mevcut biçimi korunarak syntax kontrolünde).
- Üç Blade’in component etiketlerini çalıştırmadan derlenmiş PHP syntax kontrolü başarılı. Bu gerçek Laravel render veya tarayıcı kontrolü değildir.
- TR/NL/EN/FR 55 anahtar eşliği ve boş çeviri kontrolü başarılı.
- git diff --check başarılı.

## 7. Hazırlanan ancak çalıştırılmayan testler

**Yeni 31 parametrik DB/Livewire/HTTP senaryosu hazırdır; hiçbiri bu tur çalıştırılmadı.** Faz 1 foundation/settings testleri de bekler. Ortam kullanılabilir ve gerekli migration onayları tamam olduğunda yalnız aşağıdaki ilgili kapsam birlikte koşulacak:

```bash
/opt/homebrew/opt/php@8.5/bin/php vendor/bin/phpunit tests/Feature/StudyTrackingFoundationTest.php tests/Feature/StudyTrackingSettingsTest.php tests/Feature/StudyTrackingEntriesTest.php tests/Feature/StudyTrackingFormTest.php
```

| Alan | Hazırlanan kapsam | Durum |
|---|---|---|
| Hedef | Create/update/reuse, nullable/sıfır, tek hafta kimliği, audit, stale conflict | Bekliyor |
| Çalışma | Parçalar/toplam, edit/remove, creator, soft-delete ve tek audit | Bekliyor |
| Tekrar istek | Aynı UUID tekrar, yeni UUID aynı değer, silinmiş UUID ve farklı veri | Bekliyor |
| Tarih/yetki | Gelecek/başlangıç/kapalı hafta, geçmiş anahtarı, admin, eski kaydı taşıma | Bekliyor |
| Sahiplik | Başka kullanıcı, öğretmenin atanmış öğrencisi, atama/izin geri alma | Bekliyor |
| İşlem | Revision hatasında rollback, geçersiz sayıda hiç yazmama | Bekliyor |
| Form | Save başarı/validation, selected-week, locked property tahrifi, stale warning | Bekliyor |
| Liste/HTTP | Kişisel SQL toplam, deleted dışlama, 15+1 sayfa, private/noindex, menü/guest | Bekliyor |
| Kapalı modül | Tablo sorgusu yapmama ve sahte save engeli | Bekliyor |
| Tarayıcı | Desktop/mobil, tarih alanı, hafta geçişi, toplamlar, edit/confirm cancel, pagination, console | Bekliyor |

Gerçek eşzamanlı süreç/DB lock davranışı, canlı ölçüm ve tüm proje suite’i bu sonuçla kanıtlanmaz. Lara Football/Eduan servisleri, portlar ve volume’lar değişmedi.

## 8. Değişen dosyalar

- app/Helpers/StudyInput.php
- app/Actions/Study/StudyWrite.php
- app/Actions/Study/SaveStudyTarget.php
- app/Actions/Study/SaveStudyActivity.php
- app/Actions/Study/RemoveStudyActivity.php
- app/Livewire/Frontend/Study/StudyForm.php
- app/Models/StudyActivityEntry.php
- app/Http/Controllers/Frontend/AccountController.php
- routes/frontend.php
- database/migrations/2026_10_14_110000_add_submission_key_to_study_activity_entries.php
- resources/views/frontend/study/index.blade.php
- resources/views/livewire/frontend/study/study-form.blade.php
- resources/views/frontend/account/tab.blade.php
- lang/tr/study.php, lang/nl/study.php, lang/en/study.php, lang/fr/study.php
- tests/Unit/StudyInputTest.php
- tests/Feature/StudyTrackingEntriesTest.php
- tests/Feature/StudyTrackingFormTest.php
- Codex/71-CALISMA-TAKIBI-FAZ2-HEDEFLER-VE-MANUEL-CALISMA.md
- Codex/index.html
- AGENTS.md

İlgisiz .DS_Store korunur ve commit’e alınmaz. .env/model aktivasyonu/ana DB/dil DB/ücretli API/push/deploy yok.

## 9. Elle nasıl kontrol edilir?

Şu anda servis ve DB kapısı beklediği için çalışan ekran kabulü verilemez. Eduan ortamı kullanıcı tarafından kullanılabilir hale getirildiğinde:

1. İlk iki onaylı migration ve ayrıca onay alınacak submission_key migration’ı uygulanır; testing ayrı kalır.
2. Çalışma takibi ayarından modül ve başlangıç tarihi ayrıca etkinleştirilir. Bu tur bu ayarlar değiştirilmedi.
3. Hesap → Çalışma Takibim veya `/account/study`. 150 soru/20 sayfa/2,5 saat hedef kaydet.
4. Bugüne 50, sonra 20, sonra 20 soru ekle. Toplam 90 ve üç parça gör. Birini 25 yap; toplam 95. Kaldırmayı iptal et, sonra yalnız bir parçayı kaldır.
5. Dün kaydı, gelecek tarih, önceki hafta, başlangıç öncesi, geçmiş anahtarı ve yetkisiz hesap sınırlarını denetle. İki açık sekmede hedefi farklı değiştir; eski sekmede uyarı gör.
6. Mobil/desktop hafta geçişi, 15+1 sayfa, TR/NL/FR ve console kontrolü.

Bir sonraki geliştirme Faz 3 otomatik test/sınav sayımıdır; Faz 2 kodu tam DB/browser kabulü almadan kabul edilmiş sayılmaz.
