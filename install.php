<?php

require_once 'config.php';

header('Content-Type: text/plain; charset=utf-8');

// Installation mode: fresh=1 for complete reinstall, default for safe update
$fresh_install = isset($_GET['fresh']) && $_GET['fresh'] == '1';
$current_version = '1.2.0'; // Current schema version

echo "=== AI Bilgi Yarışması Veritabanı Kurulum/Güncelleme ===\n";
echo "Mod: " . ($fresh_install ? "Fresh Install (Tüm veriler silinecek!)" : "Safe Update (Mevcut veriler korunacak)") . "\n";
echo "Hedef Version: $current_version\n\n";

if ($fresh_install) {
    echo "⚠️  UYARI: Fresh install modu tüm mevcut verileri silecek!\n";
    echo "Production ortamında kullanmayın. Devam etmek için 3 saniye bekleniyor...\n\n";
    sleep(3);
}

try {
  // Önce veritabanı olmadan MySQL sunucusuna bağlan
  $pdo_init = new PDO("mysql:host=" . DB_HOST, DB_USER, DB_PASS);
  $pdo_init->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  $pdo_init->exec("SET NAMES 'utf8mb4'");

  // Veritabanı var mı diye kontrol et, yoksa oluştur
  $stmt = $pdo_init->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '" . DB_NAME . "'");
  if ($stmt->rowCount() == 0) {
    $pdo_init->exec("CREATE DATABASE `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
    echo "Veritabanı '" . DB_NAME . "' başarıyla oluşturuldu.\n";
  } else {
    echo "Veritabanı '" . DB_NAME . "' zaten mevcut.\n";
  }

  // Oluşturulan veya mevcut veritabanına bağlan
  $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  $pdo->exec("SET NAMES 'utf8mb4'");

  echo "Veritabanı bağlantısı başarılı.\n\n";

  // --- Migration Sistemi ---

  function createMigrationTable($pdo) {
    $sql = "
      CREATE TABLE IF NOT EXISTS `schema_migrations` (
        `version` VARCHAR(20) PRIMARY KEY,
        `applied_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `description` VARCHAR(255) DEFAULT NULL
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $pdo->exec($sql);
    echo "Migration tracking tablosu hazır.\n";
  }

  function getCurrentVersion($pdo) {
    try {
      $stmt = $pdo->query("SELECT version FROM schema_migrations ORDER BY applied_at DESC LIMIT 1");
      $result = $stmt->fetch(PDO::FETCH_ASSOC);
      return $result ? $result['version'] : '0.0.0';
    } catch (PDOException $e) {
      return '0.0.0'; // Migration tablosu henüz yok
    }
  }

  function markMigrationComplete($pdo, $version, $description) {
    $stmt = $pdo->prepare("INSERT INTO schema_migrations (version, description) VALUES (?, ?) ON DUPLICATE KEY UPDATE applied_at = CURRENT_TIMESTAMP");
    $stmt->execute([$version, $description]);
    echo "✓ Migration $version tamamlandı: $description\n";
  }

  function versionCompare($v1, $v2) {
    return version_compare($v1, $v2);
  }

  // Migration tablosunu oluştur
  createMigrationTable($pdo);
  $installed_version = getCurrentVersion($pdo);
  echo "Mevcut schema version: $installed_version\n\n";

  if ($fresh_install) {
    // --- Fresh Install: Tabloları Temizle ve Yeniden Oluştur ---

  // Yabancı anahtar kısıtlamalarını dikkate alarak tabloları doğru sırada sil
  echo "Mevcut tablolar temizleniyor...\n";
  $pdo->exec("DROP TABLE IF EXISTS `user_announcements`, `user_quests`, `quests`, `duels`, `friends`, `user_achievements`, `user_difficulty_stats`, `user_stats`, `leaderboard`, `users`, `achievements`, `announcements`;");
  echo "Eski tablolar başarıyla silindi.\n\n";

  echo "Yeni tablolar oluşturuluyor...\n";

  // `users` tablosu
  $sql_users = "
    CREATE TABLE `users` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `username` VARCHAR(50) NOT NULL UNIQUE,
      `password` VARCHAR(255) NOT NULL,
      `role` ENUM('user', 'admin') NOT NULL DEFAULT 'user',
      `avatar` VARCHAR(255) NOT NULL DEFAULT 'avatar1.svg',
      `last_login_date` DATE NULL DEFAULT NULL COMMENT 'Kullanıcının son giriş yaptığı tarih',
      `login_streak` INT(11) NOT NULL DEFAULT 0 COMMENT 'Kullanıcının ardışık giriş yapma serisi',
      `failed_login_attempts` INT NOT NULL DEFAULT 0,
      `last_login_attempt` TIMESTAMP NULL DEFAULT NULL,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
  $pdo->exec($sql_users);
  echo "Tablo 'users' (rate limiting ve günlük giriş sütunları ile) başarıyla oluşturuldu.\n";

  // `friends` tablosu
  $sql_friends = "
    CREATE TABLE `friends` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `user_one_id` INT NOT NULL,
      `user_two_id` INT NOT NULL,
      `status` ENUM('pending', 'accepted', 'declined', 'blocked') NOT NULL,
      `action_user_id` INT NOT NULL COMMENT 'Son aksiyonu yapan kullanıcının IDsi',
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      FOREIGN KEY (`user_one_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
      FOREIGN KEY (`user_two_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
      UNIQUE KEY `unique_friendship` (`user_one_id`, `user_two_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
  ";
  $pdo->exec($sql_friends);
  echo "Tablo 'friends' başarıyla oluşturuldu.\n";

  // `duels` tablosu
  $sql_duels = "
    CREATE TABLE `duels` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `challenger_id` INT NOT NULL,
      `opponent_id` INT NOT NULL,
      `category` VARCHAR(50) NOT NULL,
      `difficulty` ENUM('kolay', 'orta', 'zor') NOT NULL,
      `status` ENUM('pending', 'declined', 'active', 'challenger_completed', 'opponent_completed', 'completed', 'expired') NOT NULL DEFAULT 'pending',
      `questions` JSON NOT NULL,
      `challenger_answers` JSON DEFAULT NULL,
      `opponent_answers` JSON DEFAULT NULL,
      `challenger_score` INT DEFAULT 0,
      `opponent_score` INT DEFAULT 0,
      `winner_id` INT DEFAULT NULL,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      FOREIGN KEY (`challenger_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
      FOREIGN KEY (`opponent_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
      FOREIGN KEY (`winner_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
  ";
  $pdo->exec($sql_duels);
  echo "Tablo 'duels' başarıyla oluşturuldu.\n";

  // `quests` tablosu
  $sql_quests = "
    CREATE TABLE `quests` (
        `quest_key` VARCHAR(50) PRIMARY KEY,
        `name` VARCHAR(100) NOT NULL,
        `description_template` VARCHAR(255) NOT NULL COMMENT 'e.g., ''{goal} {target} sorusu çöz''',
        `type` ENUM('solve_category', 'solve_difficulty', 'consecutive_days', 'win_duels') NOT NULL,
        `target` VARCHAR(50) DEFAULT NULL COMMENT 'e.g., ''tarih'' or ''zor''',
        `default_goal` INT NOT NULL,
        `reward_points` INT NOT NULL,
        `reward_coins` INT NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
  ";
  $pdo->exec($sql_quests);
  echo "Tablo 'quests' başarıyla oluşturuldu.\n";

  // `user_quests` tablosu
  $sql_user_quests = "
    CREATE TABLE `user_quests` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `quest_key` VARCHAR(50) NOT NULL,
        `progress` INT NOT NULL DEFAULT 0,
        `goal` INT NOT NULL,
        `is_completed` BOOLEAN NOT NULL DEFAULT FALSE,
        `assigned_date` DATE NOT NULL,
        `completed_at` TIMESTAMP NULL DEFAULT NULL,
        UNIQUE KEY `user_quest_date` (`user_id`, `quest_key`, `assigned_date`),
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`quest_key`) REFERENCES `quests`(`quest_key`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
  ";
  $pdo->exec($sql_user_quests);
  echo "Tablo 'user_quests' başarıyla oluşturuldu.\n";

  // `leaderboard` tablosu (users tablosuna bağlı)
  $sql_leaderboard = "
    CREATE TABLE `leaderboard` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `user_id` INT NOT NULL,
      `score` INT NOT NULL DEFAULT 0,
      `coins` INT NOT NULL DEFAULT 100,
      `lifeline_fifty_fifty` INT NOT NULL DEFAULT 1,
      `lifeline_extra_time` INT NOT NULL DEFAULT 1,
      `lifeline_pass` INT NOT NULL DEFAULT 1,
      `last_updated` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
      UNIQUE KEY `user_id` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
  $pdo->exec($sql_leaderboard);
  echo "Tablo 'leaderboard' başarıyla oluşturuldu ve 'users' tablosuna bağlandı.\n";

  // `user_stats` tablosu (users tablosuna bağlı) - YENİ SÜTUNLAR EKLENDİ
  $sql_user_stats = "
    CREATE TABLE `user_stats` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `user_id` INT NOT NULL,
      `category` VARCHAR(50) NOT NULL,
      `difficulty` ENUM('kolay', 'orta', 'zor') NOT NULL,
      `total_questions` INT NOT NULL DEFAULT 1,
      `correct_answers` INT NOT NULL DEFAULT 0,
      `total_time_spent` INT NOT NULL DEFAULT 0,
      UNIQUE KEY `user_category_difficulty` (`user_id`, `category`, `difficulty`),
      FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
  $pdo->exec($sql_user_stats);
  echo "Tablo 'user_stats' (zorluk ve süre ile) başarıyla oluşturuldu.\n";

  // `achievements` tablosu (Başarım tanımları için)
  $sql_achievements = "
    CREATE TABLE `achievements` (
      `achievement_key` VARCHAR(50) PRIMARY KEY,
      `name` VARCHAR(100) NOT NULL,
      `description` TEXT NOT NULL,
      `icon` VARCHAR(50) NOT NULL,
      `color` VARCHAR(50) NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
  $pdo->exec($sql_achievements);
  echo "Tablo 'achievements' başarıyla oluşturuldu.\n";

  // `user_achievements` tablosu (users tablosuna bağlı)
  $sql_user_achievements = "
    CREATE TABLE `user_achievements` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `user_id` INT NOT NULL,
      `achievement_key` VARCHAR(50) NOT NULL,
      `achieved_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      UNIQUE KEY `user_achievement` (`user_id`, `achievement_key`),
      FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
      FOREIGN KEY (`achievement_key`) REFERENCES `achievements`(`achievement_key`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
  $pdo->exec($sql_user_achievements);
  echo "Tablo 'user_achievements' başarıyla oluşturuldu ve 'users' ve 'achievements' tablolarına bağlandı.\n";

  // `user_difficulty_stats` tablosu kaldırıldığı için oluşturma kodu silindi.

  // --- Başarım Verilerini Veritabanına Ekle ---
  echo "\nBaşarım verileri veritabanına ekleniyor...\n";
  $achievements_data = [
    ['ilk_adim', 'İlk Adım', 'İlk sorunu doğru cevapladın, tebrikler!', 'fa-shoe-prints', 'green'],
    ['hiz_tutkunu', 'Hız Tutkunu', 'Bir soruyu 5 saniyeden kısa sürede doğru cevapladın!', 'fa-bolt', 'blue'],
    ['seri_galibi_10', 'Seri Galibi', 'Üst üste 10 soruyu doğru cevapladın!', 'fa-trophy', 'yellow'],
    ['seri_galibi_25', 'Yenilmez', 'İnanılmaz! 25 soruyu art arda doğru bildin!', 'fa-crown', 'red'],
    ['merakli', 'Meraklı', 'Tüm kategorilerden en az bir soru cevapladın!', 'fa-compass', 'purple'],
    ['puan_avcisi_1000', 'Puan Avcısı', 'Toplamda 1000 puana ulaştın!', 'fa-star', 'yellow'],
    ['gece_kusu', 'Gece Kuşu', 'Gece 00:00 - 04:00 arası soru çözdün!', 'fa-moon', 'indigo'],
    ['zorlu_rakip', 'Zorlu Rakip', 'Zor seviyede 10 soruyu doğru cevapladın!', 'fa-user-secret', 'gray'],
    ['koleksiyoncu', 'Koleksiyoncu', '10 farklı başarım rozeti topladın!', 'fa-gem', 'pink'],
    ['uzman_tarih', 'Tarih Kurdu', 'Tarih kategorisinde 20 soruya doğru cevap verdin!', 'fa-history', 'blue'],
    ['kusursuz_tarih', 'Kusursuz Tarihçi', 'Tarih kategorisinde %100 başarıya ulaştın (min. 10 soru)!', 'fa-scroll', 'blue'],
    ['uzman_spor', 'Spor Gurusu', 'Spor kategorisinde 20 soruya doğru cevap verdin!', 'fa-futbol', 'green'],
    ['kusursuz_spor', 'Kusursuz Atlet', 'Spor kategorisinde %100 başarıya ulaştın (min. 10 soru)!', 'fa-running', 'green'],
    ['uzman_bilim', 'Bilim Kaşifi', 'Bilim kategorisinde 20 soruya doğru cevap verdin!', 'fa-atom', 'purple'],
    ['kusursuz_bilim', 'Kusursuz Bilgin', 'Bilim kategorisinde %100 başarıya ulaştın (min. 10 soru)!', 'fa-flask', 'purple'],
    ['uzman_sanat', 'Sanat Faresi', 'Sanat kategorisinde 20 soruya doğru cevap verdin!', 'fa-palette', 'yellow'],
    ['kusursuz_sanat', 'Kusursuz Sanatçı', 'Sanat kategorisinde %100 başarıya ulaştın (min. 10 soru)!', 'fa-paint-brush', 'yellow'],
    ['uzman_coğrafya', 'Dünya Gezgini', 'Coğrafya kategorisinde 20 soruya doğru cevap verdin!', 'fa-globe-americas', 'red'],
    ['kusursuz_coğrafya', 'Kusursuz Kaşif', 'Coğrafya kategorisinde %100 başarıya ulaştın (min. 10 soru)!', 'fa-map-marked-alt', 'red'],
    ['uzman_genel kültür', 'Her Şeyi Bilen', 'Genel Kültür kategorisinde 20 soruya doğru cevap verdin!', 'fa-brain', 'indigo'],
    ['kusursuz_genel kültür', 'Kusursuz Dahi', 'Genel Kültür kategorisinde %100 başarıya ulaştın (min. 10 soru)!', 'fa-lightbulb', 'indigo']
  ];

  $stmt_ach_insert = $pdo->prepare("INSERT INTO achievements (achievement_key, name, description, icon, color) VALUES (?, ?, ?, ?, ?)");
  foreach ($achievements_data as $ach) {
    $stmt_ach_insert->execute($ach);
  }
  echo count($achievements_data) . " adet başarım veritabanına eklendi.\n";

  // --- Görev Verilerini Veritabanına Ekle ---
  echo "\nGörev verileri veritabanına ekleniyor...\n";
  $quests_data = [
    ['solve_5_tarih', 'Tarihçi', '{goal} tarih sorusu çöz', 'solve_category', 'tarih', 5, 25, 25],
    ['solve_5_spor', 'Sporcu', '{goal} spor sorusu çöz', 'solve_category', 'spor', 5, 25, 25],
    ['solve_5_bilim', 'Kaşif', '{goal} bilim sorusu çöz', 'solve_category', 'bilim', 5, 25, 25],
    ['solve_3_zor', 'Gözü Pek', '{goal} zor soru çöz', 'solve_difficulty', 'zor', 3, 50, 50],
    ['solve_10_orta', 'İstikrarlı', '{goal} orta soru çöz', 'solve_difficulty', 'orta', 10, 30, 40]
  ];

  $stmt_quest_insert = $pdo->prepare("INSERT INTO quests (quest_key, name, description_template, type, target, default_goal, reward_points, reward_coins) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
  foreach ($quests_data as $quest) {
    $stmt_quest_insert->execute($quest);
  }
  echo count($quests_data) . " adet görev veritabanına eklendi.\n";

  // --- Varsayılan Admin Kullanıcısını Oluştur ---
  echo "\nVarsayılan admin kullanıcısı oluşturuluyor...\n";
  try {
    $admin_user = 'admin';
    $admin_pass = 'password'; // Geliştirme için basit bir şifre. Canlı ortamda değiştirin!
    $hashed_password = password_hash($admin_pass, PASSWORD_DEFAULT);

    // Admin kullanıcısını ekle
    $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'admin')");
    $stmt->execute([$admin_user, $hashed_password]);
    $admin_id = $pdo->lastInsertId();

    // Admin için leaderboard kaydı oluştur
    $stmt = $pdo->prepare("INSERT INTO leaderboard (user_id, score) VALUES (?, 0)");
    $stmt->execute([$admin_id]);

    echo "Kullanıcı: '$admin_user' (Şifre: '$admin_pass') başarıyla oluşturuldu.\n";
  } catch (PDOException $e) {
    if ($e->errorInfo[1] == 1062) { // 1062 = Duplicate entry
      echo "Admin kullanıcısı zaten mevcut.\n";
    } else {
      throw $e;
    }
  }

  // Duyurular Tablosu
  $pdo->exec("
    CREATE TABLE IF NOT EXISTS announcements (
      id INT AUTO_INCREMENT PRIMARY KEY,
      author_id INT NOT NULL,
      title VARCHAR(255) NOT NULL,
      content TEXT NOT NULL,
      target_group ENUM('all', 'users', 'admins') DEFAULT 'all',
      start_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      end_date DATETIME NOT NULL,
      is_active BOOLEAN DEFAULT TRUE,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
  ");

  // Kullanıcıların Okuduğu Duyurular Tablosu
  $pdo->exec("
    CREATE TABLE IF NOT EXISTS user_announcements (
      id INT AUTO_INCREMENT PRIMARY KEY,
      user_id INT NOT NULL,
      announcement_id INT NOT NULL,
      read_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
      FOREIGN KEY (announcement_id) REFERENCES announcements(id) ON DELETE CASCADE,
      UNIQUE KEY (user_id, announcement_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
  ");

  // Site Ayarları Tablosu
  $pdo->exec("
    CREATE TABLE IF NOT EXISTS settings (
      id INT AUTO_INCREMENT PRIMARY KEY,
      setting_key VARCHAR(100) NOT NULL UNIQUE,
      setting_value TEXT NOT NULL,
      description VARCHAR(255) DEFAULT NULL,
      updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
  ");

  // Varsayılan ayarları ekle
  $default_settings = [
    ['gemini_api_key', '', 'Google Gemini API anahtarı'],
    ['gemini_model', 'gemini-1.5-flash', 'Kullanılacak Gemini model adı'],
    ['site_name', 'AI Soru Cevap Yarışması', 'Site başlığı'],
    ['registration_enabled', '1', 'Yeni kullanıcı kaydı aktif mi (1: aktif, 0: pasif)']
  ];

  $stmt_setting = $pdo->prepare("INSERT IGNORE INTO settings (setting_key, setting_value, description) VALUES (?, ?, ?)");
  foreach ($default_settings as $setting) {
    $stmt_setting->execute($setting);
  }
  echo "Site ayarları tablosu ve varsayılan değerler oluşturuldu.\n";

  // API Anahtarları Tablosu
  $pdo->exec("
    CREATE TABLE IF NOT EXISTS api_keys (
      id INT AUTO_INCREMENT PRIMARY KEY,
      name VARCHAR(100) NOT NULL,
      api_key TEXT NOT NULL,
      is_active BOOLEAN DEFAULT TRUE,
      usage_count INT DEFAULT 0,
      last_used_at TIMESTAMP NULL DEFAULT NULL,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
  ");
  echo "API anahtarları tablosu oluşturuldu.\n";

    // Fresh install tamamlandı, son versiyonu işaretle
    markMigrationComplete($pdo, $current_version, "Fresh install completed");
    echo "\n✅ Fresh install başarıyla tamamlandı!\n";
  } else {
    // --- Safe Update: Sadece gerekli migration'ları çalıştır ---
    echo "Safe update modu - mevcut veriler korunacak.\n\n";

    // Hangi migration'ların çalıştırılması gerektiğini belirle
    $migrations_to_run = [];

    if (versionCompare($installed_version, '1.0.0') < 0) {
      $migrations_to_run[] = '1.0.0';
    }

    if (versionCompare($installed_version, '1.1.0') < 0) {
      $migrations_to_run[] = '1.1.0';
    }

    if (versionCompare($installed_version, '1.2.0') < 0) {
      $migrations_to_run[] = '1.2.0';
    }

    if (empty($migrations_to_run)) {
      echo "Tüm migration'lar güncel. Güncelleme gerekmiyor.\n";
    } else {
      echo "Çalıştırılacak migration'lar: " . implode(', ', $migrations_to_run) . "\n\n";

      foreach ($migrations_to_run as $version) {
        echo "Migration $version çalıştırılıyor...\n";

        switch ($version) {
          case '1.0.0':
            migration_1_0_0($pdo);
            break;
          case '1.1.0':
            migration_1_1_0($pdo);
            break;
          case '1.2.0':
            migration_1_2_0($pdo);
            break;
          default:
            echo "Bilinmeyen migration version: $version\n";
        }
      }
    }

    echo "\n✅ Güncelleme başarıyla tamamlandı!\n";
  }

} catch (PDOException $e) {
  die("❌ Kurulum/güncelleme sırasında hata: " . $e->getMessage());
}

// === MIGRATION FUNCTIONS ===

function migration_1_0_0($pdo) {
  echo "→ Migration 1.0.0: İlk tablo yapısı oluşturuluyor...\n";

  // users tablosu
  $pdo->exec("
    CREATE TABLE IF NOT EXISTS `users` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `username` VARCHAR(50) NOT NULL UNIQUE,
      `password` VARCHAR(255) NOT NULL,
      `role` ENUM('user', 'admin') NOT NULL DEFAULT 'user',
      `avatar` VARCHAR(255) NOT NULL DEFAULT 'avatar1.svg',
      `last_login_date` DATE NULL DEFAULT NULL COMMENT 'Kullanıcının son giriş yaptığı tarih',
      `login_streak` INT(11) NOT NULL DEFAULT 0 COMMENT 'Kullanıcının ardışık giriş yapma serisi',
      `failed_login_attempts` INT NOT NULL DEFAULT 0,
      `last_login_attempt` TIMESTAMP NULL DEFAULT NULL,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
  ");

  // friends tablosu
  $pdo->exec("
    CREATE TABLE IF NOT EXISTS `friends` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `user_one_id` INT NOT NULL,
      `user_two_id` INT NOT NULL,
      `status` ENUM('pending', 'accepted', 'declined', 'blocked') NOT NULL,
      `action_user_id` INT NOT NULL COMMENT 'Son aksiyonu yapan kullanıcının IDsi',
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      FOREIGN KEY (`user_one_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
      FOREIGN KEY (`user_two_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
      UNIQUE KEY `unique_friendship` (`user_one_id`, `user_two_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
  ");

  // duels tablosu
  $pdo->exec("
    CREATE TABLE IF NOT EXISTS `duels` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `challenger_id` INT NOT NULL,
      `opponent_id` INT NOT NULL,
      `category` VARCHAR(50) NOT NULL,
      `difficulty` ENUM('kolay', 'orta', 'zor') NOT NULL,
      `status` ENUM('pending', 'declined', 'active', 'challenger_completed', 'opponent_completed', 'completed', 'expired') NOT NULL DEFAULT 'pending',
      `questions` JSON NOT NULL,
      `challenger_answers` JSON DEFAULT NULL,
      `opponent_answers` JSON DEFAULT NULL,
      `challenger_score` INT DEFAULT 0,
      `opponent_score` INT DEFAULT 0,
      `winner_id` INT DEFAULT NULL,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      FOREIGN KEY (`challenger_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
      FOREIGN KEY (`opponent_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
      FOREIGN KEY (`winner_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
  ");

  // Diğer tablolar...
  createQuestTables($pdo);
  createLeaderboardTable($pdo);
  createUserStatsTable($pdo);
  createAchievementTables($pdo);
  createAnnouncementTables($pdo);

  // Varsayılan verileri ekle
  insertDefaultData($pdo);

  markMigrationComplete($pdo, '1.0.0', 'Initial database schema');
}

function createQuestTables($pdo) {
  $pdo->exec("
    CREATE TABLE IF NOT EXISTS `quests` (
        `quest_key` VARCHAR(50) PRIMARY KEY,
        `name` VARCHAR(100) NOT NULL,
        `description_template` VARCHAR(255) NOT NULL COMMENT 'e.g., ''{goal} {target} sorusu çöz''',
        `type` ENUM('solve_category', 'solve_difficulty', 'consecutive_days', 'win_duels') NOT NULL,
        `target` VARCHAR(50) DEFAULT NULL COMMENT 'e.g., ''tarih'' or ''zor''',
        `default_goal` INT NOT NULL,
        `reward_points` INT NOT NULL,
        `reward_coins` INT NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
  ");

  $pdo->exec("
    CREATE TABLE IF NOT EXISTS `user_quests` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `quest_key` VARCHAR(50) NOT NULL,
        `progress` INT NOT NULL DEFAULT 0,
        `goal` INT NOT NULL,
        `is_completed` BOOLEAN NOT NULL DEFAULT FALSE,
        `assigned_date` DATE NOT NULL,
        `completed_at` TIMESTAMP NULL DEFAULT NULL,
        UNIQUE KEY `user_quest_date` (`user_id`, `quest_key`, `assigned_date`),
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`quest_key`) REFERENCES `quests`(`quest_key`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
  ");
}

function createLeaderboardTable($pdo) {
  $pdo->exec("
    CREATE TABLE IF NOT EXISTS `leaderboard` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `user_id` INT NOT NULL,
      `score` INT NOT NULL DEFAULT 0,
      `coins` INT NOT NULL DEFAULT 100,
      `lifeline_fifty_fifty` INT NOT NULL DEFAULT 1,
      `lifeline_extra_time` INT NOT NULL DEFAULT 1,
      `lifeline_pass` INT NOT NULL DEFAULT 1,
      `last_updated` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
      UNIQUE KEY `user_id` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
  ");
}

function createUserStatsTable($pdo) {
  $pdo->exec("
    CREATE TABLE IF NOT EXISTS `user_stats` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `user_id` INT NOT NULL,
      `category` VARCHAR(50) NOT NULL,
      `difficulty` ENUM('kolay', 'orta', 'zor') NOT NULL,
      `total_questions` INT NOT NULL DEFAULT 1,
      `correct_answers` INT NOT NULL DEFAULT 0,
      `total_time_spent` INT NOT NULL DEFAULT 0,
      UNIQUE KEY `user_category_difficulty` (`user_id`, `category`, `difficulty`),
      FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
  ");
}

function createAchievementTables($pdo) {
  $pdo->exec("
    CREATE TABLE IF NOT EXISTS `achievements` (
      `achievement_key` VARCHAR(50) PRIMARY KEY,
      `name` VARCHAR(100) NOT NULL,
      `description` TEXT NOT NULL,
      `icon` VARCHAR(50) NOT NULL,
      `color` VARCHAR(50) NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
  ");

  $pdo->exec("
    CREATE TABLE IF NOT EXISTS `user_achievements` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `user_id` INT NOT NULL,
      `achievement_key` VARCHAR(50) NOT NULL,
      `achieved_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      UNIQUE KEY `user_achievement` (`user_id`, `achievement_key`),
      FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
      FOREIGN KEY (`achievement_key`) REFERENCES `achievements`(`achievement_key`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
  ");
}

function createAnnouncementTables($pdo) {
  $pdo->exec("
    CREATE TABLE IF NOT EXISTS announcements (
      id INT AUTO_INCREMENT PRIMARY KEY,
      author_id INT NOT NULL,
      title VARCHAR(255) NOT NULL,
      content TEXT NOT NULL,
      target_group ENUM('all', 'users', 'admins') DEFAULT 'all',
      start_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      end_date DATETIME NOT NULL,
      is_active BOOLEAN DEFAULT TRUE,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
  ");

  $pdo->exec("
    CREATE TABLE IF NOT EXISTS user_announcements (
      id INT AUTO_INCREMENT PRIMARY KEY,
      user_id INT NOT NULL,
      announcement_id INT NOT NULL,
      read_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
      FOREIGN KEY (announcement_id) REFERENCES announcements(id) ON DELETE CASCADE,
      UNIQUE KEY (user_id, announcement_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
  ");
}

function insertDefaultData($pdo) {
  // Başarım verilerini ekle
  $achievements_data = [
    ['ilk_adim', 'İlk Adım', 'İlk sorunu doğru cevapladın, tebrikler!', 'fa-shoe-prints', 'green'],
    ['hiz_tutkunu', 'Hız Tutkunu', 'Bir soruyu 5 saniyeden kısa sürede doğru cevapladın!', 'fa-bolt', 'blue'],
    ['seri_galibi_10', 'Seri Galibi', 'Üst üste 10 soruyu doğru cevapladın!', 'fa-trophy', 'yellow'],
    ['seri_galibi_25', 'Yenilmez', 'İnanılmaz! 25 soruyu art arda doğru bildin!', 'fa-crown', 'red'],
    ['merakli', 'Meraklı', 'Tüm kategorilerden en az bir soru cevapladın!', 'fa-compass', 'purple'],
    ['puan_avcisi_1000', 'Puan Avcısı', 'Toplamda 1000 puana ulaştın!', 'fa-star', 'yellow'],
    ['gece_kusu', 'Gece Kuşu', 'Gece 00:00 - 04:00 arası soru çözdün!', 'fa-moon', 'indigo'],
    ['zorlu_rakip', 'Zorlu Rakip', 'Zor seviyede 10 soruyu doğru cevapladın!', 'fa-user-secret', 'gray'],
    ['koleksiyoncu', 'Koleksiyoncu', '10 farklı başarım rozeti topladın!', 'fa-gem', 'pink'],
    ['uzman_tarih', 'Tarih Kurdu', 'Tarih kategorisinde 20 soruya doğru cevap verdin!', 'fa-history', 'blue'],
    ['kusursuz_tarih', 'Kusursuz Tarihçi', 'Tarih kategorisinde %100 başarıya ulaştın (min. 10 soru)!', 'fa-scroll', 'blue'],
    ['uzman_spor', 'Spor Gurusu', 'Spor kategorisinde 20 soruya doğru cevap verdin!', 'fa-futbol', 'green'],
    ['kusursuz_spor', 'Kusursuz Atlet', 'Spor kategorisinde %100 başarıya ulaştın (min. 10 soru)!', 'fa-running', 'green'],
    ['uzman_bilim', 'Bilim Kaşifi', 'Bilim kategorisinde 20 soruya doğru cevap verdin!', 'fa-atom', 'purple'],
    ['kusursuz_bilim', 'Kusursuz Bilgin', 'Bilim kategorisinde %100 başarıya ulaştın (min. 10 soru)!', 'fa-flask', 'purple'],
    ['uzman_sanat', 'Sanat Faresi', 'Sanat kategorisinde 20 soruya doğru cevap verdin!', 'fa-palette', 'yellow'],
    ['kusursuz_sanat', 'Kusursuz Sanatçı', 'Sanat kategorisinde %100 başarıya ulaştın (min. 10 soru)!', 'fa-paint-brush', 'yellow'],
    ['uzman_coğrafya', 'Dünya Gezgini', 'Coğrafya kategorisinde 20 soruya doğru cevap verdin!', 'fa-globe-americas', 'red'],
    ['kusursuz_coğrafya', 'Kusursuz Kaşif', 'Coğrafya kategorisinde %100 başarıya ulaştın (min. 10 soru)!', 'fa-map-marked-alt', 'red'],
    ['uzman_genel kültür', 'Her Şeyi Bilen', 'Genel Kültür kategorisinde 20 soruya doğru cevap verdin!', 'fa-brain', 'indigo'],
    ['kusursuz_genel kültür', 'Kusursuz Dahi', 'Genel Kültür kategorisinde %100 başarıya ulaştın (min. 10 soru)!', 'fa-lightbulb', 'indigo']
  ];

  $stmt_ach_insert = $pdo->prepare("INSERT IGNORE INTO achievements (achievement_key, name, description, icon, color) VALUES (?, ?, ?, ?, ?)");
  foreach ($achievements_data as $ach) {
    $stmt_ach_insert->execute($ach);
  }

  // Görev verilerini ekle
  $quests_data = [
    ['solve_5_tarih', 'Tarihçi', '{goal} tarih sorusu çöz', 'solve_category', 'tarih', 5, 25, 25],
    ['solve_5_spor', 'Sporcu', '{goal} spor sorusu çöz', 'solve_category', 'spor', 5, 25, 25],
    ['solve_5_bilim', 'Kaşif', '{goal} bilim sorusu çöz', 'solve_category', 'bilim', 5, 25, 25],
    ['solve_3_zor', 'Gözü Pek', '{goal} zor soru çöz', 'solve_difficulty', 'zor', 3, 50, 50],
    ['solve_10_orta', 'İstikrarlı', '{goal} orta soru çöz', 'solve_difficulty', 'orta', 10, 30, 40]
  ];

  $stmt_quest_insert = $pdo->prepare("INSERT IGNORE INTO quests (quest_key, name, description_template, type, target, default_goal, reward_points, reward_coins) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
  foreach ($quests_data as $quest) {
    $stmt_quest_insert->execute($quest);
  }

  // Varsayılan admin kullanıcısını oluştur
  try {
    $admin_user = 'admin';
    $admin_pass = 'password';
    $hashed_password = password_hash($admin_pass, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("INSERT IGNORE INTO users (username, password, role) VALUES (?, ?, 'admin')");
    $stmt->execute([$admin_user, $hashed_password]);

    if ($pdo->lastInsertId()) {
      $admin_id = $pdo->lastInsertId();
      $stmt = $pdo->prepare("INSERT IGNORE INTO leaderboard (user_id, score) VALUES (?, 0)");
      $stmt->execute([$admin_id]);
      echo "→ Admin kullanıcısı oluşturuldu: '$admin_user' (Şifre: '$admin_pass')\n";
    }
  } catch (PDOException $e) {
    // Admin zaten mevcut, sorun değil
  }
}

function migration_1_1_0($pdo) {
  echo "→ Migration 1.1.0: Site ayarları tablosu ekleniyor...\n";

  // Site ayarları tablosu
  $pdo->exec("
    CREATE TABLE IF NOT EXISTS settings (
      id INT AUTO_INCREMENT PRIMARY KEY,
      setting_key VARCHAR(100) NOT NULL UNIQUE,
      setting_value TEXT NOT NULL,
      description VARCHAR(255) DEFAULT NULL,
      updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
  ");

  // Varsayılan ayarları ekle
  $default_settings = [
    ['gemini_api_key', '', 'Google Gemini API anahtarı'],
    ['gemini_model', 'gemini-1.5-flash', 'Kullanılacak Gemini model adı'],
    ['site_name', 'AI Soru Cevap Yarışması', 'Site başlığı'],
    ['registration_enabled', '1', 'Yeni kullanıcı kaydı aktif mi (1: aktif, 0: pasif)']
  ];

  $stmt_setting = $pdo->prepare("INSERT IGNORE INTO settings (setting_key, setting_value, description) VALUES (?, ?, ?)");
  foreach ($default_settings as $setting) {
    $stmt_setting->execute($setting);
  }

  markMigrationComplete($pdo, '1.1.0', 'Settings table and default values added');
}

function migration_1_2_0($pdo) {
  echo "→ Migration 1.2.0: API anahtarları tablosu ekleniyor...\n";

  // API anahtarları tablosu
  $pdo->exec("
    CREATE TABLE IF NOT EXISTS api_keys (
      id INT AUTO_INCREMENT PRIMARY KEY,
      name VARCHAR(100) NOT NULL,
      api_key TEXT NOT NULL,
      is_active BOOLEAN DEFAULT TRUE,
      usage_count INT DEFAULT 0,
      last_used_at TIMESTAMP NULL DEFAULT NULL,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
  ");

  markMigrationComplete($pdo, '1.2.0', 'API keys table added for multiple key support');
}
