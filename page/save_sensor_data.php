<?php
include "../config/database.php";

$data = json_decode(file_get_contents("php://input"), true);

if ($data) {
    $node = $data['Node'];
    $tegangan = $data['tegangan'];
    $arus = $data['arus'];
    $sensor1 = $data['sensor1'];
    $sensor2 = $data['sensor2'];
    $sensor3 = $data['sensor3'];

    // Simpan ke tabel 'data' untuk tiap sensor
    $sql = "INSERT INTO data (serial_number, name, value, mqtt_topic, sensor_actuator)
            VALUES 
            ('$node', 'Tegangan', '$tegangan', 'SmIr/data', 'sensor'),
            ('$node', 'Arus', '$arus', 'SmIr/data', 'sensor'),
            ('$node', 'Sensor1', '$sensor1', 'SmIr/data', 'sensor'),
            ('$node', 'Sensor2', '$sensor2', 'SmIr/data', 'sensor'),
            ('$node', 'Sensor3', '$sensor3', 'SmIr/data', 'sensor')";

    if (mysqli_query($connection, $sql)) {
        echo "OK";
    } else {
        echo "Error: " . mysqli_error($connection);
    }
}
?>
