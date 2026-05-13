<?php
session_start();
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

include "../config/db.php";
include "../config/sms_2fa.php";

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';
$two_factor = new TwoFactorAuth($conn);

// Get current user info
// Check if sms_2fa_enabled column exists
$column_check = $conn->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'sms_2fa_enabled'");
$column_check->execute();
$column_exists = $column_check->get_result()->num_rows > 0;
$column_check->close();

if ($column_exists) {
    $stmt = $conn->prepare('SELECT name, email, sms_2fa_enabled FROM users WHERE id = ?');
} else {
    $stmt = $conn->prepare('SELECT name, email FROM users WHERE id = ?');
}

if ($stmt === false) {
    $error = 'Database error. Please try again later.';
    $user = ['name' => 'User', 'email' => '', 'sms_2fa_enabled' => false];
} else {
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    // Add default value for sms_2fa_enabled if column doesn't exist
    if ($user && !$column_exists) {
        $user['sms_2fa_enabled'] = false;
    }
}

// Handle enable 2FA
if (isset($_POST['enable_2fa'])) {
    if ($two_factor->send2FACode($user_id, 'setup')) {
        $_SESSION['pending_2fa_setup'] = true;
        $success = 'A verification code has been sent to your email. Enter it below to enable 2FA.';
    } else {
        $error = 'Failed to send verification code. Please try again.';
    }
}

// Handle verify and enable 2FA
if (isset($_POST['verify_setup']) && isset($_SESSION['pending_2fa_setup'])) {
    $code = $_POST['setup_code'] ?? '';
    
    if (empty($code)) {
        $error = 'Please enter the verification code.';
    } elseif ($two_factor->verify2FACode($user_id, $code, 'setup')) {
        if ($two_factor->enableUser2FA($user_id)) {
            $success = 'Two-factor authentication has been enabled for your account!';
            unset($_SESSION['pending_2fa_setup']);
            $user['sms_2fa_enabled'] = true;
            $two_factor->log2FAAttempt($user_id, '2fa_enabled', true);
        } else {
            $error = 'Failed to enable 2FA. Please try again.';
        }
    } else {
        $error = 'Invalid or expired verification code.';
        $two_factor->log2FAAttempt($user_id, '2fa_setup_failed', false);
    }
}

// Handle disable 2FA
if (isset($_POST['disable_2fa'])) {
    if ($two_factor->disableUser2FA($user_id)) {
        $success = 'Two-factor authentication has been disabled for your account.';
        $user['sms_2fa_enabled'] = false;
        $two_factor->log2FAAttempt($user_id, '2fa_disabled', true);
    } else {
        $error = 'Failed to disable 2FA. Please try again.';
    }
}

// Handle test 2FA
if (isset($_POST['test_2fa']) && $user['sms_2fa_enabled']) {
    if ($two_factor->send2FACode($user_id, 'test')) {
        $_SESSION['pending_2fa_test'] = true;
        $success = 'A test verification code has been sent to your email.';
    } else {
        $error = 'Failed to send test code. Please try again.';
    }
}

// Handle verify test 2FA
if (isset($_POST['verify_test']) && isset($_SESSION['pending_2fa_test'])) {
    $code = $_POST['test_code'] ?? '';
    
    if (empty($code)) {
        $error = 'Please enter the verification code.';
    } elseif ($two_factor->verify2FACode($user_id, $code, 'test')) {
        $success = 'Test successful! Your 2FA is working correctly.';
        unset($_SESSION['pending_2fa_test']);
        $two_factor->log2FAAttempt($user_id, '2fa_test_success', true);
    } else {
        $error = 'Invalid or expired verification code.';
        $two_factor->log2FAAttempt($user_id, '2fa_test_failed', false);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Email 2FA Setup - Internship Hub</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #6366f1;
            --primary-dark: #4f46e5;
            --success-color: #10b981;
            --danger-color: #ef4444;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }
        
        .main-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            margin: 40px auto;
            max-width: 600px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }
        
        .status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 14px;
        }
        
        .status-enabled {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-disabled {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .feature-card {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            background: #f9fafb;
        }
        
        .btn-custom {
            border-radius: 10px;
            padding: 10px 20px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .code-input {
            font-size: 24px;
            letter-spacing: 8px;
            text-align: center;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="main-container p-4">
            <div class="text-center mb-4">
                <i class="fas fa-shield-alt fa-3x text-primary mb-3"></i>
                <h3>Email Two-Factor Authentication</h3>
                <p class="text-muted">Add an extra layer of security to your account</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Current Status -->
            <div class="feature-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1">Current Status</h5>
                        <p class="text-muted mb-0">2FA for account: <?php echo htmlspecialchars($user['email']); ?></p>
                    </div>
                    <div>
                        <?php if ($user['sms_2fa_enabled']): ?>
                            <span class="status-badge status-enabled">
                                <i class="fas fa-check-circle me-1"></i>Enabled
                            </span>
                        <?php else: ?>
                            <span class="status-badge status-disabled">
                                <i class="fas fa-times-circle me-1"></i>Disabled
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- 2FA Actions -->
            <?php if (!$user['sms_2fa_enabled']): ?>
                <!-- Enable 2FA Section -->
                <?php if (!isset($_SESSION['pending_2fa_setup'])): ?>
                    <div class="feature-card">
                        <h5><i class="fas fa-lock me-2"></i>Enable 2FA</h5>
                        <p class="text-muted">Protect your account with an additional verification step sent to your email.</p>
                        <form method="post">
                            <button type="submit" name="enable_2fa" class="btn btn-primary btn-custom">
                                <i class="fas fa-plus me-2"></i>Enable Email 2FA
                            </button>
                        </form>
                    </div>
                <?php else: ?>
                    <!-- Verify Setup -->
                    <div class="feature-card">
                        <h5><i class="fas fa-key me-2"></i>Verify Setup</h5>
                        <p class="text-muted">Enter the 6-digit code sent to your email to complete 2FA setup.</p>
                        <form method="post">
                            <input type="hidden" name="verify_setup" value="1">
                            <div class="mb-3">
                                <input type="text" name="setup_code" class="form-control code-input" 
                                       placeholder="000000" maxlength="6" pattern="\d{6}" required>
                                <small class="text-muted">Code expires in 10 minutes</small>
                            </div>
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-check me-2"></i>Enable 2FA
                                </button>
                                <a href="setup_email_2fa.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-2"></i>Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <!-- 2FA Enabled Actions -->
                <div class="feature-card">
                    <h5><i class="fas fa-cog me-2"></i>2FA Management</h5>
                    
                    <div class="mb-3">
                        <form method="post" class="d-inline">
                            <button type="submit" name="test_2fa" class="btn btn-info btn-custom me-2">
                                <i class="fas fa-vial me-2"></i>Test 2FA
                            </button>
                        </form>
                        
                        <form method="post" class="d-inline">
                            <button type="submit" name="disable_2fa" class="btn btn-danger btn-custom" 
                                    onclick="return confirm('Are you sure you want to disable 2FA? This will make your account less secure.');">
                                <i class="fas fa-times me-2"></i>Disable 2FA
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Test 2FA Verification -->
                <?php if (isset($_SESSION['pending_2fa_test'])): ?>
                    <div class="feature-card">
                        <h5><i class="fas fa-vial me-2"></i>Test 2FA</h5>
                        <p class="text-muted">Enter the test code sent to your email.</p>
                        <form method="post">
                            <input type="hidden" name="verify_test" value="1">
                            <div class="mb-3">
                                <input type="text" name="test_code" class="form-control code-input" 
                                       placeholder="000000" maxlength="6" pattern="\d{6}" required>
                            </div>
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-check me-2"></i>Verify Test Code
                                </button>
                                <a href="setup_email_2fa.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-2"></i>Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Information Section -->
            <div class="feature-card">
                <h5><i class="fas fa-info-circle me-2"></i>How Email 2FA Works</h5>
                <ol class="mb-0">
                    <li>Enter your email and password as usual</li>
                    <li>Check your email for a 6-digit verification code</li>
                    <li>Enter the code to complete your login</li>
                    <li>Codes expire after 10 minutes for security</li>
                </ol>
            </div>

            <!-- Back to Dashboard -->
            <div class="text-center mt-4">
                <a href="../<?php echo $_SESSION['role']; ?>/dashboard.php" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
?>
