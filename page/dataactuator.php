<?php
// save_actuator.php - FINAL VERSION (config/ di luar page/)
header('Content-Type: application/json; charset=utf-8');

// === 1. ERROR REPORTING (Hapus di production) ===
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// === 2. AMBIL database.php DARI LUAR FOLDER page/ ===
$databasePath = __DIR__ . '/../config/database.php';  // Kunci: naik 1 level

if (!file_exists($databasePath)) {
    error_log("save_actuator.php: config/database.php tidak ditemukan di $databasePath");
    echo json_encode(['status' => 'error', 'message' => 'File konfigurasi database tidak ditemukan']);
    exit;
}

require_once $databasePath;

// === 3. CEK KONEKSI DB ===
if (!$connection || mysqli_connect_error()) {
    $msg = 'Koneksi database gagal: ' . mysqli_connect_error();
    error_log("save_actuator.php: $msg");
    echo json_encode(['status' => 'error', 'message' => $msg]);
    exit;
}

// === 4. AMBIL INPUT JSON ===
$input = json_decode(file_get_contents('php://input'), true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['status' => 'error', 'message' => 'JSON tidak valid: ' . json_last_error_msg()]);
    exit;
}

if (!$input || !isset($input['type'], $input['solenoid'], $input['data'])) {
    echo json_encode(['status' => 'error', 'message' => 'Data input tidak lengkap']);
    exit;
}

$type = $input['type'];
$solenoid = (int)$input['solenoid'];
$data = $input['data'];

if (!in_array($solenoid, [1, 2])) {
    echo json_encode(['status' => 'error', 'message' => 'Solenoid harus 1 atau 2']);
    exit;
}

if (!in_array($type, ['manual', 'auto'])) {
    echo json_encode(['status' => 'error', 'message' => 'Mode harus manual atau auto']);
    exit;
}

// === 5. PERSIAPAN DATA ===
$mode = $type;
$state = null;
$action = null;
$threshold_data = null;

try {
    if ($type === 'manual') {
        $state = ($solenoid == 1)
            ? ($data['solenoidSatu'] ?? 0)
            : ($data['solenoidDua'] ?? 0);
        $state = (int)$state;
    } else {
        $action = in_array($data['action'] ?? '', ['on', 'off']) ? $data['action'] : 'off';
        
        $threshold_data = [];
        for ($i = 1; $i <= 4; $i++) {
            $keyA = "waterlvA$i";
            $keyB = "waterlvB$i";
            $a = isset($data[$keyA]) ? floatval($data[$keyA]) : null;
            $b = isset($data[$keyB]) ? floatval($data[$keyB]) : null;
            
            if ($a !== null && $b !== null && !is_nan($a) && !is_nan($b)) {
                $threshold_data[$keyA] = $a;
                $threshold_data[$keyB] = $b;
            }
        }
        $threshold_data = !empty($threshold_data) ? json_encode($threshold_data, JSON_UNESCAPED_UNICODE) : null;
        
        if ($threshold_data === false) {
            throw new Exception('Gagal encode JSON threshold');
        }
    }

    // === 6. INSERT KE DB ===
    $sql = "INSERT INTO actuator_history 
            (solenoid_num, mode, state, action, threshold_data, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())";

    $stmt = mysqli_prepare($connection, $sql);
    if (!$stmt) {
        throw new Exception('Prepare gagal: ' . mysqli_error($connection));
    }

    if (!mysqli_stmt_bind_param($stmt, "isiss", $solenoid, $mode, $state, $action, $threshold_data)) {
        throw new Exception('Bind gagal');
    }

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Execute gagal: ' . mysqli_stmt_error($stmt));
    }

    $insertId = mysqli_insert_id($connection);

    echo json_encode([
        'status' => 'success',
        'message' => 'History tersimpan',
        'id' => $insertId
    ]);

} catch (Exception $e) {
    error_log("save_actuator.php ERROR: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($stmt)) mysqli_stmt_close($stmt);
    if (isset($connection)) mysqli_close($connection);
}
?>