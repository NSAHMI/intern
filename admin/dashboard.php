<?php
/**
 * Admin Dashboard
 * Internship Management System
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();
require_role('admin');

// Get statistics
$stats = db_fetch("
    SELECT
        (SELECT COUNT(*) FROM users WHERE role = 'student') as students,
        (SELECT COUNT(*) FROM users WHERE role = 'company') as companies,
        (SELECT COUNT(*) FROM internships) as internships,
        (SELECT COUNT(*) FROM applications) as applications,
        (SELECT COUNT(*) FROM applications WHERE status = 'pending') as pending_apps,
        (SELECT COUNT(*) FROM applications WHERE status = 'accepted') as accepted_apps
");

// Get recent users
$recent_users = db_fetch_all("
    SELECT id, name, email, role, created_at, is_active
    FROM users
    ORDER BY created_at DESC
    LIMIT 10
");

// Get recent internships
$recent_internships = db_fetch_all("
    SELECT i.*, cp.company_name
    FROM internships i
    JOIN company_profiles cp ON i.company_id = cp.user_id
    ORDER BY i.created_at DESC
    LIMIT 10
");

// Get recent applications
$recent_applications = db_fetch_all("
    SELECT a.*, i.title as internship_title, u.name as student_name, cp.company_name
    FROM applications a
    JOIN internships i ON a.internship_id = i.id
    JOIN users u ON a.student_id = u.id
    JOIN company_profiles cp ON i.company_id = cp.user_id
    ORDER BY a.applied_at DESC
    LIMIT 10
");

$page_title = 'Admin Dashboard';
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

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--white);
            padding: 1.5rem;
            border-radius: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .stat-card .icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            margin-bottom: 1rem;
        }

        .stat-card .icon.primary { background: rgba(99,102,241,0.1); color: var(--primary); }
        .stat-card .icon.success { background: rgba(34,197,94,0.1); color: var(--success); }
        .stat-card .icon.warning { background: rgba(245,158,11,0.1); color: var(--warning); }
        .stat-card .icon.info { background: rgba(59,130,246,0.1); color: var(--info); }
        .stat-card .icon.danger { background: rgba(239,68,68,0.1); color: var(--danger); }

        .stat-card .value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--dark);
        }

        .stat-card .label {
            color: var(--gray);
            font-size: 0.9rem;
        }

        /* Section */
        .section {
            margin-bottom: 2rem;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--dark);
        }

        .btn {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.875rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
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

        /* Tables */
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

        .badge {
            padding: 0.25rem 0.75rem;
            border-radius: 100px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .badge-student { background: rgba(99,102,241,0.1); color: var(--primary); }
        .badge-company { background: rgba(34,197,94,0.1); color: var(--success); }
        .badge-admin { background: rgba(239,68,68,0.1); color: var(--danger); }

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
        .status-open { background: rgba(34,197,94,0.1); color: var(--success); }
        .status-closed { background: rgba(239,68,68,0.1); color: var(--danger); }
        .status-filled { background: rgba(59,130,246,0.1); color: var(--info); }

        .user-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .user-avatar {
            width: 36px;
            height: 36px;
            background: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 0.9rem;
        }

        /* Grid */
        .grid-2 {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
        }

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

        @media (max-width: 1024px) {
            .grid-2 { grid-template-columns: 1fr; }
        }

        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main-content { margin-left: 0; }
        }
    </style>
</head>
<body>
    <aside class="sidebar">
        <a href="/index.php" class="sidebar-logo">
            <i class="fas fa-briefcase"></i> InternHub
        </a>
        <ul class="sidebar-nav">
            <li><a href="/admin/dashboard.php" class="active"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="/admin/users.php"><i class="fas fa-users"></i> Manage Users</a></li>
            <li><a href="/admin/internships.php"><i class="fas fa-briefcase"></i> Internships</a></li>
            <li><a href="/admin/applications.php"><i class="fas fa-clipboard-list"></i> Applications</a></li>
            <li><a href="/admin/companies.php"><i class="fas fa-building"></i> Companies</a></li>
            <li><a href="/auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title">Admin Dashboard</h1>
        </div>

        <?php if ($flash = get_flash('success')): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo $flash; ?>
            </div>
        <?php endif; ?>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="icon primary"><i class="fas fa-user-graduate"></i></div>
                <div class="value"><?php echo $stats['students'] ?? 0; ?></div>
                <div class="label">Students</div>
            </div>
            <div class="stat-card">
                <div class="icon success"><i class="fas fa-building"></i></div>
                <div class="value"><?php echo $stats['companies'] ?? 0; ?></div>
                <div class="label">Companies</div>
            </div>
            <div class="stat-card">
                <div class="icon info"><i class="fas fa-briefcase"></i></div>
                <div class="value"><?php echo $stats['internships'] ?? 0; ?></div>
                <div class="label">Internships</div>
            </div>
            <div class="stat-card">
                <div class="icon warning"><i class="fas fa-file-alt"></i></div>
                <div class="value"><?php echo $stats['applications'] ?? 0; ?></div>
                <div class="label">Applications</div>
            </div>
            <div class="stat-card">
                <div class="icon danger"><i class="fas fa-clock"></i></div>
                <div class="value"><?php echo $stats['pending_apps'] ?? 0; ?></div>
                <div class="label">Pending</div>
            </div>
            <div class="stat-card">
                <div class="icon success"><i class="fas fa-check-circle"></i></div>
                <div class="value"><?php echo $stats['accepted_apps'] ?? 0; ?></div>
                <div class="label">Accepted</div>
            </div>
        </div>

        <div class="grid-2">
            <!-- Recent Users -->
            <div class="section">
                <div class="section-header">
                    <h2 class="section-title">Recent Users</h2>
                    <a href="/admin/users.php" class="btn btn-outline">View All</a>
                </div>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Role</th>
                                <th>Joined</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_users as $user): ?>
                                <tr>
                                    <td>
                                        <div class="user-info">
                                            <div class="user-avatar">
                                                <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                                            </div>
                                            <div>
                                                <strong><?php echo htmlspecialchars($user['name']); ?></strong>
                                                <div style="font-size: 0.8rem; color: var(--gray);">
                                                    <?php echo htmlspecialchars($user['email']); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo $user['role']; ?>">
                                            <?php echo ucfirst($user['role']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo format_date($user['created_at']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Recent Internships -->
            <div class="section">
                <div class="section-header">
                    <h2 class="section-title">Recent Internships</h2>
                    <a href="/admin/internships.php" class="btn btn-outline">View All</a>
                </div>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Position</th>
                                <th>Company</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_internships as $int): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($int['title']); ?></strong>
                                        <div style="font-size: 0.8rem; color: var(--gray);">
                                            <?php echo htmlspecialchars($int['location'] ?? 'Remote'); ?>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($int['company_name']); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $int['status']; ?>">
                                            <?php echo ucfirst($int['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Recent Applications -->
        <div class="section">
            <div class="section-header">
                <h2 class="section-title">Recent Applications</h2>
                <a href="/admin/applications.php" class="btn btn-outline">View All</a>
            </div>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Position</th>
                            <th>Company</th>
                            <th>Applied</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_applications as $app): ?>
                            <tr>
                                <td>
                                    <div class="user-info">
                                        <div class="user-avatar">
                                            <?php echo strtoupper(substr($app['student_name'], 0, 1)); ?>
                                        </div>
                                        <strong><?php echo htmlspecialchars($app['student_name']); ?></strong>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($app['internship_title']); ?></td>
                                <td><?php echo htmlspecialchars($app['company_name']); ?></td>
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
            </div>
        </div>
    </main>
</body>
</html>
