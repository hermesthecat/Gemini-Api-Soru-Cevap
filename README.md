# AI Bilgi Yarışması (Veritabanı & Kullanıcı Sistemi)

Bu proje, Google Gemini API'sini kullanarak çeşitli kategorilerde ve zorluk seviyelerinde dinamik olarak bilgi yarışması soruları oluşturan, çok kullanıcılı, veritabanı destekli bir "Tek Sayfa Uygulaması"dır (SPA).

## Özellikler

- **Yönetici Paneli (Admin Panel):** Admin rolüne sahip kullanıcılar için kullanıcıları (rol değiştirme, silme) ve genel site istatistiklerini yönetebilecekleri özel bir arayüz.
- **Kullanıcı Kayıt ve Giriş Sistemi:** Güvenli `password_hash` ile şifreleme ve PHP session yönetimi sayesinde kullanıcılar kendi hesaplarını oluşturabilir.
- **Veritabanı Entegrasyonu:** Tüm kullanıcı verileri, kişisel istatistikler ve puanlar MySQL veritabanında saklanır.
- **Kişiye Özel İstatistikler:** Her kullanıcının her kategorideki performansı (toplam soru, doğru cevap, başarı oranı) veritabanında tutulur ve kendi profilinde gösterilir.
- **Dinamik Liderlik Tablosu:** Kullanıcıların aldıkları puanlara göre sıralandığı ve periyodik olarak güncellenen bir liderlik tablosu bulunur.
- **Dinamik Soru Üretimi:** Google Gemini API'si ile her seferinde özgün sorular oluşturulur.
- **Tek Sayfa Uygulaması (SPA):** `fetch` API'si ve AJAX sayesinde sayfa yenilenmeden akıcı bir kullanıcı deneyimi sunar.
- **Çoklu Soru Tipi:** Çoktan seçmeli ve Doğru/Yanlış formatlarında rastgele sorular sunarak yarışmayı dinamik tutar.
- **Çoklu Kategori ve Zorluk:** Çeşitli kategorilerde "Kolay", "Orta" ve "Zor" seviyelerinde yarışma imkanı.
- **Açık/Koyu Tema:** Kullanıcının tercihine veya sistem ayarlarına göre değişen modern ve göz dostu arayüz.
- **Duyarlı Tasarım:** Tailwind CSS ile oluşturulmuş modern ve mobil uyumlu arayüz.

## Kullanılan Teknolojiler

- **Backend:** PHP, MySQL
- **Frontend:** HTML, Tailwind CSS, JavaScript (ES6+)
- **API İletişimi:** AJAX (`fetch` API)
- **AI Model:** Google Gemini Pro
- **Oturum Yönetimi:** PHP Sessions (`$_SESSION`)

## Kurulum ve Çalıştırma

Projeyi yerel makinenizde veya bir web sunucusunda çalıştırmak için aşağıdaki adımları izleyin:

1. **Projeyi Klonlayın:**

    ```bash
    git clone https://github.com/hermesthecat/ai-soru-cevap.git
    cd ai-soru-cevap
    ```

2. **`config.php` Dosyasını Oluşturun:**
    - Proje kök dizininde `config.php` adında bir dosya oluşturun. Bu dosya hem veritabanı bağlantı bilgilerinizi hem de API anahtarınızı içerecektir.

    ```php
    <?php
    // Veritabanı Ayarları
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root'); // Veritabanı kullanıcı adınız
    define('DB_PASS', '');     // Veritabanı şifreniz
    define('DB_NAME', 'ai_quiz'); // Kullanmak istediğiniz veritabanı adı

    // Google Gemini API Anahtarı
    // https://aistudio.google.com/app/apikey adresinden alabilirsiniz.
    define('GEMINI_API_KEY', 'SIZIN_API_ANAHTARINIZ');
    ```

3. **Veritabanını ve Tabloları Kurun:**
    - Tarayıcınızdan `http://localhost/proje-klasoru/install.php` adresini çalıştırın.
    - Bu betik, `config.php`'de belirttiğiniz isimde veritabanını, gerekli tüm tabloları (`users`, `leaderboard`, `user_stats`) ve varsayılan bir yönetici hesabını (`kullanıcı adı: admin`, `şifre: password`) otomatik olarak oluşturacaktır.

4. **Uygulamayı Başlatın:**
    - `install.php`'yi çalıştırdıktan sonra tarayıcınızdan ana dizine (`http://localhost/proje-klasoru/`) gidin.
    - Artık yeni bir kullanıcı kaydedebilir veya `admin` hesabıyla giriş yapabilirsiniz.

## Dosya Yapısı

```bash
.
├── api.php             # Backend: Kullanıcı, oyun, admin ve veri işlemlerini yöneten API.
├── assets/
│   ├── css/style.css   # Özel CSS stilleri.
│   └── js/app.js       # Frontend: Tüm uygulama mantığını yöneten ana JS dosyası.
├── config.php          # Veritabanı ve API anahtarı yapılandırması.
├── GeminiAPI.php       # Google Gemini API ile iletişimi yöneten sınıf.
├── index.php           # Ana HTML iskeleti, oyun ve admin paneli görünümlerini içerir.
├── install.php         # Veritabanını, tabloları ve admin kullanıcısını oluşturan kurulum betiği.
└── README.md           # Bu dosya.
```

## Nasıl Çalışır?

Uygulama, modern bir SPA mimarisiyle çalışır:

1. **Başlatma:** Kullanıcı `index.php`'yi açtığında, `app.js` çalışır ve `api.php`'ye bir `check_session` isteği göndererek aktif bir oturum olup olmadığını kontrol eder.
2. **Oturum Yönetimi:**
    - **Oturum Varsa:** `api.php` kullanıcı bilgilerini (`id`, `username`, `role`) döndürür.
        - **Kullanıcı 'admin' ise:** Frontend, Yönetim Panelini (`admin-view`) gösterir. Panel için gerekli veriler (kullanıcı listesi, genel istatistikler) API'den çekilir.
        - **Kullanıcı 'user' ise:** Frontend, ana uygulama ekranını (`main-view`) gösterir, kullanıcıyı karşılar ve verileri (kişisel istatistikler, liderlik tablosu) yükler.
    - **Oturum Yoksa:** Frontend, giriş/kayıt formlarının olduğu `auth-view`'ı gösterir.
3. **Admin İşlemleri:** Admin, panel üzerinden bir kullanıcının rolünü değiştirdiğinde veya bir kullanıcıyı sildiğinde, `app.js` ilgili `admin_*` endpoint'ini çağırır ve başarılı olursa arayüzü günceller.
4. **Oyun Akışı:** (Normal kullanıcılar veya admin "Oyuncu Görünümü"ne geçtiğinde)
    - Kullanıcı bir kategori seçer ve `api.php`'nin `get_question` endpoint'inden bir soru istenir.
    - `api.php`, Gemini'den soruyu alır, doğru cevabı ve açıklamayı sunucu tarafında `$_SESSION`'a kaydeder ve sadece soruyu/seçenekleri ön uca gönderir.
    - Kullanıcı cevabını `submit_answer` endpoint'ine gönderir.
    - `api.php`, cevabı `$_SESSION`'daki doğru cevapla karşılaştırır, puanı hesaplar ve kullanıcının `user_stats` ve `leaderboard` tablolarındaki verilerini günceller.
5. **Arayüz Güncelleme:** Ön uç, cevabın sonucunu (`doğru`/`yanlış`, `açıklama`) alır, arayüzü günceller ve en güncel istatistik/liderlik tablosu verilerini ekrana yansıtır.

## Lisans

Bu proje MIT Lisansı altında lisanslanmıştır.
