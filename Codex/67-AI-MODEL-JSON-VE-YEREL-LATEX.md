# 67 — AI model, JSON ve yerel LaTeX fazı

## 1. Onay ve kapsam

7 Ekim 2026: kullanıcı yalnız yeni GPT-6 Luna ve GPT-6 Sol seçeneklerini istedi; GPT-6.1 eklenmedi. Mevcut model seçenekleri eski ayarlarla uyum için korunur. Her gerçek ücretli testte ayrı onay, tek soru ve tek istek zorunludur. Bu fazda gerçek OpenAI isteği yapılmadı; HTTP testleri fake/preventStrayRequests kullanır. Günlük limit ve mevcut cevapların soru bazında yeniden kullanımı korunur.

## 2. İstek ve sonuç sözleşmesi

- `AiSolutionRequest`: model/effort uyumu, developer/user prompt'ları, soru ID/locale/başlık yer tutucuları ve iki görselin VRAAG/OFFICIËLE OPLOSSING etiketleri.
- Responses API strict `json_schema`; kökte `status`, `steps`, `final_answer`, `final_answer_latex`. Her adımda `rule`, `definition`, `explanation`, nullable `latex`; fazladan anahtar yok.
- Metin alanları düz metin; matematik yalnız formül alanlarında, dolar delimiter'ı olmadan. Eski `steps/final_answer` kayıtları ayrı gösterim yolunda okunmaya devam eder.
- `AiSolutionResult`: yalnız tamamlanmış, reddedilmemiş, `ready` sonuç; boş adım/açıklama/sonuç, tip/anahtar/JSON hataları reddedilir. JSON yalnız bir kere çözülür.
- Formüllerde kontrol karakteri, dolar delimiter'ı, tehlikeli komut, dengesiz süslü parantez ve begin/end kontrolü. Bu, matematik doğruluğu veya tüm TeX gramerinin sunucu tarafında doğrulanması değildir.
- Çıktı sınırı 6000 token; görüntü üretme aracı ve gereksiz reasoning summary kaldırıldı. TLS doğrulaması açık; connect 10 saniye, toplam 120 saniye. Otomatik retry veya model fallback yok.
- API anahtarı yoksa, görsel eksik/geçersizse veya model/effort uyumsuzsa dış istek yapılmaz.

## 3. Kayıt, kota ve log

Mevcut DB cevabı API'ye gidilmeden kullanılır; eski cevaplar prompt/model değişti diye geçersizleştirilmez. Yeni cevap ancak sonuç doğrulaması geçince kaydedilir. Başarısız üretim kotayı tüketmez. Mevcut DB kullanıcı kilidi ve güncel site günü korunur; aynı soru için 150 saniyelik cache lock yeni eşzamanlı üretimi engeller. Kilitten sonra DB current read ile başka işlemde kaydedilmiş cevap tekrar aranır. Gerçek paralel üretim yük testi yapılmadı.

`chatgpt` logunda ham API JSON yanıtı/usage, doğrulanmış sonuç, HTTP durumu, süre, model/effort, prompt sürümü/hash'i ve hata türü bulunur. API anahtarı ve Base64 giriş görselleri kaydedilmez. Log yazımı hata verirse geçerli ücretli yanıt sırf log nedeniyle atılmaz. Önceki salt okunur incelemede ana DB'de chatgpt logu yoktu; bu fazın log kontrolü sahte yanıtla testing DB'de yapıldı.

## 4. LaTeX gösterimi

Mevcut KaTeX **0.16.22** JS/CSS ve 20 WOFF2 fontu yerel statik dosyadır; MIT lisansı birlikte tutulur. AI sayfası ve panel AI detayında renderer yüklenir. Yeni formüller ve eski düz JSON formülleri tarayıcıda çizilir; bu gösterim CodeCogs isteği veya queue gerektirmez. Yeni final cümlesi formülden ayrı gösterilir. Livewire sonradan eklenen DOM için MutationObserver; `wire:ignore` ve formül hash key'i çizimin morph sırasında bozulmasını önler.

KaTeX trust=false, throwOnError=true, maxExpand=500, maxSize=20, MathML+HTML. Parse hatası kaynak metni güvenli textContent olarak bırakır; dış isteğe veya yeniden AI üretimine dönüşmez. Yönetici tarafından önceden HTML/görsel olarak düzenlenmiş kayıtların eski gösterim/düzenleme sözleşmesi korunur. Panel editöründeki görsele dönüştürme yolu mevcut CodeCogs helper'ını kullanmaya devam eder. Eski DB kontrol karakterleri topluca onarılmadı; eski içerik yeniden üretilmedi. `\ce` gibi ek paket komutları çekirdek KaTeX kapsamı değildir; prompt standart LaTeX ister.

## 5. Ayarlar ve onaylı yerel veri işlemi

Model/effort formda uyumlu seçeneklere daraltılır; sunucu da doğrular. Dizi gibi bozuk model girişi 422 validation olur. Eski modellerin seçenekleri korunur.

Kullanıcı ayrı onay verdi: **yalnız yerel ana hypotenuse DB** global `gptModel=gpt-6-luna`, `gptEffect=low`, `gptDeveloperMessage`, `gptUserMessage` güncellendi. Önce özel `/private/tmp` yedeği alındı; tek transaction içinde dört mevcut satır güncellendi. Yeni developer prompt 1649, user prompt 215 karakter. Diğer settings satırları hash ile, 484 AI cevabı count/content checksum ile değişmedi. API anahtarı, günlük limit ve dil kayıtları aynı; ana DB'ye test cevabı eklenmedi. Testing kalıcı ayarları değiştirilmedi; test fixture'ları transaction ile geri döner. Migration, canlı DB, deploy ve commit yok. Canlıdaki dört ayarın aktivasyonu ileride ayrı yayın adımıdır.

## 6. Değişen dosyalar

- `app/Helpers/AiHelper.php`, yeni `AiSolutionRequest.php`, `AiSolutionResult.php`.
- `app/Livewire/Frontend/Ai/SolutionAi.php`, `app/Livewire/Ai/AnswerAiForm.php`.
- `app/Actions/Settings/SettingSaveAction.php`, `resources/views/backend/settings/tabs/ai.blade.php`.
- `resources/views/components/ai/steps.blade.php`, yeni `formula.blade.php`, `legacy-text.blade.php`.
- `resources/views/livewire/frontend/ai/solution-ai.blade.php`, `resources/views/frontend/layouts/app.blade.php`, `resources/views/backend/ai/edit.blade.php`.
- Yeni `public/assets/ai-formulas.js`, `public/assets/vendor/katex/` JS/CSS/20 font/LICENSE.
- `.env APP_VERSION` ve `public/pwa-sw.js`: **1.1.22**.
- Yeni `tests/Unit/AiSolutionResultTest.php`, `tests/Feature/AiSolutionApiTest.php`; `tests/Feature/StabilityReleaseTest.php` yalnız AI başarı fake'i yeni gerçek sözleşmeye uyarlandı.
- Bu kayıt, `Codex/index.html`, `AGENTS.md` güncel faz özeti.

## 7. Dar test kanıtı

- İlk Unit+Feature: **23 test / 64 doğrulama**, başarılı.
- Eski AI izin/kota/kayıt senaryoları: StabilityReleaseTest filter `test_ai_|test_cached_ai_`, **7 test / 33 doğrulama**, başarılı.
- Eski JSON ve bozuk model girdisi eklendikten sonraki son AI Feature: **7 test / 39 doğrulama**, başarılı. İlk koşudaki beş Feature senaryosu bu yediye dahildir; **32 farklı dar senaryo** toplamı, genel suite değildir.
- Yalnız ilgili PHP dosyalarında Pint; yeni JS syntax ve ilgili diff whitespace kontrolü başarılı.
- Yerel KaTeX: kesir/beta, aligned, mathbb, kök, logaritma, türev, limit ve NaCl olmak üzere **8 temsili formül** parse edildi. OpenAI çağrısı yok.
- Gerçek Chrome, sahte yanıttan üretilmiş Blade ön izlemesi: 1280px/390px, dört formül, metin/formül ayrımı, dinamik DOM çizimi, invalid fallback, HTML enjeksiyonu yok, yeni iki model ve effort uyumu. Page error 0; bütün ağ yerel dosyalarla karşılandı. Gerçek hesapla ücretli AI üretimi veya canlı UI kabulü değildir.
- Ön izleme ilk denemede script stack'i bağımsız Blade render nedeniyle taşımadı; dış Blade şablonuyla son kontrol geçti. Test yardımcı `component` adı Laravel metoduyla çakıştı, `aiComponent` olarak düzeltildi.

## 8. Sonraki kapı

**Kullanıcının yerel manuel kontrolü:** Ayarlarda Luna/low seçimini doğrula; AI kaydı olmayan tek sorunun çözüm ekranından bir kere üret. Açıklama, sonuç ve formül çizimini incele. Geçerli cevap kaydedilmişse aynı soruyu yeniden açıp kayıtlı cevapla geldiğini kontrol et. İlk üretim ücretlidir; kayıtlı cevabın tekrar açılması yeni API isteği değildir. Kullanıcı denemesi ajan için ayrıca ücretli deneme yetkisi vermez. Ajanın her gerçek API isteği ayrı onaylı kalır.

Kayıtlı AI cevabı olmayan, okunabilir soru/çözüm görselleri bulunan tek soru seçilir. Gerçek Luna çağrısından **önce** soru, model, tek istek ve kayıt hedefi belirtilerek ayrı onay alınır. Otomatik ikinci deneme/Sol'a fallback yapılmaz. JSON, formül çizimi, matematik/doğru çözüm ilişkisi, süre ve kullanım maliyeti bu tek cevap üzerinden kullanıcıyla değerlendirilir. Gerekirse Sol için ayrıca ayrı tek istek onayı istenir. Bu faz her soruda matematiksel doğruluk veya gerçek model kalitesi garantisi vermez.

## 9. Birincil kaynaklar

[GPT-6 Luna](https://developers.openai.com/api/docs/models/gpt-6-luna), [GPT-6 Sol](https://developers.openai.com/api/docs/models/gpt-6-sol), [Structured Outputs](https://developers.openai.com/api/docs/guides/structured-outputs), [KaTeX API](https://katex.org/docs/api.html), [KaTeX güvenlik](https://katex.org/docs/security.html). API model/şema uyumu bu resmî kaynaklarla kontrol edildi; uygulama kalitesi fake testlerden ayrı değerlendirilir.
