document.addEventListener('DOMContentLoaded', () => {

    // --- DOM ELEMENTLERİ ---
    const dom = {
        appContainer: document.getElementById('app-container'),
        authView: document.getElementById('auth-view'),
        mainView: document.getElementById('main-view'),
        adminView: document.getElementById('admin-view'),
        // Auth
        loginForm: document.getElementById('login-form'),
        registerForm: document.getElementById('register-form'),
        showLoginBtn: document.getElementById('show-login-btn'),
        showRegisterBtn: document.getElementById('show-register-btn'),
        // Main View Header
        welcomeMessage: document.getElementById('welcome-message'),
        logoutBtn: document.getElementById('logout-btn'),
        userCoinBalance: document.getElementById('user-coin-balance'),
        adminViewBtn: document.getElementById('admin-view-btn'),
        userViewBtn: document.getElementById('user-view-btn'),
        // Sekmeler
        mainTabs: document.getElementById('main-tabs'),
        yarışmaTab: document.getElementById('yarışma-tab'),
        profilTab: document.getElementById('profil-tab'),
        arkadaslarTab: document.getElementById('arkadaslar-tab'),
        magazaTab: document.getElementById('magaza-tab'),
        // Game
        gameContainer: document.getElementById('game-container'),
        categorySelectionContainer: document.getElementById('category-selection-container'),
        questionContainer: document.getElementById('question-container'),
        categoryButtons: document.getElementById('category-buttons'),
        difficultyButtons: document.getElementById('difficulty-buttons'),
        questionCategory: document.getElementById('question-category'),
        questionText: document.getElementById('question-text'),
        optionsContainer: document.getElementById('options-container'),
        explanationContainer: document.getElementById('explanation-container'),
        explanationText: document.getElementById('explanation-text'),
        timerContainer: document.getElementById('timer-container'),
        countdown: document.getElementById('countdown'),
        lifelineContainer: document.getElementById('lifeline-container'),
        lifelineFiftyFifty: document.getElementById('lifeline-fifty-fifty'),
        lifelineExtraTime: document.getElementById('lifeline-extra-time'),
        lifelinePass: document.getElementById('lifeline-pass'),
        // Stats & Leaderboard
        userTotalScore: document.getElementById('user-total-score'),
        categoryStatsBody: document.getElementById('category-stats-body'),
        noStatsMessage: document.getElementById('no-stats-message'),
        leaderboardList: document.getElementById('leaderboard-list'),
        leaderboardLoading: document.getElementById('leaderboard-loading'),
        achievementsList: document.getElementById('achievements-list'),
        noAchievementsMessage: document.getElementById('no-achievements-message'),
        avatarGrid: document.getElementById('avatar-grid'),
        userAvatarDisplay: document.getElementById('user-avatar-display'),
        // Admin View
        adminTotalUsers: document.getElementById('admin-total-users'),
        adminTotalQuestions: document.getElementById('admin-total-questions'),
        adminUserListBody: document.getElementById('admin-user-list-body'),
        adminTabs: document.getElementById('admin-tabs'),
        adminUsersTab: document.getElementById('admin-users-tab'),
        adminAnnouncementsTab: document.getElementById('admin-announcements-tab'),
        adminStatsTab: document.getElementById('admin-stats-tab'),
        createAnnouncementForm: document.getElementById('create-announcement-form'),
        announcementsListBody: document.getElementById('announcements-list-body'),
        categoryChart: document.getElementById('category-chart'),
        answersChart: document.getElementById('answers-chart'),
        usersChart: document.getElementById('users-chart'),
        // Ayarlar
        themeToggle: document.getElementById('theme-toggle'),
        themeToggleDarkIcon: document.getElementById('theme-toggle-dark-icon'),
        themeToggleLightIcon: document.getElementById('theme-toggle-light-icon'),
        soundToggle: document.getElementById('sound-toggle'),
        soundOnIcon: document.getElementById('sound-on-icon'),
        soundOffIcon: document.getElementById('sound-off-icon'),
        // Genel UI
        loadingOverlay: document.getElementById('loading-overlay'),
        notificationToast: document.getElementById('notification-toast'),
        notificationText: document.getElementById('notification-text'),
        // Başarım Modalı
        achievementModal: document.getElementById('achievement-modal'),
        // Duyuru Modalı
        announcementModal: document.getElementById('announcement-modal'),
        announcementModalCloseBtn: document.getElementById('announcement-modal-close-btn'),
        announcementModalOkBtn: document.getElementById('announcement-modal-ok-btn'),
        announcementModalBody: document.getElementById('announcement-modal-body'),
        announcementsBtn: document.getElementById('announcements-btn'),
        announcementsBadge: document.getElementById('announcements-badge'),
        // Arkadaşlar Sekmesi
        friendSearchInput: document.getElementById('friend-search-input'),
        friendSearchResults: document.getElementById('friend-search-results'),
        pendingRequestsList: document.getElementById('pending-requests-list'),
        noPendingRequests: document.getElementById('no-pending-requests'),
        friendsList: document.getElementById('friends-list'),
        noFriends: document.getElementById('no-friends'),
        // Düello Modalı
        duelModal: document.getElementById('duel-modal'),
        duelModalCloseBtn: document.getElementById('duel-modal-close-btn'),
        duelOpponentName: document.getElementById('duel-opponent-name'),
        duelCategorySelect: document.getElementById('duel-category-select'),
        duelDifficultySelect: document.getElementById('duel-difficulty-select'),
        duelSendChallengeBtn: document.getElementById('duel-send-challenge-btn'),
        // Düello Listesi
        duelsList: document.getElementById('duels-list'),
        noDuels: document.getElementById('no-duels'),
        // Günlük Görevler
        dailyQuestsContainer: document.getElementById('daily-quests-container'),
        dailyQuestsList: document.getElementById('daily-quests-list'),
        dailyQuestsLoading: document.getElementById('daily-quests-loading'),
        // Mağaza
        shopItemsContainer: document.getElementById('shop-items-container'),
        // Düello Oyun Ekranı
        duelGameView: document.getElementById('duel-game-view'),
        duelGameOpponentName: document.getElementById('duel-game-opponent-name'),
        duelGameProgress: document.getElementById('duel-game-progress'),
        duelMyUsername: document.getElementById('duel-my-username'),
        duelMyScore: document.getElementById('duel-my-score'),
        duelQuestionContainer: document.getElementById('duel-question-container'),
        duelQuestionText: document.getElementById('duel-question-text'),
        duelOptionsContainer: document.getElementById('duel-options-container'),
        duelExplanationContainer: document.getElementById('duel-explanation-container'),
        duelExplanationText: document.getElementById('duel-explanation-text'),
        duelNextQuestionBtn: document.getElementById('duel-next-question-btn'),
        duelSummaryContainer: document.getElementById('duel-summary-container'),
        duelSummaryTitle: document.getElementById('duel-summary-title'),
        duelSummaryIcon: document.getElementById('duel-summary-icon'),
        duelSummaryText: document.getElementById('duel-summary-text'),
        duelSummaryMyName: document.getElementById('duel-summary-my-name'),
        duelSummaryMyScore: document.getElementById('duel-summary-my-score'),
        duelSummaryOpponentName: document.getElementById('duel-summary-opponent-name'),
        duelSummaryOpponentScore: document.getElementById('duel-summary-opponent-score'),
        duelBackToFriendsBtn: document.getElementById('duel-back-to-friends-btn'),
        // Sesler
        correctSound: document.getElementById('correct-sound'),
        incorrectSound: document.getElementById('incorrect-sound'),
        timeoutSound: document.getElementById('timeout-sound'),
        achievementSound: document.getElementById('achievement-sound'),
    };

    /**
     * Uygulama yaşam döngüsünü yöneten ana fonksiyonlar.
     */
    const App = {
        init() {
            // Tüm modülleri DOM elementleriyle başlat
            // Modüller ihtiyaç duydukları diğer modüllere (örn: appState, ui) global olarak erişir.
            ui.init(dom);
            auth.init(dom);
            game.init(dom);
            statsHandler.init(dom);
            adminHandler.init(dom);
            settingsHandler.init(dom);
            friendsHandler.init(dom);
            duelHandler.init(dom);
            questHandler.init(dom);
            announcementHandler.init(dom);
            shopHandler.init(dom);

            // Özel olayları dinle
            this.addEventListeners();

            // Oturum kontrolü ile uygulamayı başlat
            auth.checkUserSession();
        },

        addEventListeners() {
            // Modüllerden gelen merkezi olayları dinle
            document.addEventListener('loginSuccess', this.onLoginSuccess);
            document.addEventListener('logoutSuccess', this.onLogout);
            document.addEventListener('showAdminView', this.onShowAdminView);
            document.addEventListener('showMainView', this.onShowMainView);
            document.addEventListener('answerSubmitted', this.onAnswerSubmitted);
            document.addEventListener('playSound', this.onPlaySound);
            document.addEventListener('tabChanged', this.onTabChanged);

            dom.mainTabs?.addEventListener('click', (e) => {
                const tabButton = e.target.closest('.main-tab-button');
                if (tabButton && tabButton.dataset.tab) {
                    ui.showTab(tabButton.dataset.tab);
                }
            });
        },

        onLoginSuccess(e) {
            const { data: userData, daily_reward } = e.detail;
            appState.set('currentUser', { id: userData.id, username: userData.username, role: userData.role, avatar: userData.avatar });
            appState.set('csrfToken', userData.csrf_token);
            appState.set('lifelines', userData.lifelines);

            game.updateLifelineUI();
            ui.renderWelcomeMessage(userData.username, userData.avatar);
            ui.updateCoinBalance(userData.coins);
            ui.toggleAdminButton(userData.role === 'admin');
            ui.showView('main-view');
            ui.showTab('yarışma');

            // Günlük giriş ödülü bildirimini işle
            if (daily_reward) {
                // Kullanıcının ana arayüzü görmesi için kısa bir gecikme ekle
                setTimeout(() => {
                    const message = `🎉 Günlük giriş ödülünü topladın: +${daily_reward.coins_earned} Jeton! Serin ${daily_reward.streak} güne ulaştı!`;
                    ui.showToast(message, 'success');
                    // Ödül sesi çal
                    this.onPlaySound({ detail: { sound: 'achievement' } });
                }, 1000); // 1 saniye gecikme
            }

            statsHandler.updateAll();
            statsHandler.startLeaderboardUpdates();
            friendsHandler.updateAll();
            questHandler.updateQuests();
            announcementHandler.checkForAnnouncements();
        },

        onLogout() {
            appState.set('currentUser', null);
            appState.set('csrfToken', null); // Oturum kapatılınca token'ı temizle
            statsHandler.stopLeaderboardUpdates();
            ui.showView('auth-view');
        },

        onShowAdminView() {
            ui.showView('admin-view');
            adminHandler.updateAll();
            announcementHandler.updateAnnouncementsList();
            ui.showAdminTab('users');
        },

        onShowMainView() {
            ui.showView('main-view');
            statsHandler.updateAll();
            friendsHandler.updateAll();
            questHandler.updateQuests();
        },

        async onAnswerSubmitted(e) {
            const { new_achievements, completed_quests } = e.detail;

            // Tamamlanan görevler için bildirim göster
            questHandler.handleQuestCompletion(completed_quests);

            // Önce istatistikleri ve arkaplan listesini güncelle
            statsHandler.updateUserData();
            statsHandler.updateAchievements();

            // Eğer yeni bir başarım kazanıldıysa, modal ile göster
            if (new_achievements && new_achievements.length > 0) {
                // Diğer işlemlerin devam etmesi için modal gösterimini geciktir
                setTimeout(async () => {
                    for (const achievement of new_achievements) {
                        this.onPlaySound({ detail: { sound: 'achievement' } });
                        await ui.showAchievementModal(achievement);
                    }
                }, 500); // 500ms gecikme
            }
        },

        onPlaySound(e) {
            if (!appState.get('soundEnabled')) return;
            const sound = dom[`${e.detail.sound}Sound`];
            if (sound) {
                sound.currentTime = 0;
                sound.play();
            }
        },

        onTabChanged(e) {
            const tabId = e.detail.tabId;
            if (tabId === 'arkadaslar') {
                friendsHandler.updateAll();
            } else if (tabId === 'profil') {
                statsHandler.updateAll();
            } else if (tabId === 'magaza') {
                shopHandler.loadShop();
            }
            // Diğer sekmeler için de güncellemeler buraya eklenebilir
        }
    };

    // Uygulamayı Başlat
    App.init();
}); 