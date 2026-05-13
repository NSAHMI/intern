<?php
session_start();
if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'company') {
    header('Location: ../auth/login.php');
    exit;
}
include "../config/db.php";

// Get company's internships
$stmt = $conn->prepare(
    'SELECT id, title, description, duration, created_at
     FROM internships
     WHERE company_id = ?
     ORDER BY created_at DESC'
);
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$internships = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get application counts for each internship
foreach ($internships as &$internship) {
    $stmt = $conn->prepare(
        'SELECT COUNT(*) as count, status
         FROM applications
         WHERE internship_id = ?
         GROUP BY status'
    );
    $stmt->bind_param('i', $internship['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $internship['applications'] = [];
    while ($row = $result->fetch_assoc()) {
        $internship['applications'][$row['status']] = $row['count'];
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Company Dashboard</title>
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
        
        .btn-apply {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-apply:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);
        }
    </style>
</head>
<body>
<div class="main-container">
    <div class="header-section">
        <div class="header-content">
            <h1 class="header-title">Company Portal 🏢</h1>
            <p class="header-subtitle">Manage your internship postings and connect with talented students</p>
            
            <div class="nav-buttons">
                <a href="dashboard.php" class="btn-custom btn-primary-custom">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="post_internship.php" class="btn-custom btn-secondary-custom">
                    <i class="fas fa-plus-circle"></i> Post Internship
                </a>
                <a href="../auth/setup_email_2fa.php" class="btn-custom btn-secondary-custom">
                    <i class="fas fa-shield-alt"></i> 2FA Settings
                </a>
                <a href="../auth/logout.php" class="btn-custom btn-secondary-custom">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </div>

    <div class="content-section">
        <?php if (empty($internships)): ?>
            <div class="text-center py-5">
                <div class="text-muted mb-4">
                    <i class="fas fa-briefcase fa-4x"></i>
                </div>
                <h4>No Internships Posted Yet</h4>
                <p class="text-muted mb-4">Start by posting your first internship to connect with talented students!</p>
                <a href="post_internship.php" class="btn-apply">
                    <i class="fas fa-plus-circle me-2"></i>Post Your First Internship
                </a>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($internships as $internship): ?>
                    <div class="col-md-6">
                        <div class="internship-card">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h5 class="fw-bold text-dark"><?php echo htmlspecialchars($internship['title'], ENT_QUOTES); ?></h5>
                                    <small class="text-muted">
                                        <i class="fas fa-calendar me-1"></i>
                                        Posted: <?php echo date('M j, Y', strtotime($internship['created_at'])); ?>
                                    </small>
                                </div>
                                <div class="badge bg-success">
                                    <i class="fas fa-check-circle"></i> Active
                                </div>
                            </div>
                            
                            <p class="text-muted mb-3">
                                <?php echo htmlspecialchars(substr($internship['description'], 0, 120), ENT_QUOTES); ?>...
                            </p>
                            
                            <div class="mb-3">
                                <span class="badge bg-primary me-2">
                                    <i class="fas fa-clock"></i> <?php echo htmlspecialchars($internship['duration'], ENT_QUOTES); ?>
                                </span>
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="badge bg-warning me-1">
                                        <i class="fas fa-hourglass-half"></i> <?php echo $internship['applications']['pending'] ?? 0; ?> Pending
                                    </span>
                                    <span class="badge bg-success">
                                        <i class="fas fa-user-check"></i> <?php echo $internship['applications']['accepted'] ?? 0; ?> Accepted
                                    </span>
                                </div>
                                <button class="btn-apply btn-sm" onclick="viewApplications(<?php echo $internship['id']; ?>)">
                                    <i class="fas fa-eye me-1"></i> View Applications
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function viewApplications(internshipId) {
    window.location.href = 'view_applications.php?id=' + internshipId;
}
</script>
</body>
</html>
