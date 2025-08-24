<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

// Configure CORS properly - adjust for your environment
$allowedOrigins = [
    'http://localhost',
    'http://localhost:8080',
    'http://127.0.0.1',
    'http://127.0.0.1:8080',
    'http://localhost:3000'
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowedOrigins)) {
    header('Access-Control-Allow-Origin: ' . $origin);
} else {
    header('Access-Control-Allow-Origin: http://localhost');
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
try {
    if (session_status() === PHP_SESSION_NONE) {
        session_start([
            'cookie_httponly' => true,
            'cookie_secure' => isset($_SERVER['HTTPS']),
            'use_strict_mode' => true
        ]);
    }
} catch (Exception $e) {
    error_log("Session start error: " . $e->getMessage());
}

// Use absolute paths to avoid include issues
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/Auth.php';

try {
    
    $auth = new Auth($pdo);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Get input data
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || json_last_error() !== JSON_ERROR_NONE) {
            http_response_code(400);
            echo json_encode([
                'success' => false, 
                'message' => 'Invalid JSON data. Please check your input.'
            ]);
            exit;
        }
        
        $email = trim($input['email'] ?? '');
        $password = $input['password'] ?? '';
        $remember = filter_var($input['remember'] ?? false, FILTER_VALIDATE_BOOLEAN);
        
        // Validation
        $errors = [];
        
        if (empty($email)) {
            $errors[] = 'Email is required';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address';
        }
        
        if (empty($password)) {
            $errors[] = 'Password is required';
        }
        
        if (!empty($errors)) {
            http_response_code(400);
            echo json_encode([
                'success' => false, 
                'message' => implode(', ', $errors)
            ]);
            exit;
        }
        
        // Attempt login
        $result = $auth->login($email, $password, $remember);
        
        if ($result['success']) {
            // Regenerate session ID for security
            if (session_status() !== PHP_SESSION_NONE) {
                session_regenerate_id(true);
            }
            
            // Return success response - FIXED REDIRECT PATH
            echo json_encode([
                'success' => true,
                'message' => $result['message'],
                'user' => [
                    'id' => $result['user']['id'],
                    'email' => $result['user']['email'],
                    'name' => $result['user']['first_name'] . ' ' . $result['user']['last_name'],
                    'first_name' => $result['user']['first_name'],
                    'last_name' => $result['user']['last_name'],
                    'user_type' => $result['user']['user_type'] ?? 'donor'
                ],
                'redirect' => 'dashboard.php'  // ✅ CHANGED TO RELATIVE PATH FROM ROOT
            ]);
        } else {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => $result['message']
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
    error_log("Database error in login: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Database connection error. Please try again later.'
    ]);
    
} catch (Exception $e) {
    error_log("Error in login: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'An unexpected error occurred. Please try again.'
    ]);
}
?>