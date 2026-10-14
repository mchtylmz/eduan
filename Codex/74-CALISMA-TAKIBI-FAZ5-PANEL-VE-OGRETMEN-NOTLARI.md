# Çalışma Takibi — Faz 5: panel ve öğretmen notları

14 Ekim 2026. Faz 4 commit: `91064b2e`. Bu fazın kapsamı panel listesi, öğrenci raporu/düzenleme ve öğretmen notlarıdır. Sonraki Faz 6 bildirim/toplu gönderimdir; bu fazda gerçek dış servis çağrısı yapılmadı.

## 1. Ekranlar

- Panel → Çalışma Takibi: `/admin/study`. Hafta, durum, arama, kolon sıralaması ve 15 kayıt/sayfa; Bootstrap Table + server JSON.
- Tarih seçimi ilgili tam cumartesi haftasına eşlenir. Gelecek hafta veya hatalı tarih reddedilir. Önceki haftalar incelenebilir.
- Listede soru/sayfa/saat hedefleri ve manuel+otomatik gerçekleşen miktarlar, gerçek genel yüzde ve durum görünür. Yüzde 100’ü aşabilir.
- Filtreler: hedef yok, çalışma yok, sıfır çalışma kaydı, %50 altı ve çalışma mevcut. Filtreler bağımsızdır: hedefi olmayan ama kaydı olmayan öğrenci hem hedef yok hem çalışma yok filtresine girebilir; hedefli sıfır çalışma düşük gerçekleşmeye de girer.
- Görüntüle: `/admin/study/students/{user}`. Haftalık rapor, dört hafta grafik, dönem geçmişi, konu analizi, anonim karşılaştırma ve hedef/manuel çalışma düzenleme.
- Geçmiş tablodaki hafta bağlantısı aynı öğrencinin panel detayına gider; personelin kendi ön yüz hesabına yönlendirmez.
- Kendi çalışma ekranı `/account/study` mevcut kapsamını korur; yalnız giriş yapan hesabın verileri. Ayrıca öğrenciye açık notlar gösterilir.

## 2. Erişim ve düzenleme

Panel girişinde `dashboard:access`, `users:view`, `study:access`, `study:view` ve yönetici/öğretmen tipi gerekir. Yazma için ayrıca `study:update` gerekir. Salt görüntüleme izninde editör ve not yazma formu görünmez; sahte istek de reddedilir.

Öğretmen yalnız mevcut `teacher_student` bağlantısındaki öğrencileri görür. Yönetici bütün öğrencileri görür; yönetici+öğretmen karma tipte yönetici önceliklidir. Personel hesapları panel öğrenci listesine girmez; kendi çalışma takibi ön yüzde kalır. Öğretmen ilişkisi ve izinler her yeni HTTP/Livewire isteğinde tekrar kontrol edilir. Öğrenci kimliği ve düzenlenen kayıt kimliği Locked’dır.

Hedef/manuel çalışma mevcut Faz 1–2 action’larıyla aynı kullanıcı mutex’i, transaction, beklenen eski değer ve audit kuralları üzerinden kaydedilir. Panelde yapılan kayıt öğrenciye aittir, işlem aktörü öğretmen/yöneticidir. Kapalı hedef yalnız yönetici; geçmiş çalışma ayarı kapalıysa öğretmen düzenleyemez. Aktivasyon tarihinden önceye veya geleceğe giriş yönetici için de açılmaz.

Frontend bileşenlerde korumalı owner/report hook’ları eklendi; kişisel sınıflar auth kullanıcısını kullanır. Yalnız ayrı panel alt sınıfları Locked öğrenci kimliğiyle panel yetkisini uygular. Hedef/çalışma sonrası raporun hedefli yenilenmesi hem kişisel hem panel ekranında çalışır.

## 3. Not kuralları ve yeni şema

Tek migration: `2026_10_14_130000_create_study_notes_tables`.

- `study_notes`: öğrenci, ilk yazar, UUID tekrar anahtarı, metin, öğrenci görünürlüğü, tarihler ve soft delete.
- `study_note_revisions`: işlem aktörü, create/update/delete, önceki/sonraki JSON ve tarih.
- Öğrenci görünürlüğü varsayılan false. Öğrenci yalnız kendi görünür notlarını görür.
- Bağlı bütün öğretmenler notları okuyabilir. Öğretmen yalnız kendi yazdığı notu düzenler/kaldırır; yönetici tümünü yönetebilir. Yazar sonraki düzenlemede değiştirilmez.
- Metin düz metindir; Blade escape eder, HTML çalıştırılmaz. Boş/yalnız boşluk metni ve 10.000 karakter üzeri giriş reddedilir.
- Aynı UUID ve aynı içerik ikinci kayıt oluşturmaz; farklı içerik/başka öğrenci/silinmiş UUID çakışması reddedilir. Stale edit 409; kayıt ve revision atomiktir.
- Kaydı kaldırma, ekrandaki kaydedilmemiş metni değil kayıtlı snapshot’ı kullanır.
- Not oluşturma/değiştirme bildirim üretmez. Hedef/çalışma bildirimi Faz 6’da ele alınacak.

Kullanıcı bu tek migration’ı açıkça onayladı; **yerel ana hypotenuse ve ayrı testing DB’de uygulandı**. Mevcut tabloların şeması/verisi değiştirilmedi; yeni izin veya modül aktivasyonu yok. Canlıda bu migration yayın adımında uygulanmalıdır.

## 4. SQL ve güncellik

Öğrenciler izinli SQL kapsamıyla seçilir. Liste manuel SUM, kaynak cevap UNION ve hedef alt sorgularını birleştirir; öğrenci başına rapor çağırmaz. Kaynaklar mevcut created_at fallback, gerçek cevap saati, yarım/tekrar/clear ve silinmiş sonuç kurallarını korur. Sayı, filtre, arama, sıralama ve sayfalama SQL’dedir; büyük öğrenci ID listesi PHP’ye alınmaz. Eşit sıralama id ile kararlıdır.

Liste bu ilk sürümde cache kullanmaz: yeni cevap, çalışma/ hedef değişimi ve öğretmen bağlantısı anında yansır. Response private/no-store. Notlar 10 kayıt/sayfa ve eager yazarla; dönem/konu/manuel sayfalamaları ayrı isimlidir. Üretim tepe yükü veya bütün veri hacminde hız garantisi verilmez.

## 5. Test kanıtı

İlk Faz 5 dar koşu: **23 test / 121 assertion başarılı**. Ek not/izin sınırlarıyla toplam **27 yeni senaryo**. İlk birleşik koşuda uygulama izin kontrolü 403 verdikten sonra aynı test bileşenine ikinci çağrı yapılması snapshot hatasına yol açtı; test doğrudan korunan işlemi çağıracak şekilde düzeltildi, tek senaryo geçti.

Son Faz 1–5 birleşik kapsam + etkilenen TimedExam:

```bash
docker compose exec -T laravel.test php vendor/bin/phpunit tests/Unit/StudyCalendarTest.php tests/Unit/StudyInputTest.php tests/Unit/StudyAutomaticWorkTest.php tests/Unit/StudyMetricsTest.php tests/Feature/StudyTrackingFoundationTest.php tests/Feature/StudyTrackingSettingsTest.php tests/Feature/StudyTrackingEntriesTest.php tests/Feature/StudyTrackingFormTest.php tests/Feature/StudyAutomaticIntegrationTest.php tests/Feature/StudyAnalyticsTest.php tests/Feature/StudyPanelTest.php tests/Feature/TimedExamTest.php
```

**203 test / 867 assertion başarılı**, PHP 8.5.8, 26.063s. Error/failure/skipped yok. Bütün proje suite’i değildir.

Yeni kapsam: her panel izni, kendi hesabını panel yoluyla açma denemesi, normal/karma admin, atanmamış öğrenci, kişisel owner değişmeme, tarih/sort/state validation, bağımsız filtreler, manuel+otomatik miktar, >100 oran, deterministik sayfalama, ilişki kaldırma, öğrenci adına kayıt/aktör audit, salt-okuma, kapalı hafta ve başlangıç sınırı, Locked ID, panel geçmiş linki, görünür/gizli/başka öğrenci notu, diğer yazarın notuna müdahale, admin düzenleme/soft delete/audit, UUID/stale/boş/aşırı uzun/HTML, pagination/cancel, modül kapalı yazma engeli. Queue/HTTP fake: gerçek bildirim/API yok.

Pint --test başarılı; TR/NL/EN/FR kaynakları **116 eş anahtar**, boş değer yok. Yeni 16 metin dört dilde dosya kaynağına eklendi; dil DB kayıtları veya dolu özel çeviriler değiştirilmedi. Yeni CSS/JS dosyası yok; APP_VERSION/root worker 1.1.25 korunur.

## 6. Tarayıcı kanıtı

Browser plugin not available; bundled Playwright + gerçek Chrome. Mevcut localhost80 korunarak gerçek Laravel HTTPKernel testing köprüsü ve outer transaction kullanıldı. Fixture ve UI kayıtları sonunda rollback; statik mevcut assets kullanıldı, dış domainler engellendi.

NL 1440 desktop / NL 390 mobile / FR 390 liste:

- İlk gerçek Bootstrap Table JSON/satır, Yenile, durum filtresi, boş sonuç ve Enter araması.
- Öğrenci detayına geçiş, hedef150save, manuel30add ve raporun60toplamına yenilenmesi.
- Not oluşturma, görünürlük seçimi, düzenleme ve onaylı kaldırma.
- Diğer öğretmenin notunda edit düğmesi yok; öğrenci kendi görünür notunu gördü, iki gizli notu görmedi; kaldırılan not da görünmedi.
- JS pageerror **0**, HTTP5xx **0**, liste/detay document yatay taşma **yok**.

İlk mobil kontrolde OneUI block-content overflow:visible kuralı tablo kaydırmasını bozdu. Table-responsive iç kapsayıcıya ayrıldı; final akış ve ölçü başarılı. İlk QA tekrarları filtre formunu gönderme/Enter ve CSS seçicisi düzeltmeleriydi; ürünün API tekrar denemesi değildir.

Kanıtlar `/private/tmp/eduan-study-faz5-browser-result.json`, `eduan-study-faz5-list.png`, `eduan-study-faz5-detail.png`, `eduan-study-faz5-mobile.png`, `eduan-study-faz5-fr-list-mobile.png`. Gerçek üretim/Safari/PWA cihazı veya harici bildirim teslimi kabulü değildir. FR genel panel Tümü/Filtrele/Görüntüle ve bazı menüler mevcut Türkçe fallback; yeni study metinleri Fransızca. Önceden raporlanan genel FR çeviri kapsamı otomatik genişletilmedi.

## 7. Ana DB ve operasyon

Son SELECT kontrolü: languages3, translations2557, exam_result_details149914, tests_result_details10695; önceki sayılar korundu. study_weekly_targets0 / study_activity_entries0 / study_notes0 / study_note_revisions0. Study dört ayarı null; modül etkinleştirilmedi. Yeni migration kaydı1.

Port/servis/volume, canlı, cron, gerçek paid API/mail/push, push/deploy değiştirilmedi. Eduan açık bırakıldı. İlgisiz .DS_Store commit’e girmez.

## 8. Değişen dosyalar

- app/Actions/Study/AuthorizeStudyPanel.php, SaveStudyNote.php
- app/Helpers/StudyPanelQuery.php, StudyAutomaticWork.php
- app/Models/StudyNote.php, StudyNoteRevision.php
- app/Http/Controllers/Backend/StudyController.php
- app/Livewire/Backend/Study/StudyEditor.php, StudentReport.php, StudyNotes.php
- app/Livewire/Frontend/Study/StudyForm.php, StudyReport.php, VisibleStudyNotes.php
- database/migrations/2026_10_14_130000_create_study_notes_tables.php
- routes/backend.php
- resources/views/backend/layouts/sidebar-menu.blade.php
- resources/views/backend/study/index.blade.php, show.blade.php
- resources/views/frontend/study/index.blade.php
- resources/views/livewire/backend/study/study-notes.blade.php
- resources/views/livewire/frontend/study/study-form.blade.php, study-report.blade.php, visible-study-notes.blade.php
- lang/tr/study.php, lang/nl/study.php, lang/en/study.php, lang/fr/study.php
- tests/Feature/StudyPanelTest.php
- Codex/74-CALISMA-TAKIBI-FAZ5-PANEL-VE-OGRETMEN-NOTLARI.md, Codex/index.html, AGENTS.md

## 9. Elle kontrol ve sonraki faz

Yerelde Ayarlar → Çalışma Takibi bölümünden modülü etkinleştirip başlangıç tarihini seçerek bakılabilir; bu fazda otomatik aktivasyon yapılmadı.

1. Öğretmenle Panel → Çalışma Takibi: yalnız kendi öğrencileri; seçili hafta/arama/filtre.
2. Görüntüle: hedef ve çalışma değiştir; öğrenci raporu ve günlük toplam yenilensin.
3. Önceki hafta: öğretmen geçmiş hedefi değiştiremesin; yönetici değiştirebilsin.
4. Varsayılan gizli not ekle; öğrencide görünmesin. Görünür yapınca öğrenci /account/study üzerinde görsün.
5. Aynı öğrenciye bağlı ikinci öğretmen ilk notu okuyabilsin, edit düğmesi olmasın; kendi notunu yönetebilsin.
6. Notu düzenle/kaldır; öğrenci ekranındaki karşılığını kontrol et.

Faz 5 tamamlandı. Sırada **Faz 6: OneSignal izin/cihaz yönetimi, son10bildirim ve yetkili seçilmiş alıcılara kuyruklu e-posta/push**. Harici anahtarlar, ayarlar ve gerçek gönderim kontrolleri o fazın ayrı onaylı adımlarıdır.

## 10. Güncel yerel aktivasyon ve Faz 1–5 tekrar kontrolü — 14 Ekim 2026

Kullanıcı çalışma takibini yerelde kalıcı açmayı istedi. Mevcut yetkili yönetici ve SettingSaveAction ile yalnız `studyTrackingEnabled=1` / ilk `studyTrackingStartedOn=2026-10-14` kaydedildi. Önceki iki ayar kaydı konteynerde `/tmp/eduan-study-activation-before-20261014.json` dosyasına private izinle alındı. Transaction içinde diğer ayar satırlarının hash’i aynı kaldı. Geçmiş çalışma kapalı ve asgari soru10 davranışı korunur; ham iki ayar null olarak kalır. Canlı değişmedi. Önceki bölümlerdeki kapalı/null aktivasyon kanıtı artık tarihsel durumdur; **yerel modül şu anda açıktır**.

Faz 1–5 + TimedExam aynı birleşik kapsam bir kez tekrarlandı: **203 test / 867 assertion başarılı**, PHP8.5.8,13.790s; hata/atlama yok. Bütün proje suite’i değildir. Testing DB fixture ve tarayıcı yazmaları transaction sonunda geri alındı.

Testing Chrome NL1440/390 + FR390 tekrar: gerçek tablo/filter/Entersearch/refresh, öğrenci hedef ve manuel kayıt/raporrefresh, not create/edit/confirmdelete ve öğrenci görünürlüğü başarılı. JSerror0/5xx0 ve liste/detay document taşması yok.

Ek olarak ana yerel DB’de sadece GET kabul eden HTTPKernel köprüsüyle mevcut yönetici üzerinden gerçek veri okunarak Ayarlar→Çalışma Takibi aktif1/tarih14Ekim/kapalı geçmiş/min10 kontrol edildi. Kişisel çalışma ekranı ve panel sayfası anlamlı ilk HTML; admin panel SQL371öğrenci. Kişisel manuel tarih alanı minimum2026-10-14. Ana ekran1440/390kontrolünde JSerror0/5xx0/overflowyok. Ana GET yan etkileri outer transaction ile rollback, örnek kayıt oluşturulmadı. Mevcut uygulama saat dilimi **Europe/Istanbul**; değiştirilmedi. Testing iş kuralları Brussels/DST varyasyonlarını ayrıca kapsar.

Kanıt `/private/tmp/eduan-study-local-enabled-browser-result.json`, `eduan-study-local-enabled-settings.png`, `eduan-study-local-account.png`, `eduan-study-local-account-mobile.png`; testing kanıtı Faz5browser-result güncel. Son ana SELECT: eskiDilCevapSayıları aynı; hedef/çalışma/not/revision0; enabled1 ve startedOn2026-10-14. Kod/migration/izin değişmedi. Dış API/mail/push/servis/port/deploy yok.

Kullanıcı OneSignal hesabının olduğunu, Hypotenuse uygulamasının olmadığını belirtti. Faz6hazırlığı: WebPush/CustomCode uygulaması; localhost için ayrı test uygulaması; AppID ve backend AppAPIKey. OneSignal’ın resmi belgeleri ayrı localhost uygulaması ve PWA çakışması için `/push/onesignal/` alt worker scope önerir. Henüz OneSignal uygulaması oluşturulmadı, secret kaydedilmedi, SDK yükleme/ücretli işlem/gerçek bildirim yapılmadı.

Kaynaklar: [Custom Code setup](https://documentation.onesignal.com/docs/en/web-push-custom-code-setup), [service worker](https://documentation.onesignal.com/docs/en/onesignal-service-worker), [Keys & IDs](https://documentation.onesignal.com/docs/en/keys-and-ids).
