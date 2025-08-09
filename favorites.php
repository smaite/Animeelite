<?php
// Favorites page - displays user's favorite anime
session_start();
require_once 'config.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?redirect=favorites.php");
    exit();
}

// Get user data
$userData = null;
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->prepare("SELECT id, username, email, display_name, role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

// Get favorites with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 12; // Items per page
$offset = ($page - 1) * $limit;

$favorites = [];
$totalFavorites = 0;

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Count total favorites
    $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM favorites f JOIN anime a ON f.anime_id = a.id WHERE f.user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalFavorites = $result['total'];
    
    // Get favorites for current page with anime details
    $stmt = $pdo->prepare("SELECT a.*, f.added_at 
                          FROM favorites f 
                          JOIN anime a ON f.anime_id = a.id 
                          WHERE f.user_id = ? 
                          ORDER BY f.added_at DESC
                          LIMIT ? OFFSET ?");
    $stmt->bindParam(1, $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->bindParam(2, $limit, PDO::PARAM_INT);
    $stmt->bindParam(3, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $favorites = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

// Calculate pagination
$totalPages = ceil($totalFavorites / $limit);

$pageTitle = "My Favorites - AnimeElite";
include 'includes/header.php';
?>

<main class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-white mb-6">My Favorites</h1>
    
    <?php if (isset($error)): ?>
    <div class="bg-red-900 text-white p-4 rounded-lg mb-6">
        <p><?= htmlspecialchars($error) ?></p>
    </div>
    <?php endif; ?>
    
    <?php if (empty($favorites)): ?>
    <div class="bg-gray-800 text-white p-8 rounded-lg text-center">
        <svg class="mx-auto h-16 w-16 text-gray-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
        </svg>
        <h2 class="text-2xl font-bold mb-2">No Favorites Yet</h2>
        <p class="text-gray-400 mb-6">You haven't added any anime to your favorites list yet.</p>
        <a href="browse.php" class="inline-block bg-purple-600 hover:bg-purple-700 text-white font-medium py-2 px-6 rounded-lg transition-colors">
            Browse Anime
        </a>
    </div>
    <?php else: ?>
    
    <!-- Favorites grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6 mb-8">
        <?php foreach ($favorites as $anime): ?>
        <div class="bg-gray-800 rounded-lg overflow-hidden transition-transform hover:scale-105">
            <a href="anime.php?id=<?= $anime['id'] ?>" class="block">
                <div class="relative aspect-w-3 aspect-h-4">
                    <?php if ($anime['cover_image']): ?>
                    <img src="<?= htmlspecialchars($anime['cover_image']) ?>" alt="<?= htmlspecialchars($anime['title']) ?>" class="w-full h-full object-cover">
                    <?php else: ?>
                    <div class="w-full h-full bg-gray-700 flex items-center justify-center">
                        <span class="text-gray-500">No image</span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($anime['is_premium'] == 1): ?>
                    <div class="absolute top-2 right-2 bg-yellow-500 text-xs font-bold px-2 py-1 rounded text-black">PREMIUM</div>
                    <?php endif; ?>
                </div>
                
                <div class="p-4">
                    <h2 class="text-lg font-bold text-white mb-1 truncate"><?= htmlspecialchars($anime['title']) ?></h2>
                    <div class="flex items-center text-sm text-gray-400 mb-2">
                        <?php if ($anime['release_year']): ?>
                        <span><?= $anime['release_year'] ?></span>
                        <?php endif; ?>
                        <?php if ($anime['status']): ?>
                        <span class="mx-2">•</span>
                        <span><?= htmlspecialchars(ucfirst($anime['status'])) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center text-sm">
                            <span class="text-yellow-400 mr-1">★</span>
                            <span class="text-white"><?= $anime['rating'] ?? '?' ?></span>
                        </div>
                        <button class="remove-favorite text-gray-400 hover:text-red-500" data-id="<?= $anime['id'] ?>"
                            onclick="toggleFavorite(<?= $anime['id'] ?>)">
                            <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24">
                                <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </a>
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

<script>
function toggleFavorite(animeId) {
    // Send request to toggle favorite
    fetch('api/toggle_favorite.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            anime_id: animeId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Refresh the page to show updated favorites
            location.reload();
        } else {
            console.error('Error toggling favorite:', data.message);
        }
    })
    .catch(error => {
        console.error('Error toggling favorite:', error);
    });
}
</script>

<?php
include 'includes/footer.php';
?> 