<?php
// Watch History page - displays user's viewing history
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

// Get watch history with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 12; // Items per page
$offset = ($page - 1) * $limit;

$history = [];
$totalEntries = 0;

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Count total history entries
    $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM watch_history WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalEntries = $result['total'];
    
    // Get watch history for current page with episode and anime details
    $stmt = $pdo->prepare("
        SELECT wh.*, e.title as episode_title, e.episode_number, e.thumbnail, e.duration,
               s.season_number, s.title as season_title,
               a.id as anime_id, a.title as anime_title, a.cover_image 
        FROM watch_history wh
        JOIN episodes e ON wh.episode_id = e.id
        JOIN seasons s ON e.season_id = s.id
        JOIN anime a ON s.anime_id = a.id
        WHERE wh.user_id = ?
        ORDER BY wh.watched_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->bindParam(1, $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->bindParam(2, $limit, PDO::PARAM_INT);
    $stmt->bindParam(3, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

// Calculate pagination
$totalPages = ceil($totalEntries / $limit);

$pageTitle = "Watch History - AnimeElite";
include 'includes/header.php';
?>

<main class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-white">My Watch History</h1>
        
        <?php if (!empty($history)): ?>
        <form method="post" onsubmit="return confirm('Are you sure you want to clear your entire watch history? This cannot be undone.');">
            <button type="submit" name="clear_history" value="all" 
                    class="bg-red-600 hover:bg-red-700 text-white font-medium py-2 px-4 rounded-lg transition-colors">
                Clear History
            </button>
        </form>
        <?php endif; ?>
    </div>
    
    <?php if (isset($error) && !empty($error)): ?>
    <div class="bg-red-900 text-white p-4 rounded-lg mb-6">
        <p><?= htmlspecialchars($error) ?></p>
    </div>
    <?php endif; ?>
    
    <?php if (isset($successMessage) && !empty($successMessage)): ?>
    <div class="bg-green-900 text-white p-4 rounded-lg mb-6">
        <p><?= htmlspecialchars($successMessage) ?></p>
    </div>
    <?php endif; ?>
    
    <?php if (empty($history)): ?>
    <div class="bg-gray-800 text-white p-8 rounded-lg text-center">
        <svg class="mx-auto h-16 w-16 text-gray-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <h2 class="text-2xl font-bold mb-2">No Watch History</h2>
        <p class="text-gray-400 mb-6">You haven't watched any anime episodes yet.</p>
        <a href="browse.php" class="inline-block bg-purple-600 hover:bg-purple-700 text-white font-medium py-2 px-6 rounded-lg transition-colors">
            Browse Anime
        </a>
    </div>
    <?php else: ?>
    
    <!-- History list -->
    <div class="grid gap-4 mb-8">
        <?php foreach ($history as $entry): ?>
        <div class="bg-gray-800 rounded-lg overflow-hidden flex flex-col sm:flex-row">
            <div class="sm:w-48 h-32 sm:h-auto relative flex-shrink-0">
                <a href="player.php?anime=<?= $entry['anime_id'] ?>&episode=<?= $entry['episode_id'] ?>">
                    <?php if ($entry['thumbnail']): ?>
                    <img src="<?= htmlspecialchars($entry['thumbnail']) ?>" alt="Episode thumbnail" class="w-full h-full object-cover">
                    <?php elseif ($entry['cover_image']): ?>
                    <img src="<?= htmlspecialchars($entry['cover_image']) ?>" alt="Anime cover" class="w-full h-full object-cover">
                    <?php else: ?>
                    <div class="w-full h-full bg-gray-700 flex items-center justify-center">
                        <span class="text-gray-500">No image</span>
                    </div>
                    <?php endif; ?>
                    <div class="absolute inset-0 bg-gradient-to-t from-black to-transparent opacity-60"></div>
                    <div class="absolute bottom-2 right-2 bg-black bg-opacity-75 text-white text-xs px-2 py-1 rounded">
                        <?= $entry['duration'] ? $entry['duration'] . ' min' : '' ?>
                    </div>
                </a>
            </div>
            <div class="p-4 flex-grow flex flex-col justify-between">
                <div>
                    <h3 class="text-lg font-bold text-white mb-1">
                        <a href="anime.php?id=<?= $entry['anime_id'] ?>" class="hover:text-purple-400 transition-colors">
                            <?= htmlspecialchars($entry['anime_title']) ?>
                        </a>
                    </h3>
                    <p class="text-gray-300 mb-1">
                        Season <?= $entry['season_number'] ?>, Episode <?= $entry['episode_number'] ?>: 
                        <?= htmlspecialchars($entry['episode_title']) ?>
                    </p>
                    <p class="text-sm text-gray-400">Watched on <?= date('F j, Y \a\t g:i a', strtotime($entry['watched_at'])) ?></p>
                </div>
                <div class="mt-4 flex justify-between items-center">
                    <a href="player.php?anime=<?= $entry['anime_id'] ?>&episode=<?= $entry['episode_id'] ?>" 
                       class="inline-flex items-center bg-purple-600 hover:bg-purple-700 text-white font-medium py-1 px-3 rounded transition-colors">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Continue Watching
                    </a>
                    <form method="post" class="inline-block">
                        <input type="hidden" name="history_id" value="<?= $entry['id'] ?>">
                        <button type="submit" name="remove_entry" value="<?= $entry['id'] ?>" 
                                class="text-gray-400 hover:text-red-500 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="flex justify-center mt-8">
        <div class="flex space-x-2">
            <!-- Previous page -->
            <?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1 ?>" class="bg-gray-700 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
            </a>
            <?php else: ?>
            <button disabled class="bg-gray-700 opacity-50 cursor-not-allowed text-white px-4 py-2 rounded-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
            </button>
            <?php endif; ?>
            
            <!-- Page numbers -->
            <?php
            $startPage = max(1, min($page - 2, $totalPages - 4));
            $endPage = min($totalPages, max(5, $page + 2));
            
            for ($i = $startPage; $i <= $endPage; $i++):
            ?>
                <?php if ($i == $page): ?>
                <span class="bg-purple-600 text-white px-4 py-2 rounded-lg"><?= $i ?></span>
                <?php else: ?>
                <a href="?page=<?= $i ?>" class="bg-gray-700 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition-colors"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            
            <!-- Next page -->
            <?php if ($page < $totalPages): ?>
            <a href="?page=<?= $page + 1 ?>" class="bg-gray-700 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </a>
            <?php else: ?>
            <button disabled class="bg-gray-700 opacity-50 cursor-not-allowed text-white px-4 py-2 rounded-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </button>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</main>

<?php
include 'includes/footer.php';
?> 