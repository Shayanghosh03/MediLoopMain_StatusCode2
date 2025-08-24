<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'mediloop');
define('DB_USER', 'root');
define('DB_PASS', '');

class Database {
    private $pdo;
    
    public function __construct() {
        try {
            $this->pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }
    
    public function getConnection() {
        return $this->pdo;
    }
}

// Create global database instance for backward compatibility
$database = new Database();
$pdo = $database->getConnection();
?>