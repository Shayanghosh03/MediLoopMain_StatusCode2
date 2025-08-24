<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Simulate the database configuration
$config = [
    'host' => 'localhost',
    'dbname' => 'mediloop',
    'username' => 'root',
    'password' => ''
];

try {
    $conn = new PDO("mysql:host={$config['host']};dbname={$config['dbname']}", 
                   $config['username'], $config['password']);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo json_encode([
        'success' => true,
        'message' => 'Database connection successful',
        'details' => [
            'host' => $config['host'],
            'database' => $config['dbname'],
            'user' => $config['username']
        ]
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed',
        'error' => $e->getMessage()
    ]);
}
?>