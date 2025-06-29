<?php

class UserController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function register($data) {
        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';

        if (empty($username) || empty($password)) {
            return ['success' => false, 'message' => 'Kullanıcı adı ve şifre boş olamaz.'];
        }
        if (strlen($password) < 6) {
            return ['success' => false, 'message' => 'Şifre en az 6 karakter olmalıdır.'];
        }

        try {
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'Bu kullanıcı adı zaten alınmış.'];
            }

            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $this->pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
            $stmt->execute([$username, $hashed_password]);
            $user_id = $this->pdo->lastInsertId();

            // Yeni kullanıcı için leaderboard'a 0 skorla ekle
            $stmt = $this->pdo->prepare("INSERT INTO leaderboard (user_id, score) VALUES (?, 0)");
            $stmt->execute([$user_id]);

            return ['success' => true, 'message' => 'Kayıt başarılı! Şimdi giriş yapabilirsiniz.'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()];
        }
    }

    public function login($data) {
        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';

        if (empty($username) || empty($password)) {
            return ['success' => false, 'message' => 'Kullanıcı adı ve şifre boş olamaz.'];
        }

        try {
            $stmt = $this->pdo->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_role'] = $user['role'];
                return [
                    'success' => true,
                    'message' => 'Giriş başarılı!',
                    'data' => ['id' => $user['id'], 'username' => $user['username'], 'role' => $user['role']]
                ];
            } else {
                return ['success' => false, 'message' => 'Kullanıcı adı veya şifre hatalı.'];
            }
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()];
        }
    }

    public function logout() {
        session_destroy();
        return ['success' => true, 'message' => 'Çıkış yapıldı.'];
    }

    public function checkSession() {
        if (isset($_SESSION['user_id'])) {
            return [
                'success' => true,
                'message' => 'Oturum aktif.',
                'data' => ['id' => $_SESSION['user_id'], 'username' => $_SESSION['username'], 'role' => $_SESSION['user_role']]
            ];
        } else {
            return ['success' => false, 'message' => 'Oturum bulunamadı.'];
        }
    }
} 