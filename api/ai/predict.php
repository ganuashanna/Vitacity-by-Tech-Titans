<?php
/**
 * VITACITY - AI Prediction API
 * GET /api/ai/predict?zone_id=Z1&hours_ahead=2
 */

require_once __DIR__ . '/../../lib/database.php';
require_once __DIR__ . '/../../lib/ai_engine.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$zoneId = $_GET['zone_id'] ?? null;
$hoursAhead = isset($_GET['hours_ahead']) ? (int)$_GET['hours_ahead'] : 2;

if (!$zoneId) {
    sendJSON(['error' => 'zone_id parameter required'], 400);
}

// Call DECISION agent to predict congestion
$prediction = UrbanNervousSystem::predict($zoneId, $hoursAhead);

sendJSON($prediction);
?>