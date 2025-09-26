<?php include 'header.php'; ?>

    <!-- Ana Konteyner -->
    <div id="app-container" class="container mx-auto px-4 py-8 max-w-4xl">

        <?php include 'nav.php'; ?>
                <!-- Mağaza Tab -->
        <div id="magaza-tab" class="p-4 rounded-lg bg-gray-50 dark:bg-gray-800">
            <h3 class="text-xl font-bold mb-4 text-gray-800 dark:text-gray-200">Joker Mağazası</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">Jetonlarını kullanarak joker satın alabilir ve yarışmada avantaj elde edebilirsin.</p>
            <div id="shop-items-container" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Shop items will be rendered here by shop-handler.js -->
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

    <!-- Başarım Modal -->
    <div id="achievement-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
        <div class="bg-white dark:bg-gray-800 p-8 rounded-xl shadow-lg max-w-md w-full mx-4 text-center">
            <div class="mb-4">
                <div class="w-16 h-16 bg-yellow-500 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-trophy text-white text-2xl"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-200 mb-2">Yeni Başarım!</h2>
                <h3 id="achievement-modal-title" class="text-xl font-semibold text-yellow-600 dark:text-yellow-400 mb-2"></h3>
                <p id="achievement-modal-description" class="text-gray-600 dark:text-gray-400"></p>
            </div>
            <button id="achievement-modal-close" class="bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-2 px-6 rounded-lg transition-colors">Harika!</button>
        </div>
    </div>

    <!-- Günlük Görevler -->
    <div id="daily-quests-container" class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-8">
        <h2 class="text-xl font-semibold mb-4 dark:text-white">Günlük Görevler</h2>
        <div id="daily-quests-list" class="space-y-4">
            <!-- Görevler JS ile buraya yüklenecek -->
        </div>
        <div id="daily-quests-loading" class="text-center py-4">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500 mx-auto"></div>
            <p class="text-gray-500 dark:text-gray-400 mt-2">Görevler yükleniyor...</p>
        </div>
    </div>

    <!-- Duyuru Modal -->
    <div id="announcement-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-lg max-w-md w-full mx-4">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold dark:text-white">Duyuru</h2>
                <button id="announcement-modal-close-btn" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="announcement-modal-body" class="mb-4 dark:text-gray-300">
                <!-- Duyuru içeriği buraya gelecek -->
            </div>
            <button id="announcement-modal-ok-btn" class="w-full bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-lg transition-colors">Tamam</button>
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