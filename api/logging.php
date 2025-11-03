<?php
include "../config/database.php";

// Simpan log mentah (sementara, bisa hapus kalau sudah jalan)
file_put_contents("webhook_log.txt", date("Y-m-d H:i:s") . " | RAW: " . file_get_contents('php://input') . "\n", FILE_APPEND);

// Decode JSON dari EMQX
$webhookResponse = json_decode(file_get_contents('php://input'), true);

if (!$webhookResponse) {
    error_log("Webhook tidak menerima data JSON");
    exit;
}

$topic = $webhookResponse["topic"] ?? '';
$payload = $webhookResponse["payload"] ?? '';

if (strpos($topic, "SmIr/data") !== false) {

    // Handle payload sebagai string atau object
    if (is_string($payload)) {
        $data = json_decode($payload, true);
    } else {
        $data = $payload;
    }

    if ($data && isset($data["Node"])) {

        $node = mysqli_real_escape_string($connection, "Node" . $data["Node"]);
        $tegangan = mysqli_real_escape_string($connection, $data["tegangan"]);
        $arus = mysqli_real_escape_string($connection, $data["arus"]);
        $waterlvA = mysqli_real_escape_string($connection, $data["waterlvA"]);
        $waterlvB = mysqli_real_escape_string($connection, $data["waterlvB"]);
        $flowrate = mysqli_real_escape_string($connection, $data["flowrate"]);
        $totalwater = mysqli_real_escape_string($connection, $data["totalwater"]);
        $rssi = mysqli_real_escape_string($connection, $data["rssi"]);

        $sqls = [
            "INSERT INTO data (node, sensor_actuator, value, name, mqtt_topic)
             VALUES ('$node', 'sensor', '$tegangan', 'tegangan', '$topic')",
            "INSERT INTO data (node, sensor_actuator, value, name, mqtt_topic)
             VALUES ('$node', 'sensor', '$arus', 'arus', '$topic')",
            "INSERT INTO data (node, sensor_actuator, value, name, mqtt_topic)
             VALUES ('$node', 'sensor', '$waterlvA', 'waterlvA', '$topic')",
            "INSERT INTO data (node, sensor_actuator, value, name, mqtt_topic)
             VALUES ('$node', 'sensor', '$waterlvB', 'waterlvB', '$topic')",
            "INSERT INTO data (node, sensor_actuator, value, name, mqtt_topic)
             VALUES ('$node', 'sensor', '$flowrate', 'flowrate', '$topic')",
            "INSERT INTO data (node, sensor_actuator, value, name, mqtt_topic)
             VALUES ('$node', 'sensor', '$totalwater', 'totalwater', '$topic')",
            "INSERT INTO data (node, sensor_actuator, value, name, mqtt_topic)
             VALUES ('$node', 'sensor', '$rssi', 'rssi', '$topic')"
        ];

        foreach ($sqls as $sql) {
            if (!mysqli_query($connection, $sql)) {
                error_log("MySQL Error: " . mysqli_error($connection) . " | Query: " . $sql);
            }
        }

    } else {
        error_log("Payload JSON tidak valid: " . json_encode($payload));
    }
} else {
    error_log("Topik tidak sesuai: $topic");
}
?>
