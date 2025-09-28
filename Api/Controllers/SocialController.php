<?php

/**
 * Social Features Controller
 * Handles profile visits, sharing, achievement comparisons, and friend shortcuts
 */

class SocialController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Record a profile visit
     */
    public function recordProfileVisit($data) {
        try {
            $visitor_id = $_SESSION['user_id'];
            $visited_username = $data['username'] ?? '';

            if (empty($visited_username)) {
                return ['success' => false, 'message' => 'Kullanıcı adı belirtilmedi'];
            }

            // Get visited user ID
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$visited_username]);
            $visited_user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$visited_user) {
                return ['success' => false, 'message' => 'Kullanıcı bulunamadı'];
            }

            $visited_id = $visited_user['id'];

            // Don't record self-visits
            if ($visitor_id == $visited_id) {
                return ['success' => true, 'message' => 'Kendi profiliniz'];
            }

            // Insert or update visit record
            $stmt = $this->pdo->prepare("
                INSERT INTO profile_visits (visitor_id, visited_id, visit_count, last_visit, first_visit)
                VALUES (?, ?, 1, NOW(), NOW())
                ON DUPLICATE KEY UPDATE
                visit_count = visit_count + 1,
                last_visit = NOW()
            ");

            $stmt->execute([$visitor_id, $visited_id]);

            return ['success' => true, 'message' => 'Ziyaret kaydedildi'];

        } catch (Exception $e) {
            error_log("Profile visit recording error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Ziyaret kaydedilemedi'];
        }
    }

    /**
     * Get profile visiting history for a user
     */
    public function getProfileVisitHistory($data) {
        try {
            $user_id = $_SESSION['user_id'];
            $target_username = $data['username'] ?? '';
            $limit = intval($data['limit'] ?? 20);
            $offset = intval($data['offset'] ?? 0);

            // If username provided, get that user's visit history (if allowed)
            if (!empty($target_username)) {
                $stmt = $this->pdo->prepare("
                    SELECT id, profile_visibility FROM users WHERE username = ?
                ");
                $stmt->execute([$target_username]);
                $target_user = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$target_user) {
                    return ['success' => false, 'message' => 'Kullanıcı bulunamadı'];
                }

                // Check if user can view this profile's visit history
                if ($target_user['id'] != $user_id && $target_user['profile_visibility'] != 'public') {
                    return ['success' => false, 'message' => 'Bu kullanıcının ziyaret geçmişini görme yetkiniz yok'];
                }

                $user_id = $target_user['id'];
            }

            // Get recent visits to this user's profile
            $stmt = $this->pdo->prepare("
                SELECT
                    pv.*,
                    u.username as visitor_username,
                    u.avatar as visitor_avatar
                FROM profile_visits pv
                JOIN users u ON pv.visitor_id = u.id
                WHERE pv.visited_id = ?
                ORDER BY pv.last_visit DESC
                LIMIT " . intval($limit) . " OFFSET " . intval($offset) . "
            ");

            $stmt->execute([$user_id]);
            $visits = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get total count
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as total
                FROM profile_visits
                WHERE visited_id = ?
            ");
            $stmt->execute([$user_id]);
            $total = $stmt->fetchColumn();

            return [
                'success' => true,
                'data' => [
                    'visits' => $visits,
                    'total' => intval($total),
                    'has_more' => ($offset + $limit) < $total
                ]
            ];

        } catch (Exception $e) {
            error_log("Get profile visit history error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Ziyaret geçmişi alınamadı'];
        }
    }

    /**
     * Record profile share action
     */
    public function shareProfile($data) {
        try {
            $sharer_id = $_SESSION['user_id'];
            $shared_username = $data['username'] ?? '';
            $share_method = $data['method'] ?? 'link';

            if (empty($shared_username)) {
                return ['success' => false, 'message' => 'Kullanıcı adı belirtilmedi'];
            }

            // Get shared user ID
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$shared_username]);
            $shared_user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$shared_user) {
                return ['success' => false, 'message' => 'Kullanıcı bulunamadı'];
            }

            // Record share
            $stmt = $this->pdo->prepare("
                INSERT INTO profile_shares (sharer_id, shared_profile_id, share_method, ip_address, user_agent)
                VALUES (?, ?, ?, ?, ?)
            ");

            $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

            $stmt->execute([$sharer_id, $shared_user['id'], $share_method, $ip_address, $user_agent]);

            return [
                'success' => true,
                'message' => 'Profil paylaşımı kaydedildi',
                'share_url' => DOMAIN . 'profile/' . $shared_username
            ];

        } catch (Exception $e) {
            error_log("Profile share recording error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Paylaşım kaydedilemedi'];
        }
    }

    /**
     * Compare achievements between two users
     */
    public function compareAchievements($data) {
        try {
            $user1_id = $_SESSION['user_id'];
            $user2_username = $data['username'] ?? '';

            if (empty($user2_username)) {
                return ['success' => false, 'message' => 'Karşılaştırılacak kullanıcı adı belirtilmedi'];
            }

            // Get user2 ID
            $stmt = $this->pdo->prepare("SELECT id, username FROM users WHERE username = ?");
            $stmt->execute([$user2_username]);
            $user2 = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user2) {
                return ['success' => false, 'message' => 'Kullanıcı bulunamadı'];
            }

            $user2_id = $user2['id'];

            // Get user1 data
            $stmt = $this->pdo->prepare("SELECT username FROM users WHERE id = ?");
            $stmt->execute([$user1_id]);
            $user1 = $stmt->fetch(PDO::FETCH_ASSOC);

            // Get achievements for both users
            $achievements_query = "
                SELECT
                    a.id,
                    a.achievement_name,
                    a.description,
                    a.icon,
                    a.rarity,
                    ua.earned_at,
                    ua.user_id
                FROM achievements a
                LEFT JOIN user_achievements ua ON a.id = ua.achievement_id
                    AND ua.user_id IN (?, ?)
                ORDER BY a.achievement_name
            ";

            $stmt = $this->pdo->prepare($achievements_query);
            $stmt->execute([$user1_id, $user2_id]);
            $all_achievements = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Process achievements comparison
            $comparison_data = [];
            $user1_achievements = [];
            $user2_achievements = [];
            $common_achievements = [];
            $user1_only = [];
            $user2_only = [];

            // Group achievements by ID
            $achievement_groups = [];
            foreach ($all_achievements as $achievement) {
                $ach_id = $achievement['id'];
                if (!isset($achievement_groups[$ach_id])) {
                    $achievement_groups[$ach_id] = [
                        'id' => $ach_id,
                        'achievement_name' => $achievement['achievement_name'],
                        'description' => $achievement['description'],
                        'icon' => $achievement['icon'],
                        'rarity' => $achievement['rarity'],
                        'user1_earned' => null,
                        'user2_earned' => null
                    ];
                }

                if ($achievement['user_id'] == $user1_id && $achievement['earned_at']) {
                    $achievement_groups[$ach_id]['user1_earned'] = $achievement['earned_at'];
                    $user1_achievements[] = $ach_id;
                } elseif ($achievement['user_id'] == $user2_id && $achievement['earned_at']) {
                    $achievement_groups[$ach_id]['user2_earned'] = $achievement['earned_at'];
                    $user2_achievements[] = $ach_id;
                }
            }

            // Calculate comparison stats
            foreach ($achievement_groups as $ach_id => $achievement) {
                $user1_has = !is_null($achievement['user1_earned']);
                $user2_has = !is_null($achievement['user2_earned']);

                if ($user1_has && $user2_has) {
                    $common_achievements[] = $achievement;
                } elseif ($user1_has && !$user2_has) {
                    $user1_only[] = $achievement;
                } elseif (!$user1_has && $user2_has) {
                    $user2_only[] = $achievement;
                }
            }

            $comparison_result = [
                'user1' => [
                    'username' => $user1['username'],
                    'total_achievements' => count($user1_achievements),
                    'unique_achievements' => count($user1_only)
                ],
                'user2' => [
                    'username' => $user2['username'],
                    'total_achievements' => count($user2_achievements),
                    'unique_achievements' => count($user2_only)
                ],
                'common_achievements' => $common_achievements,
                'user1_only_achievements' => $user1_only,
                'user2_only_achievements' => $user2_only,
                'comparison_stats' => [
                    'common_count' => count($common_achievements),
                    'user1_advantage' => count($user1_only),
                    'user2_advantage' => count($user2_only),
                    'total_possible' => count($achievement_groups)
                ]
            ];

            // Save comparison to database
            $stmt = $this->pdo->prepare("
                INSERT INTO achievement_comparisons (user1_id, user2_id, comparison_data)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$user1_id, $user2_id, json_encode($comparison_result)]);

            return [
                'success' => true,
                'data' => $comparison_result
            ];

        } catch (Exception $e) {
            error_log("Achievement comparison error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Başarım karşılaştırması yapılamadı'];
        }
    }

    /**
     * Get friend profile shortcuts
     */
    public function getFriendShortcuts($data) {
        try {
            $user_id = $_SESSION['user_id'];

            // Get bookmarked friends
            $stmt = $this->pdo->prepare("
                SELECT
                    pb.*,
                    u.username,
                    u.avatar,
                    ls.total_score,
                    (SELECT COUNT(*) FROM user_achievements ua WHERE ua.user_id = u.id) as achievement_count
                FROM profile_bookmarks pb
                JOIN users u ON pb.bookmarked_user_id = u.id
                LEFT JOIN leaderboard ls ON u.id = ls.user_id
                WHERE pb.user_id = ?
                ORDER BY pb.created_at DESC
            ");

            $stmt->execute([$user_id]);
            $bookmarks = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get recent friends (from friends table)
            $stmt = $this->pdo->prepare("
                SELECT
                    u.id,
                    u.username,
                    u.avatar,
                    ls.total_score,
                    (SELECT COUNT(*) FROM user_achievements ua WHERE ua.user_id = u.id) as achievement_count,
                    f.created_at as friendship_date
                FROM friends f
                JOIN users u ON (
                    CASE
                        WHEN f.user1_id = ? THEN f.user2_id
                        ELSE f.user1_id
                    END = u.id
                )
                LEFT JOIN leaderboard ls ON u.id = ls.user_id
                WHERE (f.user1_id = ? OR f.user2_id = ?)
                    AND f.status = 'accepted'
                ORDER BY f.created_at DESC
                LIMIT 10
            ");

            $stmt->execute([$user_id, $user_id, $user_id]);
            $recent_friends = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'success' => true,
                'data' => [
                    'bookmarks' => $bookmarks,
                    'recent_friends' => $recent_friends
                ]
            ];

        } catch (Exception $e) {
            error_log("Get friend shortcuts error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Arkadaş kısayolları alınamadı'];
        }
    }

    /**
     * Add profile bookmark
     */
    public function addProfileBookmark($data) {
        try {
            $user_id = $_SESSION['user_id'];
            $bookmarked_username = $data['username'] ?? '';
            $bookmark_name = $data['name'] ?? '';

            if (empty($bookmarked_username)) {
                return ['success' => false, 'message' => 'Kullanıcı adı belirtilmedi'];
            }

            // Get bookmarked user ID
            $stmt = $this->pdo->prepare("SELECT id, username FROM users WHERE username = ?");
            $stmt->execute([$bookmarked_username]);
            $bookmarked_user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$bookmarked_user) {
                return ['success' => false, 'message' => 'Kullanıcı bulunamadı'];
            }

            // Can't bookmark yourself
            if ($user_id == $bookmarked_user['id']) {
                return ['success' => false, 'message' => 'Kendi profilinizi işaretleyemezsiniz'];
            }

            // Use username as default bookmark name
            if (empty($bookmark_name)) {
                $bookmark_name = $bookmarked_user['username'];
            }

            // Insert bookmark
            $stmt = $this->pdo->prepare("
                INSERT INTO profile_bookmarks (user_id, bookmarked_user_id, bookmark_name)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE bookmark_name = VALUES(bookmark_name)
            ");

            $stmt->execute([$user_id, $bookmarked_user['id'], $bookmark_name]);

            return [
                'success' => true,
                'message' => 'Profil işaretlendi'
            ];

        } catch (Exception $e) {
            error_log("Add profile bookmark error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Profil işaretlenemedi'];
        }
    }

    /**
     * Remove profile bookmark
     */
    public function removeProfileBookmark($data) {
        try {
            $user_id = $_SESSION['user_id'];
            $bookmarked_username = $data['username'] ?? '';

            if (empty($bookmarked_username)) {
                return ['success' => false, 'message' => 'Kullanıcı adı belirtilmedi'];
            }

            // Get bookmarked user ID
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$bookmarked_username]);
            $bookmarked_user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$bookmarked_user) {
                return ['success' => false, 'message' => 'Kullanıcı bulunamadı'];
            }

            // Remove bookmark
            $stmt = $this->pdo->prepare("
                DELETE FROM profile_bookmarks
                WHERE user_id = ? AND bookmarked_user_id = ?
            ");

            $stmt->execute([$user_id, $bookmarked_user['id']]);

            return [
                'success' => true,
                'message' => 'İşaret kaldırıldı'
            ];

        } catch (Exception $e) {
            error_log("Remove profile bookmark error: " . $e->getMessage());
            return ['success' => false, 'message' => 'İşaret kaldırılamadı'];
        }
    }
}

?>