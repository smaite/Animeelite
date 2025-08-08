// Wait for DOM to be ready
document.addEventListener('DOMContentLoaded', () => {
    // Check if user is already logged in
    const token = localStorage.getItem('auth_token');
    if (token) {
        verifyToken(token);
    }
    
    // Login form handling
    const loginForm = document.getElementById('login-form');
    if (loginForm) {
        loginForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const errorMsgElement = document.getElementById('error-message');
            
            try {
                // Show loading state
                const submitButton = loginForm.querySelector('button[type="submit"]');
                const originalText = submitButton.textContent;
                submitButton.innerHTML = '<div class="inline-block animate-spin h-4 w-4 border-t-2 border-white rounded-full mr-2"></div> Signing In...';
                submitButton.disabled = true;
                
                // Clear previous errors
                errorMsgElement.classList.add('hidden');
                
                // Attempt to sign in
                const response = await fetch('https://cdn.glorioustradehub.com/user_auth.php?action=login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `email=${encodeURIComponent(email)}&password=${encodeURIComponent(password)}`
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Save auth token to localStorage
                    localStorage.setItem('auth_token', data.user.token);
                    localStorage.setItem('user_data', JSON.stringify({
                        id: data.user.id,
                        username: data.user.username,
                        email: data.user.email,
                        subscription: data.user.subscription
                    }));
                    
                    // Redirect to home page on success
                    window.location.href = '../index.html';
                } else {
                    // Display error message
                    errorMsgElement.classList.remove('hidden');
                    errorMsgElement.textContent = data.message || 'Login failed. Please try again.';
                }
                
            } catch (error) {
                // Display error message
                errorMsgElement.classList.remove('hidden');
                errorMsgElement.textContent = getAuthErrorMessage(error);
                
            } finally {
                // Reset button
                submitButton.innerHTML = originalText;
                submitButton.disabled = false;
            }
        });
    }
    
    // Sign up form handling
    const signupForm = document.getElementById('signup-form');
    if (signupForm) {
        signupForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            
            const username = document.getElementById('username').value;
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm-password').value;
            const errorMsgElement = document.getElementById('error-message');
            
            // Define submitButton outside try block so it's accessible in catch block
            const submitButton = signupForm.querySelector('button[type="submit"]');
            const originalText = submitButton.textContent;
            
            try {
                // Show loading state
                submitButton.innerHTML = '<div class="inline-block animate-spin h-4 w-4 border-t-2 border-white rounded-full mr-2"></div> Creating Account...';
                submitButton.disabled = true;
                
                // Clear previous errors
                errorMsgElement.classList.add('hidden');
                
                // Validate form
                if (password !== confirmPassword) {
                    throw new Error('Passwords do not match');
                }
                
                if (password.length < 6) {
                    throw new Error('Password must be at least 6 characters long');
                }
                
                // Create user with email and password
                const response = await fetch('https://cdn.glorioustradehub.com/user_auth.php?action=register', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `username=${encodeURIComponent(username)}&email=${encodeURIComponent(email)}&password=${encodeURIComponent(password)}`
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Save auth token to localStorage
                    localStorage.setItem('auth_token', data.user.token);
                    localStorage.setItem('user_data', JSON.stringify({
                        id: data.user.id,
                        username: data.user.username,
                        email: data.user.email,
                        subscription: data.user.subscription
                    }));
                    
                    // Redirect to subscription page
                    window.location.href = '../subscription.html';
                } else {
                    // Display error message
                    errorMsgElement.classList.remove('hidden');
                    errorMsgElement.textContent = data.message || 'Registration failed. Please try again.';
                }
                
            } catch (error) {
                // Display error message
                errorMsgElement.classList.remove('hidden');
                errorMsgElement.textContent = getAuthErrorMessage(error);
                
            } finally {
                // Reset button
                submitButton.innerHTML = originalText;
                submitButton.disabled = false;
            }
        });
    }
    
    // Google sign-in handling
    const googleButton = document.getElementById('google-signin') || document.getElementById('google-signup');
    if (googleButton) {
        googleButton.addEventListener('click', async () => {
            alert('Social login is currently not available. Please use email/password registration.');
        });
    }
    
    // Facebook sign-in handling
    const facebookButton = document.getElementById('facebook-signin') || document.getElementById('facebook-signup');
    if (facebookButton) {
        facebookButton.addEventListener('click', async () => {
            alert('Social login is currently not available. Please use email/password registration.');
        });
    }
    
    // Twitter sign-in handling
    const twitterButton = document.getElementById('twitter-signin') || document.getElementById('twitter-signup');
    if (twitterButton) {
        twitterButton.addEventListener('click', async () => {
            alert('Social login is currently not available. Please use email/password registration.');
        });
    }
    
    // Logout button handling
    const logoutButton = document.getElementById('logout-button');
    if (logoutButton) {
        logoutButton.addEventListener('click', async (event) => {
            event.preventDefault();
            
            const token = localStorage.getItem('auth_token');
            if (token) {
                try {
                    await fetch('https://cdn.glorioustradehub.com/user_auth.php?action=logout', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `token=${encodeURIComponent(token)}`
                    });
                } catch (error) {
                    console.error('Logout error:', error);
                }
            }
            
            // Clear local storage and redirect
            localStorage.removeItem('auth_token');
            localStorage.removeItem('user_data');
            window.location.href = '../index.html';
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
        
        if (data.success) {
            // Update stored user data
            localStorage.setItem('user_data', JSON.stringify({
                id: data.user.id,
                username: data.user.username,
                email: data.user.email,
                subscription: data.user.subscription
            }));
            
            // Update UI for logged in user
            updateUIForSignedInUser(data.user);
        } else {
            // Token invalid, clear storage and update UI
            localStorage.removeItem('auth_token');
            localStorage.removeItem('user_data');
            updateUIForSignedOutUser();
        }
    } catch (error) {
        console.error('Token verification error:', error);
        // Assume token is invalid
        localStorage.removeItem('auth_token');
        localStorage.removeItem('user_data');
        updateUIForSignedOutUser();
    }
}

/**
 * Update UI elements for a signed-in user
 * @param {Object} user - User data
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
    
    // Check subscription status
    checkPremiumStatus(user.id);
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
 * Get user-friendly error message
 * @param {Error} error - Error object
 * @returns {string} - User-friendly error message
 */
function getAuthErrorMessage(error) {
    if (error.message) {
        return error.message;
    }
    return 'An error occurred. Please try again.';
} 