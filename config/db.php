<?php
/**
 * Database Connection (Legacy - mysqli)
 * Creates database if it doesn't exist
 */

$host = '127.0.0.1';
$dbname = 'internship';
$user = 'root';
$pass = '';

// First connect without database to create it if needed
$conn = new mysqli($host, $user, $pass);
if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

// Create database if it doesn't exist
$conn->query("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

// Now connect to the database
$conn->select_db($dbname);
$conn->set_charset('utf8mb4');

// Check if tables exist, if not create them
$tables_exist = $conn->query("SHOW TABLES LIKE 'users'")->num_rows > 0;

if (!$tables_exist) {
    // Create essential tables
    $sql = "
    -- Users table
    CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(150) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role ENUM('student', 'company', 'admin') NOT NULL DEFAULT 'student',
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;

    -- Student profiles
    CREATE TABLE IF NOT EXISTS student_profiles (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL UNIQUE,
        university VARCHAR(200) DEFAULT NULL,
        course VARCHAR(200) DEFAULT NULL,
        graduation_year INT DEFAULT NULL,
        skills TEXT DEFAULT NULL,
        bio TEXT DEFAULT NULL,
        resume_path VARCHAR(255) DEFAULT NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB;

    -- Company profiles
    CREATE TABLE IF NOT EXISTS company_profiles (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL UNIQUE,
        company_name VARCHAR(200) NOT NULL,
        industry VARCHAR(100) DEFAULT NULL,
        description TEXT DEFAULT NULL,
        website VARCHAR(255) DEFAULT NULL,
        location VARCHAR(200) DEFAULT NULL,
        logo_path VARCHAR(255) DEFAULT NULL,
        is_verified TINYINT(1) DEFAULT 0,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB;

    -- Internships
    CREATE TABLE IF NOT EXISTS internships (
        id INT AUTO_INCREMENT PRIMARY KEY,
        company_id INT NOT NULL,
        title VARCHAR(200) NOT NULL,
        description TEXT NOT NULL,
        requirements TEXT DEFAULT NULL,
        responsibilities TEXT DEFAULT NULL,
        location VARCHAR(200) DEFAULT NULL,
        type ENUM('remote', 'onsite', 'hybrid') DEFAULT 'onsite',
        duration VARCHAR(100) DEFAULT NULL,
        stipend VARCHAR(100) DEFAULT NULL,
        positions INT DEFAULT 1,
        deadline DATE DEFAULT NULL,
        status ENUM('open', 'closed', 'filled') DEFAULT 'open',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        expiration_date DATE DEFAULT NULL,
        FOREIGN KEY (company_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB;

    -- Applications
    CREATE TABLE IF NOT EXISTS applications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        internship_id INT NOT NULL,
        cover_letter TEXT DEFAULT NULL,
        status ENUM('pending', 'reviewed', 'shortlisted', 'accepted', 'rejected') DEFAULT 'pending',
        applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (internship_id) REFERENCES internships(id) ON DELETE CASCADE,
        UNIQUE KEY unique_application (student_id, internship_id)
    ) ENGINE=InnoDB;

    -- Notifications
    CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        title VARCHAR(200) NOT NULL,
        message TEXT NOT NULL,
        type VARCHAR(50) DEFAULT 'info',
        link VARCHAR(255) DEFAULT NULL,
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB;

    -- Insert default admin
    INSERT INTO users (name, email, password, role) VALUES
    ('Admin', 'admin@internhub.com', '" . password_hash('password', PASSWORD_DEFAULT) . "', 'admin')
    ON DUPLICATE KEY UPDATE name = name;
    ";

    // Execute each statement
    $conn->multi_query($sql);
    while ($conn->next_result()) {;} // Flush multi_query results
}
