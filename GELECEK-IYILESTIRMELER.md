# Gelecek İçin Yeni İyileştirme Fikirleri

Projenin mevcut çok kullanıcılı SPA yapısı, üzerine yeni özellikler inşa etmek için sağlam bir temel oluşturmaktadır. İşte projeyi daha da ileriye taşıyabilecek bazı yeni fikirler:

### 1. Kod Yapısı ve Sürdürülebilirlik

- **Frontend Modülerizasyonu (`app.js`):** Mevcut `app.js` dosyasını sorumluluklarına göre daha küçük JavaScript modüllerine ayırmak:
  - `api.js`: Sunucu ile iletişim.
  - `ui.js`: DOM manipülasyonları ve arayüz güncellemeleri.
  - `game.js`: Oyun akışı mantığı.
  - `auth.js`: Kullanıcı giriş/kayıt işlemleri.
  - `state.js`: Global uygulama durumu yönetimi.
- **Backend Yapılandırması (`api.php`):**
  - **Controller Sınıfları:** `switch-case` yapısını `UserController`, `GameController`, `AdminController` gibi sınıflara bölerek kod organizasyonunu iyileştirmek.
  - **Servis Katmanı:** Başarım kontrolü gibi karmaşık iş mantıklarını `AchievementService` gibi ayrı sınıflara taşımak.
- **Merkezi Yapılandırma:** Kategoriler ve başarım metinleri gibi verileri hem frontend hem de backend'de tekrar etmek yerine, veritabanından veya ortak bir JSON dosyasından yönetmek.

### 2. Hatalar ve Anlık İyileştirmeler

- **Joker Butonu Hatası:** `app.js` içinde `handleAnswerSubmission` fonksiyonunda tekrar tekrar eklenen olay dinleyicilerini, uygulama başlangıcında bir kez eklenecek şekilde `addEventListeners` fonksiyonuna taşımak.
- **Kullanılmayan CSS Animasyonu:** `style.css`'teki `.answer-feedback` animasyonunu, cevaplar gösterilirken kullanarak daha iyi bir görsel geri bildirim sağlamak.

### 3. Kullanıcı Deneyimi (UX) İyileştirmeleri

- **Gelişmiş Oyun Akışı:** Soru cevaplandıktan sonra otomatik olarak kategori ekranına dönmek yerine, "Aynı Kategoriden Devam Et" veya "Yeni Kategori Seç" gibi seçenekler sunmak.
- **Süre Bittiğinde Geri Bildirim:** Zamanlayıcı dolduğunda da doğru cevabı ve açıklamasını kullanıcıya göstermek.
- **Joker Kullanım Geri Bildirimi:** Joker kullanıldığında hangi jokerin kullanıldığını ve etkisini belirten kısa bir bildirim (toast) göstermek.

### 4. Potansiyel Yeni Özellikler

- **Sosyal Özellikler:** Arkadaş ekleme ve düello (meydan okuma) sistemi.
- **Yeni Oyun Modları:** "Süreli Mücadele" (belirli bir sürede en çok doğruyu yapma) veya "Hayatta Kalma" (yanlış yapana kadar devam etme) gibi modlar.
- **Kişiselleştirilebilir Profiller:** Kullanıcıların avatar seçebileceği veya profil sayfalarına küçük notlar ekleyebileceği bir yapı.
- **Şifre Sıfırlama:** "Şifremi Unuttum" özelliği ile e-posta üzerinden parola sıfırlama imkanı.
- **Genişletilmiş Joker Sistemi:** "Pas Geçme" veya "İzleyiciye Sor" (diğer oyuncuların cevap oranlarını görme) gibi yeni jokerler eklemek.
- **Haftalık/Aylık Liderlik Tabloları:** Tüm zamanların liderlik tablosuna ek olarak periyodik tablolar oluşturarak rekabeti canlı tutmak.
