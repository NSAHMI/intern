<?php
session_start();
if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}
include "../config/db.php";
include "../config/email.php";

$email = new EmailNotification($conn);
$success = '';
$errors = [];

// Handle manual email sending
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_manual_email'])) {
    $recipient_email = trim($_POST['recipient_email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    if (empty($recipient_email) || empty($subject) || empty($message)) {
        $errors[] = 'All fields are required.';
    } elseif (!filter_var($recipient_email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email address.';
    } else {
        $html_content = "<h3>$subject</h3><p>" . nl2br(htmlspecialchars($message)) . "</p>";
        $text_content = "$subject\n\n" . $message;
        
        if ($email->sendEmail($recipient_email, $subject, $html_content, $text_content)) {
            $success = 'Email queued successfully!';
        } else {
            $errors[] = 'Failed to queue email. Please try again.';
        }
    }
}

// Process email queue
if (isset($_POST['process_queue'])) {
    $processed = $email->processEmailQueue();
    $success = "Processed $processed emails from the queue.";
}

// Get email statistics
$email_stats = $email->getEmailStats();

// Get recent emails from queue
$recent_emails = [];
$stmt = $conn->prepare('
    SELECT eq.*, u.name as user_name 
    FROM email_queue eq 
    LEFT JOIN users u ON eq.to_email = u.email 
    ORDER BY eq.created_at DESC 
    LIMIT 20
');
$stmt->execute();
$recent_emails = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get email templates
$email_templates = [];
$stmt = $conn->prepare('SELECT * FROM email_templates ORDER BY template_name');
$stmt->execute();
$email_templates = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Email Management - Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #6366f1;
            --primary-dark: #4f46e5;
            --secondary-color: #f59e0b;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --dark-color: #1f2937;
            --light-bg: #f9fafb;
            --card-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            --card-hover-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
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
            max-width: 1400px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        
        .header-section {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            padding: 2rem;
            border-radius: 20px 20px 0 0;
            position: relative;
            overflow: hidden;
        }
        
        .header-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 300px;
            height: 300px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }
        
        .header-content {
            position: relative;
            z-index: 1;
        }
        
        .header-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .nav-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            margin-top: 1.5rem;
        }
        
        .btn-custom {
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-primary-custom {
            background: white;
            color: var(--primary-color);
        }
        
        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 255, 255, 0.3);
            color: var(--primary-dark);
        }
        
        .btn-secondary-custom {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        .btn-secondary-custom:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }
        
        .content-section {
            padding: 2rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: var(--card-shadow);
            text-align: center;
            transition: all 0.3s ease;
            border: 1px solid #e5e7eb;
        }
        
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--card-hover-shadow);
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: #6b7280;
            font-weight: 600;
            font-size: 0.875rem;
        }
        
        .stat-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.8;
        }
        
        .email-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: var(--card-shadow);
            border: 1px solid #e5e7eb;
            margin-bottom: 2rem;
        }
        
        .email-item {
            padding: 1rem;
            border-bottom: 1px solid #f3f4f6;
            transition: all 0.3s ease;
        }
        
        .email-item:hover {
            background: #f9fafb;
        }
        
        .email-item:last-child {
            border-bottom: none;
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-sent { background: #d1fae5; color: #065f46; }
        .status-failed { background: #fee2e2; color: #991b1b; }
        
        .form-control, .form-select {
            border-radius: 10px;
            border: 1px solid #e5e7eb;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }
        
        .btn-process {
            background: linear-gradient(135deg, var(--success-color), #059669);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-process:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
        }
        
        .template-card {
            background: linear-gradient(135deg, #f9fafb, #e5e7eb);
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1rem;
            border: 1px solid #d1d5db;
        }
    </style>
</head>
<body>
<div class="main-container">
    <div class="header-section">
        <div class="header-content">
            <h1 class="header-title">Email Management 📧</h1>
            <p class="mb-0">Monitor and manage email notifications</p>
            
            <div class="nav-buttons">
                <a href="dashboard.php" class="btn-custom btn-primary-custom">
                    <i class="fas fa-arrow-left"></i> Admin Dashboard
                </a>
                <a href="analytics.php" class="btn-custom btn-secondary-custom">
                    <i class="fas fa-chart-line"></i> Analytics
                </a>
                <a href="manage_users.php" class="btn-custom btn-secondary-custom">
                    <i class="fas fa-users"></i> Manage Users
                </a>
                <a href="../auth/logout.php" class="btn-custom btn-secondary-custom">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </div>

    <div class="content-section">
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger border-0 mb-4" style="background: linear-gradient(135deg, #fee2e2, #fecaca);">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php echo implode('<br>', $errors); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success border-0 mb-4" style="background: linear-gradient(135deg, #d1fae5, #a7f3d0);">
                <i class="fas fa-check-circle me-2"></i> <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <!-- Email Statistics -->
        <div class="stats-grid mb-4">
            <div class="stat-card">
                <div class="stat-icon text-warning">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-number text-warning"><?php echo $email_stats['pending'] ?? 0; ?></div>
                <div class="stat-label">Pending Emails</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon text-success">
                    <i class="fas fa-check"></i>
                </div>
                <div class="stat-number text-success"><?php echo $email_stats['sent'] ?? 0; ?></div>
                <div class="stat-label">Sent Emails</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon text-danger">
                    <i class="fas fa-times"></i>
                </div>
                <div class="stat-number text-danger"><?php echo $email_stats['failed'] ?? 0; ?></div>
                <div class="stat-label">Failed Emails</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon text-primary">
                    <i class="fas fa-envelope"></i>
                </div>
                <div class="stat-number text-primary"><?php echo array_sum($email_stats); ?></div>
                <div class="stat-label">Total Emails</div>
            </div>
        </div>

        <div class="row">
            <!-- Process Queue -->
            <div class="col-lg-4">
                <div class="email-card">
                    <h5 class="mb-3"><i class="fas fa-cogs me-2"></i>Email Queue</h5>
                    <p class="text-muted mb-3">Process pending emails from the queue</p>
                    <form method="post">
                        <button type="submit" name="process_queue" class="btn-process w-100">
                            <i class="fas fa-play me-2"></i>Process Queue
                        </button>
                    </form>
                </div>

                <!-- Email Templates -->
                <div class="email-card">
                    <h5 class="mb-3"><i class="fas fa-file-alt me-2"></i>Email Templates</h5>
                    <?php foreach ($email_templates as $template): ?>
                        <div class="template-card">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="mb-1"><?php echo htmlspecialchars($template['template_name']); ?></h6>
                                    <small class="text-muted"><?php echo htmlspecialchars($template['subject']); ?></small>
                                </div>
                                <i class="fas fa-envelope text-muted"></i>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Send Manual Email -->
            <div class="col-lg-8">
                <div class="email-card">
                    <h5 class="mb-3"><i class="fas fa-paper-plane me-2"></i>Send Manual Email</h5>
                    <form method="post">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Recipient Email</label>
                                    <input type="email" name="recipient_email" class="form-control" 
                                           placeholder="user@example.com" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Subject</label>
                                    <input type="text" name="subject" class="form-control" 
                                           placeholder="Email subject" required>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Message</label>
                            <textarea name="message" class="form-control" rows="5" 
                                      placeholder="Type your message here..." required></textarea>
                        </div>
                        <button type="submit" name="send_manual_email" class="btn-custom btn-primary-custom">
                            <i class="fas fa-paper-plane me-2"></i>Send Email
                        </button>
                    </form>
                </div>

                <!-- Recent Emails -->
                <div class="email-card">
                    <h5 class="mb-3"><i class="fas fa-history me-2"></i>Recent Emails</h5>
                    <?php if (!empty($recent_emails)): ?>
                        <?php foreach ($recent_emails as $email_item): ?>
                            <div class="email-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center mb-1">
                                            <h6 class="mb-0 me-2"><?php echo htmlspecialchars($email_item['subject']); ?></h6>
                                            <span class="status-badge status-<?php echo $email_item['status']; ?>">
                                                <?php echo ucfirst($email_item['status']); ?>
                                            </span>
                                        </div>
                                        <p class="mb-1">
                                            <i class="fas fa-user me-2"></i>
                                            <?php echo htmlspecialchars($email_item['user_name'] ?? $email_item['to_email']); ?>
                                        </p>
                                        <small class="text-muted">
                                            <i class="fas fa-clock me-1"></i>
                                            <?php echo date('M j, Y g:i A', strtotime($email_item['created_at'])); ?>
                                            <?php if ($email_item['sent_at']): ?>
                                                • Sent: <?php echo date('M j, g:i A', strtotime($email_item['sent_at'])); ?>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted text-center">No emails sent yet</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
