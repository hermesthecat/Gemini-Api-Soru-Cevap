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
    <div id="app-container" class="container mx-auto px-4 py-8 max-w-6xl">

        <?php include 'nav.php'; ?>

        <!-- Admin Başarım Yönetimi -->
        <div id="admin-achievements-tab" class="admin-tab-content">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-gray-800 dark:text-white">Başarım Yönetimi</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-2">Oyun başarımlarını ve kurallarını yönetin</p>
            </div>

            <div class="space-y-8">
                <!-- İstatistikler Özet -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-lg">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-blue-500 rounded-lg flex items-center justify-center">
                                <i class="fas fa-trophy text-white text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm text-gray-600 dark:text-gray-400">Toplam Başarım</p>
                                <p id="total-achievements" class="text-2xl font-bold text-gray-800 dark:text-white">-</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-lg">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-green-500 rounded-lg flex items-center justify-center">
                                <i class="fas fa-chart-line text-white text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm text-gray-600 dark:text-gray-400">Tracking Aktif</p>
                                <p id="tracking-enabled" class="text-2xl font-bold text-gray-800 dark:text-white">-</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-lg">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-yellow-500 rounded-lg flex items-center justify-center">
                                <i class="fas fa-users text-white text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm text-gray-600 dark:text-gray-400">Kazanılan Toplam</p>
                                <p id="total-earned" class="text-2xl font-bold text-gray-800 dark:text-white">-</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-lg">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-purple-500 rounded-lg flex items-center justify-center">
                                <i class="fas fa-percentage text-white text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm text-gray-600 dark:text-gray-400">Ortalama Başarı</p>
                                <p id="average-completion" class="text-2xl font-bold text-gray-800 dark:text-white">-%</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Yeni Başarım Formu -->
                <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-lg">
                    <h3 class="text-xl font-bold mb-4 dark:text-white">Yeni Başarım Oluştur</h3>
                    <form id="create-achievement-form" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="achievement-key" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Başarım Anahtarı</label>
                                <input type="text" id="achievement-key" name="achievement_key" required placeholder="ornek_basarim" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-white">
                                <p class="text-xs text-gray-500 mt-1">Sadece küçük harf, sayı ve alt çizgi kullanın</p>
                            </div>
                            <div>
                                <label for="achievement-name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Başarım Adı</label>
                                <input type="text" id="achievement-name" name="name" required placeholder="Örnek Başarım" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-white">
                            </div>
                        </div>

                        <div>
                            <label for="achievement-description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Açıklama</label>
                            <textarea id="achievement-description" name="description" rows="2" required placeholder="Bu başarımı nasıl kazanılır?" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-white"></textarea>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label for="achievement-icon" class="block text-sm font-medium text-gray-700 dark:text-gray-300">FontAwesome İkon</label>
                                <input type="text" id="achievement-icon" name="icon" required placeholder="fa-trophy" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-white">
                            </div>
                            <div>
                                <label for="achievement-color" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Renk</label>
                                <select id="achievement-color" name="color" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-white">
                                    <option value="blue">Mavi</option>
                                    <option value="green">Yeşil</option>
                                    <option value="yellow">Sarı</option>
                                    <option value="red">Kırmızı</option>
                                    <option value="purple">Mor</option>
                                    <option value="indigo">İndigo</option>
                                    <option value="pink">Pembe</option>
                                    <option value="gray">Gri</option>
                                </select>
                            </div>
                            <div>
                                <label for="rule-type" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Kural Tipi</label>
                                <select id="rule-type" name="rule_type" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-white">
                                    <option value="first_correct">İlk Doğru Cevap</option>
                                    <option value="total_score">Toplam Puan</option>
                                    <option value="night_hours">Gece Saatleri</option>
                                    <option value="all_categories">Tüm Kategoriler</option>
                                    <option value="collect_achievements">Başarım Topla</option>
                                    <option value="category_expert">Kategori Uzmanı</option>
                                    <option value="category_perfect">Kategori Kusursuz</option>
                                    <option value="difficulty_expert">Zorluk Uzmanı</option>
                                    <option value="consecutive_correct">Ardışık Doğru</option>
                                    <option value="speed_answer">Hızlı Cevap</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label for="target-value" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Hedef Değer</label>
                                <input type="text" id="target-value" name="target_value" placeholder="tarih, zor, vs. (opsiyonel)" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-white">
                                <p class="text-xs text-gray-500 mt-1">Kategori adı, zorluk seviyesi vb.</p>
                            </div>
                            <div>
                                <label for="goal-count" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Hedef Sayı</label>
                                <input type="number" id="goal-count" name="goal_count" required min="1" placeholder="10" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-white">
                            </div>
                            <div class="flex items-center mt-6">
                                <input type="checkbox" id="tracking-enabled-checkbox" name="tracking_enabled" checked class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                <label for="tracking-enabled-checkbox" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">Progress Tracking Aktif</label>
                            </div>
                        </div>

                        <button type="submit" class="w-full bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600 transition-colors">Başarım Oluştur</button>
                    </form>
                </div>

                <!-- Mevcut Başarımlar Listesi -->
                <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-lg">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-xl font-bold dark:text-white">Mevcut Başarımlar</h3>
                        <button id="refresh-achievements-btn" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition-colors">
                            <i class="fas fa-sync-alt mr-2"></i>Yenile
                        </button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Başarım</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Kural</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Hedef</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tracking</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Kazananlar</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">İşlem</th>
                                </tr>
                            </thead>
                            <tbody id="achievements-list-body" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <!-- Başarımlar buraya JS ile eklenecek -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Başarım Düzenleme Modal -->
    <div id="edit-achievement-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-lg max-w-2xl w-full mx-4 max-h-screen overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold dark:text-white">Başarım Düzenle</h2>
                <button id="edit-modal-close-btn" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form id="edit-achievement-form">
                <input type="hidden" id="edit-achievement-id" name="achievement_key">

                <div class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="edit-achievement-name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Başarım Adı</label>
                            <input type="text" id="edit-achievement-name" name="name" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-white">
                        </div>
                        <div>
                            <label for="edit-achievement-icon" class="block text-sm font-medium text-gray-700 dark:text-gray-300">FontAwesome İkon</label>
                            <input type="text" id="edit-achievement-icon" name="icon" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-white">
                        </div>
                    </div>

                    <div>
                        <label for="edit-achievement-description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Açıklama</label>
                        <textarea id="edit-achievement-description" name="description" rows="2" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-white"></textarea>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label for="edit-achievement-color" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Renk</label>
                            <select id="edit-achievement-color" name="color" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-white">
                                <option value="blue">Mavi</option>
                                <option value="green">Yeşil</option>
                                <option value="yellow">Sarı</option>
                                <option value="red">Kırmızı</option>
                                <option value="purple">Mor</option>
                                <option value="indigo">İndigo</option>
                                <option value="pink">Pembe</option>
                                <option value="gray">Gri</option>
                            </select>
                        </div>
                        <div>
                            <label for="edit-goal-count" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Hedef Sayı</label>
                            <input type="number" id="edit-goal-count" name="goal_count" required min="1" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-white">
                        </div>
                        <div class="flex items-center mt-6">
                            <input type="checkbox" id="edit-tracking-enabled" name="tracking_enabled" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                            <label for="edit-tracking-enabled" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">Progress Tracking</label>
                        </div>
                    </div>
                </div>

                <div class="flex space-x-4 mt-6">
                    <button type="submit" class="flex-1 bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-lg transition-colors">Güncelle</button>
                    <button type="button" id="edit-modal-cancel-btn" class="flex-1 bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded-lg transition-colors">İptal</button>
                </div>
            </form>
        </div>
    </div>

<script>
// Admin Başarım sayfası yüklendiğinde içeriği yükle
document.addEventListener('DOMContentLoaded', () => {
    // Achievement handler'ı yükle
    setTimeout(() => {
        if (typeof adminAchievementHandler !== 'undefined') {
            adminAchievementHandler.init();
            adminAchievementHandler.loadAchievementsList();
            adminAchievementHandler.loadStats();
        }
    }, 100);
});
</script>

<?php include 'footer.php'; ?>