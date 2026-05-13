<?php
// Email Configuration and Notification System
class EmailNotification {
    private $conn;
    private $from_email;
    private $from_name;
    private $smtp_host;
    private $smtp_port;
    private $smtp_username;
    private $smtp_password;
    
    public function __construct($database) {
        $this->conn = $database;
        
        // Email configuration (update these with your actual email settings)
        $this->from_email = 'noreply@internship.com';
        $this->from_name = 'Internship Management System';
        $this->smtp_host = 'smtp.gmail.com'; // or your SMTP server
        $this->smtp_port = 587;
        $this->smtp_username = 'your-email@gmail.com'; // your email
        $this->smtp_password = 'your-app-password'; // your email password
    }
    
    /**
     * Send email using PHPMailer or mail()
     */
    public function sendEmail($to, $subject, $html_content, $text_content = '') {
        // Check if email_queue table exists
        $this->createEmailTablesIfNotExists();
        
        // Queue email for sending
        $stmt = $this->conn->prepare('
            INSERT INTO email_queue (to_email, subject, html_content, text_content, status) 
            VALUES (?, ?, ?, ?, "pending")
        ');
        if ($stmt === false) {
            // Fallback to direct mail if table creation fails
            return $this->sendDirectEmail($to, $subject, $html_content, $text_content);
        }
        
        $stmt->bind_param('ssss', $to, $subject, $html_content, $text_content);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }
    
    /**
     * Create email tables if they don't exist
     */
    private function createEmailTablesIfNotExists() {
        $tables = [
            'CREATE TABLE IF NOT EXISTS email_queue (
                id INT AUTO_INCREMENT PRIMARY KEY,
                to_email VARCHAR(255) NOT NULL,
                subject VARCHAR(500) NOT NULL,
                html_content LONGTEXT,
                text_content TEXT,
                status ENUM("pending", "sent", "failed") DEFAULT "pending",
                attempts INT DEFAULT 0,
                error_message TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                sent_at TIMESTAMP NULL
            )',
            'CREATE TABLE IF NOT EXISTS email_templates (
                id INT AUTO_INCREMENT PRIMARY KEY,
                template_name VARCHAR(100) NOT NULL UNIQUE,
                subject VARCHAR(500) NOT NULL,
                html_content LONGTEXT,
                text_content TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )'
        ];
        
        foreach ($tables as $sql) {
            $this->conn->query($sql);
        }
        
        // Insert default templates if empty
        $this->insertDefaultTemplates();
    }
    
    /**
     * Insert default email templates
     */
    private function insertDefaultTemplates() {
        $check = $this->conn->query('SELECT COUNT(*) as count FROM email_templates');
        $result = $check->fetch_assoc();
        
        if ($result['count'] > 0) return;
        
        $templates = [
            'application_received' => [
                'subject' => 'Application Received - {{internship_title}}',
                'html_content' => '<h3>Application Received! 🎉</h3><p>Dear {{student_name}},</p><p>Your application has been submitted successfully!</p>',
                'text_content' => 'Application Received!\n\nDear {{student_name}},\n\nYour application has been submitted successfully!'
            ],
            'welcome_email' => [
                'subject' => 'Welcome to Internship Hub! 🎓',
                'html_content' => '<h3>Welcome to Internship Hub! 🎓</h3><p>Dear {{student_name}},</p><p>Thank you for joining us!</p>',
                'text_content' => 'Welcome to Internship Hub!\n\nDear {{student_name}},\n\nThank you for joining us!'
            ]
        ];
        
        foreach ($templates as $name => $template) {
            $stmt = $this->conn->prepare('
                INSERT INTO email_templates (template_name, subject, html_content, text_content) 
                VALUES (?, ?, ?, ?)
            ');
            $stmt->bind_param('ssss', $name, $template['subject'], $template['html_content'], $template['text_content']);
            $stmt->execute();
            $stmt->close();
        }
    }
    
    /**
     * Send direct email (fallback method)
     */
    public function sendDirectEmail($to, $subject, $html_content, $text_content = '') {
        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $this->from_name . ' <' . $this->from_email . '>',
            'Reply-To: ' . $this->from_email
        ];
        
        return mail($to, $subject, $html_content, implode("\r\n", $headers));
    }
    
    /**
     * Send application received notification
     */
    public function sendApplicationReceived($student_email, $student_name, $internship_title, $company_name) {
        $template = $this->getTemplate('application_received');
        if (!$template) return false;
        
        $subject = $this->replaceVariables($template['subject'], [
            'internship_title' => $internship_title
        ]);
        
        $html_content = $this->replaceVariables($template['html_content'], [
            'student_name' => $student_name,
            'internship_title' => $internship_title,
            'company_name' => $company_name
        ]);
        
        $text_content = $this->replaceVariables($template['text_content'], [
            'student_name' => $student_name,
            'internship_title' => $internship_title,
            'company_name' => $company_name
        ]);
        
        return $this->sendEmail($student_email, $subject, $html_content, $text_content);
    }
    
    /**
     * Send interview scheduled notification
     */
    public function sendInterviewScheduled($student_email, $student_name, $company_name, $internship_title, $interview_date, $interview_details) {
        $template = $this->getTemplate('interview_scheduled');
        if (!$template) return false;
        
        $subject = $this->replaceVariables($template['subject'], [
            'company_name' => $company_name
        ]);
        
        $html_content = $this->replaceVariables($template['html_content'], [
            'student_name' => $student_name,
            'company_name' => $company_name,
            'internship_title' => $internship_title,
            'interview_date' => $interview_date,
            'interview_details' => $interview_details
        ]);
        
        $text_content = $this->replaceVariables($template['text_content'], [
            'student_name' => $student_name,
            'company_name' => $company_name,
            'internship_title' => $internship_title,
            'interview_date' => $interview_date,
            'interview_details' => $interview_details
        ]);
        
        return $this->sendEmail($student_email, $subject, $html_content, $text_content);
    }
    
    /**
     * Send application accepted notification
     */
    public function sendApplicationAccepted($student_email, $student_name, $internship_title, $company_name) {
        $template = $this->getTemplate('application_accepted');
        if (!$template) return false;
        
        $subject = $this->replaceVariables($template['subject'], [
            'internship_title' => $internship_title
        ]);
        
        $html_content = $this->replaceVariables($template['html_content'], [
            'student_name' => $student_name,
            'internship_title' => $internship_title,
            'company_name' => $company_name
        ]);
        
        $text_content = $this->replaceVariables($template['text_content'], [
            'student_name' => $student_name,
            'internship_title' => $internship_title,
            'company_name' => $company_name
        ]);
        
        return $this->sendEmail($student_email, $subject, $html_content, $text_content);
    }
    
    /**
     * Send application rejected notification
     */
    public function sendApplicationRejected($student_email, $student_name, $internship_title, $company_name) {
        $subject = "Application Update - $internship_title";
        $html_content = "
            <h3>Application Update</h3>
            <p>Dear $student_name,</p>
            <p>Thank you for your interest in the $internship_title position at $company_name.</p>
            <p>After careful consideration, we have decided to move forward with other candidates at this time.</p>
            <p>We encourage you to continue applying for other opportunities that match your skills and interests.</p>
            <p>Best regards,<br>The $company_name Team</p>
        ";
        
        $text_content = "Application Update\n\nDear $student_name,\n\nThank you for your interest in the $internship_title position at $company_name.\n\nAfter careful consideration, we have decided to move forward with other candidates at this time.\n\nWe encourage you to continue applying for other opportunities that match your skills and interests.\n\nBest regards,\nThe $company_name Team";
        
        return $this->sendEmail($student_email, $subject, $html_content, $text_content);
    }
    
    /**
     * Send welcome email to new users
     */
    public function sendWelcomeEmail($user_email, $user_name, $user_role) {
        $subject = "Welcome to Internship Management System!";
        $html_content = "
            <h3>Welcome to Internship Management System!</h3>
            <p>Dear $user_name,</p>
            <p>Thank you for joining our platform! We're excited to help you " . 
            ($user_role === 'student' ? 'find amazing internship opportunities' : 'connect with talented students') . ".</p>
            <p>Get started by:</p>
            <ul>
                " . ($user_role === 'student' ? 
                '<li>Completing your profile</li>
                 <li>Uploading your resume</li>
                 <li>Browsing available internships</li>' :
                '<li>Setting up your company profile</li>
                 <li>Posting internship opportunities</li>
                 <li>Reviewing applications</li>') . "
            </ul>
            <p>If you have any questions, don't hesitate to reach out to our support team.</p>
            <p>Best regards,<br>The Internship Management Team</p>
        ";
        
        $text_content = "Welcome to Internship Management System!\n\nDear $user_name,\n\nThank you for joining our platform! We're excited to help you " . 
            ($user_role === 'student' ? 'find amazing internship opportunities' : 'connect with talented students') . ".\n\nGet started by:\n" . 
            ($user_role === 'student' ? 
            '- Completing your profile\n- Uploading your resume\n- Browsing available internships' :
            '- Setting up your company profile\n- Posting internship opportunities\n- Reviewing applications') . 
            "\n\nIf you have any questions, don't hesitate to reach out to our support team.\n\nBest regards,\nThe Internship Management Team";
        
        return $this->sendEmail($user_email, $subject, $html_content, $text_content);
    }
    
    /**
     * Get email template from database
     */
    private function getTemplate($template_name) {
        $stmt = $this->conn->prepare('SELECT * FROM email_templates WHERE template_name = ?');
        $stmt->bind_param('s', $template_name);
        $stmt->execute();
        $result = $stmt->get_result();
        $template = $result->fetch_assoc();
        $stmt->close();
        
        return $template;
    }
    
    /**
     * Replace template variables with actual values
     */
    private function replaceVariables($content, $variables) {
        foreach ($variables as $key => $value) {
            $content = str_replace('{{' . $key . '}}', $value, $content);
        }
        return $content;
    }
    
    /**
     * Process email queue (send pending emails)
     */
    public function processEmailQueue() {
        $stmt = $this->conn->prepare('SELECT * FROM email_queue WHERE status = "pending" ORDER BY created_at ASC LIMIT 10');
        $stmt->execute();
        $emails = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        foreach ($emails as $email) {
            $sent = $this->sendEmailNow($email['to_email'], $email['subject'], $email['html_content'], $email['text_content']);
            
            // Update email status
            $update_stmt = $this->conn->prepare('UPDATE email_queue SET status = ?, sent_at = CURRENT_TIMESTAMP WHERE id = ?');
            $status = $sent ? 'sent' : 'failed';
            $update_stmt->bind_param('si', $status, $email['id']);
            $update_stmt->execute();
            $update_stmt->close();
        }
        
        return count($emails);
    }
    
    /**
     * Send email immediately (using PHP mail function for simplicity)
     */
    private function sendEmailNow($to, $subject, $html_content, $text_content) {
        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $this->from_name . ' <' . $this->from_email . '>',
            'Reply-To: ' . $this->from_email
        ];
        
        $headers_string = implode("\r\n", $headers);
        
        return mail($to, $subject, $html_content, $headers_string);
    }
    
    /**
     * Get email queue statistics
     */
    public function getEmailStats() {
        $stats = [];
        
        $stmt = $this->conn->prepare('SELECT status, COUNT(*) as count FROM email_queue GROUP BY status');
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $stats[$row['status']] = $row['count'];
        }
        $stmt->close();
        
        return $stats;
    }
}
?>
