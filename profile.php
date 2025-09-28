<?php
include 'auth_check.php';
include 'header.php';

// Get user's current privacy setting from database
require_once 'config.php';
$current_visibility = 'public'; // default value
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare("SELECT profile_visibility FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result) {
        $current_visibility = $result['profile_visibility'] ?? 'public';
    }
} catch (PDOException $e) {
    // Silent fail, use default
}
?>

    <!-- Ana Konteyner -->
    <div id="app-container" class="container mx-auto px-4 py-8 max-w-4xl">

        <?php include 'nav.php'; ?>
                <!-- Profil Sekmesi İçeriği -->
        <div id="profil-tab" class="main-tab-content">
            <div class="space-y-8">
                <!-- Kişisel İstatistikler -->
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

                <!-- Başarımlar -->
                <div id="achievements-container" class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-semibold dark:text-white">Başarımlar</h2>
                        <button id="compare-achievements-btn" class="bg-purple-500 hover:bg-purple-600 text-white px-4 py-2 rounded-md text-sm flex items-center">
                            <i class="fas fa-trophy mr-2"></i>Arkadaşlarla Karşılaştır
                        </button>
                    </div>
                    <div id="achievements-list" class="space-y-4">
                        <!-- JS ile doldurulacak -->
                    </div>
                    <p id="no-achievements-message" class="text-gray-500 dark:text-gray-400 text-center py-4">Başarımlar yükleniyor...</p>
                </div>

                <!-- Profil Gizlilik Ayarları -->
                <div id="profile-privacy-container" class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                    <h2 class="text-xl font-semibold mb-4 dark:text-white flex items-center">
                        <i class="fas fa-user-shield mr-3 text-blue-500"></i>
                        Profil Gizlilik Ayarları
                    </h2>
                    <div class="space-y-4">
                        <div>
                            <label for="profile-visibility-setting" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Profil Görünürlüğü
                            </label>
                            <select id="profile-visibility-setting" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                                <option value="public" <?php echo $current_visibility === 'public' ? 'selected' : ''; ?>>🌍 Herkese Açık - Herkes profilimi görebilir</option>
                                <option value="friends" <?php echo $current_visibility === 'friends' ? 'selected' : ''; ?>>👥 Sadece Arkadaşlar - Sadece arkadaşlarım görebilir</option>
                                <option value="private" <?php echo $current_visibility === 'private' ? 'selected' : ''; ?>>🔒 Gizli - Sadece ben görebilirim</option>
                            </select>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                Bu ayar profilinizin public-profile.php sayfasındaki görünürlüğünü belirler.
                            </p>
                        </div>

                        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg p-4">
                            <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200 mb-2">📋 Gizlilik Seviyeleri</h3>
                            <ul class="text-xs text-blue-700 dark:text-blue-300 space-y-1">
                                <li><strong>Herkese Açık:</strong> Profiliniz arama sonuçlarında görünür ve herkes görebilir</li>
                                <li><strong>Sadece Arkadaşlar:</strong> Sadece arkadaş listenizdekilerin profilinizi görme izni vardır</li>
                                <li><strong>Gizli:</strong> Profiliniz sadece sizin tarafınızdan görülebilir</li>
                            </ul>
                        </div>

                        <div id="profile-sharing-section" class="border-t border-gray-200 dark:border-gray-700 pt-4">
                            <h3 class="text-lg font-medium text-gray-800 dark:text-gray-200 mb-3">🔗 Profil Paylaşımı</h3>
                            <div class="flex items-center space-x-3">
                                <input type="text" id="profile-share-url" readonly
                                       class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm"
                                       value="">
                                <button id="copy-profile-url-btn" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm">
                                    <i class="fas fa-copy"></i> Kopyala
                                </button>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                Bu link ile arkadaşlarınız profilinizi görüntüleyebilir (gizlilik ayarınıza bağlı olarak).
                            </p>

                            <!-- Sosyal Medya Paylaşım Butonları -->
                            <div class="mt-4 pt-3 border-t border-gray-200 dark:border-gray-700">
                                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">📱 Sosyal Medyada Paylaş</h4>
                                <div class="flex space-x-3">
                                    <button id="share-whatsapp-btn" class="bg-green-500 hover:bg-green-600 text-white px-3 py-2 rounded-md text-sm flex items-center">
                                        <i class="fab fa-whatsapp mr-2"></i> WhatsApp
                                    </button>
                                    <button id="share-twitter-btn" class="bg-blue-400 hover:bg-blue-500 text-white px-3 py-2 rounded-md text-sm flex items-center">
                                        <i class="fab fa-twitter mr-2"></i> Twitter
                                    </button>
                                    <button id="share-facebook-btn" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded-md text-sm flex items-center">
                                        <i class="fab fa-facebook mr-2"></i> Facebook
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- İşaretlenmiş Profiller -->
                <div id="bookmarked-profiles-container" class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-semibold dark:text-white flex items-center">
                            <i class="fas fa-bookmark mr-3 text-yellow-500"></i>
                            İşaretlenmiş Profiller
                        </h2>
                        <button id="refresh-bookmarks-btn" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 transition-colors">
                            <i class="fas fa-sync-alt"></i> Yenile
                        </button>
                    </div>
                    <div id="bookmarked-profiles-list" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <!-- Bookmarked profiles will be loaded here -->
                    </div>
                    <p id="no-bookmarks-message" class="text-gray-500 dark:text-gray-400 text-center py-4">İşaretlenmiş profiller yükleniyor...</p>
                </div>

                <!-- Profil Ziyaretçileri -->
                <div id="profile-visitors-container" class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-semibold dark:text-white flex items-center">
                            <i class="fas fa-eye mr-3 text-green-500"></i>
                            Son Profil Ziyaretçileri
                        </h2>
                        <button id="refresh-visitors-btn" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 transition-colors">
                            <i class="fas fa-sync-alt"></i> Yenile
                        </button>
                    </div>
                    <div id="profile-visitors-list" class="space-y-3">
                        <!-- Visitors will be loaded here -->
                    </div>
                    <p id="no-visitors-message" class="text-gray-500 dark:text-gray-400 text-center py-4">Son ziyaretçiler yükleniyor...</p>
                </div>

                <!-- Görev Geçmişi -->
                <div id="quest-history-container" class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-semibold dark:text-white">Görev Geçmişi</h2>
                        <button id="refresh-quest-history-btn" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 transition-colors">
                            <i class="fas fa-sync-alt"></i> Yenile
                        </button>
                    </div>

                    <!-- Quest İstatistikleri -->
                    <div id="quest-stats-summary" class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6 hidden">
                        <div class="bg-blue-50 dark:bg-blue-900 rounded-lg p-3 text-center">
                            <p class="text-sm text-blue-600 dark:text-blue-400">Toplam</p>
                            <p id="total-completed-quests" class="text-xl font-bold text-blue-800 dark:text-blue-200">0</p>
                        </div>
                        <div class="bg-green-50 dark:bg-green-900 rounded-lg p-3 text-center">
                            <p class="text-sm text-green-600 dark:text-green-400">Toplam Puan</p>
                            <p id="total-quest-points" class="text-xl font-bold text-green-800 dark:text-green-200">0</p>
                        </div>
                        <div class="bg-yellow-50 dark:bg-yellow-900 rounded-lg p-3 text-center">
                            <p class="text-sm text-yellow-600 dark:text-yellow-400">Toplam Jeton</p>
                            <p id="total-quest-coins" class="text-xl font-bold text-yellow-800 dark:text-yellow-200">0</p>
                        </div>
                        <div class="bg-purple-50 dark:bg-purple-900 rounded-lg p-3 text-center">
                            <p class="text-sm text-purple-600 dark:text-purple-400">Ort. Süre</p>
                            <p id="avg-completion-time" class="text-xl font-bold text-purple-800 dark:text-purple-200">-</p>
                        </div>
                    </div>

                    <!-- Quest Type İstatistikleri -->
                    <div id="quest-type-stats" class="mb-6 hidden">
                        <h3 class="text-lg font-semibold mb-3 dark:text-white">Quest Türlerine Göre Performans</h3>
                        <div id="quest-type-stats-container" class="space-y-2">
                            <!-- JS ile doldurulacak -->
                        </div>
                    </div>

                    <!-- Görev Geçmişi Listesi -->
                    <div id="quest-history-list" class="space-y-3">
                        <!-- JS ile doldurulacak -->
                    </div>

                    <!-- Pagination -->
                    <div id="quest-history-pagination" class="flex justify-between items-center mt-6 hidden">
                        <button id="prev-quest-history-btn" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                            <i class="fas fa-chevron-left"></i> Önceki
                        </button>
                        <span id="quest-history-page-info" class="text-gray-600 dark:text-gray-400">Sayfa 1</span>
                        <button id="next-quest-history-btn" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                            Sonraki <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>

                    <!-- Loading ve Placeholder -->
                    <div id="quest-history-loading" class="text-center py-8">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500 mx-auto mb-4"></div>
                        <p class="text-gray-500 dark:text-gray-400">Görev geçmişi yükleniyor...</p>
                    </div>

                    <p id="no-quest-history-message" class="text-gray-500 dark:text-gray-400 text-center py-8 hidden">Henüz tamamlanmış görev bulunmuyor.</p>
                </div>
            </div>
        </div>

    </div>

<script>
// Profile sayfası yüklendiğinde profil içeriğini yükle
document.addEventListener('DOMContentLoaded', () => {
    const profilTab = document.getElementById('profil-tab');
    if (profilTab) {
        profilTab.classList.remove('hidden');
        profilTab.classList.add('block');
    }

    // Stats yükle - window.onload ile tüm scriptlerin yüklenmesini bekle
    window.addEventListener('load', () => {
        const loadStats = () => {
            if (typeof statsHandler !== 'undefined' && typeof api !== 'undefined') {
                console.log('JavaScript modülleri yüklendi, stats güncelleniyor...');
                statsHandler.updateUserData();
                statsHandler.updateCombinedAchievements();
                loadQuestHistory(); // Quest history'yi de yükle
            } else {
                console.log('JavaScript modülleri henüz hazır değil, tekrar deneniyor...');
                setTimeout(loadStats, 200);
            }
        };

        // Biraz gecikme ekle, emin olmak için
        setTimeout(loadStats, 100);
    });

    // Quest History yönetimi
    let currentQuestHistoryOffset = 0;
    const questHistoryLimit = 10;

    const loadQuestHistory = async (offset = 0) => {
        console.log('loadQuestHistory called with offset:', offset);

        const loadingEl = document.getElementById('quest-history-loading');
        const listEl = document.getElementById('quest-history-list');
        const paginationEl = document.getElementById('quest-history-pagination');
        const noDataEl = document.getElementById('no-quest-history-message');
        const statsEl = document.getElementById('quest-stats-summary');
        const typeStatsEl = document.getElementById('quest-type-stats');

        // Check if API is available
        if (typeof api === 'undefined') {
            console.error('API not available for quest history');
            if (noDataEl) {
                noDataEl.textContent = 'API henüz hazır değil, tekrar denenecek...';
                noDataEl.classList.remove('hidden');
            }
            setTimeout(() => loadQuestHistory(offset), 1000);
            return;
        }

        try {
            // Loading göster
            if (loadingEl) loadingEl.classList.remove('hidden');
            if (listEl) listEl.classList.add('hidden');
            if (paginationEl) paginationEl.classList.add('hidden');
            if (noDataEl) noDataEl.classList.add('hidden');

            console.log('Calling API get_user_quest_history...');
            const response = await api.call('get_user_quest_history', {
                limit: questHistoryLimit,
                offset: offset
            }, 'POST', false);

            console.log('API response received:', response);

            if (loadingEl) loadingEl.classList.add('hidden');

            if (response.success && response.data) {
                const { history, pagination, stats, type_stats } = response.data;

                if (history && history.length > 0) {
                    renderQuestHistory(history);
                    renderQuestStats(stats, type_stats);
                    renderQuestPagination(pagination);

                    if (listEl) listEl.classList.remove('hidden');
                    if (paginationEl && pagination.total > questHistoryLimit) paginationEl.classList.remove('hidden');
                    if (statsEl) statsEl.classList.remove('hidden');
                    if (typeStatsEl && type_stats.length > 0) typeStatsEl.classList.remove('hidden');
                } else {
                    if (noDataEl) noDataEl.classList.remove('hidden');
                }
            } else {
                console.error('Quest history API error:', response);
                if (noDataEl) {
                    noDataEl.textContent = `Görev geçmişi hata: ${response.message || 'Bilinmeyen hata'}`;
                    noDataEl.classList.remove('hidden');
                }
            }

        } catch (error) {
            console.error('Quest history error:', error);
            if (loadingEl) loadingEl.classList.add('hidden');
            if (noDataEl) {
                noDataEl.textContent = 'Görev geçmişi yüklenirken hata oluştu.';
                noDataEl.classList.remove('hidden');
            }
        }
    };

    const renderQuestHistory = (history) => {
        const container = document.getElementById('quest-history-list');
        if (!container) return;

        container.innerHTML = '';

        history.forEach(quest => {
            const questEl = document.createElement('div');
            questEl.className = 'border dark:border-gray-700 rounded-lg p-4 bg-gray-50 dark:bg-gray-700';

            const completionPercentage = Math.round((quest.achieved / quest.goal) * 100);
            const typeColor = getQuestTypeColor(quest.quest_type);

            questEl.innerHTML = `
                <div class="flex justify-between items-start mb-2">
                    <div class="flex-1">
                        <h4 class="font-semibold text-gray-800 dark:text-gray-200">${quest.quest_name}</h4>
                        <div class="flex items-center space-x-4 text-sm text-gray-600 dark:text-gray-400 mt-1">
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${typeColor.bg} ${typeColor.text}">
                                ${quest.quest_type_name}
                            </span>
                            <span><i class="fas fa-calendar mr-1"></i>${quest.assigned_date_formatted}</span>
                            <span><i class="fas fa-clock mr-1"></i>${quest.completion_time_formatted}</span>
                        </div>
                    </div>
                    <div class="text-right">
                        <span class="text-sm text-gray-500 dark:text-gray-400">${quest.completed_date_formatted}</span>
                    </div>
                </div>
                <div class="flex justify-between items-center">
                    <div class="flex items-center space-x-4">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            İlerleme: ${quest.achieved}/${quest.goal} (${completionPercentage}%)
                        </span>
                    </div>
                    <div class="flex items-center space-x-2 text-sm">
                        <span class="text-blue-600 dark:text-blue-400">+${quest.reward_points}P</span>
                        <span class="text-yellow-600 dark:text-yellow-400">+${quest.reward_coins}J</span>
                    </div>
                </div>
            `;

            container.appendChild(questEl);
        });
    };

    const renderQuestStats = (stats, typeStats) => {
        if (stats) {
            const totalEl = document.getElementById('total-completed-quests');
            const pointsEl = document.getElementById('total-quest-points');
            const coinsEl = document.getElementById('total-quest-coins');
            const avgTimeEl = document.getElementById('avg-completion-time');

            if (totalEl) totalEl.textContent = stats.total_completed || '0';
            if (pointsEl) pointsEl.textContent = stats.total_points_earned || '0';
            if (coinsEl) coinsEl.textContent = stats.total_coins_earned || '0';
            if (avgTimeEl) {
                if (stats.avg_completion_time) {
                    const avgMinutes = Math.round(stats.avg_completion_time);
                    const hours = Math.floor(avgMinutes / 60);
                    const minutes = avgMinutes % 60;
                    avgTimeEl.textContent = hours > 0 ? `${hours}h ${minutes}m` : `${minutes}m`;
                } else {
                    avgTimeEl.textContent = '-';
                }
            }
        }

        if (typeStats && typeStats.length > 0) {
            const container = document.getElementById('quest-type-stats-container');
            if (!container) return;

            container.innerHTML = '';

            typeStats.forEach(type => {
                const typeEl = document.createElement('div');
                typeEl.className = 'flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-600 rounded-lg';

                const typeColor = getQuestTypeColor(type.quest_type);
                const avgTime = type.avg_time ? Math.round(type.avg_time) : 0;

                typeEl.innerHTML = `
                    <div class="flex items-center space-x-3">
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${typeColor.bg} ${typeColor.text}">
                            ${type.type_name}
                        </span>
                        <span class="text-sm text-gray-600 dark:text-gray-400">${type.count} quest</span>
                    </div>
                    <div class="flex items-center space-x-4 text-sm">
                        <span class="text-blue-600 dark:text-blue-400">${type.total_points}P</span>
                        <span class="text-purple-600 dark:text-purple-400">${avgTime}m ort.</span>
                    </div>
                `;

                container.appendChild(typeEl);
            });
        }
    };

    const renderQuestPagination = (pagination) => {
        const prevBtn = document.getElementById('prev-quest-history-btn');
        const nextBtn = document.getElementById('next-quest-history-btn');
        const pageInfo = document.getElementById('quest-history-page-info');

        if (!prevBtn || !nextBtn || !pageInfo) return;

        const currentPage = Math.floor(pagination.offset / questHistoryLimit) + 1;
        const totalPages = Math.ceil(pagination.total / questHistoryLimit);

        pageInfo.textContent = `Sayfa ${currentPage} / ${totalPages}`;

        prevBtn.disabled = pagination.offset === 0;
        nextBtn.disabled = !pagination.has_more;

        prevBtn.onclick = () => {
            if (pagination.offset > 0) {
                currentQuestHistoryOffset = Math.max(0, pagination.offset - questHistoryLimit);
                loadQuestHistory(currentQuestHistoryOffset);
            }
        };

        nextBtn.onclick = () => {
            if (pagination.has_more) {
                currentQuestHistoryOffset = pagination.offset + questHistoryLimit;
                loadQuestHistory(currentQuestHistoryOffset);
            }
        };
    };

    const getQuestTypeColor = (type) => {
        switch (type) {
            case 'solve_category':
                return { bg: 'bg-blue-100', text: 'text-blue-800' };
            case 'solve_difficulty':
                return { bg: 'bg-purple-100', text: 'text-purple-800' };
            case 'consecutive_days':
                return { bg: 'bg-orange-100', text: 'text-orange-800' };
            case 'win_duels':
                return { bg: 'bg-red-100', text: 'text-red-800' };
            default:
                return { bg: 'bg-gray-100', text: 'text-gray-800' };
        }
    };

    // Event listener'ları ekle
    document.addEventListener('DOMContentLoaded', () => {
        const refreshBtn = document.getElementById('refresh-quest-history-btn');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => {
                currentQuestHistoryOffset = 0;
                loadQuestHistory(0);
            });
        }

        // Profile privacy settings
        initializeProfilePrivacySettings();

        // Achievement comparison functionality
        initializeAchievementComparison();

        // Bookmarked profiles functionality
        initializeBookmarkedProfiles();

        // Profile visitors functionality
        initializeProfileVisitors();
    });

    // Initialize profile privacy settings
    const initializeProfilePrivacySettings = async () => {
        const visibilitySelect = document.getElementById('profile-visibility-setting');
        const shareUrlInput = document.getElementById('profile-share-url');
        const copyBtn = document.getElementById('copy-profile-url-btn');

        if (!visibilitySelect || !shareUrlInput || !copyBtn) return;

        let currentUsername = '';

        try {
            // Get current user data to populate settings
            const response = await api.call('get_user_data', {}, 'POST', false);
            if (response.success && response.data.user) {
                const currentVisibility = response.data.user.profile_visibility || 'public';
                visibilitySelect.value = currentVisibility;

                // Set profile share URL
                currentUsername = response.data.user.username;
                const baseUrl = window.location.origin;
                const path = window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/'));
                const profileUrl = baseUrl + path + '/public-profile.php?u=' + currentUsername;
                shareUrlInput.value = profileUrl;
            }
        } catch (error) {
            console.error('Error loading profile privacy settings:', error);
        }

        // Handle visibility change
        visibilitySelect.addEventListener('change', async () => {
            try {
                const result = await api.call('update_profile_visibility', {
                    visibility: visibilitySelect.value
                }, 'POST', true);

                if (result.success) {
                    const UICore = ModuleLoader?.getModule('UICore');
                    if (UICore) {
                        UICore.showToast('Profil gizlilik ayarı güncellendi! 🔒', 'success');
                    } else {
                        alert('Profil gizlilik ayarı güncellendi!');
                    }
                } else {
                    const UICore = ModuleLoader?.getModule('UICore');
                    if (UICore) {
                        UICore.showToast(result.message || 'Güncelleme başarısız', 'error');
                    } else {
                        alert(result.message || 'Güncelleme başarısız');
                    }
                    // Revert selection on error
                    const response = await api.call('get_user_data', {}, 'POST', false);
                    if (response.success && response.data.user) {
                        visibilitySelect.value = response.data.user.profile_visibility || 'public';
                    }
                }
            } catch (error) {
                console.error('Privacy update error:', error);
                const UICore = ModuleLoader?.getModule('UICore');
                if (UICore) {
                    UICore.showToast('Bağlantı hatası', 'error');
                } else {
                    alert('Bağlantı hatası');
                }
            }
        });

        // Handle copy URL button
        copyBtn.addEventListener('click', async () => {
            try {
                await navigator.clipboard.writeText(shareUrlInput.value);
                const UICore = ModuleLoader?.getModule('UICore');
                if (UICore) {
                    UICore.showToast('Profil linki kopyalandı! 📋', 'success');
                } else {
                    alert('Profil linki kopyalandı!');
                }
            } catch (error) {
                console.error('Error copying URL:', error);
                // Fallback for older browsers
                shareUrlInput.select();
                document.execCommand('copy');
                const UICore = ModuleLoader?.getModule('UICore');
                if (UICore) {
                    UICore.showToast('Profil linki kopyalandı! 📋', 'success');
                } else {
                    alert('Profil linki kopyalandı!');
                }
            }
        });

        // Handle social media sharing buttons
        const whatsappBtn = document.getElementById('share-whatsapp-btn');
        const twitterBtn = document.getElementById('share-twitter-btn');
        const facebookBtn = document.getElementById('share-facebook-btn');

        if (whatsappBtn) {
            whatsappBtn.addEventListener('click', () => {
                const message = `${currentUsername} adlı oyuncunun profilini incele! AI Quiz oyununda başarılarını gör 🏆`;
                const whatsappUrl = `https://wa.me/?text=${encodeURIComponent(message + ' ' + shareUrlInput.value)}`;
                window.open(whatsappUrl, '_blank');
            });
        }

        if (twitterBtn) {
            twitterBtn.addEventListener('click', () => {
                const message = `${currentUsername} adlı oyuncunun AI Quiz profilini incele! 🎯`;
                const twitterUrl = `https://twitter.com/intent/tweet?text=${encodeURIComponent(message)}&url=${encodeURIComponent(shareUrlInput.value)}`;
                window.open(twitterUrl, '_blank');
            });
        }

        if (facebookBtn) {
            facebookBtn.addEventListener('click', () => {
                const facebookUrl = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(shareUrlInput.value)}`;
                window.open(facebookUrl, '_blank');
            });
        }
    };

    // Initialize achievement comparison functionality
    const initializeAchievementComparison = () => {
        const compareBtn = document.getElementById('compare-achievements-btn');
        const modal = document.getElementById('achievement-comparison-modal');
        const modalContent = document.getElementById('achievement-comparison-modal-content');
        const modalClose = document.getElementById('achievement-comparison-modal-close');

        if (compareBtn) {
            compareBtn.addEventListener('click', showAchievementComparison);
        }

        if (modalClose) {
            modalClose.addEventListener('click', hideAchievementComparison);
        }

        if (modal) {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    hideAchievementComparison();
                }
            });
        }
    };

    // Show achievement comparison modal
    const showAchievementComparison = async () => {
        try {
            const modal = document.getElementById('achievement-comparison-modal');
            const modalContent = document.getElementById('achievement-comparison-modal-content');
            const contentDiv = document.getElementById('achievement-comparison-content');

            if (!modal || !modalContent || !contentDiv) return;

            // Show loading
            contentDiv.innerHTML = `
                <div class="flex items-center justify-center py-12">
                    <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-purple-500"></div>
                    <span class="ml-3 text-gray-600 dark:text-gray-400">Arkadaş listesi yükleniyor...</span>
                </div>
            `;

            // Show modal
            modal.classList.remove('hidden');
            setTimeout(() => {
                modal.classList.remove('opacity-0');
                modalContent.classList.remove('scale-95');
            }, 10);

            // Get friends list for comparison
            const response = await api.call('get_friends', {}, 'POST', false);

            if (response.success && response.data.length > 0) {
                displayFriendsForComparison(response.data);
            } else {
                contentDiv.innerHTML = `
                    <div class="text-center py-12">
                        <i class="fas fa-users text-gray-400 text-3xl mb-4"></i>
                        <p class="text-gray-600 dark:text-gray-400 mb-4">Başarımları karşılaştırmak için arkadaşlarınız olması gerekiyor.</p>
                        <button onclick="window.location.href='friends.php'" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md">
                            Arkadaş Ekle
                        </button>
                    </div>
                `;
            }
        } catch (error) {
            console.error('Error showing achievement comparison:', error);
            const UICore = ModuleLoader?.getModule('UICore');
            if (UICore) {
                UICore.showToast('Başarım karşılaştırması yapılamadı', 'error');
            }
        }
    };

    // Display friends for comparison selection
    const displayFriendsForComparison = (friends) => {
        const contentDiv = document.getElementById('achievement-comparison-content');
        if (!contentDiv) return;

        const friendsList = friends.map(friend => `
            <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg flex items-center justify-between hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center mr-3">
                        <span class="text-white font-semibold">${friend.username.charAt(0).toUpperCase()}</span>
                    </div>
                    <div>
                        <h4 class="font-medium text-gray-900 dark:text-white">${friend.username}</h4>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Toplam puan: ${friend.total_score || 0}</p>
                    </div>
                </div>
                <button onclick="compareAchievementsWith('${friend.username}')"
                        class="bg-purple-500 hover:bg-purple-600 text-white px-4 py-2 rounded-md text-sm">
                    Karşılaştır
                </button>
            </div>
        `).join('');

        contentDiv.innerHTML = `
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Karşılaştırmak istediğiniz arkadaşınızı seçin:</h3>
                <div class="space-y-3">
                    ${friendsList}
                </div>
            </div>
        `;
    };

    // Compare achievements with specific friend
    window.compareAchievementsWith = async (friendUsername) => {
        try {
            const contentDiv = document.getElementById('achievement-comparison-content');

            // Show loading
            contentDiv.innerHTML = `
                <div class="flex items-center justify-center py-12">
                    <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-purple-500"></div>
                    <span class="ml-3 text-gray-600 dark:text-gray-400">Başarımlar karşılaştırılıyor...</span>
                </div>
            `;

            // Get comparison data
            const response = await api.call('compare_achievements', {
                username: friendUsername
            }, 'POST', true);

            if (response.success) {
                displayAchievementComparison(response.data, friendUsername);
            } else {
                contentDiv.innerHTML = `
                    <div class="text-center py-12">
                        <i class="fas fa-exclamation-triangle text-red-500 text-3xl mb-4"></i>
                        <p class="text-red-600 dark:text-red-400">${response.message}</p>
                        <button onclick="showAchievementComparison()" class="mt-4 bg-purple-500 hover:bg-purple-600 text-white px-4 py-2 rounded-md">
                            Geri Dön
                        </button>
                    </div>
                `;
            }
        } catch (error) {
            console.error('Error comparing achievements:', error);
            const UICore = ModuleLoader?.getModule('UICore');
            if (UICore) {
                UICore.showToast('Karşılaştırma başarısız', 'error');
            }
        }
    };

    // Display achievement comparison results
    const displayAchievementComparison = (data, friendUsername) => {
        const contentDiv = document.getElementById('achievement-comparison-content');
        if (!contentDiv) return;

        const html = `
            <div class="space-y-6">
                <!-- Back button -->
                <button onclick="showAchievementComparison()" class="text-purple-600 hover:text-purple-800 dark:text-purple-400 dark:hover:text-purple-300 flex items-center">
                    <i class="fas fa-arrow-left mr-2"></i>Arkadaş Listesine Dön
                </button>

                <!-- Comparison Summary -->
                <div class="bg-gradient-to-r from-blue-50 to-purple-50 dark:from-blue-900/20 dark:to-purple-900/20 border border-blue-200 dark:border-blue-700 rounded-lg p-6">
                    <div class="grid grid-cols-2 gap-6">
                        <div class="text-center">
                            <h3 class="text-lg font-semibold text-blue-600 mb-2">${data.user1.username} (Sen)</h3>
                            <div class="space-y-2">
                                <p class="text-2xl font-bold text-blue-700">${data.user1.achievement_count}</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Toplam Başarım</p>
                            </div>
                        </div>
                        <div class="text-center">
                            <h3 class="text-lg font-semibold text-purple-600 mb-2">${data.user2.username}</h3>
                            <div class="space-y-2">
                                <p class="text-2xl font-bold text-purple-700">${data.user2.achievement_count}</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Toplam Başarım</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Detailed Comparison -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    ${data.achievements.map(achievement => `
                        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                            <div class="flex items-center mb-3">
                                <span class="text-2xl mr-3">${achievement.icon}</span>
                                <div class="flex-1">
                                    <h4 class="font-medium text-gray-900 dark:text-white">${achievement.name}</h4>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">${achievement.description}</p>
                                </div>
                            </div>
                            <div class="flex justify-between">
                                <div class="flex items-center">
                                    <span class="text-sm font-medium mr-2">Sen:</span>
                                    ${achievement.user1_has ?
                                        '<span class="text-green-600 dark:text-green-400"><i class="fas fa-check"></i> Kazanıldı</span>' :
                                        '<span class="text-gray-400"><i class="fas fa-times"></i> Kazanılmadı</span>'
                                    }
                                </div>
                                <div class="flex items-center">
                                    <span class="text-sm font-medium mr-2">${friendUsername}:</span>
                                    ${achievement.user2_has ?
                                        '<span class="text-green-600 dark:text-green-400"><i class="fas fa-check"></i> Kazanıldı</span>' :
                                        '<span class="text-gray-400"><i class="fas fa-times"></i> Kazanılmadı</span>'
                                    }
                                </div>
                            </div>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;

        contentDiv.innerHTML = html;
    };

    // Hide achievement comparison modal
    const hideAchievementComparison = () => {
        const modal = document.getElementById('achievement-comparison-modal');
        const modalContent = document.getElementById('achievement-comparison-modal-content');

        if (modal && modalContent) {
            modal.classList.add('opacity-0');
            modalContent.classList.add('scale-95');

            setTimeout(() => {
                modal.classList.add('hidden');
            }, 300);
        }
    };

    // Initialize bookmarked profiles functionality
    const initializeBookmarkedProfiles = () => {
        const refreshBtn = document.getElementById('refresh-bookmarks-btn');

        if (refreshBtn) {
            refreshBtn.addEventListener('click', loadBookmarkedProfiles);
        }

        // Load bookmarked profiles on initialization
        loadBookmarkedProfiles();
    };

    // Load bookmarked profiles
    const loadBookmarkedProfiles = async () => {
        try {
            const listContainer = document.getElementById('bookmarked-profiles-list');
            const noMessage = document.getElementById('no-bookmarks-message');

            if (!listContainer || !noMessage) return;

            // Show loading
            noMessage.textContent = 'İşaretlenmiş profiller yükleniyor...';
            noMessage.style.display = 'block';
            listContainer.innerHTML = '';

            // Get bookmarked profiles
            const response = await api.call('get_profile_bookmarks', {}, 'POST', false);

            if (response.success && response.data.length > 0) {
                displayBookmarkedProfiles(response.data);
                noMessage.style.display = 'none';
            } else {
                noMessage.textContent = 'Henüz işaretlenmiş profil yok. Arkadaşlarınızın profillerini ziyaret ederek onları işaretleyebilirsiniz.';
                noMessage.style.display = 'block';
            }
        } catch (error) {
            console.error('Error loading bookmarked profiles:', error);
            const noMessage = document.getElementById('no-bookmarks-message');
            if (noMessage) {
                noMessage.textContent = 'İşaretlenmiş profiller yüklenirken hata oluştu.';
                noMessage.style.display = 'block';
            }
        }
    };

    // Display bookmarked profiles
    const displayBookmarkedProfiles = (bookmarks) => {
        const listContainer = document.getElementById('bookmarked-profiles-list');
        if (!listContainer) return;

        const bookmarkCards = bookmarks.map(bookmark => `
            <div class="bg-gradient-to-br from-yellow-50 to-orange-50 dark:from-yellow-900/20 dark:to-orange-900/20 border border-yellow-200 dark:border-yellow-700 rounded-lg p-4 hover:shadow-md transition-shadow cursor-pointer"
                 onclick="window.location.href='public-profile.php?u=${bookmark.username}'">
                <div class="flex items-center space-x-3 mb-3">
                    <div class="w-12 h-12 bg-gradient-to-br from-yellow-500 to-orange-600 rounded-full flex items-center justify-center text-white font-bold text-lg">
                        ${bookmark.username.charAt(0).toUpperCase()}
                    </div>
                    <div class="flex-1 min-w-0">
                        <h4 class="font-semibold text-gray-800 dark:text-gray-200 truncate">${bookmark.bookmark_name || bookmark.username}</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400">@${bookmark.username}</p>
                    </div>
                    <div class="flex items-center text-yellow-500">
                        <i class="fas fa-bookmark"></i>
                    </div>
                </div>
                <div class="space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400">Toplam Puan:</span>
                        <span class="font-medium text-gray-800 dark:text-gray-200">${formatNumber(bookmark.total_score || 0)}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400">Başarımlar:</span>
                        <span class="font-medium text-gray-800 dark:text-gray-200">${bookmark.achievement_count || 0}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400">İşaretlendi:</span>
                        <span class="font-medium text-gray-800 dark:text-gray-200">${formatDate(bookmark.created_at)}</span>
                    </div>
                </div>
                <div class="mt-3 pt-3 border-t border-yellow-200 dark:border-yellow-700 flex space-x-2">
                    <button onclick="event.stopPropagation(); removeBookmark('${bookmark.username}')"
                            class="flex-1 bg-red-500 hover:bg-red-600 text-white text-xs px-3 py-1 rounded transition-colors">
                        <i class="fas fa-bookmark-remove mr-1"></i>İşaret Kaldır
                    </button>
                    <button onclick="event.stopPropagation(); window.location.href='public-profile.php?u=${bookmark.username}'"
                            class="flex-1 bg-blue-500 hover:bg-blue-600 text-white text-xs px-3 py-1 rounded transition-colors">
                        <i class="fas fa-eye mr-1"></i>Profili Gör
                    </button>
                </div>
            </div>
        `).join('');

        listContainer.innerHTML = bookmarkCards;
    };

    // Remove bookmark
    window.removeBookmark = async (username) => {
        try {
            const result = await api.call('remove_profile_bookmark', {
                username: username
            }, 'POST', true);

            if (result.success) {
                const UICore = ModuleLoader?.getModule('UICore');
                if (UICore) {
                    UICore.showToast('İşaret kaldırıldı! 📋', 'success');
                } else {
                    alert('İşaret kaldırıldı!');
                }
                // Reload bookmarks
                loadBookmarkedProfiles();
            } else {
                const UICore = ModuleLoader?.getModule('UICore');
                if (UICore) {
                    UICore.showToast(result.message || 'İşaret kaldırılamadı', 'error');
                } else {
                    alert(result.message || 'İşaret kaldırılamadı');
                }
            }
        } catch (error) {
            console.error('Error removing bookmark:', error);
            const UICore = ModuleLoader?.getModule('UICore');
            if (UICore) {
                UICore.showToast('Bağlantı hatası', 'error');
            } else {
                alert('Bağlantı hatası');
            }
        }
    };

    // Utility function for formatting numbers
    const formatNumber = (num) => {
        if (num >= 1000000) {
            return (num / 1000000).toFixed(1) + 'M';
        } else if (num >= 1000) {
            return (num / 1000).toFixed(1) + 'K';
        }
        return num.toString();
    };

    // Utility function for formatting dates
    const formatDate = (dateString) => {
        if (!dateString) return 'Bilinmiyor';
        const date = new Date(dateString);
        return date.toLocaleDateString('tr-TR', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    };

    // Initialize profile visitors functionality
    const initializeProfileVisitors = () => {
        const refreshBtn = document.getElementById('refresh-visitors-btn');

        if (refreshBtn) {
            refreshBtn.addEventListener('click', loadProfileVisitors);
        }

        // Load profile visitors on initialization
        loadProfileVisitors();
    };

    // Load profile visitors
    const loadProfileVisitors = async () => {
        try {
            const listContainer = document.getElementById('profile-visitors-list');
            const noMessage = document.getElementById('no-visitors-message');

            if (!listContainer || !noMessage) return;

            // Show loading
            noMessage.textContent = 'Son ziyaretçiler yükleniyor...';
            noMessage.style.display = 'block';
            listContainer.innerHTML = '';

            // Get profile visitors
            const response = await api.call('get_profile_visitors', {}, 'POST', false);

            if (response.success && response.data.length > 0) {
                displayProfileVisitors(response.data);
                noMessage.style.display = 'none';
            } else {
                noMessage.textContent = 'Henüz profilinizi ziyaret eden yok. Profil bağlantınızı arkadaşlarınızla paylaşabilirsiniz.';
                noMessage.style.display = 'block';
            }
        } catch (error) {
            console.error('Error loading profile visitors:', error);
            const noMessage = document.getElementById('no-visitors-message');
            if (noMessage) {
                noMessage.textContent = 'Ziyaretçiler yüklenirken hata oluştu.';
                noMessage.style.display = 'block';
            }
        }
    };

    // Display profile visitors
    const displayProfileVisitors = (visitors) => {
        const listContainer = document.getElementById('profile-visitors-list');
        if (!listContainer) return;

        const visitorItems = visitors.map((visitor, index) => {
            const timeAgo = getTimeAgo(visitor.visit_time);
            const isRecent = index < 3; // Mark first 3 as recent

            return `
                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors ${isRecent ? 'border-l-4 border-green-500' : ''}">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-blue-600 rounded-full flex items-center justify-center text-white font-bold">
                            ${visitor.visitor_username.charAt(0).toUpperCase()}
                        </div>
                        <div>
                            <h4 class="font-medium text-gray-900 dark:text-white">
                                ${visitor.visitor_username}
                                ${isRecent ? '<span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">Yeni</span>' : ''}
                            </h4>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                <i class="fas fa-clock mr-1"></i>${timeAgo}
                                ${visitor.visit_count > 1 ? ` • ${visitor.visit_count} kez ziyaret` : ' • İlk ziyaret'}
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2">
                        <button onclick="window.location.href='public-profile.php?u=${visitor.visitor_username}'"
                                class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-sm transition-colors">
                            <i class="fas fa-eye mr-1"></i>Profili Gör
                        </button>
                        ${visitor.is_friend ?
                            '<span class="text-green-600 dark:text-green-400 text-sm"><i class="fas fa-user-friends"></i> Arkadaş</span>' :
                            `<button onclick="sendFriendRequest('${visitor.visitor_username}')" class="bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded text-sm transition-colors">
                                <i class="fas fa-user-plus mr-1"></i>Arkadaş Ekle
                            </button>`
                        }
                    </div>
                </div>
            `;
        }).join('');

        listContainer.innerHTML = visitorItems;
    };

    // Send friend request to visitor
    window.sendFriendRequest = async (username) => {
        try {
            const result = await api.call('send_friend_request', {
                to_username: username
            }, 'POST', true);

            if (result.success) {
                const UICore = ModuleLoader?.getModule('UICore');
                if (UICore) {
                    UICore.showToast(`${username} kullanıcısına arkadaşlık isteği gönderildi! 👥`, 'success');
                } else {
                    alert(`${username} kullanıcısına arkadaşlık isteği gönderildi!`);
                }
                // Reload visitors to update friend status
                loadProfileVisitors();
            } else {
                const UICore = ModuleLoader?.getModule('UICore');
                if (UICore) {
                    UICore.showToast(result.message || 'Arkadaşlık isteği gönderilemedi', 'error');
                } else {
                    alert(result.message || 'Arkadaşlık isteği gönderilemedi');
                }
            }
        } catch (error) {
            console.error('Error sending friend request:', error);
            const UICore = ModuleLoader?.getModule('UICore');
            if (UICore) {
                UICore.showToast('Bağlantı hatası', 'error');
            } else {
                alert('Bağlantı hatası');
            }
        }
    };

    // Utility function for time ago formatting
    const getTimeAgo = (dateString) => {
        if (!dateString) return 'Bilinmiyor';

        const now = new Date();
        const visitTime = new Date(dateString);
        const diffMs = now - visitTime;
        const diffMins = Math.floor(diffMs / 60000);
        const diffHours = Math.floor(diffMs / 3600000);
        const diffDays = Math.floor(diffMs / 86400000);

        if (diffMins < 1) {
            return 'Az önce';
        } else if (diffMins < 60) {
            return `${diffMins} dakika önce`;
        } else if (diffHours < 24) {
            return `${diffHours} saat önce`;
        } else if (diffDays < 7) {
            return `${diffDays} gün önce`;
        } else {
            return visitTime.toLocaleDateString('tr-TR', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        }
    };
});
</script>

<!-- Achievement Comparison Modal -->
<div id="achievement-comparison-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4 hidden opacity-0 transition-opacity duration-300">
    <div id="achievement-comparison-modal-content" class="bg-white dark:bg-gray-800 rounded-lg shadow-2xl p-6 w-full max-w-6xl max-h-[90vh] overflow-y-auto transform scale-95 transition-transform duration-300">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-800 dark:text-white">🏆 Başarım Karşılaştırması</h2>
            <button id="achievement-comparison-modal-close" class="text-gray-500 hover:text-gray-800 dark:hover:text-white text-2xl">&times;</button>
        </div>

        <div id="achievement-comparison-content">
            <!-- Comparison content will be loaded here -->
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>

<!-- Include User Search Handler -->
<script src="assets/js/user-search-handler.js?v=<?php echo time(); ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize user search functionality
    if (window.userSearchHandler) {
        window.userSearchHandler.init();
    }

    // Populate share URL immediately with PHP session username
    const shareUrlInput = document.getElementById('profile-share-url');
    if (shareUrlInput) {
        const username = '<?php echo $_SESSION['username'] ?? ''; ?>';
        if (username) {
            const baseUrl = window.location.origin;
            const path = window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/'));
            const profileUrl = baseUrl + path + '/public-profile.php?u=' + username;
            shareUrlInput.value = profileUrl;
        }
    }

    // Handle profile visibility change
    const visibilitySelect = document.getElementById('profile-visibility-setting');
    if (visibilitySelect) {
        // Load current setting on page load
        loadCurrentVisibilitySetting();

        // Save setting on change
        visibilitySelect.addEventListener('change', async function() {
            const visibility = this.value;

            try {
                const response = await api.call('update_profile_visibility', {
                    visibility: visibility
                }, 'POST', false);

                if (response.success) {
                    // Show success notification with toast
                    const UICore = window.ModuleLoader?.getModule('UICore');
                    if (UICore && UICore.showToast) {
                        UICore.showToast('Profil gizlilik ayarı güncellendi! 🔒', 'success');
                    } else if (window.ui && window.ui.showToast) {
                        window.ui.showToast('Profil gizlilik ayarı güncellendi! 🔒', 'success');
                    } else {
                        alert('Profil gizlilik ayarı güncellendi!');
                    }
                } else {
                    // Show error and revert
                    const UICore = window.ModuleLoader?.getModule('UICore');
                    if (UICore && UICore.showToast) {
                        UICore.showToast(response.message || 'Ayar güncellenemedi', 'error');
                    } else if (window.ui && window.ui.showToast) {
                        window.ui.showToast(response.message || 'Ayar güncellenemedi', 'error');
                    } else {
                        alert(response.message || 'Ayar güncellenemedi');
                    }
                    // Reload current setting
                    loadCurrentVisibilitySetting();
                }
            } catch (error) {
                console.error('Visibility update error:', error);
                if (window.ui && window.ui.showToast) {
                    window.ui.showToast('Bir hata oluştu', 'error');
                }
                // Reload current setting
                loadCurrentVisibilitySetting();
            }
        });
    }

    // Function to load current visibility setting
    async function loadCurrentVisibilitySetting() {
        try {
            const response = await api.call('get_profile_data', {}, 'POST', false);

            if (response.success && response.user) {
                const visibility = response.user.profile_visibility || 'public';
                if (visibilitySelect) {
                    visibilitySelect.value = visibility;
                }

                // Also populate the share URL
                const shareUrlInput = document.getElementById('profile-share-url');
                if (shareUrlInput && response.user.username) {
                    const baseUrl = window.location.origin;
                    const path = window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/'));
                    const profileUrl = baseUrl + path + '/public-profile.php?u=' + response.user.username;
                    shareUrlInput.value = profileUrl;
                }
            }
        } catch (error) {
            console.error('Error loading visibility setting:', error);
        }
    }
});
</script>