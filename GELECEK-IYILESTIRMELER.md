# Gelecek İyileştirmeler ve Yol Haritası

Bu belge, AI Bilgi Yarışması projesinin gelecekteki gelişim yönünü ve potansiyel özelliklerini özetlemektedir.

---

### 1. Kod Yapısı ve Sürdürülebilirlik (✓ Tamamlandı)

- **Backend'i Yeniden Yapılandırma:**
  - [x] `api.php` dosyasını, gelen isteklere göre ilgili Controller sınıflarını çağıran bir yönlendiriciye (router) dönüştür.
  - [x] `UserController` oluşturuldu: Kullanıcı `register`, `login`, `logout` ve `check_session` işlemlerini yönetir.
  - [x] `GameController` oluşturuldu: Oyun mantığını (`get_question`, `submit_answer`) yönetir.
  - [x] `AdminController` oluşturuldu: Admin paneli işlemlerini (`get_dashboard_data`, `get_all_users` vb.) yönetir.
  - [x] `DataController` oluşturuldu: Genel veri çekme işlemlerini (`get_user_data`, `get_leaderboard` vb.) yönetir.

- **Frontend'i Modüler Hale Getirme:**
  - [x] API çağrılarını `api-handler.js` içine taşı.
  - [x] UI (arayüz) güncellemelerini (`showView`, `showToast` vb.) `ui-handler.js` içine taşı.
  - [x] Kimlik doğrulama (Auth) işlemlerini (`login`, `register`, `logout`) `auth-handler.js` içine taşı.
  - [x] Oyun mantığını (soru gösterme, cevaplama, zamanlayıcı) `game-handler.js` içine taşı.
  - [x] **(Yeni)** `app.js`'i daha da sadeleştir:
    - [x] Statik verileri (kategoriler, başarım bilgileri) `app-data.js`'e taşı.
    - [x] Dinamik uygulama durumunu (state) `app-state.js`'e taşı.
    - [x] İstatistik, liderlik tablosu ve başarım güncelleme mantığını `stats-handler.js`'e taşı.
    - [x] Admin paneli mantığını `admin-handler.js`'e taşı.
    - [x] Ayarlar (tema, ses) mantığını `settings-handler.js`'e taşı.
    - [x] `app.js`'i sadece modülleri başlatan ve aralarındaki iletişimi yöneten bir orkestratöre dönüştür.

- **Veritabanı Şemasını İyileştirme:**
  - [x] `user_stats` tablosuna zorluk seviyesi ve harcanan zaman gibi daha detaylı istatistikler ekle.
  - [x] Başarımlar için ayrı bir `achievements` tablosu oluşturarak başarım tanımlarını (isim, açıklama, ikon) veritabanında sakla.

### 2. Hata Yönetimi ve Kullanıcı Geribildirimi

- [x] **Frontend Hata Yönetimi:** `apiCall` fonksiyonunda `try-catch` bloklarını kullanarak API'den dönen hataları (örneğin, sunucu hatası, geçersiz istek) yakala ve `showToast` ile kullanıcıya anlamlı mesajlar göster.
- [x] **Backend Hata Yönetimi:** PHP tarafında `try-catch` bloklarını daha etkin kullan. Veritabanı veya API hatalarında uygun HTTP durum kodları (örneğin, 400, 401, 500) ve açıklayıcı JSON mesajları döndür.
- [x] **Yükleme Durumları:** Soru yüklenirken, cevap gönderilirken veya veri çekilirken tam ekran bir "yükleniyor" animasyonu göster.

### 3. Güvenlik İyileştirmeleri

- [x] **SQL Injection'ı Önleme:** Tüm veritabanı sorgularında `prepared statements` kullanıldığından emin ol.
- [x] **XSS (Cross-Site Scripting) Önleme:** Kullanıcıdan gelen ve ekrana basılan tüm verileri (örneğin, kullanıcı adı) `htmlspecialchars` gibi fonksiyonlarla temizle.
- [x] **CSRF (Cross-Site Request Forgery) Koruması:** Form gönderimlerinde ve önemli API isteklerinde CSRF token'ları kullan.
- [x] **Rate Limiting:** Özellikle giriş (login) ve kayıt (register) gibi işlemlere, kısa sürede çok sayıda denemeyi önlemek için hız sınırlaması (rate limiting) ekle.

### 4. Kullanıcı Deneyimi (UX) ve Arayüz (UI) Geliştirmeleri

- [x] **Cevap Sonrası Geri Bildirim:** Cevap doğru veya yanlış olduğunda şıkların renklerini (doğruyu yeşil, yanlışı kırmızı) anında değiştir.
- [x] **Jokerler:** "Yarı yarıya", "Süreyi uzat" veya "Pas geç" gibi jokerler ekle.
- [x] **Kategori ve Zorluk Seçimi:** Kategori seçme ekranını daha görsel ve çekici hale getir.
- [x] **Başarımlar:** Kazanılan başarımlar için daha dikkat çekici bir bildirim (modal veya özel bir animasyon) göster. Başarımlar sayfasını daha detaylı hale getir.
- [x] **Profil Sayfası:** Kullanıcıların kendi istatistiklerini ve başarımlarını daha detaylı görebileceği bir profil sayfası oluştur.

### 5. Sosyal Özellikler ve Rekabet

- **Arkadaşlık Sistemi:**
  - [x] `friends` veritabanı tablosu oluşturuldu.
  - [x] `FriendsController` ile arkadaşlık işlemleri (arama, istek, yanıtlama, silme) için backend mantığı eklendi.
  - [x] Arayüze "Arkadaşlar" sekmesi eklendi.
  - [x] Kullanıcı arama, arkadaş ekleme, istekleri yanıtlama ve arkadaş listeleme arayüzleri tamamlandı.
- **Düello (Meydan Okuma) Modu:**
  - [x] `duels` veritabanı tablosu oluşturuldu.
  - [x] Arkadaş listesinden meydan okuma göndermek için arayüz (modal) eklendi.
  - [x] `DuelController` oluşturuldu ve `createDuel` metodu ile meydan okuma oluşturma backend mantığı eklendi.
  - [ ] Gelen meydan okumaları listeleme ve yanıtlama (kabul/red).
  - [ ] Düello oyun ekranını oluşturma (5 soruluk özel yarışma).
  - [ ] Düello sonuçlarını kaydetme ve gösterme.

### 6. Gelecek Fikirleri

- [ ] **Günlük Görevler:** "Bugün 5 tarih sorusu çöz" gibi günlük görevler ve ödüller.
- [ ] **Farklı Soru Tipleri:** Resimli sorular, sıralama soruları gibi yeni soru formatları ekle.
- [ ] **Avatar ve Özelleştirme:** Kullanıcıların profil fotoğrafı veya avatar seçebilmesi.
