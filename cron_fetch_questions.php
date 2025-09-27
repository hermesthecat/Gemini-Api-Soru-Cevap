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
$max_batch_size = 25; // Maksimum batch size (dinamik olacak)

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

    // Eksik soru olan kategori/zorluk kombinasyonlarını topla
    $needed_combinations = [];
    foreach ($categories as $category_key => $category_name) {
        foreach ($difficulties as $difficulty) {
            // Mevcut soru sayısını kontrol et
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM questions WHERE category = ? AND difficulty = ?");
            $stmt->execute([$category_key, $difficulty]);
            $existing_count = $stmt->fetchColumn();

            if ($existing_count < $min_questions_per_category) {
                $needed = $min_questions_per_category - $existing_count;
                $needed_combinations[] = [
                    'category_key' => $category_key,
                    'category_name' => $category_name,
                    'difficulty' => $difficulty,
                    'existing_count' => $existing_count,
                    'needed' => $needed
                ];
                logMessage("$category_name ($difficulty): $existing_count/$min_questions_per_category - $needed soru eksik");
            } else {
                logMessage("$category_name ($difficulty): Yeterli soru mevcut ($existing_count)");
            }
        }
    }

    // Eğer eksik kombinasyon varsa tek seferde hepsini çek
    if (!empty($needed_combinations)) {
        logMessage("=== TEK API ÇAĞRISIYLA " . count($needed_combinations) . " KOMBİNASYON İÇİN SORU ÇEKİLİYOR ===");

        $all_questions = fetchAllQuestionsFromAI($geminiAPI, $needed_combinations);

        if (!empty($all_questions)) {
            $total_saved = 0;
            foreach ($all_questions as $combo_key => $questions) {
                if (!empty($questions)) {
                    $combination = $needed_combinations[$combo_key];
                    $saved_count = saveQuestionsToDatabase($pdo, $questions, $combination['category_key'], $combination['difficulty']);
                    $total_saved += $saved_count;
                    logMessage("✅ {$combination['category_name']} ({$combination['difficulty']}): $saved_count soru kaydedildi");
                }
            }
            logMessage("=== TOPLAM $total_saved SORU BAŞARIYLA KAYDEDİLDİ ===");
        } else {
            logMessage("❌ HATA: Toplu soru çekimi başarısız oldu");
        }
    } else {
        logMessage("✅ Tüm kategorilerde yeterli soru mevcut - işlem gerekmiyor");
    }

    logMessage("=== Cron Tamamlandı ===");

} catch (Exception $e) {
    logMessage("HATA: " . $e->getMessage());
    exit(1);
}

/**
 * AI'den toplu soru çekme fonksiyonu - Tüm kombinasyonlar tek seferde
 */
function fetchAllQuestionsFromAI($geminiAPI, $needed_combinations) {
    if (empty($needed_combinations)) {
        return [];
    }

    // JSON formatını hazırla
    $prompt = "Lütfen aşağıdaki kategori ve zorluk seviyelerine göre çoktan seçmeli sorular oluştur. Her kombinasyon için 10'ar soru yeterli.\n\n";

    // Kombinasyonları listeye
    foreach ($needed_combinations as $index => $combo) {
        $prompt .= "- {$combo['category_name']} kategorisi, {$combo['difficulty']} seviye: 10 adet soru\n";
    }

    $prompt .= "\nLütfen şu JSON formatını kullan:\n{\n";
    foreach ($needed_combinations as $index => $combo) {
        $safe_key = "combo_$index"; // Array index olarak kullanacağız
        $prompt .= "  \"$safe_key\": [\n";
        $prompt .= "    {\n";
        $prompt .= "      \"question\": \"Soru metni?\",\n";
        $prompt .= "      \"options\": [\"A) Seçenek 1\", \"B) Seçenek 2\", \"C) Seçenek 3\", \"D) Seçenek 4\"],\n";
        $prompt .= "      \"correct_answer\": \"A\",\n";
        $prompt .= "      \"explanation\": \"Açıklama\"\n";
        $prompt .= "    }\n";
        $prompt .= "    // ... 10 adet soru ({$combo['category_name']} - {$combo['difficulty']})\n";
        $prompt .= "  ]" . ($index < count($needed_combinations) - 1 ? "," : "") . "\n";
    }
    $prompt .= "}\n\nSADECE JSON formatında yanıt ver, başka açıklama ekleme. Her kombinasyon için tam 10 adet farklı soru oluştur.";

    try {
        logMessage("📝 Toplu soru prompt'u hazırlandı (" . strlen($prompt) . " karakter)");
        $response = $geminiAPI->soruSor($prompt);

        // JSON backtick'lerini temizle
        $clean_response = preg_replace('/^```json\s*|```$/m', '', trim($response));

        $decoded = json_decode($clean_response, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            $total_questions = 0;
            foreach ($decoded as $key => $questions) {
                if (is_array($questions)) {
                    $total_questions += count($questions);
                }
            }
            logMessage("📦 Toplu AI'den $total_questions soru başarıyla alındı");
            return $decoded;
        }

        logMessage("❌ JSON parse hatası: " . json_last_error_msg() . " | Raw response ilk 500 char: " . substr($clean_response, 0, 500) . "...");
        return [];

    } catch (Exception $e) {
        logMessage("❌ Toplu AI API Hatası: " . $e->getMessage());
        return [];
    }
}

/**
 * AI'den tekli soru çekme fonksiyonu (fallback olarak kalıyor)
 */
function fetchQuestionsFromAI($geminiAPI, $category, $difficulty, $count) {
    $difficulty_map = [
        'kolay' => 'kolay',
        'orta' => 'orta',
        'zor' => 'zor'
    ];

    // Güvenlik: Kategori adını sanitize et
    $safe_category = htmlspecialchars($category, ENT_QUOTES, 'UTF-8');
    $safe_difficulty = $difficulty_map[$difficulty] ?? 'orta';

    $prompt = "Lütfen $safe_category kategorisinde $safe_difficulty seviyede $count adet çoktan seçmeli soru oluştur.

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
            logMessage("AI'den " . count($decoded['questions']) . " soru başarıyla alındı.");
            return $decoded['questions'];
        }

        logMessage("JSON parse hatası: " . json_last_error_msg() . " | Raw response: " . substr($clean_response, 0, 200) . "...");
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