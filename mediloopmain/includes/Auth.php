<?php
require_once __DIR__ . '/../config/database.php';

class Auth {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    // Secure session start function
    private function startSecureSession() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start([
                'cookie_httponly' => true,
                'cookie_secure' => isset($_SERVER['HTTPS']),
                'use_strict_mode' => true
            ]);
        }
    }
    
    // Register a new user
    public function register($data) {
        try {
            // Validate input
            if (!$this->validateEmail($data['email'])) {
                return ['success' => false, 'message' => 'Invalid email format'];
            }
            
            if (!$this->validatePassword($data['password'])) {
                return ['success' => false, 'message' => 'Password must be at least 8 characters with uppercase, lowercase, and number'];
            }
            
            // Check if email already exists
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$data['email']]);
            
            if ($stmt->rowCount() > 0) {
                return ['success' => false, 'message' => 'Email already registered'];
            }
            
            // Hash password
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
            
            // Insert user (no verification required)
            $sql = "INSERT INTO users (first_name, last_name, email, phone, address, password, user_type, email_verified) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, 1)";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $data['first_name'],
                $data['last_name'],
                $data['email'],
                $data['phone'] ?? null,
                $data['address'] ?? null,
                $hashedPassword,
                $data['user_type']
            ]);
            
            return [
                'success' => true, 
                'message' => 'Registration successful! You can now login to your account.',
                'user_id' => $this->pdo->lastInsertId()
            ];
            
        } catch (PDOException $e) {
            error_log("Registration error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Registration failed. Please try again.'];
        }
    }
    
    // Login user
    public function login($email, $password, $remember = false) {
        try {
            // Check rate limiting
            if ($this->isRateLimited($email)) {
                return ['success' => false, 'message' => 'Too many login attempts. Please try again in 15 minutes.'];
            }
            
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if (!$user) {
                $this->recordLoginAttempt($email, false);
                return ['success' => false, 'message' => 'Invalid email or password'];
            }
            
            if (!password_verify($password, $user['password'])) {
                $this->recordLoginAttempt($email, false);
                return ['success' => false, 'message' => 'Invalid email or password'];
            }
            
            // Start secure session
            $this->startSecureSession();
            
            // Regenerate session ID to prevent fixation
            session_regenerate_id(true);
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
            $_SESSION['user_type'] = $user['user_type'];
            $_SESSION['login_time'] = time();
            
            // Handle remember me
            if ($remember) {
                $this->createRememberMeToken($user['id']);
            }
            
            // Record successful login
            $this->recordLoginAttempt($email, true);
            
            return ['success' => true, 'message' => 'Login successful', 'user' => $user];
            
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Login failed. Please try again.'];
        }
    }
    
    // Create remember me token
    private function createRememberMeToken($userId) {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
        
        $stmt = $this->pdo->prepare("INSERT INTO user_sessions (user_id, session_token, expires_at) VALUES (?, ?, ?)");
        $stmt->execute([$userId, $token, $expires]);
        
        setcookie('remember_token', $token, [
            'expires' => time() + (30 * 24 * 60 * 60),
            'path' => '/',
            'domain' => '',
            'secure' => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
    }
    
    // Check if user is logged in
    public function isLoggedIn() {
        // Check session first
        $this->startSecureSession();
        
        if (isset($_SESSION['user_id']) && isset($_SESSION['login_time'])) {
            // Check session expiration (8 hours)
            if (time() - $_SESSION['login_time'] > 8 * 60 * 60) {
                $this->logout();
                return false;
            }
            return true;
        }
        
        // Check remember me token
        if (isset($_COOKIE['remember_token'])) {
            return $this->validateRememberMeToken($_COOKIE['remember_token']);
        }
        
        return false;
    }
    
    // Validate remember me token
    private function validateRememberMeToken($token) {
        try {
            $stmt = $this->pdo->prepare("SELECT user_id FROM user_sessions WHERE session_token = ? AND expires_at > NOW()");
            $stmt->execute([$token]);
            $session = $stmt->fetch();
            
            if ($session) {
                // Get user data
                $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ? AND is_active = 1 AND email_verified = 1");
                $stmt->execute([$session['user_id']]);
                $user = $stmt->fetch();
                
                if ($user) {
                    // Start secure session
                    $this->startSecureSession();
                    
                    // Regenerate session ID to prevent fixation
                    session_regenerate_id(true);
                    
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                    $_SESSION['user_type'] = $user['user_type'];
                    $_SESSION['login_time'] = time();
                    
                    return true;
                }
            }
            
            // Remove invalid token
            setcookie('remember_token', '', time() - 3600, '/');
            return false;
            
        } catch (PDOException $e) {
            error_log("Remember me token validation error: " . $e->getMessage());
            return false;
        }
    }
    
    // Logout user
    public function logout() {
        $this->startSecureSession();
        
        // Remove remember me token
        if (isset($_COOKIE['remember_token'])) {
            try {
                $stmt = $this->pdo->prepare("DELETE FROM user_sessions WHERE session_token = ?");
                $stmt->execute([$_COOKIE['remember_token']]);
            } catch (PDOException $e) {
                error_log("Logout error: " . $e->getMessage());
            }
            
            setcookie('remember_token', '', time() - 3600, '/');
        }
        
        // Clear session data
        $_SESSION = [];
        
        // Destroy session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Destroy session
        session_destroy();
        
        return ['success' => true, 'message' => 'Logged out successfully'];
    }
    
    // Get current user data
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        try {
            $stmt = $this->pdo->prepare("SELECT id, first_name, last_name, email, phone, address, user_type, created_at FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Get current user error: " . $e->getMessage());
            return null;
        }
    }
    
    // Verify email - IMPROVED VERSION
    public function verifyEmail($token) {
        try {
            // First check if token is valid and not expired
            $stmt = $this->pdo->prepare("SELECT id, email_verified FROM users WHERE verification_token = ? AND verification_expires > NOW()");
            $stmt->execute([$token]);
            $user = $stmt->fetch();
            
            if (!$user) {
                // Check if token exists but is expired
                $stmt = $this->pdo->prepare("SELECT id FROM users WHERE verification_token = ?");
                $stmt->execute([$token]);
                
                if ($stmt->rowCount() > 0) {
                    return ['success' => false, 'message' => 'Verification token has expired. Please request a new one.'];
                } else {
                    return ['success' => false, 'message' => 'Invalid verification token'];
                }
            }
            
            // Check if already verified
            if ($user['email_verified']) {
                return ['success' => false, 'message' => 'Email is already verified'];
            }
            
            // Update verification status
            $stmt = $this->pdo->prepare("UPDATE users SET email_verified = 1, verification_token = NULL, verification_expires = NULL WHERE verification_token = ?");
            $stmt->execute([$token]);
            
            if ($stmt->rowCount() > 0) {
                return ['success' => true, 'message' => 'Email verified successfully'];
            } else {
                return ['success' => false, 'message' => 'Email verification failed'];
            }
            
        } catch (PDOException $e) {
            error_log("Email verification error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Email verification failed'];
        }
    }
    
    // Send password reset email
    public function sendPasswordReset($email) {
        try {
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ? AND is_active = 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if (!$user) {
                return ['success' => false, 'message' => 'If this email exists in our system, a reset link will be sent'];
            }
            
            $resetToken = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            $stmt = $this->pdo->prepare("UPDATE users SET reset_token = ?, reset_token_expires = ? WHERE id = ?");
            $stmt->execute([$resetToken, $expires, $user['id']]);
            
            // In a real application, you would send an email here
            return [
                'success' => true, 
                'message' => 'Password reset link sent to your email'
            ];
            
        } catch (PDOException $e) {
            error_log("Password reset error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Password reset request failed'];
        }
    }
    
    // Reset password
    public function resetPassword($token, $newPassword) {
        try {
            if (!$this->validatePassword($newPassword)) {
                return ['success' => false, 'message' => 'Password must be at least 8 characters with uppercase, lowercase, and number'];
            }
            
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_token_expires > NOW()");
            $stmt->execute([$token]);
            $user = $stmt->fetch();
            
            if (!$user) {
                return ['success' => false, 'message' => 'Invalid or expired reset token'];
            }
            
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            
            $stmt = $this->pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expires = NULL WHERE id = ?");
            $stmt->execute([$hashedPassword, $user['id']]);
            
            return ['success' => true, 'message' => 'Password reset successfully'];
            
        } catch (PDOException $e) {
            error_log("Password reset error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Password reset failed'];
        }
    }
    
    // Validation helpers
    private function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }
    
    private function validatePassword($password) {
        // At least 8 characters, 1 uppercase, 1 lowercase, 1 number
        return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', $password);
    }
    
    // Rate limiting
    private function isRateLimited($email) {
        try {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as attempts FROM login_attempts 
                                        WHERE email = ? AND attempted_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE) AND success = 0");
            $stmt->execute([$email]);
            $result = $stmt->fetch();
            
            return $result['attempts'] >= 5; // Limit to 5 attempts per 15 minutes
        } catch (PDOException $e) {
            error_log("Rate limit check error: " . $e->getMessage());
            return false; // Don't block on error
        }
    }
    
    private function recordLoginAttempt($email, $success) {
        try {
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $stmt = $this->pdo->prepare("INSERT INTO login_attempts (email, ip_address, success, attempted_at) 
                                        VALUES (?, ?, ?, NOW())");
            $stmt->execute([$email, $ipAddress, $success]);
        } catch (PDOException $e) {
            error_log("Login attempt recording error: " . $e->getMessage());
        }
    }
    
    // Cleanup expired tokens (run this periodically via cron job)
    public function cleanupExpiredTokens() {
        try {
            // Clean expired verification tokens
            $stmt = $this->pdo->prepare("UPDATE users SET verification_token = NULL, verification_expires = NULL 
                                        WHERE verification_expires < NOW()");
            $stmt->execute();
            
            // Clean expired reset tokens
            $stmt = $this->pdo->prepare("UPDATE users SET reset_token = NULL, reset_token_expires = NULL 
                                        WHERE reset_token_expires < NOW()");
            $stmt->execute();
            
            // Clean expired remember me tokens
            $stmt = $this->pdo->prepare("DELETE FROM user_sessions WHERE expires_at < NOW()");
            $stmt->execute();
            
            // Clean old login attempts (older than 30 days)
            $stmt = $this->pdo->prepare("DELETE FROM login_attempts WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");
            $stmt->execute();
            
            return true;
        } catch (PDOException $e) {
            error_log("Token cleanup error: " . $e->getMessage());
            return false;
        }
    }
}

?>