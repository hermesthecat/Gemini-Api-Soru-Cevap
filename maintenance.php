<?php
require_once 'config.php';
session_start();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bakım Modu - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .maintenance-animation {
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        .gear-spin {
            animation: spin 4s linear infinite;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-900 via-purple-900 to-indigo-900 min-h-screen flex items-center justify-center">

    <div class="container mx-auto px-4 py-8 max-w-2xl text-center">

        <!-- Ana Maintenance Kartı -->
        <div class="bg-white/10 backdrop-blur-lg rounded-3xl p-8 md:p-12 shadow-2xl border border-white/20">

            <!-- Animasyonlu İkon -->
            <div class="maintenance-animation mb-8">
                <div class="relative inline-block">
                    <i class="fas fa-tools fa-6x text-yellow-400 mb-4"></i>
                    <i class="fas fa-cog gear-spin absolute -top-2 -right-2 fa-2x text-blue-400"></i>
                </div>
            </div>

            <!-- Başlık -->
            <h1 class="text-4xl md:text-5xl font-bold text-white mb-6">
                🔧 Bakım Modu
            </h1>

            <!-- Açıklama -->
            <div class="text-lg md:text-xl text-gray-200 mb-8 space-y-4">
                <p class="leading-relaxed">
                    <strong class="text-yellow-400"><?php echo SITE_NAME; ?></strong> şu anda bakım çalışması nedeniyle geçici olarak kapatılmıştır.
                </p>
                <p class="text-base md:text-lg">
                    Sistem güncellemeleri ve iyileştirmeler yapılıyor. En kısa sürede tekrar hizmetinizde olacağız.
                </p>
            </div>

            <!-- Bilgi Kutuları -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <div class="bg-white/5 rounded-xl p-4 border border-white/10">
                    <i class="fas fa-clock text-blue-400 text-2xl mb-2"></i>
                    <h3 class="text-white font-semibold mb-1">Tahmini Süre</h3>
                    <p class="text-gray-300 text-sm">Birkaç dakika - Birkaç saat</p>
                </div>
                <div class="bg-white/5 rounded-xl p-4 border border-white/10">
                    <i class="fas fa-shield-alt text-green-400 text-2xl mb-2"></i>
                    <h3 class="text-white font-semibold mb-1">Verileriniz Güvende</h3>
                    <p class="text-gray-300 text-sm">Tüm veriler korunuyor</p>
                </div>
            </div>

            <!-- Çıkış Butonu -->
            <div class="space-y-4">
                <button onclick="logout()"
                        class="inline-flex items-center px-6 py-3 bg-red-500/80 hover:bg-red-600 text-white font-medium rounded-xl transition-all duration-200 transform hover:scale-105">
                    <i class="fas fa-sign-out-alt mr-2"></i>
                    Çıkış Yap
                </button>

                <!-- Admin Notı -->
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <div class="mt-6 p-4 bg-orange-500/20 border border-orange-500/30 rounded-xl">
                    <p class="text-orange-200 text-sm">
                        <i class="fas fa-crown mr-2"></i>
                        <strong>Admin Notu:</strong> Siz admin yetkisine sahip olduğunuz için siteye erişmeye devam edebilirsiniz.
                        <a href="<?php echo DOMAIN; ?>" class="underline hover:text-orange-100">Ana sayfaya dön</a>
                    </p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Footer -->
            <div class="mt-8 pt-6 border-t border-white/10">
                <p class="text-gray-400 text-sm">
                    Acil durumlar için:
                    <a href="mailto:support@example.com" class="text-blue-400 hover:text-blue-300 underline">
                        destek ekibimizle iletişime geçin
                    </a>
                </p>
            </div>

        </div>

        <!-- Alt Bilgi -->
        <div class="mt-6 text-center">
            <p class="text-gray-400 text-sm">
                © <?php echo date('Y'); ?> <?php echo SITE_NAME; ?> - Tüm hakları saklıdır.
            </p>
        </div>

    </div>

    <!-- Auto Refresh Script -->
    <script>
        // Logout fonksiyonu
        function logout() {
            fetch('<?php echo DOMAIN; ?>api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'logout'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = '<?php echo DOMAIN; ?>login.php';
                } else {
                    alert('Çıkış yapılırken bir hata oluştu');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                // Hata olsa bile login sayfasına yönlendir
                window.location.href = '<?php echo DOMAIN; ?>login.php';
            });
        }

        // Her 30 saniyede bir sayfayı yenile (maintenance bitmiş mi kontrol et)
        setInterval(() => {
            fetch('<?php echo DOMAIN; ?>api.php?action=check_session')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Session aktifse ana sayfaya yönlendir
                        window.location.href = '<?php echo DOMAIN; ?>';
                    }
                })
                .catch(() => {
                    // Hata durumunda sayfayı yenile
                    window.location.reload();
                });
        }, 30000);
    </script>

</body>
</html>