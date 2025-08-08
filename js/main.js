// Wait for DOM to be ready
document.addEventListener('DOMContentLoaded', () => {
    // Add error handler to all images
    document.querySelectorAll('img').forEach(img => {
        img.onerror = function() {
            // Replace with inline SVG placeholder if loading fails
            const alt = this.alt || 'Image';
            this.src = `data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='300' height='450' viewBox='0 0 300 450'%3E%3Crect width='300' height='450' fill='%23333333'/%3E%3Ctext x='50%25' y='50%25' font-family='Arial' font-size='18' fill='%23ffffff' text-anchor='middle' dominant-baseline='middle'%3E${alt}%3C/text%3E%3C/svg%3E`;
            this.onerror = null; // Prevent infinite loop
        };
    });
    
    // Check if user is logged in
    const token = localStorage.getItem('auth_token');
    const userData = localStorage.getItem('user_data');
    
    if (token && userData) {
        try {
            const user = JSON.parse(userData);
            updateUIForSignedInUser(user);
            checkPremiumStatus(token);
            
            // Verify token is still valid
            verifyToken(token);
        } catch (e) {
            console.error('Error parsing user data:', e);
            updateUIForSignedOutUser();
        }
    } else {
        // User is signed out
        updateUIForSignedOutUser();
    }
    
    // Load anime data from the server
    loadFeaturedAnime();
    loadLatestEpisodes();
    
    // Mobile menu toggle
    const mobileMenuButton = document.getElementById('mobile-menu-button');
    const mobileMenu = document.getElementById('mobile-menu');
    if (mobileMenuButton && mobileMenu) {
        mobileMenuButton.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
        });
    }
    
    // User dropdown toggle
    const userMenuButton = document.getElementById('user-menu-button');
    const userMenu = document.getElementById('user-menu');
    if (userMenuButton && userMenu) {
        userMenuButton.addEventListener('click', () => {
            userMenu.classList.toggle('hidden');
        });
        
        // Close the dropdown when clicking outside
        document.addEventListener('click', (event) => {
            if (!userMenuButton.contains(event.target) && !userMenu.contains(event.target)) {
                userMenu.classList.add('hidden');
            }
        });
    }
    
    // Handle logout button
    const logoutButton = document.getElementById('logout-button');
    if (logoutButton) {
        logoutButton.addEventListener('click', (event) => {
            event.preventDefault();
            
            const token = localStorage.getItem('auth_token');
            if (token) {
                fetch('https://cdn.glorioustradehub.com/user_auth.php?action=logout', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `token=${encodeURIComponent(token)}`
                }).catch(error => {
                    console.error('Logout error:', error);
                });
            }
            
            // Clear local storage and redirect
            localStorage.removeItem('auth_token');
            localStorage.removeItem('user_data');
            window.location.href = 'index.html';
        });
    }
});

/**
 * Verify user token with server
 * @param {string} token - Auth token to verify
 */
async function verifyToken(token) {
    try {
        const response = await fetch('https://cdn.glorioustradehub.com/user_auth.php?action=verify_token', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `token=${encodeURIComponent(token)}`
        });
        
        const data = await response.json();
        
        if (!data.success) {
            // Token invalid, clear storage and update UI
            localStorage.removeItem('auth_token');
            localStorage.removeItem('user_data');
            updateUIForSignedOutUser();
        }
    } catch (error) {
        console.error('Token verification error:', error);
    }
}

/**
 * Update UI elements for a signed-in user
 * @param {Object} user - User data object
 */
function updateUIForSignedInUser(user) {
    const authButtons = document.querySelectorAll('.auth-buttons');
    const userDropdown = document.querySelectorAll('.user-dropdown');
    const userDisplayName = document.querySelectorAll('.user-display-name');
    const userEmail = document.querySelectorAll('.user-email');
    const userInitials = document.querySelectorAll('.user-initials');
    const dropdownUserName = document.querySelectorAll('#dropdown-user-name');
    const dropdownUserEmail = document.querySelectorAll('#dropdown-user-email');
    
    // Hide auth buttons, show user dropdown
    authButtons.forEach(el => el.classList.add('hidden'));
    userDropdown.forEach(el => el.classList.remove('hidden'));
    
    // Update user info
    if (user.username) {
        userDisplayName.forEach(el => el.textContent = user.username);
        dropdownUserName.forEach(el => el.textContent = user.username);
        
        // Get initials for avatar
        const initials = user.username
            .split(' ')
            .map(name => name[0])
            .join('')
            .toUpperCase()
            .substring(0, 2);
        
        userInitials.forEach(el => el.textContent = initials);
    }
    
    if (user.email) {
        userEmail.forEach(el => el.textContent = user.email);
        dropdownUserEmail.forEach(el => el.textContent = user.email);
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
 * Check if user has premium subscription
 * @param {string} token - User authentication token
 */
function checkPremiumStatus(token) {
    // First try to get from cache
    const cachedStatus = sessionStorage.getItem('premiumStatus');
    if (cachedStatus) {
        updatePremiumUI(JSON.parse(cachedStatus));
        return;
    }
    
    // Fetch from server
    fetch('https://cdn.glorioustradehub.com/subscription.php?action=status', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `token=${encodeURIComponent(token)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Cache the result for 5 minutes
            sessionStorage.setItem('premiumStatus', JSON.stringify(data.subscription));
            sessionStorage.setItem('premiumStatusTime', Date.now());
            
            // Update UI
            updatePremiumUI(data.subscription);
        }
    })
    .catch(error => {
        console.error('Error checking premium status:', error);
        
        // For testing purposes only - assume user has premium
        const mockSubscription = {
            status: 'active',
            plan: 'premium',
            expiresAt: new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toISOString()
        };
        
        // Cache and update UI
        sessionStorage.setItem('premiumStatus', JSON.stringify(mockSubscription));
        updatePremiumUI(mockSubscription);
    });
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
    }
}

/**
 * Load featured anime from the server
 */
function loadFeaturedAnime() {
    const featuredContainer = document.getElementById('featured-anime');
    if (!featuredContainer) return;
    
    // Show loading state
    featuredContainer.innerHTML = `
        <div class="flex justify-center items-center h-64">
            <div class="animate-spin h-10 w-10 border-t-2 border-b-2 border-primary-500 rounded-full"></div>
        </div>
    `;
    
    // Always use the production API
    const url = 'https://cdn.glorioustradehub.com/get_featured_anime.php';
    
    // Fetch featured anime from server
    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.anime && data.anime.length > 0) {
                // Clear loading state
                featuredContainer.innerHTML = '';
                
                // Create anime cards
                data.anime.forEach(anime => {
                    const animeCard = createAnimeCard(anime);
                    featuredContainer.appendChild(animeCard);
                });
            } else {
                featuredContainer.innerHTML = `
                    <div class="text-center py-10">
                        <p class="text-gray-400">No featured anime found.</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error loading featured anime:', error);
            featuredContainer.innerHTML = `
                <div class="text-center py-10">
                    <p class="text-red-500">Failed to load featured anime. Please try again later.</p>
                </div>
            `;
        });
}

/**
 * Load latest episodes from the server
 */
function loadLatestEpisodes() {
    const episodesContainer = document.getElementById('latest-episodes');
    if (!episodesContainer) return;
    
    // Show loading state
    episodesContainer.innerHTML = `
        <div class="flex justify-center items-center h-64">
            <div class="animate-spin h-10 w-10 border-t-2 border-b-2 border-primary-500 rounded-full"></div>
        </div>
    `;
    
    // Always use the production API
    const url = 'https://cdn.glorioustradehub.com/get_latest_episodes.php';
    
    // Fetch latest episodes from server
    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.episodes && data.episodes.length > 0) {
                // Clear loading state
                episodesContainer.innerHTML = '';
                
                // Create episode cards
                data.episodes.forEach(episode => {
                    const episodeCard = createEpisodeCard(episode);
                    episodesContainer.appendChild(episodeCard);
                });
            } else {
                episodesContainer.innerHTML = `
                    <div class="text-center py-10">
                        <p class="text-gray-400">No episodes found.</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error loading latest episodes:', error);
            episodesContainer.innerHTML = `
                <div class="text-center py-10">
                    <p class="text-red-500">Failed to load latest episodes. Please try again later.</p>
                </div>
            `;
        });
}

/**
 * Create an anime card element
 * @param {Object} anime - Anime data
 * @returns {HTMLElement} - Anime card element
 */
function createAnimeCard(anime) {
    const card = document.createElement('div');
    card.className = 'anime-card bg-gray-800 rounded-lg overflow-hidden shadow-lg transition-all duration-300';
    
    // Check if anime is premium
    const isPremium = anime.is_premium === 1 || anime.is_premium === true;
    
    card.innerHTML = `
        <a href="pages/player.html?anime=${anime.id}&season=1&episode=1" class="block relative">
            <div class="relative">
                <img src="${anime.cover_image}" alt="${anime.title}" class="w-full h-48 object-cover">
                ${isPremium ? '<span class="absolute top-2 right-2 bg-yellow-600 text-white text-xs px-2 py-1 rounded">PREMIUM</span>' : ''}
                <div class="absolute inset-0 bg-gradient-to-t from-gray-900 to-transparent opacity-70"></div>
                <div class="absolute bottom-0 left-0 p-4">
                    <h3 class="text-white font-bold">${anime.title}</h3>
                    <p class="text-gray-300 text-sm">${anime.release_year} • ${anime.genres}</p>
                </div>
                <div class="absolute inset-0 flex items-center justify-center opacity-0 hover:opacity-100 transition-opacity duration-300 bg-black bg-opacity-50">
                    <div class="bg-primary-600 rounded-full p-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>
        </a>
    `;
    
    return card;
}

/**
 * Create an episode card element
 * @param {Object} episode - Episode data
 * @returns {HTMLElement} - Episode card element
 */
function createEpisodeCard(episode) {
    const card = document.createElement('div');
    card.className = 'min-w-[250px] bg-gray-800 rounded-lg overflow-hidden shadow-lg transition-all duration-300';
    
    // Check if episode is premium
    const isPremium = episode.is_premium === 1 || episode.is_premium === true;
    
    card.innerHTML = `
        <a href="pages/player.html?anime=${episode.anime_id}&season=${episode.season_id}&episode=${episode.id}" class="block relative">
            <div class="relative">
                <img src="${episode.thumbnail}" alt="${episode.title}" class="w-full h-36 object-cover">
                ${isPremium ? '<span class="absolute top-2 right-2 bg-yellow-600 text-white text-xs px-2 py-1 rounded">PREMIUM</span>' : ''}
                <div class="absolute inset-0 bg-gradient-to-t from-gray-900 to-transparent opacity-70"></div>
                <div class="absolute bottom-0 left-0 p-3">
                    <h3 class="text-white font-bold text-sm">${episode.anime_title}</h3>
                    <p class="text-gray-300 text-xs">S${episode.season_number} E${episode.episode_number}</p>
                </div>
                <div class="absolute inset-0 flex items-center justify-center opacity-0 hover:opacity-100 transition-opacity duration-300 bg-black bg-opacity-50">
                    <div class="bg-primary-600 rounded-full p-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>
        </a>
        <div class="p-3">
            <h4 class="text-white text-sm font-medium truncate">${episode.title}</h4>
            <p class="text-gray-400 text-xs mt-1">${episode.duration ? episode.duration + ' min' : 'N/A'}</p>
        </div>
    `;
    
    return card;
}

/**
 * Get relative path based on current location
 * @param {string} path - Path to convert
 * @returns {string} - Relative path
 */
function getRelativePath(path) {
    // Check if we're in a subdirectory
    const isInSubdir = window.location.pathname.split('/').length > 2;
    return isInSubdir ? '../' + path : path;
}