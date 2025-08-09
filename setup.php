<?php
// Database setup script
require_once 'config.php';

// Function to output results as HTML
function output($message, $success = true) {
    echo '<div style="margin: 10px; padding: 10px; border-radius: 5px; ' . 
         'background-color: ' . ($success ? '#d1e7dd' : '#f8d7da') . '; ' .
         'color: ' . ($success ? '#0f5132' : '#842029') . ';">' . 
         $message . '</div>';
}

try {
    // Connect to MySQL without selecting a database
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    output("Connected to MySQL server successfully.", true);
    
    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
    output("Database '$dbname' created or already exists.", true);
    
    // Select the database
    $pdo->exec("USE `$dbname`");
    output("Database '$dbname' selected.", true);
    
    // Read SQL file
    $sql = file_get_contents('db_setup.sql');
    if (!$sql) {
        throw new Exception("Could not read db_setup.sql file.");
    }
    
    // Split SQL file into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    // Execute each statement
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            $pdo->exec($statement);
        }
    }
    
    output("Database setup completed successfully!", true);
    
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
    <title>AnimeElite Setup</title>
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
    <h1>AnimeElite Database Setup</h1>
    <p>The setup process has been completed. You can now navigate to the homepage.</p>
    <a href="index.php" class="button">Go to Homepage</a>
</body>
</html> 