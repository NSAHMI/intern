<?php
session_start();
if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'student') {
    header('Location: ../auth/login.php');
    exit;
}
include "../config/db.php";
include "../config/gamification.php";

// Get internships
$stmt = $conn->prepare(
    'SELECT i.*, u.name as company_name FROM internships i JOIN users u ON i.company_id = u.id WHERE i.expiration_date >= CURDATE() ORDER BY i.created_at DESC'
);
$stmt->execute();
$result = $stmt->get_result();
$internships = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get gamification data
$gamification = new GamificationSystem($conn);
$user_points = $gamification->getUserPoints($_SESSION['user_id']);
$user_level = $gamification->getUserLevel($_SESSION['user_id']);
$recent_achievements = array_slice($gamification->getUserAchievements($_SESSION['user_id']), 0, 3);
$engagement_stats = $gamification->getEngagementStats($_SESSION['user_id']);
?>
<?php include '../includes/mobile_header.php'; ?>
    <title>Student Dashboard - Internship Hub</title>
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
        
        .card-title {
            color: var(--dark-color);
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .card-company {
            color: var(--secondary-color);
            font-weight: 600;
            margin-bottom: 1rem;
        }
        
        .card-description {
            color: #6b7280;
            line-height: 1.6;
            margin-bottom: 1rem;
        }
        
        .card-duration {
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 1rem;
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
        
        .alert-custom {
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            border: none;
            border-radius: 12px;
            color: #92400e;
            padding: 1.5rem;
        }
        
        .no-internships-icon {
            font-size: 3rem;
            color: var(--secondary-color);
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
<div class="main-container">
    <div class="header-section">
        <div class="header-content">
            <h1 class="header-title">Welcome, <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Student', ENT_QUOTES); ?> 👋</h1>
            <p class="header-subtitle">Discover amazing internship opportunities and kickstart your career</p>
            
            <div class="nav-buttons">
                <a href="dashboard.php" class="btn-custom btn-primary-custom">
                    <i class="fas fa-search"></i> Browse Internships
                </a>
                <a href="my_applications.php" class="btn-custom btn-secondary-custom">
                    <i class="fas fa-clipboard-list"></i> My Applications
                </a>
                <a href="gamification.php" class="btn-custom btn-secondary-custom">
                    <i class="fas fa-trophy"></i> Achievements
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
        <!-- Gamification Stats -->
        <div class="row mb-4">
            <div class="col-md-3 col-6 mb-3">
                <div class="text-center p-3 bg-white rounded-3 shadow-sm">
                    <i class="fas fa-trophy fa-2x text-warning mb-2"></i>
                    <div class="h5 mb-1"><?php echo $user_points; ?></div>
                    <small class="text-muted">Total Points</small>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-3">
                <div class="text-center p-3 bg-white rounded-3 shadow-sm">
                    <i class="fas fa-star fa-2x text-primary mb-2"></i>
                    <div class="h5 mb-1">Level <?php echo $user_level['level']; ?></div>
                    <small class="text-muted"><?php echo $user_level['title']; ?></small>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-3">
                <div class="text-center p-3 bg-white rounded-3 shadow-sm">
                    <i class="fas fa-paper-plane fa-2x text-success mb-2"></i>
                    <div class="h5 mb-1"><?php echo $engagement_stats['applications']; ?></div>
                    <small class="text-muted">Applications</small>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-3">
                <div class="text-center p-3 bg-white rounded-3 shadow-sm">
                    <i class="fas fa-medal fa-2x text-info mb-2"></i>
                    <div class="h5 mb-1"><?php echo count($recent_achievements); ?></div>
                    <small class="text-muted">Achievements</small>
                </div>
            </div>
        </div>

        <!-- Recent Achievements -->
        <?php if (!empty($recent_achievements)): ?>
            <div class="alert alert-success border-0 mb-4" style="background: linear-gradient(135deg, #d1fae5, #a7f3d0);">
                <div class="d-flex align-items-center">
                    <i class="fas fa-trophy fa-2x me-3"></i>
                    <div>
                        <h6 class="mb-1">Recent Achievements</h6>
                        <div class="small">
                            <?php foreach ($recent_achievements as $achievement): ?>
                                <span class="badge bg-<?php echo $achievement['badge_color']; ?> me-1">
                                    <i class="fas fa-<?php echo $achievement['icon']; ?> me-1"></i>
                                    <?php echo htmlspecialchars($achievement['name']); ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="mt-2">
                    <a href="gamification.php" class="btn btn-sm btn-outline-success">
                        <i class="fas fa-chart-line me-1"></i>View All Progress
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <?php if (empty($internships)): ?>
            <div class="alert-custom text-center">
                <div class="no-internships-icon">
                    <i class="fas fa-briefcase"></i>
                </div>
                <h4>No internships available yet</h4>
                <p class="mb-3">Check back later for new opportunities from top companies!</p>
                <div class="d-flex justify-content-center gap-2">
                    <button class="btn-apply" onclick="window.location.reload()">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                </div>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($internships as $internship): ?>
                    <div class="col-md-6">
                        <div class="internship-card">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <h5 class="card-title"><?php echo htmlspecialchars($internship['title'], ENT_QUOTES); ?></h5>
                                    <div class="card-company">
                                        <i class="fas fa-building"></i> <?php echo htmlspecialchars($internship['company_name'], ENT_QUOTES); ?>
                                    </div>
                                </div>
                                <div class="d-flex flex-column align-items-end gap-2">
                                    <div class="badge bg-<?php 
                                        echo (new DateTime($internship['expiration_date']) < new DateTime()) ? 'danger' : 'success'; 
                                    ?>">
                                        <i class="fas fa-<?php 
                                            echo (new DateTime($internship['expiration_date']) < new DateTime()) ? 'times-circle' : 'check-circle'; 
                                        ?>"></i> 
                                        <?php 
                                            if (new DateTime($internship['expiration_date']) < new DateTime()) {
                                                echo 'Expired';
                                            } else {
                                                $days_left = (new DateTime($internship['expiration_date']))->diff(new DateTime())->days;
                                                echo $days_left . ' days left';
                                            }
                                        ?>
                                    </div>
                                    <small class="text-muted">
                                        <i class="fas fa-calendar-alt"></i> 
                                        Expires: <?php echo date('M j, Y', strtotime($internship['expiration_date'])); ?>
                                    </small>
                                </div>
                            </div>
                            
                            <p class="card-description"><?php echo htmlspecialchars($internship['description'], ENT_QUOTES); ?></p>
                            
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="card-duration">
                                    <i class="fas fa-calendar-alt"></i> <?php echo htmlspecialchars($internship['duration'], ENT_QUOTES); ?>
                                </div>
                                <a href="apply.php?id=<?php echo urlencode($internship['id']); ?>" 
                                   class="btn-apply <?php echo (new DateTime($internship['expiration_date']) < new DateTime()) ? 'disabled' : ''; ?>"
                                   <?php echo (new DateTime($internship['expiration_date']) < new DateTime()) ? 'style="pointer-events: none; opacity: 0.6;"' : ''; ?>>
                                    <i class="fas fa-paper-plane"></i> 
                                    <?php echo (new DateTime($internship['expiration_date']) < new DateTime()) ? 'Expired' : 'Apply Now'; ?>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/mobile_footer.php'; ?>
