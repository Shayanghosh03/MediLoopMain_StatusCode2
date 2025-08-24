<?php
header('Content-Type: application/json');

// Configure CORS properly
$allowedOrigins = [
    'http://localhost:3000',
    'http://localhost',
    'https://yourdomain.com' // Replace with your actual domain
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowedOrigins)) {
    header('Access-Control-Allow-Origin: ' . $origin);
} else {
    header('Access-Control-Allow-Origin: http://localhost'); // Default fallback
}

header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Start session securely
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

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Check if user is actually logged in before logging out
        $wasLoggedIn = $auth->isLoggedIn();
        
        // Perform logout
        $result = $auth->logout();
        
        if ($result['success']) {
            // Clear any existing output buffers
            if (ob_get_length()) {
                ob_clean();
            }
            
            echo json_encode([
                'success' => true,
                'message' => $result['message'],
                'was_logged_in' => $wasLoggedIn,
                'redirect' => 'login.html'
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Logout failed. Please try again.'
            ]);
        }
    } else {
        http_response_code(405);
        echo json_encode([
            'success' => false, 
            'message' => 'Method not allowed. Only POST requests are accepted.'
        ]);
    }

} catch (PDOException $e) {
    error_log("Database error in logout: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Internal server error. Please try again later.'
    ]);
    
} catch (Exception $e) {
    error_log("Error in logout: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'An unexpected error occurred. Please try again.'
    ]);
}
?>