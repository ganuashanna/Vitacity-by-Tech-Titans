<?php
/**
 * VITACITY - AI Anomaly Detection
 * GET /api/ai/anomalies?zone_id=Z1
 */

require_once __DIR__ . '/../../lib/database.php';
require_once __DIR__ . '/../../lib/ai_engine.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$zoneId = $_GET['zone_id'] ?? null;

if (!$zoneId) {
    sendJSON(['error' => 'zone_id parameter required'], 400);
}

// Call OBSERVER agent to detect anomalies
$anomalies = UrbanNervousSystem::detectAnomalies($zoneId);

sendJSON($anomalies);
?>