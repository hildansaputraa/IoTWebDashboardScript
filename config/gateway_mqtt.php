<?php
// gateway_mqtt.php (versi lengkap dan direvisi)
// Tujuan: subscribe SmIr/data, simpan payload ke DB, lebih tahan banting terhadap disconnect, logging, dan retry.

// Pastikan composer autoload dan file database.php path sesuai dengan struktur proyekmu
require_once __DIR__ . '/../vendor/autoload.php';
include __DIR__ . '/database.php';

use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;

// tampilkan error selama debugging (boleh dimatikan di production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// lokasi log debug (file akan dibuat jika belum ada)
$logFile = __DIR__ . '/mqtt_debug.log';

// helper sederhana untuk append log dengan timestamp
function append_log($file, $text) {
    file_put_contents($file, "[".date('Y-m-d H:i:s')."] " . $text . PHP_EOL, FILE_APPEND);
}

// MQTT config
$server = 'broker.emqx.io';
$port = 1883;
$clientId = 'php_gateway_' . uniqid();
$username = 'emqx_test';
$password = 'emqx_test';

// connection settings
$connectionSettings = (new ConnectionSettings)
    ->setUsername($username)
    ->setPassword($password)
    ->setKeepAliveInterval(60)
    ->setUseTls(false);

// buat client MQTT
$mqtt = new MqttClient($server, $port, $clientId);

// connect ke broker
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

// daftar sensor yang akan disimpan (sesuaikan bila ada perubahan)
$sensorList = [
    'tegangan',
    'arus',
    'waterlvA',
    'waterlvB',
    'flowrate',
    'totalwater',
    'rssi'
];

// Fungsi subscribe — blok utama untuk menerima dan menyimpan data
$mqtt->subscribe('SmIr/data', function ($topic, $message) use (&$connection, $logFile, $sensorList) {

    // helper local untuk memastikan koneksi DB hidup; akan mencoba include ulang database.php bila perlu
    $ensureDb = function() use (&$connection, $logFile) {
        // cek apakah $connection adalah mysqli object / resource
        if (!isset($connection) || !($connection instanceof mysqli)) {
            append_log($logFile, "DB connection tidak valid — mencoba include ulang database.php");
            @include __DIR__ . '/database.php';
            if (!isset($connection) || !($connection instanceof mysqli)) {
                append_log($logFile, "Gagal include ulang database.php — connection masih tidak valid");
                return false;
            }
        }

        // coba ping
        if (!@mysqli_ping($connection)) {
            append_log($logFile, "DB ping gagal — mencoba include ulang database.php untuk reconnect");
            @include __DIR__ . '/database.php';
            // cek lagi
            if (!@mysqli_ping($connection)) {
                append_log($logFile, "DB reconnect attempt failed: " . mysqli_error($connection));
                return false;
            } else {
                append_log($logFile, "DB reconnect success");
            }
        }
        return true;
    };

    append_log($logFile, "Received on $topic: $message");
    echo "Pesan diterima di $topic: $message" . PHP_EOL;

    $data = json_decode($message, true);
    if (!is_array($data) || !isset($data['Node'])) {
        $err = "⚠️  Data tidak valid atau field 'Node' tidak ditemukan. Message diabaikan.";
        echo $err . PHP_EOL;
        append_log($logFile, $err);
        return;
    }

    // pastikan DB siap sebelum insert
    if (!$ensureDb()) {
        $err = "❌ DB tidak tersedia / reconnect gagal — data akan diabaikan untuk saat ini.";
        echo $err . PHP_EOL;
        append_log($logFile, $err);
        return;
    }

    // gunakan angka murni untuk node (kemudian konversi ke string untuk binding)
    $node = intval($data['Node']);
    $nodeStr = (string)$node;

    // ambil rssi jika ada, validasi dasar
    $rssi = isset($data['rssi']) && is_numeric($data['rssi']) ? floatval($data['rssi']) : 0.0;

    // gunakan prepared statement (lebih aman)
    $stmt = mysqli_prepare($connection,
        "INSERT INTO data (node, sensor_actuator, name, value, rssi, mqtt_topic, created_at)
         VALUES (?, 'sensor', ?, ?, ?, ?, NOW())"
    );

    if ($stmt === false) {
        $err = "❌ Prepare failed: " . mysqli_error($connection);
        echo $err . PHP_EOL;
        append_log($logFile, $err . " | topic=" . mysqli_real_escape_string($connection, $topic));
        return;
    }

    // loop setiap sensor dan insert jika field tersedia
    foreach ($sensorList as $sensor) {
        if (!array_key_exists($sensor, $data)) {
            $msg = "⚠️  Field '$sensor' tidak ditemukan pada Node {$data['Node']} — dilewati.";
            echo $msg . PHP_EOL;
            append_log($logFile, $msg);
            continue;
        }

        $value = floatval($data[$sensor]);
        // bind parameter: node (s), name (s), value (d), rssi (d), topic (s)
        // menggunakan "ssdds" : s=string, s=string, d=double, d=double, s=string
        mysqli_stmt_bind_param($stmt, "ssdds", $nodeStr, $sensor, $value, $rssi, $topic);

        $exec = mysqli_stmt_execute($stmt);

        if ($exec === false) {
            $err = "❌ MySQL Error (node={$nodeStr}, sensor={$sensor}): " . mysqli_stmt_error($stmt);
            echo $err . PHP_EOL;
            append_log($logFile, $err . " | values: node={$nodeStr}, sensor={$sensor}, value={$value}, rssi={$rssi}, topic=" . mysqli_real_escape_string($connection, $topic));

            // coba reconnect dan retry 1x
            append_log($logFile, "Mencoba reconnect DB & retry insert...");
            @include __DIR__ . '/database.php';
            if (@mysqli_ping($connection)) {
                // buat statement retry
                $stmtRetry = mysqli_prepare($connection,
                    "INSERT INTO data (node, sensor_actuator, name, value, rssi, mqtt_topic, created_at)
                     VALUES (?, 'sensor', ?, ?, ?, ?, NOW())"
                );
                if ($stmtRetry !== false) {
                    mysqli_stmt_bind_param($stmtRetry, "ssdds", $nodeStr, $sensor, $value, $rssi, $topic);
                    $execRetry = mysqli_stmt_execute($stmtRetry);
                    if ($execRetry !== false) {
                        $ok = "✅ Retry insert sukses (node={$nodeStr}, sensor={$sensor}, value={$value})";
                        echo $ok . PHP_EOL;
                        append_log($logFile, $ok);
                        mysqli_stmt_close($stmtRetry);
                        continue;
                    } else {
                        $err2 = "❌ Retry failed: " . mysqli_stmt_error($stmtRetry);
                        echo $err2 . PHP_EOL;
                        append_log($logFile, $err2);
                        mysqli_stmt_close($stmtRetry);
                    }
                } else {
                    append_log($logFile, "Retry prepare failed: " . mysqli_error($connection));
                }
            } else {
                append_log($logFile, "Retry DB ping gagal: " . (isset($connection) ? mysqli_error($connection) : "no connection object"));
            }
        } else {
            // pastikan affected rows > 0
            $affected = mysqli_stmt_affected_rows($stmt);
            if ($affected > 0) {
                $ok = "✅ Insert sukses (node={$nodeStr}, sensor={$sensor}, value={$value})";
                echo $ok . PHP_EOL;
                append_log($logFile, $ok);
            } else {
                $warn = "⚠️  Insert execute sukses tetapi affected_rows={$affected} (node={$nodeStr}, sensor={$sensor})";
                echo $warn . PHP_EOL;
                append_log($logFile, $warn);
            }
        }
    }

    mysqli_stmt_close($stmt);

    $done = "✅ Data Node {$data['Node']} diproses (node={$nodeStr})";
    echo $done . PHP_EOL;
    append_log($logFile, $done);

}, 0);

// loop terus menerus
$mqtt->loop(true);
