# Mimari ve çalışma akışları

## Giriş noktaları

`bootstrap/app.php` web route'unu `routes/web.php` üzerinden yükler; `routes/api.php` yoktur. `routes/web.php` ortak `web`/`set_locale` gruplarıyla `routes/frontend.php` ve `routes/backend.php` dosyalarını bağlar. Panel `config/backend.php` ile gelen `/admin` önekini kullanır; `auth`, `set_locale`, `can:dashboard:access` kapısı vardır. Panelin sayfa route'ları `Buki AutoRoute::auto` ile controller metotlarından üretilir. Ziyaretçi route'ları açık `Route::get` tanımlarıdır. Livewire update route'u ayrıca ayarlanmıştır.

Controller'lar çoğunlukla sayfayı ve başlığı seçer: `app/Http/Controllers/Backend/*`, `Frontend/*`. Form/etkileşim `app/Livewire/*` içinde; iş adımları `app/Actions/*` içinde; kalıcı ilişkiler `app/Models/*` içinde. Örnek zincir: `routes/backend.php` → `Backend/ExamController` → `backend/exams/*.blade.php` → `Livewire/Exams/ExamForm` → `Actions/Exams/CreateOrUpdateExamAction` + `UpdateExamQuestionsAction` → `Models/Exam` ve `exams_questions`.

## Bölümler

| Alan | Kaynaklar | İşlev |
|---|---|---|
| Soru havuzu | `Lesson`, `Topic`, `Question`, `Answer`; `Livewire/Questions` | Ders ve konu seçimi; soru, seçenek ve çözüm görselleri. |
| Test (ziyaretçi adı) | `Exam`, `ExamResult`, `ExamResultDetail`, `exams_questions`; `Frontend/Tests` | Seçilen sorularla çözülen test; soru başına kayıt; favori ve değerlendirme. |
| Sınav (ziyaretçi adı) | `Test`, `TestsSection`, `TestsQuestion`, `TestsResult`, `TestsResultDetail`; `Frontend/Exams` | Bölümlü sınav, süre, geçme puanı, doğru/yanlış puanı ve tamamlanma. |
| Hesap ve okul | `User`, `teacher_student`, `Frontend/Account`, `Livewire/Users` | Profil, çözülenler, sonuçlar, öğretmen/öğrenci eşleştirmesi. |
| Sıralama | `League`, `LeagueResult`, `Season`, `Jobs/CalculateLeagueResult` | Sezon ve lig sonuçları; zamanlanmış hesaplama. |
| İçerik | `Page`, `Blog`, `Faq`, `Contact`, `Newsletter` | Çok dilli sayfa, blog, SSS, iletişim ve abonelik. |
| Yapay zekâ | `AnswerAI`, `UserAiUsage`, `LatexImage`, `AiHelper` | Çözüm/oy/limit ve LaTeX görselleri. Ayrı dış servis riski taşır. |
| Operasyon | `Settings`, `Translation`, `Log`, backup, Sentry ve Telescope | Ayar, çeviri, günlük, yedek ve hata izleme. |

## Zamanlanan işler ve yan etkiler

`bootstrap/app.php` içinde GPT limit yenileme, veritabanı/dosya yedekleri, Cloudflare'a yedek gönderimi, sezon sonucu güncelleme, saatlik cache temizleme, Telescope prune ve lig hesaplama tanımlı. `routes/console.php` içinde örnek `inspire` komutu bulunur; `withRouting` içindeki `commands` satırı yorumlanmıştır. Zamanlayıcıyı çalıştırmak bu fazın konusu değildir. `app/Jobs`, `app/Mail`, `app/Console/Commands`, `app/Exports` içeriğinin dış sistem ve veri etkisini her kullanımda ayrıca incele.

## Yardımcı yapı

`app/Helpers/Functions.php` Composer autoload `files` ile yüklenir: `data()`, `settingLocale()`, `resetCache()`, `activeSeason()`, `getImage()`, `dateFormat()`, `convertLatexToImg()` gibi global yardımcılar vardır. `DataHelper`/`FilterHelper` cache'li seçenek ve liste sorgularını, `TranslationHelper` DB–JSON çeviri akışını, `AiHelper` AI istek gövdesini yönetir. `Traits/Scope/*`, `DefaultOrderBy`, `Loggable`, `CustomLivewireAlert` ve tablo filtre trait'leri tekrar kullanılan davranışlardır.

## Görünüm akışı

Panel `resources/views/backend/layouts/app.blade.php` üzerinden OneUI, jQuery, Bootstrap 5 uyumlu bileşenler, özel `public/backend/assets/app.css|js`, Livewire ve `Modal`/`Offcanvas` bileşenlerini yükler. Ön yüz `resources/views/frontend/layouts/app.blade.php` üzerinden Bootstrap, tema CSS/JS, jQuery, cookie consent, PWA ve Livewire yükler. `resources/js/app.js` ile Vite yapılandırması bulunur; incelenen layout'lar doğrudan `public/assets` dosyalarını referans alır. Vite'ı tek gerçek asset yolu sayma.
