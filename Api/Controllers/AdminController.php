<?php

class AdminController
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    private function checkAdmin()
    {
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
            http_response_code(403); // Forbidden
            return ['success' => false, 'message' => 'Bu alana erişim yetkiniz yok.'];
        }
        return true;
    }

    public function getDashboardData()
    {
        if (($check = $this->checkAdmin()) !== true) return $check;

        $stmt_users = $this->pdo->query("SELECT COUNT(*) FROM users");
        $total_users = $stmt_users->fetchColumn();

        $stmt_questions = $this->pdo->query("SELECT SUM(total_questions) FROM user_stats");
        $total_questions = $stmt_questions->fetchColumn() ?: 0;

        return [
            'success' => true,
            'data' => [
                'total_users' => $total_users,
                'total_questions_answered' => $total_questions,
            ]
        ];
    }

    public function getAllUsers()
    {
        if (($check = $this->checkAdmin()) !== true) return $check;

        $stmt = $this->pdo->query("
            SELECT u.id, u.username, u.role, u.created_at, u.avatar, l.score 
            FROM users u 
            LEFT JOIN leaderboard l ON u.id = l.user_id 
            ORDER BY u.created_at DESC
        ");
        return ['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
    }

    public function deleteUser($data)
    {
        if (($check = $this->checkAdmin()) !== true) return $check;

        $user_id_to_delete = $data['user_id'] ?? 0;
        if ($user_id_to_delete == $_SESSION['user_id']) {
            return ['success' => false, 'message' => 'Kendinizi silemezsiniz.'];
        }
        if ($user_id_to_delete > 0) {
            $stmt = $this->pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$user_id_to_delete]);
            $success = $stmt->rowCount() > 0;
            return ['success' => $success, 'message' => $success ? 'Kullanıcı silindi.' : 'Kullanıcı bulunamadı.'];
        }
        return ['success' => false, 'message' => 'Geçersiz kullanıcı ID.'];
    }

    public function updateUserRole($data)
    {
        if (($check = $this->checkAdmin()) !== true) return $check;

        $user_id_to_update = $data['user_id'] ?? 0;
        $new_role = $data['new_role'] ?? '';

        if ($user_id_to_update == $_SESSION['user_id']) {
            return ['success' => false, 'message' => 'Kendi rolünüzü değiştiremezsiniz.'];
        }

        if ($user_id_to_update > 0 && ($new_role === 'admin' || $new_role === 'user')) {
            $stmt = $this->pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
            $stmt->execute([$new_role, $user_id_to_update]);
            $success = $stmt->rowCount() > 0;
            return ['success' => $success, 'message' => $success ? 'Kullanıcı rolü güncellendi.' : 'İşlem başarısız.'];
        }

        return ['success' => false, 'message' => 'Geçersiz kullanıcı ID veya rol.'];
    }

    public function getAnnouncements()
    {
        if (($check = $this->checkAdmin()) !== true) return $check;

        $stmt = $this->pdo->query("
            SELECT a.id, a.title, a.target_group, a.start_date, a.end_date, a.is_active, u.username as author_name
            FROM announcements a
            JOIN users u ON a.author_id = u.id
            ORDER BY a.created_at DESC
        ");
        return ['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
    }

    public function createAnnouncement($data)
    {
        if (($check = $this->checkAdmin()) !== true) return $check;

        $title = $data['title'] ?? '';
        $content = $data['content'] ?? '';
        $target_group = $data['target_group'] ?? 'all';
        $end_date = $data['end_date'] ?? '';

        if (empty($title) || empty($content) || empty($end_date)) {
            return ['success' => false, 'message' => 'Lütfen tüm alanları doldurun.'];
        }

        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO announcements (author_id, title, content, target_group, end_date) 
                 VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->execute([$_SESSION['user_id'], $title, $content, $target_group, $end_date]);
            return ['success' => true, 'message' => 'Duyuru başarıyla oluşturuldu.'];
        } catch (PDOException $e) {
            error_log("Announcement creation error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Duyuru oluşturulurken bir hata oluştu.'];
        }
    }

    public function deleteAnnouncement($data)
    {
        if (($check = $this->checkAdmin()) !== true) return $check;

        $announcement_id = $data['announcement_id'] ?? 0;
        if ($announcement_id > 0) {
            $stmt = $this->pdo->prepare("DELETE FROM announcements WHERE id = ?");
            $stmt->execute([$announcement_id]);
            return ['success' => true, 'message' => 'Duyuru silindi.'];
        }
        return ['success' => false, 'message' => 'Geçersiz duyuru ID.'];
    }

    public function getAdvancedStats() {
        if (($check = $this->checkAdmin()) !== true) return $check;

        // En çok oynanan 5 kategori
        $stmt_categories = $this->pdo->query("
            SELECT category, COUNT(*) as play_count 
            FROM user_stats 
            GROUP BY category 
            ORDER BY play_count DESC 
            LIMIT 5
        ");
        $most_played_categories = $stmt_categories->fetchAll(PDO::FETCH_ASSOC);

        // Son 7 gündeki yeni kullanıcı kayıtları
        $stmt_users = $this->pdo->query("
            SELECT DATE(created_at) as registration_date, COUNT(*) as user_count 
            FROM users 
            WHERE created_at >= CURDATE() - INTERVAL 7 DAY
            GROUP BY registration_date
            ORDER BY registration_date ASC
        ");
        $new_users_last_7_days = $stmt_users->fetchAll(PDO::FETCH_ASSOC);

        // Zorluğa göre cevap dağılımı
        $stmt_answers = $this->pdo->query("
            SELECT 
                difficulty, 
                SUM(correct_answers) as correct, 
                SUM(total_questions - correct_answers) as incorrect
            FROM user_stats 
            GROUP BY difficulty
        ");
        $answer_distribution = $stmt_answers->fetchAll(PDO::FETCH_ASSOC);

        return [
            'success' => true,
            'data' => [
                'most_played_categories' => $most_played_categories,
                'new_users_last_7_days' => $new_users_last_7_days,
                'answer_distribution' => $answer_distribution,
            ]
        ];
    }
}
