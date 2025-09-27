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
            SELECT uq.quest_key, q.name, q.description_template, q.target, uq.progress, uq.goal, uq.is_completed, q.reward_points, q.reward_coins
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
            "INSERT INTO user_quests (user_id, quest_key, goal, assigned_date, start_time) VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)"
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

        // Special handling for consecutive_days ve win_duels quest types
        if ($type === 'consecutive_days') {
            return self::checkConsecutiveDaysQuests($pdo, $user_id, $today);
        } elseif ($type === 'win_duels') {
            return self::checkWinDuelsQuests($pdo, $user_id, $today);
        }

        // Standard quest progress update (solve_category, solve_difficulty)
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
                    SET is_completed = TRUE, completion_time = CURRENT_TIMESTAMP
                    WHERE user_id = ? AND quest_key = ? AND assigned_date = ?
                ");
                $stmt_add_score = $pdo->prepare("UPDATE leaderboard SET score = score + ? WHERE user_id = ?");
                $stmt_add_coins = $pdo->prepare("UPDATE users SET coins = coins + ? WHERE id = ?");

                // Quest history için prepared statement
                $stmt_add_history = $pdo->prepare("
                    INSERT INTO quest_history (
                        user_id, quest_key, quest_name, quest_type, assigned_date,
                        completed_date, goal, achieved, reward_points, reward_coins,
                        completion_time_minutes
                    ) VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP, ?, ?, ?, ?, ?)
                ");

                foreach ($completed_quests as $quest) {
                    // Görevi tamamlandı olarak işaretle
                    $stmt_complete_quest->execute([$user_id, $quest['quest_key'], $today]);

                    // Ödül puanını ve jetonunu ekle
                    $stmt_add_score->execute([$quest['reward_points'], $user_id]);
                    $stmt_add_coins->execute([$quest['reward_coins'], $user_id]);

                    // Quest history'ye kaydet
                    $completion_time_minutes = self::calculateCompletionTime($pdo, $user_id, $quest['quest_key'], $today);
                    $stmt_add_history->execute([
                        $user_id,
                        $quest['quest_key'],
                        $quest['name'],
                        $quest['type'],
                        $today,
                        $quest['goal'],
                        $quest['progress'], // achieved value
                        $quest['reward_points'],
                        $quest['reward_coins'],
                        $completion_time_minutes
                    ]);

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

    /**
     * Consecutive days quest tracking - Login streak bazlı
     */
    private static function checkConsecutiveDaysQuests($pdo, $user_id, $today)
    {
        // Kullanıcının current login streak'ini al
        $stmt = $pdo->prepare("SELECT current_login_streak FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $current_streak = $stmt->fetchColumn() ?: 0;

        // Bugünün consecutive_days quest'lerini kontrol et
        $stmt_quests = $pdo->prepare("
            SELECT uq.quest_key, uq.goal, q.reward_points, q.reward_coins, q.name
            FROM user_quests uq
            JOIN quests q ON uq.quest_key = q.quest_key
            WHERE uq.user_id = ?
              AND uq.assigned_date = ?
              AND uq.is_completed = FALSE
              AND q.type = 'consecutive_days'
        ");
        $stmt_quests->execute([$user_id, $today]);
        $quests = $stmt_quests->fetchAll(PDO::FETCH_ASSOC);

        $completed_quests = [];

        foreach ($quests as $quest) {
            // Progress'i current streak ile güncelle
            $new_progress = min($current_streak, $quest['goal']); // Goal'i aşmasın

            $stmt_update = $pdo->prepare("
                UPDATE user_quests
                SET progress = ?
                WHERE user_id = ? AND quest_key = ? AND assigned_date = ?
            ");
            $stmt_update->execute([$new_progress, $user_id, $quest['quest_key'], $today]);

            // Goal'e ulaştıysa quest'i complete et
            if ($current_streak >= $quest['goal']) {
                $completed = self::completeQuest($pdo, $user_id, $quest['quest_key'], $today, $quest);
                if ($completed) {
                    $completed_quests[] = $completed;
                }
            }
        }

        return $completed_quests;
    }

    /**
     * Win duels quest tracking - Günlük düello kazanma sayısı
     */
    private static function checkWinDuelsQuests($pdo, $user_id, $today)
    {
        // Bugün kazanılan düello sayısını hesapla
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM duels
            WHERE status = 'completed'
              AND winner_id = ?
              AND DATE(created_at) = ?
        ");
        $stmt->execute([$user_id, $today]);
        $wins_today = $stmt->fetchColumn() ?: 0;

        // Bugünün win_duels quest'lerini kontrol et
        $stmt_quests = $pdo->prepare("
            SELECT uq.quest_key, uq.goal, q.reward_points, q.reward_coins, q.name
            FROM user_quests uq
            JOIN quests q ON uq.quest_key = q.quest_key
            WHERE uq.user_id = ?
              AND uq.assigned_date = ?
              AND uq.is_completed = FALSE
              AND q.type = 'win_duels'
        ");
        $stmt_quests->execute([$user_id, $today]);
        $quests = $stmt_quests->fetchAll(PDO::FETCH_ASSOC);

        $completed_quests = [];

        foreach ($quests as $quest) {
            // Progress'i bugünkü win count ile güncelle
            $new_progress = min($wins_today, $quest['goal']); // Goal'i aşmasın

            $stmt_update = $pdo->prepare("
                UPDATE user_quests
                SET progress = ?
                WHERE user_id = ? AND quest_key = ? AND assigned_date = ?
            ");
            $stmt_update->execute([$new_progress, $user_id, $quest['quest_key'], $today]);

            // Goal'e ulaştıysa quest'i complete et
            if ($wins_today >= $quest['goal']) {
                $completed = self::completeQuest($pdo, $user_id, $quest['quest_key'], $today, $quest);
                if ($completed) {
                    $completed_quests[] = $completed;
                }
            }
        }

        return $completed_quests;
    }

    /**
     * Quest completion helper - Rewards verme ve marking complete
     */
    private static function completeQuest($pdo, $user_id, $quest_key, $today, $quest_data)
    {
        try {
            $pdo->beginTransaction();

            // Quest'i complete olarak işaretle
            $stmt_complete = $pdo->prepare("
                UPDATE user_quests
                SET is_completed = TRUE, completed_at = CURRENT_TIMESTAMP
                WHERE user_id = ? AND quest_key = ? AND assigned_date = ?
            ");
            $stmt_complete->execute([$user_id, $quest_key, $today]);

            // Rewards'ları ver
            $stmt_points = $pdo->prepare("UPDATE leaderboard SET score = score + ? WHERE user_id = ?");
            $stmt_points->execute([$quest_data['reward_points'], $user_id]);

            $stmt_coins = $pdo->prepare("UPDATE users SET coins = coins + ? WHERE id = ?");
            $stmt_coins->execute([$quest_data['reward_coins'], $user_id]);

            $pdo->commit();

            // Session güncelle
            if (isset($_SESSION['user_coins'])) {
                $_SESSION['user_coins'] += $quest_data['reward_coins'];
            }

            return [
                'name' => $quest_data['name'],
                'reward_points' => $quest_data['reward_points'],
                'reward_coins' => $quest_data['reward_coins']
            ];

        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Quest completion error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Login streak güncelleme - UserController'dan çağrılacak
     */
    public static function updateLoginStreak($pdo, $user_id)
    {
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));

        $stmt = $pdo->prepare("SELECT last_login_date, current_login_streak FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) return 0;

        $last_login = $user['last_login_date'];
        $current_streak = $user['current_login_streak'] ?: 0;

        if ($last_login === $today) {
            // Bugün zaten giriş yapılmış
            return $current_streak;
        } elseif ($last_login === $yesterday) {
            // Dün giriş yapılmış, streak devam ediyor
            $new_streak = $current_streak + 1;
        } else {
            // Streak kırılmış, yeniden başla
            $new_streak = 1;
        }

        // User login data güncelle
        $update_stmt = $pdo->prepare("
            UPDATE users
            SET last_login_date = ?,
                current_login_streak = ?,
                longest_login_streak = GREATEST(longest_login_streak, ?)
            WHERE id = ?
        ");
        $update_stmt->execute([$today, $new_streak, $new_streak, $user_id]);

        // Login streak quest'lerini kontrol et
        self::checkAndUpdateQuestProgress($pdo, $user_id, 'consecutive_days');

        return $new_streak;
    }

    /**
     * Quest completion time hesaplama - start_time ile completion_time arasındaki dakika farkı
     */
    private static function calculateCompletionTime($pdo, $user_id, $quest_key, $assigned_date)
    {
        try {
            $stmt = $pdo->prepare("
                SELECT
                    start_time,
                    completion_time,
                    TIMESTAMPDIFF(MINUTE, start_time, completion_time) as minutes_diff
                FROM user_quests
                WHERE user_id = ? AND quest_key = ? AND assigned_date = ?
            ");
            $stmt->execute([$user_id, $quest_key, $assigned_date]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result && $result['start_time'] && $result['completion_time']) {
                return max(1, (int)$result['minutes_diff']); // En az 1 dakika
            }

            // Eğer start_time yoksa veya hesaplanamıyorsa null döndür
            return null;
        } catch (PDOException $e) {
            error_log("Completion time calculation error: " . $e->getMessage());
            return null;
        }
    }
}
