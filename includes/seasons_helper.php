<?php
// Helper function using EXACT logic from debug_seasons.php that works perfectly

function getSeasonsWithEpisodes($anime_id, $pdo) {
    try {
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
        
        return $seasons;
        
    } catch (PDOException $e) {
        error_log("Error in getSeasonsWithEpisodes: " . $e->getMessage());
        return [];
    }
}
?> 