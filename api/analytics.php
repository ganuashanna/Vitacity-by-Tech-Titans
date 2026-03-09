<?php
/**
 * VITACITY - Analytics API
 * GET /api/analytics.php - Get dashboard analytics
 */

require_once __DIR__ . '/../lib/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    // Get dashboard metrics
    $metrics = Database::fetchOne("
        SELECT 
            (SELECT COUNT(*) FROM bookings WHERE DATE(created_at) = CURRENT_DATE) as today_bookings,
            (SELECT SUM(total_cost) FROM bookings WHERE DATE(actual_end) = CURRENT_DATE) as today_revenue,
            (SELECT COUNT(*) FROM complaints WHERE status IN ('open', 'in_progress')) as open_complaints,
            (SELECT COUNT(*) FROM alerts WHERE status = 'active') as active_alerts,
            (SELECT COUNT(*) FROM users) as total_users,
            (SELECT SUM(reward_points) FROM users) as total_reward_points,
            (SELECT COUNT(*) FROM zones) as total_zones,
            (SELECT SUM(total_slots) FROM zones) as total_slots
    ");
    
    // Get zone-wise occupancy
    $zoneStats = Database::fetchAll("
        SELECT 
            z.id, z.name,
            z.total_slots,
            COUNT(CASE WHEN ps.state = 'occupied' THEN 1 END) as occupied,
            COUNT(CASE WHEN ps.state = 'available' THEN 1 END) as available
        FROM zones z
        LEFT JOIN parking_slots ps ON z.id = ps.zone_id
        GROUP BY z.id, z.name, z.total_slots
        ORDER BY z.id
    ");
    
    // Get recent activity
    $recentBookings = Database::fetchAll("
        SELECT 
            b.booking_code, b.created_at,
            z.name as zone_name,
            u.full_name as user_name
        FROM bookings b
        JOIN zones z ON b.zone_id = z.id
        JOIN users u ON b.user_id = u.id
        ORDER BY b.created_at DESC
        LIMIT 10
    ");
    
    $recentComplaints = Database::fetchAll("
        SELECT 
            c.complaint_code, c.type, c.created_at,
            z.name as zone_name
        FROM complaints c
        LEFT JOIN zones z ON c.zone_id = z.id
        ORDER BY c.created_at DESC
        LIMIT 10
    ");
    
    // Get hourly booking trend (last 24 hours)
    $hourlyTrend = Database::fetchAll("
        SELECT 
            EXTRACT(HOUR FROM created_at) as hour,
            COUNT(*) as booking_count
        FROM bookings
        WHERE created_at >= NOW() - INTERVAL '24 hours'
        GROUP BY EXTRACT(HOUR FROM created_at)
        ORDER BY hour
    ");
    
    // Calculate overall occupancy
    $totalSlots = intval($metrics['total_slots']);
    $totalOccupied = array_sum(array_column($zoneStats, 'occupied'));
    $overallOccupancy = $totalSlots > 0 ? round(($totalOccupied / $totalSlots) * 100, 2) : 0;
    
    sendJSON([
        'summary' => [
            'today_bookings' => intval($metrics['today_bookings'] ?? 0),
            'today_revenue' => floatval($metrics['today_revenue'] ?? 0),
            'open_complaints' => intval($metrics['open_complaints'] ?? 0),
            'active_alerts' => intval($metrics['active_alerts'] ?? 0),
            'total_users' => intval($metrics['total_users'] ?? 0),
            'total_reward_points' => intval($metrics['total_reward_points'] ?? 0),
            'total_zones' => intval($metrics['total_zones'] ?? 0),
            'total_slots' => $totalSlots,
            'overall_occupancy' => $overallOccupancy
        ],
        'zone_stats' => array_map(function($z) {
            $occupancyPercent = $z['total_slots'] > 0 
                ? round(($z['occupied'] / $z['total_slots']) * 100, 2) 
                : 0;
            return [
                'zone_id' => $z['id'],
                'zone_name' => $z['name'],
                'total_slots' => intval($z['total_slots']),
                'occupied' => intval($z['occupied']),
                'available' => intval($z['available']),
                'occupancy_percent' => $occupancyPercent
            ];
        }, $zoneStats),
        'recent_activity' => [
            'bookings' => array_map(function($b) {
                return [
                    'code' => $b['booking_code'],
                    'zone' => $b['zone_name'],
                    'user' => $b['user_name'],
                    'time' => $b['created_at']
                ];
            }, $recentBookings),
            'complaints' => array_map(function($c) {
                return [
                    'code' => $c['complaint_code'],
                    'type' => $c['type'],
                    'zone' => $c['zone_name'],
                    'time' => $c['created_at']
                ];
            }, $recentComplaints)
        ],
        'hourly_trend' => array_map(function($h) {
            return [
                'hour' => intval($h['hour']),
                'bookings' => intval($h['booking_count'])
            ];
        }, $hourlyTrend),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    sendJSON(['error' => 'Failed to fetch analytics: ' . $e->getMessage()], 500);
}
?>