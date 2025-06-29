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

## 3. Ses Efektleri ve Ayarları

**Fikir:** Doğru/yanlış cevaplar, süre bitimi gibi olaylar için basit ses efektleri eklemek. Kullanıcıların bu sesleri açıp kapatabileceği bir ayar butonu da sunulmalıdır.

**Fayda:** Kullanıcıya anında işitsel geri bildirim vererek deneyimi daha etkileşimli ve eğlenceli hale getirir.
