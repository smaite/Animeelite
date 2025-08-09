<?php
// Settings Page
require_once 'includes/header.php';

// Initialize variables
$error = '';
$success = '';

// Get current settings
$settings = [
    'site_name' => 'AnimeElite',
    'site_description' => 'The best anime streaming site',
    'maintenance_mode' => false,
    'allow_registrations' => true,
    'items_per_page' => 12,
    'featured_anime_count' => 5
];

// Load settings from database if they exist
try {
    // Check if settings table exists
    $check_table = $pdo->query("SHOW TABLES LIKE 'settings'");
    if ($check_table->rowCount() > 0) {
        // Get all settings
        $stmt = $pdo->query("SELECT * FROM settings");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        
        // Convert boolean settings
        $settings['maintenance_mode'] = (bool)$settings['maintenance_mode'];
        $settings['allow_registrations'] = (bool)$settings['allow_registrations'];
    }
} catch (PDOException $e) {
    // Settings table might not exist yet, that's okay
    $error = "Note: Settings table not found. Using default settings.";
}

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'save_settings') {
        // Get form data
        $new_settings = [
            'site_name' => trim($_POST['site_name']),
            'site_description' => trim($_POST['site_description']),
            'maintenance_mode' => isset($_POST['maintenance_mode']) ? 1 : 0,
            'allow_registrations' => isset($_POST['allow_registrations']) ? 1 : 0,
            'items_per_page' => intval($_POST['items_per_page']),
            'featured_anime_count' => intval($_POST['featured_anime_count'])
        ];
        
        // Validate form data
        if (empty($new_settings['site_name'])) {
            $error = "Site name is required.";
        } elseif ($new_settings['items_per_page'] < 1) {
            $error = "Items per page must be at least 1.";
        } elseif ($new_settings['featured_anime_count'] < 1) {
            $error = "Featured anime count must be at least 1.";
        } else {
            try {
                // Create settings table if it doesn't exist
                $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    setting_key VARCHAR(50) NOT NULL UNIQUE,
                    setting_value TEXT,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )");
                
                // Start transaction
                $pdo->beginTransaction();
                
                // Update or insert each setting
                $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) 
                                      VALUES (?, ?) 
                                      ON DUPLICATE KEY UPDATE setting_value = ?");
                
                foreach ($new_settings as $key => $value) {
                    $stmt->execute([$key, $value, $value]);
                }
                
                // Commit transaction
                $pdo->commit();
                
                $success = "Settings saved successfully.";
                
                // Update local settings array
                $settings = $new_settings;
                
                // Convert boolean settings back
                $settings['maintenance_mode'] = (bool)$settings['maintenance_mode'];
                $settings['allow_registrations'] = (bool)$settings['allow_registrations'];
                
            } catch (PDOException $e) {
                // Rollback transaction on error
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $error = "Error saving settings: " . $e->getMessage();
            }
        }
    }
}

// Check if database connection is working
$db_status = testDatabaseConnection();

?>

<h1 class="text-2xl font-semibold mb-6">Site Settings</h1>

<?php if ($error): ?>
<div class="bg-red-900 text-white p-4 rounded-lg mb-6">
    <p><?= htmlspecialchars($error) ?></p>
</div>
<?php endif; ?>

<?php if ($success): ?>
<div class="bg-green-900 text-white p-4 rounded-lg mb-6">
    <p><?= htmlspecialchars($success) ?></p>
</div>
<?php endif; ?>

<!-- Database Status -->
<div class="bg-gray-800 rounded-lg shadow-sm p-6 mb-6">
    <h2 class="text-xl font-medium mb-4">Database Status</h2>
    
    <div class="flex items-center">
        <div class="flex-shrink-0">
            <?php if ($db_status['success']): ?>
            <div class="rounded-full bg-green-900 bg-opacity-30 p-3">
                <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <?php else: ?>
            <div class="rounded-full bg-red-900 bg-opacity-30 p-3">
                <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </div>
            <?php endif; ?>
        </div>
        <div class="ml-4">
            <h3 class="text-lg font-medium <?= $db_status['success'] ? 'text-green-400' : 'text-red-400' ?>">
                <?= $db_status['success'] ? 'Connected' : 'Connection Error' ?>
            </h3>
            <p class="text-sm text-gray-400"><?= htmlspecialchars($db_status['message']) ?></p>
            <?php if (!$db_status['success']): ?>
            <p class="text-sm text-gray-400">Connection string: <?= htmlspecialchars($db_status['connection_string']) ?></p>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if (!$db_status['success']): ?>
    <div class="mt-4">
        <a href="../setup.php" class="bg-purple-600 hover:bg-purple-700 text-white font-medium py-2 px-4 rounded-lg transition-colors inline-flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
            Run Setup Script
        </a>
    </div>
    <?php endif; ?>
</div>

<!-- Settings Form -->
<div class="bg-gray-800 rounded-lg shadow-sm p-6">
    <h2 class="text-xl font-medium mb-6">General Settings</h2>
    
    <form action="settings.php" method="post">
        <input type="hidden" name="action" value="save_settings">
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Site Name -->
            <div class="col-span-1">
                <label for="site_name" class="block text-sm font-medium text-gray-300 mb-2">Site Name *</label>
                <input type="text" id="site_name" name="site_name" value="<?= htmlspecialchars($settings['site_name']) ?>" required
                    class="w-full bg-gray-700 border border-gray-600 rounded-md py-2 px-4 text-white focus:outline-none focus:border-purple-500">
            </div>
            
            <!-- Site Description -->
            <div class="col-span-1">
                <label for="site_description" class="block text-sm font-medium text-gray-300 mb-2">Site Description</label>
                <input type="text" id="site_description" name="site_description" value="<?= htmlspecialchars($settings['site_description']) ?>"
                    class="w-full bg-gray-700 border border-gray-600 rounded-md py-2 px-4 text-white focus:outline-none focus:border-purple-500">
            </div>
            
            <!-- Items Per Page -->
            <div class="col-span-1">
                <label for="items_per_page" class="block text-sm font-medium text-gray-300 mb-2">Items Per Page *</label>
                <input type="number" id="items_per_page" name="items_per_page" value="<?= htmlspecialchars($settings['items_per_page']) ?>" min="1" required
                    class="w-full bg-gray-700 border border-gray-600 rounded-md py-2 px-4 text-white focus:outline-none focus:border-purple-500">
                <p class="text-xs text-gray-400 mt-1">Number of items to display per page in listings</p>
            </div>
            
            <!-- Featured Anime Count -->
            <div class="col-span-1">
                <label for="featured_anime_count" class="block text-sm font-medium text-gray-300 mb-2">Featured Anime Count *</label>
                <input type="number" id="featured_anime_count" name="featured_anime_count" value="<?= htmlspecialchars($settings['featured_anime_count']) ?>" min="1" required
                    class="w-full bg-gray-700 border border-gray-600 rounded-md py-2 px-4 text-white focus:outline-none focus:border-purple-500">
                <p class="text-xs text-gray-400 mt-1">Number of anime to display in the featured section</p>
            </div>
            
            <!-- Maintenance Mode -->
            <div class="col-span-1">
                <label class="flex items-center">
                    <input type="checkbox" name="maintenance_mode" <?= $settings['maintenance_mode'] ? 'checked' : '' ?>
                        class="rounded border-gray-600 text-purple-600 focus:ring-purple-500 h-5 w-5 bg-gray-700">
                    <span class="ml-2 text-gray-300">Maintenance Mode</span>
                </label>
                <p class="text-xs text-gray-400 mt-1">When enabled, only admins can access the site</p>
            </div>
            
            <!-- Allow Registrations -->
            <div class="col-span-1">
                <label class="flex items-center">
                    <input type="checkbox" name="allow_registrations" <?= $settings['allow_registrations'] ? 'checked' : '' ?>
                        class="rounded border-gray-600 text-purple-600 focus:ring-purple-500 h-5 w-5 bg-gray-700">
                    <span class="ml-2 text-gray-300">Allow Registrations</span>
                </label>
                <p class="text-xs text-gray-400 mt-1">When disabled, new users cannot register</p>
            </div>
        </div>
        
        <!-- Form actions -->
        <div class="mt-6 flex justify-end">
            <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 transition-colors">
                Save Settings
            </button>
        </div>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?> 