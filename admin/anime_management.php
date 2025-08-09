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
$anime_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

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
    } elseif (isset($_POST['action']) && $_POST['action'] === 'edit_anime') {
        // Get form data
        $edit_id = intval($_POST['anime_id']);
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $cover_image = trim($_POST['cover_image']);
        $release_year = trim($_POST['release_year']);
        $genres = trim($_POST['genres']);
        $status = $_POST['status'];
        
        // Validate form data
        if (empty($title)) {
            $error = "Title is required.";
        } elseif ($edit_id <= 0) {
            $error = "Invalid anime ID.";
        } else {
            try {
                // Update anime
                $stmt = $pdo->prepare("UPDATE anime SET title = ?, description = ?, cover_image = ?, 
                                      release_year = ?, genres = ?, status = ? WHERE id = ?");
                $stmt->execute([$title, $description, $cover_image, $release_year, $genres, $status, $edit_id]);
                
                $success = "Anime updated successfully.";
                
                // Redirect back to list
                header("Location: anime_management.php?success=updated");
                exit;
            } catch (PDOException $e) {
                $error = "Database error: " . $e->getMessage();
                
                // Preserve form data on error
                $form_anime = [
                    'id' => $edit_id,
                    'title' => $title,
                    'description' => $description,
                    'cover_image' => $cover_image,
                    'release_year' => $release_year,
                    'genres' => $genres,
                    'status' => $status
                ];
            }
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'add_season') {
        // Get form data
        $anime_id = intval($_POST['anime_id']);
        $season_number = intval($_POST['season_number']);
        $part_number = intval($_POST['part_number'] ?? 1);
        $title = trim($_POST['season_title']);
        $description = trim($_POST['description']);
        $release_year = trim($_POST['release_year']);
        
        // Validate form data
        if ($anime_id <= 0) {
            $error = "Invalid anime ID.";
        } elseif ($season_number <= 0) {
            $error = "Season number must be greater than 0.";
        } else {
            try {
                // Check if season number and part number combination already exists for this anime
                $stmt = $pdo->prepare("SELECT id FROM seasons WHERE anime_id = ? AND season_number = ? AND part_number = ?");
                $stmt->execute([$anime_id, $season_number, $part_number]);
                
                if ($stmt->rowCount() > 0) {
                    $error = "Season number and part combination already exists for this anime.";
                } else {
                    // Insert new season
                    $stmt = $pdo->prepare("INSERT INTO seasons (anime_id, season_number, part_number, title, description, release_year) 
                                          VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$anime_id, $season_number, $part_number, $title, $description, $release_year]);
                    
                    $success = "Season added successfully.";
                    
                    // Redirect back to seasons view
                    header("Location: anime_management.php?action=seasons&id=$anime_id&success=season_added");
                    exit;
                }
            } catch (PDOException $e) {
                $error = "Database error: " . $e->getMessage();
            }
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'edit_season') {
        // Get form data
        $season_id = intval($_POST['season_id']);
        $anime_id = intval($_POST['anime_id']);
        $season_number = intval($_POST['season_number']);
        $part_number = intval($_POST['part_number'] ?? 1);
        $title = trim($_POST['season_title']);
        $description = trim($_POST['description']);
        $release_year = trim($_POST['release_year']);
        
        // Validate form data
        if ($season_id <= 0 || $anime_id <= 0) {
            $error = "Invalid season or anime ID.";
        } elseif ($season_number <= 0) {
            $error = "Season number must be greater than 0.";
        } else {
            try {
                // Check if season number and part number combination already exists for this anime (excluding current season)
                $stmt = $pdo->prepare("SELECT id FROM seasons WHERE anime_id = ? AND season_number = ? AND part_number = ? AND id != ?");
                $stmt->execute([$anime_id, $season_number, $part_number, $season_id]);
                
                if ($stmt->rowCount() > 0) {
                    $error = "Season number and part combination already exists for this anime.";
                } else {
                    // Update season
                    $stmt = $pdo->prepare("UPDATE seasons SET season_number = ?, part_number = ?, title = ?, description = ?, release_year = ? 
                                          WHERE id = ? AND anime_id = ?");
                    $stmt->execute([$season_number, $part_number, $title, $description, $release_year, $season_id, $anime_id]);
                    
                    $success = "Season updated successfully.";
                    
                    // Redirect back to seasons view
                    header("Location: anime_management.php?action=seasons&id=$anime_id&success=season_updated");
                    exit;
                }
            } catch (PDOException $e) {
                $error = "Database error: " . $e->getMessage();
            }
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'add_episode') {
        // Get form data
        $season_id = intval($_POST['season_id']);
        $anime_id = intval($_POST['anime_id']);
        $episode_number = intval($_POST['episode_number']);
        $title = trim($_POST['episode_title']);
        $description = trim($_POST['description']);
        $duration = intval($_POST['duration']);
        $video_url = trim($_POST['video_url']);
        $is_premium = isset($_POST['is_premium']) ? 1 : 0;
        
        // Validate form data
        if ($season_id <= 0 || $anime_id <= 0) {
            $error = "Invalid season or anime ID.";
        } elseif ($episode_number <= 0) {
            $error = "Episode number must be greater than 0.";
        } elseif (empty($video_url)) {
            $error = "Video URL is required.";
        } else {
            try {
                // Check if episode number already exists for this season
                $stmt = $pdo->prepare("SELECT id FROM episodes WHERE season_id = ? AND episode_number = ?");
                $stmt->execute([$season_id, $episode_number]);
                
                if ($stmt->rowCount() > 0) {
                    $error = "Episode number already exists for this season.";
                } else {
                    // Insert new episode
                    $stmt = $pdo->prepare("INSERT INTO episodes (season_id, episode_number, title, description, duration, video_url, is_premium) 
                                          VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$season_id, $episode_number, $title, $description, $duration, $video_url, $is_premium]);
                    
                    $success = "Episode added successfully.";
                    
                    // Redirect back to seasons view
                    header("Location: anime_management.php?action=seasons&id=$anime_id&success=episode_added");
                    exit;
                }
            } catch (PDOException $e) {
                $error = "Database error: " . $e->getMessage();
            }
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'edit_episode') {
        // Get form data
        $episode_id = intval($_POST['episode_id']);
        $season_id = intval($_POST['season_id']);
        $anime_id = intval($_POST['anime_id']);
        $episode_number = intval($_POST['episode_number']);
        $title = trim($_POST['episode_title']);
        $description = trim($_POST['description']);
        $duration = intval($_POST['duration']);
        $video_url = trim($_POST['video_url']);
        $is_premium = isset($_POST['is_premium']) ? 1 : 0;
        
        // Validate form data
        if ($episode_id <= 0 || $season_id <= 0 || $anime_id <= 0) {
            $error = "Invalid episode, season, or anime ID.";
        } elseif ($episode_number <= 0) {
            $error = "Episode number must be greater than 0.";
        } elseif (empty($video_url)) {
            $error = "Video URL is required.";
        } else {
            try {
                // Check if episode number already exists for this season (excluding current episode)
                $stmt = $pdo->prepare("SELECT id FROM episodes WHERE season_id = ? AND episode_number = ? AND id != ?");
                $stmt->execute([$season_id, $episode_number, $episode_id]);
                
                if ($stmt->rowCount() > 0) {
                    $error = "Episode number already exists for this season.";
                } else {
                    // Update episode
                    $stmt = $pdo->prepare("UPDATE episodes SET episode_number = ?, title = ?, description = ?, 
                                          duration = ?, video_url = ?, is_premium = ? WHERE id = ? AND season_id = ?");
                    $stmt->execute([$episode_number, $title, $description, $duration, $video_url, $is_premium, $episode_id, $season_id]);
                    
                    $success = "Episode updated successfully.";
                    
                    // Redirect back to seasons view
                    header("Location: anime_management.php?action=seasons&id=$anime_id&success=episode_updated");
                    exit;
                }
            } catch (PDOException $e) {
                $error = "Database error: " . $e->getMessage();
            }
        }
    }
}

// If edit action, get anime details
if ($action === 'edit' && $anime_id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM anime WHERE id = ?");
        $stmt->execute([$anime_id]);
        
        if ($stmt->rowCount() === 1) {
            $form_anime = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $error = "Anime not found.";
            $action = 'list'; // Fallback to list if anime not found
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}

// Handle delete action
if ($action === 'delete' && $anime_id > 0) {
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // Delete episodes first (foreign key constraint)
        $stmt = $pdo->prepare("DELETE e FROM episodes e 
                              JOIN seasons s ON e.season_id = s.id 
                              WHERE s.anime_id = ?");
        $stmt->execute([$anime_id]);
        
        // Delete seasons
        $stmt = $pdo->prepare("DELETE FROM seasons WHERE anime_id = ?");
        $stmt->execute([$anime_id]);
        
        // Delete anime
        $stmt = $pdo->prepare("DELETE FROM anime WHERE id = ?");
        $stmt->execute([$anime_id]);
        
        // Commit transaction
        $pdo->commit();
        
        $success = "Anime and all related seasons/episodes deleted successfully.";
        header("Location: anime_management.php?success=deleted");
        exit;
    } catch (PDOException $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        $error = "Error deleting anime: " . $e->getMessage();
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

// Handle delete season action
if ($action === 'delete_season' && isset($_GET['id']) && isset($_GET['anime_id'])) {
    $season_id = intval($_GET['id']);
    $anime_id = intval($_GET['anime_id']);
    
    if ($season_id > 0 && $anime_id > 0) {
        try {
            // Start transaction
            $pdo->beginTransaction();
            
            // Delete episodes first
            $stmt = $pdo->prepare("DELETE FROM episodes WHERE season_id = ?");
            $stmt->execute([$season_id]);
            
            // Delete season
            $stmt = $pdo->prepare("DELETE FROM seasons WHERE id = ? AND anime_id = ?");
            $stmt->execute([$season_id, $anime_id]);
            
            // Commit transaction
            $pdo->commit();
            
            $success = "Season and all its episodes deleted successfully.";
            header("Location: anime_management.php?action=seasons&id=$anime_id&success=season_deleted");
            exit;
        } catch (PDOException $e) {
            // Rollback transaction on error
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = "Error deleting season: " . $e->getMessage();
        }
    }
}

// Handle delete episode action
if ($action === 'delete_episode' && isset($_GET['id']) && isset($_GET['season_id']) && isset($_GET['anime_id'])) {
    $episode_id = intval($_GET['id']);
    $season_id = intval($_GET['season_id']);
    $anime_id = intval($_GET['anime_id']);
    
    if ($episode_id > 0 && $season_id > 0 && $anime_id > 0) {
        try {
            // Delete episode
            $stmt = $pdo->prepare("DELETE FROM episodes WHERE id = ? AND season_id = ?");
            $stmt->execute([$episode_id, $season_id]);
            
            $success = "Episode deleted successfully.";
            header("Location: anime_management.php?action=seasons&id=$anime_id&success=episode_deleted");
            exit;
        } catch (PDOException $e) {
            $error = "Error deleting episode: " . $e->getMessage();
        }
    }
}

// Check for success message in URL
if (isset($_GET['success'])) {
    if ($_GET['success'] === 'updated') {
        $success = "Anime updated successfully.";
    } elseif ($_GET['success'] === 'deleted') {
        $success = "Anime deleted successfully.";
    } elseif ($_GET['success'] === 'season_added') {
        $success = "Season added successfully.";
    } elseif ($_GET['success'] === 'season_updated') {
        $success = "Season updated successfully.";
    } elseif ($_GET['success'] === 'season_deleted') {
        $success = "Season deleted successfully.";
    } elseif ($_GET['success'] === 'episode_added') {
        $success = "Episode added successfully.";
    } elseif ($_GET['success'] === 'episode_updated') {
        $success = "Episode updated successfully.";
    } elseif ($_GET['success'] === 'episode_deleted') {
        $success = "Episode deleted successfully.";
    }
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

<?php elseif ($action === 'edit'): ?>
<!-- Edit Anime Form -->
<div class="bg-gray-800 rounded-lg shadow-sm p-6">
    <h2 class="text-xl font-medium mb-6">Edit Anime</h2>
    
    <div class="flex mb-6">
        <div class="w-24 h-32 mr-4 overflow-hidden rounded bg-gray-700">
            <?php if ($form_anime['cover_image']): ?>
            <img src="<?= htmlspecialchars($form_anime['cover_image']) ?>" alt="<?= htmlspecialchars($form_anime['title']) ?>" class="w-full h-full object-cover">
            <?php else: ?>
            <div class="w-full h-full flex items-center justify-center">
                <svg class="w-8 h-8 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
            </div>
            <?php endif; ?>
        </div>
        <div>
            <h3 class="text-lg font-medium"><?= htmlspecialchars($form_anime['title']) ?></h3>
            <p class="text-gray-400"><?= htmlspecialchars($form_anime['release_year'] ?? '') ?></p>
            
            <div class="mt-2">
                <a href="../anime.php?id=<?= $form_anime['id'] ?>" target="_blank" class="text-purple-400 hover:text-purple-300 transition-colors flex items-center text-sm">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                    </svg>
                    View on site
                </a>
            </div>
        </div>
    </div>
    
    <form action="anime_management.php" method="post">
        <input type="hidden" name="action" value="edit_anime">
        <input type="hidden" name="anime_id" value="<?= $form_anime['id'] ?>">
        
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
        <div class="mt-6 flex justify-between">
            <div>
                <a href="anime_management.php?action=seasons&id=<?= $form_anime['id'] ?>" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                    Manage Seasons & Episodes
                </a>
            </div>
            <div class="flex space-x-3">
                <a href="anime_management.php" class="px-4 py-2 bg-gray-700 text-white rounded-md hover:bg-gray-600 transition-colors">
                    Cancel
                </a>
                <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 transition-colors">
                    Update Anime
                </button>
            </div>
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

<?php if ($action === 'seasons' && $anime_id > 0): ?>
<!-- Seasons & Episodes Management -->
<?php
// Get anime details
try {
    $stmt = $pdo->prepare("SELECT * FROM anime WHERE id = ?");
    $stmt->execute([$anime_id]);
    
    if ($stmt->rowCount() === 0) {
        $error = "Anime not found.";
    } else {
        $anime = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get seasons for this anime
        $stmt = $pdo->prepare("SELECT * FROM seasons WHERE anime_id = ? ORDER BY season_number ASC");
        $stmt->execute([$anime_id]);
        $seasons = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get episode count for each season
        foreach ($seasons as $key => $season) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM episodes WHERE season_id = ?");
            $stmt->execute([$season['id']]);
            $seasons[$key]['episode_count'] = $stmt->fetchColumn();
        }
    }
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<div class="mb-6">
    <div class="flex items-center mb-4">
        <a href="anime_management.php?action=edit&id=<?= $anime_id ?>" class="bg-gray-700 p-2 rounded-md hover:bg-gray-600 transition-colors mr-4">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
        </a>
        <h2 class="text-xl font-medium"><?= htmlspecialchars($anime['title']) ?> - Seasons & Episodes</h2>
    </div>
    
    <div class="flex mb-4 space-x-4">
        <a href="#add-season-modal" onclick="openAddSeasonModal()" class="bg-purple-600 hover:bg-purple-700 text-white font-medium py-2 px-4 rounded-lg transition-colors flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            Add New Season
        </a>
    </div>
</div>

<?php if (empty($seasons)): ?>
<div class="bg-gray-800 p-12 rounded-lg text-center">
    <svg class="w-16 h-16 text-gray-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h18M3 16h18"></path>
    </svg>
    <h3 class="text-xl font-medium text-gray-300 mb-2">No Seasons Yet</h3>
    <p class="text-gray-400 mb-6">Start by adding your first season to this anime.</p>
    <button onclick="openAddSeasonModal()" class="bg-purple-600 hover:bg-purple-700 text-white font-medium py-2 px-6 rounded-lg transition-colors">
        Add First Season
    </button>
</div>
<?php else: ?>
<!-- Seasons accordion -->
<div class="space-y-4">
    <?php foreach ($seasons as $season): ?>
    <div class="bg-gray-800 rounded-lg overflow-hidden shadow-sm" x-data="{ open: false }">
        <!-- Season header -->
        <div @click="open = !open" class="flex justify-between items-center p-4 cursor-pointer bg-gray-700 hover:bg-gray-600 transition-colors">
            <div class="flex items-center">
                <span class="text-lg font-medium">
                    Season <?= htmlspecialchars($season['season_number']) ?>
                    <?= $season['part_number'] > 1 ? ' Part ' . htmlspecialchars($season['part_number']) : '' ?>: 
                    <?= htmlspecialchars($season['title'] ?? 'Untitled') ?>
                </span>
                <span class="ml-4 text-sm text-gray-400"><?= $season['episode_count'] ?> episode<?= $season['episode_count'] !== 1 ? 's' : '' ?></span>
            </div>
            <div class="flex items-center">
                <button @click.stop="openEditSeasonModal(<?= $season['id'] ?>)" class="p-1 text-yellow-400 hover:text-yellow-300 mr-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                </button>
                <button @click.stop="if(confirm('Are you sure you want to delete this season? This will also delete all episodes in this season.')) window.location.href='anime_management.php?action=delete_season&id=<?= $season['id'] ?>&anime_id=<?= $anime_id ?>'" class="p-1 text-red-400 hover:text-red-300 mr-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                </button>
                <svg class="w-5 h-5 transform transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </div>
        </div>
        
        <!-- Season content -->
        <div x-show="open" class="p-4 border-t border-gray-700">
            <!-- Season details -->
            <div class="mb-4">
                <p class="text-sm text-gray-400"><?= htmlspecialchars($season['description'] ?? '') ?></p>
                <p class="text-sm text-gray-400 mt-1">Released: <?= htmlspecialchars($season['release_year'] ?? 'Unknown') ?></p>
            </div>
            
            <!-- Add episode button -->
            <div class="mb-4">
                <button @click="openAddEpisodeModal(<?= $season['id'] ?>)" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition-colors flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Add New Episode
                </button>
            </div>
            
            <?php
            // Get episodes for this season
            $stmt = $pdo->prepare("SELECT * FROM episodes WHERE season_id = ? ORDER BY episode_number ASC");
            $stmt->execute([$season['id']]);
            $episodes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            ?>
            
            <?php if (empty($episodes)): ?>
            <div class="text-center py-8 text-gray-500">
                No episodes in this season yet.
            </div>
            <?php else: ?>
            <!-- Episodes table -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-700">
                    <thead class="bg-gray-700">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">#</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Title</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Duration</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Premium</th>
                            <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-300 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700">
                        <?php foreach ($episodes as $episode): ?>
                        <tr class="hover:bg-gray-700">
                            <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-300"><?= $episode['episode_number'] ?></td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-300"><?= htmlspecialchars($episode['title']) ?></td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-300"><?= htmlspecialchars($episode['duration'] ?? '-') ?> min</td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm">
                                <?php if ($episode['is_premium']): ?>
                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-800 text-yellow-100">Premium</span>
                                <?php else: ?>
                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-700 text-gray-300">Free</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-right">
                                <div class="flex justify-end space-x-2">
                                    <button @click="openEditEpisodeModal(<?= $episode['id'] ?>)" class="text-yellow-400 hover:text-yellow-300 transition-colors">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                    </button>
                                    <a href="../player.php?anime=<?= $anime_id ?>&season=<?= $season['id'] ?>&episode=<?= $episode['id'] ?>" target="_blank" class="text-gray-400 hover:text-gray-200 transition-colors">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </a>
                                    <button @click="if(confirm('Are you sure you want to delete this episode?')) window.location.href='anime_management.php?action=delete_episode&id=<?= $episode['id'] ?>&season_id=<?= $season['id'] ?>&anime_id=<?= $anime_id ?>'" class="text-red-400 hover:text-red-300 transition-colors">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Add Season Modal -->
<div id="add-season-modal" class="fixed inset-0 flex items-center justify-center z-50 hidden">
    <div class="absolute inset-0 bg-black bg-opacity-50"></div>
    <div class="relative bg-gray-800 rounded-lg max-w-md w-full mx-4 overflow-hidden shadow-xl">
        <div class="px-6 py-4 border-b border-gray-700 flex justify-between items-center">
            <h3 class="text-xl font-medium">Add New Season</h3>
            <button onclick="closeAddSeasonModal()" class="text-gray-400 hover:text-gray-200">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <form id="add-season-form" action="anime_management.php" method="post">
            <input type="hidden" name="action" value="add_season">
            <input type="hidden" name="anime_id" value="<?= $anime_id ?>">
            
            <div class="p-6">
                <div class="mb-4">
                    <label for="season_number" class="block text-sm font-medium text-gray-300 mb-2">Season Number *</label>
                    <input type="number" min="1" id="season_number" name="season_number" required
                        class="w-full bg-gray-700 border border-gray-600 rounded-md py-2 px-4 text-white focus:outline-none focus:border-purple-500">
                </div>
                
                <div class="mb-4">
                    <label for="part_number" class="block text-sm font-medium text-gray-300 mb-2">Part Number</label>
                    <input type="number" min="1" id="part_number" name="part_number" value="1"
                        class="w-full bg-gray-700 border border-gray-600 rounded-md py-2 px-4 text-white focus:outline-none focus:border-purple-500">
                    <p class="text-xs text-gray-400 mt-1">Use for multiple parts within a season (default: 1)</p>
                </div>
                
                <div class="mb-4">
                    <label for="season_title" class="block text-sm font-medium text-gray-300 mb-2">Season Title</label>
                    <input type="text" id="season_title" name="season_title" 
                        class="w-full bg-gray-700 border border-gray-600 rounded-md py-2 px-4 text-white focus:outline-none focus:border-purple-500">
                </div>
                
                <div class="mb-4">
                    <label for="release_year" class="block text-sm font-medium text-gray-300 mb-2">Release Year</label>
                    <input type="text" id="release_year" name="release_year" placeholder="YYYY" pattern="[0-9]{4}"
                        class="w-full bg-gray-700 border border-gray-600 rounded-md py-2 px-4 text-white focus:outline-none focus:border-purple-500">
                </div>
                
                <div class="mb-4">
                    <label for="description" class="block text-sm font-medium text-gray-300 mb-2">Description</label>
                    <textarea id="description" name="description" rows="3"
                        class="w-full bg-gray-700 border border-gray-600 rounded-md py-2 px-4 text-white focus:outline-none focus:border-purple-500"></textarea>
                </div>
                
                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" onclick="closeAddSeasonModal()" class="px-4 py-2 bg-gray-700 text-white rounded-md hover:bg-gray-600 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 transition-colors">
                        Add Season
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Edit Season Modal -->
<div id="edit-season-modal" class="fixed inset-0 flex items-center justify-center z-50 hidden">
    <div class="absolute inset-0 bg-black bg-opacity-50"></div>
    <div class="relative bg-gray-800 rounded-lg max-w-md w-full mx-4 overflow-hidden shadow-xl">
        <div class="px-6 py-4 border-b border-gray-700 flex justify-between items-center">
            <h3 class="text-xl font-medium">Edit Season</h3>
            <button onclick="closeEditSeasonModal()" class="text-gray-400 hover:text-gray-200">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <form id="edit-season-form" action="anime_management.php" method="post">
            <input type="hidden" name="action" value="edit_season">
            <input type="hidden" name="anime_id" value="<?= $anime_id ?>">
            <input type="hidden" name="season_id" id="edit_season_id" value="">
            
            <div class="p-6">
                <div class="mb-4">
                    <label for="edit_season_number" class="block text-sm font-medium text-gray-300 mb-2">Season Number *</label>
                    <input type="number" min="1" id="edit_season_number" name="season_number" required
                        class="w-full bg-gray-700 border border-gray-600 rounded-md py-2 px-4 text-white focus:outline-none focus:border-purple-500">
                </div>
                
                <div class="mb-4">
                    <label for="edit_part_number" class="block text-sm font-medium text-gray-300 mb-2">Part Number</label>
                    <input type="number" min="1" id="edit_part_number" name="part_number" value="1"
                        class="w-full bg-gray-700 border border-gray-600 rounded-md py-2 px-4 text-white focus:outline-none focus:border-purple-500">
                    <p class="text-xs text-gray-400 mt-1">Use for multiple parts within a season (default: 1)</p>
                </div>
                
                <div class="mb-4">
                    <label for="edit_season_title" class="block text-sm font-medium text-gray-300 mb-2">Season Title</label>
                    <input type="text" id="edit_season_title" name="season_title" 
                        class="w-full bg-gray-700 border border-gray-600 rounded-md py-2 px-4 text-white focus:outline-none focus:border-purple-500">
                </div>
                
                <div class="mb-4">
                    <label for="edit_release_year" class="block text-sm font-medium text-gray-300 mb-2">Release Year</label>
                    <input type="text" id="edit_release_year" name="release_year" placeholder="YYYY" pattern="[0-9]{4}"
                        class="w-full bg-gray-700 border border-gray-600 rounded-md py-2 px-4 text-white focus:outline-none focus:border-purple-500">
                </div>
                
                <div class="mb-4">
                    <label for="edit_description" class="block text-sm font-medium text-gray-300 mb-2">Description</label>
                    <textarea id="edit_description" name="description" rows="3"
                        class="w-full bg-gray-700 border border-gray-600 rounded-md py-2 px-4 text-white focus:outline-none focus:border-purple-500"></textarea>
                </div>
                
                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" onclick="closeEditSeasonModal()" class="px-4 py-2 bg-gray-700 text-white rounded-md hover:bg-gray-600 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 transition-colors">
                        Update Season
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Add Episode Modal -->
<div id="add-episode-modal" class="fixed inset-0 flex items-center justify-center z-50 hidden">
    <div class="absolute inset-0 bg-black bg-opacity-50"></div>
    <div class="relative bg-gray-800 rounded-lg max-w-md w-full mx-4 overflow-hidden shadow-xl">
        <div class="px-6 py-4 border-b border-gray-700 flex justify-between items-center">
            <h3 class="text-xl font-medium">Add New Episode</h3>
            <button onclick="closeAddEpisodeModal()" class="text-gray-400 hover:text-gray-200">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <form id="add-episode-form" action="anime_management.php" method="post">
            <input type="hidden" name="action" value="add_episode">
            <input type="hidden" name="anime_id" value="<?= $anime_id ?>">
            <input type="hidden" name="season_id" id="add_episode_season_id" value="">
            
            <div class="p-6">
                <div class="mb-4">
                    <label for="episode_number" class="block text-sm font-medium text-gray-300 mb-2">Episode Number *</label>
                    <input type="number" min="1" id="episode_number" name="episode_number" required
                        class="w-full bg-gray-700 border border-gray-600 rounded-md py-2 px-4 text-white focus:outline-none focus:border-purple-500">
                </div>
                
                <div class="mb-4">
                    <label for="episode_title" class="block text-sm font-medium text-gray-300 mb-2">Episode Title</label>
                    <input type="text" id="episode_title" name="episode_title" 
                        class="w-full bg-gray-700 border border-gray-600 rounded-md py-2 px-4 text-white focus:outline-none focus:border-purple-500">
                </div>
                
                <div class="mb-4">
                    <label for="duration" class="block text-sm font-medium text-gray-300 mb-2">Duration (minutes)</label>
                    <input type="number" min="1" id="duration" name="duration" value="24"
                        class="w-full bg-gray-700 border border-gray-600 rounded-md py-2 px-4 text-white focus:outline-none focus:border-purple-500">
                </div>
                
                <div class="mb-4">
                    <label for="video_url" class="block text-sm font-medium text-gray-300 mb-2">Video URL *</label>
                    <input type="url" id="video_url" name="video_url" required
                        placeholder="https://example.com/video.mp4"
                        class="w-full bg-gray-700 border border-gray-600 rounded-md py-2 px-4 text-white focus:outline-none focus:border-purple-500">
                </div>
                
                <div class="mb-4">
                    <label for="episode_description" class="block text-sm font-medium text-gray-300 mb-2">Description</label>
                    <textarea id="episode_description" name="description" rows="3"
                        class="w-full bg-gray-700 border border-gray-600 rounded-md py-2 px-4 text-white focus:outline-none focus:border-purple-500"></textarea>
                </div>
                
                <div class="mb-4">
                    <label class="flex items-center">
                        <input type="checkbox" name="is_premium" id="is_premium"
                            class="rounded border-gray-600 text-purple-600 focus:ring-purple-500 h-5 w-5 bg-gray-700">
                        <span class="ml-2 text-gray-300">Premium Episode</span>
                    </label>
                    <p class="text-xs text-gray-400 mt-1">Premium episodes are only available to subscribers</p>
                </div>
                
                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" onclick="closeAddEpisodeModal()" class="px-4 py-2 bg-gray-700 text-white rounded-md hover:bg-gray-600 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 transition-colors">
                        Add Episode
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Edit Episode Modal -->
<div id="edit-episode-modal" class="fixed inset-0 flex items-center justify-center z-50 hidden">
    <div class="absolute inset-0 bg-black bg-opacity-50"></div>
    <div class="relative bg-gray-800 rounded-lg max-w-md w-full mx-4 overflow-hidden shadow-xl">
        <div class="px-6 py-4 border-b border-gray-700 flex justify-between items-center">
            <h3 class="text-xl font-medium">Edit Episode</h3>
            <button onclick="closeEditEpisodeModal()" class="text-gray-400 hover:text-gray-200">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <form id="edit-episode-form" action="anime_management.php" method="post">
            <input type="hidden" name="action" value="edit_episode">
            <input type="hidden" name="anime_id" value="<?= $anime_id ?>">
            <input type="hidden" name="season_id" id="edit_episode_season_id" value="">
            <input type="hidden" name="episode_id" id="edit_episode_id" value="">
            
            <div class="p-6">
                <div class="mb-4">
                    <label for="edit_episode_number" class="block text-sm font-medium text-gray-300 mb-2">Episode Number *</label>
                    <input type="number" min="1" id="edit_episode_number" name="episode_number" required
                        class="w-full bg-gray-700 border border-gray-600 rounded-md py-2 px-4 text-white focus:outline-none focus:border-purple-500">
                </div>
                
                <div class="mb-4">
                    <label for="edit_episode_title" class="block text-sm font-medium text-gray-300 mb-2">Episode Title</label>
                    <input type="text" id="edit_episode_title" name="episode_title" 
                        class="w-full bg-gray-700 border border-gray-600 rounded-md py-2 px-4 text-white focus:outline-none focus:border-purple-500">
                </div>
                
                <div class="mb-4">
                    <label for="edit_duration" class="block text-sm font-medium text-gray-300 mb-2">Duration (minutes)</label>
                    <input type="number" min="1" id="edit_duration" name="duration"
                        class="w-full bg-gray-700 border border-gray-600 rounded-md py-2 px-4 text-white focus:outline-none focus:border-purple-500">
                </div>
                
                <div class="mb-4">
                    <label for="edit_video_url" class="block text-sm font-medium text-gray-300 mb-2">Video URL *</label>
                    <input type="url" id="edit_video_url" name="video_url" required
                        placeholder="https://example.com/video.mp4"
                        class="w-full bg-gray-700 border border-gray-600 rounded-md py-2 px-4 text-white focus:outline-none focus:border-purple-500">
                </div>
                
                <div class="mb-4">
                    <label for="edit_episode_description" class="block text-sm font-medium text-gray-300 mb-2">Description</label>
                    <textarea id="edit_episode_description" name="description" rows="3"
                        class="w-full bg-gray-700 border border-gray-600 rounded-md py-2 px-4 text-white focus:outline-none focus:border-purple-500"></textarea>
                </div>
                
                <div class="mb-4">
                    <label class="flex items-center">
                        <input type="checkbox" name="is_premium" id="edit_is_premium"
                            class="rounded border-gray-600 text-purple-600 focus:ring-purple-500 h-5 w-5 bg-gray-700">
                        <span class="ml-2 text-gray-300">Premium Episode</span>
                    </label>
                    <p class="text-xs text-gray-400 mt-1">Premium episodes are only available to subscribers</p>
                </div>
                
                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" onclick="closeEditEpisodeModal()" class="px-4 py-2 bg-gray-700 text-white rounded-md hover:bg-gray-600 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 transition-colors">
                        Update Episode
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    // Season modal functions
    function openAddSeasonModal() {
        document.getElementById('add-season-modal').classList.remove('hidden');
    }
    
    function closeAddSeasonModal() {
        document.getElementById('add-season-modal').classList.add('hidden');
    }
    
    function openEditSeasonModal(seasonId) {
        // Get season data via AJAX
        fetch('ajax/get_season.php?id=' + seasonId)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Populate form fields
                    document.getElementById('edit_season_id').value = data.season.id;
                    document.getElementById('edit_season_number').value = data.season.season_number;
                    document.getElementById('edit_part_number').value = data.season.part_number || 1;
                    document.getElementById('edit_season_title').value = data.season.title || '';
                    document.getElementById('edit_release_year').value = data.season.release_year || '';
                    document.getElementById('edit_description').value = data.season.description || '';
                    
                    // Show modal
                    document.getElementById('edit-season-modal').classList.remove('hidden');
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error fetching season data:', error);
                alert('Failed to load season data. Please try again.');
            });
    }
    
    function closeEditSeasonModal() {
        document.getElementById('edit-season-modal').classList.add('hidden');
    }
    
    // Episode modal functions
    function openAddEpisodeModal(seasonId) {
        document.getElementById('add_episode_season_id').value = seasonId;
        document.getElementById('add-episode-modal').classList.remove('hidden');
    }
    
    function closeAddEpisodeModal() {
        document.getElementById('add-episode-modal').classList.add('hidden');
    }
    
    function openEditEpisodeModal(episodeId) {
        // Get episode data via AJAX
        fetch('ajax/get_episode.php?id=' + episodeId)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Populate form fields
                    document.getElementById('edit_episode_id').value = data.episode.id;
                    document.getElementById('edit_episode_season_id').value = data.episode.season_id;
                    document.getElementById('edit_episode_number').value = data.episode.episode_number;
                    document.getElementById('edit_episode_title').value = data.episode.title || '';
                    document.getElementById('edit_duration').value = data.episode.duration || '';
                    document.getElementById('edit_video_url').value = data.episode.video_url || '';
                    document.getElementById('edit_episode_description').value = data.episode.description || '';
                    document.getElementById('edit_is_premium').checked = data.episode.is_premium == 1;
                    
                    // Show modal
                    document.getElementById('edit-episode-modal').classList.remove('hidden');
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error fetching episode data:', error);
                alert('Failed to load episode data. Please try again.');
            });
    }
    
    function closeEditEpisodeModal() {
        document.getElementById('edit-episode-modal').classList.add('hidden');
    }
</script>

<?php endif; ?>

<?php require_once 'includes/footer.php'; ?> 