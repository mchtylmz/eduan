# Canlı log: sitemap ve boş arama — 6 Kasım 2026

Kullanıcının verdiği logda 4–6 Kasım tarihleri arasında dokuz hata kaydı var: altı sitemap `unexpected identifier "version"`, üç `ListLessons::$word` alanına null atanması. Başka bir hata türü bu ekte görülmedi; bu tüm canlı logların incelendiği anlamına gelmez.

## Düzeltme

- `resources/views/frontend/home/sitemap.blade.php`: XML başlangıcındaki `<` ve `?xml` kaynak metninde ayrıldı. PHP `short_open_tag=1` altında Blade derleyicisinin XML'i PHP açılışı sayması önlenir; üretilen XML bildirimi aynıdır. Sunucunun PHP ayarını değiştirmeye gerek yoktur.
- `resources/views/frontend/home/search.blade.php`: ders, konu, test ve blog arama bileşenlerine eksik/null sorgu yerine boş string aktarılır. Bileşenlerin string türleri ve dolu arama metni korunur; yeni arama kuralı/şema/ayar eklenmedi.
- `tests/Unit/ProductionLogRegressionTest.php`: iki PHP short-tag modu ve dört sekme × eksik/boş/whitespace/dolu sorgu sınırı.

## Kanıt

Yama öncesi gerçek Blade derlemesi, PHP short tags açıkken logdaki aynı `version` hatasını üretti. Arama şablonunun gerçek bileşen sınıflarına parametre aktarımı da aynı null/string TypeError'unu üretti. Test harness'ındaki eksik rota/SEO servisleri önce izole edildi; bunlar ürün hatası diye düzeltilmedi.

Yama sonrası PHP8.5.12: **18 test /58 doğrulama başarılı**, yeni test Pint kontrolü başarılı. XML iki PHP modunda gerçekten render edilip SimpleXML ile okundu; URL query escaping ve alternate dil bağlantısı doğrulandı. Arama şablonu gerçek Blade ile render edildi; dış layout/SEO ve Livewire veri sorgusu test doubles ile izole, native component property assignment gerçek sınıflardadır. Bu kontrol tam HTTP/Livewire hydration testi değildir.

Eduan Docker servisleri kapalı; başka projeler çalışıyor. Servisler değiştirilmedi, yeni DB/HTTP tarayıcı kabulü bu tur çalıştırılmadı. Önceki 97 kapsamındaki 555PHP/42Node/9Chrome kabulü önceki turun kanıtıdır; bu iki yeni düzeltmeden sonra bütün suite yeniden çalıştırılmış gibi sunulmaz. Canlıya aktarım yapılmadı.

## Yayında kontrol

Yeni Blade'ler aktarıldıktan sonra eski derlenmiş view cache temizlenip yeniden oluşturulur. `/sitemap.xml`200/geçerliXML; `/search?tab=lessons`, `topics`, `exams`, `newspaper` boş ve dolu sorguyla kontrol edilir. Mevcut canlı log silinmez: yayından sonraki yeni kayıtların saatleri izlenir. Başarısızlıkta bakım kaldırılmaz.

Bu düzeltme PHP/asset sürümünü, env değerlerini, veritabanını veya kullanıcı kayıtlarını değiştirmedi. Yayın hazırlığı 1.1.43 sürümünü ve `APP_PUBLIC_PATH=public_html` eşlemesini korur.
