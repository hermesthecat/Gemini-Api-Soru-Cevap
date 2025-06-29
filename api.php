<?php

/**
 * AI Bilgi Yarışması - Backend API (Stateless)
 *
 * Bu dosya, ön uçtan (JavaScript) gelen AJAX isteklerini işler.
 * Artık istatistik veya geçmiş tutmaz, sadece soru üretir, cevapları kontrol eder ve skorları yönetir.
 */

// Gerekli dosyaları ve başlangıç ayarlarını dahil et
require_once 'config.php';
require_once 'GeminiAPI.php';

// Oturumu başlat (sadece mevcut soru ve cevabı geçici olarak tutmak için)
session_start();

// Yanıt başlığını JSON olarak ayarla
header('Content-Type: application/json');

// --- Veritabanı Bağlantısı ---
$pdo = null;
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Veritabanı bağlantı hatasını JSON olarak döndür ve çık
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Veritabanı bağlantı hatası. Lütfen install.php dosyasını çalıştırdığınızdan emin olun.',
        'error' => $e->getMessage()
    ]);
    exit();
}

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
            // Rastgele soru tipi seç (%75 çoktan seçmeli, %25 doğru/yanlış)
            $tip = (rand(1, 100) <= 75) ? 'coktan_secmeli' : 'dogru_yanlis';
            $prompt = '';

            if ($tip === 'coktan_secmeli') {
                $prompt = "Lütfen {$kategori} kategorisinde {$difficulty} zorlukta bir soru hazırla. Yanıtı yalnızca şu JSON formatında, başka hiçbir metin olmadan ver:
                {
                    \"tip\": \"coktan_secmeli\",
                    \"soru\": \"(soru metni buraya)\",
                    \"siklar\": {
                        \"A\": \"(A şıkkı buraya)\",
                        \"B\": \"(B şıkkı buraya)\",
                        \"C\": \"(C şıkkı buraya)\",
                        \"D\": \"(D şıkkı buraya)\"
                    },
                    \"dogru_cevap\": \"(Doğru şıkkın harfi buraya, örneğin: A)\",
                    \"aciklama\": \"(Doğru cevabın neden doğru olduğuna dair 1-2 cümlelik açıklama)\"
                }";
            } else { // dogru_yanlis
                $prompt = "Lütfen {$kategori} kategorisinde {$difficulty} zorlukta, doğru ya da yanlış olarak cevaplanabilecek bir önerme hazırla. Yanıtı yalnızca şu JSON formatında, başka hiçbir metin olmadan ver:
                {
                    \"tip\": \"dogru_yanlis\",
                    \"soru\": \"(Önerme cümlesi buraya)\",
                    \"dogru_cevap\": \"(Doğru ya da Yanlış kelimelerinden biri)\",
                    \"aciklama\": \"(Önermenin neden doğru ya da yanlış olduğuna dair 1-2 cümlelik açıklama)\"
                }";
            }

            $yanit = $gemini->soruSor($prompt);

            if ($yanit) {
                $temiz_yanit = preg_replace('/^```json\s*|\s*```$/', '', trim($yanit));
                $veri = json_decode($temiz_yanit, true);

                if (json_last_error() === JSON_ERROR_NONE && isset($veri['tip'], $veri['soru'], $veri['dogru_cevap'], $veri['aciklama'])) {
                    // Tip çoktan seçmeli ise şıkların varlığını kontrol et
                    if ($veri['tip'] === 'coktan_secmeli' && !isset($veri['siklar'])) {
                        $response['message'] = 'API yanıtında çoktan seçmeli soru için şıklar bulunamadı.';
                        break;
                    }

                    // Cevabı ve açıklamayı bir sonraki istek için session'da sakla
                    $_SESSION['current_question_answer'] = $veri['dogru_cevap'];
                    $_SESSION['current_question_explanation'] = $veri['aciklama']; // Açıklamayı sakla
                    $_SESSION['start_time'] = time();

                    $response['success'] = true;
                    $response['message'] = 'Soru başarıyla oluşturuldu.';

                    // Ön uca gönderilecek veriyi hazırla (cevabı ve açıklamayı GÖNDERME)
                    $responseData = [
                        'tip' => $veri['tip'],
                        'question' => $veri['soru'],
                        'kategori' => $kategori,
                        'difficulty' => $difficulty
                    ];

                    if ($veri['tip'] === 'coktan_secmeli') {
                        $responseData['siklar'] = $veri['siklar'];
                    }

                    $response['data'] = $responseData;
                } else {
                    $response['message'] = 'Soru formatı geçersiz veya eksik alanlar var.';
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
        $explanation = $_SESSION['current_question_explanation'];

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
        unset($_SESSION['current_question_answer'], $_SESSION['start_time'], $_SESSION['current_question_explanation']);

        $response['success'] = true;
        $response['message'] = $result_message;
        // Sonucu, doğru şıkkı ve açıklamayı döndür
        $response['data'] = [
            'is_correct' => $is_correct,
            'correct_answer' => $correct_answer,
            'explanation' => $explanation,
            'time_left' => 30 - $gecen_sure > 0 ? 30 - $gecen_sure : 0
        ];
        break;

    case 'update_score':
        // Kullanıcının skorunu veritabanında günceller veya yeni kayıt oluşturur.
        $user_id = $request_data['user_id'] ?? null;
        $username = $request_data['username'] ?? 'Anonim Oyuncu';
        $score = $request_data['score'] ?? 0;

        if (!$user_id) {
            $response['message'] = 'Kullanıcı ID\'si eksik.';
            break;
        }

        try {
            // INSERT ... ON DUPLICATE KEY UPDATE kullanarak hem ekleme hem güncelleme yap
            $sql = "
                INSERT INTO leaderboard (user_id, username, score) 
                VALUES (:user_id, :username, :score) 
                ON DUPLICATE KEY UPDATE score = :score, username = :username
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':user_id' => $user_id,
                ':username' => $username,
                ':score' => $score
            ]);

            $response['success'] = true;
            $response['message'] = 'Skor başarıyla güncellendi.';
        } catch (PDOException $e) {
            $response['message'] = 'Skor güncellenirken bir hata oluştu: ' . $e->getMessage();
        }
        break;

    case 'get_leaderboard':
        // Liderlik tablosundaki ilk 10 kişiyi getirir.
        try {
            $stmt = $pdo->query("SELECT username, score FROM leaderboard ORDER BY score DESC, last_updated ASC LIMIT 10");
            $leaderboard = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $response['success'] = true;
            $response['message'] = 'Liderlik tablosu başarıyla alındı.';
            $response['data'] = $leaderboard;
        } catch (PDOException $e) {
            $response['message'] = 'Liderlik tablosu alınırken bir hata oluştu: ' . $e->getMessage();
        }
        break;
}

// Sonucu JSON olarak ekrana bas
echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
exit();
