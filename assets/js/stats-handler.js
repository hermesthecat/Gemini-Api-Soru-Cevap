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


    return {
        init,
        updateAll,
        startLeaderboardUpdates,
        stopLeaderboardUpdates,
        updateUserData,
    };
})(); 