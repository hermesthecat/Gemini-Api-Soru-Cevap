<?php
require_once 'config.php';
include 'header.php';
?>

    <!-- Ana Konteyner -->
    <div id="app-container" class="container mx-auto px-4 py-8 max-w-4xl">

        <!-- ===== GİRİŞ EKRANI ===== -->
        <div id="auth-view" class="block">
            <div class="text-center mb-8">
                <h1 class="text-4xl font-bold text-gray-800 dark:text-gray-200 mb-2">AI Bilgi Yarışması</h1>
                <p class="text-gray-600 dark:text-gray-400">Bilginizi konuşturmak için giriş yapın.</p>
            </div>
            <div class="max-w-md mx-auto bg-white dark:bg-gray-800 p-8 rounded-xl shadow-lg">
                <!-- Form Geçiş Butonları -->
                <div class="flex border-b border-gray-200 dark:border-gray-700 mb-6">
                    <a href="<?php echo DOMAIN; ?>login.php" class="flex-1 py-2 font-semibold border-b-2 border-blue-500 text-blue-500 text-center">Giriş Yap</a>
                    <a href="<?php echo DOMAIN; ?>register.php" class="flex-1 py-2 font-semibold text-gray-500 text-center hover:text-blue-500">Kayıt Ol</a>
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

    <!-- Ayarlar -->
    <div id="setting-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-lg w-96">
            <h2 class="text-xl font-semibold mb-4 dark:text-white">Ayarlar</h2>
            <div class="space-y-4">
                <div class="flex justify-between items-center">
                    <span class="dark:text-gray-300">Koyu Tema</span>
                    <button id="theme-toggle-modal" class="p-2 rounded-full bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 transition-colors">
                        <i id="theme-toggle-dark-icon-modal" class="fas fa-moon hidden"></i>
                        <i id="theme-toggle-light-icon-modal" class="fas fa-sun hidden"></i>
                    </button>
                </div>
                <div class="flex justify-between items-center">
                    <span class="dark:text-gray-300">Ses Efektleri</span>
                    <button id="sound-toggle-modal" class="p-2 rounded-full bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 transition-colors">
                        <i id="sound-on-icon-modal" class="fas fa-volume-up hidden"></i>
                        <i id="sound-off-icon-modal" class="fas fa-volume-mute hidden"></i>
                    </button>
                </div>
            </div>
            <button id="close-settings-modal" class="mt-4 w-full bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded-lg transition-colors">Kapat</button>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div id="loading-overlay" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg text-center">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500 mx-auto mb-4"></div>
            <p class="text-gray-700 dark:text-gray-300">Yükleniyor...</p>
        </div>
    </div>

    <!-- Notification Toast -->
    <div id="notification-toast" class="fixed top-4 right-4 max-w-xs bg-white dark:bg-gray-800 border-l-4 border-blue-500 text-gray-900 dark:text-gray-100 p-4 rounded-lg shadow-lg transform translate-x-full transition-transform duration-300 z-50">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-info-circle text-blue-500"></i>
            </div>
            <div class="ml-3">
                <p id="notification-text" class="text-sm font-medium">
                    Bildirim metni
                </p>
            </div>
        </div>
    </div>

    <!-- Global State Management -->
    <script src="assets/js/app-state.js?v=<?php echo $v; ?>"></script>

    <!-- Core API Handler -->
    <script src="assets/js/api-handler.js?v=<?php echo $v; ?>"></script>

    <!-- Core UI Handler -->
    <script src="assets/js/ui-handler.js?v=<?php echo $v; ?>"></script>

    <!-- Feature Handlers -->
    <script src="assets/js/auth-handler.js?v=<?php echo $v; ?>"></script>
    <script src="assets/js/game-handler.js?v=<?php echo $v; ?>"></script>
    <script src="assets/js/stats-handler.js?v=<?php echo $v; ?>"></script>
    <script src="assets/js/admin-handler.js?v=<?php echo $v; ?>"></script>
    <script src="assets/js/settings-handler.js?v=<?php echo $v; ?>"></script>
    <script src="assets/js/friends-handler.js?v=<?php echo $v; ?>"></script>
    <script src="assets/js/duel-handler.js?v=<?php echo $v; ?>"></script>
    <script src="assets/js/quest-handler.js?v=<?php echo $v; ?>"></script>
    <script src="assets/js/announcement-handler.js?v=<?php echo $v; ?>"></script>
    <script src="assets/js/shop-handler.js?v=<?php echo $v; ?>"></script>

    <!-- Main Application -->
    <script src="assets/js/app.js?v=<?php echo $v; ?>"></script>

    <!-- Audio Elements -->
    <audio id="correct-sound" preload="auto">
        <source src="assets/sounds/correct.mp3" type="audio/mpeg">
    </audio>
    <audio id="incorrect-sound" preload="auto">
        <source src="assets/sounds/incorrect.mp3" type="audio/mpeg">
    </audio>
    <audio id="timeout-sound" preload="auto">
        <source src="assets/sounds/timeout.mp3" type="audio/mpeg">
    </audio>
    <audio id="achievement-sound" preload="auto">
        <source src="assets/sounds/achievement.mp3" type="audio/mpeg">
    </audio>

</body>
</html>