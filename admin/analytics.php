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

// Get analytics data
$analytics = [];

// User statistics
$user_stats = [];
$stmt = $conn->prepare('SELECT role, COUNT(*) as count FROM users GROUP BY role');
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $user_stats[$row['role']] = $row['count'];
}
$stmt->close();

// Department statistics
$dept_stats = [];
$stmt = $conn->prepare('
    SELECT d.name, COUNT(i.id) as internship_count 
    FROM departments d 
    LEFT JOIN internships i ON d.id = i.department_id 
    GROUP BY d.id, d.name 
    ORDER BY internship_count DESC
');
$stmt->execute();
$result = $stmt->get_result();
$dept_stats = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Application statistics
$app_stats = [];
$stmt = $conn->prepare('
    SELECT status, COUNT(*) as count 
    FROM applications 
    GROUP BY status
');
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $app_stats[$row['status']] = $row['count'];
}
$stmt->close();

// Monthly registration trends
$registration_trends = [];
$stmt = $conn->prepare('
    SELECT DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as count 
    FROM users 
    WHERE created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(created_at, "%Y-%m")
    ORDER BY month
');
$stmt->execute();
$result = $stmt->get_result();
$registration_trends = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Top companies by postings
$top_companies = [];
$stmt = $conn->prepare('
    SELECT u.name, COUNT(i.id) as posting_count 
    FROM users u 
    JOIN internships i ON u.id = i.company_id 
    WHERE u.role = "company"
    GROUP BY u.id, u.name 
    ORDER BY posting_count DESC 
    LIMIT 10
');
$stmt->execute();
$result = $stmt->get_result();
$top_companies = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Popular internships by applications
$popular_internships = [];
$stmt = $conn->prepare('
    SELECT i.title, u.name as company_name, COUNT(a.id) as application_count 
    FROM internships i 
    JOIN users u ON i.company_id = u.id 
    LEFT JOIN applications a ON i.id = a.internship_id 
    GROUP BY i.id, i.title, u.name 
    ORDER BY application_count DESC 
    LIMIT 10
');
$stmt->execute();
$result = $stmt->get_result();
$popular_internships = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Recent activity
$recent_activity = [];
$stmt = $conn->prepare('
    SELECT 
        ua.activity_type,
        ua.resource_type,
        u.name as user_name,
        u.role as user_role,
        ua.created_at
    FROM user_activity ua 
    JOIN users u ON ua.user_id = u.id 
    ORDER BY ua.created_at DESC 
    LIMIT 20
');
$stmt->execute();
$result = $stmt->get_result();
$recent_activity = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// System health metrics
$system_health = [
    'total_users' => array_sum($user_stats),
    'total_internships' => 0,
    'total_applications' => array_sum($app_stats),
    'active_companies' => $user_stats['company'] ?? 0,
    'active_students' => $user_stats['student'] ?? 0
];

$stmt = $conn->prepare('SELECT COUNT(*) as count FROM internships WHERE expiration_date >= CURDATE()');
$stmt->execute();
$system_health['active_internships'] = $stmt->get_result()->fetch_assoc()['count'];
$stmt->close();

// Calculate conversion rates
$system_health['application_rate'] = $system_health['active_internships'] > 0 ? 
    round(($system_health['total_applications'] / $system_health['active_internships']) * 100, 1) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Analytics Dashboard - Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            height: 100%;
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
        
        .chart-container {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: var(--card-shadow);
            border: 1px solid #e5e7eb;
            margin-bottom: 2rem;
        }
        
        .activity-item {
            display: flex;
            align-items: center;
            padding: 0.75rem;
            border-bottom: 1px solid #f3f4f6;
            transition: all 0.3s ease;
        }
        
        .activity-item:hover {
            background: #f9fafb;
        }
        
        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            font-size: 0.875rem;
        }
        
        .top-list-item {
            display: flex;
            align-items: center;
            padding: 0.75rem;
            border-bottom: 1px solid #f3f4f6;
        }
        
        .rank-badge {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            margin-right: 1rem;
            font-size: 0.875rem;
        }
        
        .rank-1 { background: linear-gradient(135deg, #ffd700, #ffed4e); color: #854d0e; }
        .rank-2 { background: linear-gradient(135deg, #c0c0c0, #e8e8e8); color: #374151; }
        .rank-3 { background: linear-gradient(135deg, #cd7f32, #e4a853); color: #7c2d12; }
        .rank-other { background: #f3f4f6; color: #6b7280; }
        
        .metric-highlight {
            background: linear-gradient(135deg, #f0f9ff, #e0f2fe);
            border: 1px solid #bae6fd;
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        
        .metric-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .metric-label {
            color: #6b7280;
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
<div class="main-container">
    <div class="header-section">
        <div class="header-content">
            <h1 class="header-title">Analytics Dashboard 📊</h1>
            <p class="header-subtitle">Comprehensive insights and platform metrics</p>
            
            <div class="nav-buttons">
                <a href="dashboard.php" class="btn-custom btn-primary-custom">
                    <i class="fas fa-arrow-left"></i> Admin Dashboard
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
        <!-- Key Metrics -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stat-card">
                    <div class="stat-icon text-primary">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-number text-primary"><?php echo $system_health['total_users']; ?></div>
                    <div class="stat-label">Total Users</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stat-card">
                    <div class="stat-icon text-success">
                        <i class="fas fa-briefcase"></i>
                    </div>
                    <div class="stat-number text-success"><?php echo $system_health['active_internships']; ?></div>
                    <div class="stat-label">Active Internships</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stat-card">
                    <div class="stat-icon text-warning">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <div class="stat-number text-warning"><?php echo $system_health['total_applications']; ?></div>
                    <div class="stat-label">Total Applications</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stat-card">
                    <div class="stat-icon text-info">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-number text-info"><?php echo $system_health['application_rate']; ?>%</div>
                    <div class="stat-label">Application Rate</div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row mb-4">
            <div class="col-lg-8">
                <div class="chart-container">
                    <h5 class="mb-3"><i class="fas fa-chart-area me-2"></i>Registration Trends</h5>
                    <canvas id="registrationChart" height="100"></canvas>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="chart-container">
                    <h5 class="mb-3"><i class="fas fa-chart-pie me-2"></i>User Distribution</h5>
                    <canvas id="userDistributionChart" height="200"></canvas>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-lg-6">
                <div class="chart-container">
                    <h5 class="mb-3"><i class="fas fa-chart-bar me-2"></i>Department Distribution</h5>
                    <canvas id="departmentChart" height="150"></canvas>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="chart-container">
                    <h5 class="mb-3"><i class="fas fa-chart-donut me-2"></i>Application Status</h5>
                    <canvas id="applicationStatusChart" height="150"></canvas>
                </div>
            </div>
        </div>

        <!-- Top Lists -->
        <div class="row mb-4">
            <div class="col-lg-6">
                <div class="chart-container">
                    <h5 class="mb-3"><i class="fas fa-trophy me-2"></i>Top Companies by Postings</h5>
                    <?php if (!empty($top_companies)): ?>
                        <?php foreach ($top_companies as $index => $company): ?>
                            <div class="top-list-item">
                                <div class="rank-badge rank-<?php echo ($index < 3) ? ($index + 1) : 'other'; ?>">
                                    <?php echo $index + 1; ?>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="fw-bold"><?php echo htmlspecialchars($company['name']); ?></div>
                                    <small class="text-muted"><?php echo $company['posting_count']; ?> postings</small>
                                </div>
                                <div class="text-primary">
                                    <i class="fas fa-briefcase"></i>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted text-center">No company data available</p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="chart-container">
                    <h5 class="mb-3"><i class="fas fa-fire me-2"></i>Popular Internships</h5>
                    <?php if (!empty($popular_internships)): ?>
                        <?php foreach ($popular_internships as $index => $internship): ?>
                            <div class="top-list-item">
                                <div class="rank-badge rank-<?php echo ($index < 3) ? ($index + 1) : 'other'; ?>">
                                    <?php echo $index + 1; ?>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="fw-bold"><?php echo htmlspecialchars($internship['title']); ?></div>
                                    <small class="text-muted"><?php echo htmlspecialchars($internship['company_name']); ?> • <?php echo $internship['application_count']; ?> applications</small>
                                </div>
                                <div class="text-success">
                                    <i class="fas fa-users"></i>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted text-center">No internship data available</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="row">
            <div class="col-lg-8">
                <div class="chart-container">
                    <h5 class="mb-3"><i class="fas fa-clock me-2"></i>Recent Activity</h5>
                    <?php if (!empty($recent_activity)): ?>
                        <?php foreach ($recent_activity as $activity): ?>
                            <div class="activity-item">
                                <div class="activity-icon bg-<?php echo getActivityColor($activity['activity_type']); ?> text-white">
                                    <i class="fas fa-<?php echo getActivityIcon($activity['activity_type']); ?>"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="fw-bold"><?php echo htmlspecialchars($activity['user_name']); ?></div>
                                    <small class="text-muted">
                                        <?php echo formatActivityDescription($activity); ?> • 
                                        <?php echo timeAgo($activity['created_at']); ?>
                                    </small>
                                </div>
                                <div class="text-muted small">
                                    <?php echo date('M j, g:i A', strtotime($activity['created_at'])); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted text-center">No recent activity</p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="chart-container">
                    <h5 class="mb-3"><i class="fas fa-heartbeat me-2"></i>System Health</h5>
                    
                    <div class="metric-highlight">
                        <div class="metric-value"><?php echo $system_health['active_students']; ?></div>
                        <div class="metric-label">Active Students</div>
                    </div>
                    
                    <div class="metric-highlight">
                        <div class="metric-value"><?php echo $system_health['active_companies']; ?></div>
                        <div class="metric-label">Active Companies</div>
                    </div>
                    
                    <div class="metric-highlight">
                        <div class="metric-value"><?php echo round(($system_health['active_students'] / max($system_health['total_users'], 1)) * 100, 1); ?>%</div>
                        <div class="metric-label">Student Engagement</div>
                    </div>
                    
                    <div class="metric-highlight">
                        <div class="metric-value"><?php echo round(($system_health['active_companies'] / max(($user_stats['company'] ?? 1), 1)) * 100, 1); ?>%</div>
                        <div class="metric-label">Company Activity</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
function getActivityColor($activity) {
    $colors = [
        'login' => 'primary',
        'register' => 'success',
        'apply' => 'warning',
        'post' => 'info',
        'view' => 'secondary'
    ];
    return $colors[$activity] ?? 'secondary';
}

function getActivityIcon($activity) {
    $icons = [
        'login' => 'sign-in-alt',
        'register' => 'user-plus',
        'apply' => 'paper-plane',
        'post' => 'briefcase',
        'view' => 'eye'
    ];
    return $icons[$activity] ?? 'circle';
}

function formatActivityDescription($activity) {
    $descriptions = [
        'login' => 'Logged in',
        'register' => 'Registered new account',
        'apply' => 'Applied for internship',
        'post' => 'Posted new internship',
        'view' => 'Viewed internship'
    ];
    return $descriptions[$activity['activity_type']] ?? 'Performed action';
}

function timeAgo($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . ' minutes ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
    if ($diff < 604800) return floor($diff / 86400) . ' days ago';
    return date('M j', $time);
}
?>

<script>
// Registration Trends Chart
const registrationCtx = document.getElementById('registrationChart').getContext('2d');
new Chart(registrationCtx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode(array_column($registration_trends, 'month')); ?>,
        datasets: [{
            label: 'New Registrations',
            data: <?php echo json_encode(array_column($registration_trends, 'count')); ?>,
            borderColor: '#6366f1',
            backgroundColor: 'rgba(99, 102, 241, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

// User Distribution Chart
const userDistCtx = document.getElementById('userDistributionChart').getContext('2d');
new Chart(userDistCtx, {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode(array_keys($user_stats)); ?>,
        datasets: [{
            data: <?php echo json_encode(array_values($user_stats)); ?>,
            backgroundColor: ['#6366f1', '#10b981', '#f59e0b']
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false
    }
});

// Department Chart
const deptCtx = document.getElementById('departmentChart').getContext('2d');
new Chart(deptCtx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode(array_column($dept_stats, 'name')); ?>,
        datasets: [{
            label: 'Internships',
            data: <?php echo json_encode(array_column($dept_stats, 'internship_count')); ?>,
            backgroundColor: '#6366f1'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

// Application Status Chart
const appStatusCtx = document.getElementById('applicationStatusChart').getContext('2d');
new Chart(appStatusCtx, {
    type: 'pie',
    data: {
        labels: <?php echo json_encode(array_keys($app_stats)); ?>,
        datasets: [{
            data: <?php echo json_encode(array_values($app_stats)); ?>,
            backgroundColor: ['#f59e0b', '#10b981', '#ef4444']
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false
    }
});
</script>
</body>
</html>
