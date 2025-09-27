const statsHandler = (() => {
    let dom = {};

    const init = (domElements) => {
        dom = domElements;
        addEventListeners();
    };

    const addEventListeners = () => {
        // Avatar functionality removed - using initials instead
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
        updateAchievements();
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
    };
})(); 