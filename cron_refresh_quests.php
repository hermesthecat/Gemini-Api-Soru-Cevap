<?php
/**
 * Quest Refresh Cron Script
 *
 * Bu script veritabanındaki quest_refresh_time ayarına göre
 * günlük görevleri yeniler. Tüm kullanıcılar için yeni quest'ler atar.
 *
 * Cron job olarak çalıştırılmalı:
 * 0 * * * * /path/to/php /path/to/cron_refresh_quests.php
 * (Her saat başı çalıştır, script kendi kontrolünü yapar)
 */

require_once 'config.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "[" . date('Y-m-d H:i:s') . "] Quest Refresh Cron başlatıldı\n";

    // Settings'den quest_refresh_time ayarını al (saat cinsinden)
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'quest_refresh_time'");
    $stmt->execute();
    $refresh_hours = (int)($stmt->fetchColumn() ?: 24);

    echo "Quest yenileme süresi: {$refresh_hours} saat\n";

    // Aktif kullanıcıları al (son 7 günde giriş yapmış olanlar)
    $stmt = $pdo->prepare("
        SELECT id, username, last_login_date
        FROM users
        WHERE last_login_date >= DATE_SUB(CURRENT_DATE, INTERVAL 7 DAY)
        ORDER BY last_login_date DESC
    ");
    $stmt->execute();
    $active_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Kontrol edilecek aktif kullanıcı sayısı: " . count($active_users) . "\n\n";

    $total_refreshed = 0;
    $total_assigned = 0;

    foreach ($active_users as $user) {
        $user_id = $user['id'];
        $username = $user['username'];

        // Bu kullanıcının en son quest assignment zamanını kontrol et
        $stmt = $pdo->prepare("
            SELECT MAX(assigned_date) as last_assigned
            FROM user_quests
            WHERE user_id = ?
        ");
        $stmt->execute([$user_id]);
        $last_assigned = $stmt->fetchColumn();

        if (!$last_assigned) {
            // Hiç quest atanmamış, ilk kez ata
            $need_refresh = true;
            $reason = "İlk quest ataması";
        } else {
            // Son assignment'tan bu yana geçen saati hesapla
            $last_assigned_time = strtotime($last_assigned . ' 00:00:00');
            $current_time = time();
            $hours_passed = ($current_time - $last_assigned_time) / 3600;

            $need_refresh = $hours_passed >= $refresh_hours;
            $reason = sprintf("%.1f saat geçti (limit: %d)", $hours_passed, $refresh_hours);
        }

        if ($need_refresh) {
            // Yeni quest'ler ata
            $today = date('Y-m-d');
            $assigned_count = assignNewQuestsForUser($pdo, $user_id, $today);

            if ($assigned_count > 0) {
                echo "✓ {$username}: {$assigned_count} yeni quest atandı ({$reason})\n";
                $total_refreshed++;
                $total_assigned += $assigned_count;
            } else {
                echo "! {$username}: Quest atanamadı ({$reason})\n";
            }
        } else {
            echo "- {$username}: Quest yenileme gerekmedi ({$reason})\n";
        }
    }

    echo "\n=== ÖZET ===\n";
    echo "Quest yenilenen kullanıcı: {$total_refreshed}\n";
    echo "Toplam atanan quest: {$total_assigned}\n";
    echo "Cron job tamamlandı: " . date('Y-m-d H:i:s') . "\n";

} catch (Exception $e) {
    echo "HATA: " . $e->getMessage() . "\n";
    error_log("Quest refresh cron error: " . $e->getMessage());
}

/**
 * Belirli bir kullanıcıya yeni quest'ler atar
 */
function assignNewQuestsForUser($pdo, $user_id, $date, $count = 2)
{
    try {
        // Mevcut aktif quest'leri kontrol et
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as active_count
            FROM user_quests
            WHERE user_id = ? AND assigned_date = ? AND is_completed = FALSE
        ");
        $stmt->execute([$user_id, $date]);
        $active_count = (int)$stmt->fetchColumn();

        // Eğer bugün için zaten aktif quest'ler varsa atama
        if ($active_count >= $count) {
            return 0;
        }

        $needed_count = $count - $active_count;

        // Son 7 günde atanan quest'leri al (tekrar engelleme)
        $stmt = $pdo->prepare("
            SELECT DISTINCT quest_key
            FROM user_quests
            WHERE user_id = ? AND assigned_date >= DATE_SUB(?, INTERVAL 7 DAY)
        ");
        $stmt->execute([$user_id, $date]);
        $recent_quests = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Uygun quest'leri bul
        $exclude_clause = empty($recent_quests) ? '' : 'AND quest_key NOT IN (' . str_repeat('?,', count($recent_quests) - 1) . '?)';

        $stmt = $pdo->prepare("
            SELECT quest_key, default_goal
            FROM quests
            WHERE is_active = TRUE {$exclude_clause}
            ORDER BY RAND()
            LIMIT {$needed_count}
        ");
        $stmt->execute($recent_quests);
        $available_quests = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($available_quests)) {
            return 0;
        }

        // Quest'leri ata
        $stmt_insert = $pdo->prepare("
            INSERT INTO user_quests (user_id, quest_key, goal, assigned_date, start_time)
            VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)
            ON DUPLICATE KEY UPDATE assigned_date = assigned_date
        ");

        $assigned_count = 0;
        foreach ($available_quests as $quest) {
            try {
                $stmt_insert->execute([$user_id, $quest['quest_key'], $quest['default_goal'], $date]);
                if ($stmt_insert->rowCount() > 0) {
                    $assigned_count++;
                }
            } catch (PDOException $e) {
                // Duplicate key hatası - normal
                if ($e->getCode() != 23000) {
                    error_log("Quest assignment error for user {$user_id}: " . $e->getMessage());
                }
            }
        }

        return $assigned_count;

    } catch (Exception $e) {
        error_log("assignNewQuestsForUser error: " . $e->getMessage());
        return 0;
    }
}
?>