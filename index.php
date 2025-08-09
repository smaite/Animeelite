<?php
// Main index page for AnimeElite
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

// Function to get featured anime
function getFeaturedAnime() {
    global $host, $dbname, $username, $password;
    
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stmt = $pdo->query("SELECT * FROM anime ORDER BY id DESC LIMIT 4");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

// Function to get latest episodes
function getLatestEpisodes() {
    global $host, $dbname, $username, $password;
    
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stmt = $pdo->query("SELECT e.*, s.season_number, a.title as anime_title, a.id as anime_id 
                            FROM episodes e 
                            JOIN seasons s ON e.season_id = s.id 
                            JOIN anime a ON s.anime_id = a.id 
                            ORDER BY e.created_at DESC LIMIT 8");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

// Get user data if logged in
$userData = getUserData();
$featuredAnime = getFeaturedAnime();
$latestEpisodes = getLatestEpisodes();

// Set page title
$pageTitle = "AnimeElite - Watch Anime Online";

// Include header
include 'includes/header.php';
?>

<main class="container mx-auto px-4 py-8">
    <!-- Hero Section -->
    <section class="relative bg-gradient-to-br from-gray-900 to-black rounded-3xl overflow-hidden mb-12">
        <div class="absolute inset-0 bg-black opacity-50"></div>
        <div class="relative z-10 py-16 px-8 md:px-16 flex flex-col items-center md:items-start text-center md:text-left">
            <h1 class="text-4xl md:text-5xl font-bold mb-4 bg-gradient-to-r from-purple-500 to-pink-500 bg-clip-text text-transparent">Discover Amazing Anime</h1>
            <p class="text-xl text-gray-300 mb-8 max-w-2xl">Stream your favorite anime series and movies. New episodes added daily!</p>
            <div class="flex flex-wrap gap-4">
                <a href="browse.php" class="bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white font-medium py-3 px-6 rounded-full transition-all duration-300">
                    Browse Anime
                </a>
                <?php if (!isLoggedIn()): ?>
                <a href="login.php" class="bg-gray-800 hover:bg-gray-700 text-white font-medium py-3 px-6 rounded-full transition-all duration-300">
                    Sign In
                </a>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Animated shapes -->
        <div class="absolute top-0 right-0 w-1/2 h-full overflow-hidden">
            <div class="absolute -top-20 -right-20 w-72 h-72 bg-purple-600 rounded-full opacity-20 animate-pulse"></div>
            <div class="absolute top-40 -right-10 w-40 h-40 bg-pink-600 rounded-full opacity-20 animate-pulse" style="animation-delay: 1s;"></div>
        </div>
    </section>
    
    <!-- Featured Anime -->
    <section class="mb-16">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-white">Featured Anime</h2>
            <a href="browse.php" class="text-purple-500 hover:text-purple-400 transition-colors">View All</a>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            <?php foreach($featuredAnime as $anime): ?>
            <div class="bg-gray-800 rounded-lg overflow-hidden transition-transform duration-300 hover:-translate-y-2 hover:shadow-lg">
                <a href="anime.php?id=<?= $anime['id'] ?>">
                    <div class="aspect-h-9 h-48">
                        <?php if ($anime['cover_image']): ?>
                            <img src="<?= htmlspecialchars($anime['cover_image']) ?>" alt="<?= htmlspecialchars($anime['title']) ?>" class="object-cover w-full h-full">
                        <?php else: ?>
                            <div class="w-full h-full bg-gray-700 flex items-center justify-center">
                                <span class="text-gray-400">No Image</span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="p-4">
                        <h3 class="text-lg font-medium text-white truncate"><?= htmlspecialchars($anime['title']) ?></h3>
                        <div class="flex justify-between items-center mt-2">
                            <span class="text-sm text-gray-400"><?= htmlspecialchars($anime['release_year'] ?? 'Unknown') ?></span>
                            <span class="px-2 py-1 bg-gray-700 text-gray-300 text-xs rounded"><?= htmlspecialchars($anime['status']) ?></span>
                        </div>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
            
            <?php if (count($featuredAnime) === 0): ?>
            <div class="col-span-full py-12 text-center">
                <p class="text-gray-400">No featured anime available.</p>
            </div>
            <?php endif; ?>
        </div>
    </section>
    
    <!-- Latest Episodes -->
    <section class="mb-16">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-white">Latest Episodes</h2>
            <a href="latest.php" class="text-purple-500 hover:text-purple-400 transition-colors">View All</a>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            <?php foreach($latestEpisodes as $episode): ?>
            <div class="bg-gray-800 rounded-lg overflow-hidden transition-all duration-300 hover:-translate-y-1 hover:shadow-lg">
                <a href="player.php?anime=<?= $episode['anime_id'] ?>&season=<?= $episode['season_id'] ?>&episode=<?= $episode['id'] ?>">
                    <div class="relative aspect-w-16 aspect-h-9 h-40">
                        <?php if ($episode['thumbnail']): ?>
                            <img src="<?= htmlspecialchars($episode['thumbnail']) ?>" alt="<?= htmlspecialchars($episode['title']) ?>" class="object-cover w-full h-full">
                        <?php else: ?>
                            <div class="w-full h-full bg-gray-700 flex items-center justify-center">
                                <span class="text-gray-400">No Thumbnail</span>
                            </div>
                        <?php endif; ?>
                        <div class="absolute inset-0 bg-gradient-to-t from-black opacity-60"></div>
                        <div class="absolute bottom-2 left-2 right-2">
                            <div class="flex items-center">
                                <span class="bg-purple-600 text-white text-xs px-2 py-1 rounded mr-2">EP <?= $episode['episode_number'] ?></span>
                                <?php if ($episode['is_premium']): ?>
                                <span class="bg-yellow-600 text-white text-xs px-2 py-1 rounded">PREMIUM</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="p-4">
                        <h3 class="text-white text-sm font-medium truncate"><?= htmlspecialchars($episode['title']) ?></h3>
                        <p class="text-gray-400 text-xs mt-1 truncate"><?= htmlspecialchars($episode['anime_title']) ?></p>
                        <p class="text-gray-500 text-xs mt-1">Season <?= $episode['season_number'] ?></p>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
            
            <?php if (count($latestEpisodes) === 0): ?>
            <div class="col-span-full py-12 text-center">
                <p class="text-gray-400">No episodes available yet.</p>
            </div>
            <?php endif; ?>
        </div>
    </section>
    
    <!-- Features -->
    <section class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-16">
        <div class="bg-gray-800 p-6 rounded-xl text-center">
            <div class="w-16 h-16 mx-auto mb-4 flex items-center justify-center bg-purple-900 bg-opacity-50 rounded-full">
                <svg class="w-8 h-8 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h18M3 16h18"></path>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-white mb-2">Watch on Any Device</h3>
            <p class="text-gray-400">Stream anime on your phone, tablet, or computer with our responsive player.</p>
        </div>
        
        <div class="bg-gray-800 p-6 rounded-xl text-center">
            <div class="w-16 h-16 mx-auto mb-4 flex items-center justify-center bg-purple-900 bg-opacity-50 rounded-full">
                <svg class="w-8 h-8 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-white mb-2">New Episodes Daily</h3>
            <p class="text-gray-400">We update our library with the latest episodes as soon as they're available.</p>
        </div>
        
        <div class="bg-gray-800 p-6 rounded-xl text-center">
            <div class="w-16 h-16 mx-auto mb-4 flex items-center justify-center bg-purple-900 bg-opacity-50 rounded-full">
                <svg class="w-8 h-8 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-white mb-2">Premium Quality</h3>
            <p class="text-gray-400">Enjoy HD quality videos and exclusive content with our premium subscription.</p>
        </div>
    </section>
</main>

<?php
// Include footer
include 'includes/footer.php';
?> 