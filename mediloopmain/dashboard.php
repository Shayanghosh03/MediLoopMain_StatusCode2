<?php
// Start session at the very beginning
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_secure' => isset($_SERVER['HTTPS']),
        'use_strict_mode' => true
    ]);
}

// Use simple relative paths (they work as confirmed by your test)
require_once 'config/database.php';
require_once 'includes/Auth.php';

// Initialize database connection
try {
    
    $auth = new Auth($pdo);

    // Check if user is logged in
    if (!$auth->isLoggedIn()) {
        header('Location: login.html');
        exit;
    }

    $user = $auth->getCurrentUser();
    
    // If user not found, logout and redirect
    if (!$user) {
        $auth->logout();
        header('Location: login.html');
        exit;
    }
    
    // Get user stats
    $stats = [
        'donations' => 0,
        'requests' => 0,
        'connections' => 0,
        'reviews' => 0
    ];
    
    // Try to get real stats if possible
    try {
        // Example: Get donations count
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM donations WHERE donor_id = ?");
        $stmt->execute([$user['id']]);
        $donations = $stmt->fetch();
        $stats['donations'] = $donations['count'] ?? 0;
        
    } catch (PDOException $e) {
        error_log("Error fetching user stats: " . $e->getMessage());
    }

} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("Database connection failed. Please try again later.");
} catch (Exception $e) {
    error_log("Error in dashboard: " . $e->getMessage());
    die("An error occurred. Please try again.");
}

// Generate CSRF token for logout
$csrf_token = bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $csrf_token;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - MediLoop</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
</head>
<body class="font-inter bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50 min-h-screen">
    <!-- Navigation -->
    <nav class="navbar bg-white/95 backdrop-blur-xl border-b border-gray-100 shadow-lg">
        <div class="nav-container">
            <div class="nav-logo group cursor-pointer transition-all duration-300 hover:scale-105" onclick="window.location.href='index.html'">
                <i class="fas fa-pills text-2xl text-blue-600 group-hover:text-blue-700 transition-colors duration-300"></i>
                <span class="text-xl font-bold text-gray-800 group-hover:text-blue-600 transition-colors duration-300">MediLoop</span>
            </div>
            <ul class="nav-menu">
                <li><a href="index.html" class="nav-link group">
                    <span class="relative">
                        Home
                        <span class="absolute -bottom-1 left-0 w-0 h-0.5 bg-blue-600 group-hover:w-full transition-all duration-300"></span>
                    </span>
                </a></li>
                <li><a href="dashboard.php" class="nav-link group bg-blue-600 text-white px-4 py-2 rounded-full">
                    <i class="fas fa-tachometer-alt mr-2"></i>
                    Dashboard
                </a></li>
                <li><a href="#" onclick="logout(event)" class="nav-link group bg-red-600 text-white px-4 py-2 rounded-full hover:bg-red-700 transition-all duration-300">
                    <i class="fas fa-sign-out-alt mr-2"></i>
                    Logout
                </a></li>
            </ul>
            <div class="nav-toggle">
                <span class="bar"></span>
                <span class="bar"></span>
                <span class="bar"></span>
            </div>
        </div>
    </nav>

    <!-- Dashboard Section -->
    <section class="pt-32 pb-16 px-4">
        <div class="max-w-6xl mx-auto">
            <!-- Welcome Header -->
            <div class="bg-white/95 backdrop-blur-xl rounded-3xl shadow-2xl p-8 mb-8 border border-gray-100">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-800 mb-2">Welcome back, <?php echo htmlspecialchars($user['first_name']); ?>!</h1>
                        <p class="text-gray-600">Manage your medication donations and requests</p>
                        <p class="text-sm text-green-600 mt-2">
                            <i class="fas fa-user-circle mr-1"></i>
                            <?php echo ucfirst($user['user_type'] ?? 'donor'); ?> Account
                        </p>
                    </div>
                    <div class="w-16 h-16 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full flex items-center justify-center shadow-lg">
                        <i class="fas fa-user text-2xl text-white"></i>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white/95 backdrop-blur-xl rounded-2xl p-6 shadow-lg border border-gray-100">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-gradient-to-r from-green-400 to-green-600 rounded-full flex items-center justify-center mr-4">
                            <i class="fas fa-pills text-white"></i>
                        </div>
                        <div>
                            <h3 class="text-2xl font-bold text-gray-800"><?php echo $stats['donations']; ?></h3>
                            <p class="text-gray-600 text-sm">Donations</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white/95 backdrop-blur-xl rounded-2xl p-6 shadow-lg border border-gray-100">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-gradient-to-r from-blue-400 to-blue-600 rounded-full flex items-center justify-center mr-4">
                            <i class="fas fa-hand-holding-heart text-white"></i>
                        </div>
                        <div>
                            <h3 class="text-2xl font-bold text-gray-800"><?php echo $stats['requests']; ?></h3>
                            <p class="text-gray-600 text-sm">Requests</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white/95 backdrop-blur-xl rounded-2xl p-6 shadow-lg border border-gray-100">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-gradient-to-r from-purple-400 to-purple-600 rounded-full flex items-center justify-center mr-4">
                            <i class="fas fa-users text-white"></i>
                        </div>
                        <div>
                            <h3 class="text-2xl font-bold text-gray-800"><?php echo $stats['connections']; ?></h3>
                            <p class="text-gray-600 text-sm">Connections</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white/95 backdrop-blur-xl rounded-2xl p-6 shadow-lg border border-gray-100">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-gradient-to-r from-orange-400 to-orange-600 rounded-full flex items-center justify-center mr-4">
                            <i class="fas fa-star text-white"></i>
                        </div>
                        <div>
                            <h3 class="text-2xl font-bold text-gray-800"><?php echo $stats['reviews']; ?></h3>
                            <p class="text-gray-600 text-sm">Reviews</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div class="bg-white/95 backdrop-blur-xl rounded-3xl shadow-2xl p-8 border border-gray-100">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6">Quick Actions</h2>
                    <div class="space-y-4">
                        <?php if (in_array($user['user_type'], ['donor', 'both'])): ?>
                        <a href="donate.html" class="flex items-center p-4 bg-gradient-to-r from-green-50 to-green-100 rounded-xl hover:from-green-100 hover:to-green-200 transition-all duration-300 group">
                            <div class="w-12 h-12 bg-gradient-to-r from-green-500 to-green-600 rounded-full flex items-center justify-center mr-4 group-hover:scale-110 transition-transform duration-300">
                                <i class="fas fa-plus text-white"></i>
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-800">Donate Medication</h3>
                                <p class="text-sm text-gray-600">Share unused medications with others</p>
                            </div>
                        </a>
                        <?php endif; ?>
                        
                        <?php if (in_array($user['user_type'], ['recipient', 'both'])): ?>
                        <a href="find.html" class="flex items-center p-4 bg-gradient-to-r from-blue-50 to-blue-100 rounded-xl hover:from-blue-100 hover:to-blue-200 transition-all duration-300 group">
                            <div class="w-12 h-12 bg-gradient-to-r from-blue-500 to-blue-600 rounded-full flex items-center justify-center mr-4 group-hover:scale-110 transition-transform duration-300">
                                <i class="fas fa-search text-white"></i>
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-800">Find Medication</h3>
                                <p class="text-sm text-gray-600">Search for available medications</p>
                            </div>
                        </a>
                        <?php endif; ?>
                        
                        <a href="deliver.html" class="flex items-center p-4 bg-gradient-to-r from-purple-50 to-purple-100 rounded-xl hover:from-purple-100 hover:to-purple-200 transition-all duration-300 group">
                            <div class="w-12 h-12 bg-gradient-to-r from-purple-500 to-purple-600 rounded-full flex items-center justify-center mr-4 group-hover:scale-110 transition-transform duration-300">
                                <i class="fas fa-truck text-white"></i>
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-800">Arrange Delivery</h3>
                                <p class="text-sm text-gray-600">Coordinate medication delivery</p>
                            </div>
                        </a>
                    </div>
                </div>

                <!-- User Profile -->
                <div class="bg-white/95 backdrop-blur-xl rounded-3xl shadow-2xl p-8 border border-gray-100">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6">Profile Information</h2>
                    <div class="space-y-4">
                        <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                            <i class="fas fa-user text-gray-400 mr-3"></i>
                            <div>
                                <p class="text-sm text-gray-500">Name</p>
                                <p class="font-medium text-gray-800"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></p>
                            </div>
                        </div>
                        
                        <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                            <i class="fas fa-envelope text-gray-400 mr-3"></i>
                            <div>
                                <p class="text-sm text-gray-500">Email</p>
                                <p class="font-medium text-gray-800"><?php echo htmlspecialchars($user['email']); ?></p>
                                <?php if ($user['email_verified']): ?>
                                <span class="text-xs text-green-600"><i class="fas fa-check-circle"></i> Verified</span>
                                <?php else: ?>
                                <span class="text-xs text-red-600"><i class="fas fa-exclamation-circle"></i> Not verified</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if ($user['phone']): ?>
                        <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                            <i class="fas fa-phone text-gray-400 mr-3"></i>
                            <div>
                                <p class="text-sm text-gray-500">Phone</p>
                                <p class="font-medium text-gray-800"><?php echo htmlspecialchars($user['phone']); ?></p>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($user['address']): ?>
                        <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                            <i class="fas fa-map-marker-alt text-gray-400 mr-3"></i>
                            <div>
                                <p class="text-sm text-gray-500">Address</p>
                                <p class="font-medium text-gray-800"><?php echo htmlspecialchars($user['address']); ?></p>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                            <i class="fas fa-calendar text-gray-400 mr-3"></i>
                            <div>
                                <p class="text-sm text-gray-500">Member Since</p>
                                <p class="font-medium text-gray-800"><?php echo date('F j, Y', strtotime($user['created_at'])); ?></p>
                            </div>
                        </div>
                        
                        <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                            <i class="fas fa-users text-gray-400 mr-3"></i>
                            <div>
                                <p class="text-sm text-gray-500">Account Type</p>
                                <p class="font-medium text-gray-800"><?php echo ucfirst($user['user_type'] ?? 'donor'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script>
        // Mobile navigation toggle
        const navToggle = document.querySelector('.nav-toggle');
        const navMenu = document.querySelector('.nav-menu');
        
        navToggle.addEventListener('click', () => {
            navMenu.classList.toggle('active');
        });
        
        // Close mobile menu when clicking on a link
        document.querySelectorAll('.nav-menu a').forEach(link => {
            link.addEventListener('click', () => {
                navMenu.classList.remove('active');
            });
        });

        function logout(event) {
            event.preventDefault();
            
            if (!confirm('Are you sure you want to logout?')) {
                return;
            }
            
            fetch('auth/logout.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': '<?php echo $csrf_token; ?>'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = data.redirect || 'login.html';
                } else {
                    alert('Logout failed: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                window.location.href = 'login.html';
            });
        }
    </script>
</body>
</html>