<?php

require_once 'config.php';

header('Content-Type: text/plain; charset=utf-8');

// Installation mode: fresh=1 for complete reinstall, default for safe update
$fresh_install = isset($_GET['fresh']) && $_GET['fresh'] == '1';
$current_version = '1.14.0'; // Current schema version

echo "=== AI Bilgi Yarismasi Veritabani Kurulum/Guncelleme ===\n";
echo "Mod: " . ($fresh_install ? "Fresh Install (Tum veriler silinecek!)" : "Safe Update (Mevcut veriler korunacak)") . "\n";
echo "Hedef Version: $current_version\n\n";

if ($fresh_install) {
    echo "!  UYARI: Fresh install modu tum mevcut verileri silecek!\n";
    echo "Production ortaminda kullanmayin. Devam etmek icin 3 saniye bekleniyor...\n\n";
    sleep(3);
}

try {
  // Once veritabani olmadan MySQL sunucusuna baglan
  $pdo_init = new PDO("mysql:host=" . DB_HOST, DB_USER, DB_PASS);
  $pdo_init->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  $pdo_init->exec("SET NAMES 'utf8mb4'");

  // Veritabani var mi diye kontrol et, yoksa olustur
  $stmt = $pdo_init->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '" . DB_NAME . "'");
  if ($stmt->rowCount() == 0) {
    $pdo_init->exec("CREATE DATABASE `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
    echo "Veritabani '" . DB_NAME . "' basariyla olusturuldu.\n";
  } else {
    echo "Veritabani '" . DB_NAME . "' zaten mevcut.\n";
  }

  // Olusturulan veya mevcut veritabanina baglan
  $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  $pdo->exec("SET NAMES 'utf8mb4'");

  echo "Veritabani baglantisi basarili.\n\n";

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
    echo "Migration tracking tablosu hazir.\n";
  }

  function getCurrentVersion($pdo) {
    try {
      $stmt = $pdo->query("SELECT version FROM schema_migrations ORDER BY applied_at DESC LIMIT 1");
      $result = $stmt->fetch(PDO::FETCH_ASSOC);
      return $result ? $result['version'] : '0.0.0';
    } catch (PDOException $e) {
      return '0.0.0'; // Migration tablosu henuz yok
    }
  }

  function markMigrationComplete($pdo, $version, $description) {
    $stmt = $pdo->prepare("INSERT INTO schema_migrations (version, description) VALUES (?, ?) ON DUPLICATE KEY UPDATE applied_at = CURRENT_TIMESTAMP");
    $stmt->execute([$version, $description]);
    echo "+ Migration $version tamamlandi: $description\n";
  }

  function versionCompare($v1, $v2) {
    return version_compare($v1, $v2);
  }

  // Migration tablosunu olustur
  createMigrationTable($pdo);
  $installed_version = getCurrentVersion($pdo);
  echo "Mevcut schema version: $installed_version\n\n";

  if ($fresh_install) {
    // --- Fresh Install: Tablolari Temizle ve Yeniden Olustur ---

  // Yabanci anahtar kisitlamalarini dikkate alarak tablolari dogru sirada sil
  echo "Mevcut tablolar temizleniyor...\n";
  $pdo->exec("DROP TABLE IF EXISTS `user_announcements`, `user_quests`, `quests`, `duels`, `friends`, `user_achievements`, `user_difficulty_stats`, `user_stats`, `leaderboard`, `users`, `achievements`, `announcements`;");
  echo "Eski tablolar basariyla silindi.\n\n";

  echo "Yeni tablolar olusturuluyor...\n";

  // `users` tablosu
  $sql_users = "
    CREATE TABLE `users` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `username` VARCHAR(50) NOT NULL UNIQUE,
      `password` VARCHAR(255) NOT NULL,
      `role` ENUM('user', 'admin') NOT NULL DEFAULT 'user',
      `avatar` VARCHAR(255) NOT NULL DEFAULT 'avatar1.svg',
      `last_login_date` DATE NULL DEFAULT NULL COMMENT 'Kullanicinin son giris yaptigi tarih',
      `login_streak` INT(11) NOT NULL DEFAULT 0 COMMENT 'Kullanicinin ardisik giris yapma serisi',
      `failed_login_attempts` INT NOT NULL DEFAULT 0,
      `last_login_attempt` TIMESTAMP NULL DEFAULT NULL,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
  $pdo->exec($sql_users);
  echo "Tablo 'users' (rate limiting ve gunluk giris sutunlari ile) basariyla olusturuldu.\n";

  // `friends` tablosu
  $sql_friends = "
    CREATE TABLE `friends` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `user_one_id` INT NOT NULL,
      `user_two_id` INT NOT NULL,
      `status` ENUM('pending', 'accepted', 'declined', 'blocked') NOT NULL,
      `action_user_id` INT NOT NULL COMMENT 'Son aksiyonu yapan kullanicinin IDsi',
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      FOREIGN KEY (`user_one_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
      FOREIGN KEY (`user_two_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
      UNIQUE KEY `unique_friendship` (`user_one_id`, `user_two_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
  ";
  $pdo->exec($sql_friends);
  echo "Tablo 'friends' basariyla olusturuldu.\n";

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
  echo "Tablo 'duels' basariyla olusturuldu.\n";

  // `quests` tablosu
  $sql_quests = "
    CREATE TABLE `quests` (
        `quest_key` VARCHAR(50) PRIMARY KEY,
        `name` VARCHAR(100) NOT NULL,
        `description_template` VARCHAR(255) NOT NULL COMMENT 'e.g., ''{goal} {target} sorusu coz''',
        `type` ENUM('solve_category', 'solve_difficulty', 'consecutive_days', 'win_duels') NOT NULL,
        `target` VARCHAR(50) DEFAULT NULL COMMENT 'e.g., ''tarih'' or ''zor''',
        `default_goal` INT NOT NULL,
        `reward_points` INT NOT NULL,
        `reward_coins` INT NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
  ";
  $pdo->exec($sql_quests);
  echo "Tablo 'quests' basariyla olusturuldu.\n";

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
  echo "Tablo 'user_quests' basariyla olusturuldu.\n";

  // `leaderboard` tablosu (users tablosuna bagli)
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
  echo "Tablo 'leaderboard' basariyla olusturuldu ve 'users' tablosuna baglandi.\n";

  // `user_stats` tablosu (users tablosuna bagli) - YENI SUTUNLAR EKLENDI
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
  echo "Tablo 'user_stats' (zorluk ve sure ile) basariyla olusturuldu.\n";

  // `achievements` tablosu (Basarim tanimlari icin)
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
  echo "Tablo 'achievements' basariyla olusturuldu.\n";

  // `user_achievements` tablosu (users tablosuna bagli)
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
  echo "Tablo 'user_achievements' basariyla olusturuldu ve 'users' ve 'achievements' tablolarina baglandi.\n";

  // `user_difficulty_stats` tablosu kaldirildigi icin olusturma kodu silindi.

  // --- Basarim Verilerini Veritabanina Ekle ---
  echo "\nBasarim verileri veritabanina ekleniyor...\n";
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
    ['uzman_cografya', 'Dünya Gezgini', 'Coğrafya kategorisinde 20 soruya doğru cevap verdin!', 'fa-globe-americas', 'red'],
    ['kusursuz_cografya', 'Kusursuz Kaşif', 'Coğrafya kategorisinde %100 başarıya ulaştın (min. 10 soru)!', 'fa-map-marked-alt', 'red'],
    ['uzman_genel kultur', 'Her Şeyi Bilen', 'Genel Kültür kategorisinde 20 soruya doğru cevap verdin!', 'fa-brain', 'indigo'],
    ['kusursuz_genel kultur', 'Kusursuz Dahi', 'Genel Kültür kategorisinde %100 başarıya ulaştın (min. 10 soru)!', 'fa-lightbulb', 'indigo']
  ];

  $stmt_ach_insert = $pdo->prepare("INSERT INTO achievements (achievement_key, name, description, icon, color) VALUES (?, ?, ?, ?, ?)");
  foreach ($achievements_data as $ach) {
    $stmt_ach_insert->execute($ach);
  }
  echo count($achievements_data) . " adet basarim veritabanina eklendi.\n";

  // --- Gorev Verilerini Veritabanina Ekle ---
  echo "\nGorev verileri veritabanina ekleniyor...\n";
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
  echo count($quests_data) . " adet gorev veritabanina eklendi.\n";

  // --- Varsayilan Admin Kullanicisini Olustur ---
  echo "\nVarsayilan admin kullanicisi olusturuluyor...\n";
  try {
    $admin_user = 'admin';
    $admin_pass = 'password'; // Gelistirme icin basit bir sifre. Canli ortamda degistirin!
    $hashed_password = password_hash($admin_pass, PASSWORD_DEFAULT);

    // Admin kullanicisini ekle
    $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'admin')");
    $stmt->execute([$admin_user, $hashed_password]);
    $admin_id = $pdo->lastInsertId();

    // Admin icin leaderboard kaydi olustur
    $stmt = $pdo->prepare("INSERT INTO leaderboard (user_id, score) VALUES (?, 0)");
    $stmt->execute([$admin_id]);

    echo "Kullanici: '$admin_user' (Sifre: '$admin_pass') basariyla olusturuldu.\n";
  } catch (PDOException $e) {
    if ($e->errorInfo[1] == 1062) { // 1062 = Duplicate entry
      echo "Admin kullanicisi zaten mevcut.\n";
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

  // Kullanicilarin Okudugu Duyurular Tablosu
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

  // Site Ayarlari Tablosu
  $pdo->exec("
    CREATE TABLE IF NOT EXISTS settings (
      id INT AUTO_INCREMENT PRIMARY KEY,
      setting_key VARCHAR(100) NOT NULL UNIQUE,
      setting_value TEXT NOT NULL,
      description VARCHAR(255) DEFAULT NULL,
      updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
  ");

  // Varsayilan ayarlari ekle
  $default_settings = [
    ['gemini_model', 'gemini-1.5-flash', 'Kullanilacak Gemini model adi'],
    ['site_name', 'AI Soru Cevap Yarismasi', 'Site basligi'],
    ['registration_enabled', '1', 'Yeni kullanici kaydi aktif mi (1: aktif, 0: pasif)'],
    ['timezone_setting', 'Europe/Istanbul', 'Varsayilan zaman dilimi ayari']
  ];

  $stmt_setting = $pdo->prepare("INSERT IGNORE INTO settings (setting_key, setting_value, description) VALUES (?, ?, ?)");
  foreach ($default_settings as $setting) {
    $stmt_setting->execute($setting);
  }
  echo "Site ayarlari tablosu ve varsayilan degerler olusturuldu.\n";

  // API Anahtarlari Tablosu
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
  echo "API anahtarlari tablosu olusturuldu.\n";

  // Performance Indexleri Ekle
  echo "Performance indexleri ekleniyor...\n";

  // Leaderboard performance indexleri
  $pdo->exec("CREATE INDEX IF NOT EXISTS idx_leaderboard_score_updated ON leaderboard (score DESC, last_updated ASC)");

  // Friends performance indexleri
  $pdo->exec("CREATE INDEX IF NOT EXISTS idx_friends_status ON friends (status)");
  $pdo->exec("CREATE INDEX IF NOT EXISTS idx_friends_action_user ON friends (action_user_id)");
  $pdo->exec("CREATE INDEX IF NOT EXISTS idx_friends_created ON friends (created_at)");

  // Duels performance indexleri
  $pdo->exec("CREATE INDEX IF NOT EXISTS idx_duels_status ON duels (status)");
  $pdo->exec("CREATE INDEX IF NOT EXISTS idx_duels_category_difficulty ON duels (category, difficulty)");
  $pdo->exec("CREATE INDEX IF NOT EXISTS idx_duels_created ON duels (created_at)");

  // User quests performance indexleri
  $pdo->exec("CREATE INDEX IF NOT EXISTS idx_user_quests_assigned_date ON user_quests (assigned_date)");
  $pdo->exec("CREATE INDEX IF NOT EXISTS idx_user_quests_completed ON user_quests (is_completed)");

  // Announcements performance indexleri
  $pdo->exec("CREATE INDEX IF NOT EXISTS idx_announcements_active_dates ON announcements (is_active, start_date, end_date)");
  $pdo->exec("CREATE INDEX IF NOT EXISTS idx_announcements_target_group ON announcements (target_group)");

  // API keys performance indexleri
  $pdo->exec("CREATE INDEX IF NOT EXISTS idx_api_keys_active ON api_keys (is_active)");
  $pdo->exec("CREATE INDEX IF NOT EXISTS idx_api_keys_usage ON api_keys (usage_count ASC)");

  // Users performance indexleri
  $pdo->exec("CREATE INDEX IF NOT EXISTS idx_users_role ON users (role)");
  $pdo->exec("CREATE INDEX IF NOT EXISTS idx_users_last_login ON users (last_login_date)");
  $pdo->exec("CREATE INDEX IF NOT EXISTS idx_users_created ON users (created_at)");

  echo "Performance indexleri basariyla eklendi.\n";

    // Fresh install tamamlandi, son versiyonu isaretle
    markMigrationComplete($pdo, $current_version, "Fresh install completed");
    echo "\n+ Fresh install basariyla tamamlandi!\n";
  } else {
    // --- Safe Update: Sadece gerekli migration'lari calistir ---
    echo "Safe update modu - mevcut veriler korunacak.\n\n";

    // Hangi migration'larin calistirilmasi gerektigini belirle
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

    if (versionCompare($installed_version, '1.3.0') < 0) {
      $migrations_to_run[] = '1.3.0';
    }

    if (versionCompare($installed_version, '1.4.0') < 0) {
      $migrations_to_run[] = '1.4.0';
    }

    if (versionCompare($installed_version, '1.5.0') < 0) {
      $migrations_to_run[] = '1.5.0';
    }

    if (versionCompare($installed_version, '1.6.0') < 0) {
      $migrations_to_run[] = '1.6.0';
    }

    if (versionCompare($installed_version, '1.7.0') < 0) {
      $migrations_to_run[] = '1.7.0';
    }

    if (versionCompare($installed_version, '1.8.0') < 0) {
      $migrations_to_run[] = '1.8.0';
    }

    if (versionCompare($installed_version, '1.9.0') < 0) {
      $migrations_to_run[] = '1.9.0';
    }

    if (versionCompare($installed_version, '1.10.0') < 0) {
      $migrations_to_run[] = '1.10.0';
    }

    if (versionCompare($installed_version, '1.11.0') < 0) {
      $migrations_to_run[] = '1.11.0';
    }

    if (versionCompare($installed_version, '1.12.0') < 0) {
      $migrations_to_run[] = '1.12.0';
    }

    if (empty($migrations_to_run)) {
      echo "Tum migration'lar guncel. Guncelleme gerekmiyor.\n";
    } else {
      echo "Calistirilacak migration'lar: " . implode(', ', $migrations_to_run) . "\n\n";

      foreach ($migrations_to_run as $version) {
        echo "Migration $version calistiriliyor...\n";

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
          case '1.3.0':
            migration_1_3_0($pdo);
            break;
          case '1.4.0':
            migration_1_4_0($pdo);
            break;
          case '1.5.0':
            migration_1_5_0($pdo);
            break;
          case '1.6.0':
            migration_1_6_0($pdo);
            break;
          case '1.7.0':
            migration_1_7_0($pdo);
            break;
          case '1.8.0':
            migration_1_8_0($pdo);
            break;
          case '1.9.0':
            migration_1_9_0($pdo);
            break;
          case '1.10.0':
            migration_1_10_0($pdo);
            break;
          case '1.11.0':
            migration_1_11_0($pdo);
            break;
          case '1.12.0':
            migration_1_12_0($pdo);
            break;
          case '1.13.0':
            migration_1_13_0($pdo);
            break;
          case '1.14.0':
            migration_1_14_0($pdo);
            break;
          default:
            echo "Bilinmeyen migration version: $version\n";
        }
      }
    }

    echo "\n+ Guncelleme basariyla tamamlandi!\n";
  }

} catch (PDOException $e) {
  die("! Kurulum/guncelleme sirasinda hata: " . $e->getMessage());
}

// === MIGRATION FUNCTIONS ===

function migration_1_0_0($pdo) {
  echo "->  Migration 1.0.0: Ilk tablo yapisi olusturuluyor...\n";

  // users tablosu
  $pdo->exec("
    CREATE TABLE IF NOT EXISTS `users` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `username` VARCHAR(50) NOT NULL UNIQUE,
      `password` VARCHAR(255) NOT NULL,
      `role` ENUM('user', 'admin') NOT NULL DEFAULT 'user',
      `avatar` VARCHAR(255) NOT NULL DEFAULT 'avatar1.svg',
      `last_login_date` DATE NULL DEFAULT NULL COMMENT 'Kullanicinin son giris yaptigi tarih',
      `login_streak` INT(11) NOT NULL DEFAULT 0 COMMENT 'Kullanicinin ardisik giris yapma serisi',
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
      `action_user_id` INT NOT NULL COMMENT 'Son aksiyonu yapan kullanicinin IDsi',
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

  // Diger tablolar...
  createQuestTables($pdo);
  createLeaderboardTable($pdo);
  createUserStatsTable($pdo);
  createAchievementTables($pdo);
  createAnnouncementTables($pdo);

  // Varsayilan verileri ekle
  insertDefaultData($pdo);

  markMigrationComplete($pdo, '1.0.0', 'Initial database schema');
}

function createQuestTables($pdo) {
  $pdo->exec("
    CREATE TABLE IF NOT EXISTS `quests` (
        `quest_key` VARCHAR(50) PRIMARY KEY,
        `name` VARCHAR(100) NOT NULL,
        `description_template` VARCHAR(255) NOT NULL COMMENT 'e.g., ''{goal} {target} sorusu coz''',
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
  // Basarim verilerini ekle
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
    ['uzman_cografya', 'Dünya Gezgini', 'Coğrafya kategorisinde 20 soruya doğru cevap verdin!', 'fa-globe-americas', 'red'],
    ['kusursuz_cografya', 'Kusursuz Kaşif', 'Coğrafya kategorisinde %100 başarıya ulaştın (min. 10 soru)!', 'fa-map-marked-alt', 'red'],
    ['uzman_genel kultur', 'Her Şeyi Bilen', 'Genel Kültür kategorisinde 20 soruya doğru cevap verdin!', 'fa-brain', 'indigo'],
    ['kusursuz_genel kultur', 'Kusursuz Dahi', 'Genel Kültür kategorisinde %100 başarıya ulaştın (min. 10 soru)!', 'fa-lightbulb', 'indigo']
  ];

  $stmt_ach_insert = $pdo->prepare("INSERT IGNORE INTO achievements (achievement_key, name, description, icon, color) VALUES (?, ?, ?, ?, ?)");
  foreach ($achievements_data as $ach) {
    $stmt_ach_insert->execute($ach);
  }

  // Gorev verilerini ekle
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

  // Varsayilan admin kullanicisini olustur
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
      echo "->  Admin kullanicisi olusturuldu: '$admin_user' (Sifre: '$admin_pass')\n";
    }
  } catch (PDOException $e) {
    // Admin zaten mevcut, sorun degil
  }
}

function migration_1_1_0($pdo) {
  echo "->  Migration 1.1.0: Site ayarlari tablosu ekleniyor...\n";

  // Site ayarlari tablosu
  $pdo->exec("
    CREATE TABLE IF NOT EXISTS settings (
      id INT AUTO_INCREMENT PRIMARY KEY,
      setting_key VARCHAR(100) NOT NULL UNIQUE,
      setting_value TEXT NOT NULL,
      description VARCHAR(255) DEFAULT NULL,
      updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
  ");

  // Varsayilan ayarlari ekle
  $default_settings = [
    ['gemini_model', 'gemini-1.5-flash', 'Kullanilacak Gemini model adi'],
    ['site_name', 'AI Soru Cevap Yarismasi', 'Site basligi'],
    ['registration_enabled', '1', 'Yeni kullanici kaydi aktif mi (1: aktif, 0: pasif)'],
    ['timezone_setting', 'Europe/Istanbul', 'Varsayilan zaman dilimi ayari']
  ];

  $stmt_setting = $pdo->prepare("INSERT IGNORE INTO settings (setting_key, setting_value, description) VALUES (?, ?, ?)");
  foreach ($default_settings as $setting) {
    $stmt_setting->execute($setting);
  }

  markMigrationComplete($pdo, '1.1.0', 'Settings table and default values added');
}

function migration_1_2_0($pdo) {
  echo "->  Migration 1.2.0: API anahtarlari tablosu ekleniyor...\n";

  // API anahtarlari tablosu
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

function migration_1_3_0($pdo) {
  echo "->  Migration 1.3.0: Performance indexleri ekleniyor...\n";

  // Leaderboard performance indexleri
  echo "  Leaderboard indexleri...\n";
  $pdo->exec("CREATE INDEX IF NOT EXISTS idx_leaderboard_score_updated ON leaderboard (score DESC, last_updated ASC)");

  // Friends performance indexleri
  echo "  Friends indexleri...\n";
  $pdo->exec("CREATE INDEX IF NOT EXISTS idx_friends_status ON friends (status)");
  $pdo->exec("CREATE INDEX IF NOT EXISTS idx_friends_action_user ON friends (action_user_id)");
  $pdo->exec("CREATE INDEX IF NOT EXISTS idx_friends_created ON friends (created_at)");

  // Duels performance indexleri
  echo "  Duels indexleri...\n";
  $pdo->exec("CREATE INDEX IF NOT EXISTS idx_duels_status ON duels (status)");
  $pdo->exec("CREATE INDEX IF NOT EXISTS idx_duels_category_difficulty ON duels (category, difficulty)");
  $pdo->exec("CREATE INDEX IF NOT EXISTS idx_duels_created ON duels (created_at)");

  // User quests performance indexleri
  echo "  User quests indexleri...\n";
  $pdo->exec("CREATE INDEX IF NOT EXISTS idx_user_quests_assigned_date ON user_quests (assigned_date)");
  $pdo->exec("CREATE INDEX IF NOT EXISTS idx_user_quests_completed ON user_quests (is_completed)");

  // Announcements performance indexleri
  echo "  Announcements indexleri...\n";
  $pdo->exec("CREATE INDEX IF NOT EXISTS idx_announcements_active_dates ON announcements (is_active, start_date, end_date)");
  $pdo->exec("CREATE INDEX IF NOT EXISTS idx_announcements_target_group ON announcements (target_group)");

  // API keys performance indexleri
  echo "  API keys indexleri...\n";
  $pdo->exec("CREATE INDEX IF NOT EXISTS idx_api_keys_active ON api_keys (is_active)");
  $pdo->exec("CREATE INDEX IF NOT EXISTS idx_api_keys_usage ON api_keys (usage_count ASC)");

  // Users performance indexleri
  echo "  Users indexleri...\n";
  $pdo->exec("CREATE INDEX IF NOT EXISTS idx_users_role ON users (role)");
  $pdo->exec("CREATE INDEX IF NOT EXISTS idx_users_last_login ON users (last_login_date)");
  $pdo->exec("CREATE INDEX IF NOT EXISTS idx_users_created ON users (created_at)");

  markMigrationComplete($pdo, '1.3.0', 'Performance indexes added for query optimization');
}

function migration_1_4_0($pdo) {
  echo "->  Migration 1.4.0: Database yapisi duzeltiliyor - coins ve lifelines users tablosuna tasiniyor...\n";

  try {
    $pdo->beginTransaction();

    // 1. users tablosuna yeni sutunlari ekle
    echo "  Users tablosuna coins ve lifeline sutunlari ekleniyor...\n";
    $pdo->exec("ALTER TABLE users
                ADD COLUMN coins INT NOT NULL DEFAULT 100,
                ADD COLUMN lifeline_fifty_fifty INT NOT NULL DEFAULT 1,
                ADD COLUMN lifeline_extra_time INT NOT NULL DEFAULT 1,
                ADD COLUMN lifeline_pass INT NOT NULL DEFAULT 1");

    // 2. Mevcut verileri leaderboard'dan users'a kopyala
    echo "  Mevcut veriler leaderboard'dan users tablosuna kopyalaniyor...\n";
    $pdo->exec("UPDATE users u
                INNER JOIN leaderboard l ON u.id = l.user_id
                SET u.coins = l.coins,
                    u.lifeline_fifty_fifty = l.lifeline_fifty_fifty,
                    u.lifeline_extra_time = l.lifeline_extra_time,
                    u.lifeline_pass = l.lifeline_pass");

    // 3. Yeni kullanicilar icin leaderboard'da eksik kayitlari olustur (guvenlik icin)
    echo "  Eksik leaderboard kayitlari kontrol ediliyor...\n";
    $pdo->exec("INSERT IGNORE INTO leaderboard (user_id, score, coins, lifeline_fifty_fifty, lifeline_extra_time, lifeline_pass)
                SELECT id, 0, coins, lifeline_fifty_fifty, lifeline_extra_time, lifeline_pass
                FROM users
                WHERE id NOT IN (SELECT user_id FROM leaderboard)");

    // 4. leaderboard tablosundan coins ve lifeline sutunlarini kaldir
    echo "  Leaderboard tablosundan gereksiz sutunlar kaldiriliyor...\n";
    $pdo->exec("ALTER TABLE leaderboard
                DROP COLUMN coins,
                DROP COLUMN lifeline_fifty_fifty,
                DROP COLUMN lifeline_extra_time,
                DROP COLUMN lifeline_pass");

    // 5. purchase_logs tablosu varsa olustur (ShopController'da kullaniliyor)
    echo "  Purchase logs tablosu kontrol ediliyor...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS purchase_logs (
                  id INT AUTO_INCREMENT PRIMARY KEY,
                  user_id INT NOT NULL,
                  item_type VARCHAR(50) NOT NULL,
                  item_price INT NOT NULL,
                  purchase_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                  INDEX idx_purchase_user_date (user_id, purchase_date)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // 6. shop_settings tablosu varsa olustur (admin shop icin)
    echo "  Shop settings tablosu kontrol ediliyor...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS shop_settings (
                  id INT AUTO_INCREMENT PRIMARY KEY,
                  setting_key VARCHAR(100) NOT NULL UNIQUE,
                  setting_value TEXT NOT NULL,
                  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Varsayilan shop fiyatlarini ekle
    $default_shop_settings = [
      ['price_fifty_fifty', '95'],
      ['price_extra_time', '50'],
      ['price_pass', '50']
    ];

    $stmt = $pdo->prepare("INSERT IGNORE INTO shop_settings (setting_key, setting_value) VALUES (?, ?)");
    foreach ($default_shop_settings as $setting) {
      $stmt->execute($setting);
    }

    $pdo->commit();
    echo "  + Veritabani yapisi basariyla duzeltildi!\n";

    markMigrationComplete($pdo, '1.4.0', 'Fixed database structure: moved coins and lifelines to users table');

  } catch (Exception $e) {
    $pdo->rollBack();
    throw new Exception("Migration 1.4.0 basarisiz: " . $e->getMessage());
  }
}

function migration_1_5_0($pdo) {
  echo "->  Migration 1.5.0: Categories tablosu ekleniyor - soru kategorileri veritabanina tasiniyor...\n";

  try {
    $pdo->beginTransaction();

    // 1. categories tablosu olustur
    echo "  Categories tablosu olusturuluyor...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS categories (
                  id INT AUTO_INCREMENT PRIMARY KEY,
                  category_key VARCHAR(50) NOT NULL UNIQUE,
                  category_name VARCHAR(100) NOT NULL,
                  is_active TINYINT(1) DEFAULT 1,
                  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                  INDEX idx_category_active (is_active),
                  INDEX idx_category_key (category_key)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // 2. Varsayilan kategorileri ekle
    echo "  Varsayilan kategoriler ekleniyor...\n";
    $categories = [
        'genel_kultur' => 'Genel Kultur',
        'tarih' => 'Tarih',
        'spor' => 'Spor',
        'bilim' => 'Bilim',
        'sanat' => 'Sanat',
        'cografya' => 'Cografya',
        'teknoloji' => 'Teknoloji',
        'matematik' => 'Matematik',
        'edebiyat' => 'Edebiyat',
        'muzik' => 'Muzik'
    ];

    $stmt = $pdo->prepare('INSERT IGNORE INTO categories (category_key, category_name) VALUES (?, ?)');
    foreach($categories as $key => $name) {
        $stmt->execute([$key, $name]);
    }

    $pdo->commit();
    echo "  + Categories sistemi basariyla kuruldu!\n";

    markMigrationComplete($pdo, '1.5.0', 'Added categories table for dynamic question categories');

  } catch (Exception $e) {
    $pdo->rollBack();
    throw new Exception("Migration 1.5.0 basarisiz: " . $e->getMessage());
  }
}

function migration_1_6_0($pdo) {
  echo "->  Migration 1.6.0: Categories tablosuna icon ve color sutunlari ekleniyor...\n";

  try {
    $pdo->beginTransaction();

    // 1. Icon ve color sutunlarini ekle
    echo "  Icon ve color sutunlari ekleniyor...\n";
    $pdo->exec("ALTER TABLE categories
                ADD COLUMN icon VARCHAR(50) DEFAULT 'fa-question',
                ADD COLUMN color VARCHAR(20) DEFAULT 'gray'");

    // 2. Mevcut kategorileri icon ve renklerle guncelle
    echo "  Varsayilan icon ve renkler ataniyor...\n";
    $categoryStyles = [
        'genel_kultur' => ['icon' => 'fa-brain', 'color' => 'indigo'],
        'tarih' => ['icon' => 'fa-history', 'color' => 'blue'],
        'spor' => ['icon' => 'fa-futbol', 'color' => 'green'],
        'bilim' => ['icon' => 'fa-atom', 'color' => 'purple'],
        'sanat' => ['icon' => 'fa-palette', 'color' => 'yellow'],
        'cografya' => ['icon' => 'fa-globe-americas', 'color' => 'red'],
        'teknoloji' => ['icon' => 'fa-microchip', 'color' => 'cyan'],
        'matematik' => ['icon' => 'fa-calculator', 'color' => 'orange'],
        'edebiyat' => ['icon' => 'fa-book', 'color' => 'brown'],
        'muzik' => ['icon' => 'fa-music', 'color' => 'pink']
    ];

    $stmt = $pdo->prepare('UPDATE categories SET icon = ?, color = ? WHERE category_key = ?');
    foreach($categoryStyles as $key => $style) {
        $stmt->execute([$style['icon'], $style['color'], $key]);
    }

    $pdo->commit();
    echo "  + Kategori icon ve renk sistemi basariyla kuruldu!\n";

    markMigrationComplete($pdo, '1.6.0', 'Added icon and color columns to categories table with default styles');

  } catch (Exception $e) {
    $pdo->rollBack();
    throw new Exception("Migration 1.6.0 basarisiz: " . $e->getMessage());
  }
}

function migration_1_7_0($pdo) {
  echo "->  Migration 1.7.0: Achievement rules tablosu ekleniyor - dinamik basarim kurallari sistemi...\n";

  try {
    $pdo->beginTransaction();

    // 1. achievement_rules tablosu olustur
    echo "  Achievement rules tablosu olusturuluyor...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS achievement_rules (
                  id INT AUTO_INCREMENT PRIMARY KEY,
                  achievement_key VARCHAR(50) NOT NULL,
                  rule_type ENUM(
                    'first_correct',
                    'total_score',
                    'night_hours',
                    'all_categories',
                    'collect_achievements',
                    'category_expert',
                    'category_perfect',
                    'difficulty_expert',
                    'consecutive_correct',
                    'speed_answer'
                  ) NOT NULL,
                  target_value VARCHAR(100),
                  goal_count INT NOT NULL,
                  tracking_enabled BOOLEAN DEFAULT TRUE,
                  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                  INDEX idx_achievement_rules_key (achievement_key),
                  INDEX idx_achievement_rules_type (rule_type),
                  INDEX idx_achievement_rules_tracking (tracking_enabled),
                  FOREIGN KEY (achievement_key) REFERENCES achievements(achievement_key) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // 2. Mevcut basarimlar icin kurallari ekle
    echo "  Mevcut basarimlar icin kurallar ekleniyor...\n";
    $achievement_rules = [
      // Tracking destekli basarimlar
      ['ilk_adim', 'first_correct', null, 1, true],
      ['puan_avcisi_1000', 'total_score', null, 1000, true],
      ['gece_kusu', 'night_hours', '00:00-04:00', 1, true],
      ['merakli', 'all_categories', null, 1, true],
      ['koleksiyoncu', 'collect_achievements', null, 10, true],

      // Kategori uzmanlari (20 dogru) - tracking destekli
      ['uzman_tarih', 'category_expert', 'tarih', 20, true],
      ['uzman_spor', 'category_expert', 'spor', 20, true],
      ['uzman_bilim', 'category_expert', 'bilim', 20, true],
      ['uzman_sanat', 'category_expert', 'sanat', 20, true],
      ['uzman_cografya', 'category_expert', 'cografya', 20, true],
      ['uzman_genel kultur', 'category_expert', 'genel_kultur', 20, true],

      // Kategori kusursuzlari (%100) - tracking destekli
      ['kusursuz_tarih', 'category_perfect', 'tarih', 10, true],
      ['kusursuz_spor', 'category_perfect', 'spor', 10, true],
      ['kusursuz_bilim', 'category_perfect', 'bilim', 10, true],
      ['kusursuz_sanat', 'category_perfect', 'sanat', 10, true],
      ['kusursuz_cografya', 'category_perfect', 'cografya', 10, true],
      ['kusursuz_genel kultur', 'category_perfect', 'genel_kultur', 10, true],

      // Diger basarimlar (simdilik tracking yok ama gelecekte eklenebilir)
      ['hiz_tutkunu', 'speed_answer', '5', 1, true],
      ['seri_galibi_10', 'consecutive_correct', null, 10, true],
      ['seri_galibi_25', 'consecutive_correct', null, 25, true],
      ['zorlu_rakip', 'difficulty_expert', 'zor', 10, true]
    ];

    $stmt = $pdo->prepare("INSERT INTO achievement_rules (achievement_key, rule_type, target_value, goal_count, tracking_enabled) VALUES (?, ?, ?, ?, ?)");
    foreach ($achievement_rules as $rule) {
      $stmt->execute($rule);
    }

    $pdo->commit();
    echo "  + Achievement rules sistemi basariyla kuruldu! " . count($achievement_rules) . " adet kural eklendi.\n";

    markMigrationComplete($pdo, '1.7.0', 'Added achievement_rules table for dynamic achievement tracking system');

  } catch (Exception $e) {
    $pdo->rollBack();
    throw new Exception("Migration 1.7.0 basarisiz: " . $e->getMessage());
  }
}

// Migration 1.8.0: Quest system improvements - Login streak tracking ve yeni quest types
function migration_1_8_0($pdo) {
  echo "->  Migration 1.8.0: Quest sistem iyilestirmeleri - Login streak tracking ve yeni quest types ekleniyor...\n";

  try {
    $pdo->beginTransaction();

    // 1. Login streak tracking icin users tablosuna sutunlar ekle
    echo "  Users tablosuna login streak tracking sutunlari ekleniyor...\n";

    // MySQL IF NOT EXISTS syntax farkli, bu yuzden manuel kontrol edelim
    $columns_to_add = [
      'last_login_date' => 'ALTER TABLE users ADD COLUMN last_login_date DATE DEFAULT NULL',
      'current_login_streak' => 'ALTER TABLE users ADD COLUMN current_login_streak INT DEFAULT 0',
      'longest_login_streak' => 'ALTER TABLE users ADD COLUMN longest_login_streak INT DEFAULT 0'
    ];

    foreach ($columns_to_add as $column => $sql) {
      try {
        $check = $pdo->query("SHOW COLUMNS FROM users LIKE '$column'")->rowCount();
        if ($check == 0) {
          $pdo->exec($sql);
          echo "    + $column sutunu eklendi\n";
        } else {
          echo "    - $column sutunu zaten mevcut\n";
        }
      } catch (PDOException $e) {
        echo "    ! $column sutunu eklenirken hata: " . $e->getMessage() . "\n";
      }
    }

    // 2. Yeni quest types icin test data ekle
    echo "  Yeni quest types icin test veriler ekleniyor...\n";

    $new_quests = [
      ['login_streak_3', 'Düzenli Oyuncu', '{goal} gün üst üste giriş yap', 'consecutive_days', NULL, 3, 50, 30],
      ['login_streak_7', 'Kararlı Oyuncu', '{goal} gün üst üste giriş yap', 'consecutive_days', NULL, 7, 150, 100],
      ['login_streak_14', 'Adanmış Oyuncu', '{goal} gün üst üste giriş yap', 'consecutive_days', NULL, 14, 300, 200],
      ['win_duels_1', 'Düello Ustası', 'Bugün {goal} düello kazan', 'win_duels', NULL, 1, 75, 50],
      ['win_duels_3', 'Düello Şampiyonu', 'Bugün {goal} düello kazan', 'win_duels', NULL, 3, 200, 150],
      ['win_duels_5', 'Düello Efsanesi', 'Bugün {goal} düello kazan', 'win_duels', NULL, 5, 400, 300]
    ];

    $stmt = $pdo->prepare("INSERT IGNORE INTO quests (quest_key, name, description_template, type, target, default_goal, reward_points, reward_coins) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

    $added_count = 0;
    foreach ($new_quests as $quest) {
      $stmt->execute($quest);
      if ($stmt->rowCount() > 0) {
        $added_count++;
      }
    }

    echo "    + $added_count yeni quest eklendi\n";

    // 3. Performance indexleri ekle
    echo "  Performance indexleri ekleniyor...\n";

    $indexes = [
      "CREATE INDEX IF NOT EXISTS idx_users_login_streak ON users (current_login_streak DESC)",
      "CREATE INDEX IF NOT EXISTS idx_users_last_login ON users (last_login_date)",
      "CREATE INDEX IF NOT EXISTS idx_duels_winner_date ON duels (winner_id, created_at)",
      "CREATE INDEX IF NOT EXISTS idx_duels_completed_date ON duels (status, created_at)"
    ];

    foreach ($indexes as $index_sql) {
      try {
        $pdo->exec($index_sql);
      } catch (PDOException $e) {
        // Index zaten varsa hata vermez
      }
    }

    echo "    + Performance indexleri eklendi\n";

    $pdo->commit();
    echo "  + Quest sistem iyilestirmeleri basariyla tamamlandi!\n";

    markMigrationComplete($pdo, '1.8.0', 'Added login streak tracking and new quest types (consecutive_days, win_duels) with performance optimizations');

  } catch (Exception $e) {
    $pdo->rollBack();
    throw new Exception("Migration 1.8.0 basarisiz: " . $e->getMessage());
  }
}

// Migration 1.9.0: Quest History Tracking - Quest gecmisi ve performans analizi
function migration_1_9_0($pdo) {
  echo "->  Migration 1.9.0: Quest History Tracking - Quest gecmisi ve performans analizi ekleniyor...\n";

  try {
    $pdo->beginTransaction();

    // Quest history tablosu olustur
    echo "  quest_history tablosu olusturuluyor...\n";

    $sql = "
      CREATE TABLE IF NOT EXISTS `quest_history` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `quest_key` VARCHAR(50) NOT NULL,
        `quest_name` VARCHAR(255) NOT NULL,
        `quest_type` VARCHAR(50) NOT NULL,
        `assigned_date` DATE NOT NULL,
        `completed_date` DATETIME NOT NULL,
        `goal` INT NOT NULL,
        `achieved` INT NOT NULL,
        `reward_points` INT DEFAULT 0,
        `reward_coins` INT DEFAULT 0,
        `completion_time_minutes` INT DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_user_id` (`user_id`),
        INDEX `idx_quest_key` (`quest_key`),
        INDEX `idx_assigned_date` (`assigned_date`),
        INDEX `idx_completed_date` (`completed_date`),
        INDEX `idx_quest_type` (`quest_type`),
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";

    $pdo->exec($sql);
    echo "    + quest_history tablosu olusturuldu\n";

    // user_quests tablosuna completion tracking icin sutunlar ekle
    echo "  user_quests tablosuna completion tracking sutunlari ekleniyor...\n";

    $columns_to_add = [
      'start_time' => 'ALTER TABLE user_quests ADD COLUMN start_time DATETIME DEFAULT NULL',
      'completion_time' => 'ALTER TABLE user_quests ADD COLUMN completion_time DATETIME DEFAULT NULL'
    ];

    foreach ($columns_to_add as $column => $sql) {
      try {
        $check = $pdo->query("SHOW COLUMNS FROM user_quests LIKE '$column'")->rowCount();
        if ($check == 0) {
          $pdo->exec($sql);
          echo "    + $column sutunu eklendi\n";
        } else {
          echo "    - $column sutunu zaten mevcut\n";
        }
      } catch (PDOException $e) {
        echo "    ! $column sutunu eklenirken hata: " . $e->getMessage() . "\n";
      }
    }

    // Mevcut tamamlanmis questleri history'ye aktar (varsa)
    echo "  Mevcut tamamlanmis questler history'ye aktariliyor...\n";

    $migrate_sql = "
      INSERT INTO quest_history (
        user_id, quest_key, quest_name, quest_type, assigned_date,
        completed_date, goal, achieved, reward_points, reward_coins
      )
      SELECT
        uq.user_id,
        uq.quest_key,
        q.name,
        q.type,
        uq.assigned_date,
        COALESCE(uq.completion_time, NOW()),
        uq.goal,
        uq.progress,
        q.reward_points,
        q.reward_coins
      FROM user_quests uq
      JOIN quests q ON uq.quest_key = q.quest_key
      WHERE uq.is_completed = TRUE
      ON DUPLICATE KEY UPDATE id = id
    ";

    $stmt = $pdo->prepare($migrate_sql);
    $stmt->execute();
    $migrated_count = $stmt->rowCount();
    echo "    + $migrated_count tamamlanmis quest history'ye aktarildi\n";

    $pdo->commit();
    echo "  + Quest history tracking sistemi basariyla kuruldu!\n";

    markMigrationComplete($pdo, '1.9.0', 'Added quest_history table and completion tracking system for performance analytics');

  } catch (Exception $e) {
    $pdo->rollBack();
    throw new Exception("Migration 1.9.0 basarisiz: " . $e->getMessage());
  }
}

function migration_1_10_0($pdo) {
  echo "-> Migration 1.10.0: Duel Cancellation Support - Duello iptal ozelligi ekleniyor...\n";

  try {
    $pdo->beginTransaction();

    // duels tablosunun status enum'ina cancelled degerini ekle
    echo "  duels tablosu status enum'ina 'cancelled' degeri ekleniyor...\n";

    // Once mevcut enum degerlerini kontrol et
    $stmt = $pdo->query("SHOW COLUMNS FROM duels WHERE Field = 'status'");
    $column_info = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($column_info && strpos($column_info['Type'], 'cancelled') === false) {
      $sql = "ALTER TABLE duels MODIFY status ENUM('pending','declined','active','challenger_completed','opponent_completed','completed','expired','cancelled') NOT NULL DEFAULT 'pending'";
      $pdo->exec($sql);
      echo "    + 'cancelled' degeri duels.status enum'ina eklendi\n";
    } else {
      echo "    - 'cancelled' degeri zaten mevcut\n";
    }

    $pdo->commit();
    echo "  + Duello iptal ozelligi basariyla eklendi!\n";

    markMigrationComplete($pdo, '1.10.0', 'Added cancelled status to duels table enum for duel cancellation feature');

  } catch (Exception $e) {
    $pdo->rollBack();
    throw new Exception("Migration 1.10.0 basarisiz: " . $e->getMessage());
  }
}

function migration_1_11_0($pdo) {
  echo "-> Migration 1.11.0: Config ve Settings tablolarini birlestirilmesi - Config tablosu kaldiriliyor...\n";

  try {
    $pdo->beginTransaction();

    // Once config tablosundaki verileri kontrol et
    $config_check = $pdo->query("SHOW TABLES LIKE 'config'")->rowCount();
    if ($config_check == 0) {
      echo "    + Config tablosu bulunamadi, migration atlanıyor\n";
      $pdo->commit();
      markMigrationComplete($pdo, '1.11.0', 'Config table merge skipped - table not found');
      return;
    }

    // Config tablosundaki verileri al
    $stmt = $pdo->query("SELECT config_key, config_value, description FROM config");
    $config_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($config_data)) {
      echo "    + Config tablosundan " . count($config_data) . " ayar settings tablosuna tasiniyor...\n";

      $stmt_insert = $pdo->prepare("
        INSERT IGNORE INTO settings (setting_key, setting_value, description)
        VALUES (?, ?, ?)
      ");

      foreach ($config_data as $config) {
        $stmt_insert->execute([
          $config['config_key'],
          $config['config_value'],
          $config['description']
        ]);
        echo "      + {$config['config_key']}: {$config['config_value']}\n";
      }
    }

    // Config tablosunu sil
    echo "    + Config tablosu siliniyor...\n";
    $pdo->exec("DROP TABLE IF EXISTS config");
    echo "    + Config tablosu basariyla silindi\n";

    $pdo->commit();
    echo "  + Settings ve config tablolari basariyla birlestirildi!\n";

    markMigrationComplete($pdo, '1.11.0', 'Merged config table into settings table and removed config table');

  } catch (Exception $e) {
    $pdo->rollBack();
    throw new Exception("Migration 1.11.0 basarisiz: " . $e->getMessage());
  }
}

function migration_1_12_0($pdo) {
  echo "-> Migration 1.12.0: app_name ayarinin kaldirılması - Gereksiz ayar temizleniyor...\n";

  try {
    $pdo->beginTransaction();

    // app_name ayarını sil (zaten manuel olarak silinmişse hata vermez)
    $stmt = $pdo->prepare("DELETE FROM settings WHERE setting_key = 'app_name'");
    $stmt->execute();
    $deleted_count = $stmt->rowCount();

    if ($deleted_count > 0) {
      echo "    + app_name ayari settings tablosundan silindi\n";
    } else {
      echo "    + app_name ayari zaten mevcut degil, migration atlanıyor\n";
    }

    $pdo->commit();
    echo "  + app_name ayari temizleme islemi tamamlandi!\n";

    markMigrationComplete($pdo, '1.12.0', 'Removed redundant app_name setting from settings table');

  } catch (Exception $e) {
    $pdo->rollBack();
    throw new Exception("Migration 1.12.0 basarisiz: " . $e->getMessage());
  }
}

/**
 * Migration 1.13.0: Remove max_daily_questions setting
 */
function migration_1_13_0($pdo) {
  echo "-> Migration 1.13.0: max_daily_questions ayarini kaldirma...\n";

  try {
    $pdo->beginTransaction();

    // max_daily_questions setting'ini sil
    $stmt = $pdo->prepare("DELETE FROM settings WHERE setting_key = 'max_daily_questions'");
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
      echo "   max_daily_questions ayari silindi\n";
    } else {
      echo "   max_daily_questions ayari zaten mevcut degil\n";
    }

    $pdo->commit();
    markMigrationComplete($pdo, '1.13.0', 'Removed max_daily_questions setting from settings table');

  } catch (Exception $e) {
    $pdo->rollBack();
    throw new Exception("Migration 1.13.0 basarisiz: " . $e->getMessage());
  }
}

/**
 * Migration 1.14.0: Add welcome_bonus setting
 */
function migration_1_14_0($pdo) {
  echo "-> Migration 1.14.0: welcome_bonus ayarini ekleme...\n";

  try {
    $pdo->beginTransaction();

    // welcome_bonus setting'ini ekle
    $stmt = $pdo->prepare("
      INSERT INTO settings (setting_key, setting_value, description)
      VALUES ('welcome_bonus', '100', 'Yeni kullanıcılara kayıt sırasında verilecek hoş geldin jetonu miktarı')
      ON DUPLICATE KEY UPDATE description = VALUES(description)
    ");
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
      echo "   welcome_bonus ayari eklendi (default: 100 jeton)\n";
    } else {
      echo "   welcome_bonus ayari zaten mevcut\n";
    }

    $pdo->commit();
    markMigrationComplete($pdo, '1.14.0', 'Added welcome_bonus setting for new user registration bonus');

  } catch (Exception $e) {
    $pdo->rollBack();
    throw new Exception("Migration 1.14.0 basarisiz: " . $e->getMessage());
  }
}
