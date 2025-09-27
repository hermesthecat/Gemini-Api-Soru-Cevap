const statsHandler = (() => {
    let dom = {};

    const init = (domElements) => {
        dom = domElements;
        addEventListeners();
    };

    const addEventListeners = () => {
        // Avatar functionality removed - using initials instead
    };

    const updateCombinedAchievements = async () => {
        console.log('updateCombinedAchievements called');
        // API henüz yüklenmediyse bekle
        if (typeof api === 'undefined') {
            console.log('API not ready for combined achievements, retrying in 1 second');
            setTimeout(updateCombinedAchievements, 1000);
            return;
        }

        try {
            console.log('Calling get_combined_achievements API');
            const result = await api.call('get_combined_achievements', {}, 'POST', false);
            console.log('Combined achievements API response:', result);
            if (result && result.success) {
                renderCombinedAchievements(result.data);
            } else {
                console.error('Combined achievements yüklenemedi:', result.message);
                showAchievementsPlaceholder();
            }
        } catch (error) {
            console.error('Combined achievements hatası:', error);
            showAchievementsPlaceholder();
        }
    };

    const updateAchievements = async () => {
        const result = await api.call('get_user_achievements', {}, 'POST', false);
        if (result && result.success) {
            ui.renderAchievements(result.data);
        } else if (!result.success) {
            ui.renderAchievements([]);
        }
    };

    const updateLeaderboard = async () => {
        // Get leaderboard data
        const leaderboardResult = await api.call('get_leaderboard', {}, 'POST', false);

        // Get user's rank
        const userRankResult = await api.call('get_user_rank', {}, 'POST', false);

        if (leaderboardResult && leaderboardResult.success) {
            const userRank = userRankResult && userRankResult.success ? userRankResult.data : null;
            ui.renderLeaderboard(leaderboardResult.data, userRank);
        }
    };

    const updateUserData = async () => {
        const result = await api.call('get_user_data', {}, 'POST', false);
        if (result && result.success) {
            ui.renderUserData(result.data);
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
        console.log('updateAchievementProgress called');
        // API henüz yüklenmediyse bekle - global api değişkenini kontrol et
        if (typeof api === 'undefined') {
            console.log('API not ready, retrying in 1 second');
            setTimeout(updateAchievementProgress, 1000);
            return;
        }

        try {
            console.log('Calling get_achievement_progress API');
            const response = await api.call('get_achievement_progress');
            console.log('API response received:', response);
            if (response.success) {
                console.log('Calling renderAchievementProgress with:', response.data);
                renderAchievementProgress(response.data);
            } else {
                console.error('Achievement progress yüklenemedi:', response.message);
                // Hata durumunda placeholder göster
                showProgressPlaceholder();
            }
        } catch (error) {
            console.error('Achievement progress hatası:', error);
            showProgressPlaceholder();
        }
    };

    const renderAchievementProgress = (progressData) => {
        console.log('renderAchievementProgress called with:', progressData);
        const container = document.getElementById('achievement-progress-list');
        const noProgressMessage = document.getElementById('no-progress-message');

        console.log('Container:', container);
        console.log('No progress message:', noProgressMessage);

        if (!container) {
            console.error('achievement-progress-list container not found!');
            return;
        }

        if (!progressData || Object.keys(progressData).length === 0) {
            console.log('No progress data, showing placeholder');
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
            console.log('No achievements data, showing placeholder');
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