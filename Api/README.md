# API Dizini

Bu dizin, AI Bilgi Yarışması uygulamasının tüm backend (sunucu tarafı) mantığını içerir. Uygulamanın beyni olarak kabul edilebilir.

## Sorumluluklar

- Gelen API isteklerini işlemek.
- Veritabanı ile etkileşime geçmek (veri okuma, yazma, güncelleme).
- İş mantığını (business logic) yürütmek (örneğin, bir kullanıcının cevabının doğru olup olmadığını kontrol etmek, puan hesaplamak, düello durumunu yönetmek).
- Google Gemini API'si ile iletişim kurmak.
- Frontend'e (istemci tarafı) JSON formatında yapılandırılmış yanıtlar göndermek.

## Yapı

- **`Controllers/`**: Bu alt dizin, uygulamanın farklı mantıksal bölümlerini yöneten PHP sınıflarını (Controller'ları) barındırır. Bu yapı, kodun daha organize, yönetilebilir ve modüler olmasını sağlar. Her bir controller, belirli bir alanla ilgili sorumlulukları üstlenir (örn. `UserController` kullanıcı işlemlerini, `GameController` oyun akışını yönetir).

Projenin kök dizininde bulunan `api.php` dosyası, bir yönlendirici (router) görevi görerek gelen `action` parametresine göre bu dizindeki ilgili `Controller` metodunu çağırır.
