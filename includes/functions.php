<?php
/**
 * Helper Functions
 * Internship Management System
 */

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Redirect to a URL
 */
function redirect($url) {
    header("Location: $url");
    exit;
}

/**
 * Check if user is logged in
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Get current user ID
 */
function get_user_id() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user role
 */
function get_user_role() {
    return $_SESSION['user_role'] ?? null;
}

/**
 * Get current user name
 */
function get_user_name() {
    return $_SESSION['user_name'] ?? 'Guest';
}

/**
 * Require authentication
 */
function require_login() {
    if (!is_logged_in()) {
        redirect('/auth/login.php');
    }
}

/**
 * Require specific role
 */
function require_role($role) {
    require_login();
    if (get_user_role() !== $role) {
        redirect('/index.php');
    }
}

/**
 * Sanitize input
 */
function clean($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Flash messages
 */
function set_flash($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

function get_flash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Format date
 */
function format_date($date) {
    return date('M j, Y', strtotime($date));
}

/**
 * Time ago format
 */
function time_ago($datetime) {
    $time = strtotime($datetime);
    $diff = time() - $time;

    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . ' min ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
    if ($diff < 604800) return floor($diff / 86400) . ' days ago';

    return format_date($datetime);
}

/**
 * Get status badge class
 */
function status_badge($status) {
    $badges = [
        'pending' => 'badge-warning',
        'reviewed' => 'badge-info',
        'shortlisted' => 'badge-primary',
        'accepted' => 'badge-success',
        'rejected' => 'badge-danger',
        'open' => 'badge-success',
        'closed' => 'badge-secondary',
        'filled' => 'badge-info'
    ];
    return $badges[$status] ?? 'badge-secondary';
}

/**
 * Truncate text
 */
function truncate($text, $length = 100) {
    if (strlen($text) <= $length) return $text;
    return substr($text, 0, $length) . '...';
}
