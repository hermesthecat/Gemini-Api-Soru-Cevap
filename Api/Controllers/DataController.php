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

        // İstatistikleri al
        $stmt_stats = $this->pdo->prepare("SELECT category, total_questions, correct_answers FROM user_stats WHERE user_id = ?");
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
        $stmt = $this->pdo->prepare("SELECT achievement_key, achieved_at FROM user_achievements WHERE user_id = ? ORDER BY achieved_at DESC");
        $stmt->execute([$_SESSION['user_id']]);
        return ['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
    }
}
