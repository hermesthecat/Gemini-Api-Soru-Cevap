<?php
include 'auth_check.php';
include 'header.php';
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
                    <h2 class="text-xl font-semibold mb-4 dark:text-white">Başarımlar</h2>
                    <div id="achievements-list" class="space-y-4">
                        <!-- JS ile doldurulacak -->
                    </div>
                    <p id="no-achievements-message" class="text-gray-500 dark:text-gray-400 text-center py-4">Başarımlar yükleniyor...</p>
                </div>

                <!-- Quest Geçmişi -->
                <div id="quest-history-container" class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-semibold dark:text-white">Quest Geçmişi</h2>
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

                    <!-- Quest Geçmişi Listesi -->
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
                        <p class="text-gray-500 dark:text-gray-400">Quest geçmişi yükleniyor...</p>
                    </div>

                    <p id="no-quest-history-message" class="text-gray-500 dark:text-gray-400 text-center py-8 hidden">Henüz tamamlanmış quest bulunmuyor.</p>
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
                    noDataEl.textContent = `Quest geçmişi hata: ${response.message || 'Bilinmeyen hata'}`;
                    noDataEl.classList.remove('hidden');
                }
            }

        } catch (error) {
            console.error('Quest history error:', error);
            if (loadingEl) loadingEl.classList.add('hidden');
            if (noDataEl) {
                noDataEl.textContent = 'Quest geçmişi yüklenirken hata oluştu.';
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
    });
});
</script>

<?php include 'footer.php'; ?>