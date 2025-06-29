# Gelecek İçin İyileştirme Fikirleri

Bu dosya, "AI Bilgi Yarışması" projesini daha da geliştirmek için potansiyel fikirleri ve önerileri içermektedir.

## 1. Kalıcı İstatistikler (LocalStorage Kullanımı)

**Mevcut Durum:** İstatistikler PHP oturumunda (`$_SESSION`) saklanıyor ve tarayıcı kapatıldığında kayboluyor.

**Öneri:** Kullanıcının toplam soru, doğru cevap ve başarı oranı gibi istatistiklerini tarayıcının `localStorage` özelliğini kullanarak saklamak.

**Faydaları:**

- Kullanıcı siteye tekrar geldiğinde istatistiklerini görmeye devam eder.
- Uygulamaya daha kalıcı ve kişisel bir his verir.
- Oturum yönetimine olan bağımlılığı azaltır.

## 2. Gelişmiş Cevap Geri Bildirimi

**Mevcut Durum:** Cevap verildiğinde sadece "Doğru!" veya "Yanlış!" şeklinde bir animasyon beliriyor.

**Öneri:** Cevap gönderildikten sonra, şıkların bulunduğu ekranda:

- Doğru olan şıkkı yeşil bir arka planla vurgulamak.
- Eğer kullanıcı yanlış cevap verdiyse, kendi seçtiği şıkkı kırmızı bir arka planla işaretlemek.

**Faydaları:**

- Kullanıcıya anında görsel ve daha bilgilendirici bir geri bildirim sunar.
- Öğrenme deneyimini güçlendirir, kullanıcının doğru cevabı net bir şekilde görmesini sağlar.

## 3. Zorluk Seviyeleri

**Mevcut Durum:** Tüm sorular "orta" zorluk seviyesinde oluşturuluyor.

**Öneri:** Kategori seçim ekranına ek olarak "Kolay", "Orta" ve "Zor" gibi zorluk seviyesi seçenekleri eklemek. Kullanıcının seçimi, Gemini API'ye gönderilen `prompt`'a dahil edilir.

**Faydaları:**

- Uygulamanın tekrar oynanabilirliğini artırır.
- Kullanıcıların kendi bilgi seviyelerine göre yarışmasına olanak tanır.
- Daha geniş bir kullanıcı kitlesine hitap eder.

## 4. Kod Yapısını İyileştirme (Ayrı Dosyalar)

**Mevcut Durum:** Tüm JavaScript ve CSS kodları `index.php` dosyası içinde yer alıyor (`inline` ve `internal`).

**Öneri:**

- Tüm JavaScript kodunu `assets/js/app.js` gibi harici bir dosyaya taşımak.
- Tüm özel CSS kurallarını (`animation` vb.) `assets/css/style.css` gibi harici bir dosyaya taşımak.
- Bu dosyaları `index.php`'den `<link>` ve `<script src="...">` etiketleriyle çağırmak.

**Faydaları:**

- "Separation of Concerns" (Sorumlulukların Ayrılması) ilkesine uyum sağlar.
- `index.php` dosyasını çok daha temiz ve okunabilir hale getirir.
- Tarayıcının bu dosyaları önbelleğe almasına (caching) olanak tanıyarak performansı artırır.
- Projenin bakımını ve yönetimini kolaylaştırır.
