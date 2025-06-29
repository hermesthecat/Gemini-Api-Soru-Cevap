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
} 