<?php
// Signup page
session_start();
require_once 'config.php';

// If user is already logged in, redirect to home
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Initialize variables
$error = '';
$success = '';
$username = '';
$email = '';
$display_name = '';

// Handle registration form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $display_name = trim($_POST['display_name'] ?? $username);
    
    // Basic validation
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "All fields are required.";
    } elseif (strlen($username) < 3 || strlen($username) > 20) {
        $error = "Username must be between 3 and 20 characters.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Connect to database
        try {
            // Use the database credentials from config.php
            $db_username = $username; // From config.php
            $db_password = $password; // From config.php
            
            // Rename form variables to avoid conflict with database credentials
            $form_username = $username;
            $form_email = $email;
            $form_display_name = $display_name;
            
            $pdo = new PDO("mysql:host=$host;dbname=$dbname", $db_username, $db_password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Check if username already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$form_username]);
            if ($stmt->rowCount() > 0) {
                $error = "Username already exists.";
            } else {
                // Check if email already exists
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$form_email]);
                if ($stmt->rowCount() > 0) {
                    $error = "Email already in use.";
                } else {
                    // Hash password
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    
                    // Insert new user
                    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, display_name) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$form_username, $form_email, $hashed_password, $form_display_name]);
                    
                    $success = "Registration successful! You can now log in.";
                    
                    // Clear form fields
                    $username = '';
                    $email = '';
                    $display_name = '';
                }
            }
        } catch (PDOException $e) {
            $error = "Server error: " . $e->getMessage();
            error_log("Signup error: " . $e->getMessage());
        }
    }
}

// Set page title
$pageTitle = "Sign Up - AnimeElite";

// Include header
include 'includes/header.php';
?>

<main class="container mx-auto px-4 py-8">
    <div class="max-w-md mx-auto bg-gray-800 rounded-lg overflow-hidden shadow-lg p-6 my-8">
        <h1 class="text-2xl font-bold text-center mb-6 bg-gradient-to-r from-purple-500 to-pink-500 bg-clip-text text-transparent">Create an Account</h1>
        
        <?php if ($error): ?>
        <div class="bg-red-900 text-white p-3 rounded-md mb-4">
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
        <div class="bg-green-900 text-white p-3 rounded-md mb-4">
            <?= htmlspecialchars($success) ?>
            <p class="mt-2">
                <a href="login.php" class="underline">Click here to log in</a>
            </p>
        </div>
        <?php else: ?>
        
        <form action="signup.php" method="post">
            <div class="mb-4">
                <label for="username" class="block text-gray-300 mb-2">Username</label>
                <input type="text" id="username" name="username" value="<?= htmlspecialchars($username) ?>" required
                    class="w-full px-4 py-2 rounded-md bg-gray-700 text-white border border-gray-600 focus:outline-none focus:border-purple-500">
                <small class="text-gray-400">3-20 characters, letters and numbers only</small>
            </div>
            
            <div class="mb-4">
                <label for="email" class="block text-gray-300 mb-2">Email</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($email) ?>" required
                    class="w-full px-4 py-2 rounded-md bg-gray-700 text-white border border-gray-600 focus:outline-none focus:border-purple-500">
            </div>
            
            <div class="mb-4">
                <label for="display_name" class="block text-gray-300 mb-2">Display Name (optional)</label>
                <input type="text" id="display_name" name="display_name" value="<?= htmlspecialchars($display_name) ?>"
                    class="w-full px-4 py-2 rounded-md bg-gray-700 text-white border border-gray-600 focus:outline-none focus:border-purple-500">
                <small class="text-gray-400">This is how your name will appear on the site</small>
            </div>
            
            <div class="mb-4">
                <label for="password" class="block text-gray-300 mb-2">Password</label>
                <input type="password" id="password" name="password" required
                    class="w-full px-4 py-2 rounded-md bg-gray-700 text-white border border-gray-600 focus:outline-none focus:border-purple-500">
                <small class="text-gray-400">Minimum 6 characters</small>
            </div>
            
            <div class="mb-6">
                <label for="confirm_password" class="block text-gray-300 mb-2">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required
                    class="w-full px-4 py-2 rounded-md bg-gray-700 text-white border border-gray-600 focus:outline-none focus:border-purple-500">
            </div>
            
            <button type="submit" class="w-full bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white font-medium py-2 px-4 rounded-md transition-all duration-300">
                Sign Up
            </button>
        </form>
        <?php endif; ?>
        
        <div class="mt-6 text-center">
            <p class="text-gray-400">Already have an account? <a href="login.php" class="text-purple-400 hover:text-purple-300">Sign in</a></p>
        </div>
    </div>
</main>

<?php
// Include footer
include 'includes/footer.php';
?> 