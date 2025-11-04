<?php
// page/save_actuator.php - FINAL FIX NULL BINDING

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'POST only']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

// === INCLUDE DATABASE ===
$databasePath = __DIR__ . '/../config/database.php';
if (!file_exists($databasePath)) {
    error_log("save_actuator.php: Config tidak ditemukan: $databasePath");
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Config DB tidak ditemukan']);
    exit;
}
require_once $databasePath;

if (!$connection || mysqli_connect_error()) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'DB connection failed']);
    exit;
}

// === AMBIL INPUT ===
$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !isset($input['type'], $input['solenoid'], $input['data'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid input']);
    exit;
}

$type = $input['type'];
$solenoid = (int)$input['solenoid'];
$data = $input['data'];

if (!in_array($solenoid, [1, 2]) || !in_array($type, ['manual', 'auto'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid solenoid or mode']);
    exit;
}

$mode = $type;
$state = null;
$action = null;
$threshold_data = null;

if ($type === 'manual') {
    $state = ($solenoid == 1) ? ($data['solenoidSatu'] ?? 0) : ($data['solenoidDua'] ?? 0);
    $state = (int)$state;
} else {
    $action = in_array($data['action'] ?? '', ['on', 'off']) ? $data['action'] : 'off';
    
    $thresholds = [];
    for ($i = 1; $i <= 4; $i++) {
        $a = $data["waterlvA$i"] ?? null;
        $b = $data["waterlvB$i"] ?? null;
        if ($a !== null && $b !== null && !is_nan((float)$a) && !is_nan((float)$b)) {
            $thresholds["waterlvA$i"] = (float)$a;
            $thresholds["waterlvB$i"] = (float)$b;
        }
    }
    $threshold_data = !empty($thresholds) ? json_encode($thresholds, JSON_UNESCAPED_UNICODE) : null;
}

// === INSERT DENGAN PENANGANAN NULL ===
$sql = "INSERT INTO actuator_history 
        (solenoid_num, mode, state, action, threshold_data, created_at) 
        VALUES (?, ?, ?, ?, ?, NOW())";

$stmt = mysqli_prepare($connection, $sql);
if (!$stmt) {
    error_log("Prepare failed: " . mysqli_error($connection));
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'DB prepare error']);
    exit;
}

// === GUNAKAN NULL BINDING YANG BENAR ===
$stateParam = $state;
$actionParam = $action;
$thresholdParam = $threshold_data;

if (!mysqli_stmt_bind_param(
    $stmt, 
    "isiss", 
    $solenoid, 
    $mode, 
    $stateParam, 
    $actionParam, 
    $thresholdParam
)) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Bind failed']);
    exit;
}

// === EXECUTE ===
if (!mysqli_stmt_execute($stmt)) {
    $err = mysqli_stmt_error($stmt);
    error_log("Execute failed: $err | Input: " . json_encode($input));
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Execute failed: ' . $err]);
    exit;
}

echo json_encode([
    'status' => 'success',
    'message' => 'History saved',
    'id' => mysqli_insert_id($connection)
]);

mysqli_stmt_close($stmt);
mysqli_close($connection);
?>