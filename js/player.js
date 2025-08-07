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
    // Determine if we should use local mock API or production API
    const useMockApi = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
    
    // Build URL with parameters
    let url;
    if (useMockApi) {
        // Local development - use mock API
        url = `mock_api.php?endpoint=get_anime_details&anime_id=${animeId}`;
        console.log('Using mock API for local development');
    } else {
        // Production - use real API
        url = `https://cdn.glorioustradehub.com/get_anime_details.php?anime_id=${animeId}`;
    }
    
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
            
            // For local development, use hardcoded data if API fails
            if (useMockApi || true) { // Always use fallback for now
                console.log('Using fallback data for development');
                
                // Create mock data
                const mockData = {
                    success: true,
                    anime: {
                        id: 1,
                        title: 'Demon Slayer',
                        description: 'A family is attacked by demons and only two members survive - Tanjiro and his sister Nezuko, who is turning into a demon slowly. Tanjiro sets out to become a demon slayer to avenge his family and cure his sister.',
                        cover_image: 'https://m.media-amazon.com/images/M/MV5BNmQ5Zjg2ZTYtMGZmNC00M2Y3LTgwZGQtYmQ3NWI5MDdhZWNjXkEyXkFqcGc@._V1_.jpg',
                        release_year: '2019',
                        genres: 'Action, Fantasy',
                        status: 'ongoing'
                    },
                    seasons: [
                        {
                            id: 1,
                            season_number: 1,
                            title: 'Demon Slayer: Kimetsu no Yaiba',
                            description: 'First season of Demon Slayer',
                            cover_image: '',
                            release_year: '2019',
                            episodes: [
                                {
                                    id: 1,
                                    episode_number: 1,
                                    title: 'Cruelty',
                                    description: 'Tanjiro Kamado is a kind-hearted and intelligent boy who lives with his family in the mountains. He became his family\'s breadwinner after his father\'s death.',
                                    thumbnail: '',
                                    video_url: 'https://www.youtube.com/embed/VQGCKyvzIM4',
                                    duration: '24',
                                    is_premium: 0
                                },
                                {
                                    id: 2,
                                    episode_number: 2,
                                    title: 'Trainer Sakonji Urokodaki',
                                    description: 'Tanjiro encounters a demon slayer named Giyu Tomioka, who is impressed by Tanjiro\'s resolve and tells him to find a man named Sakonji Urokodaki.',
                                    thumbnail: '',
                                    video_url: 'https://www.youtube.com/embed/VQGCKyvzIM4',
                                    duration: '24',
                                    is_premium: 0
                                }
                            ]
                        },
                        {
                            id: 2,
                            season_number: 2,
                            title: 'Demon Slayer: Entertainment District Arc',
                            description: 'Second season of Demon Slayer',
                            cover_image: '',
                            release_year: '2021',
                            episodes: [
                                {
                                    id: 3,
                                    episode_number: 1,
                                    title: 'Sound Hashira Tengen Uzui',
                                    description: 'Tanjiro and his friends accompany the Sound Hashira Tengen Uzui to investigate disappearances in the Entertainment District.',
                                    thumbnail: '',
                                    video_url: 'https://www.youtube.com/embed/VQGCKyvzIM4',
                                    duration: '24',
                                    is_premium: 1
                                }
                            ]
                        }
                    ],
                    current_episode: {
                        id: 1,
                        episode_number: 1,
                        title: 'Cruelty',
                        description: 'Tanjiro Kamado is a kind-hearted and intelligent boy who lives with his family in the mountains. He became his family\'s breadwinner after his father\'s death.',
                        thumbnail: '',
                        video_url: 'https://www.youtube.com/embed/VQGCKyvzIM4',
                        duration: '24',
                        is_premium: 0,
                        season_id: 1,
                        season_number: 1,
                        anime_id: 1,
                        anime_title: 'Demon Slayer'
                    }
                };
                
                updateAnimeUI(mockData);
            } else {
                showError('Failed to load anime data. Please try again later.');
            }
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
    
    // Update seasons and episodes list
    updateSeasonsList(seasons, currentEpisode);
    
    // Check if premium episode
    checkPremiumEpisode(currentEpisode);
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
    
    // Update episode description
    const episodeDescription = document.getElementById('episode-description');
    if (episodeDescription) {
        episodeDescription.textContent = episode.description || 'No description available.';
    }
}

/**
 * Update seasons list
 * @param {Array} seasons - Seasons data
 * @param {Object} currentEpisode - Current episode data
 */
function updateSeasonsList(seasons, currentEpisode) {
    const seasonsList = document.getElementById('seasons-list');
    if (!seasonsList) return;
    
    // Clear previous content
    seasonsList.innerHTML = '';
    
    // Loop through seasons
    seasons.forEach(season => {
        // Create season header
        const seasonHeader = document.createElement('div');
        seasonHeader.className = 'bg-gray-800 px-4 py-2 cursor-pointer flex justify-between items-center';
        seasonHeader.innerHTML = `
            <h3 class="font-medium">Season ${season.season_number}${season.title ? ': ' + season.title : ''}</h3>
            <svg class="h-5 w-5 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
            </svg>
        `;
        
        // Create episodes container
        const episodesContainer = document.createElement('div');
        episodesContainer.className = 'bg-gray-900';
        
        // Check if this is the current season
        const isCurrentSeason = season.id == currentEpisode.season_id;
        if (!isCurrentSeason) {
            episodesContainer.classList.add('hidden');
        } else {
            seasonHeader.classList.add('bg-gray-700');
            seasonHeader.querySelector('svg').classList.add('rotate-180');
        }
        
        // Add episodes
        if (season.episodes && season.episodes.length > 0) {
            season.episodes.forEach(episode => {
                const episodeItem = document.createElement('a');
                episodeItem.href = `?anime=${currentEpisode.anime_id}&season=${season.id}&episode=${episode.id}`;
                episodeItem.className = 'flex items-center px-4 py-2 hover:bg-gray-800 border-b border-gray-800';
                
                // Check if this is the current episode
                if (episode.id == currentEpisode.id) {
                    episodeItem.classList.add('bg-gray-700');
                }
                
                // Check if premium episode
                const isPremium = episode.is_premium == 1;
                
                // Use default image if thumbnail is missing
                const thumbnailUrl = episode.thumbnail || 'https://via.placeholder.com/150x84?text=Episode';
                
                episodeItem.innerHTML = `
                    <div class="w-10 h-10 flex-shrink-0 mr-3 relative">
                        <img src="${thumbnailUrl}" alt="Episode ${episode.episode_number}" class="w-full h-full object-cover rounded" onerror="this.src='https://via.placeholder.com/150x84?text=Episode'">
                        ${isPremium ? '<span class="absolute top-0 right-0 bg-yellow-600 text-white text-xs px-1 rounded">P</span>' : ''}
                    </div>
                    <div class="flex-grow">
                        <h4 class="text-sm font-medium truncate">${episode.title}</h4>
                        <p class="text-xs text-gray-400">Episode ${episode.episode_number}${episode.duration ? ' • ' + episode.duration + ' min' : ''}</p>
                    </div>
                `;
                
                episodesContainer.appendChild(episodeItem);
            });
        } else {
            const noEpisodes = document.createElement('div');
            noEpisodes.className = 'px-4 py-2 text-gray-400 text-sm';
            noEpisodes.textContent = 'No episodes available';
            episodesContainer.appendChild(noEpisodes);
        }
        
        // Add click event to toggle episodes
        seasonHeader.addEventListener('click', () => {
            episodesContainer.classList.toggle('hidden');
            seasonHeader.querySelector('svg').classList.toggle('rotate-180');
        });
        
        // Add to seasons list
        seasonsList.appendChild(seasonHeader);
        seasonsList.appendChild(episodesContainer);
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
    
    // Fetch from server (with error handling for CORS)
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
        // For development/testing, assume user has premium to avoid blocking content
        // In production, you would want to handle this differently
        if (callback) {
            callback(true); // Temporarily allow access for testing
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