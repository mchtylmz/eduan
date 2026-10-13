# Ön yüz lig geçmişi araması ve görünüm

7 Ekim 2026. Kullanıcı Geçmiş Lig Sonuçları seçicisine arama ve lig ekranına daha şık görünüm istedi. Yalnız bu ön yüz alanı düzenlendi; hesap/puan/lig üretme mantığı değiştirilmedi.

## 1. Arama davranışı

- Mevcut dönem seçicisi ve frontend.leagues.detail bağlantıları korunur.
- Seçicide label/placeholder'lı type=search alanı vardır. Alpine, Livewire'ın mevcut dağıtımından gelir; yeni kütüphane yoktur.
- Arama görünen başlangıç/bitiş tarihini, yerel ay adını, yıl ve d.m.Y / Y-m-d karşılıklarını kapsar. Küçük/büyük harf, aksan ve kenar boşlukları normalize edilir.
- Filtre yalnız zaten yüklenen dönem seçeneklerini tarayıcıda süzer; her tuşta Livewire veya SQL isteği üretmez.
- Eşleşme yoksa mevcut çevrilmiş boş sonuç metni gösterilir. Arama temizlenince bütün seçenekler geri gelir.
- Seçili dönem aria-current=page ve onay işaretiyle belirginleşir; değişim mevcut linkle yapılır. Klavye Escape dropdown'ı kapatır.
- Liste masaüstünde400px, mobilde en fazla50vh yüksekliğinde kaydırılır; arama alanı kaydırılabilir listenin üstünde kalır.
- Liglerin mevcut leagues_all24saat cache'i, liste sorgusu ve lazy attribute'ları değiştirilmedi. Çok büyük dönemde server araması/pagination ayrı karar olabilir; bu tur kapsamı değildir.

## 2. Görünüm

Dönem üst bandı ve tarih başlığı yeniden düzenlendi. Mevcut üç lig aynı genişlikte masaüstü kartları, mobilde tek kolon olur; bağımsız kart yükseklikleri korunur. Başlık/ikon, satır aralığı ve tabular sayı düzeni yenilendi. İlk üç sırada madalya renkli sıra ve kupa; puanlar kalın/sağa hizalıdır. Kendi kullanıcı satırı mevcut my-league-position anlamını korur. Boş ligde colspan3 sonuç mesajı bulunur.

Mevcut font/Bootstrap/proje rengi kullanılır. Stil yalnız league-* sınıflarına bağlıdır; başka ön yüz tabloları veya panel stili değişmez. Lig başlığı içindeki podium görseli dekoratif alt="" olur; metin başlığı anlamı taşır. Yeni görsel/font/JS paketi yoktur.

## 3. Korunan iş kuralları

- İlk/ikinci/üçüncü lig, position/rank_position ve ayarlı leagueUserCount sorgusu aynıdır.
- Puan/doğru/yanlış/süre hesabı, tekrar deneme toplama ve tamamlama kuralları değiştirilmedi.
- LeagueResultTable12saat cache ve lazy yükleme korunur.
- Controller ilk lig seçimi veya geçmiş dönem sırası yeniden yorumlanmadı.
- Ad/soyad Blade escape edilir; ham kullanıcı HTML'i yazılmaz.
- Şemada start_at/end_at nullable olduğu için sayısal arama anahtarı null-safe üretilir. Eski tarih gösteriminin anlamı ayrıca değiştirilmedi.

## 4. Asset ve dil

Yeni search label/placeholder/sıra etiketi lang/tr/leagues.php, lang/nl/leagues.php, lang/en/leagues.php içindedir. Mevcut JSON çevirileri veya canlı çeviri kayıtları değiştirilmedi; eski eksik çeviriler otomatik onarılmadı.

public/assets/app.css değiştiği için .env APP_VERSION **1.1.11 → 1.1.12**. Config app.version ve mevcut version query düzeni kullanılır; env/config cache temizleme, build veya dağıtım yapılmadı. Canlı deployment asset sürümü ayrıca uygulanır.

## 5. Dar test kanıtı

FrontendLeaguePresentationTest yalnız testing DB + DatabaseTransactions:

- İlk iki senaryo **2 test/27 doğrulama**: nl/en/tr arama metni, dönem bağlantısı/seçili durum, üç kart render; mevcut skor/sıra/kendi satırı ve kullanıcı metni escape; boş sıralama ve boş dönem.
- Nullable tarih için yalnız yeni ilgili test **1 test/2 doğrulama**: eski null tarihli dönem yeni arama anahtarında hata oluşturmaz.
- Toplam **3 farklı senaryo/29 doğrulama** ayrı dar koşularda başarılı. İlk sandbox MySQL bağlantısını engelledi; izinli yerel bağlantıyla koşuldu. Bu uygulama SQL hatası değildir.
- Yeni dört PHP dosyasında Pint geçti. Tüm suite/build/migration çalıştırılmadı.

## 6. Tarayıcı kanıtı ve sınır

Mevcut Chrome/Playwright ile sentetik testing transaction içinde render edilmiş HTML kullanıldı.14 dönem, üç kart/15 sonuç satırı; işlem sonunda transaction geri alındı. Bu HTML yalnız geçici8765 test sunucusunda gösterildi; ana localhost veya Lara Football portu değiştirilmedi.

- Masaüstü1440px ve mobil390px görünüm kontrolü başarılı.
- ISO tarih tek eşleşme/seçili onay, ay araması büyük harf+trim, eşleşmeyen değer, temizleme/reset, Escape,400px liste scroll başarılı.
- Dropdown arama açıkken kapanmaz; mobil document taşması yok; pageerror yok.
- Son masaüstü/mobil ekran görüntüleri incelendi. /tmp/eduan-league-desktop.png ve /tmp/eduan-league-search-mobile.png geçici QA kanıtıdır.
- Dış ağ istekleri engellendi. Gerçek döneme tıklayıp DB sonucunu başka route'tan alma bu sentetik HTML kontrolünde yürütülmedi; gerçek href ve server render dar PHP testinde doğrulandı.
- Sentetik ortamın logo/renk/çeviri ayarları canlı siteyi temsil etmez; gerçek üretim/cihaz veya tüm lazy akışı kabulü değildir.
- Geçici sunucu durduruldu. Ana DB/queue/mail/AI/R2/dış servis işlemi yoktur.

## 7. Değişen dosyalar

- resources/views/livewire/frontend/leagues/league-table.blade.php
- resources/views/livewire/frontend/leagues/league-result-table.blade.php
- public/assets/app.css, .env (yalnız APP_VERSION)
- lang/tr/leagues.php, lang/nl/leagues.php, lang/en/leagues.php
- tests/Feature/FrontendLeaguePresentationTest.php
- AGENTS.md, Codex/16-PROJE-STANDARTLARI.md, bu52 raporu, Codex/index.html

## 8. Devam sınırı

Bu geliştirme50 raporundaki lazy, global cache, MyLeagueResultTable veya diğer güvenlik/SQL adaylarını düzeltme izni değildir. Eski olmayan ek hatalar otomatik düzeltme yapılmadan ayrı bildirilir.31'deki DB indeksleri veya51'deki bekleyen paket karşılıkları bu görünüm işiyle tamamlanmış sayılmaz.


## Sonraki güncelleme —55

7 Ekim: [55](55-ON-YUZ-LIG-ILK-YUKLEME-VE-SAYFALAMA-HOVER.md) bu belgedeki önceki lazy korunur kararını lig ekranı için değiştirdi; iki sınıf ilk HTML’e taşındı, cache/puan sorguları korundu. Gerçek testing route/dönem navigation/mobil kanıtı ve APP_VERSION1.1.13 artık55 içindedir.52’nin sentetik test kapsamı tarihsel olarak kalır.
