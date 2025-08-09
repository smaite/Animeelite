        </div>
    </div>
    
    <!-- Alpine.js for dropdowns -->
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    
    <!-- Mobile menu script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const mobileMenuButton = document.getElementById('mobile-menu-button');
            const mobileSidebar = document.getElementById('mobile-sidebar');
            const closeSidebar = document.getElementById('close-sidebar');
            
            if (mobileMenuButton && mobileSidebar) {
                mobileMenuButton.addEventListener('click', function() {
                    mobileSidebar.classList.toggle('hidden');
                });
            }
            
            if (closeSidebar && mobileSidebar) {
                closeSidebar.addEventListener('click', function() {
                    mobileSidebar.classList.add('hidden');
                });
            }
        });
    </script>
</body>
</html> 