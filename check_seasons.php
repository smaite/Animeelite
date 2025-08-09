<?php
// Script to check seasons data
require_once 'config.php';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h1>Checking Seasons Data</h1>";
    
    // Get all anime
    $stmt = $pdo->query("SELECT id, title FROM anime ORDER BY title");
    $animes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Found " . count($animes) . " anime titles</h2>";
    
    foreach ($animes as $anime) {
        echo "<h3>" . htmlspecialchars($anime['title']) . " (ID: {$anime['id']})</h3>";
        
        // Get all seasons for this anime
        $stmt = $pdo->prepare("SELECT * FROM seasons WHERE anime_id = ? ORDER BY season_number, part_number");
        $stmt->execute([$anime['id']]);
        $seasons = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p>Found " . count($seasons) . " seasons</p>";
        
        if (count($seasons) > 0) {
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>ID</th><th>Season Number</th><th>Part Number</th><th>Title</th><th>Episode Count</th></tr>";
            
            foreach ($seasons as $season) {
                // Count episodes for this season
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM episodes WHERE season_id = ?");
                $stmt->execute([$season['id']]);
                $episodeCount = $stmt->fetchColumn();
                
                echo "<tr>";
                echo "<td>{$season['id']}</td>";
                echo "<td>{$season['season_number']}</td>";
                echo "<td>" . ($season['part_number'] ?? 'N/A') . "</td>";
                echo "<td>" . htmlspecialchars($season['title'] ?? 'Untitled') . "</td>";
                echo "<td>$episodeCount</td>";
                echo "</tr>";
            }
            
            echo "</table>";
        }
    }
    
} catch (PDOException $e) {
    echo "<h2>Database error:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?> 