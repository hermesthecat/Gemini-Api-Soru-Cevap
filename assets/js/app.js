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
        userViewBtn: document.getElementById('user-view-btn'),
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

    ui.init(dom);

    // --- UI GÜNCELLEME FONKSİYONLARI ---
    const achievementData = {
        'ilk_adim': { name: 'İlk Adım', icon: 'fa-shoe-prints', color: 'green', description: 'İlk sorunu doğru cevapladın, tebrikler!' },
        'hiz_tutkunu': { name: 'Hız Tutkunu', icon: 'fa-bolt', color: 'blue', description: 'Bir soruyu 5 saniyeden kısa sürede doğru cevapladın!' },
        'seri_galibi_10': { name: 'Seri Galibi', icon: 'fa-trophy', color: 'yellow', description: 'Üst üste 10 soruyu doğru cevapladın!' },
        'seri_galibi_25': { name: 'Yenilmez', icon: 'fa-crown', color: 'red', description: 'İnanılmaz! 25 soruyu art arda doğru bildin!' },
        'merakli': { name: 'Meraklı', icon: 'fa-compass', color: 'purple', description: 'Tüm kategorilerden en az bir soru cevapladın!' },
        'puan_avcisi_1000': { name: 'Puan Avcısı', icon: 'fa-star', color: 'yellow', description: 'Toplamda 1000 puana ulaştın!' },
        'gece_kusu': { name: 'Gece Kuşu', icon: 'fa-moon', color: 'indigo', description: 'Gece 00:00 - 04:00 arası soru çözdün!' },
        'zorlu_rakip': { name: 'Zorlu Rakip', icon: 'fa-user-secret', color: 'gray', description: 'Zor seviyede 10 soruyu doğru cevapladın!' },
        'koleksiyoncu': { name: 'Koleksiyoncu', icon: 'fa-gem', color: 'pink', description: '10 farklı başarım rozeti topladın!' },
        'uzman_tarih': { name: 'Tarih Kurdu', icon: 'fa-history', color: 'blue', description: 'Tarih kategorisinde 20 soruya doğru cevap verdin!' },
        'kusursuz_tarih': { name: 'Kusursuz Tarihçi', icon: 'fa-scroll', color: 'blue', description: 'Tarih kategorisinde %100 başarıya ulaştın (min. 10 soru)!' },
        'uzman_spor': { name: 'Spor Gurusu', icon: 'fa-futbol', color: 'green', description: 'Spor kategorisinde 20 soruya doğru cevap verdin!' },
        'kusursuz_spor': { name: 'Kusursuz Atlet', icon: 'fa-running', color: 'green', description: 'Spor kategorisinde %100 başarıya ulaştın (min. 10 soru)!' },
        'uzman_bilim': { name: 'Bilim Kaşifi', icon: 'fa-atom', color: 'purple', description: 'Bilim kategorisinde 20 soruya doğru cevap verdin!' },
        'kusursuz_bilim': { name: 'Kusursuz Bilgin', icon: 'fa-flask', color: 'purple', description: 'Bilim kategorisinde %100 başarıya ulaştın (min. 10 soru)!' },
        'uzman_sanat': { name: 'Sanat Faresi', icon: 'fa-palette', color: 'yellow', description: 'Sanat kategorisinde 20 soruya doğru cevap verdin!' },
        'kusursuz_sanat': { name: 'Kusursuz Sanatçı', icon: 'fa-paint-brush', color: 'yellow', description: 'Sanat kategorisinde %100 başarıya ulaştın (min. 10 soru)!' },
        'uzman_coğrafya': { name: 'Dünya Gezgini', icon: 'fa-globe-americas', color: 'red', description: 'Coğrafya kategorisinde 20 soruya doğru cevap verdin!' },
        'kusursuz_coğrafya': { name: 'Kusursuz Kaşif', icon: 'fa-map-marked-alt', color: 'red', description: 'Coğrafya kategorisinde %100 başarıya ulaştın (min. 10 soru)!' },
        'uzman_genel kültür': { name: 'Her Şeyi Bilen', icon: 'fa-brain', color: 'indigo', description: 'Genel Kültür kategorisinde 20 soruya doğru cevap verdin!' },
        'kusursuz_genel kültür': { name: 'Kusursuz Dahi', icon: 'fa-lightbulb', color: 'indigo', description: 'Genel Kültür kategorisinde %100 başarıya ulaştın (min. 10 soru)!' }
    };

    const updateAchievements = async () => {
        try {
            const result = await apiCall('get_user_achievements', {}, 'GET');
            if (result && result.success && result.data.length > 0) {
                dom.achievementsList.innerHTML = '';
                dom.noAchievementsMessage.classList.add('hidden');
                result.data.forEach(ach => {
                    const achInfo = achievementData[ach.achievement_key];
                    if (achInfo) {
                        const achElement = document.createElement('div');
                        achElement.className = `text-center p-2 bg-${achInfo.color}-100 dark:bg-${achInfo.color}-900/50 rounded-lg w-20 h-20 flex flex-col justify-center items-center`;
                        achElement.title = `${achInfo.name}: ${achInfo.description}`;
                        achElement.innerHTML = `
                            <i class="fas ${achInfo.icon} fa-2x text-${achInfo.color}-500"></i>
                            <span class="text-xs mt-1 font-semibold">${achInfo.name}</span>
                        `;
                        dom.achievementsList.appendChild(achElement);
                    }
                });
            } else {
                dom.achievementsList.innerHTML = '';
                dom.noAchievementsMessage.classList.remove('hidden');
            }
        } catch (error) {
            ui.showToast(`Başarımlar yüklenemedi: ${error.message}`, 'error');
        }
    };

    const updateLeaderboard = async () => {
        try {
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
        } catch (error) {
            console.error("Liderlik tablosu hatası:", error);
            // Liderlik tablosu kritik değil, kullanıcıya hata göstermeyebiliriz.
        }
    };

    const updateUserData = async () => {
        try {
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
        } catch (error) {
            ui.showToast(`Kullanıcı verileri çekilemedi: ${error.message}`, 'error');
        }
    };

    // --- YENİ: ADMIN PANELİ FONKSİYONLARI ---
    const updateAdminDashboard = async () => {
        try {
            const result = await apiCall('admin_get_dashboard_data');
            if (result && result.success) {
                dom.adminTotalUsers.textContent = result.data.total_users;
                dom.adminTotalQuestions.textContent = result.data.total_questions_answered;
            }
        } catch (error) {
            ui.showToast(`Admin verileri alınamadı: ${error.message}`, 'error');
        }
    };

    const updateUserList = async () => {
        try {
            const result = await apiCall('admin_get_all_users');
            if (result && result.success) {
                dom.adminUserListBody.innerHTML = '';
                result.data.forEach(user => {
                    const tr = document.createElement('tr');
                    tr.className = 'bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600';
                    tr.dataset.userId = user.id;

                    const isCurrentUser = user.id === state.currentUser.id;

                    tr.innerHTML = `
                        <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">${user.username} ${isCurrentUser ? '<span class="text-xs text-blue-500">(Siz)</span>' : ''}</td>
                        <td class="px-6 py-4">${user.score || 0}</td>
                        <td class="px-6 py-4">
                            <select class="role-select bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 p-2" ${isCurrentUser ? 'disabled' : ''}>
                                <option value="user" ${user.role === 'user' ? 'selected' : ''}>User</option>
                                <option value="admin" ${user.role === 'admin' ? 'selected' : ''}>Admin</option>
                            </select>
                        </td>
                        <td class="px-6 py-4">${new Date(user.created_at).toLocaleDateString()}</td>
                        <td class="px-6 py-4">
                            <button class="delete-user-btn ml-2 text-red-500 hover:text-red-700 disabled:opacity-50 disabled:cursor-not-allowed" ${isCurrentUser ? 'disabled' : ''}><i class="fas fa-trash"></i></button>
                        </td>
                    `;
                    dom.adminUserListBody.appendChild(tr);
                });
            }
        } catch (error) {
            ui.showToast(`Kullanıcı listesi alınamadı: ${error.message}`, 'error');
        }
    };

    // --- SES ve TEMA ---
    const playSound = (soundName) => {
        if (!state.soundEnabled) return;
        const soundMap = {
            correct: dom.correctSound,
            incorrect: dom.incorrectSound,
            timeout: dom.timeoutSound
        };
        const soundToPlay = soundMap[soundName];
        if (soundToPlay) {
            soundToPlay.play().catch(e => console.error("Ses çalma hatası:", e));
        }
    };
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

    const updateLifelineUI = () => {
        const isTrueFalse = state.currentQuestionData && state.currentQuestionData.tip === 'dogru_yanlis';

        dom.lifelineFiftyFifty.disabled = state.lifelines.fiftyFifty <= 0 || isTrueFalse;
        dom.lifelineFiftyFifty.title = isTrueFalse ? "Bu soru tipinde kullanılamaz." : "50/50 Joker Hakkı";

        dom.lifelineExtraTime.disabled = state.lifelines.extraTime <= 0;

        const allUsed = state.lifelines.fiftyFifty <= 0 && state.lifelines.extraTime <= 0;
        dom.lifelineContainer.classList.toggle('hidden', allUsed);
    };

    // --- OLAY DİNLEYİCİLERİ (EVENT LISTENERS) ---
    const addEventListeners = () => {
        // Auth olaylarını dinle
        document.addEventListener('authSuccess', (e) => {
            initializeUserSession(e.detail.user);
        });

        document.addEventListener('logoutSuccess', () => {
            state.currentUser = null;
            clearInterval(state.leaderboardInterval);
            ui.showView('auth');
        });

        // Oyun modülünden gelen olayları dinle
        document.addEventListener('answerSubmitted', async (e) => {
            await updateUserData();
            await updateLeaderboard();

            const { new_achievements } = e.detail;
            if (new_achievements && new_achievements.length > 0) {
                setTimeout(() => {
                    new_achievements.forEach(key => {
                        const achInfo = achievementData[key];
                        if (achInfo) ui.showToast(`Yeni Başarım: ${achInfo.name}!`, 'achievement', 4000);
                    });
                    updateAchievements();
                }, 1000); // UI'ın güncellenmesine zaman tanır
            }
        });

        document.addEventListener('playSound', (e) => {
            playSound(e.detail.sound);
        });

        // Admin Panel
        dom.adminViewBtn.addEventListener('click', () => ui.showView('admin'));
        dom.userViewBtn.addEventListener('click', () => ui.showView('main'));
        dom.adminUserListBody.addEventListener('click', async (e) => {
            const deleteBtn = e.target.closest('.delete-user-btn');
            if (deleteBtn && confirm('Bu kullanıcıyı silmek istediğinizden emin misiniz? Bu işlem geri alınamaz.')) {
                const userId = deleteBtn.closest('tr').dataset.userId;
                ui.showLoading(true, 'Kullanıcı siliniyor...');
                try {
                    const result = await apiCall('admin_delete_user', { user_id: userId });
                    if (result && result.success) {
                        ui.showToast(result.message, 'success');
                        await updateUserList();
                        await updateAdminDashboard();
                    } else {
                        ui.showToast(result.message || 'İşlem başarısız.', 'error');
                    }
                } catch (error) {
                    ui.showToast(error.message, 'error');
                } finally {
                    ui.showLoading(false);
                }
            }
        });
        dom.adminUserListBody.addEventListener('change', async (e) => {
            const roleSelect = e.target.closest('.role-select');
            if (roleSelect) {
                const userId = roleSelect.closest('tr').dataset.userId;
                const newRole = roleSelect.value;
                ui.showLoading(true, 'Rol güncelleniyor...');
                try {
                    const result = await apiCall('admin_update_user_role', { user_id: userId, new_role: newRole });
                    if (result && result.success) {
                        ui.showToast(result.message, 'success');
                    } else {
                        ui.showToast(result.message || 'İşlem başarısız.', 'error');
                        await updateUserList(); // Hata durumunda listeyi eski haline getir
                    }
                } catch (error) {
                    ui.showToast(error.message, 'error');
                    await updateUserList(); // Hata durumunda listeyi eski haline getir
                } finally {
                    ui.showLoading(false);
                }
            }
        });

        // Ayarlar
        dom.themeToggle.addEventListener('click', () => applyTheme(state.theme === 'light' ? 'dark' : 'light'));
        dom.soundToggle.addEventListener('click', () => applySoundSetting(!state.soundEnabled));

        // Oyun
        dom.difficultyButtons.addEventListener('click', (e) => {
            const btn = e.target.closest('.difficulty-button');
            if (btn) {
                state.difficulty = btn.dataset.zorluk;
                document.querySelectorAll('.difficulty-button').forEach(b => {
                    b.classList.remove('bg-blue-500', 'text-white', 'font-semibold');
                    b.classList.add('bg-gray-200', 'dark:bg-gray-700');
                });
                btn.classList.add('bg-blue-500', 'text-white', 'font-semibold');
                btn.classList.remove('bg-gray-200', 'dark:bg-gray-700');
            }
        });
        dom.categoryButtons.addEventListener('click', async (e) => {
            const btn = e.target.closest('.category-button');
            if (btn) {
                ui.showLoading(true, 'Soru hazırlanıyor...');
                try {
                    const result = await apiCall('get_question', {
                        kategori: btn.dataset.kategori,
                        difficulty: state.difficulty
                    });
                    if (result && result.success) {
                        displayQuestion(result.data);
                    } else {
                        ui.showToast(result.message || 'Soru alınamadı.', 'error');
                    }
                } catch (error) {
                    ui.showToast(error.message, 'error');
                } finally {
                    ui.showLoading(false);
                }
            }
        });
        dom.optionsContainer.addEventListener('click', (e) => {
            const btn = e.target.closest('.option-button');
            if (btn) handleAnswerSubmission(btn.dataset.answer);
        });

        dom.lifelineFiftyFifty.addEventListener('click', () => {
            if (dom.lifelineFiftyFifty.disabled) return;

            playSound('correct');
            state.lifelines.fiftyFifty--;
            updateLifelineUI();

            const correctAnswer = state.currentQuestionData.correct_answer;
            const options = Array.from(dom.optionsContainer.querySelectorAll('.option-button'));
            const wrongOptions = options.filter(btn => btn.dataset.answer !== correctAnswer);

            wrongOptions.sort(() => 0.5 - Math.random()); // Rastgele karıştır

            wrongOptions[0].classList.add('opacity-20', 'pointer-events-none');
            wrongOptions[0].disabled = true;
            wrongOptions[1].classList.add('opacity-20', 'pointer-events-none');
            wrongOptions[1].disabled = true;
        });

        dom.lifelineExtraTime.addEventListener('click', () => {
            if (dom.lifelineExtraTime.disabled) return;

            playSound('correct');
            state.lifelines.extraTime--;
            updateLifelineUI();

            state.timeLeft += 15;
            dom.countdown.textContent = state.timeLeft;
        });
    };

    // --- BAŞLANGIÇ FONKSİYONLARI ---
    const initializeUserSession = async (userData) => {
        state.currentUser = userData;
        state.lifelines = { fiftyFifty: 1, extraTime: 1 };
        game.updateLifelineUI(); // Oyun başlangıcında jokerleri ayarla
        dom.welcomeMessage.textContent = `Hoş geldin, ${userData.username}!`;

        if (userData.role === 'admin') {
            dom.adminViewBtn.classList.remove('hidden');
            ui.showView('admin');
            await updateAdminDashboard();
            await updateUserList();
        } else {
            dom.adminViewBtn.classList.add('hidden');
            ui.showView('main');
        }

        await updateUserData();
        await updateLeaderboard();
        await updateAchievements();
        state.leaderboardInterval = setInterval(updateLeaderboard, 30000);
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
        applyTheme(localStorage.getItem('theme') || 'light', true);
        applySoundSetting(JSON.parse(localStorage.getItem('soundEnabled') ?? 'true'), true);

        auth.init(dom, ui);
        game.init(state, dom, ui);
        addEventListeners();
        populateCategories();

        ui.showLoading(true, 'Oturum kontrol ediliyor...');
        try {
            const sessionResult = await apiCall('check_session', {}, 'GET');
            if (sessionResult && sessionResult.success) {
                await initializeUserSession(sessionResult.data);
            } else {
                ui.showView('auth');
            }
        } catch (error) {
            ui.showToast(`Oturum kontrol edilemedi, lütfen tekrar giriş yapın: ${error.message}`, 'error');
            ui.showView('auth');
        } finally {
            ui.showLoading(false);
        }
    };

    initializeApp();
}); 