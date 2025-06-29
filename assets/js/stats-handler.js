const statsHandler = (() => {
    let dom = {};

    const init = (domElements) => {
        dom = domElements;
        addEventListeners();
    };

    const handleAvatarUpdate = async (avatarFile) => {
        const result = await api.call('update_avatar', { avatar: avatarFile });
        if (result.success) {
            ui.showToast('Avatar başarıyla güncellendi!', 'success');

            // Update app state
            const currentUser = appState.get('currentUser');
            currentUser.avatar = result.data.avatar;
            appState.set('currentUser', currentUser);

            // Update UI
            ui.updateAvatarDisplay(result.data.avatar);
        }
    };

    const addEventListeners = () => {
        if (dom.avatarGrid) {
            dom.avatarGrid.addEventListener('click', (e) => {
                const avatarImg = e.target.closest('img');
                if (avatarImg && avatarImg.dataset.avatar) {
                    const selectedAvatar = avatarImg.dataset.avatar;
                    const currentUser = appState.get('currentUser');
                    if (selectedAvatar !== currentUser.avatar) {
                        handleAvatarUpdate(selectedAvatar);
                    }
                }
            });
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
        const result = await api.call('get_leaderboard', {}, 'POST', false);
        if (result && result.success) {
            ui.renderLeaderboard(result.data);
        }
    };

    const updateUserData = async () => {
        const result = await api.call('get_user_data', {}, 'POST', false);
        if (result && result.success) {
            ui.renderUserData(result.data);
            const currentUser = appState.get('currentUser');
            if (currentUser) {
                ui.populateAvatarGrid(currentUser.avatar);
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
        updateUserData
    };
})(); 