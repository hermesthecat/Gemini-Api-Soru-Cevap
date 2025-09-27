        <!-- ===== ANA UYGULAMA EKRANI (Giriş yapıldığında görünür) ===== -->
        <div id="main-view" class="block">
            <!-- Üst Bar -->
            <header class="flex justify-between items-center mb-6">
                <div id="user-info" class="flex items-center space-x-4">
                    <div class="flex items-center">
                        <div id="user-avatar-display" class="w-10 h-10 rounded-full bg-blue-500 flex items-center justify-center text-white font-bold text-lg">
                            <?php echo isset($user_data) ? strtoupper(substr($user_data['username'], 0, 1)) : '?'; ?>
                        </div>
                        <div class="ml-3">
                            <h2 id="welcome-message" class="text-sm font-semibold text-gray-700 dark:text-gray-200">Hoş Geldin, <?php echo isset($user_data) ? htmlspecialchars($user_data['username']) : '...'; ?>!</h2>
                            <div class="flex items-center text-sm text-yellow-500 font-bold">
                                <i class="fas fa-coins mr-1"></i>
                                <span id="user-coin-balance"><?php echo isset($user_data) ? intval($user_data['coins']) : 0; ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex items-center space-x-2">
                    <?php if (isset($user_data) && $user_data['role'] === 'admin'): ?>
                    <div class="relative">
                        <button id="admin-dropdown-btn" class="text-sm bg-purple-500 hover:bg-purple-600 text-white py-2 px-3 rounded-lg transition-colors flex items-center">
                            Yönetim <i class="fas fa-chevron-down ml-1"></i>
                        </button>
                        <div id="admin-dropdown-menu" class="hidden absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-md shadow-lg z-10">
                            <a href="admin-users.php" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">👥 Kullanıcı Yönetimi</a>
                            <a href="admin-announcements.php" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">📢 Duyuru Yönetimi</a>
                            <a href="admin-achievements.php" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">🏆 Başarım Yönetimi</a>
                            <a href="admin-quests.php" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">📋 Quest Yönetimi</a>
                            <a href="admin-shop.php" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">🛒 Mağaza Yönetimi</a>
                            <a href="admin-stats.php" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">📊 İstatistikler</a>
                            <a href="admin-settings.php" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">🔧 Sistem Ayarları</a>
                        </div>
                    </div>
                    <?php endif; ?>
                    <button id="theme-toggle" class="p-2 rounded-full bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 transition-colors">
                        <i id="theme-toggle-dark-icon" class="fas fa-moon hidden"></i>
                        <i id="theme-toggle-light-icon" class="fas fa-sun hidden"></i>
                    </button>
                    <button id="sound-toggle" class="p-2 rounded-full bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 transition-colors">
                        <i id="sound-on-icon" class="fas fa-volume-up hidden"></i>
                        <i id="sound-off-icon" class="fas fa-volume-mute hidden"></i>
                    </button>
                    <button id="logout-btn" class="text-gray-600 dark:text-gray-300 hover:text-blue-500 dark:hover:text-blue-400" title="Çıkış Yap">
                        <i class="fas fa-sign-out-alt fa-lg"></i>
                    </button>
                    <div class="relative">
                        <button id="announcements-btn" class="text-gray-600 dark:text-gray-300 hover:text-blue-500 dark:hover:text-blue-400" title="Duyurular">
                            <i class="fas fa-bell fa-lg"></i>
                        </button>
                        <span id="announcements-badge" class="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center hidden"></span>
                    </div>
                </div>
            </header>

            <!-- Sekme Butonları -->
            <div class="mb-6 border-b border-gray-200 dark:border-gray-700">
                <ul class="flex flex-wrap -mb-px text-sm font-medium text-center" id="main-tabs">
                    <li class="mr-2">
                        <a href="<?php echo DOMAIN; ?>index.php" class="main-tab-link inline-block p-4 border-b-2 rounded-t-lg hover:text-blue-600 hover:border-blue-300 <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'border-blue-500 text-blue-500' : 'border-transparent text-gray-500'; ?>">
                            <i class="fas fa-gamepad mr-2"></i>Yarışma
                        </a>
                    </li>
                    <li class="mr-2">
                        <a href="<?php echo DOMAIN; ?>profile.php" class="main-tab-link inline-block p-4 border-b-2 rounded-t-lg hover:text-blue-600 hover:border-blue-300 <?php echo (basename($_SERVER['PHP_SELF']) == 'profile.php') ? 'border-blue-500 text-blue-500' : 'border-transparent text-gray-500'; ?>">
                            <i class="fas fa-user-chart mr-2"></i>Profil
                        </a>
                    </li>
                    <li class="mr-2">
                        <a href="<?php echo DOMAIN; ?>friends.php" class="main-tab-link inline-block p-4 border-b-2 rounded-t-lg hover:text-blue-600 hover:border-blue-300 <?php echo (basename($_SERVER['PHP_SELF']) == 'friends.php') ? 'border-blue-500 text-blue-500' : 'border-transparent text-gray-500'; ?>">
                            <i class="fas fa-users mr-2"></i>Arkadaşlar
                        </a>
                    </li>
                    <li class="mr-2">
                        <a href="<?php echo DOMAIN; ?>shop.php" class="main-tab-link inline-block p-4 border-b-2 rounded-t-lg hover:text-blue-600 hover:border-blue-300 <?php echo (basename($_SERVER['PHP_SELF']) == 'shop.php') ? 'border-blue-500 text-blue-500' : 'border-transparent text-gray-500'; ?>">
                            <i class="fas fa-store mr-2"></i>Mağaza
                        </a>
                    </li>
                    <li class="mr-2">
                        <a href="<?php echo DOMAIN; ?>leaderboard.php" class="main-tab-link inline-block p-4 border-b-2 rounded-t-lg hover:text-blue-600 hover:border-blue-300 <?php echo (basename($_SERVER['PHP_SELF']) == 'leaderboard.php') ? 'border-blue-500 text-blue-500' : 'border-transparent text-gray-500'; ?>">
                            <i class="fas fa-trophy mr-2"></i>Liderlik
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Sekme İçerikleri -->
            <div id="tab-content">
        </div>

        <script>
            // CSRF token ve kullanıcı bilgilerini JavaScript'e aktar
            <?php if (isset($user_data)): ?>
            window.USER_DATA = <?php echo json_encode($user_data); ?>;
            window.CSRF_TOKEN = '<?php echo htmlspecialchars($user_data['csrf_token']); ?>';
            <?php endif; ?>

            // Admin dropdown menü işlevselliği
            document.addEventListener('DOMContentLoaded', () => {
                const adminDropdownBtn = document.getElementById('admin-dropdown-btn');
                const adminDropdownMenu = document.getElementById('admin-dropdown-menu');

                if (adminDropdownBtn && adminDropdownMenu) {
                    adminDropdownBtn.addEventListener('click', (e) => {
                        e.stopPropagation();
                        adminDropdownMenu.classList.toggle('hidden');
                    });

                    // Dropdown dışında tıklanınca kapat
                    document.addEventListener('click', () => {
                        adminDropdownMenu.classList.add('hidden');
                    });

                    // Dropdown içinde tıklanınca kapatma
                    adminDropdownMenu.addEventListener('click', (e) => {
                        e.stopPropagation();
                    });
                }
            });
        </script>