<?php
/**
 * CuanLog - Application Core Helper & Configuration
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/database.php';

// Dynamically determine BASE_URL
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || ($_SERVER['SERVER_PORT'] ?? 80) == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$script_name = $_SERVER['SCRIPT_NAME'] ?? '';
$app_dir = str_replace('\\', '/', dirname($script_name));

$base_path = '';
if (!empty($script_name) && php_sapi_name() !== 'cli') {
    $parts = array_filter(explode('/', trim($app_dir, '/')), function($p) {
        return $p !== '' && $p !== '.';
    });
    $parts = array_values($parts);
    $idx = array_search('lomba-26', $parts);
    if ($idx !== false) {
        $base_path = '/' . implode('/', array_slice($parts, 0, $idx + 1));
    } elseif (!empty($parts)) {
        $base_path = '/' . $parts[0];
    }
} else {
    // Default fallback for localhost/lomba-26
    $base_path = '/lomba-26';
}

if ($base_path === '/' || $base_path === '/.') {
    $base_path = '';
}

define('BASE_URL', rtrim($protocol . $host . $base_path, '/'));
define('APP_NAME', 'CuanLog');
define('APP_TAGLINE', 'Smart POS & Financial Flow UMKM');
define('APP_VERSION', '1.0.0');

// Helper: Format Currency (Rupiah)
function format_rupiah($number) {
    return 'Rp ' . number_format((float)$number, 0, ',', '.');
}

// Helper: Format Date Indonesian
function format_date_indo($date_str, $with_time = false) {
    if (!$date_str) return '-';
    $timestamp = strtotime($date_str);
    $months = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    $day = date('d', $timestamp);
    $month = $months[(int)date('m', $timestamp)];
    $year = date('Y', $timestamp);
    $formatted = "$day $month $year";
    if ($with_time) {
        $formatted .= ' ' . date('H:i', $timestamp);
    }
    return $formatted;
}

// Helper: Get Setting from database with caching
function get_setting($key, $default = '') {
    static $settings_cache = null;
    $db = getDB();
    if (!$db) return $default;

    if ($settings_cache === null) {
        try {
            $stmt = $db->query("SELECT setting_key, setting_value FROM settings");
            $rows = $stmt->fetchAll();
            $settings_cache = [];
            foreach ($rows as $r) {
                $settings_cache[$r['setting_key']] = $r['setting_value'];
            }
        } catch (Exception $e) {
            return $default;
        }
    }

    return $settings_cache[$key] ?? $default;
}

// Helper: Authentication checks
function auth_check() {
    return isset($_SESSION['user_id']);
}

function auth_user() {
    if (!auth_check()) return null;
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'name' => $_SESSION['user_name'] ?? 'Pengguna',
        'username' => $_SESSION['username'] ?? '',
        'role' => $_SESSION['user_role'] ?? 'kasir',
        'phone' => $_SESSION['user_phone'] ?? '',
        'avatar' => $_SESSION['user_avatar'] ?? null,
    ];
}

function is_admin() {
    return (auth_user()['role'] ?? '') === 'admin';
}

function require_login() {
    if (!auth_check()) {
        set_flash('error', 'Silakan masuk terlebih dahulu untuk mengakses sistem.');
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

function require_admin() {
    require_login();
    if (!is_admin()) {
        set_flash('error', 'Akses ditolak. Fitur ini hanya dapat diakses oleh Administrator/Pemilik Toko.');
        header('Location: ' . BASE_URL . '/index.php');
        exit;
    }
}

// Helper: Flash Message Notification
function set_flash($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type, // 'success', 'error', 'warning', 'info'
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

// Helper: Activity Log
function log_activity($action, $details = null) {
    $db = getDB();
    if (!$db) return;
    try {
        $stmt = $db->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
        $user_id = $_SESSION['user_id'] ?? null;
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $stmt->execute([$user_id, $action, $details, $ip]);
    } catch (Exception $e) {
        // silently ignore logging failures
    }
}

// Helper: Generate Invoice Number
function generate_invoice_no() {
    $prefix = 'TR-' . date('Ymd') . '-';
    $db = getDB();
    if (!$db) {
        return $prefix . rand(1000, 9999);
    }
    try {
        $today = date('Y-m-d');
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM transactions WHERE DATE(created_at) = ?");
        $stmt->execute([$today]);
        $row = $stmt->fetch();
        $next_num = (int)($row['count'] ?? 0) + 1;
        return $prefix . str_pad($next_num, 4, '0', STR_PAD_LEFT);
    } catch (Exception $e) {
        return $prefix . rand(1000, 9999);
    }
}

// Helper: Sanitize inputs
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
}

