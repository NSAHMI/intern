<?php
// Content Management System
class CMSManager {
    private $conn;
    
    public function __construct($database) {
        $this->conn = $database;
    }
    
    /**
     * Create a new page
     */
    public function createPage($title, $slug, $content, $meta_description = '', $meta_keywords = '', $status = 'draft') {
        // Check if slug already exists
        $stmt = $this->conn->prepare('SELECT id FROM cms_pages WHERE slug = ?');
        $stmt->bind_param('s', $slug);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            return false; // Slug already exists
        }
        $stmt->close();
        
        // Create page
        $stmt = $this->conn->prepare('
            INSERT INTO cms_pages (title, slug, content, meta_description, meta_keywords, status, created_at, updated_at) 
            VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
        ');
        $stmt->bind_param('ssssss', $title, $slug, $content, $meta_description, $meta_keywords, $status);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }
    
    /**
     * Update an existing page
     */
    public function updatePage($id, $title, $content, $meta_description = '', $meta_keywords = '', $status = 'draft') {
        $stmt = $this->conn->prepare('
            UPDATE cms_pages 
            SET title = ?, content = ?, meta_description = ?, meta_keywords = ?, status = ?, updated_at = NOW() 
            WHERE id = ?
        ');
        $stmt->bind_param('sssssi', $title, $content, $meta_description, $meta_keywords, $status, $id);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }
    
    /**
     * Get page by slug
     */
    public function getPageBySlug($slug) {
        $stmt = $this->conn->prepare('SELECT * FROM cms_pages WHERE slug = ? AND status = "published"');
        $stmt->bind_param('s', $slug);
        $stmt->execute();
        $page = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        return $page;
    }
    
    /**
     * Get page by ID
     */
    public function getPageById($id) {
        $stmt = $this->conn->prepare('SELECT * FROM cms_pages WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $page = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        return $page;
    }
    
    /**
     * Get all pages
     */
    public function getAllPages($status = null) {
        $sql = 'SELECT * FROM cms_pages';
        if ($status) {
            $sql .= ' WHERE status = ?';
        }
        $sql .= ' ORDER BY created_at DESC';
        
        $stmt = $this->conn->prepare($sql);
        if ($status) {
            $stmt->bind_param('s', $status);
        }
        $stmt->execute();
        $pages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        return $pages;
    }
    
    /**
     * Delete page
     */
    public function deletePage($id) {
        $stmt = $this->conn->prepare('DELETE FROM cms_pages WHERE id = ?');
        $stmt->bind_param('i', $id);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }
    
    /**
     * Get system settings
     */
    public function getSettings() {
        $stmt = $this->conn->prepare('SELECT setting_key, setting_value FROM system_settings');
        $stmt->execute();
        $result = $stmt->get_result();
        
        $settings = [];
        while ($row = $result->fetch_assoc()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        
        $stmt->close();
        return $settings;
    }
    
    /**
     * Update system setting
     */
    public function updateSetting($key, $value) {
        $stmt = $this->conn->prepare('
            INSERT INTO system_settings (setting_key, setting_value, updated_at) 
            VALUES (?, ?, NOW()) 
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()
        ');
        $stmt->bind_param('ss', $key, $value);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }
    
    /**
     * Get menu items
     */
    public function getMenuItems($menu_name = 'main') {
        $stmt = $this->conn->prepare('
            SELECT * FROM menu_items 
            WHERE menu_name = ? AND status = "active" 
            ORDER BY sort_order ASC
        ');
        $stmt->bind_param('s', $menu_name);
        $stmt->execute();
        $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        return $items;
    }
    
    /**
     * Create menu item
     */
    public function createMenuItem($menu_name, $title, $url, $sort_order = 0, $target = '_self') {
        $stmt = $this->conn->prepare('
            INSERT INTO menu_items (menu_name, title, url, sort_order, target, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ');
        $stmt->bind_param('sssis', $menu_name, $title, $url, $sort_order, $target);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }
    
    /**
     * Update menu item
     */
    public function updateMenuItem($id, $title, $url, $sort_order, $target) {
        $stmt = $this->conn->prepare('
            UPDATE menu_items 
            SET title = ?, url = ?, sort_order = ?, target = ? 
            WHERE id = ?
        ');
        $stmt->bind_param('ssisi', $title, $url, $sort_order, $target, $id);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }
    
    /**
     * Delete menu item
     */
    public function deleteMenuItem($id) {
        $stmt = $this->conn->prepare('DELETE FROM menu_items WHERE id = ?');
        $stmt->bind_param('i', $id);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }
    
    /**
     * Get banners
     */
    public function getBanners($position = 'home') {
        $stmt = $this->conn->prepare('
            SELECT * FROM banners 
            WHERE position = ? AND status = "active" 
            AND (start_date IS NULL OR start_date <= NOW()) 
            AND (end_date IS NULL OR end_date >= NOW()) 
            ORDER BY sort_order ASC
        ');
        $stmt->bind_param('s', $position);
        $stmt->execute();
        $banners = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        return $banners;
    }
    
    /**
     * Create banner
     */
    public function createBanner($title, $image_url, $link_url, $position, $sort_order = 0, $start_date = null, $end_date = null) {
        $stmt = $this->conn->prepare('
            INSERT INTO banners (title, image_url, link_url, position, sort_order, start_date, end_date, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ');
        $stmt->bind_param('ssssiss', $title, $image_url, $link_url, $position, $sort_order, $start_date, $end_date);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }
    
    /**
     * Get testimonials
     */
    public function getTestimonials($status = 'approved') {
        $sql = 'SELECT * FROM testimonials';
        if ($status) {
            $sql .= ' WHERE status = ?';
        }
        $sql .= ' ORDER BY sort_order ASC, created_at DESC';
        
        $stmt = $this->conn->prepare($sql);
        if ($status) {
            $stmt->bind_param('s', $status);
        }
        $stmt->execute();
        $testimonials = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        return $testimonials;
    }
    
    /**
     * Create testimonial
     */
    public function createTestimonial($name, $email, $content, $rating = 5, $company = '') {
        $stmt = $this->conn->prepare('
            INSERT INTO testimonials (name, email, content, rating, company, status, created_at) 
            VALUES (?, ?, ?, ?, ?, "pending", NOW())
        ');
        $stmt->bind_param('sssis', $name, $email, $content, $rating, $company);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }
    
    /**
     * Update testimonial status
     */
    public function updateTestimonialStatus($id, $status) {
        $stmt = $this->conn->prepare('UPDATE testimonials SET status = ? WHERE id = ?');
        $stmt->bind_param('si', $status, $id);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }
    
    /**
     * Get analytics data
     */
    public function getAnalytics($period = '7days') {
        $period_map = [
            '1day' => 'DATE_SUB(NOW(), INTERVAL 1 DAY)',
            '7days' => 'DATE_SUB(NOW(), INTERVAL 7 DAY)',
            '30days' => 'DATE_SUB(NOW(), INTERVAL 30 DAY)',
            '90days' => 'DATE_SUB(NOW(), INTERVAL 90 DAY)'
        ];
        
        $date_condition = $period_map[$period] ?? $period_map['7days'];
        
        // Page views
        $stmt = $this->conn->prepare("
            SELECT DATE(created_at) as date, COUNT(*) as views 
            FROM analytics_events 
            WHERE event_type = 'page_view' AND created_at >= {$date_condition}
            GROUP BY DATE(created_at) 
            ORDER BY date ASC
        ");
        $stmt->execute();
        $page_views = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        // User registrations
        $stmt = $this->conn->prepare("
            SELECT DATE(created_at) as date, COUNT(*) as registrations 
            FROM users 
            WHERE created_at >= {$date_condition}
            GROUP BY DATE(created_at) 
            ORDER BY date ASC
        ");
        $stmt->execute();
        $registrations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        // Applications
        $stmt = $this->conn->prepare("
            SELECT DATE(created_at) as date, COUNT(*) as applications 
            FROM applications 
            WHERE created_at >= {$date_condition}
            GROUP BY DATE(created_at) 
            ORDER BY date ASC
        ");
        $stmt->execute();
        $applications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        return [
            'page_views' => $page_views,
            'registrations' => $registrations,
            'applications' => $applications
        ];
    }
    
    /**
     * Create backup
     */
    public function createBackup($type = 'full') {
        $backup_file = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
        $backup_path = __DIR__ . '/../backups/' . $backup_file;
        
        // Create backups directory if it doesn't exist
        if (!is_dir(__DIR__ . '/../backups')) {
            mkdir(__DIR__ . '/../backups', 0755, true);
        }
        
        // Get all tables
        $stmt = $this->conn->prepare('SHOW TABLES');
        $stmt->execute();
        $tables = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        $backup_content = "-- Database Backup\n-- Created: " . date('Y-m-d H:i:s') . "\n-- Type: {$type}\n\n";
        
        foreach ($tables as $table) {
            $table_name = array_values($table)[0];
            
            if ($type === 'structure') {
                // Only structure
                $backup_content .= $this->getTableStructure($table_name);
            } elseif ($type === 'data') {
                // Only data
                $backup_content .= $this->getTableData($table_name);
            } else {
                // Full backup
                $backup_content .= $this->getTableStructure($table_name);
                $backup_content .= $this->getTableData($table_name);
            }
        }
        
        if (file_put_contents($backup_path, $backup_content)) {
            // Log backup creation
            $stmt = $this->conn->prepare('
                INSERT INTO backup_logs (filename, type, file_size, created_at) 
                VALUES (?, ?, ?, NOW())
            ');
            $file_size = filesize($backup_path);
            $stmt->bind_param('ssi', $backup_file, $type, $file_size);
            $stmt->execute();
            $stmt->close();
            
            return $backup_file;
        }
        
        return false;
    }
    
    /**
     * Get table structure
     */
    private function getTableStructure($table_name) {
        $stmt = $this->conn->prepare("SHOW CREATE TABLE `{$table_name}`");
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        return "-- Table structure for `{$table_name}`\n" . $result['Create Table'] . ";\n\n";
    }
    
    /**
     * Get table data
     */
    private function getTableData($table_name) {
        $stmt = $this->conn->prepare("SELECT * FROM `{$table_name}`");
        $stmt->execute();
        $result = $stmt->get_result();
        
        $data = "-- Data for table `{$table_name}`\n";
        
        while ($row = $result->fetch_assoc()) {
            $columns = array_keys($row);
            $values = array_map(function($value) {
                return $value === null ? 'NULL' : "'" . addslashes($value) . "'";
            }, $row);
            
            $data .= "INSERT INTO `{$table_name}` (`" . implode('`, `', $columns) . "`) VALUES (" . implode(', ', $values) . ");\n";
        }
        
        $stmt->close();
        return $data . "\n";
    }
    
    /**
     * Get backup logs
     */
    public function getBackupLogs() {
        $stmt = $this->conn->prepare('SELECT * FROM backup_logs ORDER BY created_at DESC LIMIT 20');
        $stmt->execute();
        $logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        return $logs;
    }
    
    /**
     * Generate sitemap
     */
    public function generateSitemap() {
        $base_url = 'http://' . $_SERVER['HTTP_HOST'] . '/intern/';
        $sitemap = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $sitemap .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        
        // Add static pages
        $static_pages = [
            '' => '1.0',
            'index.php' => '1.0',
            'auth/login.php' => '0.8',
            'auth/register.php' => '0.8',
            'search.php' => '0.9',
            'messages.php' => '0.7'
        ];
        
        foreach ($static_pages as $page => $priority) {
            $sitemap .= "  <url>\n";
            $sitemap .= "    <loc>{$base_url}{$page}</loc>\n";
            $sitemap .= "    <priority>{$priority}</priority>\n";
            $sitemap .= "    <changefreq>weekly</changefreq>\n";
            $sitemap .= "  </url>\n";
        }
        
        // Add CMS pages
        $pages = $this->getAllPages('published');
        foreach ($pages as $page) {
            $sitemap .= "  <url>\n";
            $sitemap .= "    <loc>{$base_url}page.php?slug={$page['slug']}</loc>\n";
            $sitemap .= "    <priority>0.8</priority>\n";
            $sitemap .= "    <changefreq>monthly</changefreq>\n";
            $sitemap .= "  </url>\n";
        }
        
        // Add internship pages
        $stmt = $this->conn->prepare('SELECT id FROM internships WHERE expiration_date >= NOW()');
        $stmt->execute();
        $internships = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        foreach ($internships as $internship) {
            $sitemap .= "  <url>\n";
            $sitemap .= "    <loc>{$base_url}student/apply.php?id={$internship['id']}</loc>\n";
            $sitemap .= "    <priority>0.6</priority>\n";
            $sitemap .= "    <changefreq>weekly</changefreq>\n";
            $sitemap .= "  </url>\n";
        }
        
        $sitemap .= '</urlset>';
        
        $sitemap_file = __DIR__ . '/../sitemap.xml';
        file_put_contents($sitemap_file, $sitemap);
        
        return $sitemap_file;
    }
}
?>
