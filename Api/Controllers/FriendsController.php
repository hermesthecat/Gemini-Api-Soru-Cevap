<?php

class FriendsController
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Kullanıcıları kullanıcı adına göre arar.
     * Kendisini, zaten arkadaş olanları veya bekleyen isteği olanları hariç tutar.
     */
    public function searchUsers($data)
    {
        $current_user_id = $_SESSION['user_id'];
        $search_term = $data['username'] ?? '';

        if (empty($search_term)) {
            return ['success' => true, 'data' => []];
        }

        $stmt = $this->pdo->prepare("
            SELECT u.id, u.username 
            FROM users u
            WHERE u.username LIKE ? 
              AND u.id != ?
              AND NOT EXISTS (
                  SELECT 1 FROM friends f 
                  WHERE (f.user_one_id = u.id AND f.user_two_id = ?) 
                     OR (f.user_one_id = ? AND f.user_two_id = u.id)
              )
            LIMIT 10
        ");
        $stmt->execute(["%$search_term%", $current_user_id, $current_user_id, $current_user_id]);

        return ['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
    }

    /**
     * Bir kullanıcıya arkadaşlık isteği gönderir.
     */
    public function sendRequest($data)
    {
        $current_user_id = $_SESSION['user_id'];
        $friend_id = $data['user_id'] ?? 0;

        if ($current_user_id == $friend_id || $friend_id == 0) {
            return ['success' => false, 'message' => 'Geçersiz işlem.'];
        }

        // Kullanıcıların ID'lerini her zaman küçük olan önce gelecek şekilde sırala
        $user_one_id = min($current_user_id, $friend_id);
        $user_two_id = max($current_user_id, $friend_id);

        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO friends (user_one_id, user_two_id, status, action_user_id) 
                VALUES (?, ?, 'pending', ?)
            ");
            $stmt->execute([$user_one_id, $user_two_id, $current_user_id]);
            return ['success' => true, 'message' => 'Arkadaşlık isteği gönderildi.'];
        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) { // Duplicate entry
                return ['success' => false, 'message' => 'Bu kullanıcıyla zaten bir arkadaşlık isteğiniz veya ilişkiniz var.'];
            }
            throw $e; // Diğer hataları genel hata yakalayıcıya bırak
        }
    }

    /**
     * Gelen arkadaşlık isteklerini listeler.
     */
    public function getPendingRequests()
    {
        $current_user_id = $_SESSION['user_id'];

        $stmt = $this->pdo->prepare("
            SELECT f.id as request_id, u.id as user_id, u.username
            FROM friends f
            JOIN users u ON u.id = f.action_user_id
            WHERE ((f.user_one_id = ? AND f.action_user_id != ?) OR (f.user_two_id = ? AND f.action_user_id != ?))
              AND f.status = 'pending'
        ");
        $stmt->execute([$current_user_id, $current_user_id, $current_user_id, $current_user_id]);

        return ['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
    }

    /**
     * Bir arkadaşlık isteğini kabul eder veya reddeder.
     */
    public function respondToRequest($data)
    {
        $current_user_id = $_SESSION['user_id'];
        $request_id = $data['request_id'] ?? 0;
        $response = $data['response'] ?? ''; // 'accept' or 'decline'

        if ($request_id == 0 || !in_array($response, ['accept', 'decline'])) {
            return ['success' => false, 'message' => 'Geçersiz istek.'];
        }

        $new_status = ($response === 'accept') ? 'accepted' : 'declined';

        $stmt = $this->pdo->prepare("
            UPDATE friends 
            SET status = ?, action_user_id = ?
            WHERE id = ? 
              AND (user_one_id = ? OR user_two_id = ?)
              AND status = 'pending'
              AND action_user_id != ?
        ");
        $stmt->execute([$new_status, $current_user_id, $request_id, $current_user_id, $current_user_id, $current_user_id]);

        if ($stmt->rowCount() > 0) {
            return ['success' => true, 'message' => 'İstek ' . ($response === 'accept' ? 'kabul edildi.' : 'reddedildi.')];
        }

        return ['success' => false, 'message' => 'İşlem başarısız oldu veya bu isteğe yanıt verme yetkiniz yok.'];
    }

    /**
     * Kabul edilmiş arkadaşları listeler.
     */
    public function getFriendsList()
    {
        $current_user_id = $_SESSION['user_id'];

        $stmt = $this->pdo->prepare("
            SELECT 
                u.id, 
                u.username, 
                l.score,
                (SELECT f.id FROM friends f WHERE (f.user_one_id = u.id AND f.user_two_id = ?) OR (f.user_one_id = ? AND f.user_two_id = u.id)) as friendship_id
            FROM users u
            JOIN leaderboard l ON u.id = l.user_id
            WHERE EXISTS (
                SELECT 1 FROM friends f
                WHERE ((f.user_one_id = u.id AND f.user_two_id = ?) OR (f.user_one_id = ? AND f.user_two_id = u.id))
                  AND f.status = 'accepted'
            )
        ");
        $stmt->execute([$current_user_id, $current_user_id, $current_user_id, $current_user_id]);

        return ['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
    }

    /**
     * Bir arkadaşı siler.
     */
    public function removeFriend($data)
    {
        $friendship_id = $data['friendship_id'] ?? 0;
        $current_user_id = $_SESSION['user_id'];

        if ($friendship_id == 0) {
            return ['success' => false, 'message' => 'Geçersiz ID.'];
        }

        $stmt = $this->pdo->prepare("
            DELETE FROM friends 
            WHERE id = ? AND (user_one_id = ? OR user_two_id = ?) AND status = 'accepted'
        ");
        $stmt->execute([$friendship_id, $current_user_id, $current_user_id]);

        if ($stmt->rowCount() > 0) {
            return ['success' => true, 'message' => 'Arkadaş silindi.'];
        }

        return ['success' => false, 'message' => 'İşlem başarısız.'];
    }
}
