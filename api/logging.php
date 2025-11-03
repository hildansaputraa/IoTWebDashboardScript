<?php
include "../config/database.php";

$webhookResponse = json_decode(file_get_contents('php://input'), true);
$topic = $webhookResponse["topic"];
$payload = $webhookResponse["payload"];

// Jika topik adalah SmIr/data dan payload berisi JSON
if ($topic == "SmIr/data") {
    $data = json_decode($payload, true);
    
    if ($data) {
        $node = $data["Node"];
        $tegangan = $data["tegangan"];
        $arus = $data["arus"];
        $waterlvA = $data["waterlvA"];
        $waterlvB = $data["waterlvB"];
        $flowrate = $data["flowrate"];
        $totalwater = $data["totalwater"];
        $rssi = $data["rssi"];

        // Simpan setiap nilai sensor ke tabel data
        $sqls = [
            "INSERT INTO data (serial_number, sensor_actuator, value, name, mqtt_topic)
             VALUES ('Node$node', 'sensor', '$tegangan', 'tegangan', '$topic')",
            "INSERT INTO data (serial_number, sensor_actuator, value, name, mqtt_topic)
             VALUES ('Node$node', 'sensor', '$arus', 'arus', '$topic')",
            "INSERT INTO data (serial_number, sensor_actuator, value, name, mqtt_topic)
             VALUES ('Node$node', 'sensor', '$waterlvA', 'waterlvA', '$topic')",
            "INSERT INTO data (serial_number, sensor_actuator, value, name, mqtt_topic)
             VALUES ('Node$node', 'sensor', '$waterlvB', 'waterlvB', '$topic')",
            "INSERT INTO data (serial_number, sensor_actuator, value, name, mqtt_topic)
             VALUES ('Node$node', 'sensor', '$flowrate', 'flowrate', '$topic')",
            "INSERT INTO data (serial_number, sensor_actuator, value, name, mqtt_topic)
             VALUES ('Node$node', 'sensor', '$totalwater', 'totalwater', '$topic')",
            "INSERT INTO data (serial_number, sensor_actuator, value, name, mqtt_topic)
             VALUES ('Node$node', 'sensor', '$rssi', 'rssi', '$topic')"
        ];

        foreach ($sqls as $sql) {
            mysqli_query($connection, $sql);
        }
    }
}
?>
