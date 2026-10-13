# Rappasoft → Bootstrap Table geçiş araştırması

**Güncel uygulama49:** [49-KULLANICI-ALT-TABLOLARI-VE-MENU-GRUPLARI.md](49-KULLANICI-ALT-TABLOLARI-VE-MENU-GRUPLARI.md). Users Favorite/Students/Teachers/TopicsNotResults taşındı;31 başlangıç sınıfından30'u tamamlandı. **Yalnız Ai/LatexImagesTable kaldı.** Kullanıcı bağlamı/cache/atama ve menü rol matrisi dar testlerle doğrulandı; genel suite kabulü değildir. Eski aşağıdaki aşama sayıları kendi tarihiyle korunur. Paket karşılıklarının güncel durumu51 içindedir.

**Son uygulama47:** [47-SINAV-LIG-ISTATISTIK-VE-AI-ALT-TABLOLARI.md](47-SINAV-LIG-ISTATISTIK-VE-AI-ALT-TABLOLARI.md). Süreli sınav/sonuç/detay, lig/sonuç ve AI kullanım/oy7 eski sınıf taşındı. İstatistik JSON/Excel ve custom view sort eklendi. Kalan5: AI LaTeX görselleri ve Users Favorite/Students/Teachers/TopicsNotResults. Aşağıdaki12/28 sayıları tarihsel aşama kaydıdır; güncel kabul ve sınırlar47 içindedir.

**Son uygulama45:** [45-EGITIM-MENUSU-BOOTSTRAP-TABLE-GECISI.md](45-EGITIM-MENUSU-BOOTSTRAP-TABLE-GECISI.md). Dört eğitim ana listesi ve üç bağlı panel taşındı. AI/soru varsayılan kart; diğerleri tablo.60sn/1saat cache ve güncel öğretmen kapsamı;12 eski DataTableComponent kaldı. Dar PHPUnit ve Chrome kanıtı45 içindedir; AI kullanım/oy/LaTeX, süreli sınav, lig ve bazı user listeleri henüz geçiş bekler. Aşağıdaki aşamalar tarihseldir.

**Sonraki uygulama — 6 Ekim:** [38-BOOTSTRAP-TABLE-UC-SAYFA-PILOTU.md](38-BOOTSTRAP-TABLE-UC-SAYFA-PILOTU.md). Dersler/Konular/Kullanıcılar taşındı; JSON60sn/seçenek1saat ve readonly sağ detay paneli onaylandı.26 dar test geçti; Chrome masaüstü/mobil kontrolü yapıldı.28 diğer Rappasoft sınıfı bekliyor. Aşağıdaki kayıt kendi tarihsel kapsamıyla korunur.

**Güncel kurulum:** [35-PHP85-VE-LARAVEL13-GUNCELLEME.md](35-PHP85-VE-LARAVEL13-GUNCELLEME.md). Yerel PHP8.5.8, Laravel13.34.0, Livewire4.4.7, Alert4.2, Excel4.0.3, PHPUnit13.4.1 kuruldu.132 bağımlılık; platform/audit kontrolü başarılı.16 farklı dar senaryo geçti; tüm ürün kabulü değil. Yeni Dockerfile build registry timeout verdi, doğrulanan mevcut yerel8.5 image kullanıldı. Tablolar/tarayıcı/yedek karşılıkları beklenir.

**Güncel uygulama:** [34-PAKET-CIKARMA-VE-PROJE-TEMIZLIGI.md](34-PAKET-CIKARMA-VE-PROJE-TEMIZLIGI.md). Yedi paket gerçek composer/lock/vendor’dan kaldırıldı; 152 → 131 bağımlılık, kalan sürümlerde değişiklik yok. PHP 8.5 ve PHPUnit 13 hedefi kesin; mevcut Laravel 11/LW3 korunuyor. Ara sürümde tablolar/tarayıcı/yedek karşılıkları beklenir; canlıya alınmaz. Dar sonuç 6 test/21 doğrulama.

**Yeni sıra ve cache kararı:** [32-PAKET-KARARLARI-VE-SITEGROUND-HAZIRLIGI.md](32-PAKET-KARARLARI-VE-SITEGROUND-HAZIRLIGI.md). Rappasoft Composer hedefinden çıkarılacak; Livewire4 ile geçici Rappasoft3.8 kullanımı yok. Table JSON ve filtre seçenekleri dil/yetki kapsamında cache kullanabilecek; TTL ve ekran eylemleri sayfa bazlı belirlenecek. Bu tur paket/table değişmedi.

5 Ekim 2026. **Karar kesin, uygulama sonraki faz:** Kullanıcı Laravel yükseltmesinden sonra Rappasoft'u çıkarmayı ve kendi Lara Football projesindeki Blade + Bootstrap Table/server-side JSON düzenini kullanmayı seçti. OneUI devam eder; Vue.js eklenmez. Bu kayıt araştırmadır; paket kaldırılmadı, JSON endpoint'i/tablo yazılmadı, diğer projeye değişiklik yapılmadı.

## 1. Yerel örnek doğrulaması

Lara Football kaynakları salt okunur incelendi:

- `/Users/mucahit/Desktop/LARAVEL/lara_football/resources/views/components/admin/server-table.blade.php`
- `resources/views/components/admin/filter-bar.blade.php`
- `resources/views/admin/statistics/favorites.blade.php`
- `app/Http/Controllers/Admin/FavoriteStatisticsController.php`

Örnek server-table: table-responsive wrapper, data-url, server pagination, arama sağda/araçlar solda, refresh, sayfa boyutu ve sıralama data attribute'ları. Blade kolonları tanımlar; JS sayfa hazırken tabloyu başlatır. Tab filtre submit'i refresh + pageNumber:1 kullanır; gizli sekme ilk shown'da initialize edilir, tekrar açılışta resetView yapılır.

Örnek endpoint limit/offset/search/sort/order doğrular, sort'u SQL kolonlarına açık eşler; SQL sayfalama sonrası **total/rows** döndürür. Kullanıcı metni formatter'da escapeHtml kullanır. Bu görüntüleme/sorgu düzeni Eduan'a örnektir; futbol tabloları, mobil abonelik kuralları ve tr/en iki dil eşlemesi kopyalanmaz.

**Cache ayrımı:** İncelenen FavoriteStatisticsController'da Laravel Cache::remember yoktur. Kullanıcının Eduan için istediği sayfa/filtre backend cache'i ayrıca eklenecek bir gereksinimdir; Lara'da mevcutmuş gibi anlatılmaz.

## 2. Bootstrap Table dokümanı

Resmî [Table Options](https://bootstrap-table.com/docs/api/table-options/) ve [Methods](https://bootstrap-table.com/docs/api/methods/) incelendi. Server-side çalışmada URL/ajax gerekir; varsayılan limit yaklaşımı limit, offset, search, sort, order parametrelerini kullanır. Cevap total/rows sözleşmesine bağlanır; refresh yeniden uzak isteği yapar. Plugin'in cache seçeneği AJAX istek cache davranışıdır; Laravel SQL sonuç cache'i değildir.

Eduan altında statik kaynak `public/backend/assets/js/plugins/bootstrap-table/package.json` sürümü **1.22.6**. Resmî doküman güncel sürümü anlatabilir; uygulanacak sürüm/asset/uzantılar ayrıca sabitlenip karşılaştırılır. Şu an scriptlerin panel tablolarına bağlandığı iddia edilmez.

## 3. Mevcut bağlı kullanım envanteri

**31 DataTableComponent sınıfı + 61 yayımlanmış Blade görünümü**. İki ortak trait, config ve tam Composer 3.5.6 bağımlılığı da vardır.

| Grup | app/Livewire altındaki sınıflar |
|---|---|
| AI (4) | Ai/AnswerAiTable, AnswerVoteTable, LatexImagesTable, UserUsageTable |
| İçerik (5) | Blogs/BlogsTable; Contacts/ContactTable; Faqs/FaqTable; Newsletters/NewsletterTable; Pages/PagesTable |
| Süresiz test (6) | Exams/ExamTable, FavoriteTable, QuestionTable, ResultsDetailTable, ResultsTable, ReviewsTable |
| Havuz/dil/rol (5) | Languages/LanguageTable; Lessons/LessonTable; Topics/TopicTable; Questions/QuestionTable; Roles/RoleTable |
| Lig (2) | Leagues/LeagueTable, LeagueResultsTable |
| Süreli sınav (3) | Tests/TestTable, ResultsTable, ResultsDetailTable |
| Kullanıcı (5) | Users/UserTable, StudentsTable, TeachersTable, FavoriteTable, TopicsNotResultsTable |
| Log (1) | Settings/LogsTable |

Ek kaynaklar: `app/Traits/LivewireTableConfigure.php`, `CustomLivewireTableFilters.php`, `config/livewire-tables.php`, `resources/views/vendor/livewire-tables`, ilgili controller/Blade çağrıları. ResultStatsCustomTable adlı bileşen plain Livewire'dır, DataTableComponent değildir; yalnız adında table var diye Rappasoft bağımlılığı sayılmaz.

Ortak mevcut davranışlar: 15 default ve kabul edilen sayfa boyutları, tek sort, query string, slide-down filtre, yükleme/boş durum, Rappasoft filtre seçenekleri. Enum badge/component/Count/Link/WireLink kolonları yeni formatter/alan sözleşmesine çevrilir.

## 4. Çıkarılması uygun mu?

**Evet, veri modelleri ve iş mantığı korunarak yapılabilir.** Rappasoft ağırlıkla liste/filter/column/UI eylem katmanıdır; var olan form/Action/model katmanı kalabilir. Ancak hemen Composer remove yapmak 31 sınıfı, trait'leri ve template'leri kırar. Kaynakta delete, showModal/showDetail/showFavorites, kullanıcı atama ve detay açma gibi tabloya bağlı eylemler vardır; sadece JSON listeyle bitmez.

Her tablo için checklist:

1. Builder filtre/scope, teacher/student ve seçili parent modeli.
2. Varsayılan ve izinli sort, search kolonları, deterministic ikinci sıra.
3. İlgili dil/durum/tarih/ders/konu/kullanıcı/sınav/sezon filtreleri.
4. Kolon değerleri, enum badge, ilişkili sayılar, tarih/süre/puan.
5. Edit/delete/favorite/assignment/modal/detail eylemleri ve sunucu izinleri.
6. Export'un aynı veri/filtre/kapsamı kullanması.
7. Empty/loading/403/422/500, mobile ve Bootstrap Table locale.
8. İlgili feature/JSON + gerçek browser QA.

## 5. Gelecek JSON ve cache sözleşmesi

- GET liste endpoint'i mevcut auth/dashboard/kaynak view ve teacher kapsamını yeniden denetler.
- Total filtrelenmiş sayıdır; rows yalnız sayfa verisidir. Kayıt yok cevabı total:0/rows:[] olur; yetki hatası bununla gizlenmez.
- Normalize edilmiş limit/offset/search/sort/order ve sayfaya özel filtreler kullanılır. Kolon adı doğrudan istemciden SQL'e geçirilmez.
- Cache key tablo sürümü + locale + erişim/kullanıcı/teacher kapsamı + normalize filtreler + sort/order + sayfa içerir. İzin/sahiplik cache okumasından önce denetlenir.
- Filtre seçenekleri bağımlı ders→konu ve teacher→öğrenci kapsamıyla cache'lenebilir; başka kullanıcıya seçenek sızmaz.
- CRUD/atama/izin/ilgili sonuç değişimi hedef cache'i geçersizleştirir. Global flush yeni varsayılan değildir.
- Cache TTL, count/rows ayrımı, yenile düğmesinin bypass/invalidation davranışı ve key hacim sınırı geçişte netleştirilir. Bugün rastgele TTL seçilmedi.
- Plugin AJAX cache, olası pipeline uzantısı ve backend cache ayrı kararlardır; kullanıcıya özel panel JSON PWA offline cache'e alınmaz.

## 6. Laravel sırası ve geçiş fazı

İlk kullanıcı sırası **önce Laravel yükseltme, sonra Rappasoft kaldırma** idi; bu paragraf tarihsel engel değerlendirmesidir. Son seçim 32 ile paket çıkarma → doğrudan13/Livewire4 → kendi tabloları şeklinde değişti. Kilit 3.5.6 Illuminate 10/11 gereksinimi yükseltme engelidir (`14` kilit kanıtı). Bu tam sürümü Laravel 13'e taşımak mümkün değildir. Yükseltme fazında uyumlu geçici sürüm veya kullanıcıya sunulacak kontrollü geçiş dalı stratejisi gerekir; uyumlu sürüm henüz seçilmedi. Kullanıcı sırasını sessizce ters çevirme.

Önerilen taşıma adımları: önce en küçük bağımsız liste pilotu (dil/ders gibi), sonra havuz ve içerik, sonuç/öğrenci scope, modal/atama/AI/log karmaşık tabloları. Her biri mevcut davranışla karşılaştırılır. Son bağlı sınıf/trait/config/view/asset kalmayınca Composer remove ve hedef sürüm testleri yapılır. Bu fazlar şimdiki uygulama onayı değildir.

## 7. Performans kanıtı

Rappasoft'un gecikme sebebi olduğu henüz ölçülmedi. Önce aynı kullanıcı/veri/filtre ile HTML TTFB, ilk veri görünme süresi, Livewire/JSON istekleri, SQL count/rows süresi, N+1 ve asset hacmi kaydedilir. Yeni tablo aynı senaryoda ölçülür. Sadece HTML'nin önce gelmesi toplam veri süresinin iyileştiğini kanıtlamaz.

Pilot kabulü: pagination/search/sort/filter/kapsam ve CRUD sonrası yenileme doğru; browser plugin bir kez initialize edilir; total/rows görünür kayıtlarla eşleşir; refresh/search/filter çalışır; ilgili console hatası yoktur. Mevcut PHP testleri gerçek Bootstrap Table browser kontrolünün yerine geçmez.


## 8. Güncel kapanış —7 Ekim /60

[60](60-RAPPASOFT-SON-TABLO-VE-PAKET-KARSILIK-FAZLARI.md) ile son Ai/LatexImagesTable Bootstrap serverJSON'a geçti. Kalan aktif Rappasoft tablo0; kullanılmayan iki trait/config/62şablon/23dil dosyası kaldırıldı. Çalışma kaynaklarında Rappasoft referansı ve Composer lock paketi0. Buradaki31sınıf listesi tarihsel geçiş haritasıdır;5dar test47assert ve9JSassert yalnız son LaTeX/cache/izin/scheduler sınırını kanıtlar, bütün eski tabloların tüm ürün kabulü değildir.
