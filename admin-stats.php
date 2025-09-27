<?php
include 'auth_check.php';

// Admin kontrolü
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

include 'header.php';
?>

    <!-- Ana Konteyner -->
    <div id="app-container" class="container mx-auto px-4 py-8 max-w-4xl">

        <?php include 'nav.php'; ?>

        <!-- Admin İstatistik Grafikleri -->
        <div id="admin-stats-tab" class="admin-tab-content">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-gray-800 dark:text-white">İstatistik Grafikleri</h1>
            </div>

            <div class="space-y-8">
                <!-- İstatistik Kartları -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
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

                <!-- En Çok Oynanan Kategoriler -->
                <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-lg">
                    <h3 class="text-xl font-bold mb-4">En Çok Oynanan Kategoriler</h3>
                    <canvas id="category-chart"></canvas>
                </div>

                <!-- Zorluğa Göre Cevap Dağılımı -->
                <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-lg">
                    <h3 class="text-xl font-bold mb-4">Zorluğa Göre Cevap Dağılımı</h3>
                    <canvas id="answers-chart"></canvas>
                </div>

                <!-- Son 7 Günlük Yeni Kullanıcı Kayıtları -->
                <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-lg">
                    <h3 class="text-xl font-bold mb-4">Son 7 Günlük Yeni Kullanıcı Kayıtları</h3>
                    <canvas id="users-chart"></canvas>
                </div>

                <!-- Quest Analytics Section -->
                <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-lg">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-xl font-bold dark:text-white">Quest Analitikleri</h3>
                        <button id="refresh-quest-analytics-btn" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 transition-colors">
                            <i class="fas fa-sync-alt"></i> Yenile
                        </button>
                    </div>

                    <!-- Quest Genel İstatistikleri -->
                    <div id="quest-general-stats" class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                        <div class="bg-blue-50 dark:bg-blue-900 rounded-lg p-4 text-center">
                            <p class="text-sm text-blue-600 dark:text-blue-400">Toplam Tamamlanan</p>
                            <p id="total-quest-completions" class="text-2xl font-bold text-blue-800 dark:text-blue-200">-</p>
                        </div>
                        <div class="bg-green-50 dark:bg-green-900 rounded-lg p-4 text-center">
                            <p class="text-sm text-green-600 dark:text-green-400">Aktif Kullanıcı</p>
                            <p id="quest-active-users" class="text-2xl font-bold text-green-800 dark:text-green-200">-</p>
                        </div>
                        <div class="bg-purple-50 dark:bg-purple-900 rounded-lg p-4 text-center">
                            <p class="text-sm text-purple-600 dark:text-purple-400">Dağıtılan Puan</p>
                            <p id="total-quest-points" class="text-2xl font-bold text-purple-800 dark:text-purple-200">-</p>
                        </div>
                        <div class="bg-yellow-50 dark:bg-yellow-900 rounded-lg p-4 text-center">
                            <p class="text-sm text-yellow-600 dark:text-yellow-400">Dağıtılan Jeton</p>
                            <p id="total-quest-coins" class="text-2xl font-bold text-yellow-800 dark:text-yellow-200">-</p>
                        </div>
                    </div>

                    <!-- Quest Type Performance -->
                    <div class="mb-6">
                        <h4 class="text-lg font-semibold mb-4 dark:text-white">Quest Türü Performansı</h4>
                        <div id="quest-type-performance" class="space-y-3">
                            <!-- JS ile doldurulacak -->
                        </div>
                    </div>

                    <!-- En Aktif Kullanıcılar (Quest bazında) -->
                    <div class="mb-6">
                        <h4 class="text-lg font-semibold mb-4 dark:text-white">En Aktif Kullanıcılar (Son 30 Gün)</h4>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left">
                                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                    <tr>
                                        <th class="px-3 py-2">Kullanıcı</th>
                                        <th class="px-3 py-2 text-center">Quest</th>
                                        <th class="px-3 py-2 text-center">Ort. Süre</th>
                                        <th class="px-3 py-2 text-center">Puan</th>
                                        <th class="px-3 py-2 text-center">Jeton</th>
                                        <th class="px-3 py-2 text-center">Son Aktivite</th>
                                    </tr>
                                </thead>
                                <tbody id="quest-top-users-table">
                                    <!-- JS ile doldurulacak -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Son 30 Günlük Görev Trendleri -->
                    <div>
                        <h4 class="text-lg font-semibold mb-4 dark:text-white">Son 30 Günlük Görev Tamamlama Trendi</h4>
                        <div style="height: 300px; position: relative;">
                            <canvas id="quest-trends-chart"></canvas>
                        </div>
                    </div>

                    <!-- Loading -->
                    <div id="quest-analytics-loading" class="text-center py-8">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500 mx-auto mb-4"></div>
                        <p class="text-gray-500 dark:text-gray-400">Quest analitikleri yükleniyor...</p>
                    </div>
                </div>
            </div>
        </div>

    </div>

<script>
// Quest Analytics yükleme fonksiyonu (önce tanımla)
const loadQuestAnalytics = async () => {
    console.log('loadQuestAnalytics started');
    const loadingEl = document.getElementById('quest-analytics-loading');
    const generalStatsEl = document.getElementById('quest-general-stats');
    const typePerformanceEl = document.getElementById('quest-type-performance');
    const topUsersEl = document.getElementById('quest-top-users-table');

    // Check if API is available
    if (typeof api === 'undefined') {
        console.error('API not available for quest analytics');
        setTimeout(loadQuestAnalytics, 1000);
        return;
    }

    try {
        // Loading göster
        if (loadingEl) loadingEl.classList.remove('hidden');
        if (generalStatsEl) generalStatsEl.style.opacity = '0.5';

        console.log('Calling API get_quest_history_stats...');
        const response = await api.call('get_quest_history_stats', {}, 'POST', false);
        console.log('Quest analytics API response:', response);

        if (response.success && response.data) {
            const { general_stats, type_performance, top_users, daily_trends } = response.data;

            // Genel istatistikleri render et
            renderQuestGeneralStats(general_stats);

            // Quest type performansını render et
            renderQuestTypePerformance(type_performance);

            // En aktif kullanıcıları render et
            renderQuestTopUsers(top_users);

            // Daily trends chart'ını render et
            renderQuestTrendsChart(daily_trends);

            if (generalStatsEl) generalStatsEl.style.opacity = '1';
        } else {
            console.error('Quest analytics yüklenemedi:', response.message);
        }

    } catch (error) {
        console.error('Quest analytics error:', error);
    } finally {
        if (loadingEl) loadingEl.classList.add('hidden');
    }
};

// Admin İstatistik sayfası yüklendiğinde içeriği yükle
document.addEventListener('DOMContentLoaded', () => {
    // Admin handler'ı yükle
    setTimeout(() => {
        if (typeof adminHandler !== 'undefined') {
            adminHandler.updateAll();
            adminHandler.loadAdvancedStats();
        }
        // Quest analytics'i ayrı olarak yükle
        setTimeout(loadQuestAnalytics, 500);
    }, 100);

    // Quest analytics refresh butonu
    const refreshBtn = document.getElementById('refresh-quest-analytics-btn');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', loadQuestAnalytics);
    }
});

const renderQuestGeneralStats = (stats) => {
    if (!stats) return;

    const totalEl = document.getElementById('total-quest-completions');
    const activeUsersEl = document.getElementById('quest-active-users');
    const pointsEl = document.getElementById('total-quest-points');
    const coinsEl = document.getElementById('total-quest-coins');

    if (totalEl) totalEl.textContent = stats.total_completed || '0';
    if (activeUsersEl) activeUsersEl.textContent = stats.total_unique_users || '0';
    if (pointsEl) pointsEl.textContent = formatNumber(stats.total_points_distributed || 0);
    if (coinsEl) coinsEl.textContent = formatNumber(stats.total_coins_distributed || 0);
};

const renderQuestTypePerformance = (typePerformance) => {
    const container = document.getElementById('quest-type-performance');
    if (!container || !typePerformance) return;

    container.innerHTML = '';

    typePerformance.forEach(type => {
        const typeEl = document.createElement('div');
        typeEl.className = 'flex justify-between items-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg';

        const avgTime = type.avg_completion_time ? Math.round(type.avg_completion_time) : 0;
        const completionRate = ((type.total_completed / Math.max(type.total_completed, 1)) * 100).toFixed(1);

        typeEl.innerHTML = `
            <div class="flex items-center space-x-4">
                <div class="w-3 h-3 rounded-full ${getQuestTypeColorCode(type.quest_type)}"></div>
                <div>
                    <h5 class="font-semibold text-gray-800 dark:text-gray-200">${type.type_name}</h5>
                    <p class="text-sm text-gray-600 dark:text-gray-400">${type.unique_users} kullanıcı</p>
                </div>
            </div>
            <div class="text-right">
                <div class="flex items-center space-x-6 text-sm">
                    <div class="text-center">
                        <p class="font-semibold text-gray-800 dark:text-gray-200">${type.total_completed}</p>
                        <p class="text-gray-500 dark:text-gray-400">Quest</p>
                    </div>
                    <div class="text-center">
                        <p class="font-semibold text-gray-800 dark:text-gray-200">${avgTime}m</p>
                        <p class="text-gray-500 dark:text-gray-400">Ort. Süre</p>
                    </div>
                    <div class="text-center">
                        <p class="font-semibold text-blue-600 dark:text-blue-400">${formatNumber(type.total_points_given)}</p>
                        <p class="text-gray-500 dark:text-gray-400">Puan</p>
                    </div>
                </div>
            </div>
        `;

        container.appendChild(typeEl);
    });
};

const renderQuestTopUsers = (topUsers) => {
    const container = document.getElementById('quest-top-users-table');
    if (!container || !topUsers) return;

    container.innerHTML = '';

    if (topUsers.length === 0) {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td colspan="6" class="px-3 py-4 text-center text-gray-500 dark:text-gray-400">
                Son 30 günde quest tamamlayan kullanıcı bulunamadı.
            </td>
        `;
        container.appendChild(row);
        return;
    }

    topUsers.forEach((user, index) => {
        const row = document.createElement('tr');
        row.className = 'border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700';

        const avgTime = user.avg_time ? Math.round(user.avg_time) : 0;
        const lastActivity = new Date(user.last_completion).toLocaleDateString('tr-TR');

        row.innerHTML = `
            <td class="px-3 py-3">
                <div class="flex items-center space-x-2">
                    <span class="w-6 h-6 rounded-full bg-blue-500 text-white text-xs flex items-center justify-center font-bold">
                        ${index + 1}
                    </span>
                    <span class="font-medium text-gray-800 dark:text-gray-200">${user.username}</span>
                </div>
            </td>
            <td class="px-3 py-3 text-center font-semibold text-blue-600 dark:text-blue-400">
                ${user.completed_quests}
            </td>
            <td class="px-3 py-3 text-center text-gray-600 dark:text-gray-400">
                ${avgTime}m
            </td>
            <td class="px-3 py-3 text-center text-green-600 dark:text-green-400">
                ${formatNumber(user.total_points)}
            </td>
            <td class="px-3 py-3 text-center text-yellow-600 dark:text-yellow-400">
                ${formatNumber(user.total_coins)}
            </td>
            <td class="px-3 py-3 text-center text-sm text-gray-500 dark:text-gray-400">
                ${lastActivity}
            </td>
        `;

        container.appendChild(row);
    });
};

const renderQuestTrendsChart = (dailyTrends) => {
    const ctx = document.getElementById('quest-trends-chart');
    if (!ctx || !dailyTrends) {
        console.log('Chart context or data not available');
        return;
    }

    // Önceki chart instance'ını yok et
    if (window.questTrendsChart) {
        window.questTrendsChart.destroy();
    }

    try {
        // Verileri tersine çevir (eskiden yeniye)
        const reversedTrends = [...dailyTrends].reverse();

    const labels = reversedTrends.map(trend => {
        const date = new Date(trend.date);
        return date.toLocaleDateString('tr-TR', { month: 'short', day: 'numeric' });
    });

    const completedData = reversedTrends.map(trend => trend.completed_count || 0);
    const uniqueUsersData = reversedTrends.map(trend => trend.unique_users || 0);

    window.questTrendsChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Tamamlanan Quest',
                    data: completedData,
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4,
                    fill: true
                },
                {
                    label: 'Aktif Kullanıcı',
                    data: uniqueUsersData,
                    borderColor: 'rgb(16, 185, 129)',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    tension: 0.4,
                    fill: true
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(156, 163, 175, 0.1)'
                    }
                },
                x: {
                    grid: {
                        color: 'rgba(156, 163, 175, 0.1)'
                    }
                }
            },
            interaction: {
                intersect: false,
                mode: 'index'
            }
        }
    });

    console.log('Quest trends chart rendered successfully');

    } catch (error) {
        console.error('Chart render error:', error);
    }
};

const getQuestTypeColorCode = (type) => {
    switch (type) {
        case 'solve_category':
            return 'bg-blue-500';
        case 'solve_difficulty':
            return 'bg-purple-500';
        case 'consecutive_days':
            return 'bg-orange-500';
        case 'win_duels':
            return 'bg-red-500';
        default:
            return 'bg-gray-500';
    }
};

const formatNumber = (num) => {
    if (num >= 1000000) {
        return (num / 1000000).toFixed(1) + 'M';
    } else if (num >= 1000) {
        return (num / 1000).toFixed(1) + 'K';
    }
    return num.toString();
};
</script>

<?php include 'footer.php'; ?>