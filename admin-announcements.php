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

        <!-- Admin Duyuru Yönetimi -->
        <div id="admin-announcements-tab" class="admin-tab-content">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-gray-800 dark:text-white">Duyuru Yönetimi</h1>
            </div>

            <div class="space-y-8">
                <!-- Yeni Duyuru Formu -->
                <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-lg">
                    <h3 class="text-xl font-bold mb-4">Yeni Duyuru Oluştur</h3>
                    <form id="create-announcement-form" class="space-y-4">
                        <div>
                            <label for="announcement-title" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Başlık</label>
                            <input type="text" id="announcement-title" name="title" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700">
                        </div>
                        <div>
                            <label for="announcement-content" class="block text-sm font-medium text-gray-700 dark:text-gray-300">İçerik</label>
                            <textarea id="announcement-content" name="content" rows="4" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700"></textarea>
                        </div>
                        <div>
                            <label for="announcement-target" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Hedef Grup</label>
                            <select id="announcement-target" name="target_group" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700">
                                <option value="all">Tüm Kullanıcılar</option>
                                <option value="users">Sadece Normal Kullanıcılar</option>
                                <option value="admins">Sadece Adminler</option>
                            </select>
                        </div>
                        <div>
                            <label for="announcement-end-date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Bitiş Tarihi</label>
                            <input type="datetime-local" id="announcement-end-date" name="end_date" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700">
                        </div>
                        <button type="submit" class="w-full bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600">Duyuruyu Yayınla</button>
                    </form>
                </div>

                <!-- Mevcut Duyurular Listesi -->
                <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-lg">
                    <h3 class="text-xl font-bold mb-4">Mevcut Duyurular</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Başlık</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hedef</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bitiş Tarihi</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">İşlem</th>
                                </tr>
                            </thead>
                            <tbody id="announcements-list-body" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <!-- Duyurular buraya JS ile eklenecek -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>

<script>
// Admin Duyuru sayfası yüklendiğinde içeriği yükle
document.addEventListener('DOMContentLoaded', () => {
    // Admin handler'ı yükle
    setTimeout(() => {
        if (typeof adminHandler !== 'undefined') {
            adminHandler.updateAnnouncementsList();
        }
        if (typeof announcementHandler !== 'undefined') {
            announcementHandler.updateAnnouncementsList();
        }
    }, 100);
});
</script>

<?php include 'footer.php'; ?>