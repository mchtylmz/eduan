# Kod yazım rehberi

## Yeni işte izleyeceğin örnek

1. En yakın mevcut özelliği seç: route (`routes/frontend.php` veya `routes/backend.php`), controller, Blade, Livewire, Action, model ve migration bağlantısını çıkar.
2. Route adı ve model bağlamasını koru. Panelde `AutoRoute::auto` kullanımı yaygındır; yeni controller metodunun hangi URL/HTTP metoduna açıldığını `route:list` ile doğrula. İşlem izni için route `can:*` ve eylem içi kontrolü bir arada düşün.
3. Görünüm hazırlayan controller'ı kısa tut. Livewire bileşeninde `mount`, `rules`, `validationAttributes`, `save`, `render` düzeni var (`ExamForm`, `TestForm`, `QuestionForm`). Tekrarlanan iş adımlarını mevcut Action düzeniyle yaz; somut ihtiyaç yoksa yeni servis katmanı ekleme.
4. Eloquent ilişki ve scope isimlerini yakındaki modelle uyumlu tut. Enum'lar `app/Enums` içinde; string değerini tahmin etme. Liste ve select sorgularını `DataHelper`/`FilterHelper`, mevcut cache süresi ve görünürlük kuralına göre değerlendir.
5. Formdan gelen her değeri doğrula. Livewire public özelliklerini ve route model binding'i güvenilir kabul etme; yetki, sahiplik ve nesne ilişkisini sunucu tarafında yeniden kontrol et. Yazma işleminde çok kayıt etkileniyorsa transaction ve idempotency ihtiyacını değerlendir.
6. Blade'de mevcut Bootstrap 5/OneUI sınıflarını ve bileşenleri izle. Görünür etiketleri `__()` veya mevcut translatable alan düzeniyle ekle. `public/assets/app.*` ve `public/backend/assets/app.*` içindeki özel kodların kullanım yerini kontrol et.

## Yerel yazım ve bakım kuralları

- `.editorconfig`: UTF-8, LF, 4 boşluk, final newline; YAML 2 boşluk. Projede özel `pint.json` yok, `laravel/pint` dev bağımlılığı var. Formatter çalıştırılacaksa yalnızca değişen PHP dosyalarını hedefle ve gereksiz toplu fark üretme.
- İsimler mevcut kodda karışık (`TestsResult`, `ExamResult`, `sections_with_no_parent`, Türkçe mesajlar). Yalnız kapsam içindeki kodu değiştir; genel isim temizliği ayrı plan gerektirir.
- Geliştirici hedefi: kısa, anlaşılır ve proje tarzına uygun kod. Aynı mantığı korurken gereksiz generic abstraction, çok uzun class, davranışı saklayan helper ve kapsam dışı refactor ekleme.
- `Loggable` trait'inin model olaylarında veri kaydettiğini unutma. PII ve gizli veriyi log/açık HTML içine taşımadan önce etkisini incele.
- `resetCache()` hem tüm cache'i temizler hem `optimize:clear` çalıştırır; eski kodda bulunması yeni işlemlerde aynısını çağırmak için gerekçe değildir. Cache anahtarı, locale, kullanıcı/rol ve sayfalama kapsamını belirt.
- `UploadFileAction` dosyayı `public` diskine `uploads/...` yolu olarak taşır; uzantı ve gerçek içerik işleme akışını özellik bazında doğrula. Ayar dosyaları ayrıca PWA manifest/ikonlarını üretir.
- E-posta, queue, yedek, Cloudflare, AI ve import/export işlemleri dış sistem ve/veya veri etkili olabilir. Araştırma ve testte bu çağrıları fiilen tetikleme.

## İki tablo kütüphanesini karıştırma

Mevcut panel kodu örnekleri `app/Livewire/Exams/ExamTable.php`, `Tests/TestTable.php`, `Users/UserTable.php` ve `Traits/LivewireTableConfigure.php` dosyalarıdır. Rappasoft `DataTableComponent` `columns()`, `filters()`, isteğe bağlı `builder()`, `appendColumns()` ve `configure()` düzenini izler. `CustomLivewireTableFilters` ortak dil, durum, kullanıcı, ders, konu, tarih ve sezon filtrelerini sunar. `config/livewire-tables.php` teması `bootstrap-5`, varlık önbelleği açık. `resources/views/vendor/livewire-tables/` altında 61 yayınlanmış override dosyası vardır; görünüm ve filtre davranışı bunlara bağlıdır.

`public/backend/assets/js/plugins/bootstrap-table` statik kütüphane dosyaları ayrıca vardır; ilk taramada uygulamanın `bootstrapTable()` çağrısı saptanmadı. Gelecekte Rappasoft'tan Bootstrap Table'a geçmek kullanıcı tarafından karar verilecek ayrı iş ve performans/erişilebilirlik karşılaştırmasıdır. Biri diğerinin otomatik devamı değildir.

## Doğrulama standardı

Yeni/onarılmış iş mantığı için en dar anlamlı testleri yaz: başarılı kullanım, hatalı girdi, yetkisiz kullanıcı ve kritik sınır durumu. Gerekirse Livewire bileşenini, veritabanı yan etkisini ve HTML/JS'nin tek gerçek akışını doğrula. `tests/Feature/ExampleTest.php` ve `tests/Unit/ExampleTest.php` yalnız iskelet; onları yeterli kapsam sayma. Tüm suite veya build yalnız somut ihtiyaç varsa. Bir kez yeterli kanıt toplandıktan sonra aynı doğrulamayı tekrar etme.
