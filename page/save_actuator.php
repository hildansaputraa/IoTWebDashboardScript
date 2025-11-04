<?php
// save_actuator.php
header('Content-Type: application/json');
require_once '/../config/database.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['type'], $input['solenoid'], $input['data'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid input']);
    exit;
}

$type = $input['type']; // 'manual' atau 'auto'
$solenoid = (int)$input['solenoid'];
$data = $input['data'];

if (!in_array($solenoid, [1, 2])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid solenoid']);
    exit;
}

// Persiapan data untuk DB
$mode = $type;
$state = null;
$action = null;
$threshold_data = null;

if ($type === 'manual') {
    // Manual: solenoidSatu / solenoidDua
    $state = ($solenoid == 1) ? ($data['solenoidSatu'] ?? 0) : ($data['solenoidDua'] ?? 0);
} else {
    // Auto: simpan threshold + action
    $action = $data['action'] ?? 'off';
    $threshold_data = [];
    for ($i = 1; $i <= 4; $i++) {
        $a = $data["waterlvA$i"] ?? null;
        $b = $data["waterlvB$i"] ?? null;
        if ($a !== null && $b !== null) {
            $threshold_data["waterlvA$i"] = (float)$a;
            $threshold_data["waterlvB$i"] = (float)$b;
        }
    }
    $threshold_data = !empty($threshold_data) ? json_encode($threshold_data) : null;
}

// Insert ke actuator_history
$sql = "INSERT INTO actuator_history 
        (solenoid_num, mode, state, action, threshold_data, created_at) 
        VALUES (?, ?, ?, ?, ?, NOW())";

$stmt = mysqli_prepare($connection, $sql);
mysqli_stmt_bind_param(
    $stmt, "isiss",
    $solenoid,
    $mode,
    $state,
    $action,
    $threshold_data
);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode(['status' => 'success', 'message' => 'History saved']);
} else {
    echo json_encode(['status' => 'error', 'message' => mysqli_error($connection)]);
}

mysqli_stmt_close($stmt);
mysqli_close($connection);
?>