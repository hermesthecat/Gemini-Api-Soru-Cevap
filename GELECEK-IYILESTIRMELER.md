# Gelecek İçin Yeni İyileştirme Fikirleri

Projenin mevcut çok kullanıcılı SPA yapısı, üzerine yeni özellikler inşa etmek için sağlam bir temel oluşturmaktadır. İşte projeyi daha da ileriye taşıyabilecek bazı yeni fikirler:

## 1. Daha Fazla Soru Tipi

**Mevcut Durum:** Çoktan seçmeli ve Doğru/Yanlış soru tipleri destekleniyor.

**Fikir:** Yarışmayı daha da çeşitlendirmek için yeni soru formatları eklemek:

- **Boşluk Doldurma:** Kullanıcının bir metin kutusuna kısa bir cevap yazdığı sorular.
- **Görsel Sorular:** Bir resim gösterip onunla ilgili soru sormak (Gemini'nin görsel anlama yetenekleri kullanılabilir).

**Fayda:** Tekrar oynanabilirliği artırır ve farklı bilgi türlerini test eder.

## 2. Progressive Web App (PWA) Yetenekleri

**Fikir:** Projeye bir `manifest.json` dosyası ve bir `service worker` (`sw.js`) ekleyerek temel PWA yetenekleri kazandırmak. Bu sayede kullanıcılar uygulamayı telefonlarının veya bilgisayarlarının ana ekranına bir kısayol olarak ekleyebilir.

**Fayda:** Uygulamaya daha "yerel" bir uygulama hissi verir ve erişilebilirliği artırır. Gelecekte çevrimdışı çalışma gibi özelliklerin de önünü açar.

## 3. Joker Sistemi (Lifelines)

**Fikir:** Yarışmaya stratejik bir derinlik katmak için her yarışma başında kullanıcıya birer kez kullanabileceği joker hakları tanımlamak.

- **%50 Joker Hakkı:** Çoktan seçmeli sorularda yanlış olan iki şıkkı eler.
- **Ekstra Süre Jokeri:** Mevcut süreye 15-20 saniye ekler.

**Fayda:** Oyuna yeni bir heyecan ve strateji katmanı ekler, kullanıcının zorlandığı anlarda yardımcı olur.

## 4. Başarım Sistemi (Rozetler)

**Fikir:** Kullanıcıları belirli hedeflere ulaşmaya teşvik etmek için bir başarım ve rozet sistemi oluşturmak.

- **Kategori Uzmanı:** Bir kategoride belirli sayıda (örn: 50) soruyu doğru cevaplayınca kazanılan "Tarih Kurdu" gibi rozetler.
- **Hız Tutkunu:** Soruyu 5 saniyenin altında doğru cevaplama başarımı.
- **Seri Galibi:** Üst üste 10 soruyu doğru cevaplama başarımı.

**Fayda:** Uzun vadeli motivasyon sağlar, tekrar oynanabilirliği artırır ve kullanıcıların siteye olan bağlılığını güçlendirir.
