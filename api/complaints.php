<?php
/**
 * VITACITY - Complaints API
 * POST /api/complaints.php - File complaint
 * GET /api/complaints.php - Get complaints
 */

require_once __DIR__ . '/../lib/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// CREATE COMPLAINT
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = getJSONInput();
    
    $userId = $input['user_id'] ?? null;
    $zoneId = $input['zone_id'] ?? null;
    $slotId = $input['slot_id'] ?? null;
    $type = $input['type'] ?? 'General';
    $title = $input['title'] ?? '';
    $description = $input['description'] ?? '';
    $priority = $input['priority'] ?? 'Medium';
    
    if (!$userId || !$title || !$description) {
        sendJSON(['error' => 'Missing required fields'], 400);
    }
    
    try {
        // AI categorization
        $aiCategory = 'Service Issue';
        $requiresMaintenance = false;
        $aiPriority = 'Medium';
        
        $typeLower = strtolower($type);
        if (strpos($typeLower, 'sensor') !== false || strpos($typeLower, 'qr') !== false) {
            $aiCategory = 'Technical Issue';
            $requiresMaintenance = true;
            $aiPriority = 'High';
        } elseif (strpos($typeLower, 'billing') !== false || strpos($typeLower, 'emergency') !== false) {
            $aiPriority = 'High';
        }
        
        // Generate complaint code
        $complaintCode = 'CMP-' . date('YmdHis') . rand(100, 999);
        
        // Create complaint
        Database::execute("
            INSERT INTO complaints (
                complaint_code, user_id, zone_id, slot_id,
                type, title, description, priority, status,
                ai_category, ai_priority, requires_maintenance
            ) VALUES (
                :code, :user_id, :zone_id, :slot_id,
                :type, :title, :description, :priority, 'open',
                :ai_category, :ai_priority, :requires_maintenance
            )
        ", [
            'code' => $complaintCode,
            'user_id' => $userId,
            'zone_id' => $zoneId,
            'slot_id' => $slotId,
            'type' => $type,
            'title' => $title,
            'description' => $description,
            'priority' => $priority,
            'ai_category' => $aiCategory,
            'ai_priority' => $aiPriority,
            'requires_maintenance' => $requiresMaintenance
        ]);
        
        // Award reward points for filing complaint
        Database::execute(
            "UPDATE users SET reward_points = reward_points + 5 WHERE id = :user_id",
            ['user_id' => $userId]
        );
        
        // Update complaint patterns (for AI Memory)
        if ($slotId) {
            Database::execute("
                INSERT INTO complaint_patterns (zone_id, slot_id, complaint_type, occurrence_count, last_occurrence, pattern_detected, maintenance_required)
                VALUES (:zone_id, :slot_id, :type, 1, NOW(), FALSE, :maintenance)
                ON CONFLICT (zone_id, slot_id, complaint_type) 
                DO UPDATE SET 
                    occurrence_count = complaint_patterns.occurrence_count + 1,
                    last_occurrence = NOW(),
                    pattern_detected = CASE WHEN complaint_patterns.occurrence_count + 1 >= 3 THEN TRUE ELSE FALSE END,
                    maintenance_required = :maintenance
            ", [
                'zone_id' => $zoneId,
                'slot_id' => $slotId,
                'type' => $type,
                'maintenance' => $requiresMaintenance
            ]);
        }
        
        sendJSON([
            'success' => true,
            'complaint_code' => $complaintCode,
            'status' => 'filed',
            'ai_category' => $aiCategory,
            'ai_priority' => $aiPriority,
            'requires_maintenance' => $requiresMaintenance,
            'reward_points_earned' => 5
        ]);
        
    } catch (Exception $e) {
        sendJSON(['error' => 'Failed to file complaint: ' . $e->getMessage()], 500);
    }
}

// GET COMPLAINTS
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $userId = $_GET['user_id'] ?? null;
    $zoneId = $_GET['zone_id'] ?? null;
    $status = $_GET['status'] ?? null;
    $limit = $_GET['limit'] ?? 50;
    
    $sql = "
        SELECT 
            c.id, c.complaint_code, c.type, c.title, c.description,
            c.priority, c.status, c.zone_id, c.slot_id,
            c.created_at, c.ai_category, c.ai_priority, c.requires_maintenance,
            z.name as zone_name
        FROM complaints c
        LEFT JOIN zones z ON c.zone_id = z.id
        WHERE 1=1
    ";
    
    $params = [];
    
    if ($userId) {
        $params['user_id'] = $userId;
        $sql .= " AND c.user_id = :user_id";
    }
    
    if ($zoneId) {
        $params['zone_id'] = $zoneId;
        $sql .= " AND c.zone_id = :zone_id";
    }
    
    if ($status) {
        $params['status'] = $status;
        $sql .= " AND c.status = :status";
    }
    
    $sql .= " ORDER BY c.created_at DESC LIMIT " . intval($limit);
    
    $complaints = Database::fetchAll($sql, $params);
    
    sendJSON([
        'complaints' => array_map(function($c) {
            return [
                'id' => $c['id'],
                'code' => $c['complaint_code'],
                'type' => $c['type'],
                'title' => $c['title'],
                'description' => $c['description'],
                'priority' => $c['priority'],
                'status' => $c['status'],
                'zone_id' => $c['zone_id'],
                'zone_name' => $c['zone_name'],
                'slot_id' => $c['slot_id'],
                'created_at' => $c['created_at'],
                'ai_category' => $c['ai_category'],
                'ai_priority' => $c['ai_priority'],
                'requires_maintenance' => (bool)$c['requires_maintenance']
            ];
        }, $complaints),
        'total' => count($complaints)
    ]);
}
?>