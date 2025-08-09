<?php
// Fix the unique constraint on the seasons table
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
    
    // Drop the old unique constraint
    $pdo->exec("ALTER TABLE seasons DROP INDEX unique_season");
    output("Dropped old unique constraint.", true);
    
    // Add the new unique constraint
    $pdo->exec("ALTER TABLE seasons ADD CONSTRAINT unique_season_part UNIQUE (anime_id, season_number, part_number)");
    output("Added new unique constraint with part_number.", true);
    
    output("Constraint update completed successfully!", true);
    
} catch (PDOException $e) {
    if (strpos($e->getMessage(), "Can't DROP") !== false) {
        // The constraint might already be updated or have a different name
        try {
            // Try to add the new constraint directly
            $pdo->exec("ALTER TABLE seasons ADD CONSTRAINT unique_season_part UNIQUE (anime_id, season_number, part_number)");
            output("Added new unique constraint with part_number.", true);
            output("Constraint update completed successfully!", true);
        } catch (PDOException $e2) {
            if (strpos($e2->getMessage(), "Duplicate entry") !== false) {
                output("You have duplicate season entries with the same anime_id, season_number, and part_number. Please fix these duplicates before updating the constraint.", false);
            } else {
                output("Database Error: " . $e2->getMessage(), false);
            }
        }
    } else {
        output("Database Error: " . $e->getMessage(), false);
    }
} catch (Exception $e) {
    output("Error: " . $e->getMessage(), false);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix Season Constraint</title>
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
    <h1>Fix Season Constraint</h1>
    <p>This script updates the unique constraint on the seasons table to include part_number.</p>
    <a href="admin/anime_management.php" class="button">Go to Anime Management</a>
</body>
</html> 