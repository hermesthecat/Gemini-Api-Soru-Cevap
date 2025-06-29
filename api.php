<?php

/**
 * AI Bilgi Yarışması - Backend API
 *
 * Bu dosya, ön uçtan (JavaScript) gelen AJAX isteklerini işler.
 * Gelen 'action' parametresine göre farklı işlemler yapar ve sonuçları JSON formatında döndürür.
 */

// Gerekli dosyaları ve başlangıç ayarlarını dahil et
require_once 'config.php';
require_once 'GeminiAPI.php';

// Oturumu başlat
session_start();

// Yanıt başlığını JSON olarak ayarla
header('Content-Type: application/json');

// --- OTURUM BAŞLATMA ---
// Oturumda istatistikler ve geçmiş dizileri yoksa, boş olarak oluşturulur.
if (!isset($_SESSION['stats'])) {
    $_SESSION['stats'] = ['total_questions' => 0, 'correct_answers' => 0];
}
if (!isset($_SESSION['history'])) {
    $_SESSION['history'] = [];
}

// Gelen isteğin içeriğini al (POST istekleri için)
$request_data = json_decode(file_get_contents('php://input'), true);
$action = $_GET['action'] ?? $request_data['action'] ?? null;

// Yanıt için standart bir yapı oluştur
$response = [
    'success' => false,
    'message' => 'Geçersiz istek',
    'data' => null
];

// --- AKSİYON YÖNLENDİRİCİSİ ---
// Gelen 'action' parametresine göre ilgili fonksiyonu çalıştır
switch ($action) {
    case 'get_initial_state':
        // Sayfa ilk yüklendiğinde mevcut durumu (istatistikler, geçmiş) gönderir.
        $response['success'] = true;
        $response['message'] = 'Başlangıç durumu başarıyla alındı.';
        $response['data'] = [
            'stats' => $_SESSION['stats'],
            'history' => $_SESSION['history']
        ];
        break;

    case 'get_question':
        // Yeni bir soru oluşturur ve döndürür.
        $kategori = $request_data['kategori'] ?? 'genel kültür';
        if (empty($kategori)) {
            $response['message'] = 'Kategori belirtilmedi.';
            break;
        }

        // Mevcut soru bilgilerini temizle
        unset($_SESSION['current_question'], $_SESSION['siklar'], $_SESSION['dogru_cevap'], $_SESSION['start_time']);

        $_SESSION['kategori'] = $kategori;
        $gemini = new GeminiAPI(GEMINI_API_KEY);

        try {
            $prompt = "Lütfen {$kategori} kategorisinde orta zorlukta bir soru hazırla. Yanıtı yalnızca şu JSON formatında, başka hiçbir metin olmadan ver:
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
                    $_SESSION['current_question'] = $veri['soru'];
                    $_SESSION['siklar'] = $veri['siklar'];
                    $_SESSION['dogru_cevap'] = $veri['dogru_cevap'];
                    $_SESSION['start_time'] = time();

                    $response['success'] = true;
                    $response['message'] = 'Soru başarıyla oluşturuldu.';
                    // Sadece soruyu ve şıkları gönder, cevabı session'da tut
                    $response['data'] = [
                        'question' => $veri['soru'],
                        'siklar' => $veri['siklar'],
                        'kategori' => $kategori
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
        if (!$user_answer || !isset($_SESSION['current_question'])) {
            $response['message'] = 'Geçersiz cevap veya mevcut soru yok.';
            break;
        }

        $gecen_sure = time() - $_SESSION['start_time'];
        $is_correct = false;
        $correct_answer = $_SESSION['dogru_cevap'];

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

        // İstatistikleri güncelle
        $_SESSION['stats']['total_questions']++;
        if ($is_correct) {
            $_SESSION['stats']['correct_answers']++;
        }

        // Geçmişe ekle
        $history_item = [
            'question' => $_SESSION['current_question'],
            'user_answer' => $user_answer,
            'correct_answer' => $correct_answer,
            'is_correct' => $is_correct,
            'siklar' => $_SESSION['siklar']
        ];
        array_unshift($_SESSION['history'], $history_item);
        if (count($_SESSION['history']) > 5) {
            array_pop($_SESSION['history']);
        }

        // Soru bilgilerini temizle
        unset($_SESSION['current_question'], $_SESSION['siklar'], $_SESSION['dogru_cevap'], $_SESSION['start_time']);

        $response['success'] = true;
        $response['message'] = $result_message;
        $response['data'] = [
            'is_correct' => $is_correct,
            'correct_answer' => $correct_answer,
            'stats' => $_SESSION['stats'],
            'history' => $_SESSION['history']
        ];
        break;

    case 'reset_stats':
        // İstatistikleri ve geçmişi sıfırlar.
        $_SESSION['stats'] = ['total_questions' => 0, 'correct_answers' => 0];
        $_SESSION['history'] = [];

        $response['success'] = true;
        $response['message'] = 'İstatistikler sıfırlandı.';
        $response['data'] = [
            'stats' => $_SESSION['stats'],
            'history' => $_SESSION['history']
        ];
        break;
}

// Sonucu JSON olarak ekrana bas
echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
exit();
