<?php
// Script to remove part_number system from seasons table
require_once 'config.php';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h1>Removing Part Number System</h1>";
    
    // Check if part_number column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM seasons LIKE 'part_number'");
    $columnExists = $stmt->rowCount() > 0;
    
    if ($columnExists) {
        echo "<p>Found part_number column, removing...</p>";
        
        // Remove any unique constraints that include part_number
        try {
            $pdo->exec("ALTER TABLE seasons DROP INDEX unique_season_part");
            echo "<p>Removed unique_season_part constraint</p>";
        } catch (PDOException $e) {
            echo "<p>Note: unique_season_part constraint not found or already removed</p>";
        }
        
        // Remove part_number column
        $pdo->exec("ALTER TABLE seasons DROP COLUMN part_number");
        echo "<p>Removed part_number column</p>";
        
        // Add back simple unique constraint
        try {
            $pdo->exec("ALTER TABLE seasons ADD CONSTRAINT unique_season UNIQUE (anime_id, season_number)");
            echo "<p>Added unique_season constraint</p>";
        } catch (PDOException $e) {
            echo "<p>Note: unique_season constraint may already exist</p>";
        }
        
        echo "<p><strong>Part number system successfully removed!</strong></p>";
    } else {
        echo "<p>Part_number column not found. The system may already be removed.</p>";
    }
    
} catch (PDOException $e) {
    echo "<h2>Database error:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?> 