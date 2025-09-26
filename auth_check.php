<?php
require_once 'config.php';
session_start();

// Kullanıcı giriş yapmış mı kontrol et
if (!isset($_SESSION['user_id'])) {
    // Giriş yapmamışsa login sayfasına yönlendir
    header('Location: ' . DOMAIN . 'login.php');
    exit();
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