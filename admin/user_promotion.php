<?php
// User Promotion Page for Admin
require_once 'includes/header.php';

// Initialize variables
$users = [];
$error = '';
$success = '';
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$items_per_page = 10;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Process user promotion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'promote_user') {
        $user_id = intval($_POST['user_id']);
        $duration = intval($_POST['duration']);
        $plan_name = $_POST['plan_name'];
        
        if ($user_id <= 0) {
            $error = "Invalid user ID.";
        } elseif ($duration <= 0) {
            $error = "Duration must be greater than 0.";
        } else {
            try {
                // Check if user exists
                $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                
                if ($stmt->rowCount() === 0) {
                    $error = "User not found.";
                } else {
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    // Begin transaction
                    $pdo->beginTransaction();
                    
                    // Check for existing subscriptions and cancel them
                    $stmt = $pdo->prepare("UPDATE subscriptions SET status = 'cancelled' WHERE user_id = ? AND status = 'active'");
                    $stmt->execute([$user_id]);
                    
                    // Calculate end date
                    $end_date = date('Y-m-d H:i:s', strtotime("+$duration months"));
                    
                    // Create new subscription
                    $stmt = $pdo->prepare("INSERT INTO subscriptions (user_id, plan_name, status, start_date, end_date) 
                                          VALUES (?, ?, 'active', NOW(), ?)");
                    $stmt->execute([$user_id, $plan_name, $end_date]);
                    
                    // Commit transaction
                    $pdo->commit();
                    
                    $success = "User " . htmlspecialchars($user['username']) . " has been promoted to " . htmlspecialchars($plan_name) . " for " . $duration . " month" . ($duration != 1 ? 's' : '') . ".";
                }
            } catch (PDOException $e) {
                // Rollback on error
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $error = "Database error: " . $e->getMessage();
            }
        }
    }
}

// Get users list with subscription info
try {
    // Count total users for pagination
    $count_query = "SELECT COUNT(*) FROM users WHERE role = 'user'";
    $search_params = [];
    
    if (!empty($search)) {
        $count_query .= " AND (username LIKE ? OR email LIKE ?)";
        $search_params[] = "%$search%";
        $search_params[] = "%$search%";
    }
    
    $stmt = $pdo->prepare($count_query);
    $stmt->execute($search_params);
    $total_items = $stmt->fetchColumn();
    $total_pages = ceil($total_items / $items_per_page);
    
    // Adjust current page if out of bounds
    $current_page = min($current_page, max(1, $total_pages));
    
    // Calculate offset
    $offset = ($current_page - 1) * $items_per_page;
    
    // Get users with subscription status
    $query = "SELECT u.*, 
              (SELECT s.status FROM subscriptions s WHERE s.user_id = u.id AND s.status = 'active' LIMIT 1) as subscription_status,
              (SELECT s.plan_name FROM subscriptions s WHERE s.user_id = u.id AND s.status = 'active' LIMIT 1) as plan_name,
              (SELECT s.end_date FROM subscriptions s WHERE s.user_id = u.id AND s.status = 'active' LIMIT 1) as subscription_end
              FROM users u
              WHERE u.role = 'user'";
    
    if (!empty($search)) {
        $query .= " AND (u.username LIKE ? OR u.email LIKE ?)";
    }
    
    $query .= " ORDER BY u.username ASC LIMIT ? OFFSET ?";
    
    $stmt = $pdo->prepare($query);
    
    $i = 1;
    if (!empty($search)) {
        $stmt->bindValue($i++, "%$search%", PDO::PARAM_STR);
        $stmt->bindValue($i++, "%$search%", PDO::PARAM_STR);
    }
    
    $stmt->bindValue($i++, $items_per_page, PDO::PARAM_INT);
    $stmt->bindValue($i++, $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-semibold">User Premium Promotion</h1>
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

<!-- Search Form -->
<div class="mb-6">
    <form action="user_promotion.php" method="get" class="flex items-center">
        <div class="relative flex-grow">
            <input type="text" name="search" placeholder="Search users..." 
                value="<?= htmlspecialchars($search) ?>"
                class="w-full py-2 px-4 pr-10 rounded-l-lg bg-gray-700 border border-gray-600 text-white focus:outline-none focus:border-purple-500">
            <?php if (!empty($search)): ?>
            <a href="user_promotion.php" class="absolute right-3 top-2.5 text-gray-400 hover:text-white">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </a>
            <?php endif; ?>
        </div>
        <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white py-2 px-4 rounded-r-lg transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
        </button>
    </form>
</div>

<!-- Users Table -->
<div class="bg-gray-800 rounded-lg overflow-hidden shadow-sm">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-700">
            <thead class="bg-gray-700">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Username</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Email</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Current Status</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Expiration</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-300 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-700">
                <?php if (empty($users)): ?>
                <tr>
                    <td colspan="5" class="px-6 py-4 text-center text-gray-400">
                        <?= empty($search) ? 'No users found.' : 'No users matching "' . htmlspecialchars($search) . '".' ?>
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($users as $user): ?>
                    <tr class="hover:bg-gray-700">
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <div class="font-medium text-white"><?= htmlspecialchars($user['username']) ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300"><?= htmlspecialchars($user['email']) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <?php if ($user['subscription_status'] === 'active'): ?>
                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-800 text-green-100">
                                    <?= htmlspecialchars($user['plan_name'] ?? 'Premium') ?>
                                </span>
                            <?php else: ?>
                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-800 text-gray-300">Free</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                            <?= $user['subscription_end'] ? date('M j, Y', strtotime($user['subscription_end'])) : 'N/A' ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right">
                            <button onclick="openPromoteModal(<?= $user['id'] ?>, '<?= htmlspecialchars($user['username']) ?>')" 
                                class="text-purple-400 hover:text-purple-300 transition-colors">
                                <svg class="w-5 h-5 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                                </svg>
                                Promote
                            </button>
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
        <a href="?page=<?= $current_page - 1 ?>&search=<?= urlencode($search) ?>" class="px-4 py-2 bg-gray-700 text-white rounded-md hover:bg-gray-600 transition-colors">
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
            <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>" 
               class="px-4 py-2 rounded-md <?= $i === $current_page ? 'bg-purple-600 text-white' : 'bg-gray-700 text-white hover:bg-gray-600' ?> transition-colors">
                <?= $i ?>
            </a>
            <?php endfor; ?>
        </div>
        
        <!-- Next page link -->
        <?php if ($current_page < $total_pages): ?>
        <a href="?page=<?= $current_page + 1 ?>&search=<?= urlencode($search) ?>" class="px-4 py-2 bg-gray-700 text-white rounded-md hover:bg-gray-600 transition-colors">
            Next
        </a>
        <?php else: ?>
        <span class="px-4 py-2 bg-gray-700 text-gray-500 rounded-md cursor-not-allowed">Next</span>
        <?php endif; ?>
    </nav>
</div>
<?php endif; ?>

<!-- Promote User Modal -->
<div id="promote-modal" class="fixed inset-0 flex items-center justify-center z-50 hidden">
    <div class="absolute inset-0 bg-black bg-opacity-50"></div>
    <div class="relative bg-gray-800 rounded-lg max-w-md w-full mx-4 overflow-hidden shadow-xl">
        <div class="px-6 py-4 border-b border-gray-700 flex justify-between items-center">
            <h3 class="text-xl font-medium">Promote User to Premium</h3>
            <button onclick="closePromoteModal()" class="text-gray-400 hover:text-gray-200">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <form id="promote-user-form" action="user_promotion.php" method="post">
            <input type="hidden" name="action" value="promote_user">
            <input type="hidden" name="user_id" id="promote_user_id" value="">
            
            <div class="p-6">
                <p class="mb-4 text-gray-300">You are promoting <span id="promote_username" class="font-semibold text-white"></span> to premium status.</p>
                
                <div class="mb-4">
                    <label for="plan_name" class="block text-sm font-medium text-gray-300 mb-2">Subscription Plan *</label>
                    <select id="plan_name" name="plan_name" required
                        class="w-full bg-gray-700 border border-gray-600 rounded-md py-2 px-4 text-white focus:outline-none focus:border-purple-500">
                        <option value="Premium">Premium</option>
                        <option value="Ultimate">Ultimate</option>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label for="duration" class="block text-sm font-medium text-gray-300 mb-2">Duration (Months) *</label>
                    <input type="number" id="duration" name="duration" value="1" min="1" required
                        class="w-full bg-gray-700 border border-gray-600 rounded-md py-2 px-4 text-white focus:outline-none focus:border-purple-500">
                    <p class="text-xs text-gray-400 mt-1">Number of months for premium access</p>
                </div>
                
                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" onclick="closePromoteModal()" class="px-4 py-2 bg-gray-700 text-white rounded-md hover:bg-gray-600 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition-colors">
                        Promote User
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    function openPromoteModal(userId, username) {
        document.getElementById('promote_user_id').value = userId;
        document.getElementById('promote_username').textContent = username;
        document.getElementById('promote-modal').classList.remove('hidden');
    }
    
    function closePromoteModal() {
        document.getElementById('promote-modal').classList.add('hidden');
    }
</script>

<?php require_once 'includes/footer.php'; ?> 