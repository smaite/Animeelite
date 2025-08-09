<?php
// Profile page - view and edit user information
session_start();
require_once 'config.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?redirect=profile.php");
    exit();
}

// Initialize variables
$userData = null;
$successMessage = '';
$errorMessage = '';
$watchCount = 0;
$favoritesCount = 0;
$subscription = [
    'status' => 'free',
    'expires' => null,
    'plan' => 'Free'
];

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get user data
    $stmt = $pdo->prepare("SELECT id, username, email, display_name, role, avatar, created_at, subscription, subscription_expires FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$userData) {
        $errorMessage = "User data could not be found. Please contact support.";
    } else {
        // Handle form submission for profile update
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
            $displayName = trim($_POST['display_name']);
            $currentPassword = $_POST['current_password'];
            $newPassword = $_POST['new_password'];
            $confirmPassword = $_POST['confirm_password'];
            
            // Basic validation
            if (empty($displayName)) {
                $errorMessage = "Display name cannot be empty";
            } else {
                // Start transaction
                $pdo->beginTransaction();
                
                // Check if changing password
                if (!empty($currentPassword) && !empty($newPassword)) {
                    // Verify current password
                    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
                    $stmt->execute([$_SESSION['user_id']]);
                    $storedHash = $stmt->fetchColumn();
                    
                    if (password_verify($currentPassword, $storedHash)) {
                        // Check if new passwords match
                        if ($newPassword === $confirmPassword) {
                            // Update password
                            $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
                            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                            $stmt->execute([$passwordHash, $_SESSION['user_id']]);
                        } else {
                            $errorMessage = "New passwords do not match";
                            $pdo->rollBack();
                        }
                    } else {
                        $errorMessage = "Current password is incorrect";
                        $pdo->rollBack();
                    }
                }
                
                // If no error updating password (or not updating password)
                if (empty($errorMessage)) {
                    // Update profile
                    $stmt = $pdo->prepare("UPDATE users SET display_name = ? WHERE id = ?");
                    $stmt->execute([$displayName, $_SESSION['user_id']]);
                    
                    // Handle avatar upload if provided
                    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                        $maxFileSize = 2 * 1024 * 1024; // 2MB
                        
                        if (!in_array($_FILES['avatar']['type'], $allowedTypes)) {
                            $errorMessage = "Invalid file type. Only JPG, PNG, and GIF are allowed.";
                        } elseif ($_FILES['avatar']['size'] > $maxFileSize) {
                            $errorMessage = "File is too large. Maximum size is 2MB.";
                        } else {
                            $uploadDir = 'uploads/avatars/';
                            
                            // Create directory if it doesn't exist
                            if (!is_dir($uploadDir)) {
                                mkdir($uploadDir, 0755, true);
                            }
                            
                            // Generate unique filename
                            $fileExtension = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
                            $fileName = 'avatar_' . $_SESSION['user_id'] . '_' . time() . '.' . $fileExtension;
                            $targetPath = $uploadDir . $fileName;
                            
                            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $targetPath)) {
                                // Update avatar in database
                                $avatarUrl = $targetPath;
                                $stmt = $pdo->prepare("UPDATE users SET avatar = ? WHERE id = ?");
                                $stmt->execute([$avatarUrl, $_SESSION['user_id']]);
                            } else {
                                $errorMessage = "Failed to upload avatar. Please try again.";
                            }
                        }
                    }
                    
                    if (empty($errorMessage)) {
                        $pdo->commit();
                        $successMessage = "Profile updated successfully!";
                        
                        // Refresh user data
                        $stmt = $pdo->prepare("SELECT id, username, email, display_name, role, avatar, created_at, subscription, subscription_expires FROM users WHERE id = ?");
                        $stmt->execute([$_SESSION['user_id']]);
                        $userData = $stmt->fetch(PDO::FETCH_ASSOC);
                    } else {
                        $pdo->rollBack();
                    }
                }
            }
        }
        
        // Get subscription status
        if (isset($userData['subscription']) && $userData['subscription'] !== 'free') {
            $subscription['status'] = $userData['subscription'];
            $subscription['expires'] = $userData['subscription_expires'];
            $subscription['plan'] = ucfirst($userData['subscription']);
            
            // Check if expired
            if ($subscription['expires'] && strtotime($subscription['expires']) < time()) {
                $subscription['status'] = 'expired';
            }
        }
        
        // Get watch stats
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM watch_history WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $watchCount = $stmt->fetchColumn();
        
        // Get favorites count
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM favorites WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $favoritesCount = $stmt->fetchColumn();
    }
} catch (PDOException $e) {
    $errorMessage = "Database error: " . $e->getMessage();
}

$pageTitle = "My Profile - AnimeElite";
include 'includes/header.php';
?>

<main class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold text-white mb-6">My Profile</h1>
        
        <?php if (!empty($errorMessage)): ?>
        <div class="bg-red-900 text-white p-4 rounded-lg mb-6">
            <p><?= htmlspecialchars($errorMessage) ?></p>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($successMessage)): ?>
        <div class="bg-green-900 text-white p-4 rounded-lg mb-6">
            <p><?= htmlspecialchars($successMessage) ?></p>
        </div>
        <?php endif; ?>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Left column: User info -->
            <div class="md:col-span-1">
                <div class="bg-gray-800 rounded-lg p-6 mb-6">
                    <div class="flex flex-col items-center mb-4">
                        <?php if ($userData && isset($userData['avatar']) && $userData['avatar']): ?>
                            <img src="<?= htmlspecialchars($userData['avatar']) ?>" alt="Avatar" class="w-32 h-32 rounded-full mb-4">
                        <?php else: ?>
                            <div class="w-32 h-32 rounded-full bg-gradient-to-r from-purple-600 to-pink-600 flex items-center justify-center mb-4">
                                <span class="text-white text-4xl font-medium">
                                    <?= $userData && isset($userData['display_name']) && !empty($userData['display_name']) ? 
                                        substr($userData['display_name'], 0, 1) : 
                                        ($userData && isset($userData['username']) ? substr($userData['username'], 0, 1) : 'U') ?>
                                </span>
                            </div>
                        <?php endif; ?>
                        <h2 class="text-xl font-bold text-white"><?= $userData ? htmlspecialchars($userData['display_name'] ?? $userData['username'] ?? 'User') : 'User' ?></h2>
                        <p class="text-gray-400"><?= $userData ? htmlspecialchars($userData['email'] ?? '') : '' ?></p>
                        <p class="text-gray-400 mt-1">Member since <?= $userData && isset($userData['created_at']) ? date('F Y', strtotime($userData['created_at'])) : 'N/A' ?></p>
                    </div>
                    
                    <div class="border-t border-gray-700 pt-4 mt-4">
                        <h3 class="text-lg font-medium text-white mb-2">Account Stats</h3>
                        <div class="grid grid-cols-2 gap-3 text-center">
                            <div class="bg-gray-700 p-3 rounded-lg">
                                <div class="text-xl font-bold text-purple-400"><?= $watchCount ?></div>
                                <div class="text-sm text-gray-400">Watched</div>
                            </div>
                            <div class="bg-gray-700 p-3 rounded-lg">
                                <div class="text-xl font-bold text-purple-400"><?= $favoritesCount ?></div>
                                <div class="text-sm text-gray-400">Favorites</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gray-800 rounded-lg p-6">
                    <h3 class="text-lg font-medium text-white mb-2">Subscription</h3>
                    <div class="flex items-center mb-2">
                        <span class="text-gray-300 mr-2">Plan:</span>
                        <span class="<?= isset($subscription['status']) && $subscription['status'] === 'premium' ? 'text-yellow-400' : 'text-white' ?>">
                            <?= isset($subscription['plan']) ? $subscription['plan'] : 'Free' ?>
                        </span>
                    </div>
                    
                    <?php if (isset($subscription['expires']) && $subscription['expires']): ?>
                    <div class="flex items-center mb-4">
                        <span class="text-gray-300 mr-2">Expires:</span>
                        <span class="<?= isset($subscription['status']) && $subscription['status'] === 'expired' ? 'text-red-400' : 'text-white' ?>">
                            <?= date('F j, Y', strtotime($subscription['expires'])) ?>
                        </span>
                    </div>
                    <?php endif; ?>
                    
                    <a href="subscription_page.php" class="w-full block text-center bg-purple-600 hover:bg-purple-700 text-white font-medium py-2 px-4 rounded-lg transition-colors">
                        <?= isset($subscription['status']) && $subscription['status'] === 'premium' ? 'Manage Subscription' : 'Upgrade to Premium' ?>
                    </a>
                </div>
            </div>
            
            <!-- Right column: Edit profile form -->
            <div class="md:col-span-2">
                <div class="bg-gray-800 rounded-lg p-6">
                    <h2 class="text-xl font-medium text-white mb-4">Edit Profile</h2>
                    
                    <form action="profile.php" method="post" enctype="multipart/form-data">
                        <!-- Username (non-editable) -->
                        <div class="mb-4">
                            <label for="username" class="block text-gray-300 mb-2">Username</label>
                            <input type="text" id="username" name="username" value="<?= $userData ? htmlspecialchars($userData['username'] ?? '') : '' ?>" 
                                   class="w-full px-4 py-2 rounded-md bg-gray-700 text-white border border-gray-600 focus:outline-none focus:border-purple-500" 
                                   disabled>
                        </div>
                        
                        <!-- Email (non-editable) -->
                        <div class="mb-4">
                            <label for="email" class="block text-gray-300 mb-2">Email</label>
                            <input type="email" id="email" name="email" value="<?= $userData ? htmlspecialchars($userData['email'] ?? '') : '' ?>" 
                                   class="w-full px-4 py-2 rounded-md bg-gray-700 text-white border border-gray-600 focus:outline-none focus:border-purple-500" 
                                   disabled>
                        </div>
                        
                        <!-- Display name -->
                        <div class="mb-4">
                            <label for="display_name" class="block text-gray-300 mb-2">Display Name</label>
                            <input type="text" id="display_name" name="display_name" value="<?= $userData ? htmlspecialchars($userData['display_name'] ?? '') : '' ?>" 
                                   class="w-full px-4 py-2 rounded-md bg-gray-700 text-white border border-gray-600 focus:outline-none focus:border-purple-500" 
                                   required>
                        </div>
                        
                        <!-- Avatar upload -->
                        <div class="mb-6">
                            <label for="avatar" class="block text-gray-300 mb-2">Profile Picture</label>
                            <input type="file" id="avatar" name="avatar" accept="image/jpeg,image/png,image/gif" 
                                   class="w-full px-4 py-2 rounded-md bg-gray-700 text-white border border-gray-600 focus:outline-none focus:border-purple-500">
                            <p class="text-xs text-gray-400 mt-1">Max file size: 2MB. Allowed formats: JPG, PNG, GIF.</p>
                        </div>
                        
                        <h3 class="text-lg font-medium text-white mb-3">Change Password</h3>
                        <p class="text-sm text-gray-400 mb-4">Leave these fields empty if you don't want to change your password.</p>
                        
                        <!-- Current password -->
                        <div class="mb-4">
                            <label for="current_password" class="block text-gray-300 mb-2">Current Password</label>
                            <input type="password" id="current_password" name="current_password" 
                                   class="w-full px-4 py-2 rounded-md bg-gray-700 text-white border border-gray-600 focus:outline-none focus:border-purple-500">
                        </div>
                        
                        <!-- New password -->
                        <div class="mb-4">
                            <label for="new_password" class="block text-gray-300 mb-2">New Password</label>
                            <input type="password" id="new_password" name="new_password" 
                                   class="w-full px-4 py-2 rounded-md bg-gray-700 text-white border border-gray-600 focus:outline-none focus:border-purple-500">
                        </div>
                        
                        <!-- Confirm password -->
                        <div class="mb-6">
                            <label for="confirm_password" class="block text-gray-300 mb-2">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" 
                                   class="w-full px-4 py-2 rounded-md bg-gray-700 text-white border border-gray-600 focus:outline-none focus:border-purple-500">
                        </div>
                        
                        <div class="flex justify-end">
                            <button type="submit" name="update_profile" 
                                    class="bg-purple-600 hover:bg-purple-700 text-white font-medium py-2 px-6 rounded-lg transition-colors">
                                Update Profile
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>

<?php
include 'includes/footer.php';
?> 