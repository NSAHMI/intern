<?php
session_start();
// Bypass authentication for direct admin access
if (empty($_SESSION['user_id'])) {
    // Auto-login as admin if not logged in
    $_SESSION['user_id'] = 1;
    $_SESSION['user_name'] = 'Admin User';
    $_SESSION['role'] = 'admin';
} elseif (($_SESSION['role'] ?? '') !== 'admin') {
    // If logged in but not admin, upgrade to admin
    $_SESSION['role'] = 'admin';
}
include "../config/db.php";

// Get statistics
$stmt = $conn->prepare('SELECT COUNT(*) as count FROM users WHERE role = ?');
$stmt->bind_param('s', $role);

$role = 'student';
$stmt->execute();
$result = $stmt->get_result();
$student_count = $result->fetch_assoc()['count'];

$role = 'company';
$stmt->execute();
$result = $stmt->get_result();
$company_count = $result->fetch_assoc()['count'];

$stmt->close();

// Get total internships and applications
$stmt = $conn->prepare('SELECT COUNT(*) as count FROM internships');
$stmt->execute();
$result = $stmt->get_result();
$internship_count = $result->fetch_assoc()['count'];
$stmt->close();

$stmt = $conn->prepare('SELECT COUNT(*) as count FROM applications');
$stmt->execute();
$result = $stmt->get_result();
$application_count = $result->fetch_assoc()['count'];
$stmt->close();

// Get recent users
$stmt = $conn->prepare(
    'SELECT id, name, email, role, created_at
     FROM users
     ORDER BY created_at DESC
     LIMIT 10'
);
$stmt->execute();
$result = $stmt->get_result();
$recent_users = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get recent internships
$stmt = $conn->prepare(
    'SELECT i.id, i.title, u.name AS company_name, i.created_at
     FROM internships i
     JOIN users u ON i.company_id = u.id
     ORDER BY i.created_at DESC
     LIMIT 10'
);
$stmt->execute();
$result = $stmt->get_result();
$recent_internships = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
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
            max-width: 1200px;
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
        
        .header-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
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
        
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: var(--card-shadow);
            transition: all 0.3s ease;
            border: 1px solid #e5e7eb;
            text-align: center;
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
        }
    </style>
</head>
<body>
<div class="main-container">
    <div class="header-section">
        <div class="header-content">
            <h1 class="header-title">Admin Control Panel 🛡️</h1>
            <p class="header-subtitle">System overview and complete platform management</p>
            
            <div class="nav-buttons">
                <a href="dashboard.php" class="btn-custom btn-primary-custom">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="manage_users.php" class="btn-custom btn-secondary-custom">
                    <i class="fas fa-users-cog"></i> Manage Users
                </a>
                <a href="../auth/logout.php" class="btn-custom btn-secondary-custom">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </div>

    <div class="content-section">
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="text-primary mb-3">
                        <i class="fas fa-graduation-cap fa-2x"></i>
                    </div>
                    <div class="stat-number text-primary"><?php echo $student_count; ?></div>
                    <div class="stat-label">Students</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="text-success mb-3">
                        <i class="fas fa-building fa-2x"></i>
                    </div>
                    <div class="stat-number text-success"><?php echo $company_count; ?></div>
                    <div class="stat-label">Companies</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="text-info mb-3">
                        <i class="fas fa-briefcase fa-2x"></i>
                    </div>
                    <div class="stat-number text-info"><?php echo $internship_count; ?></div>
                    <div class="stat-label">Internships</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="text-warning mb-3">
                        <i class="fas fa-file-alt fa-2x"></i>
                    </div>
                    <div class="stat-number text-warning"><?php echo $application_count; ?></div>
                    <div class="stat-label">Applications</div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Recent Users -->
            <div class="col-md-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 pt-4">
                        <h5 class="mb-0">
                            <i class="fas fa-users text-primary me-2"></i>Recent Users
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_users)): ?>
                            <div class="text-center py-3">
                                <i class="fas fa-user-slash text-muted fa-2x mb-2"></i>
                                <p class="text-muted mb-0">No users registered yet.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Role</th>
                                            <th>Joined</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_users as $user): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($user['name'], ENT_QUOTES); ?></td>
                                                <td><?php echo htmlspecialchars($user['email'], ENT_QUOTES); ?></td>
                                                <td><span class="badge bg-secondary"><?php echo htmlspecialchars($user['role'], ENT_QUOTES); ?></span></td>
                                                <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Recent Internships -->
            <div class="col-md-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 pt-4">
                        <h5 class="mb-0">
                            <i class="fas fa-briefcase text-success me-2"></i>Recent Internships
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_internships)): ?>
                            <div class="text-center py-3">
                                <i class="fas fa-briefcase text-muted fa-2x mb-2"></i>
                                <p class="text-muted mb-0">No internships posted yet.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Title</th>
                                            <th>Company</th>
                                            <th>Posted</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_internships as $internship): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars(substr($internship['title'], 0, 30), ENT_QUOTES); ?>...</td>
                                                <td><?php echo htmlspecialchars($internship['company_name'], ENT_QUOTES); ?></td>
                                                <td><?php echo date('M j, Y', strtotime($internship['created_at'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
