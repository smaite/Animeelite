<?php
// Script to fix season numbers
require_once 'config.php';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h1>Fixing Season Numbers</h1>";
    
    // Get all anime
    $stmt = $pdo->query("SELECT id, title FROM anime ORDER BY title");
    $animes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($animes as $anime) {
        echo "<h2>Processing: " . htmlspecialchars($anime['title']) . " (ID: {$anime['id']})</h2>";
        
        // Get all seasons for this anime
        $stmt = $pdo->prepare("SELECT * FROM seasons WHERE anime_id = ? ORDER BY id");
        $stmt->execute([$anime['id']]);
        $seasons = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Count how many seasons have the same season number
        $seasonCounts = [];
        foreach ($seasons as $season) {
            $seasonNumber = $season['season_number'];
            if (!isset($seasonCounts[$seasonNumber])) {
                $seasonCounts[$seasonNumber] = 0;
            }
            $seasonCounts[$seasonNumber]++;
        }
        
        // Check if any season number appears multiple times
        $hasDuplicates = false;
        foreach ($seasonCounts as $seasonNumber => $count) {
            if ($count > 1) {
                $hasDuplicates = true;
                echo "<p>Season $seasonNumber appears $count times</p>";
            }
        }
        
        if ($hasDuplicates) {
            echo "<p>Fixing duplicate season numbers...</p>";
            
            // Group seasons by season number
            $seasonGroups = [];
            foreach ($seasons as $season) {
                $seasonNumber = $season['season_number'];
                if (!isset($seasonGroups[$seasonNumber])) {
                    $seasonGroups[$seasonNumber] = [];
                }
                $seasonGroups[$seasonNumber][] = $season;
            }
            
            // For each group of seasons with the same number, renumber them
            foreach ($seasonGroups as $seasonNumber => $seasonGroup) {
                if (count($seasonGroup) > 1) {
                    echo "<p>Renumbering seasons with number $seasonNumber</p>";
                    
                    // Keep the first one as is, renumber the rest
                    for ($i = 1; $i < count($seasonGroup); $i++) {
                        $season = $seasonGroup[$i];
                        $newSeasonNumber = $seasonNumber + $i;
                        
                        // Check if the new season number is already taken
                        while (isset($seasonCounts[$newSeasonNumber]) && $seasonCounts[$newSeasonNumber] > 0) {
                            $newSeasonNumber++;
                        }
                        
                        echo "<p>Changing season ID {$season['id']} from season $seasonNumber to season $newSeasonNumber</p>";
                        
                        // Update the season number
                        $stmt = $pdo->prepare("UPDATE seasons SET season_number = ? WHERE id = ?");
                        $stmt->execute([$newSeasonNumber, $season['id']]);
                        
                        // Update the count
                        if (!isset($seasonCounts[$newSeasonNumber])) {
                            $seasonCounts[$newSeasonNumber] = 0;
                        }
                        $seasonCounts[$newSeasonNumber]++;
                        $seasonCounts[$seasonNumber]--;
                    }
                }
            }
            
            echo "<p>Fixed season numbers for anime ID {$anime['id']}</p>";
        } else {
            echo "<p>No duplicate season numbers found</p>";
        }
    }
    
    echo "<h2>All season numbers fixed!</h2>";
    
} catch (PDOException $e) {
    echo "<h2>Database error:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?> 