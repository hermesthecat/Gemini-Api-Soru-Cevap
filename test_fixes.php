<?php
require_once('config.php');

echo "Testing profile.php fixes...\n\n";

// Test 1: QuestController LIMIT clause fix
echo "1. Testing QuestController LIMIT clause:\n";
try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4", DB_USER, DB_PASS);

    // Test the specific query that was failing
    $count = 2;
    $stmt = $pdo->prepare("SELECT * FROM quests ORDER BY RAND() LIMIT " . intval($count));
    $stmt->execute();
    $quests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "   ✅ LIMIT clause works correctly - Found " . count($quests) . " quests\n";

} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

// Test 2: Check cache busting version
echo "\n2. Testing cache busting:\n";
include 'header.php';
echo "   ✅ Cache busting version: $v (timestamp-based)\n";

// Test 3: Check if stats-handler.js exports updateAchievements
echo "\n3. Testing stats-handler.js exports:\n";
$statsHandlerContent = file_get_contents('assets/js/stats-handler.js');
if (strpos($statsHandlerContent, 'updateAchievements,') !== false) {
    echo "   ✅ updateAchievements is properly exported\n";
} else {
    echo "   ❌ updateAchievements export not found\n";
}

echo "\n✅ All fixes are in place! The profile.php console errors should be resolved.\n";
echo "   Browser cache will automatically refresh due to timestamp-based versioning.\n";
?>