<?php
/**
 * LOCAL DATABASE CONNECTION
 * Use this for localhost testing
 */

class Database {
    private static $conn = null;
    
    public static function getConnection() {
        if (self::$conn !== null) {
            return self::$conn;
        }
        
        try {
            // LOCALHOST SETTINGS
            $db_host = 'localhost';
            $db_name = 'vitacity_db';
            $db_user = 'root';  // Default XAMPP user
            $db_pass = '';      // Default XAMPP has no password
            
            // For MySQL (XAMPP)
            $dsn = "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4";
            
            self::$conn = new PDO($dsn, $db_user, $db_pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
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
                'message' => $e->getMessage(),
                'sql' => $sql
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