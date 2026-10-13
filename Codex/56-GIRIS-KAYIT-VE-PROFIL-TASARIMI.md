# Giriş, kayıt ve ön yüz profil tasarımı

7 Ekim 2026. Kullanıcı giriş/kayıt formlarının ilk ekranda hızlı açılmasını, kayıt telefon alanındaki çevirinin kalıcı düzeltilmesini ve mevcut menülerle modern bir profil tasarımı istedi. İki yeni menü daha sonra tanımlanacak; bu aşamada yeni özellik veya boş menü eklenmedi.

## 1. Önceki çalışma prensibi

- Login ve Register, `Lazy(isolate: true)` ile ilk HTML yerine sonradan mount ediliyordu.
- Profil bilgileri ve parola formları da lazy idi; her biri oturumdaki kullanıcı için ayrıca `User::find()` çağırıyordu.
- Formlar ilk ekranda gerekli, hafif bileşenlerdir. Lazy kaldırılması ilk kullanılabilir form için ek istek beklemesini kaldırır; başka ağır raporların lazy davranışına uygulanmaz.
- Kayıt formu `__('Telefon Numarası')` anahtarını kullanıyor. Flemenkçe dosyada `(1)` ve `(2)` ekli telefon anahtarları bulunması bu tam anahtarın çevrildiği anlamına gelmiyordu.
- Panel çeviri kaydı `TranslationHelper::build()` aracılığıyla JSON dosyasını veritabanındaki değerlerle yeniden oluşturur. Yalnız JSON dosyasına geçici ekleme yapmak, sonraki yeniden üretimde çeviriyi kaybettirebilirdi.
- Hesap sayfaları ortak `frontend/account/tab.blade.php` menüsünü kullanır. Menü düzenlemesi profil dışındaki favori, çözüm, lig ve istatistik sayfalarında da görünür.

## 2. İlk HTML ve sorgu değişikliği

Lazy kaldırılan dört sınıf:

1. `Frontend/Auth/Login`.
2. `Frontend/Auth/Register`.
3. `Frontend/Account/ProfileForm`.
4. `Frontend/Account/UpdatePasswordForm`.

Profil ve parola formlarının mount işlemi `request()->user()` kullanır; aynı kullanıcıyı tekrar `User::find()` ile aramaz. Menü ve kimlik özeti de oturumdaki kullanıcıdan oluşturulur; yeni istatistik veya sayaç sorgusu eklenmedi.

Tarayıcıda kayıt, giriş ve profil için ek lazy mount isteği **0** bulundu; formlar ilk HTML'deydi. Bu bir üretim TTFB/LCP ölçümü veya bütün sitenin maksimum performans kabulü değildir. Profil HTML'i, oturum bilgisi ve form snapshot'ları public yedi günlük cache'e eklenmedi.

Kurtarma, yeni parola, çözüm, favori ve diğer rapor bileşenlerinin lazy durumu bu fazın kapsamında değildir.

## 3. Kalıcı telefon çevirisi

| Dil | Telefon metni |
|---|---|
| nl | Telefoonnummer |
| en | Phone number |
| tr | Telefon Numarası |

- Mevcut tam JSON anahtarı korundu; yeni veritabanı çeviri anahtarı/migration eklenmedi.
- `lang/nl.json` içinde eksik anahtar eklendi.
- Daha önce kaynak ağacında bulunmayan `lang/en.json` ve `lang/tr.json` bu telefon anahtarıyla eklendi. Bu iki dosya bütün mevcut arayüz çevirilerinin tamamlandığı anlamına gelmez.
- `lang/nl/account.php`, `lang/en/account.php`, `lang/tr/account.php` hesap ekranı etiketlerini ve telefon için kaynak varsayılanını taşır. Mevcut `leagues.php` gibi Laravel PHP dil dosyası yaklaşımı kullanılır.
- Panel JSON üretiminde telefon değeri eksik/null/boşsa, ilgili dilin `account.php` telefon değeri kullanılır.
- nl/en değerinde çevrilmeden kopyalanmış tam Türkçe anahtar da ilgili varsayılanla değiştirilir.
- Dolu özel panel telefon çevirisi korunur. Başka JSON anahtarları değiştirilmez.
- Düzeltilen JSON çıktısıdır; canlı veya ana yerel veritabanındaki çeviri satırları bu işlemle yazılmadı.
- Normal kayıt/profil label, placeholder ve validation telefon anahtarı aynı kaldığından aynı çeviri kaynağını kullanır.

Hesap ekranının yeni metinleri `account.*` PHP anahtarlarıdır; bunlar JSON yeniden üretimi sırasında silinmez. Telefon dışındaki eski giriş/kayıt arayüzünün bütün dil anahtarları bu kapsamda yeniden yazılmadı.

## 4. Profil yerleşimi

### Kimlik özeti

- Baş harflerden oluşturulan avatar; yeni dosya yükleme veya avatar servisi yok.
- Kullanıcının mevcut görünen adı, e-posta adresi, kayıt tarihi ve e-posta onay durumu.
- Projenin mevcut fontu, ana renk değişkeni, ikonları ve Bootstrap yapısı.
- Kullanıcı metinleri Blade escape ile gösterilir; özel bilgiler yalnız oturumdaki kullanıcıdan alınır.
- Sayfanın mevcut breadcrumb/H1 düzeni korunur; hesap bölümlerinde H2 kullanılır.

### Mevcut menüler

| Menü | Mevcut route | Görünürlük |
|---|---|---|
| Hesap bilgileri | frontend.profile | Hesap kullanıcısı |
| Favori testler | frontend.favorite | Hesap kullanıcısı |
| Çözülen testler | frontend.solved | Hesap kullanıcısı |
| Çözülen sınavlar | frontend.solved.exams | Hesap kullanıcısı |
| Lig sonuçlarım | frontend.my.leagues | Mevcut leagueStatus aktif |
| İstatistikler | frontend.stats | Hesap kullanıcısı |
| Yönetim paneli | backend ana sayfası | Mevcut dashboard:access izni |
| Çıkış | Mevcut çıkış bağlantısı | Hesap kullanıcısı |

Menü kartlarında ikon, başlık ve kısa açıklama vardır. Aktif menü görsel olarak ve `aria-current="page"` ile belirtilir; sınav sonuç detayı sınav menüsünü aktif tutar. Lig kapalıysa kart gösterilmez, boş grup oluşturulmaz. Yönetim paneli bağlantısı mevcut ön yüz header'ı ve backend erişimi gibi `dashboard:access` iznini izler; yeni izin/rol tanımı yapılmadı.

Grid geniş ekranda üç, dar ekranda iki kolon kullanır. Yeni iki menü tanımlandığında aynı diziye eklenebilir; bu fazda adları veya işlevleri varsayılmadı.

### Bilgi ve parola formları

- Geniş ekranda ayrı iki kart, dar ekranda alt alta düzen.
- Ayrı bölüm başlığı ve kısa açıklama; kişisel bilgi ve parola işlemleri ayrı kalır.
- Label/input `for` ve `id` bağlantıları; ad, soyad, e-posta, telefon için uygun autocomplete.
- Parola için current-password/new-password autocomplete ve görünürlük düğmesinde erişilebilir etiket.
- Mobilde kaydetme düğmeleri kolon genişliğine yayılır; form kontrolleri taşmaz.
- Mevcut wire:model, validation, CSRF, loading ve save eylemleri korunur.
- Parola değiştirmek için mevcut parola doğrulaması devam eder. Hız sınırı, giriş yönlendirmesi, kayıt koşulları ve mail akışı değiştirilmedi.

## 5. Dar testler

Yeni `FrontendAccountPresentationTest`, `DatabaseTransactions` ile yalnız ayrı **testing** DB'de çalışır. Ana DB'ye kayıt/migration/seed yazılmaz. Mail fake kullanılır; gerçek mail gönderilmez.

| Senaryo | Denetlenen davranış |
|---|---|
| nl/en/tr ilk form HTML'i — üç vaka | Giriş/kayıt alanları ilk HTML; telefon label/validation dili; lazy işareti yok |
| Panel JSON yeniden üretimi | Eksik/null/boş/kopya Türkçe telefon fallback; özel dolu çeviri ve diğer anahtar korunur |
| Profil ve menü | Kendi kullanıcı metni, diğer kullanıcı ayrımı, mevcut lig/izin görünürlüğü, aktif link, noindex ve ilk HTML formları |
| Profil kaydetme | Hatalı kısa isim reddi; geçerli isim/telefon güncellemesi yalnız ilgili kullanıcı |
| Parola güncelleme | Yanlış mevcut parola reddi; doğru parola ile yalnız ilgili kullanıcının güncellenmesi |
| Hatalı kayıt/giriş | Validation ve koşul reddi; kullanıcı artışı veya mail gönderimi yok |
| Başarılı giriş | Gerçek Login bileşeni, authentication ve profile yönlendirmesi |

İlk koşuda 9 senaryodan 8'i geçti, başarılı giriş testinde test isteğine session bağlı olmaması nedeniyle bir error oluştu. Runtime giriş kodu değiştirilmedi; test RequestBroker isteğine session bağlandı. **Yalnız bu son test** tekrar çalıştırıldı ve geçti. Sonuç **9 farklı yeni senaryo / 95 doğrulama**, ayrı dar koşuların toplamıdır; tek temiz 9'lu koşu diye sunulmaz.

Mevcut `AuthSecurityTest` içinden yalnız giriş limiter/cache-clear dayanımı ve kayıt/kurtarma limiter senaryoları çalıştırıldı: **2 test / 10 doğrulama geçti**. Toplam 11 farklı ilgili senaryo / 105 doğrulama; bütün ürün testi veya tam rol matrisi değildir.

9 ilgili PHP dosyasında syntax ve Pint kontrolü, üç JSON dosyasında parse kontrolü başarılı.

## 6. Gerçek tarayıcı kontrolü

Mevcut yerel Sail veritabanının ayrı testing bağlantısı ve izole testing web harness'iyle Google Chrome kullanıldı. Projenin normal localhost portları değiştirilmedi; Docker/Sail veya diğer proje servisleri durdurulmadı.

- nl ve en kayıt sayfası 200; telefon label/placeholder doğru; ek mount isteği 0.
- nl giriş sayfası ilk HTML'de; gerçek QA hesabıyla profile giriş başarılı.
- Profilde mevcut altı menü, aktif link, iki form, noindex ve APP_VERSION1.1.14 CSS URL'si doğrulandı.
- Kısa isim gönderiminde gerçek Livewire validation görünür. Geçerli profil kaydı yapılmadı; başarılı kayıt davranışı transaction testindedir.
- Parola gösterme düğmesi alan tipini değiştirir; tarayıcıda geçerli parola değişikliği yapılmadı.
- 1440px masaüstü ve 390px mobil ekran; mobilde document yatay taşması yok.
- Favori menüsüne gerçek geçişte aktif link korunur; o sayfanın bütün rapor işlemleri yeniden test edilmedi.
- Page error yok. İki ekran görüntüsü ayrıca görsel olarak incelendi.
- Testing DB'de tr dil kaydı bulunmadığından gerçek browser tr isteği mevcut fallback ile nl döndü. Kalıcı QA dil kaydı eklenmedi; **Türkçe kanıtı transaction testiyle sınırlıdır**.

QA hesabının giriş zamanı/session/log değişiklikleri yalnız testing ortamındadır. Ana yerel veya canlı hesaplarla işlem yapılmadı. Testing ayarındaki ana renk kullanıldı; görünüm projenin renk değişkenini takip eder.

Ekran görüntüleri Git dışında:

- `/tmp/eduan-front-performance-1007/account-profile-desktop.png`
- `/tmp/eduan-front-performance-1007/account-profile-mobile.png`

## 7. Değişen dosyalar ve sürüm

- `app/Helpers/TranslationHelper.php`: panel telefon fallback.
- `app/Livewire/Frontend/Auth/{Login,Register}.php`: ilk HTML.
- `app/Livewire/Frontend/Account/{ProfileForm,UpdatePasswordForm}.php`: ilk HTML, oturum kullanıcısını yeniden kullanma, hesap validation etiketleri.
- `resources/views/frontend/account/{tab,index}.blade.php`: ortak hesap menüsü ve profil kart yerleşimi.
- `resources/views/livewire/frontend/account/{profile-form,update-password-form}.blade.php`: form görünümü/yerelleştirme/erişilebilirlik.
- `lang/{nl,en,tr}.json`: mevcut telefon anahtarı.
- `lang/{nl,en,tr}/account.php`: hesap etiketleri ve telefon varsayılanı.
- `public/assets/app.css`: yalnız member-* hesap görünümü kuralları.
- `.env`: **APP_VERSION1.1.13 → 1.1.14**; CSS cache yenileme.
- `tests/Feature/FrontendAccountPresentationTest.php`: ilgili dar senaryolar.
- Bu belge, AGENTS.md, standartlar16, lazy kaynak raporu50 ve index güncellendi.

## 8. Kapsam sınırları

Yeni paket, JavaScript kütüphanesi, model/şema değişikliği, migration, ana DB verisi, deploy veya dış servis işlemi yok. Genel suite/build ve üretim hız ölçümü çalıştırılmadı. Eski hesap save action'larının genel cache davranışı bu tasarım aşamasında yeniden yazılmadı; 53/50 içindeki genel cache adayları ayrı kapsamdır. Hesap özeti ilk HTTP kullanıcısını gösterir; form dışındaki özet için ek reaktif güncelleme altyapısı eklenmedi. Yeni iki menünün adı/işlevi gelecek kullanıcı kararıdır.
