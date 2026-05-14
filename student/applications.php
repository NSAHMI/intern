<?php
/**
 * My Applications
 * Internship Management System
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();
require_role('student');

// Get filter
$status_filter = $_GET['status'] ?? '';

// Build query
$where = "a.student_id = ?";
$params = [$_SESSION['user_id']];

if ($status_filter) {
    $where .= " AND a.status = ?";
    $params[] = $status_filter;
}

$applications = db_fetch_all("
    SELECT a.*, i.title as internship_title, i.location, i.type, i.duration, i.stipend,
           cp.company_name, cp.industry
    FROM applications a
    JOIN internships i ON a.internship_id = i.id
    JOIN company_profiles cp ON i.company_id = cp.user_id
    WHERE $where
    ORDER BY a.applied_at DESC
", $params);

// Get counts by status
$status_counts = db_fetch("
    SELECT
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'reviewed' THEN 1 ELSE 0 END) as reviewed,
        SUM(CASE WHEN status = 'shortlisted' THEN 1 ELSE 0 END) as shortlisted,
        SUM(CASE WHEN status = 'accepted' THEN 1 ELSE 0 END) as accepted,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
    FROM applications
    WHERE student_id = ?
", [$_SESSION['user_id']]);

$page_title = 'My Applications';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - InternHub</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --success: #22c55e;
            --danger: #ef4444;
            --warning: #f59e0b;
            --info: #3b82f6;
            --gray: #6b7280;
            --dark: #1f2937;
            --light: #f3f4f6;
            --white: #ffffff;
            --border: #e5e7eb;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--light);
            min-height: 100vh;
        }

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 260px;
            height: 100vh;
            background: var(--dark);
            color: var(--white);
            padding: 1.5rem;
            overflow-y: auto;
        }

        .sidebar-logo {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--white);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 2rem;
        }

        .sidebar-logo i { color: var(--primary); }

        .sidebar-nav { list-style: none; }
        .sidebar-nav li { margin-bottom: 0.5rem; }
        .sidebar-nav a {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.875rem 1rem;
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            border-radius: 10px;
            transition: all 0.2s;
        }
        .sidebar-nav a:hover, .sidebar-nav a.active {
            background: var(--primary);
            color: var(--white);
        }
        .sidebar-nav a i { width: 20px; }

        .main-content {
            margin-left: 260px;
            padding: 2rem;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .page-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--dark);
        }

        /* Stats */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--white);
            padding: 1rem;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            color: inherit;
            border: 2px solid transparent;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .stat-card.active {
            border-color: var(--primary);
        }

        .stat-card .value {
            font-size: 1.75rem;
            font-weight: 700;
        }

        .stat-card .label {
            font-size: 0.8rem;
            color: var(--gray);
            margin-top: 0.25rem;
        }

        .stat-card.pending .value { color: var(--warning); }
        .stat-card.reviewed .value { color: var(--info); }
        .stat-card.shortlisted .value { color: #8b5cf6; }
        .stat-card.accepted .value { color: var(--success); }
        .stat-card.rejected .value { color: var(--danger); }

        /* Alert */
        .alert {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .alert-success {
            background: rgba(34,197,94,0.1);
            color: #15803d;
        }

        /* Table */
        .table-container {
            background: var(--white);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th,
        .table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }

        .table th {
            background: var(--light);
            font-weight: 600;
            color: var(--gray);
            font-size: 0.85rem;
            text-transform: uppercase;
        }

        .table tr:last-child td {
            border-bottom: none;
        }

        .table tr:hover td {
            background: var(--light);
        }

        .job-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .company-logo {
            width: 40px;
            height: 40px;
            background: var(--primary);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 1rem;
        }

        .job-title {
            font-weight: 600;
            color: var(--dark);
        }

        .job-company {
            font-size: 0.85rem;
            color: var(--gray);
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 100px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .status-pending { background: rgba(245,158,11,0.1); color: var(--warning); }
        .status-reviewed { background: rgba(59,130,246,0.1); color: var(--info); }
        .status-shortlisted { background: rgba(139,92,246,0.1); color: #8b5cf6; }
        .status-accepted { background: rgba(34,197,94,0.1); color: var(--success); }
        .status-rejected { background: rgba(239,68,68,0.1); color: var(--danger); }

        .meta-info {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            font-size: 0.85rem;
            color: var(--gray);
        }

        .meta-info span {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .btn {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.85rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-outline {
            background: transparent;
            border: 2px solid var(--border);
            color: var(--gray);
        }

        .btn-outline:hover {
            border-color: var(--primary);
            color: var(--primary);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
        }

        .empty-state i {
            font-size: 4rem;
            color: var(--border);
            margin-bottom: 1rem;
        }

        .empty-state h3 {
            color: var(--dark);
            margin-bottom: 0.5rem;
        }

        .empty-state p {
            color: var(--gray);
            margin-bottom: 1.5rem;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
        }

        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main-content { margin-left: 0; }
            .table { font-size: 0.9rem; }
            .table th, .table td { padding: 0.75rem; }
        }
    </style>
</head>
<body>
    <aside class="sidebar">
        <a href="/index.php" class="sidebar-logo">
            <i class="fas fa-briefcase"></i> InternHub
        </a>
        <ul class="sidebar-nav">
            <li><a href="/student/dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="/student/browse.php"><i class="fas fa-search"></i> Browse Internships</a></li>
            <li><a href="/student/applications.php" class="active"><i class="fas fa-clipboard-list"></i> My Applications</a></li>
            <li><a href="/student/profile.php"><i class="fas fa-user"></i> My Profile</a></li>
            <li><a href="/student/messages.php"><i class="fas fa-envelope"></i> Messages</a></li>
            <li><a href="/auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title">My Applications</h1>
            <a href="/student/browse.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Apply to More
            </a>
        </div>

        <?php if ($flash = get_flash('success')): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo $flash; ?>
            </div>
        <?php endif; ?>

        <!-- Status Filters -->
        <div class="stats-grid">
            <a href="/student/applications.php" class="stat-card <?php echo !$status_filter ? 'active' : ''; ?>">
                <div class="value" style="color: var(--dark);"><?php echo $status_counts['total'] ?? 0; ?></div>
                <div class="label">All</div>
            </a>
            <a href="/student/applications.php?status=pending" class="stat-card pending <?php echo $status_filter === 'pending' ? 'active' : ''; ?>">
                <div class="value"><?php echo $status_counts['pending'] ?? 0; ?></div>
                <div class="label">Pending</div>
            </a>
            <a href="/student/applications.php?status=reviewed" class="stat-card reviewed <?php echo $status_filter === 'reviewed' ? 'active' : ''; ?>">
                <div class="value"><?php echo $status_counts['reviewed'] ?? 0; ?></div>
                <div class="label">Reviewed</div>
            </a>
            <a href="/student/applications.php?status=shortlisted" class="stat-card shortlisted <?php echo $status_filter === 'shortlisted' ? 'active' : ''; ?>">
                <div class="value"><?php echo $status_counts['shortlisted'] ?? 0; ?></div>
                <div class="label">Shortlisted</div>
            </a>
            <a href="/student/applications.php?status=accepted" class="stat-card accepted <?php echo $status_filter === 'accepted' ? 'active' : ''; ?>">
                <div class="value"><?php echo $status_counts['accepted'] ?? 0; ?></div>
                <div class="label">Accepted</div>
            </a>
            <a href="/student/applications.php?status=rejected" class="stat-card rejected <?php echo $status_filter === 'rejected' ? 'active' : ''; ?>">
                <div class="value"><?php echo $status_counts['rejected'] ?? 0; ?></div>
                <div class="label">Rejected</div>
            </a>
        </div>

        <!-- Applications Table -->
        <div class="table-container">
            <?php if (empty($applications)): ?>
                <div class="empty-state">
                    <i class="fas fa-clipboard-list"></i>
                    <h3>No applications <?php echo $status_filter ? 'with this status' : 'yet'; ?></h3>
                    <p>
                        <?php if ($status_filter): ?>
                            Try viewing all applications or a different status
                        <?php else: ?>
                            Start applying to internships to track your progress here
                        <?php endif; ?>
                    </p>
                    <a href="/student/browse.php" class="btn btn-primary">
                        <i class="fas fa-search"></i> Browse Internships
                    </a>
                </div>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Position</th>
                            <th>Details</th>
                            <th>Applied</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($applications as $app): ?>
                            <tr>
                                <td>
                                    <div class="job-info">
                                        <div class="company-logo">
                                            <?php echo strtoupper(substr($app['company_name'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <div class="job-title"><?php echo htmlspecialchars($app['internship_title']); ?></div>
                                            <div class="job-company"><?php echo htmlspecialchars($app['company_name']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="meta-info">
                                        <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($app['location'] ?? 'Remote'); ?></span>
                                        <span><i class="fas fa-clock"></i> <?php echo htmlspecialchars($app['duration'] ?? 'Flexible'); ?></span>
                                        <span><i class="fas fa-laptop-house"></i> <?php echo ucfirst($app['type']); ?></span>
                                    </div>
                                </td>
                                <td><?php echo format_date($app['applied_at']); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $app['status']; ?>">
                                        <?php echo ucfirst($app['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
