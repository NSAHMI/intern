<?php
session_start();
if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'student') {
    header('Location: ../auth/login.php');
    exit;
}
include "../config/db.php";
include "../config/gamification.php";

$gamification = new GamificationSystem($conn);
$user_id = $_SESSION['user_id'];

// Check for new achievements
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['check_achievements'])) {
    $new_achievements = $gamification->checkAchievements($user_id);
    if (!empty($new_achievements)) {
        $achievement_message = "🎉 Congratulations! You've earned new achievements:<br>";
        foreach ($new_achievements as $achievement) {
            $achievement_message .= "• {$achievement['name']} (+{$achievement['points']} points)<br>";
        }
    } else {
        $achievement_message = "Keep working! No new achievements at the moment.";
    }
}

// Get user data
$user_points = $gamification->getUserPoints($user_id);
$user_level = $gamification->getUserLevel($user_id);
$user_achievements = $gamification->getUserAchievements($user_id);
$leaderboard = $gamification->getLeaderboard(10);
$profile_completion = $gamification->getProfileCompletion($user_id);
$engagement_stats = $gamification->getEngagementStats($user_id);
$available_achievements = $gamification->getAvailableAchievements();

// Filter available achievements to show only locked ones
$locked_achievements = [];
foreach ($available_achievements as $achievement) {
    $is_unlocked = false;
    foreach ($user_achievements as $user_achievement) {
        if ($user_achievement['id'] == $achievement['id']) {
            $is_unlocked = true;
            break;
        }
    }
    if (!$is_unlocked) {
        $locked_achievements[] = $achievement;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Achievements & Progress - Student Dashboard</title>
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
        
        .level-card {
            background: linear-gradient(135deg, #fbbf24, #f59e0b);
            color: white;
            border-radius: 20px;
            padding: 2rem;
            text-align: center;
            box-shadow: var(--card-shadow);
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
        }
        
        .level-card::before {
            content: '';
            position: absolute;
            top: -20px;
            right: -20px;
            width: 100px;
            height: 100px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }
        
        .level-number {
            font-size: 4rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .level-title {
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .points-display {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 15px;
            padding: 1rem;
            margin-top: 1rem;
        }
        
        .progress-ring {
            width: 120px;
            height: 120px;
            margin: 0 auto 1rem;
        }
        
        .progress-ring-circle {
            transition: stroke-dashoffset 0.35s;
            transform: rotate(-90deg);
            transform-origin: 50% 50%;
        }
        
        .achievement-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: var(--card-shadow);
            border: 1px solid #e5e7eb;
            transition: all 0.3s ease;
            margin-bottom: 1rem;
        }
        
        .achievement-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--card-hover-shadow);
        }
        
        .achievement-card.unlocked {
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
            border-color: #10b981;
        }
        
        .achievement-card.locked {
            opacity: 0.7;
            background: #f9fafb;
        }
        
        .achievement-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .leaderboard-item {
            display: flex;
            align-items: center;
            padding: 1rem;
            border-bottom: 1px solid #f3f4f6;
            transition: all 0.3s ease;
        }
        
        .leaderboard-item:hover {
            background: #f9fafb;
        }
        
        .rank-badge {
            width: 40px;
            height: 40px;
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
        
        .stat-box {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: var(--card-shadow);
            border: 1px solid #e5e7eb;
            transition: all 0.3s ease;
        }
        
        .stat-box:hover {
            transform: translateY(-2px);
            box-shadow: var(--card-hover-shadow);
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .stat-label {
            color: #6b7280;
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }
        
        .btn-check {
            background: linear-gradient(135deg, var(--success-color), #059669);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-check:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
        }
    </style>
</head>
<body>
<div class="main-container">
    <div class="header-section">
        <div class="header-content">
            <h1 class="header-title">Achievements & Progress 🏆</h1>
            <p class="mb-0">Track your journey and unlock new achievements</p>
            
            <div class="nav-buttons">
                <a href="dashboard.php" class="btn-custom btn-primary-custom">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
                <a href="profile.php" class="btn-custom btn-secondary-custom">
                    <i class="fas fa-user"></i> My Profile
                </a>
                <a href="../messages.php" class="btn-custom btn-secondary-custom">
                    <i class="fas fa-envelope"></i> Messages
                </a>
                <a href="../auth/logout.php" class="btn-custom btn-secondary-custom">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </div>

    <div class="content-section">
        <?php if (isset($achievement_message)): ?>
            <div class="alert alert-success border-0 mb-4" style="background: linear-gradient(135deg, #d1fae5, #a7f3d0);">
                <i class="fas fa-trophy me-2"></i> <?php echo $achievement_message; ?>
            </div>
        <?php endif; ?>

        <!-- Level & Progress -->
        <div class="level-card">
            <div class="row align-items-center">
                <div class="col-md-4">
                    <div class="level-number"><?php echo $user_level['level']; ?></div>
                    <div class="level-title"><?php echo $user_level['title']; ?></div>
                    <div class="points-display">
                        <div class="h4 mb-1"><?php echo $user_points; ?> Points</div>
                        <small>Next level: <?php echo $user_level['next_points']; ?> points</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="progress-ring">
                        <svg width="120" height="120">
                            <circle class="progress-ring-circle" stroke="#ffffff" stroke-width="8" fill="transparent" r="52" cx="60" cy="60" 
                                    stroke-dasharray="<?php echo (2 * 3.14159 * 52); ?> <?php echo (2 * 3.14159 * 52); ?>"
                                    stroke-dashoffset="<?php echo (2 * 3.14159 * 52) * (1 - ($user_points / max($user_level['next_points'], 1))); ?>"></circle>
                        </svg>
                        <div style="margin-top: -80px; font-size: 1.5rem; font-weight: 700;"><?php echo round(($user_points / max($user_level['next_points'], 1)) * 100); ?>%</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <form method="post">
                        <button type="submit" name="check_achievements" class="btn-check">
                            <i class="fas fa-sync me-2"></i>Check for New Achievements
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="row mb-4">
            <div class="col-md-2 col-6 mb-3">
                <div class="stat-box">
                    <div class="stat-number"><?php echo $engagement_stats['applications']; ?></div>
                    <div class="stat-label">Applications</div>
                </div>
            </div>
            <div class="col-md-2 col-6 mb-3">
                <div class="stat-box">
                    <div class="stat-number"><?php echo $engagement_stats['interviews']; ?></div>
                    <div class="stat-label">Interviews</div>
                </div>
            </div>
            <div class="col-md-2 col-6 mb-3">
                <div class="stat-box">
                    <div class="stat-number"><?php echo $engagement_stats['skills']; ?></div>
                    <div class="stat-label">Skills</div>
                </div>
            </div>
            <div class="col-md-2 col-6 mb-3">
                <div class="stat-box">
                    <div class="stat-number"><?php echo $engagement_stats['bookmarks']; ?></div>
                    <div class="stat-label">Bookmarks</div>
                </div>
            </div>
            <div class="col-md-2 col-6 mb-3">
                <div class="stat-box">
                    <div class="stat-number"><?php echo $engagement_stats['messages_sent']; ?></div>
                    <div class="stat-label">Messages</div>
                </div>
            </div>
            <div class="col-md-2 col-6 mb-3">
                <div class="stat-box">
                    <div class="stat-number"><?php echo $profile_completion; ?>%</div>
                    <div class="stat-label">Profile Complete</div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Unlocked Achievements -->
            <div class="col-lg-6">
                <h4 class="mb-3"><i class="fas fa-trophy me-2"></i>Unlocked Achievements</h4>
                <?php if (!empty($user_achievements)): ?>
                    <?php foreach ($user_achievements as $achievement): ?>
                        <div class="achievement-card unlocked">
                            <div class="d-flex align-items-center">
                                <div class="achievement-icon bg-<?php echo $achievement['badge_color']; ?> text-white">
                                    <i class="fas fa-<?php echo $achievement['icon']; ?>"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($achievement['name']); ?></h6>
                                    <p class="mb-1 text-muted small"><?php echo htmlspecialchars($achievement['description']); ?></p>
                                    <small class="text-muted">
                                        <i class="fas fa-star me-1"></i><?php echo $achievement['points']; ?> points • 
                                        Earned: <?php echo date('M j, Y', strtotime($achievement['earned_at'])); ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-lock fa-2x text-muted mb-2"></i>
                        <p class="text-muted">No achievements unlocked yet. Start applying to internships!</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Locked Achievements -->
            <div class="col-lg-6">
                <h4 class="mb-3"><i class="fas fa-lock me-2"></i>Locked Achievements</h4>
                <?php if (!empty($locked_achievements)): ?>
                    <?php foreach ($locked_achievements as $achievement): ?>
                        <div class="achievement-card locked">
                            <div class="d-flex align-items-center">
                                <div class="achievement-icon bg-secondary text-white">
                                    <i class="fas fa-<?php echo $achievement['icon']; ?>"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($achievement['name']); ?></h6>
                                    <p class="mb-1 text-muted small"><?php echo htmlspecialchars($achievement['description']); ?></p>
                                    <small class="text-muted">
                                        <i class="fas fa-star me-1"></i><?php echo $achievement['points']; ?> points • 
                                        <i class="fas fa-lock me-1"></i>Locked
                                    </small>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                        <p class="text-muted">All achievements unlocked! You're a champion!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Leaderboard -->
        <div class="row mt-4">
            <div class="col-12">
                <h4 class="mb-3"><i class="fas fa-crown me-2"></i>Leaderboard</h4>
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <?php if (!empty($leaderboard)): ?>
                            <?php foreach ($leaderboard as $index => $user): ?>
                                <div class="leaderboard-item">
                                    <div class="rank-badge rank-<?php echo ($index < 3) ? ($index + 1) : 'other'; ?>">
                                        <?php echo $index + 1; ?>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="fw-bold"><?php echo htmlspecialchars($user['name']); ?></div>
                                        <small class="text-muted">
                                            <?php echo ucfirst($user['role']); ?> • 
                                            <?php echo $user['achievements_count']; ?> achievements
                                        </small>
                                    </div>
                                    <div class="text-end">
                                        <div class="fw-bold text-primary"><?php echo $user['total_points']; ?></div>
                                        <small class="text-muted">points</small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted text-center">No leaderboard data available yet</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
