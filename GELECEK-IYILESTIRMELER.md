# Gelecek İçin Yeni İyileştirme Fikirleri

Projenin mevcut SPA yapısı, üzerine yeni özellikler inşa etmek için sağlam bir temel oluşturmaktadır. İşte projeyi daha da ileriye taşıyabilecek bazı yeni fikirler:

## 1. Detaylı Cevap Açıklamaları

**Fikir:** Kullanıcı bir cevabı işaretledikten sonra, sadece doğru/yanlış sonucunu göstermek yerine, doğru cevabın neden doğru olduğuna dair 1-2 cümlelik kısa bir açıklama sunmak. Bu açıklama, soruyla birlikte Gemini API'sinden istenebilir ve cevap ekranında gösterilebilir.

**Fayda:** Uygulamanın eğitici değerini önemli ölçüde artırır. Kullanıcıların sadece neyin doğru olduğunu değil, neden doğru olduğunu da öğrenmelerini sağlar.

## 2. Tema Seçenekleri (Koyu/Açık Mod)

**Fikir:** Arayüze, kullanıcıların açık ve koyu tema arasında geçiş yapmasını sağlayan bir buton eklemek. Kullanıcının seçimi, bir sonraki ziyaretinde hatırlanması için `localStorage`'da saklanabilir.

**Fayda:** Kullanıcı deneyimini kişiselleştirir, göz yorgunluğunu azaltır ve uygulamaya modern bir dokunuş katar.

## 3. Farklı Soru Tipleri

**Fikir:** Klasik çoktan seçmeli formatına ek olarak "Doğru/Yanlış" veya "Boşluk Doldurma" gibi yeni soru türleri eklemek. `api.php` ve `app.js`, farklı soru tiplerini işleyecek şekilde güncellenmelidir. API'ye gönderilen prompt, istenen soru formatını belirtmelidir.

**Fayda:** Yarışmayı daha çeşitli, dinamik ve ilgi çekici hale getirir. Tekrar oynanabilirliği artırır.

## 4. Kategoriye Özel İstatistikler

**Fikir:** Genel başarı oranının yanı sıra, kullanıcının her bir kategorideki performansını ayrı ayrı takip etmek ve göstermek (Örn: "Tarih: %80 başarı, Spor: %60 başarı").

**Fayda:** Kullanıcılara hangi alanlarda daha güçlü veya zayıf oldukları konusunda detaylı geri bildirim sunar.

## 5. Progressive Web App (PWA) Yetenekleri

**Fikir:** Projeye bir `manifest.json` dosyası ve bir `service worker` (`sw.js`) ekleyerek temel PWA yetenekleri kazandırmak. Bu sayede kullanıcılar uygulamayı telefonlarının veya bilgisayarlarının ana ekranına bir kısayol olarak ekleyebilir.

**Fayda:** Uygulamaya daha "yerel" bir uygulama hissi verir ve erişilebilirliği artırır. Gelecekte çevrimdışı çalışma gibi özelliklerin de önünü açar.
