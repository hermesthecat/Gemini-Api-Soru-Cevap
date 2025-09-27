<?php

class ShopController
{
    private $pdo;
    private $item_prices = null; // Lazy load from database

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    private function getItemPrices()
    {
        if ($this->item_prices === null) {
            // Load prices from database
            $stmt = $this->pdo->query("SELECT setting_key, setting_value FROM shop_settings WHERE setting_key LIKE 'price_%'");
            $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

            $this->item_prices = [
                'fiftyFifty' => isset($settings['price_fifty_fifty']) ? (int)$settings['price_fifty_fifty'] : 75,
                'extraTime' => isset($settings['price_extra_time']) ? (int)$settings['price_extra_time'] : 50,
                'pass' => isset($settings['price_pass']) ? (int)$settings['price_pass'] : 50
            ];
        }

        return $this->item_prices;
    }

    public function getShopItems()
    {
        $user_id = $_SESSION['user_id'];

        // Kullanıcının mevcut jokerlerini al
        $stmt = $this->pdo->prepare("SELECT lifeline_fifty_fifty, lifeline_extra_time, lifeline_pass FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $current_lifelines = $stmt->fetch(PDO::FETCH_ASSOC);

        $prices = $this->getItemPrices();

        $items = [
            [
                'key' => 'fiftyFifty',
                'name' => '50/50 Jokeri',
                'description' => 'İki yanlış şıkkı eler, doğruyu bulma şansını artırır.',
                'price' => $prices['fiftyFifty'],
                'icon' => 'fas fa-star-half-alt',
                'current_stock' => $current_lifelines['lifeline_fifty_fifty'] ?? 0
            ],
            [
                'key' => 'extraTime',
                'name' => '+15 Saniye Jokeri',
                'description' => 'Soruya cevap vermek için 15 saniye daha kazandırır.',
                'price' => $prices['extraTime'],
                'icon' => 'fas fa-stopwatch',
                'current_stock' => $current_lifelines['lifeline_extra_time'] ?? 0
            ],
            [
                'key' => 'pass',
                'name' => 'Soruyu Geç Jokeri',
                'description' => 'Mevcut soruyu pas geçerek yerine yeni bir soru almanızı sağlar.',
                'price' => $prices['pass'],
                'icon' => 'fas fa-arrow-right',
                'current_stock' => $current_lifelines['lifeline_pass'] ?? 0
            ]
        ];

        return ['success' => true, 'data' => $items];
    }

    public function purchaseLifeline($data)
    {
        $user_id = $_SESSION['user_id'];
        $item_key = $data['item_key'] ?? '';

        $prices = $this->getItemPrices();

        if (!array_key_exists($item_key, $prices)) {
            return ['success' => false, 'message' => 'Geçersiz ürün.'];
        }

        $price = $prices[$item_key];

        // Map item keys to database column names
        $lifeline_columns = [
            'fiftyFifty' => 'lifeline_fifty_fifty',
            'extraTime' => 'lifeline_extra_time',
            'pass' => 'lifeline_pass'
        ];

        if (!isset($lifeline_columns[$item_key])) {
            return ['success' => false, 'message' => 'Geçersiz ürün türü.'];
        }

        $lifeline_column = $lifeline_columns[$item_key];


        $this->pdo->beginTransaction();
        try {
            // Jetonu kontrol et ve düş
            $stmt_coins = $this->pdo->prepare("UPDATE users SET coins = coins - ? WHERE id = ? AND coins >= ?");
            $stmt_coins->execute([$price, $user_id, $price]);

            if ($stmt_coins->rowCount() === 0) {
                $this->pdo->rollBack();
                return ['success' => false, 'message' => 'Yetersiz jeton!'];
            }

            // Jokeri ekle
            $sql = "UPDATE users SET {$lifeline_column} = {$lifeline_column} + 1 WHERE id = ?";
            $stmt_lifeline = $this->pdo->prepare($sql);
            $stmt_lifeline->execute([$user_id]);

            // Purchase log kaydı ekle
            $stmt_log = $this->pdo->prepare("
                INSERT INTO purchase_logs (user_id, item_type, item_price)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE user_id = user_id
            ");
            $stmt_log->execute([$user_id, $item_key, $price]);

            // Güncel coin balance'ı al
            $stmt_balance = $this->pdo->prepare("SELECT coins FROM users WHERE id = ?");
            $stmt_balance->execute([$user_id]);
            $new_balance = $stmt_balance->fetchColumn();

            // Session'ı güncelle
            if (!isset($_SESSION['lifelines'])) {
                $_SESSION['lifelines'] = [];
            }
            $_SESSION['lifelines'][$item_key] = ($_SESSION['lifelines'][$item_key] ?? 0) + 1;

            $this->pdo->commit();

            return ['success' => true, 'message' => 'Satın alma başarılı!', 'data' => ['new_coin_balance' => $new_balance]];
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("Purchase error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Satın alma sırasında bir hata oluştu.'];
        }
    }
}
