document.addEventListener('DOMContentLoaded', () => {
    // --- DOM Elementleri ---
    const rootHtml = document.documentElement;
    const themeToggle = document.getElementById('theme-toggle');
    const themeToggleDarkIcon = document.getElementById('theme-toggle-dark-icon');
    const themeToggleLightIcon = document.getElementById('theme-toggle-light-icon');
    const loadingOverlay = document.getElementById('loading-overlay');
    const loadingText = document.getElementById('loading-text');
    const errorContainer = document.getElementById('error-container');
    const errorMessage = document.getElementById('error-message');
    const categorySelectionContainer = document.getElementById('category-selection-container');
    const questionContainer = document.getElementById('question-container');
    const statsContainer = document.getElementById('stats-container');
    const categoryButtons = document.getElementById('category-buttons');
    const difficultyButtons = document.getElementById('difficulty-buttons');
    const questionCategory = document.getElementById('question-category');
    const changeCategoryButton = document.getElementById('change-category-button');
    const questionText = document.getElementById('question-text');
    const optionsContainer = document.getElementById('options-container');
    const timerContainer = document.getElementById('timer-container');
    const countdownEl = document.getElementById('countdown');
    const resetStatsButton = document.getElementById('reset-stats-button');
    const answerFeedbackOverlay = document.getElementById('answer-feedback-overlay');
    const answerFeedbackBox = document.getElementById('answer-feedback-box');
    const explanationContainer = document.getElementById('explanation-container');
    const explanationText = document.getElementById('explanation-text');


    // --- Uygulama Durumu (State) ---
    let state = {
        stats: {}, // { tarih: { total_questions: 5, correct_answers: 3 }, spor: { ... } }
        history: [],
        currentQuestion: null, // Mevcut soru, şıkları ve tipi tutmak için
        difficulty: 'orta', // Varsayılan zorluk seviyesi
        theme: 'light', // Varsayılan tema
    };
    let timer;

    // --- Tema Fonksiyonları ---
    const applyTheme = (theme, isInitial = false) => {
        state.theme = theme;
        if (theme === 'dark') {
            rootHtml.classList.add('dark');
            themeToggleLightIcon.classList.remove('hidden');
            themeToggleDarkIcon.classList.add('hidden');
        } else {
            rootHtml.classList.remove('dark');
            themeToggleDarkIcon.classList.remove('hidden');
            themeToggleLightIcon.classList.add('hidden');
        }
        // Başlangıç yüklemesi değilse (yani kullanıcı butona tıkladıysa) ayarı kaydet
        if (!isInitial) {
            saveStateToLocalStorage();
        }
    };

    const handleThemeToggle = () => {
        const newTheme = state.theme === 'light' ? 'dark' : 'light';
        applyTheme(newTheme);
    };


    // --- LocalStorage Fonksiyonları ---
    const saveStateToLocalStorage = () => {
        // Sadece kalıcı olması gereken verileri kaydet
        const persistentState = {
            stats: state.stats,
            history: state.history,
            theme: state.theme
        };
        localStorage.setItem('quizAppState', JSON.stringify(persistentState));
    };

    const loadStateFromLocalStorage = () => {
        const savedStateJSON = localStorage.getItem('quizAppState');
        if (savedStateJSON) {
            const savedState = JSON.parse(savedStateJSON);
            state.stats = savedState.stats || {};
            state.history = savedState.history || [];
            // Temayı state'e yükle, ancak henüz uygulama (initialize halledecek)
            state.theme = savedState.theme;
        }
    };


    // --- API Fonksiyonları ---
    const apiCall = async (action, data = {}) => {
        showLoading(true);
        try {
            const response = await fetch('api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action,
                    ...data
                })
            });
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const result = await response.json();
            if (!result.success) {
                throw new Error(result.message || 'API\'den beklenen yanıt alınamadı.');
            }
            return result.data;
        } catch (error) {
            showError(error.message);
            console.error('API Hatası:', error);
            return null;
        } finally {
            showLoading(false);
        }
    };

    // --- UI Güncelleme Fonksiyonları ---
    const showLoading = (isLoading, text = 'Yükleniyor...') => {
        loadingText.textContent = text;
        loadingOverlay.classList.toggle('hidden', !isLoading);
    };

    const showError = (message) => {
        errorMessage.textContent = message;
        errorContainer.classList.remove('hidden');
    };

    const clearError = () => {
        errorContainer.classList.add('hidden');
    };

    const showView = (viewName) => {
        categorySelectionContainer.classList.add('hidden');
        questionContainer.classList.add('hidden');
        if (viewName === 'categories') categorySelectionContainer.classList.remove('hidden');
        if (viewName === 'question') questionContainer.classList.remove('hidden');
    };

    const updateStatsUI = (stats) => {
        // Genel istatistikleri hesapla
        let overallTotal = 0;
        let overallCorrect = 0;
        const categoryStatsBody = document.getElementById('category-stats-body');
        const noStatsMessage = document.getElementById('no-stats-message');
        const statsTable = document.getElementById('stats-table');

        categoryStatsBody.innerHTML = ''; // Önceki verileri temizle

        const categories = Object.keys(stats);

        if (categories.length === 0) {
            noStatsMessage.classList.remove('hidden');
            statsTable.classList.add('hidden');
        } else {
            noStatsMessage.classList.add('hidden');
            statsTable.classList.remove('hidden');

            categories.sort().forEach(category => {
                const categoryData = stats[category];
                overallTotal += categoryData.total_questions;
                overallCorrect += categoryData.correct_answers;
                const rate = categoryData.total_questions > 0 ? Math.round((categoryData.correct_answers / categoryData.total_questions) * 100) : 0;
                const categoryName = category.charAt(0).toUpperCase() + category.slice(1);

                const row = `
                    <tr class="border-b dark:border-gray-700">
                        <td class="py-2 px-4 font-semibold">${categoryName}</td>
                        <td class="py-2 px-4 text-center">${categoryData.total_questions}</td>
                        <td class="py-2 px-4 text-center">${categoryData.correct_answers}</td>
                        <td class="py-2 px-4 text-center font-bold ${rate > 60 ? 'text-green-600' : 'text-yellow-600'}">${rate}%</td>
                    </tr>
                `;
                categoryStatsBody.innerHTML += row;
            });
        }

        // Genel istatistikleri arayüzde güncelle
        const overallRate = overallTotal > 0 ? Math.round((overallCorrect / overallTotal) * 100) : 0;
        document.getElementById('total-questions').textContent = overallTotal;
        document.getElementById('correct-answers').textContent = overallCorrect;
        document.getElementById('success-rate').textContent = `${overallRate}%`;
    };

    const updateHistoryUI = (history) => {
        const container = document.getElementById('history-container');
        container.innerHTML = '';
        if (!history || history.length === 0) {
            container.innerHTML = '<p class="text-gray-500 dark:text-gray-400 text-center">Henüz hiç soru cevaplamadınız.</p>';
            return;
        }
        history.forEach(item => {
            const isCorrectClass = item.is_correct ?
                'bg-green-100 dark:bg-green-900/50 border-green-500 dark:border-green-700' :
                'bg-red-100 dark:bg-red-900/50 border-red-500 dark:border-red-700';

            let userAnswerText, correctAnswerText;
            if (item.tip === 'dogru_yanlis') {
                userAnswerText = item.user_answer;
                correctAnswerText = item.correct_answer;
            } else {
                userAnswerText = item.siklar ? (item.siklar[item.user_answer] || 'Cevap Bulunamadı') : 'Cevap Bulunamadı';
                correctAnswerText = item.siklar ? item.siklar[item.correct_answer] : 'Doğru Cevap Bulunamadı';
            }

            const userAnswerDisplay = item.user_answer ? (item.tip === 'coktan_secmeli' ? `${item.user_answer}) ` : '') + userAnswerText : 'Süre Doldu';

            let historyHtml = `
                <div class="p-3 rounded-lg border-l-4 ${isCorrectClass}">
                    <p class="font-semibold mb-1">${item.question}</p>
                    <p class="text-sm">Sizin Cevabınız: <span class="font-bold">${userAnswerDisplay}</span></p>`;
            if (!item.is_correct) {
                const correctAnswerDisplay = (item.tip === 'coktan_secmeli' ? `${item.correct_answer}) ` : '') + correctAnswerText;
                historyHtml += `<p class="text-sm">Doğru Cevap: <span class="font-bold">${correctAnswerDisplay}</span></p>`;
            }
            historyHtml += '</div>';
            container.innerHTML += historyHtml;
        });
    };

    const displayQuestion = (data) => {
        state.currentQuestion = data; // Mevcut soruyu state'e kaydet
        explanationContainer.classList.add('hidden'); // Yeni soruda açıklamayı gizle
        const kategoriText = data.kategori.charAt(0).toUpperCase() + data.kategori.slice(1);
        const zorlukText = data.difficulty.charAt(0).toUpperCase() + data.difficulty.slice(1);
        questionCategory.innerHTML = `${kategoriText} <span class="font-normal text-gray-500">- ${zorlukText}</span>`;
        questionText.textContent = data.question;
        optionsContainer.innerHTML = '';
        optionsContainer.classList.toggle('md:grid-cols-1', data.tip === 'dogru_yanlis');
        optionsContainer.classList.toggle('md:grid-cols-2', data.tip !== 'dogru_yanlis');


        if (data.tip === 'coktan_secmeli') {
            ['A', 'B', 'C', 'D'].forEach(opt => {
                if (data.siklar[opt]) {
                    const button = document.createElement('button');
                    button.className = 'option-button p-4 text-left rounded-lg border border-gray-300 hover:bg-blue-50 transition-colors dark:bg-gray-800 dark:border-gray-600 dark:hover:bg-gray-700';
                    button.dataset.answer = opt;
                    button.innerHTML = `<span class="font-semibold">${opt}</span>) ${data.siklar[opt]}`;
                    optionsContainer.appendChild(button);
                }
            });
        } else if (data.tip === 'dogru_yanlis') {
            ['Doğru', 'Yanlış'].forEach(opt => {
                const button = document.createElement('button');
                button.className = 'option-button p-4 text-center rounded-lg border border-gray-300 hover:bg-blue-50 transition-colors dark:bg-gray-800 dark:border-gray-600 dark:hover:bg-gray-700 md:col-span-1 md:w-1/2 md:mx-auto';
                button.dataset.answer = opt;
                button.textContent = opt;
                optionsContainer.appendChild(button);
            });
        }

        showView('question');
        startTimer();
    };

    const showAnswerFeedback = (isCorrect) => {
        answerFeedbackOverlay.classList.remove('hidden');
        answerFeedbackBox.textContent = isCorrect ? 'Doğru!' : 'Yanlış!';
        answerFeedbackBox.className = `p-8 rounded-lg text-white text-3xl font-bold answer-feedback ${isCorrect ? 'bg-green-500' : 'bg-red-500'}`;
        setTimeout(() => {
            answerFeedbackOverlay.classList.add('hidden');
        }, 2000);
    };

    // --- Zamanlayıcı Fonksiyonları ---
    const startTimer = () => {
        clearInterval(timer);
        let timeLeft = 30;
        countdownEl.textContent = timeLeft;
        countdownEl.classList.remove('text-red-600');
        timerContainer.classList.remove('hidden');

        timer = setInterval(async () => {
            timeLeft--;
            countdownEl.textContent = timeLeft;
            if (timeLeft <= 10) countdownEl.classList.add('text-red-600');
            if (timeLeft <= 0) {
                clearInterval(timer);
                timerContainer.classList.add('hidden');
                showLoading(true, 'Süre Doldu!');
                await handleAnswerSubmission('TIMEOUT'); // Süre dolduğunu belirtmek için özel bir değer
                showLoading(false);
                showView('categories');
            }
        }, 1000);
    };

    const stopTimer = () => {
        clearInterval(timer);
        timerContainer.classList.add('hidden');
    };

    // --- Olay İşleyici (Event Handler) Fonksiyonları ---
    const handleCategorySelection = async (e) => {
        const button = e.target.closest('.category-button');
        if (!button) return;
        clearError();
        const kategori = button.dataset.kategori;
        const data = await apiCall('get_question', {
            kategori: kategori,
            difficulty: state.difficulty
        });
        if (data) {
            displayQuestion(data);
        }
    };

    const handleDifficultySelection = (e) => {
        const button = e.target.closest('.difficulty-button');
        if (!button) return;

        // State'i güncelle
        state.difficulty = button.dataset.zorluk;

        // Arayüzü güncelle
        document.querySelectorAll('.difficulty-button').forEach(btn => {
            btn.classList.remove('bg-blue-500', 'text-white', 'font-semibold');
            btn.classList.add('bg-gray-200', 'dark:bg-gray-700', 'hover:bg-gray-300', 'dark:hover:bg-gray-600');
        });
        button.classList.add('bg-blue-500', 'text-white', 'font-semibold');
        button.classList.remove('bg-gray-200', 'dark:bg-gray-700', 'hover:bg-gray-300', 'dark:hover:bg-gray-600');
    };

    const handleAnswerSubmission = async (answer) => {
        stopTimer();
        const data = await apiCall('submit_answer', {
            answer
        });
        if (data) {
            // --- YENİ: Gelişmiş Geri Bildirim ---
            // Tüm butonların tıklanabilirliğini kaldır ve hover efektini sıfırla.
            document.querySelectorAll('.option-button').forEach(btn => {
                btn.disabled = true;
                btn.classList.remove('hover:bg-blue-50', 'dark:hover:bg-gray-700');

                // Doğru cevabı her zaman yeşil yap
                if (btn.dataset.answer === data.correct_answer) {
                    btn.classList.add('bg-green-200', 'dark:bg-green-500', 'border-green-500', 'dark:border-green-400', 'font-semibold', 'dark:text-white');
                }
                // Kullanıcının cevabı yanlışsa, onu kırmızı yap
                else if (btn.dataset.answer === answer && !data.is_correct) {
                    btn.classList.add('bg-red-200', 'dark:bg-red-500', 'border-red-500', 'dark:border-red-400', 'font-semibold', 'dark:text-white');
                }
            });

            // Açıklamayı göster
            if (data.explanation) {
                explanationText.textContent = data.explanation;
                explanationContainer.classList.remove('hidden');
            }
            // --- BİTTİ: Gelişmiş Geri Bildirim ---

            // Kategoriye özel istatistikleri güncelle
            const category = state.currentQuestion.kategori;
            if (!state.stats[category]) {
                state.stats[category] = {
                    total_questions: 0,
                    correct_answers: 0
                };
            }
            state.stats[category].total_questions++;
            if (data.is_correct) {
                state.stats[category].correct_answers++;
            }

            // Geçmiş öğesini oluştur
            const historyItem = {
                tip: state.currentQuestion.tip,
                question: state.currentQuestion.question,
                siklar: state.currentQuestion.siklar, // Çoktan seçmeli için
                user_answer: answer === 'TIMEOUT' ? null : answer,
                correct_answer: data.correct_answer,
                is_correct: data.is_correct,
            };
            state.history.unshift(historyItem);
            if (state.history.length > 5) {
                state.history.pop();
            }

            // Yeni durumu kaydet ve UI'ı güncelle
            saveStateToLocalStorage();
            updateStatsUI(state.stats);
            updateHistoryUI(state.history);

            showAnswerFeedback(data.is_correct);
            setTimeout(() => showView('categories'), 2000); // 2 saniye sonra kategori seçimine dön
        } else {
            showView('categories'); // Hata olursa direk kategori seçimine dön
        }
        return data; // for timeout
    };

    const handleOptionClick = async (e) => {
        const button = e.target.closest('.option-button');
        if (!button) return;

        // Butonları pasif yap
        document.querySelectorAll('.option-button').forEach(btn => btn.disabled = true);

        const answer = button.dataset.answer;

        await handleAnswerSubmission(answer);
    };

    const handleResetStats = async () => {
        // State'i sıfırla
        state.stats = {};
        state.history = [];
        // LocalStorage'ı temizle ve UI'ı güncelle
        saveStateToLocalStorage();
        updateStatsUI(state.stats);
        updateHistoryUI(state.history);
    };

    // --- Başlangıç Fonksiyonu ---
    const initialize = async () => {
        loadStateFromLocalStorage();

        // Kayıtlı tema veya sistem tercihine göre temayı uygula
        const savedTheme = state.theme;
        const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        const initialTheme = savedTheme || (systemPrefersDark ? 'dark' : 'light');
        applyTheme(initialTheme, true); // `true` ile başlangıç ayarı olduğunu belirt

        updateStatsUI(state.stats);
        updateHistoryUI(state.history);
        showView('categories');
    };

    // --- Olay Dinleyicileri (Event Listeners) ---
    themeToggle.addEventListener('click', handleThemeToggle);
    difficultyButtons.addEventListener('click', handleDifficultySelection);
    categoryButtons.addEventListener('click', handleCategorySelection);
    optionsContainer.addEventListener('click', handleOptionClick);
    resetStatsButton.addEventListener('click', handleResetStats);
    changeCategoryButton.addEventListener('click', () => {
        stopTimer();
        showView('categories');
    });

    // Uygulamayı başlat
    initialize();
}); 