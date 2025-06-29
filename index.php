<?php
/**
 * AI Bilgi Yarışması - Ana Uygulama Dosyası
 *
 * Bu dosya, kullanıcı arayüzünü oluşturur, oyun mantığını yönetir (kategori seçimi,
 * soru sorma, cevap kontrolü), istatistikleri tutar ve Gemini API ile etkileşimi başlatır.
 */

require_once 'config.php';
require_once 'GeminiAPI.php';

// Oturumu başlatır, böylece kullanıcıya özel verileri (soru, istatistikler) saklayabiliriz.
session_start();

// İstatistikleri sıfırlama isteği kontrol edilir.
if (isset($_POST['reset_stats'])) {
    // Oturumdaki istatistik ve geçmiş verilerini temizler.
    $_SESSION['stats'] = ['total_questions' => 0, 'correct_answers' => 0];
    $_SESSION['history'] = [];
    // Sayfayı yeniden yönlendirerek formun tekrar gönderilmesini ve çift sıfırlamayı engeller.
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}


// Oturumda istatistikler ve geçmiş dizileri yoksa, boş olarak oluşturulur.
// Bu, sayfa ilk yüklendiğinde veya oturum sıfırlandığında hataları önler.
if (!isset($_SESSION['stats'])) {
    $_SESSION['stats'] = ['total_questions' => 0, 'correct_answers' => 0];
}
if (!isset($_SESSION['history'])) {
    $_SESSION['history'] = [];
}

// GeminiAPI sınıfından bir nesne oluşturulur ve API anahtarı ile yapılandırılır.
$gemini = new GeminiAPI(GEMINI_API_KEY);
$error_message = null; // Hata mesajlarını saklamak için bir değişken.

// Debug için sunucu loglarına POST ve SESSION verilerini yazar. Geliştirme aşamasında kullanışlıdır.
error_log("POST data: " . print_r($_POST, true));
error_log("SESSION data: " . print_r($_SESSION, true));

// Kullanıcı bir kategori seçtiğinde veya değiştirdiğinde bu blok çalışır.
if (isset($_POST['kategori'])) {
    $yeni_kategori = $_POST['kategori'];

    // Boş bir kategori seçimi yapılmadığından emin olunur.
    if (!empty($yeni_kategori)) {
        // Yeni bir soruya başlamadan önce mevcut soruyla ilgili oturum verileri temizlenir.
        unset($_SESSION['current_question']);
        unset($_SESSION['siklar']);
        unset($_SESSION['dogru_cevap']);
        unset($_SESSION['start_time']);

        // Seçilen yeni kategori oturumda saklanır.
        $_SESSION['kategori'] = $yeni_kategori;

        // API'den yeni soru almak için bir `try-catch` bloğu kullanılır. Bu, API hatalarını yakalamayı sağlar.
        try {
            // Gemini API'ye gönderilecek olan komut (prompt).
            // Yanıtın belirli bir JSON formatında olması istenir, bu da veriyi işlemeyi kolaylaştırır.
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

            // Gemini API'sine soru sormak için oluşturulan nesnenin metodu çağrılır.
            $yanit = $gemini->soruSor($prompt);
            error_log("API Response: " . $yanit);

            // API'den bir yanıt gelip gelmediği kontrol edilir.
            if ($yanit) {
                // API yanıtının başında veya sonunda olabilecek fazladan karakterleri (örneğin ```json) temizler.
                $temiz_yanit = preg_replace('/^```json\s*|\s*```$/', '', trim($yanit));
                // JSON formatındaki yanıt, bir PHP dizisine çevrilir.
                $veri = json_decode($temiz_yanit, true);

                // JSON'un doğru bir şekilde çözülüp çözülmediği ve beklenen alanları içerip içermediği kontrol edilir.
                if (json_last_error() === JSON_ERROR_NONE && isset($veri['soru'], $veri['siklar'], $veri['dogru_cevap'])) {
                    $soru = $veri['soru'];
                    $siklar = $veri['siklar'];
                    $dogru_cevap = $veri['dogru_cevap'];

                    // Gelen veriler oturumda saklanır.
                    $_SESSION['current_question'] = $soru;
                    $_SESSION['siklar'] = $siklar;
                    $_SESSION['dogru_cevap'] = $dogru_cevap;
                    // Soru için geri sayım sayacını başlatmak üzere başlangıç zamanı kaydedilir.
                    $_SESSION['start_time'] = time();

                    // Debug için yüklenen soruyu loglar.
                    error_log("Soru yüklendi: " . $soru);
                    error_log("Şıklar: " . print_r($siklar, true));
                    error_log("Doğru cevap: " . $dogru_cevap);
                } else {
                    // JSON verisi bozuksa veya eksikse, kullanıcıya gösterilecek bir hata mesajı ayarlanır.
                    $error_message = "Soru işlenirken bir hata oluştu (geçersiz format). Lütfen tekrar deneyin.";
                    error_log("JSON Decode/Structure Error. Response: " . $temiz_yanit);
                }
            } else {
                // API'den hiç yanıt alınamazsa, bir hata mesajı ayarlanır.
                $error_message = "API'den yanıt alınamadı. Lütfen API anahtarınızı kontrol edin veya daha sonra tekrar deneyin.";
            }
        } catch (Exception $e) {
            // API isteği sırasında bir sunucu hatası olursa, bu hata yakalanır ve bir mesaj ayarlanır.
            error_log("Hata: " . $e->getMessage());
            $error_message = "Soru alınırken bir sunucu hatası oluştu. Lütfen daha sonra tekrar deneyin.";
        }
    }
}

// Süre dolduğunda (JavaScript tarafından tetiklenir), mevcut kategoride yeni bir soru istemek için bu blok çalışır.
if (isset($_POST['sure_doldu'])) {
    $kategori = $_SESSION['kategori'];
    $_POST['kategori'] = $kategori;
}

// Kullanıcı bir cevaba tıkladığında bu blok çalışır.
if (isset($_POST['kullanici_cevap'])) {
    $kullanici_cevap = strtoupper(trim($_POST['kullanici_cevap']));
    $kullanici_cevap = preg_replace('/[^A-D]/', '', $kullanici_cevap);
    $gecen_sure = time() - $_SESSION['start_time'];
    $is_correct = false; // Cevabın doğruluğunu takip etmek için bir bayrak.

    error_log("Kullanıcı cevabı: " . $kullanici_cevap);
    error_log("Doğru cevap: " . $_SESSION['dogru_cevap']);

    // Sürenin dolup dolmadığı kontrol edilir.
    if ($gecen_sure > 30) {
        $sonuc = "Süre doldu! Yeni bir soru alabilirsiniz.";
        $sonuc_class = "text-red-600";
    } else {
        // Kullanıcının cevabı ile doğru cevap karşılaştırılır.
        if ($kullanici_cevap === $_SESSION['dogru_cevap']) {
            $sonuc = "Tebrikler! Doğru cevap verdiniz. Süre: {$gecen_sure} saniye";
            $sonuc_class = "text-green-600";
            $is_correct = true; // Cevap doğruysa bayrak true olarak ayarlanır.
        } else {
            $sonuc = "Üzgünüm, yanlış cevap. Doğru cevap: " . $_SESSION['dogru_cevap'];
            $sonuc_class = "text-red-600";
        }
    }

    // İstatistikler ve soru geçmişi güncellenir.
    if (!isset($sonuc) || $kullanici_cevap) { // Sadece bir cevap verildiğinde sayacı artır
        $_SESSION['stats']['total_questions']++;
        if ($is_correct) {
            $_SESSION['stats']['correct_answers']++;
        }

        // Mevcut soru ve cevap bilgileriyle bir geçmiş öğesi oluşturulur.
        $history_item = [
            'question' => $_SESSION['current_question'],
            'user_answer' => $kullanici_cevap,
            'correct_answer' => $_SESSION['dogru_cevap'],
            'is_correct' => $is_correct,
            'siklar' => $_SESSION['siklar']
        ];
        // Yeni öğe, geçmiş dizisinin başına eklenir.
        array_unshift($_SESSION['history'], $history_item);

        // Geçmiş listesinin çok uzamaması için son 5 soruyla sınırlandırılır.
        if (count($_SESSION['history']) > 5) {
            array_pop($_SESSION['history']);
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

        <!-- İstatistikler ve Geçmiş -->
        <div class="bg-white rounded-xl shadow-lg p-6 mt-8">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold">İstatistikleriniz</h2>
                <form method="POST">
                    <button type="submit" name="reset_stats" value="1" class="text-sm bg-red-500 hover:bg-red-600 text-white py-1 px-3 rounded-lg transition-colors">
                        Sıfırla
                    </button>
                </form>
            </div>

            <?php
            $stats = $_SESSION['stats'];
            $total = $stats['total_questions'];
            $correct = $stats['correct_answers'];
            $success_rate = ($total > 0) ? round(($correct / $total) * 100) : 0;
            ?>
            <div class="grid grid-cols-3 gap-4 text-center mb-6">
                <div>
                    <p class="text-2xl font-bold"><?php echo $total; ?></p>
                    <p class="text-gray-500">Toplam Soru</p>
                </div>
                <div>
                    <p class="text-2xl font-bold text-green-600"><?php echo $correct; ?></p>
                    <p class="text-gray-500">Doğru Cevap</p>
                </div>
                <div>
                    <p class="text-2xl font-bold text-blue-600"><?php echo $success_rate; ?>%</p>
                    <p class="text-gray-500">Başarı Oranı</p>
                </div>
            </div>

            <h3 class="text-lg font-semibold mb-3 border-t pt-4">Son Cevaplananlar</h3>
            <div class="space-y-4">
                <?php if (empty($_SESSION['history'])): ?>
                    <p class="text-gray-500 text-center">Henüz hiç soru cevaplamadınız.</p>
                <?php else: ?>
                    <?php foreach ($_SESSION['history'] as $item): ?>
                        <div class="p-3 rounded-lg <?php echo $item['is_correct'] ? 'bg-green-50 border-l-4 border-green-500' : 'bg-red-50 border-l-4 border-red-500'; ?>">
                            <p class="font-semibold mb-1"><?php echo htmlspecialchars($item['question']); ?></p>
                            <p class="text-sm">
                                Sizin Cevabınız: <span class="font-bold"><?php echo htmlspecialchars($item['user_answer']); ?>) <?php echo htmlspecialchars($item['siklar'][$item['user_answer']] ?? 'Cevap bulunamadı'); ?></span>
                            </p>
                            <?php if (!$item['is_correct']): ?>
                                <p class="text-sm">
                                    Doğru Cevap: <span class="font-bold"><?php echo htmlspecialchars($item['correct_answer']); ?>) <?php echo htmlspecialchars($item['siklar'][$item['correct_answer']]); ?></span>
                                </p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Footer -->
        <footer class="text-center text-gray-500 text-sm mt-8">
            <p>© 2024 AI Bilgi Yarışması. Tüm hakları saklıdır.</p>
        </footer>
    </div>

    <script>
        // --- YÜKLEME ANİMASYONU KONTROLÜ ---
        const loadingOverlay = document.getElementById('loading-overlay');
        
        // Kategori seçimi yapıldığında formu submit etmeden önce yükleme animasyonunu gösterir.
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
            // Dropdown menüsü değiştiğinde (onchange) formu submit ettiği için, forma bir event listener ekliyoruz.
            categoryChangeForm.addEventListener('submit', showLoading);
        }
    </script>

    <?php if (isset($_SESSION['current_question']) && !isset($sonuc)): ?>
        <script>
            // --- 30 SANİYE GERİ SAYIM SAYACI ---
            let timeLeft = 30;
            const countdownElement = document.getElementById('countdown');
            const answerForm = document.getElementById('answerForm');

            // Her saniye timeLeft değişkenini bir azaltır ve ekranda günceller.
            const timer = setInterval(() => {
                timeLeft--;
                countdownElement.textContent = timeLeft;

                // Süre 10 saniyenin altına düştüğünde rengi kırmızı yapar.
                if (timeLeft <= 10) {
                    countdownElement.classList.add('text-red-600');
                }

                // Süre dolduğunda sayacı durdurur ve sunucuya "süre doldu" bilgisi gönderir.
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

            // Kullanıcı bir cevap verdiğinde sayacın gereksiz yere çalışmasını engellemek için durdurulur.
            answerForm.addEventListener('submit', () => {
                clearInterval(timer);
            });
        </script>
    <?php endif; ?>
</body>

</html>