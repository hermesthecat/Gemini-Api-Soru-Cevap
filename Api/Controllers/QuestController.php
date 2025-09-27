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
     * PERFORMANCE FIX: ORDER BY RAND() yerine offset-based selection kullanır
     */
    private function assignNewQuests($user_id, $date, $count = 2)
    {
        // Performance optimized quest selection
        $available_quests = $this->getRandomQuestsOptimized($user_id, $date, $count);

        if (empty($available_quests)) {
            error_log("No available quests found for user $user_id on $date");
            return;
        }

        $stmt_insert = $this->pdo->prepare(
            "INSERT INTO user_quests (user_id, quest_key, goal, assigned_date) VALUES (?, ?, ?, ?)"
        );

        foreach ($available_quests as $quest) {
            try {
                $stmt_insert->execute([$user_id, $quest['quest_key'], $quest['default_goal'], $date]);
            } catch (PDOException $e) {
                // Duplicate quest assignment koruması
                if ($e->getCode() != 23000) { // Duplicate entry error değilse
                    error_log("Quest assignment error: " . $e->getMessage());
                }
            }
        }
    }

    /**
     * Performance optimized quest selection - ORDER BY RAND() yerine offset-based
     */
    private function getRandomQuestsOptimized($user_id, $date, $count = 2)
    {
        // 1. Kullanıcının son 7 gün içinde aldığı quest'leri exclude et
        $stmt_recent = $this->pdo->prepare("
            SELECT DISTINCT quest_key
            FROM user_quests
            WHERE user_id = ? AND assigned_date >= DATE_SUB(?, INTERVAL 7 DAY)
        ");
        $stmt_recent->execute([$user_id, $date]);
        $recent_quests = $stmt_recent->fetchAll(PDO::FETCH_COLUMN);

        // 2. Available quest'lerin sayısını al
        $exclude_list = !empty($recent_quests) ? "'" . implode("','", $recent_quests) . "'" : "''";
        $count_query = "SELECT COUNT(*) FROM quests WHERE quest_key NOT IN ($exclude_list)";
        $total_available = $this->pdo->query($count_query)->fetchColumn();

        if ($total_available < $count) {
            // Yeterli quest yoksa tüm quest'lerden seç
            $total_available = $this->pdo->query("SELECT COUNT(*) FROM quests")->fetchColumn();
            $exclude_list = "''";
        }

        if ($total_available == 0) {
            return [];
        }

        // 3. Hash-based deterministic selection (aynı kullanıcı aynı gün aynı quest'leri alır)
        $seed = crc32($user_id . $date);
        mt_srand($seed);

        $selected_offsets = [];
        $max_attempts = min($count * 3, $total_available); // Infinite loop koruması

        for ($attempt = 0; $attempt < $max_attempts && count($selected_offsets) < $count; $attempt++) {
            $offset = mt_rand(0, $total_available - 1);
            if (!in_array($offset, $selected_offsets)) {
                $selected_offsets[] = $offset;
            }
        }

        // 4. Offset'lerle quest'leri çek
        $quests = [];
        $base_query = "SELECT * FROM quests WHERE quest_key NOT IN ($exclude_list) LIMIT 1 OFFSET ?";
        $stmt = $this->pdo->prepare($base_query);

        foreach ($selected_offsets as $offset) {
            $stmt->execute([$offset]);
            if ($quest = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $quests[] = $quest;
            }
        }

        return $quests;
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
                $stmt_add_score = $pdo->prepare("UPDATE leaderboard SET score = score + ? WHERE user_id = ?");
                $stmt_add_coins = $pdo->prepare("UPDATE users SET coins = coins + ? WHERE id = ?");

                foreach ($completed_quests as $quest) {
                    // Görevi tamamlandı olarak işaretle
                    $stmt_complete_quest->execute([$user_id, $quest['quest_key'], $today]);
                    // Ödül puanını ve jetonunu ekle
                    $stmt_add_score->execute([$quest['reward_points'], $user_id]);
                    $stmt_add_coins->execute([$quest['reward_coins'], $user_id]);

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
