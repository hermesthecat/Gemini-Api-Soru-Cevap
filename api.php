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
require_once 'Api/Controllers/FriendsController.php';
require_once 'Api/Controllers/DuelController.php';
require_once 'Api/Controllers/QuestController.php';

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

// --- Temel İstek Doğrulama ---
if (!$action) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'Aksiyon (action) belirtilmedi.']);
    exit();
}

// --- Controller'ları Başlat ---
$geminiApi = new GeminiAPI(GEMINI_API_KEY);

$userController = new UserController($pdo);
$gameController = new GameController($pdo, $geminiApi);
$adminController = new AdminController($pdo);
$dataController = new DataController($pdo);
$friendsController = new FriendsController($pdo);
$duelController = new DuelController($pdo, $geminiApi);
$questController = new QuestController($pdo);

// Genel Hata Yakalama
try {
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

        // Friends Routes
        'friends_search_users' => [$friendsController, 'searchUsers', true, true],
        'friends_send_request' => [$friendsController, 'sendRequest', true, true],
        'friends_get_pending_requests' => [$friendsController, 'getPendingRequests', false, true],
        'friends_respond_to_request' => [$friendsController, 'respondToRequest', true, true],
        'friends_get_list' => [$friendsController, 'getFriendsList', false, true],
        'friends_remove' => [$friendsController, 'removeFriend', true, true],

        // Duel Routes
        'duel_create' => [$duelController, 'createDuel', true, true],
        'duel_get_duels' => [$duelController, 'getDuels', false, true],
        'duel_respond' => [$duelController, 'respondToDuel', true, true],
        'duel_start_game' => [$duelController, 'startDuelGame', true, true],
        'duel_submit_answer' => [$duelController, 'submitDuelAnswer', true, true],

        // Quest Routes
        'get_daily_quests' => [$questController, 'getDailyQuests', false, true],
    ];

    // --- Yönlendirici (Router) Mantığı ---
    if (isset($routes[$action])) {
        list($controller, $method, $needsData, $needsAuth) = $routes[$action];

        // Yetkilendirme kontrolü
        if ($needsAuth && !isset($_SESSION['user_id'])) {
            http_response_code(401); // Unauthorized
            $response = ['success' => false, 'message' => 'Bu işlem için giriş yapmalısınız.'];
        } else {
            // CSRF Token Kontrolü (POST ile gelen ve kimlik doğrulaması gerektiren tüm işlemler için)
            $isPostRequest = $_SERVER['REQUEST_METHOD'] === 'POST';
            if ($isPostRequest && $needsAuth) {
                $csrf_header = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
                if (empty($csrf_header) || !hash_equals($_SESSION['csrf_token'] ?? '', $csrf_header)) {
                    http_response_code(403); // Forbidden
                    $response = ['success' => false, 'message' => 'Geçersiz güvenlik anahtarı (CSRF). Lütfen sayfayı yenileyip tekrar deneyin.'];
                    echo json_encode($response);
                    exit();
                }
            }

            // Metodu çağır
            $data = $needsData ? $request_data : [];
            $response = $data ? $controller->$method($data) : $controller->$method();
        }
    } else {
        http_response_code(404); // Not Found
        $response = ['success' => false, 'message' => "Belirtilen aksiyon ('$action') geçersiz."];
    }
} catch (PDOException $e) {
    // Veritabanı ile ilgili kritik hatalar
    error_log("Veritabanı Hatası: " . $e->getMessage()); // Hataları log dosyasına yaz
    http_response_code(500); // Internal Server Error
    $response = ['success' => false, 'message' => 'Sunucuda bir veritabanı hatası oluştu. Lütfen daha sonra tekrar deneyin.'];
} catch (Exception $e) {
    // Diğer beklenmedik tüm hatalar
    error_log("Genel Hata: " . $e->getMessage()); // Hataları log dosyasına yaz
    http_response_code(500); // Internal Server Error
    $response = ['success' => false, 'message' => 'Sunucuda beklenmedik bir hata oluştu. Lütfen daha sonra tekrar deneyin.'];
}

// --- Yanıtı Gönder ---
echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
exit();
