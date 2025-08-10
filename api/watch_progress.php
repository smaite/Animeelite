<?php
// API for marking episodes as watched/unwatched
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
        // Mark episode as watched
        $input = json_decode(file_get_contents('php://input'), true);
        
        $episode_id = isset($input['episode_id']) ? intval($input['episode_id']) : 0;
        
        if (!$episode_id) {
            echo json_encode(['success' => false, 'message' => 'Episode ID required']);
            exit;
        }
        
        // Simply mark as watched (no position tracking)
        $stmt = $pdo->prepare("
            INSERT INTO watch_history (user_id, episode_id, position_seconds, is_completed, watched_at) 
            VALUES (?, ?, 0, 1, NOW()) 
            ON DUPLICATE KEY UPDATE 
                is_completed = 1,
                watched_at = NOW()
        ");
        $stmt->execute([$_SESSION['user_id'], $episode_id]);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Episode marked as watched'
        ]);
        
    } else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Check if episode is watched
        $episode_id = isset($_GET['episode_id']) ? intval($_GET['episode_id']) : 0;
        
        if (!$episode_id) {
            echo json_encode(['success' => false, 'message' => 'Episode ID required']);
            exit;
        }
        
        $stmt = $pdo->prepare("
            SELECT is_completed, watched_at 
            FROM watch_history 
            WHERE user_id = ? AND episode_id = ?
        ");
        $stmt->execute([$_SESSION['user_id'], $episode_id]);
        $progress = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($progress) {
            echo json_encode([
                'success' => true,
                'data' => [
                    'is_watched' => $progress['is_completed'] == 1,
                    'watched_at' => $progress['watched_at']
                ]
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'data' => [
                    'is_watched' => false,
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