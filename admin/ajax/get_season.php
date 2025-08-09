<?php
// AJAX endpoint to get season data
session_start();
require_once '../../config.php';

// Ensure we're always returning JSON
header('Content-Type: application/json');

// Initialize response
$response = [
    'success' => false,
    'message' => '',
    'season' => null
];

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    $response['message'] = 'Unauthorized access';
    echo json_encode($response);
    exit;
}

// Get season ID from request
$season_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$season_id) {
    $response['message'] = 'Season ID is required';
    echo json_encode($response);
    exit;
}

try {
    // Connect to database
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get season data
    $stmt = $pdo->prepare("SELECT * FROM seasons WHERE id = ?");
    $stmt->execute([$season_id]);
    
    if ($stmt->rowCount() === 0) {
        $response['message'] = 'Season not found';
    } else {
        $season = $stmt->fetch(PDO::FETCH_ASSOC);
        $response['success'] = true;
        $response['season'] = $season;
    }
} catch (PDOException $e) {
    $response['message'] = 'Database error: ' . $e->getMessage();
}

echo json_encode($response);
exit; 