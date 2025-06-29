document.addEventListener('DOMContentLoaded', () => {

    // --- UYGULAMA DURUMU (STATE) ---
    const state = {
        currentUser: null, // { username: '...', role: '...' }
        difficulty: 'orta',
        currentCategory: null,
        soundEnabled: true,
        theme: 'light',
        leaderboardInterval: null,
        currentQuestionData: null
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
        // Auth
        loginForm: document.getElementById('login-form'),
        registerForm: document.getElementById('register-form'),
        showLoginBtn: document.getElementById('show-login-btn'),
        showRegisterBtn: document.getElementById('show-register-btn'),
        // Main View Header
        welcomeMessage: document.getElementById('welcome-message'),
        logoutBtn: document.getElementById('logout-btn'),
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
        // Stats & Leaderboard
        userTotalScore: document.getElementById('user-total-score'),
        categoryStatsBody: document.getElementById('category-stats-body'),
        noStatsMessage: document.getElementById('no-stats-message'),
        leaderboardList: document.getElementById('leaderboard-list'),
        leaderboardLoading: document.getElementById('leaderboard-loading'),
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

    // --- API İLETİŞİMİ ---
    const apiCall = async (action, data = {}, method = 'POST') => {
        const options = {
            method: method,
            headers: { 'Content-Type': 'application/json' },
        };
        if (method === 'POST') {
            options.body = JSON.stringify({ action, ...data });
        }
        try {
            const url = `api.php${method === 'GET' ? '?action=' + action : ''}`;
            const response = await fetch(url, options);
            if (!response.ok) {
                showToast(`Sunucu hatası: ${response.status}`, 'error');
                return null;
            }
            return await response.json();
        } catch (error) {
            showToast('Ağ hatası, sunucuya ulaşılamıyor.', 'error');
            console.error('API Call Error:', error);
            return null;
        }
    };

    // --- UI GÜNCELLEME FONKSİYONLARI ---
    const showView = (viewName) => {
        dom.authView.classList.add('hidden');
        dom.mainView.classList.add('hidden');
        if (viewName === 'auth') dom.authView.classList.remove('hidden');
        else if (viewName === 'main') dom.mainView.classList.remove('hidden');
    };

    const showLoading = (isLoading, text = 'Yükleniyor...') => {
        dom.loadingOverlay.classList.toggle('hidden', !isLoading);
        if (isLoading) dom.loadingOverlay.querySelector('#loading-text').textContent = text;
    };

    const showToast = (message, type = 'success', duration = 3000) => {
        dom.notificationText.textContent = message;
        dom.notificationToast.className = `fixed bottom-5 right-5 text-white py-2 px-4 rounded-lg shadow-lg text-sm ${type === 'success' ? 'bg-green-500' : 'bg-red-500'}`;
        dom.notificationToast.classList.remove('hidden');
        setTimeout(() => dom.notificationToast.classList.add('hidden'), duration);
    };

    const updateLeaderboard = async () => {
        const result = await apiCall('get_leaderboard', {}, 'GET');
        if (result && result.success) {
            dom.leaderboardLoading.classList.add('hidden');
            dom.leaderboardList.innerHTML = '';
            result.data.forEach((player, index) => {
                const li = document.createElement('li');
                li.className = 'flex justify-between items-center text-sm p-2 rounded-md';
                li.innerHTML = `
                    <div class="flex items-center">
                        <span class="font-bold w-6">${index + 1}.</span>
                        <span>${player.username}</span>
                    </div>
                    <span class="font-semibold text-blue-500">${player.score}</span>`;
                if (index === 0) li.classList.add('bg-yellow-100', 'dark:bg-yellow-800/50');
                if (index === 1) li.classList.add('bg-gray-200', 'dark:bg-gray-700/50');
                if (index === 2) li.classList.add('bg-yellow-50', 'dark:bg-yellow-900/50');
                dom.leaderboardList.appendChild(li);
            });
        }
    };

    const updateUserData = async () => {
        const result = await apiCall('get_user_data', {}, 'GET');
        if (result && result.success) {
            const { score, stats } = result.data;
            dom.userTotalScore.textContent = score;

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
                        <td class="py-2 px-2 text-center font-bold ${rate > 60 ? 'text-green-500' : 'text-yellow-500'}">${rate}%</td>`;
                    dom.categoryStatsBody.appendChild(tr);
                });
            } else {
                dom.noStatsMessage.classList.remove('hidden');
            }
        }
    };

    // --- SES ve TEMA ---
    const playSound = (sound) => state.soundEnabled && sound.play().catch(e => console.error(e));
    const applyTheme = (theme, isInitial = false) => {
        state.theme = theme;
        document.documentElement.classList.toggle('dark', theme === 'dark');
        dom.themeToggleLightIcon.classList.toggle('hidden', theme !== 'dark');
        dom.themeToggleDarkIcon.classList.toggle('hidden', theme === 'dark');
        if (!isInitial) localStorage.setItem('theme', theme);
    };
    const applySoundSetting = (enabled, isInitial = false) => {
        state.soundEnabled = enabled;
        dom.soundOnIcon.classList.toggle('hidden', !enabled);
        dom.soundOffIcon.classList.toggle('hidden', enabled);
        if (!isInitial) localStorage.setItem('soundEnabled', JSON.stringify(enabled));
    };

    // --- OYUN MANTIĞI ---
    const displayQuestion = (data) => {
        state.currentQuestionData = data;
        dom.questionContainer.classList.remove('hidden');
        dom.categorySelectionContainer.classList.add('hidden');
        dom.explanationContainer.classList.add('hidden');

        dom.questionCategory.textContent = `${data.kategori.charAt(0).toUpperCase() + data.kategori.slice(1)} - ${data.difficulty}`;
        dom.questionText.textContent = data.question;
        dom.optionsContainer.innerHTML = '';

        if (data.tip === 'dogru_yanlis') {
            dom.optionsContainer.className = 'grid grid-cols-1 gap-4 items-center';
            ['Doğru', 'Yanlış'].forEach(opt => {
                const btn = document.createElement('button');
                btn.className = 'option-button p-4 text-center rounded-lg border dark:border-gray-600 hover:bg-blue-50 dark:hover:bg-gray-700 w-full md:w-1/2 mx-auto';
                btn.dataset.answer = opt;
                btn.textContent = opt;
                dom.optionsContainer.appendChild(btn);
            });
        } else {
            dom.optionsContainer.className = 'grid grid-cols-1 md:grid-cols-2 gap-4 items-center';
            Object.entries(data.siklar).forEach(([key, value]) => {
                const btn = document.createElement('button');
                btn.className = 'option-button p-4 text-left rounded-lg border dark:border-gray-600 hover:bg-blue-50 dark:hover:bg-gray-700';
                btn.dataset.answer = key;
                btn.innerHTML = `<span class="font-semibold">${key}</span>) ${value}`;
                dom.optionsContainer.appendChild(btn);
            });
        }
        startTimer();
    };

    const startTimer = () => {
        let timeLeft = 30;
        dom.countdown.textContent = timeLeft;
        dom.timerContainer.classList.remove('hidden');
        clearInterval(state.timerInterval);
        state.timerInterval = setInterval(async () => {
            timeLeft--;
            dom.countdown.textContent = timeLeft;
            if (timeLeft <= 0) {
                clearInterval(state.timerInterval);
                playSound(dom.timeoutSound);
                await handleAnswerSubmission('TIMEOUT');
            }
        }, 1000);
    };

    const handleAnswerSubmission = async (answer) => {
        clearInterval(state.timerInterval);
        dom.timerContainer.classList.add('hidden');
        showLoading(true, 'Cevap kontrol ediliyor...');

        const result = await apiCall('submit_answer', {
            answer: answer,
            kategori: state.currentQuestionData.kategori
        });
        showLoading(false);

        if (result && result.success) {
            const { is_correct, correct_answer, explanation } = result.data;
            playSound(is_correct ? dom.correctSound : dom.incorrectSound);

            // Butonları renklendir
            document.querySelectorAll('.option-button').forEach(btn => {
                btn.disabled = true;
                if (btn.dataset.answer === correct_answer) {
                    btn.classList.add('bg-green-200', 'dark:bg-green-500', 'font-semibold');
                } else if (btn.dataset.answer === answer && !is_correct) {
                    btn.classList.add('bg-red-200', 'dark:bg-red-500', 'font-semibold');
                }
            });

            dom.explanationText.textContent = explanation;
            dom.explanationContainer.classList.remove('hidden');

            // Verileri yenile ve 3 saniye sonra yeni soruya geç
            await updateUserData();
            await updateLeaderboard();
            setTimeout(() => {
                dom.questionContainer.classList.add('hidden');
                dom.categorySelectionContainer.classList.remove('hidden');
            }, 3000);
        } else {
            showToast(result.message || 'Cevap gönderilirken bir hata oluştu.', 'error');
            dom.questionContainer.classList.add('hidden');
            dom.categorySelectionContainer.classList.remove('hidden');
        }
    };

    // --- OLAY DİNLEYİCİLERİ (EVENT LISTENERS) ---
    const addEventListeners = () => {
        // Auth
        dom.showLoginBtn.addEventListener('click', () => {
            dom.loginForm.classList.remove('hidden');
            dom.registerForm.classList.add('hidden');
            dom.showLoginBtn.classList.add('border-blue-500', 'text-blue-500');
            dom.showRegisterBtn.classList.remove('border-blue-500', 'text-blue-500');
        });
        dom.showRegisterBtn.addEventListener('click', () => {
            dom.loginForm.classList.add('hidden');
            dom.registerForm.classList.remove('hidden');
            dom.showLoginBtn.classList.remove('border-blue-500', 'text-blue-500');
            dom.showRegisterBtn.classList.add('border-blue-500', 'text-blue-500');
        });
        dom.loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            showLoading(true, 'Giriş yapılıyor...');
            const result = await apiCall('login', {
                username: e.target.elements['login-username'].value,
                password: e.target.elements['login-password'].value
            });
            showLoading(false);
            if (result && result.success) {
                showToast('Giriş başarılı, hoş geldiniz!', 'success');
                initializeUserSession(result.data);
            } else {
                showToast(result.message || 'Giriş başarısız.', 'error');
            }
        });
        dom.registerForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            showLoading(true, 'Kayıt oluşturuluyor...');
            const result = await apiCall('register', {
                username: e.target.elements['register-username'].value,
                password: e.target.elements['register-password'].value
            });
            showLoading(false);
            if (result && result.success) {
                showToast(result.message, 'success');
                dom.showLoginBtn.click(); // Kayıt sonrası giriş sekmesine yönlendir
            } else {
                showToast(result.message || 'Kayıt başarısız.', 'error');
            }
        });
        dom.logoutBtn.addEventListener('click', async () => {
            await apiCall('logout');
            state.currentUser = null;
            clearInterval(state.leaderboardInterval);
            showView('auth');
            showToast('Başarıyla çıkış yapıldı.', 'success');
        });

        // Ayarlar
        dom.themeToggle.addEventListener('click', () => applyTheme(state.theme === 'light' ? 'dark' : 'light'));
        dom.soundToggle.addEventListener('click', () => applySoundSetting(!state.soundEnabled));

        // Oyun
        dom.difficultyButtons.addEventListener('click', (e) => {
            const btn = e.target.closest('.difficulty-button');
            if (btn) {
                state.difficulty = btn.dataset.zorluk;
                document.querySelectorAll('.difficulty-button').forEach(b => b.classList.remove('bg-blue-500', 'text-white'));
                btn.classList.add('bg-blue-500', 'text-white');
            }
        });
        dom.categoryButtons.addEventListener('click', async (e) => {
            const btn = e.target.closest('.category-button');
            if (btn) {
                showLoading(true, 'Soru hazırlanıyor...');
                const result = await apiCall('get_question', {
                    kategori: btn.dataset.kategori,
                    difficulty: state.difficulty
                });
                showLoading(false);
                if (result && result.success) {
                    displayQuestion(result.data);
                } else {
                    showToast(result.message || 'Soru alınamadı.', 'error');
                }
            }
        });
        dom.optionsContainer.addEventListener('click', (e) => {
            const btn = e.target.closest('.option-button');
            if (btn) handleAnswerSubmission(btn.dataset.answer);
        });
    };

    // --- BAŞLANGIÇ FONKSİYONLARI ---
    const initializeUserSession = (userData) => {
        state.currentUser = userData;
        dom.welcomeMessage.textContent = `Hoş geldin, ${userData.username}!`;
        showView('main');

        // Verileri ve liderlik tablosunu ilk kez yükle
        updateUserData();
        updateLeaderboard();

        // Liderlik tablosunu periyodik olarak güncelle
        state.leaderboardInterval = setInterval(updateLeaderboard, 30000); // 30 saniyede bir
    };

    const populateCategories = () => {
        dom.categoryButtons.innerHTML = '';
        for (const [key, value] of Object.entries(categories)) {
            const btn = document.createElement('button');
            btn.className = `category-button bg-${value.color}-100 hover:bg-${value.color}-200 dark:bg-gray-700 dark:hover:bg-gray-600 p-4 rounded-lg`;
            btn.dataset.kategori = key;
            btn.innerHTML = `<i class="fas ${value.icon} mb-2"></i><span class="block">${key.charAt(0).toUpperCase() + key.slice(1)}</span>`;
            dom.categoryButtons.appendChild(btn);
        }
    };

    const initializeApp = async () => {
        // Ayarları yükle
        applyTheme(localStorage.getItem('theme') || 'light', true);
        applySoundSetting(JSON.parse(localStorage.getItem('soundEnabled') ?? 'true'), true);

        addEventListeners();
        populateCategories();

        // Oturum kontrolü yap
        showLoading(true, 'Oturum kontrol ediliyor...');
        const sessionResult = await apiCall('check_session', {}, 'GET');
        showLoading(false);

        if (sessionResult && sessionResult.success) {
            initializeUserSession(sessionResult.data);
        } else {
            showView('auth');
        }
    };

    // Uygulamayı Başlat
    initializeApp();
}); 