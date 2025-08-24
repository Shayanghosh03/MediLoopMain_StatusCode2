<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

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
    
    // Check if donations table exists
    $stmt = $conn->query("SHOW TABLES LIKE 'donations'");
    $tableExists = $stmt->rowCount() > 0;
    
    if (!$tableExists) {
        echo json_encode([
            'success' => false,
            'message' => 'Donations table does not exist'
        ]);
        exit;
    }
    
    // Get table structure
    $stmt = $conn->query("DESCRIBE donations");
    $tableStructure = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'message' => 'Donations table exists',
        'table_structure' => $tableStructure
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error checking table',
        'error' => $e->getMessage()
    ]);
}
?>