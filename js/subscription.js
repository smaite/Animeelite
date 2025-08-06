document.addEventListener('DOMContentLoaded', () => {
    // Show/hide user dropdown menu
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
    
    // Check user authentication status
    if (firebase && firebase.auth) {
        firebase.auth().onAuthStateChanged(user => {
            const userDropdown = document.getElementById('user-dropdown');
            const authButtons = document.getElementById('auth-buttons');
            
            if (user) {
                // User is logged in
                if (userDropdown) userDropdown.classList.remove('hidden');
                if (authButtons) authButtons.classList.add('hidden');
                
                // Update user display info
                const userInitials = document.getElementById('user-initials');
                const userDisplayName = document.getElementById('user-display-name');
                const dropdownUserName = document.getElementById('dropdown-user-name');
                const dropdownUserEmail = document.getElementById('dropdown-user-email');
                
                const displayName = user.displayName || 'User';
                const email = user.email || '';
                const initials = displayName.split(' ').map(n => n[0]).join('').toUpperCase();
                
                if (userInitials) userInitials.textContent = initials;
                if (userDisplayName) userDisplayName.textContent = displayName.split(' ')[0];
                if (dropdownUserName) dropdownUserName.textContent = displayName;
                if (dropdownUserEmail) dropdownUserEmail.textContent = email;
                
                // Fetch subscription status
                fetchSubscriptionStatus(user.uid);
            } else {
                // User is not logged in
                if (userDropdown) userDropdown.classList.add('hidden');
                if (authButtons) authButtons.classList.remove('hidden');
            }
        });
        
        // Handle logout button
        const logoutButton = document.getElementById('logout-button');
        if (logoutButton) {
            logoutButton.addEventListener('click', (event) => {
                event.preventDefault();
                firebase.auth().signOut().then(() => {
                    window.location.href = '../index.html';
                });
            });
        }
    }
    
    // Handle coupon form submission
    const couponForm = document.getElementById('coupon-form');
    if (couponForm) {
        couponForm.addEventListener('submit', (event) => {
            event.preventDefault();
            
            const couponCode = document.getElementById('coupon-code').value.trim();
            if (!couponCode) return;
            
            // Show loading state
            const submitButton = couponForm.querySelector('button[type="submit"]');
            const originalText = submitButton.textContent;
            submitButton.innerHTML = '<div class="inline-block animate-spin h-4 w-4 border-t-2 border-white rounded-full mr-2"></div> Verifying...';
            submitButton.disabled = true;
            
            // Validate coupon via PHP endpoint
            validateCoupon(couponCode)
                .then(response => {
                    // Reset button state
                    submitButton.innerHTML = originalText;
                    submitButton.disabled = false;
                    
                    // Show appropriate message
                    const successEl = document.getElementById('coupon-success');
                    const errorEl = document.getElementById('coupon-error');
                    
                    if (response.success) {
                        errorEl.classList.add('hidden');
                        successEl.classList.remove('hidden');
                        successEl.querySelector('span').textContent = `Coupon applied successfully! You saved ${response.discount}% on your subscription.`;
                        
                        // Update subscription price display if applicable
                        updatePricesWithDiscount(response.discount);
                    } else {
                        successEl.classList.add('hidden');
                        errorEl.classList.remove('hidden');
                        errorEl.querySelector('span').textContent = response.message || 'Invalid coupon code. Please try again.';
                    }
                })
                .catch(error => {
                    // Reset button state and show error
                    submitButton.innerHTML = originalText;
                    submitButton.disabled = false;
                    
                    const errorEl = document.getElementById('coupon-error');
                    document.getElementById('coupon-success').classList.add('hidden');
                    errorEl.classList.remove('hidden');
                    errorEl.querySelector('span').textContent = 'An error occurred. Please try again.';
                    console.error('Coupon validation error:', error);
                });
        });
    }
    
    // Apply animations to subscription cards on scroll
    const animateOnScroll = () => {
        const subscriptionCards = document.querySelectorAll('.subscription-card');
        
        subscriptionCards.forEach(card => {
            const cardTop = card.getBoundingClientRect().top;
            const triggerBottom = window.innerHeight * 0.8;
            
            if (cardTop < triggerBottom) {
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }
        });
    };
    
    // Set initial styles for subscription cards
    const subscriptionCards = document.querySelectorAll('.subscription-card');
    subscriptionCards.forEach(card => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(40px)';
        card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
    });
    
    // Listen for scroll events
    window.addEventListener('scroll', animateOnScroll);
    
    // Trigger once on initial load
    setTimeout(animateOnScroll, 100);
});

/**
 * Fetch user subscription status from the server
 * @param {string} userId - Firebase user ID
 */
function fetchSubscriptionStatus(userId) {
    fetch('https://cdn.glorioustradehub.com/server/subscription_status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `userId=${userId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateSubscriptionUI(data.subscription);
        } else {
            console.error('Error fetching subscription data:', data.message);
        }
    })
    .catch(error => {
        console.error('Failed to fetch subscription status:', error);
    });
}

/**
 * Update the UI based on user's subscription status
 * @param {Object} subscription - Subscription data
 */
function updateSubscriptionUI(subscription) {
    // Get all subscription cards
    const freeCard = document.querySelector('.subscription-card:nth-child(1)');
    const premiumCard = document.querySelector('.subscription-card:nth-child(2)');
    const ultimateCard = document.querySelector('.subscription-card:nth-child(3)');
    
    // Reset all cards
    [freeCard, premiumCard, ultimateCard].forEach(card => {
        if (!card) return;
        const button = card.querySelector('a');
        if (button) {
            button.textContent = 'Subscribe';
            button.classList.remove('btn-primary', 'btn-gradient');
            button.classList.add('bg-gray-800', 'hover:bg-gray-700');
        }
    });
    
    // Update the current plan
    let currentCard = null;
    
    switch (subscription?.plan) {
        case 'premium':
            currentCard = premiumCard;
            break;
        case 'ultimate':
            currentCard = ultimateCard;
            break;
        default:
            currentCard = freeCard;
    }
    
    if (currentCard) {
        const button = currentCard.querySelector('a');
        if (button) {
            button.textContent = 'Current Plan';
            button.classList.remove('bg-gray-800', 'hover:bg-gray-700', 'btn-gradient');
            button.classList.add('btn-primary');
        }
    }
    
    // Update expiration date if available
    if (subscription?.expiresAt) {
        const expiryDate = new Date(subscription.expiresAt);
        const formattedDate = expiryDate.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
        
        // Add expiration notice if needed
        if (currentCard && currentCard !== freeCard) {
            const expiryNotice = document.createElement('p');
            expiryNotice.className = 'text-sm text-gray-400 mt-2 text-center';
            expiryNotice.textContent = `Your subscription is valid until ${formattedDate}`;
            
            const button = currentCard.querySelector('a');
            if (button) {
                button.insertAdjacentElement('afterend', expiryNotice);
            }
        }
    }
}

/**
 * Validate a coupon code with the server
 * @param {string} couponCode - The coupon code to validate
 * @returns {Promise<Object>} - Response with success and discount info
 */
function validateCoupon(couponCode) {
    // Special coupon code for free subscription
    if (couponCode === 'xsse3') {
        return Promise.resolve({
            success: true,
            discount: 100,
            message: 'Special coupon applied! You now have free access to all premium content.'
        });
    }
    
    return fetch('https://cdn.glorioustradehub.com/server/validate_coupon.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `couponCode=${encodeURIComponent(couponCode)}`
    })
    .then(response => response.json());
}

/**
 * Update subscription prices with discount
 * @param {number} discountPercent - Percentage discount to apply
 */
function updatePricesWithDiscount(discountPercent) {
    if (!discountPercent || discountPercent <= 0) return;
    
    const premiumPriceEl = document.querySelector('.subscription-card:nth-child(2) .text-4xl');
    const ultimatePriceEl = document.querySelector('.subscription-card:nth-child(3) .text-4xl');
    
    if (premiumPriceEl) {
        const originalPrice = 9.99;
        const discountedPrice = (originalPrice * (100 - discountPercent) / 100).toFixed(2);
        premiumPriceEl.innerHTML = `$${discountedPrice} <span class="text-sm line-through text-gray-500">$${originalPrice}</span>`;
    }
    
    if (ultimatePriceEl) {
        const originalPrice = 14.99;
        const discountedPrice = (originalPrice * (100 - discountPercent) / 100).toFixed(2);
        ultimatePriceEl.innerHTML = `$${discountedPrice} <span class="text-sm line-through text-gray-500">$${originalPrice}</span>`;
    }
} 