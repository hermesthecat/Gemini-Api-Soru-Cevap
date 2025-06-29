<?php

class GameController {
    private $pdo;
    private $gemini;

    public function __construct($pdo, $geminiApiKey) {
        $this->pdo = $pdo;
        $this->gemini = new GeminiAPI($geminiApiKey);
    }

    public function getQuestion($data) {
        unset($_SESSION['current_question_answer'], $_SESSION['current_question_explanation'], $_SESSION['start_time']);

        $kategori = $data['kategori'] ?? 'genel kültür';
        $difficulty = $data['difficulty'] ?? 'orta';

        try {
            $tip = (rand(1, 100) <= 75) ? 'coktan_secmeli' : 'dogru_yanlis';
            $prompt = $this->generatePrompt($tip, $kategori, $difficulty);
            $yanit = $this->gemini->soruSor($prompt);

            if (!$yanit) {
                return ['success' => false, 'message' => "Gemini API'sinden yanıt alınamadı."];
            }

            $temiz_yanit = preg_replace('/^```json\s*|\s*```$/', '', trim($yanit));
            $veri = json_decode($temiz_yanit, true);

            if (json_last_error() !== JSON_ERROR_NONE || !$this->isQuestionValid($veri)) {
                 return ['success' => false, 'message' => 'API\'den gelen soru formatı geçersiz veya eksik alanlar var.'];
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
                    'correct_answer' => $veri['dogru_cevap']
                ]
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Soru alınırken sunucu hatası: ' . $e->getMessage()];
        }
    }

    public function submitAnswer($data) {
        if (!isset($_SESSION['current_question_answer'])) {
            return ['success' => false, 'message' => 'Aktif soru bulunamadı.'];
        }

        $user_answer = $data['answer'] ?? null;
        $kategori = $data['kategori'] ?? 'bilinmiyor';
        $is_correct = ($user_answer === $_SESSION['current_question_answer']);
        
        if ($is_correct) {
            $this->updateStatsAndScore($kategori);
        } else {
             $_SESSION['consecutive_correct'] = 0;
        }

        $yeni_basarimlar = $is_correct ? $this->checkAchievements($kategori) : [];
        
        $response = [
            'success' => true,
            'data' => [
                'is_correct' => $is_correct,
                'correct_answer' => $_SESSION['current_question_answer'],
                'explanation' => $_SESSION['current_question_explanation'],
                'new_achievements' => $yeni_basarimlar
            ]
        ];

        unset($_SESSION['current_question_answer'], $_SESSION['current_question_explanation'], $_SESSION['start_time'], $_SESSION['current_question_difficulty']);
        return $response;
    }
    
    // --- Yardımcı Fonksiyonlar ---
    
    private function updateStatsAndScore($kategori) {
        $user_id = $_SESSION['user_id'];
        $gecen_sure = time() - ($_SESSION['start_time'] ?? time());
        $puan = 10 + max(0, 30 - $gecen_sure);
        $_SESSION['consecutive_correct'] = ($_SESSION['consecutive_correct'] ?? 0) + 1;

        try {
            $this->pdo->beginTransaction();
            
            // İstatistiği güncelle
            $sql_stat = "INSERT INTO user_stats (user_id, category, total_questions, correct_answers) VALUES (?, ?, 1, 1) ON DUPLICATE KEY UPDATE total_questions = total_questions + 1, correct_answers = correct_answers + 1";
            $stmt_stat = $this->pdo->prepare($sql_stat);
            $stmt_stat->execute([$user_id, $kategori]);
            
            // Skoru güncelle
            $sql_score = "UPDATE leaderboard SET score = score + ? WHERE user_id = ?";
            $stmt_score = $this->pdo->prepare($sql_score);
            $stmt_score->execute([$puan, $user_id]);

            $this->pdo->commit();
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            // Hata olsa da oyun akışı bozulmasın, sadece logla.
            error_log("Puan/İstatistik Güncelleme Hatası: " . $e->getMessage());
        }
    }

    private function checkAchievements($kategori) {
        $user_id = $_SESSION['user_id'];
        $yeni_basarimlar = [];
        $mevcut_basarimlar = [];

        try {
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
            
            // Başarım kontrolleri...
            // Seri Galibi
            if ($_SESSION['consecutive_correct'] >= 25) $grant_achievement('seri_galibi_25');
            elseif ($_SESSION['consecutive_correct'] >= 10) $grant_achievement('seri_galibi_10');

            // Hız Tutkunu
            if ((time() - $_SESSION['start_time']) <= 5) $grant_achievement('hiz_tutkunu');

            // Kategori Uzmanı ve Kusursuz
            $stmt_cat = $this->pdo->prepare("SELECT correct_answers, total_questions FROM user_stats WHERE user_id = ? AND category = ?");
            $stmt_cat->execute([$user_id, $kategori]);
            if ($cat_stats = $stmt_cat->fetch(PDO::FETCH_ASSOC)) {
                if ($cat_stats['correct_answers'] >= 20) $grant_achievement("uzman_{$kategori}");
                if ($cat_stats['total_questions'] >= 10 && $cat_stats['correct_answers'] == $cat_stats['total_questions']) $grant_achievement("kusursuz_{$kategori}");
            }

            // ... Diğer tüm başarım kontrolleri buraya eklenebilir. (İlk adım, meraklı, vs.)

        } catch (PDOException $e) {
            error_log("Başarım kontrol hatası: " . $e->getMessage());
        }
        
        return $yeni_basarimlar;
    }

    private function generatePrompt($tip, $kategori, $difficulty) {
        if ($tip === 'coktan_secmeli') {
            return "Lütfen {$kategori} kategorisinde {$difficulty} zorlukta bir soru hazırla. Yanıtı yalnızca şu JSON formatında, başka hiçbir metin olmadan ver: {\"tip\": \"coktan_secmeli\", \"soru\": \"(soru metni buraya)\", \"siklar\": {\"A\": \"(A şıkkı buraya)\", \"B\": \"(B şıkkı buraya)\", \"C\": \"(C şıkkı buraya)\", \"D\": \"(D şıkkı buraya)\"}, \"dogru_cevap\": \"(Doğru şıkkın harfi buraya, örneğin: A)\", \"aciklama\": \"(Doğru cevabın neden doğru olduğuna dair 1-2 cümlelik açıklama)\"}";
        } else { // dogru_yanlis
            return "Lütfen {$kategori} kategorisinde {$difficulty} zorlukta, doğru ya da yanlış olarak cevaplanabilecek bir önerme hazırla. Yanıtı yalnızca şu JSON formatında, başka hiçbir metin olmadan ver: {\"tip\": \"dogru_yanlis\", \"soru\": \"(Önerme cümlesi buraya)\", \"dogru_cevap\": \"(Doğru ya da Yanlış kelimelerinden biri)\", \"aciklama\": \"(Önermenin neden doğru ya da yanlış olduğuna dair 1-2 cümlelik açıklama)\"}";
        }
    }

    private function isQuestionValid($veri) {
        if (!isset($veri['tip'], $veri['soru'], $veri['dogru_cevap'], $veri['aciklama'])) {
            return false;
        }
        if ($veri['tip'] === 'coktan_secmeli' && !isset($veri['siklar'])) {
            return false;
        }
        return true;
    }
} 