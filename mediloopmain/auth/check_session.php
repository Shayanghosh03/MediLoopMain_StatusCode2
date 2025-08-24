<?php
header('Content-Type: application/json');

// Configure CORS properly - adjust based on your needs
$allowedOrigins = [
    'http://localhost:3000',
    'http://localhost',
    'https://yourdomain.com'
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowedOrigins)) {
    header('Access-Control-Allow-Origin: ' . $origin);
} else {
    header('Access-Control-Allow-Origin: http://localhost'); // Default fallback
}

header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_secure' => isset($_SERVER['HTTPS']),
        'use_strict_mode' => true
    ]);
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/Auth.php';

try {
    
    $auth = new Auth($pdo);

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if ($auth->isLoggedIn()) {
            $user = $auth->getCurrentUser();
            
            if ($user) {
                echo json_encode([
                    'success' => true,
                    'logged_in' => true,
                    'user' => [
                        'id' => $user['id'],
                        'email' => $user['email'],
                        'name' => $user['first_name'] . ' ' . $user['last_name'],
                        'first_name' => $user['first_name'],
                        'last_name' => $user['last_name'],
                        'user_type' => $user['user_type'] ?? 'donor', // Added user_type
                        'email_verified' => (bool)($user['email_verified'] ?? false)
                    ],
                    'session_time' => time() - ($_SESSION['login_time'] ?? 0)
                ]);
            } else {
                // User session exists but user not found in database
                echo json_encode([
                    'success' => true,
                    'logged_in' => false,
                    'message' => 'Session invalid'
                ]);
            }
        } else {
            echo json_encode([
                'success' => true,
                'logged_in' => false,
                'message' => 'Not logged in'
            ]);
        }
    } else {
        http_response_code(405);
        echo json_encode([
            'success' => false, 
            'message' => 'Method not allowed. Only GET requests are accepted.'
        ]);
    }

} catch (PDOException $e) {
    error_log("Database error in check_session: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Internal server error'
    ]);
    
} catch (Exception $e) {
    error_log("Error in check_session: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'An unexpected error occurred'
    ]);
}
?>