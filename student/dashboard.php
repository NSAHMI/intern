<?php
/**
 * Student Dashboard
 * Internship Management System
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();
require_role('student');

// Get student profile
$profile = db_fetch("SELECT * FROM student_profiles WHERE user_id = ?", [$_SESSION['user_id']]);

// Get application statistics
$stats = db_fetch("
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

// Get available internships
$internships = db_fetch_all("
    SELECT i.*, cp.company_name, cp.logo_path, cp.industry, cp.location as company_location
    FROM internships i
    JOIN company_profiles cp ON i.company_id = cp.user_id
    WHERE i.status = 'open'
    AND (i.deadline IS NULL OR i.deadline >= CURDATE())
    ORDER BY i.created_at DESC
    LIMIT 6
");

// Get recent applications
$recent_applications = db_fetch_all("
    SELECT a.*, i.title as internship_title, cp.company_name
    FROM applications a
    JOIN internships i ON a.internship_id = i.id
    JOIN company_profiles cp ON i.company_id = cp.user_id
    WHERE a.student_id = ?
    ORDER BY a.applied_at DESC
    LIMIT 5
", [$_SESSION['user_id']]);

$page_title = 'Student Dashboard';
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
            --secondary: #f59e0b;
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

        /* Sidebar */
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

        .sidebar-logo i {
            color: var(--primary);
        }

        .sidebar-nav {
            list-style: none;
        }

        .sidebar-nav li {
            margin-bottom: 0.5rem;
        }

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

        .sidebar-nav a:hover,
        .sidebar-nav a.active {
            background: var(--primary);
            color: var(--white);
        }

        .sidebar-nav a i {
            width: 20px;
        }

        /* Main Content */
        .main-content {
            margin-left: 260px;
            padding: 2rem;
        }

        /* Header */
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

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-avatar {
            width: 45px;
            height: 45px;
            background: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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

        .btn-primary:hover {
            background: var(--primary-dark);
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

        /* Cards Grid */
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 1.5rem;
        }

        /* Internship Card */
        .internship-card {
            background: var(--white);
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            transition: all 0.2s;
        }

        .internship-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }

        .internship-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
        }

        .company-logo {
            width: 50px;
            height: 50px;
            background: var(--primary);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 1.25rem;
        }

        .badge {
            padding: 0.25rem 0.75rem;
            border-radius: 100px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .badge-open { background: rgba(34,197,94,0.1); color: var(--success); }
        .badge-remote { background: rgba(99,102,241,0.1); color: var(--primary); }
        .badge-onsite { background: rgba(245,158,11,0.1); color: var(--warning); }
        .badge-hybrid { background: rgba(59,130,246,0.1); color: var(--info); }

        .internship-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 0.25rem;
        }

        .internship-company {
            color: var(--gray);
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .internship-meta {
            display: flex;
            gap: 1rem;
            color: var(--gray);
            font-size: 0.85rem;
            margin-bottom: 1rem;
        }

        .internship-meta span {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        /* Applications Table */
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

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: var(--gray);
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: var(--border);
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

        .alert-warning {
            background: rgba(245,158,11,0.1);
            color: #b45309;
        }

        /* Profile Completion */
        .profile-completion {
            background: var(--white);
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .progress-bar {
            height: 8px;
            background: var(--light);
            border-radius: 4px;
            overflow: hidden;
            margin: 1rem 0;
        }

        .progress-fill {
            height: 100%;
            background: var(--primary);
            border-radius: 4px;
            transition: width 0.3s;
        }

        @media (max-width: 768px) {
            .sidebar {
                display: none;
            }
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar">
        <a href="/index.php" class="sidebar-logo">
            <i class="fas fa-briefcase"></i> InternHub
        </a>

        <ul class="sidebar-nav">
            <li><a href="/student/dashboard.php" class="active"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="/student/browse.php"><i class="fas fa-search"></i> Browse Internships</a></li>
            <li><a href="/student/applications.php"><i class="fas fa-clipboard-list"></i> My Applications</a></li>
            <li><a href="/student/profile.php"><i class="fas fa-user"></i> My Profile</a></li>
            <li><a href="/student/messages.php"><i class="fas fa-envelope"></i> Messages</a></li>
            <li><a href="/auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title">Welcome back, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h1>
            <div class="user-info">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($_SESSION['user_name'], 0, 1)); ?>
                </div>
            </div>
        </div>

        <?php if ($flash = get_flash('success')): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo $flash; ?>
            </div>
        <?php endif; ?>

        <!-- Profile Completion Warning -->
        <?php
        $completion = 0;
        if ($profile) {
            if ($profile['university']) $completion += 20;
            if ($profile['course']) $completion += 20;
            if ($profile['skills']) $completion += 20;
            if ($profile['bio']) $completion += 20;
            if ($profile['resume_path']) $completion += 20;
        }
        if ($completion < 100):
        ?>
        <div class="profile-completion">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <strong>Complete Your Profile</strong>
                    <p style="color: var(--gray); font-size: 0.9rem; margin-top: 0.25rem;">
                        A complete profile increases your chances of getting selected
                    </p>
                </div>
                <a href="/student/profile.php" class="btn btn-primary">
                    <i class="fas fa-edit"></i> Update Profile
                </a>
            </div>
            <div class="progress-bar">
                <div class="progress-fill" style="width: <?php echo $completion; ?>%;"></div>
            </div>
            <span style="color: var(--gray); font-size: 0.85rem;"><?php echo $completion; ?>% complete</span>
        </div>
        <?php endif; ?>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="icon primary"><i class="fas fa-paper-plane"></i></div>
                <div class="value"><?php echo $stats['total'] ?? 0; ?></div>
                <div class="label">Total Applications</div>
            </div>
            <div class="stat-card">
                <div class="icon warning"><i class="fas fa-clock"></i></div>
                <div class="value"><?php echo $stats['pending'] ?? 0; ?></div>
                <div class="label">Pending Review</div>
            </div>
            <div class="stat-card">
                <div class="icon info"><i class="fas fa-star"></i></div>
                <div class="value"><?php echo $stats['shortlisted'] ?? 0; ?></div>
                <div class="label">Shortlisted</div>
            </div>
            <div class="stat-card">
                <div class="icon success"><i class="fas fa-check-circle"></i></div>
                <div class="value"><?php echo $stats['accepted'] ?? 0; ?></div>
                <div class="label">Accepted</div>
            </div>
        </div>

        <!-- Recent Applications -->
        <div class="section">
            <div class="section-header">
                <h2 class="section-title">Recent Applications</h2>
                <a href="/student/applications.php" class="btn btn-outline">View All</a>
            </div>

            <?php if (empty($recent_applications)): ?>
                <div class="table-container">
                    <div class="empty-state">
                        <i class="fas fa-clipboard-list"></i>
                        <h3>No applications yet</h3>
                        <p>Start applying to internships to see them here</p>
                    </div>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Position</th>
                                <th>Company</th>
                                <th>Applied</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_applications as $app): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($app['internship_title']); ?></strong></td>
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
            <?php endif; ?>
        </div>

        <!-- Available Internships -->
        <div class="section">
            <div class="section-header">
                <h2 class="section-title">Available Internships</h2>
                <a href="/student/browse.php" class="btn btn-outline">Browse All</a>
            </div>

            <?php if (empty($internships)): ?>
                <div class="table-container">
                    <div class="empty-state">
                        <i class="fas fa-briefcase"></i>
                        <h3>No internships available</h3>
                        <p>Check back later for new opportunities</p>
                    </div>
                </div>
            <?php else: ?>
                <div class="cards-grid">
                    <?php foreach ($internships as $internship): ?>
                        <div class="internship-card">
                            <div class="internship-header">
                                <div class="company-logo">
                                    <?php echo strtoupper(substr($internship['company_name'], 0, 1)); ?>
                                </div>
                                <span class="badge badge-<?php echo $internship['type']; ?>">
                                    <?php echo ucfirst($internship['type']); ?>
                                </span>
                            </div>
                            <h3 class="internship-title"><?php echo htmlspecialchars($internship['title']); ?></h3>
                            <p class="internship-company">
                                <i class="fas fa-building"></i> <?php echo htmlspecialchars($internship['company_name']); ?>
                            </p>
                            <div class="internship-meta">
                                <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($internship['location'] ?? 'Remote'); ?></span>
                                <span><i class="fas fa-clock"></i> <?php echo htmlspecialchars($internship['duration'] ?? 'Flexible'); ?></span>
                            </div>
                            <?php if ($internship['stipend']): ?>
                                <div class="internship-meta">
                                    <span><i class="fas fa-dollar-sign"></i> <?php echo htmlspecialchars($internship['stipend']); ?></span>
                                </div>
                            <?php endif; ?>
                            <a href="/student/apply.php?id=<?php echo $internship['id']; ?>" class="btn btn-primary" style="width: 100%; justify-content: center; margin-top: 1rem;">
                                <i class="fas fa-paper-plane"></i> Apply Now
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
