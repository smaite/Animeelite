<?php
// Coupon Management Page
require_once 'includes/header.php';

// Initialize variables
$coupons = [];
$error = '';
$success = '';
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$items_per_page = 10;
$coupon_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Form values for add/edit
$form_coupon = [
    'id' => '',
    'code' => '',
    'description' => '',
    'duration_months' => 1,
    'max_uses' => 1,
    'expiry_date' => date('Y-m-d', strtotime('+1 month')),
    'is_active' => 1
];

// Generate a random coupon code
function generateCouponCode() {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $code = '';
    for ($i = 0; $i < 8; $i++) {
        $code .= $chars[rand(0, strlen($chars) - 1)];
    }
    return $code;
}

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'add_coupon') {
        // Get form data
        $code = strtoupper(trim($_POST['code']));
        $description = trim($_POST['description']);
        $duration_months = intval($_POST['duration_months']);
        $max_uses = intval($_POST['max_uses']);
        $expiry_date = $_POST['expiry_date'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        // Validate form data
        if (empty($code)) {
            $error = "Coupon code is required.";
        } elseif ($duration_months <= 0) {
            $error = "Duration must be greater than 0.";
        } elseif ($max_uses <= 0) {
            $error = "Maximum uses must be greater than 0.";
        } elseif (empty($expiry_date)) {
            $error = "Expiry date is required.";
        } else {
            try {
                // Check if coupon code already exists
                $stmt = $pdo->prepare("SELECT id FROM coupons WHERE code = ?");
                $stmt->execute([$code]);
                
                if ($stmt->rowCount() > 0) {
                    $error = "Coupon code already exists.";
                } else {
                    // Insert new coupon
                    $stmt = $pdo->prepare("INSERT INTO coupons (code, description, duration_months, max_uses, used_count, expiry_date, is_active, created_at) 
                                          VALUES (?, ?, ?, ?, 0, ?, ?, NOW())");
                    $stmt->execute([$code, $description, $duration_months, $max_uses, $expiry_date, $is_active]);
                    
                    $success = "Coupon added successfully.";
                    
                    // Redirect to coupon list
                    header("Location: coupon_management.php?success=added");
                    exit;
                }
            } catch (PDOException $e) {
                $error = "Database error: " . $e->getMessage();
            }
            
            // Preserve form data on error
            $form_coupon = [
                'id' => '',
                'code' => $code,
                'description' => $description,
                'duration_months' => $duration_months,
                'max_uses' => $max_uses,
                'expiry_date' => $expiry_date,
                'is_active' => $is_active
            ];
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'edit_coupon') {
        // Get form data
        $edit_id = intval($_POST['coupon_id']);
        $description = trim($_POST['description']);
        $duration_months = intval($_POST['duration_months']);
        $max_uses = intval($_POST['max_uses']);
        $expiry_date = $_POST['expiry_date'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        // Validate form data
        if ($duration_months <= 0) {
            $error = "Duration must be greater than 0.";
        } elseif ($max_uses <= 0) {
            $error = "Maximum uses must be greater than 0.";
        } elseif (empty($expiry_date)) {
            $error = "Expiry date is required.";
        } elseif ($edit_id <= 0) {
            $error = "Invalid coupon ID.";
        } else {
            try {
                // Update coupon
                $stmt = $pdo->prepare("UPDATE coupons SET description = ?, duration_months = ?, max_uses = ?, expiry_date = ?, is_active = ? WHERE id = ?");
                $stmt->execute([$description, $duration_months, $max_uses, $expiry_date, $is_active, $edit_id]);
                
                $success = "Coupon updated successfully.";
                
                // Redirect to coupon list
                header("Location: coupon_management.php?success=updated");
                exit;
            } catch (PDOException $e) {
                $error = "Database error: " . $e->getMessage();
            }
            
            // Preserve form data on error
            $form_coupon = [
                'id' => $edit_id,
                'code' => '', // Will be set from database in edit action
                'description' => $description,
                'duration_months' => $duration_months,
                'max_uses' => $max_uses,
                'expiry_date' => $expiry_date,
                'is_active' => $is_active
            ];
        }
    }
}

// If edit action, get coupon details
if ($action === 'edit' && $coupon_id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM coupons WHERE id = ?");
        $stmt->execute([$coupon_id]);
        
        if ($stmt->rowCount() === 1) {
            $form_coupon = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $error = "Coupon not found.";
            $action = 'list'; // Fallback to list if coupon not found
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}

// Handle toggle active status
if ($action === 'toggle' && $coupon_id > 0) {
    try {
        // Get current status
        $stmt = $pdo->prepare("SELECT is_active FROM coupons WHERE id = ?");
        $stmt->execute([$coupon_id]);
        $current_status = $stmt->fetchColumn();
        
        // Toggle status
        $new_status = $current_status ? 0 : 1;
        $stmt = $pdo->prepare("UPDATE coupons SET is_active = ? WHERE id = ?");
        $stmt->execute([$new_status, $coupon_id]);
        
        $status_text = $new_status ? "activated" : "deactivated";
        $success = "Coupon $status_text successfully.";
        header("Location: coupon_management.php?success=$status_text");
        exit;
    } catch (PDOException $e) {
        $error = "Error toggling coupon status: " . $e->getMessage();
    }
}

// Handle delete action
if ($action === 'delete' && $coupon_id > 0) {
    try {
        // Delete coupon
        $stmt = $pdo->prepare("DELETE FROM coupons WHERE id = ?");
        $stmt->execute([$coupon_id]);
        
        $success = "Coupon deleted successfully.";
        header("Location: coupon_management.php?success=deleted");
        exit;
    } catch (PDOException $e) {
        $error = "Error deleting coupon: " . $e->getMessage();
    }
}

try {
    // Get total coupons count for pagination
    $stmt = $pdo->query("SELECT COUNT(*) FROM coupons");
    $total_items = $stmt->fetchColumn();
    $total_pages = ceil($total_items / $items_per_page);
    
    // Adjust current page if out of bounds
    $current_page = min($current_page, max(1, $total_pages));
    
    // Calculate offset for pagination
    $offset = ($current_page - 1) * $items_per_page;
    
    // Get coupons list
    $stmt = $pdo->prepare("SELECT * FROM coupons ORDER BY created_at DESC LIMIT ? OFFSET ?");
    $stmt->bindParam(1, $items_per_page, PDO::PARAM_INT);
    $stmt->bindParam(2, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $coupons = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Check if any coupons have expired or reached max uses
    $now = date('Y-m-d');
    foreach ($coupons as $key => $coupon) {
        $coupons[$key]['is_expired'] = ($coupon['expiry_date'] < $now);
        $coupons[$key]['is_exhausted'] = ($coupon['used_count'] >= $coupon['max_uses']);
        $coupons[$key]['is_unusable'] = $coupons[$key]['is_expired'] || $coupons[$key]['is_exhausted'] || !$coupon['is_active'];
    }
    
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

// Check for success message in URL
if (isset($_GET['success'])) {
    if ($_GET['success'] === 'added') {
        $success = "Coupon added successfully.";
    } elseif ($_GET['success'] === 'updated') {
        $success = "Coupon updated successfully.";
    } elseif ($_GET['success'] === 'deleted') {
        $success = "Coupon deleted successfully.";
    } elseif ($_GET['success'] === 'activated') {
        $success = "Coupon activated successfully.";
    } elseif ($_GET['success'] === 'deactivated') {
        $success = "Coupon deactivated successfully.";
    }
}

// Generate a random coupon code for new coupons
if ($action === 'add') {
    $form_coupon['code'] = generateCouponCode();
}
?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-semibold">Coupon Management</h1>
    <?php if ($action === 'list'): ?>
    <a href="coupon_management.php?action=add" class="bg-purple-600 hover:bg-purple-700 text-white font-medium py-2 px-4 rounded-lg transition-colors flex items-center">
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
        </svg>
        Add New Coupon
    </a>
    <?php else: ?>
    <a href="coupon_management.php" class="bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-4 rounded-lg transition-colors flex items-center">
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
<!-- Add Coupon Form -->
<div class="bg-gray-800 rounded-lg shadow-sm p-6">
    <h2 class="text-xl font-medium mb-6">Add New Coupon</h2>
    
    <form action="coupon_management.php" method="post">
        <input type="hidden" name="action" value="add_coupon">
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Coupon Code -->
            <div class="col-span-1">
                <label for="code" class="block text-sm font-medium text-gray-300 mb-2">Coupon Code *</label>
                <div class="flex">
                    <input type="text" id="code" name="code" value="<?= htmlspecialchars($form_coupon['code']) ?>" required
                        class="w-full bg-gray-700 border border-gray-600 rounded-l-md py-2 px-4 text-white focus:outline-none focus:border-purple-500 uppercase">
                    <button type="button" onclick="generateNewCode()" class="bg-gray-600 hover:bg-gray-500 text-gray-300 px-3 rounded-r-md border border-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                    </button>
                </div>
                <p class="text-xs text-gray-400 mt-1">Code cannot be changed after creation</p>
            </div>
            
            <!-- Duration in Months -->
            <div class="col-span-1">
                <label for="duration_months" class="block text-sm font-medium text-gray-300 mb-2">Duration (Months) *</label>
                <input type="number" id="duration_months" name="duration_months" value="<?= htmlspecialchars($form_coupon['duration_months']) ?>" min="1" required
                    class="w-full bg-gray-700 border border-gray-600 rounded-md py-2 px-4 text-white focus:outline-none focus:border-purple-500">
            </div>
            
            <!-- Description -->
            <div class="col-span-2">
                <label for="description" class="block text-sm font-medium text-gray-300 mb-2">Description</label>
                <input type="text" id="description" name="description" value="<?= htmlspecialchars($form_coupon['description']) ?>"
                    placeholder="Premium subscription for new users"
                    class="w-full bg-gray-700 border border-gray-600 rounded-md py-2 px-4 text-white focus:outline-none focus:border-purple-500">
            </div>
            
            <!-- Maximum Uses -->
            <div class="col-span-1">
                <label for="max_uses" class="block text-sm font-medium text-gray-300 mb-2">Maximum Uses *</label>
                <input type="number" id="max_uses" name="max_uses" value="<?= htmlspecialchars($form_coupon['max_uses']) ?>" min="1" required
                    class="w-full bg-gray-700 border border-gray-600 rounded-md py-2 px-4 text-white focus:outline-none focus:border-purple-500">
            </div>
            
            <!-- Expiry Date -->
            <div class="col-span-1">
                <label for="expiry_date" class="block text-sm font-medium text-gray-300 mb-2">Expiry Date *</label>
                <input type="date" id="expiry_date" name="expiry_date" value="<?= htmlspecialchars($form_coupon['expiry_date']) ?>" required
                    class="w-full bg-gray-700 border border-gray-600 rounded-md py-2 px-4 text-white focus:outline-none focus:border-purple-500">
            </div>
            
            <!-- Active Status -->
            <div class="col-span-2">
                <label class="flex items-center">
                    <input type="checkbox" name="is_active" <?= $form_coupon['is_active'] ? 'checked' : '' ?>
                        class="rounded border-gray-600 text-purple-600 focus:ring-purple-500 h-5 w-5 bg-gray-700">
                    <span class="ml-2 text-gray-300">Active</span>
                </label>
                <p class="text-xs text-gray-400 mt-1">If unchecked, the coupon cannot be used even if it's not expired and hasn't reached maximum uses.</p>
            </div>
        </div>
        
        <!-- Form actions -->
        <div class="mt-6 flex justify-end space-x-3">
            <a href="coupon_management.php" class="px-4 py-2 bg-gray-700 text-white rounded-md hover:bg-gray-600 transition-colors">
                Cancel
            </a>
            <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 transition-colors">
                Add Coupon
            </button>
        </div>
    </form>
</div>

<script>
    function generateNewCode() {
        const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        let code = '';
        for (let i = 0; i < 8; i++) {
            code += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        document.getElementById('code').value = code;
    }
</script>

<?php elseif ($action === 'edit'): ?>
<!-- Edit Coupon Form -->
<div class="bg-gray-800 rounded-lg shadow-sm p-6">
    <h2 class="text-xl font-medium mb-6">Edit Coupon</h2>
    
    <div class="flex mb-6 items-center">
        <div class="bg-gray-700 px-4 py-2 rounded-lg mr-4">
            <span class="text-lg font-mono font-bold text-purple-400"><?= htmlspecialchars($form_coupon['code']) ?></span>
        </div>
        <div>
            <h3 class="text-lg font-medium"><?= htmlspecialchars($form_coupon['description'] ?: 'No description') ?></h3>
            <p class="text-gray-400">
                <?= $form_coupon['used_count'] ?>/<?= $form_coupon['max_uses'] ?> uses · 
                Expires: <?= date('M j, Y', strtotime($form_coupon['expiry_date'])) ?>
            </p>
        </div>
    </div>
    
    <form action="coupon_management.php" method="post">
        <input type="hidden" name="action" value="edit_coupon">
        <input type="hidden" name="coupon_id" value="<?= $form_coupon['id'] ?>">
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Coupon Code (disabled, cannot be changed) -->
            <div class="col-span-1">
                <label class="block text-sm font-medium text-gray-300 mb-2">Coupon Code</label>
                <input type="text" value="<?= htmlspecialchars($form_coupon['code']) ?>" disabled
                    class="w-full bg-gray-600 border border-gray-700 rounded-md py-2 px-4 text-gray-300 uppercase">
                <p class="text-xs text-gray-400 mt-1">Code cannot be changed</p>
            </div>
            
            <!-- Duration in Months -->
            <div class="col-span-1">
                <label for="duration_months" class="block text-sm font-medium text-gray-300 mb-2">Duration (Months) *</label>
                <input type="number" id="duration_months" name="duration_months" value="<?= htmlspecialchars($form_coupon['duration_months']) ?>" min="1" required
                    class="w-full bg-gray-700 border border-gray-600 rounded-md py-2 px-4 text-white focus:outline-none focus:border-purple-500">
            </div>
            
            <!-- Description -->
            <div class="col-span-2">
                <label for="description" class="block text-sm font-medium text-gray-300 mb-2">Description</label>
                <input type="text" id="description" name="description" value="<?= htmlspecialchars($form_coupon['description']) ?>"
                    placeholder="Premium subscription for new users"
                    class="w-full bg-gray-700 border border-gray-600 rounded-md py-2 px-4 text-white focus:outline-none focus:border-purple-500">
            </div>
            
            <!-- Maximum Uses -->
            <div class="col-span-1">
                <label for="max_uses" class="block text-sm font-medium text-gray-300 mb-2">Maximum Uses *</label>
                <div class="flex items-center">
                    <input type="number" id="max_uses" name="max_uses" value="<?= htmlspecialchars($form_coupon['max_uses']) ?>" min="<?= $form_coupon['used_count'] ?>" required
                        class="w-full bg-gray-700 border border-gray-600 rounded-md py-2 px-4 text-white focus:outline-none focus:border-purple-500">
                    <span class="ml-2 text-gray-400">Current uses: <?= $form_coupon['used_count'] ?></span>
                </div>
            </div>
            
            <!-- Expiry Date -->
            <div class="col-span-1">
                <label for="expiry_date" class="block text-sm font-medium text-gray-300 mb-2">Expiry Date *</label>
                <input type="date" id="expiry_date" name="expiry_date" value="<?= htmlspecialchars($form_coupon['expiry_date']) ?>" required
                    class="w-full bg-gray-700 border border-gray-600 rounded-md py-2 px-4 text-white focus:outline-none focus:border-purple-500">
            </div>
            
            <!-- Active Status -->
            <div class="col-span-2">
                <label class="flex items-center">
                    <input type="checkbox" name="is_active" <?= $form_coupon['is_active'] ? 'checked' : '' ?>
                        class="rounded border-gray-600 text-purple-600 focus:ring-purple-500 h-5 w-5 bg-gray-700">
                    <span class="ml-2 text-gray-300">Active</span>
                </label>
                <p class="text-xs text-gray-400 mt-1">If unchecked, the coupon cannot be used even if it's not expired and hasn't reached maximum uses.</p>
            </div>
        </div>
        
        <!-- Form actions -->
        <div class="mt-6 flex justify-end space-x-3">
            <a href="coupon_management.php" class="px-4 py-2 bg-gray-700 text-white rounded-md hover:bg-gray-600 transition-colors">
                Cancel
            </a>
            <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 transition-colors">
                Update Coupon
            </button>
        </div>
    </form>
</div>

<?php elseif ($action === 'list'): ?>
<!-- Coupons listing -->
<div class="bg-gray-800 rounded-lg overflow-hidden shadow-sm">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-700">
            <thead class="bg-gray-700">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Code</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Description</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Duration</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Usage</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Expiry</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Status</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-300 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-700">
                <?php if (empty($coupons)): ?>
                <tr>
                    <td colspan="7" class="px-6 py-4 text-center text-gray-400">No coupons found.</td>
                </tr>
                <?php else: ?>
                    <?php foreach ($coupons as $coupon): ?>
                    <tr class="hover:bg-gray-700 <?= $coupon['is_unusable'] ? 'opacity-70' : '' ?>">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-mono font-bold text-purple-400"><?= htmlspecialchars($coupon['code']) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300"><?= htmlspecialchars($coupon['description']) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300"><?= $coupon['duration_months'] ?> month<?= $coupon['duration_months'] != 1 ? 's' : '' ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                            <?= $coupon['used_count'] ?>/<?= $coupon['max_uses'] ?>
                            <?php if ($coupon['is_exhausted']): ?>
                            <span class="ml-2 px-2 py-0.5 text-xs rounded bg-red-900 text-red-100">Exhausted</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                            <?= date('M j, Y', strtotime($coupon['expiry_date'])) ?>
                            <?php if ($coupon['is_expired']): ?>
                            <span class="ml-2 px-2 py-0.5 text-xs rounded bg-red-900 text-red-100">Expired</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <?php if ($coupon['is_active']): ?>
                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-800 text-green-100">Active</span>
                            <?php else: ?>
                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-700 text-gray-400">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right">
                            <div class="flex justify-end space-x-2">
                                <a href="coupon_management.php?action=toggle&id=<?= $coupon['id'] ?>" class="<?= $coupon['is_active'] ? 'text-red-400 hover:text-red-300' : 'text-green-400 hover:text-green-300' ?> transition-colors">
                                    <?php if ($coupon['is_active']): ?>
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <?php else: ?>
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <?php endif; ?>
                                </a>
                                <a href="coupon_management.php?action=edit&id=<?= $coupon['id'] ?>" class="text-yellow-400 hover:text-yellow-300 transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </a>
                                <a href="coupon_management.php?action=delete&id=<?= $coupon['id'] ?>" class="text-red-400 hover:text-red-300 transition-colors" 
                                   onclick="return confirm('Are you sure you want to delete this coupon?')">
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