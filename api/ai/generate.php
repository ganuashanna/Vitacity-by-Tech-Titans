<?php
/**
 * VITACITY - QR Code Generation API
 * POST /api/qr/generate.php - Generate QR code for vehicle
 * GET /api/qr/scan.php?code=xxx - Get vehicle details from QR
 */

require_once __DIR__ . '/../../lib/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// GENERATE QR CODE FOR VEHICLE
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = getJSONInput();
    
    $userId = $input['user_id'] ?? null;
    $vehicleId = $input['vehicle_id'] ?? null;
    $licensePlate = $input['license_plate'] ?? null;
    
    if (!$userId || !$licensePlate) {
        sendJSON(['error' => 'user_id and license_plate required'], 400);
    }
    
    try {
        // Generate unique QR code
        $qrCode = 'VTC-QR-' . strtoupper(substr(md5($licensePlate . time()), 0, 10));
        
        if ($vehicleId) {
            // Update existing vehicle
            Database::execute(
                "UPDATE vehicles SET qr_code = :qr WHERE id = :id",
                ['qr' => $qrCode, 'id' => $vehicleId]
            );
        } else {
            // Check if vehicle already exists
            $existing = Database::fetchOne(
                "SELECT id FROM vehicles WHERE license_plate = :plate",
                ['plate' => $licensePlate]
            );
            
            if ($existing) {
                sendJSON(['error' => 'Vehicle already registered'], 400);
            }
            
            // Create new vehicle
            Database::execute("
                INSERT INTO vehicles (
                    user_id, license_plate, vehicle_type, qr_code
                ) VALUES (
                    :user_id, :plate, :type, :qr
                )
            ", [
                'user_id' => $userId,
                'plate' => $licensePlate,
                'type' => $input['vehicle_type'] ?? 'Two-Wheeler',
                'qr' => $qrCode
            ]);
        }
        
        // Generate QR code image URL (using Google Charts API as fallback)
        $qrImageUrl = "https://api.qrserver.com/v1/create-qr-code/?" . http_build_query([
            'size' => '300x300',
            'data' => $qrCode,
            'format' => 'png'
        ]);
        
        sendJSON([
            'success' => true,
            'qr_code' => $qrCode,
            'qr_image_url' => $qrImageUrl,
            'license_plate' => $licensePlate,
            'message' => 'QR code generated successfully',
            'download_url' => $qrImageUrl
        ]);
        
    } catch (Exception $e) {
        sendJSON(['error' => 'QR generation failed: ' . $e->getMessage()], 500);
    }
}

// SCAN QR CODE (GET VEHICLE DETAILS)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $qrCode = $_GET['code'] ?? $_GET['qr_code'] ?? null;
    
    if (!$qrCode) {
        sendJSON(['error' => 'QR code parameter required'], 400);
    }
    
    try {
        // Get vehicle details
        $vehicle = Database::fetchOne("
            SELECT 
                v.id, v.license_plate, v.vehicle_type, v.model, v.color,
                v.qr_code, v.total_trips, v.status,
                u.id as user_id, u.full_name, u.phone, u.email,
                u.reward_points
            FROM vehicles v
            JOIN users u ON v.user_id = u.id
            WHERE v.qr_code = :qr
        ", ['qr' => $qrCode]);
        
        if (!$vehicle) {
            sendJSON(['error' => 'Vehicle not found', 'qr_code' => $qrCode], 404);
        }
        
        // Get active booking if any
        $activeBooking = Database::fetchOne("
            SELECT 
                b.id, b.booking_code, b.zone_id, b.slot_id,
                b.scheduled_start, b.scheduled_end, b.status,
                z.name as zone_name, ps.slot_number
            FROM bookings b
            LEFT JOIN zones z ON b.zone_id = z.id
            LEFT JOIN parking_slots ps ON b.slot_id = ps.id
            WHERE b.vehicle_id = :vehicle_id
            AND b.status IN ('active', 'pending')
            ORDER BY b.created_at DESC
            LIMIT 1
        ", ['vehicle_id' => $vehicle['id']]);
        
        // Get violation/complaint history
        $violations = Database::fetchAll("
            SELECT 
                c.id, c.complaint_code, c.type, c.title, c.created_at, c.status
            FROM complaints c
            JOIN bookings b ON c.zone_id = b.zone_id
            WHERE b.vehicle_id = :vehicle_id
            AND c.type LIKE '%Wrong Parking%'
            ORDER BY c.created_at DESC
            LIMIT 5
        ", ['vehicle_id' => $vehicle['id']]);
        
        sendJSON([
            'success' => true,
            'vehicle' => [
                'id' => $vehicle['id'],
                'license_plate' => $vehicle['license_plate'],
                'vehicle_type' => $vehicle['vehicle_type'],
                'model' => $vehicle['model'],
                'color' => $vehicle['color'],
                'qr_code' => $vehicle['qr_code'],
                'total_trips' => intval($vehicle['total_trips']),
                'status' => $vehicle['status']
            ],
            'owner' => [
                'user_id' => $vehicle['user_id'],
                'name' => $vehicle['full_name'],
                'phone' => $vehicle['phone'],
                'email' => $vehicle['email'],
                'reward_points' => intval($vehicle['reward_points'])
            ],
            'active_booking' => $activeBooking ? [
                'booking_code' => $activeBooking['booking_code'],
                'zone' => $activeBooking['zone_name'],
                'slot' => $activeBooking['slot_number'],
                'start' => $activeBooking['scheduled_start'],
                'end' => $activeBooking['scheduled_end'],
                'status' => $activeBooking['status']
            ] : null,
            'violations' => array_map(function($v) {
                return [
                    'code' => $v['complaint_code'],
                    'type' => $v['type'],
                    'title' => $v['title'],
                    'date' => $v['created_at'],
                    'status' => $v['status']
                ];
            }, $violations),
            'violation_count' => count($violations)
        ]);
        
    } catch (Exception $e) {
        sendJSON(['error' => 'QR scan failed: ' . $e->getMessage()], 500);
    }
}
?>
