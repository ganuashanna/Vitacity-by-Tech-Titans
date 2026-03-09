<?php
/**
 * VITACITY - Health Check
 * GET /api/health.php
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

echo json_encode([
    'status' => 'healthy',
    'app' => 'VITACITY',
    'team' => 'Tech Titan',
    'university' => 'Dr. Babasaheb Ambedkar Marathwada University',
    'event' => 'SAMVED-2026',
    'version' => '1.0.0',
    'timestamp' => date('Y-m-d H:i:s'),
    'message' => 'Smart Parking Urban Nervous System is running!'
], JSON_PRETTY_PRINT);
?>