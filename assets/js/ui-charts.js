/**
 * UI Charts Module - Analytics and Chart Management
 *
 * This module handles all chart rendering and analytics visualization
 * throughout the application. It manages Chart.js instances and provides
 * reusable chart creation functions.
 *
 * Phase 3 of ui-handler.js modularization - Independent Feature Modules
 * Created: 2025-09-28
 */

const UICharts = (() => {
    let dom = {};
    let charts = {}; // To store chart instances

    const init = (domElements) => {
        dom = domElements;
        // Add chart-specific DOM elements
        dom.categoryChart = document.getElementById('category-chart');
        dom.answersChart = document.getElementById('answers-chart');
        dom.usersChart = document.getElementById('users-chart');

        console.log('UICharts initialized');
    };

    // Utility function to destroy existing chart instances
    const destroyChart = (chartName) => {
        if (charts[chartName]) {
            charts[chartName].destroy();
            delete charts[chartName];
        }
    };

    // Destroy all chart instances
    const destroyAllCharts = () => {
        Object.keys(charts).forEach(chartName => {
            destroyChart(chartName);
        });
    };

    // Create bar chart for categories
    const createCategoryChart = (data, targetElement = null) => {
        const element = targetElement || dom.categoryChart;
        if (!element || !data) return null;

        const ctx = element.getContext('2d');
        const chart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.map(item => item.category_name),
                datasets: [{
                    label: 'Soru Sayısı',
                    data: data.map(item => item.question_count),
                    backgroundColor: [
                        'rgba(59, 130, 246, 0.6)',
                        'rgba(16, 185, 129, 0.6)',
                        'rgba(245, 101, 101, 0.6)',
                        'rgba(251, 191, 36, 0.6)',
                        'rgba(139, 92, 246, 0.6)'
                    ],
                    borderColor: [
                        'rgba(59, 130, 246, 1)',
                        'rgba(16, 185, 129, 1)',
                        'rgba(245, 101, 101, 1)',
                        'rgba(251, 191, 36, 1)',
                        'rgba(139, 92, 246, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'En Çok Oynanan Kategoriler'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        return chart;
    };

    // Create doughnut chart for answer distribution
    const createAnswerDistributionChart = (data, targetElement = null) => {
        const element = targetElement || dom.answersChart;
        if (!element || !data) return null;

        const ctx = element.getContext('2d');
        const chart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Doğru', 'Yanlış'],
                datasets: [{
                    data: [data.correct, data.incorrect],
                    backgroundColor: [
                        'rgba(16, 185, 129, 0.8)',
                        'rgba(245, 101, 101, 0.8)'
                    ],
                    borderColor: [
                        'rgba(16, 185, 129, 1)',
                        'rgba(245, 101, 101, 1)'
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Cevap Dağılımı'
                    }
                }
            }
        });

        return chart;
    };

    // Create line chart for user growth
    const createUserGrowthChart = (data, targetElement = null) => {
        const element = targetElement || dom.usersChart;
        if (!element || !data) return null;

        const ctx = element.getContext('2d');
        const chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.map(item => item.date),
                datasets: [{
                    label: 'Yeni Kullanıcılar',
                    data: data.map(item => item.user_count),
                    borderColor: 'rgba(139, 92, 246, 1)',
                    backgroundColor: 'rgba(139, 92, 246, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Son 7 Gün Yeni Kullanıcılar'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        return chart;
    };

    // Main function to render advanced stats (extracted from ui-handler.js)
    const renderAdvancedStats = (statsData) => {
        if (!statsData) return;

        const { most_played_categories, new_users_last_7_days, answer_distribution } = statsData;

        // Destroy existing charts to prevent duplicates
        destroyChart('categories');
        destroyChart('answers');
        destroyChart('users');

        // Chart 1: Most Played Categories (Bar Chart)
        if (most_played_categories && most_played_categories.length > 0) {
            charts.categories = createCategoryChart(most_played_categories);
        }

        // Chart 2: Answer Distribution (Doughnut Chart)
        if (answer_distribution) {
            charts.answers = createAnswerDistributionChart(answer_distribution);
        }

        // Chart 3: New Users (Line Chart)
        if (new_users_last_7_days && new_users_last_7_days.length > 0) {
            charts.users = createUserGrowthChart(new_users_last_7_days);
        }
    };

    // Render question statistics
    const renderQuestionStats = (statsData) => {
        const container = document.getElementById('question-stats-container');
        if (!container || !statsData) return;

        container.innerHTML = `
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-100 dark:bg-blue-900">
                            <i class="fas fa-question-circle text-blue-600 dark:text-blue-400"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300">Toplam Soru</h3>
                            <p class="text-2xl font-bold text-blue-600">${statsData.total_questions || 0}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-yellow-100 dark:bg-yellow-900">
                            <i class="fas fa-star text-yellow-600 dark:text-yellow-400"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300">Ortalama Puan</h3>
                            <p class="text-2xl font-bold text-yellow-600">${statsData.average_rating || 0}/5</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-red-100 dark:bg-red-900">
                            <i class="fas fa-flag text-red-600 dark:text-red-400"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300">Şikayet Edilen</h3>
                            <p class="text-2xl font-bold text-red-600">${statsData.reported_questions || 0}</p>
                        </div>
                    </div>
                </div>
            </div>

            ${statsData.category_stats ? `
                <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
                    <h3 class="text-xl font-semibold mb-4 text-gray-700 dark:text-gray-300">Kategori Bazında İstatistikler</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Kategori</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Soru Sayısı</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Ortalama Puan</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                ${statsData.category_stats.map(cat => `
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">${cat.category_name}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">${cat.question_count}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">${cat.average_rating}/5</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                </div>
            ` : ''}
        `;
    };

    // Generic chart creation function
    const createChart = (type, data, options = {}, targetElement) => {
        if (!targetElement) return null;

        const ctx = targetElement.getContext('2d');
        return new Chart(ctx, {
            type,
            data,
            options: {
                responsive: true,
                ...options
            }
        });
    };

    // Public API
    return {
        init,

        // Chart management
        destroyChart,
        destroyAllCharts,

        // Specific chart creators
        createCategoryChart,
        createAnswerDistributionChart,
        createUserGrowthChart,

        // Generic chart creator
        createChart,

        // Main rendering functions
        renderAdvancedStats,
        renderQuestionStats,

        // Chart instances access (read-only)
        get charts() { return { ...charts }; },
        get chartCount() { return Object.keys(charts).length; }
    };
})();

// Auto-register with ModuleLoader when available
if (typeof ModuleLoader !== 'undefined') {
    if (ModuleLoader.isInitialized) {
        ModuleLoader.register('UICharts', UICharts);
    } else {
        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => {
                if (typeof ModuleLoader !== 'undefined') {
                    ModuleLoader.register('UICharts', UICharts);
                }
            }, 100);
        });
    }
}