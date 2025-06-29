<?php

// Hata raporlamayı geliştirme aşamasında aç, canlıda kapat
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/**
 * Google Gemini API Anahtarı
 * Lütfen https://aistudio.google.com/app/apikey adresinden kendi anahtarınızı alın.
 */
define('GEMINI_API_KEY', 'API-KEY-BURAYA'); // API anahtarınızın doğru olduğundan emin olun


/**
 * Veritabanı Bağlantı Ayarları
 * XAMPP varsayılan ayarlarına göre düzenlenmiştir.
 */
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'ai_quiz');
