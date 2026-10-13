# Livewire, lazy ve sayfa kod incelemesi

6 Ekim 2026. Kullanıcı bütün sayfalarda kaynak kontrolü ve performans raporu istedi. Kullanıcı alt tabloları ve menü uygulaması [49](49-KULLANICI-ALT-TABLOLARI-VE-MENU-GRUPLARI.md) içindedir. Aşağıdaki kapsam dışı adaylar otomatik düzeltilmedi; rapor yeni geliştirme/migration/iş çalıştırma izni değildir.

**7 Ekim güncellemesi56:** Login/Register ve ProfileForm/UpdatePasswordForm onaylı olarak ilk HTML'e alındı; profil formları oturum kullanıcısını yeniden kullanır. Tablo ve37 sınıf sayısı6 Ekim kaynak envanteridir. Kurtarma/yeni parola ve diğer ağır raporlar bu güncellemeyle değiştirilmedi. 9 yeni dar senaryo/95 doğrulama ve2 limiter testi/10 doğrulama; ChromeNL/EN/mobil, TR yalnız transaction testi. Kanıt [56](56-GIRIS-KAYIT-VE-PROFIL-TASARIMI.md) içindedir.

## 1. İnceleme kapsamı ve kanıt

- app/routes/bootstrap/config altında **290 PHP dosyası** PHP sözdizimi kontrolünden geçti; derlenmiş bootstrap cache kapsam dışıdır.
- Uygulamaya ait **296 Blade şablonu** geçici dosyada derlenip PHP sözdizimi kontrolünden geçti. Vendor şablonları kapsam dışıdır; uygulama view cache build yapılmadı.
- **89 sayfa şablonu**, doğrudan sayfadaki Livewire çağrıları ve literal include bağlantıları aşağıda listelendi. Bu sayı bütün partial/component veya dinamik route kombinasyonlarının sayısı değildir.
- Doğrudan view döndüren **30 controller** için şablon eşlemesi çıkarıldı. Dinamik view adları, middleware, action çağrıları ve bütün rol kombinasyonları bu sayımla doğrulanmış olmaz.
- Yorum satırları dışlanınca kaynakta **37 sınıfta gerçek Lazy attribute** bulunuyor. Sınıf bulunması aktif sayfada 37 HTTP isteği olduğunu göstermez.
- Bu turun dar işlev kanıtı 49 içindedir: 13 farklı ilgili senaryo, altı kullanıcı sekmesinin testing tarayıcı kontrolü. Genel suite, build, üretim yük testi veya kapsamlı güvenlik taraması çalıştırılmadı.
- Kaynak üzerinden risk görülen akışlar aşağıda kaynak kanıtı / doğrulama gerektiren aday olarak ayrıldı. Gerçek HTTP 500, yetkisiz veri okuma veya ölçülmüş üretim gecikmesi diye sunulmaz.
- Ana veritabanı/migration, queue/scheduler, mail/AI/R2 dış servisleri bu rapor için çalıştırılmadı.

## 2. Lazy kullanım kararı ve öneri

**Bütün lazy'leri kaldırmak önerilmiyor.** İlk ekranda gereken hafif form/ana içerik HTML'i doğrudan gelmeli; ekran dışında kalan ikincil ağır alanlar ihtiyaç halinde yüklenmeli. Ağır sorguyu yalnız ilk HTML'e taşımak TTFB'yi artırabilir; önce sorgu/cache kapsamını düzeltmek gerekir. Aşağıdaki satırlar sonraki geliştirme önerisidir.

| Alan | Kaynaktaki durum | Öneri / gerekli kontrol |
|---|---|---|
| Panel dashboard | 44/46 ile lazy widget akışı kaldırıldı, saatlik kapsamlı cache | Mevcut ilk HTML standardını koru; eski Home widget sınıflarını aktif sanma |
| Kullanıcı favori / hiç çözülmeyen konu | 49 ile lazy kaldırıldı; hafif shell + Bootstrap JSON | Mevcut yapı korunur; JSON'un asenkron gelmesi Livewire lazy değildir |
| Giriş/kayıt/kurtarma/yeni parola | Frontend/Auth sınıfları lazy | Kullanılacak form ilk HTML'de olsun; dil, validation, token/throttle testleriyle ayrı küçük faz |
| Profil/parola ve ana CRUD formları | Bazı Account, UserForm, TopicForm, LanguageForm lazy | İlk görünür formu eager değerlendirmek; yetki/snapshot boyutu kontrolü |
| Ana sayfa ders bölümü / ders-konu-test-sınav listeleri | Lazy; LessonsSection içinde ListLessons ikinci lazy katmanı olabilir | İlk görünür içerikte ardışık mount beklemesini azalt; doğru kapsamlı cache ve sorgu ölçümü önce |
| Süresiz test çözümü | Frontend/Tests/SolveForm lazy; Exam modelini kullanır | Mount soru/deneme sorgularını ölç; yetki/cevap davranışını koruyarak ilk soru için ayrı karar |
| Süreli sınav çözümü | Frontend/Exams/SolveForm sınıfında Lazy attribute yok; Test modelini kullanır | Mevcut server deadline/cevap kaydı davranışını koru; lazy ekleme veya süreyi istemciye taşıma |
| Detay istatistiği/yorumlar/ikincil hesap raporları | Bir kısmı lazy | Ekranın altındaki ağır alanlarda lazy kalabilir; görünür boşluk ve ağ isteği ölçülür |
| Çeviri ekranı | Lazy ama bütün çeviri dizisini public state'e alıyor | Lazy payload problemini çözmez; görünür sayfa durumunu küçültme ayrı uygulama gerekir |
| AI çözümü | Lazy isolate ve kayıtlı cevapta sleep(1) | Yetki/limit kontrolü önce, yapay bekleme ayrı düzeltme; gerçek AI isteği yapmadan fake ile test |

Ölçüm planı: soğuk/sıcak cache ayrı; ilk HTML süresi, ilk kullanılabilir içerik, mount/JSON istek adedi, SQL sayısı/toplam süresi, request/snapshot byte ve PHP bellek. Aynı dil, aynı rol/öğrenci kapsamı ve aynı veri hacmiyle karşılaştır. Yerel süreyi üretim hızlanma katsayısı olarak sunma. Global cache temizliği mevcutken TTL yararını ayrıca değerlendir.

## 3. Kaynak bulguları ve sonraki dar doğrulamalar

| ID / öncelik | Kaynak / gözlem | Kanıt sınırı ve sonraki işlem |
|---|---|---|
| K01 — yüksek | Backend/ExamController.php:266 backend.exams.review döndürüyor; ilgili Blade yok | Eksik literal view doğrulandı. Route'un gerçek HTTP akışı bu tur koşulmadı; mevcut reviews/review-detail amacı netleştirilip tek render testi gerekir |
| K02 — yüksek | Ai/LatexImagesTable.php:21 kaldırılan DataTableComponent'ten türemiş; backend/ai/images.blade.php çağırıyor | Son bağımlı sınıf kaynakta doğrulandı. AI ana liste değil LaTeX görsel listesi etkilenir; ayrı Bootstrap Table geçişi bekliyor |
| K03 — yüksek | Frontend/HomeController.php:40 request()->user()->cannot; routes/frontend.php ai.solution tanımında auth middleware yok | Misafir null erişimi adayı. Uygulama grubu middleware ve misafir HTTP testiyle doğrulanmalı; misafirin login'e yönlenmesi mevcut ürün kararıdır |
| K04 — yüksek aday | Frontend/Ai/SolutionAi.php askGpt public remainingGptLimit'e bakıyor; güncel ai:solution iznini action içinde tekrar kontrol etmiyor | Mount'taki disabled UI tek başına işlem denetimi değildir. Hydration/izin kaldırılması/günlük limit testleri fake AI ile gerekir; bu tur exploit veya dış istek çalıştırılmadı |
| K05 —58 ile giderildi | Tarihsel: Frontend/Account/MyLeagueResultTable.php mutable userId ile sorguluyordu | Güncel58: Locked, mount actor eşitliği403, her sorguda auth ID ve15 sayfalama; başka owner/hydration/kapsam dar testleri geçti. Eski sürümde fiilî veri sızıntısı gösterildiği iddia edilmez |
| K06 — izin adayı | Season/SeasonForm save/delete ve Leagues/LeagueRunForm run içinde işlem can/authorize yok | Önceki40/47 adayları devam ediyor. Sayfa/UI izni ile işlem izni ayrı test edilmeli; dispatchSync lig işi çalıştırılmadı |
| K07 — performans | Languages/LanguageTranslateForm.php:38 bütün çevirileri public translations dizisine alıyor;40 satır pagination snapshot'ı küçültmüyor | Tam dizi kaynakta doğrulandı; gerçek payload boyutu ölçülmedi. Yalnız görünür sayfa değerleri + DB toplamı önerilir;16MiB sınırını tekrar büyütmek çözüm değildir |
| K08 — performans | AppServiceProvider.php:68–72 non-production boot cache flush; Functions.php resetCache global flush/clear/optimize:clear; bootstrap/app.php saatlik optimize temizliği | Kaynak doğrulandı;32/40'taki eski aday devam eder. Lokal her request ile production model/scheduler temizliği ayrılır. Yeni60sn/1saat cache katmanı vardır ama global flush etkin ömrünü kesebilir; hedefli invalidation sonraki ayrı faz |
| K09 — süre tutarsızlığı | Lessons/ListLessons:49, ListTopics:45, Faqs/ListFaqs:25 TTL 60*60*60*365; Exams/ListExams:54 60*60*30 yanında1ay yorumu | İlk değer78.840.000sn =912,5 gün; ikinci108.000sn =30saat. İstenen TTL henüz seçilmedi; locale/query-shape ve invalidation testleriyle düzenlenmeli. Dil anahtarının olmaması tek başına yanlış çeviri kanıtı değildir |
| K10 — sorgu adayı | Frontend/Lessons/ListLessons.php:59 code OR name araması active filtresinden önce gruplamasız | Eloquent scope SQL'ini gruplayabilir; SQL/aktif-pasif fixture doğrulanmadan pasif kayıt sızıyor denmez. Nested where önerisi ancak dar sorgu testiyle |
| K11 — gecikme | Frontend/Ai/SolutionAi.php:54 mevcut cevap bulunduğunda sleep(1) | Kaynakta zorunlu1sn bekleme doğrulandı; kaldırma bu rapor kapsamında yapılmadı |
| K12 — ölçek | Frontend/Account/SolvedTests sonuç ID'lerini pluck; TestController solutions / AccountController result sonuçları ilişkileriyle get; MyLeagueResultTable get | Büyük geçmişte bellek/sorgu büyümesi adayı. Sayfalama veya SQL alt sorgusu kullanıcıya gösterilen geçmişi kaybetmeden tasarlanmalı; yük ölçümü yapılmadı |
| K13 — SQL doğruluğu adayı | Frontend/Account/StatsTable stats() konu/ders groupBy ile aggregate olmayan tarih alanı seçiyor | Mevcut strict=false altında belirsiz tarih; ONLY_FULL_GROUP_BY hedefinde uyumluluk riski. Bu tur çalışan ekranda500 gözlenmedi; tarih için MIN/MAX/son deneme kararı gerekir |
| K14 — görünüm/izin adayı | backend/users/edit.blade.php Diğer İşlemler test/sınav linkleri; ResultsTable mount kendi sonuç iznini arıyor | users:view olan ama sonuç izni olmayan kişide link görünürken403 olabilir. Yeni endpoint izinleri gevşetilmedi; sekme görünürlüğü dar rol testiyle ayrı faz |
| K15 — içerik erişim adayı | Frontend BlogController detail, LessonController topics, ExamController detail, TestController detail | Slug/code route binding sonrası pasif/yayın tarihi kapsamı her detayda açık değil. Preview ve doğrudan bağlantı ürün anlamı netleşip HTTP fixture testleriyle doğrulanmalı; aktif listeden görünmemek detay erişimini garanti etmez |
| K16 — performans/SEO adayı | frontend/home/sitemap.blade.php birden çok modelin bütün kayıtlarını getiriyor | Gerekli kolon/chunk/cache ve gerçek lastmod kullanımını ayrı değerlendirmek gerekir; büyük sitemap yükü ve Google kabulü ölçülmedi |

Mevcut SolvedExams yanıt sayısı bütün kullanıcıların tamamlanan sonuçlarını sayıyor. Kişisel sonuç ekranında bunun genel popülerlik mi kişisel deneme sayısı mı olduğu karar gerektirir; otomatik değiştirilmedi. Aynı denemelerin toplamı/yarım süresiz test ve tamamlanan süreli sınavın lig kuralları bu raporla değiştirilmez.

## 4. Lazy sınıf envanteri

Aşağıdaki37 sınıf gerçek attribute taşır. Home/ChartWidget, LessonTable, Statistics, TestTable ve UserTable eski dashboard kaynaklarıdır; güncel dashboard onları kullanmıyor. ResultsUserDetail gibi dinamik çağrılabilen sınıfların bütün ulaşılabilirlik yolları bu sayımla kanıtlanmış değildir.

- `app/Livewire/Ai/LatexImagesTable.php`
- `app/Livewire/Exams/ResultsUserDetail.php`
- `app/Livewire/Frontend/Account/FavoriteTests.php`
- `app/Livewire/Frontend/Account/ProfileForm.php`
- `app/Livewire/Frontend/Account/SolvedExams.php`
- `app/Livewire/Frontend/Account/SolvedTests.php`
- `app/Livewire/Frontend/Account/StatsTable.php`
- `app/Livewire/Frontend/Account/UpdatePasswordForm.php`
- `app/Livewire/Frontend/Ai/SolutionAi.php`
- `app/Livewire/Frontend/Auth/CreatePassword.php`
- `app/Livewire/Frontend/Auth/Login.php`
- `app/Livewire/Frontend/Auth/RecoverPassword.php`
- `app/Livewire/Frontend/Auth/Register.php`
- `app/Livewire/Frontend/Blogs/ListBlogs.php`
- `app/Livewire/Frontend/Exams/DetailStatisticList.php`
- `app/Livewire/Frontend/Exams/ListExams.php`
- `app/Livewire/Frontend/Faqs/ListFaqs.php`
- `app/Livewire/Frontend/Home/LessonsSection.php`
- `app/Livewire/Frontend/Leagues/LeagueResultTable.php`
- `app/Livewire/Frontend/Leagues/LeagueTable.php`
- `app/Livewire/Frontend/Lessons/ListLessons.php`
- `app/Livewire/Frontend/Lessons/ListTopics.php`
- `app/Livewire/Frontend/Tests/DetailStatisticList.php`
- `app/Livewire/Frontend/Tests/ListTests.php`
- `app/Livewire/Frontend/Tests/ReviewFormAndList.php`
- `app/Livewire/Frontend/Tests/SolveForm.php`
- `app/Livewire/Frontend/Tests/SolveQuestionsStatistic.php`
- `app/Livewire/Home/ChartWidget.php`
- `app/Livewire/Home/LessonTable.php`
- `app/Livewire/Home/Statistics.php`
- `app/Livewire/Home/TestTable.php`
- `app/Livewire/Home/UserTable.php`
- `app/Livewire/Languages/LanguageForm.php`
- `app/Livewire/Languages/LanguageTranslateForm.php`
- `app/Livewire/Tests/ResultsUserDetail.php`
- `app/Livewire/Topics/TopicForm.php`
- `app/Livewire/Users/UserForm.php`

## 5. Sayfa bazında kaynak haritası

Doğrudan çağrılar listelenir; layout/include içindeki alt çağrılar ayrıca incelenir. Dinamik includeIf ve değişken tab isimleri regex envanterinde tam çözülemez. Kullanıcı düzenleme alt sekmelerinin işlev matrisi49 içindedir. Bütün satırların durumu **statik kaynak kontrolü; tüm akış/rol/browser kabulü değil**.

| Sayfa şablonu | Doğrudan Livewire | Literal include |
|---|---|---|
| `backend/ai/edit.blade.php` | `ai.answer-ai-detail`, `ai.user-usage-table`, `ai.answer-vote-table`, `ai.answer-ai-form` | — |
| `backend/ai/image-form.blade.php` | `ai.latex-image-form` | — |
| `backend/ai/images.blade.php` | `ai.latex-images-table` | — |
| `backend/ai/index.blade.php` | `ai.answer-ai-table` | — |
| `backend/auth/login.blade.php` | `auth.login` | — |
| `backend/blogs/create.blade.php` | `blogs.blog-form` | — |
| `backend/blogs/edit.blade.php` | `blogs.blog-form` | — |
| `backend/blogs/index.blade.php` | `blogs.blogs-table` | — |
| `backend/contacts/detail.blade.php` | `contacts.contact-reply-form` | — |
| `backend/contacts/index.blade.php` | `contacts.contact-table` | — |
| `backend/exams/create.blade.php` | `exams.exam-form` | — |
| `backend/exams/edit.blade.php` | `exams.exam-form` | — |
| `backend/exams/index.blade.php` | `exams.exam-table` | — |
| `backend/exams/result.blade.php` | `exams.results-table` | — |
| `backend/exams/results.blade.php` | `exams.results-table` | — |
| `backend/exams/review-detail.blade.php` | `exams.review-detail-form` | — |
| `backend/exams/reviews.blade.php` | `exams.reviews-table` | — |
| `backend/faqs/create.blade.php` | `faqs.faq-form` | — |
| `backend/faqs/edit.blade.php` | `faqs.faq-form` | — |
| `backend/faqs/index.blade.php` | `faqs.faq-table` | — |
| `backend/home/index.blade.php` | — | `backend.home.partials.metric`, `backend.home.partials.metric`, `backend.home.partials.metric`, `backend.home.partials.metric`, `backend.home.partials.ranking`, `backend.home.partials.ranking`, `backend.home.partials.ranking`, `backend.home.partials.ranking`, `backend.home.partials.users`, `backend.home.partials.users` |
| `backend/languages/create.blade.php` | `languages.language-form` | — |
| `backend/languages/edit.blade.php` | `languages.language-form` | — |
| `backend/languages/index.blade.php` | `languages.language-table` | — |
| `backend/languages/translate.blade.php` | `languages.language-translate-form` | — |
| `backend/leagues/index.blade.php` | `leagues.league-table` | — |
| `backend/lessons/create.blade.php` | `lessons.lesson-form` | — |
| `backend/lessons/edit.blade.php` | `lessons.lesson-form` | — |
| `backend/lessons/index.blade.php` | `lessons.lesson-table` | — |
| `backend/newsletters/index.blade.php` | `newsletters.newsletter-table` | — |
| `backend/newsletters/send.blade.php` | `newsletters.newsletter-send-form` | — |
| `backend/pages/all.blade.php` | `pages.pages-table` | — |
| `backend/pages/create.blade.php` | `pages.page-form` | — |
| `backend/pages/edit.blade.php` | `pages.page-form` | — |
| `backend/pages/home.blade.php` | `pages.page-home-form` | — |
| `backend/questions/create.blade.php` | `questions.question-form` | — |
| `backend/questions/edit.blade.php` | `questions.question-form` | — |
| `backend/questions/index.blade.php` | `questions.question-table` | — |
| `backend/roles/create.blade.php` | — | `backend.roles.form` |
| `backend/roles/edit.blade.php` | — | `backend.roles.form` |
| `backend/roles/index.blade.php` | `roles.role-table` | — |
| `backend/season/index.blade.php` | `season.season-form` | — |
| `backend/settings/index.blade.php` | — | `backend.settings.tabs.` |
| `backend/settings/logs.blade.php` | `settings.logs-table` | — |
| `backend/stats/index.blade.php` | — | — |
| `backend/tests/create.blade.php` | `tests.test-form` | — |
| `backend/tests/edit.blade.php` | `tests.test-form` | — |
| `backend/tests/index.blade.php` | `tests.test-table` | — |
| `backend/tests/result.blade.php` | `tests.results-table` | — |
| `backend/tests/results.blade.php` | `tests.results-table` | — |
| `backend/tests/sections.blade.php` | `tests.section-form` | — |
| `backend/topics/create.blade.php` | `topics.topic-form` | — |
| `backend/topics/edit.blade.php` | `topics.topic-form` | — |
| `backend/topics/index.blade.php` | `topics.topic-table` | — |
| `backend/users/create.blade.php` | `users.user-form` | — |
| `backend/users/edit.blade.php` | — | — |
| `backend/users/index.blade.php` | `users.user-table` | — |
| `frontend/account/favorite.blade.php` | `frontend.account.favorite-tests` | `frontend.layouts.section.page-breadcrumb`, `frontend.account.tab` |
| `frontend/account/index.blade.php` | `frontend.account.profile-form`, `frontend.account.update-password-form` | `frontend.layouts.section.page-breadcrumb`, `frontend.account.tab` |
| `frontend/account/my-leagues.blade.php` | `frontend.account.my-league-result-table` | `frontend.layouts.section.page-breadcrumb`, `frontend.account.tab` |
| `frontend/account/result.blade.php` | — | — |
| `frontend/account/results.blade.php` | `frontend.account.solved-exams` | `frontend.layouts.section.page-breadcrumb`, `frontend.account.tab` |
| `frontend/account/solved-not-complete.blade.php` | `frontend.account.solved-tests` | `frontend.layouts.section.page-breadcrumb`, `frontend.account.tab` |
| `frontend/account/solved.blade.php` | `frontend.account.solved-tests` | `frontend.layouts.section.page-breadcrumb`, `frontend.account.tab` |
| `frontend/account/stats.blade.php` | `frontend.account.stats-table` | `frontend.layouts.section.page-breadcrumb`, `frontend.account.tab` |
| `frontend/account/unsubscribe.blade.php` | — | — |
| `frontend/auth/create-password.blade.php` | `frontend.auth.create-password` | `frontend.layouts.section.page-breadcrumb` |
| `frontend/auth/login.blade.php` | `frontend.auth.login` | `frontend.layouts.section.page-breadcrumb` |
| `frontend/auth/recover-password.blade.php` | `frontend.auth.recover-password` | `frontend.layouts.section.page-breadcrumb` |
| `frontend/auth/register.blade.php` | `frontend.auth.register` | `frontend.layouts.section.page-breadcrumb` |
| `frontend/blogs/detail.blade.php` | `frontend.blogs.list-blogs` | `components.seo.tags` |
| `frontend/blogs/index.blade.php` | `frontend.blogs.list-blogs` | `components.seo.tags`, `frontend.layouts.section.page-breadcrumb` |
| `frontend/contact/index.blade.php` | `frontend.contacts.contact-form` | `components.seo.tags`, `frontend.layouts.section.page-breadcrumb`, `frontend.layouts.section.social-links` |
| `frontend/exams/detail.blade.php` | `frontend.exams.detail-statistic-list` | `components.seo.tags` |
| `frontend/exams/index.blade.php` | `frontend.exams.list-exams` | `components.seo.tags`, `frontend.layouts.section.page-breadcrumb` |
| `frontend/exams/start.blade.php` | `frontend.exams.solve-form` | — |
| `frontend/faqs/index.blade.php` | `frontend.faqs.list-faqs` | `components.seo.tags`, `frontend.layouts.section.page-breadcrumb` |
| `frontend/home/ai-solution.blade.php` | `frontend.ai.solution-ai` | — |
| `frontend/home/index.blade.php` | `frontend.home.welcome-section`, `frontend.home.lessons-section`, `frontend.home.tests-section`, `frontend.home.blog-section`, `frontend.home.faq-section` | — |
| `frontend/home/page.blade.php` | — | `components.seo.tags`, `frontend.layouts.section.page-breadcrumb` |
| `frontend/home/search.blade.php` | `frontend.lessons.list-lessons`, `frontend.lessons.list-topics`, `frontend.tests.list-tests`, `frontend.blogs.list-blogs` | `components.seo.tags` |
| `frontend/leagues/index.blade.php` | `frontend.leagues.league-table` | `components.seo.tags` |
| `frontend/lessons/index.blade.php` | `frontend.lessons.list-lessons` | `components.seo.tags`, `frontend.layouts.section.page-breadcrumb` |
| `frontend/lessons/topics.blade.php` | `frontend.lessons.list-topics` | `components.seo.tags`, `frontend.layouts.section.page-breadcrumb` |
| `frontend/pwa.blade.php` | — | — |
| `frontend/tests/detail.blade.php` | `frontend.tests.review-form-and-list`, `frontend.tests.detail-statistic-list` | `components.seo.tags` |
| `frontend/tests/index.blade.php` | `frontend.tests.list-tests` | `components.seo.tags`, `frontend.layouts.section.page-breadcrumb` |
| `frontend/tests/solutions.blade.php` | — | `frontend.layouts.section.page-breadcrumb` |
| `frontend/tests/start.blade.php` | `frontend.tests.solve-form`, `frontend.tests.solve-questions-statistic` | — |

## 6. Controller → görünüm haritası

| Controller | Doğrudan view adları |
|---|---|
| `Backend/AiController.php` | `backend.ai.index`, `backend.ai.images`, `backend.ai.image-form`, `backend.ai.edit` |
| `Backend/Auth/LoginController.php` | `backend.auth.login` |
| `Backend/BlogController.php` | `backend.blogs.index`, `backend.blogs.create`, `backend.blogs.edit` |
| `Backend/ContactController.php` | `backend.contacts.index`, `backend.contacts.detail` |
| `Backend/ExamController.php` | `backend.exams.index`, `backend.exams.create`, `backend.exams.edit`, `backend.exams.results`, `backend.exams.result`, `backend.exams.reviews`, `backend.exams.review`, `backend.exams.review-detail` |
| `Backend/FaqController.php` | `backend.faqs.index`, `backend.faqs.create`, `backend.faqs.edit` |
| `Backend/HomeController.php` | `backend.home.index` |
| `Backend/LanguageController.php` | `backend.languages.index`, `backend.languages.create`, `backend.languages.edit`, `backend.languages.translate` |
| `Backend/LeagueController.php` | `backend.leagues.index` |
| `Backend/LessonController.php` | `backend.lessons.index`, `backend.lessons.create`, `backend.lessons.edit` |
| `Backend/NewsletterController.php` | `backend.newsletters.index`, `backend.newsletters.send` |
| `Backend/PageController.php` | `backend.pages.home`, `backend.pages.all`, `backend.pages.create`, `backend.pages.edit` |
| `Backend/QuestionController.php` | `backend.questions.index`, `backend.questions.create`, `backend.questions.edit` |
| `Backend/RolesController.php` | `backend.roles.index`, `backend.roles.create`, `backend.roles.edit` |
| `Backend/SeasonController.php` | `backend.season.index` |
| `Backend/SettingsController.php` | `backend.settings.index`, `backend.settings.logs` |
| `Backend/StatsController.php` | `backend.stats.index` |
| `Backend/TestsController.php` | `backend.tests.index`, `backend.tests.create`, `backend.tests.edit`, `backend.tests.sections`, `backend.tests.results`, `backend.tests.result` |
| `Backend/TopicController.php` | `backend.topics.index`, `backend.topics.create`, `backend.topics.edit` |
| `Backend/UsersController.php` | `backend.users.index`, `backend.users.create`, `backend.users.edit` |
| `Frontend/AccountController.php` | `frontend.account.index`, `frontend.account.favorite`, `frontend.account.stats`, `frontend.account.solved`, `frontend.account.solved-not-complete`, `frontend.account.results`, `frontend.account.my-leagues`, `frontend.account.result`, `frontend.account.unsubscribe` |
| `Frontend/AuthController.php` | `frontend.auth.login`, `frontend.auth.recover-password`, `frontend.auth.create-password`, `frontend.auth.register` |
| `Frontend/BlogController.php` | `frontend.blogs.index`, `frontend.blogs.detail` |
| `Frontend/ContactController.php` | `frontend.contact.index` |
| `Frontend/ExamController.php` | `frontend.exams.index`, `frontend.exams.detail`, `frontend.exams.start` |
| `Frontend/FaqController.php` | `frontend.faqs.index` |
| `Frontend/HomeController.php` | `frontend.home.index`, `frontend.home.search`, `frontend.home.ai-solution`, `frontend.home.page`, `frontend.home.sitemap` |
| `Frontend/LeagueController.php` | `frontend.leagues.index`, `frontend.leagues.index` |
| `Frontend/LessonController.php` | `frontend.lessons.index`, `frontend.lessons.topics` |
| `Frontend/TestController.php` | `frontend.tests.index`, `frontend.tests.detail`, `frontend.tests.start`, `frontend.tests.solutions` |

## 7. Sonraki işlem sırası

1. Bu rapor yeni hata düzeltme izni değildir; kullanıcı seçtiği adayları onaylar.
2. Son LaTeX tablo geçişi ve eksik view/misafir AI akışı için ayrı küçük paketler.
3. Yetki/hydration adayları fake servis + testing transaction testleriyle önce doğrulanır.
4. Global cache temizleme ve çeviri snapshot küçültme birbirinden ayrı ölçülür.
5. İlk görünür form/listelerde lazy kararları sayfa bazında verilerek uygulanır; ikincil içerik otomatik eager yapılmaz.
6. Paket karşılıkları51; kullanıcı tablolarının geçerli kabul kapsamı49. Tam ürün suite sonucu36/37 tarihsel kayıttır; bu tur bütün testler başarılı sonucu üretmez.


## Güncel kapsam notu —7 Ekim /53

[53-ON-YUZ-PERFORMANS-VE-CACHE-DUZELTMELERI.md](53-ON-YUZ-PERFORMANS-VE-CACHE-DUZELTMELERI.md) K08'in nonproduction boot flush'ını kaldırdı; public içerik cache'ini ayırdı ve hit/iletişim/bülten temizliğini kesti. Eski panel resetCache ve scheduler kısmı kalır. K09'daki public TTL'ler7gün/değişiklikte yenile ile değişti; K10 nested where ve aktif/pasif dar testiyle düzenlendi. Yedi public ana içerik lazy'si kaldırıldı. Yukarıdaki37 lazy kaynak envanteri tarihsel başlangıçtır; kalan lazy/kod adaylarının tamamının düzeltilmesi veya ürün onayı değildir. K07 çeviri snapshot ve K16 sitemap bu tur değiştirilmedi.


## Güncel SEO kapsamı —7 Ekim /54

[54-ON-YUZ-SEO-VE-ARAMA-MOTORU-STANDARDI.md](54-ON-YUZ-SEO-VE-ARAMA-MOTORU-STANDARDI.md) K16 sitemap adayının dar kolon/public cache/gerçek lastmod ve URL-dil kapsamını uyguladı. Publish deadline, değişiklikte yenile ve hits tarihinin korunması dar testle doğrulandı. Çok büyük sitemap parçalama ve canlı Google/Core Web Vitals kabulü halen ayrı ölçüm gerektirir. Diğer50 adaylarının otomatik düzeltme izni veya kapatılması değildir.


## Güncel lig lazy kararı —7 Ekim /55

[55](55-ON-YUZ-LIG-ILK-YUKLEME-VE-SAYFALAMA-HOVER.md) ile LeagueTable/LeagueResultTable lazy kaldırıldı. İlk HTML üç kart; ek mount4→0, mevcut cache/SQL kapsamı ve puan korunur. Kaynak lazy envanteri başlangıç kaydıdır; kalan lazy sınıflar için toplu kaldırma izni değildir.


## Güncel detay kapsamı —7 Ekim /59

[59](59-ANASAYFA-KARTLARI-VE-TEST-SINAV-DETAYLARI.md) ile test/sınav DetailStatisticList ilk HTML'e alındı; review ikincil lazy kalır. Test public summary mevcut7gün cache, kişisel toplam ayrı; sınav sections.parents eager ve ilk boş root/child navigation denetimi eklendi. K12'deki TestController solutions eager ilişkilere geçti, ancak bütün geçmiş get() kaldığı için ölçek adayının tamamı kapanmadı. Yorum query/oturum sınırları dar önce/sonra testlerle düzeltildi. Bu not diğer lazy/hata adaylarının otomatik kapatılması değildir.
