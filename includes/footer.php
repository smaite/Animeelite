<?php
// Footer template for all pages
// This file should be included at the bottom of all PHP pages
?>
    <!-- Footer -->
    <footer class="mt-auto bg-gray-900 text-gray-400">
        <div class="container mx-auto px-4 py-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <!-- Logo and description -->
                <div>
                    <a href="index.php" class="text-xl font-bold bg-gradient-to-r from-purple-500 to-pink-500 bg-clip-text text-transparent">AnimeElite</a>
                    <p class="mt-4">Your premium anime streaming platform with the latest episodes and classic favorites.</p>
                </div>
                
                <!-- Quick links -->
                <div>
                    <h3 class="text-white text-lg font-semibold mb-4">Quick Links</h3>
                    <ul class="space-y-2">
                        <li><a href="index.php" class="hover:text-white transition-colors">Home</a></li>
                        <li><a href="browse.php" class="hover:text-white transition-colors">Browse</a></li>
                        <li><a href="latest.php" class="hover:text-white transition-colors">Latest Episodes</a></li>
                        <li><a href="subscription.php" class="hover:text-white transition-colors">Premium</a></li>
                    </ul>
                </div>
                
                <!-- Help & Info -->
                <div>
                    <h3 class="text-white text-lg font-semibold mb-4">Help & Info</h3>
                    <ul class="space-y-2">
                        <li><a href="about.php" class="hover:text-white transition-colors">About Us</a></li>
                        <li><a href="faq.php" class="hover:text-white transition-colors">FAQ</a></li>
                        <li><a href="contact.php" class="hover:text-white transition-colors">Contact Us</a></li>
                        <li><a href="terms.php" class="hover:text-white transition-colors">Terms of Service</a></li>
                        <li><a href="privacy.php" class="hover:text-white transition-colors">Privacy Policy</a></li>
                    </ul>
                </div>
                
                <!-- Connect with us -->
                <div>
                    <h3 class="text-white text-lg font-semibold mb-4">Connect With Us</h3>
                    <div class="flex space-x-4">
                        <a href="#" class="hover:text-white transition-colors">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="hover:text-white transition-colors">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="hover:text-white transition-colors">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="hover:text-white transition-colors">
                            <i class="fab fa-discord"></i>
                        </a>
                    </div>
                    <div class="mt-4">
                        <h4 class="text-white text-sm font-medium mb-2">Subscribe to our newsletter</h4>
                        <form action="subscribe.php" method="post" class="flex">
                            <input type="email" name="email" placeholder="Your email" required class="bg-gray-800 text-white px-4 py-2 rounded-l-md focus:outline-none focus:ring-2 focus:ring-purple-600 flex-grow">
                            <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-r-md transition-colors">
                                <i class="fas fa-arrow-right"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="border-t border-gray-800 mt-8 pt-6 flex flex-col md:flex-row justify-between items-center">
                <p>&copy; <?= date('Y') ?> AnimeElite. All rights reserved.</p>
                <p class="mt-2 md:mt-0">Made with <i class="fas fa-heart text-red-500"></i> for anime fans</p>
            </div>
        </div>
    </footer>
    
    <!-- Alpine.js for dropdowns -->
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    
    <!-- Main JS -->
    <script>
        // Mobile menu toggle
        document.addEventListener('DOMContentLoaded', function() {
            const mobileMenuButton = document.getElementById('mobile-menu-button');
            const mobileMenu = document.getElementById('mobile-menu');
            
            if (mobileMenuButton && mobileMenu) {
                mobileMenuButton.addEventListener('click', function() {
                    mobileMenu.classList.toggle('hidden');
                });
            }
        });
    </script>
</body>
</html> 