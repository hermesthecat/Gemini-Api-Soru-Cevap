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
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
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
            SELECT u.id, u.username, u.role, u.created_at, u.coins, l.score
            FROM users u
            LEFT JOIN leaderboard l ON u.id = l.user_id
            ORDER BY u.created_at DESC
        ");
        return ['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
    }

    public function getUserStatistics($data)
    {
        if (($check = $this->checkAdmin()) !== true) return $check;

        $user_id = $data['user_id'] ?? 0;
        if ($user_id <= 0) {
            return ['success' => false, 'message' => 'Geçersiz kullanıcı ID'];
        }

        try {
            // Get aggregate statistics
            $stmt = $this->pdo->prepare("
                SELECT
                    SUM(total_questions) as total_games,
                    SUM(correct_answers) as total_correct,
                    AVG(CASE WHEN total_questions > 0 THEN (correct_answers * 100.0 / total_questions) ELSE 0 END) as success_rate,
                    SUM(total_time_spent) as total_time
                FROM user_stats
                WHERE user_id = ?
            ");
            $stmt->execute([$user_id]);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);

            // Get last game date
            $stmt = $this->pdo->prepare("
                SELECT MAX(created_at) as last_game
                FROM user_stats
                WHERE user_id = ?
            ");
            $stmt->execute([$user_id]);
            $lastGame = $stmt->fetchColumn();

            // Get category breakdown
            $stmt = $this->pdo->prepare("
                SELECT
                    category,
                    SUM(total_questions) as questions,
                    SUM(correct_answers) as correct,
                    ROUND(AVG(CASE WHEN total_questions > 0 THEN (correct_answers * 100.0 / total_questions) ELSE 0 END), 1) as success_rate
                FROM user_stats
                WHERE user_id = ?
                GROUP BY category
                ORDER BY questions DESC
            ");
            $stmt->execute([$user_id]);
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'success' => true,
                'data' => [
                    'total_games' => $stats['total_games'] ?? 0,
                    'total_correct' => $stats['total_correct'] ?? 0,
                    'success_rate' => round($stats['success_rate'] ?? 0, 1),
                    'total_time' => $stats['total_time'] ?? 0,
                    'last_game' => $lastGame ?? null,
                    'categories' => $categories
                ]
            ];
        } catch (Exception $e) {
            error_log("Error in getUserStatistics: " . $e->getMessage());
            return ['success' => false, 'message' => 'İstatistikler yüklenirken hata oluştu'];
        }
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

    public function updateUserCoins($data)
    {
        if (($check = $this->checkAdmin()) !== true) return $check;

        $user_id_to_update = $data['user_id'] ?? 0;
        $new_coins = $data['new_coins'] ?? 0;

        if ($user_id_to_update > 0 && is_numeric($new_coins) && $new_coins >= 0 && $new_coins <= 999999) {
            try {
                $stmt = $this->pdo->prepare("UPDATE users SET coins = ? WHERE id = ?");
                $stmt->execute([$new_coins, $user_id_to_update]);

                if ($stmt->rowCount() > 0) {
                    // Session'da coins güncelle (eğer current user ise)
                    if ($user_id_to_update == $_SESSION['user_id']) {
                        $_SESSION['coins'] = $new_coins;
                    }

                    return ['success' => true, 'message' => 'Kullanıcının jeton miktarı güncellendi.'];
                } else {
                    return ['success' => false, 'message' => 'Kullanıcı bulunamadı.'];
                }
            } catch (PDOException $e) {
                error_log("Coin update error: " . $e->getMessage());
                return ['success' => false, 'message' => 'Veritabanı hatası oluştu.'];
            }
        }

        return ['success' => false, 'message' => 'Geçersiz kullanıcı ID veya jeton miktarı.'];
    }

    public function getAnnouncements()
    {
        if (($check = $this->checkAdmin()) !== true) return $check;

        $stmt = $this->pdo->query("
            SELECT a.id, a.title, a.content, a.target_group, a.start_date, a.end_date, a.is_active, a.created_at, u.username as author_name
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

    public function getAdvancedStats()
    {
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

    /**
     * Mağaza istatistiklerini getirir
     */
    public function getShopStats()
    {
        if (($check = $this->checkAdmin()) !== true) return $check;

        // ShopController'dan fiyatları al
        require_once 'ShopController.php';
        $shopController = new ShopController($this->pdo);
        $shopItems = $shopController->getShopItems();

        if (!$shopItems['success']) {
            return ['success' => false, 'message' => 'Mağaza verileri alınamadı'];
        }

        // Purchase_logs tablosu yoksa oluştur (geçici)
        // Bu normalde migration'da olmalı ama hızlı test için burada ekliyoruz
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS purchase_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                item_type VARCHAR(50) NOT NULL,
                item_price INT NOT NULL,
                purchase_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ");

        // Toplam satış sayısı
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM purchase_logs");
        $total_sales = $stmt->fetchColumn();

        // Toplam gelir
        $stmt = $this->pdo->query("SELECT SUM(item_price) FROM purchase_logs");
        $total_revenue = $stmt->fetchColumn() ?: 0;

        // En popüler joker
        $stmt = $this->pdo->query("
            SELECT item_type, COUNT(*) as count
            FROM purchase_logs
            GROUP BY item_type
            ORDER BY count DESC
            LIMIT 1
        ");
        $most_popular_result = $stmt->fetch(PDO::FETCH_ASSOC);
        $most_popular = $most_popular_result ? $this->getJokerDisplayName($most_popular_result['item_type']) : 'Henüz satış yok';

        // Joker türüne göre detaylı istatistikler
        $stmt = $this->pdo->query("
            SELECT
                item_type,
                COUNT(*) as sales_count,
                SUM(item_price) as total_revenue,
                AVG(item_price) as avg_price,
                COUNT(*) / (DATEDIFF(CURDATE(), MIN(purchase_date)) + 1) as daily_avg
            FROM purchase_logs
            GROUP BY item_type
            ORDER BY sales_count DESC
        ");
        $detailed_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Joker adlarını düzenle
        foreach ($detailed_stats as &$stat) {
            $stat['item_display_name'] = $this->getJokerDisplayName($stat['item_type']);
        }

        // Son satışlar (10 adet)
        $stmt = $this->pdo->query("
            SELECT
                u.username,
                pl.item_type,
                pl.item_price,
                pl.purchase_date
            FROM purchase_logs pl
            JOIN users u ON pl.user_id = u.id
            ORDER BY pl.purchase_date DESC
            LIMIT 10
        ");
        $recent_sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Son satışların joker adlarını düzenle
        foreach ($recent_sales as &$sale) {
            $sale['item_display_name'] = $this->getJokerDisplayName($sale['item_type']);
        }

        return [
            'success' => true,
            'data' => [
                'total_sales' => $total_sales,
                'total_revenue' => $total_revenue,
                'most_popular' => $most_popular,
                'detailed_stats' => $detailed_stats,
                'recent_sales' => $recent_sales,
                'current_prices' => $shopItems['data']
            ]
        ];
    }

    /**
     * Mağaza fiyatlarını günceller
     */
    public function updateShopPrices($data)
    {
        if (($check = $this->checkAdmin()) !== true) return $check;

        $prices = $data['prices'] ?? [];

        if (empty($prices) || !is_array($prices)) {
            return ['success' => false, 'message' => 'Geçersiz fiyat verisi'];
        }

        // Map frontend names to database keys
        $mapping = [
            'fiftyFifty' => 'price_fifty_fifty',
            'extraTime' => 'price_extra_time',
            'pass' => 'price_pass'
        ];

        $updated_count = 0;

        try {
            $this->pdo->beginTransaction();

            foreach ($prices as $type => $price) {
                if (isset($mapping[$type]) && is_numeric($price) && $price > 0 && $price <= 1000) {
                    $db_key = $mapping[$type];
                    $price_value = intval($price);

                    // Update or insert the price in shop_settings table
                    $stmt = $this->pdo->prepare("
                        INSERT INTO shop_settings (setting_key, setting_value, updated_at)
                        VALUES (?, ?, NOW())
                        ON DUPLICATE KEY UPDATE
                        setting_value = VALUES(setting_value),
                        updated_at = NOW()
                    ");

                    $stmt->execute([$db_key, $price_value]);
                    $updated_count++;
                }
            }

            $this->pdo->commit();

            return [
                'success' => true,
                'message' => "{$updated_count} fiyat başarıyla güncellendi",
                'updated_count' => $updated_count
            ];

        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("Shop prices update error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()];
        }
    }

    /**
     * Joker türünü kullanıcı dostu isime çevirir
     */
    private function getJokerDisplayName($item_type)
    {
        $names = [
            'fiftyFifty' => '50/50 Joker',
            'extraTime' => '+15 Saniye',
            'pass' => 'Soruyu Geç'
        ];

        return $names[$item_type] ?? $item_type;
    }

    // --- Category Management ---

    public function getCategories()
    {
        $stmt = $this->pdo->prepare("
            SELECT id, category_key, category_name, icon, color, is_active, created_at,
                   (SELECT COUNT(*) FROM questions WHERE category = categories.category_key) as question_count
            FROM categories
            ORDER BY category_name ASC
        ");
        $stmt->execute();
        return ['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
    }

    public function addCategory($data)
    {
        $category_key = trim($data['category_key'] ?? '');
        $category_name = trim($data['category_name'] ?? '');
        $icon = trim($data['icon'] ?? 'fa-question');
        $color = trim($data['color'] ?? 'gray');

        if (empty($category_key) || empty($category_name)) {
            return ['success' => false, 'message' => 'Kategori key ve ismi zorunludur.'];
        }

        // Key formatını kontrol et (sadece harf, rakam, alt çizgi)
        if (!preg_match('/^[a-z0-9_]+$/', $category_key)) {
            return ['success' => false, 'message' => 'Kategori key sadece küçük harf, rakam ve alt çizgi içerebilir.'];
        }

        try {
            $stmt = $this->pdo->prepare("INSERT INTO categories (category_key, category_name, icon, color) VALUES (?, ?, ?, ?)");
            $stmt->execute([$category_key, $category_name, $icon, $color]);
            return ['success' => true, 'message' => 'Kategori başarıyla eklendi.'];
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) { // Duplicate entry
                return ['success' => false, 'message' => 'Bu kategori key zaten mevcut.'];
            }
            throw $e;
        }
    }

    public function updateCategory($data)
    {
        $id = intval($data['id'] ?? 0);
        $category_name = trim($data['category_name'] ?? '');
        $icon = trim($data['icon'] ?? '');
        $color = trim($data['color'] ?? '');
        $is_active = isset($data['is_active']) ? intval($data['is_active']) : 1;

        if ($id <= 0 || empty($category_name)) {
            return ['success' => false, 'message' => 'Geçersiz kategori ID veya ismi.'];
        }

        // Icon ve color varsa güncelle, yoksa mevcut değerleri koru
        if (!empty($icon) && !empty($color)) {
            $stmt = $this->pdo->prepare("UPDATE categories SET category_name = ?, icon = ?, color = ?, is_active = ? WHERE id = ?");
            $stmt->execute([$category_name, $icon, $color, $is_active, $id]);
        } else {
            $stmt = $this->pdo->prepare("UPDATE categories SET category_name = ?, is_active = ? WHERE id = ?");
            $stmt->execute([$category_name, $is_active, $id]);
        }

        if ($stmt->rowCount() > 0) {
            return ['success' => true, 'message' => 'Kategori başarıyla güncellendi.'];
        } else {
            return ['success' => false, 'message' => 'Kategori bulunamadı veya değişiklik yapılmadı.'];
        }
    }

    public function deleteCategory($data)
    {
        $id = intval($data['id'] ?? 0);

        if ($id <= 0) {
            return ['success' => false, 'message' => 'Geçersiz kategori ID.'];
        }

        // Önce bu kategoride soru var mı kontrol et
        $stmt_check = $this->pdo->prepare("SELECT category_key FROM categories WHERE id = ?");
        $stmt_check->execute([$id]);
        $category = $stmt_check->fetch(PDO::FETCH_ASSOC);

        if (!$category) {
            return ['success' => false, 'message' => 'Kategori bulunamadı.'];
        }

        $stmt_questions = $this->pdo->prepare("SELECT COUNT(*) FROM questions WHERE category = ?");
        $stmt_questions->execute([$category['category_key']]);
        $question_count = $stmt_questions->fetchColumn();

        if ($question_count > 0) {
            return ['success' => false, 'message' => "Bu kategoride $question_count soru var. Önce soruları silin."];
        }

        $stmt = $this->pdo->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$id]);

        return ['success' => true, 'message' => 'Kategori başarıyla silindi.'];
    }

    // === ACHIEVEMENT MANAGEMENT ===

    public function getAchievements()
    {
        if (($check = $this->checkAdmin()) !== true) return $check;

        $stmt = $this->pdo->prepare("
            SELECT
                a.achievement_key,
                a.name,
                a.description,
                a.icon,
                a.color,
                ar.rule_type,
                ar.target_value,
                ar.goal_count,
                ar.tracking_enabled,
                COUNT(ua.id) as earned_count
            FROM achievements a
            LEFT JOIN achievement_rules ar ON a.achievement_key = ar.achievement_key
            LEFT JOIN user_achievements ua ON a.achievement_key = ua.achievement_key
            GROUP BY a.achievement_key
            ORDER BY a.name ASC
        ");
        $stmt->execute();

        return ['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
    }

    public function getAchievementDetails($data)
    {
        if (($check = $this->checkAdmin()) !== true) return $check;

        $achievement_key = $data['achievement_key'] ?? '';
        if (empty($achievement_key)) {
            return ['success' => false, 'message' => 'Başarım anahtarı gerekli.'];
        }

        $stmt = $this->pdo->prepare("
            SELECT
                a.achievement_key,
                a.name,
                a.description,
                a.icon,
                a.color,
                ar.rule_type,
                ar.target_value,
                ar.goal_count,
                ar.tracking_enabled
            FROM achievements a
            LEFT JOIN achievement_rules ar ON a.achievement_key = ar.achievement_key
            WHERE a.achievement_key = ?
        ");
        $stmt->execute([$achievement_key]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            return ['success' => false, 'message' => 'Başarım bulunamadı.'];
        }

        return ['success' => true, 'data' => $result];
    }

    public function createAchievement($data)
    {
        if (($check = $this->checkAdmin()) !== true) return $check;

        $achievement_key = $data['achievement_key'] ?? '';
        $name = $data['name'] ?? '';
        $description = $data['description'] ?? '';
        $icon = $data['icon'] ?? '';
        $color = $data['color'] ?? '';
        $rule_type = $data['rule_type'] ?? '';
        $target_value = $data['target_value'] ?? null;
        $goal_count = intval($data['goal_count'] ?? 0);
        $tracking_enabled = $data['tracking_enabled'] ?? false;

        // Validasyon
        if (empty($achievement_key) || empty($name) || empty($description) || empty($icon) || empty($color) || empty($rule_type) || $goal_count <= 0) {
            return ['success' => false, 'message' => 'Tüm gerekli alanlar doldurulmalıdır.'];
        }

        if (!preg_match('/^[a-z0-9_]+$/', $achievement_key)) {
            return ['success' => false, 'message' => 'Başarım anahtarı sadece küçük harf, sayı ve alt çizgi içerebilir.'];
        }

        try {
            $this->pdo->beginTransaction();

            // 1. Achievement oluştur
            $stmt_achievement = $this->pdo->prepare("
                INSERT INTO achievements (achievement_key, name, description, icon, color)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt_achievement->execute([$achievement_key, $name, $description, $icon, $color]);

            // 2. Achievement rule oluştur
            $stmt_rule = $this->pdo->prepare("
                INSERT INTO achievement_rules (achievement_key, rule_type, target_value, goal_count, tracking_enabled)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt_rule->execute([$achievement_key, $rule_type, $target_value, $goal_count, $tracking_enabled]);

            $this->pdo->commit();
            return ['success' => true, 'message' => 'Başarım başarıyla oluşturuldu.'];

        } catch (PDOException $e) {
            $this->pdo->rollBack();
            if ($e->errorInfo[1] == 1062) { // Duplicate entry
                return ['success' => false, 'message' => 'Bu başarım anahtarı zaten kullanımda.'];
            }
            return ['success' => false, 'message' => 'Başarım oluşturulurken hata oluştu: ' . $e->getMessage()];
        }
    }

    public function updateAchievement($data)
    {
        if (($check = $this->checkAdmin()) !== true) return $check;

        $achievement_key = $data['achievement_key'] ?? '';
        $name = $data['name'] ?? '';
        $description = $data['description'] ?? '';
        $icon = $data['icon'] ?? '';
        $color = $data['color'] ?? '';
        $goal_count = intval($data['goal_count'] ?? 0);
        $tracking_enabled = $data['tracking_enabled'] ?? false;

        if (empty($achievement_key) || empty($name) || empty($description) || empty($icon) || empty($color) || $goal_count <= 0) {
            return ['success' => false, 'message' => 'Tüm gerekli alanlar doldurulmalıdır.'];
        }

        try {
            $this->pdo->beginTransaction();

            // 1. Achievement güncelle
            $stmt_achievement = $this->pdo->prepare("
                UPDATE achievements
                SET name = ?, description = ?, icon = ?, color = ?
                WHERE achievement_key = ?
            ");
            $stmt_achievement->execute([$name, $description, $icon, $color, $achievement_key]);

            // 2. Achievement rule güncelle
            $stmt_rule = $this->pdo->prepare("
                UPDATE achievement_rules
                SET goal_count = ?, tracking_enabled = ?
                WHERE achievement_key = ?
            ");
            $stmt_rule->execute([$goal_count, $tracking_enabled, $achievement_key]);

            $this->pdo->commit();

            if ($stmt_achievement->rowCount() > 0 || $stmt_rule->rowCount() > 0) {
                return ['success' => true, 'message' => 'Başarım başarıyla güncellendi.'];
            } else {
                return ['success' => false, 'message' => 'Başarım bulunamadı veya değişiklik yapılmadı.'];
            }

        } catch (PDOException $e) {
            $this->pdo->rollBack();
            return ['success' => false, 'message' => 'Başarım güncellenirken hata oluştu: ' . $e->getMessage()];
        }
    }

    public function toggleAchievementTracking($data)
    {
        if (($check = $this->checkAdmin()) !== true) return $check;

        $achievement_key = $data['achievement_key'] ?? '';
        $tracking_enabled = $data['tracking_enabled'] ?? false;

        if (empty($achievement_key)) {
            return ['success' => false, 'message' => 'Başarım anahtarı gerekli.'];
        }

        $stmt = $this->pdo->prepare("
            UPDATE achievement_rules
            SET tracking_enabled = ?
            WHERE achievement_key = ?
        ");
        $stmt->execute([$tracking_enabled, $achievement_key]);

        if ($stmt->rowCount() > 0) {
            return ['success' => true, 'message' => 'Tracking durumu başarıyla değiştirildi.'];
        } else {
            return ['success' => false, 'message' => 'Başarım bulunamadı.'];
        }
    }

    public function deleteAchievement($data)
    {
        if (($check = $this->checkAdmin()) !== true) return $check;

        $achievement_key = $data['achievement_key'] ?? '';
        if (empty($achievement_key)) {
            return ['success' => false, 'message' => 'Başarım anahtarı gerekli.'];
        }

        try {
            $this->pdo->beginTransaction();

            // 1. Kullanıcı başarımlarını sil
            $stmt_user_achievements = $this->pdo->prepare("DELETE FROM user_achievements WHERE achievement_key = ?");
            $stmt_user_achievements->execute([$achievement_key]);

            // 2. Achievement rule'ı sil
            $stmt_rule = $this->pdo->prepare("DELETE FROM achievement_rules WHERE achievement_key = ?");
            $stmt_rule->execute([$achievement_key]);

            // 3. Achievement'ı sil
            $stmt_achievement = $this->pdo->prepare("DELETE FROM achievements WHERE achievement_key = ?");
            $stmt_achievement->execute([$achievement_key]);

            $this->pdo->commit();

            if ($stmt_achievement->rowCount() > 0) {
                return ['success' => true, 'message' => 'Başarım ve tüm ilgili veriler başarıyla silindi.'];
            } else {
                return ['success' => false, 'message' => 'Başarım bulunamadı.'];
            }

        } catch (PDOException $e) {
            $this->pdo->rollBack();
            return ['success' => false, 'message' => 'Başarım silinirken hata oluştu: ' . $e->getMessage()];
        }
    }

    public function getAchievementStats()
    {
        if (($check = $this->checkAdmin()) !== true) return $check;

        // 1. Toplam başarım sayısı
        $stmt_total = $this->pdo->query("SELECT COUNT(*) FROM achievements");
        $total_achievements = $stmt_total->fetchColumn();

        // 2. Tracking aktif olan başarım sayısı
        $stmt_tracking = $this->pdo->query("SELECT COUNT(*) FROM achievement_rules WHERE tracking_enabled = 1");
        $tracking_enabled = $stmt_tracking->fetchColumn();

        // 3. Toplam kazanılan başarım sayısı
        $stmt_earned = $this->pdo->query("SELECT COUNT(*) FROM user_achievements");
        $total_earned = $stmt_earned->fetchColumn();

        // 4. Ortalama tamamlanma yüzdesi
        $average_completion = 0;
        if ($total_achievements > 0) {
            $stmt_users = $this->pdo->query("SELECT COUNT(*) FROM users");
            $total_users = $stmt_users->fetchColumn();

            if ($total_users > 0) {
                $average_completion = round(($total_earned / ($total_achievements * $total_users)) * 100, 1);
            }
        }

        return [
            'success' => true,
            'data' => [
                'total_achievements' => $total_achievements,
                'tracking_enabled' => $tracking_enabled,
                'total_earned' => $total_earned,
                'average_completion' => $average_completion
            ]
        ];
    }

    // =============== QUEST MANAGEMENT METHODS ===============

    public function getAllQuests()
    {
        if (($check = $this->checkAdmin()) !== true) return $check;

        try {
            $stmt = $this->pdo->query("
                SELECT
                    quest_key,
                    name,
                    description_template,
                    type,
                    target,
                    default_goal,
                    reward_points,
                    reward_coins,
                    is_active,
                    created_at
                FROM quests
                ORDER BY created_at DESC
            ");

            return ['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
        } catch (Exception $e) {
            error_log("getAllQuests error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Quest listesi alınamadı: ' . $e->getMessage()];
        }
    }

    public function createQuest($data)
    {
        if (($check = $this->checkAdmin()) !== true) return $check;

        $quest_key = $data['quest_key'] ?? '';
        $name = $data['name'] ?? '';
        $description_template = $data['description_template'] ?? '';
        $type = $data['type'] ?? '';
        $target = $data['target'] ?? null;
        $default_goal = intval($data['default_goal'] ?? 0);
        $reward_points = intval($data['reward_points'] ?? 0);
        $reward_coins = intval($data['reward_coins'] ?? 0);
        $is_active = $data['is_active'] ?? true;

        // Validasyon
        if (empty($quest_key) || empty($name) || empty($description_template) || empty($type) || $default_goal <= 0) {
            return ['success' => false, 'message' => 'Tüm gerekli alanlar doldurulmalıdır.'];
        }

        if (!preg_match('/^[a-z0-9_]+$/', $quest_key)) {
            return ['success' => false, 'message' => 'Quest anahtarı sadece küçük harf, sayı ve alt çizgi içerebilir.'];
        }

        // Quest type kontrolü
        $valid_types = ['solve_category', 'solve_difficulty', 'consecutive_days', 'win_duels'];
        if (!in_array($type, $valid_types)) {
            return ['success' => false, 'message' => 'Geçersiz quest tipi. Geçerli tipler: ' . implode(', ', $valid_types)];
        }

        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO quests (quest_key, name, description_template, type, target, default_goal, reward_points, reward_coins, is_active)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$quest_key, $name, $description_template, $type, $target, $default_goal, $reward_points, $reward_coins, $is_active]);

            return ['success' => true, 'message' => 'Quest başarıyla oluşturuldu.'];

        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) { // Duplicate entry
                return ['success' => false, 'message' => 'Bu quest anahtarı zaten kullanımda.'];
            }
            return ['success' => false, 'message' => 'Quest oluşturulurken hata oluştu: ' . $e->getMessage()];
        }
    }

    public function updateQuest($data)
    {
        if (($check = $this->checkAdmin()) !== true) return $check;

        $quest_key = $data['quest_key'] ?? '';
        $name = $data['name'] ?? '';
        $description_template = $data['description_template'] ?? '';
        $target = $data['target'] ?? null;
        $default_goal = intval($data['default_goal'] ?? 0);
        $reward_points = intval($data['reward_points'] ?? 0);
        $reward_coins = intval($data['reward_coins'] ?? 0);
        $is_active = $data['is_active'] ?? true;

        if (empty($quest_key) || empty($name) || empty($description_template) || $default_goal <= 0) {
            return ['success' => false, 'message' => 'Tüm gerekli alanlar doldurulmalıdır.'];
        }

        try {
            $stmt = $this->pdo->prepare("
                UPDATE quests
                SET name = ?, description_template = ?, target = ?, default_goal = ?,
                    reward_points = ?, reward_coins = ?, is_active = ?
                WHERE quest_key = ?
            ");
            $stmt->execute([$name, $description_template, $target, $default_goal, $reward_points, $reward_coins, $is_active, $quest_key]);

            if ($stmt->rowCount() > 0) {
                return ['success' => true, 'message' => 'Quest başarıyla güncellendi.'];
            } else {
                return ['success' => false, 'message' => 'Quest bulunamadı veya değişiklik yapılmadı.'];
            }

        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Quest güncellenirken hata oluştu: ' . $e->getMessage()];
        }
    }

    public function deleteQuest($data)
    {
        if (($check = $this->checkAdmin()) !== true) return $check;

        $quest_key = $data['quest_key'] ?? '';

        if (empty($quest_key)) {
            return ['success' => false, 'message' => 'Quest anahtarı gereklidir.'];
        }

        try {
            $this->pdo->beginTransaction();

            // 1. Önce user_quests tablosundan bu quest'e ait kayıtları sil
            $stmt_user_quests = $this->pdo->prepare("DELETE FROM user_quests WHERE quest_key = ?");
            $stmt_user_quests->execute([$quest_key]);

            // 2. Quest'i sil
            $stmt_quest = $this->pdo->prepare("DELETE FROM quests WHERE quest_key = ?");
            $stmt_quest->execute([$quest_key]);

            $this->pdo->commit();

            if ($stmt_quest->rowCount() > 0) {
                return ['success' => true, 'message' => 'Quest ve tüm ilgili veriler başarıyla silindi.'];
            } else {
                return ['success' => false, 'message' => 'Quest bulunamadı.'];
            }

        } catch (PDOException $e) {
            $this->pdo->rollBack();
            return ['success' => false, 'message' => 'Quest silinirken hata oluştu: ' . $e->getMessage()];
        }
    }

    public function getQuestStats()
    {
        if (($check = $this->checkAdmin()) !== true) return $check;

        try {
            // 1. Toplam quest sayısı
            $stmt_total = $this->pdo->query("SELECT COUNT(*) FROM quests");
            $total_quests = $stmt_total->fetchColumn();

            // 2. Aktif quest sayısı
            $stmt_active = $this->pdo->query("SELECT COUNT(*) FROM quests WHERE is_active = 1");
            $active_quests = $stmt_active->fetchColumn();

            // 3. Bugün atanmış quest sayısı
            $today = date('Y-m-d');
            $stmt_assigned_today = $this->pdo->prepare("SELECT COUNT(*) FROM user_quests WHERE assigned_date = ?");
            $stmt_assigned_today->execute([$today]);
            $assigned_today = $stmt_assigned_today->fetchColumn();

            // 4. Bugün tamamlanan quest sayısı
            $stmt_completed_today = $this->pdo->prepare("SELECT COUNT(*) FROM user_quests WHERE assigned_date = ? AND is_completed = 1");
            $stmt_completed_today->execute([$today]);
            $completed_today = $stmt_completed_today->fetchColumn();

            // 5. Toplam tamamlanan quest sayısı
            $stmt_total_completed = $this->pdo->query("SELECT COUNT(*) FROM user_quests WHERE is_completed = 1");
            $total_completed = $stmt_total_completed->fetchColumn();

            // 6. Quest tiplerine göre dağılım
            $stmt_types = $this->pdo->query("
                SELECT type, COUNT(*) as count
                FROM quests
                WHERE is_active = 1
                GROUP BY type
            ");
            $quest_types = $stmt_types->fetchAll(PDO::FETCH_ASSOC);

            return [
                'success' => true,
                'data' => [
                    'total_quests' => $total_quests,
                    'active_quests' => $active_quests,
                    'assigned_today' => $assigned_today,
                    'completed_today' => $completed_today,
                    'total_completed' => $total_completed,
                    'quest_types' => $quest_types
                ]
            ];
        } catch (Exception $e) {
            error_log("getQuestStats error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Quest istatistikleri alınamadı: ' . $e->getMessage()];
        }
    }

    // =============== QUESTION MANAGEMENT METHODS ===============

    /**
     * Get reported questions for admin review
     */
    public function getReportedQuestions($data = [])
    {
        if (($check = $this->checkAdmin()) !== true) return $check;

        $limit = min((int)($data['limit'] ?? 20), 100);
        $offset = max((int)($data['offset'] ?? 0), 0);
        $status = $data['status'] ?? 'all'; // all, pending, reviewed

        try {
            $where_clause = "WHERE qr.report_reason IS NOT NULL";
            $params = [];

            if ($status === 'pending') {
                $where_clause .= " AND q.id NOT IN (SELECT question_id FROM question_reviews WHERE status = 'reviewed')";
            } elseif ($status === 'reviewed') {
                $where_clause .= " AND q.id IN (SELECT question_id FROM question_reviews WHERE status = 'reviewed')";
            }

            $stmt = $this->pdo->prepare("
                SELECT
                    q.id,
                    q.question_text,
                    q.category,
                    q.difficulty,
                    q.usage_count,
                    q.created_at as question_created_at,
                    COUNT(qr.id) as report_count,
                    AVG(qr.rating) as average_rating,
                    GROUP_CONCAT(DISTINCT qr.report_reason) as report_reasons,
                    GROUP_CONCAT(DISTINCT CONCAT(u.username, ': ', qr.feedback) SEPARATOR ' | ') as feedback_summary,
                    MAX(qr.created_at) as last_report_date
                FROM questions q
                JOIN question_ratings qr ON q.id = qr.question_id
                LEFT JOIN users u ON qr.user_id = u.id
                $where_clause
                GROUP BY q.id
                HAVING report_count > 0
                ORDER BY last_report_date DESC, report_count DESC
                LIMIT $limit OFFSET $offset
            ");
            $stmt->execute($params);
            $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get total count
            $stmt = $this->pdo->prepare("
                SELECT COUNT(DISTINCT q.id)
                FROM questions q
                JOIN question_ratings qr ON q.id = qr.question_id
                $where_clause
            ");
            $stmt->execute($params);
            $total_count = $stmt->fetchColumn();

            return [
                'success' => true,
                'data' => [
                    'questions' => $questions,
                    'total_count' => (int)$total_count,
                    'limit' => $limit,
                    'offset' => $offset,
                    'has_more' => ($offset + $limit) < $total_count
                ]
            ];

        } catch (Exception $e) {
            error_log("getReportedQuestions error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Raporlanan sorular alınırken hata oluştu.'];
        }
    }

    /**
     * Get detailed information about a specific question including all ratings
     */
    public function getQuestionDetails($data)
    {
        if (($check = $this->checkAdmin()) !== true) return $check;

        $question_id = (int)($data['question_id'] ?? 0);

        if ($question_id <= 0) {
            return ['success' => false, 'message' => 'Geçersiz soru ID.'];
        }

        try {
            // Get question details
            $stmt = $this->pdo->prepare("
                SELECT
                    id,
                    question_text,
                    question_type,
                    options,
                    correct_answer,
                    explanation,
                    category,
                    difficulty,
                    usage_count,
                    created_at,
                    updated_at
                FROM questions
                WHERE id = ?
            ");
            $stmt->execute([$question_id]);
            $question = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$question) {
                return ['success' => false, 'message' => 'Soru bulunamadı.'];
            }

            // Get all ratings and feedback
            $stmt = $this->pdo->prepare("
                SELECT
                    qr.id,
                    qr.rating,
                    qr.feedback,
                    qr.report_reason,
                    qr.created_at,
                    u.username
                FROM question_ratings qr
                JOIN users u ON qr.user_id = u.id
                WHERE qr.question_id = ?
                ORDER BY qr.created_at DESC
            ");
            $stmt->execute([$question_id]);
            $ratings = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get rating statistics
            $stmt = $this->pdo->prepare("
                SELECT
                    COUNT(*) as total_ratings,
                    AVG(rating) as average_rating,
                    COUNT(CASE WHEN report_reason IS NOT NULL THEN 1 END) as report_count,
                    GROUP_CONCAT(DISTINCT report_reason) as unique_report_reasons
                FROM question_ratings
                WHERE question_id = ?
            ");
            $stmt->execute([$question_id]);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);

            // Get usage analytics
            $stmt = $this->pdo->prepare("
                SELECT
                    COUNT(*) as total_answers,
                    SUM(CASE WHEN is_correct = 1 THEN 1 ELSE 0 END) as correct_answers,
                    AVG(answer_time_seconds) as average_time
                FROM question_analytics
                WHERE question_id = ?
            ");
            $stmt->execute([$question_id]);
            $analytics = $stmt->fetch(PDO::FETCH_ASSOC);

            return [
                'success' => true,
                'data' => [
                    'question' => $question,
                    'ratings' => $ratings,
                    'statistics' => [
                        'total_ratings' => (int)$stats['total_ratings'],
                        'average_rating' => $stats['total_ratings'] > 0 ? round((float)$stats['average_rating'], 2) : 0,
                        'report_count' => (int)$stats['report_count'],
                        'unique_report_reasons' => $stats['unique_report_reasons'] ? explode(',', $stats['unique_report_reasons']) : [],
                        'total_answers' => (int)$analytics['total_answers'],
                        'correct_answers' => (int)$analytics['correct_answers'],
                        'success_rate' => $analytics['total_answers'] > 0 ? round(($analytics['correct_answers'] / $analytics['total_answers']) * 100, 2) : 0,
                        'average_time' => $analytics['average_time'] ? round((float)$analytics['average_time'], 2) : 0
                    ]
                ]
            ];

        } catch (Exception $e) {
            error_log("getQuestionDetails error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Soru detayları alınırken hata oluştu.'];
        }
    }

    /**
     * Review a question (mark as reviewed, hide, or delete)
     */
    public function reviewQuestion($data)
    {
        if (($check = $this->checkAdmin()) !== true) return $check;

        $question_id = (int)($data['question_id'] ?? 0);
        $action = $data['action'] ?? ''; // reviewed, hidden, deleted
        $admin_notes = trim($data['admin_notes'] ?? '');

        if ($question_id <= 0) {
            return ['success' => false, 'message' => 'Geçersiz soru ID.'];
        }

        $valid_actions = ['reviewed', 'hidden', 'deleted'];
        if (!in_array($action, $valid_actions)) {
            return ['success' => false, 'message' => 'Geçersiz işlem.'];
        }

        try {
            $this->pdo->beginTransaction();

            // Check if question exists
            $stmt = $this->pdo->prepare("SELECT id FROM questions WHERE id = ?");
            $stmt->execute([$question_id]);
            if (!$stmt->fetch()) {
                $this->pdo->rollBack();
                return ['success' => false, 'message' => 'Soru bulunamadı.'];
            }

            if ($action === 'deleted') {
                // Delete the question and all related data
                $stmt = $this->pdo->prepare("DELETE FROM question_ratings WHERE question_id = ?");
                $stmt->execute([$question_id]);

                $stmt = $this->pdo->prepare("DELETE FROM question_analytics WHERE question_id = ?");
                $stmt->execute([$question_id]);

                $stmt = $this->pdo->prepare("DELETE FROM questions WHERE id = ?");
                $stmt->execute([$question_id]);

                $message = 'Soru başarıyla silindi.';
            } else {
                // Add or update question review record
                $stmt = $this->pdo->prepare("
                    INSERT INTO question_reviews (question_id, admin_id, status, admin_notes, reviewed_at)
                    VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)
                    ON DUPLICATE KEY UPDATE
                        admin_id = VALUES(admin_id),
                        status = VALUES(status),
                        admin_notes = VALUES(admin_notes),
                        reviewed_at = CURRENT_TIMESTAMP
                ");
                $stmt->execute([$question_id, $_SESSION['user_id'], $action, $admin_notes]);

                $message = $action === 'reviewed' ? 'Soru incelendi olarak işaretlendi.' : 'Soru gizlendi.';
            }

            $this->pdo->commit();

            return [
                'success' => true,
                'message' => $message,
                'data' => [
                    'question_id' => $question_id,
                    'action' => $action
                ]
            ];

        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("reviewQuestion error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Soru işlenirken hata oluştu.'];
        }
    }

    /**
     * Get question management statistics
     */
    public function getQuestionStats()
    {
        if (($check = $this->checkAdmin()) !== true) return $check;

        try {
            // Total questions
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM questions");
            $total_questions = $stmt->fetchColumn();

            // Questions with reports
            $stmt = $this->pdo->query("
                SELECT COUNT(DISTINCT question_id)
                FROM question_ratings
                WHERE report_reason IS NOT NULL
            ");
            $reported_questions = $stmt->fetchColumn();

            // Questions by category
            $stmt = $this->pdo->query("
                SELECT category, COUNT(*) as count
                FROM questions
                GROUP BY category
                ORDER BY count DESC
            ");
            $by_category = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Questions by difficulty
            $stmt = $this->pdo->query("
                SELECT difficulty, COUNT(*) as count
                FROM questions
                GROUP BY difficulty
                ORDER BY FIELD(difficulty, 'kolay', 'orta', 'zor')
            ");
            $by_difficulty = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Rating statistics
            $stmt = $this->pdo->query("
                SELECT
                    COUNT(*) as total_ratings,
                    AVG(rating) as average_rating,
                    COUNT(CASE WHEN rating <= 2 THEN 1 END) as low_ratings
                FROM question_ratings
            ");
            $rating_stats = $stmt->fetch(PDO::FETCH_ASSOC);

            // Most reported questions
            $stmt = $this->pdo->query("
                SELECT
                    q.id,
                    q.question_text,
                    q.category,
                    COUNT(qr.id) as report_count
                FROM questions q
                JOIN question_ratings qr ON q.id = qr.question_id
                WHERE qr.report_reason IS NOT NULL
                GROUP BY q.id
                ORDER BY report_count DESC
                LIMIT 5
            ");
            $most_reported = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'success' => true,
                'data' => [
                    'totals' => [
                        'total_questions' => (int)$total_questions,
                        'reported_questions' => (int)$reported_questions,
                        'total_ratings' => (int)$rating_stats['total_ratings'],
                        'low_ratings' => (int)$rating_stats['low_ratings']
                    ],
                    'average_rating' => $rating_stats['total_ratings'] > 0 ? round((float)$rating_stats['average_rating'], 2) : 0,
                    'by_category' => $by_category,
                    'by_difficulty' => $by_difficulty,
                    'most_reported' => $most_reported
                ]
            ];

        } catch (Exception $e) {
            error_log("getQuestionStats error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Soru istatistikleri alınırken hata oluştu.'];
        }
    }
}
