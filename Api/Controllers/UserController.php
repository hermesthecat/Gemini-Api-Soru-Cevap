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

        // Yeni kullanıcı için varsayılan bir avatar ata
        $default_avatar = 'avatar' . rand(1, 10) . '.svg';
        $stmt_avatar = $this->pdo->prepare("UPDATE users SET avatar = ? WHERE id = ?");
        $stmt_avatar->execute([$default_avatar, $user_id]);

        // Yeni kullanıcı için leaderboard'a 0 skorla ekle
        $stmt = $this->pdo->prepare("INSERT INTO leaderboard (user_id, score, coins) VALUES (?, 0, 100)"); // 100 başlangıç jetonu
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

        $stmt = $this->pdo->prepare("SELECT u.id, u.username, u.password, u.role, u.failed_login_attempts, u.last_login_attempt, u.avatar, u.last_login_date, u.login_streak, l.coins, l.lifeline_fifty_fifty, l.lifeline_extra_time, l.lifeline_pass 
            FROM users u
            JOIN leaderboard l ON u.id = l.user_id
            WHERE u.username = ?");
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

            // --- Günlük Giriş Ödülü Mantığı ---
            $today = date('Y-m-d');
            $last_login_date = $user['last_login_date'];
            $login_streak = $user['login_streak'];
            $daily_reward = null;

            if ($last_login_date != $today) {
                $yesterday = date('Y-m-d', strtotime('-1 day'));
                if ($last_login_date == $yesterday) {
                    // Streak devam ediyor
                    $login_streak++;
                } else {
                    // Streak kırıldı veya ilk giriş
                    $login_streak = 1;
                }

                // Ödülü hesapla (10 jeton + seri başına 5, en fazla 50)
                $reward_coins = min(50, 10 + ($login_streak * 5));

                // Veritabanını güncelle
                $this->pdo->prepare("UPDATE users SET last_login_date = ?, login_streak = ? WHERE id = ?")
                     ->execute([$today, $login_streak, $user['id']]);

                $this->pdo->prepare("UPDATE leaderboard SET coins = coins + ? WHERE user_id = ?")
                     ->execute([$reward_coins, $user['id']]);

                // Kullanıcıya bilgi vermek için veriyi ayarla
                $daily_reward = [
                    'coins_earned' => $reward_coins,
                    'streak' => $login_streak
                ];
                
                // Yanıtta ve session'da kullanılacak jeton miktarını güncelle
                $user['coins'] += $reward_coins;
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
                $_SESSION['user_avatar'] = $user['avatar'];
                $_SESSION['user_coins'] = $user['coins'];
                $_SESSION['lifelines'] = [
                    'fiftyFifty' => $user['lifeline_fifty_fifty'],
                    'extraTime' => $user['lifeline_extra_time'],
                    'pass' => $user['lifeline_pass']
                ];
                $csrf_token = $this->generateCsrfToken();
                return [
                    'success' => true,
                    'message' => 'Giriş başarılı!',
                    'data' => [
                        'id' => $user['id'],
                        'username' => $user['username'],
                        'role' => $user['role'],
                        'avatar' => $user['avatar'],
                        'coins' => $user['coins'],
                        'lifelines' => $_SESSION['lifelines'],
                        'csrf_token' => $csrf_token
                    ],
                    'daily_reward' => $daily_reward
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
        } else {
            http_response_code(404); // Not Found
            return ['success' => false, 'message' => 'Kullanıcı bulunamadı.'];
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
                    'avatar' => $_SESSION['user_avatar'] ?? 'default.svg',
                    'coins' => $_SESSION['user_coins'] ?? 0,
                    'lifelines' => $_SESSION['lifelines'] ?? ['fiftyFifty' => 0, 'extraTime' => 0, 'pass' => 0],
                    'csrf_token' => $csrf_token
                ]
            ];
        } else {
            return ['success' => false, 'message' => 'Oturum bulunamadı.'];
        }
    }

    public function updateAvatar($data)
    {
        $user_id = $_SESSION['user_id'];
        $new_avatar = $data['avatar'] ?? '';

        if (empty($new_avatar) || !preg_match('/^avatar\d{1,2}\.svg$/', $new_avatar)) {
            return ['success' => false, 'message' => 'Geçersiz avatar seçimi.'];
        }

        $stmt = $this->pdo->prepare("UPDATE users SET avatar = ? WHERE id = ?");
        $stmt->execute([$new_avatar, $user_id]);

        if ($stmt->rowCount() > 0) {
            $_SESSION['user_avatar'] = $new_avatar;
            return ['success' => true, 'message' => 'Avatar güncellendi.', 'data' => ['avatar' => $new_avatar]];
        }

        return ['success' => false, 'message' => 'Avatar güncellenemedi veya zaten bu avatarı kullanıyorsunuz.'];
    }
}
