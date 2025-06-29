<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Bilgi Yarışması</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <script>
        // Tema yönetimi için FOUC önleyici betik
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark')
        } else {
            document.documentElement.classList.remove('dark')
        }
    </script>
</head>

<body class="bg-gray-50 dark:bg-gray-900 min-h-screen text-gray-800 dark:text-gray-200 font-sans">

    <!-- Ana Konteyner -->
    <div id="app-container" class="container mx-auto px-4 py-8 max-w-4xl">

        <!-- ===== GİRİŞ VE KAYIT EKRANI (Başlangıçta görünebilir) ===== -->
        <div id="auth-view" class="hidden">
            <div class="text-center mb-8">
                <h1 class="text-4xl font-bold text-gray-800 dark:text-gray-200 mb-2">AI Bilgi Yarışması</h1>
                <p class="text-gray-600 dark:text-gray-400">Bilginizi konuşturmak için giriş yapın veya kayıt olun.</p>
            </div>
            <div class="max-w-md mx-auto bg-white dark:bg-gray-800 p-8 rounded-xl shadow-lg">
                <!-- Form Geçiş Butonları -->
                <div class="flex border-b border-gray-200 dark:border-gray-700 mb-6">
                    <button id="show-login-btn" class="flex-1 py-2 font-semibold border-b-2 border-blue-500 text-blue-500">Giriş Yap</button>
                    <button id="show-register-btn" class="flex-1 py-2 font-semibold text-gray-500">Kayıt Ol</button>
                </div>

                <!-- Giriş Formu -->
                <form id="login-form">
                    <div class="mb-4">
                        <label for="login-username" class="block mb-2 text-sm font-medium dark:text-gray-300">Kullanıcı Adı</label>
                        <input type="text" id="login-username" class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    </div>
                    <div class="mb-6">
                        <label for="login-password" class="block mb-2 text-sm font-medium dark:text-gray-300">Şifre</label>
                        <input type="password" id="login-password" class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    </div>
                    <button type="submit" class="w-full bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-lg transition-colors">Giriş Yap</button>
                </form>

                <!-- Kayıt Formu -->
                <form id="register-form" class="hidden">
                    <div class="mb-4">
                        <label for="register-username" class="block mb-2 text-sm font-medium dark:text-gray-300">Kullanıcı Adı</label>
                        <input type="text" id="register-username" class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    </div>
                    <div class="mb-6">
                        <label for="register-password" class="block mb-2 text-sm font-medium dark:text-gray-300">Şifre (min. 6 karakter)</label>
                        <input type="password" id="register-password" class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    </div>
                    <button type="submit" class="w-full bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded-lg transition-colors">Kayıt Ol</button>
                </form>
            </div>
        </div>

        <!-- ===== ANA UYGULAMA EKRANI (Giriş yapıldığında görünür) ===== -->
        <div id="main-view" class="hidden">
            <!-- Üst Bar -->
            <header class="flex justify-between items-center mb-6">
                <div id="user-info" class="font-semibold">
                    <span id="welcome-message"></span>
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
                    <button id="logout-btn" class="text-sm bg-red-500 hover:bg-red-600 text-white py-2 px-3 rounded-lg transition-colors">Çıkış Yap</button>
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
                </ul>
            </div>

            <!-- Sekme İçerikleri -->
            <div id="tab-content">
                <!-- Yarışma Sekmesi İçeriği -->
                <div id="yarışma-tab" class="main-tab-content">
                    <main id="game-container" class="space-y-8">
                        <!-- Kategori Seçim Alanı -->
                        <div id="category-selection-container" class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                            <h2 class="text-xl font-semibold mb-4 text-center dark:text-white">Zorluk Seçin</h2>
                            <div class="flex justify-center mb-6 space-x-2" id="difficulty-buttons">
                                <button data-zorluk="kolay" class="difficulty-button px-4 py-2 rounded-lg bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">Kolay</button>
                                <button data-zorluk="orta" class="difficulty-button px-4 py-2 rounded-lg bg-blue-500 text-white font-semibold transition-colors">Orta</button>
                                <button data-zorluk="zor" class="difficulty-button px-4 py-2 rounded-lg bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">Zor</button>
                            </div>
                            <h2 class="text-xl font-semibold mb-4 border-t pt-6 text-center dark:text-white dark:border-gray-700">Kategori Seçin</h2>
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-4" id="category-buttons">
                                <!-- Kategori butonları JS ile doldurulacak -->
                            </div>
                        </div>

                        <!-- Soru Alanı -->
                        <div id="question-container" class="hidden bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                            <div class="flex justify-between items-center mb-4">
                                <span id="question-category" class="inline-block bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300 px-3 py-1 rounded-full text-sm"></span>
                                <div class="flex items-center space-x-4">
                                    <!-- Joker Butonları -->
                                    <div id="lifeline-container" class="flex items-center space-x-2">
                                        <button id="lifeline-fifty-fifty" class="lifeline-button p-2 w-10 h-10 rounded-full bg-yellow-400 hover:bg-yellow-500 text-white dark:bg-yellow-600 dark:hover:bg-yellow-500 transition-colors shadow-md" title="50/50 Joker Hakkı">
                                            <span class="font-bold">½</span>
                                        </button>
                                        <button id="lifeline-extra-time" class="lifeline-button p-2 w-10 h-10 rounded-full bg-green-400 hover:bg-green-500 text-white dark:bg-green-600 dark:hover:bg-green-500 transition-colors shadow-md" title="Ekstra Süre Jokeri">
                                            <i class="fas fa-stopwatch"></i>
                                        </button>
                                        <button id="lifeline-pass" class="lifeline-button p-2 w-10 h-10 rounded-full bg-blue-400 hover:bg-blue-500 text-white dark:bg-blue-600 dark:hover:bg-blue-500 transition-colors shadow-md" title="Soruyu Geç Jokeri">
                                            <i class="fas fa-forward"></i>
                                        </button>
                                    </div>
                                    <div id="timer-container" class="text-lg font-bold">Kalan Süre: <span id="countdown" class="text-blue-600">30</span></div>
                                </div>
                            </div>
                            <div class="text-gray-700 dark:text-gray-300 mb-4">
                                <h3 class="text-xl font-semibold mb-2 dark:text-white">Soru:</h3>
                                <p id="question-text"></p>
                            </div>
                            <div id="options-container" class="grid grid-cols-1 md:grid-cols-2 gap-4 items-center"></div>
                            <div id="explanation-container" class="hidden mt-6 p-4 bg-blue-50 dark:bg-gray-700/50 border-l-4 border-blue-500">
                                <h4 class="font-bold text-blue-800 dark:text-blue-300 mb-1">Açıklama</h4>
                                <p id="explanation-text" class="text-blue-700 dark:text-blue-400"></p>
                            </div>
                        </div>
                    </main>
                </div>

                <!-- Profil Sekmesi İçeriği -->
                <div id="profil-tab" class="main-tab-content hidden">
                    <aside class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        <!-- Sol Taraf (İstatistikler ve Liderlik) -->
                        <div class="space-y-8">
                            <div id="stats-container" class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                                <h2 class="text-xl font-semibold mb-4 dark:text-white">Kişisel İstatistikler</h2>
                                <div class="text-center mb-4 border-b dark:border-gray-700 pb-4">
                                    <p class="text-gray-500 dark:text-gray-400">Toplam Puan</p>
                                    <p id="user-total-score" class="text-3xl font-bold text-blue-600">0</p>
                                </div>
                                <h3 class="text-lg font-semibold mb-3 dark:text-white">Kategori Detayları</h3>
                                <div id="category-stats-container" class="max-h-60 overflow-y-auto">
                                    <table class="w-full text-left text-sm">
                                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400 sticky top-0">
                                            <tr>
                                                <th class="py-2 px-2">Kategori</th>
                                                <th class="py-2 px-2 text-center">Soru</th>
                                                <th class="py-2 px-2 text-center">Doğru</th>
                                                <th class="py-2 px-2 text-center">%</th>
                                            </tr>
                                        </thead>
                                        <tbody id="category-stats-body">
                                            <!-- JS ile doldurulacak -->
                                        </tbody>
                                    </table>
                                    <p id="no-stats-message" class="text-gray-500 dark:text-gray-400 text-center py-4">Henüz veri yok.</p>
                                </div>
                            </div>
                             <div id="leaderboard-container" class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                                <h2 class="text-xl font-semibold mb-4 dark:text-white">Liderlik Tablosu</h2>
                                <ol id="leaderboard-list" class="space-y-3">
                                    <!-- JS ile doldurulacak -->
                                </ol>
                                <p id="leaderboard-loading" class="text-gray-500 dark:text-gray-400 text-center py-4">Yükleniyor...</p>
                            </div>
                        </div>
                        <!-- Sağ Taraf (Başarımlar) -->
                        <div id="achievements-container" class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                            <h2 class="text-xl font-semibold mb-4 dark:text-white">Kazanılan Rozetler</h2>
                            <div id="achievements-list" class="space-y-4">
                                <!-- JS ile doldurulacak -->
                            </div>
                            <p id="no-achievements-message" class="text-gray-500 dark:text-gray-400 text-center py-4">Henüz kazanılmış rozet yok.</p>
                        </div>
                    </aside>
                </div>
            </div>
        </div>

        <!-- ===== ADMİN PANELİ EKRANI (Admin giriş yaptığında görünür) ===== -->
        <div id="admin-view" class="hidden">
            <!-- Üst Bar -->
            <header class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold">Yönetim Paneli</h1>
                    <p class="text-gray-500 dark:text-gray-400">Uygulama genel verileri ve kullanıcı yönetimi</p>
                </div>
                <button id="user-view-btn" class="text-sm bg-blue-500 hover:bg-blue-600 text-white py-2 px-3 rounded-lg transition-colors">Oyuncu Görünümüne Geç</button>
            </header>

            <!-- Dashboard İstatistik Kartları -->
            <div id="admin-dashboard-cards" class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-lg flex items-center space-x-4">
                    <div class="bg-blue-100 dark:bg-blue-900 p-3 rounded-full">
                        <i class="fas fa-users fa-2x text-blue-500"></i>
                    </div>
                    <div>
                        <p class="text-gray-500 dark:text-gray-400">Toplam Kullanıcı</p>
                        <p id="admin-total-users" class="text-2xl font-bold">0</p>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-lg flex items-center space-x-4">
                    <div class="bg-green-100 dark:bg-green-900 p-3 rounded-full">
                        <i class="fas fa-question-circle fa-2x text-green-500"></i>
                    </div>
                    <div>
                        <p class="text-gray-500 dark:text-gray-400">Cevaplanan Soru Sayısı</p>
                        <p id="admin-total-questions" class="text-2xl font-bold">0</p>
                    </div>
                </div>
            </div>

            <!-- Kullanıcı Yönetim Tablosu -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-semibold mb-4 dark:text-white">Kullanıcı Yönetimi</h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                            <tr>
                                <th scope="col" class="px-6 py-3">Kullanıcı</th>
                                <th scope="col" class="px-6 py-3">Puan</th>
                                <th scope="col" class="px-6 py-3">Rol</th>
                                <th scope="col" class="px-6 py-3">Kayıt Tarihi</th>
                                <th scope="col" class="px-6 py-3">İşlemler</th>
                            </tr>
                        </thead>
                        <tbody id="admin-user-list-body">
                            <!-- JS ile doldurulacak -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <!-- Genel Yükleme ve Bildirim Alanları -->
    <div id="loading-overlay" class="hidden fixed inset-0 bg-gray-900 bg-opacity-60 flex items-center justify-center z-50">
        <div class="flex items-center text-white">
            <i class="fas fa-spinner fa-spin text-4xl mr-4"></i>
            <span class="text-2xl font-semibold" id="loading-text">Yükleniyor...</span>
        </div>
    </div>
    <div id="notification-toast" class="hidden fixed bottom-5 right-5 bg-green-500 text-white py-2 px-4 rounded-lg shadow-lg text-sm">
        <p id="notification-text"></p>
    </div>

    <!-- Başarım Kazanıldı Modalı -->
    <div id="achievement-modal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-75 flex items-center justify-center z-50 transition-opacity duration-300 opacity-0">
        <div id="achievement-modal-content" class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl text-center p-8 max-w-sm mx-auto transform scale-95 transition-all duration-300">
            <h2 class="text-2xl font-bold text-yellow-500 mb-2">Başarım Kazanıldı!</h2>
            <div id="achievement-modal-icon-container" class="my-6">
                <!-- Icon JS ile eklenecek -->
            </div>
            <h3 id="achievement-modal-name" class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-2"></h3>
            <p id="achievement-modal-description" class="text-gray-600 dark:text-gray-400 mb-6"></p>
            <button id="achievement-modal-close-btn" class="w-full bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-lg transition-colors">Harika!</button>
        </div>
    </div>

    <!-- Ses Efektleri -->
    <audio id="correct-sound" src="https://actions.google.com/sounds/v1/positive/success.ogg" preload="auto"></audio>
    <audio id="incorrect-sound" src="https://actions.google.com/sounds/v1/negative/failure.ogg" preload="auto"></audio>
    <audio id="timeout-sound" src="https://actions.google.com/sounds/v1/alarms/alarm_clock.ogg" preload="auto"></audio>
    <audio id="achievement-sound" src="https://actions.google.com/sounds/v1/achievements/achievement_bell.ogg" preload="auto"></audio>

    <script src="assets/js/api-handler.js"></script>
    <script src="assets/js/app-data.js"></script>
    <script src="assets/js/app-state.js"></script>
    <script src="assets/js/ui-handler.js"></script>
    <script src="assets/js/auth-handler.js"></script>
    <script src="assets/js/game-handler.js"></script>
    <script src="assets/js/stats-handler.js"></script>
    <script src="assets/js/admin-handler.js"></script>
    <script src="assets/js/settings-handler.js"></script>
    <script src="assets/js/app.js"></script>
</body>

</html>