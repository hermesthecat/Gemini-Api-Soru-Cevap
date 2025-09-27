<?php
include 'auth_check.php';
include 'header.php';
?>

    <!-- Ana Konteyner -->
    <div id="app-container" class="container mx-auto px-4 py-8 max-w-4xl">


        <?php include 'nav.php'; ?>
                <!-- Yarışma Sekmesi İçeriği -->
                <div id="yarışma-tab" class="main-tab-content">
                    <main id="game-container" class="space-y-8">
                        <!-- Kategori Seçim Alanı -->
                        <div id="category-selection-container" class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                            <h2 class="text-xl font-semibold mb-4 text-center dark:text-white">Zorluk Seçin</h2>
                            <div class="flex justify-center mb-6 space-x-2" id="difficulty-buttons">
                                <button data-zorluk="kolay" class="difficulty-button px-4 py-2 rounded-lg bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">Kolay</button>
                                <button data-zorluk="orta" class="difficulty-button px-4 py-2 rounded-lg bg-blue-500 text-white font-semibold transition-colors">Orta</button>
                                <button data-zorluk="zor" class="difficulty-button px-4 py-2 rounded-lg bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">Zor</button>
                            </div>

                            <!-- Günlük Görevler -->
                            <div id="daily-quests-container" class="mt-6 pt-6 border-t dark:border-gray-700">
                                <h3 class="text-lg font-semibold mb-3 text-center dark:text-white">Günlük Görevler</h3>
                                <div id="daily-quests-list" class="space-y-3">
                                    <!-- Görevler JS ile doldurulacak -->
                                    <p id="daily-quests-loading" class="text-gray-500 dark:text-gray-400 text-center">Görevler yükleniyor...</p>
                                </div>
                            </div>

                            <h2 class="text-xl font-semibold mb-4 border-t pt-6 text-center dark:text-white dark:border-gray-700">Kategori Seçin</h2>
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-4" id="category-buttons">
                                <!-- Kategori butonları JS ile doldurulacak -->
                            </div>
                        </div>

                        <!-- Soru Alanı -->
                        <div id="question-container" class="hidden bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                            <div class="flex justify-between items-center mb-4">
                                <span id="question-category" class="inline-block bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300 px-3 py-1 rounded-full text-sm"></span>
                                <div class="flex items-center space-x-4">
                                    <!-- Joker Butonları -->
                                    <div id="lifeline-container" class="flex items-center space-x-2">
                                        <button id="lifeline-fifty-fifty" class="lifeline-button p-2 w-10 h-10 rounded-full bg-yellow-400 hover:bg-yellow-500 text-white dark:bg-yellow-600 dark:hover:bg-yellow-500 transition-colors shadow-md" title="50/50 Joker Hakkı">
                                            <span class="font-bold">½</span>
                                        </button>
                                        <button id="lifeline-extra-time" class="lifeline-button p-2 w-10 h-10 rounded-full bg-green-400 hover:bg-green-500 text-white dark:bg-green-600 dark:hover:bg-green-500 transition-colors shadow-md" title="Ekstra Süre Jokeri">
                                            <i class="fas fa-stopwatch"></i>
                                        </button>
                                        <button id="lifeline-pass" class="lifeline-button p-2 w-10 h-10 rounded-full bg-blue-400 hover:bg-blue-500 text-white dark:bg-blue-600 dark:hover:bg-blue-500 transition-colors shadow-md" title="Soruyu Geç Jokeri">
                                            <i class="fas fa-forward"></i>
                                        </button>
                                    </div>
                                    <div id="timer-container" class="text-lg font-bold">Kalan Süre: <span id="countdown" class="text-blue-600">30</span></div>
                                </div>
                            </div>
                            <div class="text-gray-700 dark:text-gray-300 mb-4">
                                <h3 class="text-xl font-semibold mb-2 dark:text-white">Soru:</h3>
                                <p id="question-text"></p>
                            </div>
                            <div id="options-container" class="grid grid-cols-1 md:grid-cols-2 gap-4 items-center"></div>
                            <div id="explanation-container" class="hidden mt-6 p-4 bg-blue-50 dark:bg-gray-700/50 border-l-4 border-blue-500">
                                <h4 class="font-bold text-blue-800 dark:text-blue-300 mb-1">Açıklama</h4>
                                <p id="explanation-text" class="text-blue-700 dark:text-blue-400"></p>
                            </div>
                        </div>
                    </main>
                </div>



            </div>
        </div>


        <!-- ===== DÜELLO OYUN EKRANI ===== -->
        <div id="duel-game-view" class="hidden">
            <header class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-4 mb-6">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Düello Rakibi</p>
                        <h2 id="duel-game-opponent-name" class="text-xl font-bold">Rakip Adı</h2>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">İlerleme</p>
                        <p id="duel-game-progress" class="text-xl font-bold text-blue-500">Soru 1 / 5</p>
                    </div>
                </div>
                <div id="duel-game-scores" class="mt-4 pt-4 border-t dark:border-gray-700 flex justify-around text-center">
                    <div>
                        <p id="duel-my-username" class="font-semibold"></p>
                        <p id="duel-my-score" class="text-2xl font-bold text-green-500">0</p>
                    </div>
                </div>
            </header>

            <main id="duel-question-container" class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                <div class="text-gray-700 dark:text-gray-300 mb-4">
                    <h3 class="text-xl font-semibold mb-2 dark:text-white">Soru:</h3>
                    <p id="duel-question-text"></p>
                </div>
                <div id="duel-options-container" class="grid grid-cols-1 md:grid-cols-2 gap-4 items-center">
                    <!-- Düello soru şıkları buraya gelecek -->
                </div>
                <div id="duel-explanation-container" class="hidden mt-6 p-4 bg-blue-50 dark:bg-gray-700/50 border-l-4 border-blue-500">
                    <h4 class="font-bold text-blue-800 dark:text-blue-300 mb-1">Açıklama</h4>
                    <p id="duel-explanation-text" class="text-blue-700 dark:text-blue-400"></p>
                </div>
                <div class="mt-6 text-center">
                    <button id="duel-next-question-btn" class="hidden bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-8 rounded-lg transition-colors">Sıradaki Soru</button>
                </div>
            </main>

            <div id="duel-summary-container" class="hidden bg-white dark:bg-gray-800 rounded-xl shadow-lg p-8 mt-6 text-center">
                <h2 id="duel-summary-title" class="text-3xl font-bold mb-4">Düello Bitti!</h2>
                <div id="duel-summary-icon" class="text-6xl mb-4"></div>
                <p id="duel-summary-text" class="text-lg text-gray-600 dark:text-gray-400 mb-6">Sonuçlar hesaplanıyor...</p>
                <div class="flex justify-around items-center text-2xl font-bold mb-8">
                    <div>
                        <p id="duel-summary-my-name" class="text-lg font-normal mb-1"></p>
                        <p id="duel-summary-my-score" class="text-4xl"></p>
                    </div>
                    <span class="text-gray-400">-vs-</span>
                    <div>
                        <p id="duel-summary-opponent-name" class="text-lg font-normal mb-1"></p>
                        <p id="duel-summary-opponent-score" class="text-4xl"></p>
                    </div>
                </div>
                <button id="duel-back-to-friends-btn" class="bg-purple-500 hover:bg-purple-600 text-white font-bold py-3 px-6 rounded-lg transition-colors">Arkadaşlar Menüsüne Dön</button>
            </div>
        </div>

    </div>

    <!-- Genel Yükleme ve Bildirim Alanları -->
    <div id="loading-overlay" class="hidden fixed inset-0 bg-gray-900 bg-opacity-60 flex items-center justify-center z-50">
        <div class="flex items-center text-white">
            <i class="fas fa-spinner fa-spin text-4xl mr-4"></i>
            <span class="text-2xl font-semibold" id="loading-text">Yükleniyor...</span>
        </div>
    </div>

    <!-- Başarım Kazanıldı Modalı -->
    <div id="achievement-modal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-75 flex items-center justify-center z-50 transition-opacity duration-300 opacity-0">
        <div id="achievement-modal-content" class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl text-center p-8 max-w-sm mx-auto transform scale-95 transition-all duration-300">
            <h2 class="text-2xl font-bold text-yellow-500 mb-2">Başarım Kazanıldı!</h2>
            <div id="achievement-modal-icon-container" class="my-6">
                <!-- Icon JS ile eklenecek -->
            </div>
            <h3 id="achievement-modal-name" class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-2"></h3>
            <p id="achievement-modal-description" class="text-gray-600 dark:text-gray-400 mb-6"></p>
            <button id="achievement-modal-close-btn" class="w-full bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-lg transition-colors">Harika!</button>
        </div>
    </div>

    <!-- Düello Başlatma Modalı -->
    <div id="duel-modal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-75 flex items-center justify-center z-50 transition-opacity duration-300 opacity-0">
        <div id="duel-modal-content" class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl p-8 max-w-md mx-auto transform scale-95 transition-all duration-300 w-full">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-100">Meydan Oku</h2>
                <button id="duel-modal-close-btn" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                    <i class="fas fa-times fa-lg"></i>
                </button>
            </div>
            <p class="mb-6 text-gray-600 dark:text-gray-400">
                <strong id="duel-opponent-name" class="text-blue-500"></strong> adlı arkadaşına meydan okumak için bir kategori ve zorluk seç.
            </p>

            <div class="space-y-4">
                <div>
                    <label for="duel-category-select" class="block mb-2 text-sm font-medium dark:text-gray-300">Kategori</label>
                    <select id="duel-category-select" class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <!-- Kategoriler JS ile doldurulacak -->
                    </select>
                </div>
                <div>
                    <label for="duel-difficulty-select" class="block mb-2 text-sm font-medium dark:text-gray-300">Zorluk</label>
                    <select id="duel-difficulty-select" class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="kolay">Kolay</option>
                        <option value="orta" selected>Orta</option>
                        <option value="zor">Zor</option>
                    </select>
                </div>
            </div>

            <div class="mt-8">
                <button id="duel-send-challenge-btn" class="w-full bg-green-500 hover:bg-green-600 text-white font-bold py-3 px-4 rounded-lg transition-colors">
                    <i class="fas fa-paper-plane mr-2"></i>Meydan Okuma Gönder
                </button>
            </div>
        </div>
    </div>

    <!-- Duyuru Modalı -->
    <div id="announcement-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4 hidden opacity-0 transition-opacity duration-300">
        <div id="announcement-modal-content" class="bg-white dark:bg-gray-800 rounded-lg shadow-2xl p-6 w-full max-w-lg transform scale-95 transition-transform duration-300 max-h-[80vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-2xl font-bold text-gray-800 dark:text-white flex items-center"><i class="fas fa-bullhorn mr-3 text-blue-500"></i>Duyurular</h2>
                <button id="announcement-modal-close-btn" class="text-gray-500 hover:text-gray-800 dark:hover:text-white">&times;</button>
            </div>
            <div id="announcement-modal-body" class="space-y-4">
                <!-- Duyuru içerikleri buraya gelecek -->
            </div>
            <div class="mt-6 text-right">
                <button id="announcement-modal-ok-btn" class="bg-blue-500 text-white px-6 py-2 rounded-lg hover:bg-blue-600">Okudum</button>
            </div>
        </div>
    </div>

    <!-- Soru Değerlendirme Modalı -->
    <div id="question-rating-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4 hidden opacity-0 transition-opacity duration-300">
        <div id="question-rating-modal-content" class="bg-white dark:bg-gray-800 rounded-lg shadow-2xl p-6 w-full max-w-md transform scale-95 transition-transform duration-300">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold text-gray-800 dark:text-white flex items-center">
                    <i class="fas fa-star mr-3 text-yellow-500"></i>Soruyu Değerlendir
                </h2>
                <button id="question-rating-modal-close-btn" class="text-gray-500 hover:text-gray-800 dark:hover:text-white">&times;</button>
            </div>

            <div class="space-y-4">
                <!-- Yıldız Derecelendirmesi -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Bu soruyu kaç yıldızla değerlendirirsiniz?
                    </label>
                    <div id="rating-stars" class="flex space-x-1 justify-center">
                        <button class="rating-star text-2xl text-gray-300 hover:text-yellow-400 transition-colors" data-rating="1">
                            <i class="fas fa-star"></i>
                        </button>
                        <button class="rating-star text-2xl text-gray-300 hover:text-yellow-400 transition-colors" data-rating="2">
                            <i class="fas fa-star"></i>
                        </button>
                        <button class="rating-star text-2xl text-gray-300 hover:text-yellow-400 transition-colors" data-rating="3">
                            <i class="fas fa-star"></i>
                        </button>
                        <button class="rating-star text-2xl text-gray-300 hover:text-yellow-400 transition-colors" data-rating="4">
                            <i class="fas fa-star"></i>
                        </button>
                        <button class="rating-star text-2xl text-gray-300 hover:text-yellow-400 transition-colors" data-rating="5">
                            <i class="fas fa-star"></i>
                        </button>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 text-center mt-1">
                        1 = Çok Kötü, 5 = Mükemmel
                    </p>
                </div>

                <!-- Geri Bildirim (Opsiyonel) -->
                <div>
                    <label for="rating-feedback" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Geri Bildirim (İsteğe Bağlı)
                    </label>
                    <textarea id="rating-feedback" rows="3"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                        placeholder="Soruyla ilgili düşüncelerinizi paylaşın..."></textarea>
                </div>

                <!-- Şikayet Seçenekleri -->
                <div id="report-section" class="hidden">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Şikayet Sebebi
                    </label>
                    <select id="report-reason" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                        <option value="">Şikayet sebebi seçin</option>
                        <option value="hata">Soru hatası</option>
                        <option value="belirsiz">Belirsiz/Anlaşılmaz</option>
                        <option value="kalitesiz">Kalitesiz içerik</option>
                        <option value="tekrar">Tekrarlanan soru</option>
                        <option value="diger">Diğer</option>
                    </select>
                </div>

                <!-- Butonlar -->
                <div class="flex space-x-3 pt-4">
                    <button id="rating-submit-btn" class="flex-1 bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md transition-colors disabled:opacity-50" disabled>
                        <i class="fas fa-check mr-2"></i>Gönder
                    </button>
                    <button id="rating-report-btn" class="flex-1 bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-md transition-colors">
                        <i class="fas fa-flag mr-2"></i>Şikayet Et
                    </button>
                </div>
            </div>
        </div>
    </div>

<?php include 'footer.php'; ?>