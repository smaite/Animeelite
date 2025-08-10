<?php
// Quick test to verify the seasons helper function works
require_once 'config.php';
require_once 'includes/seasons_helper.php';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h1>Testing Seasons Helper Function</h1>";
    
    // Test with anime ID 2 (Demon Slayer)
    $anime_id = 2;
    echo "<h2>Testing with anime ID $anime_id:</h2>";
    
    $seasons = getSeasonsWithEpisodes($anime_id, $pdo);
    
    echo "<table border='1'>";
    echo "<tr><th>Season Number</th><th>Title</th><th>Episodes Count</th></tr>";
    
    foreach ($seasons as $season) {
        echo "<tr>";
        echo "<td>{$season['season_number']}</td>";
        echo "<td>" . htmlspecialchars($season['title'] ?? 'N/A') . "</td>";
        echo "<td>{$season['episode_count']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<p><strong>Total seasons found: " . count($seasons) . "</strong></p>";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?> 