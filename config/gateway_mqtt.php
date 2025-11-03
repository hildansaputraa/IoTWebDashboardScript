<?php
require_once __DIR__ . '/../vendor/autoload.php';
include __DIR__ . '/database.php';

use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;

// Konfigurasi broker MQTT
$server = 'broker.emqx.io';
$port = 1883; // gunakan 8883 jika TLS diaktifkan
$clientId = 'php_gateway_' . uniqid();
$username = 'emqx_test';
$password = 'emqx_test';

// Pengaturan koneksi
$connectionSettings = (new ConnectionSettings)
    ->setUsername($username)
    ->setPassword($password)
    ->setKeepAliveInterval(60)
    ->setUseTls(false);

// Inisialisasi klien MQTT
$mqtt = new MqttClient($server, $port, $clientId);

// Coba koneksi ke broker
$mqtt->connect($connectionSettings, true);
echo "Terhubung ke broker MQTT...\n";

// Subscribe ke topik utama
$mqtt->subscribe('SmIr/data', function ($topic, $message) use ($connection) {
    echo "Pesan diterima di $topic: $message\n";

    $data = json_decode($message, true);
    if (!$data || !isset($data['Node'])) {
        echo "Data tidak valid atau field 'Node' tidak ditemukan.\n";
        return;
    }

    // Siapkan variabel dasar
    $node = "Node" . intval($data["Node"]);
    $rssi = isset($data["rssi"]) ? mysqli_real_escape_string($connection, $data["rssi"]) : 0;
    $topic = mysqli_real_escape_string($connection, $topic);

    // Loop setiap key di JSON (otomatis simpan semua field)
    foreach ($data as $key => $value) {
        if ($key === "Node") continue; // lewati field Node

        $keyEsc = mysqli_real_escape_string($connection, $key);
        $valEsc = mysqli_real_escape_string($connection, $value);

        $sql = "INSERT INTO data (node, sensor_actuator, name, value, mqtt_topic, rssi, created_at)
                VALUES ('$node', 'sensor', '$keyEsc', '$valEsc', '$topic', '$rssi', NOW())";

        if (!mysqli_query($connection, $sql)) {
            echo "MySQL Error (Node {$data['Node']}): " . mysqli_error($connection) . "\n";
        }
    }

    echo "Data Node {$data['Node']} disimpan ke database\n";
});

// Jalankan loop MQTT terus-menerus
$mqtt->loop(true);
