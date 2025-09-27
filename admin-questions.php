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
    <div id="app-container" class="container mx-auto px-4 py-8 max-w-7xl">

        <?php include 'nav.php'; ?>

        <!-- Soru Yönetimi -->
        <div id="admin-questions-tab" class="admin-tab-content">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-gray-800 dark:text-white flex items-center">
                    <i class="fas fa-question-circle mr-3 text-blue-500"></i>
                    Soru Yönetimi
                </h1>
                <p class="text-gray-600 dark:text-gray-400 mt-2">
                    Kullanıcılar tarafından şikayet edilen soruları inceleyin ve yönetin.
                </p>
            </div>

            <!-- Tab Navigation -->
            <div class="mb-6">
                <div class="flex space-x-1 bg-gray-100 dark:bg-gray-700 p-1 rounded-lg">
                    <button id="reported-questions-tab"
                        class="tab-button flex-1 px-4 py-2 text-sm font-medium rounded-md transition-colors bg-white dark:bg-gray-800 text-blue-600 dark:text-blue-400 shadow">
                        <i class="fas fa-flag mr-2"></i>Şikayetli Sorular
                    </button>
                    <button id="question-stats-tab"
                        class="tab-button flex-1 px-4 py-2 text-sm font-medium rounded-md transition-colors text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200">
                        <i class="fas fa-chart-bar mr-2"></i>İstatistikler
                    </button>
                </div>
            </div>

            <!-- Şikayetli Sorular İçeriği -->
            <div id="reported-questions-content" class="tab-content">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-bold text-gray-800 dark:text-white">
                            Şikayet Edilen Sorular
                        </h2>
                        <button id="refresh-reported-questions"
                            class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition-colors">
                            <i class="fas fa-sync-alt mr-2"></i>Yenile
                        </button>
                    </div>

                    <div id="reported-questions-container" class="space-y-4">
                        <!-- Reported questions will be loaded here -->
                        <div class="text-center py-8">
                            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500 mx-auto mb-4"></div>
                            <p class="text-gray-600 dark:text-gray-400">Şikayetli sorular yükleniyor...</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- İstatistikler İçeriği -->
            <div id="question-stats-content" class="tab-content hidden">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-bold text-gray-800 dark:text-white">
                            Soru İstatistikleri
                        </h2>
                        <button id="refresh-question-stats"
                            class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg transition-colors">
                            <i class="fas fa-sync-alt mr-2"></i>Yenile
                        </button>
                    </div>

                    <div id="question-stats-container">
                        <!-- Question stats will be loaded here -->
                        <div class="text-center py-8">
                            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-green-500 mx-auto mb-4"></div>
                            <p class="text-gray-600 dark:text-gray-400">İstatistikler yükleniyor...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php include 'footer.php'; ?>

<script>
// Tab functionality
document.addEventListener('DOMContentLoaded', function() {
    // Tab switching
    const tabButtons = document.querySelectorAll('.tab-button');
    const tabContents = document.querySelectorAll('.tab-content');

    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            const targetId = button.id.replace('-tab', '-content');

            // Update button styles
            tabButtons.forEach(btn => {
                btn.classList.remove('bg-white', 'dark:bg-gray-800', 'text-blue-600', 'dark:text-blue-400', 'shadow');
                btn.classList.add('text-gray-600', 'dark:text-gray-400', 'hover:text-gray-800', 'dark:hover:text-gray-200');
            });

            button.classList.add('bg-white', 'dark:bg-gray-800', 'text-blue-600', 'dark:text-blue-400', 'shadow');
            button.classList.remove('text-gray-600', 'dark:text-gray-400', 'hover:text-gray-800', 'dark:hover:text-gray-200');

            // Update content visibility
            tabContents.forEach(content => {
                content.classList.add('hidden');
            });

            const targetContent = document.getElementById(targetId);
            if (targetContent) {
                targetContent.classList.remove('hidden');
            }
        });
    });

    // Refresh buttons
    document.getElementById('refresh-reported-questions')?.addEventListener('click', () => {
        adminHandler.loadReportedQuestions();
    });

    document.getElementById('refresh-question-stats')?.addEventListener('click', () => {
        adminHandler.loadQuestionStats();
    });

    // Load initial data
    adminHandler.loadReportedQuestions();
    adminHandler.loadQuestionStats();
});
</script>