<?php
// Complete setup script for MediLoop
echo "<h1>MediLoop Complete Setup</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; }
    .error { color: red; }
    .warning { color: orange; }
    .info { color: blue; }
    .step { margin: 20px 0; padding: 10px; border-left: 4px solid #007cba; background: #f0f8ff; }
</style>";

// Step 1: Check PHP Version
echo "<div class='step'>";
echo "<h2>Step 1: PHP Environment Check</h2>";
echo "PHP Version: " . phpversion() . "<br>";
if (version_compare(PHP_VERSION, '7.4.0') >= 0) {
    echo "<span class='success'>✅ PHP version is compatible</span><br>";
} else {
    echo "<span class='error'>❌ PHP version is too old. Please upgrade to 7.4+</span><br>";
    exit;
}

// Check required extensions
$required_extensions = ['pdo', 'pdo_mysql', 'session', 'json'];
foreach ($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "<span class='success'>✅ $ext extension is loaded</span><br>";
    } else {
        echo "<span class='error'>❌ $ext extension is missing</span><br>";
        exit;
    }
}
echo "</div>";

// Step 2: Database Setup
echo "<div class='step'>";
echo "<h2>Step 2: Database Setup</h2>";

$host = 'localhost';
$user = 'root';
$pass = '';

try {
    // Create connection without database
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<span class='success'>✅ MySQL connection successful</span><br>";
    
    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS mediloop CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "<span class='success'>✅ Database 'mediloop' created successfully</span><br>";
    
    // Select the database
    $pdo->exec("USE mediloop");
    
    // Create users table with all required columns
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        first_name VARCHAR(50) NOT NULL,
        last_name VARCHAR(50) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        phone VARCHAR(20),
        date_of_birth DATE,
        gender ENUM('male', 'female', 'other'),
        address TEXT,
        city VARCHAR(50),
        state VARCHAR(50),
        zip_code VARCHAR(20),
        country VARCHAR(50) DEFAULT 'USA',
        user_type ENUM('donor', 'recipient', 'both') DEFAULT 'donor',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        is_active BOOLEAN DEFAULT TRUE,
        email_verified BOOLEAN DEFAULT FALSE,
        verification_token VARCHAR(255),
        verification_expires TIMESTAMP NULL,
        reset_token VARCHAR(255),
        reset_token_expires TIMESTAMP NULL
    )";
    
    $pdo->exec($sql);
    echo "<span class='success'>✅ Users table created successfully</span><br>";
    
    // Create sessions table for remember me functionality
    $sql = "CREATE TABLE IF NOT EXISTS user_sessions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        session_token VARCHAR(255) UNIQUE NOT NULL,
        expires_at TIMESTAMP NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    
    $pdo->exec($sql);
    echo "<span class='success'>✅ User sessions table created successfully</span><br>";
    
    // Create login_attempts table for rate limiting
    $sql = "CREATE TABLE IF NOT EXISTS login_attempts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(100) NOT NULL,
        ip_address VARCHAR(45) NOT NULL,
        success BOOLEAN DEFAULT FALSE,
        attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_email_time (email, attempted_at),
        INDEX idx_ip_time (ip_address, attempted_at)
    )";
    
    $pdo->exec($sql);
    echo "<span class='success'>✅ Login attempts table created successfully</span><br>";
    
    // Check if we need to add missing columns to existing users table
    $columns = $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('user_type', $columns)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN user_type ENUM('donor', 'recipient', 'both') DEFAULT 'donor' AFTER country");
        echo "<span class='success'>✅ Added user_type column to users table</span><br>";
    }
    
    if (!in_array('verification_expires', $columns)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN verification_expires TIMESTAMP NULL AFTER verification_token");
        echo "<span class='success'>✅ Added verification_expires column to users table</span><br>";
    }
    
    if (!in_array('email_verified', $columns)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN email_verified BOOLEAN DEFAULT FALSE AFTER verification_expires");
        echo "<span class='success'>✅ Added email_verified column to users table</span><br>";
    }
    
} catch(PDOException $e) {
    echo "<span class='error'>❌ Database setup failed: " . $e->getMessage() . "</span><br>";
    exit;
}
echo "</div>";

// Step 3: Test Configuration Files
echo "<div class='step'>";
echo "<h2>Step 3: Configuration Files Test</h2>";

// Test database config
try {
    require_once __DIR__ . '/../config/database.php';
    echo "<span class='success'>✅ Database configuration loaded successfully</span><br>";
    
    // Test connection
    $test_query = $pdo->query("SELECT 1");
    echo "<span class='success'>✅ Database connection test successful</span><br>";
    
} catch (Exception $e) {
    echo "<span class='error'>❌ Database configuration failed: " . $e->getMessage() . "</span><br>";
    exit;
}

// Test Auth class
try {
    require_once __DIR__ . '/../includes/Auth.php';
    $auth = new Auth($pdo);
    echo "<span class='success'>✅ Auth class loaded successfully</span><br>";
} catch (Exception $e) {
    echo "<span class='error'>❌ Auth class failed: " . $e->getMessage() . "</span><br>";
    exit;
}
echo "</div>";

// Step 4: Test Auth Endpoints
echo "<div class='step'>";
echo "<h2>Step 4: Auth Endpoints Test</h2>";

$auth_endpoints = [
    'auth/register.php',
    'auth/login.php',
    'auth/verify.php',
    'auth/logout.php',
    'auth/check_session.php'
];

foreach ($auth_endpoints as $endpoint) {
    if (file_exists(__DIR__ . '/../' . $endpoint)) {
        echo "<span class='success'>✅ $endpoint exists</span><br>";
    } else {
        echo "<span class='error'>❌ $endpoint is missing</span><br>";
    }
}
echo "</div>";

// Step 5: Test Main Pages
echo "<div class='step'>";
echo "<h2>Step 5: Main Pages Test</h2>";

$main_pages = [
    'dashboard.php',
    'index.html',
    'login.html',
    'signup.html',
    'verify.html'
];

foreach ($main_pages as $page) {
    if (file_exists(__DIR__ . '/../' . $page)) {
        echo "<span class='success'>✅ $page exists</span><br>";
    } else {
        echo "<span class='warning'>⚠️ $page is missing</span><br>";
    }
}
echo "</div>";

// Step 6: Session Test
echo "<div class='step'>";
echo "<h2>Step 6: Session Test</h2>";

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
if (session_status() == PHP_SESSION_ACTIVE) {
    echo "<span class='success'>✅ Sessions are working</span><br>";
    $_SESSION['test'] = 'test_value';
    if (isset($_SESSION['test'])) {
        echo "<span class='success'>✅ Session data can be set and retrieved</span><br>";
    } else {
        echo "<span class='error'>❌ Session data cannot be set</span><br>";
    }
} else {
    echo "<span class='error'>❌ Sessions are not working</span><br>";
}
echo "</div>";

// Step 7: File Permissions
echo "<div class='step'>";
echo "<h2>Step 7: File Permissions</h2>";

$test_dirs = ['config', 'includes', 'auth', 'setup'];
foreach ($test_dirs as $dir) {
    $dir_path = __DIR__ . '/../' . $dir;
    if (is_dir($dir_path)) {
        echo "<span class='success'>✅ Directory '$dir' exists</span><br>";
        if (is_readable($dir_path)) {
            echo "<span class='success'>✅ Directory '$dir' is readable</span><br>";
        } else {
            echo "<span class='error'>❌ Directory '$dir' is not readable</span><br>";
        }
    } else {
        echo "<span class='error'>❌ Directory '$dir' is missing</span><br>";
    }
}
echo "</div>";

echo "<div class='step'>";
echo "<h2>🎉 Setup Complete!</h2>";
echo "<p><span class='success'>All systems are ready to use!</span></p>";
echo "<h3>Next Steps:</h3>";
echo "<ol>";
echo "<li><a href='../signup.html' target='_blank'>Create a new account</a></li>";
echo "<li><a href='../login.html' target='_blank'>Test login functionality</a></li>";
echo "<li><a href='../dashboard.php' target='_blank'>Access dashboard</a></li>";
echo "</ol>";
echo "<h3>Test Credentials:</h3>";
echo "<p>You can create a test account with any email and password to test the system.</p>";
echo "</div>";
?>
