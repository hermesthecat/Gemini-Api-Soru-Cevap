<?php

/**
 * AI Bilgi Yarışması - Backend API (Stateless)
 *
 * Bu dosya, ön uçtan (JavaScript) gelen AJAX isteklerini işler.
 * Artık istatistik veya geçmiş tutmaz, sadece soru üretir, cevapları kontrol eder ve skorları yönetir.
 */

// --- Kurulum ve Başlangıç Ayarları ---
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'config.php';
require_once 'GeminiAPI.php';

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

// --- Gelen Veri ve Yanıt Yapısı ---
$request_data = json_decode(file_get_contents('php://input'), true);
$action = $request_data['action'] ?? $_GET['action'] ?? null;
$response = ['success' => false, 'message' => 'Geçersiz istek.', 'data' => null];

// --- Ana Yönlendirici (Router) ---
switch ($action) {
    // KULLANICI İŞLEMLERİ
    case 'register':
        $username = $request_data['username'] ?? '';
        $password = $request_data['password'] ?? '';

        if (empty($username) || empty($password)) {
            $response['message'] = 'Kullanıcı adı ve şifre boş olamaz.';
            break;
        }
        if (strlen($password) < 6) {
            $response['message'] = 'Şifre en az 6 karakter olmalıdır.';
            break;
        }

        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $response['message'] = 'Bu kullanıcı adı zaten alınmış.';
                break;
            }

            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
            $stmt->execute([$username, $hashed_password]);
            $user_id = $pdo->lastInsertId();

            // Yeni kullanıcı için leaderboard'a 0 skorla ekle
            $stmt = $pdo->prepare("INSERT INTO leaderboard (user_id, score) VALUES (?, 0)");
            $stmt->execute([$user_id]);

            $response['success'] = true;
            $response['message'] = 'Kayıt başarılı! Şimdi giriş yapabilirsiniz.';
        } catch (PDOException $e) {
            $response['message'] = 'Veritabanı hatası: ' . $e->getMessage();
        }
        break;

    case 'login':
        $username = $request_data['username'] ?? '';
        $password = $request_data['password'] ?? '';

        if (empty($username) || empty($password)) {
            $response['message'] = 'Kullanıcı adı ve şifre boş olamaz.';
            break;
        }

        try {
            $stmt = $pdo->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_role'] = $user['role'];
                $response['success'] = true;
                $response['message'] = 'Giriş başarılı!';
                $response['data'] = ['id' => $user['id'], 'username' => $user['username'], 'role' => $user['role']];
            } else {
                $response['message'] = 'Kullanıcı adı veya şifre hatalı.';
            }
        } catch (PDOException $e) {
            $response['message'] = 'Veritabanı hatası: ' . $e->getMessage();
        }
        break;

    case 'logout':
        session_destroy();
        $response['success'] = true;
        $response['message'] = 'Çıkış yapıldı.';
        break;

    case 'check_session':
        if (isset($_SESSION['user_id'])) {
            $response['success'] = true;
            $response['message'] = 'Oturum aktif.';
            $response['data'] = ['id' => $_SESSION['user_id'], 'username' => $_SESSION['username'], 'role' => $_SESSION['user_role']];
        } else {
            $response['message'] = 'Oturum bulunamadı.';
        }
        break;

    // OTURUM GEREKTİREN İŞLEMLER
    case 'get_question':
    case 'submit_answer':
    case 'get_user_data':
    case 'get_leaderboard':
    case 'get_user_achievements':
        // --- YENİ: ADMIN İŞLEMLERİ ---
    case 'admin_get_dashboard_data':
    case 'admin_get_all_users':
    case 'admin_delete_user':
    case 'admin_update_user_role':
        if (!isset($_SESSION['user_id'])) {
            $response['message'] = 'Bu işlem için giriş yapmalısınız.';
            $response['data'] = ['auth_required' => true];
            break;
        }

        // --- Oyun ve Veri İşlemleri Yönlendiricisi ---
        switch ($action) {
            case 'get_question':
                // Mevcut soru bilgilerini temizle
                unset($_SESSION['current_question_answer'], $_SESSION['current_question_explanation'], $_SESSION['start_time']);

                $kategori = $request_data['kategori'] ?? 'genel kültür';
                $difficulty = $request_data['difficulty'] ?? 'orta';
                $gemini = new GeminiAPI(GEMINI_API_KEY);

                try {
                    // Rastgele soru tipi seç (%75 çoktan seçmeli, %25 doğru/yanlış)
                    $tip = (rand(1, 100) <= 75) ? 'coktan_secmeli' : 'dogru_yanlis';
                    $prompt = '';

                    if ($tip === 'coktan_secmeli') {
                        $prompt = "Lütfen {$kategori} kategorisinde {$difficulty} zorlukta bir soru hazırla. Yanıtı yalnızca şu JSON formatında, başka hiçbir metin olmadan ver:
                        {
                            \"tip\": \"coktan_secmeli\",
                            \"soru\": \"(soru metni buraya)\",
                            \"siklar\": {
                                \"A\": \"(A şıkkı buraya)\",
                                \"B\": \"(B şıkkı buraya)\",
                                \"C\": \"(C şıkkı buraya)\",
                                \"D\": \"(D şıkkı buraya)\"
                            },
                            \"dogru_cevap\": \"(Doğru şıkkın harfi buraya, örneğin: A)\",
                            \"aciklama\": \"(Doğru cevabın neden doğru olduğuna dair 1-2 cümlelik açıklama)\"
                        }";
                    } else { // dogru_yanlis
                        $prompt = "Lütfen {$kategori} kategorisinde {$difficulty} zorlukta, doğru ya da yanlış olarak cevaplanabilecek bir önerme hazırla. Yanıtı yalnızca şu JSON formatında, başka hiçbir metin olmadan ver:
                        {
                            \"tip\": \"dogru_yanlis\",
                            \"soru\": \"(Önerme cümlesi buraya)\",
                            \"dogru_cevap\": \"(Doğru ya da Yanlış kelimelerinden biri)\",
                            \"aciklama\": \"(Önermenin neden doğru ya da yanlış olduğuna dair 1-2 cümlelik açıklama)\"
                        }";
                    }

                    $yanit = $gemini->soruSor($prompt);

                    if ($yanit) {
                        $temiz_yanit = preg_replace('/^```json\s*|\s*```$/', '', trim($yanit));
                        $veri = json_decode($temiz_yanit, true);

                        if (json_last_error() === JSON_ERROR_NONE && isset($veri['tip'], $veri['soru'], $veri['dogru_cevap'], $veri['aciklama'])) {
                            if ($veri['tip'] === 'coktan_secmeli' && !isset($veri['siklar'])) {
                                $response['message'] = 'API yanıtında çoktan seçmeli soru için şıklar bulunamadı.';
                                break;
                            }

                            // Cevap ve açıklamayı session'a kaydet
                            $_SESSION['current_question_answer'] = $veri['dogru_cevap'];
                            $_SESSION['current_question_explanation'] = $veri['aciklama'];
                            $_SESSION['start_time'] = time();

                            // Kullanıcıya sadece güvenli verileri gönder
                            $response['data'] = ['tip' => $veri['tip'], 'question' => $veri['soru'], 'siklar' => $veri['siklar'] ?? null, 'kategori' => $kategori, 'difficulty' => $difficulty, 'correct_answer' => $veri['dogru_cevap']];
                            $response['success'] = true;
                        } else {
                            $response['message'] = 'API\'den gelen soru formatı geçersiz veya eksik alanlar var. Yanıt: ' . $temiz_yanit;
                        }
                    } else {
                        $response['message'] = "Gemini API'sinden yanıt alınamadı.";
                    }
                } catch (Exception $e) {
                    $response['message'] = 'Soru alınırken sunucu hatası: ' . $e->getMessage();
                }
                break;

            case 'submit_answer':
                if (!isset($_SESSION['current_question_answer'])) {
                    $response['message'] = 'Aktif soru bulunamadı.';
                    break;
                }

                $user_answer = $request_data['answer'] ?? null;
                $kategori = $request_data['kategori'] ?? 'bilinmiyor';
                $gecen_sure = time() - ($_SESSION['start_time'] ?? time());
                $is_correct = ($user_answer === $_SESSION['current_question_answer']);
                $puan = 0;
                $yeni_basarimlar = [];

                if ($is_correct) {
                    $puan = 10 + max(0, 30 - $gecen_sure); // Doğru cevap için 10 puan + kalan süre kadar bonus
                    $_SESSION['consecutive_correct'] = ($_SESSION['consecutive_correct'] ?? 0) + 1;
                } else {
                    $_SESSION['consecutive_correct'] = 0;
                }

                try {
                    // 1. İstatistiği güncelle
                    $sql_stat = "
                        INSERT INTO user_stats (user_id, category, total_questions, correct_answers)
                        VALUES (?, ?, 1, ?)
                        ON DUPLICATE KEY UPDATE total_questions = total_questions + 1, correct_answers = correct_answers + ?";
                    $stmt_stat = $pdo->prepare($sql_stat);
                    $stmt_stat->execute([$_SESSION['user_id'], $kategori, $is_correct ? 1 : 0, $is_correct ? 1 : 0]);

                    // 2. Skoru güncelle
                    if ($is_correct) {
                        $sql_score = "UPDATE leaderboard SET score = score + ? WHERE user_id = ?";
                        $stmt_score = $pdo->prepare($sql_score);
                        $stmt_score->execute([$puan, $_SESSION['user_id']]);
                    }

                    // 3. Başarımları kontrol et (sadece doğru cevapta)
                    if ($is_correct) {
                        $user_id = $_SESSION['user_id'];

                        // Mevcut başarımları al
                        $stmt_ach = $pdo->prepare("SELECT achievement_key FROM user_achievements WHERE user_id = ?");
                        $stmt_ach->execute([$user_id]);
                        $mevcut_basarimlar = $stmt_ach->fetchAll(PDO::FETCH_COLUMN);

                        $grant_achievement = function ($key) use ($pdo, $user_id, $mevcut_basarimlar, &$yeni_basarimlar) {
                            if (!in_array($key, $mevcut_basarimlar)) {
                                $stmt = $pdo->prepare("INSERT INTO user_achievements (user_id, achievement_key) VALUES (?, ?)");
                                $stmt->execute([$user_id, $key]);
                                $yeni_basarimlar[] = $key;
                            }
                        };

                        // Başarım 1: Seri Galibi
                        if ($_SESSION['consecutive_correct'] >= 25) {
                            $grant_achievement('seri_galibi_25');
                        } elseif ($_SESSION['consecutive_correct'] >= 10) {
                            $grant_achievement('seri_galibi_10');
                        }

                        // Başarım 2: Hız Tutkunu (5 saniye altı)
                        if ($gecen_sure <= 5) {
                            $grant_achievement('hiz_tutkunu');
                        }

                        // Başarım 3: Kategori Uzmanı (Bir kategoride 20 doğru)
                        $stmt_cat = $pdo->prepare("SELECT correct_answers FROM user_stats WHERE user_id = ? AND category = ?");
                        $stmt_cat->execute([$user_id, $kategori]);
                        $cat_corrects = $stmt_cat->fetchColumn();
                        if ($cat_corrects && $cat_corrects >= 20) {
                            $grant_achievement("uzman_{$kategori}");
                        }

                        // Başarım 4: İlk Adım (İlk doğru cevap)
                        $stmt_total_correct = $pdo->prepare("SELECT SUM(correct_answers) FROM user_stats WHERE user_id = ?");
                        $stmt_total_correct->execute([$user_id]);
                        if ($stmt_total_correct->fetchColumn() == 1) {
                            $grant_achievement('ilk_adim');
                        }

                        // Başarım 5: Meraklı (Tüm kategorilerden oynamış)
                        // Not: Kategorileri bir config dosyasında tutmak daha iyi olur, şimdilik sabit.
                        $kategori_sayisi = 6;
                        $stmt_cats_played = $pdo->prepare("SELECT COUNT(DISTINCT category) FROM user_stats WHERE user_id = ?");
                        $stmt_cats_played->execute([$user_id]);
                        if ($stmt_cats_played->fetchColumn() >= $kategori_sayisi) {
                            $grant_achievement('merakli');
                        }

                        // Başarım 6: Puan Avcısı (1000 puana ulaşma)
                        $stmt_score = $pdo->prepare("SELECT score FROM leaderboard WHERE user_id = ?");
                        $stmt_score->execute([$user_id]);
                        if ($stmt_score->fetchColumn() >= 1000) {
                            $grant_achievement('puan_avcisi_1000');
                        }
                    }
                } catch (PDOException $e) {
                    // Hata olsa bile devam et, oyun akışı bozulmasın
                }

                $response['success'] = true;
                $response['data'] = [
                    'is_correct' => $is_correct,
                    'correct_answer' => $_SESSION['current_question_answer'],
                    'explanation' => $_SESSION['current_question_explanation'],
                    'new_achievements' => $yeni_basarimlar
                ];
                unset($_SESSION['current_question_answer'], $_SESSION['current_question_explanation'], $_SESSION['start_time']);
                break;

            case 'get_user_data':
                $user_id = $_SESSION['user_id'];
                $user_data = [];
                // Liderlik tablosundan skor al
                $stmt_score = $pdo->prepare("SELECT score FROM leaderboard WHERE user_id = ?");
                $stmt_score->execute([$user_id]);
                $user_data['score'] = $stmt_score->fetchColumn() ?: 0;

                // İstatistikleri al
                $stmt_stats = $pdo->prepare("SELECT category, total_questions, correct_answers FROM user_stats WHERE user_id = ?");
                $stmt_stats->execute([$user_id]);
                $user_data['stats'] = $stmt_stats->fetchAll(PDO::FETCH_ASSOC);

                $response['success'] = true;
                $response['data'] = $user_data;
                break;

            case 'get_leaderboard':
                $stmt = $pdo->prepare("
                    SELECT u.username, l.score 
                    FROM leaderboard l
                    JOIN users u ON l.user_id = u.id
                    ORDER BY l.score DESC, l.last_updated ASC 
                    LIMIT 10
                ");
                $stmt->execute();
                $response['success'] = true;
                $response['data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                break;

            case 'get_user_achievements':
                $stmt = $pdo->prepare("SELECT achievement_key, achieved_at FROM user_achievements WHERE user_id = ? ORDER BY achieved_at DESC");
                $stmt->execute([$_SESSION['user_id']]);
                $response['success'] = true;
                $response['data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                break;

            // --- YENİ: ADMIN İŞLEMLERİ ALT YÖNLENDİRİCİSİ ---
            case 'admin_get_dashboard_data':
            case 'admin_get_all_users':
            case 'admin_delete_user':
            case 'admin_update_user_role':
                if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
                    $response['message'] = 'Bu alana erişim yetkiniz yok.';
                    http_response_code(403); // Forbidden
                    break;
                }

                // Admin işlemleri
                switch ($action) {
                    case 'admin_get_dashboard_data':
                        $stmt_users = $pdo->query("SELECT COUNT(*) FROM users");
                        $total_users = $stmt_users->fetchColumn();

                        $stmt_questions = $pdo->query("SELECT SUM(total_questions) FROM user_stats");
                        $total_questions = $stmt_questions->fetchColumn() ?: 0;

                        $response['success'] = true;
                        $response['data'] = [
                            'total_users' => $total_users,
                            'total_questions_answered' => $total_questions,
                        ];
                        break;

                    case 'admin_get_all_users':
                        $stmt = $pdo->query("
                            SELECT u.id, u.username, u.role, u.created_at, l.score 
                            FROM users u 
                            LEFT JOIN leaderboard l ON u.id = l.user_id 
                            ORDER BY u.created_at DESC
                        ");
                        $response['success'] = true;
                        $response['data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        break;

                    case 'admin_delete_user':
                        $user_id_to_delete = $request_data['user_id'] ?? 0;
                        if ($user_id_to_delete === $_SESSION['user_id']) {
                            $response['message'] = 'Kendinizi silemezsiniz.';
                            break;
                        }
                        if ($user_id_to_delete > 0) {
                            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                            $stmt->execute([$user_id_to_delete]);
                            $response['success'] = $stmt->rowCount() > 0;
                            $response['message'] = $response['success'] ? 'Kullanıcı silindi.' : 'Kullanıcı bulunamadı.';
                        }
                        break;

                    case 'admin_update_user_role':
                        $user_id_to_update = $request_data['user_id'] ?? 0;
                        $new_role = $request_data['new_role'] ?? '';
                        if ($user_id_to_update === $_SESSION['user_id']) {
                            $response['message'] = 'Kendi rolünüzü değiştiremezsiniz.';
                            break;
                        }
                        if ($user_id_to_update > 0 && ($new_role === 'admin' || $new_role === 'user')) {
                            $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
                            $stmt->execute([$new_role, $user_id_to_update]);
                            $response['success'] = $stmt->rowCount() > 0;
                            $response['message'] = $response['success'] ? 'Kullanıcı rolü güncellendi.' : 'İşlem başarısız.';
                        } else {
                            $response['message'] = 'Geçersiz kullanıcı ID veya rol.';
                        }
                        break;
                }
                break;
        }
        break;

    default:
        $response['message'] = "Belirtilen aksiyon ('$action') geçersiz.";
        break;
}

// --- Yanıtı Gönder ---
echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
exit();
