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
    $pdo->exec("DROP TABLE IF EXISTS `user_achievements`;");
    $pdo->exec("DROP TABLE IF EXISTS `user_stats`;");
    $pdo->exec("DROP TABLE IF EXISTS `leaderboard`;");
    $pdo->exec("DROP TABLE IF EXISTS `users`;");
    echo "Eski tablolar başarıyla silindi.\n\n";

    echo "Yeni tablolar oluşturuluyor...\n";

    // `users` tablosu
    $sql_users = "
    CREATE TABLE `users` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `username` VARCHAR(50) NOT NULL UNIQUE,
      `password` VARCHAR(255) NOT NULL,
      `role` ENUM('user', 'admin') NOT NULL DEFAULT 'user',
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $pdo->exec($sql_users);
    echo "Tablo 'users' başarıyla oluşturuldu.\n";

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

    // `user_stats` tablosu (users tablosuna bağlı)
    $sql_user_stats = "
    CREATE TABLE `user_stats` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `user_id` INT NOT NULL,
      `category` VARCHAR(50) NOT NULL,
      `total_questions` INT NOT NULL DEFAULT 0,
      `correct_answers` INT NOT NULL DEFAULT 0,
      UNIQUE KEY `user_category` (`user_id`, `category`),
      FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $pdo->exec($sql_user_stats);
    echo "Tablo 'user_stats' başarıyla oluşturuldu ve 'users' tablosuna bağlandı.\n";

    // `user_achievements` tablosu (users tablosuna bağlı)
    $sql_user_achievements = "
    CREATE TABLE `user_achievements` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `user_id` INT NOT NULL,
      `achievement_key` VARCHAR(50) NOT NULL,
      `achieved_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      UNIQUE KEY `user_achievement` (`user_id`, `achievement_key`),
      FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $pdo->exec($sql_user_achievements);
    echo "Tablo 'user_achievements' başarıyla oluşturuldu ve 'users' tablosuna bağlandı.\n";

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
