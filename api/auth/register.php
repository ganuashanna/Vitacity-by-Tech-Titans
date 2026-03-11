<?php
/**
 * VITACITY - Authentication API
 * POST /api/auth/register.php - Register new user
 * POST /api/auth/login.php - Login user
 */

require_once __DIR__ . '/../../lib/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$requestUri = $_SERVER['REQUEST_URI'];

// REGISTER
if (strpos($requestUri, 'register.php') !== false && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = getJSONInput();
    
    $fullName = $input['full_name'] ?? null;
    $email = $input['email'] ?? null;
    $phone = $input['phone'] ?? null;
    $password = $input['password'] ?? null;
    $emergencyName = $input['emergency_contact_name'] ?? null;
    $emergencyPhone = $input['emergency_contact_phone'] ?? null;
    $membershipTier = $input['membership_tier'] ?? 'Bronze';
    
    if (!$fullName || !$email || !$phone || !$password) {
        sendJSON(['error' => 'Missing required fields'], 400);
    }
    
    try {
        // Check if user exists
        $existing = Database::fetchOne(
            "SELECT id FROM users WHERE email = :email OR phone = :phone",
            ['email' => $email, 'phone' => $phone]
        );
        
        if ($existing) {
            sendJSON(['error' => 'User already exists'], 400);
        }
        
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert user
        Database::execute("
            INSERT INTO users (
                email, full_name, phone, password_hash, 
                emergency_contact_name, emergency_contact_phone,
                tier, role, reward_points, status
            ) VALUES (
                :email, :name, :phone, :password,
                :emergency_name, :emergency_phone,
                :tier, 'citizen', 0, 'active'
            )
        ", [
            'email' => $email,
            'name' => $fullName,
            'phone' => $phone,
            'password' => $hashedPassword,
            'emergency_name' => $emergencyName,
            'emergency_phone' => $emergencyPhone,
            'tier' => $membershipTier
        ]);
        
        $userId = Database::lastInsertId();
        
        sendJSON([
            'success' => true,
            'user_id' => $userId,
            'user_name' => $fullName,
            'membership_tier' => $membershipTier,
            'message' => 'Registration successful!'
        ]);
        
    } catch (Exception $e) {
        sendJSON(['error' => 'Registration failed: ' . $e->getMessage()], 500);
    }
}

// LOGIN
if (strpos($requestUri, 'login.php') !== false && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = getJSONInput();
    
    $loginId = $input['login_id'] ?? null;
    $password = $input['password'] ?? null;
    
    if (!$loginId || !$password) {
        sendJSON(['error' => 'Missing credentials'], 400);
    }
    
    try {
        // Find user by email or phone
        $user = Database::fetchOne("
            SELECT 
                id, email, full_name, phone, password_hash,
                tier, reward_points,
                emergency_contact_name, emergency_contact_phone
            FROM users
            WHERE email = :login_id OR phone = :login_id
        ", ['login_id' => $loginId]);
        
        if (!$user) {
            sendJSON(['error' => 'User not found'], 404);
        }
        
        // Verify password
        if (!password_verify($password, $user['password_hash'])) {
            sendJSON(['error' => 'Invalid password'], 401);
        }
        
        sendJSON([
            'success' => true,
            'user_id' => $user['id'],
            'full_name' => $user['full_name'],
            'email' => $user['email'],
            'phone' => $user['phone'],
            'membership_tier' => $user['tier'],
            'reward_points' => $user['reward_points'],
            'emergency_contact_name' => $user['emergency_contact_name'],
            'emergency_contact_phone' => $user['emergency_contact_phone']
        ]);
        
    } catch (Exception $e) {
        sendJSON(['error' => 'Login failed: ' . $e->getMessage()], 500);
    }
}
?>
