# Hesap istatistikleri, kişisel lig ve kart düzeni — 7 Ekim 2026

## 1. Kullanıcı kapsamı

57'deki sol menü/sağ içerik korunarak hesap ekranlarının iç görünümü yenilendi. Çıkış kırmızı/beyaz, mobil menü seçili sayfanın adı, istatistikler rapor seçicisi ve sonuç kartları, kişisel lig dönem kartları, iki kolonlu favori/çözülen test/sınav kartları ve daha sıkı bilgi/parola formu istendi. Bu belge hesap ekranları ve ortak üst menü çevirilerini kapsar; tüm sitenin tüm metinlerinin eksiksiz çevrildiği veya genel ürün testlerinin geçtiği anlamına gelmez.

## 2. Dil kaynakları ve menüler

- `lang/{nl,en,tr}/account.php`: beş istatistik başlığı, tarih/dönem/ders/konu, doğru/yanlış/boş, soru sayısı/başarı, puan/sıra/lig, kart etiketleri, boş/loading durumları ve hesap başlıkları.
- `AccountController` hesap başlıklarını bu kaynaklardan alır. Konu raporunun eski “Ders” başlığı “Konu” olur; içerik adları mevcut kayıt/locale sözleşmesinde kalır.
- Sidebar ve üst hesap açılır menüsü aynı account.* kaynaklarını kullanır. `dashboard:access`, leagueStatus, route ve active koşulları korunur.
- Üst public menüde eksik “Ligler” çevirisi `leagues.title` ile nl/en/tr kaynaklarından gelir.
- Kaynak PHP etiketleri panel JSON üretiminden bağımsızdır; ana DB çeviri kayıtları güncellenmedi. Kendi dil tarayıcısı51'de bekleyen iştir.
- Mobil menü düğmesi seçili hesabın sayfa adını gösterir. Tamamlanmamış test ekranı test menüsünde, sınav detayı sınav menüsünde kalır. Aç/kapa/aria davranışı korunur.

## 3. İstatistikler

- Beş düğme yerine label ile seçilebilir rapor filtresi; mevcut sezon varsa tarih aralığı gösterilir.
- Günlük, ders ve konu sonuçları kartlarda; başarı yüzdesi ve native HTML progress, soru/doğru/yanlış ve ders/konuda boş yanıt sayıları.
- Başarı hesabında sıfır payda0% olarak gösterilir. Sonuçların eski toplama, tarih/sezon ve “en çok/en az” seçim/sıralama kuralları değişmedi.
- Önceden var olan20 kayıtlık sayfalama korunur; rapor değiştiğinde sayfa1'e döner. Ortak frontend Livewire pagination şablonu kullanılır, içerik alanına kayar.
- Seçilebilir rapor anahtarı ve SQL kolon seçimi whitelist ile doğrulanır; geçersiz değer422. Tabs/sezon/kullanıcı context Locked; sorgular oturumdaki auth ID'yi kullanır.
- StatsTable mevcut lazy davranışını korur. Bu tasarımda ağır raporların bütün lazy'lerini kaldırma veya ortak kişisel cache ekleme kararı verilmedi.
- Günlük soru/yanlış toplamı ile detay toplamlarının eski iş anlamı yeniden hesaplanmadı.50/K13 aggregate olmayan tarih adayı ayrı karar olarak kalır.

## 4. Kişisel lig sonuçları

- Geniş tablo yerine dönem kartı: lig numarası, sıra, başlangıç/bitiş tarihi, puan, doğru/yanlış ve bunların mevcut toplamı.
- Masaüstü iki, tablet/mobil tek kolon; uzun tarih ve etiketler kolon içinde kalır. Negatif puan gizlenmez.
- SQL oturumdaki kullanıcının sonuçlarını getirir; league eager loading.15 kayıtlık pagination, created_at ve id ile kararlı azalan sıra.
- Mount'ta oturum ve kullanıcı ID eşitliği denetlenir; userId Locked. Başka kullanıcı mount ve hydration değişikliği reddedilir.50/K05'in bu ekrandaki bağlam açığı için dar kanıt artık bu belgededir.
- Eksik league ilişkisi varsa dönem bilgisi yok mesajı gösterilir; geçmiş kayıt silinmez/onarılmaz.
- Lig hesaplama işi, puan/deneme kuralları, kayıtlı sıralama ve geçmiş sonuçlar değiştirilmedi. Public lig sıralaması52/55 ayrı ekrandır.

## 5. Kartlar, form ve CSS

- Favori testler, çözülen testler ve çözülen sınavlarda geniş ekranda iki kart; daha dar hesap içeriğinde tek kart. Kartlar aynı satırda eşit yükseklikte, footer/content aralıkları daha kısa.
- Bu üç sınıfın mevcut21 kayıtlık pagination, lazy ve sorguları korunur. Favoriden çıkarma/sonuç detay linkleri aynı işlemleri çağırır.
- Bilgi formunun alt açıklaması kaldırıldı, parola açıklaması kısa tek cümle; label/input/validation/autocomplete ve parola kuralları korunur. Kart padding ve form satırı aralıkları düşürüldü.
- Çıkış zemini `#b83446`, icon/text beyaz; hover/focus koyu kırmızı. Renk ve metin birlikte anlamı korur.
- Yeni kurallar ortak `public/assets/app.css` içinde member-* alanına sınırlı; mevcut font, Bootstrap ve proje renk değişkenleri kullanılır. Testing QA marka rengi siyah olduğundan ekran görüntüleri üretimdeki mor rengin kanıtı değildir.
- CSS için `.env` **APP_VERSION1.1.15 → 1.1.16**. Tarayıcı CSS URL'si ve computed logout renkleri doğrulandı. Yeni JS veya paket yok.

## 6. Dar testler

Yeni `tests/Feature/FrontendAccountReportsTest.php`, yalnız ayrı **testing** DB ve `DatabaseTransactions` kullanır. Fixture'lar geri alınır; testing geçmişi sıfırlanmaz.

Sekiz farklı senaryo ayrı dar koşularda geçti:

1. NL rapor/lig/menü ve0% kaynak çevirisi.
2. EN aynı kaynak ve menü kontrolü.
3. TR aynı kaynak ve menü kontrolü.
4.21 günlük sonuç20+1 sayfalanır, başka kullanıcının999 sorusu dışarıda; rapor değişince sayfa sıfırlanır, geçersiz rapor422.
5. Konu toplam3/doğru1/boş1 ve33,33%; doğru konu etiketi; geçersiz SQL kolonu422.
6.16 kişisel lig sonucu15+1; başka kullanıcının kaydı dışarıda.
7. Başka kullanıcı mount403, Locked hydration reddi.
8. Üç bağlı kart ekranının veriyle render ve iki kolon markup kontrolü.

İlk doğrulamada istatistik Blade'indeki tek satır @php ile sonraki blok etkileşimi derleme hatası verdi; blok @php düzeniyle giderildi. Test hazırlığındaki PHPUnit final `result()` isim çakışması `createAttempt` ile, Livewire lazy placeholder beklentileri her çağrıda withoutLazyLoading ile düzeltildi; ürünün lazy politikası değiştirilmedi. Başarılı senaryolar gereksiz genel koşuda tekrarlanmadı; son kart senaryosu **1 test/5 doğrulama**. Üst menüye eklenen son çeviri kontrollerinde yalnız ilgili üç dil yeniden koşuldu: **3 test/45 doğrulama geçti**. Bu son koşunun ilk host denemesi Docker `mysql` adı çözülemediği için0 doğrulamada durdu; DB_HOST127.0.0.1 ile aynı testing DB'de geçti.

10 ilgili PHP dosyasında syntax ve Pint başarılı; ilgili kaynak diff whitespace kontrolü başarılı. Genel suite/build çalıştırılmadı.

## 7. Gerçek Chrome kanıtı

Mevcut ayrı testing web harness ve sentetik QA hesabıyla:

- 1440px: kırmızı logout/beyaz icon-text, CSS1.1.16, profil/form, beş rapor seçimi ve yeni başlıklar.
- Günlük iki, ders/konu bir kart ve iki boş rapor durumu görüldü; filtre sonrası gerçek Livewire güncellemesi çalıştı.
- 768/390px istatistik ekranında yatay taşma yok;390px mobil menü “Statistieken”, açılıp kapanır.
- Kişisel ligde bir gerçek testing QA kartı, favori/çözülen testlerde birer, tamamlanan sınavlarda iki kart açıldı; mobil seçili adlar ve yatay taşma denetlendi.
- Tamamlanan sınavdaki iki kart gerçek Chrome ölçümünde aynı top172,75px, farklı left405/900px ve eşit465px genişlikte.
- NL/EN üst lig/hesap menüsü çevirileri, EN rapor başlığı ve390px lig kartı kontrolü başarılı; pageerror/HTTP500 yok.
- Browser'daki kişisel lig/daily geçmiş hacmi ikinci sayfa düğmesi göstermeye yetmiyordu.15+1 ve20+1 işlevi yalnız transaction testinde doğrulandı; gerçek ikinci sayfa browser kanıtı olarak sunulmaz. TR testing kalıcı dil kaydı olmadığı için otomatik transaction testiyle sınırlı.

Görseller Git dışında `/tmp/eduan-front-performance-1007/account-reports-{profile,stats,mobile,my-leagues,favorite,solved,results-exams,league-mobile}.png`. Stats/iki sınav kartı/mobile/lig görselleri incelendi. Testing QA giriş zamanı/session/logu güncellenebilir; ana yerel kullanıcı/veri kullanılmadı.

## 8. Kapsam dışı mevcut bulgular ve dosyalar

- `favorite-tests.blade.php` doğru/yanlış sayısını `test->results` üzerinden bütün kullanıcıların sonuçlarından topluyor; koşulda kullanılan userResults farklıdır. Kişisel toplam mı genel popülerlik mi gösterileceği ayrıca netleştirilmeli. Bu eski sorgu otomatik değiştirilmedi.
- `SolvedExams` results_count bütün kullanıcıların tamamlanan denemelerini sayıyor;50'deki mevcut aday kalır. Kart yerleşimini değiştirmek bu toplamın ürün anlamına karar vermez.
- Ana DB/migration/geçmiş yeniden hesaplama, Composer/vendor, public cache, mail/R2 veya deploy işlemi yok. Normal localhost/Sail portları değiştirilmedi; diğer projeler durdurulmadı. İzole QA web sunucusu kontrol bitince kapatılır.

Değişen dosyalar: AccountController; StatsTable/MyLeagueResultTable; account/tab ve index; üç course card Blade; stats-table/my-league-result-table Blade; header-auth/main-menu Blade; üç account.php ve üç leagues.php; app.css; yerel .env; yeni ilgili test. Rehber AGENTS.md,16,50,index ve bu58 ile güncellendi.57 ortak dış yerleşimin,56 form/telefon düzeltmesinin tarihsel kaydı olarak kalır.
