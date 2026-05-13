<?php
session_start();
if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'student') {
    header('Location: ../auth/login.php');
    exit;
}
include "../config/db.php";

$errors = [];
$success = '';

// Get student profile
$profile_stmt = $conn->prepare('
    SELECT sp.*, u.name, u.email 
    FROM student_profiles sp 
    JOIN users u ON sp.user_id = u.id 
    WHERE sp.user_id = ?
');
$profile_stmt->bind_param('i', $_SESSION['user_id']);
$profile_stmt->execute();
$profile = $profile_stmt->get_result()->fetch_assoc();
$profile_stmt->close();

// Get student skills
$skills_stmt = $conn->prepare('
    SELECT s.name, ss.proficiency_level 
    FROM student_skills ss 
    JOIN skills s ON ss.skill_id = s.id 
    WHERE ss.student_id = ?
');
$skills_stmt->bind_param('i', $_SESSION['user_id']);
$skills_stmt->execute();
$student_skills = $skills_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$skills_stmt->close();

// Get all available skills
$all_skills_stmt = $conn->prepare('SELECT * FROM skills ORDER BY category, name');
$all_skills_stmt->execute();
$all_skills = $all_skills_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$all_skills_stmt->close();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bio = trim($_POST['bio'] ?? '');
    $gpa = $_POST['gpa'] ?? '';
    $graduation_year = $_POST['graduation_year'] ?? '';
    $university = trim($_POST['university'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $linkedin_url = trim($_POST['linkedin_url'] ?? '');
    $portfolio_url = trim($_POST['portfolio_url'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    // Handle resume upload
    $resume_path = $profile['resume_path'] ?? '';
    if (isset($_FILES['resume']) && $_FILES['resume']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        $file_info = finfo_open(FILEINFO_MIME_TYPE);
        $file_type = finfo_file($file_info, $_FILES['resume']['tmp_name']);
        finfo_close($file_info);

        if (in_array($file_type, $allowed_types)) {
            $upload_dir = '../uploads/resumes/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $filename = 'resume_' . $_SESSION['user_id'] . '_' . time() . '_' . basename($_FILES['resume']['name']);
            $upload_path = $upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['resume']['tmp_name'], $upload_path)) {
                $resume_path = $filename;
            } else {
                $errors[] = 'Failed to upload resume file.';
            }
        } else {
            $errors[] = 'Invalid file type. Please upload PDF or Word documents.';
        }
    }

    // Handle skills
    $selected_skills = $_POST['skills'] ?? [];
    $skill_levels = $_POST['skill_levels'] ?? [];

    if (empty($errors)) {
        // Update or insert profile
        if ($profile) {
            $stmt = $conn->prepare('
                UPDATE student_profiles SET 
                bio = ?, gpa = ?, graduation_year = ?, university = ?, 
                location = ?, linkedin_url = ?, portfolio_url = ?, 
                phone = ?, resume_path = ?, updated_at = CURRENT_TIMESTAMP
                WHERE user_id = ?
            ');
            $stmt->bind_param('sdissssssi', $bio, $gpa, $graduation_year, $university, 
                              $location, $linkedin_url, $portfolio_url, $phone, $resume_path, $_SESSION['user_id']);
        } else {
            $stmt = $conn->prepare('
                INSERT INTO student_profiles 
                (user_id, bio, gpa, graduation_year, university, location, linkedin_url, portfolio_url, phone, resume_path) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ');
            $stmt->bind_param('isdissssss', $_SESSION['user_id'], $bio, $gpa, $graduation_year, 
                              $university, $location, $linkedin_url, $portfolio_url, $phone, $resume_path);
        }

        if ($stmt->execute()) {
            // Update skills
            $conn->prepare('DELETE FROM student_skills WHERE student_id = ?')->bind_param('i', $_SESSION['user_id'])->execute();
            
            foreach ($selected_skills as $skill_id) {
                $level = $skill_levels[$skill_id] ?? 'intermediate';
                $skill_stmt = $conn->prepare('INSERT INTO student_skills (student_id, skill_id, proficiency_level) VALUES (?, ?, ?)');
                $skill_stmt->bind_param('iis', $_SESSION['user_id'], $skill_id, $level);
                $skill_stmt->execute();
                $skill_stmt->close();
            }

            // Update profile completion percentage
            updateProfileCompletion($conn, $_SESSION['user_id']);
            $success = 'Profile updated successfully!';
        } else {
            $errors[] = 'Failed to update profile. Please try again.';
        }
        $stmt->close();

        // Refresh profile data
        $profile_stmt = $conn->prepare('
            SELECT sp.*, u.name, u.email 
            FROM student_profiles sp 
            JOIN users u ON sp.user_id = u.id 
            WHERE sp.user_id = ?
        ');
        $profile_stmt->bind_param('i', $_SESSION['user_id']);
        $profile_stmt->execute();
        $profile = $profile_stmt->get_result()->fetch_assoc();
        $profile_stmt->close();
    }
}

function updateProfileCompletion($conn, $user_id) {
    $stmt = $conn->prepare('SELECT * FROM student_profiles WHERE user_id = ?');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $profile = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $completion = 0;
    $fields = ['bio', 'gpa', 'graduation_year', 'university', 'location', 'phone', 'resume_path'];
    
    foreach ($fields as $field) {
        if (!empty($profile[$field])) {
            $completion += 10;
        }
    }

    // Check skills
    $skills_stmt = $conn->prepare('SELECT COUNT(*) as count FROM student_skills WHERE student_id = ?');
    $skills_stmt->bind_param('i', $user_id);
    $skills_stmt->execute();
    $skills_count = $skills_stmt->get_result()->fetch_assoc()['count'];
    $skills_stmt->close();

    if ($skills_count >= 3) {
        $completion += 30;
    }

    $update_stmt = $conn->prepare('UPDATE student_profiles SET profile_complete_percentage = ? WHERE user_id = ?');
    $update_stmt->bind_param('ii', $completion, $user_id);
    $update_stmt->execute();
    $update_stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Profile - Student Dashboard</title>
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
        
        .completion-meter {
            background: #e5e7eb;
            border-radius: 10px;
            height: 20px;
            overflow: hidden;
            margin-bottom: 0.5rem;
        }
        
        .completion-bar {
            background: linear-gradient(135deg, var(--success-color), #059669);
            height: 100%;
            transition: width 0.3s ease;
        }
        
        .skill-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            margin: 0.25rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
        }
        
        .skill-beginner { background: #fef3c7; color: #92400e; }
        .skill-intermediate { background: #dbeafe; color: #1e40af; }
        .skill-advanced { background: #e9d5ff; color: #6b21a8; }
        .skill-expert { background: #fecaca; color: #991b1b; }
        
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
        
        .avatar-placeholder {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3rem;
            font-weight: 700;
            margin: 0 auto 1rem;
        }
    </style>
</head>
<body>
<div class="main-container">
    <div class="header-section">
        <div class="header-content">
            <h1 class="header-title">My Profile 👤</h1>
            <p class="header-subtitle">Manage your professional profile and skills</p>
            
            <div class="nav-buttons">
                <a href="dashboard.php" class="btn-custom btn-primary-custom">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
                <a href="my_applications.php" class="btn-custom btn-secondary-custom">
                    <i class="fas fa-clipboard-list"></i> My Applications
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
                        <div class="avatar-placeholder">
                            <?php echo strtoupper(substr($_SESSION['user_name'], 0, 2)); ?>
                        </div>
                        <h4><?php echo htmlspecialchars($_SESSION['user_name']); ?></h4>
                        <p class="text-muted"><?php echo htmlspecialchars($profile['email'] ?? ''); ?></p>
                        
                        <div class="mt-3">
                            <div class="completion-meter">
                                <div class="completion-bar" style="width: <?php echo $profile['profile_complete_percentage'] ?? 0; ?>%"></div>
                            </div>
                            <small class="text-muted">Profile Complete: <?php echo $profile['profile_complete_percentage'] ?? 0; ?>%</small>
                        </div>

                        <div class="mt-4">
                            <h6>Skills</h6>
                            <div class="text-start">
                                <?php if (!empty($student_skills)): ?>
                                    <?php foreach ($student_skills as $skill): ?>
                                        <span class="skill-badge skill-<?php echo $skill['proficiency_level']; ?>">
                                            <?php echo htmlspecialchars($skill['name']); ?>
                                        </span>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="text-muted small">No skills added yet</p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if (!empty($profile['resume_path'])): ?>
                            <div class="mt-3">
                                <a href="../uploads/resumes/<?php echo htmlspecialchars($profile['resume_path']); ?>" 
                                   class="btn btn-outline-primary btn-sm" target="_blank">
                                    <i class="fas fa-file-pdf me-1"></i>View Resume
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Profile Form -->
                <div class="col-lg-8">
                    <div class="profile-card">
                        <h5 class="mb-4"><i class="fas fa-user-edit me-2"></i>Edit Profile</h5>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">
                                        <i class="fas fa-graduation-cap me-2"></i>University
                                    </label>
                                    <input type="text" name="university" class="form-control" 
                                           value="<?php echo htmlspecialchars($profile['university'] ?? ''); ?>"
                                           placeholder="Your university name">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">
                                        <i class="fas fa-calendar me-2"></i>Graduation Year
                                    </label>
                                    <input type="number" name="graduation_year" class="form-control" 
                                           value="<?php echo htmlspecialchars($profile['graduation_year'] ?? ''); ?>"
                                           placeholder="2024" min="2020" max="2030">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">
                                        <i class="fas fa-chart-line me-2"></i>GPA
                                    </label>
                                    <input type="number" name="gpa" class="form-control" 
                                           value="<?php echo htmlspecialchars($profile['gpa'] ?? ''); ?>"
                                           placeholder="3.5" step="0.1" min="0" max="4">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">
                                        <i class="fas fa-map-marker-alt me-2"></i>Location
                                    </label>
                                    <input type="text" name="location" class="form-control" 
                                           value="<?php echo htmlspecialchars($profile['location'] ?? ''); ?>"
                                           placeholder="City, State">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-phone me-2"></i>Phone
                            </label>
                            <input type="tel" name="phone" class="form-control" 
                                   value="<?php echo htmlspecialchars($profile['phone'] ?? ''); ?>"
                                   placeholder="+1 (555) 123-4567">
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">
                                        <i class="fab fa-linkedin me-2"></i>LinkedIn URL
                                    </label>
                                    <input type="url" name="linkedin_url" class="form-control" 
                                           value="<?php echo htmlspecialchars($profile['linkedin_url'] ?? ''); ?>"
                                           placeholder="https://linkedin.com/in/yourprofile">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">
                                        <i class="fas fa-globe me-2"></i>Portfolio URL
                                    </label>
                                    <input type="url" name="portfolio_url" class="form-control" 
                                           value="<?php echo htmlspecialchars($profile['portfolio_url'] ?? ''); ?>"
                                           placeholder="https://yourportfolio.com">
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-align-left me-2"></i>Bio
                            </label>
                            <textarea name="bio" class="form-control" rows="4" 
                                      placeholder="Tell us about yourself, your career goals, and what you're looking for in an internship..."><?php echo htmlspecialchars($profile['bio'] ?? ''); ?></textarea>
                            <div class="form-text">Write a compelling bio that showcases your strengths and aspirations.</div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-upload me-2"></i>Resume Upload
                            </label>
                            <div class="upload-area" onclick="document.getElementById('resume').click()">
                                <i class="fas fa-cloud-upload-alt fa-2x text-muted mb-2"></i>
                                <p class="mb-1">Click to upload or drag and drop</p>
                                <small class="text-muted">PDF, DOC, DOCX (MAX. 5MB)</small>
                                <input type="file" name="resume" id="resume" class="d-none" accept=".pdf,.doc,.docx">
                            </div>
                            <?php if (!empty($profile['resume_path'])): ?>
                                <small class="text-success">
                                    <i class="fas fa-check-circle me-1"></i>
                                    Current resume: <?php echo htmlspecialchars($profile['resume_path']); ?>
                                </small>
                            <?php endif; ?>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-tools me-2"></i>Skills
                            </label>
                            <div class="row">
                                <?php 
                                $skills_by_category = [];
                                foreach ($all_skills as $skill) {
                                    $skills_by_category[$skill['category']][] = $skill;
                                }
                                
                                foreach ($skills_by_category as $category => $category_skills): 
                                ?>
                                    <div class="col-md-6 mb-3">
                                        <h6 class="text-muted mb-2"><?php echo ucfirst($category); ?></h6>
                                        <?php foreach ($category_skills as $skill): 
                                            $is_selected = false;
                                            $current_level = 'intermediate';
                                            foreach ($student_skills as $student_skill) {
                                                if ($student_skill['name'] === $skill['name']) {
                                                    $is_selected = true;
                                                    $current_level = $student_skill['proficiency_level'];
                                                    break;
                                                }
                                            }
                                        ?>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" 
                                                       name="skills[]" value="<?php echo $skill['id']; ?>"
                                                       id="skill_<?php echo $skill['id']; ?>"
                                                       <?php echo $is_selected ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="skill_<?php echo $skill['id']; ?>">
                                                    <?php echo htmlspecialchars($skill['name']); ?>
                                                </label>
                                                <select name="skill_levels[<?php echo $skill['id']; ?>]" 
                                                        class="form-select form-select-sm ms-2 d-inline-block" 
                                                        style="width: auto;">
                                                    <option value="beginner" <?php echo ($current_level === 'beginner') ? 'selected' : ''; ?>>Beginner</option>
                                                    <option value="intermediate" <?php echo ($current_level === 'intermediate') ? 'selected' : ''; ?>>Intermediate</option>
                                                    <option value="advanced" <?php echo ($current_level === 'advanced') ? 'selected' : ''; ?>>Advanced</option>
                                                    <option value="expert" <?php echo ($current_level === 'expert') ? 'selected' : ''; ?>>Expert</option>
                                                </select>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <button type="submit" class="btn-save">
                            <i class="fas fa-save me-2"></i>Save Profile
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('resume').addEventListener('change', function(e) {
    const fileName = e.target.files[0]?.name;
    if (fileName) {
        document.querySelector('.upload-area p').textContent = 'Selected: ' + fileName;
    }
});
</script>
</body>
</html>
