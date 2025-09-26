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
}