<?php
// Konfigurasi Session yang Aman
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1); // Aktifkan jika menggunakan HTTPS
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Strict');

session_start();

// Redirect jika sudah login
if (isset($_SESSION['username'])) {
    echo "<script> location.href='index.php' </script>";  
    exit();
}

include "config/database.php";

$message = "Insert Username and Password!";
$login_attempts_key = 'login_attempts_' . $_SERVER['REMOTE_ADDR'];
$lockout_time = 900; // 15 menit lockout
$max_attempts = 5;

// Fungsi untuk log aktivitas
function logActivity($username, $status, $ip) {
    $log_file = 'logs/login_activity.log';
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[$timestamp] IP: $ip | Username: $username | Status: $status\n";
    file_put_contents($log_file, $log_entry, FILE_APPEND);
}

// Generate CSRF Token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Cek apakah IP sedang di-lockout
if (isset($_SESSION[$login_attempts_key])) {
    $attempts_data = $_SESSION[$login_attempts_key];
    
    if ($attempts_data['count'] >= $max_attempts) {
        $time_passed = time() - $attempts_data['last_attempt'];
        
        if ($time_passed < $lockout_time) {
            $remaining = ceil(($lockout_time - $time_passed) / 60);
            $message = "<b style='color:red;'>Too many failed attempts. Please try again in $remaining minutes.</b>";
            $locked = true;
        } else {
            // Reset setelah lockout time habis
            unset($_SESSION[$login_attempts_key]);
        }
    }
}

// Proses Login
if (isset($_POST['username']) && !isset($locked)) {
    
    // Validasi CSRF Token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $message = "<b style='color:red;'>Invalid security token. Please refresh the page.</b>";
        logActivity('UNKNOWN', 'CSRF_FAILED', $_SERVER['REMOTE_ADDR']);
    } else {
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        
        // Validasi input
        if (empty($username) || empty($password)) {
            $message = "<b style='color:red;'>Username and Password are required.</b>";
        } else {
            // Prepared statement untuk mencegah SQL injection
            $sql = "SELECT * FROM user WHERE username = ? LIMIT 1";
            
            if ($stmt = mysqli_prepare($connection, $sql)) {
                mysqli_stmt_bind_param($stmt, "s", $username);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);

                if (mysqli_num_rows($result) > 0) {
                    $data = mysqli_fetch_assoc($result);
                    
                    // Verifikasi password
                    if (password_verify($password, $data['password'])) {
                        // Login berhasil
                        
                        // Regenerate session ID untuk mencegah session fixation
                        session_regenerate_id(true);
                        
                        // Set session variables
                        $_SESSION['username'] = $username;
                        $_SESSION['fullname'] = $data['fullname'];
                        $_SESSION['role'] = $data['role'];
                        $_SESSION['login_time'] = time();
                        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
                        $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
                        
                        // Reset login attempts
                        unset($_SESSION[$login_attempts_key]);
                        
                        // Generate new CSRF token
                        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                        
                        // Log aktivitas
                        logActivity($username, 'SUCCESS', $_SERVER['REMOTE_ADDR']);
                        
                        echo "<script> location.href='index.php' </script>";  
                        exit();
                    } else {
                        // Password salah
                        $message = "<b style='color:red;'>Username or Password is Wrong</b>";
                        logActivity($username, 'FAILED_PASSWORD', $_SERVER['REMOTE_ADDR']);
                        $login_failed = true;
                    }
                } else {
                    // Username tidak ditemukan
                    $message = "<b style='color:red;'>Username or Password is Wrong</b>";
                    logActivity($username, 'FAILED_USERNAME', $_SERVER['REMOTE_ADDR']);
                    $login_failed = true;
                }

                mysqli_stmt_close($stmt);
            } else {
                // Jangan tampilkan detail error database
                $message = "<b style='color:red;'>System error. Please try again later.</b>";
                logActivity($username, 'DB_ERROR', $_SERVER['REMOTE_ADDR']);
                error_log("Database prepare failed: " . mysqli_error($connection));
            }
            
            // Tracking login attempts
            if (isset($login_failed)) {
                if (!isset($_SESSION[$login_attempts_key])) {
                    $_SESSION[$login_attempts_key] = [
                        'count' => 1,
                        'last_attempt' => time()
                    ];
                } else {
                    $_SESSION[$login_attempts_key]['count']++;
                    $_SESSION[$login_attempts_key]['last_attempt'] = time();
                }
                
                $remaining_attempts = $max_attempts - $_SESSION[$login_attempts_key]['count'];
                if ($remaining_attempts > 0 && $remaining_attempts <= 3) {
                    $message .= "<br><small>Warning: $remaining_attempts attempts remaining before lockout.</small>";
                }
            }
        }
    }
    
    // Generate new CSRF token after each submission
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="robots" content="noindex, nofollow">
    <!-- Favicon / Logo di Tab Browser -->
    <link rel="icon" type="image/jpg" href="../dist/img/unesa.jpg">
    <link rel="shortcut icon" type="image/jpg" href="../dist/img/unesa.jpg">

    <title>IoT Agrowisata Banjarsari</title>

    <!-- Security Headers -->
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; script-src 'self' 'unsafe-inline';">
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-Frame-Options" content="DENY">
    <meta http-equiv="X-XSS-Protection" content="1; mode=block">
    <meta name="referrer" content="no-referrer">

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
    <!-- icheck bootstrap -->
    <link rel="stylesheet" href="plugins/icheck-bootstrap/icheck-bootstrap.min.css">
    <!-- Theme style -->
    <link rel="stylesheet" href="dist/css/adminlte.min.css">
    
    <style>
        .password-toggle {
            cursor: pointer;
        }
        .strength-meter {
            height: 5px;
            margin-top: 5px;
            border-radius: 3px;
            transition: all 0.3s;
        }
    </style>
</head>

<body class="hold-transition login-page">
    <div class="login-box">
        <div class="login-logo">
            <a><b>IoT Smart Irrigation</b> LOGIN</a>
        </div>
        <!-- /.login-logo -->
        <div class="card">
            <div class="card-body login-card-body">
                <p class="login-box-msg"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p>

                <form action="" method="post" autocomplete="off">
                    <!-- CSRF Token -->
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                    
                    <div class="input-group mb-3">
                        <input type="text" 
                               class="form-control" 
                               name="username" 
                               placeholder="Username" 
                               required 
                               maxlength="50"
                               pattern="[a-zA-Z0-9_]{3,50}"
                               title="Username must be 3-50 characters (letters, numbers, underscore only)"
                               autocomplete="off"
                               <?php echo isset($locked) ? 'disabled' : ''; ?>>
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-user"></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="input-group mb-3">
                        <input type="password" 
                               class="form-control" 
                               name="password" 
                               id="password"
                               placeholder="Password" 
                               required
                               minlength="3"
                               autocomplete="off"
                               <?php echo isset($locked) ? 'disabled' : ''; ?>>
                        <div class="input-group-append">
                            <div class="input-group-text password-toggle" onclick="togglePassword()">
                                <span class="fas fa-eye" id="toggleIcon"></span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- /.col -->
                    <div class="col-12">
                        <button type="submit" 
                                class="btn btn-primary btn-block" 
                                <?php echo isset($locked) ? 'disabled' : ''; ?>>
                            <i class="fas fa-sign-in-alt"></i> Sign In
                        </button>
                    </div>
                    <!-- /.col -->
                </form>
                
                <p class="mt-3 mb-1 text-center">
                    <small class="text-muted">
                        <i class="fas fa-shield-alt"></i> Microprocessor Laboratory Unesa
                    </small>
                </p>
            </div>
            <!-- /.login-card-body -->
        </div>
    </div>
    <!-- /.login-box -->

    <!-- jQuery -->
    <script src="plugins/jquery/jquery.min.js"></script>
    <!-- Bootstrap 4 -->
    <script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <!-- AdminLTE App -->
    <script src="dist/js/adminlte.min.js"></script>
    
    <script>
        // Toggle password visibility
        function togglePassword() {
            const passwordField = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
        
        // Prevent form resubmission on page refresh
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
        
        // Auto-focus username field
        document.addEventListener('DOMContentLoaded', function() {
            const usernameField = document.querySelector('input[name="username"]');
            if (usernameField && !usernameField.disabled) {
                usernameField.focus();
            }
        });
        // Fungsi untuk membuat judul berjalan di tab browser
        window.addEventListener('load', function() {
        const originalTitle = "IoT Agrowisata Banjarsari - Monitoring & Control System";
        let titleIndex = 0;

        function scrollTitle() {
            // Buat efek scrolling dengan memotong dan menggabungkan string
            document.title = originalTitle.substring(titleIndex) + " | " + originalTitle.substring(0, titleIndex);
            
            titleIndex++;
            if (titleIndex > originalTitle.length) {
            titleIndex = 0;
            }
        }

        // Jalankan animasi setiap 300ms (ubah angka untuk mengatur kecepatan)
        setInterval(scrollTitle, 300);
        });
    </script>
</body>

</html>