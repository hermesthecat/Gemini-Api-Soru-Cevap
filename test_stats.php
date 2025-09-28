<?php
require_once 'config.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $user_id = 1; // admin user

    // Test leaderboard query
    echo "Testing leaderboard query:\n";
    $stmt = $pdo->prepare("SELECT score FROM leaderboard WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $leaderboard = $stmt->fetch(PDO::FETCH_ASSOC);
    var_dump($leaderboard);

    // Test user_stats query
    echo "\nTesting user_stats query:\n";
    $stmt = $pdo->prepare("
        SELECT
            SUM(total_questions) as total_questions,
            SUM(correct_answers) as correct_answers
        FROM user_stats
        WHERE user_id = ?
    ");
    $stmt->execute([$user_id]);
    $userStats = $stmt->fetch(PDO::FETCH_ASSOC);
    var_dump($userStats);

    // Test the full statistics
    echo "\nBuilding statistics array:\n";
    $stats = [];
    $stats['total_score'] = (int)($leaderboard['score'] ?? 0);
    $stats['total_questions'] = (int)($userStats['total_questions'] ?? 0);
    $stats['correct_answers'] = (int)($userStats['correct_answers'] ?? 0);
    $stats['accuracy_percentage'] = $stats['total_questions'] > 0
        ? round(($stats['correct_answers'] / $stats['total_questions']) * 100, 1)
        : 0;

    var_dump($stats);

    echo "\nAs JSON:\n";
    echo json_encode((object)$stats, JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}