<?php

require_once 'config.php';

header('Content-Type: text/plain; charset=utf-8');

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

  // --- Tabloları Temizle ve Yeniden Oluştur ---

  // Yabancı anahtar kısıtlamalarını dikkate alarak tabloları doğru sırada sil
  echo "Mevcut tablolar temizleniyor...\n";
  $pdo->exec("DROP TABLE IF EXISTS `user_quests`, `quests`, `duels`, `friends`, `user_achievements`, `user_difficulty_stats`, `user_stats`, `leaderboard`, `users`, `achievements`;");
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
      `failed_login_attempts` INT NOT NULL DEFAULT 0,
      `last_login_attempt` TIMESTAMP NULL DEFAULT NULL,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
  $pdo->exec($sql_users);
  echo "Tablo 'users' (rate limiting sütunları ile) başarıyla oluşturuldu.\n";

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
        `reward_points` INT NOT NULL
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
      `user_id` INT NOT NULL UNIQUE,
      `score` INT NOT NULL DEFAULT 0,
      `last_updated` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
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
    ['solve_5_tarih', 'Tarihçi', '{goal} tarih sorusu çöz', 'solve_category', 'tarih', 5, 25],
    ['solve_5_spor', 'Sporcu', '{goal} spor sorusu çöz', 'solve_category', 'spor', 5, 25],
    ['solve_5_bilim', 'Kaşif', '{goal} bilim sorusu çöz', 'solve_category', 'bilim', 5, 25],
    ['solve_3_zor', 'Gözü Pek', '{goal} zor soru çöz', 'solve_difficulty', 'zor', 3, 50],
    ['solve_10_orta', 'İstikrarlı', '{goal} orta soru çöz', 'solve_difficulty', 'orta', 10, 30]
  ];

  $stmt_quest_insert = $pdo->prepare("INSERT INTO quests (quest_key, name, description_template, type, target, default_goal, reward_points) VALUES (?, ?, ?, ?, ?, ?, ?)");
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

  echo "\nKurulum başarıyla tamamlandı!";
} catch (PDOException $e) {
  die("Kurulum sırasında bir hata oluştu: " . $e->getMessage());
}
