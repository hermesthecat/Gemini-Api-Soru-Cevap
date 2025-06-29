<?php

class UserController
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    private function generateCsrfToken()
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public function register($data)
    {
        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';

        if (empty($username) || empty($password)) {
            http_response_code(400); // Bad Request
            return ['success' => false, 'message' => 'Kullanıcı adı ve şifre boş olamaz.'];
        }
        if (strlen($password) < 6) {
            http_response_code(400); // Bad Request
            return ['success' => false, 'message' => 'Şifre en az 6 karakter olmalıdır.'];
        }

        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            http_response_code(409); // Conflict
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
    }

    public function login($data)
    {
        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';

        if (empty($username) || empty($password)) {
            http_response_code(400); // Bad Request
            return ['success' => false, 'message' => 'Kullanıcı adı ve şifre boş olamaz.'];
        }

        $stmt = $this->pdo->prepare("SELECT id, username, password, role, failed_login_attempts, last_login_attempt FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Rate Limiting Kontrolü
            $lockout_time = 15 * 60; // 15 dakika
            $max_attempts = 5;

            if ($user['failed_login_attempts'] >= $max_attempts) {
                $time_since_last = time() - strtotime($user['last_login_attempt']);
                if ($time_since_last < $lockout_time) {
                    http_response_code(429); // Too Many Requests
                    $remaining_time = ceil(($lockout_time - $time_since_last) / 60);
                    return ['success' => false, 'message' => "Çok fazla başarısız deneme. Lütfen {$remaining_time} dakika sonra tekrar deneyin."];
                }
            }
        }

        if ($user && password_verify($password, $user['password'])) {
            // Başarılı giriş: deneme sayacını sıfırla
            if ($user['failed_login_attempts'] > 0) {
                $stmt = $this->pdo->prepare("UPDATE users SET failed_login_attempts = 0, last_login_attempt = NULL WHERE id = ?");
                $stmt->execute([$user['id']]);
            }

            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_role'] = $user['role'];
            $csrf_token = $this->generateCsrfToken();
            return [
                'success' => true,
                'message' => 'Giriş başarılı!',
                'data' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'role' => $user['role'],
                    'csrf_token' => $csrf_token
                ]
            ];
        } else {
            // Başarısız giriş: deneme sayacını artır
            if ($user) {
                $stmt = $this->pdo->prepare("UPDATE users SET failed_login_attempts = failed_login_attempts + 1, last_login_attempt = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt->execute([$user['id']]);
            }

            http_response_code(401); // Unauthorized
            return ['success' => false, 'message' => 'Kullanıcı adı veya şifre hatalı.'];
        }
    }

    public function logout()
    {
        session_destroy();
        return ['success' => true, 'message' => 'Çıkış yapıldı.'];
    }

    public function checkSession()
    {
        if (isset($_SESSION['user_id'])) {
            $csrf_token = $this->generateCsrfToken();
            return [
                'success' => true,
                'message' => 'Oturum aktif.',
                'data' => [
                    'id' => $_SESSION['user_id'],
                    'username' => $_SESSION['username'],
                    'role' => $_SESSION['user_role'],
                    'csrf_token' => $csrf_token
                ]
            ];
        } else {
            return ['success' => false, 'message' => 'Oturum bulunamadı.'];
        }
    }
}
