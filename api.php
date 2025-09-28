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
require_once 'Api/Controllers/QuestionsController.php';
require_once 'Api/Controllers/ShopController.php';
require_once 'Api/Controllers/SettingsController.php';
require_once 'Api/Controllers/SocialController.php';

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

// --- Maintenance Mode Kontrolü ---
try {
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'maintenance_mode'");
    $stmt->execute();
    $maintenance_mode = $stmt->fetchColumn();

    // Eğer maintenance mode aktifse ve kullanıcı admin değilse API'yi engelle
    // Sadece login, register ve check_session işlemlerine izin ver
    $allowed_actions_during_maintenance = ['login', 'register', 'check_session'];

    if ($maintenance_mode == '1' && !in_array($action, $allowed_actions_during_maintenance)) {
        $user_role = $_SESSION['role'] ?? 'user';
        if ($user_role !== 'admin') {
            http_response_code(503); // Service Unavailable
            echo json_encode([
                'success' => false,
                'message' => 'Site bakım modunda. Lütfen daha sonra tekrar deneyin.',
                'maintenance_mode' => true
            ]);
            exit();
        }
    }
} catch (PDOException $e) {
    error_log("Maintenance mode check error in API: " . $e->getMessage());
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
// Gemini API için dinamik API key kullanımı - SettingsController'dan çekilecek
$geminiApi = new GeminiAPI(null, $pdo); // API key null geçiliyor, GeminiAPI class'ında dinamik çekilecek

$userController = new UserController($pdo);
$gameController = new GameController($pdo, $geminiApi);
$adminController = new AdminController($pdo);
$dataController = new DataController($pdo);
$friendsController = new FriendsController($pdo);
$duelController = new DuelController($pdo, $geminiApi);
$questController = new QuestController($pdo);
$questionsController = new QuestionsController($pdo);
$shopController = new ShopController($pdo);
$settingsController = new SettingsController($pdo);
$socialController = new SocialController($pdo);

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

        // Public Profile Routes
        'get_public_profile' => [$userController, 'getPublicProfile', true, false],
        'search_users' => [$userController, 'searchUsers', true, false],
        'update_profile_visibility' => [$userController, 'updateProfileVisibility', true, true],

        // Game Routes
        'get_question' => [$gameController, 'getQuestion', true, true],
        'submit_answer' => [$gameController, 'submitAnswer', true, true],
        'use_lifeline' => [$gameController, 'useLifeline', true, true],

        // Data Routes
        'get_user_data' => [$dataController, 'getUserData', false, true],
        'get_leaderboard' => [$dataController, 'getLeaderboard', false, true],
        'get_user_rank' => [$dataController, 'getUserRank', false, true],
        'get_user_achievements' => [$dataController, 'getUserAchievements', false, true],
        'get_achievement_progress' => [$dataController, 'getAchievementProgress', false, true],
        'get_combined_achievements' => [$dataController, 'getCombinedAchievements', false, true],
        'get_user_quest_history' => [$dataController, 'getUserQuestHistory', false, true],
        'get_quest_history_stats' => [$dataController, 'getQuestHistoryStats', false, true],
        'get_active_announcements' => [$dataController, 'getActiveAnnouncements', false, true],
        'mark_announcements_as_read' => [$dataController, 'markAnnouncementsAsRead', true, true],
        'get_categories' => [$dataController, 'getCategories', false, false],

        // Admin Routes
        'admin_get_dashboard_data' => [$adminController, 'getDashboardData', false, true],
        'admin_get_all_users' => [$adminController, 'getAllUsers', false, true],
        'admin_delete_user' => [$adminController, 'deleteUser', true, true],
        'admin_update_user_role' => [$adminController, 'updateUserRole', true, true],
        'admin_update_user_coins' => [$adminController, 'updateUserCoins', true, true],
        'admin_get_announcements' => [$adminController, 'getAnnouncements', false, true],
        'admin_create_announcement' => [$adminController, 'createAnnouncement', true, true],
        'admin_delete_announcement' => [$adminController, 'deleteAnnouncement', true, true],
        'admin_get_advanced_stats' => [$adminController, 'getAdvancedStats', false, true],
        'admin_get_shop_stats' => [$adminController, 'getShopStats', false, true],
        'admin_update_shop_prices' => [$adminController, 'updateShopPrices', true, true],

        // Category Management
        'admin_get_categories' => [$adminController, 'getCategories', false, true],
        'admin_add_category' => [$adminController, 'addCategory', true, true],
        'admin_update_category' => [$adminController, 'updateCategory', true, true],
        'admin_delete_category' => [$adminController, 'deleteCategory', true, true],

        // Achievement Management
        'admin_get_achievements' => [$adminController, 'getAchievements', false, true],
        'admin_get_achievement_details' => [$adminController, 'getAchievementDetails', true, true],
        'admin_create_achievement' => [$adminController, 'createAchievement', true, true],
        'admin_update_achievement' => [$adminController, 'updateAchievement', true, true],
        'admin_delete_achievement' => [$adminController, 'deleteAchievement', true, true],
        'admin_toggle_achievement_tracking' => [$adminController, 'toggleAchievementTracking', true, true],
        'admin_get_achievement_stats' => [$adminController, 'getAchievementStats', false, true],

        // Quest Management
        'admin_get_quests' => [$adminController, 'getAllQuests', false, true],
        'admin_create_quest' => [$adminController, 'createQuest', true, true],
        'admin_update_quest' => [$adminController, 'updateQuest', true, true],
        'admin_delete_quest' => [$adminController, 'deleteQuest', true, true],
        'admin_get_quest_stats' => [$adminController, 'getQuestStats', false, true],

        // Question Management
        'admin_get_reported_questions' => [$adminController, 'getReportedQuestions', false, true],
        'admin_get_question_details' => [$adminController, 'getQuestionDetails', true, true],
        'admin_review_question' => [$adminController, 'reviewQuestion', true, true],
        'admin_get_question_stats' => [$adminController, 'getQuestionStats', false, true],

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
        'duel_cancel' => [$duelController, 'cancelDuel', true, true],
        'duel_start_game' => [$duelController, 'startDuelGame', true, true],
        'duel_submit_answer' => [$duelController, 'submitDuelAnswer', true, true],

        // Quest Routes
        'get_daily_quests' => [$questController, 'getDailyQuests', false, true],
        'refresh_quests' => [$questController, 'refreshQuests', false, true],

        // Question Rating Routes
        'submit_question_rating' => [$questionsController, 'submitQuestionRating', true, true],
        'get_question_rating' => [$questionsController, 'getQuestionRating', true, false],
        'report_question' => [$questionsController, 'reportQuestion', true, true],
        'get_user_rating_history' => [$questionsController, 'getUserRatingHistory', false, true],

        // Shop Routes
        'get_shop_items' => [$shopController, 'getShopItems', false, true],
        'purchase_lifeline' => [$shopController, 'purchaseLifeline', true, true],

        // Settings Routes
        'get_settings' => [$settingsController, 'getSettings', false, true],
        'update_settings' => [$settingsController, 'updateSettings', true, true],

        // API Keys Routes
        'get_api_keys' => [$settingsController, 'getApiKeys', false, true],
        'add_api_key' => [$settingsController, 'addApiKey', true, true],
        'update_api_key_status' => [$settingsController, 'updateApiKeyStatus', true, true],
        'delete_api_key' => [$settingsController, 'deleteApiKey', true, true],

        // Social Features Routes
        'record_profile_visit' => [$socialController, 'recordProfileVisit', true, true],
        'get_profile_visit_history' => [$socialController, 'getProfileVisitHistory', true, true],
        'share_profile' => [$socialController, 'shareProfile', true, true],
        'compare_achievements' => [$socialController, 'compareAchievements', true, true],
        'get_friend_shortcuts' => [$socialController, 'getFriendShortcuts', false, true],
        'add_profile_bookmark' => [$socialController, 'addProfileBookmark', true, true],
        'remove_profile_bookmark' => [$socialController, 'removeProfileBookmark', true, true],
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
