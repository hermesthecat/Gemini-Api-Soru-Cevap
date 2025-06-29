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

        // Liderlik tablosundan skor ve jeton al
        $stmt_score = $this->pdo->prepare("SELECT score, coins FROM leaderboard WHERE user_id = ?");
        $stmt_score->execute([$user_id]);
        $leaderboard_data = $stmt_score->fetch(PDO::FETCH_ASSOC);

        $user_data['score'] = $leaderboard_data['score'] ?? 0;
        $user_data['coins'] = $leaderboard_data['coins'] ?? 0;

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
            SELECT u.username, u.avatar, l.score 
            FROM leaderboard l
            JOIN users u ON l.user_id = u.id
            ORDER BY l.score DESC, l.last_updated ASC 
            LIMIT 10
        ");
        $stmt->execute();
        return ['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
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
        $user_role = $_SESSION['user_role'];

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
            LEFT JOIN user_announcements ua ON a.id = ua.announcement_id AND ua.user_id = :user_id
            WHERE a.is_active = TRUE
              AND a.start_date <= CURRENT_TIMESTAMP
              AND a.end_date >= CURRENT_TIMESTAMP
              AND a.target_group IN ($placeholders)
              AND ua.id IS NULL -- Sadece okunmamış olanları getir
            ORDER BY a.created_at DESC
        ");

        $params = array_merge([$user_role], [$user_id]);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        foreach ($target_groups as $k => $group) {
            $stmt->bindValue(($k + 1), $group);
        }

        $stmt->execute();
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
}
