<?php
// Browse anime page
session_start();
require_once 'config.php';

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Function to get user data
function getUserData() {
    if (!isLoggedIn()) {
        return null;
    }
    
    global $host, $dbname, $username, $password;
    
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stmt = $pdo->prepare("SELECT id, username, email, display_name, avatar, role FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $user;
    } catch (PDOException $e) {
        return null;
    }
}

// Get user data if logged in
$userData = getUserData();

// Initialize variables
$anime_list = [];
$error = '';
$total_pages = 1;
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$items_per_page = 12;
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$genre_filter = isset($_GET['genre']) ? trim($_GET['genre']) : '';
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : '';
$sort_by = isset($_GET['sort']) ? trim($_GET['sort']) : 'title_asc';

// Get all available genres for filter
$genres = [];
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->query("SELECT DISTINCT genres FROM anime");
    $genre_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Extract individual genres from comma-separated lists
    $all_genres = [];
    foreach ($genre_results as $row) {
        $genre_array = explode(',', $row['genres']);
        foreach ($genre_array as $genre) {
            $genre = trim($genre);
            if (!empty($genre) && !in_array($genre, $all_genres)) {
                $all_genres[] = $genre;
            }
        }
    }
    
    // Sort genres alphabetically
    sort($all_genres);
    $genres = $all_genres;
    
} catch (PDOException $e) {
    $error = "Error fetching genres: " . $e->getMessage();
}

// Get anime list with filtering and pagination
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Build WHERE clause for filtering
    $where_conditions = [];
    $params = [];
    
    if (!empty($search_query)) {
        $where_conditions[] = "(title LIKE ? OR description LIKE ?)";
        $params[] = "%$search_query%";
        $params[] = "%$search_query%";
    }
    
    if (!empty($genre_filter)) {
        $where_conditions[] = "genres LIKE ?";
        $params[] = "%$genre_filter%";
    }
    
    if (!empty($status_filter)) {
        $where_conditions[] = "status = ?";
        $params[] = $status_filter;
    }
    
    $where_clause = empty($where_conditions) ? "" : "WHERE " . implode(" AND ", $where_conditions);
    
    // Count total results for pagination
    $count_sql = "SELECT COUNT(*) FROM anime $where_clause";
    $stmt = $pdo->prepare($count_sql);
    $stmt->execute($params);
    $total_items = $stmt->fetchColumn();
    $total_pages = ceil($total_items / $items_per_page);
    
    // Adjust current page if out of bounds
    $current_page = min($current_page, max(1, $total_pages));
    
    // Calculate offset for pagination
    $offset = ($current_page - 1) * $items_per_page;
    
    // Determine sort order
    $sort_column = 'title';
    $sort_direction = 'ASC';
    
    switch ($sort_by) {
        case 'title_desc':
            $sort_column = 'title';
            $sort_direction = 'DESC';
            break;
        case 'newest':
            $sort_column = 'release_year';
            $sort_direction = 'DESC';
            break;
        case 'oldest':
            $sort_column = 'release_year';
            $sort_direction = 'ASC';
            break;
        default:
            // Default to title_asc
            break;
    }
    
    // Fetch results
    $sql = "SELECT * FROM anime $where_clause ORDER BY $sort_column $sort_direction LIMIT ? OFFSET ?";
    $stmt = $pdo->prepare($sql);
    
    // Add pagination parameters to the params array
    $params[] = (int)$items_per_page;
    $params[] = (int)$offset;
    
    // Bind all parameters
    for ($i = 0; $i < count($params); $i++) {
        $param_type = is_int($params[$i]) ? PDO::PARAM_INT : PDO::PARAM_STR;
        $stmt->bindValue($i + 1, $params[$i], $param_type);
    }
    
    $stmt->execute();
    $anime_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

// Set page title
$pageTitle = "Browse Anime - AnimeElite";

// Include header
include 'includes/header.php';
?>

<main class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-8">Browse Anime</h1>
    
    <?php if ($error): ?>
    <div class="bg-red-900 text-white p-4 rounded-lg mb-4">
        <h3 class="font-bold mb-2">Error</h3>
        <p><?= htmlspecialchars($error) ?></p>
    </div>
    <?php endif; ?>
    
    <!-- Search and filters -->
    <div class="bg-gray-800 rounded-lg p-6 mb-8">
        <form method="GET" action="browse.php" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Search input -->
            <div>
                <label for="search" class="block text-gray-300 mb-2">Search</label>
                <input type="text" id="search" name="search" value="<?= htmlspecialchars($search_query) ?>" 
                       class="w-full px-4 py-2 rounded-md bg-gray-700 text-white border border-gray-600 focus:outline-none focus:border-purple-500"
                       placeholder="Search by title or description">
            </div>
            
            <!-- Genre filter -->
            <div>
                <label for="genre" class="block text-gray-300 mb-2">Genre</label>
                <select id="genre" name="genre" class="w-full px-4 py-2 rounded-md bg-gray-700 text-white border border-gray-600 focus:outline-none focus:border-purple-500">
                    <option value="">All Genres</option>
                    <?php foreach ($genres as $genre): ?>
                    <option value="<?= htmlspecialchars($genre) ?>" <?= $genre === $genre_filter ? 'selected' : '' ?>>
                        <?= htmlspecialchars($genre) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Status filter -->
            <div>
                <label for="status" class="block text-gray-300 mb-2">Status</label>
                <select id="status" name="status" class="w-full px-4 py-2 rounded-md bg-gray-700 text-white border border-gray-600 focus:outline-none focus:border-purple-500">
                    <option value="">All Status</option>
                    <option value="ongoing" <?= $status_filter === 'ongoing' ? 'selected' : '' ?>>Ongoing</option>
                    <option value="completed" <?= $status_filter === 'completed' ? 'selected' : '' ?>>Completed</option>
                    <option value="upcoming" <?= $status_filter === 'upcoming' ? 'selected' : '' ?>>Upcoming</option>
                </select>
            </div>
            
            <!-- Sort by -->
            <div>
                <label for="sort" class="block text-gray-300 mb-2">Sort By</label>
                <select id="sort" name="sort" class="w-full px-4 py-2 rounded-md bg-gray-700 text-white border border-gray-600 focus:outline-none focus:border-purple-500">
                    <option value="title_asc" <?= $sort_by === 'title_asc' ? 'selected' : '' ?>>Title (A-Z)</option>
                    <option value="title_desc" <?= $sort_by === 'title_desc' ? 'selected' : '' ?>>Title (Z-A)</option>
                    <option value="newest" <?= $sort_by === 'newest' ? 'selected' : '' ?>>Newest First</option>
                    <option value="oldest" <?= $sort_by === 'oldest' ? 'selected' : '' ?>>Oldest First</option>
                </select>
            </div>
            
            <!-- Submit button -->
            <div class="md:col-span-2 lg:col-span-4 flex justify-between items-center">
                <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white font-medium py-2 px-6 rounded-md transition-colors">
                    Apply Filters
                </button>
                
                <a href="browse.php" class="text-purple-400 hover:text-purple-300 transition-colors">Reset Filters</a>
            </div>
        </form>
    </div>
    
    <!-- Results count and pagination info -->
    <div class="flex justify-between items-center mb-6">
        <p class="text-gray-400">
            Showing <?= count($anime_list) ?> of <?= $total_items ?> results
            <?= !empty($search_query) ? "for \"" . htmlspecialchars($search_query) . "\"" : "" ?>
        </p>
        
        <?php if ($total_pages > 1): ?>
        <div class="text-sm">
            Page <?= $current_page ?> of <?= $total_pages ?>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Anime grid -->
    <?php if (empty($anime_list)): ?>
    <div class="bg-gray-800 rounded-lg p-12 text-center">
        <p class="text-xl text-gray-400 mb-4">No anime found matching your criteria.</p>
        <a href="browse.php" class="text-purple-400 hover:text-purple-300 transition-colors">Clear filters and try again</a>
    </div>
    <?php else: ?>
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6 mb-8">
        <?php foreach($anime_list as $anime): ?>
        <div class="bg-gray-800 rounded-lg overflow-hidden transition-transform duration-300 hover:-translate-y-2 hover:shadow-lg">
            <a href="anime.php?id=<?= $anime['id'] ?>">
                <div class="relative aspect-w-16 aspect-h-9 h-48">
                    <?php if ($anime['cover_image']): ?>
                        <img src="<?= htmlspecialchars($anime['cover_image']) ?>" alt="<?= htmlspecialchars($anime['title']) ?>" class="object-cover w-full h-full">
                    <?php else: ?>
                        <div class="w-full h-full bg-gray-700 flex items-center justify-center">
                            <span class="text-gray-400">No Image</span>
                        </div>
                    <?php endif; ?>
                    <div class="absolute top-2 right-2">
                        <span class="bg-purple-600 text-white text-xs px-2 py-1 rounded"><?= htmlspecialchars($anime['status']) ?></span>
                    </div>
                </div>
                <div class="p-4">
                    <h3 class="text-lg font-medium text-white truncate"><?= htmlspecialchars($anime['title']) ?></h3>
                    <div class="flex justify-between items-center mt-2">
                        <span class="text-sm text-gray-400"><?= htmlspecialchars($anime['release_year'] ?? 'Unknown') ?></span>
                        
                        <?php
                        // Show first genre only
                        $anime_genres = explode(',', $anime['genres']);
                        if (!empty($anime_genres[0])):
                        ?>
                        <span class="px-2 py-1 bg-gray-700 text-gray-300 text-xs rounded"><?= htmlspecialchars(trim($anime_genres[0])) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <div class="flex justify-center mt-8">
        <nav class="flex space-x-2" aria-label="Pagination">
            <!-- Previous page link -->
            <?php if ($current_page > 1): ?>
            <a href="?page=<?= $current_page - 1 ?>&search=<?= urlencode($search_query) ?>&genre=<?= urlencode($genre_filter) ?>&status=<?= urlencode($status_filter) ?>&sort=<?= urlencode($sort_by) ?>" 
               class="px-4 py-2 bg-gray-700 text-white rounded-md hover:bg-gray-600 transition-colors">
                Previous
            </a>
            <?php else: ?>
            <span class="px-4 py-2 bg-gray-700 text-gray-500 rounded-md cursor-not-allowed">Previous</span>
            <?php endif; ?>
            
            <!-- Page numbers -->
            <div class="hidden md:flex space-x-2">
                <?php
                // Show up to 5 page links centered around the current page
                $start_page = max(1, min($current_page - 2, $total_pages - 4));
                $end_page = min($total_pages, max($current_page + 2, 5));
                
                for ($i = $start_page; $i <= $end_page; $i++):
                ?>
                <a href="?page=<?= $i ?>&search=<?= urlencode($search_query) ?>&genre=<?= urlencode($genre_filter) ?>&status=<?= urlencode($status_filter) ?>&sort=<?= urlencode($sort_by) ?>" 
                   class="px-4 py-2 rounded-md <?= $i === $current_page ? 'bg-purple-600 text-white' : 'bg-gray-700 text-white hover:bg-gray-600' ?> transition-colors">
                    <?= $i ?>
                </a>
                <?php endfor; ?>
            </div>
            
            <!-- Next page link -->
            <?php if ($current_page < $total_pages): ?>
            <a href="?page=<?= $current_page + 1 ?>&search=<?= urlencode($search_query) ?>&genre=<?= urlencode($genre_filter) ?>&status=<?= urlencode($status_filter) ?>&sort=<?= urlencode($sort_by) ?>" 
               class="px-4 py-2 bg-gray-700 text-white rounded-md hover:bg-gray-600 transition-colors">
                Next
            </a>
            <?php else: ?>
            <span class="px-4 py-2 bg-gray-700 text-gray-500 rounded-md cursor-not-allowed">Next</span>
            <?php endif; ?>
        </nav>
    </div>
    <?php endif; ?>
</main>

<?php
// Include footer
include 'includes/footer.php';
?> 