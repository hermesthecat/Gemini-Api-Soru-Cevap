const statsHandler = (() => {
    let dom = {};

    const init = (domElements) => {
        dom = domElements;
    };

    const updateAchievements = async () => {
        try {
            const result = await api.call('get_user_achievements');
            if (result && result.success) {
                ui.renderAchievements(result.data, appData.achievements);
            } else {
                ui.renderAchievements([], appData.achievements); // Hata durumunda boş liste gönder
            }
        } catch (error) {
            ui.showToast(`Başarımlar yüklenemedi: ${error.message}`, 'error');
        }
    };

    const updateLeaderboard = async () => {
        try {
            const result = await api.call('get_leaderboard');
            if (result && result.success) {
                ui.renderLeaderboard(result.data);
            }
        } catch (error) {
            console.error("Liderlik tablosu hatası:", error);
            // Liderlik tablosu kritik değil, kullanıcıya hata göstermeyebiliriz.
        }
    };

    const updateUserData = async () => {
        try {
            const result = await api.call('get_user_data');
            if (result && result.success) {
                ui.renderUserData(result.data);
            }
        } catch (error) {
            ui.showToast(`Kullanıcı verileri çekilemedi: ${error.message}`, 'error');
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
        updateLeaderboard(); // İlk başta hemen yükle
        const intervalId = setInterval(updateLeaderboard, 60000); // Her 60 saniyede bir güncelle
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
        stopLeaderboardUpdates
    };
})(); 