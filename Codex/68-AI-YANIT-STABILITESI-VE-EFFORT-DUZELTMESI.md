# 68 — AI yanıt stabilitesi ve effort listesi

## 1. Kapsam ve bulgu

7 Ekim 2026 kullanıcı manuel AI denemelerinde genel hata sonrası yeniden denemede cevap geldiğini ve effort listesinin tekrarlandığını bildirdi. Yerel ana veritabanının son 12 `chatgpt` kaydı yalnız SELECT ile incelendi; ajan ücretli API isteği yapmadı. Başarılı HTTP 200/completed yanıtları arasında `solution_not_ready` ve `invalid_solution_text` vardı. İlk durumda model kaynak çözümde çelişki bildirmişti; ikinci durumda açıklamadaki zararsız matematik komutu geçerli yanıtın reddine yol açmıştı. İncelenen istekler yaklaşık 4–14 saniyeydi; bu örneklerde bağlantı hatası yoktu.

## 2. Düzeltmeler

- `AiSolutionResult`: unreadable/inconsistent JSON ayrıca doğrulanır; boş steps, null final formül ve dolu, güvenli açıklama zorunludur. Bunlar ready cevap değildir. HTML, kontrol karakteri, bozuk JSON/şema ve formül güvenlik kontrolleri korunur.
- Metin alanındaki matematik komutu tek başına bütün ücretli yanıtı geçersiz kılmaz. Metin Blade ile escape edilir; TeX veya HTML olarak çalıştırılmaz. Formül alanlarının mevcut kontrolü ve KaTeX renderer değişmedi.
- `SolutionAi`: kaynak çelişkisi/okunamama gerekçesini genel hata yerine gösterir; AnswerAI kaydı ve kota tüketimi yapılmaz. Önceki uyarı yeni yetkili eylemde temizlenir; kayıtlı geçerli cevap tekrar kullanılabilir. Soru kilidi alınamadığında üretim sürüyor mesajı gösterilir. Otomatik tekrar veya model fallback eklenmedi.
- AI ayarlarındaki Bootstrap Select sürümü refresh sırasında dahili option verisini ekliyordu. Yalnız effort picker destroy/init ile yeniden kurulur; model değişiminde seçenekler ve seçili etiket tekrarlanmaz. Diğer selectpicker alanları/vendor kodu değiştirilmedi.
- `solution-v3` prompt: az matematik bilen öğrenci, sembollerin açıklanması, gerekli ara işlemler, kullanılan kuralın nedeni ve cevap seçeneğine bağlantı. Küçük notasyon farkı ile güvenilir cevabı engelleyen gerçek çelişki ayrılır. Çıktı sınırı6000, Luna/low ve görsel detail high korunur. Daha ayrıntılı yanıtın gerçek hız/kalite etkisi ücretli deneme olmadan ölçülmedi.

## 3. Ayar ve veri sınırı

İlk düzeltme turunda ana DB yazımı yapılmadı. Ardından kullanıcı iki prompt'u önce ana DB'de uygulamamı açıkça istedi. 7 Ekim 2026 18:58 yerel saatte yalnız yerel **hypotenuse** veritabanındaki iki mevcut global `gptDeveloperMessage` ve `gptUserMessage` satırı solution-v3 metinleriyle tek transaction içinde güncellendi. Önce özel `/private/tmp` yedeği alındı; okunan değerlerin kodla eşleşmesi doğrulandı. Diğer settings satırlarının hash'i ve işlem başındaki483AIcevabın count/content checksum'u aynı kaldı. Model=gpt-6-luna/effort=low, API anahtarı ve günlük limit korundu. Testing ve canlı DB'ye uygulanmadı; migration/silme/API çağrısı yapılmadı. Sonraki ücretli denemeler yine her istek için ayrı onaylıdır.

## 4. Dar kanıt

- Onaylı prompt güncellemesi sonrası commit edilmiş ana DB değerleri v3 ile eşleşti; dış istek göndermeden oluşturulan gövdede developer prompt tek kez yer aldı, user yer tutucuları çözüldü. HTTP çağrısı0. Diğer ayar ve cevap koruma kontrolleri transaction içinde başarılı; önceki PHP/browser testleri bu veri yazımı için tekrar çalıştırılmadı.

- AI Unit+Feature: **29 test/91 doğrulama**, başarılı. Zararsız matematik metni, geçerli kaynak tanısı, tanının cevap/kota olmaması, önceki uyarı temizliği, HTTP tek istek ve kayıt tekrar kullanımı dahil.
- Mevcut AI yetki/kota testleri: **7 test/33 doğrulama**, başarılı. Toplam36 farklı dar senaryo; genel suite çalıştırılmadı.
- Yalnız değişen5PHP dosyasında Pint başarılı.
- Chrome sahte Blade ön izlemesi, gerçek yerel Bootstrap Select+Bootstrap: Luna/Sol/eski model arasında tekrarlı geçiş, daima4effort seçeneği ve tek doğru seçili etiket. 1280/390px, mevcut4formül/dinamik çizim/geçersiz formül kaynak fallback kontrolü başarılı. Page error0; dış ağ isteği0. İlk OneUI kabuğu eksik preview hatası, preview'da Bootstrap kullanılarak giderildi; ürün OneUI değiştirilmedi. Gerçek ücretli model kalitesi veya üretim kabulü değildir.
- İlgili diff whitespace kontrolü; APP_VERSION/root worker **1.1.23**, canlı deploy/commit yok.

## 5. Değişen dosyalar

`app/Helpers/AiSolutionRequest.php`, `app/Helpers/AiSolutionResult.php`, `app/Livewire/Frontend/Ai/SolutionAi.php`, `resources/views/backend/settings/tabs/ai.blade.php`, `tests/Unit/AiSolutionResultTest.php`, `tests/Feature/AiSolutionApiTest.php`, `.env`, `public/pwa-sw.js`, bu rapor, `Codex/index.html`, `AGENTS.md`.

Kaynak soru/çözümde saptanan içerik çelişkileri otomatik düzeltilmedi. Yeni prompt mevcut cevapları yeniden üretmez; kullanıcı manuel kontrolünde AI kaydı olmayan bir soru gerekir. Ajanın ücretli test yetkisi verilmedi.
