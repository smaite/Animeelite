// Wait for DOM to be ready
document.addEventListener('DOMContentLoaded', () => {
    // Get URL parameters
    const urlParams = new URLSearchParams(window.location.search);
    const animeId = urlParams.get('anime');
    const seasonId = urlParams.get('season');
    const episodeId = urlParams.get('episode');
    
    if (!animeId) {
        showError('No anime specified');
        return;
    }
    
    // Show loading state
    showLoading();
    
    // Fetch anime details from server
    fetchAnimeDetails(animeId, seasonId, episodeId);
    
    // Initialize Firebase authentication
    if (typeof firebase !== 'undefined') {
        const auth = firebase.auth();
        
        // Listen for authentication state changes
        auth.onAuthStateChanged(user => {
            if (user) {
                // User is signed in
                updateUIForSignedInUser(user);
                checkPremiumStatus(user.uid);
            } else {
                // User is signed out
                updateUIForSignedOutUser();
            }
        });
    }
    
    // Mobile menu toggle
    const mobileMenuButton = document.getElementById('mobile-menu-button');
    const mobileMenu = document.getElementById('mobile-menu');
    if (mobileMenuButton && mobileMenu) {
        mobileMenuButton.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
        });
    }
    
    // User dropdown toggle
    const userDropdownButton = document.getElementById('user-dropdown-button');
    const userMenu = document.getElementById('user-menu');
    if (userDropdownButton && userMenu) {
        userDropdownButton.addEventListener('click', () => {
            userMenu.classList.toggle('hidden');
        });
        
        // Close the dropdown when clicking outside
        document.addEventListener('click', (event) => {
            if (!userDropdownButton.contains(event.target) && !userMenu.contains(event.target)) {
                userMenu.classList.add('hidden');
            }
        });
    }
});

/**
 * Fetch anime details from server
 * @param {string|number} animeId - Anime ID
 * @param {string|number} seasonId - Season ID (optional)
 * @param {string|number} episodeId - Episode ID (optional)
 */
function fetchAnimeDetails(animeId, seasonId, episodeId) {
    // Always use the production API for episodes
    let url = `https://cdn.glorioustradehub.com/get_anime_details.php?anime_id=${animeId}`;
    if (seasonId) url += `&season_id=${seasonId}`;
    if (episodeId) url += `&episode_id=${episodeId}`;
    
    console.log('Fetching anime details from:', url);
    
    // Fetch data from server
    fetch(url)
        .then(response => {
            console.log('Response status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('Received data:', data);
            if (data.success) {
                // Update UI with anime data
                updateAnimeUI(data);
            } else {
                showError(data.message || 'Failed to load anime data');
            }
        })
        .catch(error => {
            console.error('Error fetching anime details:', error);
            showError('Failed to load anime data. Please try again later.');
        })
        .finally(() => {
            hideLoading();
        });
}

/**
 * Update UI with anime data
 * @param {Object} data - Anime data from server
 */
function updateAnimeUI(data) {
    const anime = data.anime;
    const seasons = data.seasons;
    const currentEpisode = data.current_episode;
    
    if (!anime || !seasons || !currentEpisode) {
        showError('Incomplete anime data');
        return;
    }
    
    // Update page title
    document.title = `${anime.title} - S${currentEpisode.season_number} E${currentEpisode.episode_number} - AnimeElite`;
    
    // Update video player
    updateVideoPlayer(currentEpisode);
    
    // Update episode info
    updateEpisodeInfo(currentEpisode, anime);
    
    // Update episodes list
    updateEpisodesList(seasons, currentEpisode);
    
    // Update season selector
    updateSeasonSelector(seasons, currentEpisode);
    
    // Check if premium episode
    checkPremiumEpisode(currentEpisode);
}

/**
 * Update season selector dropdown
 * @param {Array} seasons - Seasons data
 * @param {Object} currentEpisode - Current episode data
 */
function updateSeasonSelector(seasons, currentEpisode) {
    const seasonSelector = document.getElementById('season-selector');
    if (!seasonSelector) return;
    
    // Clear previous options
    seasonSelector.innerHTML = '';
    
    // Add options for each season
    seasons.forEach(season => {
        const option = document.createElement('option');
        option.value = season.id;
        option.textContent = `Season ${season.season_number}${season.title ? ': ' + season.title : ''}`;
        option.selected = season.id == currentEpisode.season_id;
        seasonSelector.appendChild(option);
    });
    
    // Add change event listener
    seasonSelector.addEventListener('change', function() {
        const selectedSeasonId = this.value;
        const selectedSeason = seasons.find(s => s.id == selectedSeasonId);
        if (selectedSeason && selectedSeason.episodes && selectedSeason.episodes.length > 0) {
            // Navigate to the first episode of the selected season
            window.location.href = `?anime=${currentEpisode.anime_id}&season=${selectedSeasonId}&episode=${selectedSeason.episodes[0].id}`;
        }
    });
}

/**
 * Update video player with episode data
 * @param {Object} episode - Episode data
 */
function updateVideoPlayer(episode) {
    const playerContainer = document.getElementById('player-container');
    const playerPlaceholder = document.getElementById('player-placeholder');
    const videoPlayer = document.getElementById('video-player');
    
    if (!playerContainer || !videoPlayer) {
        console.error('Video player elements not found');
        return;
    }
    
    // Set iframe source
    videoPlayer.src = episode.video_url;
    
    // Hide placeholder, show player
    if (playerPlaceholder) {
        playerPlaceholder.classList.add('hidden');
    }
    videoPlayer.classList.remove('hidden');
}

/**
 * Update episode information
 * @param {Object} episode - Episode data
 * @param {Object} anime - Anime data
 */
function updateEpisodeInfo(episode, anime) {
    // Update episode title
    const episodeTitle = document.getElementById('episode-title');
    if (episodeTitle) {
        episodeTitle.textContent = episode.title;
    }
    
    // Update episode number
    const episodeNumber = document.getElementById('episode-number');
    if (episodeNumber) {
        episodeNumber.textContent = `Season ${episode.season_number}, Episode ${episode.episode_number}`;
    }
    
    // Update anime title
    const animeTitle = document.getElementById('anime-title');
    if (animeTitle) {
        animeTitle.textContent = anime.title;
    }
    
    // Update season title
    const seasonTitle = document.getElementById('season-title');
    if (seasonTitle) {
        // Find the current season
        const currentSeason = anime.seasons ? anime.seasons.find(s => s.id == episode.season_id) : null;
        if (currentSeason) {
            seasonTitle.textContent = currentSeason.title || `Season ${episode.season_number}`;
        } else {
            seasonTitle.textContent = `Season ${episode.season_number}`;
        }
    }
    
    // Update episode description
    const episodeDescription = document.getElementById('episode-description');
    if (episodeDescription) {
        episodeDescription.textContent = episode.description || 'No description available.';
    }
}

/**
 * Update episodes list
 * @param {Array} seasons - Seasons data
 * @param {Object} currentEpisode - Current episode data
 */
function updateEpisodesList(seasons, currentEpisode) {
    const episodesList = document.getElementById('episode-list');
    if (!episodesList) return;
    
    // Clear previous content
    episodesList.innerHTML = '';
    
    // Find the current season
    const currentSeason = seasons.find(season => season.id == currentEpisode.season_id);
    if (!currentSeason || !currentSeason.episodes) return;
    
    // Add episodes from the current season
    currentSeason.episodes.forEach(episode => {
        const episodeItem = document.createElement('a');
        episodeItem.href = `?anime=${currentEpisode.anime_id}&season=${currentSeason.id}&episode=${episode.id}`;
        episodeItem.className = 'bg-gray-800 hover:bg-gray-700 rounded-lg overflow-hidden transition-all duration-200 flex items-center';
        
        // Check if this is the current episode
        if (episode.id == currentEpisode.id) {
            episodeItem.classList.add('bg-gray-700', 'ring-2', 'ring-primary-500');
        }
        
        // Check if premium episode
        const isPremium = episode.is_premium == 1;
        
        // Use default image if thumbnail is missing
        const thumbnailUrl = episode.thumbnail || 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="150" height="84" viewBox="0 0 150 84"%3E%3Crect width="150" height="84" fill="%23333333"/%3E%3Ctext x="50%25" y="50%25" font-family="Arial" font-size="12" fill="%23ffffff" text-anchor="middle" dominant-baseline="middle"%3EEpisode ' + episode.episode_number + '%3C/text%3E%3C/svg%3E';
        
        episodeItem.innerHTML = `
            <div class="w-16 h-16 flex-shrink-0 relative">
                <img src="${thumbnailUrl}" alt="Episode ${episode.episode_number}" class="w-full h-full object-cover" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\\'http://www.w3.org/2000/svg\\' width=\\'150\\' height=\\'84\\' viewBox=\\'0 0 150 84\\'%3E%3Crect width=\\'150\\' height=\\'84\\' fill=\\'%23333333\\'/%3E%3Ctext x=\\'50%25\\' y=\\'50%25\\' font-family=\\'Arial\\' font-size=\\'12\\' fill=\\'%23ffffff\\' text-anchor=\\'middle\\' dominant-baseline=\\'middle\\'%3EEpisode ${episode.episode_number}%3C/text%3E%3C/svg%3E'">
                ${isPremium ? '<span class="absolute top-0 right-0 bg-yellow-600 text-white text-xs px-1 rounded">P</span>' : ''}
            </div>
            <div class="p-3 flex-grow">
                <h4 class="text-sm font-medium truncate">${episode.title}</h4>
                <p class="text-xs text-gray-400">Episode ${episode.episode_number}${episode.duration ? ' • ' + episode.duration + ' min' : ''}</p>
            </div>
        `;
        
        episodesList.appendChild(episodeItem);
    });
}

/**
 * Check if episode is premium and update UI
 * @param {Object} episode - Episode data
 */
function checkPremiumEpisode(episode) {
    if (episode.is_premium == 1) {
        const videoContainer = document.getElementById('player-container');
        if (!videoContainer) {
            console.error('Video container not found');
            return;
        }
        
        const premiumOverlay = document.createElement('div');
        premiumOverlay.className = 'premium-overlay absolute inset-0 bg-black bg-opacity-80 flex flex-col items-center justify-center z-10';
        premiumOverlay.innerHTML = `
            <svg class="h-16 w-16 text-yellow-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
            <h3 class="text-xl font-bold text-white mb-2">Premium Episode</h3>
            <p class="text-gray-300 text-center mb-4 px-4">This episode is only available for premium subscribers.</p>
            <a href="../pages/subscription.html" class="bg-gradient-to-r from-primary-600 to-purple-600 hover:from-primary-700 hover:to-purple-700 text-white font-medium py-2 px-6 rounded-lg transition-all duration-200">
                Upgrade to Premium
            </a>
        `;
        
        // Add to video container
        videoContainer.appendChild(premiumOverlay);
        
        // Check if user has premium subscription
        if (typeof firebase !== 'undefined' && firebase.auth().currentUser) {
            checkPremiumStatus(firebase.auth().currentUser.uid, (isPremium) => {
                if (isPremium) {
                    // User has premium, remove overlay
                    premiumOverlay.remove();
                }
            });
        }
    }
}

/**
 * Check if user has premium subscription
 * @param {string} userId - Firebase user ID
 * @param {Function} callback - Callback function with premium status
 */
function checkPremiumStatus(userId, callback) {
    // First try to get from cache
    const cachedStatus = sessionStorage.getItem('premiumStatus');
    if (cachedStatus) {
        const status = JSON.parse(cachedStatus);
        if (status && status.status === 'active' && callback) {
            callback(true);
        }
        return;
    }
    
    // Fetch from server
    fetch('https://cdn.glorioustradehub.com/subscription_status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `userId=${userId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Cache the result for 5 minutes
            sessionStorage.setItem('premiumStatus', JSON.stringify(data.subscription));
            sessionStorage.setItem('premiumStatusTime', Date.now());
            
            // Check if user has premium
            const isPremium = data.subscription && data.subscription.status === 'active';
            
            // Call callback if provided
            if (callback) {
                callback(isPremium);
            }
            
            // Update UI
            updatePremiumUI(data.subscription);
        }
    })
    .catch(error => {
        console.error('Error checking premium status:', error);
        
        // For testing purposes only - assume user has premium
        if (callback) {
            callback(true);
        }
    });
}

/**
 * Update UI elements for a signed-in user
 * @param {Object} user - Firebase user object
 */
function updateUIForSignedInUser(user) {
    const authButtons = document.querySelectorAll('.auth-buttons');
    const userDropdown = document.querySelectorAll('.user-dropdown');
    const userDisplayName = document.querySelectorAll('.user-display-name');
    const userEmail = document.querySelectorAll('.user-email');
    const userInitials = document.querySelectorAll('.user-initials');
    
    // Hide auth buttons, show user dropdown
    authButtons.forEach(el => el.classList.add('hidden'));
    userDropdown.forEach(el => el.classList.remove('hidden'));
    
    // Update user info
    if (user.displayName) {
        userDisplayName.forEach(el => el.textContent = user.displayName);
        
        // Get initials for avatar
        const initials = user.displayName
            .split(' ')
            .map(name => name[0])
            .join('')
            .toUpperCase()
            .substring(0, 2);
        
        userInitials.forEach(el => el.textContent = initials);
    }
    
    if (user.email) {
        userEmail.forEach(el => el.textContent = user.email);
    }
}

/**
 * Update UI elements for a signed-out user
 */
function updateUIForSignedOutUser() {
    const authButtons = document.querySelectorAll('.auth-buttons');
    const userDropdown = document.querySelectorAll('.user-dropdown');
    
    // Show auth buttons, hide user dropdown
    authButtons.forEach(el => el.classList.remove('hidden'));
    userDropdown.forEach(el => el.classList.add('hidden'));
}

/**
 * Update UI based on premium status
 * @param {Object} subscription - Subscription data
 */
function updatePremiumUI(subscription) {
    const premiumBadges = document.querySelectorAll('.premium-badge');
    const premiumContent = document.querySelectorAll('.premium-content');
    const premiumButtons = document.querySelectorAll('.premium-button');
    
    if (subscription && subscription.status === 'active') {
        // Show premium badge
        premiumBadges.forEach(el => el.classList.remove('hidden'));
        
        // Show premium content
        premiumContent.forEach(el => {
            el.classList.remove('blur-sm');
            el.classList.remove('pointer-events-none');
            
            // Remove premium overlay if any
            const overlay = el.querySelector('.premium-overlay');
            if (overlay) {
                overlay.remove();
            }
        });
        
        // Update premium buttons
        premiumButtons.forEach(el => {
            el.textContent = 'Premium Active';
            el.classList.remove('bg-yellow-600');
            el.classList.add('bg-green-600');
        });
        
        // Remove premium overlay from player if exists
        const premiumOverlay = document.querySelector('#player-container .premium-overlay');
        if (premiumOverlay) {
            premiumOverlay.remove();
        }
    }
}

/**
 * Show loading state
 */
function showLoading() {
    const contentContainer = document.getElementById('content-container');
    if (!contentContainer) return;
    
    // Create loading element
    const loading = document.createElement('div');
    loading.id = 'loading-indicator';
    loading.className = 'fixed inset-0 flex items-center justify-center bg-black bg-opacity-75 z-50';
    loading.innerHTML = `
        <div class="text-center">
            <div class="animate-spin h-12 w-12 border-t-2 border-b-2 border-primary-500 rounded-full mb-4"></div>
            <p class="text-white">Loading...</p>
        </div>
    `;
    
    // Add to body
    document.body.appendChild(loading);
}

/**
 * Hide loading state
 */
function hideLoading() {
    const loading = document.getElementById('loading-indicator');
    if (loading) {
        loading.remove();
    }
}

/**
 * Show error message
 * @param {string} message - Error message
 */
function showError(message) {
    const contentContainer = document.getElementById('content-container');
    if (!contentContainer) return;
    
    // Hide loading
    hideLoading();
    
    // Create error element
    const error = document.createElement('div');
    error.className = 'bg-red-900 text-white p-4 rounded-lg mb-4';
    error.innerHTML = `
        <h3 class="font-bold mb-2">Error</h3>
        <p>${message}</p>
        <a href="../index.html" class="inline-block mt-4 bg-white text-red-900 px-4 py-2 rounded-lg font-medium">Back to Home</a>
    `;
    
    // Clear container and add error
    contentContainer.innerHTML = '';
    contentContainer.appendChild(error);
} 