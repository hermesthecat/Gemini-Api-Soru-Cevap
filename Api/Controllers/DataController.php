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
        $user_data['score'] = $stmt_score->fetchColumn() ?: 0;

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
            FROM user_achievements ua
            JOIN achievements a ON ua.achievement_key = a.achievement_key
            WHERE ua.user_id = ? 
            ORDER BY ua.achieved_at DESC
        ");
        $stmt->execute([$_SESSION['user_id']]);
        return ['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
    }
}
