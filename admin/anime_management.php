<?php
// Anime Management Page
require_once 'includes/header.php';

// Initialize variables
$anime_list = [];
$error = '';
$success = '';
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$items_per_page = 10;

// Form values for add/edit
$form_anime = [
    'id' => '',
    'title' => '',
    'description' => '',
    'cover_image' => '',
    'release_year' => '',
    'genres' => '',
    'status' => 'ongoing'
];

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'add_anime') {
        // Get form data
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $cover_image = trim($_POST['cover_image']);
        $release_year = trim($_POST['release_year']);
        $genres = trim($_POST['genres']);
        $status = $_POST['status'];
        
        // Validate form data
        if (empty($title)) {
            $error = "Title is required.";
        } else {
            try {
                // Insert new anime
                $stmt = $pdo->prepare("INSERT INTO anime (title, description, cover_image, release_year, genres, status) 
                                      VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$title, $description, $cover_image, $release_year, $genres, $status]);
                
                $new_anime_id = $pdo->lastInsertId();
                $success = "Anime added successfully.";
                
                // Redirect to anime management page after successful addition
                header("Location: anime_management.php?action=seasons&id=$new_anime_id");
                exit;
            } catch (PDOException $e) {
                $error = "Database error: " . $e->getMessage();
                
                // Preserve form data on error
                $form_anime = [
                    'id' => '',
                    'title' => $title,
                    'description' => $description,
                    'cover_image' => $cover_image,
                    'release_year' => $release_year,
                    'genres' => $genres,
                    'status' => $status
                ];
            }
        }
    }
}

try {
    // Get total anime count for pagination
    $stmt = $pdo->query("SELECT COUNT(*) FROM anime");
    $total_items = $stmt->fetchColumn();
    $total_pages = ceil($total_items / $items_per_page);
    
    // Adjust current page if out of bounds
    $current_page = min($current_page, max(1, $total_pages));
    
    // Calculate offset for pagination
    $offset = ($current_page - 1) * $items_per_page;
    
    // Get anime list
    $stmt = $pdo->prepare("SELECT a.*, 
                          (SELECT COUNT(*) FROM seasons WHERE anime_id = a.id) as season_count,
                          (SELECT COUNT(*) FROM seasons s JOIN episodes e ON s.id = e.season_id WHERE s.anime_id = a.id) as episode_count
                          FROM anime a
                          ORDER BY a.title ASC
                          LIMIT ? OFFSET ?");
    $stmt->bindParam(1, $items_per_page, PDO::PARAM_INT);
    $stmt->bindParam(2, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $anime_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-semibold">Anime Management</h1>
    <?php if ($action === 'list'): ?>
    <a href="anime_management.php?action=add" class="bg-purple-600 hover:bg-purple-700 text-white font-medium py-2 px-4 rounded-lg transition-colors flex items-center">
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
        </svg>
        Add New Anime
    </a>
    <?php else: ?>
    <a href="anime_management.php" class="bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-4 rounded-lg transition-colors flex items-center">
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
        </svg>
        Back to List
    </a>
    <?php endif; ?>
</div>

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

<?php if ($action === 'add'): ?>
<!-- Add Anime Form -->
<div class="bg-gray-800 rounded-lg shadow-sm p-6">
    <h2 class="text-xl font-medium mb-6">Add New Anime</h2>
    
    <form action="anime_management.php" method="post">
        <input type="hidden" name="action" value="add_anime">
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Title -->
            <div class="col-span-1">
                <label for="title" class="block text-sm font-medium text-gray-300 mb-2">Title *</label>
                <input type="text" id="title" name="title" value="<?= htmlspecialchars($form_anime['title']) ?>" required
                    class="w-full bg-gray-700 border border-gray-600 rounded-md py-2 px-4 text-white focus:outline-none focus:border-purple-500">
            </div>
            
            <!-- Release Year -->
            <div class="col-span-1">
                <label for="release_year" class="block text-sm font-medium text-gray-300 mb-2">Release Year</label>
                <input type="text" id="release_year" name="release_year" value="<?= htmlspecialchars($form_anime['release_year']) ?>" 
                    placeholder="YYYY" pattern="[0-9]{4}"
                    class="w-full bg-gray-700 border border-gray-600 rounded-md py-2 px-4 text-white focus:outline-none focus:border-purple-500">
            </div>
            
            <!-- Cover Image URL -->
            <div class="col-span-2">
                <label for="cover_image" class="block text-sm font-medium text-gray-300 mb-2">Cover Image URL</label>
                <input type="url" id="cover_image" name="cover_image" value="<?= htmlspecialchars($form_anime['cover_image']) ?>"
                    placeholder="https://example.com/image.jpg"
                    class="w-full bg-gray-700 border border-gray-600 rounded-md py-2 px-4 text-white focus:outline-none focus:border-purple-500">
                <p class="text-xs text-gray-400 mt-1">Direct link to the image. Leave empty to use a placeholder.</p>
            </div>
            
            <!-- Genres -->
            <div class="col-span-1">
                <label for="genres" class="block text-sm font-medium text-gray-300 mb-2">Genres</label>
                <input type="text" id="genres" name="genres" value="<?= htmlspecialchars($form_anime['genres']) ?>"
                    placeholder="Action, Adventure, Drama"
                    class="w-full bg-gray-700 border border-gray-600 rounded-md py-2 px-4 text-white focus:outline-none focus:border-purple-500">
                <p class="text-xs text-gray-400 mt-1">Separate multiple genres with commas</p>
            </div>
            
            <!-- Status -->
            <div class="col-span-1">
                <label for="status" class="block text-sm font-medium text-gray-300 mb-2">Status</label>
                <select id="status" name="status" 
                    class="w-full bg-gray-700 border border-gray-600 rounded-md py-2 px-4 text-white focus:outline-none focus:border-purple-500">
                    <option value="ongoing" <?= $form_anime['status'] === 'ongoing' ? 'selected' : '' ?>>Ongoing</option>
                    <option value="completed" <?= $form_anime['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                    <option value="upcoming" <?= $form_anime['status'] === 'upcoming' ? 'selected' : '' ?>>Upcoming</option>
                </select>
            </div>
            
            <!-- Description -->
            <div class="col-span-2">
                <label for="description" class="block text-sm font-medium text-gray-300 mb-2">Description</label>
                <textarea id="description" name="description" rows="4"
                    class="w-full bg-gray-700 border border-gray-600 rounded-md py-2 px-4 text-white focus:outline-none focus:border-purple-500"><?= htmlspecialchars($form_anime['description']) ?></textarea>
            </div>
        </div>
        
        <!-- Form actions -->
        <div class="mt-6 flex justify-end space-x-3">
            <a href="anime_management.php" class="px-4 py-2 bg-gray-700 text-white rounded-md hover:bg-gray-600 transition-colors">
                Cancel
            </a>
            <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 transition-colors">
                Add Anime
            </button>
        </div>
    </form>
</div>

<?php elseif ($action === 'list'): ?>
<!-- Anime listing -->
<div class="bg-gray-800 rounded-lg overflow-hidden shadow-sm">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-700">
            <thead class="bg-gray-700">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Title</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Release Year</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Status</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Seasons</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Episodes</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-300 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-700">
                <?php if (empty($anime_list)): ?>
                <tr>
                    <td colspan="6" class="px-6 py-4 text-center text-gray-400">No anime found.</td>
                </tr>
                <?php else: ?>
                    <?php foreach ($anime_list as $anime): ?>
                    <tr class="hover:bg-gray-700">
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <div class="flex items-center">
                                <div class="h-10 w-10 flex-shrink-0 overflow-hidden rounded bg-gray-600 mr-4">
                                    <?php if ($anime['cover_image']): ?>
                                    <img src="<?= htmlspecialchars($anime['cover_image']) ?>" alt="<?= htmlspecialchars($anime['title']) ?>" class="h-full w-full object-cover">
                                    <?php endif; ?>
                                </div>
                                <div class="font-medium text-white"><?= htmlspecialchars($anime['title']) ?></div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300"><?= htmlspecialchars($anime['release_year'] ?? 'Unknown') ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                                <?php 
                                switch ($anime['status']) {
                                    case 'ongoing':
                                        echo 'bg-blue-800 text-blue-100';
                                        break;
                                    case 'completed':
                                        echo 'bg-green-800 text-green-100';
                                        break;
                                    case 'upcoming':
                                        echo 'bg-yellow-800 text-yellow-100';
                                        break;
                                    default:
                                        echo 'bg-gray-800 text-gray-100';
                                }
                                ?>">
                                <?= ucfirst(htmlspecialchars($anime['status'])) ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300"><?= $anime['season_count'] ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300"><?= $anime['episode_count'] ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right">
                            <div class="flex justify-end space-x-2">
                                <a href="../anime.php?id=<?= $anime['id'] ?>" target="_blank" class="text-gray-400 hover:text-gray-200 transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                </a>
                                <a href="anime_management.php?action=seasons&id=<?= $anime['id'] ?>" class="text-blue-400 hover:text-blue-300 transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
                                    </svg>
                                </a>
                                <a href="anime_management.php?action=edit&id=<?= $anime['id'] ?>" class="text-yellow-400 hover:text-yellow-300 transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </a>
                                <a href="anime_management.php?action=delete&id=<?= $anime['id'] ?>" class="text-red-400 hover:text-red-300 transition-colors" 
                                   onclick="return confirm('Are you sure you want to delete this anime? This will also delete all its seasons and episodes.')">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Pagination -->
<?php if ($total_pages > 1): ?>
<div class="flex justify-center mt-6">
    <nav class="flex space-x-2" aria-label="Pagination">
        <!-- Previous page link -->
        <?php if ($current_page > 1): ?>
        <a href="?page=<?= $current_page - 1 ?>" class="px-4 py-2 bg-gray-700 text-white rounded-md hover:bg-gray-600 transition-colors">
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
            <a href="?page=<?= $i ?>" 
               class="px-4 py-2 rounded-md <?= $i === $current_page ? 'bg-purple-600 text-white' : 'bg-gray-700 text-white hover:bg-gray-600' ?> transition-colors">
                <?= $i ?>
            </a>
            <?php endfor; ?>
        </div>
        
        <!-- Next page link -->
        <?php if ($current_page < $total_pages): ?>
        <a href="?page=<?= $current_page + 1 ?>" class="px-4 py-2 bg-gray-700 text-white rounded-md hover:bg-gray-600 transition-colors">
            Next
        </a>
        <?php else: ?>
        <span class="px-4 py-2 bg-gray-700 text-gray-500 rounded-md cursor-not-allowed">Next</span>
        <?php endif; ?>
    </nav>
</div>
<?php endif; ?>

<?php endif; ?>

<?php require_once 'includes/footer.php'; ?> 