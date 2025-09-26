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

        <!-- Admin İstatistik Grafikleri -->
        <div id="admin-stats-tab" class="admin-tab-content">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-gray-800 dark:text-white">İstatistik Grafikleri</h1>
            </div>

            <div class="space-y-8">
                <!-- İstatistik Kartları -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
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
                </div>

                <!-- En Çok Oynanan Kategoriler -->
                <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-lg">
                    <h3 class="text-xl font-bold mb-4">En Çok Oynanan Kategoriler</h3>
                    <canvas id="category-chart"></canvas>
                </div>

                <!-- Zorluğa Göre Cevap Dağılımı -->
                <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-lg">
                    <h3 class="text-xl font-bold mb-4">Zorluğa Göre Cevap Dağılımı</h3>
                    <canvas id="answers-chart"></canvas>
                </div>

                <!-- Son 7 Günlük Yeni Kullanıcı Kayıtları -->
                <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-lg">
                    <h3 class="text-xl font-bold mb-4">Son 7 Günlük Yeni Kullanıcı Kayıtları</h3>
                    <canvas id="users-chart"></canvas>
                </div>
            </div>
        </div>

    </div>

<script>
// Admin İstatistik sayfası yüklendiğinde içeriği yükle
document.addEventListener('DOMContentLoaded', () => {
    // Admin handler'ı yükle
    setTimeout(() => {
        if (typeof adminHandler !== 'undefined') {
            adminHandler.updateAll();
            adminHandler.loadAdvancedStats();
        }
    }, 100);
});
</script>

<?php include 'footer.php'; ?>