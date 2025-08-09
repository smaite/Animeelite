<?php
// Script to update user's last_active status
session_start();
require_once 'config.php';

// Only update if user is logged in and on the player page
if (isset($_SESSION['user_id'])) {
    // Get the current page name
    $current_page = basename($_SERVER['PHP_SELF']);
    
    // Only update last_active if user is on player.php
    if ($current_page === 'player.php') {
        try {
            $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Update last_active timestamp
            $stmt = $pdo->prepare("UPDATE users SET last_active = NOW() WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            
            // Return success
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'status' => 'online']);
            
        } catch (PDOException $e) {
            // Return error
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    } else {
        // Not on player page, return success but no update
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'status' => 'not_on_player']);
    }
} else {
    // User not logged in
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'User not logged in']);
}
?> 