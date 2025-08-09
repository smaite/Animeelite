<?php
// Player page for watching anime episodes
session_start();
require_once 'config.php';

// Get user data if logged in
$userData = null;
if (isset($_SESSION['user_id'])) {
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stmt = $pdo->prepare("SELECT id, username, email, display_name, role FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Just log error and continue
        error_log("Error fetching user data: " . $e->getMessage());
    }
}

// Get URL parameters
$animeId = isset($_GET['anime']) ? intval($_GET['anime']) : 0;
$seasonId = isset($_GET['season']) ? intval($_GET['season']) : 0;
$episodeId = isset($_GET['episode']) ? intval($_GET['episode']) : 0;

// Initialize variables
$error = '';
$anime = null;
$seasons = [];
$currentEpisode = null;
$isPremiumEpisode = false;
$userHasPremium = false;

if (!$animeId) {
    $error = "No anime specified.";
} else {
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Get anime details
        $stmt = $pdo->prepare("SELECT * FROM anime WHERE id = ?");
        $stmt->execute([$animeId]);
        
        if ($stmt->rowCount() === 0) {
            $error = "Anime not found.";
        } else {
            $anime = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Get seasons for this anime
            $stmt = $pdo->prepare("SELECT * FROM seasons WHERE anime_id = ? ORDER BY season_number");
            $stmt->execute([$animeId]);
            $seasons = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get episodes for each season
            foreach ($seasons as &$season) {
                $stmt = $pdo->prepare("SELECT * FROM episodes WHERE season_id = ? ORDER BY episode_number");
                $stmt->execute([$season['id']]);
                $season['episodes'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            // Get current episode
            if ($episodeId) {
                // Get specific episode
                $stmt = $pdo->prepare("SELECT e.*, s.season_number, s.title as season_title 
                                      FROM episodes e 
                                      JOIN seasons s ON e.season_id = s.id 
                                      WHERE e.id = ? AND s.anime_id = ?");
                $stmt->execute([$episodeId, $animeId]);
                
                if ($stmt->rowCount() === 1) {
                    $currentEpisode = $stmt->fetch(PDO::FETCH_ASSOC);
                }
            } 
            
            // If no episode found yet, try season
            if (!$currentEpisode && $seasonId) {
                $stmt = $pdo->prepare("SELECT e.*, s.season_number, s.title as season_title 
                                      FROM episodes e 
                                      JOIN seasons s ON e.season_id = s.id 
                                      WHERE s.id = ? AND s.anime_id = ? 
                                      ORDER BY e.episode_number LIMIT 1");
                $stmt->execute([$seasonId, $animeId]);
                
                if ($stmt->rowCount() === 1) {
                    $currentEpisode = $stmt->fetch(PDO::FETCH_ASSOC);
                }
            }
            
            // If still no episode, get first episode of first season
            if (!$currentEpisode && !empty($seasons) && !empty($seasons[0]['episodes'])) {
                $firstSeason = $seasons[0];
                $firstEpisode = $firstSeason['episodes'][0];
                
                $stmt = $pdo->prepare("SELECT e.*, s.season_number, s.title as season_title 
                                      FROM episodes e 
                                      JOIN seasons s ON e.season_id = s.id 
                                      WHERE e.id = ?");
                $stmt->execute([$firstEpisode['id']]);
                $currentEpisode = $stmt->fetch(PDO::FETCH_ASSOC);
            }
            
            // Check if episode is premium
            if ($currentEpisode && $currentEpisode['is_premium'] == 1) {
                $isPremiumEpisode = true;
                
                // Check if user has premium access
                if ($userData) {
                    $stmt = $pdo->prepare("SELECT * FROM subscriptions WHERE user_id = ? AND status = 'active' AND end_date > NOW()");
                    $stmt->execute([$userData['id']]);
                    $userHasPremium = ($stmt->rowCount() > 0);
                }
            }
            
            // Record watch history if user is logged in
            if ($userData && $currentEpisode) {
                $stmt = $pdo->prepare("INSERT INTO watch_history (user_id, episode_id) 
                                      VALUES (?, ?) 
                                      ON DUPLICATE KEY UPDATE watched_at = CURRENT_TIMESTAMP");
                $stmt->execute([$userData['id'], $currentEpisode['id']]);
            }
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}

// Set page title
$pageTitle = $currentEpisode ? "$anime[title] - S{$currentEpisode['season_number']} E{$currentEpisode['episode_number']} - AnimeElite" : "Player - AnimeElite";

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
    
    <!-- Player layout -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <!-- Video player and episode info (2/3 width on desktop) -->
        <div class="md:col-span-2">
            <!-- Video player -->
            <div id="player-container" class="relative bg-gray-900 rounded-lg overflow-hidden mb-6 aspect-w-16 aspect-h-9">
                <?php if ($isPremiumEpisode && !$userHasPremium): ?>
                <!-- Premium episode overlay -->
                <div class="premium-overlay absolute inset-0 bg-black bg-opacity-80 flex flex-col items-center justify-center z-10 p-4">
                    <svg class="h-16 w-16 text-yellow-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                    <h3 class="text-xl font-bold text-white mb-2">Premium Episode</h3>
                    <p class="text-gray-300 text-center mb-4">This episode is only available for premium subscribers.</p>
                    <a href="subscription.php" class="bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white font-medium py-2 px-6 rounded-lg transition-all duration-200">
                        Upgrade to Premium
                    </a>
                </div>
                <?php endif; ?>
                
                <?php if ($currentEpisode): ?>
                <iframe id="video-player" src="<?= htmlspecialchars($currentEpisode['video_url']) ?>" 
                    class="w-full h-full <?= ($isPremiumEpisode && !$userHasPremium) ? 'opacity-20' : '' ?>"
                    allowfullscreen></iframe>
                <?php else: ?>
                <div id="player-placeholder" class="w-full h-full bg-gray-800 flex items-center justify-center">
                    <span class="text-gray-400">No episode selected</span>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Episode info -->
            <?php if ($currentEpisode): ?>
            <div class="bg-gray-800 rounded-lg p-6 mb-8">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <h1 id="episode-title" class="text-2xl font-bold text-white mb-1"><?= htmlspecialchars($currentEpisode['title']) ?></h1>
                        <p id="episode-number" class="text-gray-400 mb-1">Season <?= $currentEpisode['season_number'] ?>, Episode <?= $currentEpisode['episode_number'] ?></p>
                        <h2 id="anime-title" class="text-lg font-medium text-purple-400"><?= htmlspecialchars($anime['title']) ?></h2>
                    </div>
                    <div class="flex gap-2">
                        <?php 
                        // Find previous episode
                        $prevEpisode = null;
                        $nextEpisode = null;
                        $foundCurrent = false;
                        
                        foreach ($seasons as $season) {
                            foreach ($season['episodes'] as $episode) {
                                if ($foundCurrent) {
                                    $nextEpisode = [
                                        'id' => $episode['id'],
                                        'season_id' => $season['id']
                                    ];
                                    break 2;
                                }
                                
                                if ($episode['id'] == $currentEpisode['id']) {
                                    $foundCurrent = true;
                                } else {
                                    $prevEpisode = [
                                        'id' => $episode['id'],
                                        'season_id' => $season['id']
                                    ];
                                }
                            }
                        }
                        ?>
                        
                        <!-- Previous episode button -->
                        <?php if ($prevEpisode): ?>
                        <a href="?anime=<?= $animeId ?>&season=<?= $prevEpisode['season_id'] ?>&episode=<?= $prevEpisode['id'] ?>" 
                           class="flex items-center justify-center w-10 h-10 bg-gray-700 hover:bg-gray-600 rounded-full transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                            </svg>
                        </a>
                        <?php else: ?>
                        <button disabled class="flex items-center justify-center w-10 h-10 bg-gray-700 opacity-50 cursor-not-allowed rounded-full">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                            </svg>
                        </button>
                        <?php endif; ?>
                        
                        <!-- Next episode button -->
                        <?php if ($nextEpisode): ?>
                        <a href="?anime=<?= $animeId ?>&season=<?= $nextEpisode['season_id'] ?>&episode=<?= $nextEpisode['id'] ?>" 
                           class="flex items-center justify-center w-10 h-10 bg-gray-700 hover:bg-gray-600 rounded-full transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </a>
                        <?php else: ?>
                        <button disabled class="flex items-center justify-center w-10 h-10 bg-gray-700 opacity-50 cursor-not-allowed rounded-full">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if ($currentEpisode['description']): ?>
                <div class="mt-4">
                    <h3 class="text-lg font-medium text-white mb-2">Episode Description</h3>
                    <p id="episode-description" class="text-gray-300"><?= htmlspecialchars($currentEpisode['description']) ?></p>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <!-- Comments section -->
            <div class="bg-gray-800 rounded-lg p-6">
                <h3 class="text-lg font-medium text-white mb-4">Comments</h3>
                
                <?php if (isset($userData) && $userData): ?>
                <form class="mb-6">
                    <div class="mb-4">
                        <textarea class="w-full px-4 py-2 rounded-md bg-gray-700 text-white border border-gray-600 focus:outline-none focus:border-purple-500" 
                                rows="3" placeholder="Add a comment..."></textarea>
                    </div>
                    <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white font-medium py-2 px-4 rounded-md transition-colors">
                        Post Comment
                    </button>
                </form>
                <?php else: ?>
                <div class="bg-gray-700 p-4 rounded-md mb-6">
                    <p class="text-gray-300">Please <a href="login.php" class="text-purple-400 hover:text-purple-300">sign in</a> to leave a comment.</p>
                </div>
                <?php endif; ?>
                
                <div class="space-y-6">
                    <!-- Sample comments (can be replaced with real comments from database) -->
                    <div class="flex gap-4">
                        <div class="w-10 h-10 rounded-full bg-gray-700 flex-shrink-0"></div>
                        <div>
                            <div class="flex items-center">
                                <h4 class="font-medium text-white">AnimeUser123</h4>
                                <span class="text-gray-400 text-sm ml-2">2 days ago</span>
                            </div>
                            <p class="text-gray-300 mt-1">This episode was amazing! I can't wait for the next one.</p>
                            <div class="flex gap-4 mt-2 text-sm text-gray-400">
                                <button class="hover:text-white transition-colors">Like</button>
                                <button class="hover:text-white transition-colors">Reply</button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex gap-4">
                        <div class="w-10 h-10 rounded-full bg-gray-700 flex-shrink-0"></div>
                        <div>
                            <div class="flex items-center">
                                <h4 class="font-medium text-white">OtakuFan99</h4>
                                <span class="text-gray-400 text-sm ml-2">3 days ago</span>
                            </div>
                            <p class="text-gray-300 mt-1">That plot twist was unexpected! I'm still processing everything that happened.</p>
                            <div class="flex gap-4 mt-2 text-sm text-gray-400">
                                <button class="hover:text-white transition-colors">Like</button>
                                <button class="hover:text-white transition-colors">Reply</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Episodes list (1/3 width on desktop) -->
        <div class="md:col-span-1">
            <!-- Season selector -->
            <div class="bg-gray-800 rounded-lg p-4 mb-6">
                <label for="season-selector" class="block text-gray-300 mb-2">Select Season</label>
                <select id="season-selector" class="w-full bg-gray-700 text-white border border-gray-600 rounded-md px-4 py-2 focus:outline-none focus:border-purple-500">
                    <?php foreach ($seasons as $season): ?>
                    <option value="<?= $season['id'] ?>" <?= ($currentEpisode && $currentEpisode['season_id'] == $season['id']) ? 'selected' : '' ?>>
                        Season <?= $season['season_number'] ?><?= $season['title'] ? ': ' . htmlspecialchars($season['title']) : '' ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Episodes list -->
            <div class="bg-gray-800 rounded-lg p-4">
                <h3 id="season-title" class="text-lg font-medium text-white mb-4">
                    <?= $currentEpisode ? 'Season ' . $currentEpisode['season_number'] : 'Episodes' ?>
                </h3>
                
                <div id="episode-list" class="space-y-3">
                    <?php 
                    $currentSeasonId = $currentEpisode ? $currentEpisode['season_id'] : ($seasons[0]['id'] ?? 0);
                    $currentSeasonEpisodes = [];
                    
                    foreach ($seasons as $season) {
                        if ($season['id'] == $currentSeasonId) {
                            $currentSeasonEpisodes = $season['episodes'];
                            break;
                        }
                    }
                    ?>
                    
                    <?php if (empty($currentSeasonEpisodes)): ?>
                    <div class="text-center py-8 text-gray-400">No episodes available for this season.</div>
                    <?php else: ?>
                        <?php foreach ($currentSeasonEpisodes as $episode): ?>
                        <a href="?anime=<?= $animeId ?>&season=<?= $episode['season_id'] ?>&episode=<?= $episode['id'] ?>" 
                           class="bg-gray-700 hover:bg-gray-600 rounded-lg overflow-hidden transition-all duration-200 flex items-center 
                                 <?= ($currentEpisode && $currentEpisode['id'] == $episode['id']) ? 'bg-gray-600 ring-2 ring-purple-500' : '' ?>">
                            <div class="w-16 h-16 flex-shrink-0 relative bg-gray-600">
                                <?php if ($episode['thumbnail']): ?>
                                <img src="<?= htmlspecialchars($episode['thumbnail']) ?>" alt="Episode <?= $episode['episode_number'] ?>" class="w-full h-full object-cover">
                                <?php endif; ?>
                                <?php if ($episode['is_premium'] == 1): ?>
                                <span class="absolute top-0 right-0 bg-yellow-600 text-white text-xs px-1 rounded">P</span>
                                <?php endif; ?>
                            </div>
                            <div class="p-3 flex-grow">
                                <h4 class="text-sm font-medium truncate"><?= htmlspecialchars($episode['title']) ?></h4>
                                <p class="text-xs text-gray-400">Episode <?= $episode['episode_number'] ?><?= $episode['duration'] ? ' • ' . $episode['duration'] . ' min' : '' ?></p>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</main>

<script>
    // Handle season selector
    document.addEventListener('DOMContentLoaded', function() {
        const seasonSelector = document.getElementById('season-selector');
        
        if (seasonSelector) {
            seasonSelector.addEventListener('change', function() {
                window.location.href = `player.php?anime=<?= $animeId ?>&season=${this.value}`;
            });
        }
    });
</script>

<?php
// Include footer
include 'includes/footer.php';
?> 