<?php
// Prevent PHP from outputting errors as HTML
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Ensure we're always returning JSON
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

// Handle PHP errors and convert them to JSON responses
function handleError($errno, $errstr, $errfile, $errline) {
    $response = [
        'success' => false,
        'message' => 'Server error: ' . $errstr,
        'error_code' => $errno
    ];
    echo json_encode($response);
    exit;
}
set_error_handler('handleError');

// Handle uncaught exceptions
function handleException($exception) {
    $response = [
        'success' => false,
        'message' => 'Server exception: ' . $exception->getMessage(),
        'error_code' => $exception->getCode()
    ];
    echo json_encode($response);
    exit;
}
set_exception_handler('handleException');

try {
    // Database connection settings
    require_once 'config.php';
    
    // Initialize response
    $response = [
        'success' => false,
        'message' => '',
        'anime' => null,
        'seasons' => [],
        'current_episode' => null,
        'debug' => [] // Add debug field for troubleshooting
    ];
    
    // Get request parameters
    $anime_id = isset($_GET['anime_id']) ? intval($_GET['anime_id']) : 0;
    $season_id = isset($_GET['season_id']) ? intval($_GET['season_id']) : 0;
    $episode_id = isset($_GET['episode_id']) ? intval($_GET['episode_id']) : 0;
    
    // Add debug information
    $response['debug'] = [
        'requested_anime_id' => $anime_id,
        'requested_season_id' => $season_id,
        'requested_episode_id' => $episode_id
    ];
    
    if (!$anime_id) {
        $response['message'] = 'Anime ID is required';
        echo json_encode($response);
        exit;
    }
    
    // Connect to database
    $conn = new mysqli($host, $username, $password, $dbname);
    
    // Check connection
    if ($conn->connect_error) {
        throw new Exception('Database connection failed: ' . $conn->connect_error);
    }
    
    // Get anime details
    $stmt = $conn->prepare("SELECT * FROM anime WHERE id = ?");
    $stmt->bind_param("i", $anime_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $response['message'] = 'Anime not found';
        echo json_encode($response);
        exit;
    }
    
    $anime = $result->fetch_assoc();
    $response['anime'] = $anime;
    
    // Get seasons for this anime
    $stmt = $conn->prepare("SELECT * FROM seasons WHERE anime_id = ? ORDER BY season_number");
    $stmt->bind_param("i", $anime_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $seasons = [];
    while ($season = $result->fetch_assoc()) {
        // Get episodes for this season
        $stmt_episodes = $conn->prepare("SELECT * FROM episodes WHERE season_id = ? ORDER BY episode_number");
        $stmt_episodes->bind_param("i", $season['id']);
        $stmt_episodes->execute();
        $result_episodes = $stmt_episodes->get_result();
        
        $episodes = [];
        while ($episode = $result_episodes->fetch_assoc()) {
            $episodes[] = $episode;
        }
        
        $season['episodes'] = $episodes;
        $seasons[] = $season;
    }
    
    $response['seasons'] = $seasons;
    
    // Add season structure debug information
    $seasons_debug = [];
    foreach ($seasons as $index => $season) {
        $seasons_debug[] = [
            'index' => $index,
            'id' => $season['id'],
            'season_number' => $season['season_number'],
            'episode_count' => count($season['episodes'])
        ];
    }
    $response['debug']['seasons'] = $seasons_debug;
    
    // Get current episode details if provided
    if ($episode_id) {
        $stmt = $conn->prepare("SELECT e.*, s.season_number, s.anime_id FROM episodes e 
                               JOIN seasons s ON e.season_id = s.id 
                               WHERE e.id = ? AND s.anime_id = ?");
        $stmt->bind_param("ii", $episode_id, $anime_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $response['current_episode'] = $result->fetch_assoc();
        } else {
            // If episode_id not found, get first episode of the requested season
            if ($season_id) {
                $stmt = $conn->prepare("SELECT e.*, s.season_number, s.anime_id FROM episodes e 
                                      JOIN seasons s ON e.season_id = s.id 
                                      WHERE s.id = ? AND s.anime_id = ? ORDER BY e.episode_number LIMIT 1");
                $stmt->bind_param("ii", $season_id, $anime_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 1) {
                    $response['current_episode'] = $result->fetch_assoc();
                }
            } else {
                // If no season_id provided, get first episode of first season
                $stmt = $conn->prepare("SELECT e.*, s.season_number, s.anime_id FROM episodes e 
                                      JOIN seasons s ON e.season_id = s.id 
                                      WHERE s.anime_id = ? ORDER BY s.season_number, e.episode_number LIMIT 1");
                $stmt->bind_param("i", $anime_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 1) {
                    $response['current_episode'] = $result->fetch_assoc();
                }
            }
        }
    } else if ($season_id) {
        // If only season_id provided, get first episode of that season
        $stmt = $conn->prepare("SELECT e.*, s.season_number, s.anime_id FROM episodes e 
                              JOIN seasons s ON e.season_id = s.id 
                              WHERE s.id = ? AND s.anime_id = ? ORDER BY e.episode_number LIMIT 1");
        $stmt->bind_param("ii", $season_id, $anime_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $response['current_episode'] = $result->fetch_assoc();
        }
    } else {
        // Default: first episode of first season
        $stmt = $conn->prepare("SELECT e.*, s.season_number, s.anime_id FROM episodes e 
                              JOIN seasons s ON e.season_id = s.id 
                              WHERE s.anime_id = ? ORDER BY s.season_number, e.episode_number LIMIT 1");
        $stmt->bind_param("i", $anime_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $response['current_episode'] = $result->fetch_assoc();
        }
    }
    
    // If we still don't have a current episode but have seasons with episodes, use the first one
    if (!$response['current_episode'] && !empty($seasons) && !empty($seasons[0]['episodes'])) {
        $first_episode = $seasons[0]['episodes'][0];
        $first_episode['season_number'] = $seasons[0]['season_number'];
        $first_episode['anime_id'] = $anime_id;
        $response['current_episode'] = $first_episode;
    }
    
    // Add debug info about current episode if one is found
    if ($response['current_episode']) {
        $response['debug']['current_episode'] = [
            'id' => $response['current_episode']['id'],
            'title' => $response['current_episode']['title'],
            'anime_id' => $response['current_episode']['anime_id'],
            'season_id' => $response['current_episode']['season_id'],
            'season_number' => $response['current_episode']['season_number'],
            'episode_number' => $response['current_episode']['episode_number'],
        ];

        // Verify that the current episode belongs to the requested anime
        if ($response['current_episode']['anime_id'] != $anime_id) {
            $response['debug']['error'] = "Wrong anime! Requested anime_id=$anime_id but got anime_id=" . $response['current_episode']['anime_id'];
            
            // Force a correction - find the correct episode for the requested anime
            $stmt = $conn->prepare("SELECT e.*, s.season_number, s.anime_id FROM episodes e 
                                   JOIN seasons s ON e.season_id = s.id 
                                   WHERE e.episode_number = ? AND s.season_number = ? AND s.anime_id = ?
                                   LIMIT 1");
            $requested_episode_number = $response['current_episode']['episode_number'];
            $requested_season_number = $response['current_episode']['season_number'];
            $stmt->bind_param("iii", $requested_episode_number, $requested_season_number, $anime_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $response['current_episode'] = $result->fetch_assoc();
                $response['debug']['correction'] = "Found correct episode for anime_id=$anime_id";
            }
        }
    }
    
    $response['success'] = true;
    
    // Close connection
    $conn->close();
    
    // Return JSON response
    echo json_encode($response);
    
} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => 'Error: ' . $e->getMessage(),
        'anime' => null,
        'seasons' => [],
        'current_episode' => null
    ];
    echo json_encode($response);
}
?> 