# İlk faz bulguları ve açık sorular

> En güncel CRUD/liste düzeltmeleri ve sayfa testleri `18`; hedef Bootstrap Table geçişi `19`; sayfa bazlı standart/eksik kapsam `20` dosyasındadır. Sonradan yanıtlanan kararlar ilk tarama sorularından üstündür.

> Güncel toplam istatistik kararı, panel işlev testleri ve yeni doğrulanmış CRUD bulguları `17-PANEL-ISLEV-TESTLERI-VE-ISTATISTIK.md`; yeni özellik standart listesi `16-PROJE-STANDARTLARI.md` içindedir.

> Sonraki kesin lig/mail kararları ve tamamlanan gönderim düzeltmesi: `15-LIG-VE-MAIL-KARARLARI.md`. Son deneme isteği iptal edildi; yeni gönderim izni users:send-email.

## İlk tarama bulguları ve güncel durum

Güncel devam paketi ve kalan sınırlar için `13-GUNCEL-DUZELTMELER-VE-TEST-KANITI.md` esas alınır. Aşağıdaki sorular ilk tarama geçmişidir; öğretmen atama ve PWA ad kararı sonradan yanıtlandı.

İlk dar düzeltme paketi uygulandı; ayrıntı ve test kanıtları `11-ILK-DUZELTMELER-VE-SINAV-AKISI.md` içindedir. Aşağıda giderildiği yazılmayan noktalar sonraki kapsam için araştırma adaylarıdır.

| Öncelik | Bulgu | Kaynak ve sınır |
|---|---|
| Giderildi | `public/systeminfo.php` doğrudan `phpinfo()` çağırıyordu. | Dosya kaldırıldı; yerel HTTP 404. Üretime dağıtılmadı. |
| Uygulandı | Sınav süresi/aktif deneme davranışı sunucuda denetleniyor. | `StartTestResult`, `SaveTestResultAnswer`, `CompleteTestResult` ve `TimedExamTest`; tekrar giriş süreyi/cevapları korur, tamamlandıktan sonra yeni deneme mümkündür. |
| Uygulandı; ortam doğrulaması açık | Süre sonunda DB cevaplarıyla sonuç tamamlanıyor. | Handler testleri var. Kullanıcı her dakika cron ile `--stop-when-empty` düzenini ve gecikmesini kabul etti; gerçek işlenme gecikmesi ölçülmedi. |
| Giderildi; dar negatif testler var | Test yanıtlama Livewire public state'inde doğru cevap ve soru bilgisi taşıyor. | `Frontend/Tests/SolveForm`; doğruluk/soru ilişkisi sunucudan hesaplanır; altı negatif/izinli test var. |
| Kısmen giderildi | Giriş/kayıt/verify route'larında throttle/signed middleware görünmedi. | `routes/frontend.php`, `AuthController`, Livewire auth; 5/dakika limit uygulandı; eski verify linkleri kullanıcı kararıyla korundu. Gerçek proxy/IP zinciri ayrı doğrulanmalı. |
| Giderildi | `TestForm` güncellemede `exams:update` kullanıyordu; iki formun `save` eylemi izinsiz yazabiliyordu. | İzin `tests:update` düzeltildi; `save` izni model durumundan hesaplanır. `ExamForm::updateExamRelations` private yapıldı. Negatif ve izinli kayıt testleri var. |
| Giderildi | Çeviri yazma eylemlerinde `languages:translate` denetimi yoktu. | Controller/mount/updated/save korundu; inline yazma dil kapsamındadır. Yetkisiz, başka dil ve izinli JSON üretme testleri var. |
| Giderildi | Misafir sınav başlangıcında null kullanıcı hatası vardı. | Giriş rotasına yönlendirme HTTP feature testiyle doğrulandı. |
| Dar dosya sınırı uygulandı | `SettingsController::store` tek genel request'i `SettingSaveAction`a gönderiyor; çok sayıda ayar/dosya etkisi var. | Görsel/10 MB/MIME/uzantı/izin ve genel files yasağı uygulandı; bütün ayar iş kuralları kapsanmadı. |
| Orta inceleme | `resetCache()` tüm cache'i ve optimize cache'i temizliyor; üretim dışında servis sağlayıcı her açılışta cache flush yapıyor. | `Helpers/Functions.php`, `Providers/AppServiceProvider.php`; performans ölçmeden değiştirme. |
| Dar düzeltme uygulandı | Sitemap URL, aktiflik ve `all()` sorguları incelenmeli. | `frontend/home/sitemap.blade.php`; İki HTTP/XML testinde canonical ve sitemap doğrulandı. |
| Dar düzeltme uygulandı | PWA worker `/assets/` altından kaydediliyor; kök/hesap sayfası kontrolü doğrulanmadı. | `public/assets/app.js`, `public/assets/pwa-sw.js`; Root scope ve özel HTML cache yasağı uygulandı; offline isim kararı onaylandı. Yedi VM testi ve online offline-HTML tarayıcı akışı var; gerçek ağ kesintisi açık. |
| Kaynak eşitlendi; ortam açık | Kaynak varsayılan dil `nl` yapıldı; seed hâlâ `tr,nl`, `en` kayıt kararı açık. | `SetLocale`, `SettingSeeder`, `config/app.php`, `.env.example`; mevcut DB/ortam ayarı değiştirilmedi. |
| Doğrulama ihtiyacı | En son migration dahil yerel kayıtlar uygulanmış, ama fiziksel şema karşılaştırılmadı. | Salt okunur `migrate:status`; üretim hiç incelenmedi. |

Bu satırlar önceliklendirme adaylarıdır; doğrulanmış güvenlik açığı veya canlı hata hükmü yalnız kaynaktaki somut gözlemden daha ileri götürülmemelidir. İlk tarama salt okunurdu; sonraki dar düzeltmeler yukarıdaki güncel durum sütununda belirtilmiştir.

## Proje sahibine karar soruları

1. **Yanıtlandı:** Flemenkçe kodu `nl` ve hedef varsayılan dil `nl`. İngilizcenin aktif `Language` kaydı olarak ne zaman ekleneceği ayrıca açık.
2. **Yanıtlandı:** Tek giriş tek aktif denemedir. Süre/cevap korunarak devam edilir; cevaplar süre dolana kadar değişebilir. Tamamlandıktan sonra bütün sınavlar yeni süreli denemeyle tekrar çözülebilir. Sayfa kapalıyken queue işi tamamlar. Lig hesabına yalnız tamamlanmış sınav denemeleri girer. Canlıda her dakika cron ve `--stop-when-empty` kullanıldığı bildirildi; kullanıcı mevcut cron gecikmesini kabul etti. Sürekli worker planı istenmedi.
3. Test/`Exam` ve sınav/`Test` adları için ileride hedef SQL/model/route isimleri nedir? Eski URL ve veri kimliklerinin korunma zorunluluğu var mı?
4. Yerel veritabanı, staging ve üretim arasında hangisi şema için güvenilir kaynak? Eksik olduğunu düşündüğünüz migration/kolonlar veya elle uygulanmış SQL var mı?
5. Öğretmen/okul rolü birden fazla okula, öğretmene ve öğrenciye nasıl bağlanmalı? Mevcut `teacher_student` eşleştirmesi ürün ihtiyacını tam karşılıyor mu?
6. Bir sonraki fazda önce test/şema güvenliği mi, Laravel sürüm yükseltmesi mi? Hedef Laravel sürümü ve PHP çalışma ortamı nedir?
7. Rappasoft tablosunda bugün ölçülen belirli yavaş ekran/sorgu var mı? Bootstrap Table'a geçme değerlendirmesi için kabul ölçütü nedir?
8. PWA çevrimdışı kullanım beklentisi ve cache'lenmesine izin verilen sayfalar hangileri? Kullanıcıya özel sonuçlar çevrimdışı görünecek mi?
9. SEO için üretim alan adı, çok dilli URL düzeni, indekslenmeyecek sayfalar ve Search Console erişimi/ölçümleri neler?
10. Paket sadeleştirmesinde korunması gereken ücretli/özel lisanslar veya entegrasyonlar var mı? Yedek, Cloudflare, Sentry, AI ve e-posta çalışma şartları nelerdir?

Yanıt gelmeden bu soruların sonuçları kod standardına kesin karar olarak eklenmez. Belirli yeni geliştirme istenince yalnız o geliştirmeyi engelleyen sorular sorulur.

## Dar test hazırlığında görülen ek adaylar — tarihsel kayıt

- **Sonradan giderildi:** `TestForm::initializeExistingTest` ve `ExamForm::initializeExistingExam` nullable DB `content` alanını doğrudan string özelliğe atar. `TestForm` fixture'ında null içerik TypeError üretti; yetki testi içerik sözleşmesine uygun fixture ile sürdürüldü. Devam paketinde null değer boş string olarak açılır; iki nullable/fallback testi geçti.
- **Önceki aday geri çekildi:** `ExamForm::initializeExistingExam` içindeki `toArray()` kullanımının iç içe soru modelini diziye çevirdiği değerlendirmesi yanlıştı. Kurulu Collection davranışı incelendi ve DB kullanmadan aynı `map → toArray → keyBy → sortBy → toArray` zinciri çalıştırıldı; içteki soru nesnesi model olarak kaldı. Bu iddia hata olarak takip edilmez. Dolu mevcut test düzenleme akışının test kapsamı hâlâ eksiktir.

Güncel modül kapsamı, test yazma hazırlığı ve kalan sorular: `12-TEST-KAPSAMI-VE-HAZIRLIK.md`.
