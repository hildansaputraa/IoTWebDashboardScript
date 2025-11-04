<?php
// page/save_actuator.php - FINAL VERSION (config di luar page/)

// === CEK METHOD POST ===
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Gunakan POST']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

// === 1. INCLUDE DATABASE (dari folder page/ ke config/) ===
$databasePath = __DIR__ . '/../config/database.php';  // KUNCI: ../config/

if (!file_exists($databasePath)) {
    error_log("save_actuator.php: File tidak ditemukan: $databasePath");
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Config database tidak ditemukan']);
    exit;
}

require_once $databasePath;

// === 2. CEK KONEKSI DB ===
if (!$connection || mysqli_connect_error()) {
    $msg = 'DB gagal: ' . mysqli_connect_error();
    error_log("save_actuator.php: $msg");
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $msg]);
    exit;
}

// === 3. AMBIL DATA JSON ===
$input = json_decode(file_get_contents('php://input'), true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['status' => 'error', 'message' => 'JSON invalid']);
    exit;
}

if (!$input || !isset($input['type'], $input['solenoid'], $input['data'])) {
    echo json_encode(['status' => 'error', 'message' => 'Data tidak lengkap']);
    exit;
}

$type = $input['type'];
$solenoid = (int)$input['solenoid'];
$data = $input['data'];

if (!in_array($solenoid, [1, 2])) {
    echo json_encode(['status' => 'error', 'message' => 'Solenoid invalid']);
    exit;
}

if (!in_array($type, ['manual', 'auto'])) {
    echo json_encode(['status' => 'error', 'message' => 'Mode invalid']);
    exit;
}

// === 4. PROSES DATA ===
$mode = $type;
$state = null;
$action = null;
$threshold_data = null;

if ($type === 'manual') {
    $state = ($solenoid == 1) ? ($data['solenoidSatu'] ?? 0) : ($data['solenoidDua'] ?? 0);
    $state = (int)$state;
} else {
    $action = in_array($data['action'] ?? '', ['on', 'off']) ? $data['action'] : 'off';
    
    $threshold_data = [];
    for ($i = 1; $i <= 4; $i++) {
        $a = $data["waterlvA$i"] ?? null;
        $b = $data["waterlvB$i"] ?? null;
        if ($a !== null && $b !== null) {
            $a = floatval($a);
            $b = floatval($b);
            if (!is_nan($a) && !is_nan($b)) {
                $threshold_data["waterlvA$i"] = $a;
                $threshold_data["waterlvB$i"] = $b;
            }
        }
    }
    $threshold_data = !empty($threshold_data) ? json_encode($threshold_data, JSON_UNESCAPED_UNICODE) : null;
}

// === 5. INSERT KE DB ===
$sql = "INSERT INTO actuator_history 
        (solenoid_num, mode, state, action, threshold_data, created_at) 
        VALUES (?, ?, ?, ?, ?, NOW())";

$stmt = mysqli_prepare($connection, $sql);
if (!$stmt) {
    $err = mysqli_error($connection);
    error_log("save_actuator.php: Prepare failed: $err");
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'DB Error']);
    exit;
}

$bind = mysqli_stmt_bind_param($stmt, "isiss", $solenoid, $mode, $state, $action, $threshold_data);
if (!$bind) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Bind failed']);
    exit;
}

if (!mysqli_stmt_execute($stmt)) {
    $err = mysqli_stmt_error($stmt);
    error_log("save_actuator.php: Execute failed: $err");
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Execute failed']);
    exit;
}

echo json_encode([
    'status' => 'success',
    'message' => 'History tersimpan',
    'id' => mysqli_insert_id($connection)
]);

// === TUTUP ===
mysqli_stmt_close($stmt);
mysqli_close($connection);
?>