        <!-- ===== ANA UYGULAMA EKRANI (Giriş yapıldığında görünür) ===== -->
        <div id="main-view" class="hidden">
            <!-- Üst Bar -->
            <header class="flex justify-between items-center mb-6">
                <div id="user-info" class="flex items-center space-x-4">
                    <div class="flex items-center">
                        <img id="user-avatar-display" src="assets/images/avatars/avatar1.svg" alt="User Avatar" class="w-10 h-10 rounded-full">
                        <div class="ml-3">
                            <h2 id="welcome-message" class="text-sm font-semibold text-gray-700 dark:text-gray-200">Hoş Geldin, ...!</h2>
                            <div class="flex items-center text-sm text-yellow-500 font-bold">
                                <i class="fas fa-coins mr-1"></i>
                                <span id="user-coin-balance">0</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex items-center space-x-2">
                    <button id="admin-view-btn" class="hidden text-sm bg-purple-500 hover:bg-purple-600 text-white py-2 px-3 rounded-lg transition-colors">Yönetim Paneli</button>
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
                        <button class="main-tab-button inline-block p-4 border-b-2 rounded-t-lg" data-tab="yarışma">
                            <i class="fas fa-gamepad mr-2"></i>Yarışma
                        </button>
                    </li>
                    <li class="mr-2">
                        <button class="main-tab-button inline-block p-4 border-b-2 rounded-t-lg" data-tab="profil">
                            <i class="fas fa-user-chart mr-2"></i>Profil ve İstatistikler
                        </button>
                    </li>
                    <li class="mr-2">
                        <button class="main-tab-button inline-block p-4 border-b-2 rounded-t-lg" data-tab="arkadaslar">
                            <i class="fas fa-users mr-2"></i>Arkadaşlar
                        </button>
                    </li>
                    <li class="mr-2">
                        <button class="main-tab-button inline-block p-4 border-b-2 rounded-t-lg" data-tab="magaza">
                            <i class="fas fa-store mr-2"></i>Mağaza
                        </button>
                    </li>
                </ul>
            </div>

            <!-- Sekme İçerikleri -->
            <div id="tab-content">