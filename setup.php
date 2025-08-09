<?php
// Database setup script
require_once 'config.php';

// Enable error display during setup
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Set header for proper HTML rendering
header('Content-Type: text/html; charset=utf-8');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AnimeElite Database Setup</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            color: #333;
        }
        h1, h2 {
            color: #2c3e50;
            margin-top: 30px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .info {
            background-color: #d1ecf1;
            color: #0c5460;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        pre {
            background: #f8f8f8;
            padding: 10px;
            border-radius: 5px;
            overflow: auto;
        }
        .btn {
            display: inline-block;
            background-color: #3498db;
            color: white;
            padding: 10px 15px;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <h1>AnimeElite Database Setup</h1>

<?php
try {
    // Connect to MySQL server without selecting a database
    $conn = new mysqli($host, $username, $password);
    
    if ($conn->connect_error) {
        throw new Exception("MySQL connection failed: " . $conn->connect_error);
    }
    
    echo "<div class='success'>Connected to MySQL server successfully.</div>";
    
    // Create database if it doesn't exist
    $sql = "CREATE DATABASE IF NOT EXISTS $dbname";
    if ($conn->query($sql) === TRUE) {
        echo "<div class='success'>Database '$dbname' created successfully or already exists.</div>";
    } else {
        throw new Exception("Error creating database: " . $conn->error);
    }
    
    // Select the database
    $conn->select_db($dbname);
    echo "<div class='info'>Using database: $dbname</div>";
    
    // Read SQL file
    $sql_file = file_get_contents('db_setup.sql');
    if (!$sql_file) {
        throw new Exception("Could not read db_setup.sql file.");
    }
    
    echo "<div class='info'>Successfully read SQL setup file.</div>";
    
    // Execute SQL statements
    $statements = explode(';', $sql_file);
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            if ($conn->query($statement) === FALSE) {
                throw new Exception("Error executing SQL statement: " . $conn->error . "<br>Statement: " . htmlspecialchars($statement));
            }
        }
    }
    
    echo "<div class='success'>Database setup completed successfully!</div>";
    
    // Check if tables were created
    $tables = ['anime', 'seasons', 'episodes', 'users', 'sessions', 'subscriptions', 'coupons', 'watch_history', 'favorites'];
    echo "<h2>Database Tables Status:</h2>";
    echo "<ul>";
    foreach ($tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        if ($result->num_rows > 0) {
            // Count rows
            $count_result = $conn->query("SELECT COUNT(*) as count FROM $table");
            $count = $count_result->fetch_assoc()['count'];
            echo "<li>Table '$table': <strong>Created</strong> ($count records)</li>";
        } else {
            echo "<li>Table '$table': <strong style='color:red'>Not created</strong></li>";
        }
    }
    echo "</ul>";
    
    // Close connection
    $conn->close();
    
    echo "<div class='info'>You can now use the AnimeElite website. <a href='index.php' class='btn'>Go to Homepage</a></div>";
    
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<h2>Error:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
    
    echo "<div class='info'>";
    echo "<h2>Troubleshooting:</h2>";
    echo "<ol>";
    echo "<li>Check that your MySQL server is running</li>";
    echo "<li>Verify the credentials in config.php are correct</li>";
    echo "<li>Make sure the web server has permissions to create databases</li>";
    echo "<li>Check for any syntax errors in db_setup.sql</li>";
    echo "</ol>";
    echo "</div>";
}
?>

</body>
</html> 