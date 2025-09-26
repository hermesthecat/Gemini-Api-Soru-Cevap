<?php
include 'auth_check.php';
include 'header.php';
?>

    <!-- Ana Konteyner -->
    <div id="app-container" class="container mx-auto px-4 py-8 max-w-4xl">

        <?php include 'nav.php'; ?>
                <!-- Profil Sekmesi İçeriği -->
        <div id="profil-tab" class="main-tab-content block">
            <aside class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Sol Taraf (İstatistikler ve Liderlik) -->
                <div class="space-y-8">
                    <!-- Avatar Seçimi -->
                    <div id="avatar-selection-container" class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                        <h2 class="text-xl font-semibold mb-4 dark:text-white">Avatarını Değiştir</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Yeni bir avatar seçmek için üzerine tıkla.</p>
                        <div id="avatar-grid" class="grid grid-cols-5 gap-4">
                            <!-- Avatarlar JS ile buraya yüklenecek -->
                        </div>
                    </div>

                    <div id="stats-container" class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                        <h2 class="text-xl font-semibold mb-4 dark:text-white">Kişisel İstatistikler</h2>
                        <div class="text-center mb-4 border-b dark:border-gray-700 pb-4">
                            <p class="text-gray-500 dark:text-gray-400">Toplam Puan</p>
                            <p id="user-total-score" class="text-3xl font-bold text-blue-600">0</p>
                        </div>
                        <h3 class="text-lg font-semibold mb-3 dark:text-white">Kategori Detayları</h3>
                        <div id="category-stats-container" class="max-h-60 overflow-y-auto">
                            <table class="w-full text-left text-sm">
                                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400 sticky top-0">
                                    <tr>
                                        <th class="py-2 px-2">Kategori</th>
                                        <th class="py-2 px-2 text-center">Soru</th>
                                        <th class="py-2 px-2 text-center">Doğru</th>
                                        <th class="py-2 px-2 text-center">%</th>
                                    </tr>
                                </thead>
                                <tbody id="category-stats-body">
                                    <!-- JS ile doldurulacak -->
                                </tbody>
                            </table>
                            <p id="no-stats-message" class="text-gray-500 dark:text-gray-400 text-center py-4">Henüz veri yok.</p>
                        </div>
                    </div>
                    <div id="leaderboard-container" class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                        <h2 class="text-xl font-semibold mb-4 dark:text-white">Liderlik Tablosu</h2>
                        <ol id="leaderboard-list" class="space-y-3">
                            <!-- JS ile doldurulacak -->
                        </ol>
                        <p id="leaderboard-loading" class="text-gray-500 dark:text-gray-400 text-center py-4">Yükleniyor...</p>
                    </div>
                </div>
                <!-- Sağ Taraf (Başarımlar) -->
                <div id="achievements-container" class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                    <h2 class="text-xl font-semibold mb-4 dark:text-white">Kazanılan Rozetler</h2>
                    <div id="achievements-list" class="space-y-4">
                        <!-- JS ile doldurulacak -->
                    </div>
                    <p id="no-achievements-message" class="text-gray-500 dark:text-gray-400 text-center py-4">Henüz kazanılmış rozet yok.</p>
                </div>
            </aside>
        </div>

    </div>

<?php include 'footer.php'; ?>