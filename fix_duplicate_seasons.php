<?php
// Script to fix duplicate seasons
require_once 'config.php';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h1>Fixing Duplicate Seasons</h1>";
    
    // Get all anime
    $stmt = $pdo->query("SELECT id, title FROM anime ORDER BY title");
    $animes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($animes as $anime) {
        echo "<h2>Processing: " . htmlspecialchars($anime['title']) . " (ID: {$anime['id']})</h2>";
        
        // Find duplicate season numbers for this anime
        $stmt = $pdo->prepare("
            SELECT season_number, COUNT(*) as count, GROUP_CONCAT(id) as season_ids 
            FROM seasons 
            WHERE anime_id = ? 
            GROUP BY season_number 
            HAVING COUNT(*) > 1
        ");
        $stmt->execute([$anime['id']]);
        $duplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($duplicates) > 0) {
            echo "<p>Found duplicate seasons:</p>";
            
            foreach ($duplicates as $duplicate) {
                $seasonNumber = $duplicate['season_number'];
                $seasonIds = explode(',', $duplicate['season_ids']);
                
                echo "<p>Season $seasonNumber has " . $duplicate['count'] . " entries (IDs: " . implode(', ', $seasonIds) . ")</p>";
                
                // Keep the first season, merge episodes from others, then delete duplicates
                $keepSeasonId = $seasonIds[0];
                $deleteSeasonIds = array_slice($seasonIds, 1);
                
                foreach ($deleteSeasonIds as $deleteId) {
                    // Move episodes from duplicate season to the main one
                    $stmt = $pdo->prepare("UPDATE episodes SET season_id = ? WHERE season_id = ?");
                    $stmt->execute([$keepSeasonId, $deleteId]);
                    echo "<p>Moved episodes from season ID $deleteId to $keepSeasonId</p>";
                    
                    // Delete the duplicate season
                    $stmt = $pdo->prepare("DELETE FROM seasons WHERE id = ?");
                    $stmt->execute([$deleteId]);
                    echo "<p>Deleted duplicate season ID $deleteId</p>";
                }
            }
        } else {
            echo "<p>No duplicate seasons found</p>";
        }
    }
    
    echo "<h2>All duplicates fixed!</h2>";
    
} catch (PDOException $e) {
    echo "<h2>Database error:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?> 