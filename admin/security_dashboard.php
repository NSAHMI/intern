<?php
session_start();
if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}
include "../config/db.php";
include "../config/security.php";

$security = new SecurityManager($conn);
$stats = $security->getSecurityStats();

// Get recent security events
$stmt = $conn->prepare('
    SELECT * FROM security_logs 
    ORDER BY created_at DESC 
    LIMIT 20
');
$stmt->execute();
$recent_events = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get user security status
$stmt = $conn->prepare('
    SELECT 
        u.id, u.name, u.email, u.email_verified, u.two_factor_enabled, u.last_login,
        COUNT(sl.id) as failed_attempts
    FROM users u
    LEFT JOIN security_logs sl ON u.email = sl.identifier 
        AND sl.action = "login" 
        AND sl.success = 0 
        AND sl.created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
    GROUP BY u.id
    ORDER BY failed_attempts DESC, u.created_at DESC
');
$stmt->execute();
$users_security = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Security Dashboard - Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #6366f1;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --dark-color: #1f2937;
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
            background: linear-gradient(135deg, var(--primary-color), #4f46e5);
            color: white;
            padding: 2rem;
            border-radius: 20px 20px 0 0;
        }
        
        .content-section {
            padding: 2rem;
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
        
        .btn-secondary-custom {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
            text-align: center;
            transition: all 0.3s ease;
            border: 1px solid #e5e7eb;
        }
        
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
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
        
        .security-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e7eb;
            margin-bottom: 2rem;
        }
        
        .event-item {
            padding: 1rem;
            border-bottom: 1px solid #f3f4f6;
            transition: all 0.3s ease;
        }
        
        .event-item:hover {
            background: #f9fafb;
        }
        
        .event-item:last-child {
            border-bottom: none;
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .status-success { background: #d1fae5; color: #065f46; }
        .status-failed { background: #fee2e2; color: #991b1b; }
        .status-warning { background: #fef3c7; color: #92400e; }
        
        .user-security-row {
            padding: 1rem;
            border-bottom: 1px solid #f3f4f6;
            transition: all 0.3s ease;
        }
        
        .user-security-row:hover {
            background: #f9fafb;
        }
        
        .security-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 0.5rem;
        }
        
        .security-high { background: var(--success-color); }
        .security-medium { background: var(--warning-color); }
        .security-low { background: var(--danger-color); }
        
        .threat-level {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.875rem;
        }
        
        .threat-low { background: #d1fae5; color: #065f46; }
        .threat-medium { background: #fef3c7; color: #92400e; }
        .threat-high { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>
<div class="main-container">
    <div class="header-section">
        <h1 class="mb-3"><i class="fas fa-shield-alt me-2"></i>Security Dashboard</h1>
        <p class="mb-0">Monitor and manage platform security</p>
        
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

    <div class="content-section">
        <!-- Security Overview -->
        <div class="stats-grid mb-4">
            <div class="stat-card">
                <div class="stat-number text-primary"><?php echo $stats['user_security']['total_users'] ?? 0; ?></div>
                <div class="stat-label">Total Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-number text-success"><?php echo $stats['user_security']['verified_users'] ?? 0; ?></div>
                <div class="stat-label">Verified Emails</div>
            </div>
            <div class="stat-card">
                <div class="stat-number text-warning"><?php echo $stats['user_security']['two_factor_users'] ?? 0; ?></div>
                <div class="stat-label">2FA Enabled</div>
            </div>
            <div class="stat-card">
                <div class="stat-number text-danger">
                    <?php 
                    $failed_logins = 0;
                    foreach ($stats['recent_activity'] ?? [] as $activity) {
                        if ($activity['action'] === 'login' && !$activity['success']) {
                            $failed_logins += $activity['count'];
                        }
                    }
                    echo $failed_logins;
                    ?>
                </div>
                <div class="stat-label">Failed Logins (24h)</div>
            </div>
        </div>

        <div class="row">
            <!-- Recent Security Events -->
            <div class="col-lg-7">
                <div class="security-card">
                    <h5 class="mb-3"><i class="fas fa-history me-2"></i>Recent Security Events</h5>
                    <?php if (!empty($recent_events)): ?>
                        <?php foreach ($recent_events as $event): ?>
                            <div class="event-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center mb-1">
                                            <h6 class="mb-0 me-2"><?php echo htmlspecialchars($event['action']); ?></h6>
                                            <span class="status-badge status-<?php echo $event['success'] ? 'success' : 'failed'; ?>">
                                                <?php echo $event['success'] ? 'Success' : 'Failed'; ?>
                                            </span>
                                        </div>
                                        <p class="mb-1">
                                            <i class="fas fa-user me-2"></i><?php echo htmlspecialchars($event['identifier']); ?>
                                        </p>
                                        <?php if (!empty($event['details'])): ?>
                                            <p class="mb-1 text-muted small">
                                                <i class="fas fa-info-circle me-2"></i><?php echo htmlspecialchars($event['details']); ?>
                                            </p>
                                        <?php endif; ?>
                                        <small class="text-muted">
                                            <i class="fas fa-clock me-1"></i>
                                            <?php echo date('M j, Y g:i A', strtotime($event['created_at'])); ?>
                                            <?php if (!empty($event['ip_address'])): ?>
                                                • IP: <?php echo htmlspecialchars($event['ip_address']); ?>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted text-center">No security events recorded</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Suspicious IPs -->
            <div class="col-lg-5">
                <div class="security-card">
                    <h5 class="mb-3"><i class="fas fa-exclamation-triangle me-2"></i>Suspicious IP Addresses</h5>
                    <?php if (!empty($stats['suspicious_ips'])): ?>
                        <?php foreach ($stats['suspicious_ips'] as $ip): ?>
                            <div class="alert alert-warning mb-2">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><?php echo htmlspecialchars($ip['ip_address']); ?></strong>
                                        <br>
                                        <small><?php echo $ip['attempts']; ?> failed attempts</small>
                                    </div>
                                    <button class="btn btn-sm btn-outline-danger" onclick="blockIP('<?php echo htmlspecialchars($ip['ip_address']); ?>')">
                                        <i class="fas fa-ban"></i> Block
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted text-center">No suspicious activity detected</p>
                    <?php endif; ?>
                </div>

                <!-- Security Recommendations -->
                <div class="security-card">
                    <h5 class="mb-3"><i class="fas fa-lightbulb me-2"></i>Security Recommendations</h5>
                    <div class="alert alert-info">
                        <h6 class="mb-2"><i class="fas fa-shield-alt me-2"></i>Enable 2FA</h6>
                        <p class="mb-0 small">
                            <?php $unprotected_users = ($stats['user_security']['total_users'] ?? 0) - ($stats['user_security']['two_factor_users'] ?? 0); ?>
                            <?php echo $unprotected_users; ?> users don't have 2FA enabled
                        </p>
                    </div>
                    <div class="alert alert-warning">
                        <h6 class="mb-2"><i class="fas fa-envelope me-2"></i>Email Verification</h6>
                        <p class="mb-0 small">
                            <?php $unverified_users = ($stats['user_security']['total_users'] ?? 0) - ($stats['user_security']['verified_users'] ?? 0); ?>
                            <?php echo $unverified_users; ?> users haven't verified their email
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- User Security Status -->
        <div class="security-card">
            <h5 class="mb-3"><i class="fas fa-users me-2"></i>User Security Status</h5>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Email</th>
                            <th>Email Verified</th>
                            <th>2FA Enabled</th>
                            <th>Failed Attempts</th>
                            <th>Security Level</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users_security as $user): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="security-indicator <?php 
                                            if ($user['email_verified'] && $user['two_factor_enabled']) echo 'security-high';
                                            elseif ($user['email_verified'] || $user['two_factor_enabled']) echo 'security-medium';
                                            else echo 'security-low';
                                        ?>"></div>
                                        <?php echo htmlspecialchars($user['name']); ?>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <span class="status-badge <?php echo $user['email_verified'] ? 'status-success' : 'status-failed'; ?>">
                                        <?php echo $user['email_verified'] ? 'Verified' : 'Not Verified'; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo $user['two_factor_enabled'] ? 'status-success' : 'status-warning'; ?>">
                                        <?php echo $user['two_factor_enabled'] ? 'Enabled' : 'Disabled'; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($user['failed_attempts'] > 0): ?>
                                        <span class="status-badge status-failed"><?php echo $user['failed_attempts']; ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">0</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                    if ($user['email_verified'] && $user['two_factor_enabled']) {
                                        echo '<span class="threat-level threat-low">High</span>';
                                    } elseif ($user['email_verified'] || $user['two_factor_enabled']) {
                                        echo '<span class="threat-level threat-medium">Medium</span>';
                                    } else {
                                        echo '<span class="threat-level threat-high">Low</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-primary" onclick="viewUser(<?php echo $user['id']; ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <?php if (!$user['email_verified']): ?>
                                            <button class="btn btn-outline-success" onclick="verifyEmail(<?php echo $user['id']; ?>)">
                                                <i class="fas fa-envelope"></i>
                                            </button>
                                        <?php endif; ?>
                                        <?php if ($user['failed_attempts'] > 3): ?>
                                            <button class="btn btn-outline-warning" onclick="resetSecurity(<?php echo $user['id']; ?>)">
                                                <i class="fas fa-shield-alt"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function blockIP(ip) {
    if (confirm(`Block IP address ${ip}? This will prevent all login attempts from this IP.`)) {
        // Implement IP blocking functionality
        alert('IP blocking functionality would be implemented here');
    }
}

function viewUser(userId) {
    window.open(`manage_users.php?view=${userId}`, '_blank');
}

function verifyEmail(userId) {
    if (confirm('Manually verify this user\'s email address?')) {
        // Implement manual email verification
        alert('Email verification functionality would be implemented here');
    }
}

function resetSecurity(userId) {
    if (confirm('Reset security settings for this user? This will clear failed login attempts.')) {
        // Implement security reset
        alert('Security reset functionality would be implemented here');
    }
}
</script>
</body>
</html>
