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
    <div id="notification-toast" class="hidden fixed top-8 right-4 max-w-xs bg-white dark:bg-gray-800 border-l-4 border-blue-500 text-gray-900 dark:text-gray-100 p-4 rounded-lg shadow-lg transform translate-x-full transition-transform duration-300 z-50">
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

    <!-- Düello Modal -->
    <div id="duel-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-lg max-w-md w-full mx-4">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold dark:text-white">Düello Daveti Gönder</h2>
                <button id="duel-modal-close-btn" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="mb-4">
                <p class="text-gray-600 dark:text-gray-300 mb-2">Oyuncu: <span id="duel-opponent-name" class="font-semibold"></span></p>

                <div class="mb-4">
                    <label for="duel-category-select" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Kategori</label>
                    <select id="duel-category-select" class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <!-- Kategoriler dinamik olarak yüklenecek -->
                    </select>
                </div>

                <div class="mb-4">
                    <label for="duel-difficulty-select" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Zorluk</label>
                    <select id="duel-difficulty-select" class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="kolay">Kolay (5 Jeton)</option>
                        <option value="orta">Orta (10 Jeton)</option>
                        <option value="zor">Zor (15 Jeton)</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label for="duel-question-count-select" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Soru Sayısı</label>
                    <select id="duel-question-count-select" class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="5" selected>5 Soru</option>
                        <option value="10">10 Soru</option>
                        <option value="15">15 Soru</option>
                        <option value="20">20 Soru</option>
                        <option value="25">25 Soru</option>
                    </select>
                </div>
            </div>
            <button id="duel-send-challenge-btn" class="w-full bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded-lg transition-colors">Düello Daveti Gönder</button>
        </div>
    </div>

    <!-- Düello Oyun Ekranı -->
    <div id="duel-game-view" class="hidden">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-2xl font-bold text-red-600 dark:text-red-400">DÜELLO</h2>
                <div class="text-right">
                    <p class="text-sm text-gray-600 dark:text-gray-400">Rakip:</p>
                    <p id="duel-game-opponent-name" class="font-semibold"></p>
                </div>
            </div>

            <!-- İlerleme Çubuğu -->
            <div class="mb-4">
                <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400 mb-1">
                    <span>Soru İlerlemesi</span>
                    <span id="duel-game-progress">1/5</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div id="duel-progress-bar" class="bg-red-500 h-2 rounded-full" style="width: 20%"></div>
                </div>
            </div>

            <!-- Skor Tablosu -->
            <div class="grid grid-cols-2 gap-4 mb-6">
                <div class="text-center p-3 bg-blue-100 dark:bg-blue-900 rounded-lg">
                    <p class="text-sm text-gray-600 dark:text-gray-400">Ben</p>
                    <p id="duel-my-username" class="font-semibold text-blue-600 dark:text-blue-400"></p>
                    <p id="duel-my-score" class="text-2xl font-bold text-blue-600 dark:text-blue-400">0</p>
                </div>
                <div class="text-center p-3 bg-red-100 dark:bg-red-900 rounded-lg">
                    <p class="text-sm text-gray-600 dark:text-gray-400">Rakip</p>
                    <p id="duel-opponent-name-score" class="font-semibold text-red-600 dark:text-red-400"></p>
                    <p id="duel-opponent-score" class="text-2xl font-bold text-red-600 dark:text-red-400">0</p>
                </div>
            </div>
        </div>

        <!-- Soru Konteyner -->
        <div id="duel-question-container" class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
            <div class="mb-4">
                <p id="duel-question-text" class="text-lg font-semibold text-gray-800 dark:text-gray-200"></p>
            </div>
            <div id="duel-options-container" class="space-y-3">
                <!-- Seçenekler buraya gelecek -->
            </div>

            <!-- Açıklama Alanı -->
            <div id="duel-explanation-container" class="hidden mt-4 p-4 bg-gray-100 dark:bg-gray-700 rounded-lg">
                <h4 class="font-semibold text-gray-800 dark:text-gray-200 mb-2">Açıklama:</h4>
                <p id="duel-explanation-text" class="text-gray-700 dark:text-gray-300"></p>
                <button id="duel-next-question-btn" class="mt-4 w-full bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-lg transition-colors">Sonraki Soru</button>
            </div>
        </div>

        <!-- Düello Sonuç Ekranı -->
        <div id="duel-summary-container" class="hidden bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mt-6 text-center">
            <div class="mb-6">
                <div id="duel-summary-icon" class="w-20 h-20 mx-auto mb-4 rounded-full flex items-center justify-center">
                    <!-- İkon buraya gelecek -->
                </div>
                <h2 id="duel-summary-title" class="text-3xl font-bold mb-2"></h2>
                <p id="duel-summary-text" class="text-gray-600 dark:text-gray-400"></p>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-6">
                <div class="p-4 bg-blue-100 dark:bg-blue-900 rounded-lg">
                    <p class="text-sm text-gray-600 dark:text-gray-400">Sen</p>
                    <p id="duel-summary-my-name" class="font-semibold text-blue-600 dark:text-blue-400"></p>
                    <p id="duel-summary-my-score" class="text-2xl font-bold text-blue-600 dark:text-blue-400">0</p>
                </div>
                <div class="p-4 bg-red-100 dark:bg-red-900 rounded-lg">
                    <p class="text-sm text-gray-600 dark:text-gray-400">Rakip</p>
                    <p id="duel-summary-opponent-name" class="font-semibold text-red-600 dark:text-red-400"></p>
                    <p id="duel-summary-opponent-score" class="text-2xl font-bold text-red-600 dark:text-red-400">0</p>
                </div>
            </div>

            <button id="duel-back-to-friends-btn" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-6 rounded-lg transition-colors">Arkadaşlara Dön</button>
        </div>
    </div>

    <!-- Global State Management -->
    <script src="assets/js/app-state.js?v=<?php echo $v; ?>"></script>

    <!-- Core API Handler -->
    <script src="assets/js/api-handler.js?v=<?php echo $v; ?>"></script>

    <!-- App Data -->
    <script src="assets/js/app-data.js?v=<?php echo $v; ?>"></script>

    <!-- Module Loading System -->
    <script src="assets/js/module-loader.js?v=<?php echo $v; ?>"></script>
    <script src="assets/js/ui-core.js?v=<?php echo $v; ?>"></script>
    <script src="assets/js/ui-components.js?v=<?php echo $v; ?>"></script>
    <script src="assets/js/ui-charts.js?v=<?php echo $v; ?>"></script>
    <script src="assets/js/ui-questions.js?v=<?php echo $v; ?>"></script>
    <script src="assets/js/ui-game.js?v=<?php echo $v; ?>"></script>

    <!-- Core UI Handler (Legacy - will be replaced) -->
    <script src="assets/js/ui-handler.js?v=<?php echo $v; ?>"></script>

    <!-- Feature Handlers -->
    <script src="assets/js/auth-handler.js?v=<?php echo $v; ?>"></script>
    <script src="assets/js/game-handler.js?v=<?php echo $v; ?>"></script>
    <script src="assets/js/stats-handler.js?v=<?php echo $v; ?>"></script>
    <script src="assets/js/settings-handler.js?v=<?php echo $v; ?>"></script>
    <script src="assets/js/friends-handler.js?v=<?php echo $v; ?>"></script>
    <script src="assets/js/duel-handler.js?v=<?php echo $v; ?>"></script>
    <script src="assets/js/quest-handler.js?v=<?php echo $v; ?>"></script>
    <script src="assets/js/announcement-handler.js?v=<?php echo $v; ?>"></script>
    <script src="assets/js/shop-handler.js?v=<?php echo $v; ?>"></script>

    <!-- Admin Handlers (Only for Admin Users) -->
    <?php if (isset($user_data) && $user_data['role'] === 'admin'): ?>
    <script src="assets/js/admin-handler.js?v=<?php echo $v; ?>"></script>
    <script src="assets/js/admin-settings-handler.js?v=<?php echo $v; ?>"></script>
    <script src="assets/js/admin-shop-handler.js?v=<?php echo $v; ?>"></script>
    <script src="assets/js/admin-category-handler.js?v=<?php echo $v; ?>"></script>
    <script src="assets/js/admin-achievement-handler.js?v=<?php echo $v; ?>"></script>
    <script src="assets/js/admin-quest-handler.js?v=<?php echo $v; ?>"></script>
    <?php endif; ?>

    <!-- Main Application -->
    <script src="assets/js/app.js?v=<?php echo $v; ?>"></script>

    <!-- User Data ve CSRF Token'ı JavaScript'e aktar -->
    <?php if (isset($user_data)): ?>
    <script>
        window.USER_DATA = <?php echo json_encode($user_data); ?>;
        window.CSRF_TOKEN = '<?php echo $user_data['csrf_token']; ?>';
    </script>
    <?php endif; ?>

    <!-- Audio Elements (disabled - no sound files) -->
    <audio id="correct-sound" preload="none">
        <!-- <source src="assets/sounds/correct.mp3" type="audio/mpeg"> -->
    </audio>
    <audio id="incorrect-sound" preload="none">
        <!-- <source src="assets/sounds/incorrect.mp3" type="audio/mpeg"> -->
    </audio>
    <audio id="timeout-sound" preload="none">
        <!-- <source src="assets/sounds/timeout.mp3" type="audio/mpeg"> -->
    </audio>
    <audio id="achievement-sound" preload="none">
        <!-- <source src="assets/sounds/achievement.mp3" type="audio/mpeg"> -->
    </audio>

</body>
</html>