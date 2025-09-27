<?php
/**
 * Cron Script: AI'den Soru Çekme ve Veritabanına Kaydetme
 *
 * Bu script periyodik olarak AI'den soru çekerek veritabanına kaydeder.
 * Kullanım: php cron_fetch_questions.php
 * Cron: 0,30 * * * * /y/xampp/php/php.exe /y/xampp/htdocs/ai-soru-cevap/cron_fetch_questions.php
 */

require_once 'config.php';
require_once 'GeminiAPI.php';

// Cron mode check
$is_cli = php_sapi_name() === 'cli';
if (!$is_cli) {
    // Web'den erişim engellendi
    http_response_code(403);
    exit('Bu script sadece CLI\'dan çalıştırılabilir.');
}

// Logging fonksiyonu
function logMessage($message) {
    $timestamp = date('Y-m-d H:i:s');
    echo "[$timestamp] $message\n";
}

// Zorluk seviyeleri
$difficulties = ['kolay', 'orta', 'zor'];

// Her kategoride minimum soru sayısı
$min_questions_per_category = 50;
$batch_size = 5; // Her seferde kaç soru çekilecek

logMessage("=== AI Soru Çekme Cron Başlatıldı ===");

try {
    // Veritabanı bağlantısı
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Gemini API'yi başlat
    $geminiAPI = new GeminiAPI(null, $pdo);

    // Kategorileri veritabanından çek
    $stmt_categories = $pdo->prepare("SELECT category_key, category_name FROM categories WHERE is_active = 1");
    $stmt_categories->execute();
    $categories = $stmt_categories->fetchAll(PDO::FETCH_KEY_PAIR);

    if (empty($categories)) {
        logMessage("HATA: Aktif kategori bulunamadı!");
        exit(1);
    }

    // Her kategori ve zorluk için kontrol
    foreach ($categories as $category_key => $category_name) {
        foreach ($difficulties as $difficulty) {
            // Mevcut soru sayısını kontrol et
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM questions WHERE category = ? AND difficulty = ?");
            $stmt->execute([$category_key, $difficulty]);
            $existing_count = $stmt->fetchColumn();

            if ($existing_count < $min_questions_per_category) {
                $needed = $min_questions_per_category - $existing_count;
                $to_fetch = min($needed, $batch_size);

                logMessage("$category_name ($difficulty): $existing_count/$min_questions_per_category - $to_fetch soru çekiliyor...");

                // AI'den sorular çek
                $questions = fetchQuestionsFromAI($geminiAPI, $category_name, $difficulty, $to_fetch);

                if (!empty($questions)) {
                    // Soruları veritabanına kaydet
                    $saved_count = saveQuestionsToDatabase($pdo, $questions, $category_key, $difficulty);
                    logMessage("$saved_count soru başarıyla kaydedildi.");
                } else {
                    logMessage("Soru çekilemedi - API hatası olabilir.");
                }

                // API rate limit için bekle
                sleep(2);
            } else {
                logMessage("$category_name ($difficulty): Yeterli soru mevcut ($existing_count)");
            }
        }
    }

    logMessage("=== Cron Tamamlandı ===");

} catch (Exception $e) {
    logMessage("HATA: " . $e->getMessage());
    exit(1);
}

/**
 * AI'den soru çekme fonksiyonu
 */
function fetchQuestionsFromAI($geminiAPI, $category, $difficulty, $count) {
    $difficulty_map = [
        'kolay' => 'kolay',
        'orta' => 'orta',
        'zor' => 'zor'
    ];

    $prompt = "Lütfen $category kategorisinde {$difficulty_map[$difficulty]} seviyede $count adet çoktan seçmeli soru oluştur.

Her soru için şu JSON formatını kullan:
{
  \"questions\": [
    {
      \"question\": \"İlk soru metni?\",
      \"options\": [\"A) Seçenek 1\", \"B) Seçenek 2\", \"C) Seçenek 3\", \"D) Seçenek 4\"],
      \"correct_answer\": \"A\",
      \"explanation\": \"İlk soru açıklaması\"
    },
    {
      \"question\": \"İkinci soru metni?\",
      \"options\": [\"A) Seçenek 1\", \"B) Seçenek 2\", \"C) Seçenek 3\", \"D) Seçenek 4\"],
      \"correct_answer\": \"B\",
      \"explanation\": \"İkinci soru açıklaması\"
    }
  ]
}

$count adet farklı soru oluştur. SADECE JSON formatında yanıt ver, başka açıklama ekleme.";

    try {
        $response = $geminiAPI->soruSor($prompt);

        // JSON backtick'lerini temizle
        $clean_response = preg_replace('/^```json\s*|```$/m', '', trim($response));

        $decoded = json_decode($clean_response, true);

        if (json_last_error() === JSON_ERROR_NONE && isset($decoded['questions'])) {
            return $decoded['questions'];
        }

        logMessage("JSON parse hatası: " . json_last_error_msg());
        return [];

    } catch (Exception $e) {
        logMessage("AI API Hatası: " . $e->getMessage());
        return [];
    }
}

/**
 * Soruları veritabanına kaydetme fonksiyonu
 */
function saveQuestionsToDatabase($pdo, $questions, $category, $difficulty) {
    $saved_count = 0;

    $stmt = $pdo->prepare("
        INSERT INTO questions (question_text, question_type, options, correct_answer, explanation, category, difficulty)
        VALUES (?, 'coktan_secmeli', ?, ?, ?, ?, ?)
    ");

    foreach ($questions as $question) {
        try {
            // Veri doğrulama
            if (empty($question['question']) || empty($question['options']) ||
                empty($question['correct_answer']) || empty($question['explanation'])) {
                logMessage("Eksik veri - soru atlandı");
                continue;
            }

            // Options'ı JSON olarak kaydet
            $options_json = json_encode($question['options'], JSON_UNESCAPED_UNICODE);

            $stmt->execute([
                $question['question'],
                $options_json,
                $question['correct_answer'],
                $question['explanation'],
                $category,
                $difficulty
            ]);

            $saved_count++;

        } catch (PDOException $e) {
            logMessage("Veritabanı hatası: " . $e->getMessage());
        }
    }

    return $saved_count;
}