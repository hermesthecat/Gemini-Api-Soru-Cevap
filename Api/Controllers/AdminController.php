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
            SELECT u.id, u.username, u.role, u.created_at, l.score
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

        // Geçerli joker türleri
        $valid_types = ['fiftyFifty', 'extraTime', 'pass'];

        // ShopController dosyasını oku ve fiyatları güncelle
        $shop_file = __DIR__ . '/ShopController.php';
        $content = file_get_contents($shop_file);

        if (!$content) {
            return ['success' => false, 'message' => 'ShopController dosyası okunamadı'];
        }

        $updated_count = 0;
        foreach ($prices as $type => $price) {
            if (in_array($type, $valid_types) && is_numeric($price) && $price > 0 && $price <= 1000) {
                // Regex ile fiyatı güncelle
                $pattern = "/'{$type}'\s*=>\s*\d+/";
                $replacement = "'{$type}' => " . intval($price);
                $content = preg_replace($pattern, $replacement, $content);
                $updated_count++;
            }
        }

        if ($updated_count > 0) {
            // Dosyayı yaz
            if (file_put_contents($shop_file, $content)) {
                return [
                    'success' => true,
                    'message' => "{$updated_count} fiyat başarıyla güncellendi",
                    'updated_count' => $updated_count
                ];
            } else {
                return ['success' => false, 'message' => 'Dosya yazma hatası'];
            }
        } else {
            return ['success' => false, 'message' => 'Güncellenecek geçerli fiyat bulunamadı'];
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
}
