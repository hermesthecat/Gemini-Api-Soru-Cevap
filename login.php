<?php
require_once 'config.php';

// Maintenance mode kontrolü (giriş yapmamış kullanıcılar için)
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'maintenance_mode'");
    $stmt->execute();
    $maintenance_mode = $stmt->fetchColumn();

    // Eğer maintenance mode aktifse maintenance sayfasına yönlendir
    if ($maintenance_mode == '1') {
        header('Location: ' . DOMAIN . 'maintenance.php');
        exit();
    }
} catch (PDOException $e) {
    error_log("Maintenance mode check error: " . $e->getMessage());
}

include 'header.php';
?>

    <!-- Ana Konteyner -->
    <div id="app-container" class="container mx-auto px-4 py-8 max-w-4xl">

        <!-- ===== GİRİŞ EKRANI ===== -->
        <div id="auth-view" class="block">
            <div class="text-center mb-8">
                <h1 class="text-4xl font-bold text-gray-800 dark:text-gray-200 mb-2"><?php echo SITE_NAME; ?></h1>
                <p class="text-gray-600 dark:text-gray-400">Bilginizi konuşturmak için giriş yapın.</p>
            </div>
            <div class="max-w-md mx-auto bg-white dark:bg-gray-800 p-8 rounded-xl shadow-lg">
                <!-- Form Geçiş Butonları -->
                <div class="flex border-b border-gray-200 dark:border-gray-700 mb-6">
                    <a href="<?php echo DOMAIN; ?>login.php" class="<?php echo REGISTRATION_ENABLED ? 'flex-1' : 'w-full'; ?> py-2 font-semibold border-b-2 border-blue-500 text-blue-500 text-center">Giriş Yap</a>
                    <?php if (REGISTRATION_ENABLED): ?>
                    <a href="<?php echo DOMAIN; ?>register.php" class="flex-1 py-2 font-semibold text-gray-500 text-center hover:text-blue-500">Kayıt Ol</a>
                    <?php endif; ?>
                </div>

                <!-- Giriş Formu -->
                <form id="login-form">
                    <div class="mb-4">
                        <label for="login-username" class="block mb-2 text-sm font-medium dark:text-gray-300">Kullanıcı Adı</label>
                        <input type="text" id="login-username" autocomplete="username" class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    </div>
                    <div class="mb-6">
                        <label for="login-password" class="block mb-2 text-sm font-medium dark:text-gray-300">Şifre</label>
                        <input type="password" id="login-password" autocomplete="current-password" class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    </div>
                    <button type="submit" class="w-full bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-lg transition-colors">Giriş Yap</button>
                </form>
            </div>
        </div>

    </div>

<?php include 'footer.php'; ?>