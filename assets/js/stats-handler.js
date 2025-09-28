const statsHandler = (() => {
    let dom = {};

    const init = (domElements) => {
        dom = domElements;
        addEventListeners();
    };

    // Helper methods to get modules with fallback
    const getUILeaderboard = () => {
        const UILeaderboard = ModuleLoader?.getModule('UILeaderboard');
        if (UILeaderboard) {
            return UILeaderboard;
        } else if (window.ui) {
            // Fallback to legacy ui-handler
            return window.ui;
        }
        return null;
    };

    const getUIGame = () => {
        const UIGame = ModuleLoader?.getModule('UIGame');
        if (UIGame) {
            return UIGame;
        } else if (window.ui) {
            // Fallback to legacy ui-handler
            return window.ui;
        }
        return null;
    };

    const addEventListeners = () => {
        // Avatar functionality removed - using initials instead

        // Compare achievements button
        const compareBtn = document.getElementById('compare-achievements-btn');
        if (compareBtn) {
            compareBtn.addEventListener('click', showAchievementComparison);
        }
    };

    const updateCombinedAchievements = async () => {
        // API henüz yüklenmediyse bekle
        if (typeof api === 'undefined') {
            setTimeout(updateCombinedAchievements, 1000);
            return;
        }

        try {
            const result = await api.call('get_combined_achievements', {}, 'POST', false);
            if (result && result.success) {
                renderCombinedAchievements(result.data);
            } else {
                showAchievementsPlaceholder();
            }
        } catch (error) {
            showAchievementsPlaceholder();
        }
    };

    const updateAchievements = async () => {
        const result = await api.call('get_user_achievements', {}, 'POST', false);
        const uiGame = getUIGame();
        if (result && result.success) {
            if (uiGame && uiGame.renderAchievements) {
                uiGame.renderAchievements(result.data);
            }
        } else if (!result.success) {
            if (uiGame && uiGame.renderAchievements) {
                uiGame.renderAchievements([]);
            }
        }
    };

    const updateLeaderboard = async () => {
        // Get leaderboard data
        const leaderboardResult = await api.call('get_leaderboard', {}, 'POST', false);

        // Get user's rank
        const userRankResult = await api.call('get_user_rank', {}, 'POST', false);

        if (leaderboardResult && leaderboardResult.success) {
            const userRank = userRankResult && userRankResult.success ? userRankResult.data : null;
            const uiLeaderboard = getUILeaderboard();
            if (uiLeaderboard && uiLeaderboard.renderLeaderboard) {
                uiLeaderboard.renderLeaderboard(leaderboardResult.data, userRank);
            }
        }
    };

    const updateUserData = async () => {
        const result = await api.call('get_user_data', {}, 'POST', false);
        if (result && result.success) {
            const uiLeaderboard = getUILeaderboard();
            if (uiLeaderboard && uiLeaderboard.renderUserData) {
                uiLeaderboard.renderUserData(result.data);
            }
            const currentUser = appState.get('currentUser');
            if (currentUser) {
            }
        }
    };

    const updateAll = () => {
        updateUserData();
        updateLeaderboard();
        updateCombinedAchievements();
    };

    // Show achievement comparison modal
    const showAchievementComparison = async () => {
        try {
            // Create modal
            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
            modal.innerHTML = `
                <div class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-4xl w-full max-h-[80vh] overflow-y-auto">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-2xl font-bold text-gray-800 dark:text-white">
                            <i class="fas fa-trophy mr-2 text-yellow-500"></i>
                            Arkadaşlarla Başarım Karşılaştırması
                        </h2>
                        <button id="close-comparison-modal" class="text-gray-500 hover:text-gray-700 dark:hover:text-white">
                            <i class="fas fa-times text-2xl"></i>
                        </button>
                    </div>
                    <div id="comparison-content" class="text-center py-8">
                        <i class="fas fa-spinner fa-spin text-4xl text-blue-500"></i>
                        <p class="mt-4 text-gray-600 dark:text-gray-400">Yükleniyor...</p>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);

            // Close modal handler
            document.getElementById('close-comparison-modal').addEventListener('click', () => {
                document.body.removeChild(modal);
            });

            // Load comparison data
            const response = await api.call('get_friends_achievement_comparison', {}, 'POST', false);
            console.log('Achievement comparison response:', response);
            const content = document.getElementById('comparison-content');

            if (response.success && response.friends && response.friends.length > 0) {
                const userCount = response.user_achievement_count || 0;
                content.innerHTML = `
                    <div class="mb-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                        <p class="text-sm text-blue-800 dark:text-blue-300">
                            <i class="fas fa-info-circle mr-2"></i>
                            Sen: <span class="font-bold">${userCount}</span> başarım kazandın
                        </p>
                    </div>
                    <div class="space-y-4">
                        ${response.friends.map((friend, index) => `
                            <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-lg ${index === 0 && friend.achievement_count > 0 ? 'border-2 border-yellow-400' : ''}">
                                <div class="flex items-center space-x-3">
                                    ${index === 0 && friend.achievement_count > 0 ? '<i class="fas fa-crown text-yellow-500 text-xl"></i>' : ''}
                                    <div class="w-12 h-12 rounded-full bg-gradient-to-br from-purple-400 to-purple-600 flex items-center justify-center text-white font-bold">
                                        ${friend.username.substring(0, 2).toUpperCase()}
                                    </div>
                                    <div class="text-left">
                                        <h3 class="font-semibold text-gray-800 dark:text-gray-200">${friend.username}</h3>
                                        <div class="text-xs text-gray-600 dark:text-gray-400">
                                            ${friend.recent_achievements && friend.recent_achievements.length > 0
                                                ? friend.recent_achievements.map(ach => `<i class="fas ${ach.icon} mr-1"></i>`).join('')
                                                : 'Henüz başarım yok'}
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-4">
                                    <div class="text-center">
                                        <div class="text-2xl font-bold ${friend.achievement_count > userCount ? 'text-green-500' : friend.achievement_count < userCount ? 'text-red-500' : 'text-yellow-500'}">${friend.achievement_count || 0}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">Başarım</div>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        ${friend.achievement_count > userCount
                                            ? `<i class="fas fa-arrow-up text-green-500"></i> +${friend.achievement_count - userCount}`
                                            : friend.achievement_count < userCount
                                                ? `<i class="fas fa-arrow-down text-red-500"></i> -${userCount - friend.achievement_count}`
                                                : '<i class="fas fa-equals text-yellow-500"></i> Eşit'}
                                    </div>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                `;
            } else {
                content.innerHTML = `
                    <div class="text-center py-8">
                        <i class="fas fa-users text-6xl text-gray-400 mb-4"></i>
                        <p class="text-xl text-gray-600 dark:text-gray-400 mb-2">Henüz arkadaşın yok</p>
                        <p class="text-sm text-gray-500 dark:text-gray-500 mb-4">
                            Arkadaş ekleyerek başarımlarını karşılaştırabilirsin
                        </p>
                        <a href="friends.php" class="inline-flex items-center px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg">
                            <i class="fas fa-user-plus mr-2"></i>
                            Arkadaş Ekle
                        </a>
                    </div>
                `;
            }
        } catch (error) {
            console.error('Achievement comparison error:', error);
            if (window.UICore && UICore.showToast) {
                UICore.showToast('Karşılaştırma yüklenirken hata oluştu', 'error');
            }
        }
    };

    const startLeaderboardUpdates = () => {
        if (appState.get('leaderboardInterval')) {
            clearInterval(appState.get('leaderboardInterval'));
        }
        updateLeaderboard();
        const intervalId = setInterval(updateLeaderboard, 60000);
        appState.set('leaderboardInterval', intervalId);
    };

    const stopLeaderboardUpdates = () => {
        if (appState.get('leaderboardInterval')) {
            clearInterval(appState.get('leaderboardInterval'));
            appState.set('leaderboardInterval', null);
        }
    };

    const updateAchievementProgress = async () => {
        // API henüz yüklenmediyse bekle - global api değişkenini kontrol et
        if (typeof api === 'undefined') {
            setTimeout(updateAchievementProgress, 1000);
            return;
        }

        try {
            const response = await api.call('get_achievement_progress');
            if (response.success) {
                renderAchievementProgress(response.data);
            } else {
                // Hata durumunda placeholder göster
                showProgressPlaceholder();
            }
        } catch (error) {
            showProgressPlaceholder();
        }
    };

    const renderAchievementProgress = (progressData) => {
        const container = document.getElementById('achievement-progress-list');
        const noProgressMessage = document.getElementById('no-progress-message');

        if (!container) {
            return;
        }

        if (!progressData || Object.keys(progressData).length === 0) {
            container.classList.add('hidden');
            if (noProgressMessage) {
                noProgressMessage.textContent = 'İlerleme verisi bulunamadı.';
                noProgressMessage.classList.remove('hidden');
            }
            return;
        }

        container.innerHTML = '';
        container.classList.remove('hidden');
        if (noProgressMessage) noProgressMessage.classList.add('hidden');

        Object.entries(progressData).forEach(([key, progress]) => {
            const progressPercentage = Math.round((progress.current / progress.target) * 100);
            const progressElement = document.createElement('div');
            progressElement.className = 'border dark:border-gray-700 rounded-lg p-4';

            progressElement.innerHTML = `
                <div class="flex justify-between items-center mb-2">
                    <div>
                        <h3 class="font-semibold text-gray-800 dark:text-gray-200">${progress.name}</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">${progress.description}</p>
                        ${progress.hint ? `<p class="text-sm text-blue-600 dark:text-blue-400 mt-1">${progress.hint}</p>` : ''}
                    </div>
                    <div class="text-right">
                        <span class="text-lg font-bold ${progress.completed ? 'text-green-600 dark:text-green-400' : 'text-gray-600 dark:text-gray-400'}">
                            ${progress.current}/${progress.target}
                        </span>
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            ${progressPercentage}%
                        </div>
                    </div>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                    <div class="h-2 rounded-full transition-all duration-300 ${
                        progress.completed
                            ? 'bg-green-500'
                            : progressPercentage >= 75
                                ? 'bg-yellow-500'
                                : progressPercentage >= 50
                                    ? 'bg-blue-500'
                                    : 'bg-gray-400'
                    }" style="width: ${Math.min(progressPercentage, 100)}%"></div>
                </div>
                ${progress.completed ? '<div class="mt-2 text-center"><span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">✓ Tamamlandı</span></div>' : ''}
            `;

            container.appendChild(progressElement);
        });
    };

    const renderCombinedAchievements = (achievementsData) => {
        const container = document.getElementById('achievements-list');
        const noAchievementsMessage = document.getElementById('no-achievements-message');

        if (!container) {
            // Silent return - achievements container not needed on all admin pages
            return;
        }

        if (!achievementsData || achievementsData.length === 0) {
            container.classList.add('hidden');
            if (noAchievementsMessage) {
                noAchievementsMessage.textContent = 'Başarım verisi bulunamadı.';
                noAchievementsMessage.classList.remove('hidden');
            }
            return;
        }

        container.innerHTML = '';
        container.classList.remove('hidden');
        if (noAchievementsMessage) noAchievementsMessage.classList.add('hidden');

        achievementsData.forEach(achievement => {
            const achievementElement = document.createElement('div');
            achievementElement.className = 'border dark:border-gray-700 rounded-lg p-4';

            if (achievement.earned) {
                // Kazanılmış başarım - Yeşil rozet + Tamamlandı
                achievementElement.innerHTML = `
                    <div class="flex items-center space-x-4">
                        <div class="w-12 h-12 rounded-full bg-green-500 flex items-center justify-center text-white text-xl">
                            <i class="fas ${achievement.icon || 'fa-trophy'}"></i>
                        </div>
                        <div class="flex-1">
                            <h3 class="font-semibold text-gray-800 dark:text-gray-200">${achievement.name}</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">${achievement.description}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">Kazanıldı: ${new Date(achievement.achieved_at).toLocaleDateString('tr-TR')}</p>
                        </div>
                        <div class="text-center">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                ✓ Tamamlandı
                            </span>
                        </div>
                    </div>
                `;
            } else if (achievement.progress) {
                // Devam eden başarım - Progress bar
                const progressPercentage = achievement.progress.percentage;
                achievementElement.innerHTML = `
                    <div class="flex justify-between items-center mb-2">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center text-gray-600 dark:text-gray-300">
                                <i class="fas ${achievement.icon || 'fa-target'}"></i>
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-800 dark:text-gray-200">${achievement.name}</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400">${achievement.description}</p>
                                ${achievement.progress.hint ? `<p class="text-sm text-blue-600 dark:text-blue-400 mt-1">${achievement.progress.hint}</p>` : ''}
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="text-lg font-bold text-gray-600 dark:text-gray-400">
                                ${achievement.progress.current}/${achievement.progress.target}
                            </span>
                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                ${progressPercentage}%
                            </div>
                        </div>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                        <div class="h-2 rounded-full transition-all duration-300 ${
                            progressPercentage >= 75
                                ? 'bg-yellow-500'
                                : progressPercentage >= 50
                                    ? 'bg-blue-500'
                                    : 'bg-gray-400'
                        }" style="width: ${Math.min(progressPercentage, 100)}%"></div>
                    </div>
                `;
            } else {
                // Başarım progress verisi yok - sadece açıklama
                achievementElement.innerHTML = `
                    <div class="flex items-center space-x-4">
                        <div class="w-10 h-10 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center text-gray-600 dark:text-gray-300">
                            <i class="fas ${achievement.icon || 'fa-question'}"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-800 dark:text-gray-200">${achievement.name}</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">${achievement.description}</p>
                        </div>
                    </div>
                `;
            }

            container.appendChild(achievementElement);
        });
    };

    const showAchievementsPlaceholder = () => {
        const container = document.getElementById('achievements-list');
        const noAchievementsMessage = document.getElementById('no-achievements-message');

        if (!container) return;

        container.classList.add('hidden');
        if (noAchievementsMessage) {
            noAchievementsMessage.textContent = 'Başarımlar şu an yüklenemiyor.';
            noAchievementsMessage.classList.remove('hidden');
        }
    };

    const showProgressPlaceholder = () => {
        const container = document.getElementById('achievement-progress-list');
        const noProgressMessage = document.getElementById('no-progress-message');

        if (!container) return;

        container.classList.add('hidden');
        if (noProgressMessage) {
            noProgressMessage.textContent = 'İlerleme verisi şu an yüklenemiyor.';
            noProgressMessage.classList.remove('hidden');
        }
    };

    return {
        init,
        updateAll,
        updateLeaderboard,
        startLeaderboardUpdates,
        stopLeaderboardUpdates,
        updateUserData,
        updateAchievements,
        updateAchievementProgress,
        updateCombinedAchievements,
    };
})(); 