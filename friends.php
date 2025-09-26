<?php
include 'auth_check.php';
include 'header.php';
?>

    <!-- Ana Konteyner -->
    <div id="app-container" class="container mx-auto px-4 py-8 max-w-4xl">

        <?php include 'nav.php'; ?>
                <!-- Arkadaşlar Sekmesi İçeriği -->
        <div id="arkadaslar-tab" class="main-tab-content block">
            <div class="space-y-8">
                <!-- Kullanıcı Arama -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                    <h2 class="text-xl font-semibold mb-4 dark:text-white">Kullanıcı Bul ve Ekle</h2>
                    <div class="relative">
                        <input type="text" id="friend-search-input" class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Kullanıcı adı yazın...">
                        <i class="fas fa-search absolute right-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    </div>
                    <div id="friend-search-results" class="mt-4 space-y-2">
                        <!-- Arama sonuçları buraya gelecek -->
                    </div>
                </div>

                <!-- Gelen İstekler -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                    <h2 class="text-xl font-semibold mb-4 dark:text-white">Bekleyen Arkadaşlık İstekleri</h2>
                    <div id="pending-requests-list" class="space-y-3">
                        <!-- İstekler buraya gelecek -->
                    </div>
                    <p id="no-pending-requests" class="text-gray-500 dark:text-gray-400 text-center py-2">Bekleyen istek yok.</p>
                </div>

                <!-- Arkadaş Listesi -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                    <h2 class="text-xl font-semibold mb-4 dark:text-white">Arkadaşlarım</h2>
                    <div id="friends-list" class="space-y-3">
                        <!-- Arkadaşlar buraya gelecek -->
                    </div>
                    <p id="no-friends" class="text-gray-500 dark:text-gray-400 text-center py-4">Henüz arkadaşın yok.</p>
                </div>
            </div>

            <!-- Meydan Okumalar Bölümü (aynı sekmede, altta) -->
            <div id="duels-section" class="mt-8 bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-semibold mb-4 dark:text-white">Düellolarım</h2>
                <div id="duels-list" class="space-y-4">
                    <!-- Düello listesi buraya gelecek -->
                </div>
                <p id="no-duels" class="text-gray-500 dark:text-gray-400 text-center py-4 hidden">Gösterilecek düello yok.</p>
            </div>
        </div>

    </div>

<?php include 'footer.php'; ?>