<?php
require_once __DIR__ . '/vendor/autoload.php';
include __DIR__ . '/config/database.php';

use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;

$server = 'broker.emqx.io';
$port = 1883; // gunakan 8883 jika pakai TLS
$clientId = 'php_gateway_' . uniqid();
$username = 'emqx_test';
$password = 'emqx_test';

$connectionSettings = (new ConnectionSettings)
    ->setUsername($username)
    ->setPassword($password)
    ->setKeepAliveInterval(60)
    ->setUseTls(false);

$mqtt = new MqttClient($server, $port, $clientId);

$mqtt->connect($connectionSettings, true);

echo "Terhubung ke broker MQTT...\n";

// Subscribe ke topik data
$mqtt->subscribe('SmIr/data', function ($topic, $message) use ($connection, $mqtt) {
    echo "Pesan diterima di $topic: $message\n";

    $data = json_decode($message, true);

    if (!$data || !isset($data['Node'])) {
        echo "Data tidak valid\n";
        return;
    }

    $node = mysqli_real_escape_string($connection, "Node" . $data["Node"]);
    $tegangan = mysqli_real_escape_string($connection, $data["tegangan"]);
    $arus = mysqli_real_escape_string($connection, $data["arus"]);
    $waterlvA = mysqli_real_escape_string($connection, $data["waterlvA"]);
    $waterlvB = mysqli_real_escape_string($connection, $data["waterlvB"]);
    $flowrate = mysqli_real_escape_string($connection, $data["flowrate"]);
    $totalwater = mysqli_real_escape_string($connection, $data["totalwater"]);
    $rssi = mysqli_real_escape_string($connection, $data["rssi"]);

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
            echo "MySQL Error: " . mysqli_error($connection) . "\n";
        }
    }

    echo "Data Node {$data['Node']} disimpan ke database\n";
});

$mqtt->loop(true); // listen terus menerus
