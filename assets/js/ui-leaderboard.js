/**
 * UI Leaderboard Module - Ranking and Statistics
 *
 * This module handles leaderboard displays, user statistics,
 * ranking systems, and performance analytics.
 *
 * Phase 4 of ui-handler.js modularization - Social Feature Modules
 * Created: 2025-09-28
 */

const UILeaderboard = (() => {
    let dom = {};

    const init = (domElements) => {
        dom = domElements;
        // Add leaderboard-specific DOM elements
        dom.leaderboardList = document.getElementById('leaderboard-list');
        dom.leaderboardLoading = document.getElementById('leaderboard-loading');
        dom.userTotalScore = document.getElementById('user-total-score');
        dom.categoryStatsBody = document.getElementById('category-stats-body');
        dom.noStatsMessage = document.getElementById('no-stats-message');

        console.log('UILeaderboard initialized');
    };

    // Create podium place element (extracted from ui-handler.js)
    const createPodiumPlace = (player, rank, heightClass, icon) => {
        const place = document.createElement('div');
        place.className = 'flex flex-col items-center';

        const playerCard = document.createElement('div');
        playerCard.className = 'bg-white dark:bg-gray-800 rounded-lg p-3 mb-2 shadow-lg text-center min-w-[100px]';

        // Use UIComponents for consistent avatar generation
        const UIComponents = ModuleLoader.getModule('UIComponents');
        const avatar = document.createElement('div');
        if (UIComponents) {
            const color = UIComponents.getAvatarColor(player.username);
            avatar.className = `w-12 h-12 rounded-full ${color} flex items-center justify-center text-white font-bold text-lg mx-auto mb-2`;
        } else {
            avatar.className = 'w-12 h-12 rounded-full bg-blue-500 flex items-center justify-center text-white font-bold text-lg mx-auto mb-2';
        }
        avatar.textContent = player.username.charAt(0).toUpperCase();

        const name = document.createElement('div');
        name.className = 'font-semibold text-sm dark:text-white truncate';
        name.textContent = player.username;

        const score = document.createElement('div');
        score.className = 'text-blue-600 dark:text-blue-400 font-bold text-lg';
        score.textContent = player.score;

        playerCard.appendChild(avatar);
        playerCard.appendChild(name);
        playerCard.appendChild(score);

        const pedestal = document.createElement('div');
        pedestal.className = `${heightClass} w-20 rounded-t-lg flex items-end justify-center pb-2`;

        const iconSpan = document.createElement('span');
        iconSpan.className = 'text-2xl';
        iconSpan.textContent = icon;
        pedestal.appendChild(iconSpan);

        place.appendChild(playerCard);
        place.appendChild(pedestal);

        return place;
    };

    // Create table row for leaderboard (extracted from ui-handler.js)
    const createTableRow = (player, rank) => {
        const row = document.createElement('tr');
        row.className = 'hover:bg-gray-50 dark:hover:bg-gray-700/50';

        const rankCell = document.createElement('td');
        rankCell.className = 'px-3 py-3 font-semibold';
        rankCell.textContent = `#${rank}`;

        const playerCell = document.createElement('td');
        playerCell.className = 'px-3 py-3';

        const playerDiv = document.createElement('div');
        playerDiv.className = 'flex items-center space-x-3';

        // Use UIComponents for consistent avatar generation
        const UIComponents = ModuleLoader.getModule('UIComponents');
        const avatar = document.createElement('div');
        if (UIComponents) {
            const color = UIComponents.getAvatarColor(player.username);
            avatar.className = `w-8 h-8 rounded-full ${color} flex items-center justify-center text-white font-bold text-sm`;
        } else {
            avatar.className = 'w-8 h-8 rounded-full bg-blue-500 flex items-center justify-center text-white font-bold text-sm';
        }
        avatar.textContent = player.username.charAt(0).toUpperCase();

        const name = document.createElement('span');
        name.className = 'font-medium dark:text-white';
        name.textContent = player.username;

        playerDiv.appendChild(avatar);
        playerDiv.appendChild(name);
        playerCell.appendChild(playerDiv);

        const scoreCell = document.createElement('td');
        scoreCell.className = 'px-3 py-3 text-right font-bold text-blue-600 dark:text-blue-400';
        scoreCell.textContent = player.score;

        row.appendChild(rankCell);
        row.appendChild(playerCell);
        row.appendChild(scoreCell);

        return row;
    };

    // Render main leaderboard (extracted from ui-handler.js)
    const renderLeaderboard = (leaderboardData, userRank = null) => {
        if (!dom.leaderboardList || !dom.leaderboardLoading) return;

        dom.leaderboardLoading.classList.add('hidden');
        dom.leaderboardList.innerHTML = '';

        // Top 3 Podium
        if (leaderboardData.length > 0) {
            const podiumContainer = document.createElement('div');
            podiumContainer.className = 'mb-8';

            const podiumTitle = document.createElement('h3');
            podiumTitle.className = 'text-xl font-bold text-center mb-6 dark:text-white';
            podiumTitle.textContent = '🏆 İlk 3';
            podiumContainer.appendChild(podiumTitle);

            const podium = document.createElement('div');
            podium.className = 'flex justify-center items-end space-x-4';

            // 2nd place (left)
            if (leaderboardData[1]) {
                const secondPlace = createPodiumPlace(leaderboardData[1], 2, 'h-24 bg-gray-300 dark:bg-gray-600', '🥈');
                podium.appendChild(secondPlace);
            }

            // 1st place (center, tallest)
            if (leaderboardData[0]) {
                const firstPlace = createPodiumPlace(leaderboardData[0], 1, 'h-32 bg-yellow-400 dark:bg-yellow-500', '👑');
                podium.appendChild(firstPlace);
            }

            // 3rd place (right)
            if (leaderboardData[2]) {
                const thirdPlace = createPodiumPlace(leaderboardData[2], 3, 'h-20 bg-orange-300 dark:bg-orange-500', '🥉');
                podium.appendChild(thirdPlace);
            }

            podiumContainer.appendChild(podium);
            dom.leaderboardList.appendChild(podiumContainer);
        }

        // Next 7 players in table format
        if (leaderboardData.length > 3) {
            const tableContainer = document.createElement('div');
            tableContainer.className = 'mb-8';

            const tableTitle = document.createElement('h3');
            tableTitle.className = 'text-lg font-bold mb-4 dark:text-white';
            tableTitle.textContent = '📊 Sıralama';
            tableContainer.appendChild(tableTitle);

            const table = document.createElement('table');
            table.className = 'w-full text-sm';

            const thead = document.createElement('thead');
            thead.innerHTML = `
                <tr class="border-b dark:border-gray-700">
                    <th class="text-left px-3 py-2 font-semibold dark:text-gray-300">Sıra</th>
                    <th class="text-left px-3 py-2 font-semibold dark:text-gray-300">Oyuncu</th>
                    <th class="text-right px-3 py-2 font-semibold dark:text-gray-300">Puan</th>
                </tr>
            `;
            table.appendChild(thead);

            const tbody = document.createElement('tbody');
            const playersToShow = leaderboardData.slice(3, 10);
            playersToShow.forEach((player, index) => {
                const row = createTableRow(player, index + 4);
                tbody.appendChild(row);
            });

            table.appendChild(tbody);
            tableContainer.appendChild(table);
            dom.leaderboardList.appendChild(tableContainer);
        }

        // User's own position (if provided and not in top 10)
        if (userRank && userRank.position > 10) {
            const userPositionContainer = document.createElement('div');
            userPositionContainer.className = 'mt-8 pt-6 border-t dark:border-gray-700';

            const userTitle = document.createElement('h3');
            userTitle.className = 'text-lg font-bold mb-4 dark:text-white';
            userTitle.textContent = '👤 Senin Sıran';
            userPositionContainer.appendChild(userTitle);

            const userCard = document.createElement('div');
            userCard.className = 'bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 flex justify-between items-center';

            const userInfo = document.createElement('div');
            userInfo.className = 'flex items-center space-x-3';

            // Use UIComponents for consistent avatar generation
            const UIComponents = ModuleLoader.getModule('UIComponents');
            const userAvatar = document.createElement('div');
            if (UIComponents) {
                const color = UIComponents.getAvatarColor(userRank.username);
                userAvatar.className = `w-10 h-10 rounded-full ${color} flex items-center justify-center text-white font-bold`;
            } else {
                userAvatar.className = 'w-10 h-10 rounded-full bg-blue-500 flex items-center justify-center text-white font-bold';
            }
            userAvatar.textContent = userRank.username.charAt(0).toUpperCase();

            const userName = document.createElement('span');
            userName.className = 'font-semibold dark:text-white';
            userName.textContent = userRank.username;

            userInfo.appendChild(userAvatar);
            userInfo.appendChild(userName);

            const userRankInfo = document.createElement('div');
            userRankInfo.className = 'text-right';

            const position = document.createElement('div');
            position.className = 'text-lg font-bold text-blue-600 dark:text-blue-400';
            position.textContent = `#${userRank.position}`;

            const score = document.createElement('div');
            score.className = 'text-sm text-gray-600 dark:text-gray-400';
            score.textContent = `${userRank.score} puan`;

            userRankInfo.appendChild(position);
            userRankInfo.appendChild(score);

            userCard.appendChild(userInfo);
            userCard.appendChild(userRankInfo);
            userPositionContainer.appendChild(userCard);

            dom.leaderboardList.appendChild(userPositionContainer);
        }
    };

    // Render user statistics (extracted from ui-handler.js)
    const renderUserData = (userData) => {
        if (!dom.userTotalScore || !dom.categoryStatsBody || !dom.noStatsMessage) return;

        const { score, stats, coins } = userData;
        dom.userTotalScore.textContent = score;
        if (dom.userCoinBalance) dom.userCoinBalance.textContent = coins;

        dom.categoryStatsBody.innerHTML = '';
        if (stats && stats.length > 0) {
            dom.noStatsMessage.classList.add('hidden');
            stats.forEach(cat => {
                const rate = cat.total_questions > 0 ? Math.round((cat.correct_answers / cat.total_questions) * 100) : 0;
                const tr = document.createElement('tr');
                tr.className = 'border-b dark:border-gray-700';
                tr.innerHTML = `
                    <td class="py-2 px-2">${cat.category.charAt(0).toUpperCase() + cat.category.slice(1)}</td>
                    <td class="py-2 px-2 text-center">${cat.total_questions}</td>
                    <td class="py-2 px-2 text-center">${cat.correct_answers}</td>
                    <td class="py-2 px-2 text-center font-bold ${rate > 60 ? 'text-green-500' : 'text-yellow-500'}">${rate}%</td>
                `;
                dom.categoryStatsBody.appendChild(tr);
            });
        } else {
            dom.noStatsMessage.classList.remove('hidden');
        }
    };

    // Update user rank display
    const updateUserRank = (rank, score) => {
        const rankElement = document.getElementById('user-rank');
        const scoreElement = document.getElementById('user-score');

        if (rankElement) {
            rankElement.textContent = rank ? `#${rank}` : '-';
        }
        if (scoreElement) {
            scoreElement.textContent = score || '0';
        }
    };

    // Render category performance chart
    const renderCategoryPerformance = (categoryStats) => {
        const container = document.getElementById('category-performance-chart');
        if (!container || !categoryStats || categoryStats.length === 0) return;

        container.innerHTML = '';

        const chartContainer = document.createElement('div');
        chartContainer.className = 'space-y-3';

        categoryStats.forEach(cat => {
            const rate = cat.total_questions > 0 ? Math.round((cat.correct_answers / cat.total_questions) * 100) : 0;

            const categoryRow = document.createElement('div');
            categoryRow.className = 'flex items-center justify-between mb-2';

            const categoryName = document.createElement('span');
            categoryName.className = 'text-sm font-medium text-gray-700 dark:text-gray-300';
            categoryName.textContent = cat.category.charAt(0).toUpperCase() + cat.category.slice(1);

            const progressContainer = document.createElement('div');
            progressContainer.className = 'flex items-center space-x-2 flex-1 ml-4';

            const progressBar = document.createElement('div');
            progressBar.className = 'flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-2';

            const progressFill = document.createElement('div');
            progressFill.className = `h-2 rounded-full ${rate > 80 ? 'bg-green-500' : rate > 60 ? 'bg-yellow-500' : 'bg-red-500'}`;
            progressFill.style.width = `${rate}%`;

            const percentage = document.createElement('span');
            percentage.className = 'text-sm font-semibold text-gray-600 dark:text-gray-400 min-w-[40px] text-right';
            percentage.textContent = `${rate}%`;

            progressBar.appendChild(progressFill);
            progressContainer.appendChild(progressBar);
            progressContainer.appendChild(percentage);

            categoryRow.appendChild(categoryName);
            categoryRow.appendChild(progressContainer);
            chartContainer.appendChild(categoryRow);
        });

        container.appendChild(chartContainer);
    };

    // Show leaderboard loading state
    const showLeaderboardLoading = (show = true) => {
        if (dom.leaderboardLoading) {
            dom.leaderboardLoading.classList.toggle('hidden', !show);
        }
        if (dom.leaderboardList && show) {
            dom.leaderboardList.innerHTML = '';
        }
    };

    // Format rank with appropriate styling
    const formatRank = (rank) => {
        if (rank <= 3) {
            const medals = ['🥇', '🥈', '🥉'];
            return `${medals[rank - 1]} #${rank}`;
        }
        return `#${rank}`;
    };

    // Calculate performance grade based on statistics
    const calculatePerformanceGrade = (stats) => {
        if (!stats || stats.length === 0) return { grade: 'N/A', color: 'gray' };

        const totalQuestions = stats.reduce((sum, cat) => sum + cat.total_questions, 0);
        const totalCorrect = stats.reduce((sum, cat) => sum + cat.correct_answers, 0);

        if (totalQuestions === 0) return { grade: 'N/A', color: 'gray' };

        const accuracy = (totalCorrect / totalQuestions) * 100;

        if (accuracy >= 90) return { grade: 'A+', color: 'green' };
        if (accuracy >= 80) return { grade: 'A', color: 'green' };
        if (accuracy >= 70) return { grade: 'B', color: 'blue' };
        if (accuracy >= 60) return { grade: 'C', color: 'yellow' };
        return { grade: 'D', color: 'red' };
    };

    // Public API
    return {
        init,

        // Leaderboard functions
        renderLeaderboard,
        showLeaderboardLoading,
        updateUserRank,
        formatRank,

        // User statistics functions
        renderUserData,
        renderCategoryPerformance,
        calculatePerformanceGrade,

        // Helper functions
        createPodiumPlace,
        createTableRow
    };
})();

// Auto-register with ModuleLoader when available
if (typeof ModuleLoader !== 'undefined') {
    if (ModuleLoader.isInitialized) {
        ModuleLoader.register('UILeaderboard', UILeaderboard);
    } else {
        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => {
                if (typeof ModuleLoader !== 'undefined') {
                    ModuleLoader.register('UILeaderboard', UILeaderboard);
                }
            }, 100);
        });
    }
}