<?php
require_once 'config.php';
require_once 'GeminiAPI.php';

session_start();

$gemini = new GeminiAPI(GEMINI_API_KEY);
$error_message = null; // Hata mesajları için değişken

// Debug için
error_log("POST data: " . print_r($_POST, true));
error_log("SESSION data: " . print_r($_SESSION, true));

// Kategori değiştirildiğinde veya süre dolduğunda
if (isset($_POST['kategori'])) {
    $yeni_kategori = $_POST['kategori'];

    // Boş kategori seçimi kontrolü
    if (!empty($yeni_kategori)) {
        // Session'ı temizle
        unset($_SESSION['current_question']);
        unset($_SESSION['siklar']);
        unset($_SESSION['dogru_cevap']);
        unset($_SESSION['start_time']);

        // Yeni kategoriyi kaydet
        $_SESSION['kategori'] = $yeni_kategori;

        // Yeni soru al
        try {
            $prompt = "Lütfen {$yeni_kategori} kategorisinde orta zorlukta bir soru hazırla. Yanıtı yalnızca şu JSON formatında, başka hiçbir metin olmadan ver:
            {
                \"soru\": \"(soru metni buraya)\",
                \"siklar\": {
                    \"A\": \"(A şıkkı buraya)\",
                    \"B\": \"(B şıkkı buraya)\",
                    \"C\": \"(C şıkkı buraya)\",
                    \"D\": \"(D şıkkı buraya)\"
                },
                \"dogru_cevap\": \"(Doğru şıkkın harfi buraya, örneğin: A)\"
            }";

            $yanit = $gemini->soruSor($prompt);
            error_log("API Response: " . $yanit);

            if ($yanit) {
                // API'den gelen yanıtı temizleyelim (bazen başlangıç ve bitişte ```json ... ``` gibi ifadeler olabiliyor)
                $temiz_yanit = preg_replace('/^```json\s*|\s*```$/', '', trim($yanit));
                $veri = json_decode($temiz_yanit, true);

                if (json_last_error() === JSON_ERROR_NONE && isset($veri['soru'], $veri['siklar'], $veri['dogru_cevap'])) {
                    $soru = $veri['soru'];
                    $siklar = $veri['siklar'];
                    $dogru_cevap = $veri['dogru_cevap'];

                    $_SESSION['current_question'] = $soru;
                    $_SESSION['siklar'] = $siklar;
                    $_SESSION['dogru_cevap'] = $dogru_cevap;
                    $_SESSION['start_time'] = time();

                    error_log("Soru yüklendi: " . $soru);
                    error_log("Şıklar: " . print_r($siklar, true));
                    error_log("Doğru cevap: " . $dogru_cevap);
                } else {
                    $error_message = "Soru işlenirken bir hata oluştu (geçersiz format). Lütfen tekrar deneyin.";
                    error_log("JSON Decode/Structure Error. Response: " . $temiz_yanit);
                }
            } else {
                $error_message = "API'den yanıt alınamadı. Lütfen API anahtarınızı kontrol edin veya daha sonra tekrar deneyin.";
            }
        } catch (Exception $e) {
            error_log("Hata: " . $e->getMessage());
            $error_message = "Soru alınırken bir sunucu hatası oluştu. Lütfen daha sonra tekrar deneyin.";
        }
    }
}

// Süre dolduğunda
if (isset($_POST['sure_doldu'])) {
    $kategori = $_SESSION['kategori'];
    $_POST['kategori'] = $kategori;
}

// Kullanıcı cevap verdiğinde
if (isset($_POST['kullanici_cevap'])) {
    $kullanici_cevap = strtoupper(trim($_POST['kullanici_cevap']));
    $kullanici_cevap = preg_replace('/[^A-D]/', '', $kullanici_cevap);
    $gecen_sure = time() - $_SESSION['start_time'];

    error_log("Kullanıcı cevabı: " . $kullanici_cevap);
    error_log("Doğru cevap: " . $_SESSION['dogru_cevap']);

    if ($gecen_sure > 30) {
        $sonuc = "Süre doldu! Yeni bir soru alabilirsiniz.";
        $sonuc_class = "text-red-600";
    } else {
        if ($kullanici_cevap === $_SESSION['dogru_cevap']) {
            $sonuc = "Tebrikler! Doğru cevap verdiniz. Süre: {$gecen_sure} saniye";
            $sonuc_class = "text-green-600";
        } else {
            $sonuc = "Üzgünüm, yanlış cevap. Doğru cevap: " . $_SESSION['dogru_cevap'];
            $sonuc_class = "text-red-600";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Bilgi Yarışması</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body class="bg-gray-50 min-h-screen">

    <!-- Loading Overlay -->
    <div id="loading-overlay" class="hidden fixed inset-0 bg-gray-900 bg-opacity-60 flex items-center justify-center z-50">
        <div class="flex items-center text-white">
            <i class="fas fa-spinner fa-spin text-4xl mr-4"></i>
            <span class="text-2xl font-semibold">Soru Yükleniyor...</span>
        </div>
    </div>

    <div class="container mx-auto px-4 py-8 max-w-4xl">
        <!-- Hata Mesajı Alanı -->
        <?php if ($error_message): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                <p class="font-bold">Bir Sorun Oluştu</p>
                <p><?php echo htmlspecialchars($error_message); ?></p>
            </div>
        <?php endif; ?>

        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-gray-800 mb-2">AI Bilgi Yarışması</h1>
            <p class="text-gray-600">Bilginizi test edin!</p>
        </div>

        <!-- Timer -->
        <?php if (isset($_SESSION['current_question']) && !isset($sonuc)): ?>
            <div id="timer" class="fixed top-4 right-4 bg-white rounded-lg shadow-lg p-4 text-center">
                <div class="text-xl font-bold">Kalan Süre</div>
                <div id="countdown" class="text-2xl text-blue-600">30</div>
            </div>
        <?php endif; ?>

        <!-- Kategori Seçimi -->
        <?php if (!isset($_SESSION['current_question'])): ?>
            <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
                <h2 class="text-xl font-semibold mb-4">Kategori Seçin</h2>
                <form id="category-form" method="POST" class="grid grid-cols-2 md:grid-cols-3 gap-4">
                    <button type="submit" name="kategori" value="tarih" class="category-button bg-blue-100 hover:bg-blue-200 p-4 rounded-lg">
                        <i class="fas fa-history mb-2"></i>
                        <span class="block">Tarih</span>
                    </button>
                    <button type="submit" name="kategori" value="spor" class="category-button bg-green-100 hover:bg-green-200 p-4 rounded-lg">
                        <i class="fas fa-futbol mb-2"></i>
                        <span class="block">Spor</span>
                    </button>
                    <button type="submit" name="kategori" value="bilim" class="category-button bg-purple-100 hover:bg-purple-200 p-4 rounded-lg">
                        <i class="fas fa-atom mb-2"></i>
                        <span class="block">Bilim</span>
                    </button>
                    <button type="submit" name="kategori" value="sanat" class="category-button bg-yellow-100 hover:bg-yellow-200 p-4 rounded-lg">
                        <i class="fas fa-palette mb-2"></i>
                        <span class="block">Sanat</span>
                    </button>
                    <button type="submit" name="kategori" value="coğrafya" class="category-button bg-red-100 hover:bg-red-200 p-4 rounded-lg">
                        <i class="fas fa-globe-americas mb-2"></i>
                        <span class="block">Coğrafya</span>
                    </button>
                    <button type="submit" name="kategori" value="genel kültür" class="category-button bg-indigo-100 hover:bg-indigo-200 p-4 rounded-lg">
                        <i class="fas fa-brain mb-2"></i>
                        <span class="block">Genel Kültür</span>
                    </button>
                </form>
            </div>
        <?php endif; ?>

        <!-- Soru ve Cevap Alanı -->
        <?php if (isset($_SESSION['current_question']) && isset($_SESSION['siklar'])): ?>
            <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
                <div class="mb-6">
                    <!-- Kategori Seçimi ve Mevcut Kategori Gösterimi -->
                    <div class="flex justify-between items-center mb-4">
                        <span class="inline-block bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm">
                            <?php echo htmlspecialchars($_SESSION['kategori']); ?>
                        </span>
                        <div class="relative">
                            <form id="category-change-form" method="POST" class="inline">
                                <select name="kategori" onchange="this.form.submit()"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5">
                                    <option value="">Kategori Değiştir</option>
                                    <option value="tarih" <?php echo ($_SESSION['kategori'] == 'tarih') ? 'selected' : ''; ?>>Tarih</option>
                                    <option value="spor" <?php echo ($_SESSION['kategori'] == 'spor') ? 'selected' : ''; ?>>Spor</option>
                                    <option value="bilim" <?php echo ($_SESSION['kategori'] == 'bilim') ? 'selected' : ''; ?>>Bilim</option>
                                    <option value="sanat" <?php echo ($_SESSION['kategori'] == 'sanat') ? 'selected' : ''; ?>>Sanat</option>
                                    <option value="coğrafya" <?php echo ($_SESSION['kategori'] == 'coğrafya') ? 'selected' : ''; ?>>Coğrafya</option>
                                    <option value="genel kültür" <?php echo ($_SESSION['kategori'] == 'genel kültür') ? 'selected' : ''; ?>>Genel Kültür</option>
                                </select>
                            </form>
                        </div>
                    </div>

                    <!-- Soru -->
                    <div class="text-gray-700 mb-4">
                        <h3 class="text-xl font-semibold mb-2">Soru:</h3>
                        <p><?php echo htmlspecialchars($_SESSION['current_question']); ?></p>
                    </div>
                </div>

                <!-- Şıklar -->
                <form method="POST" id="answerForm" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <?php foreach ($_SESSION['siklar'] as $harf => $metin): ?>
                            <button type="submit" name="kullanici_cevap" value="<?php echo $harf; ?>"
                                class="p-4 text-left rounded-lg border border-gray-300 hover:bg-blue-50 transition-colors">
                                <span class="font-semibold"><?php echo $harf; ?></span>) <?php echo htmlspecialchars($metin); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </form>

                <?php if (isset($sonuc)): ?>
                    <div class="mt-6 p-4 rounded-lg bg-gray-50">
                        <p class="<?php echo $sonuc_class; ?> font-semibold"><?php echo $sonuc; ?></p>
                        <form method="POST" class="mt-4">
                            <button type="submit" name="kategori" value="<?php echo $_SESSION['kategori']; ?>"
                                class="bg-gray-600 hover:bg-gray-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-200">
                                Yeni Soru
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Footer -->
        <footer class="text-center text-gray-500 text-sm">
            <p>© 2024 AI Bilgi Yarışması. Tüm hakları saklıdır.</p>
        </footer>
    </div>

    <script>
        const loadingOverlay = document.getElementById('loading-overlay');

        // Kategori seçim formları için event listener ekle
        const categoryForm = document.getElementById('category-form');
        const categoryChangeForm = document.getElementById('category-change-form');

        const showLoading = () => {
            if (loadingOverlay) {
                loadingOverlay.classList.remove('hidden');
            }
        };

        if (categoryForm) {
            categoryForm.addEventListener('submit', showLoading);
        }

        if (categoryChangeForm) {
            // Dropdown'un formu onchange ile submit edildiği için forma listener ekliyoruz.
            categoryChangeForm.addEventListener('submit', showLoading);
        }
    </script>

    <?php if (isset($_SESSION['current_question']) && !isset($sonuc)): ?>
        <script>
            let timeLeft = 30;
            const countdownElement = document.getElementById('countdown');
            const answerForm = document.getElementById('answerForm');

            const timer = setInterval(() => {
                timeLeft--;
                countdownElement.textContent = timeLeft;

                if (timeLeft <= 10) {
                    countdownElement.classList.add('text-red-600');
                }

                if (timeLeft <= 0) {
                    clearInterval(timer);
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = '<input type="hidden" name="sure_doldu" value="1">' +
                        '<input type="hidden" name="kategori" value="<?php echo $_SESSION['kategori']; ?>">';
                    document.body.appendChild(form);
                    form.submit();
                }
            }, 1000);

            answerForm.addEventListener('submit', () => {
                clearInterval(timer);
            });
        </script>
    <?php endif; ?>
</body>

</html>