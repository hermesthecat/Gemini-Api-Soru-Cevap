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

    /**
     * Get public profile by username or user ID
     */
    public function getPublicProfile($data)
    {
        $username = $data['username'] ?? '';
        $user_id = (int)($data['user_id'] ?? 0);

        if (empty($username) && $user_id <= 0) {
            return ['success' => false, 'message' => 'Kullanıcı adı veya ID gerekli.'];
        }

        try {
            // Get user basic info
            if (!empty($username)) {
                $stmt = $this->pdo->prepare("
                    SELECT id, username, avatar, profile_visibility, created_at, login_streak, longest_login_streak, coins
                    FROM users
                    WHERE username = ?
                ");
                $stmt->execute([$username]);
            } else {
                $stmt = $this->pdo->prepare("
                    SELECT id, username, avatar, profile_visibility, created_at, login_streak, longest_login_streak, coins
                    FROM users
                    WHERE id = ?
                ");
                $stmt->execute([$user_id]);
            }

            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$user) {
                return ['success' => false, 'message' => 'Kullanıcı bulunamadı.'];
            }

            // Check visibility permissions
            $current_user_id = $_SESSION['user_id'] ?? null;
            $is_own_profile = $current_user_id == $user['id'];

            if (!$is_own_profile && $user['profile_visibility'] === 'private') {
                return ['success' => false, 'message' => 'Bu profil gizli olarak ayarlanmış.'];
            }

            if (!$is_own_profile && $user['profile_visibility'] === 'friends') {
                // Check if users are friends
                if ($current_user_id) {
                    $stmt = $this->pdo->prepare("
                        SELECT id FROM friends
                        WHERE ((user_one_id = ? AND user_two_id = ?) OR (user_one_id = ? AND user_two_id = ?))
                        AND status = 'accepted'
                    ");
                    $stmt->execute([$current_user_id, $user['id'], $user['id'], $current_user_id]);

                    if (!$stmt->fetch()) {
                        return ['success' => false, 'message' => 'Bu profili görüntülemek için arkadaş olmanız gerekli.'];
                    }
                } else {
                    return ['success' => false, 'message' => 'Bu profili görüntülemek için giriş yapmanız gerekli.'];
                }
            }

            // Get profile statistics
            $profileStats = $this->getProfileStatistics($user['id']);

            return [
                'success' => true,
                'data' => [
                    'user' => [
                        'id' => (int)$user['id'],
                        'username' => $user['username'],
                        'avatar' => $user['avatar'],
                        'profile_visibility' => $user['profile_visibility'],
                        'member_since' => $user['created_at'],
                        'login_streak' => (int)$user['login_streak'],
                        'longest_login_streak' => (int)$user['longest_login_streak'],
                        'coins' => (int)$user['coins'],
                        'is_own_profile' => $is_own_profile
                    ],
                    'statistics' => $profileStats
                ]
            ];

        } catch (PDOException $e) {
            error_log("Get public profile error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Profil bilgileri alınırken hata oluştu.'];
        }
    }

    /**
     * Search users for public profiles
     */
    public function searchUsers($data)
    {
        $query = trim($data['query'] ?? '');
        $limit = min((int)($data['limit'] ?? 20), 50); // Max 50 users
        $offset = max((int)($data['offset'] ?? 0), 0);

        if (strlen($query) < 2) {
            return ['success' => false, 'message' => 'Arama terimi en az 2 karakter olmalıdır.'];
        }

        try {
            // Search users with public or friends visibility
            $stmt = $this->pdo->prepare("
                SELECT
                    u.id,
                    u.username,
                    u.avatar,
                    u.profile_visibility,
                    u.login_streak,
                    l.total_score,
                    COUNT(ua.id) as achievement_count
                FROM users u
                LEFT JOIN leaderboard l ON u.id = l.user_id
                LEFT JOIN user_achievements ua ON u.id = ua.user_id
                WHERE u.username LIKE ?
                AND u.profile_visibility IN ('public', 'friends')
                GROUP BY u.id, u.username, u.avatar, u.profile_visibility, u.login_streak, l.total_score
                ORDER BY l.total_score DESC, u.username ASC
                LIMIT $limit OFFSET $offset
            ");

            $searchTerm = '%' . $query . '%';
            $stmt->execute([$searchTerm]);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get total count for pagination
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as total
                FROM users
                WHERE username LIKE ?
                AND profile_visibility IN ('public', 'friends')
            ");
            $stmt->execute([$searchTerm]);
            $total_count = $stmt->fetchColumn();

            return [
                'success' => true,
                'data' => [
                    'users' => array_map(function($user) {
                        return [
                            'id' => (int)$user['id'],
                            'username' => $user['username'],
                            'avatar' => $user['avatar'],
                            'profile_visibility' => $user['profile_visibility'],
                            'login_streak' => (int)$user['login_streak'],
                            'total_score' => (int)($user['total_score'] ?? 0),
                            'achievement_count' => (int)$user['achievement_count']
                        ];
                    }, $users),
                    'pagination' => [
                        'total_count' => (int)$total_count,
                        'limit' => $limit,
                        'offset' => $offset,
                        'has_more' => ($offset + $limit) < $total_count
                    ]
                ]
            ];

        } catch (PDOException $e) {
            error_log("Search users error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Kullanıcı arama işlemi sırasında hata oluştu.'];
        }
    }

    /**
     * Update profile visibility setting
     */
    public function updateProfileVisibility($data)
    {
        if (!isset($_SESSION['user_id'])) {
            return ['success' => false, 'message' => 'Bu işlem için giriş yapmalısınız.'];
        }

        $visibility = $data['visibility'] ?? '';
        $valid_options = ['public', 'friends', 'private'];

        if (!in_array($visibility, $valid_options)) {
            return ['success' => false, 'message' => 'Geçersiz gizlilik ayarı.'];
        }

        try {
            $stmt = $this->pdo->prepare("
                UPDATE users
                SET profile_visibility = ?
                WHERE id = ?
            ");
            $stmt->execute([$visibility, $_SESSION['user_id']]);

            return [
                'success' => true,
                'message' => 'Profil gizlilik ayarı güncellendi.',
                'data' => ['visibility' => $visibility]
            ];

        } catch (PDOException $e) {
            error_log("Update profile visibility error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Gizlilik ayarı güncellenirken hata oluştu.'];
        }
    }

    /**
     * Get aggregated profile statistics
     */
    private function getProfileStatistics($user_id)
    {
        $stats = [];

        try {
            // Get leaderboard position and total score
            $stmt = $this->pdo->prepare("
                SELECT total_score, total_questions, correct_answers
                FROM leaderboard
                WHERE user_id = ?
            ");
            $stmt->execute([$user_id]);
            $leaderboard = $stmt->fetch(PDO::FETCH_ASSOC);

            $stats['total_score'] = (int)($leaderboard['total_score'] ?? 0);
            $stats['total_questions'] = (int)($leaderboard['total_questions'] ?? 0);
            $stats['correct_answers'] = (int)($leaderboard['correct_answers'] ?? 0);
            $stats['accuracy_percentage'] = $stats['total_questions'] > 0
                ? round(($stats['correct_answers'] / $stats['total_questions']) * 100, 1)
                : 0;

            // Get global rank
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) + 1 as rank
                FROM leaderboard
                WHERE total_score > (
                    SELECT total_score FROM leaderboard WHERE user_id = ?
                )
            ");
            $stmt->execute([$user_id]);
            $stats['global_rank'] = (int)$stmt->fetchColumn();

            // Get achievements count
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM user_achievements WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $stats['achievement_count'] = (int)$stmt->fetchColumn();

            // Get recent achievements
            $stmt = $this->pdo->prepare("
                SELECT a.name, a.description, a.icon, ua.earned_at
                FROM user_achievements ua
                JOIN achievements a ON ua.achievement_id = a.id
                WHERE ua.user_id = ?
                ORDER BY ua.earned_at DESC
                LIMIT 5
            ");
            $stmt->execute([$user_id]);
            $stats['recent_achievements'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get category stats
            $stmt = $this->pdo->prepare("
                SELECT category,
                       SUM(total_questions) as questions,
                       SUM(correct_answers) as correct,
                       ROUND(AVG(correct_answers / total_questions * 100), 1) as accuracy
                FROM user_stats
                WHERE user_id = ? AND total_questions > 0
                GROUP BY category
                ORDER BY accuracy DESC, questions DESC
                LIMIT 10
            ");
            $stmt->execute([$user_id]);
            $stats['category_stats'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get quest completion stats
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as completed_quests
                FROM quest_history
                WHERE user_id = ? AND status = 'completed'
            ");
            $stmt->execute([$user_id]);
            $stats['completed_quests'] = (int)$stmt->fetchColumn();

            // Get duel stats
            $stmt = $this->pdo->prepare("
                SELECT
                    COUNT(*) as total_duels,
                    SUM(CASE WHEN winner_id = ? THEN 1 ELSE 0 END) as wins
                FROM duels
                WHERE (challenger_id = ? OR opponent_id = ?)
                AND status = 'completed'
            ");
            $stmt->execute([$user_id, $user_id, $user_id]);
            $duel_stats = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats['duel_stats'] = [
                'total_duels' => (int)$duel_stats['total_duels'],
                'wins' => (int)$duel_stats['wins'],
                'win_rate' => $duel_stats['total_duels'] > 0
                    ? round(($duel_stats['wins'] / $duel_stats['total_duels']) * 100, 1)
                    : 0
            ];

            return $stats;

        } catch (PDOException $e) {
            error_log("Get profile statistics error: " . $e->getMessage());
            return [];
        }
    }

}
