<?php
// Database connection settings
$host = 'localhost';
$dbname = 'glorious_hq';
$username = 'glorious_hq';
$password = 'glorious_hq';

// Initialize response array for API endpoints
function initializeResponse() {
    return [
        'success' => false,
        'message' => '',
        'data' => null
    ];
}

/**
 * Test database connection and provide detailed error messages
 * @return array Connection status with success flag and message
 */
function testDatabaseConnection() {
    global $host, $dbname, $username, $password;
    
    $result = [
        'success' => false,
        'message' => '',
        'error_code' => null,
        'connection_string' => "mysql:host=$host;dbname=$dbname"
    ];
    
    try {
        // Try to connect to the database
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Test if we can query
        $stmt = $pdo->query("SELECT 1");
        $stmt->fetch();
        
        $result['success'] = true;
        $result['message'] = 'Database connection successful';
        
    } catch (PDOException $e) {
        $result['success'] = false;
        $result['error_code'] = $e->getCode();
        
        // Provide detailed error message based on error code
        switch ($e->getCode()) {
            case 1049:
                $result['message'] = "Database '$dbname' not found. Please check your database configuration.";
                break;
            case 1045:
                $result['message'] = "Access denied for user '$username'. Invalid database credentials.";
                break;
            case 2002:
                $result['message'] = "Cannot connect to database server at '$host'. Server might be down or unreachable.";
                break;
            case '42S02':
                $result['message'] = "Table not found. Please run the database setup script first.";
                break;
            default:
                $result['message'] = 'Database connection error: ' . $e->getMessage();
                break;
        }
    }
    
    return $result;
}
?> 