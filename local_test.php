<?php
// Set content type to JSON
header('Content-Type: application/json');

// Include database configuration
require_once 'server/config.php';

// Initialize response array
$response = [
    'success' => false,
    'message' => '',
    'anime' => null,
    'seasons' => [],
    'current_episode' => null,
    'debug' => []
];

// Get anime ID from query string or use default 1
$animeId = isset($_GET['anime_id']) ? intval($_GET['anime_id']) : 1;
$seasonId = isset($_GET['season_id']) ? intval($_GET['season_id']) : 0;
$episodeId = isset($_GET['episode_id']) ? intval($_GET['episode_id']) : 0;

// Add debug info
$response['debug']['params'] = [
    'anime_id' => $animeId,
    'season_id' => $seasonId,
    'episode_id' => $episodeId
];

try {
    // Connect to database
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $response['debug']['connection'] = "Connected to database $dbname at $host";
    
    // Fetch anime details
    $stmt = $pdo->prepare("
        SELECT id, title, description, cover_image, release_year, genres, status
        FROM anime
        WHERE id = :animeId
    ");
    $stmt->bindParam(':animeId', $animeId, PDO::PARAM_INT);
    $stmt->execute();
    
    $anime = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$anime) {
        $response['message'] = "Anime with ID $animeId not found";
        $response['debug']['anime_query'] = "No results for anime ID $animeId";
        
        // Check if any anime exists
        $stmt = $pdo->query("SELECT COUNT(*) FROM anime");
        $animeCount = $stmt->fetchColumn();
        $response['debug']['anime_count'] = $animeCount;
        
        if ($animeCount > 0) {
            $stmt = $pdo->query("SELECT id, title FROM anime LIMIT 5");
            $response['debug']['available_anime'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        echo json_encode($response);
        exit;
    }
    
    $response['anime'] = $anime;
    $response['debug']['anime_found'] = true;
    
    // Fetch seasons for this anime
    $stmt = $pdo->prepare("
        SELECT id, season_number, title, description, cover_image, release_year
        FROM seasons
        WHERE anime_id = :animeId
        ORDER BY season_number
    ");
    $stmt->bindParam(':animeId', $animeId, PDO::PARAM_INT);
    $stmt->execute();
    
    $seasons = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $response['debug']['seasons_count'] = count($seasons);
    
    // If no seasons found, check if any seasons exist in the database
    if (empty($seasons)) {
        $stmt = $pdo->query("SELECT COUNT(*) FROM seasons");
        $seasonsCount = $stmt->fetchColumn();
        $response['debug']['total_seasons_count'] = $seasonsCount;
    }
    
    // If no season ID is provided, use the first season
    if ($seasonId <= 0 && count($seasons) > 0) {
        $seasonId = $seasons[0]['id'];
        $response['debug']['selected_first_season'] = $seasonId;
    }
    
    // For each season, fetch episodes
    foreach ($seasons as &$season) {
        $stmt = $pdo->prepare("
            SELECT id, episode_number, title, description, thumbnail, video_url, duration, is_premium
            FROM episodes
            WHERE season_id = :seasonId
            ORDER BY episode_number
        ");
        $stmt->bindParam(':seasonId', $season['id'], PDO::PARAM_INT);
        $stmt->execute();
        
        $season['episodes'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $response['debug']['episodes_in_season_' . $season['id']] = count($season['episodes']);
        
        // If this is the current season and no episode ID is provided, use the first episode
        if ($seasonId == $season['id'] && $episodeId <= 0 && count($season['episodes']) > 0) {
            $episodeId = $season['episodes'][0]['id'];
            $response['debug']['selected_first_episode'] = $episodeId;
        }
    }
    
    $response['seasons'] = $seasons;
    
    // Fetch current episode details
    if ($episodeId > 0) {
        $stmt = $pdo->prepare("
            SELECT e.id, e.episode_number, e.title, e.description, e.thumbnail, e.video_url, e.duration, e.is_premium,
                  s.id as season_id, s.season_number, a.id as anime_id, a.title as anime_title
            FROM episodes e
            JOIN seasons s ON e.season_id = s.id
            JOIN anime a ON s.anime_id = a.id
            WHERE e.id = :episodeId
        ");
        $stmt->bindParam(':episodeId', $episodeId, PDO::PARAM_INT);
        $stmt->execute();
        
        $currentEpisode = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($currentEpisode) {
            $response['current_episode'] = $currentEpisode;
            $response['debug']['current_episode_found'] = true;
        } else {
            $response['debug']['current_episode_not_found'] = "Episode ID $episodeId not found";
        }
    } else {
        $response['debug']['no_episode_selected'] = "No episode ID provided or found";
    }
    
    // Set response success
    $response['success'] = true;
    
} catch (PDOException $e) {
    $response['message'] = 'Database error: ' . $e->getMessage();
    $response['debug']['error_code'] = $e->getCode();
    $response['debug']['error_message'] = $e->getMessage();
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
    $response['debug']['error'] = $e->getMessage();
}

// Return JSON response
echo json_encode($response);
?> 