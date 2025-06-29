# Gelecek İyileştirmeler ve Yol Haritası

Bu belge, AI Bilgi Yarışması projesinin gelecekteki gelişim yönünü ve potansiyel özelliklerini özetlemektedir.

---

## 🚀 Kısa ve Orta Vadeli Planlar

Bu bölüm, gelecek geliştirme döngüleri için planlanan özellikleri ve iyileştirmeleri içerir.

### 1. Oyun Deneyimi ve Çeşitlilik

- [ ] **Farklı Soru Tipleri:** Resimli, sesli veya sıralama bazlı sorular gibi yeni formatlar ekleyerek oyun çeşitliliğini artır.
- [ ] **Turnuva Modu:** Haftalık veya aylık periyotlarla düzenlenen, özel ödüllere sahip turnuvalar oluştur.
- [ ] **Özelleştirilebilir Oyunlar:** Kullanıcıların soru sayısı, kategori, zorluk ve süre limiti gibi ayarları belirleyerek kendi özel oyunlarını oluşturup arkadaşlarıyla paylaşabilmesi.
- [ ] **Soru Kalitesi Geribildirimi:** Kullanıcıların soruları (1-5 yıldız) oylayabilmesi veya hatalı/kalitesiz soruları raporlayabilmesi için bir mekanizma ekle.

### 2. Sosyal Özellikler ve Etkileşim

- [ ] **Gerçek Zamanlı Bildirimler:** Arkadaşlık isteği, düello daveti gibi olaylar için anlık bildirim sistemi (WebSocket veya Server-Sent Events ile).
- [ ] **Gelişmiş Kullanıcı Profilleri:** Diğer kullanıcıların ziyaret edebileceği, daha detaylı istatistikler ve kazanılan başarımların sergilendiği herkese açık profil sayfaları.
- [ ] **Takım/Klan Sistemi:** Kullanıcıların takımlar oluşturarak takım bazlı liderlik tablolarında ve turnuvalarda yarışabilmesi.

### 3. Yönetici Paneli Geliştirmeleri

- [ ] **Detaylı İstatistikler:** Admin paneline grafikler ve daha ayrıntılı analizler (örn. en çok oynanan kategoriler, günlük aktif kullanıcı sayısı) ekle.
- [ ] **Duyuru Sistemi:** Admin'in tüm kullanıcılara veya belirli gruplara uygulama içi duyurular gönderebilmesi.
- [ ] **Soru Yönetimi:** Raporlanan soruları incelemek ve yönetmek için bir arayüz.

---

## 💡 Uzun Vadeli Fikirler ve Teknik Geliştirmeler

Bu bölüm, projenin uzun vadeli sağlığı ve ölçeklenebilirliği için daha geniş fikirleri ve teknik iyileştirmeleri içerir.

### 1. Oyunlaştırma ve Ekonomi

- [ ] **Oyun İçi Para Birimi:** Doğru cevaplar, tamamlanan görevler ve kazanılan düellolar için "jeton" kazanma sistemi.
- [ ] **Mağaza:** Kazanılan jetonlarla yeni avatarlar, profil çerçeveleri, tema renkleri veya ek jokerler gibi kozmetik veya işlevsel öğelerin satın alınabileceği bir mağaza.
- [ ] **Günlük Giriş Ödülleri:** Kullanıcıları her gün giriş yapmaya teşvik eden ödül sistemi.

### 2. Teknik İyileştirmeler

- [ ] **Frontend Refactoring:** `ui-handler.js` gibi büyük dosyaları daha küçük, yönetilebilir bileşenlere ayır.
- [ ] **API Dokümantasyonu:** Projenin API'si için Swagger/OpenAPI gibi standartlarda bir dokümantasyon oluştur.
- [ ] **Test Altyapısı:** PHPUnit (backend) ve Jest (frontend) gibi araçlarla birim ve entegrasyon testleri ekle.
- [ ] **Asenkron İşlemler:** E-posta gönderme veya karmaşık rapor oluşturma gibi uzun süren işlemler için bir "queue" (kuyruk) sistemi kur.

---

## ✅ Tamamlananlar

Bu bölüm, daha önce tamamlanmış olan ana özellikleri ve yeniden yapılandırma çalışmalarını arşivlemektedir.

- **Modüler Kod Mimarisi:** Backend Controller (`User`, `Game`, `Admin` vb.) ve Frontend Handler (`api`, `ui`, `auth` vb.) sınıfları ile kodun yeniden yapılandırılması.
- **Güvenlik İyileştirmeleri:** SQL Injection, XSS ve CSRF'e karşı korumalar ve giriş denemeleri için hız sınırlaması (rate limiting) eklendi.
- **Kullanıcı Deneyimi:** Jokerler, cevap sonrası anlık geribildirim, koyu/açık tema, ses ayarları ve başarım bildirimleri gibi özellikler eklendi.
- **Sosyal Özellikler:** Arkadaşlık sistemi (arama, ekleme, çıkarma) ve arkadaşlarla düello (meydan okuma) modu tamamen entegre edildi.
- **Oyunlaştırma:** Günlük görevler, 20'den fazla başarım ve dinamik liderlik tablosu eklendi.
- **Özelleştirme:** Kullanıcıların 10 farklı avatar arasından seçim yapabilmesi sağlandı.
