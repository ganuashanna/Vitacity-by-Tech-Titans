<?php
/**
 * VITACITY - Database Connection (Fixed for Supabase)
 * Team Tech Titan - SAMVED 2026
 */

class Database {
    private static $conn = null;
    
    public static function getConnection() {
        if (self::$conn !== null) {
            return self::$conn;
        }
        
        try {
            // Get environment variables
            $db_host = getenv('DB_HOST');
            $db_name = getenv('DB_NAME');
            $db_user = getenv('DB_USER');
            $db_pass = getenv('DB_PASS');
            $db_port = getenv('DB_PORT') ?: '5432';
            
            // Validate required variables
            if (!$db_host || !$db_name || !$db_user || !$db_pass) {
                throw new Exception('Missing database credentials. Check environment variables.');
            }
            
            // Build DSN for PostgreSQL with SSL
            $dsn = "pgsql:host={$db_host};port={$db_port};dbname={$db_name};sslmode=require";
            
            // Create PDO connection
            self::$conn = new PDO($dsn, $db_user, $db_pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_TIMEOUT => 5
            ]);
            
            return self::$conn;
            
        } catch (PDOException $e) {
            // Log error for debugging
            error_log('Database connection failed: ' . $e->getMessage());
            
            http_response_code(500);
            echo json_encode([
                'error' => 'Database connection failed',
                'message' => 'Could not connect to database. Please contact support.',
                'debug' => getenv('APP_ENV') === 'development' ? $e->getMessage() : null
            ]);
            exit;
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'error' => 'Configuration error',
                'message' => $e->getMessage()
            ]);
            exit;
        }
    }
    
    public static function query($sql, $params = []) {
        try {
            $conn = self::getConnection();
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log('Query failed: ' . $e->getMessage() . ' | SQL: ' . $sql);
            
            http_response_code(500);
            echo json_encode([
                'error' => 'Query failed',
                'message' => getenv('APP_ENV') === 'development' ? $e->getMessage() : 'Database query error',
                'sql' => getenv('APP_ENV') === 'development' ? $sql : null
            ]);
            exit;
        }
    }
    
    public static function fetchAll($sql, $params = []) {
        $stmt = self::query($sql, $params);
        return $stmt->fetchAll();
    }
    
    public static function fetchOne($sql, $params = []) {
        $stmt = self::query($sql, $params);
        return $stmt->fetch();
    }
    
    public static function execute($sql, $params = []) {
        $stmt = self::query($sql, $params);
        return $stmt->rowCount();
    }
    
    public static function lastInsertId() {
        $conn = self::getConnection();
        return $conn->lastInsertId();
    }
}

// Helper function for JSON responses
function sendJSON($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        exit(0);
    }
    
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

// Helper function to get JSON input
function getJSONInput() {
    $input = file_get_contents('php://input');
    $decoded = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        sendJSON(['error' => 'Invalid JSON input'], 400);
    }
    
    return $decoded ?: [];
}
?>
