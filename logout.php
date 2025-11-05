<?php
session_start();

// Log logout activity
function logLogout($username, $ip) {
    $log_file = 'logs/login_activity.log';
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[$timestamp] IP: $ip | Username: $username | Status: LOGOUT\n";
    file_put_contents($log_file, $log_entry, FILE_APPEND);
}

// Save username for logging before destroying session
$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'UNKNOWN';
$ip = $_SERVER['REMOTE_ADDR'];

// Destroy all session data
$_SESSION = array();

// Delete session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], 
        $params["domain"],
        $params["secure"], 
        $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Log the logout
logLogout($username, $ip);

// Redirect to login page
header("Location: login.php?logout=success");
exit();
?>