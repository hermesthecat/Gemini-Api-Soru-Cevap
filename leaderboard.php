<?php
include 'auth_check.php';
include 'header.php';
?>

    <!-- Ana Konteyner -->
    <div id="app-container" class="container mx-auto px-4 py-8 max-w-4xl">

        <?php include 'nav.php'; ?>
                <!-- Liderlik Tablosu Sayfası -->
        <div id="leaderboard-tab" class="main-tab-content">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-gray-800 dark:text-white">🏆 Liderlik Tablosu</h1>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-8">
                <div class="text-center mb-6">
                    <h2 class="text-2xl font-semibold mb-2 dark:text-white">En İyi Oyuncular</h2>
                    <p class="text-gray-600 dark:text-gray-400">Toplam puana göre sıralama</p>
                </div>

                <div id="leaderboard-container" class="max-w-2xl mx-auto">
                    <ol id="leaderboard-list" class="space-y-4">
                        <!-- JS ile doldurulacak -->
                    </ol>
                    <p id="leaderboard-loading" class="text-gray-500 dark:text-gray-400 text-center py-8">
                        <i class="fas fa-spinner fa-spin mr-2"></i>Liderlik tablosu yükleniyor...
                    </p>
                </div>

                <!-- İstatistikler -->
                <div class="mt-8 pt-6 border-t dark:border-gray-700">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-center">
                        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
                            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400" id="total-players">-</div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">Toplam Oyuncu</div>
                        </div>
                        <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
                            <div class="text-2xl font-bold text-green-600 dark:text-green-400" id="total-questions">-</div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">Cevaplanan Soru</div>
                        </div>
                        <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4">
                            <div class="text-2xl font-bold text-purple-600 dark:text-purple-400" id="avg-score">-</div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">Ortalama Puan</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

<script>
// Liderlik tablosu sayfası yüklendiğinde içeriği yükle
document.addEventListener('DOMContentLoaded', () => {
    const leaderboardTab = document.getElementById('leaderboard-tab');
    if (leaderboardTab) {
        leaderboardTab.classList.remove('hidden');
        leaderboardTab.classList.add('block');
    }

    // Stats yükle
    setTimeout(() => {
        if (typeof statsHandler !== 'undefined') {
            statsHandler.updateLeaderboard();
            statsHandler.startLeaderboardUpdates();

            // İstatistikleri yükle
            loadLeaderboardStats();
        }
    }, 100);
});

// Liderlik tablosu istatistiklerini yükle
async function loadLeaderboardStats() {
    try {
        // Toplam oyuncu sayısı
        const usersResponse = await ApiHandler.makeRequest('admin_get_dashboard_data');
        if (usersResponse.success) {
            document.getElementById('total-players').textContent = usersResponse.data.total_users || 0;
            document.getElementById('total-questions').textContent = usersResponse.data.total_questions || 0;
        }

        // Liderlik tablosundan ortalama puan hesapla
        const leaderboardResponse = await ApiHandler.makeRequest('get_leaderboard');
        if (leaderboardResponse.success && leaderboardResponse.data.length > 0) {
            const totalScore = leaderboardResponse.data.reduce((sum, user) => sum + parseInt(user.score), 0);
            const avgScore = Math.round(totalScore / leaderboardResponse.data.length);
            document.getElementById('avg-score').textContent = avgScore;
        }
    } catch (error) {
        console.log('İstatistikler yüklenirken hata:', error);
    }
}
</script>

<?php include 'footer.php'; ?>