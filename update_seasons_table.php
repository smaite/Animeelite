<?php
// Update the seasons table structure
require_once 'config.php';

// Function to output results as HTML
function output($message, $success = true) {
    echo '<div style="margin: 10px; padding: 10px; border-radius: 5px; ' . 
         'background-color: ' . ($success ? '#d1e7dd' : '#f8d7da') . '; ' .
         'color: ' . ($success ? '#0f5132' : '#842029') . ';">' . 
         $message . '</div>';
}

try {
    // Connect to the database
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    output("Connected to database successfully.", true);
    
    // Check if part_number column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM seasons LIKE 'part_number'");
    $columnExists = ($stmt->rowCount() > 0);
    
    if (!$columnExists) {
        // Add part_number column if it doesn't exist
        $pdo->exec("ALTER TABLE seasons ADD COLUMN part_number INT DEFAULT 1 AFTER season_number");
        output("Added part_number column to seasons table.", true);
    } else {
        output("part_number column already exists.", true);
    }
    
    // Check for duplicate entries
    $stmt = $pdo->query("
        SELECT anime_id, season_number, part_number, COUNT(*) as count
        FROM seasons
        GROUP BY anime_id, season_number, part_number
        HAVING COUNT(*) > 1
    ");
    
    $duplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($duplicates) > 0) {
        output("Found duplicate entries that would violate the unique constraint:", false);
        echo "<pre>";
        print_r($duplicates);
        echo "</pre>";
        
        // Suggest a fix for duplicates
        output("You need to fix these duplicates before adding the unique constraint. You can update the part_number for one of the duplicates to make it unique.", false);
    } else {
        // Get all constraints on the seasons table
        $stmt = $pdo->query("
            SELECT CONSTRAINT_NAME
            FROM information_schema.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = '$dbname'
            AND TABLE_NAME = 'seasons'
            AND CONSTRAINT_TYPE = 'UNIQUE'
        ");
        
        $constraints = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Drop existing unique constraints
        foreach ($constraints as $constraint) {
            if ($constraint !== 'PRIMARY') {
                $pdo->exec("ALTER TABLE seasons DROP INDEX `$constraint`");
                output("Dropped constraint: $constraint", true);
            }
        }
        
        // Add the new unique constraint
        $pdo->exec("ALTER TABLE seasons ADD CONSTRAINT unique_season_part UNIQUE (anime_id, season_number, part_number)");
        output("Added new unique constraint with part_number.", true);
        
        output("Table update completed successfully!", true);
    }
    
} catch (PDOException $e) {
    output("Database Error: " . $e->getMessage(), false);
} catch (Exception $e) {
    output("Error: " . $e->getMessage(), false);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Seasons Table</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f0f2f5;
        }
        h1 {
            color: #333;
        }
        pre {
            background-color: #f8f8f8;
            padding: 10px;
            border-radius: 5px;
            overflow-x: auto;
        }
        .button {
            display: inline-block;
            padding: 10px 15px;
            background-color: #4a5568;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
        .button:hover {
            background-color: #2d3748;
        }
    </style>
</head>
<body>
    <h1>Update Seasons Table</h1>
    <p>This script updates the seasons table to support multiple parts per season.</p>
    <a href="admin/anime_management.php" class="button">Go to Anime Management</a>
</body>
</html> 