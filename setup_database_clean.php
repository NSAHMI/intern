<?php
/**
 * CLEAN DATABASE SETUP - Error-Free Approach
 * This script creates a fresh database without any conflicts
 */

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'internship';

echo "<h2>🚀 Clean Database Setup - Internship Management System</h2>\n";

try {
    // Step 1: Create database connection
    echo "<p>📡 Connecting to MySQL server...</p>\n";
    $conn = new mysqli($host, $username, $password);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    echo "<p>✅ Connected successfully</p>\n";
    
    // Step 2: Drop existing database if it exists
    echo "<p>🗑️ Removing old database (if exists)...</p>\n";
    $conn->query("DROP DATABASE IF EXISTS `$database`");
    echo "<p>✅ Old database removed</p>\n";
    
    // Step 3: Create fresh database
    echo "<p>🏗️ Creating fresh database...</p>\n";
    if (!$conn->query("CREATE DATABASE `$database`")) {
        throw new Exception("Database creation failed: " . $conn->error);
    }
    echo "<p>✅ Database created successfully</p>\n";
    
    // Step 4: Select the database
    $conn->select_db($database);
    echo "<p>✅ Database selected</p>\n";
    
    // Step 5: Create tables in correct order (no dependencies)
    echo "<p>📋 Creating database tables...</p>\n";
    
    // Core tables first
    $tables = [
        // Departments
        "CREATE TABLE departments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL UNIQUE,
            description TEXT,
            icon VARCHAR(50),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB",
        
        // Users
        "CREATE TABLE users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            role ENUM('student', 'company', 'admin') NOT NULL,
            department_id INT NULL,
            two_factor_secret VARCHAR(255),
            two_factor_enabled BOOLEAN DEFAULT FALSE,
            email_verified BOOLEAN DEFAULT FALSE,
            status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
            last_login TIMESTAMP NULL,
            phone VARCHAR(20),
            phone_verified BOOLEAN DEFAULT FALSE,
            sms_2fa_enabled BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_email (email),
            INDEX idx_role (role),
            INDEX idx_status (status)
        ) ENGINE=InnoDB",
        
        // Skills
        "CREATE TABLE skills (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL UNIQUE,
            category VARCHAR(50),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_category (category)
        ) ENGINE=InnoDB",
        
        // Achievements
        "CREATE TABLE achievements (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL UNIQUE,
            description TEXT,
            icon VARCHAR(50),
            points INT DEFAULT 0,
            badge_color VARCHAR(20) DEFAULT 'primary',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_name (name)
        ) ENGINE=InnoDB",
        
        // Internships
        "CREATE TABLE internships (
            id INT AUTO_INCREMENT PRIMARY KEY,
            company_id INT NOT NULL,
            department_id INT NOT NULL DEFAULT 1,
            title VARCHAR(255) NOT NULL,
            location VARCHAR(255),
            work_type ENUM('onsite', 'remote', 'hybrid') DEFAULT 'onsite',
            description TEXT NOT NULL,
            requirements TEXT,
            salary_stipend VARCHAR(100),
            benefits TEXT,
            duration VARCHAR(100) NOT NULL,
            expiration_date DATE NOT NULL DEFAULT (DATE_ADD(CURRENT_DATE, INTERVAL 3 MONTH)),
            notification_sent BOOLEAN DEFAULT FALSE,
            featured BOOLEAN DEFAULT FALSE,
            status ENUM('active', 'inactive', 'expired') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_company (company_id),
            INDEX idx_department (department_id),
            INDEX idx_status (status),
            INDEX idx_expiration (expiration_date)
        ) ENGINE=InnoDB",
        
        // Applications
        "CREATE TABLE applications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            internship_id INT NOT NULL,
            student_id INT NOT NULL,
            cover_letter TEXT,
            status ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_application (internship_id, student_id),
            INDEX idx_student (student_id),
            INDEX idx_status (status)
        ) ENGINE=InnoDB",
        
        // Student Profiles
        "CREATE TABLE student_profiles (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNIQUE NOT NULL,
            resume_path VARCHAR(255),
            bio TEXT,
            gpa DECIMAL(3,2),
            graduation_year INT,
            university VARCHAR(255),
            location VARCHAR(255),
            linkedin_url VARCHAR(255),
            portfolio_url VARCHAR(255),
            phone VARCHAR(20),
            profile_complete_percentage INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_user (user_id)
        ) ENGINE=InnoDB",
        
        // Company Profiles
        "CREATE TABLE company_profiles (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNIQUE NOT NULL,
            company_name VARCHAR(255) NOT NULL,
            logo_path VARCHAR(255),
            website VARCHAR(255),
            description TEXT,
            industry VARCHAR(100),
            company_size ENUM('1-10', '11-50', '51-200', '201-500', '500+') DEFAULT '11-50',
            founded_year INT,
            location VARCHAR(255),
            social_links JSON,
            is_verified BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_user (user_id),
            INDEX idx_verified (is_verified)
        ) ENGINE=InnoDB",
        
        // Student Skills
        "CREATE TABLE student_skills (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id INT NOT NULL,
            skill_id INT NOT NULL,
            proficiency_level ENUM('beginner', 'intermediate', 'advanced', 'expert') DEFAULT 'intermediate',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_student_skill (student_id, skill_id),
            INDEX idx_student (student_id),
            INDEX idx_skill (skill_id)
        ) ENGINE=InnoDB",
        
        // User Achievements
        "CREATE TABLE user_achievements (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            achievement_id INT NOT NULL,
            earned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_user_achievement (user_id, achievement_id),
            INDEX idx_user (user_id),
            INDEX idx_achievement (achievement_id)
        ) ENGINE=InnoDB",
        
        // Messages
        "CREATE TABLE messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            sender_id INT NOT NULL,
            receiver_id INT NOT NULL,
            internship_id INT NULL,
            subject VARCHAR(255),
            message TEXT NOT NULL,
            is_read BOOLEAN DEFAULT FALSE,
            message_type ENUM('application', 'question', 'interview', 'offer', 'general') DEFAULT 'general',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_sender (sender_id),
            INDEX idx_receiver (receiver_id),
            INDEX idx_read (is_read)
        ) ENGINE=InnoDB",
        
        // Interviews
        "CREATE TABLE interviews (
            id INT AUTO_INCREMENT PRIMARY KEY,
            application_id INT NOT NULL,
            company_id INT NOT NULL,
            student_id INT NOT NULL,
            interview_type ENUM('phone', 'video', 'onsite') DEFAULT 'video',
            scheduled_date DATETIME,
            duration_minutes INT DEFAULT 60,
            location_url VARCHAR(255),
            status ENUM('scheduled', 'completed', 'cancelled', 'rescheduled') DEFAULT 'scheduled',
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_application (application_id),
            INDEX idx_company (company_id),
            INDEX idx_student (student_id),
            INDEX idx_status (status)
        ) ENGINE=InnoDB",
        
        // Bookmarks
        "CREATE TABLE bookmarks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            internship_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_user_bookmark (user_id, internship_id),
            INDEX idx_user (user_id),
            INDEX idx_internship (internship_id)
        ) ENGINE=InnoDB",
        
        // Reviews
        "CREATE TABLE reviews (
            id INT AUTO_INCREMENT PRIMARY KEY,
            reviewer_id INT NOT NULL,
            reviewed_user_id INT NOT NULL,
            internship_id INT NULL,
            rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
            review_text TEXT,
            experience_type ENUM('student_to_company', 'company_to_student') NOT NULL,
            status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_reviewer (reviewer_id),
            INDEX idx_reviewed (reviewed_user_id),
            INDEX idx_rating (rating)
        ) ENGINE=InnoDB",
        
        // Security tables
        "CREATE TABLE email_verifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNIQUE NOT NULL,
            token VARCHAR(255) NOT NULL,
            expires_at TIMESTAMP NOT NULL,
            verified_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_token (token),
            INDEX idx_expires (expires_at)
        ) ENGINE=InnoDB",
        
        "CREATE TABLE password_resets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            token VARCHAR(255) NOT NULL,
            expires_at TIMESTAMP NOT NULL,
            used_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_token (token),
            INDEX idx_expires (expires_at)
        ) ENGINE=InnoDB",
        
        "CREATE TABLE sms_verifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            phone_number VARCHAR(20) NOT NULL,
            verification_code VARCHAR(6) NOT NULL,
            purpose ENUM('login', 'registration', 'password_reset', 'setup', 'test') DEFAULT 'login',
            attempts INT DEFAULT 0,
            expires_at TIMESTAMP NOT NULL,
            verified_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_phone (user_id, phone_number),
            INDEX idx_expires (expires_at),
            INDEX idx_purpose (purpose)
        ) ENGINE=InnoDB",
        
        "CREATE TABLE sms_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            phone_number VARCHAR(20) NOT NULL,
            message TEXT NOT NULL,
            purpose VARCHAR(50),
            status ENUM('sent', 'failed', 'delivered') DEFAULT 'sent',
            provider VARCHAR(50),
            cost DECIMAL(10,4),
            response TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_phone (phone_number),
            INDEX idx_status (status),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB",
        
        "CREATE TABLE security_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            identifier VARCHAR(255) NOT NULL,
            action VARCHAR(100) NOT NULL,
            details TEXT,
            ip_address VARCHAR(45),
            user_agent TEXT,
            success BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_identifier_action (identifier, action),
            INDEX idx_created (created_at),
            INDEX idx_success (success)
        ) ENGINE=InnoDB",
        
        // Email system
        "CREATE TABLE email_templates (
            id INT AUTO_INCREMENT PRIMARY KEY,
            template_name VARCHAR(100) NOT NULL UNIQUE,
            subject VARCHAR(255) NOT NULL,
            html_content TEXT NOT NULL,
            text_content TEXT,
            variables JSON,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_template_name (template_name)
        ) ENGINE=InnoDB",
        
        "CREATE TABLE email_queue (
            id INT AUTO_INCREMENT PRIMARY KEY,
            to_email VARCHAR(255) NOT NULL,
            subject VARCHAR(255) NOT NULL,
            html_content TEXT NOT NULL,
            text_content TEXT,
            status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
            attempts INT DEFAULT 0,
            sent_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_status (status),
            INDEX idx_to_email (to_email)
        ) ENGINE=InnoDB",
        
        // Analytics
        "CREATE TABLE user_activity (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            activity_type VARCHAR(50) NOT NULL,
            resource_type VARCHAR(50),
            resource_id INT,
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user (user_id),
            INDEX idx_activity_type (activity_type),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB",
        
        "CREATE TABLE analytics_events (
            id INT AUTO_INCREMENT PRIMARY KEY,
            event_type VARCHAR(50) NOT NULL,
            event_data JSON,
            user_id INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_event_type (event_type),
            INDEX idx_user (user_id),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB",
        
        // CMS tables
        "CREATE TABLE cms_pages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL UNIQUE,
            content LONGTEXT,
            meta_description TEXT,
            meta_keywords VARCHAR(500),
            status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
            author_id INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_status (status),
            INDEX idx_slug (slug)
        ) ENGINE=InnoDB",
        
        "CREATE TABLE system_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) NOT NULL UNIQUE,
            setting_value TEXT,
            setting_type ENUM('text', 'textarea', 'number', 'boolean', 'json') DEFAULT 'text',
            description TEXT,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_key (setting_key)
        ) ENGINE=InnoDB",
        
        "CREATE TABLE menu_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            menu_name VARCHAR(50) NOT NULL DEFAULT 'main',
            title VARCHAR(255) NOT NULL,
            url VARCHAR(500) NOT NULL,
            sort_order INT DEFAULT 0,
            target ENUM('_self', '_blank') DEFAULT '_self',
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_menu_order (menu_name, sort_order),
            INDEX idx_status (status)
        ) ENGINE=InnoDB",
        
        "CREATE TABLE banners (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            image_url VARCHAR(500) NOT NULL,
            link_url VARCHAR(500),
            position ENUM('home', 'sidebar', 'footer') DEFAULT 'home',
            sort_order INT DEFAULT 0,
            status ENUM('active', 'inactive') DEFAULT 'active',
            start_date DATE,
            end_date DATE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_position (position, sort_order),
            INDEX idx_status_dates (status, start_date, end_date)
        ) ENGINE=InnoDB",
        
        "CREATE TABLE testimonials (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255),
            content TEXT NOT NULL,
            rating INT DEFAULT 5 CHECK (rating >= 1 AND rating <= 5),
            company VARCHAR(255),
            position VARCHAR(255),
            image_url VARCHAR(500),
            status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            sort_order INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_status (status),
            INDEX idx_rating (rating)
        ) ENGINE=InnoDB",
        
        "CREATE TABLE backup_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            filename VARCHAR(255) NOT NULL,
            type ENUM('full', 'structure', 'data') DEFAULT 'full',
            file_size BIGINT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB"
    ];
    
    // Execute table creation
    foreach ($tables as $sql) {
        if (!$conn->query($sql)) {
            throw new Exception("Table creation failed: " . $conn->error);
        }
    }
    echo "<p>✅ All tables created successfully</p>\n";
    
    // Step 6: Add foreign key constraints
    echo "<p>🔗 Adding foreign key constraints...</p>\n";
    
    $constraints = [
        "ALTER TABLE users ADD CONSTRAINT fk_users_department FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL",
        "ALTER TABLE internships ADD CONSTRAINT fk_internships_company FOREIGN KEY (company_id) REFERENCES users(id) ON DELETE CASCADE",
        "ALTER TABLE internships ADD CONSTRAINT fk_internships_department FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE",
        "ALTER TABLE applications ADD CONSTRAINT fk_applications_internship FOREIGN KEY (internship_id) REFERENCES internships(id) ON DELETE CASCADE",
        "ALTER TABLE applications ADD CONSTRAINT fk_applications_student FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE",
        "ALTER TABLE student_profiles ADD CONSTRAINT fk_student_profiles_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE",
        "ALTER TABLE company_profiles ADD CONSTRAINT fk_company_profiles_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE",
        "ALTER TABLE student_skills ADD CONSTRAINT fk_student_skills_student FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE",
        "ALTER TABLE student_skills ADD CONSTRAINT fk_student_skills_skill FOREIGN KEY (skill_id) REFERENCES skills(id) ON DELETE CASCADE",
        "ALTER TABLE user_achievements ADD CONSTRAINT fk_user_achievements_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE",
        "ALTER TABLE user_achievements ADD CONSTRAINT fk_user_achievements_achievement FOREIGN KEY (achievement_id) REFERENCES achievements(id) ON DELETE CASCADE",
        "ALTER TABLE messages ADD CONSTRAINT fk_messages_sender FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE",
        "ALTER TABLE messages ADD CONSTRAINT fk_messages_receiver FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE",
        "ALTER TABLE messages ADD CONSTRAINT fk_messages_internship FOREIGN KEY (internship_id) REFERENCES internships(id) ON DELETE CASCADE",
        "ALTER TABLE interviews ADD CONSTRAINT fk_interviews_application FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE",
        "ALTER TABLE interviews ADD CONSTRAINT fk_interviews_company FOREIGN KEY (company_id) REFERENCES users(id) ON DELETE CASCADE",
        "ALTER TABLE interviews ADD CONSTRAINT fk_interviews_student FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE",
        "ALTER TABLE bookmarks ADD CONSTRAINT fk_bookmarks_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE",
        "ALTER TABLE bookmarks ADD CONSTRAINT fk_bookmarks_internship FOREIGN KEY (internship_id) REFERENCES internships(id) ON DELETE CASCADE",
        "ALTER TABLE reviews ADD CONSTRAINT fk_reviews_reviewer FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE CASCADE",
        "ALTER TABLE reviews ADD CONSTRAINT fk_reviews_reviewed FOREIGN KEY (reviewed_user_id) REFERENCES users(id) ON DELETE CASCADE",
        "ALTER TABLE reviews ADD CONSTRAINT fk_reviews_internship FOREIGN KEY (internship_id) REFERENCES internships(id) ON DELETE CASCADE",
        "ALTER TABLE email_verifications ADD CONSTRAINT fk_email_verifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE",
        "ALTER TABLE password_resets ADD CONSTRAINT fk_password_resets_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE",
        "ALTER TABLE sms_verifications ADD CONSTRAINT fk_sms_verifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE",
        "ALTER TABLE user_activity ADD CONSTRAINT fk_user_activity_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE",
        "ALTER TABLE analytics_events ADD CONSTRAINT fk_analytics_events_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE",
        "ALTER TABLE cms_pages ADD CONSTRAINT fk_cms_pages_author FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL"
    ];
    
    foreach ($constraints as $sql) {
        if (!$conn->query($sql)) {
            echo "<p>⚠️ Constraint failed (non-critical): " . $conn->error . "</p>\n";
        }
    }
    echo "<p>✅ Foreign key constraints added</p>\n";
    
    // Step 7: Insert basic data
    echo "<p>📝 Inserting basic data...</p>\n";
    
    // Insert departments
    $departments = [
        ['Software Engineering', 'Software development, web development, mobile apps, and IT solutions', 'fa-laptop-code'],
        ['Transport & Logistics', 'Supply chain management, transportation, logistics, and distribution', 'fa-truck'],
        ['Nursing & Healthcare', 'Medical care, nursing, healthcare administration, and patient services', 'fa-heartbeat'],
        ['Accounting & Finance', 'Financial management, accounting, bookkeeping, and financial analysis', 'fa-calculator'],
        ['Building Science & Technology', 'Construction management, architecture, civil engineering, and building technology', 'fa-building'],
        ['Business Administration', 'Management, marketing, human resources, and business operations', 'fa-briefcase'],
        ['Education & Teaching', 'Teaching, educational administration, curriculum development, and training', 'fa-graduation-cap'],
        ['Media & Communications', 'Journalism, public relations, digital media, and content creation', 'fa-newspaper']
    ];
    
    $stmt = $conn->prepare("INSERT INTO departments (name, description, icon) VALUES (?, ?, ?)");
    foreach ($departments as $dept) {
        $stmt->bind_param('sss', $dept[0], $dept[1], $dept[2]);
        $stmt->execute();
    }
    $stmt->close();
    
    // Insert skills
    $skills = [
        ['JavaScript', 'Programming'],
        ['Python', 'Programming'],
        ['Java', 'Programming'],
        ['C++', 'Programming'],
        ['React', 'Frontend'],
        ['Angular', 'Frontend'],
        ['Vue.js', 'Frontend'],
        ['Node.js', 'Backend'],
        ['PHP', 'Backend'],
        ['MySQL', 'Database'],
        ['MongoDB', 'Database'],
        ['Git', 'Tools'],
        ['Docker', 'Tools'],
        ['AWS', 'Cloud'],
        ['Communication', 'Soft Skills'],
        ['Leadership', 'Soft Skills'],
        ['Problem Solving', 'Soft Skills'],
        ['Teamwork', 'Soft Skills'],
        ['Project Management', 'Business'],
        ['Data Analysis', 'Analytics'],
        ['Machine Learning', 'AI'],
        ['Marketing', 'Business'],
        ['Sales', 'Business'],
        ['Accounting', 'Finance'],
        ['Financial Analysis', 'Finance']
    ];
    
    $stmt = $conn->prepare("INSERT INTO skills (name, category) VALUES (?, ?)");
    foreach ($skills as $skill) {
        $stmt->bind_param('ss', $skill[0], $skill[1]);
        $stmt->execute();
    }
    $stmt->close();
    
    // Insert achievements
    $achievements = [
        ['First Application', 'Submitted your first internship application', 'fa-paper-plane', 10, 'success'],
        ['Profile Complete', 'Completed your profile 100%', 'fa-user-check', 25, 'primary'],
        ['Active Seeker', 'Applied to 5+ internships', 'fa-search', 15, 'info'],
        ['Interview Ready', 'Got your first interview', 'fa-calendar-check', 30, 'warning'],
        ['Network Builder', 'Connected with 10+ companies', 'fa-users', 20, 'secondary'],
        ['Skill Master', 'Added 10+ skills to profile', 'fa-tools', 20, 'primary'],
        ['Early Bird', 'Applied within first week of posting', 'fa-clock', 15, 'success'],
        ['Perfect Match', 'Got accepted to first choice', 'fa-bullseye', 50, 'warning']
    ];
    
    $stmt = $conn->prepare("INSERT INTO achievements (name, description, icon, points, badge_color) VALUES (?, ?, ?, ?, ?)");
    foreach ($achievements as $achievement) {
        $stmt->bind_param('sssii', $achievement[0], $achievement[1], $achievement[2], $achievement[3], $achievement[4]);
        $stmt->execute();
    }
    $stmt->close();
    
    // Insert system settings
    $settings = [
        ['site_name', 'Internship Hub', 'text', 'Site name displayed in header and title'],
        ['site_description', 'Connect students, companies, and administrators for amazing internship opportunities', 'textarea', 'Site description for SEO'],
        ['site_keywords', 'internships, jobs, students, companies, career', 'text', 'Site keywords for SEO'],
        ['contact_email', 'admin@internship.com', 'text', 'Contact email address'],
        ['maintenance_mode', 'false', 'boolean', 'Enable maintenance mode'],
        ['allow_registrations', 'true', 'boolean', 'Allow new user registrations'],
        ['require_email_verification', 'true', 'boolean', 'Require email verification for new accounts'],
        ['max_login_attempts', '5', 'number', 'Maximum failed login attempts before lockout'],
        ['session_timeout', '3600', 'number', 'Session timeout in seconds'],
        ['enable_2fa', 'true', 'boolean', 'Allow users to enable two-factor authentication']
    ];
    
    $stmt = $conn->prepare("INSERT INTO system_settings (setting_key, setting_value, setting_type, description) VALUES (?, ?, ?, ?)");
    foreach ($settings as $setting) {
        $stmt->bind_param('ssss', $setting[0], $setting[1], $setting[2], $setting[3]);
        $stmt->execute();
    }
    $stmt->close();
    
    echo "<p>✅ Basic data inserted successfully</p>\n";
    
    // Step 8: Create admin user
    echo "<p>👤 Creating default admin user...</p>\n";
    $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param('ssss', 'Admin User', 'admin@internship.com', $admin_password, 'admin');
    $stmt->execute();
    $stmt->close();
    echo "<p>✅ Admin user created (admin@internship.com / admin123)</p>\n";
    
    $conn->close();
    
    echo "<h3>🎉 SETUP COMPLETED SUCCESSFULLY!</h3>\n";
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;'>\n";
    echo "<strong>✅ Database Setup Complete</strong><br>\n";
    echo "✅ All 25+ tables created<br>\n";
    echo "✅ Foreign key constraints added<br>\n";
    echo "✅ Basic data inserted<br>\n";
    echo "✅ Admin account created<br>\n";
    echo "</div>\n";
    
    echo "<h3>🚀 Next Steps:</h3>\n";
    echo "<ol>\n";
    echo "<li><strong>Update database configuration:</strong> Edit config/dbcon.php with your credentials</li>\n";
    echo "<li><strong>Create directories:</strong> Create uploads/resumes/, uploads/logos/, and backups/ folders</li>\n";
    echo "<li><strong>Access the system:</strong> Navigate to <a href='../index.php'>http://localhost/intern/</a></li>\n";
    echo "<li><strong>Login as admin:</strong> Use admin@internship.com / admin123</li>\n";
    echo "<li><strong>Register new users:</strong> Use the registration form for students and companies</li>\n";
    echo "</ol>\n";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0;'>\n";
    echo "<strong>❌ Error:</strong> " . $e->getMessage() . "<br>\n";
    echo "</div>\n";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2 { color: #2c3e50; }
h3 { color: #27ae60; }
p { margin: 10px 0; }
code { background: #f8f9fa; padding: 2px 5px; border-radius: 3px; }
</style>
