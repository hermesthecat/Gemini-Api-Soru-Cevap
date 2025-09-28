<?php
// Public profile pages don't require authentication but need session for CSRF token
include 'auth_check.php';
require_once 'config.php';
include 'header.php';

// Get username from URL parameter
$username = $_GET['u'] ?? '';
if (empty($username)) {
    header('Location: index.php');
    exit;
}
?>

    <!-- Ana Konteyner -->
    <div id="app-container" class="container mx-auto px-4 py-8 max-w-6xl">

        <?php include 'nav.php'; ?>

        <!-- Profile Header -->
        <div id="profile-header" class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-8 stat-card">
            <div class="flex flex-col md:flex-row items-center md:items-start space-y-4 md:space-y-0 md:space-x-6">
                <!-- Avatar -->
                <div class="flex-shrink-0">
                    <div id="profile-avatar" class="profile-avatar w-24 h-24 rounded-full flex items-center justify-center text-white text-2xl font-bold">
                        <!-- Username initials will be generated here -->
                    </div>
                </div>

                <!-- User Info -->
                <div class="flex-1 text-center md:text-left">
                    <h1 id="profile-username" class="text-3xl font-bold text-gray-800 dark:text-white mb-2">
                        <!-- Username will be loaded here -->
                    </h1>
                    <div class="flex flex-wrap justify-center md:justify-start gap-4 text-sm text-gray-600 dark:text-gray-400 mb-4">
                        <span id="profile-member-since" class="flex items-center">
                            <i class="fas fa-calendar-alt mr-2"></i>
                            <!-- Member since date -->
                        </span>
                        <span id="profile-global-rank" class="flex items-center">
                            <i class="fas fa-trophy mr-2"></i>
                            <!-- Global rank -->
                        </span>
                        <span id="profile-login-streak" class="flex items-center">
                            <i class="fas fa-fire mr-2"></i>
                            <!-- Login streak -->
                        </span>
                    </div>

                    <!-- Quick Stats -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="text-center stat-card bg-blue-50 dark:bg-blue-900/20 p-3 rounded-lg">
                            <div class="text-blue-600 text-lg mb-1"><i class="fas fa-star"></i></div>
                            <div id="profile-total-score" class="animated-number text-2xl font-bold text-blue-600">0</div>
                            <div class="text-xs text-gray-500">Toplam Puan</div>
                        </div>
                        <div class="text-center stat-card bg-green-50 dark:bg-green-900/20 p-3 rounded-lg">
                            <div class="text-green-600 text-lg mb-1"><i class="fas fa-bullseye"></i></div>
                            <div id="profile-accuracy" class="animated-number text-2xl font-bold text-green-600">0%</div>
                            <div class="text-xs text-gray-500">Doğruluk</div>
                        </div>
                        <div class="text-center stat-card bg-yellow-50 dark:bg-yellow-900/20 p-3 rounded-lg">
                            <div class="text-yellow-600 text-lg mb-1"><i class="fas fa-trophy"></i></div>
                            <div id="profile-achievements" class="animated-number text-2xl font-bold text-yellow-600">0</div>
                            <div class="text-xs text-gray-500">Başarım</div>
                        </div>
                        <div class="text-center stat-card bg-purple-50 dark:bg-purple-900/20 p-3 rounded-lg">
                            <div class="text-purple-600 text-lg mb-1"><i class="fas fa-tasks"></i></div>
                            <div id="profile-quests" class="animated-number text-2xl font-bold text-purple-600">0</div>
                            <div class="text-xs text-gray-500">Görev</div>
                        </div>
                    </div>
                </div>

                <!-- Social Actions -->
                <div class="flex flex-col space-y-3">
                    <!-- Privacy Settings (Own Profile Only) -->
                    <div id="profile-privacy-settings" class="hidden">
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                            <h3 class="text-sm font-medium text-gray-800 dark:text-gray-200 mb-3">Profil Gizliliği</h3>
                            <select id="profile-visibility-select" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm">
                                <option value="public">🌍 Herkese Açık</option>
                                <option value="friends">👥 Sadece Arkadaşlar</option>
                                <option value="private">🔒 Gizli</option>
                            </select>
                        </div>
                    </div>

                    <!-- Social Actions (Other Profiles Only) -->
                    <div id="social-actions" class="hidden">
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                            <h3 class="text-sm font-medium text-gray-800 dark:text-gray-200 mb-3">Sosyal İşlemler</h3>
                            <div class="flex flex-wrap gap-2">
                                <button id="share-profile-btn" class="flex items-center px-3 py-2 bg-blue-500 hover:bg-blue-600 text-white text-sm rounded-md transition-colors">
                                    <i class="fas fa-share mr-2"></i>Paylaş
                                </button>
                                <button id="compare-achievements-btn" class="flex items-center px-3 py-2 bg-purple-500 hover:bg-purple-600 text-white text-sm rounded-md transition-colors">
                                    <i class="fas fa-trophy mr-2"></i>Başarım Karşılaştır
                                </button>
                                <button id="bookmark-profile-btn" class="flex items-center px-3 py-2 bg-yellow-500 hover:bg-yellow-600 text-white text-sm rounded-md transition-colors">
                                    <i class="fas fa-bookmark mr-2"></i>İşaretle
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Loading State -->
        <div id="profile-loading" class="text-center py-12">
            <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-blue-500 mx-auto mb-4"></div>
            <p class="text-gray-600 dark:text-gray-400">Profil yükleniyor...</p>
        </div>

        <!-- Error State -->
        <div id="profile-error" class="hidden bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-6 text-center">
            <i class="fas fa-exclamation-triangle text-red-500 text-3xl mb-4"></i>
            <h3 class="text-lg font-medium text-red-800 dark:text-red-200 mb-2">Profil Yüklenemedi</h3>
            <p id="profile-error-message" class="text-red-600 dark:text-red-300"></p>
            <button id="retry-load-profile" class="mt-4 bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded transition-colors">
                Tekrar Dene
            </button>
        </div>

        <!-- Profile Content -->
        <div id="profile-content" class="hidden space-y-8">
            <!-- Detailed Statistics -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Category Performance -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 stat-card">
                    <h2 class="text-xl font-semibold mb-4 dark:text-white flex items-center">
                        <i class="fas fa-chart-bar mr-3 text-blue-500"></i>
                        Kategori Performansı
                    </h2>
                    <div id="category-performance-container" class="space-y-4">
                        <!-- Category stats will be loaded here -->
                    </div>
                </div>

                <!-- Duel Statistics -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                    <h2 class="text-xl font-semibold mb-4 dark:text-white flex items-center">
                        <i class="fas fa-sword mr-3 text-red-500"></i>
                        Düello İstatistikleri
                    </h2>
                    <div id="duel-stats-container" class="space-y-4">
                        <div class="grid grid-cols-3 gap-4 text-center">
                            <div>
                                <div id="duel-total" class="text-2xl font-bold text-gray-800 dark:text-white">0</div>
                                <div class="text-sm text-gray-500">Toplam</div>
                            </div>
                            <div>
                                <div id="duel-wins" class="text-2xl font-bold text-green-600">0</div>
                                <div class="text-sm text-gray-500">Galibiyet</div>
                            </div>
                            <div>
                                <div id="duel-win-rate" class="text-2xl font-bold text-blue-600">0%</div>
                                <div class="text-sm text-gray-500">Başarı</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Achievement Gallery -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-semibold mb-6 dark:text-white flex items-center">
                    <i class="fas fa-trophy mr-3 text-yellow-500"></i>
                    Başarımlar
                    <span id="achievement-count-badge" class="ml-2 bg-yellow-100 text-yellow-800 text-sm px-2 py-1 rounded-full">0</span>
                </h2>

                <!-- Recent Achievements -->
                <div id="recent-achievements" class="mb-6">
                    <h3 class="text-lg font-medium text-gray-800 dark:text-gray-200 mb-4">Son Kazanılanlar</h3>
                    <div id="recent-achievements-container" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <!-- Recent achievements will be loaded here -->
                    </div>
                </div>

                <!-- View All Achievements Button -->
                <div class="text-center">
                    <button id="view-all-achievements" class="bg-yellow-500 hover:bg-yellow-600 text-white px-6 py-3 rounded-lg transition-colors">
                        <i class="fas fa-eye mr-2"></i>
                        Tüm Başarımları Görüntüle
                    </button>
                </div>
            </div>

            <!-- Profile Visit History (Own Profile Only) -->
            <div id="visit-history-section" class="hidden bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 stat-card">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold dark:text-white flex items-center">
                        <i class="fas fa-eye mr-3 text-green-500"></i>
                        Profil Ziyaretçileri
                    </h2>
                    <button id="load-more-visits-btn" class="text-blue-500 hover:text-blue-600 text-sm">
                        Daha Fazla
                    </button>
                </div>
                <div id="visit-history-container" class="space-y-3">
                    <!-- Visit history will be loaded here -->
                </div>
            </div>

            <!-- Friend Shortcuts Section -->
            <div id="friend-shortcuts-section" class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 stat-card">
                <h2 class="text-xl font-semibold mb-4 dark:text-white flex items-center">
                    <i class="fas fa-users mr-3 text-indigo-500"></i>
                    Arkadaş Kısayolları
                </h2>

                <!-- Bookmarked Friends -->
                <div class="mb-6">
                    <h3 class="text-lg font-medium text-gray-800 dark:text-gray-200 mb-3">İşaretlenen Profiller</h3>
                    <div id="bookmarked-friends-container" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                        <!-- Bookmarked friends will be loaded here -->
                    </div>
                </div>

                <!-- Recent Friends -->
                <div>
                    <h3 class="text-lg font-medium text-gray-800 dark:text-gray-200 mb-3">Son Arkadaşlar</h3>
                    <div id="recent-friends-container" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                        <!-- Recent friends will be loaded here -->
                    </div>
                </div>
            </div>

            <!-- Activity Summary -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-semibold mb-4 dark:text-white flex items-center">
                    <i class="fas fa-activity mr-3 text-green-500"></i>
                    Aktivite Özeti
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="text-center">
                        <div id="total-questions-answered" class="text-3xl font-bold text-blue-600">0</div>
                        <div class="text-sm text-gray-500">Cevaplanan Soru</div>
                    </div>
                    <div class="text-center">
                        <div id="correct-answers" class="text-3xl font-bold text-green-600">0</div>
                        <div class="text-sm text-gray-500">Doğru Cevap</div>
                    </div>
                    <div class="text-center">
                        <div id="longest-streak" class="text-3xl font-bold text-purple-600">0</div>
                        <div class="text-sm text-gray-500">En Uzun Seri</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- User Search Modal -->
        <div id="user-search-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4 hidden opacity-0 transition-opacity duration-300">
            <div id="user-search-modal-content" class="bg-white dark:bg-gray-800 rounded-lg shadow-2xl p-6 w-full max-w-2xl transform scale-95 transition-transform duration-300">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-bold text-gray-800 dark:text-white">Kullanıcı Ara</h2>
                    <button id="user-search-modal-close" class="text-gray-500 hover:text-gray-800 dark:hover:text-white">&times;</button>
                </div>

                <div class="mb-4">
                    <input type="text" id="user-search-input"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                        placeholder="Kullanıcı adı ara...">
                </div>

                <div id="user-search-results" class="space-y-2 max-h-96 overflow-y-auto">
                    <!-- Search results will be loaded here -->
                </div>
            </div>
        </div>

        <!-- Share Profile Modal -->
        <div id="share-profile-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4 hidden opacity-0 transition-opacity duration-300">
            <div id="share-profile-modal-content" class="bg-white dark:bg-gray-800 rounded-lg shadow-2xl p-6 w-full max-w-md transform scale-95 transition-transform duration-300">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-bold text-gray-800 dark:text-white">Profili Paylaş</h2>
                    <button id="share-profile-modal-close" class="text-gray-500 hover:text-gray-800 dark:hover:text-white text-2xl">&times;</button>
                </div>

                <div class="space-y-4">
                    <!-- Share URL -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Profil Linki</label>
                        <div class="flex">
                            <input type="text" id="share-url-input" readonly
                                class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-l-md bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm">
                            <button id="copy-url-btn" class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-r-md transition-colors">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Social Share Buttons -->
                    <div class="grid grid-cols-2 gap-3">
                        <button id="share-whatsapp-btn" class="flex items-center justify-center px-4 py-3 bg-green-500 hover:bg-green-600 text-white rounded-md transition-colors">
                            <i class="fab fa-whatsapp mr-2"></i>WhatsApp
                        </button>
                        <button id="share-twitter-btn" class="flex items-center justify-center px-4 py-3 bg-blue-400 hover:bg-blue-500 text-white rounded-md transition-colors">
                            <i class="fab fa-twitter mr-2"></i>Twitter
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Achievement Comparison Modal -->
        <div id="achievement-comparison-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4 hidden opacity-0 transition-opacity duration-300">
            <div id="achievement-comparison-modal-content" class="bg-white dark:bg-gray-800 rounded-lg shadow-2xl p-6 w-full max-w-6xl max-h-[90vh] overflow-y-auto transform scale-95 transition-transform duration-300">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Başarım Karşılaştırması</h2>
                    <button id="achievement-comparison-modal-close" class="text-gray-500 hover:text-gray-800 dark:hover:text-white text-2xl">&times;</button>
                </div>

                <div id="achievement-comparison-content">
                    <!-- Comparison content will be loaded here -->
                </div>
            </div>
        </div>

        <!-- All Achievements Modal -->
        <div id="all-achievements-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4 hidden opacity-0 transition-opacity duration-300">
            <div id="all-achievements-modal-content" class="bg-white dark:bg-gray-800 rounded-lg shadow-2xl p-6 w-full max-w-4xl max-h-[80vh] overflow-y-auto transform scale-95 transition-transform duration-300">
                <div id="all-achievements-modal-body">
                    <!-- Modal content will be loaded here -->
                </div>
            </div>
        </div>
    </div>

<?php include 'footer.php'; ?>

<!-- Include JavaScript handlers -->
<script src="assets/js/public-profile-handler.js?v=<?php echo time(); ?>"></script>
<script src="assets/js/user-search-handler.js?v=<?php echo time(); ?>"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize public profile handler
    if (window.publicProfileHandler) {
        window.publicProfileHandler.init('<?php echo htmlspecialchars($username, ENT_QUOTES); ?>');
    }

    // Initialize user search functionality
    if (window.userSearchHandler) {
        window.userSearchHandler.init();
    }
});
</script>