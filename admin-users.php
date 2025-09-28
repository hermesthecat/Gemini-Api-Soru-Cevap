<?php
include 'auth_check.php';

// Admin kontrolü
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

include 'header.php';
?>

    <!-- Ana Konteyner -->
    <div id="app-container" class="container mx-auto px-4 py-8 max-w-4xl">

        <?php include 'nav.php'; ?>

        <!-- Admin Kullanıcı Yönetimi -->
        <div id="admin-users-tab" class="admin-tab-content">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-gray-800 dark:text-white">Kullanıcı Yönetimi</h1>
            </div>

            <!-- İstatistikler -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <!-- Toplam Kullanıcı -->
                <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-lg flex items-center space-x-4">
                    <i class="fas fa-users fa-3x text-blue-500"></i>
                    <div>
                        <p class="text-gray-500 dark:text-gray-400">Toplam Kullanıcı</p>
                        <p id="admin-total-users" class="text-2xl font-bold text-gray-900 dark:text-white">0</p>
                    </div>
                </div>
                <!-- Toplam Cevaplanan Soru -->
                <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-lg flex items-center space-x-4">
                    <i class="fas fa-question-circle fa-3x text-green-500"></i>
                    <div>
                        <p class="text-gray-500 dark:text-gray-400">Toplam Cevaplanan Soru</p>
                        <p id="admin-total-questions" class="text-2xl font-bold text-gray-900 dark:text-white">0</p>
                    </div>
                </div>
                <!-- Quest Yönetimi -->
                <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-lg">
                    <div class="text-center">
                        <i class="fas fa-tasks fa-3x text-purple-500 mb-3"></i>
                        <p class="text-gray-500 dark:text-gray-400 text-sm mb-3">Quest Yönetimi</p>
                        <button id="refresh-all-quests-btn"
                                class="w-full bg-purple-500 hover:bg-purple-600 text-white px-4 py-2 rounded-lg transition-colors text-sm font-medium">
                            Tüm Kullanıcıların Questlerini Yenile
                        </button>
                        <div id="quest-refresh-status" class="mt-2 text-xs text-gray-500 dark:text-gray-400 hidden">
                            <!-- Status mesajları buraya -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- Kullanıcı Listesi -->
            <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-lg">
                <h3 class="text-xl font-bold mb-4">Kullanıcı Listesi</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kullanıcı Adı</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Puan</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jeton</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rol</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kayıt Tarihi</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">İşlem</th>
                            </tr>
                        </thead>
                        <tbody id="admin-user-list-body" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            <!-- Kullanıcılar buraya JS ile eklenecek -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Jeton Güncelleme Modalı -->
        <div id="coin-update-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
                <div class="mt-3">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Jeton Güncelle</h3>
                        <button id="coin-modal-close" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <div class="mb-4">
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                            Kullanıcı: <span id="coin-modal-username" class="font-semibold"></span>
                        </p>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                            Mevcut jeton: <span id="coin-modal-current" class="font-semibold text-yellow-600"></span>
                        </p>

                        <label for="coin-modal-input" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Yeni jeton miktarı:
                        </label>
                        <input type="number" id="coin-modal-input" min="0" max="999999"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>

                    <div class="flex justify-end space-x-3">
                        <button id="coin-modal-cancel" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition-colors">
                            İptal
                        </button>
                        <button id="coin-modal-save" class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 transition-colors">
                            Güncelle
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kullanıcı Detayları Modalı -->
        <div id="user-details-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
                <div class="mt-3">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Kullanıcı Detayları</h3>
                        <button id="user-details-close" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <div class="mb-4 space-y-3">
                        <div>
                            <span class="text-sm text-gray-600 dark:text-gray-400">Kullanıcı Adı:</span>
                            <span id="user-details-name" class="ml-2 font-semibold text-gray-900 dark:text-white"></span>
                        </div>
                        <div>
                            <span class="text-sm text-gray-600 dark:text-gray-400">Toplam Puan:</span>
                            <span id="user-details-score" class="ml-2 font-semibold text-gray-900 dark:text-white"></span>
                        </div>
                        <div>
                            <span class="text-sm text-gray-600 dark:text-gray-400">Jeton:</span>
                            <span id="user-details-coins" class="ml-2 font-semibold text-yellow-600"></span>
                        </div>
                        <div>
                            <span class="text-sm text-gray-600 dark:text-gray-400">Rol:</span>
                            <span id="user-details-role" class="ml-2 font-semibold text-gray-900 dark:text-white"></span>
                        </div>
                        <div>
                            <span class="text-sm text-gray-600 dark:text-gray-400">Kayıt Tarihi:</span>
                            <span id="user-details-join-date" class="ml-2 font-semibold text-gray-900 dark:text-white"></span>
                        </div>
                    </div>

                    <div class="mb-4">
                        <h4 class="text-md font-medium text-gray-900 dark:text-white mb-2">İstatistikler</h4>
                        <div id="user-details-stats" class="border-t pt-3">
                            <!-- User statistics will be loaded here -->
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button id="user-details-close-btn" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition-colors">
                            Kapat
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>

<script>
// Admin Kullanıcı sayfası yüklendiğinde içeriği yükle
document.addEventListener('DOMContentLoaded', () => {
    // Admin handler'ı yükle
    setTimeout(() => {
        if (typeof adminHandler !== 'undefined') {
            adminHandler.updateAll();
        }
    }, 100);

    // User details modal event listeners
    const modal = document.getElementById('user-details-modal');
    const closeBtn = document.getElementById('user-details-close');
    const closeBtnFooter = document.getElementById('user-details-close-btn');

    // Close modal
    [closeBtn, closeBtnFooter].forEach(btn => {
        btn?.addEventListener('click', () => {
            modal?.classList.add('hidden');
        });
    });

    // Close on backdrop click
    modal?.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.classList.add('hidden');
        }
    });
});
</script>

<?php include 'footer.php'; ?>