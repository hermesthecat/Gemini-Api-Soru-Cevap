<?php

/**
 * AI Bilgi Yarışması - Backend API (Stateless)
 *
 * Bu dosya, ön uçtan (JavaScript) gelen AJAX isteklerini işler.
 * Artık istatistik veya geçmiş tutmaz, sadece soru üretir ve cevapları kontrol eder.
 */

// Gerekli dosyaları ve başlangıç ayarlarını dahil et
require_once 'config.php';
require_once 'GeminiAPI.php';

// Oturumu başlat (sadece mevcut soru ve cevabı geçici olarak tutmak için)
session_start();

// Yanıt başlığını JSON olarak ayarla
header('Content-Type: application/json');

// Gelen isteğin içeriğini al
$request_data = json_decode(file_get_contents('php://input'), true);
$action = $request_data['action'] ?? null;

// Yanıt için standart bir yapı oluştur
$response = [
    'success' => false,
    'message' => 'Geçersiz istek',
    'data' => null
];

// --- AKSİYON YÖNLENDİRİCİSİ ---
switch ($action) {
    case 'get_question':
        // Yeni bir soru oluşturur ve döndürür.
        $kategori = $request_data['kategori'] ?? 'genel kültür';
        $difficulty = $request_data['difficulty'] ?? 'orta'; // Zorluk seviyesini al, varsayılan 'orta'
        if (empty($kategori)) {
            $response['message'] = 'Kategori belirtilmedi.';
            break;
        }

        // Mevcut soru bilgilerini temizle
        unset($_SESSION['current_question_answer']);
        unset($_SESSION['start_time']);

        $gemini = new GeminiAPI(GEMINI_API_KEY);

        try {
            $prompt = "Lütfen {$kategori} kategorisinde {$difficulty} zorlukta bir soru hazırla. Yanıtı yalnızca şu JSON formatında, başka hiçbir metin olmadan ver:
            {
                \"soru\": \"(soru metni buraya)\",
                \"siklar\": {
                    \"A\": \"(A şıkkı buraya)\",
                    \"B\": \"(B şıkkı buraya)\",
                    \"C\": \"(C şıkkı buraya)\",
                    \"D\": \"(D şıkkı buraya)\"
                },
                \"dogru_cevap\": \"(Doğru şıkkın harfi buraya, örneğin: A)\"
            }";

            $yanit = $gemini->soruSor($prompt);

            if ($yanit) {
                $temiz_yanit = preg_replace('/^```json\s*|\s*```$/', '', trim($yanit));
                $veri = json_decode($temiz_yanit, true);

                if (json_last_error() === JSON_ERROR_NONE && isset($veri['soru'], $veri['siklar'], $veri['dogru_cevap'])) {
                    // Doğru cevabı bir sonraki istek için session'da sakla
                    $_SESSION['current_question_answer'] = $veri['dogru_cevap'];
                    $_SESSION['start_time'] = time();

                    $response['success'] = true;
                    $response['message'] = 'Soru başarıyla oluşturuldu.';
                    // Sadece soruyu ve şıkları gönder, cevabı GÖNDERME
                    $response['data'] = [
                        'question' => $veri['soru'],
                        'siklar' => $veri['siklar'],
                        'kategori' => $kategori,
                        'difficulty' => $difficulty // Zorluğu yanıta ekle
                    ];
                } else {
                    $response['message'] = 'Soru formatı geçersiz.';
                }
            } else {
                $response['message'] = "API'den yanıt alınamadı.";
            }
        } catch (Exception $e) {
            $response['message'] = 'Soru alınırken sunucu hatası: ' . $e->getMessage();
        }
        break;

    case 'submit_answer':
        // Kullanıcının cevabını kontrol eder ve sonucu döndürür.
        $user_answer = $request_data['answer'] ?? null;
        if (!isset($_SESSION['current_question_answer'])) {
            $response['message'] = 'Kontrol edilecek aktif bir soru bulunamadı.';
            break;
        }

        $gecen_sure = time() - ($_SESSION['start_time'] ?? 0);
        $is_correct = false;
        $correct_answer = $_SESSION['current_question_answer'];

        if ($gecen_sure > 30) {
            $result_message = "Süre doldu!";
        } else {
            if ($user_answer === $correct_answer) {
                $result_message = "Tebrikler! Doğru cevap.";
                $is_correct = true;
            } else {
                $result_message = "Üzgünüm, yanlış cevap.";
            }
        }

        // Cevap kontrol edildikten sonra session'daki veriyi temizle
        unset($_SESSION['current_question_answer'], $_SESSION['start_time']);

        $response['success'] = true;
        $response['message'] = $result_message;
        // Sadece cevabın sonucunu ve doğru şıkkı döndür
        $response['data'] = [
            'is_correct' => $is_correct,
            'correct_answer' => $correct_answer
        ];
        break;
}

// Sonucu JSON olarak ekrana bas
echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
exit();
