<?php
// SMS/Email 2FA Authentication System
class TwoFactorAuth {
    private $conn;
    
    public function __construct($database) {
        $this->conn = $database;
    }
    
    /**
     * Generate and send 2FA code via email
     */
    public function send2FACode($user_id, $purpose = 'login') {
        // Get user details
        $stmt = $this->conn->prepare('SELECT email, name FROM users WHERE id = ?');
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$user) {
            return false;
        }
        
        // Check if sms_verifications table exists
        $table_check = $this->conn->prepare("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sms_verifications'");
        $table_check->execute();
        $table_exists = $table_check->get_result()->num_rows > 0;
        $table_check->close();
        
        // Generate 6-digit code
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        if (!$table_exists) {
            // Table doesn't exist, send email directly without storing code
            return $this->send2FAEmail($user['email'], $user['name'], $code, $purpose);
        }
        $expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));
        
        // Clean old codes for this user
        $this->conn->prepare('DELETE FROM sms_verifications WHERE user_id = ? AND purpose = ?')->execute();
        
        // Store verification code
        $stmt = $this->conn->prepare('
            INSERT INTO sms_verifications (user_id, phone_number, verification_code, purpose, expires_at) 
            VALUES (?, ?, ?, ?, ?)
        ');
        $phone_number = $user['email']; // Store email as phone number for reuse
        $stmt->bind_param('issss', $user_id, $phone_number, $code, $purpose, $expires_at);
        $result = $stmt->execute();
        $stmt->close();
        
        if ($result) {
            // Send code via email
            return $this->send2FAEmail($user['email'], $user['name'], $code, $purpose);
        }
        
        return false;
    }
    
    /**
     * Send 2FA code via email
     */
    private function send2FAEmail($email, $name, $code, $purpose) {
        // Include EmailNotification class only when needed
        require_once __DIR__ . '/email.php';
        $email_notifier = new EmailNotification($this->conn);
        
        $subject = "Your 2FA Verification Code";
        
        $html_content = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <div style='background: #6366f1; color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0;'>
                    <h2>🔐 Two-Factor Authentication</h2>
                </div>
                <div style='background: #f9fafb; padding: 30px; border-radius: 0 0 10px 10px; border: 1px solid #e5e7eb;'>
                    <h3>Hello {$name},</h3>
                    <p>Your verification code is:</p>
                    <div style='background: white; border: 2px dashed #6366f1; padding: 20px; text-align: center; margin: 20px 0; border-radius: 8px;'>
                        <span style='font-size: 32px; font-weight: bold; color: #6366f1; letter-spacing: 5px;'>{$code}</span>
                    </div>
                    <p><strong>This code will expire in 10 minutes.</strong></p>
                    <p>If you didn't request this code, please ignore this email.</p>
                    <hr style='border: none; border-top: 1px solid #e5e7eb; margin: 30px 0;'>
                    <p style='color: #6b7280; font-size: 14px;'>
                        Best regards,<br>
                        Internship Hub Security Team
                    </p>
                </div>
            </div>
        ";
        
        $text_content = "Your 2FA verification code is: {$code}\n\nThis code will expire in 10 minutes.\n\nIf you didn't request this code, please ignore this email.\n\nInternship Hub Security Team";
        
        return $email_notifier->sendDirectEmail($email, $subject, $html_content, $text_content);
    }
    
    /**
     * Verify 2FA code
     */
    public function verify2FACode($user_id, $code, $purpose = 'login') {
        // Check if sms_verifications table exists
        $table_check = $this->conn->prepare("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sms_verifications'");
        $table_check->execute();
        $table_exists = $table_check->get_result()->num_rows > 0;
        $table_check->close();
        
        if (!$table_exists) {
            // Table doesn't exist, cannot verify codes properly
            // For now, return false to maintain security
            return false;
        }
        
        $stmt = $this->conn->prepare('
            SELECT id FROM sms_verifications 
            WHERE user_id = ? AND verification_code = ? AND purpose = ? 
            AND expires_at > NOW() AND verified_at IS NULL
        ');
        if ($stmt === false) {
            return false;
        }
        $stmt->bind_param('iss', $user_id, $code, $purpose);
        $stmt->execute();
        $result = $stmt->get_result();
        $verification = $result->fetch_assoc();
        $stmt->close();
        
        if ($verification) {
            // Mark as verified
            $stmt = $this->conn->prepare('UPDATE sms_verifications SET verified_at = NOW() WHERE id = ?');
            if ($stmt !== false) {
                $stmt->bind_param('i', $verification['id']);
                $stmt->execute();
                $stmt->close();
            }
            
            return true;
        }
        
        // Increment attempts
        $stmt = $this->conn->prepare('UPDATE sms_verifications SET attempts = attempts + 1 WHERE user_id = ? AND purpose = ?');
        if ($stmt !== false) {
            $stmt->bind_param('is', $user_id, $purpose);
            $stmt->execute();
            $stmt->close();
        }
        
        return false;
    }
    
    /**
     * Check if user has 2FA enabled
     */
    public function isUser2FAEnabled($user_id) {
        // Check if sms_2fa_enabled column exists
        $column_check = $this->conn->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'sms_2fa_enabled'");
        $column_check->execute();
        $column_exists = $column_check->get_result()->num_rows > 0;
        $column_check->close();
        
        if (!$column_exists) {
            return false; // 2FA not available if column doesn't exist
        }
        
        $stmt = $this->conn->prepare('SELECT sms_2fa_enabled FROM users WHERE id = ?');
        if ($stmt === false) {
            return false;
        }
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        
        return $user && $user['sms_2fa_enabled'];
    }
    
    /**
     * Enable 2FA for user
     */
    public function enableUser2FA($user_id) {
        // Check if sms_2fa_enabled column exists
        $column_check = $this->conn->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'sms_2fa_enabled'");
        $column_check->execute();
        $column_exists = $column_check->get_result()->num_rows > 0;
        $column_check->close();
        
        if (!$column_exists) {
            return false; // Cannot enable 2FA if column doesn't exist
        }
        
        $stmt = $this->conn->prepare('UPDATE users SET sms_2fa_enabled = TRUE WHERE id = ?');
        if ($stmt === false) {
            return false;
        }
        $stmt->bind_param('i', $user_id);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }
    
    /**
     * Disable 2FA for user
     */
    public function disableUser2FA($user_id) {
        // Check if sms_2fa_enabled column exists
        $column_check = $this->conn->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'sms_2fa_enabled'");
        $column_check->execute();
        $column_exists = $column_check->get_result()->num_rows > 0;
        $column_check->close();
        
        if (!$column_exists) {
            return true; // 2FA is already disabled if column doesn't exist
        }
        
        $stmt = $this->conn->prepare('UPDATE users SET sms_2fa_enabled = FALSE WHERE id = ?');
        if ($stmt === false) {
            return false;
        }
        $stmt->bind_param('i', $user_id);
        $result = $stmt->execute();
        $stmt->close();
        
        // Clean up verification codes
        $this->conn->prepare('DELETE FROM sms_verifications WHERE user_id = ?')->execute();
        
        return $result;
    }
    
    /**
     * Log 2FA attempt
     */
    public function log2FAAttempt($user_id, $action, $success, $details = '') {
        $stmt = $this->conn->prepare('
            INSERT INTO security_logs (identifier, action, details, success) 
            VALUES (?, ?, ?, ?)
        ');
        $identifier = "user_id:$user_id";
        $stmt->bind_param('sssi', $identifier, $action, $details, $success);
        $stmt->execute();
        $stmt->close();
    }
}
?>
