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
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
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
});
</script>

<?php include 'footer.php'; ?>