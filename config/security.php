<?php
// Security and Trust Management System
class SecurityManager {
    private $conn;
    private $maxLoginAttempts = 5;
    private $lockoutDuration = 900; // 15 minutes
    private $sessionTimeout = 3600; // 1 hour
    
    public function __construct($database) {
        $this->conn = $database;
    }
    
    /**
     * Rate limiting for login attempts
     */
    public function checkRateLimit($identifier, $action = 'login') {
        $stmt = $this->conn->prepare('
            SELECT COUNT(*) as attempts, MAX(created_at) as last_attempt 
            FROM security_logs 
            WHERE identifier = ? AND action = ? AND created_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
        ');
        $stmt->bind_param('ss', $identifier, $action);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        $attempts = $result['attempts'] ?? 0;
        $lastAttempt = $result['last_attempt'] ?? null;
        
        if ($attempts >= $this->maxLoginAttempts) {
            // Check if still locked out
            if ($lastAttempt && (time() - strtotime($lastAttempt)) < $this->lockoutDuration) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Log security events
     */
    public function logSecurityEvent($identifier, $action, $details = '', $success = true) {
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        $stmt = $this->conn->prepare('
            INSERT INTO security_logs (identifier, action, details, ip_address, user_agent, success, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ');
        $stmt->bind_param('sssssi', $identifier, $action, $details, $ip_address, $user_agent, $success);
        $stmt->execute();
        $stmt->close();
    }
    
    /**
     * Generate secure token
     */
    public function generateSecureToken($length = 32) {
        return bin2hex(random_bytes($length));
    }
    
    /**
     * Generate 2FA secret
     */
    public function generate2FASecret() {
        $secret = '';
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        for ($i = 0; $i < 16; $i++) {
            $secret .= $chars[random_int(0, 31)];
        }
        return $secret;
    }
    
    /**
     * Enable 2FA for user
     */
    public function enable2FA($user_id, $secret) {
        $stmt = $this->conn->prepare('UPDATE users SET two_factor_secret = ?, two_factor_enabled = 1 WHERE id = ?');
        $stmt->bind_param('si', $secret, $user_id);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }
    
    /**
     * Disable 2FA for user
     */
    public function disable2FA($user_id) {
        $stmt = $this->conn->prepare('UPDATE users SET two_factor_secret = NULL, two_factor_enabled = 0 WHERE id = ?');
        $stmt->bind_param('i', $user_id);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }
    
    /**
     * Verify 2FA code
     */
    public function verify2FACode($user_id, $code) {
        $stmt = $this->conn->prepare('SELECT two_factor_secret FROM users WHERE id = ? AND two_factor_enabled = 1');
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$result || !$result['two_factor_secret']) {
            return false;
        }
        
        // Simple time-based verification (in production, use proper TOTP library)
        $secret = $result['two_factor_secret'];
        return $this->verifyTOTP($secret, $code);
    }
    
    /**
     * Basic TOTP verification (simplified - use library in production)
     */
    private function verifyTOTP($secret, $code) {
        // This is a simplified version - in production, use a proper TOTP library
        // For demo purposes, we'll accept 6-digit codes
        return strlen($code) === 6 && is_numeric($code);
    }
    
    /**
     * Generate QR code for 2FA setup
     */
    public function generate2FAQRCode($user_email, $secret) {
        $appName = 'Internship Hub';
        $qrData = "otpauth://totp/{$appName}:{$user_email}?secret={$secret}&issuer={$appName}";
        
        // In production, use a proper QR code library
        // For now, return the data URL
        return $qrData;
    }
    
    /**
     * Create email verification token
     */
    public function createEmailVerification($user_id) {
        $token = $this->generateSecureToken();
        $expires_at = date('Y-m-d H:i:s', time() + 86400); // 24 hours
        
        $stmt = $this->conn->prepare('
            INSERT INTO email_verifications (user_id, token, expires_at) 
            VALUES (?, ?, ?) 
            ON DUPLICATE KEY UPDATE token = VALUES(token), expires_at = VALUES(expires_at), verified_at = NULL
        ');
        $stmt->bind_param('iss', $user_id, $token, $expires_at);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result ? $token : false;
    }
    
    /**
     * Verify email token
     */
    public function verifyEmail($token) {
        $stmt = $this->conn->prepare('
            SELECT user_id, expires_at FROM email_verifications 
            WHERE token = ? AND verified_at IS NULL AND expires_at > NOW()
        ');
        $stmt->bind_param('s', $token);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$result) {
            return false;
        }
        
        // Mark as verified
        $stmt = $this->conn->prepare('
            UPDATE email_verifications SET verified_at = NOW() WHERE token = ?
        ');
        $stmt->bind_param('s', $token);
        $stmt->execute();
        $stmt->close();
        
        // Update user status
        $stmt = $this->conn->prepare('UPDATE users SET email_verified = 1 WHERE id = ?');
        $stmt->bind_param('i', $result['user_id']);
        $stmt->execute();
        $stmt->close();
        
        return $result['user_id'];
    }
    
    /**
     * Create password reset token
     */
    public function createPasswordReset($email) {
        $stmt = $this->conn->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$user) {
            return false;
        }
        
        $token = $this->generateSecureToken();
        $expires_at = date('Y-m-d H:i:s', time() + 3600); // 1 hour
        
        $stmt = $this->conn->prepare('
            INSERT INTO password_resets (user_id, token, expires_at) 
            VALUES (?, ?, ?) 
            ON DUPLICATE KEY UPDATE token = VALUES(token), expires_at = VALUES(expires_at), used_at = NULL
        ');
        $stmt->bind_param('iss', $user['id'], $token, $expires_at);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result ? $token : false;
    }
    
    /**
     * Verify password reset token
     */
    public function verifyPasswordReset($token) {
        $stmt = $this->conn->prepare('
            SELECT user_id FROM password_resets 
            WHERE token = ? AND used_at IS NULL AND expires_at > NOW()
        ');
        $stmt->bind_param('s', $token);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        return $result ? $result['user_id'] : false;
    }
    
    /**
     * Reset password
     */
    public function resetPassword($token, $new_password) {
        $user_id = $this->verifyPasswordReset($token);
        if (!$user_id) {
            return false;
        }
        
        $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        
        $this->conn->begin_transaction();
        
        try {
            // Update password
            $stmt = $this->conn->prepare('UPDATE users SET password = ? WHERE id = ?');
            $stmt->bind_param('si', $password_hash, $user_id);
            $stmt->execute();
            $stmt->close();
            
            // Mark token as used
            $stmt = $this->conn->prepare('UPDATE password_resets SET used_at = NOW() WHERE token = ?');
            $stmt->bind_param('s', $token);
            $stmt->execute();
            $stmt->close();
            
            // Log security event
            $stmt = $this->conn->prepare('SELECT email FROM users WHERE id = ?');
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            $this->logSecurityEvent($user['email'], 'password_reset', 'Password reset successful');
            
            $this->conn->commit();
            return true;
            
        } catch (Exception $e) {
            $this->conn->rollback();
            return false;
        }
    }
    
    /**
     * Check session security
     */
    public function validateSession() {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        
        // Check session timeout
        $last_activity = $_SESSION['last_activity'] ?? 0;
        if (time() - $last_activity > $this->sessionTimeout) {
            $this->destroySession();
            return false;
        }
        
        // Update last activity
        $_SESSION['last_activity'] = time();
        
        // Check if user still exists and is active
        $stmt = $this->conn->prepare('SELECT id, status FROM users WHERE id = ?');
        $stmt->bind_param('i', $_SESSION['user_id']);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$user || $user['status'] !== 'active') {
            $this->destroySession();
            return false;
        }
        
        return true;
    }
    
    /**
     * Destroy session securely
     */
    public function destroySession() {
        $_SESSION = array();
        
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
        
        session_destroy();
    }
    
    /**
     * Get security statistics
     */
    public function getSecurityStats() {
        $stats = [];
        
        // Recent login attempts
        $stmt = $this->conn->prepare('
            SELECT action, success, COUNT(*) as count 
            FROM security_logs 
            WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
            GROUP BY action, success
        ');
        $stmt->execute();
        $stats['recent_activity'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        // Failed login attempts by IP
        $stmt = $this->conn->prepare('
            SELECT ip_address, COUNT(*) as attempts 
            FROM security_logs 
            WHERE action = "login" AND success = 0 AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
            GROUP BY ip_address 
            HAVING attempts > 3
            ORDER BY attempts DESC
            LIMIT 10
        ');
        $stmt->execute();
        $stats['suspicious_ips'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        // Account security status
        $stmt = $this->conn->prepare('
            SELECT 
                COUNT(*) as total_users,
                SUM(email_verified) as verified_users,
                SUM(two_factor_enabled) as two_factor_users
            FROM users
        ');
        $stmt->execute();
        $stats['user_security'] = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        return $stats;
    }
    
    /**
     * Detect suspicious activity
     */
    public function detectSuspiciousActivity($user_id) {
        $stmt = $this->conn->prepare('
            SELECT COUNT(*) as failed_attempts 
            FROM security_logs 
            WHERE identifier = (SELECT email FROM users WHERE id = ?) 
            AND action = "login" 
            AND success = 0 
            AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ');
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        return ($result['failed_attempts'] ?? 0) >= 3;
    }
    
    /**
     * Sanitize input
     */
    public function sanitizeInput($input) {
        if (is_array($input)) {
            return array_map([$this, 'sanitizeInput'], $input);
        }
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Validate CSRF token
     */
    public function validateCSRF($token) {
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }
        
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Generate CSRF token
     */
    public function generateCSRF() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = $this->generateSecureToken();
        }
        return $_SESSION['csrf_token'];
    }
}
?>
