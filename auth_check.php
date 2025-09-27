<?php
require_once 'config.php';
session_start();

// Kullanıcı giriş yapmış mı kontrol et
if (!isset($_SESSION['user_id'])) {
    // Giriş yapmamışsa login sayfasına yönlendir
    header('Location: ' . DOMAIN . 'login.php');
    exit();
}

// Maintenance mode kontrolü
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Maintenance mode ayarını kontrol et
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'maintenance_mode'");
    $stmt->execute();
    $maintenance_mode = $stmt->fetchColumn();

    // Eğer maintenance mode aktifse ve kullanıcı admin değilse, maintenance sayfasına yönlendir
    if ($maintenance_mode == '1' && ($_SESSION['role'] ?? 'user') !== 'admin') {
        header('Location: ' . DOMAIN . 'maintenance.php');
        exit();
    }
} catch (PDOException $e) {
    // Veritabanı hatası durumunda devam et (güvenlik açısından)
    error_log("Maintenance mode check error: " . $e->getMessage());
}

// CSRF token üret
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Kullanıcı bilgilerini al
$user_data = [
    'id' => $_SESSION['user_id'],
    'username' => $_SESSION['username'] ?? 'Unknown',
    'role' => $_SESSION['role'] ?? 'user',
    'avatar' => $_SESSION['avatar'] ?? 'avatar1.svg',
    'coins' => $_SESSION['coins'] ?? 0,
    'csrf_token' => $_SESSION['csrf_token']
];
?>