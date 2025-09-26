<?php
require_once 'config.php';
session_start();

// Kullanıcı giriş yapmış mı kontrol et
if (!isset($_SESSION['user_id'])) {
    // Giriş yapmamışsa ana sayfaya yönlendir
    header('Location: ' . DOMAIN . 'index.php');
    exit();
}

// Kullanıcı bilgilerini al
$user_data = [
    'id' => $_SESSION['user_id'],
    'username' => $_SESSION['username'] ?? 'Unknown',
    'role' => $_SESSION['role'] ?? 'user',
    'avatar' => $_SESSION['avatar'] ?? 'avatar1.svg',
    'coins' => $_SESSION['coins'] ?? 0,
    'csrf_token' => $_SESSION['csrf_token'] ?? ''
];
?>