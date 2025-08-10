<?php
// Header template for all pages
// This file should be included at the top of all PHP pages
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and get user data
$userData = null;
if (isset($_SESSION['user_id'])) {
    require_once dirname(__FILE__) . '/../config.php';
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stmt = $pdo->prepare("SELECT id, username, email, display_name, role, avatar FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Just log error and continue
        error_log("Error fetching user data in header: " . $e->getMessage());
    }
}

if (!isset($pageTitle)) {
    $pageTitle = "AnimeElite";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <link rel="icon" type="image/png" href="../extension_icon.png">
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
            background-color: #000;
            color: #fff;
        }
        .animate-pulse {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        @keyframes pulse {
            0%, 100% {
                opacity: 0.2;
            }
            50% {
                opacity: 0.4;
            }
        }
        /* Video player aspect ratio */
        .aspect-w-16 {
            position: relative;
            padding-bottom: 56.25%; /* 16:9 aspect ratio */
        }
        .aspect-w-16 iframe,
        .aspect-w-16 > div {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
        }
    </style>
    <?php if (isset($_SESSION['user_id'])): ?>
    <script>
        // Update user activity status every 2 minutes
        function updateUserActivity() {
            fetch('update_activity.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.status === 'online') {
                        console.log('Online status updated');
                    }
                })
                .catch(error => console.error('Error updating activity status:', error));
        }
        
        // Update immediately and then every 2 minutes
        document.addEventListener('DOMContentLoaded', function() {
            // Check if we're on the player page
            const currentPage = window.location.pathname.split('/').pop();
            if (currentPage === 'player.php') {
                updateUserActivity();
                setInterval(updateUserActivity, 120000); // 2 minutes
            }
        });
    </script>
    <?php endif; ?>
</head>
<body class="bg-black text-white min-h-screen flex flex-col">
    <!-- Header -->
    <header class="bg-gray-900 shadow-md">
        <div class="container mx-auto px-4 py-3">
            <div class="flex justify-between items-center">
                <!-- Logo -->
                <a href="index.php" class="flex items-center">
                    <span class="text-2xl font-bold bg-gradient-to-r from-purple-500 to-pink-500 bg-clip-text text-transparent">AnimeElite</span>
                </a>
                
                <!-- Desktop Navigation -->
                <nav class="hidden md:flex space-x-6 items-center">
                    <a href="index.php" class="text-gray-300 hover:text-white transition-colors">Home</a>
                    <a href="browse.php" class="text-gray-300 hover:text-white transition-colors">Browse</a>
                    <a href="latest.php" class="text-gray-300 hover:text-white transition-colors">Latest</a>
                    
                    <?php if (isset($userData) && $userData): ?>
                    <!-- User is logged in -->
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" class="flex items-center focus:outline-none">
                            <?php if ($userData['avatar']): ?>
                                <img src="<?= htmlspecialchars($userData['avatar']) ?>" alt="Avatar" class="w-8 h-8 rounded-full mr-2">
                            <?php else: ?>
                                <div class="w-8 h-8 rounded-full bg-gradient-to-r from-purple-600 to-pink-600 flex items-center justify-center mr-2">
                                    <span class="text-white text-sm font-medium">
                                        <?= isset($userData['display_name']) ? substr($userData['display_name'], 0, 1) : substr($userData['username'], 0, 1) ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                            <span class="text-gray-300"><?= htmlspecialchars($userData['display_name'] ?? $userData['username']) ?></span>
                            <svg class="w-4 h-4 ml-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        
                        <!-- Dropdown menu -->
                        <div x-show="open" @click.away="open = false" class="absolute right-0 mt-2 w-48 bg-gray-800 rounded-md shadow-lg py-1 z-50">
                            <a href="profile.php" class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700">Profile</a>
                            <a href="favorites.php" class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700">Favorites</a>
                            <a href="history.php" class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700">Watch History</a>
                            <?php if ($userData['role'] === 'admin'): ?>
                                <a href="admin/" class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700">Admin Panel</a>
                            <?php endif; ?>
                            <div class="border-t border-gray-700 my-1"></div>
                            <a href="logout.php" class="block px-4 py-2 text-sm text-red-400 hover:bg-gray-700">Sign Out</a>
                        </div>
                    </div>
                    <?php else: ?>
                    <!-- User is not logged in -->
                    <div class="flex space-x-4">
                        <a href="login.php" class="text-gray-300 hover:text-white transition-colors">Sign In</a>
                        <a href="signup.php" class="bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white px-4 py-2 rounded-full transition-all duration-300">Sign Up</a>
                    </div>
                    <?php endif; ?>
                </nav>
                
                <!-- Mobile menu button -->
                <div class="md:hidden">
                    <button id="mobile-menu-button" class="text-gray-300 hover:text-white focus:outline-none">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                </div>
            </div>
            
            <!-- Mobile menu -->
            <div id="mobile-menu" class="md:hidden hidden mt-4 pb-4">
                <a href="index.php" class="block py-2 text-gray-300 hover:text-white">Home</a>
                <a href="browse.php" class="block py-2 text-gray-300 hover:text-white">Browse</a>
                <a href="latest.php" class="block py-2 text-gray-300 hover:text-white">Latest</a>
                
                <?php if (isset($userData) && $userData): ?>
                <!-- User is logged in (mobile) -->
                <div class="border-t border-gray-700 my-2 pt-2">
                    <a href="profile.php" class="block py-2 text-gray-300 hover:text-white">Profile</a>
                    <a href="favorites.php" class="block py-2 text-gray-300 hover:text-white">Favorites</a>
                    <a href="history.php" class="block py-2 text-gray-300 hover:text-white">Watch History</a>
                    <?php if ($userData['role'] === 'admin'): ?>
                        <a href="admin/" class="block py-2 text-gray-300 hover:text-white">Admin Panel</a>
                    <?php endif; ?>
                    <a href="logout.php" class="block py-2 text-red-400 hover:text-red-300">Sign Out</a>
                </div>
                <?php else: ?>
                <!-- User is not logged in (mobile) -->
                <div class="border-t border-gray-700 my-2 pt-2">
                    <a href="login.php" class="block py-2 text-gray-300 hover:text-white">Sign In</a>
                    <a href="signup.php" class="block py-2 text-gray-300 hover:text-white">Sign Up</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </header> 