<?php
// Script to add last_active column to users table
require_once 'config.php';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if last_active column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'last_active'");
    $columnExists = $stmt->rowCount() > 0;
    
    if (!$columnExists) {
        // Add last_active column
        $pdo->exec("ALTER TABLE users ADD COLUMN last_active TIMESTAMP NULL");
        echo "Successfully added last_active column to users table.\n";
    } else {
        echo "The last_active column already exists in the users table.\n";
    }
    
    echo "Database update completed successfully.";
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
}
?> 