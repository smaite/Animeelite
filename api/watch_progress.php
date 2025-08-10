<?php
// API for saving and retrieving watch progress
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

session_start();
require_once '../config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Save watch progress
        $input = json_decode(file_get_contents('php://input'), true);
        
        $episode_id = isset($input['episode_id']) ? intval($input['episode_id']) : 0;
        $position_seconds = isset($input['position_seconds']) ? intval($input['position_seconds']) : 0;
        $duration_seconds = isset($input['duration_seconds']) ? intval($input['duration_seconds']) : 0;
        $is_completed = isset($input['is_completed']) ? ($input['is_completed'] ? 1 : 0) : 0;
        
        if (!$episode_id) {
            echo json_encode(['success' => false, 'message' => 'Episode ID required']);
            exit;
        }
        
        // Auto-detect completion based on watch percentage (90% threshold)
        if ($duration_seconds > 0 && $position_seconds >= ($duration_seconds * 0.9)) {
            $is_completed = 1;
        }
        
        // Update or insert watch progress
        $stmt = $pdo->prepare("
            INSERT INTO watch_history (user_id, episode_id, position_seconds, is_completed, watched_at) 
            VALUES (?, ?, ?, ?, NOW()) 
            ON DUPLICATE KEY UPDATE 
                position_seconds = VALUES(position_seconds),
                is_completed = VALUES(is_completed),
                watched_at = NOW()
        ");
        $stmt->execute([$_SESSION['user_id'], $episode_id, $position_seconds, $is_completed]);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Progress saved',
            'is_completed' => $is_completed
        ]);
        
    } else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Get watch progress
        $episode_id = isset($_GET['episode_id']) ? intval($_GET['episode_id']) : 0;
        
        if (!$episode_id) {
            echo json_encode(['success' => false, 'message' => 'Episode ID required']);
            exit;
        }
        
        $stmt = $pdo->prepare("
            SELECT position_seconds, is_completed, watched_at 
            FROM watch_history 
            WHERE user_id = ? AND episode_id = ?
        ");
        $stmt->execute([$_SESSION['user_id'], $episode_id]);
        $progress = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($progress) {
            echo json_encode([
                'success' => true,
                'data' => $progress
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'data' => [
                    'position_seconds' => 0,
                    'is_completed' => 0,
                    'watched_at' => null
                ]
            ]);
        }
    }
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?> 