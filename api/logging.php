<?php
include "../config/database.php";

// Aktifkan log error ke file di folder yang sama
ini_set("log_errors", 1);
ini_set("error_log", __DIR__ . "/webhook_error_log.txt");  // log error PHP & MySQL
error_reporting(E_ALL);

// Simpan log mentah dari EMQX (request body)
file_put_contents(__DIR__ . "/webhook_log.txt", date("Y-m-d H:i:s") . " | RAW: " . file_get_contents('php://input') . "\n", FILE_APPEND);

// Ambil isi JSON dari webhook EMQX
$rawInput = file_get_contents('php://input');
$webhookResponse = json_decode($rawInput, true);

if (!$webhookResponse) {
    error_log("Webhook tidak menerima data JSON: " . $rawInput);
    exit;
}

$topic = $webhookResponse["topic"] ?? '';
$payload = $webhookResponse["payload"] ?? '';

if (strpos($topic, "SmIr/data") !== false) {

    // Handle payload string atau object
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

        // Query: sertakan kolom rssi
        $sqls = [
            "INSERT INTO data (node, sensor_actuator, value, name, mqtt_topic, rssi)
             VALUES ('$node', 'sensor', '$tegangan', 'tegangan', '$topic', '$rssi')",
            "INSERT INTO data (node, sensor_actuator, value, name, mqtt_topic, rssi)
             VALUES ('$node', 'sensor', '$arus', 'arus', '$topic', '$rssi')",
            "INSERT INTO data (node, sensor_actuator, value, name, mqtt_topic, rssi)
             VALUES ('$node', 'sensor', '$waterlvA', 'waterlvA', '$topic', '$rssi')",
            "INSERT INTO data (node, sensor_actuator, value, name, mqtt_topic, rssi)
             VALUES ('$node', 'sensor', '$waterlvB', 'waterlvB', '$topic', '$rssi')",
            "INSERT INTO data (node, sensor_actuator, value, name, mqtt_topic, rssi)
             VALUES ('$node', 'sensor', '$flowrate', 'flowrate', '$topic', '$rssi')",
            "INSERT INTO data (node, sensor_actuator, value, name, mqtt_topic, rssi)
             VALUES ('$node', 'sensor', '$totalwater', 'totalwater', '$topic', '$rssi')",
            "INSERT INTO data (node, sensor_actuator, value, name, mqtt_topic, rssi)
             VALUES ('$node', 'sensor', '$rssi', 'rssi', '$topic', '$rssi')"
        ];

        foreach ($sqls as $sql) {
            if (!mysqli_query($connection, $sql)) {
                error_log("MySQL Error: " . mysqli_error($connection) . " | Query: " . $sql);
            }
        }

    } else {
        error_log("Payload JSON tidak valid atau tidak ada field 'Node': " . json_encode($payload));
    }
} else {
    error_log("Topik tidak sesuai: $topic");
}
?>
