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

// Get continue watching data - latest episode from each anime
$continueWatching = [];

try {
    // Get the latest watched episode for each anime
    $stmt = $pdo->prepare("
        SELECT wh.*, e.title as episode_title, e.episode_number, e.thumbnail, e.duration,
               s.season_number, s.title as season_title,
               a.id as anime_id, a.title as anime_title, a.cover_image,
               wh.position_seconds, wh.is_completed
        FROM watch_history wh
        JOIN episodes e ON wh.episode_id = e.id
        JOIN seasons s ON e.season_id = s.id
        JOIN anime a ON s.anime_id = a.id
        WHERE wh.user_id = ? 
          AND wh.id IN (
              SELECT MAX(wh2.id) 
              FROM watch_history wh2 
              JOIN episodes e2 ON wh2.episode_id = e2.id
              JOIN seasons s2 ON e2.season_id = s2.id
              WHERE wh2.user_id = ? 
              GROUP BY s2.anime_id
          )
        ORDER BY wh.watched_at DESC
    ");
    $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
    $continueWatching = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

$pageTitle = "Continue Watching - AnimeElite";
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
            <h1 class="text-3xl font-bold text-white mb-2">Continue Watching</h1>
            <p class="text-gray-400">Pick up where you left off with each anime</p>
        </div>
        
        <?php if (!empty($continueWatching)): ?>
        <form method="POST" onsubmit="return confirm('Are you sure you want to clear all your watch history?');">
            <button type="submit" name="clear_history" value="all" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm transition-colors">
                Clear All History
            </button>
        </form>
        <?php endif; ?>
    </div>

    <?php if (empty($continueWatching)): ?>
    <div class="text-center py-12">
        <div class="bg-gray-800 rounded-lg p-8 max-w-md mx-auto">
            <h3 class="text-xl font-medium text-white mb-4">No anime in progress</h3>
            <p class="text-gray-400 mb-6">Start watching some episodes to see your progress here!</p>
            <a href="browse.php" class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-3 rounded-lg font-medium transition-colors">
                Browse Anime
            </a>
        </div>
    </div>
    <?php else: ?>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($continueWatching as $item): ?>
        <?php 
        $episodeDuration = $item['duration'] ? intval($item['duration']) * 60 : 1440;
        $progressPercentage = $episodeDuration > 0 ? min(($item['position_seconds'] / $episodeDuration) * 100, 100) : 0;
        $isCompleted = $item['is_completed'];
        ?>
        
        <div class="bg-gray-800 rounded-lg overflow-hidden transition-transform duration-300 hover:-translate-y-2 hover:shadow-lg">
            <a href="player.php?anime=<?= $item['anime_id'] ?>&episode=<?= $item['episode_id'] ?>">
                <div class="relative">
                    <div class="aspect-h-9 h-48">
                        <?php if ($item['cover_image']): ?>
                            <img src="<?= htmlspecialchars($item['cover_image']) ?>" alt="<?= htmlspecialchars($item['anime_title']) ?>" class="object-cover w-full h-full">
                        <?php else: ?>
                            <div class="w-full h-full bg-gray-700 flex items-center justify-center">
                                <span class="text-gray-400">No Image</span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Progress overlay -->
                    <?php if (!$isCompleted && $item['position_seconds'] > 0): ?>
                    <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black to-transparent p-4">
                        <div class="w-full bg-gray-600 rounded-full h-1.5 mb-2">
                            <div class="bg-purple-500 h-1.5 rounded-full" style="width: <?= $progressPercentage ?>%"></div>
                        </div>
                        <span class="text-xs text-white font-medium"><?= round($progressPercentage, 1) ?>% watched</span>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Status badge -->
                    <div class="absolute top-4 right-4">
                        <?php if ($isCompleted): ?>
                        <span class="bg-green-600 text-white text-xs px-2 py-1 rounded-full font-medium">
                            COMPLETED
                        </span>
                        <?php elseif ($item['position_seconds'] > 0): ?>
                        <span class="bg-purple-600 text-white text-xs px-2 py-1 rounded-full font-medium">
                            CONTINUE
                        </span>
                        <?php else: ?>
                        <span class="bg-blue-600 text-white text-xs px-2 py-1 rounded-full font-medium">
                            START
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="p-4">
                    <h3 class="text-lg font-medium text-white mb-1 truncate"><?= htmlspecialchars($item['anime_title']) ?></h3>
                    <p class="text-sm text-gray-400 mb-2">Season <?= $item['season_number'] ?>, Episode <?= $item['episode_number'] ?></p>
                    <p class="text-sm text-gray-300 truncate"><?= htmlspecialchars($item['episode_title']) ?></p>
                    
                    <div class="mt-3 flex justify-between items-center text-xs text-gray-500">
                        <span>
                            <?php if ($isCompleted): ?>
                                Completed
                            <?php elseif ($item['position_seconds'] > 0): ?>
                                Resume at <?= gmdate("i:s", $item['position_seconds']) ?>
                            <?php else: ?>
                                Not started
                            <?php endif; ?>
                        </span>
                        <span>
                            <?= date('M j', strtotime($item['watched_at'])) ?>
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