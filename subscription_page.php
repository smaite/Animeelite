<?php
// Subscription management page
session_start();
require_once 'config.php';

// Initialize variables
$userData = null;
$subscription = null;
$error = '';
$successMessage = '';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?redirect=subscription_page.php");
    exit();
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get user data
    $stmt = $pdo->prepare("SELECT id, username, email, display_name, role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get subscription details from subscriptions table
    $stmt = $pdo->prepare("
        SELECT * FROM subscriptions 
        WHERE user_id = ? 
        AND status = 'active' 
        AND end_date > NOW() 
        ORDER BY id DESC 
        LIMIT 1
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $subscriptionData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Set subscription status
    $hasActiveSubscription = false;
    $subscriptionExpired = false;
    
    if ($subscriptionData) {
        $hasActiveSubscription = true;
    } else {
        // Check for expired subscription
        $stmt = $pdo->prepare("
            SELECT * FROM subscriptions 
            WHERE user_id = ? 
            AND (status = 'active' OR status = 'expired')
            ORDER BY end_date DESC 
            LIMIT 1
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $expiredSubscription = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($expiredSubscription) {
            $subscriptionExpired = true;
        }
    }
    
    // Process coupon form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['coupon_code'])) {
        $couponCode = trim($_POST['coupon_code']);
        
        if (empty($couponCode)) {
            $error = "Please enter a coupon code.";
        } else {
            // Check if coupon exists and is valid
            $stmt = $pdo->prepare("SELECT * FROM coupons WHERE code = ? AND is_active = 1");
            $stmt->execute([$couponCode]);
            $coupon = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$coupon) {
                $error = "Invalid or inactive coupon code.";
            } else {
                // Check if coupon has expired
                $isExpired = ($coupon['expires_at'] !== null && strtotime($coupon['expires_at']) < time());
                
                // Check if coupon has reached usage limit
                $usageLimitReached = ($coupon['usage_limit'] > 0 && $coupon['usage_count'] >= $coupon['usage_limit']);
                
                if ($isExpired) {
                    $error = "This coupon has expired.";
                } else if ($usageLimitReached) {
                    $error = "This coupon has reached its usage limit.";
                } else {
                    // Calculate subscription end date based on coupon duration
                    $startDate = date('Y-m-d H:i:s');
                    $endDate = date('Y-m-d H:i:s', strtotime('+' . $coupon['duration_days'] . ' days'));
                    
                    // Begin transaction
                    $pdo->beginTransaction();
                    
                    try {
                        // Insert new subscription
                        $stmt = $pdo->prepare("INSERT INTO subscriptions (user_id, plan_name, status, start_date, end_date) VALUES (?, 'premium', 'active', ?, ?)");
                        $stmt->execute([$_SESSION['user_id'], $startDate, $endDate]);
                        
                        // We don't need to update the users table since we don't have subscription columns there
                        
                        // Increment coupon usage count
                        $stmt = $pdo->prepare("UPDATE coupons SET usage_count = usage_count + 1 WHERE id = ?");
                        $stmt->execute([$coupon['id']]);
                        
                        // Commit transaction
                        $pdo->commit();
                        
                        $successMessage = "Subscription activated successfully! Your premium access is valid until " . date('F j, Y', strtotime($endDate)) . ".";
                        
                        // Refresh subscription data
                        $stmt = $pdo->prepare("
                            SELECT * FROM subscriptions 
                            WHERE user_id = ? 
                            AND status = 'active' 
                            AND end_date > NOW() 
                            ORDER BY id DESC 
                            LIMIT 1
                        ");
                        $stmt->execute([$_SESSION['user_id']]);
                        $subscriptionData = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        // Update hasActiveSubscription flag
                        $hasActiveSubscription = ($subscriptionData !== false);
                        
                        // Refresh user data
                        $stmt = $pdo->prepare("SELECT id, username, email, display_name, role FROM users WHERE id = ?");
                        $stmt->execute([$_SESSION['user_id']]);
                        $userData = $stmt->fetch(PDO::FETCH_ASSOC);
                    } catch (PDOException $e) {
                        $pdo->rollBack();
                        $error = "Database error: " . $e->getMessage();
                    }
                }
            }
        }
    }
    
    // Process cancellation request
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_subscription'])) {
        // Begin transaction
        $pdo->beginTransaction();
        
        try {
            // Update subscription status
            $stmt = $pdo->prepare("UPDATE subscriptions SET status = 'cancelled' WHERE user_id = ? AND status = 'active'");
            $stmt->execute([$_SESSION['user_id']]);
            
            // Update user record
            $stmt = $pdo->prepare("UPDATE users SET subscription = 'free' WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            
            // Commit transaction
            $pdo->commit();
            
            $successMessage = "Your subscription has been cancelled. You will have access until the end of your current billing period.";
            
            // Refresh subscription data
            $stmt = $pdo->prepare("SELECT * FROM subscriptions WHERE user_id = ? ORDER BY id DESC LIMIT 1");
            $stmt->execute([$_SESSION['user_id']]);
            $subscription = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Refresh user data
            $stmt = $pdo->prepare("SELECT id, username, email, display_name, role, subscription, subscription_expires FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $userData = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Database error: " . $e->getMessage();
        }
    }
    
    // Get subscription plans
    $stmt = $pdo->query("SELECT * FROM subscription_plans WHERE is_active = 1 ORDER BY price_monthly");
    $plans = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

$pageTitle = "Subscription - AnimeElite";
include 'includes/header.php';
?>

<main class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-white mb-6">Subscription Management</h1>
    
    <?php if (!empty($error)): ?>
    <div class="bg-red-900 text-white p-4 rounded-lg mb-6">
        <p><?= htmlspecialchars($error) ?></p>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($successMessage)): ?>
    <div class="bg-green-900 text-white p-4 rounded-lg mb-6">
        <p><?= htmlspecialchars($successMessage) ?></p>
    </div>
    <?php endif; ?>
    
    <!-- Current Subscription Status -->
    <div class="bg-gray-800 rounded-lg p-6 mb-8">
        <h2 class="text-xl font-bold text-white mb-4">Your Subscription</h2>
        
        <?php if ($hasActiveSubscription): ?>
        <div class="bg-gray-700 p-4 rounded-lg mb-6">
            <div class="flex items-center mb-2">
                <div class="w-12 h-12 bg-yellow-500 rounded-full flex items-center justify-center mr-4">
                    <svg class="w-6 h-6 text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-yellow-400">Premium Subscription Active</h3>
                    <p class="text-gray-300">Expires on <?= date('F j, Y', strtotime($userData['subscription_expires'])) ?></p>
                </div>
            </div>
            <div class="mt-4">
                <ul class="space-y-2 text-gray-300">
                    <li class="flex items-center">
                        <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Access to all premium content
                    </li>
                    <li class="flex items-center">
                        <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        No ads while watching
                    </li>
                    <li class="flex items-center">
                        <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Access to newest episodes immediately
                    </li>
                </ul>
            </div>
            
            <form method="post" class="mt-6" onsubmit="return confirm('Are you sure you want to cancel your subscription? You will still have access until the expiration date.');">
                <button type="submit" name="cancel_subscription" value="1" class="bg-red-600 hover:bg-red-700 text-white font-medium py-2 px-4 rounded-lg transition-colors">
                    Cancel Subscription
                </button>
            </form>
        </div>
        <?php elseif ($subscriptionExpired): ?>
        <div class="bg-gray-700 p-4 rounded-lg mb-6">
            <div class="flex items-center mb-2">
                <div class="w-12 h-12 bg-red-500 rounded-full flex items-center justify-center mr-4">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-red-400">Subscription Expired</h3>
                    <p class="text-gray-300">Your premium subscription has expired.</p>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="bg-gray-700 p-4 rounded-lg mb-6">
            <div class="flex items-center mb-2">
                <div class="w-12 h-12 bg-gray-600 rounded-full flex items-center justify-center mr-4">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-white">Free Plan</h3>
                    <p class="text-gray-300">You are currently on the free plan.</p>
                </div>
            </div>
            <div class="mt-4">
                <p class="text-gray-300">Upgrade to premium for access to all content and features!</p>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (!$hasActiveSubscription): ?>
        <!-- Coupon Redemption -->
        <div class="mt-6">
            <h3 class="text-lg font-medium text-white mb-3">Redeem a Coupon</h3>
            <form method="post" class="flex">
                <input type="text" name="coupon_code" placeholder="Enter coupon code" 
                       class="flex-grow px-4 py-2 rounded-l-lg bg-gray-700 text-white border border-gray-600 focus:outline-none focus:border-purple-500">
                <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white font-medium py-2 px-6 rounded-r-lg transition-colors">
                    Redeem
                </button>
            </form>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Subscription Plans -->
    <div class="bg-gray-800 rounded-lg p-6">
        <h2 class="text-xl font-bold text-white mb-6">Subscription Plans</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Free Plan -->
            <div class="bg-gray-700 rounded-lg overflow-hidden border border-gray-600">
                <div class="bg-gray-800 p-6">
                    <h3 class="text-lg font-bold text-white">Free</h3>
                    <div class="mt-2 mb-1">
                        <span class="text-2xl font-bold text-white">$0</span>
                        <span class="text-gray-400">/month</span>
                    </div>
                </div>
                <div class="p-6">
                    <ul class="space-y-2 mb-6">
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mt-0.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span class="text-gray-300">Access to free content</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mt-0.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span class="text-gray-300">Create watchlist</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-red-500 mt-0.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            <span class="text-gray-300">Ads while watching</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-red-500 mt-0.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            <span class="text-gray-300">No premium content</span>
                        </li>
                    </ul>
                    <div class="text-center">
                        <span class="inline-block bg-gray-600 text-gray-300 px-4 py-2 rounded-lg">Current Plan</span>
                    </div>
                </div>
            </div>
            
            <!-- Premium Plan -->
            <div class="bg-gray-700 rounded-lg overflow-hidden border-2 border-purple-600 transform scale-105 z-10 shadow-lg">
                <div class="bg-gradient-to-r from-purple-600 to-pink-600 p-6">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-bold text-white">Premium</h3>
                        <span class="bg-yellow-400 text-black text-xs font-bold px-2 py-1 rounded">POPULAR</span>
                    </div>
                    <div class="mt-2 mb-1">
                        <span class="text-2xl font-bold text-white">$9.99</span>
                        <span class="text-gray-200">/month</span>
                    </div>
                </div>
                <div class="p-6">
                    <ul class="space-y-2 mb-6">
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mt-0.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span class="text-gray-300">Access to all content</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mt-0.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span class="text-gray-300">No ads</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mt-0.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span class="text-gray-300">Early access to new episodes</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mt-0.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span class="text-gray-300">HD streaming quality</span>
                        </li>
                    </ul>
                    <div class="text-center">
                        <?php if ($hasActiveSubscription): ?>
                        <span class="inline-block bg-purple-600 text-white px-4 py-2 rounded-lg">Current Plan</span>
                        <?php else: ?>
                        <div class="flex justify-center">
                            <a href="#" class="bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white font-medium py-2 px-6 rounded-lg transition-colors">
                                Subscribe
                            </a>
                        </div>
                        <p class="text-sm text-gray-400 mt-2">Or use a coupon code above</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Ultimate Plan -->
            <div class="bg-gray-700 rounded-lg overflow-hidden border border-gray-600">
                <div class="bg-gray-800 p-6">
                    <h3 class="text-lg font-bold text-white">Ultimate</h3>
                    <div class="mt-2 mb-1">
                        <span class="text-2xl font-bold text-white">$14.99</span>
                        <span class="text-gray-400">/month</span>
                    </div>
                </div>
                <div class="p-6">
                    <ul class="space-y-2 mb-6">
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mt-0.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span class="text-gray-300">All Premium features</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mt-0.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span class="text-gray-300">4K streaming quality</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mt-0.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span class="text-gray-300">Offline downloads</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mt-0.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span class="text-gray-300">Priority customer support</span>
                        </li>
                    </ul>
                    <div class="text-center">
                        <a href="#" class="inline-block bg-gray-600 hover:bg-gray-500 text-white font-medium py-2 px-6 rounded-lg transition-colors">
                            Coming Soon
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php
include 'includes/footer.php';
?> 