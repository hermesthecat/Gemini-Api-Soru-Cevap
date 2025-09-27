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

        $kategori = $data['kategori'] ?? 'genel_kultur';
        $difficulty = $data['difficulty'] ?? 'orta';

        // Önce veritabanından soru çekmeyi dene
        $question = $this->getQuestionFromDatabase($kategori, $difficulty);

        if ($question) {
            // Veritabanından soru bulundu
            $_SESSION['current_question_answer'] = $question['correct_answer'];
            $_SESSION['current_question_explanation'] = $question['explanation'];
            $_SESSION['start_time'] = time();
            $_SESSION['current_question_difficulty'] = $difficulty;
            $_SESSION['current_question_id'] = $question['id'];

            // Options'ı parse et
            $options = json_decode($question['options'], true);

            return [
                'success' => true,
                'data' => [
                    'tip' => $question['question_type'],
                    'question' => $question['question_text'],
                    'siklar' => $options,
                    'kategori' => $kategori,
                    'difficulty' => $difficulty,
                    'source' => 'database'
                ]
            ];
        }

        // Veritabanında soru yoksa AI'dan çek (fallback)
        return $this->getQuestionFromAI($kategori, $difficulty);
    }

    /**
     * Veritabanından rastgele soru çeker
     */
    private function getQuestionFromDatabase($kategori, $difficulty)
    {
        try {
            // Kategori adını normalize et
            $kategori = str_replace(' ', '_', strtolower($kategori));
            $kategori = str_replace('ğ', 'g', $kategori);
            $kategori = str_replace('ş', 's', $kategori);
            $kategori = str_replace('ç', 'c', $kategori);
            $kategori = str_replace('ı', 'i', $kategori);
            $kategori = str_replace('ö', 'o', $kategori);
            $kategori = str_replace('ü', 'u', $kategori);

            $stmt = $this->pdo->prepare("
                SELECT id, question_text, question_type, options, correct_answer, explanation
                FROM questions
                WHERE category = ? AND difficulty = ?
                ORDER BY RAND()
                LIMIT 1
            ");

            $stmt->execute([$kategori, $difficulty]);
            $question = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($question) {
                // Usage count'u artır
                $update_stmt = $this->pdo->prepare("UPDATE questions SET usage_count = usage_count + 1 WHERE id = ?");
                $update_stmt->execute([$question['id']]);

                return $question;
            }

            return null;

        } catch (PDOException $e) {
            error_log("Database question fetch error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * AI'dan soru çeker (fallback method)
     */
    private function getQuestionFromAI($kategori, $difficulty)
    {
        $tip = (rand(1, 100) <= 75) ? 'coktan_secmeli' : 'dogru_yanlis';
        $prompt = self::generatePrompt($tip, $kategori, $difficulty);
        $yanit = $this->gemini->soruSor($prompt);

        if (!$yanit) {
            throw new Exception("Veritabanında soru bulunamadı ve Gemini API'sinden yanıt alınamadı.");
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
                'source' => 'ai'
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

    public function useLifeline($data)
    {
        $user_id = $_SESSION['user_id'];
        $type = $data['type'] ?? '';

        $lifeline_map = [
            'fiftyFifty' => 'lifeline_fifty_fifty',
            'extraTime' => 'lifeline_extra_time',
            'pass' => 'lifeline_pass'
        ];

        if (!array_key_exists($type, $lifeline_map) || ($_SESSION['lifelines'][$type] ?? 0) <= 0) {
            return ['success' => false, 'message' => 'Geçersiz veya tükenmiş joker.'];
        }

        $column_name = $lifeline_map[$type];

        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare("UPDATE leaderboard SET $column_name = $column_name - 1 WHERE user_id = ? AND $column_name > 0");
            $stmt->execute([$user_id]);

            if ($stmt->rowCount() === 0) {
                $this->pdo->rollBack();
                return ['success' => false, 'message' => 'Joker kullanılamadı veya tükendi.'];
            }

            $_SESSION['lifelines'][$type]--;
            $this->pdo->commit();

            return ['success' => true, 'data' => ['lifelines' => $_SESSION['lifelines']]];
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("Lifeline use error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Joker kullanılırken bir hata oluştu.'];
        }
    }

    // --- Yardımcı Fonksiyonlar ---

    private function updateStatsAndScore($kategori, $difficulty, $gecen_sure, $is_correct)
    {
        $user_id = $_SESSION['user_id'];
        $puan = 0;
        $coins_earned = 0;

        if ($is_correct) {
            $puan = 10 + max(0, 30 - $gecen_sure);
            $coins_earned = 5; // Her doğru cevap için 5 jeton
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

            // Skoru ve jetonu güncelle (sadece doğru cevapta)
            if ($is_correct && ($puan > 0 || $coins_earned > 0)) {
                // Puanı leaderboard'a, coin'i users'a ekle
                $stmt_score = $this->pdo->prepare("UPDATE leaderboard SET score = score + ? WHERE user_id = ?");
                $stmt_score->execute([$puan, $user_id]);

                $stmt_coins = $this->pdo->prepare("UPDATE users SET coins = coins + ? WHERE id = ?");
                $stmt_coins->execute([$coins_earned, $user_id]);

                // Session'daki jeton miktarını da güncelle
                $_SESSION['user_coins'] = ($_SESSION['user_coins'] ?? 0) + $coins_earned;
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

        // İlk Adım, Puan Avcısı ve Gece Kuşu kontrolü
        try {
            // 1. İlk Adım - İlk doğru cevap
            $stmt_first_correct = $this->pdo->prepare("SELECT SUM(correct_answers) as total_correct FROM user_stats WHERE user_id = ?");
            $stmt_first_correct->execute([$user_id]);
            $total_correct = $stmt_first_correct->fetchColumn() ?: 0;
            if ($total_correct >= 1) {
                $grant_achievement('ilk_adim');
            }

            // 2. Puan Avcısı - 1000 puan
            $stmt_score = $this->pdo->prepare("SELECT score FROM leaderboard WHERE user_id = ?");
            $stmt_score->execute([$user_id]);
            $current_score = $stmt_score->fetchColumn() ?: 0;
            if ($current_score >= 1000) {
                $grant_achievement('puan_avcisi_1000');
            }

            // 3. Gece Kuşu - Gece saatlerinde oyun oynama (00:00-04:00)
            $current_hour = (int)date('H');
            if ($current_hour >= 0 && $current_hour <= 4) {
                $grant_achievement('gece_kusu');
            }

            // 4. Meraklı - Tüm kategorilerde en az 1 soru çözmek
            $stmt_categories = $this->pdo->prepare("SELECT COUNT(DISTINCT category) as unique_categories FROM user_stats WHERE user_id = ? AND total_questions > 0");
            $stmt_categories->execute([$user_id]);
            $unique_categories = $stmt_categories->fetchColumn() ?: 0;

            // Toplam kategori sayısını al
            $stmt_total_categories = $this->pdo->prepare("SELECT COUNT(*) FROM categories");
            $stmt_total_categories->execute();
            $total_categories = $stmt_total_categories->fetchColumn() ?: 0;

            if ($unique_categories >= $total_categories && $total_categories > 0) {
                $grant_achievement('merakli');
            }

            // 5. Koleksiyoncu - 10 başarım toplamak
            $stmt_achievement_count = $this->pdo->prepare("SELECT COUNT(*) FROM user_achievements WHERE user_id = ?");
            $stmt_achievement_count->execute([$user_id]);
            $achievement_count = $stmt_achievement_count->fetchColumn() ?: 0;
            if ($achievement_count >= 10) {
                $grant_achievement('koleksiyoncu');
            }
        } catch (Exception $e) {
            // Başarım kontrolünde hata olursa logla ama devam et
            error_log("Achievement check error: " . $e->getMessage());
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
