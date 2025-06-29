document.addEventListener('DOMContentLoaded', () => {
    // --- DOM Elementleri ---
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


    // --- Uygulama Durumu (State) ---
    let state = {
        stats: {
            total_questions: 0,
            correct_answers: 0
        },
        history: [],
        currentQuestion: null, // Mevcut soru ve şıkları tutmak için
        difficulty: 'orta', // Varsayılan zorluk seviyesi
    };
    let timer;

    // --- LocalStorage Fonksiyonları ---
    const saveStateToLocalStorage = () => {
        // Sadece kalıcı olması gereken verileri kaydet
        const persistentState = {
            stats: state.stats,
            history: state.history,
        };
        localStorage.setItem('quizAppState', JSON.stringify(persistentState));
    };

    const loadStateFromLocalStorage = () => {
        const savedState = localStorage.getItem('quizAppState');
        if (savedState) {
            const parsedState = JSON.parse(savedState);
            state.stats = parsedState.stats;
            state.history = parsedState.history;
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
        const total = stats.total_questions;
        const correct = stats.correct_answers;
        const rate = total > 0 ? Math.round((correct / total) * 100) : 0;
        document.getElementById('total-questions').textContent = total;
        document.getElementById('correct-answers').textContent = correct;
        document.getElementById('success-rate').textContent = `${rate}%`;
    };

    const updateHistoryUI = (history) => {
        const container = document.getElementById('history-container');
        container.innerHTML = '';
        if (!history || history.length === 0) {
            container.innerHTML = '<p class="text-gray-500 text-center">Henüz hiç soru cevaplamadınız.</p>';
            return;
        }
        history.forEach(item => {
            const isCorrectClass = item.is_correct ? 'bg-green-50 border-green-500' : 'bg-red-50 border-red-500';
            const userAnswerText = item.siklar[item.user_answer] || 'Cevap bulunamadı';
            let historyHtml = `
                <div class="p-3 rounded-lg border-l-4 ${isCorrectClass}">
                    <p class="font-semibold mb-1">${item.question}</p>
                    <p class="text-sm">Sizin Cevabınız: <span class="font-bold">${item.user_answer || 'Süre Doldu'}) ${userAnswerText}</span></p>`;
            if (!item.is_correct) {
                const correctAnswerText = item.siklar[item.correct_answer];
                historyHtml += `<p class="text-sm">Doğru Cevap: <span class="font-bold">${item.correct_answer}) ${correctAnswerText}</span></p>`;
            }
            historyHtml += '</div>';
            container.innerHTML += historyHtml;
        });
    };

    const displayQuestion = (data) => {
        state.currentQuestion = data; // Mevcut soruyu state'e kaydet
        const kategoriText = data.kategori.charAt(0).toUpperCase() + data.kategori.slice(1);
        const zorlukText = data.difficulty.charAt(0).toUpperCase() + data.difficulty.slice(1);
        questionCategory.innerHTML = `${kategoriText} <span class="font-normal text-gray-500">- ${zorlukText}</span>`;
        questionText.textContent = data.question;
        optionsContainer.innerHTML = '';
        ['A', 'B', 'C', 'D'].forEach(opt => {
            if (data.siklar[opt]) {
                const button = document.createElement('button');
                button.className = 'option-button p-4 text-left rounded-lg border border-gray-300 hover:bg-blue-50 transition-colors';
                button.dataset.answer = opt;
                button.innerHTML = `<span class="font-semibold">${opt}</span>) ${data.siklar[opt]}`;
                optionsContainer.appendChild(button);
            }
        });
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
            btn.classList.add('bg-gray-200', 'hover:bg-gray-300');
        });
        button.classList.add('bg-blue-500', 'text-white', 'font-semibold');
        button.classList.remove('bg-gray-200', 'hover:bg-gray-300');
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
                btn.classList.remove('hover:bg-blue-50');

                // Doğru cevabı her zaman yeşil yap
                if (btn.dataset.answer === data.correct_answer) {
                    btn.classList.add('bg-green-200', 'border-green-500', 'font-semibold');
                }
                // Kullanıcının cevabı yanlışsa, onu kırmızı yap
                else if (btn.dataset.answer === answer && !data.is_correct) {
                    btn.classList.add('bg-red-200', 'border-red-500', 'font-semibold');
                }
            });
            // --- BİTTİ: Gelişmiş Geri Bildirim ---

            // İstatistikleri güncelle
            state.stats.total_questions++;
            if (data.is_correct) {
                state.stats.correct_answers++;
            }

            // Geçmiş öğesini oluştur
            const historyItem = {
                question: state.currentQuestion.question,
                siklar: state.currentQuestion.siklar,
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
        state.stats = {
            total_questions: 0,
            correct_answers: 0
        };
        state.history = [];
        // LocalStorage'ı temizle ve UI'ı güncelle
        saveStateToLocalStorage();
        updateStatsUI(state.stats);
        updateHistoryUI(state.history);
    };

    // --- Başlangıç Fonksiyonu ---
    const initialize = async () => {
        loadStateFromLocalStorage();
        updateStatsUI(state.stats);
        updateHistoryUI(state.history);
        showView('categories');
    };

    // --- Olay Dinleyicileri (Event Listeners) ---
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