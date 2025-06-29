<?php

/**
 * AI Bilgi Yarışması - Backend API Router
 *
 * Bu dosya, ön uçtan (JavaScript) gelen AJAX isteklerini işler.
 * Gelen 'action' parametresine göre ilgili Controller'a yönlendirme yapar.
 */

// --- Kurulum ve Başlangıç Ayarları ---
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'config.php';
require_once 'GeminiAPI.php';
require_once 'Api/Controllers/UserController.php';
require_once 'Api/Controllers/GameController.php';
require_once 'Api/Controllers/AdminController.php';
require_once 'Api/Controllers/DataController.php';

session_start();
header('Content-Type: application/json');

// --- Veritabanı Bağlantısı ---
$pdo = null;
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Veritabanı bağlantı hatası. Lütfen install.php dosyasını çalıştırdığınızdan emin olun.']);
    exit();
}

// --- Gelen Veri ---
$request_data = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $request_data['action'] ?? $_GET['action'] ?? null;

// --- Controller'ları Başlat ---
$userController = new UserController($pdo);
$gameController = new GameController($pdo, GEMINI_API_KEY);
$adminController = new AdminController($pdo);
$dataController = new DataController($pdo);

// --- Rota Tanımları ---
// Rota yapısı: 'action' => [Controller, 'method', $data_gerekiyor_mu, $auth_gerekiyor_mu]
$routes = [
    // User Routes
    'register' => [$userController, 'register', true, false],
    'login' => [$userController, 'login', true, false],
    'logout' => [$userController, 'logout', false, true],
    'check_session' => [$userController, 'checkSession', false, false],

    // Game Routes
    'get_question' => [$gameController, 'getQuestion', true, true],
    'submit_answer' => [$gameController, 'submitAnswer', true, true],

    // Data Routes
    'get_user_data' => [$dataController, 'getUserData', false, true],
    'get_leaderboard' => [$dataController, 'getLeaderboard', false, true],
    'get_user_achievements' => [$dataController, 'getUserAchievements', false, true],

    // Admin Routes
    'admin_get_dashboard_data' => [$adminController, 'getDashboardData', false, true],
    'admin_get_all_users' => [$adminController, 'getAllUsers', false, true],
    'admin_delete_user' => [$adminController, 'deleteUser', true, true],
    'admin_update_user_role' => [$adminController, 'updateUserRole', true, true],
];

// --- Yönlendirici (Router) Mantığı ---
if (isset($routes[$action])) {
    list($controller, $method, $needsData, $needsAuth) = $routes[$action];

    // Yetkilendirme kontrolü
    if ($needsAuth && !isset($_SESSION['user_id'])) {
        http_response_code(401); // Unauthorized
        $response = ['success' => false, 'message' => 'Bu işlem için giriş yapmalısınız.'];
    } else {
        // Metodu çağır
        $data = $needsData ? $request_data : null;
        $response = $data ? $controller->$method($data) : $controller->$method();
    }
} else {
    $response = ['success' => false, 'message' => "Belirtilen aksiyon ('$action') geçersiz."];
}

// --- Yanıtı Gönder ---
echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
exit();
