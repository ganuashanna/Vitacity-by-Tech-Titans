<?php
/**
 * VITACITY - Bookings API
 * POST /api/bookings.php - Create booking
 * GET /api/bookings.php - Get bookings (with filters)
 */

require_once __DIR__ . '/../lib/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// CREATE BOOKING
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = getJSONInput();
    
    $userId = $input['user_id'] ?? null;
    $vehicleId = $input['vehicle_id'] ?? null;
    $zoneId = $input['zone_id'] ?? null;
    $slotId = $input['slot_id'] ?? null;
    $hours = $input['hours'] ?? 1;
    
    if (!$userId || !$vehicleId || !$zoneId || !$slotId) {
        sendJSON(['error' => 'Missing required fields'], 400);
    }
    
    try {
        // Check slot availability
        $slot = Database::fetchOne(
            "SELECT state FROM parking_slots WHERE id = :slot_id",
            ['slot_id' => $slotId]
        );
        
        if (!$slot || $slot['state'] !== 'available') {
            sendJSON(['error' => 'Slot not available'], 400);
        }
        
        // Get zone price
        $zone = Database::fetchOne(
            "SELECT dynamic_price_per_hour, base_price_per_hour FROM zones WHERE id = :zone_id",
            ['zone_id' => $zoneId]
        );
        
        $price = $zone['dynamic_price_per_hour'] ?? $zone['base_price_per_hour'];
        
        // Generate booking code
        $bookingCode = 'VTC-' . date('YmdHis') . rand(100, 999);
        
        // Create booking
        Database::execute("
            INSERT INTO bookings (
                booking_code, user_id, vehicle_id, zone_id, slot_id,
                scheduled_start, scheduled_end, hourly_rate, status
            ) VALUES (
                :code, :user_id, :vehicle_id, :zone_id, :slot_id,
                NOW(), NOW() + INTERVAL '1 hour' * :hours, :rate, 'pending'
            )
        ", [
            'code' => $bookingCode,
            'user_id' => $userId,
            'vehicle_id' => $vehicleId,
            'zone_id' => $zoneId,
            'slot_id' => $slotId,
            'hours' => $hours,
            'rate' => $price
        ]);
        
        // Update slot state
        Database::execute(
            "UPDATE parking_slots SET state = 'reserved' WHERE id = :slot_id",
            ['slot_id' => $slotId]
        );
        
        // Award reward points
        Database::execute(
            "UPDATE users SET reward_points = reward_points + 10 WHERE id = :user_id",
            ['user_id' => $userId]
        );
        
        sendJSON([
            'success' => true,
            'booking_code' => $bookingCode,
            'status' => 'confirmed',
            'reward_points_earned' => 10,
            'total_cost' => $price * $hours
        ]);
        
    } catch (Exception $e) {
        sendJSON(['error' => 'Booking failed: ' . $e->getMessage()], 500);
    }
}

// GET BOOKINGS
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $userId = $_GET['user_id'] ?? null;
    $status = $_GET['status'] ?? null;
    $limit = $_GET['limit'] ?? 50;
    
    $sql = "
        SELECT 
            b.id, b.booking_code, b.zone_id, b.slot_id,
            b.scheduled_start, b.scheduled_end, b.status,
            b.hourly_rate, b.total_cost,
            v.license_plate, z.name as zone_name
        FROM bookings b
        LEFT JOIN vehicles v ON b.vehicle_id = v.id
        LEFT JOIN zones z ON b.zone_id = z.id
        WHERE 1=1
    ";
    
    $params = [];
    
    if ($userId) {
        $params['user_id'] = $userId;
        $sql .= " AND b.user_id = :user_id";
    }
    
    if ($status) {
        $params['status'] = $status;
        $sql .= " AND b.status = :status";
    }
    
    $sql .= " ORDER BY b.created_at DESC LIMIT " . intval($limit);
    
    $bookings = Database::fetchAll($sql, $params);
    
    sendJSON([
        'bookings' => array_map(function($b) {
            return [
                'id' => $b['id'],
                'code' => $b['booking_code'],
                'zone_id' => $b['zone_id'],
                'zone_name' => $b['zone_name'],
                'slot_id' => $b['slot_id'],
                'start' => $b['scheduled_start'],
                'end' => $b['scheduled_end'],
                'status' => $b['status'],
                'rate' => floatval($b['hourly_rate']),
                'total_cost' => $b['total_cost'] ? floatval($b['total_cost']) : null,
                'vehicle' => $b['license_plate']
            ];
        }, $bookings),
        'total' => count($bookings)
    ]);
}
?>