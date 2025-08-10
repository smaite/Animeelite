<?php
// API to get user's continue watching list (episodes with saved progress but not completed)
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
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
    
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
    
    // Get episodes with progress (not completed) ordered by most recently watched
    $stmt = $pdo->prepare("
        SELECT wh.episode_id, wh.position_seconds, wh.watched_at,
               e.title as episode_title, e.episode_number, e.thumbnail, e.duration,
               s.season_number, s.title as season_title,
               a.id as anime_id, a.title as anime_title, a.cover_image
        FROM watch_history wh
        JOIN episodes e ON wh.episode_id = e.id
        JOIN seasons s ON e.season_id = s.id
        JOIN anime a ON s.anime_id = a.id
        WHERE wh.user_id = ? 
          AND wh.is_completed = 0 
          AND wh.position_seconds > 30
        ORDER BY wh.watched_at DESC
        LIMIT ?
    ");
    $stmt->execute([$_SESSION['user_id'], $limit]);
    $continueWatching = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format the data
    $formattedData = [];
    foreach ($continueWatching as $item) {
        $episodeDuration = $item['duration'] ? intval($item['duration']) * 60 : 1440;
        $progressPercentage = min(($item['position_seconds'] / $episodeDuration) * 100, 100);
        
        $formattedData[] = [
            'anime_id' => $item['anime_id'],
            'anime_title' => $item['anime_title'],
            'anime_cover' => $item['cover_image'],
            'episode_id' => $item['episode_id'],
            'episode_title' => $item['episode_title'],
            'episode_number' => $item['episode_number'],
            'season_number' => $item['season_number'],
            'season_title' => $item['season_title'],
            'thumbnail' => $item['thumbnail'],
            'position_seconds' => $item['position_seconds'],
            'progress_percentage' => round($progressPercentage, 1),
            'watched_at' => $item['watched_at'],
            'formatted_time' => gmdate("H:i:s", $item['position_seconds']),
            'player_url' => "player.php?anime={$item['anime_id']}&episode={$item['episode_id']}"
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $formattedData,
        'count' => count($formattedData)
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?> 