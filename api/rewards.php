<?php
/**
 * VITACITY - Rewards API
 * GET /api/rewards.php?user_id=xxx - Get user rewards
 * POST /api/rewards.php - Redeem rewards
 */

require_once __DIR__ . '/../lib/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// GET USER REWARDS
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $userId = $_GET['user_id'] ?? null;
    
    if (!$userId) {
        sendJSON(['error' => 'user_id parameter required'], 400);
    }
    
    try {
        // Get user reward info
        $user = Database::fetchOne(
            "SELECT id, full_name, reward_points, tier FROM users WHERE id = :user_id",
            ['user_id' => $userId]
        );
        
        if (!$user) {
            sendJSON(['error' => 'User not found'], 404);
        }
        
        // Get reward history from bookings
        $bookingHistory = Database::fetchAll("
            SELECT 
                'booking' as type,
                booking_code as code,
                10 as points,
                created_at
            FROM bookings
            WHERE user_id = :user_id
            AND status IN ('completed', 'active')
            ORDER BY created_at DESC
            LIMIT 10
        ", ['user_id' => $userId]);
        
        // Get reward history from complaints
        $complaintHistory = Database::fetchAll("
            SELECT 
                'complaint' as type,
                complaint_code as code,
                5 as points,
                created_at
            FROM complaints
            WHERE user_id = :user_id
            AND status IN ('resolved', 'in_progress')
            ORDER BY created_at DESC
            LIMIT 10
        ", ['user_id' => $userId]);
        
        // Merge and sort history
        $history = array_merge($bookingHistory, $complaintHistory);
        usort($history, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });
        $history = array_slice($history, 0, 20);
        
        // Calculate tier benefits
        $tierBenefits = [
            'Bronze' => ['discount' => 0, 'priority_booking' => false],
            'Silver' => ['discount' => 5, 'priority_booking' => false],
            'Gold' => ['discount' => 10, 'priority_booking' => true],
            'Platinum' => ['discount' => 15, 'priority_booking' => true]
        ];
        
        sendJSON([
            'user_id' => $user['id'],
            'user_name' => $user['full_name'],
            'total_points' => intval($user['reward_points']),
            'tier' => $user['tier'],
            'tier_benefits' => $tierBenefits[$user['tier']] ?? $tierBenefits['Bronze'],
            'history' => array_map(function($h) {
                return [
                    'type' => $h['type'],
                    'code' => $h['code'],
                    'points' => intval($h['points']),
                    'date' => $h['created_at']
                ];
            }, $history),
            'redemption_options' => [
                ['points' => 100, 'benefit' => 'Free 2-hour parking'],
                ['points' => 250, 'benefit' => '10% discount for 1 week'],
                ['points' => 500, 'benefit' => 'Upgrade to next tier'],
                ['points' => 1000, 'benefit' => 'Free parking for 1 month']
            ]
        ]);
        
    } catch (Exception $e) {
        sendJSON(['error' => 'Failed to fetch rewards: ' . $e->getMessage()], 500);
    }
}

// REDEEM REWARDS
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = getJSONInput();
    
    $userId = $input['user_id'] ?? null;
    $pointsToRedeem = $input['points_to_redeem'] ?? 0;
    $redemptionType = $input['redemption_type'] ?? 'discount';
    
    if (!$userId || $pointsToRedeem <= 0) {
        sendJSON(['error' => 'Invalid redemption request'], 400);
    }
    
    try {
        // Get user current points
        $user = Database::fetchOne(
            "SELECT reward_points FROM users WHERE id = :user_id",
            ['user_id' => $userId]
        );
        
        if (!$user) {
            sendJSON(['error' => 'User not found'], 404);
        }
        
        if ($user['reward_points'] < $pointsToRedeem) {
            sendJSON(['error' => 'Insufficient points'], 400);
        }
        
        // Deduct points
        Database::execute(
            "UPDATE users SET reward_points = reward_points - :points WHERE id = :user_id",
            ['points' => $pointsToRedeem, 'user_id' => $userId]
        );
        
        $newBalance = $user['reward_points'] - $pointsToRedeem;
        
        // Log redemption (you can create a redemptions table if needed)
        
        sendJSON([
            'success' => true,
            'points_redeemed' => $pointsToRedeem,
            'remaining_points' => $newBalance,
            'redemption_type' => $redemptionType,
            'message' => 'Rewards redeemed successfully!'
        ]);
        
    } catch (Exception $e) {
        sendJSON(['error' => 'Redemption failed: ' . $e->getMessage()], 500);
    }
}
?>