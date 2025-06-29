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
  - [ ] `user_stats` tablosuna zorluk seviyesi ve harcanan zaman gibi daha detaylı istatistikler ekle.
  - [ ] Başarımlar için ayrı bir `achievements` tablosu oluşturarak başarım tanımlarını (isim, açıklama, ikon) veritabanında sakla.

### 2. Hata Yönetimi ve Kullanıcı Geribildirimi

- [ ] **Frontend Hata Yönetimi:** `apiCall` fonksiyonunda `try-catch` bloklarını kullanarak API'den dönen hataları (örneğin, sunucu hatası, geçersiz istek) yakala ve `showToast` ile kullanıcıya anlamlı mesajlar göster.
- [ ] **Backend Hata Yönetimi:** PHP tarafında `try-catch` bloklarını daha etkin kullan. Veritabanı veya API hatalarında uygun HTTP durum kodları (örneğin, 400, 401, 500) ve açıklayıcı JSON mesajları döndür.
- [ ] **Yükleme Durumları:** Soru yüklenirken, cevap gönderilirken veya veri çekilirken tam ekran bir "yükleniyor" animasyonu göster.

### 3. Güvenlik İyileştirmeleri

- [ ] **SQL Injection'ı Önleme:** Tüm veritabanı sorgularında `prepared statements` kullanıldığından emin ol.
- [ ] **XSS (Cross-Site Scripting) Önleme:** Kullanıcıdan gelen ve ekrana basılan tüm verileri (örneğin, kullanıcı adı) `htmlspecialchars` gibi fonksiyonlarla temizle.
- [ ] **CSRF (Cross-Site Request Forgery) Koruması:** Form gönderimlerinde ve önemli API isteklerinde CSRF token'ları kullan.
- [ ] **Rate Limiting:** Özellikle giriş (login) ve kayıt (register) gibi işlemlere, kısa sürede çok sayıda denemeyi önlemek için hız sınırlaması (rate limiting) ekle.

### 4. Kullanıcı Deneyimi (UX) ve Arayüz (UI) Geliştirmeleri

- [ ] **Cevap Sonrası Geri Bildirim:** Cevap doğru veya yanlış olduğunda şıkların renklerini (doğruyu yeşil, yanlışı kırmızı) anında değiştir.
- [ ] **Jokerler:** "Yarı yarıya", "Süreyi uzat" veya "Pas geç" gibi jokerler ekle.
- [ ] **Kategori ve Zorluk Seçimi:** Kategori seçme ekranını daha görsel ve çekici hale getir.
- [ ] **Başarımlar:** Kazanılan başarımlar için daha dikkat çekici bir bildirim (modal veya özel bir animasyon) göster. Başarımlar sayfasını daha detaylı hale getir.
- [ ] **Profil Sayfası:** Kullanıcıların kendi istatistiklerini ve başarımlarını daha detaylı görebileceği bir profil sayfası oluştur.

### 5. Yeni Özellik Fikirleri

- [ ] **Düello Modu:** İki kullanıcının aynı anda aynı soruları çözdüğü bir rekabet modu.
- [ ] **Günlük Görevler:** "Bugün 5 tarih sorusu çöz" gibi günlük görevler ve ödüller.
- [ ] **Arkadaş Ekleme ve Meydan Okuma:** Kullanıcıların birbirini arkadaş olarak ekleyip meydan okuması.
- [ ] **Farklı Soru Tipleri:** Resimli sorular, sıralama soruları gibi yeni soru formatları ekle.
- [ ] **Avatar ve Özelleştirme:** Kullanıcıların profil fotoğrafı veya avatar seçebilmesi.
