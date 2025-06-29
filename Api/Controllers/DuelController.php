<?php

require_once 'GameController.php';

class DuelController
{
    private $pdo;
    private $gemini;

    public function __construct($pdo, $geminiApiKey)
    {
        $this->pdo = $pdo;
        $this->gemini = new GeminiAPI($geminiApiKey);
    }

    /**
     * Bir arkadaşa meydan okuma (düello) isteği gönderir.
     */
    public function createDuel($data)
    {
        $challenger_id = $_SESSION['user_id'];
        $opponent_id = $data['opponent_id'] ?? 0;
        $category = $data['category'] ?? 'genel kültür';
        $difficulty = $data['difficulty'] ?? 'orta';
        $question_count = 5; // Her düello 5 sorudan oluşacak

        if ($opponent_id == 0 || $challenger_id == $opponent_id) {
            return ['success' => false, 'message' => 'Geçersiz rakip seçimi.'];
        }

        // 1. Gemini API'den 5 soru al
        // GameController'daki prompt oluşturma mantığını kullanabiliriz.
        $gameController = new GameController($this->pdo, ''); // API key'e burada gerek yok, sadece prompt için
        $prompt_method = new ReflectionMethod('GameController', 'generatePrompt');
        $prompt_method->setAccessible(true);
        $prompt = $prompt_method->invoke($gameController, 'coktan_secmeli', $category, $difficulty, $question_count);
        
        $yanit = $this->gemini->soruSor($prompt);
        if (!$yanit) {
             return ['success' => false, 'message' => "Düello soruları oluşturulamadı. API'den yanıt alınamadı."];
        }

        $temiz_yanit = preg_replace('/^```json\s*|\s*```$/', '', trim($yanit));
        $sorular = json_decode($temiz_yanit, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($sorular) || count($sorular) === 0) {
            error_log("Invalid JSON for Duel from Gemini: " . $temiz_yanit);
            return ['success' => false, 'message' => 'API\'den gelen soru formatı geçersiz veya eksik.'];
        }

        // 2. Düelloyu veritabanına kaydet
        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO duels (challenger_id, opponent_id, category, difficulty, status, questions) 
                 VALUES (?, ?, ?, ?, 'pending', ?)"
            );
            $stmt->execute([$challenger_id, $opponent_id, $category, $difficulty, json_encode($sorular)]);
            
            return ['success' => true, 'message' => 'Meydan okuma gönderildi! Rakibinin kabul etmesi bekleniyor.'];

        } catch (PDOException $e) {
            error_log("Duel creation DB error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Meydan okuma oluşturulurken bir veritabanı hatası oluştu.'];
        }
    }

    /**
     * Kullanıcının dahil olduğu tüm düelloları (gelen, giden, aktif, tamamlanmış) listeler.
     */
    public function getDuels()
    {
        $user_id = $_SESSION['user_id'];

        $stmt = $this->pdo->prepare("
            SELECT 
                d.id, d.category, d.difficulty, d.status,
                d.challenger_id, c.username as challenger_name,
                d.opponent_id, o.username as opponent_name,
                d.winner_id, w.username as winner_name,
                d.challenger_score, d.opponent_score,
                d.updated_at
            FROM duels d
            JOIN users c ON d.challenger_id = c.id
            JOIN users o ON d.opponent_id = o.id
            LEFT JOIN users w ON d.winner_id = w.id
            WHERE d.challenger_id = ? OR d.opponent_id = ?
            ORDER BY d.updated_at DESC
        ");
        $stmt->execute([$user_id, $user_id]);

        return ['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
    }

    /**
     * Gelen bir meydan okuma isteğini kabul eder veya reddeder.
     */
    public function respondToDuel($data)
    {
        $user_id = $_SESSION['user_id'];
        $duel_id = $data['duel_id'] ?? 0;
        $response = $data['response'] ?? ''; // 'accept' or 'decline'

        if ($duel_id == 0 || !in_array($response, ['accept', 'decline'])) {
            return ['success' => false, 'message' => 'Geçersiz istek.'];
        }

        $new_status = ($response === 'accept') ? 'active' : 'declined';

        $stmt = $this->pdo->prepare("
            UPDATE duels 
            SET status = ?
            WHERE id = ? 
              AND opponent_id = ?
              AND status = 'pending'
        ");
        $stmt->execute([$new_status, $duel_id, $user_id]);

        if ($stmt->rowCount() > 0) {
            return ['success' => true, 'message' => 'Meydan okuma ' . ($response === 'accept' ? 'kabul edildi!' : 'reddedildi.')];
        }

        return ['success' => false, 'message' => 'İşlem başarısız oldu veya bu isteğe yanıt verme yetkiniz yok.'];
    }

    /**
     * Bir düello oyununu başlatmak için gerekli verileri alır.
     * Soruları, cevapları ve açıklamaları olmadan döndürür.
     */
    public function startDuelGame($data)
    {
        $user_id = $_SESSION['user_id'];
        $duel_id = $data['duel_id'] ?? 0;

        $stmt = $this->pdo->prepare("SELECT * FROM duels WHERE id = ?");
        $stmt->execute([$duel_id]);
        $duel = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$duel) {
            return ['success' => false, 'message' => 'Düello bulunamadı.'];
        }

        // İzin kontrolleri
        $is_challenger = $duel['challenger_id'] == $user_id;
        $is_opponent = $duel['opponent_id'] == $user_id;

        if (!$is_challenger && !$is_opponent) {
            return ['success' => false, 'message' => 'Bu düelloya erişim yetkiniz yok.'];
        }

        $user_has_played = ($is_challenger && $duel['challenger_answers'] !== null) || ($is_opponent && $duel['opponent_answers'] !== null);
        if ($user_has_played) {
            return ['success' => false, 'message' => 'Sıranızı zaten oynadınız.'];
        }

        if (!in_array($duel['status'], ['active', 'challenger_completed', 'opponent_completed'])) {
            return ['success' => false, 'message' => 'Bu düello şu anda oynanabilir durumda değil. (' . $duel['status'] . ')'];
        }
        
        // Eğer rakip sırasını oynamışsa ve sıra bizdeyse
        if ($duel['status'] === 'challenger_completed' && !$is_opponent) {
             return ['success' => false, 'message' => 'Sıra rakibinizde.'];
        }
        if ($duel['status'] === 'opponent_completed' && !$is_challenger) {
             return ['success' => false, 'message' => 'Sıra rakibinizde.'];
        }


        $questions = json_decode($duel['questions'], true);
        
        // Cevapları ve açıklamaları client'a göndermeden önce temizle
        $sanitized_questions = array_map(function ($q) {
            unset($q['dogru_cevap']);
            unset($q['aciklama']);
            return $q;
        }, $questions);

        $duel['questions'] = $sanitized_questions;

        return ['success' => true, 'data' => $duel];
    }


    /**
     * Bir düello sorusuna verilen cevabı işler.
     */
    public function submitDuelAnswer($data)
    {
        $user_id = $_SESSION['user_id'];
        $duel_id = $data['duel_id'] ?? 0;
        $question_index = $data['question_index'] ?? -1;
        $user_answer = $data['answer'] ?? null;

        if ($duel_id == 0 || $question_index < 0 || $user_answer === null) {
            return ['success' => false, 'message' => 'Eksik parametre.'];
        }

        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM duels WHERE id = ? FOR UPDATE");
            $stmt->execute([$duel_id]);
            $duel = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$duel) {
                 $this->pdo->rollBack();
                return ['success' => false, 'message' => 'Düello bulunamadı.'];
            }

            // İzin kontrolleri
            $is_challenger = $duel['challenger_id'] == $user_id;
            $is_opponent = $duel['opponent_id'] == $user_id;
            if (!$is_challenger && !$is_opponent) {
                 $this->pdo->rollBack();
                return ['success' => false, 'message' => 'Bu düelloya erişim yetkiniz yok.'];
            }
            
            $answers_column = $is_challenger ? 'challenger_answers' : 'opponent_answers';
            $user_has_submitted_this_question = false;
            if ($duel[$answers_column]) {
                $current_answers = json_decode($duel[$answers_column], true);
                if (isset($current_answers[$question_index])) {
                    $user_has_submitted_this_question = true;
                }
            } else {
                $current_answers = [];
            }
             
            if($user_has_submitted_this_question){
                $this->pdo->rollBack();
                return ['success' => false, 'message' => 'Bu soruyu zaten cevapladınız.'];
            }


            $questions = json_decode($duel['questions'], true);
            if (!isset($questions[$question_index])) {
                 $this->pdo->rollBack();
                return ['success' => false, 'message' => 'Geçersiz soru indeksi.'];
            }

            $question = $questions[$question_index];
            $is_correct = ($user_answer === $question['dogru_cevap']);
            
            // Kullanıcının cevabını kaydet
            $current_answers[$question_index] = [
                'answer' => $user_answer,
                'is_correct' => $is_correct
            ];
            
            $stmt_update_answers = $this->pdo->prepare("UPDATE duels SET $answers_column = ? WHERE id = ?");
            $stmt_update_answers->execute([json_encode($current_answers), $duel_id]);

            $is_last_question = (count($current_answers) == count($questions));
            $final_state = null;

            if ($is_last_question) {
                $final_state = $this->finalizeTurn($duel_id, $user_id);
            }

            $this->pdo->commit();

            return [
                'success' => true,
                'data' => [
                    'is_correct' => $is_correct,
                    'correct_answer' => $question['dogru_cevap'],
                    'explanation' => $question['aciklama'] ?? 'Açıklama mevcut değil.',
                    'is_last_question' => $is_last_question,
                    'final_state' => $final_state,
                ]
            ];
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("Duel Submit DB Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Cevap işlenirken bir veritabanı hatası oluştu.'];
        }
    }
    
    /**
     * Bir oyuncu 5 soruyu da cevapladığında sırasını tamamlar, skoru ve durumu günceller.
     * Bu fonksiyon bir transaction içinde çağrılmalıdır.
     */
    private function finalizeTurn($duel_id, $user_id) {
        // En güncel durumu almak için düelloyu tekrar çek.
        $stmt = $this->pdo->prepare("SELECT * FROM duels WHERE id = ? FOR UPDATE");
        $stmt->execute([$duel_id]);
        $duel = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $is_challenger = $duel['challenger_id'] == $user_id;

        $answers_column = $is_challenger ? 'challenger_answers' : 'opponent_answers';
        $score_column = $is_challenger ? 'challenger_score' : 'opponent_score';
        
        $answers = json_decode($duel[$answers_column], true);
        $score = 0;
        foreach($answers as $ans) {
            if ($ans['is_correct']) {
                $score += 10;
            }
        }
        
        // Yeni durumu belirle
        $new_status = $duel['status'];
        $opponent_answers_column = $is_challenger ? 'opponent_answers' : 'challenger_answers';
        
        if ($duel[$opponent_answers_column] !== null) {
            // Rakip zaten bitirmiş, bu son hamle.
            $new_status = 'completed';
        } else {
            // Rakip henüz bitirmedi.
            $new_status = $is_challenger ? 'challenger_completed' : 'opponent_completed';
        }


        $winner_id = $duel['winner_id']; // Default'u koru
        if ($new_status === 'completed') {
            $opponent_score_column = $is_challenger ? 'opponent_score' : 'challenger_score';
            $opponent_score = $duel[$opponent_score_column];
            
            if ($score > $opponent_score) {
                $winner_id = $user_id;
            } else if ($opponent_score > $score) {
                $winner_id = $is_challenger ? $duel['opponent_id'] : $duel['challenger_id'];
            } else { // Berabere
                $winner_id = 0; // 0 ID'sini berabere olarak kullanalım. NULL da olabilir.
            }
        }
        
        // Skoru, durumu ve kazananı güncelle
        $sql = "UPDATE duels SET $score_column = ?, status = ?, winner_id = ? WHERE id = ?";
        $stmt_update = $this->pdo->prepare($sql);
        $stmt_update->execute([$score, $new_status, $winner_id, $duel_id]);

        return ['new_status' => $new_status, 'my_score' => $score];
    }
} 