# Çalışma Takibi — Faz 4: öğrenci raporları, grafikler ve analiz

14 Ekim 2026. Faz 1–3 yerel kontrol commit’i `705fc1ea`; Faz 3 kaynak commit’i `4bf2f14e`. Kullanıcı bütün bekleyen yerel migration/testleri onayladı; dört migration ana hypotenuse ve ayrı testing’de uygulandı. Canlıya aktarım değildir.

## 1. Son iş kararları

- Devam eden süreli sınavlar soru adedine **ve doğruluk/konu analizine dahil edilir**: bu tur kullanıcı açıkça bunu seçti. Tamamlanmış sınavda kaydedilmiş puanlama sonucu; devam eden sınavda seçili cevabın güncel doğru/yanlış bilgisi okunur. Sonuç/cevap puanlaması değiştirilmez. Devam eden sınav sırasında öğrenci raporunda bu analiz görülebilir; kullanıcı kararının bilinçli sonucudur.
- Karşılaştırmalar **yalnız öğrenci hesaplarında**. Öğretmen/yönetici kendi miktar/hedef/grafik/konu raporunu görür; öğrenci karşılaştırması görmez. Öğrenci ve personel tipinin beraber verildiği hesapta personel önceliklidir.
- Aktif + pozitif haftalık hedefli öğrenciler kohorta alınır. Pasif/silinmiş, hedefsiz/yalnız sıfır hedefli ve personel tipindeki hesaplar dışarıda kalır; normal/premium ayrımı yok.
- Öğretmen grubu mevcut öğretmenlerin öğrenci birleşimi, tekil user_id; ders ataması/seçici eklenmedi. Başka öğrencilerin ismi/email/id listesi ön yüze gönderilmez.
- Aynı değerde aynı sıra; görünen “ilk yüzde” eşit grubun sonunu kullanır. Hepsi eşitse herkesi ilk küçük yüzde saymaz. “Daha yüksek” oranı yalnız kesin olarak daha düşük değerlere dayanır. Doğruluk verisi olmayanlar o metrikte hariçtir.

## 2. Ekran

/account/study içinde yeni StudyReport ilk HTML’de, önceki hedef/çalışma formunun üstünde bulunur:

1. Soru, okunan sayfa ve çalışma saati için hedef, gerçek miktar, yüzde ve genel eşit ağırlıklı ortalama.
2. Gerçek kategori/genel yüzdeler100’ü aşar; sadece görsel gerçekleşme çubuğu100’de dolar. Boş/sıfır hedef ortalamaya katılmaz.
3. Seçilen hafta dahil son dört hafta: gri hedef/mor gerçekleşen miktar aynı ölçekte ayrı çubuklar. Gerçek miktar çubuğu kendi ölçeğinde kısıtlanmaz. Sıfır miktar boş çubuktur.
4. Mevcut dönem seçicisi +20hafta/sayfa geçmiş tablosu. Pasif eski dönemler okunabilir. Cumartesi–cuma tam haftalar, kısa hafta yok; iki dönemde aynı hafta aynı hesap/tek hedef kimliğidir.
5. Hedef yok, çalışma yok, sıfır kayıt, yüzde50altı, çalışma mevcut ve gelecek hafta durumları ayrılır.
6. Konu/doğru/yanlış/oran/etiket: >70%doğru güçlü, >70%yanlış zayıf, kalan dengeli; asgari ayarı uygulanır (varsayılan10). Boş/bilinmeyen doğruluk paydada olmaz. Eski silinmiş konular güvenli “bilinmeyen” etiketi alır.20konu/sayfa.
7. Anonim tüm öğrenciler/öğretmen grubu karşılaştırmaları: soru, sayfa, süre, genel gerçekleşme, doğruluk. İlgili hafta hedefsiz veya personel hesabında karşılaştırma bölümü açılmaz.
8. Hedef/manuel kayıt değişince yalnız StudyReport component’i event ile yenilenir. Rapor seçicisi mevcut formdaki girişleri yeniden yüklemez. Dönem tercihi oturumda tutulur; eski hafta bağlantısı seçilen dönemi taşır. Ayrıntı listesinin önceki15sayfa sözleşmesi korunur.

No-period durumunda haftalık rapor ve dört haftalık grafik çalışır; uydurma akademik yıl oluşturulmaz. Yeni AJAX backend JSON/rapor route’u veya ayrı kullanıcı kimlik sistemi eklenmedi. Native HTML/Bootstrap, mevcut member-form tasarımı; yeni CSS/JS/asset yok, APP_VERSION/root worker1.1.25 aynı.

TR/NL/EN/FR kaynak study.php100eş anahtar; yeni40metin. Dil DB, aktivasyon ve mevcut özel çeviri satırları değiştirilmedi.

## 3. Kod ve SQL

StudyMetrics saf gerçek yüzde/ortalama, minimum/70sınırı ve bağ/tie yüzdelik hesabıdır. StudyAnalytics izinli sahibin bütün dönemini toplu okur; hafta başına SQL çağrısı yapmaz. Kişisel günlük manuel aggregate + otomatik tarih aralığı UNION, dönem hedefleri eager week, seçili haftanın konu aggregate/name ilişkisi ve kohort toplu kullanıcı aggregate’leri kullanılır. PHP döngüleri bu alınmış sayısal veriler üzerinde çalışır.

StudyAutomaticWork shared forOwners query factory’si internaldır: authorize edilmeden endpoint olarak kullanılmaz. Public kişisel daily/rangeDaily ve rapor sahibi girişleri AuthorizeStudyAccess ile korunur. Tam dönem quantity hesabı önceki cevap saati/created_at fallback/partial/repeat/clear/deleted-result kurallarını korur.

Doğruluk seçimi için answers’a soru+cevap eşleşmeli LEFT JOIN yapılır. Devam eden süreli sınavda seçilmiş cevabın doğruluğu, tamamlanmış kayıtta bilinen detail doğruluğu kullanılır. Doğruluk bulunamayan satır soru miktarında kalabilir, konu/doğruluk paydasına girmez. Öğrencinin kendi verisidir; diğer öğrencilerin tekil doğru/yanlış veya seçtiği cevaplar gösterilmez.

StudyReport weekStart Locked; dönem girdisi integer/existing kayıt doğrulaması, güncel izin her render/refresh’te. Dönem/konu/listelerin paginator isimleri farklıdır. Diğer kullanıcı ID’si tarayıcıdan alınmaz. Yeni cache yok; yerel rapor hemen güncellenir. Yeni migration gerekmedi.

## 4. Test kanıtı

İlk Faz4dar koşu: StudyMetrics + StudyAnalytics + etkilenen StudyTrackingForm/StudyAutomaticIntegration **47test/205assert başarılı**.

Ardından kullanıcı isteğine uygun yalnız Faz1–4 birleşik kapsam + etkilenen süreli sınav sınıfı:

```bash
docker compose exec -T laravel.test php vendor/bin/phpunit tests/Unit/StudyCalendarTest.php tests/Unit/StudyInputTest.php tests/Unit/StudyAutomaticWorkTest.php tests/Unit/StudyMetricsTest.php tests/Feature/StudyTrackingFoundationTest.php tests/Feature/StudyTrackingSettingsTest.php tests/Feature/StudyTrackingEntriesTest.php tests/Feature/StudyTrackingFormTest.php tests/Feature/StudyAutomaticIntegrationTest.php tests/Feature/StudyAnalyticsTest.php tests/Feature/TimedExamTest.php
```

**176test/737assert geçti**, PHP8.5.8,10.875s, error/failure/skipped yok. Tüm proje suite’i değildir. Yeni11saf +10DB/Livewire senaryosu:

- >100gerçek/genel yüzde, sıfır/boş hedef hariç.
- >70/tam70 ve minimum1/10, boş doğruluk.
- yüzde/tie/sadece kendisi/eksik veri.
- dört hafta ve53tamhaftalı dönem, ortak hafta/iki dönem.
- hedef yok/çalışma yok/sıfır/düşük ayrımı.
- pasif/hedefsiz/personel dışlama ve iki öğretmen üzerinden tekil grup.
- kendi raporu/personel karma tip önceliği, kişisel miktar ve gizlilik.
- devam eden sınav doğruluğu, mevcut correct/completed kayıtlarının değişmemesi.
- dönem değiştirme/20sayfa/reset/rapor refresh/invalid dönem.
- izin geri alma/Lockedweek, bütün yıl SQL sayısının hafta başına artmaması.

12PHP syntax/Pint, iki Blade derlenmiş PHP syntax ve dört dil100anahtar eşliği/boş değer kontrolü başarılı. Son UI düzeltmesi backend sonrası yalnız çubukların Bootstrap görünümüydü; gerçek browser’da doğrulandı, PHP suite’i tekrar edilmedi.

## 5. Tarayıcı kanıtı

Browser plugin not available; bundled Playwright + Chrome. Mevcut localhost80 adresi korundu; PHPHTTPKernel testing köprüsü ve outer transaction kullanıldı. İşlemler sonunda rollback. Main ayarlar veya veri üzerinde UI yazımı yok. Statik assets mevcut app’den okundu; dış servisler engelli.

| Kontrol | Sonuç |
|---|---|
| Sayfa/başlık, anlamlı ilk HTML, hata ekranı | Başarılı |
| NL1440desktop / NL390mobile / FR390mobile | Başarılı |
| Hedef150save, çalışma50add, reload, düzenleme25 | Başarılı |
| Raporun otomatik yenilenmesi, manuel+test toplam55 | Başarılı |
| Okuma/süre150%, genel112.2%, sadece görünür yüzde çubuğu dolu | Başarılı |
| Kısa dönem→yıl, ikinci geçmiş sayfası/Mayıs16ilkhafta | Başarılı |
| Dolu konu80% / güçlüNL-Sterk veFR-Fort | Başarılı |
| Öğrenci anonim karşılaştırma | Göründü, PHP kapsam/gizlilik testleri ayrıca geçti |
| JSerror / HTTP5xx / sayfa yatay taşması | 0 / 0 / yok |

İlk görünüm kontrolünde0hedef native progress çubuğu dolu göründü; Bootstrap0width çubuklarıyla düzeltildi ve aynı hedef akışı tekrar doğrulandı. Bu final screenshot’ta boş değer boş çubuktur.

Kanıt: /private/tmp/eduan-study-faz4-browser-result.json, eduan-study-faz4-desktop.png, eduan-study-faz4-mobile.png ve eduan-study-faz4-fr-mobile.png. Gerçek Safari/PWA cihazı, üretim worker teslimi ve peak load kabulü değildir. Report dışındaki FR hesap geri dönüş bağlantısı mevcut account çeviri fallback’iyle Türkçe görünüyor; kapsam dışı bulgu, otomatik değiştirilmedi.

## 6. Ana DB ve operasyon sınırı

Son salt-okunur anaDB: languages3,translations2557,exam_result_details149914,tests_result_details10695 aynı; study_weekly_targets0/study_activity_entries0, study ayarları null, main dolu yeni cevap saati0. Yalnız önceki açık onaylı dörtmigration/grant etkisi var. Modül ana DB’de etkin değil; kullanıcı yerelde Ayarlar→Çalışma Takibi’nden etkinleştirip başlangıç tarihini seçerek elle bakabilir. Bu tur otomatik etkinleştirilmedi.

Docker servisi/port/volume değiştirilmedi; Eduan açık bırakıldı. Yeni migration, gerçek paidAPI/mail/push, canlı erişim, push/deploy yok. İlgisiz .DS_Store korunur ve commit’e girmez.

## 7. Değişen dosyalar

- app/Helpers/StudyMetrics.php
- app/Helpers/StudyAnalytics.php
- app/Helpers/StudyAutomaticWork.php
- app/Livewire/Frontend/Study/StudyReport.php
- app/Livewire/Frontend/Study/StudyForm.php
- app/Http/Controllers/Frontend/AccountController.php
- resources/views/livewire/frontend/study/study-report.blade.php
- resources/views/frontend/study/index.blade.php
- lang/tr/study.php,lang/nl/study.php,lang/en/study.php,lang/fr/study.php
- tests/Unit/StudyMetricsTest.php
- tests/Feature/StudyAnalyticsTest.php
- Codex/73-CALISMA-TAKIBI-FAZ4-RAPOR-GRAFIK-VE-ANALIZ.md
- Codex/index.html
- AGENTS.md

## 8. Elle kontrol ve sonraki faz

Yerel modülü etkinleştirdikten sonra Hesap→Çalışma Takibim (/account/study): haftalık hedef ve miktarı,4hafta grafiği, dönem seçimi/20sayfa geçmiş ve konu analizini gör. Aynı haftaya yeni giriş yapınca rapor kendini yenilemeli. Öğrenci rolünde pozitif hedef varsa anonim karşılaştırma görünür; öğretmen/yönetici kendi raporunu görür. Diğer öğrencilerin kimliği açıklanmaz.

Sonraki **Faz5:** panel/öğrenci kapsamlı liste-filtre-detay ve öğretmen notları. Bildirim ve toplu iletişim Faz6, genel modül/yayın kapısı Faz7. Bu tur sonraki faza geçilmedi.
