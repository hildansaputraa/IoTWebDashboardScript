<?php
require_once __DIR__ . '/../vendor/autoload.php';
include __DIR__ . '/database.php';

use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;

$server = 'broker.emqx.io';
$port = 1883;
$clientId = 'php_gateway_' . uniqid();
$username = 'emqx_test';
$password = 'emqx_test';

$connectionSettings = (new ConnectionSettings)
    ->setUsername($username)
    ->setPassword($password)
    ->setKeepAliveInterval(60)
    ->setUseTls(false);

$mqtt = new MqttClient($server, $port, $clientId);

try {
    $mqtt->connect($connectionSettings, true);
    echo "Terhubung ke broker MQTT...\n";
} catch (Exception $e) {
    echo "Gagal konek ke broker: " . $e->getMessage() . "\n";
    exit(1);
}

$mqtt->subscribe('SmIr/data', function ($topic, $message) use ($connection) {
    echo "Pesan diterima di $topic: $message\n";

    $data = json_decode($message, true);

    // Validasi struktur data
    if (!is_array($data) || !isset($data['Node'])) {
        echo "⚠️  Data tidak valid, abaikan.\n";
        return;
    }

    // Simpan node sebagai angka murni (tanpa "Node")
    $node = intval($data["Node"]);

    // Escape untuk keamanan SQL
    $node = mysqli_real_escape_string($connection, $node);
    $rssi = isset($data["rssi"]) ? floatval($data["rssi"]) : 0;

    // Daftar sensor
    $sensorList = [
        'tegangan',
        'arus',
        'waterlvA',
        'waterlvB',
        'flowrate',
        'totalwater',
        'rssi'
    ];

    foreach ($sensorList as $sensor) {
        if (!isset($data[$sensor])) {
            echo "⚠️  Data $sensor tidak ditemukan di Node {$data['Node']}\n";
            continue;
        }

        $value = floatval($data[$sensor]);
        $value = mysqli_real_escape_string($connection, $value);

        $sql = "INSERT INTO data (node, sensor_actuator, name, value, rssi, mqtt_topic)
                VALUES ('$node', 'sensor', '$sensor', '$value', '$rssi', '$topic')";

        if (!mysqli_query($connection, $sql)) {
            echo "❌ MySQL Error: " . mysqli_error($connection) . "\n";
        }
    }

    echo "✅ Data Node {$data['Node']} disimpan ke database sebagai Node $node\n";

}, 0);

// Loop agar tetap mendengarkan pesan
$mqtt->loop(true);
