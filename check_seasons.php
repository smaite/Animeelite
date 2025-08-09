<?php
// Script to check seasons data
require_once 'config.php';

try {
    // Connect to database
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get all seasons
    $stmt = $pdo->query("SELECT * FROM seasons ORDER BY anime_id, season_number, part_number");
    $seasons = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h1>Seasons Data</h1>";
    echo "<pre>";
    print_r($seasons);
    echo "</pre>";
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
}
?> 