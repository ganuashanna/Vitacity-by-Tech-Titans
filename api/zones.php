<?php
/**
 * VITACITY - Zones API (FIXED)
 * GET /api/zones.php - Get all zones with real-time occupancy
 * GET /api/zones.php?id=Z1 - Get specific zone with slots
 */

require_once __DIR__ . '/../lib/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$zoneId = $_GET['id'] ?? null;

try {
    if ($zoneId) {
        // Get specific zone with all slots
        $zone = Database::fetchOne("
            SELECT * FROM zones WHERE id = :id
        ", ['id' => $zoneId]);
        
        if (!$zone) {
            sendJSON(['error' => 'Zone not found'], 404);
        }
        
        // Get all slots for this zone
        $slots = Database::fetchAll("
            SELECT 
                id, slot_number, slot_type, state, sensor_status,
                qr_code, last_occupied_at
            FROM parking_slots
            WHERE zone_id = :zone_id
            ORDER BY slot_number
        ", ['zone_id' => $zoneId]);
        
        // Calculate occupancy
        $occupied = count(array_filter($slots, fn($s) => $s['state'] === 'occupied'));
        $available = count(array_filter($slots, fn($s) => $s['state'] === 'available'));
        
        sendJSON([
            'zone' => [
                'id' => $zone['id'],
                'name' => $zone['name'],
                'latitude' => floatval($zone['latitude']),
                'longitude' => floatval($zone['longitude']),
                'total_slots' => intval($zone['total_slots']),
                'occupied' => $occupied,
                'available' => $available,
                'base_price' => floatval($zone['base_price_per_hour']),
                'dynamic_price' => floatval($zone['dynamic_price_per_hour'] ?? $zone['base_price_per_hour']),
                'congestion_level' => $zone['congestion_level'],
                'status' => $zone['status']
            ],
            'slots' => array_map(function($slot) {
                return [
                    'id' => $slot['id'],
                    'slot_number' => intval($slot['slot_number']),
                    'type' => $slot['slot_type'],
                    'state' => $slot['state'],
                    'sensor_status' => (bool)$slot['sensor_status'],
                    'qr_code' => $slot['qr_code']
                ];
            }, $slots)
        ]);
        
    } else {
        // Get all zones with occupancy stats
        $zones = Database::fetchAll("
            SELECT 
                z.id,
                z.name,
                z.latitude,
                z.longitude,
                z.total_slots,
                z.base_price_per_hour,
                z.dynamic_price_per_hour,
                z.congestion_level,
                z.rating,
                z.status,
                COUNT(CASE WHEN ps.state = 'occupied' THEN 1 END) as occupied_count,
                COUNT(CASE WHEN ps.state = 'available' THEN 1 END) as available_count,
                COUNT(CASE WHEN ps.state = 'reserved' THEN 1 END) as reserved_count
            FROM zones z
            LEFT JOIN parking_slots ps ON z.id = ps.zone_id
            GROUP BY z.id, z.name, z.latitude, z.longitude, z.total_slots, 
                     z.base_price_per_hour, z.dynamic_price_per_hour, 
                     z.congestion_level, z.rating, z.status
            ORDER BY z.id
        ");
        
        if (empty($zones)) {
            sendJSON([
                'error' => 'No zones found',
                'message' => 'Please import seed.sql in Supabase',
                'zones' => []
            ]);
        }
        
        sendJSON([
            'zones' => array_map(function($zone) {
                $total = intval($zone['total_slots']);
                $occupied = intval($zone['occupied_count']);
                $available = intval($zone['available_count']);
                $occupancy = $total > 0 ? round(($occupied / $total) * 100, 2) : 0;
                
                return [
                    'id' => $zone['id'],
                    'name' => $zone['name'],
                    'latitude' => floatval($zone['latitude']),
                    'longitude' => floatval($zone['longitude']),
                    'total' => $total,
                    'occupied' => $occupied,
                    'available' => $available,
                    'reserved' => intval($zone['reserved_count']),
                    'occupancy_percent' => $occupancy,
                    'price' => floatval($zone['dynamic_price_per_hour'] ?? $zone['base_price_per_hour']),
                    'base_price' => floatval($zone['base_price_per_hour']),
                    'congestion' => $zone['congestion_level'],
                    'rating' => floatval($zone['rating']),
                    'status' => $zone['status']
                ];
            }, $zones),
            'total_zones' => count($zones),
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
    
} catch (Exception $e) {
    error_log('Zones API Error: ' . $e->getMessage());
    sendJSON([
        'error' => 'Failed to fetch zones',
        'message' => $e->getMessage(),
        'zones' => []
    ], 500);
}
?>