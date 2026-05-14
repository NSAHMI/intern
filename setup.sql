-- =====================================================
-- INTERNSHIP MANAGEMENT SYSTEM - DATABASE SETUP
-- Run this SQL in phpMyAdmin or MySQL command line
-- =====================================================

-- Create Database
CREATE DATABASE IF NOT EXISTS internship_db;
USE internship_db;

-- =====================================================
-- USERS TABLE - All users (students, companies, admins)
-- =====================================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('student', 'company', 'admin') NOT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    avatar VARCHAR(255) DEFAULT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- =====================================================
-- STUDENT PROFILES - Additional student information
-- =====================================================
CREATE TABLE IF NOT EXISTS student_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    university VARCHAR(150) DEFAULT NULL,
    course VARCHAR(150) DEFAULT NULL,
    year_of_study INT DEFAULT NULL,
    skills TEXT DEFAULT NULL,
    bio TEXT DEFAULT NULL,
    resume_path VARCHAR(255) DEFAULT NULL,
    linkedin_url VARCHAR(255) DEFAULT NULL,
    portfolio_url VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =====================================================
-- COMPANY PROFILES - Company information
-- =====================================================
CREATE TABLE IF NOT EXISTS company_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    company_name VARCHAR(150) NOT NULL,
    industry VARCHAR(100) DEFAULT NULL,
    description TEXT DEFAULT NULL,
    website VARCHAR(255) DEFAULT NULL,
    location VARCHAR(150) DEFAULT NULL,
    company_size VARCHAR(50) DEFAULT NULL,
    logo_path VARCHAR(255) DEFAULT NULL,
    is_verified BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =====================================================
-- INTERNSHIPS - Job postings by companies
-- =====================================================
CREATE TABLE IF NOT EXISTS internships (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,
    requirements TEXT DEFAULT NULL,
    responsibilities TEXT DEFAULT NULL,
    location VARCHAR(150) DEFAULT NULL,
    type ENUM('remote', 'onsite', 'hybrid') DEFAULT 'onsite',
    duration VARCHAR(50) DEFAULT NULL,
    stipend VARCHAR(100) DEFAULT NULL,
    positions INT DEFAULT 1,
    deadline DATE DEFAULT NULL,
    status ENUM('open', 'closed', 'filled') DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =====================================================
-- APPLICATIONS - Student applications to internships
-- =====================================================
CREATE TABLE IF NOT EXISTS applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    internship_id INT NOT NULL,
    cover_letter TEXT DEFAULT NULL,
    resume_path VARCHAR(255) DEFAULT NULL,
    status ENUM('pending', 'reviewed', 'shortlisted', 'accepted', 'rejected') DEFAULT 'pending',
    company_notes TEXT DEFAULT NULL,
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (internship_id) REFERENCES internships(id) ON DELETE CASCADE,
    UNIQUE KEY unique_application (student_id, internship_id)
);

-- =====================================================
-- MESSAGES - Communication between users
-- =====================================================
CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    subject VARCHAR(200) DEFAULT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =====================================================
-- NOTIFICATIONS - System notifications
-- =====================================================
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,
    type VARCHAR(50) DEFAULT 'info',
    is_read BOOLEAN DEFAULT FALSE,
    link VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =====================================================
-- INSERT DEFAULT ADMIN USER
-- Password: admin123 (change this in production!)
-- =====================================================
INSERT INTO users (name, email, password, role) VALUES
('System Admin', 'admin@internhub.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- =====================================================
-- INSERT SAMPLE DATA (Optional - for testing)
-- =====================================================

-- Sample Companies
INSERT INTO users (name, email, password, role) VALUES
('Tech Corp HR', 'hr@techcorp.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'company'),
('Design Studio', 'jobs@designstudio.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'company');

-- Company Profiles
INSERT INTO company_profiles (user_id, company_name, industry, description, location, is_verified) VALUES
(2, 'Tech Corp', 'Technology', 'Leading software development company specializing in web and mobile applications.', 'San Francisco, CA', TRUE),
(3, 'Design Studio', 'Creative', 'Award-winning design agency creating beautiful digital experiences.', 'New York, NY', TRUE);

-- Sample Internships
INSERT INTO internships (company_id, title, description, requirements, location, type, duration, stipend, positions, deadline, status) VALUES
(2, 'Software Development Intern', 'Join our engineering team to work on cutting-edge web applications. You will learn modern development practices and contribute to real projects.', 'Currently pursuing CS degree, Knowledge of JavaScript, HTML, CSS, Git experience preferred', 'San Francisco, CA', 'hybrid', '3 months', '$1500/month', 3, DATE_ADD(CURDATE(), INTERVAL 30 DAY), 'open'),
(2, 'Data Science Intern', 'Work with our data team to analyze large datasets and build machine learning models.', 'Python, SQL, Statistics knowledge, Familiarity with pandas and scikit-learn', 'Remote', 'remote', '6 months', '$2000/month', 2, DATE_ADD(CURDATE(), INTERVAL 45 DAY), 'open'),
(3, 'UI/UX Design Intern', 'Create beautiful user interfaces and improve user experiences for our clients.', 'Proficiency in Figma or Sketch, Understanding of design principles, Portfolio required', 'New York, NY', 'onsite', '4 months', '$1800/month', 2, DATE_ADD(CURDATE(), INTERVAL 60 DAY), 'open');

-- Sample Students
INSERT INTO users (name, email, password, role) VALUES
('John Student', 'john@university.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student'),
('Jane Learner', 'jane@college.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student');

-- Student Profiles
INSERT INTO student_profiles (user_id, university, course, year_of_study, skills, bio) VALUES
(4, 'State University', 'Computer Science', 3, 'JavaScript, Python, React, Node.js', 'Passionate about building web applications and learning new technologies.'),
(5, 'City College', 'Graphic Design', 2, 'Figma, Adobe XD, Illustrator, Photoshop', 'Creative designer with a focus on user-centered design.');

-- =====================================================
-- INDEXES FOR BETTER PERFORMANCE
-- =====================================================
CREATE INDEX idx_internships_company ON internships(company_id);
CREATE INDEX idx_internships_status ON internships(status);
CREATE INDEX idx_applications_student ON applications(student_id);
CREATE INDEX idx_applications_internship ON applications(internship_id);
CREATE INDEX idx_applications_status ON applications(status);
CREATE INDEX idx_messages_receiver ON messages(receiver_id);
CREATE INDEX idx_notifications_user ON notifications(user_id);

-- =====================================================
-- SETUP COMPLETE!
-- Default login credentials:
-- Admin: admin@internhub.com / password
-- Company: hr@techcorp.com / password
-- Student: john@university.edu / password
-- =====================================================
