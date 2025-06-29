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
        adminViewBtn: document.getElementById('admin-view-btn'),
        userViewBtn: document.getElementById('user-view-btn'),
        // Sekmeler
        mainTabs: document.getElementById('main-tabs'),
        yarışmaTab: document.getElementById('yarışma-tab'),
        profilTab: document.getElementById('profil-tab'),
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
        // Admin View
        adminTotalUsers: document.getElementById('admin-total-users'),
        adminTotalQuestions: document.getElementById('admin-total-questions'),
        adminUserListBody: document.getElementById('admin-user-list-body'),
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
        // Sesler
        correctSound: document.getElementById('correct-sound'),
        incorrectSound: document.getElementById('incorrect-sound'),
        timeoutSound: document.getElementById('timeout-sound'),
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
        },

        onLoginSuccess(e) {
            const userData = e.detail;
            appState.set('currentUser', { id: userData.id, username: userData.username, role: userData.role });
            appState.set('csrfToken', userData.csrf_token);
            appState.set('lifelines', { fiftyFifty: 1, extraTime: 1, pass: 1 });
            
            game.updateLifelineUI();
            ui.renderWelcomeMessage(userData.username);
            ui.toggleAdminButton(userData.role === 'admin');
            ui.showView('main-view');

            statsHandler.updateAll();
            statsHandler.startLeaderboardUpdates();
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
        },

        onShowMainView() {
            ui.showView('main-view');
            statsHandler.updateAll();
        },

        onAnswerSubmitted(e) {
            // Cevap gönderildikten sonra istatistikleri ve başarımları güncelle
            statsHandler.updateUserData();
            statsHandler.updateAchievements();
            
            // Eğer yeni bir başarım kazanıldıysa, toast ile göster
            const { new_achievements } = e.detail;
            if (new_achievements && new_achievements.length > 0) {
                // Not: Bu sadece bir anahtar döner, başarım detaylarını almak için
                // appData veya state içinden bakmak gerekebilir. Şimdilik basit tutalım.
                ui.showToast(`Yeni başarım kazandın: ${new_achievements.join(', ')}!`, 'success');
            }
        },

        onPlaySound(e) {
            if (!appState.get('soundEnabled')) return;
            const sound = dom[`${e.detail.sound}Sound`];
            if (sound) {
                sound.currentTime = 0;
                sound.play();
            }
        }
    };

    // Uygulamayı Başlat
    App.init();
}); 