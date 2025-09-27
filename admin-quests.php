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

        <!-- Admin Görev Yönetimi -->
        <div id="admin-quests-tab" class="admin-tab-content">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-gray-800 dark:text-white">Görev Yönetimi</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-2">Günlük görevleri yönetin ve oluşturun</p>
            </div>

            <div class="space-y-8">
                <!-- İstatistikler Özet -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-lg">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-blue-500 rounded-lg flex items-center justify-center">
                                <i class="fas fa-tasks text-white text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm text-gray-600 dark:text-gray-400">Toplam Görev</p>
                                <p id="total-quests" class="text-2xl font-bold text-gray-800 dark:text-white">-</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-lg">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-green-500 rounded-lg flex items-center justify-center">
                                <i class="fas fa-check-circle text-white text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm text-gray-600 dark:text-gray-400">Aktif Görev</p>
                                <p id="active-quests" class="text-2xl font-bold text-gray-800 dark:text-white">-</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-lg">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-yellow-500 rounded-lg flex items-center justify-center">
                                <i class="fas fa-calendar-day text-white text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm text-gray-600 dark:text-gray-400">Bugün Atanan</p>
                                <p id="assigned-today" class="text-2xl font-bold text-gray-800 dark:text-white">-</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-lg">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-purple-500 rounded-lg flex items-center justify-center">
                                <i class="fas fa-trophy text-white text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm text-gray-600 dark:text-gray-400">Bugün Tamamlanan</p>
                                <p id="completed-today" class="text-2xl font-bold text-gray-800 dark:text-white">-</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Yeni Quest Formu -->
                <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-lg">
                    <h3 class="text-xl font-bold mb-4 dark:text-white">Yeni Görev Oluştur</h3>
                    <form id="create-quest-form" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="quest-key" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Görev Anahtarı</label>
                                <input type="text" id="quest-key" name="quest_key" required placeholder="ornek_gorev" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-white">
                                <p class="text-xs text-gray-500 mt-1">Sadece küçük harf, sayı ve alt çizgi kullanın</p>
                            </div>
                            <div>
                                <label for="quest-name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Görev Adı</label>
                                <input type="text" id="quest-name" name="name" required placeholder="Örnek Görev" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-white">
                            </div>
                        </div>

                        <div>
                            <label for="quest-description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Açıklama Template</label>
                            <input type="text" id="quest-description" name="description_template" required placeholder="{goal} adet {target} sorusu çöz" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-white">
                            <p class="text-xs text-gray-500 mt-1">{goal} ve {target} placeholder'larını kullanabilirsiniz</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <label for="quest-type" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Görev Tipi</label>
                                <select id="quest-type" name="type" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-white">
                                    <option value="">Seçiniz</option>
                                    <option value="solve_category">Kategori Çözme</option>
                                    <option value="solve_difficulty">Zorluk Çözme</option>
                                    <option value="consecutive_days">Ardışık Giriş</option>
                                    <option value="win_duels">Düello Kazanma</option>
                                </select>
                            </div>
                            <div>
                                <label for="quest-target" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Hedef Değer</label>
                                <input type="text" id="quest-target" name="target" placeholder="tarih, kolay, vs. (opsiyonel)" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-white">
                                <p class="text-xs text-gray-500 mt-1">Kategori adı, zorluk seviyesi vb.</p>
                            </div>
                            <div>
                                <label for="quest-goal" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Hedef Sayı</label>
                                <input type="number" id="quest-goal" name="default_goal" required min="1" placeholder="5" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-white">
                            </div>
                            <div class="flex items-center mt-6">
                                <input type="checkbox" id="quest-active" name="is_active" checked class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                <label for="quest-active" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">Aktif</label>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="quest-reward-points" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Ödül Puanı</label>
                                <input type="number" id="quest-reward-points" name="reward_points" required min="0" placeholder="10" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-white">
                            </div>
                            <div>
                                <label for="quest-reward-coins" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Ödül Jetonu</label>
                                <input type="number" id="quest-reward-coins" name="reward_coins" required min="0" placeholder="5" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-white">
                            </div>
                        </div>

                        <button type="submit" class="w-full bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600 transition-colors">Görev Oluştur</button>
                    </form>
                </div>

                <!-- Mevcut Quest Listesi -->
                <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-lg">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-xl font-bold dark:text-white">Mevcut Görevler</h3>
                        <button id="refresh-quests-btn" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition-colors">
                            <i class="fas fa-sync-alt mr-2"></i>Yenile
                        </button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Görev</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tip</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Hedef/Goal</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Ödül</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Durum</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">İşlem</th>
                                </tr>
                            </thead>
                            <tbody id="quests-list-body" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <!-- Görevler buraya JS ile eklenecek -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Görev Düzenleme Modal -->
    <div id="edit-quest-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-lg max-w-2xl w-full mx-4 max-h-screen overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold dark:text-white">Görev Düzenle</h2>
                <button id="edit-modal-close-btn" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form id="edit-quest-form">
                <input type="hidden" id="edit-quest-id" name="quest_key">

                <div class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="edit-quest-name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Görev Adı</label>
                            <input type="text" id="edit-quest-name" name="name" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-white">
                        </div>
                        <div>
                            <label for="edit-quest-target" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Hedef Değer</label>
                            <input type="text" id="edit-quest-target" name="target" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-white">
                        </div>
                    </div>

                    <div>
                        <label for="edit-quest-description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Açıklama Template</label>
                        <input type="text" id="edit-quest-description" name="description_template" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-white">
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label for="edit-quest-goal" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Hedef Sayı</label>
                            <input type="number" id="edit-quest-goal" name="default_goal" required min="1" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-white">
                        </div>
                        <div>
                            <label for="edit-quest-reward-points" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Ödül Puanı</label>
                            <input type="number" id="edit-quest-reward-points" name="reward_points" required min="0" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-white">
                        </div>
                        <div>
                            <label for="edit-quest-reward-coins" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Ödül Jetonu</label>
                            <input type="number" id="edit-quest-reward-coins" name="reward_coins" required min="0" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-white">
                        </div>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" id="edit-quest-active" name="is_active" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                        <label for="edit-quest-active" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">Aktif</label>
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
// Admin Quest sayfası yüklendiğinde içeriği yükle
document.addEventListener('DOMContentLoaded', () => {
    // Quest handler'ı yükle
    setTimeout(() => {
        if (typeof adminQuestHandler !== 'undefined') {
            adminQuestHandler.init();
            adminQuestHandler.loadQuestsList();
            adminQuestHandler.loadStats();
        }
    }, 100);
});
</script>

<?php include 'footer.php'; ?>