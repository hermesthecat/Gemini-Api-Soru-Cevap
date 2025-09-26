<?php

class SettingsController
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Get all system settings
     */
    public function getSettings()
    {
        // Admin kontrolü
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            return ['success' => false, 'message' => 'Bu işlem için admin yetkisi gerekiyor.'];
        }

        $stmt = $this->pdo->prepare("
            SELECT setting_key, setting_value, description
            FROM settings
            ORDER BY setting_key
        ");
        $stmt->execute();
        $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Convert to associative array for easier frontend handling
        $settings_array = [];
        foreach ($settings as $setting) {
            $settings_array[$setting['setting_key']] = [
                'value' => $setting['setting_value'],
                'description' => $setting['description']
            ];
        }

        return ['success' => true, 'data' => $settings_array];
    }

    /**
     * Update system settings
     */
    public function updateSettings($data)
    {
        // Admin kontrolü
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            return ['success' => false, 'message' => 'Bu işlem için admin yetkisi gerekiyor.'];
        }

        $settings = $data['settings'] ?? [];

        if (empty($settings) || !is_array($settings)) {
            return ['success' => false, 'message' => 'Geçersiz ayarlar verisi.'];
        }

        // Valid setting keys
        $valid_keys = ['gemini_api_key', 'gemini_model', 'site_name', 'registration_enabled'];

        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare("
                UPDATE settings
                SET setting_value = ?
                WHERE setting_key = ?
            ");

            $updated_count = 0;
            foreach ($settings as $key => $value) {
                if (in_array($key, $valid_keys)) {
                    // Validate specific settings
                    if ($key === 'registration_enabled') {
                        $value = in_array($value, ['0', '1', 0, 1]) ? (string)$value : '1';
                    } elseif ($key === 'site_name') {
                        $value = trim($value);
                        if (empty($value)) {
                            $value = 'AI Soru Cevap Yarışması';
                        }
                    } elseif ($key === 'gemini_model') {
                        $value = trim($value);
                        if (empty($value)) {
                            $value = 'gemini-1.5-flash';
                        }
                    }

                    $stmt->execute([$value, $key]);
                    if ($stmt->rowCount() > 0) {
                        $updated_count++;
                    }
                }
            }

            $this->pdo->commit();
            return [
                'success' => true,
                'message' => "$updated_count ayar başarıyla güncellendi.",
                'updated_count' => $updated_count
            ];

        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("Settings update error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Ayarlar güncellenirken bir hata oluştu.'];
        }
    }

    /**
     * Get a specific setting value
     */
    public function getSetting($setting_key)
    {
        $stmt = $this->pdo->prepare("
            SELECT setting_value
            FROM settings
            WHERE setting_key = ?
        ");
        $stmt->execute([$setting_key]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ? $result['setting_value'] : null;
    }

    /**
     * Update a specific setting
     */
    public function setSetting($setting_key, $setting_value)
    {
        $stmt = $this->pdo->prepare("
            UPDATE settings
            SET setting_value = ?
            WHERE setting_key = ?
        ");
        $stmt->execute([$setting_value, $setting_key]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Get all API keys
     */
    public function getApiKeys()
    {
        // Admin kontrolü
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            return ['success' => false, 'message' => 'Bu işlem için admin yetkisi gerekiyor.'];
        }

        $stmt = $this->pdo->prepare("
            SELECT id, name,
                   CONCAT(LEFT(api_key, 8), '...', RIGHT(api_key, 4)) as masked_key,
                   is_active, usage_count, last_used_at, created_at
            FROM api_keys
            ORDER BY created_at DESC
        ");
        $stmt->execute();

        return ['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
    }

    /**
     * Add new API key
     */
    public function addApiKey($data)
    {
        // Admin kontrolü
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            return ['success' => false, 'message' => 'Bu işlem için admin yetkisi gerekiyor.'];
        }

        $name = trim($data['name'] ?? '');
        $api_key = trim($data['api_key'] ?? '');

        if (empty($name) || empty($api_key)) {
            return ['success' => false, 'message' => 'Anahtar adı ve API anahtarı gereklidir.'];
        }

        // Check if name already exists
        $stmt = $this->pdo->prepare("SELECT id FROM api_keys WHERE name = ?");
        $stmt->execute([$name]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Bu isimde bir anahtar zaten var.'];
        }

        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO api_keys (name, api_key, is_active)
                VALUES (?, ?, TRUE)
            ");
            $stmt->execute([$name, $api_key]);

            return ['success' => true, 'message' => 'API anahtarı başarıyla eklendi.'];
        } catch (PDOException $e) {
            error_log("API key add error: " . $e->getMessage());
            return ['success' => false, 'message' => 'API anahtarı eklenirken hata oluştu.'];
        }
    }

    /**
     * Update API key status
     */
    public function updateApiKeyStatus($data)
    {
        // Admin kontrolü
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            return ['success' => false, 'message' => 'Bu işlem için admin yetkisi gerekiyor.'];
        }

        $id = $data['id'] ?? 0;
        $is_active = $data['is_active'] ?? false;

        if (!$id) {
            return ['success' => false, 'message' => 'Geçersiz anahtar ID.'];
        }

        try {
            $stmt = $this->pdo->prepare("
                UPDATE api_keys SET is_active = ? WHERE id = ?
            ");
            $stmt->execute([$is_active ? 1 : 0, $id]);

            if ($stmt->rowCount() > 0) {
                $status = $is_active ? 'aktif' : 'pasif';
                return ['success' => true, 'message' => "API anahtarı $status olarak işaretlendi."];
            } else {
                return ['success' => false, 'message' => 'API anahtarı bulunamadı.'];
            }
        } catch (PDOException $e) {
            error_log("API key status update error: " . $e->getMessage());
            return ['success' => false, 'message' => 'API anahtarı durumu güncellenirken hata oluştu.'];
        }
    }

    /**
     * Delete API key
     */
    public function deleteApiKey($data)
    {
        // Admin kontrolü
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            return ['success' => false, 'message' => 'Bu işlem için admin yetkisi gerekiyor.'];
        }

        $id = $data['id'] ?? 0;

        if (!$id) {
            return ['success' => false, 'message' => 'Geçersiz anahtar ID.'];
        }

        // Check if this is the last active key
        $stmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM api_keys WHERE is_active = TRUE");
        $stmt->execute();
        $activeCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        $stmt = $this->pdo->prepare("SELECT is_active FROM api_keys WHERE id = ?");
        $stmt->execute([$id]);
        $keyData = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($keyData && $keyData['is_active'] && $activeCount <= 1) {
            return ['success' => false, 'message' => 'En az bir aktif API anahtarı kalmalıdır.'];
        }

        try {
            $stmt = $this->pdo->prepare("DELETE FROM api_keys WHERE id = ?");
            $stmt->execute([$id]);

            if ($stmt->rowCount() > 0) {
                return ['success' => true, 'message' => 'API anahtarı başarıyla silindi.'];
            } else {
                return ['success' => false, 'message' => 'API anahtarı bulunamadı.'];
            }
        } catch (PDOException $e) {
            error_log("API key delete error: " . $e->getMessage());
            return ['success' => false, 'message' => 'API anahtarı silinirken hata oluştu.'];
        }
    }

    /**
     * Get a random active API key for use
     */
    public function getActiveApiKey()
    {
        $stmt = $this->pdo->prepare("
            SELECT api_key FROM api_keys
            WHERE is_active = TRUE
            ORDER BY usage_count ASC, RAND()
            LIMIT 1
        ");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ? $result['api_key'] : null;
    }

    /**
     * Update API key usage
     */
    public function updateApiKeyUsage($api_key)
    {
        $stmt = $this->pdo->prepare("
            UPDATE api_keys
            SET usage_count = usage_count + 1, last_used_at = CURRENT_TIMESTAMP
            WHERE api_key = ?
        ");
        $stmt->execute([$api_key]);
    }
}