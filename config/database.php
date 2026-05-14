<?php
/**
 * Database Configuration
 * Internship Management System
 */

// Database credentials - Update these for your environment
define('DB_HOST', 'localhost');
define('DB_NAME', 'internship_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// Create connection
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    // For development - show error
    // For production - log error and show generic message
    die("Database connection failed. Please check your configuration.");
}

/**
 * Helper function to safely execute queries
 */
function db_query($sql, $params = []) {
    global $pdo;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

/**
 * Get single row
 */
function db_fetch($sql, $params = []) {
    return db_query($sql, $params)->fetch();
}

/**
 * Get multiple rows
 */
function db_fetch_all($sql, $params = []) {
    return db_query($sql, $params)->fetchAll();
}

/**
 * Get last insert ID
 */
function db_last_id() {
    global $pdo;
    return $pdo->lastInsertId();
}
