<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/Auth.php';

$auth = new Auth($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        $input = $_POST;
    }
    
    // Extract form data
    $data = [
        'first_name' => trim($input['first_name'] ?? ''),
        'last_name' => trim($input['last_name'] ?? ''),
        'email' => trim($input['email'] ?? ''),
        'phone' => trim($input['phone'] ?? ''),
        'address' => trim($input['address'] ?? ''),
        'password' => $input['password'] ?? '',
        'confirm_password' => $input['confirm_password'] ?? '',
        'user_type' => $input['user_type'] ?? '', // Make sure this is included
        'terms' => isset($input['terms']) && ($input['terms'] === 'on' || $input['terms'] === 'true' || $input['terms'] === true) // Check terms
    ];
    
    // Validation
    $errors = [];
    
    if (empty($data['first_name'])) {
        $errors[] = 'First name is required';
    }
    
    if (empty($data['last_name'])) {
        $errors[] = 'Last name is required';
    }
    
    if (empty($data['email'])) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address';
    }
    
    if (empty($data['password'])) {
        $errors[] = 'Password is required';
    } elseif (strlen($data['password']) < 8) {
        $errors[] = 'Password must be at least 8 characters long';
    }
    
    if ($data['password'] !== $data['confirm_password']) {
        $errors[] = 'Passwords do not match';
    }
    
    if (!empty($data['phone']) && !preg_match('/^[\+]?[1-9][\d]{0,15}$/', $data['phone'])) {
        $errors[] = 'Please enter a valid phone number';
    }
    
    if (empty($data['user_type'])) {
        $errors[] = 'Please select how you want to use MediLoop';
    }
    
    // Check if terms are agreed to
    if (!$data['terms']) {
        $errors[] = 'You must agree to the Terms of Service and Privacy Policy';
    }
    
    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
        exit;
    }
    
    // Remove confirm_password and terms from data array before passing to Auth
    unset($data['confirm_password']);
    unset($data['terms']);
    
    // Attempt registration
    $result = $auth->register($data);
    
    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'message' => $result['message']
        ]);
    } else {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $result['message']
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>