// Wait for DOM to be ready
document.addEventListener('DOMContentLoaded', () => {
    // Initialize Firebase authentication
    const auth = firebase.auth();
    const db = firebase.firestore();
    
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
                await auth.signInWithEmailAndPassword(email, password);
                
                // Redirect to home page on success
                window.location.href = '../index.html';
                
            } catch (error) {
                // Display error message
                errorMsgElement.classList.remove('hidden');
                errorMsgElement.textContent = getAuthErrorMessage(error);
                
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
            
            try {
                // Show loading state
                const submitButton = signupForm.querySelector('button[type="submit"]');
                const originalText = submitButton.textContent;
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
                const userCredential = await auth.createUserWithEmailAndPassword(email, password);
                const user = userCredential.user;
                
                // Update user profile with username
                await user.updateProfile({
                    displayName: username
                });
                
                // Store additional user data in Firestore
                await db.collection('users').doc(user.uid).set({
                    username,
                    email,
                    displayName: username,
                    createdAt: firebase.firestore.FieldValue.serverTimestamp(),
                    subscription: 'free',
                    favorites: [],
                    watchHistory: []
                });
                
                // Redirect to home page on success
                window.location.href = '../subscription.html';
                
            } catch (error) {
                // Display error message
                errorMsgElement.classList.remove('hidden');
                errorMsgElement.textContent = getAuthErrorMessage(error);
                
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
            try {
                const provider = new firebase.auth.GoogleAuthProvider();
                const result = await auth.signInWithPopup(provider);
                
                // Check if user is new
                if (result.additionalUserInfo.isNewUser) {
                    const user = result.user;
                    
                    // Store additional user data in Firestore
                    await db.collection('users').doc(user.uid).set({
                        username: user.displayName,
                        email: user.email,
                        displayName: user.displayName,
                        photoURL: user.photoURL,
                        createdAt: firebase.firestore.FieldValue.serverTimestamp(),
                        subscription: 'free',
                        favorites: [],
                        watchHistory: []
                    });
                }
                
                // Redirect to home page on success
                window.location.href = '../subscription.html';
                
            } catch (error) {
                console.error("Google Sign In Error", error);
                alert('Google sign-in failed: ' + getAuthErrorMessage(error));
            }
        });
    }
    
    // Facebook sign-in handling
    const facebookButton = document.getElementById('facebook-signin') || document.getElementById('facebook-signup');
    if (facebookButton) {
        facebookButton.addEventListener('click', async () => {
            try {
                const provider = new firebase.auth.FacebookAuthProvider();
                const result = await auth.signInWithPopup(provider);
                
                // Check if user is new
                if (result.additionalUserInfo.isNewUser) {
                    const user = result.user;
                    
                    // Store additional user data in Firestore
                    await db.collection('users').doc(user.uid).set({
                        username: user.displayName,
                        email: user.email,
                        displayName: user.displayName,
                        photoURL: user.photoURL,
                        createdAt: firebase.firestore.FieldValue.serverTimestamp(),
                        subscription: 'free',
                        favorites: [],
                        watchHistory: []
                    });
                }
                
                // Redirect to home page on success
                window.location.href = '../subscription.html';
                
            } catch (error) {
                console.error("Facebook Sign In Error", error);
                alert('Facebook sign-in failed: ' + getAuthErrorMessage(error));
            }
        });
    }
    
    // Twitter sign-in handling
    const twitterButton = document.getElementById('twitter-signin') || document.getElementById('twitter-signup');
    if (twitterButton) {
        twitterButton.addEventListener('click', async () => {
            try {
                const provider = new firebase.auth.TwitterAuthProvider();
                const result = await auth.signInWithPopup(provider);
                
                // Check if user is new
                if (result.additionalUserInfo.isNewUser) {
                    const user = result.user;
                    
                    // Store additional user data in Firestore
                    await db.collection('users').doc(user.uid).set({
                        username: user.displayName,
                        email: user.email,
                        displayName: user.displayName,
                        photoURL: user.photoURL,
                        createdAt: firebase.firestore.FieldValue.serverTimestamp(),
                        subscription: 'free',
                        favorites: [],
                        watchHistory: []
                    });
                }
                
                // Redirect to home page on success
                window.location.href = '../subscription.html';
                
            } catch (error) {
                console.error("Twitter Sign In Error", error);
                alert('Twitter sign-in failed: ' + getAuthErrorMessage(error));
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
});

/**
 * Get user-friendly error message from Firebase error code
 * @param {Error} error - Firebase auth error
 * @returns {string} - User-friendly error message
 */
function getAuthErrorMessage(error) {
    switch (error.code) {
        case 'auth/email-already-in-use':
            return 'This email is already registered. Please use another email or sign in.';
        case 'auth/invalid-email':
            return 'The email address is not valid.';
        case 'auth/weak-password':
            return 'The password is too weak. Please use at least 6 characters.';
        case 'auth/user-not-found':
        case 'auth/wrong-password':
            return 'Invalid email or password. Please try again.';
        case 'auth/too-many-requests':
            return 'Too many failed login attempts. Please try again later or reset your password.';
        case 'auth/popup-closed-by-user':
            return 'Sign-in was canceled. Please try again.';
        default:
            if (error.message) {
                return error.message;
            }
            return 'An error occurred. Please try again.';
    }
} 