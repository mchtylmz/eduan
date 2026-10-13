# Hesap alanı: sol menü ve sağ içerik

7 Ekim 2026. Kullanıcı önceki56 tasarımını revize etti: hesap kapak/breadcrumb alanı kaldırılacak; geniş ekranda3 kolon profil/menü ve9 kolon içerik olacak. Mobilde kısa kimlik bölümü altında açılır menü kullanılacak. Menü alt açıklamaları kaldırılacak. Gerçek fotoğraf özelliği bu fazda istenmedi.

## 1. Ortak sayfa yerleşimi

Yeni `resources/views/frontend/account/layout.blade.php`, mevcut `frontend.layouts.app` şablonunu genişletir. Hesap sayfaları bu ortak layout üzerinden `account-content` bölümünü doldurur.

- Ortak site header/footer/SEO/asset/Livewire yapısı korunur.
- Hesap sayfaları artık coverAccount ayarı veya page-breadcrumb include'u kullanmaz. Başka sayfaların kapak alanları etkilenmez; ayarın veritabanındaki değeri silinmez.
- `aside.col-md-3.member-sidebar`: profil kimliği ve menü.
- `.col-md-9.member-content`: mevcut sayfa içeriği.
- Sağ içerikte tek H1 ve mevcut sayfa başlığı vardır. Kapak kaldırılırken ana başlık kaybedilmedi.
- 768px ve üzerinde menü/içerik aynı hizadan başlar; oran1:3'tür. iPad'de menü açık kalır.
- 768px altında kimlik/menü üstte, içerik alttadır; menü kapalı başlangıçla ilk içerik için uzun menü yüksekliği oluşturmaz.
- Bilgi/parola kartları içerik içinde geniş ekranda iki kolon, dar tablette/mobilde alt alta gelir.

### Uygulanan ekranlar

| Şablon | Mevcut ekran |
|---|---|
| index | Profil / hesap bilgileri |
| favorite | Favori testler |
| solved | Çözülen testler |
| solved-not-complete | Tamamlanmamış testler |
| results | Çözülen sınavlar |
| result | Sınav sonuç detayı |
| stats | İstatistikler |
| my-leagues | Kişisel lig sonuçları |

Sınav sonuç detayı da ortak menüye alındı; detayın tab/sonuç HTML'i ve sorguları değiştirilmedi. `unsubscribe` misafir abonelik bağlantısıdır; authenticated hesap menüsüne dahil edilmedi, mevcut kapaksız görünümü korundu.

## 2. Profil kimliği ve menü

- Sol üstte ad/soyad baş harflerinden72px avatar ve kullanıcının görünen adı.
- Mobilde avatar48px, kısa yatay kimlik alanı.
- Gerçek fotoğraf yükleme, yeni DB kolonu, dosya veya dış avatar servisi yok.
- Önceki büyük üst kimlik kartının intro/e-posta/kayıt/onay meta satırları kaldırıldı; e-posta ve diğer bilgiler mevcut profil formunda kalır.
- Menüde yalnız ikon ve menü adı vardır; `_hint` açıklamaları gösterilmez.
- Profil/favori/test/sınav/lig/istatistik route'ları korunur.
- Tamamlanmamış test ekranında test menüsü; sınav sonuç detayında sınav menüsü aktif olur.
- Aktif link `aria-current="page"` ile de belirtilir.
- Lig linki yalnız mevcut leagueStatus aktifken görünür.
- Yönetim paneli aynı `dashboard:access` izniyle görünür; çıkış bağlantısı menünün altındadır.
- Yetki/oturum/sahiplik sorguları, kayıt/parola validation ve action'ları değiştirilmedi.
- İki gelecek menünün adı/işlevi hâlâ kullanıcı kararıdır; boş link/kart üretilmedi.

## 3. Mobil açılır menü

Mevcut Livewire'ın Alpine ortamı kullanılır; yeni JavaScript dosyası veya paket yok.

- `menuOpen: false` ile her tam sayfa yüklemesi kapalı başlar.
- Düğme `aria-controls`, dinamik `aria-expanded` ve gerçek button type taşır.
- Tıklama/Enter ile açılıp kapanır; yalnız ikon değil bütün düğme tıklanır.
- Link seçildiğinde `menuOpen=false` ve normal mevcut route navigasyonu çalışır.
- Gidilen hesap sayfası yeniden kapalı başlar; aktif link yeni route'a göre belirlenir.
- `x-cloak` mobilde başlangıç menü parlamasını önler. Desktop media kuralı menüyü sürekli görünür tutar; düğme geniş ekranda yoktur.
- Klavye focus görünürlüğü korunur. Mobilde sayfa yatay taşmaz.

## 4. Stil ve dosyalar

- Yeni `resources/views/frontend/account/layout.blade.php`.
- Ortak `resources/views/frontend/account/tab.blade.php` sol menü olarak revize edildi.
- Sekiz hesabın `index/favorite/solved/solved-not-complete/results/result/stats/my-leagues.blade.php` şablonu ortak yerleşime geçti.
- `public/assets/app.css`: eski member-* yatay kimlik/grid menü kuralları yerine sidebar/düğme kuralları; form görünümü korunur. 768–991px'de ikon/padding daraltılarak uzun menü adına yer ayrılır.
- `.env`: **APP_VERSION1.1.14 → 1.1.15**. CSS URL'si tarayıcıda doğrulandı.
- `tests/Feature/FrontendAccountPresentationTest.php`: sekiz route ortak yerleşim ve kapak testi eklendi; önceki kullanıcı/izin görünürlüğü testi korundu.
- AGENTS.md, standartlar16, bu rapor ve index güncellendi.56 önceki tasarımın tarihsel kanıtıdır; güncel yerleşim bu belgedir.

## 5. Dar test ve tarayıcı kanıtı

Yalnız iki ilgili test çalıştırıldı: **2 test / 94 doğrulama geçti**.

1. Mevcut profil testi: kendi kullanıcı bilgileri, başka kullanıcı metni yok; dashboard izni/lig koşulu, ilk HTML formları, noindex ve aktif link.
2. Yeni ortak sayfa testi: sekiz route200,3/9 kolonlar, mobil düğme/aria-controls, aktif menü, tekH1; coverAccount dolu verilmesine rağmen kapak/breadcrumb yok; menü açıklaması yok.

Testler yalnız ayrı **testing** DB'de `DatabaseTransactions` kullanır; yeni fixture kayıtları transaction sonunda geri alınır. İlk sandbox çağrısı DB bağlantısına izin vermedi ve0 doğrulamada durdu; izinli yerel bağlantıda iki test geçti. Uygulama hatası olarak değerlendirilmez. Test PHP syntax/Pint ve ilgili diff whitespace kontrolü başarılı.

Gerçek Google Chrome / testing QA:

- 1440px: sol/sağ aynı başlangıç yüksekliği, ölçülen sidebar/content oranı0.333333; kapak yok, menü açık, mobil düğme gizli.
- 768px: aynı oran/hiza, menü görünür; bilgi/parola kartları içerikte alt alta, document taşması yok. Uzun Flemenkçe menü adının son dar CSS revizyonu ayrıca tablet kontrolüyle incelendi.
- 390px: kapalı başlangıç; düğmeyle açılma, favoriye gerçek route geçişi sonrası kapalı menü ve aktif link.
- Enter ile açma/kapama ve sayfa yatay taşma kontrolü geçti; page error yok.
- Tarayıcı testindeki ilk anlık görünürlük ölçümleri Alpine DOM güncellemesini beklemiyordu; yalnız QA scriptine visible/hidden beklemesi eklendi, ürün davranışı değiştirilmedi.

Ekran görüntüleri Git dışında `/tmp/eduan-front-performance-1007/account-sidebar-{desktop,tablet,mobile,mobile-open}.png`. Desktop/tablet/mobil açık görüntüler görsel olarak incelendi. Testing QA hesabının giriş zamanı/session/logu testing ortamında güncellenebilir; ana yerel hesap/veri kullanılmadı.

## 6. Kapsam sınırı

Ana DB, migration, paket, controller/model/sorgu/cache, mail veya deploy değişikliği yok. Normal localhost/Sail portları değiştirilmedi, Docker veya diğer projeler durdurulmadı. İzole QA web sunucusu kontrol bitince kapatılır. Genel suite/build, bütün hesap raporlarının iş mantığı, üretim hız ölçümü veya tüm cihaz/rol matrisi bu görsel revizyonla doğrulanmış sayılmaz. Profil fotoğrafı ve iki yeni menü ayrı gelecek kapsamdır.
