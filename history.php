<?php
// Continue Watching page - displays user's latest episodes from each anime they're watching
session_start();
require_once 'config.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?redirect=history.php");
    exit();
}

// Initialize variables
$userData = null;
$error = '';

// Get user data
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->prepare("SELECT id, username, email, display_name, role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

// Handle clear history action
if (isset($_POST['clear_history']) && $_POST['clear_history'] === 'all') {
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stmt = $pdo->prepare("DELETE FROM watch_history WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        
        $successMessage = "Your watch history has been cleared.";
    } catch (PDOException $e) {
        $error = "Failed to clear history: " . $e->getMessage();
    }
}

// Get anime watch statistics - completion percentage per anime
$animeStats = [];

try {
    // Get all anime the user has watched with completion stats
    $stmt = $pdo->prepare("
        SELECT 
            a.id as anime_id,
            a.title as anime_title,
            a.cover_image,
            COUNT(DISTINCT e.id) as total_episodes,
            COUNT(DISTINCT wh.episode_id) as watched_episodes,
            ROUND((COUNT(DISTINCT wh.episode_id) / COUNT(DISTINCT e.id)) * 100, 1) as completion_percentage,
            MAX(wh.watched_at) as last_watched
        FROM anime a
        JOIN seasons s ON a.id = s.anime_id
        JOIN episodes e ON s.id = e.season_id
        LEFT JOIN watch_history wh ON e.id = wh.episode_id AND wh.user_id = ? AND wh.is_completed = 1
        WHERE a.id IN (
            SELECT DISTINCT s2.anime_id 
            FROM watch_history wh2 
            JOIN episodes e2 ON wh2.episode_id = e2.id 
            JOIN seasons s2 ON e2.season_id = s2.id 
            WHERE wh2.user_id = ? AND wh2.is_completed = 1
        )
        GROUP BY a.id, a.title, a.cover_image
        ORDER BY last_watched DESC
    ");
    $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
    $animeStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

$pageTitle = "My Anime Progress - AnimeElite";
include 'includes/header.php';
?>

<main class="container mx-auto px-4 py-8">
    <?php if ($error): ?>
    <div class="bg-red-900 text-white p-4 rounded-lg mb-4">
        <h3 class="font-bold mb-2">Error</h3>
        <p><?= htmlspecialchars($error) ?></p>
    </div>
    <?php endif; ?>

    <?php if (isset($successMessage)): ?>
    <div class="bg-green-900 text-white p-4 rounded-lg mb-4">
        <p><?= htmlspecialchars($successMessage) ?></p>
    </div>
    <?php endif; ?>

    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-white mb-2">My Anime Progress</h1>
            <p class="text-gray-400">Track your completion progress for each anime</p>
        </div>
        
        <?php if (!empty($animeStats)): ?>
        <form method="POST" onsubmit="return confirm('Are you sure you want to clear all your watch history?');">
            <button type="submit" name="clear_history" value="all" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm transition-colors">
                Clear All History
            </button>
        </form>
        <?php endif; ?>
    </div>

    <?php if (empty($animeStats)): ?>
    <div class="text-center py-12">
        <div class="bg-gray-800 rounded-lg p-8 max-w-md mx-auto">
            <h3 class="text-xl font-medium text-white mb-4">No anime watched yet</h3>
            <p class="text-gray-400 mb-6">Start watching some episodes to see your progress here!</p>
            <a href="browse.php" class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-3 rounded-lg font-medium transition-colors">
                Browse Anime
            </a>
        </div>
    </div>
    <?php else: ?>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($animeStats as $anime): ?>
        <div class="bg-gray-800 rounded-lg overflow-hidden transition-transform duration-300 hover:-translate-y-2 hover:shadow-lg">
            <a href="anime.php?id=<?= $anime['anime_id'] ?>">
                <div class="relative">
                    <div class="aspect-w-16 aspect-h-9 h-48">
                        <?php if ($anime['cover_image']): ?>
                            <img src="<?= htmlspecialchars($anime['cover_image']) ?>" alt="<?= htmlspecialchars($anime['anime_title']) ?>" class="object-cover w-full h-full">
                        <?php else: ?>
                            <div class="w-full h-full bg-gray-700 flex items-center justify-center">
                                <span class="text-gray-400">No Image</span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Completion percentage overlay -->
                    <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black to-transparent p-4">
                        <div class="w-full bg-gray-600 rounded-full h-2 mb-2">
                            <div class="bg-gradient-to-r from-purple-500 to-pink-500 h-2 rounded-full transition-all duration-300" 
                                 style="width: <?= $anime['completion_percentage'] ?>%"></div>
                        </div>
                        <span class="text-xs text-white font-medium"><?= $anime['completion_percentage'] ?>% Complete</span>
                    </div>
                    
                    <!-- Status badge -->
                    <div class="absolute top-4 right-4">
                        <?php if ($anime['completion_percentage'] == 100): ?>
                        <span class="bg-green-600 text-white text-xs px-2 py-1 rounded-full font-medium">
                            COMPLETED
                        </span>
                        <?php else: ?>
                        <span class="bg-purple-600 text-white text-xs px-2 py-1 rounded-full font-medium">
                            WATCHING
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="p-4">
                    <h3 class="text-lg font-medium text-white mb-2 truncate"><?= htmlspecialchars($anime['anime_title']) ?></h3>
                    
                    <div class="flex justify-between items-center text-sm text-gray-400 mb-2">
                        <span><?= $anime['watched_episodes'] ?>/<?= $anime['total_episodes'] ?> episodes</span>
                        <span><?= $anime['completion_percentage'] ?>%</span>
                    </div>
                    
                    <div class="flex justify-between items-center text-xs text-gray-500">
                        <span>
                            <?php if ($anime['completion_percentage'] == 100): ?>
                                Completed
                            <?php else: ?>
                                In Progress
                            <?php endif; ?>
                        </span>
                        <span>
                            <?= $anime['last_watched'] ? date('M j', strtotime($anime['last_watched'])) : 'Unknown' ?>
                        </span>
                    </div>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</main>

<?php include 'includes/footer.php'; ?> 