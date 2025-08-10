<?php
// API endpoint to get seasons data using the exact working logic from debug_seasons.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config.php';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $anime_id = isset($_GET['anime_id']) ? intval($_GET['anime_id']) : 0;
    
    if (!$anime_id) {
        echo json_encode(['success' => false, 'message' => 'No anime ID provided']);
        exit;
    }
    
    // Use EXACT logic from debug_seasons.php that works
    $stmt = $pdo->prepare("SELECT * FROM seasons WHERE anime_id = ? ORDER BY season_number ASC");
    $stmt->execute([$anime_id]);
    $seasons = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // For each season, get episodes using same logic as debug
    foreach ($seasons as &$season) {
        $stmt = $pdo->prepare("SELECT * FROM episodes WHERE season_id = ? ORDER BY episode_number");
        $stmt->execute([$season['id']]);
        $season['episodes'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Also add episode count like debug does
        $season['episode_count'] = count($season['episodes']);
    }
    
    echo json_encode([
        'success' => true,
        'message' => '',
        'data' => $seasons
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?> 