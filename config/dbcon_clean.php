<?php
/**
 * CLEAN DATABASE CONFIGURATION
 * Error-free database connection setup
 */

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'internship');

// Create connection
try {
    $conn = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);
    
    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // Set charset
    $conn->set_charset("utf8mb4");
    
    // Error reporting for development
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    
} catch (Exception $e) {
    // Log error and show user-friendly message
    error_log("Database connection error: " . $e->getMessage());
    
    // User-friendly error message
    die("<div style='text-align: center; padding: 50px; font-family: Arial, sans-serif;'>
        <h2>🔌 Database Connection Error</h2>
        <p>We're having trouble connecting to the database.</p>
        <p>Please check your database configuration and run the setup script.</p>
        <p><a href='setup_database_clean.php'>Run Database Setup</a></p>
        <small>Error: " . htmlspecialchars($e->getMessage()) . "</small>
    </div>");
}

// Function to safely execute queries
function safeQuery($conn, $sql, $params = [], $types = "") {
    try {
        $stmt = $conn->prepare($sql);
        if ($params && $types) {
            // Use call_user_func_array for compatibility with older PHP versions
            $bind_params = array_merge([$types], $params);
            call_user_func_array([$stmt, 'bind_param'], makeReferences($bind_params));
        }
        $stmt->execute();
        return $stmt;
    } catch (Exception $e) {
        error_log("Query error: " . $e->getMessage());
        throw $e;
    }
}

// Helper function to create references for bind_param
function makeReferences($arr) {
    $refs = [];
    foreach ($arr as $key => $value) {
        $refs[$key] = &$arr[$key];
    }
    return $refs;
}

// Function to check if tables exist
function tablesExist($conn) {
    $result = $conn->query("SHOW TABLES");
    return $result->num_rows > 0;
}

// Function to get database info
function getDatabaseInfo($conn) {
    $info = [];
    
    // Get table count
    $result = $conn->query("SHOW TABLES");
    $info['table_count'] = $result->num_rows;
    
    // Get user count
    $result = $conn->query("SELECT COUNT(*) as count FROM users");
    $info['user_count'] = $result->fetch_assoc()['count'];
    
    // Get database size
    $result = $conn->query("SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'size_mb' 
                           FROM information_schema.tables 
                           WHERE table_schema = '" . DB_NAME . "'");
    $info['size_mb'] = $result->fetch_assoc()['size_mb'];
    
    return $info;
}
?>
