<?php
// User Management Page
require_once 'includes/header.php';

// Initialize variables
$users = [];
$error = '';
$success = '';
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$items_per_page = 10;
$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Form values for add/edit
$form_user = [
    'id' => '',
    'username' => '',
    'email' => '',
    'display_name' => '',
    'avatar' => '',
    'role' => 'user'
];

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'add_user') {
        // Get form data
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $display_name = trim($_POST['display_name']);
        $avatar = trim($_POST['avatar']);
        $role = $_POST['role'];
        
        // Validate form data
        if (empty($username)) {
            $error = "Username is required.";
        } elseif (empty($email)) {
            $error = "Email is required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email format.";
        } elseif (empty($password)) {
            $error = "Password is required.";
        } elseif ($password !== $confirm_password) {
            $error = "Passwords do not match.";
        } else {
            try {
                // Check if username or email already exists
                $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
                $stmt->execute([$username, $email]);
                
                if ($stmt->rowCount() > 0) {
                    $error = "Username or email already exists.";
                } else {
                    // Hash password
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    
                    // Insert new user
                    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, display_name, avatar, role) 
                                          VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$username, $email, $hashed_password, $display_name, $avatar, $role]);
                    
                    $success = "User added successfully.";
                    
                    // Redirect to user list
                    header("Location: user_management.php?success=added");
                    exit;
                }
            } catch (PDOException $e) {
                $error = "Database error: " . $e->getMessage();
            }
            
            // Preserve form data on error
            $form_user = [
                'id' => '',
                'username' => $username,
                'email' => $email,
                'display_name' => $display_name,
                'avatar' => $avatar,
                'role' => $role
            ];
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'edit_user') {
        // Get form data
        $edit_id = intval($_POST['user_id']);
        $email = trim($_POST['email']);
        $display_name = trim($_POST['display_name']);
        $avatar = trim($_POST['avatar']);
        $role = $_POST['role'];
        $new_password = $_POST['new_password'];
        
        // Validate form data
        if (empty($email)) {
            $error = "Email is required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email format.";
        } elseif ($edit_id <= 0) {
            $error = "Invalid user ID.";
        } else {
            try {
                // Check if email already exists for another user
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $stmt->execute([$email, $edit_id]);
                
                if ($stmt->rowCount() > 0) {
                    $error = "Email already exists for another user.";
                } else {
                    // Update user
                    if (!empty($new_password)) {
                        // Update with new password
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("UPDATE users SET email = ?, display_name = ?, avatar = ?, role = ?, password = ? WHERE id = ?");
                        $stmt->execute([$email, $display_name, $avatar, $role, $hashed_password, $edit_id]);
                    } else {
                        // Update without changing password
                        $stmt = $pdo->prepare("UPDATE users SET email = ?, display_name = ?, avatar = ?, role = ? WHERE id = ?");
                        $stmt->execute([$email, $display_name, $avatar, $role, $edit_id]);
                    }
                    
                    $success = "User updated successfully.";
                    
                    // Redirect to user list
                    header("Location: user_management.php?success=updated");
                    exit;
                }
            } catch (PDOException $e) {
                $error = "Database error: " . $e->getMessage();
            }
            
            // Preserve form data on error
            $form_user = [
                'id' => $edit_id,
                'username' => '', // Will be set from database in edit action
                'email' => $email,
                'display_name' => $display_name,
                'avatar' => $avatar,
                'role' => $role
            ];
        }
    }
}

// If edit action, get user details
if ($action === 'edit' && $user_id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT id, username, email, display_name, avatar, role FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        
        if ($stmt->rowCount() === 1) {
            $form_user = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $error = "User not found.";
            $action = 'list'; // Fallback to list if user not found
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}

// Handle delete action
if ($action === 'delete' && $user_id > 0) {
    try {
        // Check if user is deleting themselves
        if ($user_id === $_SESSION['user_id']) {
            $error = "You cannot delete your own account.";
        } else {
            // Delete user
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            
            $success = "User deleted successfully.";
            header("Location: user_management.php?success=deleted");
            exit;
        }
    } catch (PDOException $e) {
        $error = "Error deleting user: " . $e->getMessage();
    }
}

try {
    // Get total users count for pagination
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $total_items = $stmt->fetchColumn();
    $total_pages = ceil($total_items / $items_per_page);
    
    // Adjust current page if out of bounds
    $current_page = min($current_page, max(1, $total_pages));
    
    // Calculate offset for pagination
    $offset = ($current_page - 1) * $items_per_page;
    
    // Get users list with subscription info
    $stmt = $pdo->prepare("SELECT u.*, 
                          (SELECT COUNT(*) FROM subscriptions s WHERE s.user_id = u.id AND s.status = 'active' AND s.end_date > NOW()) as has_subscription,
                          (u.last_active IS NOT NULL AND u.last_active > DATE_SUB(NOW(), INTERVAL 5 MINUTE)) as is_online
                          FROM users u
                          ORDER BY u.created_at DESC
                          LIMIT ? OFFSET ?");
    $stmt->bindParam(1, $items_per_page, PDO::PARAM_INT);
    $stmt->bindParam(2, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

// Check for success message in URL
if (isset($_GET['success'])) {
    if ($_GET['success'] === 'added') {
        $success = "User added successfully.";
    } elseif ($_GET['success'] === 'updated') {
        $success = "User updated successfully.";
    } elseif ($_GET['success'] === 'deleted') {
        $success = "User deleted successfully.";
    }
}
?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-semibold">User Management</h1>
    <?php if ($action === 'list'): ?>
    <a href="user_management.php?action=add" class="bg-purple-600 hover:bg-purple-700 text-white font-medium py-2 px-4 rounded-lg transition-colors flex items-center">
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
        </svg>
        Add New User
    </a>
    <?php else: ?>
    <a href="user_management.php" class="bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-4 rounded-lg transition-colors flex items-center">
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
<!-- Add User Form -->
<div class="bg-gray-800 rounded-lg shadow-sm p-6">
    <h2 class="text-xl font-medium mb-6">Add New User</h2>
    
    <form action="user_management.php" method="post">
        <input type="hidden" name="action" value="add_user">
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Username -->
            <div class="col-span-1">
                <label for="username" class="block text-sm font-medium text-gray-300 mb-2">Username *</label>
                <input type="text" id="username" name="username" value="<?= htmlspecialchars($form_user['username']) ?>" required
                    class="w-full bg-gray-700 border border-gray-600 rounded-md py-2 px-4 text-white focus:outline-none focus:border-purple-500">
                <p class="text-xs text-gray-400 mt-1">Username cannot be changed after creation</p>
            </div>
            
            <!-- Email -->
            <div class="col-span-1">
                <label for="email" class="block text-sm font-medium text-gray-300 mb-2">Email *</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($form_user['email']) ?>" required
                    class="w-full bg-gray-700 border border-gray-600 rounded-md py-2 px-4 text-white focus:outline-none focus:border-purple-500">
            </div>
            
            <!-- Display Name -->
            <div class="col-span-1">
                <label for="display_name" class="block text-sm font-medium text-gray-300 mb-2">Display Name</label>
                <input type="text" id="display_name" name="display_name" value="<?= htmlspecialchars($form_user['display_name']) ?>"
                    class="w-full bg-gray-700 border border-gray-600 rounded-md py-2 px-4 text-white focus:outline-none focus:border-purple-500">
                <p class="text-xs text-gray-400 mt-1">Leave blank to use username</p>
            </div>
            
            <!-- Avatar URL -->
            <div class="col-span-1">
                <label for="avatar" class="block text-sm font-medium text-gray-300 mb-2">Avatar URL</label>
                <input type="url" id="avatar" name="avatar" value="<?= htmlspecialchars($form_user['avatar']) ?>"
                    placeholder="https://example.com/avatar.jpg"
                    class="w-full bg-gray-700 border border-gray-600 rounded-md py-2 px-4 text-white focus:outline-none focus:border-purple-500">
            </div>
            
            <!-- Role -->
            <div class="col-span-1">
                <label for="role" class="block text-sm font-medium text-gray-300 mb-2">Role</label>
                <select id="role" name="role" 
                    class="w-full bg-gray-700 border border-gray-600 rounded-md py-2 px-4 text-white focus:outline-none focus:border-purple-500">
                    <option value="user" <?= $form_user['role'] === 'user' ? 'selected' : '' ?>>User</option>
                    <option value="admin" <?= $form_user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                </select>
            </div>
            
            <!-- Password -->
            <div class="col-span-1">
                <label for="password" class="block text-sm font-medium text-gray-300 mb-2">Password *</label>
                <input type="password" id="password" name="password" required
                    class="w-full bg-gray-700 border border-gray-600 rounded-md py-2 px-4 text-white focus:outline-none focus:border-purple-500">
            </div>
            
            <!-- Confirm Password -->
            <div class="col-span-1">
                <label for="confirm_password" class="block text-sm font-medium text-gray-300 mb-2">Confirm Password *</label>
                <input type="password" id="confirm_password" name="confirm_password" required
                    class="w-full bg-gray-700 border border-gray-600 rounded-md py-2 px-4 text-white focus:outline-none focus:border-purple-500">
            </div>
        </div>
        
        <!-- Form actions -->
        <div class="mt-6 flex justify-end space-x-3">
            <a href="user_management.php" class="px-4 py-2 bg-gray-700 text-white rounded-md hover:bg-gray-600 transition-colors">
                Cancel
            </a>
            <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 transition-colors">
                Add User
            </button>
        </div>
    </form>
</div>

<?php elseif ($action === 'edit'): ?>
<!-- Edit User Form -->
<div class="bg-gray-800 rounded-lg shadow-sm p-6">
    <h2 class="text-xl font-medium mb-6">Edit User</h2>
    
    <div class="flex mb-6">
        <div class="w-16 h-16 mr-4 overflow-hidden rounded-full bg-gray-700">
            <?php if ($form_user['avatar']): ?>
            <img src="../<?= htmlspecialchars($form_user['avatar']) ?>" alt="<?= htmlspecialchars($form_user['username']) ?>" class="w-full h-full object-cover">
            <?php else: ?>
            <div class="w-full h-full flex items-center justify-center bg-purple-900">
                <span class="text-xl font-medium text-white">
                    <?= strtoupper(substr($form_user['display_name'] ?: $form_user['username'], 0, 1)) ?>
                </span>
            </div>
            <?php endif; ?>
        </div>
        <div>
            <h3 class="text-lg font-medium"><?= htmlspecialchars($form_user['display_name'] ?: $form_user['username']) ?></h3>
            <p class="text-gray-400"><?= htmlspecialchars($form_user['email']) ?></p>
            <p class="text-sm mt-1">
                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                    <?= $form_user['role'] === 'admin' ? 'bg-red-800 text-red-100' : 'bg-blue-800 text-blue-100' ?>">
                    <?= ucfirst(htmlspecialchars($form_user['role'])) ?>
                </span>
            </p>
        </div>
    </div>
    
    <form action="user_management.php" method="post">
        <input type="hidden" name="action" value="edit_user">
        <input type="hidden" name="user_id" value="<?= $form_user['id'] ?>">
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Username (disabled, cannot be changed) -->
            <div class="col-span-1">
                <label class="block text-sm font-medium text-gray-300 mb-2">Username</label>
                <input type="text" value="<?= htmlspecialchars($form_user['username']) ?>" disabled
                    class="w-full bg-gray-600 border border-gray-700 rounded-md py-2 px-4 text-gray-300">
                <p class="text-xs text-gray-400 mt-1">Username cannot be changed</p>
            </div>
            
            <!-- Email -->
            <div class="col-span-1">
                <label for="email" class="block text-sm font-medium text-gray-300 mb-2">Email *</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($form_user['email']) ?>" required
                    class="w-full bg-gray-700 border border-gray-600 rounded-md py-2 px-4 text-white focus:outline-none focus:border-purple-500">
            </div>
            
            <!-- Display Name -->
            <div class="col-span-1">
                <label for="display_name" class="block text-sm font-medium text-gray-300 mb-2">Display Name</label>
                <input type="text" id="display_name" name="display_name" value="<?= htmlspecialchars($form_user['display_name']) ?>"
                    class="w-full bg-gray-700 border border-gray-600 rounded-md py-2 px-4 text-white focus:outline-none focus:border-purple-500">
                <p class="text-xs text-gray-400 mt-1">Leave blank to use username</p>
            </div>
            
            <!-- Avatar URL -->
            <div class="col-span-1">
                <label for="avatar" class="block text-sm font-medium text-gray-300 mb-2">Avatar URL</label>
                <input type="url" id="avatar" name="avatar" value="<?= htmlspecialchars($form_user['avatar']) ?>"
                    placeholder="https://example.com/avatar.jpg"
                    class="w-full bg-gray-700 border border-gray-600 rounded-md py-2 px-4 text-white focus:outline-none focus:border-purple-500">
            </div>
            
            <!-- Role -->
            <div class="col-span-1">
                <label for="role" class="block text-sm font-medium text-gray-300 mb-2">Role</label>
                <select id="role" name="role" <?= $form_user['id'] === $_SESSION['user_id'] ? 'disabled' : '' ?>
                    class="w-full bg-gray-700 border border-gray-600 rounded-md py-2 px-4 text-white focus:outline-none focus:border-purple-500">
                    <option value="user" <?= $form_user['role'] === 'user' ? 'selected' : '' ?>>User</option>
                    <option value="admin" <?= $form_user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                </select>
                <?php if ($form_user['id'] === $_SESSION['user_id']): ?>
                <p class="text-xs text-gray-400 mt-1">You cannot change your own role</p>
                <input type="hidden" name="role" value="<?= $form_user['role'] ?>">
                <?php endif; ?>
            </div>
            
            <!-- New Password -->
            <div class="col-span-1">
                <label for="new_password" class="block text-sm font-medium text-gray-300 mb-2">New Password</label>
                <input type="password" id="new_password" name="new_password"
                    class="w-full bg-gray-700 border border-gray-600 rounded-md py-2 px-4 text-white focus:outline-none focus:border-purple-500">
                <p class="text-xs text-gray-400 mt-1">Leave blank to keep current password</p>
            </div>
        </div>
        
        <!-- Form actions -->
        <div class="mt-6 flex justify-end space-x-3">
            <a href="user_management.php" class="px-4 py-2 bg-gray-700 text-white rounded-md hover:bg-gray-600 transition-colors">
                Cancel
            </a>
            <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 transition-colors">
                Update User
            </button>
        </div>
    </form>
</div>

<?php elseif ($action === 'list'): ?>
<!-- Users listing -->
<div class="bg-gray-800 rounded-lg overflow-hidden shadow-sm">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-700">
            <thead class="bg-gray-700">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Username</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Email</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Role</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Status</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-300 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-700">
                <?php if (empty($users)): ?>
                <tr>
                    <td colspan="6" class="px-6 py-4 text-center text-gray-400">No users found.</td>
                </tr>
                <?php else: ?>
                    <?php foreach ($users as $user): ?>
                    <tr class="hover:bg-gray-700">
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <div class="flex items-center">
                                <div class="h-10 w-10 flex-shrink-0 overflow-hidden rounded-full bg-gray-700 mr-4">
                                    <?php if ($user['avatar']): ?>
                                    <img src="../<?= htmlspecialchars($user['avatar']) ?>" alt="<?= htmlspecialchars($user['username']) ?>" class="h-full w-full object-cover">
                                    <?php else: ?>
                                    <div class="h-full w-full flex items-center justify-center bg-purple-900">
                                        <span class="text-white font-medium">
                                            <?= strtoupper(substr($user['display_name'] ?: $user['username'], 0, 1)) ?>
                                        </span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <div class="font-medium text-white"><?= htmlspecialchars($user['display_name'] ?: $user['username']) ?></div>
                                    <div class="text-xs text-gray-400"><?= htmlspecialchars($user['username']) ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300"><?= htmlspecialchars($user['email']) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                                <?= $user['role'] === 'admin' ? 'bg-red-800 text-red-100' : 'bg-blue-800 text-blue-100' ?>">
                                <?= ucfirst(htmlspecialchars($user['role'])) ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <?php if ($user['is_online']): ?>
                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-800 text-green-100">Online</span>
                            <?php else: ?>
                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-700 text-gray-300">Offline</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300"><?= date('M j, Y', strtotime($user['created_at'])) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right">
                            <div class="flex justify-end space-x-2">
                                <a href="user_management.php?action=edit&id=<?= $user['id'] ?>" class="text-yellow-400 hover:text-yellow-300 transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </a>
                                
                                <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                                <a href="user_management.php?action=delete&id=<?= $user['id'] ?>" class="text-red-400 hover:text-red-300 transition-colors" 
                                   onclick="return confirm('Are you sure you want to delete this user?')">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </a>
                                <?php endif; ?>
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