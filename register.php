<?php
require_once 'config.php';
include 'header.php';
?>

    <!-- Ana Konteyner -->
    <div id="app-container" class="container mx-auto px-4 py-8 max-w-4xl">

        <!-- ===== KAYIT EKRANI ===== -->
        <div id="auth-view" class="block">
            <div class="text-center mb-8">
                <h1 class="text-4xl font-bold text-gray-800 dark:text-gray-200 mb-2"><?php echo SITE_NAME; ?></h1>
                <p class="text-gray-600 dark:text-gray-400">Hesap oluşturun ve yarışmaya katılın.</p>
            </div>
            <div class="max-w-md mx-auto bg-white dark:bg-gray-800 p-8 rounded-xl shadow-lg">
                <?php if (REGISTRATION_ENABLED): ?>
                <!-- Form Geçiş Butonları -->
                <div class="flex border-b border-gray-200 dark:border-gray-700 mb-6">
                    <a href="<?php echo DOMAIN; ?>login.php" class="flex-1 py-2 font-semibold text-gray-500 text-center hover:text-blue-500">Giriş Yap</a>
                    <a href="<?php echo DOMAIN; ?>register.php" class="flex-1 py-2 font-semibold border-b-2 border-green-500 text-green-500 text-center">Kayıt Ol</a>
                </div>

                <!-- Kayıt Formu -->
                <form id="register-form">
                    <div class="mb-4">
                        <label for="register-username" class="block mb-2 text-sm font-medium dark:text-gray-300">Kullanıcı Adı</label>
                        <input type="text" id="register-username" autocomplete="username" class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-green-500" required>
                    </div>
                    <div class="mb-6">
                        <label for="register-password" class="block mb-2 text-sm font-medium dark:text-gray-300">Şifre (min. 6 karakter)</label>
                        <input type="password" id="register-password" autocomplete="new-password" class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-green-500" required>
                    </div>
                    <button type="submit" class="w-full bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded-lg transition-colors">Kayıt Ol</button>
                </form>
                <?php else: ?>
                <!-- Kayıtlar Kapalı Mesajı -->
                <div class="text-center">
                    <div class="mb-6">
                        <div class="inline-flex items-center justify-center w-16 h-16 bg-red-100 dark:bg-red-900 rounded-full mb-4">
                            <svg class="w-8 h-8 text-red-600 dark:text-red-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-800 dark:text-gray-200 mb-2">Kayıtlar Kapalı</h3>
                        <p class="text-gray-600 dark:text-gray-400 mb-4">Şu anda yeni kullanıcı kaydı kabul edilmemektedir.</p>
                    </div>
                    <a href="<?php echo DOMAIN; ?>login.php" class="inline-block bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-6 rounded-lg transition-colors">
                        Giriş Sayfasına Dön
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>

    </div>

<?php include 'footer.php'; ?>