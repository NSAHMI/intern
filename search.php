<?php
session_start();
include "config/db.php";

// Helper function to create references for bind_param
function makeReferences($arr) {
    $refs = [];
    foreach ($arr as $key => $value) {
        $refs[$key] = &$arr[$key];
    }
    return $refs;
}

// Get search parameters
$search_query = trim($_GET['q'] ?? '');
$department_id = $_GET['department'] ?? '';
$location = trim($_GET['location'] ?? '');
$work_type = $_GET['work_type'] ?? '';
$duration = $_GET['duration'] ?? '';
$salary_min = $_GET['salary_min'] ?? '';
$sort_by = $_GET['sort'] ?? 'recent';

// Build the query
$where_conditions = ["i.expiration_date >= CURDATE()"];
$params = [];
$types = "";

if (!empty($search_query)) {
    $where_conditions[] = "(i.title LIKE ? OR i.description LIKE ?)";
    $search_param = "%$search_query%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

if (!empty($department_id)) {
    $where_conditions[] = "i.department_id = ?";
    $params[] = $department_id;
    $types .= "i";
}

if (!empty($location)) {
    $where_conditions[] = "i.location LIKE ?";
    $location_param = "%$location%";
    $params[] = $location_param;
    $types .= "s";
}

if (!empty($work_type)) {
    $where_conditions[] = "i.work_type = ?";
    $params[] = $work_type;
    $types .= "s";
}

if (!empty($duration)) {
    $where_conditions[] = "i.duration LIKE ?";
    $duration_param = "%$duration%";
    $params[] = $duration_param;
    $types .= "s";
}

// Build ORDER BY clause
$order_by = "ORDER BY i.created_at DESC";
switch ($sort_by) {
    case 'title':
        $order_by = "ORDER BY i.title ASC";
        break;
    case 'company':
        $order_by = "ORDER BY u.name ASC";
        break;
    case 'deadline':
        $order_by = "ORDER BY i.expiration_date ASC";
        break;
}

$where_clause = implode(" AND ", $where_conditions);

// Execute query
$stmt = $conn->prepare("
    SELECT i.*, u.name as company_name, d.name as department_name, d.icon as department_icon,
           (SELECT COUNT(*) FROM applications a WHERE a.internship_id = i.id) as application_count
    FROM internships i 
    JOIN users u ON i.company_id = u.id 
    JOIN departments d ON i.department_id = d.id
    WHERE $where_clause
    $order_by
");

if (!empty($params)) {
    // Use call_user_func_array for compatibility with older PHP versions
    $bind_params = array_merge([$types], $params);
    call_user_func_array([$stmt, 'bind_param'], makeReferences($bind_params));
}
$stmt->execute();
$result = $stmt->get_result();
$internships = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get departments for filter
$dept_stmt = $conn->prepare('SELECT id, name, icon FROM departments ORDER BY name');
$dept_stmt->execute();
$departments = $dept_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$dept_stmt->close();

// Get unique locations
$location_stmt = $conn->prepare('SELECT DISTINCT location FROM internships WHERE location IS NOT NULL AND location != "" ORDER BY location');
$location_stmt->execute();
$locations = $location_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$location_stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Advanced Search - Internship Opportunities</title>
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
        
        .filter-sidebar {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: var(--card-shadow);
            border: 1px solid #e5e7eb;
            height: fit-content;
            position: sticky;
            top: 20px;
        }
        
        .internship-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: var(--card-shadow);
            transition: all 0.3s ease;
            border: 1px solid #e5e7eb;
            height: 100%;
        }
        
        .internship-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--card-hover-shadow);
            border-color: var(--primary-color);
        }
        
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
        
        .btn-search {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-search:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);
        }
        
        .badge-department {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
        }
        
        .stats-row {
            background: linear-gradient(135deg, #f3f4f6, #e5e7eb);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .search-highlight {
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
<div class="main-container">
    <div class="header-section">
        <div class="header-content">
            <h1 class="header-title">Advanced Search 🔍</h1>
            <p class="header-subtitle">Find your perfect internship with powerful filters and search</p>
            
            <div class="nav-buttons">
                <a href="index.php" class="btn-custom btn-primary-custom">
                    <i class="fas fa-home"></i> Home
                </a>
                <a href="public_internships.php" class="btn-custom btn-secondary-custom">
                    <i class="fas fa-briefcase"></i> Browse All
                </a>
                <?php if (!empty($_SESSION['user_id'])): ?>
                    <a href="<?php echo $_SESSION['role']; ?>/dashboard.php" class="btn-custom btn-secondary-custom">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                <?php else: ?>
                    <a href="auth/login.php" class="btn-custom btn-secondary-custom">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="content-section">
        <?php if (!empty($search_query) || !empty($department_id) || !empty($location)): ?>
            <div class="search-highlight">
                <h5><i class="fas fa-filter me-2"></i>Search Results</h5>
                <div class="d-flex flex-wrap gap-2 mt-2">
                    <?php if (!empty($search_query)): ?>
                        <span class="badge bg-primary">Query: <?php echo htmlspecialchars($search_query); ?></span>
                    <?php endif; ?>
                    <?php if (!empty($department_id)): ?>
                        <?php 
                        $dept_name = array_filter($departments, fn($d) => $d['id'] == $department_id)[0]['name'] ?? '';
                        ?>
                        <span class="badge bg-info">Department: <?php echo htmlspecialchars($dept_name); ?></span>
                    <?php endif; ?>
                    <?php if (!empty($location)): ?>
                        <span class="badge bg-success">Location: <?php echo htmlspecialchars($location); ?></span>
                    <?php endif; ?>
                    <span class="badge bg-secondary"><?php echo count($internships); ?> results found</span>
                </div>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Filter Sidebar -->
            <div class="col-lg-3 col-md-4">
                <div class="filter-sidebar">
                    <h5 class="mb-4"><i class="fas fa-sliders-h me-2"></i>Filters</h5>
                    
                    <form method="GET" action="search.php">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-search me-2"></i>Keywords
                            </label>
                            <input type="text" name="q" class="form-control" 
                                   value="<?php echo htmlspecialchars($search_query); ?>"
                                   placeholder="Search titles, descriptions...">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-graduation-cap me-2"></i>Department
                            </label>
                            <select name="department" class="form-select">
                                <option value="">All Departments</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo $dept['id']; ?>" 
                                            <?php echo ($department_id == $dept['id']) ? 'selected' : ''; ?>>
                                        <i class="fas <?php echo $dept['icon']; ?>"></i> 
                                        <?php echo htmlspecialchars($dept['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-map-marker-alt me-2"></i>Location
                            </label>
                            <input type="text" name="location" class="form-control" 
                                   value="<?php echo htmlspecialchars($location); ?>"
                                   placeholder="City, State, or Remote">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-laptop-house me-2"></i>Work Type
                            </label>
                            <select name="work_type" class="form-select">
                                <option value="">All Types</option>
                                <option value="onsite" <?php echo ($work_type === 'onsite') ? 'selected' : ''; ?>>
                                    On-site
                                </option>
                                <option value="remote" <?php echo ($work_type === 'remote') ? 'selected' : ''; ?>>
                                    Remote
                                </option>
                                <option value="hybrid" <?php echo ($work_type === 'hybrid') ? 'selected' : ''; ?>>
                                    Hybrid
                                </option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-clock me-2"></i>Duration
                            </label>
                            <select name="duration" class="form-select">
                                <option value="">All Durations</option>
                                <option value="summer" <?php echo ($duration === 'summer') ? 'selected' : ''; ?>>
                                    Summer
                                </option>
                                <option value="semester" <?php echo ($duration === 'semester') ? 'selected' : ''; ?>>
                                    Semester
                                </option>
                                <option value="year" <?php echo ($duration === 'year') ? 'selected' : ''; ?>>
                                    Year-round
                                </option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-sort me-2"></i>Sort By
                            </label>
                            <select name="sort" class="form-select">
                                <option value="recent" <?php echo ($sort_by === 'recent') ? 'selected' : ''; ?>>
                                    Most Recent
                                </option>
                                <option value="title" <?php echo ($sort_by === 'title') ? 'selected' : ''; ?>>
                                    Title (A-Z)
                                </option>
                                <option value="company" <?php echo ($sort_by === 'company') ? 'selected' : ''; ?>>
                                    Company (A-Z)
                                </option>
                                <option value="deadline" <?php echo ($sort_by === 'deadline') ? 'selected' : ''; ?>>
                                    Deadline Soon
                                </option>
                            </select>
                        </div>

                        <button type="submit" class="btn-search w-100">
                            <i class="fas fa-search me-2"></i>Search Internships
                        </button>
                        
                        <a href="search.php" class="btn btn-outline-secondary w-100 mt-2">
                            <i class="fas fa-times me-2"></i>Clear Filters
                        </a>
                    </form>
                </div>
            </div>

            <!-- Results -->
            <div class="col-lg-9 col-md-8">
                <?php if (empty($internships)): ?>
                    <div class="text-center py-5">
                        <div class="text-muted mb-4">
                            <i class="fas fa-search fa-3x"></i>
                        </div>
                        <h4>No Internships Found</h4>
                        <p class="text-muted">Try adjusting your search criteria or browse all opportunities.</p>
                        <a href="public_internships.php" class="btn-search">
                            <i class="fas fa-briefcase me-2"></i>Browse All Internships
                        </a>
                    </div>
                <?php else: ?>
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5><i class="fas fa-briefcase me-2"></i><?php echo count($internships); ?> Opportunities Found</h5>
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()">
                                <i class="fas fa-print"></i> Print
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="shareResults()">
                                <i class="fas fa-share"></i> Share
                            </button>
                        </div>
                    </div>

                    <div class="row g-4">
                        <?php foreach ($internships as $internship): ?>
                            <div class="col-lg-6">
                                <div class="internship-card">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div class="flex-grow-1">
                                            <h6 class="card-title mb-2">
                                                <?php echo htmlspecialchars($internship['title']); ?>
                                            </h6>
                                            <div class="text-muted mb-2">
                                                <i class="fas fa-building me-1"></i> 
                                                <?php echo htmlspecialchars($internship['company_name']); ?>
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <span class="badge-department">
                                                <i class="fas <?php echo $internship['department_icon']; ?> me-1"></i>
                                                <?php echo htmlspecialchars($internship['department_name']); ?>
                                            </span>
                                        </div>
                                    </div>

                                    <p class="card-description mb-3">
                                        <?php echo htmlspecialchars(substr($internship['description'], 0, 150)) . '...'; ?>
                                    </p>

                                    <div class="d-flex flex-wrap gap-2 mb-3">
                                        <?php if (!empty($internship['location'])): ?>
                                            <span class="badge bg-light text-dark">
                                                <i class="fas fa-map-marker-alt me-1"></i>
                                                <?php echo htmlspecialchars($internship['location']); ?>
                                            </span>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($internship['work_type']) && $internship['work_type'] !== 'onsite'): ?>
                                            <span class="badge bg-info">
                                                <i class="fas fa-laptop-house me-1"></i>
                                                <?php echo ucfirst($internship['work_type']); ?>
                                            </span>
                                        <?php endif; ?>

                                        <span class="badge bg-warning">
                                            <i class="fas fa-clock me-1"></i>
                                            <?php echo htmlspecialchars($internship['duration']); ?>
                                        </span>

                                        <span class="badge bg-<?php 
                                            echo (new DateTime($internship['expiration_date']) < new DateTime()) ? 'danger' : 'success'; 
                                        ?>">
                                            <i class="fas fa-calendar-alt me-1"></i>
                                            <?php 
                                                if (new DateTime($internship['expiration_date']) < new DateTime()) {
                                                    echo 'Expired';
                                                } else {
                                                    $days_left = (new DateTime($internship['expiration_date']))->diff(new DateTime())->days;
                                                    echo $days_left . ' days left';
                                                }
                                            ?>
                                        </span>
                                    </div>

                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">
                        <i class="fas fa-map-marker-alt me-2"></i>Location
                    </label>
                    <input type="text" name="location" class="form-control" 
                           value="<?php echo htmlspecialchars($location); ?>"
                           placeholder="City, State, or Remote">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">
                        <i class="fas fa-laptop-house me-2"></i>Work Type
                    </label>
                    <select name="work_type" class="form-select">
                        <option value="">All Types</option>
                        <option value="onsite" <?php echo ($work_type === 'onsite') ? 'selected' : ''; ?>>
                            On-site
                        </option>
                        <option value="remote" <?php echo ($work_type === 'remote') ? 'selected' : ''; ?>>
                            Remote
                        </option>
                        <option value="hybrid" <?php echo ($work_type === 'hybrid') ? 'selected' : ''; ?>>
                            Hybrid
                        </option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">
                        <i class="fas fa-clock me-2"></i>Duration
                    </label>
                    <select name="duration" class="form-select">
                        <option value="">All Durations</option>
                        <option value="summer" <?php echo ($duration === 'summer') ? 'selected' : ''; ?>>
                            Summer
                        </option>
                        <option value="semester" <?php echo ($duration === 'semester') ? 'selected' : ''; ?>>
                            Semester
                        </option>
                        <option value="year" <?php echo ($duration === 'year') ? 'selected' : ''; ?>>
                            Year-round
                        </option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">
                        <i class="fas fa-sort me-2"></i>Sort By
                    </label>
                    <select name="sort" class="form-select">
                        <option value="recent" <?php echo ($sort_by === 'recent') ? 'selected' : ''; ?>>
                            Most Recent
                        </option>
                        <option value="title" <?php echo ($sort_by === 'title') ? 'selected' : ''; ?>>
                            Title (A-Z)
                        </option>
                        <option value="company" <?php echo ($sort_by === 'company') ? 'selected' : ''; ?>>
                            Company (A-Z)
                        </option>
                        <option value="deadline" <?php echo ($sort_by === 'deadline') ? 'selected' : ''; ?>>
                            Deadline Soon
                        </option>
                    </select>
                </div>

                <button type="submit" class="btn-search w-100">
                    <i class="fas fa-search me-2"></i>Search Internships
                </button>
                
                <a href="search.php" class="btn btn-outline-secondary w-100 mt-2">
                    <i class="fas fa-times me-2"></i>Clear Filters
                </a>
            </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function shareResults() {
    const url = window.location.href;
    if (navigator.share) {
        navigator.share({
            title: 'Internship Opportunities',
            text: 'Check out these internship opportunities!',
            url: url
        });
    } else {
        navigator.clipboard.writeText(url);
        alert('Search URL copied to clipboard!');
    }
}
</script>
</body>
</html>
