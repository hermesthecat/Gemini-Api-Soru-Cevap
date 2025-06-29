<?php

class ShopController
{
    private $pdo;
    private $item_prices = [
        'fiftyFifty' => 75,
        'extraTime' => 50,
        'pass' => 100
    ];

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function getShopItems()
    {
        $user_id = $_SESSION['user_id'];

        // Kullanıcının mevcut jokerlerini al
        $stmt = $this->pdo->prepare("SELECT lifeline_fifty_fifty, lifeline_extra_time, lifeline_pass FROM leaderboard WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $current_lifelines = $stmt->fetch(PDO::FETCH_ASSOC);

        $items = [
            [
                'key' => 'fiftyFifty',
                'name' => '50/50 Jokeri',
                'description' => 'İki yanlış şıkkı eler, doğruyu bulma şansını artırır.',
                'price' => $this->item_prices['fiftyFifty'],
                'icon' => 'fas fa-star-half-alt',
                'current_stock' => $current_lifelines['lifeline_fifty_fifty'] ?? 0
            ],
            [
                'key' => 'extraTime',
                'name' => '+15 Saniye Jokeri',
                'description' => 'Soruya cevap vermek için 15 saniye daha kazandırır.',
                'price' => $this->item_prices['extraTime'],
                'icon' => 'fas fa-stopwatch',
                'current_stock' => $current_lifelines['lifeline_extra_time'] ?? 0
            ],
            [
                'key' => 'pass',
                'name' => 'Soruyu Geç Jokeri',
                'description' => 'Mevcut soruyu pas geçerek yerine yeni bir soru almanızı sağlar.',
                'price' => $this->item_prices['pass'],
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

        if (!array_key_exists($item_key, $this->item_prices)) {
            return ['success' => false, 'message' => 'Geçersiz ürün.'];
        }

        $price = $this->item_prices[$item_key];
        $lifeline_column = "lifeline_" . str_replace(['Fifty'], ['_fifty'], $item_key);


        $this->pdo->beginTransaction();
        try {
            // Jetonu kontrol et ve düş
            $stmt_coins = $this->pdo->prepare("UPDATE leaderboard SET coins = coins - ? WHERE user_id = ? AND coins >= ?");
            $stmt_coins->execute([$price, $user_id, $price]);

            if ($stmt_coins->rowCount() === 0) {
                $this->pdo->rollBack();
                return ['success' => false, 'message' => 'Yetersiz jeton!'];
            }

            // Jokeri ekle
            $stmt_lifeline = $this->pdo->prepare("UPDATE leaderboard SET $lifeline_column = $lifeline_column + 1 WHERE user_id = ?");
            $stmt_lifeline->execute([$user_id]);
            
            // Session'ı güncelle
            $_SESSION['user_coins'] -= $price;
            if(!isset($_SESSION['lifelines'])) {
                $_SESSION['lifelines'] = [];
            }
            $_SESSION['lifelines'][$item_key] = ($_SESSION['lifelines'][$item_key] ?? 0) + 1;

            $this->pdo->commit();

            return ['success' => true, 'message' => 'Satın alma başarılı!', 'data' => ['new_coin_balance' => $_SESSION['user_coins']]];
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("Purchase error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Satın alma sırasında bir hata oluştu.'];
        }
    }
} 