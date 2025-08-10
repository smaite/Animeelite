<?php
// API to get user's currently watching anime (incomplete progress)
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
    
    // Get anime with incomplete progress (less than 100%)
    $stmt = $pdo->prepare("
        SELECT 
            a.id as anime_id,
            a.title as anime_title,
            a.cover_image,
            COUNT(DISTINCT e.id) as total_episodes,
            COUNT(DISTINCT wh.episode_id) as watched_episodes,
            ROUND((COUNT(DISTINCT wh.episode_id) / COUNT(DISTINCT e.id)) * 100, 1) as completion_percentage,
            MAX(wh.watched_at) as last_watched
        FROM anime a
        JOIN seasons s ON a.id = s.anime_id
        JOIN episodes e ON s.id = e.season_id
        LEFT JOIN watch_history wh ON e.id = wh.episode_id AND wh.user_id = ? AND wh.is_completed = 1
        WHERE a.id IN (
            SELECT DISTINCT s2.anime_id 
            FROM watch_history wh2 
            JOIN episodes e2 ON wh2.episode_id = e2.id 
            JOIN seasons s2 ON e2.season_id = s2.id 
            WHERE wh2.user_id = ? AND wh2.is_completed = 1
        )
        GROUP BY a.id, a.title, a.cover_image
        HAVING completion_percentage < 100
        ORDER BY last_watched DESC
        LIMIT ?
    ");
    $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id'], $limit]);
    $continueWatching = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format the data
    $formattedData = [];
    foreach ($continueWatching as $item) {
        $formattedData[] = [
            'anime_id' => $item['anime_id'],
            'anime_title' => $item['anime_title'],
            'anime_cover' => $item['cover_image'],
            'total_episodes' => $item['total_episodes'],
            'watched_episodes' => $item['watched_episodes'],
            'completion_percentage' => $item['completion_percentage'],
            'last_watched' => $item['last_watched'],
            'last_watched_formatted' => $item['last_watched'] ? date('M j, Y', strtotime($item['last_watched'])) : 'Unknown'
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