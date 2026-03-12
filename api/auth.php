<?php
/**
 * VITACITY - Authentication API (FIXED)
 * POST /api/auth.php?action=register - Register new user
 * POST /api/auth.php?action=login - Login user
 */

require_once __DIR__ . '/../lib/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSON(['error' => 'Method not allowed'], 405);
}

// REGISTER
if ($action === 'register') {
    $input = getJSONInput();
    
    $fullName = trim($input['full_name'] ?? '');
    $email = trim($input['email'] ?? '');
    $phone = trim($input['phone'] ?? '');
    $password = $input['password'] ?? '';
    $emergencyName = trim($input['emergency_contact_name'] ?? '');
    $emergencyPhone = trim($input['emergency_contact_phone'] ?? '');
    $membershipTier = $input['membership_tier'] ?? 'Bronze';
    
    // Validate required fields
    if (!$fullName || !$email || !$phone || !$password) {
        sendJSON(['error' => 'Missing required fields'], 400);
    }
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendJSON(['error' => 'Invalid email format'], 400);
    }
    
    // Validate phone (Indian format)
    if (!preg_match('/^[6-9]\d{9}$/', $phone)) {
        sendJSON(['error' => 'Invalid phone number'], 400);
    }
    
    try {
        // Check if user already exists
        $existing = Database::fetchOne(
            "SELECT id FROM users WHERE email = :email OR phone = :phone",
            ['email' => $email, 'phone' => $phone]
        );
        
        if ($existing) {
            sendJSON(['error' => 'User already exists with this email or phone'], 400);
        }
        
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        
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
        
        // Get the newly created user
        $user = Database::fetchOne(
            "SELECT id, email, full_name, phone, tier, reward_points, 
                    emergency_contact_name, emergency_contact_phone 
             FROM users WHERE email = :email",
            ['email' => $email]
        );
        
        sendJSON([
            'success' => true,
            'message' => 'Registration successful!',
            'user' => [
                'user_id' => $user['id'],
                'full_name' => $user['full_name'],
                'email' => $user['email'],
                'phone' => $user['phone'],
                'membership_tier' => $user['tier'],
                'reward_points' => $user['reward_points'],
                'emergency_contact_name' => $user['emergency_contact_name'],
                'emergency_contact_phone' => $user['emergency_contact_phone']
            ]
        ]);
        
    } catch (Exception $e) {
        error_log('Registration error: ' . $e->getMessage());
        sendJSON([
            'error' => 'Registration failed',
            'message' => 'Could not create account. Please try again.'
        ], 500);
    }
}

// LOGIN
else if ($action === 'login') {
    $input = getJSONInput();
    
    $loginId = trim($input['login_id'] ?? '');
    $password = $input['password'] ?? '';
    
    if (!$loginId || !$password) {
        sendJSON(['error' => 'Missing credentials'], 400);
    }
    
    try {
        // Find user by email or phone
        $user = Database::fetchOne("
            SELECT 
                id, email, full_name, phone, password_hash,
                tier, reward_points,
                emergency_contact_name, emergency_contact_phone,
                status
            FROM users
            WHERE email = :login_id OR phone = :login_id
        ", ['login_id' => $loginId]);
        
        if (!$user) {
            sendJSON(['error' => 'User not found'], 404);
        }
        
        // Check if account is active
        if ($user['status'] !== 'active') {
            sendJSON(['error' => 'Account is not active'], 403);
        }
        
        // Verify password
        if (!password_verify($password, $user['password_hash'])) {
            sendJSON(['error' => 'Invalid password'], 401);
        }
        
        sendJSON([
            'success' => true,
            'message' => 'Login successful!',
            'user' => [
                'user_id' => $user['id'],
                'full_name' => $user['full_name'],
                'email' => $user['email'],
                'phone' => $user['phone'],
                'membership_tier' => $user['tier'],
                'reward_points' => $user['reward_points'],
                'emergency_contact_name' => $user['emergency_contact_name'],
                'emergency_contact_phone' => $user['emergency_contact_phone']
            ]
        ]);
        
    } catch (Exception $e) {
        error_log('Login error: ' . $e->getMessage());
        sendJSON([
            'error' => 'Login failed',
            'message' => 'Could not login. Please try again.'
        ], 500);
    }
}

else {
    sendJSON(['error' => 'Invalid action. Use ?action=register or ?action=login'], 400);
}
?>
