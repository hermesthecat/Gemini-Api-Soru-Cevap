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

    echo "Veritabanı bağlantısı başarılı.\n";

    // Tablo var mı diye kontrol et
    $stmt = $pdo->query("SHOW TABLES LIKE 'leaderboard'");
    if ($stmt->rowCount() == 0) {
        // Liderlik tablosu SQL komutu
        $sql = "
        CREATE TABLE `leaderboard` (
          `id` INT AUTO_INCREMENT PRIMARY KEY,
          `user_id` VARCHAR(50) NOT NULL UNIQUE,
          `username` VARCHAR(50) NOT NULL,
          `score` INT NOT NULL DEFAULT 0,
          `last_updated` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";

        $pdo->exec($sql);
        echo "Tablo 'leaderboard' başarıyla oluşturuldu.\n";
    } else {
        echo "Tablo 'leaderboard' zaten mevcut.\n";
    }

    echo "\nKurulum başarıyla tamamlandı!";

} catch (PDOException $e) {
    die("Kurulum sırasında bir hata oluştu: " . $e->getMessage());
}

?> 