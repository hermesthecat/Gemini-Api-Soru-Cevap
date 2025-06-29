# AI Bilgi Yarışması

Bu proje, Google Gemini API'sini kullanarak çeşitli kategorilerde dinamik olarak bilgi yarışması soruları oluşturan web tabanlı bir uygulamadır. Kullanıcılar farklı kategorilerde bilgi seviyelerini test edebilir ve her soru için 30 saniye içinde cevap vermeleri gerekir.

## Özellikler

- **Dinamik Soru Üretimi:** Google Gemini API'si ile her seferinde özgün sorular oluşturulur.
- **Çoklu Kategori:** Tarih, Spor, Bilim, Sanat, Coğrafya ve Genel Kültür gibi çeşitli kategorilerde yarışma imkanı.
- **Zaman Sınırı:** Her soru için 30 saniyelik geri sayım sayacı.
- **Sonuç Bildirimi:** Verilen cevabın doğruluğunu veya sürenin dolduğunu anında gösterir.
- **Duyarlı Tasarım:** Tailwind CSS ile oluşturulmuş modern ve mobil uyumlu arayüz.
- **Açık Kaynak:** Kod tabanı tamamen açık kaynaktır ve geliştirmeye açıktır.

## Kullanılan Teknolojiler

- **Backend:** PHP
- **API:** Google Gemini Pro
- **Frontend:** HTML, Tailwind CSS, JavaScript

## Ekran Görüntüleri

| Ana Sayfa | Soru Ekranı | Doğru Cevap | Yanlış Cevap |
| :---: | :---: | :---: | :---: |
| ![Ana Sayfa](https://i.ibb.co/SncgVR2/image.png) | ![Örnek Soru Ekranı](https://i.ibb.co/fNnwgc0/image.png) | ![Doğru Cevap](https://i.ibb.co/w4Qtrbj/image.png) | ![Yanlış Cevap](https://i.ibb.co/TTjWyNM/image.png) |

## Kurulum ve Çalıştırma

Projeyi yerel makinenizde veya bir web sunucusunda çalıştırmak için aşağıdaki adımları izleyin:

1.  **Projeyi Klonlayın:**
    ```bash
    git clone https://github.com/kullanici-adiniz/ai-soru-cevap.git
    cd ai-soru-cevap
    ```

2.  **Google Gemini API Anahtarı Alın:**
    - [Google AI Studio](https://aistudio.google.com/app/apikey) adresine gidin ve bir API anahtarı oluşturun.
    - Bu anahtar, uygulamanın soru üretebilmesi için gereklidir.

3.  **API Anahtarını Yapılandırın:**
    - Proje kök dizinindeki `config.php` dosyasını açın.
    - `'API-KEY-BURAYA'` yazan kısmı kendi Gemini API anahtarınızla değiştirin.
    ```php
    <?php
    define('GEMINI_API_KEY', 'SIZIN_API_ANAHTARINIZ'); // API anahtarınızın doğru olduğundan emin olun
    ```

4.  **Sunucuyu Başlatın:**
    - Projeyi XAMPP, WAMP gibi bir yerel sunucu ortamının `htdocs` veya `www` klasörüne taşıyın.
    - Apache ve MySQL sunucularını başlatın.
    - Tarayıcınızdan `http://localhost/ai-soru-cevap` adresine gidin.

## Dosya Yapısı

```
.
├── config.php          # API anahtarı gibi yapılandırma ayarlarını içerir.
├── GeminiAPI.php       # Google Gemini API ile iletişimi yöneten sınıf.
├── index.php           # Ana uygulama dosyası, kullanıcı arayüzü ve mantığı.
├── README.md           # Bu dosya.
└── favicon.ico         # Tarayıcı sekmesinde görünen ikon.
```

## Nasıl Çalışır?

1.  Kullanıcı, ana sayfada sunulan kategorilerden birini seçer.
2.  `index.php`, seçilen kategoriye uygun bir soru oluşturması için `GeminiAPI.php` sınıfı aracılığıyla Google Gemini API'sine bir istek gönderir.
3.  API'den gelen yanıt (soru, şıklar ve doğru cevap) ayrıştırılır ve PHP `$_SESSION` içinde saklanır.
4.  Soru ve şıklar kullanıcıya gösterilir ve 30 saniyelik zamanlayıcı başlar.
5.  Kullanıcı bir şıkkı seçtiğinde, cevabı `$_SESSION` içinde saklanan doğru cevapla karşılaştırılır.
6.  Sonuç (doğru, yanlış veya süre doldu) ekranda görüntülenir.
7.  Kullanıcı yeni bir soru isteyebilir veya farklı bir kategori seçebilir.

## Lisans

Bu proje MIT Lisansı altında lisanslanmıştır. Daha fazla bilgi için `LICENSE` dosyasına bakın.
(Not: Projede `LICENSE` dosyası bulunmamaktadır. Eklenmesi tavsiye edilir.)
