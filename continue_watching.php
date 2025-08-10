<?php
// Continue Watching page - displays anime user is currently watching (not completed)
session_start();
require_once 'config.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?redirect=continue_watching.php");
    exit();
}

$pageTitle = "Continue Watching - AnimeElite";
include 'includes/header.php';
?>

<main class="container mx-auto px-4 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-white mb-2">Continue Watching</h1>
        <p class="text-gray-400">Anime you're currently watching</p>
    </div>
    
    <div id="continue-watching-container" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Loading state -->
        <div class="col-span-full text-center py-8">
            <div class="animate-pulse text-gray-400">Loading your progress...</div>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    loadContinueWatching();
});

function loadContinueWatching() {
    fetch('api/continue_watching.php')
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById('continue-watching-container');
            
            if (data.success && data.data.length > 0) {
                let html = '';
                data.data.forEach(item => {
                    html += createContinueWatchingCard(item);
                });
                container.innerHTML = html;
            } else {
                container.innerHTML = `
                    <div class="col-span-full text-center py-12">
                        <div class="bg-gray-800 rounded-lg p-8">
                            <h3 class="text-xl font-medium text-white mb-4">No anime in progress</h3>
                            <p class="text-gray-400 mb-6">Start watching some anime to see your progress here!</p>
                            <a href="browse.php" class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-3 rounded-lg font-medium transition-colors">
                                Browse Anime
                            </a>
                        </div>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error loading continue watching:', error);
            const container = document.getElementById('continue-watching-container');
            container.innerHTML = `
                <div class="col-span-full text-center py-8">
                    <div class="text-red-400">Error loading progress. Please try again.</div>
                </div>
            `;
        });
}

function createContinueWatchingCard(item) {
    return `
        <div class="bg-gray-800 rounded-lg overflow-hidden transition-transform duration-300 hover:-translate-y-2 hover:shadow-lg">
            <a href="anime.php?id=${item.anime_id}">
                <div class="relative">
                    <div class="aspect-w-16 aspect-h-9 h-48">
                        ${item.anime_cover ? 
                            `<img src="${item.anime_cover}" alt="${item.anime_title}" class="object-cover w-full h-full">` :
                            `<div class="w-full h-full bg-gray-700 flex items-center justify-center">
                                <span class="text-gray-400">No Image</span>
                             </div>`
                        }
                    </div>
                    
                    <!-- Progress overlay -->
                    <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black to-transparent p-4">
                        <div class="w-full bg-gray-600 rounded-full h-2 mb-2">
                            <div class="bg-gradient-to-r from-purple-500 to-pink-500 h-2 rounded-full" style="width: ${item.completion_percentage}%"></div>
                        </div>
                        <span class="text-xs text-white font-medium">${item.completion_percentage}% Complete</span>
                    </div>
                    
                    <!-- Status badge -->
                    <div class="absolute top-4 right-4">
                        <span class="bg-purple-600 text-white text-xs px-2 py-1 rounded-full font-medium">
                            WATCHING
                        </span>
                    </div>
                </div>
                
                <div class="p-4">
                    <h3 class="text-lg font-medium text-white mb-2 truncate">${item.anime_title}</h3>
                    <div class="flex justify-between items-center text-sm text-gray-400 mb-2">
                        <span>${item.watched_episodes}/${item.total_episodes} episodes</span>
                        <span>${item.completion_percentage}%</span>
                    </div>
                    <p class="text-xs text-gray-500">Last watched: ${item.last_watched_formatted}</p>
                </div>
            </a>
        </div>
    `;
}
</script>

<?php include 'includes/footer.php'; ?> 