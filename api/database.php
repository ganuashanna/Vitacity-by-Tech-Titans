<?php
/**
 * VITACITY - Database Connection
 * Team Tech Titan - SAMVED 2026
 */

class Database {
    private static $conn = null;
    
    public static function getConnection() {
        if (self::$conn !== null) {
            return self::$conn;
        }
        
        try {
            $db_host = getenv('DB_HOST') ?: 'db.your-project.supabase.co';
            $db_name = getenv('DB_NAME') ?: 'postgres';
            $db_user = getenv('DB_USER') ?: 'postgres';
            $db_pass = getenv('DB_PASS') ?: 'your-password';
            $db_port = getenv('DB_PORT') ?: '5432';
            
            $dsn = "pgsql:host=$db_host;port=$db_port;dbname=$db_name;sslmode=require";
            
            self::$conn = new PDO($dsn, $db_user, $db_pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
            
            return self::$conn;
            
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'error' => 'Database connection failed',
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
            http_response_code(500);
            echo json_encode([
                'error' => 'Query failed',
                'message' => $e->getMessage()
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

function sendJSON($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        exit(0);
    }
    
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}

function getJSONInput() {
    $input = file_get_contents('php://input');
    return json_decode($input, true);
}
?>