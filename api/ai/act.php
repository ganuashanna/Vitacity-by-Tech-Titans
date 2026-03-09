<?php
/**
 * VITACITY - AI Action Endpoint
 * POST /api/ai/act
 * Body: { "zone_id": "Z1" }
 */

require_once __DIR__ . '/../../lib/database.php';
require_once __DIR__ . '/../../lib/ai_engine.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSON(['error' => 'Method not allowed'], 405);
}

$input = getJSONInput();
$zoneId = $input['zone_id'] ?? null;

if (!$zoneId) {
    sendJSON(['error' => 'zone_id required in request body'], 400);
}

// Call ACTION agent to take autonomous actions
$result = UrbanNervousSystem::act($zoneId);

sendJSON($result);
?>