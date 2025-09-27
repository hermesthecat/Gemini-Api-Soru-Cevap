<?php
// Session başlat
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Cache busting için timestamp kullan (güvenilir yenileme için)
function getVersion() {
    return time();
}
$v = getVersion();
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?></title>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🧠</text></svg>">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo $v; ?>">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Tema yönetimi için FOUC önleyici betik
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark')
        } else {
            document.documentElement.classList.remove('dark')
        }

        // CSRF token'ı erken set et (auth_check.php'den geliyorsa)
        <?php if (isset($user_data) && isset($user_data['csrf_token'])): ?>
        window.CSRF_TOKEN = '<?php echo $user_data['csrf_token']; ?>';
        window.USER_DATA = <?php echo json_encode($user_data); ?>;
        <?php endif; ?>
    </script>
</head>

<body class="bg-gray-50 dark:bg-gray-900 min-h-screen text-gray-800 dark:text-gray-200 font-sans">

    <!-- Header Admin Link -->
    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
    <div class="bg-purple-600 text-white py-1 px-4 text-center text-sm">
        <i class="fas fa-crown mr-2"></i>Admin Paneli:
        <a href="admin-users.php" class="underline hover:text-purple-200">Kullanıcılar</a> |
        <a href="admin-announcements.php" class="underline hover:text-purple-200">Duyurular</a> |
        <a href="admin-stats.php" class="underline hover:text-purple-200">İstatistikler</a> |
        <a href="admin-settings.php" class="underline hover:text-purple-200">Ayarlar</a> |
        <a href="index.php" class="underline hover:text-purple-200">Ana Sayfa</a>
    </div>
    <?php endif; ?>