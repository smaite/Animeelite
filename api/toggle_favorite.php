<?php
// API endpoint to toggle anime favorite status
session_start();
require_once '../config.php';

// Ensure we're always returning JSON
header('Content-Type: application/json');

// Initialize response
$response = [
    'success' => false,
    'message' => '',
    'isFavorite' => false
];

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'User not logged in';
    echo json_encode($response);
    exit;
}

// Get anime ID from request
$anime_id = isset($_POST['anime_id']) ? intval($_POST['anime_id']) : 0;

if (!$anime_id) {
    $response['message'] = 'Anime ID is required';
    echo json_encode($response);
    exit;
}

try {
    // Connect to database
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if anime exists
    $stmt = $pdo->prepare("SELECT id FROM anime WHERE id = ?");
    $stmt->execute([$anime_id]);
    
    if ($stmt->rowCount() === 0) {
        $response['message'] = 'Anime not found';
        echo json_encode($response);
        exit;
    }
    
    // Check if user has this anime in favorites
    $stmt = $pdo->prepare("SELECT id FROM favorites WHERE user_id = ? AND anime_id = ?");
    $stmt->execute([$_SESSION['user_id'], $anime_id]);
    
    if ($stmt->rowCount() > 0) {
        // Remove from favorites
        $stmt = $pdo->prepare("DELETE FROM favorites WHERE user_id = ? AND anime_id = ?");
        $stmt->execute([$_SESSION['user_id'], $anime_id]);
        $response['message'] = 'Removed from favorites';
        $response['isFavorite'] = false;
    } else {
        // Add to favorites
        $stmt = $pdo->prepare("INSERT INTO favorites (user_id, anime_id) VALUES (?, ?)");
        $stmt->execute([$_SESSION['user_id'], $anime_id]);
        $response['message'] = 'Added to favorites';
        $response['isFavorite'] = true;
    }
    
    $response['success'] = true;
    
} catch (PDOException $e) {
    $response['message'] = 'Database error: ' . $e->getMessage();
}

// Return JSON response
echo json_encode($response);
?> 