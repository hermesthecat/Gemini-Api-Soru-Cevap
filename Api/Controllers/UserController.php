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

        // Hoş geldin bonuslarını settings'den al
        $stmt = $this->pdo->prepare("
            SELECT setting_key, setting_value
            FROM settings
            WHERE setting_key IN ('welcome_bonus', 'welcome_lifeline_fifty_fifty', 'welcome_lifeline_extra_time', 'welcome_lifeline_pass')
        ");
        $stmt->execute();
        $welcome_settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        $welcome_bonus = (int)($welcome_settings['welcome_bonus'] ?? 100);
        $welcome_fifty_fifty = (int)($welcome_settings['welcome_lifeline_fifty_fifty'] ?? 3);
        $welcome_extra_time = (int)($welcome_settings['welcome_lifeline_extra_time'] ?? 3);
        $welcome_pass = (int)($welcome_settings['welcome_lifeline_pass'] ?? 3);

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare("
            INSERT INTO users (username, password, coins, lifeline_fifty_fifty, lifeline_extra_time, lifeline_pass)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$username, $hashed_password, $welcome_bonus, $welcome_fifty_fifty, $welcome_extra_time, $welcome_pass]);
        $user_id = $this->pdo->lastInsertId();


        // Yeni kullanıcı için leaderboard'a 0 skorla ekle
        $stmt = $this->pdo->prepare("INSERT INTO leaderboard (user_id, score) VALUES (?, 0)"); // Leaderboard kaydı
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

        $stmt = $this->pdo->prepare("SELECT u.id, u.username, u.password, u.role, u.failed_login_attempts, u.last_login_attempt, u.last_login_date, u.current_login_streak, u.coins, u.lifeline_fifty_fifty, u.lifeline_extra_time, u.lifeline_pass
            FROM users u
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
            $daily_reward = $this->processLoginReward($user['id'], $user['last_login_date'], $user['current_login_streak']);

            // Eğer ödül varsa, coin miktarını güncelle
            if ($daily_reward) {
                $user['coins'] += $daily_reward['coins_earned'];
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
                $_SESSION['role'] = $user['role'];
                $_SESSION['coins'] = $user['coins'];
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
                    'role' => $_SESSION['role'],
                    'coins' => $_SESSION['coins'] ?? 0,
                    'lifelines' => $_SESSION['lifelines'] ?? ['fiftyFifty' => 0, 'extraTime' => 0, 'pass' => 0],
                    'csrf_token' => $csrf_token
                ]
            ];
        } else {
            return ['success' => false, 'message' => 'Oturum bulunamadı.'];
        }
    }

    /**
     * Günlük giriş ödülü işleme - Settings kullanarak dinamik hesaplama
     */
    private function processLoginReward($user_id, $last_login_date, $current_streak)
    {
        $today = date('Y-m-d');

        // Bugün zaten giriş yapıldıysa ödül yok
        if ($last_login_date === $today) {
            return null;
        }

        // QuestController ile streak güncelleme (bu current_login_streak'i günceller ve quest sistemi tetikler)
        require_once __DIR__ . '/QuestController.php';
        $new_streak = QuestController::updateLoginStreak($this->pdo, $user_id);

        // Settings'den ödül parametrelerini al
        $settings = $this->getLoginRewardSettings();

        // Ödül hesapla: base + (streak * bonus), max'a kadar
        $reward_coins = min(
            $settings['max_reward'],
            $settings['base_reward'] + (($new_streak - 1) * $settings['streak_bonus'])
        );

        // Coin ödülü ver
        $stmt = $this->pdo->prepare("UPDATE users SET coins = coins + ? WHERE id = ?");
        $stmt->execute([$reward_coins, $user_id]);

        return [
            'coins_earned' => $reward_coins,
            'streak' => $new_streak
        ];
    }

    /**
     * Login reward settings'lerini getir
     */
    private function getLoginRewardSettings()
    {
        $stmt = $this->pdo->prepare("
            SELECT setting_key, setting_value
            FROM settings
            WHERE setting_key IN ('login_base_reward', 'login_max_reward', 'login_streak_bonus')
        ");
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        return [
            'base_reward' => (int)($results['login_base_reward'] ?? 10),
            'max_reward' => (int)($results['login_max_reward'] ?? 50),
            'streak_bonus' => (int)($results['login_streak_bonus'] ?? 5)
        ];
    }

}
