document.addEventListener('DOMContentLoaded', () => {

    // --- UYGULAMA DURUMU (STATE) ---
    const state = {
        currentUser: null, // { id: 1, username: '...', role: '...' }
        difficulty: 'orta',
        currentCategory: null,
        soundEnabled: true,
        theme: 'light',
        leaderboardInterval: null,
        currentQuestionData: null,
        timerInterval: null,
        timeLeft: 30,
        lifelines: {
            fiftyFifty: 1,
            extraTime: 1
        }
    };

    // --- KATEGORİLER ---
    const categories = {
        'tarih': { icon: 'fa-history', color: 'blue' },
        'spor': { icon: 'fa-futbol', color: 'green' },
        'bilim': { icon: 'fa-atom', color: 'purple' },
        'sanat': { icon: 'fa-palette', color: 'yellow' },
        'coğrafya': { icon: 'fa-globe-americas', color: 'red' },
        'genel kültür': { icon: 'fa-brain', color: 'indigo' }
    };

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
            ui.init(dom);
            auth.init(dom);
            game.init(state, dom, ui);
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
        },

        onLoginSuccess(e) {
            const userData = e.detail;
            state.currentUser = userData;
            state.lifelines = { fiftyFifty: 1, extraTime: 1 };
            game.updateLifelineUI(); // Oyun başlangıcında jokerleri ayarla
            dom.welcomeMessage.textContent = `Hoş Geldin, ${userData.username}!`;
            dom.adminViewBtn.classList.toggle('hidden', userData.role !== 'admin');
            ui.showView('main-view');

            // Verileri yükle
            statsHandler.updateAll();
            statsHandler.startLeaderboardUpdates();
            game.populateCategories(categories);
        },
        
        onLogout() {
            state.currentUser = null;
            clearInterval(state.leaderboardInterval);
            ui.showView('auth-view');
            statsHandler.stopLeaderboardUpdates();
        },

        onShowAdminView() {
            ui.showView('admin-view');
            adminHandler.updateAll();
        },

        onShowMainView() {
            ui.showView('main-view');
            statsHandler.updateAll();
        }
    };

    // Uygulamayı Başlat
    App.init();
}); 