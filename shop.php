<?php
include 'auth_check.php';
include 'header.php';
?>

    <!-- Ana Konteyner -->
    <div id="app-container" class="container mx-auto px-4 py-8 max-w-4xl">

        <?php include 'nav.php'; ?>
                <!-- Mağaza Tab -->
        <div id="magaza-tab" class="main-tab-content block p-4 rounded-lg bg-gray-50 dark:bg-gray-800">
            <h3 class="text-xl font-bold mb-4 text-gray-800 dark:text-gray-200">Joker Mağazası</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">Jetonlarını kullanarak joker satın alabilir ve yarışmada avantaj elde edebilirsin.</p>
            <div id="shop-items-container" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Shop items will be rendered here by shop-handler.js -->
            </div>
        </div>

    </div>

<?php include 'footer.php'; ?>

<!-- Include User Search Handler -->
<script src="assets/js/user-search-handler.js?v=<?php echo time(); ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize user search functionality
    if (window.userSearchHandler) {
        window.userSearchHandler.init();
    }
});
</script>