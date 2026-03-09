<?php
/**
 * VITACITY - SOS/Emergency API
 * POST /api/sos.php - Create SOS alert
 * GET /api/sos.php - Get SOS alerts
 */

require_once __DIR__ . '/../lib/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// CREATE SOS ALERT
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = getJSONInput();
    
    $userId = $input['user_id'] ?? null;
    $vehicleId = $input['vehicle_id'] ?? null;
    $lat = $input['latitude'] ?? null;
    $lng = $input['longitude'] ?? null;
    $emergencyType = $input['emergency_type'] ?? 'General Emergency';
    $message = $input['message'] ?? '';
    
    if (!$userId || !$vehicleId || !$lat || !$lng) {
        sendJSON(['error' => 'Missing required fields'], 400);
    }
    
    try {
        // Get vehicle and user details
        $details = Database::fetchOne("
            SELECT 
                v.license_plate, v.qr_code,
                u.full_name, u.phone
            FROM vehicles v
            JOIN users u ON v.user_id = u.id
            WHERE v.id = :vehicle_id
        ", ['vehicle_id' => $vehicleId]);
        
        if (!$details) {
            sendJSON(['error' => 'Vehicle not found'], 404);
        }
        
        // Generate SOS code
        $sosCode = 'SOS-' . date('YmdHis') . rand(100, 999);
        
        // Create SOS alert in system
        Database::execute("
            INSERT INTO alerts (
                alert_type, severity, title, message, status
            ) VALUES (
                'emergency', 'critical', :title, :message, 'active'
            )
        ", [
            'title' => "SOS: {$emergencyType}",
            'message' => "Emergency from {$details['full_name']} ({$details['license_plate']}) at coordinates: {$lat}, {$lng}. Message: {$message}"
        ]);
        
        sendJSON([
            'success' => true,
            'alert_code' => $sosCode,
            'status' => 'dispatched',
            'emergency_type' => $emergencyType,
            'location' => [
                'latitude' => floatval($lat),
                'longitude' => floatval($lng)
            ],
            'vehicle' => $details['license_plate'],
            'contact' => [
                'name' => $details['full_name'],
                'phone' => $details['phone']
            ],
            'message' => 'Emergency services have been notified. Help is on the way!'
        ]);
        
    } catch (Exception $e) {
        sendJSON(['error' => 'SOS alert failed: ' . $e->getMessage()], 500);
    }
}

// GET SOS ALERTS
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $status = $_GET['status'] ?? null;
    $limit = $_GET['limit'] ?? 20;
    
    $sql = "
        SELECT 
            id, alert_type, severity, title, message, status, created_at
        FROM alerts
        WHERE alert_type = 'emergency'
    ";
    
    $params = [];
    
    if ($status) {
        $params['status'] = $status;
        $sql .= " AND status = :status";
    }
    
    $sql .= " ORDER BY created_at DESC LIMIT " . intval($limit);
    
    $alerts = Database::fetchAll($sql, $params);
    
    sendJSON([
        'sos_alerts' => array_map(function($a) {
            return [
                'id' => $a['id'],
                'type' => $a['alert_type'],
                'severity' => $a['severity'],
                'title' => $a['title'],
                'message' => $a['message'],
                'status' => $a['status'],
                'created_at' => $a['created_at']
            ];
        }, $alerts),
        'total' => count($alerts)
    ]);
}
?>