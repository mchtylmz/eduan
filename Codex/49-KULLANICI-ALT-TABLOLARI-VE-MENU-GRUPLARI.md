# Kullanıcı alt tabloları ve yetkili menü grupları

6 Ekim 2026. Kullanıcı düzenleme altındaki bütün tabloların Bootstrap Table standardına alınması ve sol menünün boş yetki grupları oluşturmadan görsel olarak gruplanması istendi. Genel lazy/kod/paket incelemesi ayrı 50–51 raporlarındadır; bu raporlardaki öneriler uygulama onayı değildir.

## 1. Ekran ve veri eşleştirmesi

| Sekme / kullanıcı tipi | Livewire sınıfı | JSON endpoint / tablo | Durum |
|---|---|---|---|
| Favori Testler | Users/FavoriteTable | users.getFavoritesData / user-favorites | Bu aşamada taşındı; gerçek Exam favorileri |
| Öğretmen/okul ataması, öğrenci | Users/TeachersTable | users.getTeachersData / user-teachers | Bu aşamada taşındı |
| Atanan öğrenciler, öğretmen | Users/StudentsTable | users.getStudentsData / user-students | Bu aşamada taşındı |
| Hiç Çözülmeyen Konular | Users/TopicsNotResultsTable | users.getUnsolvedTopicsData / user-unsolved-topics | Bu aşamada taşındı |
| Çözülen Testler | Exams/ResultsTable | exams.getResultsData / exam-results | Önceki45; kullanıcı parent filtresi korunur |
| Çözülen Sınavlar | Tests/ResultsTable | tests.getResultsData / test-results | Önceki47; kullanıcı parent filtresi korunur |
| Sonuç İstatistiği | Users/ResultStatsCustomTable | users.getStatsData / result-stats | Önceki47; Excel ve mail hazırlama korunur |

Kullanıcı bilgisi/parola/atama ekleme/mail formları tablo değildir; kayıt kuralları yeniden yazılmadı. Kullanıcı düzenleme ekranının mevcut sekme yapısı korunur. Kullanıcı altındaki dört eski sınıf artık Livewire Component'tir; Rappasoft DataTableComponent kullanmaz. Uygulamadaki son eski tablo AI/LaTeX görselleridir.

## 2. Liste standardı

- Ortak x-admin.server-table: sunucu JSON total/rows, limit/offset/search/sort/order, yenile solda/arama sağda, kolon sıralama, boş/yükleniyor/hata durumu ve responsive yatay tablo kaydırma.
- Ortak filtre bandı ve durum seçimi; filtre/input kolon genişliği ve label standardı48'den alınır.
- Favori listesi5 kayıt, diğer üç kullanıcı alt listesi15 kayıtla başlar. Sayfa başına adet seçicisi yok; kayıt aralığı ve sayfa geçişi korunur.
- Başlık/hücre15px, işlem16px, koyu tema ve mobil davranış mevcut ortak CSS'dendir. Yeni CSS/JS yok, APP_VERSION1.1.11 korunur.
- Kullanıcı parent filtresi hidden user_id; kullanıcı alt listeleri data-table-url-state=false ile ana sayfa URL'sine nested arama/sayfa alanları taşımaz.
- Atama tabloları yalnız Kaldır eylemini gösterir. Bu kullanıcı kaydını silmez; teacher_student eşleşmesini ayırır. Görüntüle/düzenle eylemi bu alt tabloda eklenmedi. Mevcut JS formatter hide_detail=true kullanır, düğme etiketi yerel table-messages deleteLabel=Kaldır'dır.
- JSON yalnız gerekli kolonları döndürür; parola/token/full User snapshot taşımaz. Favori dil adı eager loading ile alınır. Kod/ad/görünürlük/durum ve atanmış kişinin kullanıcı adı/isim/soyisim/durumu korunur.

## 3. Yetki ve ilişki kapsamı

Her JSON ve Livewire render/action erişiminde dashboard:access + users:view aranır. AuthorizeStudentAccess öğretmeni kendi öğrencileriyle sınırlar; AuthorizeTeacherAccess öğretmen bağlamının gerçekten öğretmen olduğunu ve öğretmenin yalnız kendi atama listesiyle işlem yaptığını doğrular. Bu kontrol cache okumadan önce çalışır.

Context userId/teacherId/studentId veya User parametresi Livewire Locked durumudur. Kaldır işlemi users:student-delete veya users:teacher-delete ister; ilişki yeniden sorgulanır. Öğretmen kendi öğrencisinden başka öğretmenin eşleşmesini kaldıramaz. Yöneticiye mevcut işlem yetkileri korunur. Artık olmayan/bağlı olmayan ilişki404 döner; başka öğretmen bağlamı403 döner.

Kayıtlı öğrenci/öğretmen ekleme formlarının izin ve findable kuralları korunur. Yeni pivot cache invalidation dışında iş davranışı değişmez. Tablo yenileme dispatch'i yalnız kendi tablosunu hedefler.

## 4. Konu sorgusu ve performans

Önceki mount dört ayrı pluck/array sorgusuyla test/sınav sonuç ID'lerini ve konu ID'lerini PHP belleğine alıyordu. Yeni UserRelatedTable::unsolvedTopics doğrudan iki SQL alt sorgusuyla dışlar; kullanıcıyla ilgili bütün sonuç/soru ID'leri Livewire durumuna taşınmaz. NOT IN alt sorgusunda null topic_id dışlanır; geçerli konular listeden yanlışlıkla kaybolmaz.

Mevcut anlam korunur: yarım/tamam denemede kaydedilmiş herhangi bir cevap konuyu çözülmüş kabul eder; bitmiş deneme filtresi eklenmedi. Başka öğrencinin cevabı seçili öğrencinin konusunu dışlamaz. Denemeleri/soruları PHP tarafında toplama yoktur; count ve paged rows veritabanındadır. Yeni indeks/migration yoktur. Üretim süre kazancı ölçülmedi.

Favoriler ve hiç çözülmeyen konuların eski lazy mount isteği kaldırıldı. Hafif tablo HTML'i ilk yanıttadır; satırlar Bootstrap Table'ın JSON isteğiyle gelir. JSON'un asenkron gelmesi ile Livewire lazy mount farklı şeylerdir. Bütün proje lazy'leri kaldırılmadı.

## 5. Cache ve yenileme

JSON TableCache ile60 saniye; actor/izin/dil/parent/filtre/arama/sort/sayfa/version anahtarı korunur. Yenile refresh=1 kullanır. Durum filtresi sabit enum seçenekleridir; gereksiz DB/seçenek cache'i eklenmez. İlgili önceki sonuç/istatistik filtre seçenekleri1 saat standardını sürdürür.

StudentAddForm/TeacherAddForm attach ve iki tablo detach işlemleri user-students/user-teachers/options revision'ını yeniler. UpdateExamFavoriteForUserAction user-favorites revision'ını da yeniler. User/Exam/Topic/sonuç/detay/dil değişiklikleri TableCacheObserver üzerinden ilgili yeni tabloları geçersizleştirir. Mevcut global resetCache bu aşamada kaldırılmadı; ayrı50 bulgusudur. Ana DB pivotlarına elle işlem yapılmadı.

## 6. Menü grupları

| Grup | Görünür olma koşulu | Çocuklar |
|---|---|---|
| Eğitim İçerikleri | lessons:view/topics:view/ai:view/questions:view izinlerinden en az biri | Dersler, Konular, AI, Soru Havuzu |
| Testler | exams:view | Testler, Test Sonuçları |
| Sınavlar | tests:view | Sınavlar, Sınav Sonuçları |
| Sonuçlar ve İstatistikler | stats:view/leagues:view izinlerinden en az biri | İstatistikler, Ligler/Sonuçlar |
| Yönetim | users:view/roles:view/settings:view/seasons:view izinlerinden en az biri | Kullanıcılar, mevcut Ayarlar/Yetkiler alt menüsü |

OneUI nav-main-heading kullanıldı; yeni tema/CSS yoktur. Her çocuk kendi mevcut can kontrolüyle kalır. Bilgi Girişleri alt menüsü mevcut canany kapsamıyla korunur. Başlık koşulları çocuk izinlerinin tam birleşimidir; yetkisiz boş başlık yoktur. AI-only kullanıcının öğesi artık eğitim grubunun kapsamındadır; önceki dış canany AI iznini içermiyordu. Ayarlar parent izinlerindeki tekrarlar kaldırıldı; Dönemler route'unda parent açık/aktif olur. Yeni izin kaydı/rol ataması/DB seed yapılmadı.

## 7. Dar test ve tarayıcı kanıtı

- Ayrı testing DB + DatabaseTransactions: UserRelatedTableTest **11 test/62 doğrulama**; favori sayfalama/sıralama/arama/refresh, iki sonuç ailesinde konu dışlama/observer invalidation, öğretmen kapsamı/yanlış parent/yetkisiz kaldırma, geçersiz sort/limit/durum, yetki kaldırılmış cached istek ve sekme renderları.
- Menü matrisi: hiç izin yok/yalnız AI/yalnız test/yalnız sınav/yalnız istatistik/yalnız dönem; başlık sayısı ve ilgili link görünümü doğrulandı.
- Mevcut StudentAssignmentTest'ten yalnız öğrenci ekle-kaldır ve yönetici öğretmen atama: **2 test/5 doğrulama**.
- Son hide_detail formatter sözleşmesi düzeltmesinde yalnız ilgili atama testi: **1 test/13 doğrulama**. Toplam13 farklı dar senaryo; son tek test önceki koşudaki senaryonun tekrarıdır,14 farklı test diye sunulmaz.
- İlgili14 PHP dosyasında Pint uygulandı. Genel kod raporuna ait salt sözdizimi kontrolü290 PHP/296 Blade şablonunda hata bulmadı; genel suite/build çalıştırılmadı.
- Browser plugin olmadığı için mevcut Chrome/Playwright + izole testing harness127.0.0.1:8765. Altı kullanıcı sekmesi200; gerçek JSON total/rows, Yenile refresh=1 ve390px taşma kontrolü geçti. Favoride1, test sonucunda1, sınav sonucunda5, raporda1 satır; öğretmen ve hiç çözülmeyen konu listesi bu QA kullanıcısında boştu. Dolu öğrenci atama ve konu dışlama PHP fixture testleriyle doğrulandı; boş browser durumunu dolu veri kanıtı diye sunma.
- Hiç çözülmeyen konu araması gerçek klavye Enter ile200; boş sonuç da işlendi. Tarayıcı scripti ilk denemede dolu konu bekliyordu, sonra Enter olayını ve istatistikte zorunlu result_percent=100 alanını tamamladı; bunlar ürün hatası değil test harness beklentileridir.
- İlk tarayıcı kontrolünde pageerror yok; screenshot dosyaları /tmp altında. Kaldır/atama, mail veya dış servis işlemi browser'da çalıştırılmadı; bunlar ilgili PHP testinde yalnız testing transaction kapsamındadır.
- Son UI kontrolü: mevcut assignment formatter yalnız Kaldır üretir, detay düğmesi yok; koyu filtre zemini rgb(23,30,40),390px kapalı sidebar durumunda document taşmaz. Son mobile ve koyu screenshot görsel olarak incelendi.
- Ana DB/migration/deploy/paket değişimi yoktur. Testing giriş mevcut QA login/logunu güncelleyebilir. Geçici sunucu kontrol sonunda durdurulur. Genel ürün/üretim veya bütün rol matrisi kabulü değildir.

## 8. Değişen kaynaklar

- Yeni app/Helpers/UserRelatedTable.php; UsersController dört JSON endpoint.
- Users/FavoriteTable, StudentsTable, TeachersTable, TopicsNotResultsTable Livewire shell'leri.
- Yeni üç users/*-server-table Blade; mevcut table-messages'a isteğe bağlı Kaldır etiketi.
- StudentAddForm/TeacherAddForm, UpdateExamFavoriteForUserAction, TableCacheObserver hedefli invalidation.
- backend/layouts/sidebar-menu; nl/en/tr menus.php.
- Yeni tests/Feature/UserRelatedTableTest.php.
- AGENTS.md,16 standartlar,19 geçiş haritası,49–51 raporları ve Codex/index.html.
