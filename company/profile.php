<?php
session_start();
if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'company') {
    header('Location: ../auth/login.php');
    exit;
}
include "../config/db.php";

$errors = [];
$success = '';

// Get company profile
$profile_stmt = $conn->prepare('
    SELECT cp.*, u.name, u.email 
    FROM company_profiles cp 
    JOIN users u ON cp.user_id = u.id 
    WHERE cp.user_id = ?
');
$profile_stmt->bind_param('i', $_SESSION['user_id']);
$profile_stmt->execute();
$profile = $profile_stmt->get_result()->fetch_assoc();
$profile_stmt->close();

// Get company internships
$internships_stmt = $conn->prepare('
    SELECT i.*, d.name as department_name, 
           (SELECT COUNT(*) FROM applications a WHERE a.internship_id = i.id) as application_count
    FROM internships i 
    JOIN departments d ON i.department_id = d.id
    WHERE i.company_id = ?
    ORDER BY i.created_at DESC
');
$internships_stmt->bind_param('i', $_SESSION['user_id']);
$internships_stmt->execute();
$internships = $internships_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$internships_stmt->close();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $company_name = trim($_POST['company_name'] ?? '');
    $website = trim($_POST['website'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $industry = trim($_POST['industry'] ?? '');
    $company_size = $_POST['company_size'] ?? '11-50';
    $founded_year = $_POST['founded_year'] ?? '';
    $location = trim($_POST['location'] ?? '');
    $linkedin_url = trim($_POST['linkedin_url'] ?? '');
    $twitter_url = trim($_POST['twitter_url'] ?? '');
    $facebook_url = trim($_POST['facebook_url'] ?? '');

    // Handle logo upload
    $logo_path = $profile['logo_path'] ?? '';
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $file_info = finfo_open(FILEINFO_MIME_TYPE);
        $file_type = finfo_file($file_info, $_FILES['logo']['tmp_name']);
        finfo_close($file_info);

        if (in_array($file_type, $allowed_types)) {
            $upload_dir = '../uploads/logos/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $filename = 'logo_' . $_SESSION['user_id'] . '_' . time() . '_' . basename($_FILES['logo']['name']);
            $upload_path = $upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['logo']['tmp_name'], $upload_path)) {
                $logo_path = $filename;
            } else {
                $errors[] = 'Failed to upload logo file.';
            }
        } else {
            $errors[] = 'Invalid file type. Please upload JPG, PNG, GIF, or WebP images.';
        }
    }

    if (empty($errors)) {
        // Prepare social links as JSON
        $social_links = json_encode([
            'linkedin' => $linkedin_url,
            'twitter' => $twitter_url,
            'facebook' => $facebook_url
        ]);

        // Update or insert profile
        if ($profile) {
            $stmt = $conn->prepare('
                UPDATE company_profiles SET 
                company_name = ?, website = ?, description = ?, industry = ?, 
                company_size = ?, founded_year = ?, location = ?, logo_path = ?, 
                social_links = ?, updated_at = CURRENT_TIMESTAMP
                WHERE user_id = ?
            ');
            $stmt->bind_param('sssssisssi', $company_name, $website, $description, $industry, 
                              $company_size, $founded_year, $location, $logo_path, $social_links, $_SESSION['user_id']);
        } else {
            $stmt = $conn->prepare('
                INSERT INTO company_profiles 
                (user_id, company_name, website, description, industry, company_size, founded_year, location, logo_path, social_links) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ');
            $stmt->bind_param('issssissss', $_SESSION['user_id'], $company_name, $website, $description, 
                              $industry, $company_size, $founded_year, $location, $logo_path, $social_links);
        }

        if ($stmt->execute()) {
            $success = 'Company profile updated successfully!';
        } else {
            $errors[] = 'Failed to update profile. Please try again.';
        }
        $stmt->close();

        // Refresh profile data
        $profile_stmt = $conn->prepare('
            SELECT cp.*, u.name, u.email 
            FROM company_profiles cp 
            JOIN users u ON cp.user_id = u.id 
            WHERE cp.user_id = ?
        ');
        $profile_stmt->bind_param('i', $_SESSION['user_id']);
        $profile_stmt->execute();
        $profile = $profile_stmt->get_result()->fetch_assoc();
        $profile_stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Company Profile - Dashboard</title>
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
        
        .profile-card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: var(--card-shadow);
            border: 1px solid #e5e7eb;
            margin-bottom: 2rem;
        }
        
        .company-logo {
            width: 120px;
            height: 120px;
            border-radius: 16px;
            object-fit: cover;
            border: 4px solid #f3f4f6;
        }
        
        .logo-placeholder {
            width: 120px;
            height: 120px;
            border-radius: 16px;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3rem;
            font-weight: 700;
            margin: 0 auto 1rem;
        }
        
        .verified-badge {
            background: linear-gradient(135deg, var(--success-color), #059669);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .social-links {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }
        
        .social-link {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .social-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }
        
        .social-link.linkedin { background: #0077b5; }
        .social-link.twitter { background: #1da1f2; }
        .social-link.facebook { background: #1877f2; }
        
        .internship-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: var(--card-shadow);
            border: 1px solid #e5e7eb;
            transition: all 0.3s ease;
            margin-bottom: 1rem;
        }
        
        .internship-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--card-hover-shadow);
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
        
        .btn-save {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);
        }
        
        .upload-area {
            border: 2px dashed #e5e7eb;
            border-radius: 10px;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .upload-area:hover {
            border-color: var(--primary-color);
            background: #f9fafb;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-box {
            background: linear-gradient(135deg, #f9fafb, #e5e7eb);
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
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
    </style>
</head>
<body>
<div class="main-container">
    <div class="header-section">
        <div class="header-content">
            <h1 class="header-title">Company Profile 🏢</h1>
            <p class="header-subtitle">Manage your company profile and showcase your brand</p>
            
            <div class="nav-buttons">
                <a href="dashboard.php" class="btn-custom btn-primary-custom">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
                <a href="post_internship.php" class="btn-custom btn-secondary-custom">
                    <i class="fas fa-plus"></i> Post Internship
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
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger border-0 mb-4" style="background: linear-gradient(135deg, #fee2e2, #fecaca);">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php echo implode('<br>', $errors); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success border-0 mb-4" style="background: linear-gradient(135deg, #d1fae5, #a7f3d0);">
                <i class="fas fa-check-circle me-2"></i> <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data">
            <div class="row">
                <!-- Profile Overview -->
                <div class="col-lg-4">
                    <div class="profile-card text-center">
                        <?php if (!empty($profile['logo_path'])): ?>
                            <img src="../uploads/logos/<?php echo htmlspecialchars($profile['logo_path']); ?>" 
                                 alt="Company Logo" class="company-logo mb-3">
                        <?php else: ?>
                            <div class="logo-placeholder">
                                <?php echo strtoupper(substr($profile['company_name'] ?? $_SESSION['user_name'], 0, 2)); ?>
                            </div>
                        <?php endif; ?>
                        
                        <h4><?php echo htmlspecialchars($profile['company_name'] ?? $_SESSION['user_name']); ?></h4>
                        <p class="text-muted"><?php echo htmlspecialchars($profile['industry'] ?? 'Company'); ?></p>
                        
                        <?php if ($profile['is_verified']): ?>
                            <span class="verified-badge mb-3">
                                <i class="fas fa-check-circle me-1"></i>Verified Company
                            </span>
                        <?php endif; ?>

                        <?php if (!empty($profile['social_links'])): ?>
                            <?php 
                            $social_links = json_decode($profile['social_links'], true);
                            if (!empty($social_links['linkedin']) || !empty($social_links['twitter']) || !empty($social_links['facebook'])):
                            ?>
                                <div class="social-links justify-content-center">
                                    <?php if (!empty($social_links['linkedin'])): ?>
                                        <a href="<?php echo htmlspecialchars($social_links['linkedin']); ?>" target="_blank" class="social-link linkedin">
                                            <i class="fab fa-linkedin-in"></i>
                                        </a>
                                    <?php endif; ?>
                                    <?php if (!empty($social_links['twitter'])): ?>
                                        <a href="<?php echo htmlspecialchars($social_links['twitter']); ?>" target="_blank" class="social-link twitter">
                                            <i class="fab fa-twitter"></i>
                                        </a>
                                    <?php endif; ?>
                                    <?php if (!empty($social_links['facebook'])): ?>
                                        <a href="<?php echo htmlspecialchars($social_links['facebook']); ?>" target="_blank" class="social-link facebook">
                                            <i class="fab fa-facebook-f"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>

                        <div class="mt-4">
                            <h6>Quick Stats</h6>
                            <div class="stats-grid">
                                <div class="stat-box">
                                    <div class="stat-number"><?php echo count($internships); ?></div>
                                    <div class="stat-label">Active Postings</div>
                                </div>
                                <div class="stat-box">
                                    <div class="stat-number"><?php echo array_sum(array_column($internships, 'application_count')); ?></div>
                                    <div class="stat-label">Total Applications</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Profile Form -->
                <div class="col-lg-8">
                    <div class="profile-card">
                        <h5 class="mb-4"><i class="fas fa-building me-2"></i>Company Information</h5>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">
                                        <i class="fas fa-building me-2"></i>Company Name
                                    </label>
                                    <input type="text" name="company_name" class="form-control" 
                                           value="<?php echo htmlspecialchars($profile['company_name'] ?? ''); ?>"
                                           placeholder="Your company name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">
                                        <i class="fas fa-globe me-2"></i>Website
                                    </label>
                                    <input type="url" name="website" class="form-control" 
                                           value="<?php echo htmlspecialchars($profile['website'] ?? ''); ?>"
                                           placeholder="https://yourcompany.com">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">
                                        <i class="fas fa-industry me-2"></i>Industry
                                    </label>
                                    <input type="text" name="industry" class="form-control" 
                                           value="<?php echo htmlspecialchars($profile['industry'] ?? ''); ?>"
                                           placeholder="e.g. Technology, Healthcare, Finance">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">
                                        <i class="fas fa-users me-2"></i>Company Size
                                    </label>
                                    <select name="company_size" class="form-select">
                                        <option value="1-10" <?php echo ($profile['company_size'] ?? '') === '1-10' ? 'selected' : ''; ?>>1-10</option>
                                        <option value="11-50" <?php echo ($profile['company_size'] ?? '') === '11-50' ? 'selected' : ''; ?>>11-50</option>
                                        <option value="51-200" <?php echo ($profile['company_size'] ?? '') === '51-200' ? 'selected' : ''; ?>>51-200</option>
                                        <option value="201-500" <?php echo ($profile['company_size'] ?? '') === '201-500' ? 'selected' : ''; ?>>201-500</option>
                                        <option value="500+" <?php echo ($profile['company_size'] ?? '') === '500+' ? 'selected' : ''; ?>>500+</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">
                                        <i class="fas fa-calendar me-2"></i>Founded Year
                                    </label>
                                    <input type="number" name="founded_year" class="form-control" 
                                           value="<?php echo htmlspecialchars($profile['founded_year'] ?? ''); ?>"
                                           placeholder="2020" min="1800" max="<?php echo date('Y'); ?>">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-map-marker-alt me-2"></i>Location
                            </label>
                            <input type="text" name="location" class="form-control" 
                                   value="<?php echo htmlspecialchars($profile['location'] ?? ''); ?>"
                                   placeholder="City, State, Country">
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-align-left me-2"></i>Company Description
                            </label>
                            <textarea name="description" class="form-control" rows="4" 
                                      placeholder="Tell students about your company culture, mission, and what makes you a great place to work..."><?php echo htmlspecialchars($profile['description'] ?? ''); ?></textarea>
                            <div class="form-text">Write a compelling description that attracts top talent.</div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-share-alt me-2"></i>Social Media Links
                            </label>
                            <div class="row">
                                <div class="col-md-4">
                                    <input type="url" name="linkedin_url" class="form-control" 
                                           value="<?php echo htmlspecialchars(json_decode($profile['social_links'] ?? '{}', true)['linkedin'] ?? ''); ?>"
                                           placeholder="LinkedIn URL">
                                </div>
                                <div class="col-md-4">
                                    <input type="url" name="twitter_url" class="form-control" 
                                           value="<?php echo htmlspecialchars(json_decode($profile['social_links'] ?? '{}', true)['twitter'] ?? ''); ?>"
                                           placeholder="Twitter URL">
                                </div>
                                <div class="col-md-4">
                                    <input type="url" name="facebook_url" class="form-control" 
                                           value="<?php echo htmlspecialchars(json_decode($profile['social_links'] ?? '{}', true)['facebook'] ?? ''); ?>"
                                           placeholder="Facebook URL">
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-image me-2"></i>Company Logo
                            </label>
                            <div class="upload-area" onclick="document.getElementById('logo').click()">
                                <i class="fas fa-cloud-upload-alt fa-2x text-muted mb-2"></i>
                                <p class="mb-1">Click to upload or drag and drop</p>
                                <small class="text-muted">PNG, JPG, GIF up to 5MB</small>
                                <input type="file" name="logo" id="logo" class="d-none" accept="image/*">
                            </div>
                            <?php if (!empty($profile['logo_path'])): ?>
                                <small class="text-success">
                                    <i class="fas fa-check-circle me-1"></i>
                                    Current logo: <?php echo htmlspecialchars($profile['logo_path']); ?>
                                </small>
                            <?php endif; ?>
                        </div>

                        <button type="submit" class="btn-save">
                            <i class="fas fa-save me-2"></i>Save Profile
                        </button>
                    </div>
                </div>
            </div>
        </form>

        <!-- Recent Internships -->
        <?php if (!empty($internships)): ?>
            <div class="profile-card">
                <h5 class="mb-4"><i class="fas fa-briefcase me-2"></i>Recent Internship Postings</h5>
                <div class="row">
                    <?php foreach (array_slice($internships, 0, 6) as $internship): ?>
                        <div class="col-md-6">
                            <div class="internship-card">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($internship['title']); ?></h6>
                                    <span class="badge bg-primary">
                                        <?php echo htmlspecialchars($internship['department_name']); ?>
                                    </span>
                                </div>
                                <p class="text-muted small mb-2">
                                    <?php echo htmlspecialchars(substr($internship['description'], 0, 100)) . '...'; ?>
                                </p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        <i class="fas fa-users me-1"></i>
                                        <?php echo $internship['application_count']; ?> applicants
                                    </small>
                                    <a href="view_applications.php?id=<?php echo $internship['id']; ?>" 
                                       class="btn btn-sm btn-outline-primary">
                                        View Applications
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php if (count($internships) > 6): ?>
                    <div class="text-center mt-3">
                        <a href="dashboard.php" class="btn btn-outline-primary">
                            View All Internships
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.getElementById('logo').addEventListener('change', function(e) {
    const fileName = e.target.files[0]?.name;
    if (fileName) {
        document.querySelector('.upload-area p').textContent = 'Selected: ' + fileName;
    }
});
</script>
</body>
</html>
