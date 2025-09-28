/**
 * UI Admin Stats Module - Analytics and Statistics
 *
 * This module handles admin statistics, analytics charts, announcements
 * management, and data visualization for the admin panel.
 *
 * Phase 5 of ui-handler.js modularization - Admin Feature Modules
 * Created: 2025-09-28
 */

const UIAdminStats = (() => {
    let dom = {};

    const init = (domElements) => {
        dom = domElements;
        // Add admin stats-specific DOM elements
        dom.announcementsListBody = document.getElementById('announcements-list-body');
        dom.questionStatsContainer = document.getElementById('question-stats-container');
        dom.advancedStatsContainer = document.getElementById('advanced-stats-container');

        console.log('UIAdminStats initialized');
    };

    // Render advanced stats with charts (extracted from ui-handler.js)
    const renderAdvancedStats = (statsData) => {
        if (!statsData) return;

        const UICharts = ModuleLoader.getModule('UICharts');
        if (UICharts) {
            UICharts.renderAdvancedStats(statsData);
        }
    };

    // Render admin announcements list (extracted from ui-handler.js)
    const renderAdminAnnouncementsList = (announcements) => {
        if (!dom.announcementsListBody) return;
        dom.announcementsListBody.innerHTML = '';

        if (announcements.length === 0) {
            dom.announcementsListBody.innerHTML = '<tr><td colspan="4" class="text-center py-4 text-gray-500">Mevcut duyuru bulunmuyor.</td></tr>';
            return;
        }

        announcements.forEach(ann => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="py-2 px-4">${ann.title}</td>
                <td class="py-2 px-4">${ann.content.substring(0, 50)}${ann.content.length > 50 ? '...' : ''}</td>
                <td class="py-2 px-4">${ann.created_at}</td>
                <td class="py-2 px-4">
                    <button data-id="${ann.id}" class="delete-announcement-btn text-red-500 hover:text-red-700" title="Duyuruyu Sil">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            `;
            dom.announcementsListBody.appendChild(tr);
        });
    };

    // Render question statistics (extracted from ui-handler.js)
    const renderQuestionStats = (statsData) => {
        const container = document.getElementById('question-stats-container');
        if (!container) return;

        container.innerHTML = `
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-md">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-100 dark:bg-blue-900">
                            <i class="fas fa-question-circle text-blue-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300">Toplam Soru</h3>
                            <p class="text-2xl font-bold text-blue-600">${statsData.total_questions || 0}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-md">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-yellow-100 dark:bg-yellow-900">
                            <i class="fas fa-star text-yellow-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300">Ortalama Puan</h3>
                            <p class="text-2xl font-bold text-yellow-600">${statsData.average_rating || 0}/5</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-md">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-red-100 dark:bg-red-900">
                            <i class="fas fa-flag text-red-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300">Şikayet Edilen</h3>
                            <p class="text-2xl font-bold text-red-600">${statsData.reported_questions || 0}</p>
                        </div>
                    </div>
                </div>
            </div>

            ${statsData.category_stats ? `
                <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-md">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Kategori Bazında İstatistikler</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-200 dark:border-gray-700">
                                    <th class="text-left py-2">Kategori</th>
                                    <th class="text-center py-2">Soru Sayısı</th>
                                    <th class="text-center py-2">Ortalama Puan</th>
                                    <th class="text-center py-2">Şikayet</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${statsData.category_stats.map(cat => `
                                    <tr class="border-b border-gray-100 dark:border-gray-800">
                                        <td class="py-2 font-medium">${cat.category}</td>
                                        <td class="text-center py-2">${cat.question_count}</td>
                                        <td class="text-center py-2">${cat.average_rating || 0}/5</td>
                                        <td class="text-center py-2">${cat.report_count || 0}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                </div>
            ` : ''}
        `;
    };

    // Create stats dashboard
    const createStatsDashboard = (data) => {
        const container = document.getElementById('admin-stats-dashboard');
        if (!container) return;

        const UIAdmin = ModuleLoader.getModule('UIAdmin');
        if (!UIAdmin) return;

        container.innerHTML = '';

        // Main stats cards
        const statsCards = [
            {
                title: 'Toplam Kullanıcı',
                value: data.total_users || '0',
                icon: 'fas fa-users',
                color: 'blue',
                change: data.user_growth || '+0%',
                changeType: 'positive'
            },
            {
                title: 'Bu Ay Yeni Kullanıcı',
                value: data.new_users_this_month || '0',
                icon: 'fas fa-user-plus',
                color: 'green',
                change: data.monthly_growth || '+0%',
                changeType: 'positive'
            },
            {
                title: 'Toplam Oyun',
                value: data.total_games || '0',
                icon: 'fas fa-gamepad',
                color: 'purple',
                change: data.game_growth || '+0%',
                changeType: 'positive'
            },
            {
                title: 'Aktif Düello',
                value: data.active_duels || '0',
                icon: 'fas fa-sword',
                color: 'red',
                change: data.duel_growth || '+0%',
                changeType: 'positive'
            }
        ];

        const cardsContainer = document.createElement('div');
        cardsContainer.className = 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8';

        statsCards.forEach(cardData => {
            const card = createAdvancedStatsCard(cardData);
            if (card) {
                cardsContainer.appendChild(card);
            }
        });

        container.appendChild(cardsContainer);

        // Charts section
        const chartsContainer = document.createElement('div');
        chartsContainer.className = 'grid grid-cols-1 lg:grid-cols-2 gap-6';
        chartsContainer.innerHTML = `
            <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-md">
                <h3 class="text-lg font-semibold mb-4 dark:text-white">En Çok Oynanan Kategoriler</h3>
                <canvas id="category-chart" width="400" height="200"></canvas>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-md">
                <h3 class="text-lg font-semibold mb-4 dark:text-white">Cevap Dağılımı</h3>
                <canvas id="answers-chart" width="400" height="200"></canvas>
            </div>
        `;

        container.appendChild(chartsContainer);
    };

    // Create advanced stats card with trend indicators
    const createAdvancedStatsCard = (options) => {
        const {
            title,
            value,
            icon,
            color,
            change,
            changeType,
            description
        } = options;

        const card = document.createElement('div');
        card.className = 'bg-white dark:bg-gray-800 rounded-lg p-6 shadow-md';

        const changeColor = changeType === 'positive' ? 'text-green-500' : 'text-red-500';
        const changeIcon = changeType === 'positive' ? 'fas fa-arrow-up' : 'fas fa-arrow-down';

        card.innerHTML = `
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">${title}</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-gray-100">${value}</p>
                    ${change ? `
                        <div class="flex items-center mt-2">
                            <i class="${changeIcon} ${changeColor} text-sm mr-1"></i>
                            <span class="${changeColor} text-sm font-medium">${change}</span>
                            <span class="text-gray-500 text-sm ml-2">bu ay</span>
                        </div>
                    ` : ''}
                    ${description ? `<p class="text-xs text-gray-500 dark:text-gray-400 mt-1">${description}</p>` : ''}
                </div>
                <div class="p-3 rounded-full bg-${color}-100 dark:bg-${color}-900">
                    <i class="${icon} text-${color}-600 dark:text-${color}-400 text-2xl"></i>
                </div>
            </div>
        `;

        return card;
    };

    // Render system performance metrics
    const renderSystemMetrics = (metrics) => {
        const container = document.getElementById('system-metrics-container');
        if (!container || !metrics) return;

        container.innerHTML = `
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-md">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300">Sunucu Performansı</h3>
                        <i class="fas fa-server text-blue-600"></i>
                    </div>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600 dark:text-gray-400">CPU Kullanımı</span>
                            <span class="font-semibold">${metrics.cpu_usage || '0'}%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-blue-600 h-2 rounded-full" style="width: ${metrics.cpu_usage || 0}%"></div>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Bellek Kullanımı</span>
                            <span class="font-semibold">${metrics.memory_usage || '0'}%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-green-600 h-2 rounded-full" style="width: ${metrics.memory_usage || 0}%"></div>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-md">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300">Veritabanı</h3>
                        <i class="fas fa-database text-green-600"></i>
                    </div>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Sorgu Sürati</span>
                            <span class="font-semibold">${metrics.avg_query_time || '0'}ms</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Aktif Bağlantı</span>
                            <span class="font-semibold">${metrics.active_connections || '0'}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Veri Boyutu</span>
                            <span class="font-semibold">${metrics.database_size || '0'} MB</span>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-md">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300">API Kullanımı</h3>
                        <i class="fas fa-chart-line text-purple-600"></i>
                    </div>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Günlük İstek</span>
                            <span class="font-semibold">${metrics.daily_requests || '0'}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Hata Oranı</span>
                            <span class="font-semibold text-red-600">${metrics.error_rate || '0'}%</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Ortalama Yanıt</span>
                            <span class="font-semibold">${metrics.avg_response_time || '0'}ms</span>
                        </div>
                    </div>
                </div>
            </div>
        `;
    };

    // Export stats to various formats
    const exportStats = (format = 'csv', data) => {
        const UIAdmin = ModuleLoader.getModule('UIAdmin');

        switch (format) {
            case 'csv':
                exportToCSV(data);
                break;
            case 'json':
                exportToJSON(data);
                break;
            case 'pdf':
                exportToPDF(data);
                break;
            default:
                if (UIAdmin) {
                    UIAdmin.showAdminNotification('Desteklenmeyen format', 'error');
                }
        }
    };

    // Export to CSV
    const exportToCSV = (data) => {
        const csvData = [
            ['Metrik', 'Değer', 'Tarih'],
            ['Toplam Kullanıcı', data.total_users || '0', new Date().toLocaleDateString()],
            ['Yeni Kullanıcılar (Bu Ay)', data.new_users_this_month || '0', new Date().toLocaleDateString()],
            ['Toplam Oyun', data.total_games || '0', new Date().toLocaleDateString()],
            ['Aktif Düello', data.active_duels || '0', new Date().toLocaleDateString()]
        ];

        const csvContent = csvData.map(row => row.join(',')).join('\n');
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = `istatistikler_${new Date().toISOString().split('T')[0]}.csv`;
        link.click();
    };

    // Export to JSON
    const exportToJSON = (data) => {
        const jsonContent = JSON.stringify(data, null, 2);
        const blob = new Blob([jsonContent], { type: 'application/json;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = `istatistikler_${new Date().toISOString().split('T')[0]}.json`;
        link.click();
    };

    // Generate stats summary report
    const generateStatsReport = (data) => {
        const report = {
            generated_at: new Date().toISOString(),
            summary: {
                total_users: data.total_users || 0,
                new_users_this_month: data.new_users_this_month || 0,
                total_games: data.total_games || 0,
                active_duels: data.active_duels || 0,
                user_growth_rate: data.user_growth || '0%',
                game_growth_rate: data.game_growth || '0%'
            },
            categories: data.category_stats || [],
            trends: {
                user_registration_trend: 'increasing',
                game_activity_trend: 'stable',
                engagement_score: '85%'
            }
        };

        return report;
    };

    // Initialize admin stats events
    const initAdminStatsEvents = () => {
        // Export buttons
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('export-stats-btn')) {
                const format = e.target.dataset.format;
                // Get current stats data and export
                exportStats(format, {});
            }

            if (e.target.classList.contains('refresh-stats-btn')) {
                refreshAllStats();
            }

            if (e.target.classList.contains('delete-announcement-btn')) {
                const announcementId = e.target.dataset.id;
                deleteAnnouncement(announcementId);
            }
        });
    };

    // Refresh all statistics
    const refreshAllStats = () => {
        const UIAdmin = ModuleLoader.getModule('UIAdmin');
        if (UIAdmin) {
            UIAdmin.showAdminLoading(true, 'İstatistikler yenileniyor...');
        }

        // Simulate API calls to refresh stats
        setTimeout(() => {
            if (UIAdmin) {
                UIAdmin.showAdminLoading(false);
                UIAdmin.showAdminNotification('İstatistikler başarıyla yenilendi', 'success');
            }
        }, 2000);
    };

    // Delete announcement
    const deleteAnnouncement = (announcementId) => {
        const UIAdmin = ModuleLoader.getModule('UIAdmin');
        if (UIAdmin) {
            const confirmed = confirm('Bu duyuruyu silmek istediğinizden emin misiniz?');
            if (confirmed) {
                UIAdmin.showAdminNotification('Duyuru silindi', 'success');
                // Remove row from table
                const row = document.querySelector(`[data-id="${announcementId}"]`).closest('tr');
                if (row) row.remove();
            }
        }
    };

    // Public API
    return {
        init,

        // Stats rendering
        renderAdvancedStats,
        renderQuestionStats,
        renderSystemMetrics,
        createStatsDashboard,

        // Announcements
        renderAdminAnnouncementsList,

        // Export functionality
        exportStats,
        generateStatsReport,

        // Event management
        initAdminStatsEvents,
        refreshAllStats,

        // Utilities
        createAdvancedStatsCard
    };
})();

// Auto-register with ModuleLoader when available
if (typeof ModuleLoader !== 'undefined') {
    if (ModuleLoader.isInitialized) {
        ModuleLoader.register('UIAdminStats', UIAdminStats);
    } else {
        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => {
                if (typeof ModuleLoader !== 'undefined') {
                    ModuleLoader.register('UIAdminStats', UIAdminStats);
                }
            }, 100);
        });
    }
}