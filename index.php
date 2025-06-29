<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Bilgi Yarışması (AJAX)</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Sonuç gösteriminde animasyon için */
        .answer-feedback {
            animation: fade-in-out 2s forwards;
        }

        @keyframes fade-in-out {
            0% {
                opacity: 0;
                transform: scale(0.9);
            }

            10% {
                opacity: 1;
                transform: scale(1);
            }

            90% {
                opacity: 1;
                transform: scale(1);
            }

            100% {
                opacity: 0;
                transform: scale(0.9);
            }
        }
    </style>
</head>

<body class="bg-gray-50 min-h-screen">

    <!-- Loading Overlay -->
    <div id="loading-overlay" class="hidden fixed inset-0 bg-gray-900 bg-opacity-60 flex items-center justify-center z-50">
        <div class="flex items-center text-white">
            <i class="fas fa-spinner fa-spin text-4xl mr-4"></i>
            <span class="text-2xl font-semibold" id="loading-text">Yükleniyor...</span>
        </div>
    </div>

    <!-- Answer Feedback Overlay -->
    <div id="answer-feedback-overlay" class="hidden fixed inset-0 flex items-center justify-center z-40">
        <div id="answer-feedback-box" class="p-8 rounded-lg text-white text-3xl font-bold"></div>
    </div>


    <div class="container mx-auto px-4 py-8 max-w-4xl">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-gray-800 mb-2">AI Bilgi Yarışması (AJAX)</h1>
            <p class="text-gray-600">Bilginizi test edin! Sayfa yenilenmeden...</p>
        </div>

        <!-- Hata Mesajı Alanı -->
        <div id="error-container" class="hidden bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
            <p class="font-bold">Bir Sorun Oluştu</p>
            <p id="error-message"></p>
        </div>

        <!-- Timer -->
        <div id="timer-container" class="hidden fixed top-4 right-4 bg-white rounded-lg shadow-lg p-4 text-center">
            <div class="text-xl font-bold">Kalan Süre</div>
            <div id="countdown" class="text-2xl text-blue-600">30</div>
        </div>

        <!-- Ana İçerik Alanı -->
        <main id="main-content">
            <!-- Kategori Seçim Alanı -->
            <div id="category-selection-container">
                <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
                    <h2 class="text-xl font-semibold mb-4">Kategori Seçin</h2>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4" id="category-buttons">
                        <button data-kategori="tarih" class="category-button bg-blue-100 hover:bg-blue-200 p-4 rounded-lg"><i class="fas fa-history mb-2"></i><span class="block">Tarih</span></button>
                        <button data-kategori="spor" class="category-button bg-green-100 hover:bg-green-200 p-4 rounded-lg"><i class="fas fa-futbol mb-2"></i><span class="block">Spor</span></button>
                        <button data-kategori="bilim" class="category-button bg-purple-100 hover:bg-purple-200 p-4 rounded-lg"><i class="fas fa-atom mb-2"></i><span class="block">Bilim</span></button>
                        <button data-kategori="sanat" class="category-button bg-yellow-100 hover:bg-yellow-200 p-4 rounded-lg"><i class="fas fa-palette mb-2"></i><span class="block">Sanat</span></button>
                        <button data-kategori="coğrafya" class="category-button bg-red-100 hover:bg-red-200 p-4 rounded-lg"><i class="fas fa-globe-americas mb-2"></i><span class="block">Coğrafya</span></button>
                        <button data-kategori="genel kültür" class="category-button bg-indigo-100 hover:bg-indigo-200 p-4 rounded-lg"><i class="fas fa-brain mb-2"></i><span class="block">Genel Kültür</span></button>
                    </div>
                </div>
            </div>

            <!-- Soru Alanı -->
            <div id="question-container" class="hidden">
                <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
                    <div class="flex justify-between items-center mb-4">
                        <span id="question-category" class="inline-block bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm"></span>
                        <button id="change-category-button" class="text-sm text-blue-600 hover:underline">Kategori Değiştir</button>
                    </div>
                    <div class="text-gray-700 mb-4">
                        <h3 class="text-xl font-semibold mb-2">Soru:</h3>
                        <p id="question-text"></p>
                    </div>
                    <div id="options-container" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Şıklar buraya dinamik olarak eklenecek -->
                    </div>
                </div>
            </div>
        </main>

        <!-- İstatistikler ve Geçmiş -->
        <div id="stats-container" class="bg-white rounded-xl shadow-lg p-6 mt-8">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold">İstatistikleriniz</h2>
                <button id="reset-stats-button" class="text-sm bg-red-500 hover:bg-red-600 text-white py-1 px-3 rounded-lg transition-colors">Sıfırla</button>
            </div>
            <div class="grid grid-cols-3 gap-4 text-center mb-6">
                <div>
                    <p id="total-questions" class="text-2xl font-bold">0</p>
                    <p class="text-gray-500">Toplam Soru</p>
                </div>
                <div>
                    <p id="correct-answers" class="text-2xl font-bold text-green-600">0</p>
                    <p class="text-gray-500">Doğru Cevap</p>
                </div>
                <div>
                    <p id="success-rate" class="text-2xl font-bold text-blue-600">0%</p>
                    <p class="text-gray-500">Başarı Oranı</p>
                </div>
            </div>
            <h3 class="text-lg font-semibold mb-3 border-t pt-4">Son Cevaplananlar</h3>
            <div id="history-container" class="space-y-4">
                <p class="text-gray-500 text-center">Henüz hiç soru cevaplamadınız.</p>
            </div>
        </div>

        <!-- Footer -->
        <footer class="text-center text-gray-500 text-sm mt-8">
            <p>© 2024 AI Bilgi Yarışması (AJAX). Tüm hakları saklıdır.</p>
        </footer>
    </div>

    <script>
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
                questionCategory.textContent = data.kategori.charAt(0).toUpperCase() + data.kategori.slice(1);
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
                    kategori
                });
                if (data) {
                    displayQuestion(data);
                }
            };

            const handleAnswerSubmission = async (answer) => {
                stopTimer();
                const data = await apiCall('submit_answer', {
                    answer
                });
                if (data) {
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

                // Seçilen cevabı vurgula
                button.classList.add('bg-yellow-200', 'border-yellow-400');

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
    </script>
</body>

</html>