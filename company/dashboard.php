<?php
/**
 * Company Dashboard
 * Internship Management System
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();
require_role('company');

// Get company profile
$profile = db_fetch("SELECT * FROM company_profiles WHERE user_id = ?", [$_SESSION['user_id']]);

// Get company's internships with application counts
$internships = db_fetch_all("
    SELECT i.*,
           (SELECT COUNT(*) FROM applications WHERE internship_id = i.id) as total_applications,
           (SELECT COUNT(*) FROM applications WHERE internship_id = i.id AND status = 'pending') as pending_count,
           (SELECT COUNT(*) FROM applications WHERE internship_id = i.id AND status = 'accepted') as accepted_count
    FROM internships i
    WHERE i.company_id = ?
    ORDER BY i.created_at DESC
", [$_SESSION['user_id']]);

// Calculate statistics
$total_internships = count($internships);
$total_applications = 0;
$pending_applications = 0;
$accepted_applications = 0;

foreach ($internships as $int) {
    $total_applications += $int['total_applications'];
    $pending_applications += $int['pending_count'];
    $accepted_applications += $int['accepted_count'];
}

// Get recent applications
$recent_applications = db_fetch_all("
    SELECT a.*, i.title as internship_title, u.name as student_name, u.email as student_email,
           sp.university, sp.course
    FROM applications a
    JOIN internships i ON a.internship_id = i.id
    JOIN users u ON a.student_id = u.id
    LEFT JOIN student_profiles sp ON a.student_id = sp.user_id
    WHERE i.company_id = ?
    ORDER BY a.applied_at DESC
    LIMIT 10
", [$_SESSION['user_id']]);

$is_profile_complete = $profile && !empty($profile['company_name']) && !empty($profile['description']);
$is_verified = $profile['is_verified'] ?? false;

$page_title = 'Company Dashboard';
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

        /* Status Banner */
        .status-banner {
            padding: 1rem 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .status-banner.pending {
            background: rgba(245, 158, 11, 0.1);
            border: 1px solid rgba(245, 158, 11, 0.3);
            color: #b45309;
        }

        .status-banner.verified {
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid rgba(34, 197, 94, 0.3);
            color: #15803d;
        }

        .status-banner.incomplete {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #b91c1c;
        }

        .status-banner i { font-size: 1.5rem; }

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
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
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

        .internship-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 0.25rem;
        }

        .internship-meta {
            display: flex;
            gap: 0.75rem;
            font-size: 0.85rem;
            color: var(--gray);
            margin-bottom: 1rem;
        }

        .internship-meta span {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .badge {
            padding: 0.25rem 0.75rem;
            border-radius: 100px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .badge-open { background: rgba(34,197,94,0.1); color: var(--success); }
        .badge-closed { background: rgba(239,68,68,0.1); color: var(--danger); }
        .badge-filled { background: rgba(59,130,246,0.1); color: var(--info); }

        .internship-stats {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .stat-badge {
            padding: 0.5rem 0.75rem;
            background: var(--light);
            border-radius: 8px;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .stat-badge.pending { color: var(--warning); }
        .stat-badge.accepted { color: var(--success); }

        .internship-footer {
            display: flex;
            justify-content: flex-end;
            padding-top: 1rem;
            border-top: 1px solid var(--border);
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

        .applicant-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .applicant-avatar {
            width: 40px;
            height: 40px;
            background: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }

        .applicant-name {
            font-weight: 600;
            color: var(--dark);
        }

        .applicant-details {
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
            <li><a href="/company/dashboard.php" class="active"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="/company/post.php"><i class="fas fa-plus-circle"></i> Post Internship</a></li>
            <li><a href="/company/internships.php"><i class="fas fa-briefcase"></i> My Internships</a></li>
            <li><a href="/company/applications.php"><i class="fas fa-users"></i> Applications</a></li>
            <li><a href="/company/profile.php"><i class="fas fa-building"></i> Company Profile</a></li>
            <li><a href="/company/messages.php"><i class="fas fa-envelope"></i> Messages</a></li>
            <li><a href="/auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title">Company Dashboard</h1>
            <div class="user-info">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($profile['company_name'] ?? $_SESSION['user_name'], 0, 1)); ?>
                </div>
            </div>
        </div>

        <?php if ($flash = get_flash('success')): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo $flash; ?>
            </div>
        <?php endif; ?>

        <!-- Status Banner -->
        <?php if (!$is_profile_complete): ?>
            <div class="status-banner incomplete">
                <i class="fas fa-exclamation-circle"></i>
                <div>
                    <strong>Profile Incomplete</strong>
                    <p style="margin: 0.25rem 0 0;">Complete your company profile to attract more candidates.</p>
                </div>
                <a href="/company/profile.php" class="btn btn-primary" style="margin-left: auto;">
                    <i class="fas fa-edit"></i> Complete Profile
                </a>
            </div>
        <?php elseif (!$is_verified): ?>
            <div class="status-banner pending">
                <i class="fas fa-clock"></i>
                <div>
                    <strong>Pending Verification</strong>
                    <p style="margin: 0.25rem 0 0;">Your company profile is under review. You'll receive a verification badge once approved.</p>
                </div>
            </div>
        <?php else: ?>
            <div class="status-banner verified">
                <i class="fas fa-check-circle"></i>
                <div>
                    <strong>Verified Company</strong>
                    <p style="margin: 0.25rem 0 0;">Your company is verified. Students can trust your internship postings.</p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="icon primary"><i class="fas fa-briefcase"></i></div>
                <div class="value"><?php echo $total_internships; ?></div>
                <div class="label">Active Internships</div>
            </div>
            <div class="stat-card">
                <div class="icon info"><i class="fas fa-users"></i></div>
                <div class="value"><?php echo $total_applications; ?></div>
                <div class="label">Total Applications</div>
            </div>
            <div class="stat-card">
                <div class="icon warning"><i class="fas fa-clock"></i></div>
                <div class="value"><?php echo $pending_applications; ?></div>
                <div class="label">Pending Review</div>
            </div>
            <div class="stat-card">
                <div class="icon success"><i class="fas fa-check-circle"></i></div>
                <div class="value"><?php echo $accepted_applications; ?></div>
                <div class="label">Accepted</div>
            </div>
        </div>

        <!-- Recent Applications -->
        <div class="section">
            <div class="section-header">
                <h2 class="section-title">Recent Applications</h2>
                <a href="/company/applications.php" class="btn btn-outline">View All</a>
            </div>

            <?php if (empty($recent_applications)): ?>
                <div class="table-container">
                    <div class="empty-state">
                        <i class="fas fa-users"></i>
                        <h3>No applications yet</h3>
                        <p>Post internships to start receiving applications from students</p>
                    </div>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Applicant</th>
                                <th>Position</th>
                                <th>Applied</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_applications as $app): ?>
                                <tr>
                                    <td>
                                        <div class="applicant-info">
                                            <div class="applicant-avatar">
                                                <?php echo strtoupper(substr($app['student_name'], 0, 1)); ?>
                                            </div>
                                            <div>
                                                <div class="applicant-name"><?php echo htmlspecialchars($app['student_name']); ?></div>
                                                <div class="applicant-details">
                                                    <?php echo htmlspecialchars($app['university'] ?? 'University not specified'); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($app['internship_title']); ?></td>
                                    <td><?php echo format_date($app['applied_at']); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $app['status']; ?>">
                                            <?php echo ucfirst($app['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="/company/view-application.php?id=<?php echo $app['id']; ?>" class="btn btn-outline">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Your Internships -->
        <div class="section">
            <div class="section-header">
                <h2 class="section-title">Your Internships</h2>
                <a href="/company/post.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Post New
                </a>
            </div>

            <?php if (empty($internships)): ?>
                <div class="table-container">
                    <div class="empty-state">
                        <i class="fas fa-briefcase"></i>
                        <h3>No internships posted</h3>
                        <p>Create your first internship posting to connect with talented students</p>
                        <a href="/company/post.php" class="btn btn-primary" style="margin-top: 1rem;">
                            <i class="fas fa-plus"></i> Post Internship
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="cards-grid">
                    <?php foreach ($internships as $int): ?>
                        <div class="internship-card">
                            <div class="internship-header">
                                <div>
                                    <h3 class="internship-title"><?php echo htmlspecialchars($int['title']); ?></h3>
                                    <div class="internship-meta">
                                        <span><i class="fas fa-calendar-alt"></i> <?php echo format_date($int['created_at']); ?></span>
                                    </div>
                                </div>
                                <span class="badge badge-<?php echo $int['status']; ?>">
                                    <?php echo ucfirst($int['status']); ?>
                                </span>
                            </div>

                            <div class="internship-meta">
                                <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($int['location'] ?? 'Remote'); ?></span>
                                <span><i class="fas fa-clock"></i> <?php echo htmlspecialchars($int['duration'] ?? 'Flexible'); ?></span>
                                <span><i class="fas fa-laptop-house"></i> <?php echo ucfirst($int['type']); ?></span>
                            </div>

                            <div class="internship-stats">
                                <span class="stat-badge pending">
                                    <i class="fas fa-hourglass-half"></i> <?php echo $int['pending_count']; ?> Pending
                                </span>
                                <span class="stat-badge accepted">
                                    <i class="fas fa-check"></i> <?php echo $int['accepted_count']; ?> Accepted
                                </span>
                            </div>

                            <div class="internship-footer">
                                <a href="/company/applications.php?internship=<?php echo $int['id']; ?>" class="btn btn-primary">
                                    <i class="fas fa-users"></i> View Applications
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
