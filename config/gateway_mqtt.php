<?php
// gateway_mqtt.php (revisi)
// Pastikan file ini berada di direktori yang sama dengan database.php (include path sesuai)
require_once __DIR__ . '/../vendor/autoload.php';
include __DIR__ . '/database.php';

use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;

// tampilkan semua error selama debugging (opsional)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// lokasi log debug
$logFile = __DIR__ . '/mqtt_debug.log';

// helper logging
function append_log($file, $text) {
    file_put_contents($file, "[".date('Y-m-d H:i:s')."] " . $text . PHP_EOL, FILE_APPEND);
}

// MQTT config
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
    $msg = "✔ Terhubung ke broker MQTT...";
    echo $msg . PHP_EOL;
    append_log($logFile, $msg);
} catch (Exception $e) {
    $err = "✖ Gagal konek ke broker: " . $e->getMessage();
    echo $err . PHP_EOL;
    append_log($logFile, $err);
    exit(1);
}

// daftar sensor yang akan disimpan (urutan tidak penting)
$sensorList = [
    'tegangan',
    'arus',
    'waterlvA',
    'waterlvB',
    'flowrate',
    'totalwater',
    'rssi'
];

$mqtt->subscribe('SmIr/data', function ($topic, $message) use ($connection, $logFile, $sensorList) {
    // log raw message
    append_log($logFile, "Received on $topic: $message");
    echo "Pesan diterima di $topic: $message" . PHP_EOL;

    $data = json_decode($message, true);
    if (!is_array($data) || !isset($data['Node'])) {
        $err = "⚠️  Data tidak valid atau field 'Node' tidak ditemukan. Message diabaikan.";
        echo $err . PHP_EOL;
        append_log($logFile, $err);
        return;
    }

    // gunakan angka murni untuk node
    $node = intval($data['Node']);
    $nodeStr = (string)$node; // untuk prepared statement binding jika perlu

    // ambil rssi jika ada
    $rssi = isset($data['rssi']) ? floatval($data['rssi']) : 0.0;

    // escape topic (untuk log & fallback)
    $topicEsc = mysqli_real_escape_string($connection, $topic);

    // siapkan prepared statement (lebih aman)
    $stmt = mysqli_prepare($connection, "INSERT INTO data (node, sensor_actuator, name, value, rssi, mqtt_topic, created_at) VALUES (?, 'sensor', ?, ?, ?, ?, NOW())");
    if ($stmt === false) {
        $err = "❌ Gagal prepare statement: " . mysqli_error($connection);
        echo $err . PHP_EOL;
        append_log($logFile, $err . " | Topic: $topicEsc");
        return;
    }

    // loop sensor list dan insert per sensor jika ada di payload
    foreach ($sensorList as $sensor) {
        if (!array_key_exists($sensor, $data)) {
            $msg = "⚠️  Field '$sensor' tidak ditemukan pada Node {$data['Node']} — dilewati.";
            echo $msg . PHP_EOL;
            append_log($logFile, $msg);
            continue;
        }

        $value = floatval($data[$sensor]);

        // bind params: node (s), name (s), value (d), rssi (d), topic (s)
        // Node disimpan sebagai string karena kolom node di DB adalah VARCHAR
        mysqli_stmt_bind_param($stmt, "ssdds", $nodeStr, $sensor, $value, $rssi, $topic);
        $exec = mysqli_stmt_execute($stmt);

        if ($exec === false) {
            $err = "❌ MySQL Error (node={$nodeStr}, sensor={$sensor}): " . mysqli_stmt_error($stmt);
            echo $err . PHP_EOL;
            append_log($logFile, $err . " | SQL bind values: node={$nodeStr}, sensor={$sensor}, value={$value}, rssi={$rssi}, topic={$topicEsc}");
        } else {
            $ok = "✅ Insert sukses (node={$nodeStr}, sensor={$sensor}, value={$value})";
            echo $ok . PHP_EOL;
            append_log($logFile, $ok);
        }
    }

    mysqli_stmt_close($stmt);

    $done = "✅ Data Node {$data['Node']} diproses (node={$nodeStr})";
    echo $done . PHP_EOL;
    append_log($logFile, $done);
}, 0);

// loop terus menerus
$mqtt->loop(true);
