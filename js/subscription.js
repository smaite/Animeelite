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
 * Validate a coupon code with the server
 * @param {string} couponCode - The coupon code to validate
 * @returns {Promise<Object>} - Response with success and discount info
 */
function validateCoupon(couponCode) {
    // Special coupon codes for free premium subscription
    const premiumCoupons = ['xsse3', 'ELITE100', 'ANIMEPRO', 'PREMIUM24'];
    
    // Check if the coupon is a special premium coupon
    if (premiumCoupons.includes(couponCode)) {
        // Get the current user
        const user = firebase.auth().currentUser;
        
        if (user) {
            const db = firebase.firestore();
            
            // Add the subscription to user's Firestore document
            db.collection('users').doc(user.uid).update({
                subscription: 'premium',
                subscriptionUpdatedAt: firebase.firestore.FieldValue.serverTimestamp(),
                subscriptionExpiresAt: new Date(Date.now() + 365 * 24 * 60 * 60 * 1000), // 1 year from now
                couponUsed: couponCode
            })
            .then(() => {
                console.log('Premium subscription activated successfully!');
                
                // Update session storage
                const subscriptionData = {
                    status: 'active',
                    plan: 'premium',
                    expiresAt: new Date(Date.now() + 365 * 24 * 60 * 60 * 1000).toISOString()
                };
                sessionStorage.setItem('premiumStatus', JSON.stringify(subscriptionData));
                
                // Update UI
                updateSubscriptionUI(subscriptionData);
            })
            .catch(error => {
                console.error('Error updating subscription:', error);
            });
        }
        
        return Promise.resolve({
            success: true,
            discount: 100,
            message: 'Premium subscription activated! You now have access to all premium content for 1 year.'
        });
    }
    
    return fetch('https://cdn.glorioustradehub.com/validate_coupon.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `couponCode=${encodeURIComponent(couponCode)}`
    })
    .then(response => response.json())
    .catch(error => {
        console.error('Coupon validation error:', error);
        
        // For testing purposes only - simulate coupon validation
        if (couponCode.length >= 5) {
            // Get the current user
            const user = firebase.auth().currentUser;
            
            if (user) {
                const db = firebase.firestore();
                
                // Add the subscription to user's Firestore document
                db.collection('users').doc(user.uid).update({
                    subscription: 'premium',
                    subscriptionUpdatedAt: firebase.firestore.FieldValue.serverTimestamp(),
                    subscriptionExpiresAt: new Date(Date.now() + 365 * 24 * 60 * 60 * 1000), // 1 year from now
                    couponUsed: couponCode
                })
                .then(() => {
                    console.log('Premium subscription activated successfully (testing mode)!');
                });
            }
            
            // Simulate a valid coupon
            return {
                success: true,
                discount: 100,
                message: 'Premium subscription activated! (Testing mode)'
            };
        } else {
            // Simulate an invalid coupon
            return {
                success: false,
                message: 'Invalid coupon code. Please try again.'
            };
        }
    });
}

/**
 * Fetch user subscription status from the server
 * @param {string} userId - Firebase user ID
 */
function fetchSubscriptionStatus(userId) {
    // First check if we have cached data
    const cachedStatus = sessionStorage.getItem('premiumStatus');
    if (cachedStatus) {
        try {
            const status = JSON.parse(cachedStatus);
            updateSubscriptionUI(status);
            return;
        } catch (e) {
            console.error('Error parsing cached subscription status:', e);
        }
    }

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
            // Cache the result
            sessionStorage.setItem('premiumStatus', JSON.stringify(data.subscription));
            updateSubscriptionUI(data.subscription);
        } else {
            console.error('Error fetching subscription data:', data.message);
        }
    })
    .catch(error => {
        console.error('Failed to fetch subscription status:', error);
        
        // For testing purposes only - create a mock subscription
        const mockSubscription = {
            status: 'active',
            plan: 'premium',
            expiresAt: new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toISOString() // 30 days from now
        };
        
        // Update UI with mock data for testing
        updateSubscriptionUI(mockSubscription);
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
            // Remove all subscription buttons - just for showcase
            button.style.display = 'none';
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
            button.style.display = 'block'; // Show only current plan button
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
            // Check if there's already an expiry notice
            let expiryNotice = currentCard.querySelector('.expiry-notice');
            
            if (!expiryNotice) {
                expiryNotice = document.createElement('p');
                expiryNotice.className = 'text-sm text-gray-400 mt-2 text-center expiry-notice';
                
                const button = currentCard.querySelector('a');
                if (button) {
                    button.insertAdjacentElement('afterend', expiryNotice);
                }
            }
            
            expiryNotice.textContent = `Your subscription is valid until ${formattedDate}`;
        }
    }
    
    // Update coupon section visibility based on subscription
    const couponSection = document.querySelector('section.py-12.bg-gray-900');
    if (couponSection) {
        if (subscription?.plan === 'premium' || subscription?.plan === 'ultimate') {
            // Hide coupon section for users with active subscriptions
            const successMessage = document.createElement('div');
            successMessage.className = 'max-w-2xl mx-auto bg-gray-800 rounded-xl p-8 shadow-lg animate-fade-in text-center';
            successMessage.innerHTML = `
                <div class="flex items-center justify-center mb-4">
                    <svg class="h-12 w-12 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <h3 class="text-2xl font-bold mb-4">You're a Premium Member!</h3>
                <p class="text-gray-400">You already have access to all premium content.</p>
            `;
            couponSection.innerHTML = '';
            couponSection.appendChild(successMessage);
        }
    }
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