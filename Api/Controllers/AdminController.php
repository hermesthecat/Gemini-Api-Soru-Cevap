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
}
