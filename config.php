<?php

// Hata raporlamayı geliştirme aşamasında aç, canlıda kapat
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/**
 * Veritabanı Bağlantı Ayarları
 * XAMPP varsayılan ayarlarına göre düzenlenmiştir.
 */
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'ai_quiz');

/**
 * Domain Ayarları
 */
define('DOMAIN', 'https://ai-soru-cevap.local.keremgok.tr/'); // Domain adresi

/**
 * Sistem Ayarları
 * Veritabanından sistem ayarlarını çek ve sabitleri tanımla
 */
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);

    // Timezone ayarını çek
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'timezone_setting'");
    $stmt->execute();
    $timezone = $stmt->fetchColumn();

    if ($timezone) {
        date_default_timezone_set($timezone);
        define('DEFAULT_TIMEZONE', $timezone);
    } else {
        date_default_timezone_set('Europe/Istanbul');
        define('DEFAULT_TIMEZONE', 'Europe/Istanbul');
    }

    // Registration ayarını çek
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'registration_enabled'");
    $stmt->execute();
    $registration_enabled = $stmt->fetchColumn();
    define('REGISTRATION_ENABLED', $registration_enabled === '1');

    // Site adını çek
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'site_name'");
    $stmt->execute();
    $site_name = $stmt->fetchColumn();
    define('SITE_NAME', $site_name ?: 'AI Soru Cevap Yarışması');

} catch (PDOException $e) {
    // Veritabanı henüz kurulmamışsa varsayılan değerleri kullan
    date_default_timezone_set('Europe/Istanbul');
    define('DEFAULT_TIMEZONE', 'Europe/Istanbul');
    define('REGISTRATION_ENABLED', true);
    define('SITE_NAME', 'AI Soru Cevap Yarışması');
}
