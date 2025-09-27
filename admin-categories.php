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

        <!-- Admin Kategori Yönetimi -->
        <div id="admin-categories-tab" class="admin-tab-content">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-gray-800 dark:text-white">Kategori Yönetimi</h1>
            </div>

            <div class="space-y-8">
                <!-- Yeni Kategori Formu -->
                <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-lg">
                    <h3 class="text-xl font-bold mb-4">Yeni Kategori Oluştur</h3>

                    <form id="add-category-form" class="space-y-4">
                        <div>
                            <label for="category_key" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Kategori Key</label>
                            <input type="text" id="category_key" name="category_key" required placeholder="ornk: sinema" pattern="[a-z0-9_]+" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700">
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Sadece küçük harf, rakam ve alt çizgi</p>
                        </div>
                        <div>
                            <label for="category_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Kategori Adı</label>
                            <input type="text" id="category_name" name="category_name" required placeholder="Sinema" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700">
                        </div>
                        <div>
                            <label for="icon" class="block text-sm font-medium text-gray-700 dark:text-gray-300">FontAwesome Icon</label>
                            <input type="text" id="icon" name="icon" placeholder="fa-video" value="fa-question" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700">
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">FontAwesome sınıfı</p>
                        </div>
                        <div>
                            <label for="color" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Renk</label>
                            <select id="color" name="color" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700">
                                <option value="gray">Gray</option>
                                <option value="red">Red</option>
                                <option value="orange">Orange</option>
                                <option value="yellow">Yellow</option>
                                <option value="green">Green</option>
                                <option value="teal">Teal</option>
                                <option value="blue">Blue</option>
                                <option value="indigo">Indigo</option>
                                <option value="purple">Purple</option>
                                <option value="pink">Pink</option>
                                <option value="cyan">Cyan</option>
                                <option value="brown">Brown</option>
                            </select>
                        </div>

                        <button type="submit" class="w-full bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600">Kategoriyi Oluştur</button>
                    </form>
                </div>

                <!-- Mevcut Kategoriler Listesi -->
                <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-lg">
                    <h3 class="text-xl font-bold mb-4">Mevcut Kategoriler</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kategori</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Key</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Soru Sayısı</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Durum</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">İşlem</th>
                                </tr>
                            </thead>
                            <tbody id="categories-list-body" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <!-- Kategoriler buraya JS ile eklenecek -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
    </div>

    <!-- Edit Category Modal -->
    <div id="edit-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full m-4">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                    <i class="fas fa-edit mr-2 text-blue-500"></i>
                    Kategori Düzenle
                </h3>
            </div>

            <form id="edit-category-form" class="p-6 space-y-4">
                <input type="hidden" id="edit-category-id" name="id">

                <div>
                    <label for="edit-category-name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Kategori Adı</label>
                    <input type="text" id="edit-category-name" name="category_name" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700">
                </div>

                <div>
                    <label for="edit-icon" class="block text-sm font-medium text-gray-700 dark:text-gray-300">FontAwesome Icon</label>
                    <input type="text" id="edit-icon" name="icon" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700">
                </div>

                <div>
                    <label for="edit-color" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Renk</label>
                    <select id="edit-color" name="color" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700">
                        <option value="gray">Gray</option>
                        <option value="red">Red</option>
                        <option value="orange">Orange</option>
                        <option value="yellow">Yellow</option>
                        <option value="green">Green</option>
                        <option value="teal">Teal</option>
                        <option value="blue">Blue</option>
                        <option value="indigo">Indigo</option>
                        <option value="purple">Purple</option>
                        <option value="pink">Pink</option>
                        <option value="cyan">Cyan</option>
                        <option value="brown">Brown</option>
                    </select>
                </div>

                <div>
                    <label class="flex items-center">
                        <input type="checkbox" id="edit-is-active" name="is_active" value="1"
                            class="rounded border-gray-300 dark:border-gray-600 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Aktif</span>
                    </label>
                </div>
            </form>

            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700 flex justify-end space-x-3 rounded-b-lg">
                <button id="cancel-edit" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-600 border border-gray-300 dark:border-gray-500 rounded-md hover:bg-gray-50 dark:hover:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    İptal
                </button>
                <button id="save-edit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Kaydet
                </button>
            </div>
        </div>
    </div>

<?php include 'footer.php'; ?>

<script>
// Admin Kategori sayfası yüklendiğinde içeriği yükle
document.addEventListener('DOMContentLoaded', () => {
    // Category handler'ı yükle
    setTimeout(() => {
        if (typeof categoryHandler !== 'undefined') {
            categoryHandler.init();
        }
    }, 100);
});
</script>
</body>
</html>