<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Bilgi Yarışması</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

                            <!-- Günlük Görevler -->
                            <div id="daily-quests-container" class="mt-6 pt-6 border-t dark:border-gray-700">
                                <h3 class="text-lg font-semibold mb-3 text-center dark:text-white">Günlük Görevler</h3>
                                <div id="daily-quests-list" class="space-y-3">
                                    <!-- Görevler JS ile doldurulacak -->
                                    <p id="daily-quests-loading" class="text-gray-500 dark:text-gray-400 text-center">Görevler yükleniyor...</p>
                                </div>
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
                            <!-- Avatar Seçimi -->
                            <div id="avatar-selection-container" class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                                <h2 class="text-xl font-semibold mb-4 dark:text-white">Avatarını Değiştir</h2>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Yeni bir avatar seçmek için üzerine tıkla.</p>
                                <div id="avatar-grid" class="grid grid-cols-5 gap-4">
                                    <!-- Avatarlar JS ile buraya yüklenecek -->
                                </div>
                            </div>

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

                <!-- Arkadaşlar Sekmesi İçeriği -->
                <div id="arkadaslar-tab" class="main-tab-content hidden">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        <!-- Sol Taraf: Arkadaş Arama ve İstekler -->
                        <div class="space-y-8">
                            <!-- Kullanıcı Arama -->
                            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                                <h2 class="text-xl font-semibold mb-4 dark:text-white">Kullanıcı Bul ve Ekle</h2>
                                <div class="relative">
                                    <input type="text" id="friend-search-input" class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Kullanıcı adı yazın...">
                                    <i class="fas fa-search absolute right-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                </div>
                                <div id="friend-search-results" class="mt-4 space-y-2">
                                    <!-- Arama sonuçları buraya gelecek -->
                                </div>
                            </div>
                            <!-- Gelen İstekler -->
                            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                                <h2 class="text-xl font-semibold mb-4 dark:text-white">Bekleyen Arkadaşlık İstekleri</h2>
                                <div id="pending-requests-list" class="space-y-3">
                                    <!-- İstekler buraya gelecek -->
                                </div>
                                <p id="no-pending-requests" class="text-gray-500 dark:text-gray-400 text-center py-2">Bekleyen istek yok.</p>
                            </div>
                        </div>

                        <!-- Sağ Taraf: Arkadaş Listesi -->
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                            <h2 class="text-xl font-semibold mb-4 dark:text-white">Arkadaşlarım</h2>
                            <div id="friends-list" class="space-y-3">
                                <!-- Arkadaşlar buraya gelecek -->
                            </div>
                            <p id="no-friends" class="text-gray-500 dark:text-gray-400 text-center py-4">Henüz arkadaşın yok.</p>
                        </div>
                    </div>

                    <!-- Meydan Okumalar Bölümü (aynı sekmede, altta) -->
                    <div id="duels-section" class="mt-8 bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                        <h2 class="text-xl font-semibold mb-4 dark:text-white">Düellolarım</h2>
                        <div id="duels-list" class="space-y-4">
                            <!-- Düello listesi buraya gelecek -->
                        </div>
                        <p id="no-duels" class="text-gray-500 dark:text-gray-400 text-center py-4 hidden">Gösterilecek düello yok.</p>
                    </div>
                </div>

                <!-- Mağaza Tab -->
                <div id="magaza-tab" class="hidden p-4 rounded-lg bg-gray-50 dark:bg-gray-800">
                    <h3 class="text-xl font-bold mb-4 text-gray-800 dark:text-gray-200">Joker Mağazası</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">Jetonlarını kullanarak joker satın alabilir ve yarışmada avantaj elde edebilirsin.</p>
                    <div id="shop-items-container" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <!-- Shop items will be rendered here by shop-handler.js -->
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== ADMİN PANELİ EKRANI (Admin giriş yaptığında görünür) ===== -->
        <div id="admin-view" class="hidden p-4 md:p-8">
            <div class="max-w-7xl mx-auto">
                <div class="flex justify-between items-center mb-6">
                    <h1 class="text-3xl font-bold text-gray-800 dark:text-white">Yönetim Paneli</h1>
                    <button id="user-view-btn-admin" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">Oyuncu Görünümüne Geç</button>
                </div>

                <!-- İstatistikler -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <!-- Toplam Kullanıcı -->
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-lg flex items-center space-x-4">
                        <i class="fas fa-users fa-3x text-blue-500"></i>
                        <div>
                            <p class="text-gray-500 dark:text-gray-400">Toplam Kullanıcı</p>
                            <p id="admin-total-users" class="text-2xl font-bold text-gray-900 dark:text-white">0</p>
                        </div>
                    </div>
                    <!-- Toplam Cevaplanan Soru -->
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-lg flex items-center space-x-4">
                        <i class="fas fa-question-circle fa-3x text-green-500"></i>
                        <div>
                            <p class="text-gray-500 dark:text-gray-400">Toplam Cevaplanan Soru</p>
                            <p id="admin-total-questions" class="text-2xl font-bold text-gray-900 dark:text-white">0</p>
                        </div>
                    </div>
                </div>

                <!-- Yönetim Sekmeleri -->
                <div class="mb-4 border-b border-gray-200 dark:border-gray-700">
                    <ul class="flex flex-wrap -mb-px text-sm font-medium text-center" id="admin-tabs">
                        <li class="mr-2">
                            <button class="admin-tab-button inline-block p-4 border-b-2 rounded-t-lg" data-tab="users">Kullanıcı Yönetimi</button>
                        </li>
                        <li class="mr-2">
                            <button class="admin-tab-button inline-block p-4 border-b-2 rounded-t-lg" data-tab="announcements">Duyuru Yönetimi</button>
                        </li>
                        <li class="mr-2">
                            <button class="admin-tab-button inline-block p-4 border-b-2 rounded-t-lg" data-tab="stats">İstatistik Grafikleri</button>
                        </li>
                    </ul>
                </div>

                <!-- Kullanıcı Yönetimi Sekmesi -->
                <div id="admin-users-tab" class="admin-tab-content">
                    <!-- ... existing user management table ... -->
                </div>

                <!-- Duyuru Yönetimi Sekmesi -->
                <div id="admin-announcements-tab" class="admin-tab-content hidden">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        <!-- Yeni Duyuru Formu -->
                        <div class="lg:col-span-1">
                            <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-lg">
                                <h3 class="text-xl font-bold mb-4">Yeni Duyuru Oluştur</h3>
                                <form id="create-announcement-form" class="space-y-4">
                                    <div>
                                        <label for="announcement-title" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Başlık</label>
                                        <input type="text" id="announcement-title" name="title" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700">
                                    </div>
                                    <div>
                                        <label for="announcement-content" class="block text-sm font-medium text-gray-700 dark:text-gray-300">İçerik</label>
                                        <textarea id="announcement-content" name="content" rows="4" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700"></textarea>
                                    </div>
                                    <div>
                                        <label for="announcement-target" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Hedef Grup</label>
                                        <select id="announcement-target" name="target_group" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700">
                                            <option value="all">Tüm Kullanıcılar</option>
                                            <option value="users">Sadece Normal Kullanıcılar</option>
                                            <option value="admins">Sadece Adminler</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label for="announcement-end-date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Bitiş Tarihi</label>
                                        <input type="datetime-local" id="announcement-end-date" name="end_date" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700">
                                    </div>
                                    <button type="submit" class="w-full bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600">Duyuruyu Yayınla</button>
                                </form>
                            </div>
                        </div>

                        <!-- Mevcut Duyurular Listesi -->
                        <div class="lg:col-span-2">
                            <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-lg">
                                <h3 class="text-xl font-bold mb-4">Mevcut Duyurular</h3>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                        <thead class="bg-gray-50 dark:bg-gray-700">
                                            <tr>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Başlık</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hedef</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bitiş Tarihi</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">İşlem</th>
                                            </tr>
                                        </thead>
                                        <tbody id="announcements-list-body" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                            <!-- Duyurular buraya JS ile eklenecek -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- İstatistik Grafikleri Sekmesi -->
                <div id="admin-stats-tab" class="admin-tab-content hidden">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-lg">
                            <h3 class="text-xl font-bold mb-4">En Çok Oynanan Kategoriler</h3>
                            <canvas id="category-chart"></canvas>
                        </div>
                        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-lg">
                            <h3 class="text-xl font-bold mb-4">Zorluğa Göre Cevap Dağılımı</h3>
                            <canvas id="answers-chart"></canvas>
                        </div>
                        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-lg lg:col-span-2">
                            <h3 class="text-xl font-bold mb-4">Son 7 Günlük Yeni Kullanıcı Kayıtları</h3>
                            <canvas id="users-chart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== DÜELLO OYUN EKRANI ===== -->
        <div id="duel-game-view" class="hidden">
            <header class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-4 mb-6">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Düello Rakibi</p>
                        <h2 id="duel-game-opponent-name" class="text-xl font-bold">Rakip Adı</h2>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">İlerleme</p>
                        <p id="duel-game-progress" class="text-xl font-bold text-blue-500">Soru 1 / 5</p>
                    </div>
                </div>
                <div id="duel-game-scores" class="mt-4 pt-4 border-t dark:border-gray-700 flex justify-around text-center">
                    <div>
                        <p id="duel-my-username" class="font-semibold"></p>
                        <p id="duel-my-score" class="text-2xl font-bold text-green-500">0</p>
                    </div>
                </div>
            </header>

            <main id="duel-question-container" class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                <div class="text-gray-700 dark:text-gray-300 mb-4">
                    <h3 class="text-xl font-semibold mb-2 dark:text-white">Soru:</h3>
                    <p id="duel-question-text"></p>
                </div>
                <div id="duel-options-container" class="grid grid-cols-1 md:grid-cols-2 gap-4 items-center">
                    <!-- Düello soru şıkları buraya gelecek -->
                </div>
                <div id="duel-explanation-container" class="hidden mt-6 p-4 bg-blue-50 dark:bg-gray-700/50 border-l-4 border-blue-500">
                    <h4 class="font-bold text-blue-800 dark:text-blue-300 mb-1">Açıklama</h4>
                    <p id="duel-explanation-text" class="text-blue-700 dark:text-blue-400"></p>
                </div>
                <div class="mt-6 text-center">
                    <button id="duel-next-question-btn" class="hidden bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-8 rounded-lg transition-colors">Sıradaki Soru</button>
                </div>
            </main>

            <div id="duel-summary-container" class="hidden bg-white dark:bg-gray-800 rounded-xl shadow-lg p-8 mt-6 text-center">
                <h2 id="duel-summary-title" class="text-3xl font-bold mb-4">Düello Bitti!</h2>
                <div id="duel-summary-icon" class="text-6xl mb-4"></div>
                <p id="duel-summary-text" class="text-lg text-gray-600 dark:text-gray-400 mb-6">Sonuçlar hesaplanıyor...</p>
                <div class="flex justify-around items-center text-2xl font-bold mb-8">
                    <div>
                        <p id="duel-summary-my-name" class="text-lg font-normal mb-1"></p>
                        <p id="duel-summary-my-score" class="text-4xl"></p>
                    </div>
                    <span class="text-gray-400">-vs-</span>
                    <div>
                        <p id="duel-summary-opponent-name" class="text-lg font-normal mb-1"></p>
                        <p id="duel-summary-opponent-score" class="text-4xl"></p>
                    </div>
                </div>
                <button id="duel-back-to-friends-btn" class="bg-purple-500 hover:bg-purple-600 text-white font-bold py-3 px-6 rounded-lg transition-colors">Arkadaşlar Menüsüne Dön</button>
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

    <!-- Düello Başlatma Modalı -->
    <div id="duel-modal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-75 flex items-center justify-center z-50 transition-opacity duration-300 opacity-0">
        <div id="duel-modal-content" class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl p-8 max-w-md mx-auto transform scale-95 transition-all duration-300 w-full">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-100">Meydan Oku</h2>
                <button id="duel-modal-close-btn" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                    <i class="fas fa-times fa-lg"></i>
                </button>
            </div>
            <p class="mb-6 text-gray-600 dark:text-gray-400">
                <strong id="duel-opponent-name" class="text-blue-500"></strong> adlı arkadaşına meydan okumak için bir kategori ve zorluk seç.
            </p>

            <div class="space-y-4">
                <div>
                    <label for="duel-category-select" class="block mb-2 text-sm font-medium dark:text-gray-300">Kategori</label>
                    <select id="duel-category-select" class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <!-- Kategoriler JS ile doldurulacak -->
                    </select>
                </div>
                <div>
                    <label for="duel-difficulty-select" class="block mb-2 text-sm font-medium dark:text-gray-300">Zorluk</label>
                    <select id="duel-difficulty-select" class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="kolay">Kolay</option>
                        <option value="orta" selected>Orta</option>
                        <option value="zor">Zor</option>
                    </select>
                </div>
            </div>

            <div class="mt-8">
                <button id="duel-send-challenge-btn" class="w-full bg-green-500 hover:bg-green-600 text-white font-bold py-3 px-4 rounded-lg transition-colors">
                    <i class="fas fa-paper-plane mr-2"></i>Meydan Okuma Gönder
                </button>
            </div>
        </div>
    </div>

    <!-- Duyuru Modalı -->
    <div id="announcement-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4 hidden opacity-0 transition-opacity duration-300">
        <div id="announcement-modal-content" class="bg-white dark:bg-gray-800 rounded-lg shadow-2xl p-6 w-full max-w-lg transform scale-95 transition-transform duration-300 max-h-[80vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-2xl font-bold text-gray-800 dark:text-white flex items-center"><i class="fas fa-bullhorn mr-3 text-blue-500"></i>Duyurular</h2>
                <button id="announcement-modal-close-btn" class="text-gray-500 hover:text-gray-800 dark:hover:text-white">&times;</button>
            </div>
            <div id="announcement-modal-body" class="space-y-4">
                <!-- Duyuru içerikleri buraya gelecek -->
            </div>
            <div class="mt-6 text-right">
                <button id="announcement-modal-ok-btn" class="bg-blue-500 text-white px-6 py-2 rounded-lg hover:bg-blue-600">Okudum</button>
            </div>
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
    <script src="assets/js/friends-handler.js"></script>
    <script src="assets/js/duel-handler.js"></script>
    <script src="assets/js/quest-handler.js"></script>
    <script src="assets/js/announcement-handler.js"></script>
    <script src="assets/js/app.js"></script>
</body>

</html>