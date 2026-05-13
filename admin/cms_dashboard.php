<?php
session_start();
if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}
include "../config/db.php";
include "../config/cms.php";

$cms = new CMSManager($conn);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_page'])) {
        $title = $_POST['title'] ?? '';
        $slug = $_POST['slug'] ?? '';
        $content = $_POST['content'] ?? '';
        $meta_description = $_POST['meta_description'] ?? '';
        $meta_keywords = $_POST['meta_keywords'] ?? '';
        $status = $_POST['status'] ?? 'draft';
        
        if ($cms->createPage($title, $slug, $content, $meta_description, $meta_keywords, $status)) {
            $success = 'Page created successfully!';
        } else {
            $error = 'Failed to create page. Slug may already exist.';
        }
    } elseif (isset($_POST['update_setting'])) {
        foreach ($_POST['settings'] as $key => $value) {
            $cms->updateSetting($key, $value);
        }
        $success = 'Settings updated successfully!';
    } elseif (isset($_POST['create_menu_item'])) {
        $menu_name = $_POST['menu_name'] ?? 'main';
        $title = $_POST['menu_title'] ?? '';
        $url = $_POST['menu_url'] ?? '';
        $sort_order = $_POST['menu_sort_order'] ?? 0;
        $target = $_POST['menu_target'] ?? '_self';
        
        if ($cms->createMenuItem($menu_name, $title, $url, $sort_order, $target)) {
            $success = 'Menu item created successfully!';
        } else {
            $error = 'Failed to create menu item.';
        }
    } elseif (isset($_POST['create_backup'])) {
        $backup_type = $_POST['backup_type'] ?? 'full';
        $backup_file = $cms->createBackup($backup_type);
        if ($backup_file) {
            $success = "Backup created: {$backup_file}";
        } else {
            $error = 'Failed to create backup.';
        }
    }
}

// Get data
$pages = $cms->getAllPages();
$settings = $cms->getSettings();
$menu_items = $cms->getMenuItems();
$backup_logs = $cms->getBackupLogs();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CMS Dashboard - Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #6366f1;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
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
            background: linear-gradient(135deg, var(--primary-color), #4f46e5);
            color: white;
            padding: 2rem;
            border-radius: 20px 20px 0 0;
        }
        
        .content-section {
            padding: 2rem;
        }
        
        .nav-tabs {
            border-bottom: 2px solid #e5e7eb;
            margin-bottom: 2rem;
        }
        
        .nav-tabs .nav-link {
            border: none;
            color: #6b7280;
            font-weight: 600;
            padding: 1rem 1.5rem;
            border-bottom: 3px solid transparent;
        }
        
        .nav-tabs .nav-link.active {
            color: var(--primary-color);
            background: none;
            border-bottom-color: var(--primary-color);
        }
        
        .cms-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e7eb;
            margin-bottom: 2rem;
        }
        
        .page-item {
            padding: 1rem;
            border-bottom: 1px solid #f3f4f6;
            transition: all 0.3s ease;
        }
        
        .page-item:hover {
            background: #f9fafb;
        }
        
        .page-item:last-child {
            border-bottom: none;
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .status-draft { background: #fef3c7; color: #92400e; }
        .status-published { background: #d1fae5; color: #065f46; }
        .status-archived { background: #f3f4f6; color: #374151; }
        
        .btn-cms {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 600;
            border: none;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-cms:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        .form-control, .form-select {
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            transition: all 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }
        
        .backup-item {
            padding: 1rem;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        
        .backup-item:hover {
            background: #f9fafb;
        }
    </style>
</head>
<body>
<div class="main-container">
    <div class="header-section">
        <h1 class="mb-3"><i class="fas fa-cogs me-2"></i>CMS Dashboard</h1>
        <p class="mb-0">Manage content, settings, and system configuration</p>
        
        <div class="nav-buttons">
            <a href="dashboard.php" class="btn btn-light">
                <i class="fas fa-arrow-left"></i> Admin Dashboard
            </a>
            <a href="security_dashboard.php" class="btn btn-light">
                <i class="fas fa-shield-alt"></i> Security
            </a>
            <a href="analytics.php" class="btn btn-light">
                <i class="fas fa-chart-line"></i> Analytics
            </a>
            <a href="../auth/logout.php" class="btn btn-light">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>

    <div class="content-section">
        <?php if (isset($success)): ?>
            <div class="alert alert-success border-0 mb-4">
                <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger border-0 mb-4">
                <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <ul class="nav nav-tabs" id="cmsTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="pages-tab" data-bs-toggle="tab" data-bs-target="#pages" type="button">
                    <i class="fas fa-file-alt me-2"></i>Pages
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="settings-tab" data-bs-toggle="tab" data-bs-target="#settings" type="button">
                    <i class="fas fa-cog me-2"></i>Settings
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="menu-tab" data-bs-toggle="tab" data-bs-target="#menu" type="button">
                    <i class="fas fa-bars me-2"></i>Menu
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="backup-tab" data-bs-toggle="tab" data-bs-target="#backup" type="button">
                    <i class="fas fa-download me-2"></i>Backup
                </button>
            </li>
        </ul>

        <div class="tab-content" id="cmsTabContent">
            <!-- Pages Tab -->
            <div class="tab-pane fade show active" id="pages" role="tabpanel">
                <div class="cms-card">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="mb-0"><i class="fas fa-file-alt me-2"></i>Manage Pages</h5>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createPageModal">
                            <i class="fas fa-plus me-2"></i>Create Page
                        </button>
                    </div>
                    
                    <?php if (!empty($pages)): ?>
                        <?php foreach ($pages as $page): ?>
                            <div class="page-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($page['title']); ?></h6>
                                        <div class="d-flex align-items-center gap-3 mb-1">
                                            <small class="text-muted">
                                                <i class="fas fa-link me-1"></i>/intern/page.php?slug=<?php echo htmlspecialchars($page['slug']); ?>
                                            </small>
                                            <span class="status-badge status-<?php echo $page['status']; ?>">
                                                <?php echo ucfirst($page['status']); ?>
                                            </span>
                                            <small class="text-muted">
                                                <i class="fas fa-clock me-1"></i><?php echo date('M j, Y', strtotime($page['updated_at'])); ?>
                                            </small>
                                        </div>
                                        <?php if (!empty($page['meta_description'])): ?>
                                            <p class="mb-0 text-muted small"><?php echo htmlspecialchars(substr($page['meta_description'], 0, 100)) . '...'; ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="btn-group btn-group-sm">
                                        <a href="edit_page.php?id=<?php echo $page['id']; ?>" class="btn btn-outline-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="../page.php?slug=<?php echo $page['slug']; ?>" target="_blank" class="btn btn-outline-success">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <button class="btn btn-outline-danger" onclick="deletePage(<?php echo $page['id']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted text-center">No pages created yet. Click "Create Page" to get started.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Settings Tab -->
            <div class="tab-pane fade" id="settings" role="tabpanel">
                <div class="cms-card">
                    <h5 class="mb-4"><i class="fas fa-cog me-2"></i>System Settings</h5>
                    <form method="post">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Site Name</label>
                                    <input type="text" name="settings[site_name]" class="form-control" 
                                           value="<?php echo htmlspecialchars($settings['site_name'] ?? ''); ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Contact Email</label>
                                    <input type="email" name="settings[contact_email]" class="form-control" 
                                           value="<?php echo htmlspecialchars($settings['contact_email'] ?? ''); ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Max Login Attempts</label>
                                    <input type="number" name="settings[max_login_attempts]" class="form-control" 
                                           value="<?php echo htmlspecialchars($settings['max_login_attempts'] ?? '5'); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Site Description</label>
                                    <textarea name="settings[site_description]" class="form-control" rows="3"><?php echo htmlspecialchars($settings['site_description'] ?? ''); ?></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Session Timeout (seconds)</label>
                                    <input type="number" name="settings[session_timeout]" class="form-control" 
                                           value="<?php echo htmlspecialchars($settings['session_timeout'] ?? '3600'); ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Posts Per Page</label>
                                    <input type="number" name="settings[posts_per_page]" class="form-control" 
                                           value="<?php echo htmlspecialchars($settings['posts_per_page'] ?? '10'); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <h6 class="mt-4 mb-3">Feature Toggles</h6>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" name="settings[maintenance_mode]" 
                                           value="true" <?php echo ($settings['maintenance_mode'] ?? 'false') === 'true' ? 'checked' : ''; ?>>
                                    <label class="form-check-label">Maintenance Mode</label>
                                </div>
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" name="settings[allow_registrations]" 
                                           value="true" <?php echo ($settings['allow_registrations'] ?? 'true') === 'true' ? 'checked' : ''; ?>>
                                    <label class="form-check-label">Allow Registrations</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" name="settings[require_email_verification]" 
                                           value="true" <?php echo ($settings['require_email_verification'] ?? 'true') === 'true' ? 'checked' : ''; ?>>
                                    <label class="form-check-label">Email Verification</label>
                                </div>
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" name="settings[enable_2fa]" 
                                           value="true" <?php echo ($settings['enable_2fa'] ?? 'true') === 'true' ? 'checked' : ''; ?>>
                                    <label class="form-check-label">Enable 2FA</label>
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" name="update_setting" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Save Settings
                        </button>
                    </form>
                </div>
            </div>

            <!-- Menu Tab -->
            <div class="tab-pane fade" id="menu" role="tabpanel">
                <div class="cms-card">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="mb-0"><i class="fas fa-bars me-2"></i>Menu Management</h5>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createMenuModal">
                            <i class="fas fa-plus me-2"></i>Add Menu Item
                        </button>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Main Menu</h6>
                            <?php 
                            $main_menu = array_filter($menu_items, function($item) { return $item['menu_name'] === 'main'; });
                            foreach ($main_menu as $item): ?>
                                <div class="d-flex justify-content-between align-items-center p-2 border-bottom">
                                    <div>
                                        <strong><?php echo htmlspecialchars($item['title']); ?></strong>
                                        <br>
                                        <small class="text-muted"><?php echo htmlspecialchars($item['url']); ?></small>
                                    </div>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-primary" onclick="editMenuItem(<?php echo $item['id']; ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-outline-danger" onclick="deleteMenuItem(<?php echo $item['id']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="col-md-6">
                            <h6>Footer Menu</h6>
                            <?php 
                            $footer_menu = array_filter($menu_items, function($item) { return $item['menu_name'] === 'footer'; });
                            foreach ($footer_menu as $item): ?>
                                <div class="d-flex justify-content-between align-items-center p-2 border-bottom">
                                    <div>
                                        <strong><?php echo htmlspecialchars($item['title']); ?></strong>
                                        <br>
                                        <small class="text-muted"><?php echo htmlspecialchars($item['url']); ?></small>
                                    </div>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-primary" onclick="editMenuItem(<?php echo $item['id']; ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-outline-danger" onclick="deleteMenuItem(<?php echo $item['id']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Backup Tab -->
            <div class="tab-pane fade" id="backup" role="tabpanel">
                <div class="cms-card">
                    <h5 class="mb-4"><i class="fas fa-download me-2"></i>Backup Management</h5>
                    
                    <form method="post" class="mb-4">
                        <div class="row align-items-end">
                            <div class="col-md-6">
                                <label class="form-label">Backup Type</label>
                                <select name="backup_type" class="form-select">
                                    <option value="full">Full Backup</option>
                                    <option value="structure">Structure Only</option>
                                    <option value="data">Data Only</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <button type="submit" name="create_backup" class="btn btn-primary">
                                    <i class="fas fa-download me-2"></i>Create Backup
                                </button>
                                <button type="button" class="btn btn-success ms-2" onclick="generateSitemap()">
                                    <i class="fas fa-sitemap me-2"></i>Generate Sitemap
                                </button>
                            </div>
                        </div>
                    </form>
                    
                    <h6 class="mb-3">Recent Backups</h6>
                    <?php if (!empty($backup_logs)): ?>
                        <?php foreach ($backup_logs as $backup): ?>
                            <div class="backup-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><?php echo htmlspecialchars($backup['filename']); ?></strong>
                                        <br>
                                        <small class="text-muted">
                                            Type: <?php echo ucfirst($backup['type']); ?> • 
                                            Size: <?php echo number_format($backup['file_size'] / 1024 / 1024, 2); ?> MB • 
                                            <?php echo date('M j, Y g:i A', strtotime($backup['created_at'])); ?>
                                        </small>
                                    </div>
                                    <div class="btn-group btn-group-sm">
                                        <a href="../backups/<?php echo htmlspecialchars($backup['filename']); ?>" 
                                           class="btn btn-outline-primary" download>
                                            <i class="fas fa-download"></i>
                                        </a>
                                        <button class="btn btn-outline-danger" onclick="deleteBackup('<?php echo htmlspecialchars($backup['filename']); ?>')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted">No backups created yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create Page Modal -->
<div class="modal fade" id="createPageModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create New Page</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Page Title</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">URL Slug</label>
                        <input type="text" name="slug" class="form-control" required>
                        <div class="form-text">This will be used in the URL: /intern/page.php?slug=your-slug</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Content</label>
                        <textarea name="content" class="form-control" rows="10" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Meta Description</label>
                        <textarea name="meta_description" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Meta Keywords</label>
                        <input type="text" name="meta_keywords" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="draft">Draft</option>
                            <option value="published">Published</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="create_page" class="btn btn-primary">Create Page</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Create Menu Modal -->
<div class="modal fade" id="createMenuModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Menu Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Menu</label>
                        <select name="menu_name" class="form-select">
                            <option value="main">Main Menu</option>
                            <option value="footer">Footer Menu</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" name="menu_title" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">URL</label>
                        <input type="text" name="menu_url" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Sort Order</label>
                        <input type="number" name="menu_sort_order" class="form-control" value="0">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Target</label>
                        <select name="menu_target" class="form-select">
                            <option value="_self">Same Window</option>
                            <option value="_blank">New Window</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="create_menu_item" class="btn btn-primary">Add Menu Item</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function deletePage(id) {
    if (confirm('Are you sure you want to delete this page?')) {
        window.location.href = `delete_page.php?id=${id}`;
    }
}

function deleteMenuItem(id) {
    if (confirm('Are you sure you want to delete this menu item?')) {
        window.location.href = `delete_menu_item.php?id=${id}`;
    }
}

function deleteMenuItem(id) {
    if (confirm('Are you sure you want to delete this menu item?')) {
        window.location.href = `delete_menu_item.php?id=${id}`;
    }
}

function deleteBackup(filename) {
    if (confirm('Are you sure you want to delete this backup?')) {
        window.location.href = `delete_backup.php?filename=${filename}`;
    }
}

function generateSitemap() {
    if (confirm('Generate sitemap.xml for better SEO?')) {
        window.location.href = 'generate_sitemap.php';
    }
}

// Auto-generate slug from title
document.querySelector('input[name="title"]')?.addEventListener('input', function() {
    const title = this.value;
    const slug = title.toLowerCase()
        .replace(/[^a-z0-9\s-]/g, '')
        .replace(/\s+/g, '-')
        .replace(/-+/g, '-')
        .trim('-');
    document.querySelector('input[name="slug"]').value = slug;
});
</script>
</body>
</html>
