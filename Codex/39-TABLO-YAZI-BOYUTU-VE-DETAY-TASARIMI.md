# Tablo yazı boyutu ve detay tasarımı

6 Ekim 2026. Kullanıcı üç pilot tablonun küçük yazılarının büyütülmesini, Görüntüle panellerinin daha kullanışlı tasarlanmasını ve ilişkili alt veriler için kapsamın belirlenmesini istedi. Bu kayıt ilk görsel düzenleme ve bekleyen içerik kararlarını ayırır.

## 1. Uygulanan yazı standardı

Mevcut `public/backend/assets/app.css` Bootstrap Table hücre/başlıklarını13px yapıyordu. Bu dosya genel olarak değiştirilmedi; yalnız pilot `server-table.css` içinde daha güçlü, tabloya kapsamlı seçiciler eklendi.

| Alan | Yeni standart |
|---|---|
| Üç tabloda hücre ve kolon başlığı |15px; önce13px|
| Satır işlem düğmeleri |16px; önce btn-sm14px|
| Tablo durum ve rol badge'leri |14px|
| Detay bilgi satırları |16px|
| Detay özet sayıları |24px|

Filtre bandı ve seçim araçları değişmedi. Hücre padding.85rem/line-height1.45 korunur. Bu seçiciler üç pilotun ortak admin-server-table bileşenine bağlıdır; diğer28 eski tablo veya ön yüz için uygulanmış standart sayılmaz.

## 2. Üç detay panelinin yeni görünümü

- Ders: kitap ikonu, ders adı/kodu, konu/soru sayısı özet kartları, temel bilgilerin çerçeveli bilgi bölümü.
- Konu: konu ikonu, konu başlığı/ilişkili ders, toplam soru sayısı kartı, temel bilgi bölümü.
- Kullanıcı: kullanıcı ikonu, ad-soyad/kullanıcı adı, mevcut öğrenci/öğretmen sayısı kartları, temel bilgi bölümü.
- Görüntüle halen salt okunurdur; Düzenle ayrı mevcut sayfaya gider.
- Başlık/özet/metin Blade escaping ile render edilir. Yeni veri kaynağı veya ek erişim kapsamı açılmadı.
- 760px panel, mobilde ekran genişliği; bilgi satırları mobilde etiket ve değeri alt alta gösterir.
- Üç arayüz dilinde yeni `tables.read_only` etiketi eklendi.

Bu aşamada yalnız mevcut detay verisinin sunumu geliştirildi. İlişkili kayıt listeleri aşağıdaki kararlar yanıtlanmadan eklenmedi.

## 3. Yanıt bekleyen alt veri kararları

Kullanıcının belirsiz konuda işlem yapmadan soru sorma talimatı gereği üç soru gönderildi:

1. Ders Bilgisi / Konular sekmeleri için konuları sayfalı Bootstrap Table ile kod, başlık, aktif/pasif durum ve soru sayısı olarak mı gösterelim; yoksa basit liste mi olsun?
2. Konu panelinde aktif/pasif soru sayıları yanında sayfalı soru listesi de mi olsun; yoksa yalnız iki sayı mı gösterilsin? Soru listesi seçilirse mevcut soru view izni kontrol edilir.
3. Kullanıcıda mevcut temel bilgiler/sayılar tasarımı şimdilik yeterli mi; yoksa öğrenciler/öğretmenler sekmesi de bu aşamada mı eklensin?

Henüz yanıt yoktur. Ders konuları, konu soru tablosu, aktif/pasif ayrımı ve yeni kullanıcı sekmeleri uygulanmış sayılmaz. Sonraki cevap bu belgeyi ve ilgili test kapsamını günceller.

## 4. Dar test ve browser kanıtı

Yalnız ilgili üç test çalıştırıldı: üç sayfa render/yerel asset, yeni üç dil etiketleri, detayın öğretmen kapsamını yeniden denetlemesi. **3 test/124 doğrulama geçti**; gerçek container PHP8.5.8, PHPUnit13.4.1, yalnız testing DB ve DatabaseTransactions. Log `/tmp/eduan-font-details-php.log`.

Gerçek Chrome + mevcut Playwright, geçici localhost8765 testing web ortamında masaüstü1440×1000 ve mobil390×844 kontrol edildi. Browser plugin not available; yeni browser bağımlılığı yüklenmedi. Üç sayfada hesaplanan cell/header15px, işlem16px, badge14px doğrulandı. Üç detay aç/kapat, masaüstü/mobil screenshot ve HTML genişliği390px kontrol edildi. İlgili pageerror listesi boştur. İlk QA scriptindeki submit seçicisi abonelik düğmesiyle çakıştı; yalnız account-btn seçicisine daraltıldı, ürün hatası değildi.

Tarayıcı aynı38 pilotundaki sentetik QA hesabını ve /tmp UI-only Gate izin desteğini kullanır; DB'ye izin/rol ataması yapılmaz. Yetki doğrulaması normal uygulamanın dar PHPUnit testine dayanır. Normal login sentetik kullanıcının last_login_at alanını güncelleyebilir. Ana hypotenuse DB, migration, seed, gerçek mail ve silme kullanılmadı. Screenshot `/tmp/eduan-font-1006/{lessons,topics,users}-detail.png`, `*-detail-mobile.png` ve `*-mobile.png` içindedir. Geçici web sunucusu kontrol sonunda kapatıldı.

Genel test/build çalıştırılmadı. Kalan28 tablo veya tüm proje kabulü bu üç testin sonucu değildir. İlgili diff whitespace kontrolü temizdir.

## 5. Değişen dosyalar

- `public/backend/assets/server-table.css`
- `resources/views/components/admin/table-detail.blade.php`
- `resources/views/livewire/backend/lessons/server-table.blade.php`
- `resources/views/livewire/backend/topics/server-table.blade.php`
- `resources/views/livewire/backend/users/server-table.blade.php`
- `lang/nl/tables.php`, `lang/en/tables.php`, `lang/tr/tables.php`
- `tests/Feature/BootstrapTablePilotTest.php` — yeni readonly etiketini mevcut dil testine ekler.
- AGENTS.md, Codex16/38/bu39 ve index.html.

Controller/Livewire PHP, SQL, paket veya API davranışı bu görsel düzenlemede değiştirilmedi.
