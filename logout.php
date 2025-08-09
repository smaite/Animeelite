<?php
// Logout page
session_start();
require_once 'config.php';

// If there's a session token, remove it from database
if (isset($_COOKIE['auth_token'])) {
    $token = $_COOKIE['auth_token'];
    
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Delete the token from database
        $stmt = $pdo->prepare("DELETE FROM sessions WHERE token = ?");
        $stmt->execute([$token]);
    } catch (PDOException $e) {
        // Just log the error and continue with logout
        error_log("Error removing token from database: " . $e->getMessage());
    }
    
    // Clear the auth cookie
    setcookie('auth_token', '', time() - 3600, '/', '', false, true);
}

// Destroy the session
$_SESSION = array();
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

// Redirect to login page
header("Location: login.php");
exit();
?> 