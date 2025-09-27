<?php

class DataController
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function getUserData()
    {
        $user_id = $_SESSION['user_id'];
        $user_data = [];

        // Liderlik tablosundan skor al
        $stmt_score = $this->pdo->prepare("SELECT score FROM leaderboard WHERE user_id = ?");
        $stmt_score->execute([$user_id]);
        $leaderboard_data = $stmt_score->fetch(PDO::FETCH_ASSOC);

        // Coins'i users tablosundan al
        $stmt_coins = $this->pdo->prepare("SELECT coins, lifeline_fifty_fifty, lifeline_extra_time, lifeline_pass FROM users WHERE id = ?");
        $stmt_coins->execute([$user_id]);
        $user_coins_data = $stmt_coins->fetch(PDO::FETCH_ASSOC);

        $user_data['score'] = $leaderboard_data['score'] ?? 0;
        $user_data['coins'] = $user_coins_data['coins'] ?? 0;
        $user_data['lifelines'] = [
            'fiftyFifty' => $user_coins_data['lifeline_fifty_fifty'] ?? 1,
            'extraTime' => $user_coins_data['lifeline_extra_time'] ?? 1,
            'pass' => $user_coins_data['lifeline_pass'] ?? 1
        ];

        // İstatistikleri al (zorluk seviyelerine göre gruplayarak birleştir)
        $stmt_stats = $this->pdo->prepare("
            SELECT 
                category, 
                SUM(total_questions) as total_questions, 
                SUM(correct_answers) as correct_answers 
            FROM user_stats 
            WHERE user_id = ? 
            GROUP BY category
        ");
        $stmt_stats->execute([$user_id]);
        $user_data['stats'] = $stmt_stats->fetchAll(PDO::FETCH_ASSOC);

        return ['success' => true, 'data' => $user_data];
    }

    public function getLeaderboard()
    {
        $stmt = $this->pdo->prepare("
            SELECT u.username, l.score
            FROM leaderboard l
            JOIN users u ON l.user_id = u.id
            ORDER BY l.score DESC, l.last_updated ASC
            LIMIT 10
        ");
        $stmt->execute();
        return ['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
    }

    public function getUserRank()
    {
        $user_id = $_SESSION['user_id'];

        // Get user's rank and info
        $stmt = $this->pdo->prepare("
            SELECT
                u.username,
                l.score,
                (SELECT COUNT(*) + 1
                 FROM leaderboard l2
                 WHERE l2.score > l.score
                    OR (l2.score = l.score AND l2.last_updated < l.last_updated)
                ) as position
            FROM leaderboard l
            JOIN users u ON l.user_id = u.id
            WHERE u.id = ?
        ");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            return ['success' => false, 'message' => 'Kullanıcı sıralaması bulunamadı'];
        }

        return ['success' => true, 'data' => $result];
    }

    public function getUserAchievements()
    {
        $stmt = $this->pdo->prepare("
            SELECT 
                a.achievement_key,
                a.name,
                a.description,
                a.icon,
                a.color,
                ua.achieved_at
            FROM achievements a
            LEFT JOIN user_achievements ua ON a.achievement_key = ua.achievement_key AND ua.user_id = ?
            ORDER BY ua.achieved_at DESC, a.name ASC
        ");
        $stmt->execute([$_SESSION['user_id']]);
        return ['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
    }

    public function getActiveAnnouncements()
    {
        $user_id = $_SESSION['user_id'];
        $user_role = $_SESSION['role'] ?? 'user';

        // Kullanıcının rolüne göre hedef grupları belirle
        $target_groups = ['all'];
        if ($user_role === 'admin') {
            $target_groups[] = 'admins';
        }
        $target_groups[] = 'users'; // 'users' tüm rolleri kapsar (şimdilik)

        $placeholders = implode(',', array_fill(0, count($target_groups), '?'));

        $stmt = $this->pdo->prepare("
            SELECT a.id, a.title, a.content, a.created_at
            FROM announcements a
            LEFT JOIN user_announcements ua ON a.id = ua.announcement_id AND ua.user_id = ?
            WHERE a.is_active = TRUE
              AND a.start_date <= CURRENT_TIMESTAMP
              AND a.end_date >= CURRENT_TIMESTAMP
              AND a.target_group IN ($placeholders)
              AND ua.id IS NULL -- Sadece okunmamış olanları getir
            ORDER BY a.created_at DESC
        ");

        $params = array_merge([$user_id], $target_groups);
        $stmt->execute($params);
        return ['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
    }

    public function markAnnouncementsAsRead($data)
    {
        $user_id = $_SESSION['user_id'];
        $announcement_ids = $data['ids'] ?? [];

        if (empty($announcement_ids) || !is_array($announcement_ids)) {
            return ['success' => false, 'message' => 'Geçersiz ID.'];
        }

        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO user_announcements (user_id, announcement_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE read_at = CURRENT_TIMESTAMP"
            );
            foreach ($announcement_ids as $id) {
                $stmt->execute([$user_id, $id]);
            }
            $this->pdo->commit();
            return ['success' => true];
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            return ['success' => false, 'message' => 'Duyurular okunmuş olarak işaretlenemedi.'];
        }
    }

    public function getCategories()
    {
        $stmt = $this->pdo->prepare("
            SELECT category_key, category_name, icon, color, is_active
            FROM categories
            WHERE is_active = 1
            ORDER BY category_name ASC
        ");
        $stmt->execute();
        return ['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
    }

    public function getCombinedAchievements()
    {
        if (!isset($_SESSION['user_id'])) {
            return ['success' => false, 'message' => 'Kullanıcı oturumu bulunamadı.'];
        }

        $user_id = $_SESSION['user_id'];

        try {
            // 1. Tüm başarımları ve kullanıcının durumunu al
            $stmt = $this->pdo->prepare("
                SELECT
                    a.achievement_key,
                    a.name,
                    a.description,
                    a.icon,
                    a.color,
                    ua.achieved_at
                FROM achievements a
                LEFT JOIN user_achievements ua ON a.achievement_key = ua.achievement_key AND ua.user_id = ?
                ORDER BY
                    CASE WHEN ua.achieved_at IS NOT NULL THEN 0 ELSE 1 END,
                    ua.achieved_at DESC,
                    a.name ASC
            ");
            $stmt->execute([$user_id]);
            $achievements = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // 2. Progress verilerini al
            $progressResponse = $this->getAchievementProgress();
            $progressData = $progressResponse['success'] ? $progressResponse['data'] : [];

            // 3. Başarımları kombine et
            $combinedAchievements = [];

            foreach ($achievements as $achievement) {
                $achievement_key = $achievement['achievement_key'];
                $isEarned = !empty($achievement['achieved_at']);

                $combinedAchievement = [
                    'achievement_key' => $achievement_key,
                    'name' => $achievement['name'],
                    'description' => $achievement['description'],
                    'icon' => $achievement['icon'],
                    'color' => $achievement['color'],
                    'earned' => $isEarned,
                    'achieved_at' => $achievement['achieved_at']
                ];

                // Eğer kazanılmamışsa progress bilgisini ekle
                if (!$isEarned) {
                    // İlk 5 tracking başarımı için progress bilgisi var
                    $trackingAchievements = ['ilk_adim', 'puan_avcisi_1000', 'gece_kusu', 'merakli', 'koleksiyoncu'];

                    if (in_array($achievement_key, $trackingAchievements) && isset($progressData[$achievement_key])) {
                        $progress = $progressData[$achievement_key];
                        $combinedAchievement['progress'] = [
                            'current' => $progress['current'],
                            'target' => $progress['target'],
                            'percentage' => round(($progress['current'] / $progress['target']) * 100),
                            'hint' => $progress['hint'] ?? ''
                        ];
                    }
                }

                $combinedAchievements[] = $combinedAchievement;
            }

            return ['success' => true, 'data' => $combinedAchievements];

        } catch (Exception $e) {
            error_log("Combined achievements error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Başarımlar alınamadı: ' . $e->getMessage()];
        }
    }

    public function getAchievementProgress()
    {
        if (!isset($_SESSION['user_id'])) {
            return ['success' => false, 'message' => 'Kullanıcı oturumu bulunamadı.'];
        }

        $user_id = $_SESSION['user_id'];
        $progress = [];

        try {
            error_log("getAchievementProgress called for user: " . $user_id);
            // 1. İlk Adım - İlk doğru cevap
            $stmt = $this->pdo->prepare("SELECT SUM(correct_answers) as total_correct FROM user_stats WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $total_correct = $stmt->fetchColumn() ?: 0;
            $progress['ilk_adim'] = [
                'current' => min($total_correct, 1),
                'target' => 1,
                'completed' => $total_correct >= 1,
                'name' => 'İlk Adım',
                'description' => 'İlk doğru cevabını ver'
            ];

            // 2. Puan Avcısı - 1000 puan
            $stmt = $this->pdo->prepare("SELECT score FROM leaderboard WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $current_score = $stmt->fetchColumn() ?: 0;
            $progress['puan_avcisi_1000'] = [
                'current' => $current_score,
                'target' => 1000,
                'completed' => $current_score >= 1000,
                'name' => 'Puan Avcısı',
                'description' => '1000 puan topla'
            ];

            // 3. Gece Kuşu - Gece saatlerinde oyun
            $stmt = $this->pdo->prepare("SELECT achievement_key FROM user_achievements WHERE user_id = ? AND achievement_key = 'gece_kusu'");
            $stmt->execute([$user_id]);
            $has_gece_kusu = $stmt->fetch() ? true : false;
            $current_hour = (int)date('H');
            $is_night_time = ($current_hour >= 0 && $current_hour <= 4);

            $progress['gece_kusu'] = [
                'current' => $has_gece_kusu ? 1 : ($is_night_time ? 1 : 0),
                'target' => 1,
                'completed' => $has_gece_kusu,
                'name' => 'Gece Kuşu',
                'description' => 'Gece saatlerinde (00:00-04:00) oyun oyna',
                'hint' => $is_night_time && !$has_gece_kusu ? 'Şimdi bir soru çöz!' : ''
            ];

            // 4. Meraklı - Tüm kategorilerde oyun
            $stmt = $this->pdo->prepare("SELECT COUNT(DISTINCT category) as unique_categories FROM user_stats WHERE user_id = ? AND total_questions > 0");
            $stmt->execute([$user_id]);
            $unique_categories = $stmt->fetchColumn() ?: 0;

            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM categories WHERE is_active = 1");
            $stmt->execute();
            $total_categories = $stmt->fetchColumn() ?: 0;

            $progress['merakli'] = [
                'current' => $unique_categories,
                'target' => $total_categories,
                'completed' => $unique_categories >= $total_categories && $total_categories > 0,
                'name' => 'Meraklı',
                'description' => 'Tüm kategorilerde en az 1 soru çöz'
            ];

            // 5. Koleksiyoncu - 10 başarım toplama
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM user_achievements WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $achievement_count = $stmt->fetchColumn() ?: 0;

            $progress['koleksiyoncu'] = [
                'current' => $achievement_count,
                'target' => 10,
                'completed' => $achievement_count >= 10,
                'name' => 'Koleksiyoncu',
                'description' => '10 başarım topla'
            ];

            return ['success' => true, 'data' => $progress];

        } catch (Exception $e) {
            error_log("Achievement progress error: " . $e->getMessage());
            error_log("Achievement progress stack trace: " . $e->getTraceAsString());
            return ['success' => false, 'message' => 'Başarım ilerlemesi alınamadı: ' . $e->getMessage()];
        }
    }
}
