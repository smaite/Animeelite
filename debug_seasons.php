<?php
// Quick debug script to see what's happening with seasons
require_once 'config.php';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h1>Season Debug</h1>";
    
    // Check a specific anime - let's use anime ID 2 (Demon Slayer) as an example
    $anime_id = 2;
    
    echo "<h2>Raw seasons data for anime ID $anime_id:</h2>";
    $stmt = $pdo->prepare("SELECT * FROM seasons WHERE anime_id = ? ORDER BY season_number ASC");
    $stmt->execute([$anime_id]);
    $seasons = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Season Number</th><th>Title</th><th>Part Number</th><th>Episodes Count</th></tr>";
    
    foreach ($seasons as $season) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM episodes WHERE season_id = ?");
        $stmt->execute([$season['id']]);
        $episodeCount = $stmt->fetchColumn();
        
        echo "<tr>";
        echo "<td>{$season['id']}</td>";
        echo "<td>{$season['season_number']}</td>";
        echo "<td>" . htmlspecialchars($season['title'] ?? 'N/A') . "</td>";
        echo "<td>" . ($season['part_number'] ?? 'N/A') . "</td>";
        echo "<td>$episodeCount</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h2>What the frontend query returns:</h2>";
    echo "<p>Query: SELECT * FROM seasons WHERE anime_id = ? ORDER BY season_number ASC</p>";
    echo "<p>Result count: " . count($seasons) . "</p>";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?> 