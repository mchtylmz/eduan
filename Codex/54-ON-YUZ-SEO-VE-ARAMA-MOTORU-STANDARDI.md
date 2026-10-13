# 54 — Ön yüz SEO ve arama motoru standardı

Tarih: 7 Ekim 2026. Kullanıcı ana sayfa, blog, test/sınav detayları ve diğer ön yüz sayfalarında SEO eksiklerinin giderilmesini, hızlı çalışmasını ve yapay zekâ aramalarında bulunabilirliğini istedi. Bu kayıt kaynak uygulamasını ve dar yerel doğrulamayı anlatır; canlıya dağıtım veya arama motorunun siteyi kabul ettiği anlamına gelmez.

## 1. Bulunan eksikler ve düzeltme

| Eksik | Uygulanan değişiklik |
|---|---|
| Canonical mevcut isteğin takip parametrelerini taşıyabiliyordu | APP_URL üzerinden güvenilir kök URL; yalnız dil ve gerçek sayfa numarası korunur. |
| Oturum dili, parametresiz Flemenkçe dil bağlantısının anlamını değiştirebiliyordu | Public canonical/hreflang/sitemap URL'lerinde varsayılan dil dahil açık `lang=nl/en/tr` kullanılır. Oturum middleware'i değiştirilmedi. |
| İçeriğin mevcut çevirileriyle dil alternatifleri eşleşmiyordu | Aktif diller ve gerçek içerik çevirileri kesiştirilir; olmayan çeviri hreflang olarak sunulmaz. |
| Bazı sosyal etiketler yanlış veya yinelenen başlık üretiyordu | Tek title/description; Open Graph ve Twitter başlık, açıklama, mutlak görsel URL'si ve alt metni. |
| Genel sayfalarda semantik ana başlık tutarsızdı | Ana sayfa, ortak breadcrumb, blog/test/sınav/özel sayfa ve lig başlık düzeni düzeltildi. |
| Sitemap ana test/sınav liste sayfalarını eksik kapsıyordu | Genel liste ve gerçek detay URL'leri, aktif/yayınlanmış kapsamla eklendi. |
| Sitemap lastmod her istekte bugünün tarihini kullanıyordu | Gerçek DB değişiklik tarihleri; tarihi bilinmeyen statik sayfada lastmod üretilmez. |
| Görüntüleme sayacı içerik updated_at tarihini değiştiriyordu | Yalnız `incrementIf(..., column: hits)` timestamps olmadan artırılır. Diğer kolonlar mevcut davranışı korur. |
| Yapılandırılmış veri ortak ve tutarlı değildi | Gerçek veriden kuruluş, site, sayfa, breadcrumb, blog ve öğrenme kaynağı JSON-LD üretilir. |
| Pasif veya gelecekte yayımlanacak içeriğin doğrudan detay URL'si açılabiliyordu | Blog, ders ve test/sınav detaylarında aktif/yayın zamanı kontrolü; uygun olmayan kayıt HTTP404. |

Kullanıcıdaki **test=Exam/exams**, **sınav=Test/tests** eşleştirmesi korunur. Soru çözme, sonuç, lig puanı veya sınav süresi değiştirilmedi.

## 2. Sayfa ve indeksleme matrisi

| Sayfa ailesi / route | SEO davranışı |
|---|---|
| `frontend.home` | Sayfa/marka başlığı, açıklama, WebSite/kuruluş/WebPage; mevcut ana sayfa çevirileri. |
| `frontend.lessons` | Liste başlığı, sayfa numarası ve dil canonical'ı; breadcrumb. |
| `frontend.lesson` | Aktif dersin adı/açıklaması ve gerçek name çevirileri. |
| `frontend.tests` | Genel test listesi veya aktif konu + Testler başlığı; konu açıklaması/çevirileri. Konunun dersi de aktif olmalı. |
| `frontend.test.detail` | Aktif Exam adı/content açıklaması; LearningResource/practice test, gerçek içerik dili. |
| `frontend.exams` | Sınav listesi, dil ve sayfa numarası canonical'ı. |
| `frontend.exam.detail` | Aktif Test adı/content açıklaması; LearningResource/exam, gerçek içerik dili. |
| `frontend.blog` | Yayınlanmış blog listesi ve dil/sayfa canonical'ı. |
| `frontend.blog.detail` | Aktif ve yayın zamanı gelmiş yazı; brief/content açıklaması, BlogPosting, yayın/değişiklik tarihi. |
| `frontend.faqs` | Genel SSS başlığı/açıklaması, sayfa ve breadcrumb. Ticari eğitim sitesi için FAQ rich result vaadi verilmez. |
| `frontend.contact` | Genel iletişim başlığı/açıklaması ve ContactPage. Form/CSRF cache'e alınmaz. |
| `frontend.leagues`, `frontend.leagues.detail` | Etkin lig ayarında genel liste/dönem label başlığı, breadcrumb. Kapalı lig yönlendirmesi korunur. |
| `frontend.page` | Aktif, custom/footer ve yönlendirmesiz sayfanın title/brief çevirileri; tek ana başlık. |
| Arama ve serbest metin filtreleri | `noindex, nofollow`; public yapılandırılmış veri/dil alternatifleri üretilmez. |
| Login/kayıt/parola kurtarma, hesap/favoriler/istatistik/sonuçlar | `noindex, nofollow`; sitemap ve public JSON-LD dışında. Mevcut auth/yetki sınırları korunur. |
| Test/sınav başlatma/çözüm, özel AI çözümü, token bağlantıları | `noindex, nofollow`; sitemap dışında. Parola oluşturma/doğrulama/abonelikten çıkma token'ı canonical veya sosyal URL'ye konmaz. |

`noindex` yetkilendirme değildir. `robots.txt` ile engellenmiş URL'de robot noindex'i okuyamayabilir; özel sayfaların asıl sınırı mevcut auth/izin ve URL'nin sitemap'e konmamasıdır. Önceden indekslenmiş özel URL için canlı Search Console durumuna göre kaldırma/crawl planı ayrıca yapılır.

## 3. URL ve çok dilli sözleşme

1. Canonical kökü `config('app.url')`; canlıda HTTPS ve gerçek alan adı doğru olmalıdır. Request Host alias'ı kendiliğinden canonical olmaz.
2. Public canonical ve sitemap URL'leri açık dil taşır: `/lessons?lang=nl`, `/lessons?lang=en`; ikinci sayfa `?lang=nl&page=2`. UTM, reklam ve takip parametreleri taşınmaz.
3. Parametresiz sayfa mevcut oturum dilini açmaya devam eder; SEO URL'si aynı içeriğin dilini açıkça belirtir. Dil seçme davranışı yeniden yazılmadı.
4. Blog/Exam/Test tek locale kaydıdır. Aynı slug/code'a sahte diğer dil alternatifi eklenmez; kayıtların gerçekten eşlenmiş ayrı çevirisi mevcut modelde tanımlı değildir.
5. Lesson/Topic/Page JSON çevirilerinde yalnız dolu ad/başlık ve aktif dil sunulur. Çevirisi olmayan UI diliyle açılan bu detay `noindex` alır; canonical gerçek mevcut fallback dile döner. Kayıt veya uygulama dili kalıcı olarak değiştirilmez.
6. Genel liste/statik sayfalar aktif dillerle karşılıklı hreflang üretir. `x-default` yalnız varsayılan dil o sayfanın alternatiflerinde varsa kullanılır.
7. HTML `lang` arayüz dilini korur; blog/test/sınav içerik alanında kendi kayıt dili ayrıca belirtilir. OG Flemenkçe bölgesi `nl_BE`; İngilizce/Türkçe için kullanıcıdan gelmeyen ülke varsayılmaz.

Kaynak: [Google çok dilli sürümler](https://developers.google.com/search/docs/specialty/international/localized-versions), [canonical ve yinelenen URL'ler](https://developers.google.com/search/docs/crawling-indexing/consolidate-duplicate-urls).

## 4. Etiketler, başlıklar ve içerik

- `FrontendSeo` HTML/script/style temizlenmiş düz metin üretir. Açıklama en fazla160 karakterlik yerel sınırla kesilir; bu Google'ın zorunlu karakter sınırı değildir.
- Blog brief, ardından content; ders/konu description; test/sınav content; özel/ana sayfa brief tercih edilir. Eksikse sayfa başlığı + mevcut siteDescription kullanılır.
- Title, kayıt/sayfa başlığı ve mevcut siteTitle markasıyla kurulur. Marka zaten başlığın tamamıysa tekrarlanmaz. DB içeriğine yeni reklam veya anahtar kelime metni yazılmadı.
- Mevcut panel `siteKeywords`/kayıt keywords alanı korunur. Google meta keywords kullanmaz; anahtar kelime doldurma veya yeni `ai-keywords` etiketi eklenmedi.
- Sosyal paylaşım görselleri mevcut blog/kapak/siteLogo kaynağından gelir; OG ve Twitter alt metni gerçek başlıktır. Yeni görsel veya logo üretilmedi.
- Blog/test/sınav başlığı H1, eski görünüm boyutu Bootstrap `h3` utility ile korunur. Özel sayfada kapak varsa kapak H1, yoksa gövde H1; breadcrumb çift H1 üretmez.
- Ana sayfada Welcome başlığı yoksa mevcut sayfa title'ı erişilebilir `visually-hidden` H1 olur; test section başlığı H2'dir. Lig sayfası ilk HTML H1, sonradan gelen dönem başlığı H2'dir.
- Editörde kullanıcı tarafından yazılmış içerikteki H1/bozuk HTML topluca dönüştürülmedi. Mevcut içerik HTML'si ayrıca editoryal olarak kontrol edilmelidir; şablon testinin tek H1 olması tüm canlı yazıların aynı olduğunu kanıtlamaz.

Kaynak: [Google snippet/açıklama](https://developers.google.com/search/docs/appearance/snippet), [Google SEO başlangıç rehberi](https://developers.google.com/search/docs/fundamentals/seo-starter-guide).

## 5. JSON-LD ve yapay zekâ araması

`SeoSchema` sadece indexable public sayfada üretilir:

- EducationalOrganization: gerçek site markası/kök URL ve varsa mevcut logo; adres/telefon/ödül uydurulmaz.
- WebSite ve WebPage/ContactPage: canonical URL, görünür sayfa başlığı/açıklaması, içerik dili, kuruluş/site referansı.
- BreadcrumbList: gerçek ana sayfa, gerekli parent liste ve mevcut sayfa linkleri.
- BlogPosting: gerçek başlık/açıklama/yayın/değişiklik zamanı ve görsel; modelde yazar alanı bulunmadığından sahte yazar eklenmez.
- LearningResource: test veya sınavın gerçek adı/açıklaması/dili. Soru/cevap, öğrenci sonucu, puan veya sahte rating eklenmez. Bu Google'ın özel test rich result'ı desteği iddiası değildir.
- JSON `JSON_HEX_TAG/AMP/APOS/QUOT` ile script kapanışı ve HTML sızıntısına karşı encode edilir.

Google AI Overviews/AI Mode için ayrı bir meta etiketi, özel AI schema veya `llms.txt` şartı yoktur. Mevcut crawl/index/snippet uygunluğu, yararlı erişilebilir içerik ve görünen bilgiyle eşleşen schema temeldir. Bu yüzden teknik SEO iyileştirildi; sıralama veya AI yanıtında alıntılanma garantisi verilmez.

Kaynaklar: [Google AI özellikleri](https://developers.google.com/search/docs/appearance/ai-features), [Article yapılandırılmış veri](https://developers.google.com/search/docs/appearance/structured-data/article), [Organization yapılandırılmış veri](https://developers.google.com/search/docs/appearance/structured-data/organization).

OpenAI OAI-SearchBot arama, GPTBot eğitim amacıyla ayrı değerlendirilir. Mevcut wildcard public erişim korunur; kullanıcı adına yeni eğitim/bot istisnası veya Cloudflare kuralı seçilmedi. Robots izni tek başına arama motorundaki görünürlüğü kanıtlamaz. Kaynak: [OpenAI robotları](https://developers.openai.com/api/docs/bots).

## 6. Sitemap, robots ve performans

- Sitemap `SeoSitemap` public dizisi üzerinden oluşur; büyük content alanları yüklenmez, her model için gerekli ID/slug/code/locale/title/updated_at kolonları seçilir.
- Ana sayfa, ders/test/sınav/blog/SSS/iletişim listeleri, gerçek ders/konu/test/sınav/blog/custom footer detayları ve etkin ligler dahildir. Auth/hesap/çözüm/arama/pasif kayıt, gelecekteki yazı ve harici yönlendirme sayfası dahil değildir.
- XML sitemap namespace ve XHTML dil alternatifleri içerir; her dil kendi loc'u ile sunulur. Yanıltıcı priority veya her istekte `now()` lastmod üretilmez.
- Sitemap ayrı frontend cache resource'udur;7gün/değişiklikte yenile. Locale, kök URL ve resource revision anahtara girer. Warm helper sorgusu testte0 SQL; bütün HTTP isteği0 SQL iddiası değildir.
- İçerik/dil/model değişikliği sitemap revision'ını yeniler; ana sayfa bulk save ve SettingsSaveAction da açık yeniler. Lig/lig sonucu değişimi sitemap'i yeniler. Global flush eklenmedi.
- Gelecekteki aktif blogun yayın zamanı TTL'yi kısaltır; yazı yeniden düzenlenmeden sitemap'e girer. Saatli yayın senaryosu test edildi.
- Hits-only artış, içerik tarihi veya sitemap revision'ını değiştirmez. Önceden hit ile değişmiş tarihleri geriye doğru düzeltmek için canlı kayıtlara müdahale edilmedi.
- Public HTML/snapshot/CSRF/kişisel veri cache edilmez;53'teki ilk HTML/dar SQL/public cache hız düzeni korunur. Schema harici JS veya chart kütüphanesi gerektirmez.
- `public/robots.txt` private URL ailelerini engeller, CSS/JS/Livewire render kaynaklarını engellemez; üretim sitemap adresi `https://hypotenuse.be/sitemap.xml`. Statik dosya olduğu için alan adı taşınırsa ayrıca güncellenir; yerelde üretim URL'sini ilan eder.
- DB indeks migration'ı yapılmadı. Arama motoruna indekslenebilir URL sunmak ile SQL indeksi eklemek farklı işlemlerdir.

Kaynaklar: [Google sitemap oluşturma](https://developers.google.com/search/docs/crawling-indexing/sitemaps/build-sitemap), [robots meta kuralları](https://developers.google.com/search/docs/crawling-indexing/robots-meta-tag).

## 7. Dar test ve tarayıcı kanıtı

Yalnız ayrı `testing` DB ve DatabaseTransactions. Ana DB yazımı, migration/seed/temizlik, queue/email gönderimi, paket değişimi veya deploy yok.

- Son ilgili iki SEO dosyası: **20 test /221 doğrulama geçti**. Ardından token canonical sınırı için tek ek dar test **1/4 geçti**. Toplam **21 farklı SEO senaryosu /225 doğrulama**, iki son koşuyla başarılı; tam ürün suite'i değildir.
- Sekiz genel sayfa tek title/description/robots/canonical/OG ve ilk HTML ana başlığıyla sınandı. Blog, test/sınav, konu, ders, özel footer ve lig detayları; gerçek dil ve olmayan çeviri sınırı kapsandı.
- Arama/auth noindex ve özel hesap redirect'i; token URL'nin metadata'ya konmaması; pasif/gelecek detay404; gelecekte yayımlama; sitemap warm0 SQL ve değişiklikte yenile; hit ile tarihin değişmemesi; gerçek content edit tarihinin ilerlemesi sınandı.
- Varsayılan NL alternatifine İngilizce session'dan gidince gerçekten NL açılması ayrı testtir. Alias Host ve takip parametreleri canonical'ın kaynağı olamaz.
- Ortak cache davranışı için mevcut `FrontendPerformanceCacheTest::test_edit_invalidates_content_but_view_counter_does_not_and_delete_still_does`: **1 test /4 doğrulama geçti**.
-15 değişen PHP dosyasında syntax başarılı;14 kaynak/test dosyasında Pint başarılı. Eski büyük Functions.php yeniden biçimlendirilmedi, yalnız ilgili incrementIf bloğu değişti ve PHP syntax kontrol edildi.
- Gerçek Chrome/headless, izole testing web127.0.0.1:8765: sekiz genel sayfa + gerçek test detayında HTTP200/tek H1/metadata/schema; pageerror0. Test detayı390px mobilde yatay taşmasız ve görsel olarak kontrol edildi.
- Son dil URL revizyonu EN→NL oturum akışı ve gerçek test detayında ayrıca Chrome ile kontrol edildi: HTML dili ve canonical/hreflang eşleşti; pageerror0, mobil taşma yok. Genel sekiz ekranın browser kontrolü bu son URL revizyonundan önceydi; son genel canonical sözleşmesi20 testlik dar otomatik koşuda doğrulandı.
- İlk keşif testinde boş lig HTML'inde H1 eksikliği; sonra gerçek browser'da lazy sonrası çift H1 görüldü. İlk HTML ana başlık ve dönem H2 çözümüyle nihai kontrol geçti. Özel sayfa çift başlık riski ve olmayan EN konu çevirisi testi de bu aşamada giderildi. Önceki başarısız denemeler mevcut başarı sonucunu temsil etmez.

## 8. Dosya haritası

Yeni helperlar: `app/Helpers/FrontendSeo.php`, `SeoSchema.php`, `SeoSitemap.php`. Yeni test: `tests/Feature/FrontendSeoUpgradeTest.php`; eski `FrontendSeoTest.php` sitemap beklentisi açık dil URL'sine uyarlandı.

Değişen PHP: `FrontendCache`, `Functions` yalnız incrementIf; Frontend Home/Blog/Lesson/Test/Exam/League controllerları; Pages/UpdateHomePageAction ve Settings/SettingSaveAction.

Değişen görünümler: `components/seo/tags`; `frontend/layouts/app` ve `section/page-breadcrumb`; `frontend/home/index`, `page`, `sitemap`; blog/test/sınav detail; frontend leagues/index; Livewire home/tests-section ve leagues/league-table. `public/robots.txt` yenilendi.

Rehber: AGENTS, standartlar16, kaynak raporu50, bu54 ve index. CSS/JS dosyaları değişmedi; **APP_VERSION1.1.12 kalır**. Mevcut projedeki diğer çalışma değişiklikleri bu SEO aşamasının geliştirmesi sayılmaz.

## 9. Canlı doğrulama ve içerik eksikleri

1. Dağıtım sonrası APP_URL/HTTPS/gerçek domain, robotlara HTTP200, aktif diller ve sunucu/CDN engelleri kontrol edilir. Bu tur canlıya bağlantı veya dağıtım yapılmadı.
2. Search Console sitemap gönderimi, Google URL Inspection/Rich Results ve gerçek indeks durumu dış işlem olarak ayrıca yapılır; kaynakta doğru HTML bulunması canlı Google kabulü değildir.
3. Gerçek Core Web Vitals/LCP, ağ/TTFB ve büyük canlı sitemap hacmi ayrı ölçülür. XML geçerliliği veya warm helper0 SQL maksimum üretim hızı garantisi değildir.
4. Google tek sitemap için50.000 URL/50MB sınırını belirtir. Bu orta ölçekli uygulamada dar kolonlu cached sitemap kullanılır; katalog büyürse ölçüm ve sitemap index/parçalama ayrıca uygulanmalıdır.
5. Testing ayarlarında bazı site/yerel liste açıklamaları boş; fallback yalnız başlık üretir. Her dilde özgün ders/test/blog açıklaması, eğitim yaklaşımı ve doğrulanabilir yazar/kurum bilgisi panelden hazırlanmalıdır. Bu tur sahte editoryal metin veya yazar yazılmadı.
6. Eksik NL/EN/TR çeviri için gerçek içerik hazırlanmadıkça o dilde zengin arama görünümü iddiası yapılmaz. Yalnız meta keywords artırmak çözüm değildir.
7. Mevcut editör içeriğindeki başlık/görsel alt metni/HTML kalitesi ve eski blog görselleri yayın bazında kontrol edilir. Bu tur bütün canlı kayıtlar yeniden yazılmadı veya görseller taşınmadı.
8. SEO dışı50/51 adayları, kalan paket karşılıkları ve panel sorunları otomatik düzeltilmedi. Bu rapor tüm güvenlik/rol/PWA/ürün senaryolarının tamamlandığı anlamına gelmez.

Bu teknik geliştirme, arama motorlarında ve AI aramalarında bulunabilirlik için uygun kaynak oluşturur. Konum, indeksleme ve alıntılanma kararı arama sistemlerine aittir; birinci sıra garantisi yoktur.
