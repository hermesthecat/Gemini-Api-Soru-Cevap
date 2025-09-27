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

        <!-- Admin Mağaza -->
        <div id="admin-shop-tab" class="admin-tab-content">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-gray-800 dark:text-white">🛒 Mağaza Yönetimi</h1>
                <p class="text-gray-600 dark:text-gray-300 mt-2">Joker fiyatlarını ve mağaza ayarlarını yönetin.</p>
            </div>

            <div class="space-y-8">
                <!-- Mağaza İstatistikleri -->
                <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-lg">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">📊 Mağaza İstatistikleri</h3>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                        <div class="bg-blue-50 dark:bg-blue-900 p-4 rounded-lg">
                            <div class="flex items-center">
                                <div class="p-2 bg-blue-500 rounded-lg mr-3">
                                    <i class="fas fa-shopping-cart text-white"></i>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">Toplam Satış</p>
                                    <p id="total-sales" class="text-xl font-bold text-blue-600 dark:text-blue-400">-</p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-green-50 dark:bg-green-900 p-4 rounded-lg">
                            <div class="flex items-center">
                                <div class="p-2 bg-green-500 rounded-lg mr-3">
                                    <i class="fas fa-coins text-white"></i>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">Toplam Gelir</p>
                                    <p id="total-revenue" class="text-xl font-bold text-green-600 dark:text-green-400">-</p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-purple-50 dark:bg-purple-900 p-4 rounded-lg">
                            <div class="flex items-center">
                                <div class="p-2 bg-purple-500 rounded-lg mr-3">
                                    <i class="fas fa-magic text-white"></i>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">En Popüler</p>
                                    <p id="most-popular" class="text-xl font-bold text-purple-600 dark:text-purple-400">-</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Satış Detayları -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Joker Türü
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Satış Sayısı
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Toplam Gelir
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Ortalama/Günlük
                                    </th>
                                </tr>
                            </thead>
                            <tbody id="shop-stats-body" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <!-- İstatistikler buraya yüklenecek -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Fiyat Yönetimi -->
                <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-lg">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">💰 Fiyat Yönetimi</h3>

                    <form id="shop-settings-form" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <!-- 50/50 Joker -->
                            <div class="border border-gray-200 dark:border-gray-700 p-4 rounded-lg">
                                <div class="flex items-center mb-3">
                                    <div class="p-2 bg-blue-500 rounded-lg mr-3">
                                        <i class="fas fa-balance-scale text-white"></i>
                                    </div>
                                    <h4 class="font-medium text-gray-900 dark:text-white">50/50 Joker</h4>
                                </div>
                                <div>
                                    <label for="fifty-fifty-price" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Fiyat (Jeton)
                                    </label>
                                    <input
                                        type="number"
                                        id="fifty-fifty-price"
                                        name="fiftyFifty"
                                        min="1"
                                        max="1000"
                                        class="w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm dark:bg-gray-700 dark:text-white"
                                    >
                                </div>
                            </div>

                            <!-- Extra Time Joker -->
                            <div class="border border-gray-200 dark:border-gray-700 p-4 rounded-lg">
                                <div class="flex items-center mb-3">
                                    <div class="p-2 bg-green-500 rounded-lg mr-3">
                                        <i class="fas fa-clock text-white"></i>
                                    </div>
                                    <h4 class="font-medium text-gray-900 dark:text-white">+15 Saniye</h4>
                                </div>
                                <div>
                                    <label for="extra-time-price" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Fiyat (Jeton)
                                    </label>
                                    <input
                                        type="number"
                                        id="extra-time-price"
                                        name="extraTime"
                                        min="1"
                                        max="1000"
                                        class="w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm dark:bg-gray-700 dark:text-white"
                                    >
                                </div>
                            </div>

                            <!-- Pass Joker -->
                            <div class="border border-gray-200 dark:border-gray-700 p-4 rounded-lg">
                                <div class="flex items-center mb-3">
                                    <div class="p-2 bg-purple-500 rounded-lg mr-3">
                                        <i class="fas fa-forward text-white"></i>
                                    </div>
                                    <h4 class="font-medium text-gray-900 dark:text-white">Soruyu Geç</h4>
                                </div>
                                <div>
                                    <label for="pass-price" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Fiyat (Jeton)
                                    </label>
                                    <input
                                        type="number"
                                        id="pass-price"
                                        name="pass"
                                        min="1"
                                        max="1000"
                                        class="w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm dark:bg-gray-700 dark:text-white"
                                    >
                                </div>
                            </div>
                        </div>

                        <!-- Kaydet Butonu -->
                        <div class="pt-6">
                            <button
                                type="submit"
                                class="w-full bg-blue-500 text-white px-6 py-3 rounded-lg hover:bg-blue-600 transition-colors font-medium"
                            >
                                💾 Fiyatları Güncelle
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Son Satışlar -->
                <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-lg">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">🔄 Son Satışlar</h3>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Kullanıcı
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Joker
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Fiyat
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Tarih
                                    </th>
                                </tr>
                            </thead>
                            <tbody id="recent-sales-body" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <!-- Son satışlar buraya yüklenecek -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>

<script>
// Admin mağaza sayfası yüklendiğinde içeriği yükle
document.addEventListener('DOMContentLoaded', () => {
    // Admin shop handler'ı yükle
    setTimeout(() => {
        if (typeof adminShopHandler !== 'undefined') {
            adminShopHandler.init();
            adminShopHandler.loadShopData();
        }
    }, 100);
});
</script>

<?php include 'footer.php'; ?>