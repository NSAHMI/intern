<?php
/**
 * View Applications
 * Internship Management System
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();
require_role('company');

// Get filters
$status_filter = $_GET['status'] ?? '';
$internship_filter = $_GET['internship'] ?? '';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $app_id = intval($_POST['application_id'] ?? 0);
    $new_status = $_POST['new_status'] ?? '';

    // Verify this application belongs to the company
    $app = db_fetch("
        SELECT a.*, i.company_id, i.title as internship_title, u.name as student_name
        FROM applications a
        JOIN internships i ON a.internship_id = i.id
        JOIN users u ON a.student_id = u.id
        WHERE a.id = ? AND i.company_id = ?
    ", [$app_id, $_SESSION['user_id']]);

    if ($app && in_array($new_status, ['pending', 'reviewed', 'shortlisted', 'accepted', 'rejected'])) {
        db_query("UPDATE applications SET status = ? WHERE id = ?", [$new_status, $app_id]);

        // Create notification for student
        $notification_msg = "Your application for {$app['internship_title']} has been " . ($new_status === 'accepted' ? 'accepted!' : $new_status);
        db_query(
            "INSERT INTO notifications (user_id, title, message, type, link) VALUES (?, ?, ?, 'application', ?)",
            [$app['student_id'], 'Application Update', $notification_msg, '/student/applications.php']
        );

        set_flash('success', "Application status updated to: " . ucfirst($new_status));
    }
    redirect('/company/applications.php' . ($internship_filter ? "?internship=$internship_filter" : ''));
}

// Build query
$where = ["i.company_id = ?"];
$params = [$_SESSION['user_id']];

if ($status_filter) {
    $where[] = "a.status = ?";
    $params[] = $status_filter;
}

if ($internship_filter) {
    $where[] = "a.internship_id = ?";
    $params[] = $internship_filter;
}

$where_clause = implode(' AND ', $where);

// Get applications
$applications = db_fetch_all("
    SELECT a.*, i.title as internship_title, i.location, i.type,
           u.name as student_name, u.email as student_email,
           sp.university, sp.course, sp.skills, sp.bio, sp.resume_path
    FROM applications a
    JOIN internships i ON a.internship_id = i.id
    JOIN users u ON a.student_id = u.id
    LEFT JOIN student_profiles sp ON a.student_id = sp.user_id
    WHERE $where_clause
    ORDER BY a.applied_at DESC
", $params);

// Get company's internships for filter
$internships = db_fetch_all(
    "SELECT id, title FROM internships WHERE company_id = ? ORDER BY created_at DESC",
    [$_SESSION['user_id']]
);

// Get counts
$status_counts = db_fetch("
    SELECT
        COUNT(*) as total,
        SUM(CASE WHEN a.status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN a.status = 'reviewed' THEN 1 ELSE 0 END) as reviewed,
        SUM(CASE WHEN a.status = 'shortlisted' THEN 1 ELSE 0 END) as shortlisted,
        SUM(CASE WHEN a.status = 'accepted' THEN 1 ELSE 0 END) as accepted,
        SUM(CASE WHEN a.status = 'rejected' THEN 1 ELSE 0 END) as rejected
    FROM applications a
    JOIN internships i ON a.internship_id = i.id
    WHERE i.company_id = ?
", [$_SESSION['user_id']]);

$page_title = 'Applications';
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
            grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
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
        }

        .stat-card.active {
            border-color: var(--primary);
        }

        .stat-card .value {
            font-size: 1.5rem;
            font-weight: 700;
        }

        .stat-card .label {
            font-size: 0.75rem;
            color: var(--gray);
        }

        .stat-card.pending .value { color: var(--warning); }
        .stat-card.reviewed .value { color: var(--info); }
        .stat-card.shortlisted .value { color: #8b5cf6; }
        .stat-card.accepted .value { color: var(--success); }
        .stat-card.rejected .value { color: var(--danger); }

        /* Filter */
        .filter-bar {
            background: var(--white);
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .filter-control {
            padding: 0.5rem 1rem;
            border: 2px solid var(--border);
            border-radius: 8px;
            font-size: 0.9rem;
            font-family: inherit;
        }

        .filter-control:focus {
            outline: none;
            border-color: var(--primary);
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

        /* Cards */
        .applications-grid {
            display: grid;
            gap: 1rem;
        }

        .application-card {
            background: var(--white);
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: 1.5rem;
            align-items: start;
        }

        .applicant-avatar {
            width: 60px;
            height: 60px;
            background: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 1.5rem;
        }

        .applicant-info h3 {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 0.25rem;
        }

        .applicant-info .meta {
            color: var(--gray);
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .applicant-info .position {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            background: rgba(99,102,241,0.1);
            color: var(--primary);
            border-radius: 100px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 0.75rem;
        }

        .applicant-info .skills {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }

        .skill-tag {
            padding: 0.25rem 0.5rem;
            background: var(--light);
            border-radius: 4px;
            font-size: 0.75rem;
            color: var(--gray);
        }

        .application-actions {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            align-items: flex-end;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 100px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .status-pending { background: rgba(245,158,11,0.1); color: var(--warning); }
        .status-reviewed { background: rgba(59,130,246,0.1); color: var(--info); }
        .status-shortlisted { background: rgba(139,92,246,0.1); color: #8b5cf6; }
        .status-accepted { background: rgba(34,197,94,0.1); color: var(--success); }
        .status-rejected { background: rgba(239,68,68,0.1); color: var(--danger); }

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

        .btn-sm {
            padding: 0.35rem 0.75rem;
            font-size: 0.8rem;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-success {
            background: var(--success);
            color: white;
        }

        .btn-danger {
            background: var(--danger);
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

        .action-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .applied-date {
            font-size: 0.8rem;
            color: var(--gray);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: var(--white);
            border-radius: 16px;
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
        }

        /* Modal */
        .modal-backdrop {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal-backdrop.show {
            display: flex;
        }

        .modal {
            background: var(--white);
            border-radius: 16px;
            padding: 2rem;
            max-width: 600px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .modal-header h3 {
            font-size: 1.25rem;
            font-weight: 700;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: var(--gray);
            cursor: pointer;
        }

        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main-content { margin-left: 0; }
            .application-card { grid-template-columns: 1fr; }
            .application-actions { align-items: flex-start; flex-direction: row; }
        }
    </style>
</head>
<body>
    <aside class="sidebar">
        <a href="/index.php" class="sidebar-logo">
            <i class="fas fa-briefcase"></i> InternHub
        </a>
        <ul class="sidebar-nav">
            <li><a href="/company/dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="/company/post.php"><i class="fas fa-plus-circle"></i> Post Internship</a></li>
            <li><a href="/company/internships.php"><i class="fas fa-briefcase"></i> My Internships</a></li>
            <li><a href="/company/applications.php" class="active"><i class="fas fa-users"></i> Applications</a></li>
            <li><a href="/company/profile.php"><i class="fas fa-building"></i> Company Profile</a></li>
            <li><a href="/company/messages.php"><i class="fas fa-envelope"></i> Messages</a></li>
            <li><a href="/auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title">Applications</h1>
        </div>

        <?php if ($flash = get_flash('success')): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo $flash; ?>
            </div>
        <?php endif; ?>

        <!-- Status Filters -->
        <div class="stats-grid">
            <a href="?<?php echo $internship_filter ? "internship=$internship_filter" : ''; ?>" class="stat-card <?php echo !$status_filter ? 'active' : ''; ?>">
                <div class="value" style="color: var(--dark);"><?php echo $status_counts['total'] ?? 0; ?></div>
                <div class="label">All</div>
            </a>
            <a href="?status=pending<?php echo $internship_filter ? "&internship=$internship_filter" : ''; ?>" class="stat-card pending <?php echo $status_filter === 'pending' ? 'active' : ''; ?>">
                <div class="value"><?php echo $status_counts['pending'] ?? 0; ?></div>
                <div class="label">Pending</div>
            </a>
            <a href="?status=reviewed<?php echo $internship_filter ? "&internship=$internship_filter" : ''; ?>" class="stat-card reviewed <?php echo $status_filter === 'reviewed' ? 'active' : ''; ?>">
                <div class="value"><?php echo $status_counts['reviewed'] ?? 0; ?></div>
                <div class="label">Reviewed</div>
            </a>
            <a href="?status=shortlisted<?php echo $internship_filter ? "&internship=$internship_filter" : ''; ?>" class="stat-card shortlisted <?php echo $status_filter === 'shortlisted' ? 'active' : ''; ?>">
                <div class="value"><?php echo $status_counts['shortlisted'] ?? 0; ?></div>
                <div class="label">Shortlisted</div>
            </a>
            <a href="?status=accepted<?php echo $internship_filter ? "&internship=$internship_filter" : ''; ?>" class="stat-card accepted <?php echo $status_filter === 'accepted' ? 'active' : ''; ?>">
                <div class="value"><?php echo $status_counts['accepted'] ?? 0; ?></div>
                <div class="label">Accepted</div>
            </a>
            <a href="?status=rejected<?php echo $internship_filter ? "&internship=$internship_filter" : ''; ?>" class="stat-card rejected <?php echo $status_filter === 'rejected' ? 'active' : ''; ?>">
                <div class="value"><?php echo $status_counts['rejected'] ?? 0; ?></div>
                <div class="label">Rejected</div>
            </a>
        </div>

        <!-- Filter by Internship -->
        <?php if (count($internships) > 1): ?>
        <div class="filter-bar">
            <label><strong>Filter by Position:</strong></label>
            <select class="filter-control" onchange="window.location.href='?internship='+this.value+'<?php echo $status_filter ? "&status=$status_filter" : ''; ?>'">
                <option value="">All Positions</option>
                <?php foreach ($internships as $int): ?>
                    <option value="<?php echo $int['id']; ?>" <?php echo $internship_filter == $int['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($int['title']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>

        <!-- Applications List -->
        <?php if (empty($applications)): ?>
            <div class="empty-state">
                <i class="fas fa-users"></i>
                <h3>No applications found</h3>
                <p>
                    <?php if ($status_filter || $internship_filter): ?>
                        Try adjusting your filters
                    <?php else: ?>
                        Post internships to start receiving applications
                    <?php endif; ?>
                </p>
            </div>
        <?php else: ?>
            <div class="applications-grid">
                <?php foreach ($applications as $app): ?>
                    <div class="application-card">
                        <div class="applicant-avatar">
                            <?php echo strtoupper(substr($app['student_name'], 0, 1)); ?>
                        </div>

                        <div class="applicant-info">
                            <h3><?php echo htmlspecialchars($app['student_name']); ?></h3>
                            <div class="meta">
                                <?php if ($app['university']): ?>
                                    <i class="fas fa-university"></i> <?php echo htmlspecialchars($app['university']); ?>
                                    <?php if ($app['course']): ?> - <?php echo htmlspecialchars($app['course']); ?><?php endif; ?>
                                <?php else: ?>
                                    <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($app['student_email']); ?>
                                <?php endif; ?>
                            </div>
                            <div class="position">
                                <i class="fas fa-briefcase"></i> <?php echo htmlspecialchars($app['internship_title']); ?>
                            </div>
                            <?php if ($app['skills']): ?>
                                <div class="skills">
                                    <?php foreach (explode(',', $app['skills']) as $skill): ?>
                                        <span class="skill-tag"><?php echo htmlspecialchars(trim($skill)); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="application-actions">
                            <span class="status-badge status-<?php echo $app['status']; ?>">
                                <?php echo ucfirst($app['status']); ?>
                            </span>
                            <div class="applied-date">
                                <i class="fas fa-calendar-alt"></i>
                                <?php echo format_date($app['applied_at']); ?>
                            </div>

                            <div class="action-buttons" style="margin-top: 0.5rem;">
                                <button class="btn btn-outline btn-sm" onclick="viewApplication(<?php echo htmlspecialchars(json_encode($app)); ?>)">
                                    <i class="fas fa-eye"></i> View
                                </button>

                                <?php if ($app['status'] === 'pending'): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="application_id" value="<?php echo $app['id']; ?>">
                                        <input type="hidden" name="new_status" value="reviewed">
                                        <button type="submit" class="btn btn-primary btn-sm">
                                            <i class="fas fa-check"></i> Mark Reviewed
                                        </button>
                                    </form>
                                <?php elseif ($app['status'] === 'reviewed'): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="application_id" value="<?php echo $app['id']; ?>">
                                        <input type="hidden" name="new_status" value="shortlisted">
                                        <button type="submit" class="btn btn-primary btn-sm">
                                            <i class="fas fa-star"></i> Shortlist
                                        </button>
                                    </form>
                                <?php elseif ($app['status'] === 'shortlisted'): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="application_id" value="<?php echo $app['id']; ?>">
                                        <input type="hidden" name="new_status" value="accepted">
                                        <button type="submit" class="btn btn-success btn-sm">
                                            <i class="fas fa-check-circle"></i> Accept
                                        </button>
                                    </form>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="application_id" value="<?php echo $app['id']; ?>">
                                        <input type="hidden" name="new_status" value="rejected">
                                        <button type="submit" class="btn btn-danger btn-sm">
                                            <i class="fas fa-times"></i> Reject
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <!-- View Application Modal -->
    <div class="modal-backdrop" id="applicationModal">
        <div class="modal">
            <div class="modal-header">
                <h3>Application Details</h3>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div id="modalContent">
                <!-- Content populated by JavaScript -->
            </div>
        </div>
    </div>

    <script>
        function viewApplication(app) {
            const content = `
                <div style="margin-bottom: 1.5rem;">
                    <h4 style="margin-bottom: 0.5rem;">${app.student_name}</h4>
                    <p style="color: var(--gray);">${app.student_email}</p>
                    ${app.university ? `<p style="color: var(--gray);"><i class="fas fa-university"></i> ${app.university}${app.course ? ' - ' + app.course : ''}</p>` : ''}
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <strong>Position Applied:</strong>
                    <p>${app.internship_title}</p>
                </div>

                ${app.cover_letter ? `
                <div style="margin-bottom: 1.5rem;">
                    <strong>Cover Letter:</strong>
                    <div style="background: var(--light); padding: 1rem; border-radius: 8px; margin-top: 0.5rem; white-space: pre-wrap;">${app.cover_letter}</div>
                </div>
                ` : ''}

                ${app.skills ? `
                <div style="margin-bottom: 1.5rem;">
                    <strong>Skills:</strong>
                    <p>${app.skills}</p>
                </div>
                ` : ''}

                ${app.bio ? `
                <div style="margin-bottom: 1.5rem;">
                    <strong>Bio:</strong>
                    <p>${app.bio}</p>
                </div>
                ` : ''}

                <div>
                    <strong>Applied:</strong>
                    <p>${new Date(app.applied_at).toLocaleDateString()}</p>
                </div>
            `;

            document.getElementById('modalContent').innerHTML = content;
            document.getElementById('applicationModal').classList.add('show');
        }

        function closeModal() {
            document.getElementById('applicationModal').classList.remove('show');
        }

        // Close modal when clicking outside
        document.getElementById('applicationModal').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });
    </script>
</body>
</html>
