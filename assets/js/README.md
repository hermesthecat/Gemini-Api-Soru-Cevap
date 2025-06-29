# JavaScript Dizini

Bu dizin, AI Bilgi Yarışması uygulamasının tüm frontend (istemci tarafı) mantığını içerir. Uygulama, sorumlulukların net bir şekilde ayrıldığı modüler bir JavaScript yapısı kullanır. Bu, kodun bakımını, okunabilirliğini ve geliştirilmesini kolaylaştırır.

Ana orkestrasyon `app.js` tarafından yönetilir ve her modül belirli bir göreve odaklanır.

## Modüller ve Sorumlulukları

- **`app.js`**: Uygulamanın ana giriş noktası (entry point) ve orkestratörüdür.
  - DOM yüklendiğinde çalışır.
  - Tüm diğer modülleri (`ui-handler`, `api-handler`, `game-handler` vb.) başlatır.
  - Modüller arasında iletişimi sağlayan genel olay dinleyicilerini (`event listener`) kurar (örn. `loginSuccess`, `answerSubmitted`).
  - Uygulamanın genel yaşam döngüsünü (initialization, state changes) yönetir.

- **`api-handler.js`**: Backend API ile olan tüm iletişimi merkezileştirir.
  - Sunucuya `fetch` istekleri gönderen bir `call` metodu sağlar.
  - İsteklere otomatik olarak CSRF token'ı ekler.
  - Yükleme durumlarını (`loading`) yönetir.
  - API'den gelen hataları yakalar ve kullanıcıya `ui-handler` aracılığıyla bildirim gösterir.

- **`ui-handler.js`**: DOM manipülasyonları ve arayüz güncellemelerinden sorumludur.
  - Farklı uygulama görünümlerini (giriş, ana ekran, admin paneli) gösterip gizler.
  - Bildirimleri (`toast`), yükleniyor ekranını ve modal pencereleri (başarım, düello, duyuru) yönetir.
  - Liderlik tablosu, istatistikler, arkadaşlar listesi gibi dinamik içerikleri HTML'ye render eder (döker).
  - Yönetici paneli için `Chart.js` kütüphanesini kullanarak istatistiksel grafikler çizer.
  - Tüm görsel güncellemeler bu modül üzerinden yapılır.

- **`app-state.js`**: Uygulamanın anlık durumunu (state) tutan merkezi bir yapıdır.
  - Giriş yapmış kullanıcı bilgileri (`currentUser` - avatar dahil), zorluk seviyesi, ses ayarları gibi değişkenleri saklar.
  - Diğer modüllerin uygulama durumu hakkında bilgi almasını (`get`) ve durumu değiştirmesini (`set`) sağlar.
  - Paneldeki verileri (`api-handler` ile) günceller.
  - Kullanıcı rolünü değiştirme veya silme gibi admin eylemlerini dinler ve ilgili API isteklerini tetikler.
  - Gelişmiş istatistik verilerini API'den çeker ve grafiklerin çizilmesi için `ui-handler`'a iletir.

- **`app-data.js`**: Uygulamanın statik verilerini barındırır.
  - Kategoriler, ikonları ve renkleri gibi uygulama boyunca değişmeyen verileri içerir.

- **`auth-handler.js`**: Kimlik doğrulama (authentication) işlemlerini yönetir.
  - Giriş ve kayıt formlarının gönderilmesini dinler.
  - `api-handler`'ı kullanarak `login` ve `register` isteklerini yapar.
  - Başarılı giriş veya çıkış durumunda `app.js`'e olaylar (`event`) fırlatır.

- **`game-handler.js`**: Tek kişilik ana oyun akışını yönetir.
  - Kategori ve zorluk seçimine göre yeni soru ister.
  - Soruyu ve seçenekleri ekranda gösterir.
  - Geri sayım sayacını (timer) başlatır ve yönetir.
  - Kullanıcının cevabını alır, `api-handler` ile sunucuya gönderir ve sonucu ekranda gösterir.
  - Jokerlerin kullanım mantığını içerir.

- **`stats-handler.js`**: Profil sayfasındaki verilerin güncellenmesinden sorumludur.
  - Kullanıcı istatistiklerini, başarımlarını ve liderlik tablosunu periyodik olarak veya gerektiğinde `api-handler` aracılığıyla günceller.
  - Kullanıcının profil sayfasından yeni bir avatar seçme ve güncelleme işlemini yönetir.

- **`admin-handler.js`**: Yönetici paneliyle ilgili istemci tarafı mantığını içerir.
  - Paneldeki verileri (`api-handler` ile) günceller.
  - Kullanıcı rolünü değiştirme veya silme gibi admin eylemlerini dinler ve ilgili API isteklerini tetikler.
  - Gelişmiş istatistik verilerini API'den çeker ve grafiklerin çizilmesi için `ui-handler`'a iletir.

- **`friends-handler.js`**: Arkadaşlık sistemi arayüzü ve etkileşimlerini yönetir.
  - Kullanıcı arama, istek gönderme, istekleri yanıtlama ve arkadaş silme işlemlerini yönetir.
  - Düello başlatma modal'ının gösterilmesini ve meydan okuma isteğinin gönderilmesini tetikler.
  - Düello oyun modunun istemci tarafı mantığını yönetir.
  - Bir düello oyununu başlatır ve düello verilerini ayarlar.
  - Düello sorularını ve ilerlemeyi ekranda gösterir.
  - Cevapları işler, sonucu gösterir ve oyunun sonunda özet ekranını oluşturur.

- **`duel-handler.js`**: Düello oyun modunun istemci tarafı mantığını yönetir.
  - Bir düello oyununu başlatır ve düello verilerini ayarlar.
  - Düello sorularını ve ilerlemeyi ekranda gösterir.
  - Cevapları işler, sonucu gösterir ve oyunun sonunda özet ekranını oluşturur.

- **`announcement-handler.js`**: Duyuru sisteminin istemci tarafı mantığını yönetir.
  - Kullanıcı için duyuruları gösterir ve okundu olarak işaretler.
  - Yönetici için duyuru oluşturma/silme arayüzü etkileşimlerini yönetir.

- **`quest-handler.js`**: Günlük görevler arayüzünü yönetir.
  - Günlük görevleri `api-handler` ile çeker ve `ui-handler` ile ekrana basar.
  - Bir görev tamamlandığında bildirim gösterir.

- **`settings-handler.js`**: Ayarlar (tema ve ses) mantığını yönetir.
  - Tema (açık/koyu) ve ses açma/kapama ayarlarını `localStorage`'e kaydeder ve uygular.
