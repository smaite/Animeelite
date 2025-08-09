<?php
// Start output buffering to prevent "headers already sent" errors
ob_start();

// Admin header template
session_start();
require_once '../config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Get admin info
$admin_data = null;
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->prepare("SELECT id, username, email, display_name, avatar FROM users WHERE id = ? AND role = 'admin'");
    $stmt->execute([$_SESSION['user_id']]);
    $admin_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

// Get current page for active menu highlighting
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - AnimeElite</title>
    <!-- Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#f5f3ff',
                            100: '#ede9fe',
                            200: '#ddd6fe',
                            300: '#c4b5fd',
                            400: '#a78bfa',
                            500: '#8b5cf6',
                            600: '#7c3aed',
                            700: '#6d28d9',
                            800: '#5b21b6',
                            900: '#4c1d95',
                        }
                    },
                }
            }
        }
    </script>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom styles -->
    <style>
        body {
            background-color: #0f172a;
            color: #fff;
        }
    </style>
</head>
<body class="bg-gray-900 text-white min-h-screen flex">
    <!-- Sidebar -->
    <div class="bg-gray-800 w-64 px-6 py-4 fixed h-full overflow-auto hidden md:block">
        <div class="flex items-center justify-center mb-8">
            <a href="index.php" class="text-2xl font-bold bg-gradient-to-r from-purple-500 to-pink-500 bg-clip-text text-transparent">AnimeElite</a>
        </div>
        
        <h2 class="text-xs uppercase text-gray-400 font-medium mb-4 tracking-wider">Management</h2>
        
        <nav class="mb-8">
            <a href="index.php" class="flex items-center py-2 px-4 text-gray-300 hover:text-white rounded-md mb-1 <?= $current_page === 'index.php' ? 'bg-gray-700 text-white' : '' ?>">
                <i class="fas fa-tachometer-alt w-5 mr-3"></i>
                <span>Dashboard</span>
            </a>
            <a href="anime_management.php" class="flex items-center py-2 px-4 text-gray-300 hover:text-white rounded-md mb-1 <?= $current_page === 'anime_management.php' ? 'bg-gray-700 text-white' : '' ?>">
                <i class="fas fa-film w-5 mr-3"></i>
                <span>Anime</span>
            </a>
            <a href="user_management.php" class="flex items-center py-2 px-4 text-gray-300 hover:text-white rounded-md mb-1 <?= $current_page === 'user_management.php' ? 'bg-gray-700 text-white' : '' ?>">
                <i class="fas fa-users w-5 mr-3"></i>
                <span>Users</span>
            </a>
            <a href="coupon_management.php" class="flex items-center py-2 px-4 text-gray-300 hover:text-white rounded-md mb-1 <?= $current_page === 'coupon_management.php' ? 'bg-gray-700 text-white' : '' ?>">
                <i class="fas fa-ticket-alt w-5 mr-3"></i>
                <span>Coupons</span>
            </a>
        </nav>
        
        <h2 class="text-xs uppercase text-gray-400 font-medium mb-4 tracking-wider">System</h2>
        
        <nav>
            <a href="settings.php" class="flex items-center py-2 px-4 text-gray-300 hover:text-white rounded-md mb-1 <?= $current_page === 'settings.php' ? 'bg-gray-700 text-white' : '' ?>">
                <i class="fas fa-cog w-5 mr-3"></i>
                <span>Settings</span>
            </a>
            <a href="../index.php" class="flex items-center py-2 px-4 text-gray-300 hover:text-white rounded-md mb-1">
                <i class="fas fa-home w-5 mr-3"></i>
                <span>View Site</span>
            </a>
            <a href="../logout.php" class="flex items-center py-2 px-4 text-red-400 hover:text-red-300 rounded-md">
                <i class="fas fa-sign-out-alt w-5 mr-3"></i>
                <span>Logout</span>
            </a>
        </nav>
    </div>
    
    <!-- Main content -->
    <div class="flex-1 md:ml-64 flex flex-col">
        <!-- Top navigation -->
        <header class="bg-gray-800 shadow-md py-4 px-6 flex justify-between items-center">
            <!-- Mobile menu button -->
            <button id="mobile-menu-button" class="md:hidden text-gray-300 hover:text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>
            
            <!-- Admin info -->
            <div class="flex items-center ml-auto">
                <span class="mr-2"><?= htmlspecialchars($admin_data['display_name'] ?? $admin_data['username'] ?? 'Admin') ?></span>
                <?php if (isset($admin_data['avatar']) && $admin_data['avatar']): ?>
                    <img src="<?= htmlspecialchars($admin_data['avatar']) ?>" alt="Avatar" class="w-8 h-8 rounded-full">
                <?php else: ?>
                    <div class="w-8 h-8 rounded-full bg-gradient-to-r from-purple-600 to-pink-600 flex items-center justify-center">
                        <span class="text-white text-sm font-medium">
                            <?= isset($admin_data['display_name']) ? substr($admin_data['display_name'], 0, 1) : 
                                (isset($admin_data['username']) ? substr($admin_data['username'], 0, 1) : 'A') ?>
                        </span>
                    </div>
                <?php endif; ?>
            </div>
        </header>
        
        <!-- Mobile sidebar -->
        <div id="mobile-sidebar" class="fixed inset-0 bg-gray-900 bg-opacity-50 z-40 md:hidden hidden">
            <div class="bg-gray-800 w-64 h-full overflow-auto">
                <div class="flex justify-between items-center p-4 border-b border-gray-700">
                    <a href="index.php" class="text-xl font-bold bg-gradient-to-r from-purple-500 to-pink-500 bg-clip-text text-transparent">AnimeElite</a>
                    <button id="close-sidebar" class="text-gray-300 hover:text-white">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <div class="p-4">
                    <h2 class="text-xs uppercase text-gray-400 font-medium mb-4 tracking-wider">Management</h2>
                    
                    <nav class="mb-8">
                        <a href="index.php" class="flex items-center py-2 px-4 text-gray-300 hover:text-white rounded-md mb-1 <?= $current_page === 'index.php' ? 'bg-gray-700 text-white' : '' ?>">
                            <i class="fas fa-tachometer-alt w-5 mr-3"></i>
                            <span>Dashboard</span>
                        </a>
                        <a href="anime_management.php" class="flex items-center py-2 px-4 text-gray-300 hover:text-white rounded-md mb-1 <?= $current_page === 'anime_management.php' ? 'bg-gray-700 text-white' : '' ?>">
                            <i class="fas fa-film w-5 mr-3"></i>
                            <span>Anime</span>
                        </a>
                        <a href="user_management.php" class="flex items-center py-2 px-4 text-gray-300 hover:text-white rounded-md mb-1 <?= $current_page === 'user_management.php' ? 'bg-gray-700 text-white' : '' ?>">
                            <i class="fas fa-users w-5 mr-3"></i>
                            <span>Users</span>
                        </a>
                        <a href="coupon_management.php" class="flex items-center py-2 px-4 text-gray-300 hover:text-white rounded-md mb-1 <?= $current_page === 'coupon_management.php' ? 'bg-gray-700 text-white' : '' ?>">
                            <i class="fas fa-ticket-alt w-5 mr-3"></i>
                            <span>Coupons</span>
                        </a>
                    </nav>
                    
                    <h2 class="text-xs uppercase text-gray-400 font-medium mb-4 tracking-wider">System</h2>
                    
                    <nav>
                        <a href="settings.php" class="flex items-center py-2 px-4 text-gray-300 hover:text-white rounded-md mb-1 <?= $current_page === 'settings.php' ? 'bg-gray-700 text-white' : '' ?>">
                            <i class="fas fa-cog w-5 mr-3"></i>
                            <span>Settings</span>
                        </a>
                        <a href="../index.php" class="flex items-center py-2 px-4 text-gray-300 hover:text-white rounded-md mb-1">
                            <i class="fas fa-home w-5 mr-3"></i>
                            <span>View Site</span>
                        </a>
                        <a href="../logout.php" class="flex items-center py-2 px-4 text-red-400 hover:text-red-300 rounded-md">
                            <i class="fas fa-sign-out-alt w-5 mr-3"></i>
                            <span>Logout</span>
                        </a>
                    </nav>
                </div>
            </div>
        </div>
        
        <div class="p-6 flex-grow">
            <!-- Main content goes here --> 