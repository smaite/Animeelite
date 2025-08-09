<?php
// Script to update user's last_active status
session_start();
require_once 'config.php';

// Only update if user is logged in
if (isset($_SESSION['user_id'])) {
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Update last_active timestamp
        $stmt = $pdo->prepare("UPDATE users SET last_active = NOW() WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        
        // Return success
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        
    } catch (PDOException $e) {
        // Return error
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    // User not logged in
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'User not logged in']);
}
?> 