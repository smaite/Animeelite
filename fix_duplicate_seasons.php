<?php
// Script to clean up duplicate seasons
require_once 'config.php';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h1>Fixing Duplicate Seasons</h1>";
    
    // Get all anime
    $stmt = $pdo->query("SELECT id, title FROM anime");
    $animes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($animes as $anime) {
        echo "<h2>Processing anime: " . htmlspecialchars($anime['title']) . " (ID: {$anime['id']})</h2>";
        
        // Get all seasons for this anime
        $stmt = $pdo->prepare("SELECT * FROM seasons WHERE anime_id = ? ORDER BY season_number, id");
        $stmt->execute([$anime['id']]);
        $seasons = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Group seasons by season_number
        $seasonGroups = [];
        foreach ($seasons as $season) {
            $seasonNumber = $season['season_number'];
            if (!isset($seasonGroups[$seasonNumber])) {
                $seasonGroups[$seasonNumber] = [];
            }
            $seasonGroups[$seasonNumber][] = $season;
        }
        
        // Process each group of seasons
        foreach ($seasonGroups as $seasonNumber => $seasonGroup) {
            if (count($seasonGroup) > 1) {
                echo "<p>Found " . count($seasonGroup) . " duplicate seasons for Season $seasonNumber</p>";
                
                // Keep the first season, merge episodes from others
                $keepSeason = $seasonGroup[0];
                echo "<p>Keeping season ID: {$keepSeason['id']}</p>";
                
                // Process other seasons
                for ($i = 1; $i < count($seasonGroup); $i++) {
                    $mergeSeason = $seasonGroup[$i];
                    echo "<p>Merging season ID: {$mergeSeason['id']} into {$keepSeason['id']}</p>";
                    
                    // Update episodes to point to the kept season
                    $stmt = $pdo->prepare("UPDATE episodes SET season_id = ? WHERE season_id = ?");
                    $stmt->execute([$keepSeason['id'], $mergeSeason['id']]);
                    $updatedCount = $stmt->rowCount();
                    echo "<p>Updated $updatedCount episodes</p>";
                    
                    // Delete the duplicate season
                    $stmt = $pdo->prepare("DELETE FROM seasons WHERE id = ?");
                    $stmt->execute([$mergeSeason['id']]);
                    echo "<p>Deleted season ID: {$mergeSeason['id']}</p>";
                }
            } else {
                echo "<p>Season $seasonNumber has no duplicates</p>";
            }
        }
    }
    
    echo "<h2>Cleanup complete!</h2>";
    
} catch (PDOException $e) {
    echo "<h2>Database error:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?> 