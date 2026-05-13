<?php
session_start();
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

include "../config/db.php";
include "../config/security.php";

$security = new SecurityManager($conn);
$error = '';
$success = '';

// Get user info
$stmt = $conn->prepare('SELECT email, two_factor_enabled FROM users WHERE id = ?');
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Handle 2FA setup
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['enable_2fa'])) {
        $secret = $security->generate2FASecret();
        
        if ($security->enable2FA($_SESSION['user_id'], $secret)) {
            $qr_data = $security->generate2FAQRCode($user['email'], $secret);
            $success = '2FA setup initiated! Scan the QR code with your authenticator app.';
        } else {
            $error = 'Failed to enable 2FA. Please try again.';
        }
    } elseif (isset($_POST['disable_2fa'])) {
        if ($security->disable2FA($_SESSION['user_id'])) {
            $success = '2FA has been disabled for your account.';
        } else {
            $error = 'Failed to disable 2FA. Please try again.';
        }
    } elseif (isset($_POST['verify_2fa'])) {
        $code = $_POST['verification_code'] ?? '';
        
        if (empty($code)) {
            $error = 'Please enter the verification code.';
        } elseif ($security->verify2FACode($_SESSION['user_id'], $code)) {
            $success = '2FA verification successful! Your account is now protected.';
        } else {
            $error = 'Invalid verification code. Please try again.';
        }
    }
}

// Get current 2FA status
$stmt = $conn->prepare('SELECT two_factor_secret, two_factor_enabled FROM users WHERE id = ?');
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$two_fa_status = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Two-Factor Authentication Setup</title>
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
            margin: 20px auto;
            max-width: 600px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        
        .header-section {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            padding: 2rem;
            border-radius: 20px 20px 0 0;
            text-align: center;
        }
        
        .content-section {
            padding: 2rem;
        }
        
        .security-card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e7eb;
            margin-bottom: 2rem;
        }
        
        .qr-code {
            width: 200px;
            height: 200px;
            margin: 0 auto;
            background: #f9fafb;
            border: 2px dashed #d1d5db;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            color: #6b7280;
            text-align: center;
        }
        
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .status-enabled {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-disabled {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .btn-security {
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            font-weight: 600;
            border: none;
            transition: all 0.3s ease;
        }
        
        .btn-enable {
            background: linear-gradient(135deg, var(--success-color), #059669);
            color: white;
        }
        
        .btn-disable {
            background: linear-gradient(135deg, var(--danger-color), #dc2626);
            color: white;
        }
        
        .btn-verify {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
        }
        
        .btn-security:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        .form-control {
            border-radius: 10px;
            border: 1px solid #e5e7eb;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }
        
        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 2rem;
        }
        
        .step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e5e7eb;
            color: #6b7280;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            position: relative;
        }
        
        .step.active {
            background: var(--primary-color);
            color: white;
        }
        
        .step.completed {
            background: var(--success-color);
            color: white;
        }
        
        .step:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 100%;
            width: 40px;
            height: 2px;
            background: #e5e7eb;
            transform: translateY(-50%);
        }
        
        .step.completed:not(:last-child)::after {
            background: var(--success-color);
        }
    </style>
</head>
<body>
<div class="main-container">
    <div class="header-section">
        <h1 class="mb-3"><i class="fas fa-shield-alt me-2"></i>Two-Factor Authentication</h1>
        <p class="mb-0">Add an extra layer of security to your account</p>
    </div>

    <div class="content-section">
        <?php if ($error): ?>
            <div class="alert alert-danger border-0 mb-4" style="background: linear-gradient(135deg, #fee2e2, #fecaca);">
                <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success border-0 mb-4" style="background: linear-gradient(135deg, #d1fae5, #a7f3d0);">
                <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <!-- Current Status -->
        <div class="security-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Current Status</h5>
                <span class="status-badge <?php echo $two_fa_status['two_factor_enabled'] ? 'status-enabled' : 'status-disabled'; ?>">
                    <i class="fas fa-<?php echo $two_fa_status['two_factor_enabled'] ? 'check-circle' : 'times-circle'; ?>"></i>
                    <?php echo $two_fa_status['two_factor_enabled'] ? 'Enabled' : 'Disabled'; ?>
                </span>
            </div>
            <p class="text-muted mb-0">
                <?php echo $two_fa_status['two_factor_enabled'] 
                    ? 'Your account is protected with two-factor authentication.' 
                    : 'Enable 2FA to add an extra layer of security to your account.'; ?>
            </p>
        </div>

        <?php if (!$two_fa_status['two_factor_enabled']): ?>
            <!-- Enable 2FA -->
            <div class="security-card">
                <h5 class="mb-4"><i class="fas fa-plus-circle me-2"></i>Enable Two-Factor Authentication</h5>
                
                <div class="step-indicator">
                    <div class="step active">1</div>
                    <div class="step">2</div>
                    <div class="step">3</div>
                </div>

                <form method="post">
                    <div class="text-center mb-4">
                        <p class="mb-3">Step 1: Install an authenticator app</p>
                        <div class="d-flex justify-content-center gap-3 mb-3">
                            <a href="https://apps.apple.com/app/google-authenticator/id388497605" target="_blank" class="btn btn-outline-primary">
                                <i class="fab fa-apple me-2"></i>App Store
                            </a>
                            <a href="https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2" target="_blank" class="btn btn-outline-primary">
                                <i class="fab fa-google-play me-2"></i>Google Play
                            </a>
                        </div>
                        <small class="text-muted">We recommend Google Authenticator or Microsoft Authenticator</small>
                    </div>

                    <div class="text-center">
                        <button type="submit" name="enable_2fa" class="btn-security btn-enable">
                            <i class="fas fa-qrcode me-2"></i>Generate QR Code
                        </button>
                    </div>
                </form>

                <?php if (isset($qr_data)): ?>
                    <div class="mt-4 p-3 bg-light rounded-3">
                        <h6 class="text-center mb-3">Step 2: Scan this QR Code</h6>
                        <div class="qr-code mb-3">
                            <i class="fas fa-qrcode fa-3x mb-2"></i><br>
                            QR Code Data:<br>
                            <small><?php echo htmlspecialchars($qr_data); ?></small>
                        </div>
                        <p class="text-center text-muted small mb-3">
                            Or manually enter this code in your authenticator app:<br>
                            <code><?php echo htmlspecialchars($two_fa_status['two_factor_secret']); ?></code>
                        </p>
                        
                        <form method="post">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Step 3: Enter Verification Code</label>
                                <input type="text" name="verification_code" class="form-control" 
                                       placeholder="Enter 6-digit code" maxlength="6" required>
                                <div class="form-text">Enter the code shown in your authenticator app</div>
                            </div>
                            <div class="text-center">
                                <button type="submit" name="verify_2fa" class="btn-security btn-verify">
                                    <i class="fas fa-check me-2"></i>Verify & Enable
                                </button>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <!-- Disable 2FA -->
            <div class="security-card">
                <h5 class="mb-3"><i class="fas fa-minus-circle me-2"></i>Disable Two-Factor Authentication</h5>
                <p class="text-muted mb-4">
                    Disabling 2FA will make your account less secure. We recommend keeping it enabled.
                </p>
                
                <form method="post">
                    <div class="text-center">
                        <button type="submit" name="disable_2fa" class="btn-security btn-disable"
                                onclick="return confirm('Are you sure you want to disable 2FA? This will make your account less secure.')">
                            <i class="fas fa-times me-2"></i>Disable 2FA
                        </button>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <!-- Security Tips -->
        <div class="security-card">
            <h5 class="mb-3"><i class="fas fa-lightbulb me-2"></i>Security Tips</h5>
            <ul class="mb-0">
                <li>Keep your authenticator app secure and backed up</li>
                <li>Never share your verification codes with anyone</li>
                <li>Enable 2FA on all your important accounts</li>
                <li>Use a unique, strong password for your account</li>
                <li>Regularly review your account activity</li>
            </ul>
        </div>

        <div class="text-center">
            <a href="../student/dashboard.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
            </a>
        </div>
    </div>
</div>
</body>
</html>
