<?php
// Admin dashboard main page
require_once 'includes/header.php';

// Initialize stats
$total_anime = 0;
$total_users = 0;
$total_premium_users = 0;
$total_episodes = 0;
$recent_users = [];
$recent_anime = [];

try {
    // Get total anime count
    $stmt = $pdo->query("SELECT COUNT(*) FROM anime");
    $total_anime = $stmt->fetchColumn();
    
    // Get total users count
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $total_users = $stmt->fetchColumn();
    
    // Get premium users count
    $stmt = $pdo->query("SELECT COUNT(DISTINCT user_id) FROM subscriptions WHERE status = 'active' AND end_date > NOW()");
    $total_premium_users = $stmt->fetchColumn();
    
    // Get total episodes
    $stmt = $pdo->query("SELECT COUNT(*) FROM episodes");
    $total_episodes = $stmt->fetchColumn();
    
    // Get recent users
    $stmt = $pdo->query("SELECT id, username, email, display_name, created_at FROM users ORDER BY created_at DESC LIMIT 5");
    $recent_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get recent anime
    $stmt = $pdo->query("SELECT id, title, cover_image, release_year, created_at FROM anime ORDER BY created_at DESC LIMIT 5");
    $recent_anime = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<h1 class="text-2xl font-semibold mb-6">Dashboard</h1>

<!-- Stats Overview -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-gray-800 rounded-lg p-6 shadow-sm">
        <div class="flex justify-between items-start">
            <div>
                <h3 class="text-gray-400 text-sm font-medium mb-1">Total Anime</h3>
                <p class="text-3xl font-bold"><?= number_format($total_anime) ?></p>
            </div>
            <div class="rounded-full bg-purple-900 bg-opacity-30 p-3">
                <svg class="w-6 h-6 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h18M3 16h18"></path>
                </svg>
            </div>
        </div>
        <a href="anime_management.php" class="text-sm text-purple-400 hover:text-purple-300 mt-4 block">View all anime</a>
    </div>
    
    <div class="bg-gray-800 rounded-lg p-6 shadow-sm">
        <div class="flex justify-between items-start">
            <div>
                <h3 class="text-gray-400 text-sm font-medium mb-1">Total Users</h3>
                <p class="text-3xl font-bold"><?= number_format($total_users) ?></p>
            </div>
            <div class="rounded-full bg-blue-900 bg-opacity-30 p-3">
                <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                </svg>
            </div>
        </div>
        <a href="user_management.php" class="text-sm text-blue-400 hover:text-blue-300 mt-4 block">Manage users</a>
    </div>
    
    <div class="bg-gray-800 rounded-lg p-6 shadow-sm">
        <div class="flex justify-between items-start">
            <div>
                <h3 class="text-gray-400 text-sm font-medium mb-1">Premium Users</h3>
                <p class="text-3xl font-bold"><?= number_format($total_premium_users) ?></p>
                <?php if ($total_users > 0): ?>
                <p class="text-sm text-gray-400 mt-1"><?= round(($total_premium_users / $total_users) * 100) ?>% of users</p>
                <?php endif; ?>
            </div>
            <div class="rounded-full bg-yellow-900 bg-opacity-30 p-3">
                <svg class="w-6 h-6 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
        </div>
        <a href="coupon_management.php" class="text-sm text-yellow-400 hover:text-yellow-300 mt-4 block">Manage subscriptions</a>
    </div>
    
    <div class="bg-gray-800 rounded-lg p-6 shadow-sm">
        <div class="flex justify-between items-start">
            <div>
                <h3 class="text-gray-400 text-sm font-medium mb-1">Total Episodes</h3>
                <p class="text-3xl font-bold"><?= number_format($total_episodes) ?></p>
                <?php if ($total_anime > 0): ?>
                <p class="text-sm text-gray-400 mt-1">~<?= round($total_episodes / $total_anime) ?> per anime</p>
                <?php endif; ?>
            </div>
            <div class="rounded-full bg-green-900 bg-opacity-30 p-3">
                <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
        </div>
        <a href="anime_management.php" class="text-sm text-green-400 hover:text-green-300 mt-4 block">Manage episodes</a>
    </div>
</div>

<!-- Recent Activity Section -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
    <!-- Recent Users -->
    <div class="bg-gray-800 rounded-lg shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-700 flex justify-between items-center">
            <h2 class="text-lg font-medium">Recent Users</h2>
            <a href="user_management.php" class="text-sm text-purple-400 hover:text-purple-300">View All</a>
        </div>
        
        <div class="divide-y divide-gray-700">
            <?php if (empty($recent_users)): ?>
            <div class="p-6 text-center text-gray-400">
                No users found.
            </div>
            <?php else: ?>
                <?php foreach ($recent_users as $user): ?>
                <div class="px-6 py-4 flex items-center">
                    <div class="w-10 h-10 rounded-full bg-gray-700 flex-shrink-0 mr-4 flex items-center justify-center">
                        <span class="text-white text-sm font-medium">
                            <?= isset($user['display_name']) ? substr($user['display_name'], 0, 1) : substr($user['username'], 0, 1) ?>
                        </span>
                    </div>
                    <div class="flex-grow">
                        <div class="flex justify-between">
                            <p class="font-medium"><?= htmlspecialchars($user['display_name'] ?? $user['username']) ?></p>
                            <span class="text-sm text-gray-400">
                                <?= date('M j, Y', strtotime($user['created_at'])) ?>
                            </span>
                        </div>
                        <p class="text-sm text-gray-400"><?= htmlspecialchars($user['email']) ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Recent Anime -->
    <div class="bg-gray-800 rounded-lg shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-700 flex justify-between items-center">
            <h2 class="text-lg font-medium">Recent Anime</h2>
            <a href="anime_management.php" class="text-sm text-purple-400 hover:text-purple-300">View All</a>
        </div>
        
        <div class="divide-y divide-gray-700">
            <?php if (empty($recent_anime)): ?>
            <div class="p-6 text-center text-gray-400">
                No anime found.
            </div>
            <?php else: ?>
                <?php foreach ($recent_anime as $anime): ?>
                <div class="px-6 py-4 flex items-center">
                    <div class="w-14 h-20 rounded bg-gray-700 flex-shrink-0 mr-4 overflow-hidden">
                        <?php if ($anime['cover_image']): ?>
                        <img src="<?= htmlspecialchars($anime['cover_image']) ?>" alt="<?= htmlspecialchars($anime['title']) ?>" class="w-full h-full object-cover">
                        <?php else: ?>
                        <div class="w-full h-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="flex-grow">
                        <div class="flex justify-between">
                            <p class="font-medium"><?= htmlspecialchars($anime['title']) ?></p>
                            <span class="text-sm text-gray-400">
                                <?= date('M j, Y', strtotime($anime['created_at'])) ?>
                            </span>
                        </div>
                        <p class="text-sm text-gray-400">Released: <?= htmlspecialchars($anime['release_year'] ?? 'Unknown') ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="mt-8 bg-gray-800 rounded-lg shadow-sm p-6">
    <h2 class="text-lg font-medium mb-4">Quick Actions</h2>
    
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
        <a href="anime_management.php?action=add" class="flex items-center p-4 bg-gray-700 rounded-lg hover:bg-gray-600 transition-colors">
            <div class="rounded-full bg-purple-900 bg-opacity-30 p-3 mr-4">
                <svg class="w-6 h-6 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
            </div>
            <span>Add New Anime</span>
        </a>
        
        <a href="user_management.php?action=add" class="flex items-center p-4 bg-gray-700 rounded-lg hover:bg-gray-600 transition-colors">
            <div class="rounded-full bg-blue-900 bg-opacity-30 p-3 mr-4">
                <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                </svg>
            </div>
            <span>Add New Admin</span>
        </a>
        
        <a href="coupon_management.php?action=add" class="flex items-center p-4 bg-gray-700 rounded-lg hover:bg-gray-600 transition-colors">
            <div class="rounded-full bg-yellow-900 bg-opacity-30 p-3 mr-4">
                <svg class="w-6 h-6 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path>
                </svg>
            </div>
            <span>Create Coupon</span>
        </a>
        
        <a href="settings.php" class="flex items-center p-4 bg-gray-700 rounded-lg hover:bg-gray-600 transition-colors">
            <div class="rounded-full bg-green-900 bg-opacity-30 p-3 mr-4">
                <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
            </div>
            <span>Site Settings</span>
        </a>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 