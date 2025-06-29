<?php

class QuestController
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Kullanıcının günlük görevlerini alır.
     * Eğer o gün için görevi yoksa, rastgele 2 yeni görev atar.
     */
    public function getDailyQuests()
    {
        $user_id = $_SESSION['user_id'];
        $today = date('Y-m-d');

        // Bugün için atanmış görev var mı kontrol et
        $stmt = $this->pdo->prepare("
            SELECT uq.quest_key, q.name, q.description_template, q.target, uq.progress, uq.goal, uq.is_completed, q.reward_points
            FROM user_quests uq
            JOIN quests q ON uq.quest_key = q.quest_key
            WHERE uq.user_id = ? AND uq.assigned_date = ?
        ");
        $stmt->execute([$user_id, $today]);
        $quests = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Görev yoksa, yeni görevler ata
        if (count($quests) === 0) {
            $this->assignNewQuests($user_id, $today, 2);
            // Yeniden görevleri çek
            $stmt->execute([$user_id, $today]);
            $quests = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // Açıklamaları formatla
        foreach ($quests as &$quest) {
            $quest['description'] = str_replace(
                ['{goal}', '{target}'],
                [$quest['goal'], $quest['target'] ?? ''],
                $quest['description_template']
            );
        }

        return ['success' => true, 'data' => $quests];
    }

    /**
     * Bir kullanıcıya belirtilen sayıda rastgele yeni görev atar.
     */
    private function assignNewQuests($user_id, $date, $count = 2)
    {
        // Atanabilecek tüm görevleri al
        $stmt = $this->pdo->prepare("SELECT * FROM quests ORDER BY RAND() LIMIT ?");
        $stmt->execute([$count]);
        $available_quests = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt_insert = $this->pdo->prepare(
            "INSERT INTO user_quests (user_id, quest_key, goal, assigned_date) VALUES (?, ?, ?, ?)"
        );

        foreach ($available_quests as $quest) {
            // Aynı görevin tekrar atanmasını önlemek için ON DUPLICATE KEY IGNORE kullanılabilir,
            // ama günlük atama yaptığımız için şimdilik gerekmeyebilir.
            $stmt_insert->execute([$user_id, $quest['quest_key'], $quest['default_goal'], $date]);
        }
    }

    /**
     * Bir eyleme göre görev ilerlemesini kontrol eder ve günceller.
     * Bu fonksiyon public static, çünkü GameController gibi diğer yerlerden direkt çağrılacak.
     */
    public static function checkAndUpdateQuestProgress($pdo, $user_id, $type, $target = null)
    {
        $today = date('Y-m-d');
        $newly_completed_quests = [];

        $sql = "
            UPDATE user_quests uq
            JOIN quests q ON uq.quest_key = q.quest_key
            SET uq.progress = uq.progress + 1
            WHERE uq.user_id = ? 
              AND uq.assigned_date = ?
              AND uq.is_completed = FALSE
              AND q.type = ?
        ";
        $params = [$user_id, $today, $type];

        if ($target !== null) {
            $sql .= " AND q.target = ?";
            $params[] = $target;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        // Şimdi tamamlanmış olabilecek görevleri kontrol et
        $stmt_check = $pdo->prepare("
            SELECT uq.quest_key, uq.progress, uq.goal, q.reward_points, q.reward_coins, q.name
            FROM user_quests uq
            JOIN quests q ON uq.quest_key = q.quest_key
            WHERE uq.user_id = ?
              AND uq.assigned_date = ?
              AND uq.is_completed = FALSE
              AND uq.progress >= uq.goal
        ");
        $stmt_check->execute([$user_id, $today]);
        $completed_quests = $stmt_check->fetchAll(PDO::FETCH_ASSOC);

        if (count($completed_quests) > 0) {
            $pdo->beginTransaction();
            try {
                $stmt_complete_quest = $pdo->prepare("
                    UPDATE user_quests 
                    SET is_completed = TRUE, completed_at = CURRENT_TIMESTAMP 
                    WHERE user_id = ? AND quest_key = ? AND assigned_date = ?
                ");
                $stmt_add_rewards = $pdo->prepare("
                    UPDATE leaderboard SET score = score + ?, coins = coins + ? WHERE user_id = ?
                ");

                foreach ($completed_quests as $quest) {
                    // Görevi tamamlandı olarak işaretle
                    $stmt_complete_quest->execute([$user_id, $quest['quest_key'], $today]);
                    // Ödül puanını ve jetonunu ekle
                    $stmt_add_rewards->execute([$quest['reward_points'], $quest['reward_coins'], $user_id]);
                    
                    // Session'ı güncelle
                    $_SESSION['user_coins'] = ($_SESSION['user_coins'] ?? 0) + $quest['reward_coins'];

                    $newly_completed_quests[] = [
                        'name' => $quest['name'],
                        'reward_points' => $quest['reward_points'],
                        'reward_coins' => $quest['reward_coins']
                    ];
                }
                $pdo->commit();
            } catch (PDOException $e) {
                $pdo->rollBack();
                error_log("Quest completion error: " . $e->getMessage());
                return []; // Hata durumunda boş dizi döndür
            }
        }

        return $newly_completed_quests;
    }
}
