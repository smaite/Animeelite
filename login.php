<?php
// Login page
session_start();
require_once 'config.php';

// If user is already logged in, redirect to home
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Initialize variables
$error = '';
$username = '';

// Handle login form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    // Basic validation
    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password.";
    } else {
        // Connect to database
        try {
            $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Get user from database
            $stmt = $pdo->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
            $stmt->execute([$username]);
            
            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Verify password
                if (password_verify($password, $user['password'])) {
                    // Password is correct, create session
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    
                    // Create session token
                    $token = bin2hex(random_bytes(32));
                    $expiry = date('Y-m-d H:i:s', strtotime('+30 days'));
                    
                    // Store token in database
                    $stmt = $pdo->prepare("INSERT INTO sessions (user_id, token, expires_at) VALUES (?, ?, ?)");
                    $stmt->execute([$user['id'], $token, $expiry]);
                    
                    // Set cookie for persistent login
                    setcookie('auth_token', $token, strtotime('+30 days'), '/', '', false, true);
                    
                    // Redirect to homepage or requested page
                    $redirect_to = isset($_SESSION['redirect_after_login']) ? $_SESSION['redirect_after_login'] : 'index.php';
                    unset($_SESSION['redirect_after_login']);
                    
                    header("Location: $redirect_to");
                    exit();
                } else {
                    $error = "Invalid username or password.";
                }
            } else {
                $error = "Invalid username or password.";
            }
        } catch (PDOException $e) {
            $error = "Server error. Please try again later.";
        }
    }
}

// Set page title
$pageTitle = "Sign In - AnimeElite";

// Include header
include 'includes/header.php';
?>

<main class="container mx-auto px-4 py-8">
    <div class="max-w-md mx-auto bg-gray-800 rounded-lg overflow-hidden shadow-lg p-6 my-8">
        <h1 class="text-2xl font-bold text-center mb-6 bg-gradient-to-r from-purple-500 to-pink-500 bg-clip-text text-transparent">Sign In to AnimeElite</h1>
        
        <?php if ($error): ?>
        <div class="bg-red-900 text-white p-3 rounded-md mb-4">
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>
        
        <form action="login.php" method="post">
            <div class="mb-4">
                <label for="username" class="block text-gray-300 mb-2">Username</label>
                <input type="text" id="username" name="username" value="<?= htmlspecialchars($username) ?>" required
                    class="w-full px-4 py-2 rounded-md bg-gray-700 text-white border border-gray-600 focus:outline-none focus:border-purple-500">
            </div>
            
            <div class="mb-6">
                <label for="password" class="block text-gray-300 mb-2">Password</label>
                <input type="password" id="password" name="password" required
                    class="w-full px-4 py-2 rounded-md bg-gray-700 text-white border border-gray-600 focus:outline-none focus:border-purple-500">
            </div>
            
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center">
                    <input type="checkbox" id="remember" name="remember" class="mr-2">
                    <label for="remember" class="text-gray-300">Remember me</label>
                </div>
                <a href="forgot-password.php" class="text-purple-400 hover:text-purple-300">Forgot password?</a>
            </div>
            
            <button type="submit" class="w-full bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white font-medium py-2 px-4 rounded-md transition-all duration-300">
                Sign In
            </button>
        </form>
        
        <div class="mt-6 text-center">
            <p class="text-gray-400">Don't have an account? <a href="signup.php" class="text-purple-400 hover:text-purple-300">Sign up</a></p>
        </div>
    </div>
</main>

<?php
// Include footer
include 'includes/footer.php';
?> 