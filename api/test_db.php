<?php
/**
 * VITACITY - Database Connection Test
 * GET /api/test-db.php - Test database connection
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$result = [
    'timestamp' => date('Y-m-d H:i:s'),
    'checks' => []
];

// Check 1: Environment Variables
$result['checks']['env_vars'] = [
    'DB_HOST' => getenv('DB_HOST') ? '✅ Found' : '❌ Missing',
    'DB_NAME' => getenv('DB_NAME') ? '✅ Found' : '❌ Missing',
    'DB_USER' => getenv('DB_USER') ? '✅ Found' : '❌ Missing',
    'DB_PASS' => getenv('DB_PASS') ? '✅ Found (hidden)' : '❌ Missing',
    'DB_PORT' => getenv('DB_PORT') ? '✅ Found' : '❌ Missing',
];

$result['env_values'] = [
    'DB_HOST' => getenv('DB_HOST') ?: 'NOT SET',
    'DB_NAME' => getenv('DB_NAME') ?: 'NOT SET',
    'DB_USER' => getenv('DB_USER') ?: 'NOT SET',
    'DB_PORT' => getenv('DB_PORT') ?: 'NOT SET',
    'DB_PASS' => getenv('DB_PASS') ? 'SET (hidden)' : 'NOT SET'
];

// Check 2: Try PDO Connection
try {
    $db_host = getenv('DB_HOST');
    $db_name = getenv('DB_NAME');
    $db_user = getenv('DB_USER');
    $db_pass = getenv('DB_PASS');
    $db_port = getenv('DB_PORT') ?: '5432';
    
    if (!$db_host || !$db_name || !$db_user || !$db_pass) {
        throw new Exception('Missing environment variables');
    }
    
    $dsn = "pgsql:host=$db_host;port=$db_port;dbname=$db_name;sslmode=require";
    
    $pdo = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    $result['checks']['connection'] = '✅ Connected successfully!';
    
    // Check 3: Try to query zones table
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM zones");
        $count = $stmt->fetch();
        $result['checks']['zones_table'] = "✅ Found {$count['count']} zones";
        
        // Get actual zones
        $stmt = $pdo->query("SELECT id, name FROM zones LIMIT 3");
        $zones = $stmt->fetchAll();
        $result['sample_zones'] = $zones;
        
    } catch (Exception $e) {
        $result['checks']['zones_table'] = '❌ Error: ' . $e->getMessage();
    }
    
    // Check 4: Try to query users table
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
        $count = $stmt->fetch();
        $result['checks']['users_table'] = "✅ Found {$count['count']} users";
    } catch (Exception $e) {
        $result['checks']['users_table'] = '❌ Error: ' . $e->getMessage();
    }
    
} catch (PDOException $e) {
    $result['checks']['connection'] = '❌ Connection failed: ' . $e->getMessage();
} catch (Exception $e) {
    $result['checks']['connection'] = '❌ Error: ' . $e->getMessage();
}

// Overall status
$allGood = true;
foreach ($result['checks'] as $check => $status) {
    if (strpos($status, '❌') !== false) {
        $allGood = false;
        break;
    }
}

$result['overall_status'] = $allGood ? '✅ ALL CHECKS PASSED!' : '❌ SOME CHECKS FAILED';
$result['next_steps'] = $allGood 
    ? 'Database is working! Your API should work now.'
    : 'Fix the failed checks above. See troubleshooting guide.';

echo json_encode($result, JSON_PRETTY_PRINT);
?>
