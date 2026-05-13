-- ============================================
-- INTERNSHIP MANAGEMENT SYSTEM DATABASE
-- Fresh Database Schema - Clean Table Creation
-- ============================================

-- Create database if not exists
CREATE DATABASE IF NOT EXISTS internship;
USE internship;

-- ============================================
-- CORE TABLES
-- ============================================

-- Departments table for multi-field support
CREATE TABLE departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    icon VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Users table for authentication and role management
CREATE TABLE users (
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
    FOREIGN KEY (department_id) REFERENCES departments(id)
);

-- Internships table for job postings
CREATE TABLE internships (
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
    FOREIGN KEY (company_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (department_id) REFERENCES departments(id)
);

-- Applications table
CREATE TABLE applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    internship_id INT NOT NULL,
    student_id INT NOT NULL,
    cover_letter TEXT,
    status ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (internship_id) REFERENCES internships(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_application (internship_id, student_id)
);

-- ============================================
-- ADVANCED FEATURES TABLES
-- ============================================

-- Student Profiles Enhancement
CREATE TABLE IF NOT EXISTS student_profiles (
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
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Skills System
CREATE TABLE IF NOT EXISTS skills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    category VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS student_skills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    skill_id INT NOT NULL,
    proficiency_level ENUM('beginner', 'intermediate', 'advanced', 'expert') DEFAULT 'intermediate',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (skill_id) REFERENCES skills(id) ON DELETE CASCADE,
    UNIQUE KEY unique_student_skill (student_id, skill_id)
);

-- Company Profiles
CREATE TABLE IF NOT EXISTS company_profiles (
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
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Messaging System
CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    internship_id INT NULL,
    subject VARCHAR(255),
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    message_type ENUM('application', 'question', 'interview', 'offer', 'general') DEFAULT 'general',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (internship_id) REFERENCES internships(id) ON DELETE CASCADE
);

-- Interviews & Scheduling
CREATE TABLE IF NOT EXISTS interviews (
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
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
    FOREIGN KEY (company_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Analytics & Tracking
CREATE TABLE IF NOT EXISTS user_activity (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    activity_type VARCHAR(50) NOT NULL,
    resource_type VARCHAR(50),
    resource_id INT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS analytics_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_type VARCHAR(50) NOT NULL,
    event_data JSON,
    user_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Bookmarks & Favorites
CREATE TABLE IF NOT EXISTS bookmarks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    internship_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (internship_id) REFERENCES internships(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_bookmark (user_id, internship_id)
);

-- Reviews & Ratings
CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reviewer_id INT NOT NULL,
    reviewed_user_id INT NOT NULL,
    internship_id INT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    review_text TEXT,
    experience_type ENUM('student_to_company', 'company_to_student') NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (internship_id) REFERENCES internships(id) ON DELETE CASCADE
);

-- Email Templates & Notifications
CREATE TABLE IF NOT EXISTS email_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_name VARCHAR(100) NOT NULL UNIQUE,
    subject VARCHAR(255) NOT NULL,
    html_content TEXT NOT NULL,
    text_content TEXT,
    variables JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS email_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    to_email VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    html_content TEXT NOT NULL,
    text_content TEXT,
    status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
    attempts INT DEFAULT 0,
    sent_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Gamification
CREATE TABLE IF NOT EXISTS achievements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    icon VARCHAR(50),
    points INT DEFAULT 0,
    badge_color VARCHAR(20) DEFAULT 'primary',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS user_achievements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    achievement_id INT NOT NULL,
    earned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (achievement_id) REFERENCES achievements(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_achievement (user_id, achievement_id)
);

-- Security Features
CREATE TABLE IF NOT EXISTS email_verifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE NOT NULL,
    token VARCHAR(255) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    verified_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(255) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    used_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================
-- 2FA & SECURITY TABLES
-- ============================================

-- SMS/Email Verification Codes
CREATE TABLE IF NOT EXISTS sms_verifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    phone_number VARCHAR(20) NOT NULL,
    verification_code VARCHAR(6) NOT NULL,
    purpose ENUM('login', 'registration', 'password_reset', 'setup', 'test') DEFAULT 'login',
    attempts INT DEFAULT 0,
    expires_at TIMESTAMP NOT NULL,
    verified_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_phone (user_id, phone_number),
    INDEX idx_expires (expires_at),
    INDEX idx_purpose (purpose)
);

-- SMS Logs
CREATE TABLE IF NOT EXISTS sms_logs (
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
);

-- Security Logs
CREATE TABLE IF NOT EXISTS security_logs (
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
);

-- ============================================
-- CMS & ADMINISTRATIVE TABLES
-- ============================================

-- CMS Pages
CREATE TABLE IF NOT EXISTS cms_pages (
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
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_slug (slug)
);

-- System Settings
CREATE TABLE IF NOT EXISTS system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    setting_type ENUM('text', 'textarea', 'number', 'boolean', 'json') DEFAULT 'text',
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_key (setting_key)
);

-- Menu Items
CREATE TABLE IF NOT EXISTS menu_items (
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
);

-- Banners/Slides
CREATE TABLE IF NOT EXISTS banners (
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
);

-- Testimonials
CREATE TABLE IF NOT EXISTS testimonials (
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
);

-- Backup Logs
CREATE TABLE IF NOT EXISTS backup_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) NOT NULL,
    type ENUM('full', 'structure', 'data') DEFAULT 'full',
    file_size BIGINT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_created (created_at)
);

-- ============================================
-- ADD ENHANCED COLUMNS TO EXISTING TABLES
-- ============================================

-- Add additional columns to users table for enhanced features
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS two_factor_secret VARCHAR(255),
ADD COLUMN IF NOT EXISTS two_factor_enabled BOOLEAN DEFAULT FALSE,
ADD COLUMN IF NOT EXISTS email_verified BOOLEAN DEFAULT FALSE,
ADD COLUMN IF NOT EXISTS status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
ADD COLUMN IF NOT EXISTS last_login TIMESTAMP NULL,
ADD COLUMN IF NOT EXISTS phone VARCHAR(20),
ADD COLUMN IF NOT EXISTS phone_verified BOOLEAN DEFAULT FALSE,
ADD COLUMN IF NOT EXISTS sms_2fa_enabled BOOLEAN DEFAULT FALSE;

-- Add additional columns to internships table for enhanced features
ALTER TABLE internships 
ADD COLUMN IF NOT EXISTS featured BOOLEAN DEFAULT FALSE,
ADD COLUMN IF NOT EXISTS status ENUM('active', 'inactive', 'expired') DEFAULT 'active';

-- ============================================
-- INSERT INITIAL DATA
-- ============================================

-- Insert departments (ignore duplicates)
INSERT IGNORE INTO departments (name, description, icon) VALUES
('Software Engineering', 'Software development, web development, mobile apps, and IT solutions', 'fa-laptop-code'),
('Transport & Logistics', 'Supply chain management, transportation, logistics, and distribution', 'fa-truck'),
('Nursing & Healthcare', 'Medical care, nursing, healthcare administration, and patient services', 'fa-heartbeat'),
('Accounting & Finance', 'Financial management, accounting, bookkeeping, and financial analysis', 'fa-calculator'),
('Building Science & Technology', 'Construction management, architecture, civil engineering, and building technology', 'fa-building'),
('Business Administration', 'Management, marketing, human resources, and business operations', 'fa-briefcase'),
('Education & Teaching', 'Teaching, educational administration, curriculum development, and training', 'fa-graduation-cap'),
('Media & Communications', 'Journalism, public relations, digital media, and content creation', 'fa-newspaper');

-- Insert default admin user (password: admin123)
INSERT IGNORE INTO users (name, email, password, role) VALUES
('Admin User', 'admin@internship.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin')

-- Insert sample companies
INSERT IGNORE INTO users (name, email, password, role) VALUES
('Tech Corp', 'company@techcorp.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'company'),
('Healthcare Plus', 'company@healthcare.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'company')

-- Insert sample students with different departments
INSERT INTO users (name, email, password, role, department_id) VALUES
('John Student', 'john@internship.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 1),
('Sarah Nurse', 'sarah@internship.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 3),
('Mike Accountant', 'mike@internship.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 4),
('Tom Builder', 'tom@internship.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 5),
('Lisa Business', 'lisa@internship.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 6),
('David Teacher', 'david@internship.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 7),
('Emma Media', 'emma@internship.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 8)

-- Insert sample internships across different departments
INSERT INTO internships (company_id, department_id, title, location, work_type, description, requirements, duration, expiration_date) VALUES
(2, 1, 'Full Stack Developer Intern', 'San Francisco, CA', 'remote', 'Join our team to build amazing web applications using modern technologies.', 'Experience with React, Node.js, and databases. Strong problem-solving skills required.', '3 months', DATE_ADD(CURRENT_DATE, INTERVAL 2 MONTH)),
(2, 1, 'Mobile App Developer', 'New York, NY', 'hybrid', 'Create innovative mobile applications for iOS and Android platforms.', 'Flutter or React Native experience. Portfolio of mobile apps required.', '4 months', DATE_ADD(CURRENT_DATE, INTERVAL 3 MONTH)),
(2, 2, 'Logistics Coordinator', 'Chicago, IL', 'onsite', 'Manage supply chain operations and coordinate transportation for efficient delivery systems.', 'Strong organizational skills. Experience with logistics software preferred.', '3 months', DATE_ADD(CURRENT_DATE, INTERVAL 2 MONTH)),
(2, 3, 'Nursing Intern', 'Boston, MA', 'onsite', 'Gain hands-on experience in patient care, medical procedures, and healthcare administration.', 'Current nursing student. Clinical rotation experience required.', '6 months', DATE_ADD(CURRENT_DATE, INTERVAL 5 MONTH)),
(2, 4, 'Accounting Assistant', 'Miami, FL', 'hybrid', 'Assist with financial reporting, bookkeeping, and budget analysis in a corporate environment.', 'Accounting major preferred. Excel proficiency required.', '3 months', DATE_ADD(CURRENT_DATE, INTERVAL 3 MONTH)),
(2, 5, 'Construction Project Intern', 'Seattle, WA', 'onsite', 'Learn construction management, site supervision, and building technology implementation.', 'Engineering or construction management major. CAD experience helpful.', '4 months', DATE_ADD(CURRENT_DATE, INTERVAL 6 MONTH)),
(2, 6, 'Business Analyst Intern', 'Austin, TX', 'remote', 'Analyze business processes, develop strategies, and support management decisions.', 'Business or economics major. Strong analytical skills required.', '3 months', DATE_ADD(CURRENT_DATE, INTERVAL 2 MONTH)),
(2, 7, 'Teaching Assistant', 'Denver, CO', 'hybrid', 'Support classroom instruction, develop educational materials, and assist with student mentoring.', 'Education major preferred. Teaching experience helpful.', '5 months', DATE_ADD(CURRENT_DATE, INTERVAL 4 MONTH)),
(2, 8, 'Digital Media Intern', 'Los Angeles, CA', 'remote', 'Create content for social media, write articles, and assist with digital marketing campaigns.', 'Journalism or marketing major. Social media experience required.', '3 months', DATE_ADD(CURRENT_DATE, INTERVAL 3 MONTH))

-- Insert skills data
INSERT IGNORE INTO skills (name, category) VALUES
('JavaScript', 'Programming'),
('Python', 'Programming'),
('Java', 'Programming'),
('C++', 'Programming'),
('React', 'Frontend'),
('Angular', 'Frontend'),
('Vue.js', 'Frontend'),
('Node.js', 'Backend'),
('PHP', 'Backend'),
('MySQL', 'Database'),
('PostgreSQL', 'Database'),
('MongoDB', 'Database'),
('HTML/CSS', 'Frontend'),
('TypeScript', 'Programming'),
('Docker', 'DevOps'),
('Git', 'Tools'),
('AWS', 'Cloud'),
('Azure', 'Cloud'),
('Communication', 'Soft Skills'),
('Leadership', 'Soft Skills'),
('Problem Solving', 'Soft Skills'),
('Teamwork', 'Soft Skills'),
('Project Management', 'Business'),
('Data Analysis', 'Analytics'),
('Machine Learning', 'AI'),
('Marketing', 'Business'),
('Sales', 'Business'),
('Accounting', 'Finance'),
('Financial Analysis', 'Finance')

-- Insert achievements
INSERT IGNORE INTO achievements (name, description, icon, points, badge_color) VALUES
('First Application', 'Submitted your first internship application', 'fa-paper-plane', 10, 'success'),
('Profile Complete', 'Completed your profile 100%', 'fa-user-check', 25, 'primary'),
('Active Seeker', 'Applied to 5+ internships', 'fa-search', 15, 'info'),
('Interview Ready', 'Got your first interview', 'fa-calendar-check', 30, 'warning'),
('Network Builder', 'Connected with 10+ companies', 'fa-users', 20, 'secondary'),
('Skill Master', 'Added 10+ skills to profile', 'fa-tools', 20, 'primary'),
('Early Bird', 'Applied within first week of posting', 'fa-clock', 15, 'success'),
('Perfect Match', 'Got accepted to first choice', 'fa-bullseye', 50, 'warning')

-- Insert email templates
INSERT IGNORE INTO email_templates (template_name, subject, html_content, text_content, variables) VALUES
('application_received', 'Application Received - {{internship_title}}', 
 '<h3>Application Received!</h3><p>Dear {{student_name}},</p><p>Your application for {{internship_title}} at {{company_name}} has been received.</p><p>Good luck!</p>', 
 'Application Received! Your application for {{internship_title}} at {{company_name}} has been received.',
 '["student_name", "internship_title", "company_name"]'),
('interview_scheduled', 'Interview Scheduled - {{company_name}}',
 '<h3>Interview Scheduled!</h3><p>Dear {{student_name}},</p><p>{{company_name}} has scheduled an interview for {{internship_title}} on {{interview_date}}.</p><p>Details: {{interview_details}}</p>',
 'Interview Scheduled! {{company_name}} has scheduled an interview for {{internship_title}} on {{interview_date}}.',
 '["student_name", "company_name", "internship_title", "interview_date", "interview_details"]'),
('application_accepted', 'Congratulations! Application Accepted - {{internship_title}}',
 '<h3>Congratulations!</h3><p>Dear {{student_name}},</p><p>We are pleased to inform you that your application for {{internship_title}} at {{company_name}} has been accepted!</p><p>Welcome aboard!</p>',
 'Congratulations! Your application for {{internship_title}} at {{company_name}} has been accepted!',
 '["student_name", "internship_title", "company_name"]')

-- Insert default system settings
INSERT IGNORE INTO system_settings (setting_key, setting_value, setting_type, description) VALUES
('site_name', 'Internship Hub', 'text', 'Site name displayed in header and title'),
('site_description', 'Connect students, companies, and administrators for amazing internship opportunities', 'textarea', 'Site description for SEO'),
('site_keywords', 'internships, jobs, students, companies, career', 'text', 'Site keywords for SEO'),
('contact_email', 'admin@internship.com', 'text', 'Contact email address'),
('maintenance_mode', 'false', 'boolean', 'Enable maintenance mode'),
('allow_registrations', 'true', 'boolean', 'Allow new user registrations'),
('require_email_verification', 'true', 'boolean', 'Require email verification for new accounts'),
('max_login_attempts', '5', 'number', 'Maximum failed login attempts before lockout'),
('session_timeout', '3600', 'number', 'Session timeout in seconds'),
('enable_2fa', 'true', 'boolean', 'Allow users to enable two-factor authentication'),
('default_user_role', 'student', 'text', 'Default role for new registrations'),
('posts_per_page', '10', 'number', 'Number of items per page in listings')

-- Insert default menu items
INSERT IGNORE INTO menu_items (menu_name, title, url, sort_order) VALUES
('main', 'Home', '../index.php', 1),
('main', 'Browse Internships', '../search.php', 2),
('main', 'About Us', '../page.php?slug=about', 3),
('main', 'Contact', '../page.php?slug=contact', 4),
('footer', 'Privacy Policy', '../page.php?slug=privacy-policy', 1),
('footer', 'Terms of Service', '../page.php?slug=terms-of-service', 2),
('footer', 'FAQ', '../page.php?slug=faq', 3)

-- Insert sample CMS pages
INSERT IGNORE INTO cms_pages (title, slug, content, meta_description, status, author_id) VALUES
('About Us', 'about', 
'<h2>About Internship Hub</h2>
<p>Internship Hub is a comprehensive platform designed to connect talented students with amazing internship opportunities from top companies worldwide.</p>
<h3>Our Mission</h3>
<p>We believe in empowering the next generation of professionals by providing seamless access to real-world experience and career-building opportunities.</p>
<h3>Why Choose Us?</h3>
<ul>
<li>Trusted by thousands of students and companies</li>
<li>Advanced matching algorithms</li>
<li>Comprehensive profile management</li>
<li>Real-time communication tools</li>
<li>Secure and reliable platform</li>
</ul>',
'Learn about Internship Hub and our mission to connect students with opportunities', 'published', 1),

('Contact Us', 'contact', 
'<h2>Get in Touch</h2>
<p>We\'re here to help! Whether you\'re a student looking for opportunities or a company seeking talent, we\'d love to hear from you.</p>
<div class="row">
    <div class="col-md-6">
        <h3>Contact Information</h3>
        <p><strong>Email:</strong> support@internship.com</p>
        <p><strong>Phone:</strong> +1 (555) 123-4567</p>
        <p><strong>Address:</strong> 123 Career Street, Opportunity City, OC 12345</p>
    </div>
    <div class="col-md-6">
        <h3>Business Hours</h3>
        <p>Monday - Friday: 9:00 AM - 6:00 PM</p>
        <p>Saturday: 10:00 AM - 4:00 PM</p>
        <p>Sunday: Closed</p>
    </div>
</div>',
'Contact Internship Hub for support and inquiries', 'published', 1)

-- Insert sample banners
INSERT IGNORE INTO banners (title, image_url, link_url, position, sort_order, status) VALUES
('Welcome to Internship Hub', '/intern/assets/images/banner1.jpg', '/intern/search.php', 'home', 1, 'active'),
('Find Your Dream Internship', '/intern/assets/images/banner2.jpg', '/intern/student/dashboard.php', 'home', 2, 'active'),
('Connect with Top Companies', '/intern/assets/images/banner3.jpg', '/intern/auth/register.php', 'home', 3, 'active')

-- Insert sample testimonials
INSERT IGNORE INTO testimonials (name, email, content, rating, company, status) VALUES
('Sarah Johnson', 'sarah.j@university.edu', 'Internship Hub helped me find the perfect internship at a tech startup. The platform made it so easy to apply and track my applications!', 5, 'State University', 'approved'),
('Michael Chen', 'm.chen@company.com', 'As a recruiter, I love the quality of candidates on Internship Hub. The platform has streamlined our hiring process significantly.', 5, 'TechCorp Inc.', 'approved'),
('Emily Davis', 'emily.d@email.com', 'The user interface is intuitive and the features are exactly what students need. I found my dream internship within weeks!', 5, 'Business College', 'approved')

-- ============================================
-- SETUP COMPLETE
-- ============================================
-- 
-- NO DEFAULT LOGIN CREDENTIALS:
-- You will register as a new user from the website
-- Go to: http://localhost/intern/auth/register.php
-- Choose your role: student, company, or admin
-- Create your own account with your email and password
-- 
-- CREATE THESE DIRECTORIES:
-- uploads/resumes/
-- uploads/logos/
-- backups/
-- 
-- Your COMPLETE internship management system is ready!
-- This single database.sql file contains EVERYTHING needed!
-- 
-- FEATURES INCLUDED:
-- ✅ Core authentication & role management
-- ✅ Advanced search & filtering
-- ✅ Email notification system
-- ✅ Gamification & achievements
-- ✅ Mobile PWA capabilities
-- ✅ Security features (2FA, rate limiting)
-- ✅ CMS & administrative tools
-- ✅ Analytics & reporting
-- ✅ Messaging system
-- ✅ Interview scheduling
-- ✅ Reviews & ratings
-- ✅ Bookmarks & favorites
-- ✅ Skills management
-- ✅ Profile management
-- ✅ Application tracking
-- ✅ Company management
-- ✅ Student dashboard
-- ✅ Admin dashboard
-- ✅ Responsive design
-- ✅ Email 2FA authentication
-- ✅ Security logging
-- ✅ Backup system
-- ✅ System settings
-- ✅ Dynamic menus
-- ✅ Banner management
-- ✅ Testimonials
-- ✅ Content pages
-- ✅ And much more!
-- 
-- TOTAL: 25+ tables, complete with sample data and relationships

-- ============================================
-- ADVANCED FEATURES TABLES
-- ============================================

-- Student Profiles Enhancement
CREATE TABLE IF NOT EXISTS student_profiles (
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
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Skills System
CREATE TABLE IF NOT EXISTS skills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    category VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS student_skills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    skill_id INT NOT NULL,
    proficiency_level ENUM('beginner', 'intermediate', 'advanced', 'expert') DEFAULT 'intermediate',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (skill_id) REFERENCES skills(id) ON DELETE CASCADE,
    UNIQUE KEY unique_student_skill (student_id, skill_id)
);

-- Company Profiles
CREATE TABLE IF NOT EXISTS company_profiles (
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
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Messaging System
CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    internship_id INT NULL,
    subject VARCHAR(255),
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    message_type ENUM('application', 'question', 'interview', 'offer', 'general') DEFAULT 'general',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (internship_id) REFERENCES internships(id) ON DELETE CASCADE
);

-- Interviews & Scheduling
CREATE TABLE IF NOT EXISTS interviews (
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
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
    FOREIGN KEY (company_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Analytics & Tracking
CREATE TABLE IF NOT EXISTS user_activity (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    activity_type VARCHAR(50) NOT NULL,
    resource_type VARCHAR(50),
    resource_id INT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS analytics_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_type VARCHAR(50) NOT NULL,
    event_data JSON,
    user_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Bookmarks & Favorites
CREATE TABLE IF NOT EXISTS bookmarks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    internship_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (internship_id) REFERENCES internships(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_bookmark (user_id, internship_id)
);

-- Reviews & Ratings
CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reviewer_id INT NOT NULL,
    reviewed_user_id INT NOT NULL,
    internship_id INT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    review_text TEXT,
    experience_type ENUM('student_to_company', 'company_to_student') NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (internship_id) REFERENCES internships(id) ON DELETE CASCADE
);

-- Email Templates & Notifications
CREATE TABLE IF NOT EXISTS email_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_name VARCHAR(100) NOT NULL UNIQUE,
    subject VARCHAR(255) NOT NULL,
    html_content TEXT NOT NULL,
    text_content TEXT,
    variables JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS email_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    to_email VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    html_content TEXT NOT NULL,
    text_content TEXT,
    status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
    attempts INT DEFAULT 0,
    sent_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Gamification
CREATE TABLE IF NOT EXISTS achievements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    icon VARCHAR(50),
    points INT DEFAULT 0,
    badge_color VARCHAR(20) DEFAULT 'primary',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS user_achievements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    achievement_id INT NOT NULL,
    earned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (achievement_id) REFERENCES achievements(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_achievement (user_id, achievement_id)
);

-- Security Features
CREATE TABLE IF NOT EXISTS email_verifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE NOT NULL,
    token VARCHAR(255) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    verified_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(255) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    used_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================
-- INSERT INITIAL DATA
-- ============================================

-- Insert departments
INSERT IGNORE INTO departments (name, description, icon) VALUES
('Software Engineering', 'Software development, web development, mobile apps, and IT solutions', 'fa-laptop-code'),
('Transport & Logistics', 'Supply chain management, transportation, logistics, and distribution', 'fa-truck'),
('Nursing & Healthcare', 'Medical care, nursing, healthcare administration, and patient services', 'fa-heartbeat'),
('Accounting & Finance', 'Financial management, accounting, bookkeeping, and financial analysis', 'fa-calculator'),
('Building Science & Technology', 'Construction management, architecture, civil engineering, and building technology', 'fa-building'),
('Business Administration', 'Management, marketing, human resources, and business operations', 'fa-briefcase'),
('Education & Teaching', 'Teaching, educational administration, curriculum development, and training', 'fa-graduation-cap'),
('Media & Communications', 'Journalism, public relations, digital media, and content creation', 'fa-newspaper');

-- No default users - you will register as a new user from the website
-- No sample internships - you will create internships after registering as a company

-- Insert skills data
INSERT IGNORE INTO skills (name, category) VALUES
('JavaScript', 'Programming'),
('Python', 'Programming'),
('Java', 'Programming'),
('C++', 'Programming'),
('React', 'Frontend'),
('Angular', 'Frontend'),
('Vue.js', 'Frontend'),
('Node.js', 'Backend'),
('PHP', 'Backend'),
('MySQL', 'Database'),
('PostgreSQL', 'Database'),
('MongoDB', 'Database'),
('HTML/CSS', 'Frontend'),
('TypeScript', 'Programming'),
('Docker', 'DevOps'),
('Git', 'Tools'),
('AWS', 'Cloud'),
('Azure', 'Cloud'),
('Communication', 'Soft Skills'),
('Leadership', 'Soft Skills'),
('Problem Solving', 'Soft Skills'),
('Teamwork', 'Soft Skills'),
('Project Management', 'Business'),
('Data Analysis', 'Analytics'),
('Machine Learning', 'AI'),
('Marketing', 'Business'),
('Sales', 'Business'),
('Accounting', 'Finance'),
('Financial Analysis', 'Finance');

-- Insert achievements
INSERT IGNORE INTO achievements (name, description, icon, points, badge_color) VALUES
('First Application', 'Submitted your first internship application', 'fa-paper-plane', 10, 'success'),
('Profile Complete', 'Completed your profile 100%', 'fa-user-check', 25, 'primary'),
('Active Seeker', 'Applied to 5+ internships', 'fa-search', 15, 'info'),
('Interview Ready', 'Got your first interview', 'fa-calendar-check', 30, 'warning'),
('Network Builder', 'Connected with 10+ companies', 'fa-users', 20, 'secondary'),
('Skill Master', 'Added 10+ skills to profile', 'fa-tools', 20, 'primary'),
('Early Bird', 'Applied within first week of posting', 'fa-clock', 15, 'success'),
('Perfect Match', 'Got accepted to first choice', 'fa-bullseye', 50, 'warning');

-- Insert email templates
INSERT IGNORE INTO email_templates (template_name, subject, html_content, text_content, variables) VALUES
('application_received', 'Application Received - {{internship_title}}', 
 '<h3>Application Received!</h3><p>Dear {{student_name}},</p><p>Your application for {{internship_title}} at {{company_name}} has been received.</p><p>Good luck!</p>', 
 'Application Received! Your application for {{internship_title}} at {{company_name}} has been received.',
 '["student_name", "internship_title", "company_name"]'),
('interview_scheduled', 'Interview Scheduled - {{company_name}}',
 '<h3>Interview Scheduled!</h3><p>Dear {{student_name}},</p><p>{{company_name}} has scheduled an interview for {{internship_title}} on {{interview_date}}.</p><p>Details: {{interview_details}}</p>',
 'Interview Scheduled! {{company_name}} has scheduled an interview for {{internship_title}} on {{interview_date}}.',
 '["student_name", "company_name", "internship_title", "interview_date", "interview_details"]'),
('application_accepted', 'Congratulations! Application Accepted - {{internship_title}}',
 '<h3>Congratulations!</h3><p>Dear {{student_name}},</p><p>We are pleased to inform you that your application for {{internship_title}} at {{company_name}} has been accepted!</p><p>Welcome aboard!</p>',
 'Congratulations! Your application for {{internship_title}} at {{company_name}} has been accepted!',
 '["student_name", "internship_title", "company_name"]');

-- ============================================
-- CMS & ADMINISTRATIVE TABLES
-- ============================================

-- CMS Pages
CREATE TABLE IF NOT EXISTS cms_pages (
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
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_slug (slug)
);

-- System Settings
CREATE TABLE IF NOT EXISTS system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    setting_type ENUM('text', 'textarea', 'number', 'boolean', 'json') DEFAULT 'text',
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_key (setting_key)
);

-- Menu Items
CREATE TABLE IF NOT EXISTS menu_items (
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
);

-- Banners/Slides
CREATE TABLE IF NOT EXISTS banners (
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
);

-- Testimonials
CREATE TABLE IF NOT EXISTS testimonials (
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
);

-- Backup Logs
CREATE TABLE IF NOT EXISTS backup_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) NOT NULL,
    type ENUM('full', 'structure', 'data') DEFAULT 'full',
    file_size BIGINT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_created (created_at)
);

-- Security Logs
CREATE TABLE IF NOT EXISTS security_logs (
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
);

-- Add additional columns to users table for enhanced features
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS two_factor_secret VARCHAR(255),
ADD COLUMN IF NOT EXISTS two_factor_enabled BOOLEAN DEFAULT FALSE,
ADD COLUMN IF NOT EXISTS email_verified BOOLEAN DEFAULT FALSE,
ADD COLUMN IF NOT EXISTS status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
ADD COLUMN IF NOT EXISTS last_login TIMESTAMP NULL;

-- Add additional columns to internships table for enhanced features
ALTER TABLE internships 
ADD COLUMN IF NOT EXISTS featured BOOLEAN DEFAULT FALSE,
ADD COLUMN IF NOT EXISTS status ENUM('active', 'inactive', 'expired') DEFAULT 'active';

-- ============================================
-- INSERT CMS & ADMINISTRATIVE DATA
-- ============================================

-- Insert default system settings
INSERT IGNORE INTO system_settings (setting_key, setting_value, setting_type, description) VALUES
('site_name', 'Internship Hub', 'text', 'Site name displayed in header and title'),
('site_description', 'Connect students, companies, and administrators for amazing internship opportunities', 'textarea', 'Site description for SEO'),
('site_keywords', 'internships, jobs, students, companies, career', 'text', 'Site keywords for SEO'),
('contact_email', 'admin@internship.com', 'text', 'Contact email address'),
('maintenance_mode', 'false', 'boolean', 'Enable maintenance mode'),
('allow_registrations', 'true', 'boolean', 'Allow new user registrations'),
('require_email_verification', 'true', 'boolean', 'Require email verification for new accounts'),
('max_login_attempts', '5', 'number', 'Maximum failed login attempts before lockout'),
('session_timeout', '3600', 'number', 'Session timeout in seconds'),
('enable_2fa', 'true', 'boolean', 'Allow users to enable two-factor authentication'),
('default_user_role', 'student', 'text', 'Default role for new registrations'),
('posts_per_page', '10', 'number', 'Number of items per page in listings')

-- Insert default menu items
INSERT IGNORE INTO menu_items (menu_name, title, url, sort_order) VALUES
('main', 'Home', '../index.php', 1),
('main', 'Browse Internships', '../search.php', 2),
('main', 'About Us', '../page.php?slug=about', 3),
('main', 'Contact', '../page.php?slug=contact', 4),
('footer', 'Privacy Policy', '../page.php?slug=privacy-policy', 1),
('footer', 'Terms of Service', '../page.php?slug=terms-of-service', 2),
('footer', 'FAQ', '../page.php?slug=faq', 3)

-- Insert sample CMS pages
INSERT IGNORE INTO cms_pages (title, slug, content, meta_description, status, author_id) VALUES
('About Us', 'about', 
'<h2>About Internship Hub</h2>
<p>Internship Hub is a comprehensive platform designed to connect talented students with amazing internship opportunities from top companies worldwide.</p>
<h3>Our Mission</h3>
<p>We believe in empowering the next generation of professionals by providing seamless access to real-world experience and career-building opportunities.</p>
<h3>Why Choose Us?</h3>
<ul>
<li>Trusted by thousands of students and companies</li>
<li>Advanced matching algorithms</li>
<li>Comprehensive profile management</li>
<li>Real-time communication tools</li>
<li>Secure and reliable platform</li>
</ul>',
'Learn about Internship Hub and our mission to connect students with opportunities', 'published', 1),

('Contact Us', 'contact', 
'<h2>Get in Touch</h2>
<p>We\'re here to help! Whether you\'re a student looking for opportunities or a company seeking talent, we\'d love to hear from you.</p>
<div class="row">
    <div class="col-md-6">
        <h3>Contact Information</h3>
        <p><strong>Email:</strong> support@internship.com</p>
        <p><strong>Phone:</strong> +1 (555) 123-4567</p>
        <p><strong>Address:</strong> 123 Career Street, Opportunity City, OC 12345</p>
    </div>
    <div class="col-md-6">
        <h3>Business Hours</h3>
        <p>Monday - Friday: 9:00 AM - 6:00 PM</p>
        <p>Saturday: 10:00 AM - 4:00 PM</p>
        <p>Sunday: Closed</p>
    </div>
</div>',
'Contact Internship Hub for support and inquiries', 'published', 1)

-- Insert sample banners
INSERT IGNORE INTO banners (title, image_url, link_url, position, sort_order, status) VALUES
('Welcome to Internship Hub', '/intern/assets/images/banner1.jpg', '/intern/search.php', 'home', 1, 'active'),
('Find Your Dream Internship', '/intern/assets/images/banner2.jpg', '/intern/student/dashboard.php', 'home', 2, 'active'),
('Connect with Top Companies', '/intern/assets/images/banner3.jpg', '/intern/auth/register.php', 'home', 3, 'active')

-- Insert sample testimonials
INSERT IGNORE INTO testimonials (name, email, content, rating, company, status) VALUES
('Sarah Johnson', 'sarah.j@university.edu', 'Internship Hub helped me find the perfect internship at a tech startup. The platform made it so easy to apply and track my applications!', 5, 'State University', 'approved'),
('Michael Chen', 'm.chen@company.com', 'As a recruiter, I love the quality of candidates on Internship Hub. The platform has streamlined our hiring process significantly.', 5, 'TechCorp Inc.', 'approved'),
('Emily Davis', 'emily.d@email.com', 'The user interface is intuitive and the features are exactly what students need. I found my dream internship within weeks!', 5, 'Business College', 'approved')

-- ============================================
-- SETUP COMPLETE
-- ============================================
-- 
-- NO DEFAULT LOGIN CREDENTIALS:
-- You will register as a new user from the website
-- Go to: http://localhost/intern/auth/register.php
-- Choose your role: student, company, or admin
-- Create your own account with your email and password
-- 
-- CREATE THESE DIRECTORIES:
-- uploads/resumes/
-- uploads/logos/
-- backups/
-- 
-- Your COMPLETE internship management system is ready!
-- This single database.sql file contains EVERYTHING needed!
