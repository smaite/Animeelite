<?php
// Start output buffering to prevent "headers already sent" errors
ob_start();

// AJAX endpoint to get episode data
session_start();
require_once '../../config.php';

// Ensure we're always returning JSON
header('Content-Type: application/json');

// Initialize response
$response = [
    'success' => false,
    'message' => '',
    'episode' => null
];

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    $response['message'] = 'Unauthorized access';
    echo json_encode($response);
    exit;
}

// Get episode ID from request
$episode_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$episode_id) {
    $response['message'] = 'Episode ID is required';
    echo json_encode($response);
    exit;
}

try {
    // Connect to database
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get episode data
    $stmt = $pdo->prepare("SELECT * FROM episodes WHERE id = ?");
    $stmt->execute([$episode_id]);
    
    if ($stmt->rowCount() === 0) {
        $response['message'] = 'Episode not found';
    } else {
        $episode = $stmt->fetch(PDO::FETCH_ASSOC);
        $response['success'] = true;
        $response['episode'] = $episode;
    }
} catch (PDOException $e) {
    $response['message'] = 'Database error: ' . $e->getMessage();
}

echo json_encode($response);
exit; 