<?php

class QuestionsController
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Submit a rating for a question
     */
    public function submitQuestionRating($data)
    {
        // Authentication check
        if (!isset($_SESSION['user_id'])) {
            return ['success' => false, 'message' => 'Bu işlem için giriş yapmalısınız.'];
        }

        $question_id = (int)($data['question_id'] ?? 0);
        $rating = (int)($data['rating'] ?? 0);
        $feedback = trim($data['feedback'] ?? '');
        $report_reason = $data['report_reason'] ?? null;
        $user_id = $_SESSION['user_id'];

        // Validation
        if ($question_id <= 0) {
            return ['success' => false, 'message' => 'Geçersiz soru ID.'];
        }

        if ($rating < 1 || $rating > 5) {
            return ['success' => false, 'message' => 'Puan 1-5 arasında olmalıdır.'];
        }

        // Check if question exists
        $stmt = $this->pdo->prepare("SELECT id FROM questions WHERE id = ?");
        $stmt->execute([$question_id]);
        if (!$stmt->fetch()) {
            return ['success' => false, 'message' => 'Soru bulunamadı.'];
        }

        // Check if user already rated this question
        $stmt = $this->pdo->prepare("SELECT id FROM question_ratings WHERE question_id = ? AND user_id = ?");
        $stmt->execute([$question_id, $user_id]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Bu soruyu zaten değerlendirdiniz.'];
        }

        // Validate report reason if provided
        $valid_reasons = ['hata', 'belirsiz', 'kalitesiz', 'tekrar', 'diger'];
        if ($report_reason && !in_array($report_reason, $valid_reasons)) {
            $report_reason = null;
        }

        try {
            $this->pdo->beginTransaction();

            // Insert rating
            $stmt = $this->pdo->prepare("
                INSERT INTO question_ratings (question_id, user_id, rating, feedback, report_reason)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$question_id, $user_id, $rating, $feedback, $report_reason]);

            $this->pdo->commit();

            return [
                'success' => true,
                'message' => 'Değerlendirmeniz kaydedildi. Teşekkürler!',
                'data' => [
                    'rating_id' => $this->pdo->lastInsertId(),
                    'rating' => $rating,
                    'has_feedback' => !empty($feedback),
                    'is_reported' => !empty($report_reason)
                ]
            ];

        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("Question rating error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Değerlendirme kaydedilirken hata oluştu.'];
        }
    }

    /**
     * Get rating statistics for a question
     */
    public function getQuestionRating($data)
    {
        $question_id = (int)($data['question_id'] ?? 0);

        if ($question_id <= 0) {
            return ['success' => false, 'message' => 'Geçersiz soru ID.'];
        }

        try {
            // Get rating statistics
            $stmt = $this->pdo->prepare("
                SELECT
                    COUNT(*) as total_ratings,
                    AVG(rating) as average_rating,
                    SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as rating_5,
                    SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as rating_4,
                    SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as rating_3,
                    SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as rating_2,
                    SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as rating_1,
                    COUNT(CASE WHEN report_reason IS NOT NULL THEN 1 END) as report_count
                FROM question_ratings
                WHERE question_id = ?
            ");
            $stmt->execute([$question_id]);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);

            // Check if current user has rated this question
            $user_rating = null;
            if (isset($_SESSION['user_id'])) {
                $stmt = $this->pdo->prepare("
                    SELECT rating, feedback, report_reason, created_at
                    FROM question_ratings
                    WHERE question_id = ? AND user_id = ?
                ");
                $stmt->execute([$question_id, $_SESSION['user_id']]);
                $user_rating = $stmt->fetch(PDO::FETCH_ASSOC);
            }

            return [
                'success' => true,
                'data' => [
                    'question_id' => $question_id,
                    'total_ratings' => (int)$stats['total_ratings'],
                    'average_rating' => $stats['total_ratings'] > 0 ? round((float)$stats['average_rating'], 2) : 0,
                    'rating_distribution' => [
                        '5' => (int)$stats['rating_5'],
                        '4' => (int)$stats['rating_4'],
                        '3' => (int)$stats['rating_3'],
                        '2' => (int)$stats['rating_2'],
                        '1' => (int)$stats['rating_1']
                    ],
                    'report_count' => (int)$stats['report_count'],
                    'user_rating' => $user_rating
                ]
            ];

        } catch (PDOException $e) {
            error_log("Get question rating error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Değerlendirme bilgileri alınırken hata oluştu.'];
        }
    }

    /**
     * Report a question (shorthand for rating with report reason)
     */
    public function reportQuestion($data)
    {
        // Authentication check
        if (!isset($_SESSION['user_id'])) {
            return ['success' => false, 'message' => 'Bu işlem için giriş yapmalısınız.'];
        }

        $question_id = (int)($data['question_id'] ?? 0);
        $report_reason = $data['report_reason'] ?? '';
        $feedback = trim($data['feedback'] ?? '');
        $user_id = $_SESSION['user_id'];

        // Validation
        if ($question_id <= 0) {
            return ['success' => false, 'message' => 'Geçersiz soru ID.'];
        }

        $valid_reasons = ['hata', 'belirsiz', 'kalitesiz', 'tekrar', 'diger'];
        if (!in_array($report_reason, $valid_reasons)) {
            return ['success' => false, 'message' => 'Geçersiz rapor sebebi.'];
        }

        // Check if question exists
        $stmt = $this->pdo->prepare("SELECT id FROM questions WHERE id = ?");
        $stmt->execute([$question_id]);
        if (!$stmt->fetch()) {
            return ['success' => false, 'message' => 'Soru bulunamadı.'];
        }

        // Check if user already rated/reported this question
        $stmt = $this->pdo->prepare("SELECT id FROM question_ratings WHERE question_id = ? AND user_id = ?");
        $stmt->execute([$question_id, $user_id]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Bu soruyu zaten değerlendirdiniz.'];
        }

        try {
            $this->pdo->beginTransaction();

            // Insert report (with rating 1 since it's a negative report)
            $stmt = $this->pdo->prepare("
                INSERT INTO question_ratings (question_id, user_id, rating, feedback, report_reason)
                VALUES (?, ?, 1, ?, ?)
            ");
            $stmt->execute([$question_id, $user_id, $feedback, $report_reason]);

            $this->pdo->commit();

            return [
                'success' => true,
                'message' => 'Sorunuzu bildirdiğiniz için teşekkürler. İncelemeye alınacaktır.',
                'data' => [
                    'report_id' => $this->pdo->lastInsertId(),
                    'report_reason' => $report_reason
                ]
            ];

        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("Question report error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Rapor gönderilirken hata oluştu.'];
        }
    }

    /**
     * Get user's rating history
     */
    public function getUserRatingHistory($data = [])
    {
        // Authentication check
        if (!isset($_SESSION['user_id'])) {
            return ['success' => false, 'message' => 'Bu işlem için giriş yapmalısınız.'];
        }

        $user_id = $_SESSION['user_id'];
        $limit = min((int)($data['limit'] ?? 20), 100); // Max 100
        $offset = max((int)($data['offset'] ?? 0), 0);

        try {
            $stmt = $this->pdo->prepare("
                SELECT
                    qr.id,
                    qr.question_id,
                    qr.rating,
                    qr.feedback,
                    qr.report_reason,
                    qr.created_at,
                    q.question_text,
                    q.category,
                    q.difficulty
                FROM question_ratings qr
                JOIN questions q ON qr.question_id = q.id
                WHERE qr.user_id = ?
                ORDER BY qr.created_at DESC
                LIMIT $limit OFFSET $offset
            ");
            $stmt->execute([$user_id]);
            $ratings = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get total count
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM question_ratings WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $total_count = $stmt->fetchColumn();

            return [
                'success' => true,
                'data' => [
                    'ratings' => $ratings,
                    'total_count' => (int)$total_count,
                    'limit' => $limit,
                    'offset' => $offset,
                    'has_more' => ($offset + $limit) < $total_count
                ]
            ];

        } catch (PDOException $e) {
            error_log("Get user rating history error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Değerlendirme geçmişi alınırken hata oluştu.'];
        }
    }
}