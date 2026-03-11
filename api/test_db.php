<?php
/**
 * VITACITY - Simple Database Test (FIXED)
 * GET /api/test-connection.php - Test database connection
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$result = [
    'timestamp' => date('Y-m-d H:i:s'),
    'status' => 'testing...',
    'steps' => []
];

// Step 1: Check environment variables
$result['steps'][] = '1. Checking environment variables...';
$envVars = [
    'DB_HOST' => getenv('DB_HOST'),
    'DB_NAME' => getenv('DB_NAME'),
    'DB_USER' => getenv('DB_USER'),
    'DB_PASS' => getenv('DB_PASS') ? 'SET' : 'MISSING',
    'DB_PORT' => getenv('DB_PORT'),
];

$result['environment'] = $envVars;

if (!getenv('DB_HOST') || !getenv('DB_PASS')) {
    $result['status'] = 'FAILED';
    $result['error'] = 'Missing environment variables';
    echo json_encode($result, JSON_PRETTY_PRINT);
    exit;
}

$result['steps'][] = '2. Environment variables OK';

// Step 2: Try to connect
$result['steps'][] = '3. Attempting database connection...';

try {
    $dsn = sprintf(
        "pgsql:host=%s;port=%s;dbname=%s;sslmode=require",
        getenv('DB_HOST'),
        getenv('DB_PORT') ?: '5432',
        getenv('DB_NAME')
    );
    
    $pdo = new PDO($dsn, getenv('DB_USER'), getenv('DB_PASS'), [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 5
    ]);
    
    $result['steps'][] = '4. Connection successful!';
    
    // Step 3: Check if zones table exists and has data
    $result['steps'][] = '5. Checking zones table...';
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM zones");
    $count = $stmt->fetch();
    
    $result['steps'][] = "6. Found {$count['count']} zones";
    
    if ($count['count'] > 0) {
        // Get sample zones
        $stmt = $pdo->query("SELECT id, name, total_slots FROM zones LIMIT 3");
        $zones = $stmt->fetchAll();
        $result['sample_zones'] = $zones;
        $result['steps'][] = '7. Sample zones retrieved';
    }
    
    // Step 4: Check users table
    $result['steps'][] = '8. Checking users table...';
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $userCount = $stmt->fetch();
    $result['steps'][] = "9. Found {$userCount['count']} users";
    
    // Success!
    $result['status'] = 'SUCCESS';
    $result['message'] = 'Database is connected and working!';
    $result['summary'] = [
        'zones' => $count['count'],
        'users' => $userCount['count'],
        'ready' => true
    ];
    
} catch (PDOException $e) {
    $result['status'] = 'FAILED';
    $result['error'] = $e->getMessage();
    $result['steps'][] = 'ERROR: ' . $e->getMessage();
}

echo json_encode($result, JSON_PRETTY_PRINT);
?>