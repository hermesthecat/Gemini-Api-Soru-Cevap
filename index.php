<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Bilgi Yarışması (AJAX)</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <script>
        // Sayfa yanıp sönmesini (FOUC) önlemek için temayı en başta uygula
        if (localStorage.quizAppState && JSON.parse(localStorage.quizAppState).theme === 'dark' || (!('theme' in JSON.parse(localStorage.quizAppState || '{}')) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark')
        } else {
            document.documentElement.classList.remove('dark')
        }
    </script>
</head>

<body class="bg-gray-50 dark:bg-gray-900 min-h-screen text-gray-800 dark:text-gray-200">

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


    <div class="container mx-auto px-4 py-8 max-w-4xl relative">
        <!-- Theme Switcher -->
        <div class="absolute top-4 right-4 z-10">
            <button id="theme-toggle" class="p-2 rounded-full bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 transition-colors">
                <i id="theme-toggle-dark-icon" class="fas fa-moon hidden"></i>
                <i id="theme-toggle-light-icon" class="fas fa-sun hidden"></i>
            </button>
        </div>

        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-gray-800 dark:text-gray-200 mb-2">AI Bilgi Yarışması (AJAX)</h1>
            <p class="text-gray-600 dark:text-gray-400">Bilginizi test edin! Sayfa yenilenmeden...</p>
        </div>

        <!-- Hata Mesajı Alanı -->
        <div id="error-container" class="hidden bg-red-100 dark:bg-red-900/50 border-l-4 border-red-500 text-red-700 dark:text-red-300 p-4 mb-6" role="alert">
            <p class="font-bold">Bir Sorun Oluştu</p>
            <p id="error-message"></p>
        </div>

        <!-- Timer -->
        <div id="timer-container" class="hidden fixed top-4 right-4 bg-white dark:bg-gray-800 rounded-lg shadow-lg p-4 text-center">
            <div class="text-xl font-bold">Kalan Süre</div>
            <div id="countdown" class="text-2xl text-blue-600">30</div>
        </div>

        <!-- Ana İçerik Alanı -->
        <main id="main-content">
            <!-- Kategori Seçim Alanı -->
            <div id="category-selection-container">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-8">
                    <h2 class="text-xl font-semibold mb-4 text-center dark:text-white">Zorluk Seçin</h2>
                    <div class="flex justify-center mb-6 space-x-2" id="difficulty-buttons">
                        <button data-zorluk="kolay" class="difficulty-button px-4 py-2 rounded-lg bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">Kolay</button>
                        <button data-zorluk="orta" class="difficulty-button px-4 py-2 rounded-lg bg-blue-500 text-white font-semibold transition-colors">Orta</button>
                        <button data-zorluk="zor" class="difficulty-button px-4 py-2 rounded-lg bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">Zor</button>
                    </div>

                    <h2 class="text-xl font-semibold mb-4 border-t pt-6 text-center dark:text-white dark:border-gray-700">Kategori Seçin</h2>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4" id="category-buttons">
                        <button data-kategori="tarih" class="category-button bg-blue-100 hover:bg-blue-200 dark:bg-gray-700 dark:hover:bg-gray-600 p-4 rounded-lg"><i class="fas fa-history mb-2"></i><span class="block">Tarih</span></button>
                        <button data-kategori="spor" class="category-button bg-green-100 hover:bg-green-200 dark:bg-gray-700 dark:hover:bg-gray-600 p-4 rounded-lg"><i class="fas fa-futbol mb-2"></i><span class="block">Spor</span></button>
                        <button data-kategori="bilim" class="category-button bg-purple-100 hover:bg-purple-200 dark:bg-gray-700 dark:hover:bg-gray-600 p-4 rounded-lg"><i class="fas fa-atom mb-2"></i><span class="block">Bilim</span></button>
                        <button data-kategori="sanat" class="category-button bg-yellow-100 hover:bg-yellow-200 dark:bg-gray-700 dark:hover:bg-gray-600 p-4 rounded-lg"><i class="fas fa-palette mb-2"></i><span class="block">Sanat</span></button>
                        <button data-kategori="coğrafya" class="category-button bg-red-100 hover:bg-red-200 dark:bg-gray-700 dark:hover:bg-gray-600 p-4 rounded-lg"><i class="fas fa-globe-americas mb-2"></i><span class="block">Coğrafya</span></button>
                        <button data-kategori="genel kültür" class="category-button bg-indigo-100 hover:bg-indigo-200 dark:bg-gray-700 dark:hover:bg-gray-600 p-4 rounded-lg"><i class="fas fa-brain mb-2"></i><span class="block">Genel Kültür</span></button>
                    </div>
                </div>
            </div>

            <!-- Soru Alanı -->
            <div id="question-container" class="hidden">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-8">
                    <div class="flex justify-between items-center mb-4">
                        <span id="question-category" class="inline-block bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300 px-3 py-1 rounded-full text-sm"></span>
                        <button id="change-category-button" class="text-sm text-blue-600 hover:underline">Kategori Değiştir</button>
                    </div>
                    <div class="text-gray-700 dark:text-gray-300 mb-4">
                        <h3 class="text-xl font-semibold mb-2 dark:text-white">Soru:</h3>
                        <p id="question-text"></p>
                    </div>
                    <div id="options-container" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Şıklar buraya dinamik olarak eklenecek -->
                    </div>
                    <!-- Cevap Açıklaması Alanı -->
                    <div id="explanation-container" class="hidden mt-6 p-4 bg-blue-50 dark:bg-gray-700/50 border-l-4 border-blue-400 dark:border-blue-500">
                        <h4 class="font-bold text-blue-800 dark:text-blue-300 mb-1">Açıklama</h4>
                        <p id="explanation-text" class="text-blue-700 dark:text-blue-400"></p>
                    </div>
                </div>
            </div>
        </main>

        <!-- İstatistikler ve Geçmiş -->
        <div id="stats-container" class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mt-8">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold dark:text-white">İstatistikleriniz</h2>
                <button id="reset-stats-button" class="text-sm bg-red-500 hover:bg-red-600 text-white py-1 px-3 rounded-lg transition-colors">Sıfırla</button>
            </div>
            <div class="grid grid-cols-3 gap-4 text-center mb-6">
                <div>
                    <p id="total-questions" class="text-2xl font-bold">0</p>
                    <p class="text-gray-500 dark:text-gray-400">Toplam Soru</p>
                </div>
                <div>
                    <p id="correct-answers" class="text-2xl font-bold text-green-600">0</p>
                    <p class="text-gray-500 dark:text-gray-400">Doğru Cevap</p>
                </div>
                <div>
                    <p id="success-rate" class="text-2xl font-bold text-blue-600">0%</p>
                    <p class="text-gray-500 dark:text-gray-400">Başarı Oranı</p>
                </div>
            </div>
            <h3 class="text-lg font-semibold mb-3 border-t pt-4 dark:text-white dark:border-gray-700">Son Cevaplananlar</h3>
            <div id="history-container" class="space-y-4">
                <p class="text-gray-500 dark:text-gray-400 text-center">Henüz hiç soru cevaplamadınız.</p>
            </div>
        </div>

        <!-- Footer -->
        <footer class="text-center text-gray-500 dark:text-gray-400 text-sm mt-8">
            <p>© 2024 AI Bilgi Yarışması (AJAX). Tüm hakları saklıdır.</p>
        </footer>
    </div>

    <script src="assets/js/app.js"></script>
</body>

</html>