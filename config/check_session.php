<?php
/**
 * Session Security Middleware
 * Include file ini di setiap halaman yang memerlukan autentikasi
 * Contoh: include 'config/check_session.php';
 */

// Konfigurasi session yang aman
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 1); // Aktifkan jika HTTPS
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_samesite', 'Strict');
    session_start();
}

// Fungsi untuk destroy session dengan aman
function destroySession() {
    $_SESSION = array();
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
}

// Cek apakah user sudah login
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Session timeout (30 menit = 1800 detik)
$session_timeout = 1800;
if (isset($_SESSION['login_time'])) {
    $elapsed_time = time() - $_SESSION['login_time'];
    
    if ($elapsed_time > $session_timeout) {
        destroySession();
        header("Location: login.php?timeout=1");
        exit();
    }
    
    // Update last activity time
    $_SESSION['login_time'] = time();
}

// Session Hijacking Protection - Cek User Agent
if (isset($_SESSION['user_agent'])) {
    if ($_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
        destroySession();
        error_log("Possible session hijacking attempt detected for user: " . $_SESSION['username']);
        header("Location: login.php?error=security");
        exit();
    }
}

// Session Hijacking Protection - Cek IP Address (opsional, bisa dinonaktifkan jika user sering ganti network)
if (isset($_SESSION['ip_address'])) {
    if ($_SESSION['ip_address'] !== $_SERVER['REMOTE_ADDR']) {
        destroySession();
        error_log("IP address mismatch detected for user: " . $_SESSION['username']);
        header("Location: login.php?error=security");
        exit();
    }
}

// Regenerate session ID secara berkala (setiap 5 menit)
if (!isset($_SESSION['last_regeneration'])) {
    $_SESSION['last_regeneration'] = time();
} elseif (time() - $_SESSION['last_regeneration'] > 300) {
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}

// Function untuk cek CSRF token di form POST
function validateCSRFToken() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token'])) {
            return false;
        }
        
        if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            return false;
        }
    }
    return true;
}

// Generate CSRF token baru jika belum ada
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Helper function untuk output CSRF field
function csrf_field() {
    if (isset($_SESSION['csrf_token'])) {
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') . '">';
    }
    return '';
}

// Helper function untuk get CSRF token
function csrf_token() {
    return isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : '';
}

// Fungsi untuk cek role/permission
function hasRole($required_role) {
    if (!isset($_SESSION['role'])) {
        return false;
    }
    
    $user_role = $_SESSION['role'];
    
    // Role hierarchy: admin > user
    $roles = ['admin' => 2, 'user' => 1];
    
    $user_level = isset($roles[$user_role]) ? $roles[$user_role] : 0;
    $required_level = isset($roles[$required_role]) ? $roles[$required_role] : 0;
    
    return $user_level >= $required_level;
}

// Set security headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: no-referrer");
header("Permissions-Policy: geolocation=(), microphone=(), camera=()");

// Jika menggunakan HTTPS, uncomment baris berikut:
// header("Strict-Transport-Security: max-age=31536000; includeSubDomains");

?>