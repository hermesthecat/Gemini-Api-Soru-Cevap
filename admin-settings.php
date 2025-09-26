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

        <!-- Admin Ayarlar -->
        <div id="admin-settings-tab" class="admin-tab-content">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-gray-800 dark:text-white">🔧 Sistem Ayarları</h1>
                <p class="text-gray-600 dark:text-gray-300 mt-2">Site genelindeki ayarları yönetin.</p>
            </div>

            <div class="space-y-8">
                <!-- Ayarlar Formu -->
                <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-lg">
                    <form id="settings-form" class="space-y-6">

                        <!-- Gemini API Ayarları -->
                        <div class="border-b border-gray-200 dark:border-gray-700 pb-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">🤖 Gemini API Ayarları</h3>

                            <div class="space-y-4">
                                <div>
                                    <label for="gemini-api-key" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        API Anahtarı
                                        <span class="text-xs text-gray-500">(Google AI Studio'dan alabilirsiniz)</span>
                                    </label>
                                    <input
                                        type="password"
                                        id="gemini-api-key"
                                        name="gemini_api_key"
                                        placeholder="AIza..."
                                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm dark:bg-gray-700 dark:text-white"
                                    >
                                    <p class="mt-1 text-xs text-gray-500">Boş bırakılırsa config.php'deki değer kullanılır</p>
                                </div>

                                <div>
                                    <label for="gemini-model" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Model Adı
                                    </label>
                                    <input
                                        type="text"
                                        id="gemini-model"
                                        name="gemini_model"
                                        placeholder="gemini-1.5-flash"
                                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm dark:bg-gray-700 dark:text-white"
                                    >
                                    <p class="mt-1 text-xs text-gray-500">Örnek: gemini-1.5-flash, gemini-1.5-pro, gemini-pro</p>
                                </div>
                            </div>
                        </div>

                        <!-- Site Ayarları -->
                        <div class="border-b border-gray-200 dark:border-gray-700 pb-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">🌐 Site Ayarları</h3>

                            <div class="space-y-4">
                                <div>
                                    <label for="site-name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Site Adı
                                    </label>
                                    <input
                                        type="text"
                                        id="site-name"
                                        name="site_name"
                                        placeholder="AI Soru Cevap Yarışması"
                                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm dark:bg-gray-700 dark:text-white"
                                    >
                                </div>
                            </div>
                        </div>

                        <!-- Kullanıcı Ayarları -->
                        <div class="pb-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">👥 Kullanıcı Ayarları</h3>

                            <div class="space-y-4">
                                <div class="flex items-center">
                                    <input
                                        type="checkbox"
                                        id="registration-enabled"
                                        name="registration_enabled"
                                        value="1"
                                        class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded dark:border-gray-600 dark:bg-gray-700"
                                    >
                                    <label for="registration-enabled" class="ml-2 block text-sm text-gray-900 dark:text-white">
                                        Yeni kullanıcı kaydına izin ver
                                    </label>
                                </div>
                                <p class="text-xs text-gray-500">Bu seçenek kapatıldığında yeni kullanıcılar kayıt olamaz</p>
                            </div>
                        </div>

                        <!-- Kaydet Butonu -->
                        <div class="pt-6">
                            <button
                                type="submit"
                                class="w-full bg-blue-500 text-white px-6 py-3 rounded-lg hover:bg-blue-600 transition-colors font-medium"
                            >
                                💾 Ayarları Kaydet
                            </button>
                        </div>

                    </form>
                </div>

                <!-- Sistem Bilgileri -->
                <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-lg">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">ℹ️ Sistem Bilgileri</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-gray-500">PHP Sürümü:</span>
                            <span class="text-gray-900 dark:text-white font-mono"><?php echo PHP_VERSION; ?></span>
                        </div>
                        <div>
                            <span class="text-gray-500">Veritabanı:</span>
                            <span class="text-gray-900 dark:text-white font-mono">MySQL</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Timezone:</span>
                            <span class="text-gray-900 dark:text-white font-mono"><?php echo date_default_timezone_get(); ?></span>
                        </div>
                        <div>
                            <span class="text-gray-500">Server Time:</span>
                            <span class="text-gray-900 dark:text-white font-mono"><?php echo date('Y-m-d H:i:s'); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

<script>
// Admin ayarlar sayfası yüklendiğinde içeriği yükle
document.addEventListener('DOMContentLoaded', () => {
    // Admin settings handler'ı yükle
    setTimeout(() => {
        if (typeof adminSettingsHandler !== 'undefined') {
            adminSettingsHandler.init();
            adminSettingsHandler.loadSettings();
        }
    }, 100);
});
</script>

<?php include 'footer.php'; ?>