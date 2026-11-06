# Livewire route cache ve giriş 404 düzeltmesi

4 Kasım 2026. Canlı yayın kabulünde kullanıcı, giriş gönderiminin
`/livewire-03b9b506/update` adresinde 404 verdiğini bildirdi. Başarısız kuyruk
kayıtlarının temizlendiği kullanıcı çıktısıyla doğrulandı; kuyruk temizliği bu
hata tanısına dahil değildir.

## Neden ve kapsam

Livewire özel update adresi `routes/web.php` içinde kayıtlıydı. Route cache
açıldığında bu dosya çalışmıyor. Livewire 4.4.8 mekanizmasının o istekte tuttuğu
varsayılan route nesnesi istemciye hash içeren adresi veriyor; özel rota da
mevcut olduğundan varsayılan adrese gönderim güvenlik kontrolünde 404 oluyor.
İzole uygulamada cache kapalıyken özel, açıkken varsayılan adres seçimi üretildi.

Kayıt `AppServiceProvider::boot()` içine taşındı. Her açılışta özel adres
belirleniyor. `routes/web.php` içindeki tekrar kaldırıldı. Adres, HTTP yöntemi,
web/header middleware, form doğrulamaları ve parola kuralları değişmedi.
Vendor, veritabanı, CSS/JS ve APP_VERSION değiştirilmedi.

## Dar doğrulama

- `LivewireUpdateRouteCacheTest`: ayrı geçici Laravel uygulaması ve ayrı süreç;
  gerçek AppServiceProvider + Livewire provider kullanır. Proje .env dosyası,
  DB ve asıl cache dizinleri kullanılmaz.
- Gerçek `route:cache` ardından yeni uygulama açılır. Cache açık/kapalı özel
  istemci adresi, route adresi, POST, web ve Livewire header middleware:
  **1 test / 12 doğrulama başarılı**.
- AppServiceProvider ve yeni test Pint başarılı; routes/web.php PHP syntax
  başarılı. Route dosyasının önceden mevcut diğer biçimleme farkları değiştirilmedi.

## Yayın durumu

Yalnız iki uygulama dosyası içeren `eduan-20261104-login-route-fix.zip`
hazırlandı; ZIP bütünlüğü ve kaynak byte eşitliği kontrol edildi. Eski v2
teslim arşivi değiştirilmedi. BASE-FILES ve FILES hash listeleri ayrı saklandı.
Canlı dosya aktarımı, cache açık gerçek tarayıcı gönderimi ve kullanıcı girişi
henüz doğrulanmadı. Canlıya aktarım öncesinde iki dosyalık kapsam onayı gerekir.

Geçici işlem: canlıda `artisan route:clear`, sayfayı tam yenileyip tekrar deneme.
Kalıcı düzeltme uygulanmadan `route:cache` yeniden çalıştırılmamalı.
