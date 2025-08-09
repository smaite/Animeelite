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
            $stmt = $pdo->prepare("SELECT * FROM seasons WHERE anime_id = ? ORDER BY season_number, part_number");
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
                $stmt = $pdo->prepare("SELECT e.*, s.season_number, s.part_number, s.title as season_title 
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
            <div id="player-container" class="relative bg-gray-900 rounded-lg overflow-hidden mb-6">
                <div class="aspect-w-16">
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
                        class="<?= ($isPremiumEpisode && !$userHasPremium) ? 'opacity-20' : '' ?>"
                        allowfullscreen frameborder="0"></iframe>
                    <?php else: ?>
                    <div id="player-placeholder" class="bg-gray-800 flex items-center justify-center">
                        <span class="text-gray-400">No episode selected</span>
                    </div>
                    <?php endif; ?>
                </div>
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
                <form id="comment-form" class="mb-6">
                    <div class="mb-4">
                        <textarea id="comment-content" class="w-full px-4 py-2 rounded-md bg-gray-700 text-white border border-gray-600 focus:outline-none focus:border-purple-500" 
                                rows="3" placeholder="Add a comment..."></textarea>
                    </div>
                    <div class="flex justify-between items-center">
                        <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white font-medium py-2 px-4 rounded-md transition-colors">
                            Post Comment
                        </button>
                        <div id="comment-status" class="text-sm"></div>
                    </div>
                </form>
                <?php else: ?>
                <div class="bg-gray-700 p-4 rounded-md mb-6">
                    <p class="text-gray-300">Please <a href="login.php" class="text-purple-400 hover:text-purple-300">sign in</a> to leave a comment.</p>
                </div>
                <?php endif; ?>
                
                <div id="comments-container" class="space-y-6">
                    <!-- Comments will be loaded here via JavaScript -->
                    <div class="text-center py-4 text-gray-400">
                        <div class="animate-pulse">Loading comments...</div>
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
                        Season <?= $season['season_number'] ?>
                        <?= $season['part_number'] > 1 ? ' Part ' . $season['part_number'] : '' ?>
                        <?= $season['title'] ? ': ' . htmlspecialchars($season['title']) : '' ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Episodes list -->
            <div class="bg-gray-800 rounded-lg p-4">
                <h3 id="season-title" class="text-lg font-medium text-white mb-4">
                    <?php if ($currentEpisode): ?>
                        Season <?= $currentEpisode['season_number'] ?>
                        <?= isset($currentEpisode['part_number']) && $currentEpisode['part_number'] > 1 ? ' Part ' . $currentEpisode['part_number'] : '' ?>
                    <?php else: ?>
                        Episodes
                    <?php endif; ?>
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
        
        // Initialize comments
        loadComments();
        
        // Handle comment form submission
        const commentForm = document.getElementById('comment-form');
        if (commentForm) {
            commentForm.addEventListener('submit', function(e) {
                e.preventDefault();
                submitComment();
            });
        }
    });
    
    // Function to load comments
    function loadComments() {
        const commentsContainer = document.getElementById('comments-container');
        if (!commentsContainer) return;
        
        // Fetch comments from API
        fetch(`api/comments.php?episode_id=<?= $currentEpisode ? $currentEpisode['id'] : 0 ?>`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayComments(data.data);
                } else {
                    commentsContainer.innerHTML = `<div class="text-center py-4 text-red-400">Error loading comments: ${data.message}</div>`;
                }
            })
            .catch(error => {
                commentsContainer.innerHTML = `<div class="text-center py-4 text-red-400">Error loading comments. Please try again later.</div>`;
                console.error('Error loading comments:', error);
            });
    }
    
    // Function to display comments
    function displayComments(comments) {
        const commentsContainer = document.getElementById('comments-container');
        if (!commentsContainer) return;
        
        if (comments.length === 0) {
            commentsContainer.innerHTML = `<div class="text-center py-4 text-gray-400">No comments yet. Be the first to comment!</div>`;
            return;
        }
        
        let html = '';
        comments.forEach(comment => {
            html += createCommentHTML(comment);
        });
        
        commentsContainer.innerHTML = html;
        
        // Add event listeners to reply buttons
        document.querySelectorAll('.reply-button').forEach(button => {
            button.addEventListener('click', function() {
                const commentId = this.getAttribute('data-comment-id');
                showReplyForm(commentId);
            });
        });
        
        // Add event listeners to delete buttons
        document.querySelectorAll('.delete-button').forEach(button => {
            button.addEventListener('click', function() {
                const commentId = this.getAttribute('data-comment-id');
                deleteComment(commentId);
            });
        });
    }
    
    // Function to create HTML for a comment
    function createCommentHTML(comment) {
        const date = new Date(comment.created_at);
        const timeAgo = getTimeAgo(date);
        
        const isCurrentUser = <?= isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'null' ?> === parseInt(comment.user_id);
        const isAdmin = <?= isset($_SESSION['role']) && $_SESSION['role'] === 'admin' ? 'true' : 'false' ?>;
        
        return `
            <div class="comment-item flex gap-4" data-comment-id="${comment.id}">
                <div class="w-10 h-10 rounded-full bg-gray-700 flex-shrink-0 overflow-hidden">
                    ${comment.avatar ? `<img src="${comment.avatar}" alt="Avatar" class="w-full h-full object-cover">` : 
                     `<div class="w-full h-full flex items-center justify-center bg-gradient-to-r from-purple-600 to-pink-600">
                        <span class="text-white text-sm font-medium">${comment.display_name.charAt(0).toUpperCase()}</span>
                      </div>`}
                </div>
                <div class="flex-grow">
                    <div class="flex items-center">
                        <h4 class="font-medium text-white">${comment.display_name}</h4>
                        <span class="text-gray-400 text-sm ml-2">${timeAgo}</span>
                    </div>
                    <p class="text-gray-300 mt-1">${comment.content}</p>
                    <div class="flex gap-4 mt-2 text-sm text-gray-400">
                        <button class="reply-button hover:text-white transition-colors" data-comment-id="${comment.id}">Reply</button>
                        ${(isCurrentUser || isAdmin) ? 
                          `<button class="delete-button hover:text-red-500 transition-colors" data-comment-id="${comment.id}">Delete</button>` : ''}
                    </div>
                    <div class="reply-form-container mt-3" id="reply-form-${comment.id}"></div>
                    ${comment.reply_count > 0 ? 
                      `<button class="view-replies-button mt-2 text-sm text-purple-400 hover:text-purple-300" data-comment-id="${comment.id}">
                        View ${comment.reply_count} ${comment.reply_count === 1 ? 'reply' : 'replies'}
                       </button>
                       <div class="replies-container mt-3 pl-6 border-l border-gray-700" id="replies-${comment.id}"></div>` : ''}
                </div>
            </div>
        `;
    }
    
    // Function to submit a comment
    function submitComment(parentId = null) {
        const contentField = parentId ? 
                            document.getElementById(`reply-content-${parentId}`) : 
                            document.getElementById('comment-content');
        const content = contentField.value.trim();
        
        if (!content) {
            showStatus('Please enter a comment', false, parentId);
            return;
        }
        
        const statusElement = parentId ? 
                             document.getElementById(`reply-status-${parentId}`) : 
                             document.getElementById('comment-status');
        
        statusElement.innerHTML = '<span class="text-gray-400">Posting...</span>';
        
        const data = {
            episode_id: <?= $currentEpisode ? $currentEpisode['id'] : 0 ?>,
            content: content
        };
        
        if (parentId) {
            data.parent_id = parentId;
        }
        
        fetch('api/comments.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                contentField.value = '';
                showStatus('Comment posted successfully!', true, parentId);
                
                if (parentId) {
                    // If it's a reply, reload the replies
                    loadReplies(parentId);
                    // Hide the reply form
                    document.getElementById(`reply-form-${parentId}`).innerHTML = '';
                } else {
                    // If it's a top-level comment, reload all comments
                    loadComments();
                }
            } else {
                showStatus(`Error: ${data.message}`, false, parentId);
            }
        })
        .catch(error => {
            showStatus('Error posting comment. Please try again.', false, parentId);
            console.error('Error posting comment:', error);
        });
    }
    
    // Function to delete a comment
    function deleteComment(commentId) {
        if (!confirm('Are you sure you want to delete this comment?')) {
            return;
        }
        
        fetch(`api/comments.php?id=${commentId}`, {
            method: 'DELETE'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Reload comments
                loadComments();
            } else {
                alert(`Error: ${data.message}`);
            }
        })
        .catch(error => {
            alert('Error deleting comment. Please try again.');
            console.error('Error deleting comment:', error);
        });
    }
    
    // Function to show reply form
    function showReplyForm(commentId) {
        const container = document.getElementById(`reply-form-${commentId}`);
        
        container.innerHTML = `
            <div class="bg-gray-700 p-3 rounded-md">
                <textarea id="reply-content-${commentId}" class="w-full px-3 py-2 rounded-md bg-gray-600 text-white border border-gray-500 focus:outline-none focus:border-purple-500" 
                        rows="2" placeholder="Write a reply..."></textarea>
                <div class="flex justify-between items-center mt-2">
                    <div>
                        <button onclick="submitReply(${commentId})" class="bg-purple-600 hover:bg-purple-700 text-white text-sm font-medium py-1 px-3 rounded-md transition-colors">
                            Reply
                        </button>
                        <button onclick="cancelReply(${commentId})" class="text-gray-400 hover:text-white text-sm font-medium py-1 px-3 ml-2 transition-colors">
                            Cancel
                        </button>
                    </div>
                    <div id="reply-status-${commentId}" class="text-xs"></div>
                </div>
            </div>
        `;
        
        // Focus on the textarea
        document.getElementById(`reply-content-${commentId}`).focus();
    }
    
    // Function to submit a reply
    function submitReply(commentId) {
        submitComment(commentId);
    }
    
    // Function to cancel reply
    function cancelReply(commentId) {
        document.getElementById(`reply-form-${commentId}`).innerHTML = '';
    }
    
    // Function to load replies
    function loadReplies(commentId) {
        const container = document.getElementById(`replies-${commentId}`);
        if (!container) return;
        
        container.innerHTML = '<div class="text-center py-2 text-gray-400"><div class="animate-pulse">Loading replies...</div></div>';
        
        fetch(`api/comments.php?parent_id=${commentId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayReplies(commentId, data.data);
                } else {
                    container.innerHTML = `<div class="text-center py-2 text-red-400">Error loading replies: ${data.message}</div>`;
                }
            })
            .catch(error => {
                container.innerHTML = `<div class="text-center py-2 text-red-400">Error loading replies. Please try again later.</div>`;
                console.error('Error loading replies:', error);
            });
    }
    
    // Function to display replies
    function displayReplies(commentId, replies) {
        const container = document.getElementById(`replies-${commentId}`);
        if (!container) return;
        
        if (replies.length === 0) {
            container.innerHTML = `<div class="text-center py-2 text-gray-400">No replies yet.</div>`;
            return;
        }
        
        let html = '';
        replies.forEach(reply => {
            html += createReplyHTML(reply);
        });
        
        container.innerHTML = html;
        
        // Add event listeners to delete buttons
        container.querySelectorAll('.delete-button').forEach(button => {
            button.addEventListener('click', function() {
                const replyId = this.getAttribute('data-comment-id');
                deleteComment(replyId);
            });
        });
    }
    
    // Function to create HTML for a reply
    function createReplyHTML(reply) {
        const date = new Date(reply.created_at);
        const timeAgo = getTimeAgo(date);
        
        const isCurrentUser = <?= isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'null' ?> === parseInt(reply.user_id);
        const isAdmin = <?= isset($_SESSION['role']) && $_SESSION['role'] === 'admin' ? 'true' : 'false' ?>;
        
        return `
            <div class="reply-item flex gap-3 mt-3" data-reply-id="${reply.id}">
                <div class="w-8 h-8 rounded-full bg-gray-700 flex-shrink-0 overflow-hidden">
                    ${reply.avatar ? `<img src="${reply.avatar}" alt="Avatar" class="w-full h-full object-cover">` : 
                     `<div class="w-full h-full flex items-center justify-center bg-gradient-to-r from-purple-600 to-pink-600">
                        <span class="text-white text-xs font-medium">${reply.display_name.charAt(0).toUpperCase()}</span>
                      </div>`}
                </div>
                <div class="flex-grow">
                    <div class="flex items-center">
                        <h4 class="font-medium text-white text-sm">${reply.display_name}</h4>
                        <span class="text-gray-400 text-xs ml-2">${timeAgo}</span>
                    </div>
                    <p class="text-gray-300 text-sm mt-1">${reply.content}</p>
                    <div class="flex gap-4 mt-1 text-xs text-gray-400">
                        ${(isCurrentUser || isAdmin) ? 
                          `<button class="delete-button hover:text-red-500 transition-colors" data-comment-id="${reply.id}">Delete</button>` : ''}
                    </div>
                </div>
            </div>
        `;
    }
    
    // Function to show status message
    function showStatus(message, success, parentId = null) {
        const statusElement = parentId ? 
                             document.getElementById(`reply-status-${parentId}`) : 
                             document.getElementById('comment-status');
        
        statusElement.innerHTML = `<span class="${success ? 'text-green-400' : 'text-red-400'}">${message}</span>`;
        
        // Clear status after 3 seconds
        setTimeout(() => {
            statusElement.innerHTML = '';
        }, 3000);
    }
    
    // Function to format time ago
    function getTimeAgo(date) {
        const now = new Date();
        const seconds = Math.floor((now - date) / 1000);
        
        let interval = Math.floor(seconds / 31536000);
        if (interval >= 1) {
            return interval === 1 ? '1 year ago' : `${interval} years ago`;
        }
        
        interval = Math.floor(seconds / 2592000);
        if (interval >= 1) {
            return interval === 1 ? '1 month ago' : `${interval} months ago`;
        }
        
        interval = Math.floor(seconds / 86400);
        if (interval >= 1) {
            return interval === 1 ? '1 day ago' : `${interval} days ago`;
        }
        
        interval = Math.floor(seconds / 3600);
        if (interval >= 1) {
            return interval === 1 ? '1 hour ago' : `${interval} hours ago`;
        }
        
        interval = Math.floor(seconds / 60);
        if (interval >= 1) {
            return interval === 1 ? '1 minute ago' : `${interval} minutes ago`;
        }
        
        return seconds < 10 ? 'just now' : `${seconds} seconds ago`;
    }
</script>

<?php
// Include footer
include 'includes/footer.php';
?> 