# Controllers Dizini

Bu dizin, uygulamanın ana iş mantığını (business logic) yöneten PHP sınıflarını içerir. Her bir "Controller", belirli bir sorumluluk alanına odaklanarak kodun modüler ve organize kalmasını sağlar. Projenin kök dizinindeki `api.php` dosyası, gelen isteğin `action` parametresine göre buradaki uygun Controller metodunu çağırır.

## Controller Sınıfları ve Sorumlulukları

- **`AdminController.php`**: Yönetici (admin) paneliyle ilgili işlemleri yönetir.
  - Genel uygulama istatistiklerini (toplam kullanıcı, toplam cevaplanan soru vb.) sağlar.
  - Gelişmiş istatistik verilerini (`Chart.js` grafikleri için) sağlar.
  - Tüm kullanıcıları listeler.
  - Kullanıcıların rolünü (admin/user) değiştirir.
  - Kullanıcıları veritabanından siler.
  - Duyuruları yönetir (oluşturma, listeleme, silme).

- **`DataController.php`**: Kullanıcıya özel verileri ve genel listeleri çekmekle sorumludur.
  - Bir kullanıcının kişisel istatistiklerini (puan, kategori başarı oranları) döndürür.
  - Genel liderlik tablosunu (kullanıcı avatarlarıyla birlikte) oluşturur.
  - Bir kullanıcının kazandığı ve kazanmadığı başarımları listeler.
  - Kullanıcılar için aktif ve okunmamış duyuruları getirir ve okundu olarak işaretlenmesini sağlar.

- **`DuelController.php`**: Arkadaşlar arası düello (meydan okuma) modunun tüm mantığını yönetir.
  - Yeni bir düello oluşturur ve soruları Gemini API'den alır.
  - Kullanıcının dahil olduğu düelloları (katılımcı avatarlarıyla birlikte) listeler.
  - Gelen düello isteklerine yanıt verilmesini (kabul/red) işler.
  - Düello oyununu başlatır ve cevapları işleyerek sonucu belirler.

- **`FriendsController.php`**: Arkadaşlık sistemiyle ilgili tüm işlemleri yönetir.
  - Kullanıcıları kullanıcı adına göre (avatarlarıyla birlikte) arar.
  - Arkadaşlık isteği gönderir.
  - Gelen arkadaşlık isteklerini listeler ve yanıtlama (kabul/red) işlemlerini yönetir.
  - Kullanıcının arkadaş listesini (avatarlarıyla birlikte) döndürür.
  - Arkadaş silme işlemini gerçekleştirir.

- **`GameController.php`**: Tek kişilik ana oyun modunun mantığını yönetir.
  - Gemini API'sini kullanarak yeni bir soru (çoktan seçmeli veya doğru/yanlış) alır.
  - Kullanıcının cevabını işler, puanı ve jetonu hesaplar, istatistikleri ve liderlik tablosunu günceller.
  - Cevap sonrası kazanılan başarımları ve tamamlanan görevleri kontrol eder.
  - Joker kullanımını sunucu tarafında işleyerek veritabanından düşer.

- **`QuestController.php`**: Günlük görevler sistemini yönetir.
  - Kullanıcı için günlük görevleri atar veya mevcut olanları getirir.
  - Bir oyun eylemi (örn. soru çözme) sonrasında görev ilerlemesini statik bir metot aracılığıyla günceller.
  - Tamamlanan görevler için ödülleri (puan ve jeton) verir.

- **`ShopController.php`**: Oyun içi mağaza mantığını yönetir.
  - Satılabilir ürünleri (şu an için jokerler) listeler.
  - Kullanıcının jetonlarını kullanarak joker satın alma işlemini gerçekleştirir, jeton bakiyesini ve joker envanterini günceller.

- **`UserController.php`**: Kullanıcı kimlik doğrulama (authentication) ve oturum (session) işlemlerini yönetir.
  - Yeni kullanıcı kaydı oluşturur (varsayılan avatar, jeton ve jokerlerle).
  - Kullanıcı girişi yapar ve oturum başlatır (avatar, jeton ve joker bilgileriyle).
  - Başarısız giriş denemelerini sayarak kaba kuvvet saldırılarına karşı hız sınırlaması (rate limiting) uygular.
  - Kullanıcı çıkışını gerçekleştirir.
  - Mevcut oturumun durumunu kontrol eder ve CSRF token üretir.
  - Kullanıcının avatarını güncellemesini sağlar.
