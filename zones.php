<?php
/**
 * VITACITY - Zones API
 * GET /api/zones - Get all parking zones
 * GET /api/zones/{id} - Get specific zone
 */

require_once __DIR__ . '/../lib/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Get zone ID from query parameter or path
$zoneId = $_GET['id'] ?? null;

if ($zoneId) {
    // Get specific zone with slots
    $zoneSql = "
        SELECT 
            z.id, z.name, z.latitude, z.longitude, z.total_slots,
            z.base_price_per_hour, z.dynamic_price_per_hour,
            z.congestion_level, z.rating,
            COUNT(CASE WHEN ps.state = 'available' THEN 1 END) as available,
            COUNT(CASE WHEN ps.state = 'occupied' THEN 1 END) as occupied,
            COUNT(CASE WHEN ps.state = 'reserved' THEN 1 END) as reserved
        FROM zones z
        LEFT JOIN parking_slots ps ON z.id = ps.zone_id
        WHERE z.id = :zone_id
        GROUP BY z.id
    ";
    
    $zone = Database::fetchOne($zoneSql, ['zone_id' => $zoneId]);
    
    if (!$zone) {
        sendJSON(['error' => 'Zone not found'], 404);
    }
    
    // Get slots for this zone
    $slotsSql = "
        SELECT id, slot_number, slot_type, state, sensor_status, qr_code
        FROM parking_slots
        WHERE zone_id = :zone_id
        ORDER BY slot_number
    ";
    
    $slots = Database::fetchAll($slotsSql, ['zone_id' => $zoneId]);
    
    $occupancyPercent = $zone['total_slots'] > 0 
        ? round(($zone['occupied'] / $zone['total_slots']) * 100, 2) 
        : 0;
    
    sendJSON([
        'zone' => [
            'id' => $zone['id'],
            'name' => $zone['name'],
            'lat' => (float)$zone['latitude'],
            'lng' => (float)$zone['longitude'],
            'total' => $zone['total_slots'],
            'price' => (float)($zone['dynamic_price_per_hour'] ?? $zone['base_price_per_hour']),
            'rating' => (float)$zone['rating'],
            'congestion' => $zone['congestion_level'],
            'available' => $zone['available'],
            'occupied' => $zone['occupied'],
            'reserved' => $zone['reserved'],
            'occupancy_percent' => $occupancyPercent,
            'slots' => array_map(function($slot) {
                return [
                    'id' => $slot['id'],
                    'number' => $slot['slot_number'],
                    'type' => $slot['slot_type'],
                    'state' => $slot['state'],
                    'sensor' => (bool)$slot['sensor_status'],
                    'qr' => $slot['qr_code']
                ];
            }, $slots)
        ]
    ]);
    
} else {
    // Get all zones
    $sql = "
        SELECT 
            z.id, z.name, z.latitude, z.longitude, z.total_slots,
            z.base_price_per_hour, z.dynamic_price_per_hour,
            z.congestion_level, z.rating,
            COUNT(CASE WHEN ps.state = 'available' THEN 1 END) as available,
            COUNT(CASE WHEN ps.state = 'occupied' THEN 1 END) as occupied,
            COUNT(CASE WHEN ps.state = 'reserved' THEN 1 END) as reserved
        FROM zones z
        LEFT JOIN parking_slots ps ON z.id = ps.zone_id
        GROUP BY z.id
        ORDER BY z.id
    ";
    
    $zones = Database::fetchAll($sql);
    
    $result = array_map(function($zone) {
        $occupancyPercent = $zone['total_slots'] > 0 
            ? round(($zone['occupied'] / $zone['total_slots']) * 100, 2) 
            : 0;
            
        return [
            'id' => $zone['id'],
            'name' => $zone['name'],
            'lat' => (float)$zone['latitude'],
            'lng' => (float)$zone['longitude'],
            'total' => $zone['total_slots'],
            'price' => (float)($zone['dynamic_price_per_hour'] ?? $zone['base_price_per_hour']),
            'rating' => (float)$zone['rating'],
            'congestion' => $zone['congestion_level'],
            'available' => $zone['available'],
            'occupied' => $zone['occupied'],
            'reserved' => $zone['reserved'],
            'occupancy_percent' => $occupancyPercent
        ];
    }, $zones);
    
    sendJSON([
        'zones' => $result,
        'total_zones' => count($result),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>