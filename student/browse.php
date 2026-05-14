<?php
/**
 * Browse Internships
 * Internship Management System
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();
require_role('student');

// Filter parameters
$search = $_GET['search'] ?? '';
$type = $_GET['type'] ?? '';
$industry = $_GET['industry'] ?? '';

// Build query
$where = ["i.status = 'open'", "(i.deadline IS NULL OR i.deadline >= CURDATE())"];
$params = [];

if ($search) {
    $where[] = "(i.title LIKE ? OR i.description LIKE ? OR cp.company_name LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if ($type) {
    $where[] = "i.type = ?";
    $params[] = $type;
}

if ($industry) {
    $where[] = "cp.industry = ?";
    $params[] = $industry;
}

$where_clause = implode(' AND ', $where);

$internships = db_fetch_all("
    SELECT i.*, cp.company_name, cp.logo_path, cp.industry, cp.location as company_location,
           (SELECT COUNT(*) FROM applications WHERE internship_id = i.id) as application_count
    FROM internships i
    JOIN company_profiles cp ON i.company_id = cp.user_id
    WHERE $where_clause
    ORDER BY i.created_at DESC
", $params);

// Get industries for filter
$industries = db_fetch_all("SELECT DISTINCT industry FROM company_profiles WHERE industry IS NOT NULL ORDER BY industry");

// Get student's existing applications
$my_applications = db_fetch_all(
    "SELECT internship_id FROM applications WHERE student_id = ?",
    [$_SESSION['user_id']]
);
$applied_ids = array_column($my_applications, 'internship_id');

$page_title = 'Browse Internships';
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

        /* Filters */
        .filters {
            background: var(--white);
            padding: 1.5rem;
            border-radius: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .filters-form {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            align-items: flex-end;
        }

        .filter-group {
            flex: 1;
            min-width: 200px;
        }

        .filter-label {
            display: block;
            font-weight: 600;
            font-size: 0.85rem;
            margin-bottom: 0.5rem;
            color: var(--gray);
        }

        .filter-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid var(--border);
            border-radius: 10px;
            font-size: 0.95rem;
            font-family: inherit;
            transition: all 0.2s;
        }

        .filter-control:focus {
            outline: none;
            border-color: var(--primary);
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.95rem;
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

        /* Results */
        .results-info {
            color: var(--gray);
            margin-bottom: 1rem;
        }

        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 1.5rem;
        }

        .internship-card {
            background: var(--white);
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            transition: all 0.2s;
            display: flex;
            flex-direction: column;
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
        .badge-applied { background: rgba(139,92,246,0.1); color: #8b5cf6; }

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

        .internship-description {
            color: var(--gray);
            font-size: 0.9rem;
            line-height: 1.5;
            margin-bottom: 1rem;
            flex-grow: 1;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .internship-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            color: var(--gray);
            font-size: 0.85rem;
            margin-bottom: 1rem;
        }

        .internship-meta span {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .internship-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 1rem;
            border-top: 1px solid var(--border);
            margin-top: auto;
        }

        .applicants-count {
            font-size: 0.85rem;
            color: var(--gray);
        }

        .btn-apply {
            padding: 0.5rem 1rem;
            background: var(--primary);
            color: white;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s;
        }

        .btn-apply:hover {
            background: var(--primary-dark);
        }

        .btn-apply.disabled {
            background: var(--border);
            color: var(--gray);
            cursor: not-allowed;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: var(--white);
            border-radius: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
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

        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main-content { margin-left: 0; }
            .filters-form { flex-direction: column; }
            .filter-group { min-width: 100%; }
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
            <li><a href="/student/browse.php" class="active"><i class="fas fa-search"></i> Browse Internships</a></li>
            <li><a href="/student/applications.php"><i class="fas fa-clipboard-list"></i> My Applications</a></li>
            <li><a href="/student/profile.php"><i class="fas fa-user"></i> My Profile</a></li>
            <li><a href="/student/messages.php"><i class="fas fa-envelope"></i> Messages</a></li>
            <li><a href="/auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title">Browse Internships</h1>
        </div>

        <!-- Filters -->
        <div class="filters">
            <form class="filters-form" method="GET" action="">
                <div class="filter-group" style="flex: 2;">
                    <label class="filter-label">Search</label>
                    <input type="text" name="search" class="filter-control"
                           placeholder="Search by title, company, or keywords..."
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="filter-group">
                    <label class="filter-label">Work Type</label>
                    <select name="type" class="filter-control">
                        <option value="">All Types</option>
                        <option value="remote" <?php echo $type === 'remote' ? 'selected' : ''; ?>>Remote</option>
                        <option value="onsite" <?php echo $type === 'onsite' ? 'selected' : ''; ?>>On-site</option>
                        <option value="hybrid" <?php echo $type === 'hybrid' ? 'selected' : ''; ?>>Hybrid</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label class="filter-label">Industry</label>
                    <select name="industry" class="filter-control">
                        <option value="">All Industries</option>
                        <?php foreach ($industries as $ind): ?>
                            <option value="<?php echo htmlspecialchars($ind['industry']); ?>"
                                    <?php echo $industry === $ind['industry'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($ind['industry']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Search
                    </button>
                </div>
                <?php if ($search || $type || $industry): ?>
                    <div>
                        <a href="/student/browse.php" class="btn btn-outline">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    </div>
                <?php endif; ?>
            </form>
        </div>

        <!-- Results -->
        <p class="results-info">
            Found <strong><?php echo count($internships); ?></strong> internship<?php echo count($internships) !== 1 ? 's' : ''; ?>
            <?php if ($search || $type || $industry): ?>
                matching your criteria
            <?php endif; ?>
        </p>

        <?php if (empty($internships)): ?>
            <div class="empty-state">
                <i class="fas fa-search"></i>
                <h3>No internships found</h3>
                <p>Try adjusting your search filters or check back later for new opportunities</p>
            </div>
        <?php else: ?>
            <div class="cards-grid">
                <?php foreach ($internships as $internship): ?>
                    <div class="internship-card">
                        <div class="internship-header">
                            <div class="company-logo">
                                <?php echo strtoupper(substr($internship['company_name'], 0, 1)); ?>
                            </div>
                            <div style="display: flex; gap: 0.5rem;">
                                <span class="badge badge-<?php echo $internship['type']; ?>">
                                    <?php echo ucfirst($internship['type']); ?>
                                </span>
                                <?php if (in_array($internship['id'], $applied_ids)): ?>
                                    <span class="badge badge-applied">Applied</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <h3 class="internship-title"><?php echo htmlspecialchars($internship['title']); ?></h3>
                        <p class="internship-company">
                            <i class="fas fa-building"></i> <?php echo htmlspecialchars($internship['company_name']); ?>
                            <?php if ($internship['industry']): ?>
                                <span style="color: var(--border);">|</span>
                                <?php echo htmlspecialchars($internship['industry']); ?>
                            <?php endif; ?>
                        </p>

                        <p class="internship-description">
                            <?php echo htmlspecialchars($internship['description']); ?>
                        </p>

                        <div class="internship-meta">
                            <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($internship['location'] ?? 'Remote'); ?></span>
                            <span><i class="fas fa-clock"></i> <?php echo htmlspecialchars($internship['duration'] ?? 'Flexible'); ?></span>
                            <?php if ($internship['stipend']): ?>
                                <span><i class="fas fa-dollar-sign"></i> <?php echo htmlspecialchars($internship['stipend']); ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="internship-footer">
                            <span class="applicants-count">
                                <i class="fas fa-users"></i>
                                <?php echo $internship['application_count']; ?> applicant<?php echo $internship['application_count'] !== 1 ? 's' : ''; ?>
                            </span>
                            <?php if (in_array($internship['id'], $applied_ids)): ?>
                                <span class="btn-apply disabled">
                                    <i class="fas fa-check"></i> Applied
                                </span>
                            <?php else: ?>
                                <a href="/student/apply.php?id=<?php echo $internship['id']; ?>" class="btn-apply">
                                    <i class="fas fa-paper-plane"></i> Apply
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
