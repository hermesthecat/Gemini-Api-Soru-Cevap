# AI Bilgi Yarışması (AJAX & SPA)

Bu proje, Google Gemini API'sini kullanarak çeşitli kategorilerde ve zorluk seviyelerinde dinamik olarak bilgi yarışması soruları oluşturan, sayfa yenilemesi olmadan çalışan (Single Page Application) web tabanlı bir uygulamadır.

## Özellikler

- **Dinamik Soru Üretimi:** Google Gemini API'si ile her seferinde özgün sorular oluşturulur.
- **Tek Sayfa Uygulaması (SPA):** `fetch` API'si ve AJAX sayesinde sayfa yenilenmeden akıcı bir kullanıcı deneyimi sunar.
- **Çoklu Soru Tipi:** Çoktan seçmeli ve Doğru/Yanlış formatlarında rastgele sorular sunarak yarışmayı dinamik tutar.
- **Çoklu Kategori ve Zorluk:** Tarih, Spor, Bilim gibi kategorilerde "Kolay", "Orta" ve "Zor" seviyelerinde yarışma imkanı.
- **Detaylı İstatistikler:** Genel başarı oranının yanı sıra, her kategori için ayrı ayrı detaylı istatistikler tutulur ve gösterilir.
- **Kalıcı Veri:** Kullanıcının tema tercihi ve tüm istatistikleri, tarayıcının `localStorage` özelliği kullanılarak saklanır.
- **Gelişmiş Geri Bildirim:** Cevap verildiğinde, seçenekler üzerinde doğru/yanlış şıklar vurgulanır ve doğru cevabın neden doğru olduğuna dair bir açıklama sunulur.
- **Açık/Koyu Tema:** Kullanıcının tercihine veya sistem ayarlarına göre değişen modern ve göz dostu arayüz.
- **Zaman Sınırı:** Her soru için 30 saniyelik geri sayım sayacı.
- **Duyarlı Tasarım:** Tailwind CSS ile oluşturulmuş modern ve mobil uyumlu arayüz.

## Kullanılan Teknolojiler

- **Backend:** PHP (Sadece istekleri işleyen ve durum tutmayan "stateless" bir API)
- **Frontend:** HTML, Tailwind CSS, JavaScript (ES6+)
- **API İletişimi:** AJAX (`fetch` API)
- **AI Model:** Google Gemini Pro
- **Veri Formatı:** JSON

## Kurulum ve Çalıştırma

Projeyi yerel makinenizde veya bir web sunucusunda çalıştırmak için aşağıdaki adımları izleyin:

1. **Projeyi Klonlayın:**

    ```bash
    git clone https://github.com/kullanici-adiniz/ai-soru-cevap.git
    cd ai-soru-cevap
    ```

2. **Google Gemini API Anahtarı Alın:**
    - [Google AI Studio](https://aistudio.google.com/app/apikey) adresine gidin ve bir API anahtarı oluşturun.
    - Bu anahtar, uygulamanın soru üretebilmesi için gereklidir.

3. **API Anahtarını Yapılandırın:**
    - Proje kök dizinindeki `config.php` dosyasını açın.
    - `'API-KEY-BURAYA'` yazan kısmı kendi Gemini API anahtarınızla değiştirin.

    ```php
    <?php
    define('GEMINI_API_KEY', 'SIZIN_API_ANAHTARINIZ'); // API anahtarınızın doğru olduğundan emin olun
    ```

4. **Sunucuyu Başlatın:**
    - Projeyi XAMPP, WAMP gibi bir yerel sunucu ortamının `htdocs` veya `www` klasörüne taşıyın.
    - Apache sunucusunu başlatın.
    - Tarayıcınızdan `http://localhost/ai-soru-cevap` adresine gidin.

## Dosya Yapısı

```bash
.
├── api.php             # Backend mantığını işleyen, AJAX isteklerine yanıt veren API dosyası.
├── assets/
│   ├── css/style.css   # Özel CSS stilleri.
│   └── js/app.js       # Tüm frontend mantığını, API çağrılarını ve UI güncellemelerini yöneten JS dosyası.
├── config.php          # API anahtarı gibi yapılandırma ayarlarını içerir.
├── GeminiAPI.php       # Google Gemini API ile iletişimi yöneten sınıf.
├── index.php           # Ana HTML iskeleti ve uygulamanın başlangıç noktası.
└── README.md           # Bu dosya.
```

## Nasıl Çalışır?

Uygulama, ön uç (frontend) ve arka uç (backend) olarak ikiye ayrılmış modern bir yapı kullanır:

1. **Başlatma:** Kullanıcı `index.php`'yi açtığında, `assets/js/app.js` dosyası çalışır. `localStorage`'dan kaydedilmiş tema tercihini ve istatistikleri yükler, arayüzü buna göre hazırlar.
2. **Soru İsteği:** Kullanıcı bir zorluk ve kategori seçtiğinde, `app.js` bu bilgileri içeren bir AJAX isteğini `api.php`'ye gönderir.
3. **Soru Üretme:** `api.php`, gelen isteğe göre rastgele bir soru tipi (`coktan_secmeli` veya `dogru_yanlis`) seçer. Bu tipe uygun bir komut oluşturarak Google Gemini'den soru, cevap, şıklar (varsa) ve bir açıklama üretmesini ister.
4. **Soru Gönderme:** `api.php`, Gemini'den aldığı verileri (soru tipi dahil) JSON formatında ön uca gönderir. *Doğru cevap ve açıklama bu aşamada kullanıcıya gönderilmez*, sunucuda geçici olarak `$_SESSION` içinde saklanır.
5. **Soru Gösterimi:** `app.js`, aldığı verinin tipine göre arayüzü dinamik olarak oluşturur (çoktan seçmeli veya doğru/yanlış butonları), soruyu ekranda gösterir ve sayacı başlatır.
6. **Cevap Kontrolü:** Kullanıcı bir seçim yaptığında, `app.js` seçilen cevabı yeni bir AJAX isteği ile tekrar `api.php`'ye gönderir.
7. **Sonuç Bildirimi:** `api.php`, gelen cevabı sunucuda saklanan doğru cevapla karşılaştırır ve sonucun doğruluğunu (`is_correct`), doğru cevabı (`correct_answer`) ve açıklamayı (`explanation`) içeren bir JSON yanıtı oluşturur.
8. **Arayüz Güncelleme:** `app.js` bu sonuç verisini alır. Arayüzü (şık renklendirme, açıklama alanı) günceller. İlgili kategorinin istatistiğini artırır ve hem genel hem de kategori bazlı istatistik tablolarını yeniden çizer. Tüm yeni veriler `localStorage`'a kaydedilir. Birkaç saniye sonra kullanıcıyı tekrar kategori seçim ekranına yönlendirir.

## Ekran Görüntüleri

| Ana Sayfa | Soru Ekranı | Doğru Cevap | Yanlış Cevap |
| :---: | :---: | :---: | :---: |
| ![Ana Sayfa](https://i.ibb.co/SncgVR2/image.png) | ![Örnek Soru Ekranı](https://i.ibb.co/fNnwgc0/image.png) | ![Doğru Cevap](https://i.ibb.co/w4Qtrbj/image.png) | ![Yanlış Cevap](https://i.ibb.co/TTjWyNM/image.png) |

## Lisans

Bu proje MIT Lisansı altında lisanslanmıştır. Daha fazla bilgi için `LICENSE` dosyasına bakın.
(Not: Projede `LICENSE` dosyası bulunmamaktadır. Eklenmesi tavsiye edilir.)
