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
}
