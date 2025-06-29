<?php

class GameController
{
    private $pdo;
    private $gemini;

    public function __construct($pdo, GeminiAPI $gemini)
    {
        $this->pdo = $pdo;
        $this->gemini = $gemini;
    }

    public function getQuestion($data)
    {
        unset($_SESSION['current_question_answer'], $_SESSION['current_question_explanation'], $_SESSION['start_time']);

        $kategori = $data['kategori'] ?? 'genel kültür';
        $difficulty = $data['difficulty'] ?? 'orta';

        $tip = (rand(1, 100) <= 75) ? 'coktan_secmeli' : 'dogru_yanlis';
        $prompt = self::generatePrompt($tip, $kategori, $difficulty);
        $yanit = $this->gemini->soruSor($prompt);

        if (!$yanit) {
            throw new Exception("Gemini API'sinden yanıt alınamadı.");
        }

        $temiz_yanit = preg_replace('/^```json\s*|\s*```$/', '', trim($yanit));
        $veri = json_decode($temiz_yanit, true);

        if (json_last_error() !== JSON_ERROR_NONE || !$this->isQuestionValid($veri)) {
            error_log("Invalid JSON from Gemini: " . $temiz_yanit);
            throw new Exception('API\'den gelen soru formatı geçersiz veya eksik alanlar var.');
        }

        $_SESSION['current_question_answer'] = $veri['dogru_cevap'];
        $_SESSION['current_question_explanation'] = $veri['aciklama'];
        $_SESSION['start_time'] = time();
        $_SESSION['current_question_difficulty'] = $difficulty;

        return [
            'success' => true,
            'data' => [
                'tip' => $veri['tip'],
                'question' => $veri['soru'],
                'siklar' => $veri['siklar'] ?? null,
                'kategori' => $kategori,
                'difficulty' => $difficulty,
            ]
        ];
    }

    public function submitAnswer($data)
    {
        if (!isset($_SESSION['current_question_answer'])) {
            http_response_code(400);
            return ['success' => false, 'message' => 'Aktif soru bulunamadı. Lütfen yeni bir soru alın.'];
        }

        $user_answer = $data['answer'] ?? null;
        $kategori = $data['kategori'] ?? 'bilinmiyor';
        $difficulty = $_SESSION['current_question_difficulty'] ?? 'orta';
        $gecen_sure = time() - ($_SESSION['start_time'] ?? time());
        $is_correct = ($user_answer === $_SESSION['current_question_answer']);

        if (!$is_correct) {
            $_SESSION['consecutive_correct'] = 0;
        }

        $this->updateStatsAndScore($kategori, $difficulty, $gecen_sure, $is_correct);

        $yeni_basarimlar = $is_correct ? $this->checkAchievements($kategori, $difficulty) : [];

        // Görev ilerlemesini kontrol et (sadece doğru cevapta)
        $yeni_gorevler = [];
        if ($is_correct) {
            require_once 'QuestController.php'; // Statik metot için dahil et
            $yeni_gorevler = QuestController::checkAndUpdateQuestProgress($this->pdo, $_SESSION['user_id'], 'solve_category', $kategori);
            $yeni_gorevler_zorluk = QuestController::checkAndUpdateQuestProgress($this->pdo, $_SESSION['user_id'], 'solve_difficulty', $difficulty);
            $yeni_gorevler = array_merge($yeni_gorevler, $yeni_gorevler_zorluk);
        }

        $response = [
            'success' => true,
            'data' => [
                'is_correct' => $is_correct,
                'correct_answer' => $_SESSION['current_question_answer'],
                'explanation' => $_SESSION['current_question_explanation'],
                'new_achievements' => $yeni_basarimlar,
                'completed_quests' => $yeni_gorevler
            ]
        ];

        unset($_SESSION['current_question_answer'], $_SESSION['current_question_explanation'], $_SESSION['start_time'], $_SESSION['current_question_difficulty']);
        return $response;
    }

    // --- Yardımcı Fonksiyonlar ---

    private function updateStatsAndScore($kategori, $difficulty, $gecen_sure, $is_correct)
    {
        $user_id = $_SESSION['user_id'];
        $puan = 0;

        if ($is_correct) {
            $puan = 10 + max(0, 30 - $gecen_sure);
            $_SESSION['consecutive_correct'] = ($_SESSION['consecutive_correct'] ?? 0) + 1;
        }

        try {
            $this->pdo->beginTransaction();

            // İstatistiği güncelle (yeni sütunlarla)
            $sql_stat = "
                INSERT INTO user_stats (user_id, category, difficulty, total_questions, correct_answers, total_time_spent) 
                VALUES (?, ?, ?, 1, ?, ?)
                ON DUPLICATE KEY UPDATE 
                    total_questions = total_questions + 1, 
                    correct_answers = correct_answers + VALUES(correct_answers),
                    total_time_spent = total_time_spent + VALUES(total_time_spent)";
            $stmt_stat = $this->pdo->prepare($sql_stat);
            $stmt_stat->execute([$user_id, $kategori, $difficulty, $is_correct ? 1 : 0, $gecen_sure]);

            // Skoru güncelle (sadece doğru cevapta)
            if ($is_correct && $puan > 0) {
                $sql_score = "UPDATE leaderboard SET score = score + ? WHERE user_id = ?";
                $stmt_score = $this->pdo->prepare($sql_score);
                $stmt_score->execute([$puan, $user_id]);
            }

            $this->pdo->commit();
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            // Hatayı tekrar fırlatarak merkezi hata yöneticisinin yakalamasını sağla
            throw $e;
        }
    }

    private function checkAchievements($kategori, $difficulty)
    {
        $user_id = $_SESSION['user_id'];
        $yeni_basarimlar = [];
        $mevcut_basarimlar = [];

        // Mevcut başarımları al
        $stmt_ach = $this->pdo->prepare("SELECT achievement_key FROM user_achievements WHERE user_id = ?");
        $stmt_ach->execute([$user_id]);
        $mevcut_basarimlar = $stmt_ach->fetchAll(PDO::FETCH_COLUMN);

        $grant_achievement = function ($key) use (&$yeni_basarimlar, &$mevcut_basarimlar, $user_id) {
            if (!in_array($key, $mevcut_basarimlar)) {
                $stmt = $this->pdo->prepare("INSERT INTO user_achievements (user_id, achievement_key) VALUES (?, ?)");
                $stmt->execute([$user_id, $key]);
                $yeni_basarimlar[] = $key;
                $mevcut_basarimlar[] = $key;
            }
        };

        // Seri Galibi
        if (isset($_SESSION['consecutive_correct']) && $_SESSION['consecutive_correct'] >= 25) {
            $grant_achievement('seri_galibi_25');
        } elseif (isset($_SESSION['consecutive_correct']) && $_SESSION['consecutive_correct'] >= 10) {
            $grant_achievement('seri_galibi_10');
        }

        // Hız Tutkunu
        if (isset($_SESSION['start_time']) && (time() - $_SESSION['start_time']) <= 5) {
            $grant_achievement('hiz_tutkunu');
        }

        // Zorlu Rakip
        if ($difficulty === 'zor') {
            $stmt_diff_check = $this->pdo->prepare("SELECT SUM(correct_answers) FROM user_stats WHERE user_id = ? AND difficulty = 'zor'");
            $stmt_diff_check->execute([$user_id]);
            if ($stmt_diff_check->fetchColumn() >= 10) {
                $grant_achievement('zorlu_rakip');
            }
        }

        // Kategori Uzmanı ve Kusursuz
        $stmt_cat = $this->pdo->prepare("SELECT correct_answers, total_questions FROM user_stats WHERE user_id = ? AND category = ?");
        $stmt_cat->execute([$user_id, $kategori]);
        if ($cat_stats = $stmt_cat->fetch(PDO::FETCH_ASSOC)) {
            if ($cat_stats['correct_answers'] >= 20) $grant_achievement("uzman_{$kategori}");
            if ($cat_stats['total_questions'] >= 10 && $cat_stats['correct_answers'] == $cat_stats['total_questions']) $grant_achievement("kusursuz_{$kategori}");
        }

        if (!empty($yeni_basarimlar)) {
            // Anahtarları kullanarak başarımların tüm detaylarını veritabanından çek
            $placeholders = implode(',', array_fill(0, count($yeni_basarimlar), '?'));
            $stmt_details = $this->pdo->prepare("SELECT achievement_key, name, description, icon, color FROM achievements WHERE achievement_key IN ($placeholders)");
            $stmt_details->execute($yeni_basarimlar);
            return $stmt_details->fetchAll(PDO::FETCH_ASSOC);
        }

        return [];
    }

    public static function generatePrompt($tip, $kategori, $difficulty, $adet = 1)
    {
        $format_aciklama_coktan_secmeli = '{\"tip\": \"coktan_secmeli\", \"soru\": \"(soru metni buraya)\", \"siklar\": {\"A\": \"(A şıkkı buraya)\", \"B\": \"(B şıkkı buraya)\", \"C\": \"(C şıkkı buraya)\", \"D\": \"(D şıkkı buraya)\"}, \"dogru_cevap\": \"(Doğru şıkkın harfi buraya, örneğin: A)\", \"aciklama\": \"(Doğru cevabın neden doğru olduğuna dair 1-2 cümlelik açıklama)\"}';
        $format_aciklama_dogru_yanlis = '{\"tip\": \"dogru_yanlis\", \"soru\": \"(Önerme cümlesi buraya)\", \"dogru_cevap\": \"(Doğru ya da Yanlış kelimelerinden biri)\", \"aciklama\": \"(Önermenin neden doğru ya da yanlış olduğuna dair 1-2 cümlelik açıklama)\"}';

        if ($adet > 1) {
            return "Lütfen {$kategori} kategorisinde {$difficulty} zorlukta, birbirinden farklı {$adet} adet soru hazırla. Soruların yarısı çoktan seçmeli, diğer yarısı doğru/yanlış formatında olabilir. Yanıtı yalnızca geçerli bir JSON dizisi formatında, başka hiçbir metin olmadan ver. Örnek format: [{$format_aciklama_coktan_secmeli}, {$format_aciklama_dogru_yanlis}]";
        }

        if ($tip === 'coktan_secmeli') {
            return "Lütfen {$kategori} kategorisinde {$difficulty} zorlukta bir soru hazırla. Yanıtı yalnızca şu JSON formatında, başka hiçbir metin olmadan ver: {$format_aciklama_coktan_secmeli}";
        } else { // dogru_yanlis
            return "Lütfen {$kategori} kategorisinde {$difficulty} zorlukta, doğru ya da yanlış olarak cevaplanabilecek bir önerme hazırla. Yanıtı yalnızca şu JSON formatında, başka hiçbir metin olmadan ver: {$format_aciklama_dogru_yanlis}";
        }
    }

    private function isQuestionValid($veri)
    {
        if (!isset($veri['tip'], $veri['soru'], $veri['dogru_cevap'], $veri['aciklama'])) {
            return false;
        }
        if ($veri['tip'] === 'coktan_secmeli' && !isset($veri['siklar'])) {
            return false;
        }
        return true;
    }
}
