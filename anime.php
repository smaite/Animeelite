<?php
// Anime details page
session_start();
require_once 'config.php';

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Function to get user data
function getUserData() {
    if (!isLoggedIn()) {
        return null;
    }
    
    global $host, $dbname, $username, $password;
    
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stmt = $pdo->prepare("SELECT id, username, email, display_name, avatar, role FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $user;
    } catch (PDOException $e) {
        return null;
    }
}

// Get user data if logged in
$userData = getUserData();

// Get anime ID from URL
$anime_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Initialize variables
$anime = null;
$seasons = [];
$error = '';

if (!$anime_id) {
    $error = "No anime specified.";
} else {
    try {
        // Connect to database
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Get anime details
        $stmt = $pdo->prepare("SELECT * FROM anime WHERE id = ?");
        $stmt->execute([$anime_id]);
        
        if ($stmt->rowCount() === 0) {
            $error = "Anime not found.";
        } else {
            $anime = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Get seasons for this anime - use MIN(id) to get one season per season_number
            $stmt = $pdo->prepare("SELECT *, MIN(id) as min_id FROM seasons WHERE anime_id = ? GROUP BY season_number ORDER BY season_number ASC");
            $stmt->execute([$anime_id]);
            $seasons = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
            // Get episodes for each season (including all parts)
            foreach ($seasons as &$season) {
                $stmt = $pdo->prepare("SELECT e.* FROM episodes e 
                                      JOIN seasons s ON e.season_id = s.id 
                                      WHERE s.anime_id = ? AND s.season_number = ? 
                                      ORDER BY e.episode_number");
                $stmt->execute([$anime_id, $season['season_number']]);
                $season['episodes'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            // Check if user has this anime in favorites
            $isFavorite = false;
            if ($userData) {
                $stmt = $pdo->prepare("SELECT * FROM favorites WHERE user_id = ? AND anime_id = ?");
                $stmt->execute([$userData['id'], $anime_id]);
                $isFavorite = ($stmt->rowCount() > 0);
            }
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}

// Set page title
$pageTitle = $anime ? "{$anime['title']} - AnimeElite" : "Anime Details - AnimeElite";

// Include header
include 'includes/header.php';
?>

<main class="container mx-auto px-4 py-8">
    <?php if ($error): ?>
    <div class="bg-red-900 text-white p-4 rounded-lg mb-4">
        <h3 class="font-bold mb-2">Error</h3>
        <p><?= htmlspecialchars($error) ?></p>
        <a href="index.php" class="inline-block mt-4 bg-white text-red-900 px-4 py-2 rounded-lg font-medium">Back to Home</a>
    </div>
    <?php else: ?>
    
    <!-- Anime details section -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-12">
        <!-- Anime poster (1/3 width on desktop) -->
        <div class="md:col-span-1">
            <div class="bg-gray-800 rounded-lg overflow-hidden shadow-lg">
                <?php if ($anime['cover_image']): ?>
                <img src="<?= htmlspecialchars($anime['cover_image']) ?>" alt="<?= htmlspecialchars($anime['title']) ?>" class="w-full object-cover">
                <?php else: ?>
                <div class="w-full aspect-[2/3] bg-gray-700 flex items-center justify-center">
                    <span class="text-gray-400">No Image</span>
                </div>
                <?php endif; ?>
                
                <div class="p-4">
                    <div class="flex justify-between items-center mb-4">
                        <span class="bg-purple-600 text-white text-xs px-3 py-1 rounded"><?= htmlspecialchars($anime['status']) ?></span>
                        <span class="text-gray-400"><?= htmlspecialchars($anime['release_year'] ?? '') ?></span>
                    </div>
                    
                    <div class="flex flex-wrap gap-1 mb-4">
                        <?php foreach (explode(',', $anime['genres']) as $genre): ?>
                        <span class="bg-gray-700 text-gray-300 text-xs px-2 py-1 rounded"><?= htmlspecialchars(trim($genre)) ?></span>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="flex space-x-2">
                        <?php if (isset($userData) && $userData): ?>
                        <!-- User is logged in -->
                        <button id="favorite-button" class="flex-1 flex items-center justify-center gap-2 px-4 py-2 bg-gray-700 hover:bg-gray-600 rounded-lg transition-colors">
                            <svg class="w-5 h-5 <?= $isFavorite ? 'text-red-500' : 'text-gray-400' ?>" fill="<?= $isFavorite ? 'currentColor' : 'none' ?>" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                            </svg>
                            <span><?= $isFavorite ? 'Remove from Favorites' : 'Add to Favorites' ?></span>
                        </button>
                        <?php else: ?>
                        <!-- User is not logged in -->
                        <a href="login.php" class="flex-1 text-center px-4 py-2 bg-gray-700 hover:bg-gray-600 rounded-lg transition-colors">
                            Sign In to Add to Favorites
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Anime details (2/3 width on desktop) -->
        <div class="md:col-span-2">
            <h1 class="text-3xl md:text-4xl font-bold mb-4"><?= htmlspecialchars($anime['title']) ?></h1>
            
            <?php if ($anime['description']): ?>
            <div class="bg-gray-800 rounded-lg p-6 mb-8">
                <h2 class="text-xl font-semibold mb-4">Synopsis</h2>
                <p class="text-gray-300 leading-relaxed"><?= htmlspecialchars($anime['description']) ?></p>
            </div>
            <?php endif; ?>
            
            <!-- Seasons section -->
            <h2 class="text-xl font-semibold mb-4">Seasons and Episodes</h2>
            
            <?php if (empty($seasons)): ?>
            <div class="bg-gray-800 rounded-lg p-6 text-center">
                <p class="text-gray-400">No seasons available for this anime.</p>
            </div>
            <?php else: ?>
                <?php foreach ($seasons as $season): ?>
                <div class="bg-gray-800 rounded-lg overflow-hidden mb-6">
                    <div class="p-4 bg-gray-700">
                        <h3 class="text-lg font-medium">Season <?= $season['season_number'] ?><?= $season['title'] ? ': ' . htmlspecialchars($season['title']) : '' ?></h3>
                    </div>
                    
                    <?php if (empty($season['episodes'])): ?>
                    <div class="p-4 text-center">
                        <p class="text-gray-400">No episodes available for this season.</p>
                    </div>
                    <?php else: ?>
                    <div class="divide-y divide-gray-700">
                        <?php foreach ($season['episodes'] as $episode): ?>
                        <a href="player.php?anime=<?= $anime_id ?>&season=<?= $season['id'] ?>&episode=<?= $episode['id'] ?>" class="flex items-center p-4 hover:bg-gray-700 transition-colors">
                            <div class="w-10 h-10 flex-shrink-0 flex items-center justify-center bg-gray-600 rounded-lg mr-4">
                                <span class="font-medium"><?= $episode['episode_number'] ?></span>
                            </div>
                            <div class="flex-grow">
                                <h4 class="font-medium"><?= htmlspecialchars($episode['title']) ?></h4>
                                <?php if ($episode['description']): ?>
                                <p class="text-sm text-gray-400 truncate mt-1"><?= htmlspecialchars($episode['description']) ?></p>
                                <?php endif; ?>
                            </div>
                            <?php if ($episode['is_premium'] == 1): ?>
                            <span class="ml-4 bg-yellow-600 text-white text-xs px-2 py-1 rounded">PREMIUM</span>
                            <?php endif; ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Related anime section -->
    <div class="mb-12">
        <h2 class="text-2xl font-bold text-white mb-6">You May Also Like</h2>
        
        <?php
        // Get related anime based on genres
        $relatedAnime = [];
        if ($anime && isset($anime['genres']) && !empty($anime['genres'])) {
            try {
                // Extract the first few genres
                $genres = explode(',', $anime['genres']);
                $mainGenre = trim($genres[0]);
                
                // Get anime with similar genre, excluding the current one
                $stmt = $pdo->prepare("SELECT * FROM anime WHERE id != ? AND genres LIKE ? ORDER BY RAND() LIMIT 4");
                $stmt->execute([$anime_id, "%$mainGenre%"]);
                $relatedAnime = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                // Silently fail, not critical
            }
        }
        ?>
        
        <?php if (!empty($relatedAnime)): ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            <?php foreach($relatedAnime as $related): ?>
            <div class="bg-gray-800 rounded-lg overflow-hidden transition-transform duration-300 hover:-translate-y-2 hover:shadow-lg">
                <a href="anime.php?id=<?= $related['id'] ?>">
                    <div class="aspect-w-16 aspect-h-9 h-48">
                        <?php if ($related['cover_image']): ?>
                            <img src="<?= htmlspecialchars($related['cover_image']) ?>" alt="<?= htmlspecialchars($related['title']) ?>" class="object-cover w-full h-full">
                        <?php else: ?>
                            <div class="w-full h-full bg-gray-700 flex items-center justify-center">
                                <span class="text-gray-400">No Image</span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="p-4">
                        <h3 class="text-lg font-medium text-white truncate"><?= htmlspecialchars($related['title']) ?></h3>
                        <div class="flex justify-between items-center mt-2">
                            <span class="text-sm text-gray-400"><?= htmlspecialchars($related['release_year'] ?? 'Unknown') ?></span>
                            <span class="px-2 py-1 bg-gray-700 text-gray-300 text-xs rounded"><?= htmlspecialchars($related['status']) ?></span>
                        </div>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="text-center py-8 text-gray-400">
            No related anime found.
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</main>

<?php if (isset($userData) && $userData): ?>
<script>
    // Handle favorite button functionality
    document.addEventListener('DOMContentLoaded', function() {
        const favoriteButton = document.getElementById('favorite-button');
        
        if (favoriteButton) {
            favoriteButton.addEventListener('click', function() {
                fetch('api/toggle_favorite.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `anime_id=<?= $anime_id ?>`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Toggle icon
                        const iconSvg = favoriteButton.querySelector('svg');
                        const textSpan = favoriteButton.querySelector('span');
                        
                        if (data.isFavorite) {
                            iconSvg.classList.remove('text-gray-400');
                            iconSvg.classList.add('text-red-500');
                            iconSvg.setAttribute('fill', 'currentColor');
                            textSpan.textContent = 'Remove from Favorites';
                        } else {
                            iconSvg.classList.remove('text-red-500');
                            iconSvg.classList.add('text-gray-400');
                            iconSvg.setAttribute('fill', 'none');
                            textSpan.textContent = 'Add to Favorites';
                        }
                    }
                })
                .catch(error => {
                    console.error('Error toggling favorite:', error);
                });
            });
        }
    });
</script>
<?php endif; ?>

<?php
// Include footer
include 'includes/footer.php';
?> 